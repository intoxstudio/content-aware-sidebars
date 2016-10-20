<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
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

}

//