<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('CAS_App::PLUGIN_VERSION')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

abstract class CAS_Admin {

	/**
	 * Screen identifier
	 * @var string
	 */
	protected $_screen;

	public function __construct() {
		add_action('admin_menu',
			array($this,'add_menu'),99);
	}

	/**
	 * Set up screen and menu if necessary
	 *
	 * @since 3.4
	 */
	public function add_menu() {
		$this->_screen = $this->get_screen();
		add_action('load-'.$this->_screen,
			array($this,'prepare_screen'));
		add_action('load-'.$this->_screen,
			array($this,'add_scripts_styles'));
		add_action('load-'.$this->_screen,
			array($this,'prepare_upgrade_modal'));
	}

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
	public function prepare_upgrade_modal() {
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
		echo '<a style="display:none;" class="thickbox js-cas-pro-popup" href="#TB_inline?width=630&amp;height=230&amp;inlineId=pro-popup-notice" title="'.__('Buy Content Aware Sidebars Pro','content-aware-sidebars').'"></a>';
		echo '<div id="pro-popup-notice" style="display:none;">';
		echo '<img style="margin-top:15px;" class="alignright" src="'.plugins_url('../css/icon.png', __FILE__).'" width="128" height="128" />';
		echo '
		<h2>'.__('Get All Features With Content Aware Sidebars Pro','content-aware-sidebars').'</h2>';
		echo '<p>'.sprintf(__('Power up your sidebars with: %s and more.','content-aware-sidebars'),strtolower(implode(', ', $features))).'</p>';
		echo '<p>'.__('You can upgrade without leaving the admin panel by clicking below.','content-aware-sidebars');
		echo '<br />'.__('Free updates and email support included.','content-aware-sidebars').'</p>';
		echo '<p><a class="button-primary" target="_blank" href="'.esc_url(cas_fs()->get_upgrade_url()).'">'.__('Buy Now','content-aware-sidebars').'</a> <a href="" class="button-secondary js-cas-pro-read-more" target="_blank" href="">'.__('Read More','content-aware-sidebars').'</a></p>';
		echo '</div>';
	}

}

//