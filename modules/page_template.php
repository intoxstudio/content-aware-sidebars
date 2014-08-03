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
 * Page Template Module
 * 
 * Detects if current content has:
 * a) any or specific page template
 *
 *
 */
class CASModule_page_template extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('page_templates',__('Page Templates',ContentAwareSidebars::DOMAIN));

		$this->type_display = true;
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		if(is_singular() && !('page' == get_option( 'show_on_front') && get_option('page_on_front') == get_the_ID())) {
			$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
			return ($template && $template != 'default');
		}
		return false;
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		return array(
			$this->id,
			get_post_meta(get_the_ID(),'_wp_page_template',true)
		);
	}

	/**
	 * Get page templates
	 * @param  array $args
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		$templates = array_flip(get_page_templates());
		if(isset($args['include'])) {
			$templates = array_intersect_key($templates,array_flip($args['include']));
		}
		return $templates;
	}
	
}
