<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
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
	const PLUGIN_VERSION       = '3.1';

	/**
	 * Prefix for sidebar id
	 */
	const SIDEBAR_PREFIX       = 'ca-sidebar-';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR         = 'sidebar';

	/**
	 * Language domain
	 */
	const DOMAIN               = 'content-aware-sidebars';

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

		//__('Manage and show sidebars according to the content being viewed.',"content-aware-sidebars");
		//__('Content Aware Sidebars',"content-aware-sidebars");

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
			add_filter('plugin_action_links_'.plugin_basename(__FILE__),
				array($this,'plugin_action_links'), 10, 4 );
			add_filter('admin_footer_text',
				array($this,"admin_footer_text"),99);
		}
	}

	/**
	 * Load textdomain
	 *
	 * @since  3.0
	 * @return void 
	 */
	public function load_textdomain() {
		load_plugin_textdomain("content-aware-sidebars", false, dirname(plugin_basename(__FILE__)).'/lang/');
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
		$stars = "";
		for($i = 5; $i > 0; $i--) { $stars .= '<span class="dashicons dashicons-star-filled"></span>'; }
		if($screen->post_type == self::TYPE_SIDEBAR || $screen->id == "widgets") {
			$text .= " ".sprintf("Please support future development of %sContent Aware Sidebars%s with a %s%s review on WordPress.org%s",
				'<a target="_blank" href="http://www.intox.dk/plugin/content-aware-sidebars/">',
				'</a>',
				'<a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?filter=5#postform">',
				$stars,
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
			'<a href="http://www.intox.dk/en/plugin/content-aware-sidebars-en/faq/" target="_blank">'.__('FAQ',"content-aware-sidebars").'</a>'
		);

		return array_merge($new_actions,$actions);
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
				wp_enqueue_script('cas/admin/edit');
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
				'edit'           => $sidebar->labels->edit_item,
				'addNew'         => $sidebar->labels->add_new_item,
				'filterSidebars' => __("Filter Sidebars","content-aware-sidebars"),
				'filterWidgets'  => __("Filter Widgets", "content-aware-sidebars")
			));

		}

	}

}

//eol