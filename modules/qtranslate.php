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
 * qTranslate Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_qtranslate extends CASModule {
	
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
		if(function_exists('qtrans_getLanguage')) {
			$data[] = qtrans_getLanguage();
		}
		return $data;
	}

	/**
	 * Get content for sidebar edit screen
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @global  array     $q_config
	 * @param   array     $args
	 * @return  array
	 */
	protected function _get_content($args = array()) {
		global $q_config;

		$langs = array();

		if(isset($q_config['language_name'])) {
			foreach((array)get_option('qtranslate_enabled_languages') as $lng) {
				if(isset($q_config['language_name'][$lng])) {
					$langs[$lng] = $q_config['language_name'][$lng];
				}
			}
		}

		if(isset($args['include'])) {
			$langs = array_intersect_key($langs,array_flip($args['include']));
		}
		return $langs;
	}
}
