<?php
/**
 * @package Content Aware Sidebars
 * @copyright Joachim Jensen <jv@intox.dk>
 */

if (!defined('ContentAwareSidebars::DB_VERSION')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 *
 * Date Module
 * 
 * Detects if current content is:
 * a) a date archive
 *
 */
class CASModule_date extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'date',
			__('Dates',ContentAwareSidebars::DOMAIN),
			false
		);
	}

	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return is_date();
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.6
	 * @return array
	 */
	public function get_context_data() {
		global $wpdb;
		return $wpdb->prepare(
			"(date.meta_value IS NULL OR '%s' = date.meta_value)",
			"0000-00-00"
		);
	}

	/**
	 * Get content
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.6
	 * @param   array     $args
	 * @return  array
	 */
	protected function _get_content($args = array()) {
		$data = array(
			'0000-00-00' => __('Date Archives', ContentAwareSidebars::DOMAIN)
		);
		if(isset($args['include'])) {
			$data = array_intersect_key($data, array_flip($args['include']));
		}
		return $data;

	}
	
}
