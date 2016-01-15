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
				__( "Get started", "content-aware-sidebars" ),
				wpautop(__( "You've just installed or updated Content Aware Sidebars!\n\nThe UI has been completely rewritten to make it easier for you to create a sidebar in no time!\n\nClick Start Quick Tour to get a 3 Step Introduction and learn how to display a sidebar exactly where and when you want it to.", "content-aware-sidebars" ) )),
			'ref_id'    => '#titlediv',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			),
			'pointerWidth' => 450,
			'next' => __("Start Quick Tour","content-aware-sidebars"),
			'prev' => false,
			'dismiss' => __("Not now","content-aware-sidebars")
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				"1. ".__( 'Select Content Type', "content-aware-sidebars" ),
				wpautop(__( "With this dropdown you can create condition groups that determines where the sidebar should be displayed.\n\nContent Aware Sidebars has built-in support for many types of content and even other plugins!\n\nSelect something to continue the tour. You can change it later.", "content-aware-sidebars" ) )),
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
				wpautop(__( "Click on the input field and select the content you want.\n\nContent you add to this group will be isolated from other groups, and if you add other types to this group, you will target the context.\n\n Adding e.g. \"All Posts\" and an Author will target all posts written by that author. Awesome!\n\nRemember to save the changes on each group.", "content-aware-sidebars" ) )),
			'ref_id'    => '#cas-groups > ul',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				"3. ".__( 'Options, options', "content-aware-sidebars" ),
				wpautop(__( "Should the sidebar be displayed on singular pages and/or archives?\n\nShould it merge with another sidebar or replace it? Maybe you want to insert it manually in your content with a shortcode.\n\nSchedule the sidebar just like you do with posts and pages, or make it private so that it is only visible for logged-in users.\n\n You are in control.", "content-aware-sidebars" ) )),
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
			'next' => false
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