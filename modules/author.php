<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

if (!defined('ContentAwareSidebars::DB_VERSION')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 *
 * Author Module
 * 
 * Detects if current content is:
 * a) post type written by any or specific author
 * b) any or specific author archive
 *
 */
class CASModule_author extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('authors',__('Authors',ContentAwareSidebars::DOMAIN));

		$this->searchable = true;
		$this->type_display = true;

		if(is_admin()) {
			add_action('wp_ajax_cas-autocomplete-'.$this->id, array(&$this,'ajax_content_search'));
		}
		
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return (is_singular() && !is_front_page()) || is_author();
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @global WP_Post $post
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		global $post;
		return array(
			$this->id,
			(string)(is_singular() ? $post->post_author : get_query_var('author'))
		);			
	}

	/**
	 * Get authors
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  
	 * @param  array     $args
	 * @return array
	 */
	protected function _get_content($args = array()) {

		$args['number'] = 20;
		$args['fields'] = array('ID','display_name');

		$user_query = new WP_User_Query(  $args );

		$author_list = array();
		if($user_query->results) {
			foreach($user_query->results as $user) {
				$author_list[$user->ID] = $user->display_name;
			}
		}
		return $author_list;
	}

	/**
	 * Get authors with AJAX search
	 * @global wpdb $wpdb
	 * @return void
	 */
	public function ajax_content_search() {
		global $wpdb;

		if(!isset($_POST['sidebar_id'])) {
			die(-1);
		}

		// Verify request
		check_ajax_referer(ContentAwareSidebars::SIDEBAR_PREFIX.$_POST['sidebar_id'],'nonce');
	
		$suggestions = array();

		$authors =$wpdb->get_results($wpdb->prepare("
			SELECT ID, display_name 
			FROM $wpdb->users 
			WHERE display_name 
			LIKE '%s' 
			ORDER BY display_name ASC 
			LIMIT 0,20
		", 
		'%'.$_REQUEST['q'].'%'));

		foreach($authors as $user) {
			$suggestions[] = array(
						'label'  => $user->display_name,
						'value'  => $user->ID,
						'id'     => $user->ID,
						'module' => $this->id,
						'name'   => 'cas_condition['.$this->id.']',
						'id2'    => $this->id,
						'elem'   => $this->id.'-'.$user->ID
					);
		}

		echo json_encode($suggestions);
		die();
	}
	
}
