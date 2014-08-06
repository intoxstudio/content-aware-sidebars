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
 * Static Pages Module
 * 
 * Detects if current content is:
 * a) front page
 * b) search results
 * c) 404 page
 *
 */
class CASModule_static extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('static',__('Static Pages',ContentAwareSidebars::DOMAIN));
		$this->type_display = false;
	}
	
	/**
	 * Get static content
	 * @param  array $args
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		$static = array(
			'front-page' => __('Front Page', ContentAwareSidebars::DOMAIN),
			'search'     => __('Search Results', ContentAwareSidebars::DOMAIN),
			'404'        => __('404 Page', ContentAwareSidebars::DOMAIN)
		);
		if(isset($args['include'])) {
			$static = array_intersect_key($static, array_flip($args['include']));
		}
		return $static;
	}

	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return is_front_page() || is_search() || is_404();
	}
	
	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		if(is_front_page()) {
			$val = 'front-page';
		} else if(is_search()) {
			$val = 'search';
		} else {
			$val = '404';
		}
		return array(
			$val
		);
	}

}
