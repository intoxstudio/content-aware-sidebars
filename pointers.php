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
		if(!$this->_has_finished_tour()) {
			add_action('admin_enqueue_scripts',
				array(&$this,'load_admin_scripts'));
			add_action('wp_ajax_cas_finish_tour',
				array(&$this,'finish_tour'));
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
				__( "Get started", ContentAwareSidebars::DOMAIN ),
				wpautop(__( "You've just installed Content Aware Sidebars!\n\nIt gives you a lot of options, so this screen might look overwhelming at first. Don't worry.\n\nClick Start Tour to view a quick introduction and learn how to display a sidebar exactly where and when you want it to.", ContentAwareSidebars::DOMAIN ) )),
			'ref_id'    => '#titlediv',
			'position'  => array(
				'edge'      => 'top',
				'align'     => 'center'
			),
			'pointerWidth' => 450,
			'next' => __("Start Tour",ContentAwareSidebars::DOMAIN),
			'prev' => false,
			'dismiss' => __("Not now",ContentAwareSidebars::DOMAIN)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				__( 'Condition Groups', ContentAwareSidebars::DOMAIN ),
				wpautop(__( "To make a sidebar contextual, you start by creating a condition group.\n\nCondition groups are isolated from each other, meaning that a sidebar can have very different conditions.\n\nNegating a group means that the sidebar will be displayed on all but those conditions.", ContentAwareSidebars::DOMAIN ) )),
			'ref_id'    => '#cas-groups',
			'position'  => array(
				'edge'      => 'right',
				'align'     => 'top'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				__( 'Content and contexts', ContentAwareSidebars::DOMAIN ),
				wpautop(__( "Here you'll find all the content on your site that Content Aware Sidebars supports out of the box.\n\nSimply select the content you want the sidebar to be displayed with and add it to a group.\n\nYou can even combine associated content in one group; try selecting \"All Posts\", a Category and an Author. Awesome!", ContentAwareSidebars::DOMAIN ) )),
			'ref_id'    => '#cas-accordion',
			'position'  => array(
				'edge'      => 'left',
				'align'     => 'top'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				__( 'Options, options', ContentAwareSidebars::DOMAIN ),
				wpautop(__( "Should the sidebar be displayed on singular pages and/or archives?\n\nShould it merge with another sidebar or replace it? Maybe you want to insert it manually in your content with a shortcode.\n\nSchedule the sidebar just like you do with posts and pages, or make it private so that it is only visible for logged-in users.\n\n You are in control.", ContentAwareSidebars::DOMAIN ) )),
			'ref_id'    => '#cas-options',
			'position'  => array(
				'edge'      => 'right',
				'align'     => 'top'
			)
		);
		$pointers[] = array(
			'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
				__( 'Help and Support', ContentAwareSidebars::DOMAIN ),
				wpautop(__( "That's it! Now you can start creating sidebars and display them on your own conditions.\n\nIf you need more help, click on the \"Help\" tab here.", ContentAwareSidebars::DOMAIN ) )),
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
	 * @param  [type]  $hook
	 * @return void
	 */
	public function load_admin_scripts($hook) {

		$current_screen = get_current_screen();

		if($current_screen->post_type == ContentAwareSidebars::TYPE_SIDEBAR && $current_screen->base == 'post') {

				wp_enqueue_script('cas/pointers', plugins_url('/js/pointers.js', __FILE__), array('wp-pointer'), ContentAwareSidebars::PLUGIN_VERSION, true);
				wp_enqueue_style( 'wp-pointer' );

				wp_localize_script( 'cas/pointers', 'CASPointers', array(
					'pointers' => $this->_get_pointers(),
					'close' => __('Close',ContentAwareSidebars::DOMAIN),
					'next' => __('Next',ContentAwareSidebars::DOMAIN)
				));
		}
	}

}

//eol