<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
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
	const PLUGIN_VERSION       = '3.3.3';

	/**
	 * Prefix for sidebar id
	 */
	const SIDEBAR_PREFIX       = 'ca-sidebar-';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR         = 'sidebar';

	/**
	 * Capability to manage sidebars
	 */
	const CAPABILITY           = 'edit_theme_options';

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

		//__('Manage and show sidebars according to the content being viewed.','content-aware-sidebars');
		//__('Content Aware Sidebars','content-aware-sidebars');

		$this->_manager = new CAS_Sidebar_Manager();

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

		if(is_admin()) {
			add_action('plugins_loaded',
				array($this,'redirect_revision_link'));
			add_action('admin_enqueue_scripts',
				array($this,'load_admin_scripts'));
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
			if ( cas_fs()->is_not_paying() )  {
				add_filter('admin_footer_text',
					array($this,'admin_footer_text'),99);
			}
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
	 * Admin footer text on plugin specific pages
	 *
	 * @since  3.1
	 * @param  string  $text
	 * @return string
	 */
	public function admin_footer_text($text) {
		$screen = get_current_screen();
		if($screen->post_type == self::TYPE_SIDEBAR || $screen->id == 'widgets') {
			$text .= ' '.sprintf('Please support future development of %sContent Aware Sidebars%s with a %s%s review on WordPress.org%s',
				'<a target="_blank" href="http://www.intox.dk/plugin/content-aware-sidebars/">',
				'</a>',
				'<a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?filter=5#postform">',
				'5★',
				'</a>');
		}
		return $text;
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
			'<a href="https://dev.institute/wordpress/sidebars-pro/faq/?utm_source=plugin&utm_medium=referral&utm_content=plugin-list&utm_campaign=cas" target="_blank">'.__('FAQ','content-aware-sidebars').'</a>',
			'<a href="https://wordpress.org/support/plugin/content-aware-sidebars" target="_blank">'.__('Get Support','content-aware-sidebars').'</a>'
		);

		global $cas_fs;

		if ( $cas_fs->is_not_paying() )  {
			$new_actions[] = '<a href="'.$cas_fs->get_upgrade_url().'">'.__('Upgrade','content-aware-sidebars').'</a>';
		}

		return array_merge($new_actions,$actions);
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

	/**
	 * Load scripts and styles for administration
	 * @param  string $hook 
	 * @return void 
	 */
	public function load_admin_scripts($hook) {

		$current_screen = get_current_screen();

		if($current_screen->post_type == CAS_App::TYPE_SIDEBAR) {
			
			wp_register_script('cas/admin/edit', plugins_url('/js/cas_admin.min.js', __FILE__), array('jquery'), CAS_App::PLUGIN_VERSION, true);
			
			wp_register_style('cas/admin/style', plugins_url('/css/style.css', __FILE__), array(), CAS_App::PLUGIN_VERSION);

			//Sidebar editor
			if ($current_screen->base == 'post') {

				//Other plugins add buggy scripts
				//causing the screen to stop working
				//temporary as we move forward...
				$script_whitelist = array(
					'common',
					'admin-bar',
					'autosave',
					'post',
					'utils',
					'svg-painter',
					'wp-auth-check',
					'bp-confirm',
					'suggest',
					'heartbeat',
					'jquery',
					'yoast-seo-admin-global-script',
					'select2',
					'backbone',
					'backbone.trackit',
					'_ca_condition-groups',
				);
				global $wp_scripts;
				$script_whitelist = array_flip($script_whitelist);
				foreach ($wp_scripts->queue as $script) {
					if(!isset($script_whitelist[$script])) {
						wp_dequeue_script($script);
					}
				}

				$visibility = array();
				foreach ($this->_manager->metadata()->get('visibility')->get_input_list() as $k => $v) {
					$visibility[] = array(
						'id'   => $k,
						'text' => $v
					);
				}

				if(cas_fs()->is_not_paying()) {
					$visibility[] = array(
						'id' => 'pro',
						'text' => __('User Roles available in Pro','content-aware-sidebars'),
						'disabled' => true
					);
				}

				wp_enqueue_script('cas/admin/edit');
				wp_localize_script( 'cas/admin/edit', 'CASAdmin', array(
					'allVisibility'  => __('All Users','content-aware-sidebars'),
					'visibility' => $visibility
				));
				wp_enqueue_style('cas/admin/style');
			//Sidebar overview
			} else if ($hook == 'edit.php') {
				wp_enqueue_style('cas/admin/style');
			}			
		} else if($current_screen->base == 'widgets') {
			wp_register_style('cas/admin/style', plugins_url('/css/style.css', __FILE__), array(), CAS_App::PLUGIN_VERSION);
			wp_enqueue_style('cas/admin/style');

			$sidebar = get_post_type_object(CAS_App::TYPE_SIDEBAR);

			wp_register_script('cas/admin/widgets', plugins_url('/js/widgets.min.js', __FILE__), array('jquery'), CAS_App::PLUGIN_VERSION, true);
			wp_enqueue_script('cas/admin/widgets');
			wp_localize_script( 'cas/admin/widgets', 'CASAdmin', array(
				'addNew'         => $sidebar->labels->add_new_item,
				'collapse'       => __('Collapse','content-aware-sidebars'),
				'expand'         => __('Expand','content-aware-sidebars'),
				'filterSidebars' => __('Filter Sidebars','content-aware-sidebars'),
				'filterWidgets'  => __('Filter Widgets', 'content-aware-sidebars')
			));

		}

	}

}

//eol