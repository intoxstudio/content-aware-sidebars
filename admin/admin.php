<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	exit;
}

abstract class CAS_Admin {

	/**
	 * Screen identifier
	 * @var string
	 */
	protected $_screen;

	public function __construct() {
		if(is_admin()) {
			add_action('admin_menu',
				array($this,'add_menu'),99);
			$this->admin_hooks();
		} else {
			$this->frontend_hooks();
		}
	}

	/**
	 * Set up screen and menu if necessary
	 *
	 * @since 3.4
	 */
	public function add_menu() {
		$this->_screen = $this->get_screen();
		add_action('load-'.$this->_screen,
			array($this,'load_screen'));
	}

	/**
	 * Add filters and actions for admin dashboard
	 * e.g. AJAX calls
	 *
	 * @since  3.5
	 * @return void
	 */
	abstract public function admin_hooks();

	/**
	 * Add filters and actions for frontend
	 *
	 * @since  3.5
	 * @return void
	 */
	abstract public function frontend_hooks();

	/**
	 * Get current screen
	 *
	 * @since  3.4
	 * @return string
	 */
	abstract public function get_screen();

	/**
	 * Prepare screen load
	 *
	 * @since  3.4
	 * @return void
	 */
	abstract public function prepare_screen();

	/**
	 * Authorize user for screen
	 *
	 * @since  3.5
	 * @return boolean
	 */
	abstract public function authorize_user();

	/**
	 * Register and enqueue scripts styles
	 * for screen
	 *
	 * @since 3.4
	 */
	abstract public function add_scripts_styles();

	/**
	 * Prepare plugin upgrade modal
	 *
	 * @since  3.4.1
	 * @return void
	 */
	public function load_screen() {
		if(!$this->authorize_user()) {
			wp_die(
				'<p>' . __( 'You do not have access to this screen.', 'content-aware-sidebars' ) . '</p>',
				403
			);
		}
		$this->prepare_screen();
		add_action('admin_enqueue_scripts',
			array($this,'add_scripts_styles'),11);
		if ( cas_fs()->is_not_paying() ) {
			add_thickbox();
			//enqueue scripts here
			add_action('admin_footer',
				array($this,'render_upgrade_modal'));
		}
	}

	/**
	 * Render plugin upgrade modal
	 *
	 * @since  3.4.1
	 * @return void
	 */
	public function render_upgrade_modal() {
		$features = array(
			__('Extra condition types','content-aware-sidebars'),
			__('Widget Revisions','content-aware-sidebars'),
			__('Visibility for roles','content-aware-sidebars'),
			__('Time Schedule','content-aware-sidebars'),
			__('Sync widgets across themes','content-aware-sidebars')
		);
		echo '<a style="display:none;" class="thickbox js-cas-pro-popup" href="#TB_inline?width=600&amp;height=235&amp;inlineId=pro-popup-notice" title="'.__('Content Aware Sidebars Pro','content-aware-sidebars').'"></a>';
		echo '<div id="pro-popup-notice" style="display:none;">';
		echo '<img style="margin-top:15px;" class="alignright" src="'.plugins_url('../css/icon.png', __FILE__).'" width="128" height="128" />';
		echo '
		<h2>'.__('Get All Features With Content Aware Sidebars Pro','content-aware-sidebars').'</h2>';
		echo '<p>'.sprintf(__('Enhance your sidebars and widget areas with: %s and more.','content-aware-sidebars'),strtolower(implode(', ', $features))).'</p>';
		echo '<p>'.__('You can upgrade without leaving the admin panel by clicking below.','content-aware-sidebars');
		echo '<br />'.__('Free updates and email support included.','content-aware-sidebars').'</p>';
		echo '<p><a class="button-primary" target="_blank" href="'.esc_url(cas_fs()->get_upgrade_url()).'">'.__('Upgrade Now','content-aware-sidebars').'</a> <a href="" class="button-secondary js-cas-pro-read-more" target="_blank" href="">'.__('Read More','content-aware-sidebars').'</a></p>';
		echo '</div>';
	}

}

//