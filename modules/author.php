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
		parent::__construct('authors',__('Authors',ContentAwareSidebars::DOMAIN),true);
		$this->searchable = true;
		$this->type_display = true;
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

		if(isset($args['search'])) {
			add_filter( 'user_search_columns', array(&$this,'filter_search_column'), 10, 3 );
		}

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
	 * Get content in HTML
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.5
	 * @param   array    $args
	 * @return  string
	 */
	public function ajax_get_content($args) {
		$args = wp_parse_args($args, array(
			'item_object'    => '',
			'paged'          => 1,
			'search'         => ''
		));

		//display_name does not seem to be recognized, add it anyway
		$posts = $this->_get_content(array(
			'orderby'   => 'title',
			'order'     => 'ASC',
			'offset'    => ($args['paged']-1)*20,
			'search'    => '*'.$args['search'].'*',
			'search_columns' => array( 'user_nicename', 'user_login', 'display_name' )
		));

		return $this->_get_checkboxes($posts, empty($args['search']));

	}

	/**
	 * Filter to definitely add display_name to search_columns
	 * WP3.6+
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.5
	 * @param   array      $search_columns
	 * @param   string     $search
	 * @param   WP_User    $user
	 * @return  array
	 */
	function filter_search_column($search_columns, $search, $user) {
		$search_columns[] = 'display_name';
		return $search_columns;
	}

}
