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
 * Post Type Module
 *
 * Detects if current content is:
 * a) specific post type or specific post
 * b) specific post type archive or home
 * 
 */
class CASModule_post_type extends CASModule {
	
	/**
	 * Registered public post types
	 * @var array
	 */
	private $post_type_objects;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('post_types',__('Post Types',ContentAwareSidebars::DOMAIN), true);
		$this->type_display = true;
		$this->searchable = true;
		
		add_action('transition_post_status', array(&$this,'post_ancestry_check'),10,3);

		if(is_admin()) {
			add_action('wp_ajax_cas-autocomplete-'.$this->id, array(&$this,'ajax_content_search'));
		}

	}

	/**
	 * Display module in Screen Settings
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.3
	 * @param   array    $columns
	 * @return  array
	 */
	public function metabox_preferences($columns) {
		foreach ($this->_get_post_types() as $post_type) {
			$columns['box-'.$this->id.'-'.$post_type->name] = $post_type->label;
		}
		return $columns;
	}

	/**
	 * Get content for sidebar editor
	 * @param  array $args
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		$args = wp_parse_args($args, array(
			'include'        => '',
			'post_type'      => 'post',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'paged'          => 1,
			'posts_per_page' => 20
		));
		extract($args);

		$exclude = array();
		if ($post_type == 'page' && 'page' == get_option('show_on_front')) {
			$exclude[] = get_option('page_on_front');
			$exclude[] = get_option('page_for_posts');
		}

		//WP3.1 does not support (array) as post_status
		$query = new WP_Query(array(
			'posts_per_page'         => $posts_per_page,
			'post_type'              => $post_type,
			'post_status'            => 'publish,private,future',
			'post__in'               => $include,
			'exclude'                => $exclude,
			'orderby'                => $orderby,
			'order'                  => $order,
			'paged'                  => $paged,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false
		));
		$this->pagination = array(
			'paged'       => $paged,
			'per_page'    => 20,
			'total_pages' => $query->max_num_pages,
			'total_items' => $query->found_posts
		);
		//wp_reset_postdata();
		return $query->posts;
	}

	/**
	 * Get registered public post types
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @return  array
	 */
	protected function _get_post_types() {
		if (empty($this->post_type_objects)) {
			// List public post types
			foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
				$this->post_type_objects[$post_type->name] = $post_type;
			}
		}
		return $this->post_type_objects;		
	}

	/**
	 * Print saved condition data for a group
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.0
	 * @param   int    $post_id
	 * @return  void
	 */
	public function print_group_data($post_id) {
		$ids = get_post_custom_values(ContentAwareSidebars::PREFIX . $this->id, $post_id);
		
		if($ids) {
			$lookup = array_flip((array)$ids);
			foreach($this->_get_post_types() as $post_type) {
				$posts =$this->_get_content(array('include' => $ids, 'posts_per_page' => -1, 'post_type' => $post_type->name, 'orderby' => 'title', 'order' => 'ASC'));
				if($posts || isset($lookup[$post_type->name]) || isset($lookup[ContentAwareSidebars::PREFIX.'sub_' . $post_type->name])) {
					echo '<div class="cas-condition cas-condition-'.$this->id.'-'.$post_type->name.'">';
					echo '<strong>'.$post_type->label.'</strong>';
					echo '<ul>';
					if(isset($lookup[ContentAwareSidebars::PREFIX.'sub_' . $post_type->name])) {
						echo '<li><label><input type="checkbox" name="cas_condition[post_types][]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $post_type->name . '" checked="checked" /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label></li>' . "\n";
					}
					if(isset($lookup[$post_type->name])) {
						echo '<li><label><input type="checkbox" name="cas_condition[post_types][]" value="'.$post_type->name.'" checked="checked" /> '.$post_type->labels->all_items.'</label></li>' . "\n";
					}
					if($posts) {
						echo $this->post_checklist($post_type, $posts, false, $ids);	
					}					
					echo '</ul>';
					echo '</div>';	
				}
			}

		}

	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return ((is_singular() || is_home()) && !is_front_page()) || is_post_type_archive();
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		if(is_singular()) {
			return array(
				get_post_type(),
				get_the_ID()
			);
		}
		global $post_type;
		// Home has post as default post type
		if(!$post_type) $post_type = 'post';
		return array(
			$post_type
		);
	}

	/**
	 * Meta box content
	 * @global WP_Post $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;

		$hidden_columns  = get_hidden_columns( ContentAwareSidebars::TYPE_SIDEBAR );

		foreach ($this->_get_post_types() as $post_type) {

			$id = 'box-'.$this->id.'-'.$post_type->name;
			$hidden = in_array($id, $hidden_columns) ? ' hide-if-js' : '';

			echo '<li id="'.$id.'" class="manage-column column-box-'.$this->id.'-'. $post_type->name.' control-section accordion-section'.$hidden.'">';
			echo '<h3 class="accordion-section-title" title="'.$post_type->label.'" tabindex="0">'.$post_type->label.'</h3>'."\n";
			echo '<div class="accordion-section-content cas-rule-content" data-cas-module="'.$this->id.'" id="cas-' . $this->id . '-' . $post_type->name . '">'."\n";

			$recent_posts = $this->_get_content(array('post_type' => $post_type->name));


			if($post_type->hierarchical) {
				echo '<ul><li>' . "\n";
				echo '<label><input type="checkbox" name="cas_condition['.$this->id.'][]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $post_type->name . '" /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label>' . "\n";
				echo '</li></ul>' . "\n";
			}
			
			if($this->type_display) {
				echo '<ul><li>' . "\n";
				echo '<label><input class="cas-chk-all" type="checkbox" name="cas_condition['.$this->id.'][]" value="' . $post_type->name . '" /> ' . sprintf(__('Display with %s', ContentAwareSidebars::DOMAIN), $post_type->labels->all_items) . '</label>' . "\n";
				echo '</li></ul>' . "\n";				
			}

			if (!$recent_posts) {
				echo '<p>' . __('No items.') . '</p>';
			} else {
				//No need to use two queries before knowing there are items
				if(count($recent_posts) < 20) {
					$posts = $recent_posts;
				} else {
					$posts = $this->_get_content(array('post_type' => $post_type->name, 'orderby' => 'title', 'order' => 'ASC'));
				}

				$tabs = array();
				$tabs['most-recent'] = array(
					'title' => __('Most Recent'),
					'status' => true,
					'content' => $this->post_checklist($post_type, $recent_posts)
				);
				$tabs['all'] = array(
					'title' => __('View All'),
					'status' => false,
					'content' => $this->post_checklist($post_type, $posts, true)
				);
				if($this->searchable) {
					$tabs['search'] = array(
						'title' => __('Search'),
						'status' => false,
						'content' => '',
						'content_before' => '<p><input class="cas-autocomplete-' . $this->id . ' cas-autocomplete quick-search" id="cas-autocomplete-' . $this->id . '-' . $post_type->name . '" type="search" name="cas-autocomplete" value="" placeholder="'.__('Search').'" autocomplete="off" /><span class="spinner"></span></p>'
					);
				}

				echo $this->create_tab_panels($this->id . '-' . $post_type->name,$tabs);
				
			}

			echo '<p class="button-controls">';

			echo '<span class="add-to-group"><input data-cas-condition="'.$this->id.'-'.$post_type->name.'" type="button" name="" id="cas-' . $this->id . '-' . $post_type->name . '-add" class="js-cas-condition-add button" value="'.__('Add to Group',ContentAwareSidebars::DOMAIN).'"></span>';

			echo '</p>';

			echo '</div>';
			echo '</li>';
		}
	}

	/**
	 * Show posts from a specific post type
	 * @param  int     $post_id      
	 * @param  object  $post_type    
	 * @param  array   $posts        
	 * @param  array   $selected_ids 
	 * @return void                
	 */
	private function post_checklist($post_type, $posts, $pagination = false, $selected_ids = array()) {

		$walker = new CAS_Walker_Checklist('post',array('parent' => 'post_parent', 'id' => 'ID'));

		$args = array(
			'post_type'	=> $post_type,
			'selected_terms' => $selected_ids
		);

		$return = call_user_func_array(array(&$walker, 'walk'), array($posts, 0, $args));

		if($pagination) {
			$paginate = paginate_links(array(
				'base'         => admin_url( 'admin-ajax.php').'%_%',
				'format'       => '?paged=%#%',
				'total'        => $this->pagination['total_pages'],
				'current'      => $this->pagination['paged'],
				'mid_size'     => 2,
				'end_size'     => 1,
				'prev_next'    => true,
				'prev_text'    => 'prev',
				'next_text'    => 'next',
				'add_args'     => array('item_object'=>$post_type->name),
			));
			$return = $paginate.$return.$paginate;
		}
		
		return $return;
	}

	public function ajax_get_content() {

		//validation
		$paged = isset($_POST['paged']) ? $_POST['paged'] : 1;
		$search = isset($_POST['search']) ? $_POST['search'] : false;
		$post_type = get_post_type_object($_POST['item_object']);

		$posts = $this->_get_content(array('post_type' => $_POST['item_object'], 'orderby' => 'title', 'order' => 'ASC', 'paged' => $paged));
		$response = $this->post_checklist($post_type, $posts, true);
		//$response = $_POST['paged'];
		echo json_encode($response);
		die();
	}

	/**
	 * Get posts with AJAX search
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
		if ( preg_match('/cas-autocomplete-'.$this->id.'-([a-zA-Z_-]*\b)/', $_REQUEST['type'], $matches) ) {
			if(get_post_type_object( $matches[1] )) {
				$exclude = array();
				$exclude_query = "";
				if ($matches[1] == 'page' && 'page' == get_option('show_on_front')) {
					$exclude[] = get_option('page_on_front');
					$exclude[] = get_option('page_for_posts');
					$exclude_query = " AND ID NOT IN (".implode(",", $exclude).")";
				}

				//WordPress searches in title and content by default
				//We want to search in title and slug
				//Using unprepared (safe) exclude because WP is not good at parsing arrays
				$posts = $wpdb->get_results($wpdb->prepare("
					SELECT ID, post_title, post_type
					FROM $wpdb->posts
					WHERE post_type = '%s' AND (post_title LIKE '%s' OR post_name LIKE '%s') AND post_status IN('publish','private','future')
					".$exclude_query."
					ORDER BY post_title ASC
					LIMIT 0,20
					",
					$matches[1],
					"%".$_REQUEST['q']."%",
					"%".$_REQUEST['q']."%"
				));

				// $posts = get_posts(array(
				// 	'posts_per_page' => 10,
				// 	'post_type' => $matches[1],
				// 	's' => $_REQUEST['term'],
				// 	'exclude' => $exclude,
				// 	'orderby' => 'title',
				// 	'order' => 'ASC',
				// 	'post_status'	=> 'publish,private,future'
				// ));
				
				foreach($posts as $post) {
					$suggestions[] = array(
						'label' => $post->post_title,
						'value' => $post->ID,
						'id'	=> $post->ID,
						'module' => $this->id,
						'name' => 'cas_condition['.$this->id.']',
						'id2' => $this->id.'-'.$post->post_type,
						'elem' => $post->post_type.'-'.$post->ID
					);
				}
			}
		}

		echo json_encode($suggestions);
		die();
	}

	
	/**
	 * Automatically select child of selected parent
	 * @param  string  $new_status 
	 * @param  string  $old_status 
	 * @param  WP_Post $post       
	 * @return void 
	 */
	public function post_ancestry_check($new_status, $old_status, $post) {
		
		if($post->post_type != ContentAwareSidebars::TYPE_SIDEBAR && $post->post_type != ContentAwareSidebars::TYPE_CONDITION_GROUP) {
			
			$status = array('publish','private','future');
			// Only new posts are relevant
			if(!in_array($old_status,$status) && in_array($new_status,$status)) {
				
				$post_type = get_post_type_object($post->post_type);
				if($post_type->hierarchical && $post_type->public && $post->parent != '0') {
				
					// Get sidebars with post ancestor wanting to auto-select post
					$query = new WP_Query(array(
						'post_type'				=> ContentAwareSidebars::TYPE_CONDITION_GROUP,
						'meta_query'			=> array(
							'relation'			=> 'AND',
							array(
								'key'			=> ContentAwareSidebars::PREFIX . $this->id,
								'value'			=> ContentAwareSidebars::PREFIX.'sub_' . $post->post_type,
								'compare'		=> '='
							),
							array(
								'key'			=> ContentAwareSidebars::PREFIX . $this->id,
								'value'			=> get_ancestors($post->ID,$post->post_type),
								'type'			=> 'numeric',
								'compare'		=> 'IN'
							)
						)
					));
					if($query && $query->found_posts) {
						foreach($query->posts as $sidebar) {
							add_post_meta($sidebar->ID, ContentAwareSidebars::PREFIX.$this->id, $post->ID);
						}
					}
				}
			}	
		}	
	}

}
