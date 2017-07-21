<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

final class CAS_App {

	/**
	 * Plugin version
	 */
	const PLUGIN_VERSION       = '3.6.2';

	/**
	 * Prefix for sidebar id
	 */
	const SIDEBAR_PREFIX       = 'ca-sidebar-';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR         = 'sidebar';

	/**
	 * Sidebar statuses
	 */
	const STATUS_ACTIVE        = 'publish';
	const STATUS_INACTIVE      = 'draft';
	const STATUS_SCHEDULED     = 'future';

	/**
	 * Capability to manage sidebars
	 */
	const CAPABILITY           = 'edit_theme_options';

	/**
	 * Base admin screen name
	 */
	const BASE_SCREEN          = 'wpcas';

	/**
	 * Prefix for metadata keys
	 */
	const META_PREFIX          = '_ca_';

	private $manager;

	/**
	 * Class singleton
	 * @var CAS_App
	 */
	private static $_instance;

	/**
	 * Instantiates and returns class singleton
	 * 
	 * @return CAS_App 
	 */
	public static function instance() {
		if(!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		$this->_manager = new CAS_Sidebar_Manager();

		if(is_admin()) {
			new CAS_Sidebar_Overview();
			new CAS_Sidebar_Edit();
			new CAS_Post_Type_Sidebar();
			new CAS_Admin_Screen_Widgets();
		}

		$this->add_actions();
		$this->add_filters();

	}

	public function manager() {
		return $this->_manager;
	}

	/**
	 * Add actions to queues
	 *
	 * @since  3.1
	 * @return void
	 */
	protected function add_actions() {
		add_action('init',
			array($this,'load_textdomain'));
		add_action('admin_bar_menu',
			array($this,'admin_bar_menu'),99);
		add_action('cas/event/deactivate',
			array($this,'scheduled_deactivation'));

		if(is_admin()) {
			add_action('plugins_loaded',
				array($this,'redirect_revision_link'));
		}
	}

	/**
	 * Add filters to queues
	 *
	 * @since  3.1
	 * @return void
	 */
	protected function add_filters() {
		if(is_admin()) {
			$file = plugin_basename( plugin_dir_path( __FILE__ )).'/content-aware-sidebars.php';
			add_filter('plugin_action_links_'.$file,
				array($this,'plugin_action_links'), 10, 4 );
		}
	}

	/**
	 * Load textdomain
	 *
	 * @since  3.0
	 * @return void 
	 */
	public function load_textdomain() {
		load_plugin_textdomain('content-aware-sidebars', false, dirname(plugin_basename(__FILE__)).'/lang/');
	}

	/**
	 * Add admin bar link to create sidebars
	 *
	 * @since  3.4
	 * @param  [type]  $wp_admin_bar
	 * @return void
	 */
	public function admin_bar_menu($wp_admin_bar) {
		$post_type = get_post_type_object(self::TYPE_SIDEBAR);
		if (current_user_can( $post_type->cap->create_posts ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'    => 'new-content',
				'id'        => self::BASE_SCREEN,
				'title'     => $post_type->labels->singular_name,
				'href'      => admin_url( 'admin.php?page=wpcas-edit' )
			) );
		}
		
	}

	/**
	 * Add actions to plugin in Plugins screen
	 * @version 2.4
	 * @param   array     $actions
	 * @param   string    $plugin_file
	 * @param   [type]    $plugin_data
	 * @param   [type]    $context
	 * @return  array
	 */
	public function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {

		$new_actions = array(
			'<a href="https://wordpress.org/support/plugin/content-aware-sidebars" target="_blank">'.__('Get Support','content-aware-sidebars').'</a>'
		);

		global $cas_fs;

		if ( $cas_fs->is_not_paying() )  {
			$new_actions[] = '<a href="'.$cas_fs->get_upgrade_url().'">'.__('Upgrade','content-aware-sidebars').'</a>';
		}

		return array_merge($new_actions,$actions);
	}

	/**
	 * Callback for scheduled deactivation
	 *
	 * @since  3.4
	 * @param  int   $post_id
	 * @return void
	 */
	public function scheduled_deactivation($post_id) {
		$success = wp_update_post(array(
			'ID'          => $post_id,
			'post_status' => self::STATUS_INACTIVE
		));
		if($success) {
			delete_post_meta($post_id, self::META_PREFIX.'deactivate_time');
		}
	}

	/**
	 * Redirect revision link to upgrade
	 *
	 * @since  3.2
	 * @return void
	 */
	public function redirect_revision_link() {
		global $pagenow;
		if($pagenow == 'post.php' 
			&& isset($_GET['action'],$_GET['post']) 
			&& $_GET['action'] == 'cas-revisions') {
			wp_safe_redirect(cas_fs()->get_upgrade_url());
			exit;
		}
	}

}

//eol