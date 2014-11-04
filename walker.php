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
 * Walker for post types and taxonomies
 *
 */
class CAS_Walker_Checklist extends Walker {
	
	/**
	 * Constructor
	 * @param string $tree_type 
	 * @param array  $db_fields 
	 */
	function __construct($tree_type, $db_fields) {
		
		$this->tree_type = $tree_type;
		$this->db_fields = $db_fields;
		
	}
	
	/**
	 * Start outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "</li>$indent<li><ul class='children'>\n";
	}
	
	/**
	 * End outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function end_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul></li>\n";
	}
	
	/**
	 * Start outputting element
	 * @param  string $output 
	 * @param  object $term   
	 * @param  int    $depth  
	 * @param  array  $args 
	 * @param  int 	  $current_object_id
	 * @return void
	 */
	public function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		extract($args);		
		
		if(isset($post_type)) {

			if (empty($post_type)) {
				$output .= "\n<li>";
				return;
			}

			$value = $term->ID;
			$title = $term->post_title;
			$name = 'cas_condition[post_types][]';
			
		} else {

			if (empty($taxonomy)) {
				$output .= "\n<li>";
				return;
			}

			//Hierarchical taxonomies use ids instead of slugs
			//see http://codex.wordpress.org/Function_Reference/wp_set_post_terms
			$value_var = ($taxonomy->hierarchical ? 'term_id' : 'slug');

			$value = $term->$value_var;
			$title = $term->name;
			$name = 'cas_condition[tax_input]['.$taxonomy->name.'][]';

		}

		if(is_array($selected_terms)) {
			$selected = checked(in_array($value,$selected_terms),true,false);
		} else {
			$selected = checked($selected_terms,true,false);
		}

		$output .= "\n".'<li><label class="selectit"><input value="'.$value.'" type="checkbox" title="'.esc_attr( $title ).'" name="'.$name.'"'.$selected.'/> '.esc_html( $title ).'</label>';

	}

	/**
	 * End outputting element
	 * @param  string $output 
	 * @param  object $object   
	 * @param  int    $depth  
	 * @param  array  $args   
	 * @return void         
	 */
	public function end_el(&$output, $object, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
	
}

/**
 *
 * Walker for post types and taxonomies
 *
 */
class CAS_Walker_Post_Type_List extends Walker {
	
	/**
	 * Constructor
	 * @param string $tree_type 
	 * @param array  $db_fields 
	 */
	function __construct($tree_type, $db_fields) {
		
		$this->tree_type = $tree_type;
		$this->db_fields = $db_fields;
		
	}
	
	/**
	 * Start outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "</li>$indent<li><ul class='children'>\n";
	}
	
	/**
	 * End outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function end_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul></li>\n";
	}
	
	/**
	 * Start outputting element
	 * @param  string $output 
	 * @param  object $term   
	 * @param  int    $depth  
	 * @param  array  $args 
	 * @param  int 	  $current_object_id
	 * @return void
	 */
	public function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		extract($args);

		$value = $term->ID;
		$title = $term->post_title;
		$name = 'cas_condition[post_types][]';

		$output .= "\n".'<li><label class="selectit"><input value="'.$value.'" type="checkbox" title="'.esc_attr( $title ).'" name="'.$name.'"'.$this->_checked($value,$selected_terms).'/> '.esc_html( $title ).'</label>';

	}

	/**
	 * End outputting element
	 * @param  string $output 
	 * @param  object $object   
	 * @param  int    $depth  
	 * @param  array  $args   
	 * @return void         
	 */
	public function end_el(&$output, $object, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

	/**
	 * Output if input is checked or not
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.4
	 * @param   string         $current
	 * @param   array|boolean  $selected
	 * @return  string
	 */
	private function _checked($current,$selected) {
		if(is_array($selected)) {
			return checked(in_array($current,$selected),true,false);
		}
		return checked($selected,true,false);
	}
}

//eol