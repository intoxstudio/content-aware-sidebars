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
 * Transposh Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_transposh extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('language',__('Languages',ContentAwareSidebars::DOMAIN));
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return true;
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		$data = array($this->id);
		if(function_exists('transposh_get_current_language')) {
			$data[] = transposh_get_current_language();
		}
		return $data;
	}

	/**
	 * Get content for sidebar editor
	 * @global object $my_transposh_plugin
	 * @param  array $args
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		global $my_transposh_plugin;
		$langs = array();

		/**
		 * isset($my_transposh_plugin->options->viewable_languages)
		 * returns false because transposh dev has not implemented __isset
		 * using get_option instead for robustness
		 */

		if(defined('TRANSPOSH_OPTIONS') && method_exists('transposh_consts', 'get_language_orig_name')) {
			$options = get_option(TRANSPOSH_OPTIONS);

			if(isset($options['viewable_languages'])) {
				foreach(explode(',',$options['viewable_languages']) as $lng) {
					$langs[$lng] = transposh_consts::get_language_orig_name($lng);
				}
			}
		}

		if(isset($args['include'])) {
			$langs = array_intersect_key($langs,array_flip($args['include']));
		}
		return $langs;
	}
	
}
