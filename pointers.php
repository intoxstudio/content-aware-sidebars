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

/**
 * Pointer Manager for
 * introduction tour
 */
final class CASPointerManager {

	const KEY_TOUR = "cas_tour";

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_loaded',
				array($this,'initiate_tour'));
	}

	public function initiate_tour() {
		if(!$this->_has_finished_tour()) {
			add_action('admin_enqueue_scripts',
				array($this,'load_admin_scripts'));
			add_action('wp_ajax_cas_finish_tour',
				array($this,'finish_tour'));
		}
	}

	/**
	 * Has user finished tour
	 *
	 * @since  3.0
	 * @return boolean
	 */
	private function _has_finished_tour() {
		return get_user_option(WPCACore::PREFIX.self::KEY_TOUR) !== false;
	}

	/**
	 * Set finish flag for user
	 *
	 * @since  3.0
	 * @return void
	 */
	public function finish_tour() {

		// Verify nonce
		//if (!check_admin_referer(WPCACore::PREFIX.$post_id, WPCACore::NONCE))
		//	return;

		echo json_encode(update_user_option(get_current_user_id(),WPCACore::PREFIX.self::KEY_TOUR, time()));
		die();
	}

	/**
	 * Get pointer data for tour
	 *
	 * @since  3.0
	 * @return array
	 */
	private function _get_pointers() {
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3>%s',
				__( "Get Started in 3 Easy Steps", "content-aware-sidebars" ),
				wpautop(__( "You've just installed or updated Content Aware Sidebars. Awesome!\n\nYou can display sidebars on any page or in any context. If that is new to you, this 3 step interactive guide will show you just how easy it is.", "content-aware-sidebars" ) )),
			'ref_id'    => '#titlediv',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			),
			'pointerWidth' => 400,
			'next' => __("Start Quick Tour","content-aware-sidebars"),
			'prev' => false,
			'dismiss' => __("I know how to use it","content-aware-sidebars")
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				"1. ".__( 'Select Content Type', "content-aware-sidebars" ),
				wpautop(__( "With this dropdown you can select on what conditions the sidebar should be displayed.\n\nContent Aware Sidebars has built-in support for many types of content and even other plugins!\n\nSelect something to continue the tour. You can change it later.", "content-aware-sidebars" ) )),
			'ref_id'    => '.cas-group-new',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			),
			'next' => ".js-wpca-add-or",
			'nextEvent' => "change"
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				"2. ".__( 'Condition Groups', "content-aware-sidebars" ),
				wpautop(__( "Click on the input field and select the content you want.\n\nIf you can't find the right content in the list, type something to search.\n\n You can add several types of content to the same group, try e.g. \"All Posts\" and an Author to target all posts written by that author. Awesome!\n\nRemember to save the changes on each group.", "content-aware-sidebars" ) )),
			'ref_id'    => '#cas-groups > ul',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				"3. ".__( 'Options, options', "content-aware-sidebars" ),
				wpautop(__( "Should the sidebar be displayed on singular pages and/or archives?\n\nShould it merge with another sidebar or replace it? Maybe you want to insert it manually in your content with a shortcode.\n\nSchedule the sidebar just like you do with posts and pages, or make it visible only for logged-in users.\n\n You are in control.", "content-aware-sidebars" ) )),
			'ref_id'    => '#cas-options',
			'position'  => array(
				'edge'      => 'right',
				'align'     => 'top'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				__( 'Help and Support', "content-aware-sidebars" ),
				wpautop(__( "That's it! Now you can start creating sidebars and display them on your own conditions.\n\nIf you need more help, click on the \"Help\" tab here.", "content-aware-sidebars" ) )),
			'ref_id'    => '#contextual-help-link-wrap',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'right'
			),
			'next' => false,
			'dismiss' => __("Finish Tour","content-aware-sidebars")
		);
		return $pointers;
	}

	/**
	 * Load scripts and styles
	 *
	 * @since  3.0
	 * @param  string  $hook
	 * @return void
	 */
	public function load_admin_scripts($hook) {

		$current_screen = get_current_screen();

		if($current_screen->post_type == CAS_App::TYPE_SIDEBAR && $current_screen->base == 'post') {

				wp_enqueue_script('cas/pointers', plugins_url('/js/pointers.js', __FILE__), array('wp-pointer'), CAS_App::PLUGIN_VERSION, true);
				wp_enqueue_style( 'wp-pointer' );

				wp_localize_script( 'cas/pointers', 'CASPointers', array(
					'pointers' => $this->_get_pointers(),
					'close' => __('Close',"content-aware-sidebars"),
					'next' => __('Next',"content-aware-sidebars")
				));
		}
	}

}

//eol