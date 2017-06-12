<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	exit;
}

class CAS_Post_Type_Sidebar {

	const MODULE_NAME = 'post_type';
	const NONCE       = '_cas_nonce';

	protected static $_theme_sidebars = array();

	public function __construct(){
		add_action('admin_init',
			array(__CLASS__,'initiate'));
		add_action('widgets_init',
			array(__CLASS__,'get_theme_sidebars'),98);
	}

	public static function initiate() {
		$module = WPCACore::modules()->get(self::MODULE_NAME);
		if(self::$_theme_sidebars && $module) {
			foreach ($module->_post_types()->get_all() as $post_type) {
				add_action('add_meta_boxes_'.$post_type->name,
					array(__CLASS__,'create_meta_boxes'));
				add_action('save_post_'.$post_type->name,
					array(__CLASS__,'save_post_sidebars'),10,2);
			}
			add_action('admin_enqueue_scripts',
				array(__CLASS__,'register_scripts'),8);
			add_action('admin_enqueue_scripts',
				array(__CLASS__,'enqueue_scripts'),11);
		}
	}

	/**
	 * Gather theme sidebars for later use
	 *
	 * @since  3.3
	 * @return void
	 */
	public static function get_theme_sidebars() {
		if(is_admin()) {
			global $wp_registered_sidebars;
			foreach($wp_registered_sidebars as $sidebar) {
				//todo: check for cas id, issue after switching themes
				self::$_theme_sidebars[$sidebar['id']] = array(
					'label' => $sidebar['name'],
					'options' => array()
				);
			}
		}
	}

	/**
	 * Save sidebars and conditions for post
	 *
	 * @since  3.3
	 * @param  int      $post_id
	 * @param  WP_Post  $post
	 * @return void
	 */
	public static function save_post_sidebars($post_id, $post) {

		if(!(isset($_POST[self::NONCE])
			&& wp_verify_nonce($_POST[self::NONCE], self::NONCE.$post_id))) {
			return;
		}
		
		// Check post permissions
		if (!current_user_can('edit_post', $post_id))
			return;

		if($post->post_status == 'auto-draft') {
			return;
		}

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		$meta_key = WPCACore::PREFIX . self::MODULE_NAME;
		$new = isset($_POST['cas_sidebars']) ? $_POST['cas_sidebars'] : array();

		$relations = array();
		foreach(self::_get_content_sidebars(array($post_id)) as $relation) {
			$relations[$relation->ID] = $relation->group_id;
		}

		$user_can_create_sidebar = current_user_can(CAS_App::CAPABILITY);

		foreach ($new as $host => $sidebar_ids) {
			foreach ($sidebar_ids as $sidebar_id) {
				//Post has sidebar already
				if(isset($relations[$sidebar_id])) {
					unset($relations[$sidebar_id]);
				//Discard empty
				} elseif($sidebar_id) {

					$condition_group_id = 0;
					
					//New sidebar
					//check permissions here
					if($sidebar_id[0] == '_') {
						if($user_can_create_sidebar) {
							$id = wp_insert_post(array(
								'post_title'  => str_replace('__',',',substr($sidebar_id,1)),
								'post_status' => CAS_App::STATUS_INACTIVE, 
								'post_type'   => CAS_App::TYPE_SIDEBAR
							));
							if($id) {
								//wp_insert_post does not handle meta before WP4.4
								add_post_meta($id, WPCACore::PREFIX.'host', $host);
								$condition_group_id = WPCACore::add_condition_group($id);
							}
						}
					} else {
						//Add post to group with other posts
						$id = intval($sidebar_id);
						$condition_groups = get_posts(array(
							'posts_per_page'   => 1,
							'meta_key'         => $meta_key,
							'meta_value'       => $post->post_type,
							'meta_compare'     => '!=',
							'post_parent'      => $id,
							'post_type'        => WPCACore::TYPE_CONDITION_GROUP,
							'post_status'      => WPCACore::STATUS_PUBLISHED
						));
						if($condition_groups) {
							$condition_group_id = $condition_groups[0]->ID;
						} else {
							$condition_group_id = WPCACore::add_condition_group($id);
						}
					}

					if($condition_group_id) {
						add_post_meta($condition_group_id, $meta_key, $post_id);
						//add_post_meta($condition_group_id, $meta_key.'_direct', 1);
					}
				}
			}
		}

		//remove old relations
		//todo: sanity check if post is added to several groups?
		$sidebars = CAS_App::instance()->manager()->sidebars;
		$host_meta = CAS_App::instance()->manager()->metadata()->get('host');
		foreach ($relations as $sidebar_id => $group_id) {
			if(isset($sidebars['ca-sidebar-'.$sidebar_id])) {
				$host_id = $host_meta->get_data($sidebar_id);
				if(!isset(self::$_theme_sidebars[$host_id])) {
					continue;
				}
			}

			//group with no post_type meta will be removed
			//even if it has other meta (unlikely)
			$group_meta = get_post_meta($group_id, $meta_key);
			if(count($group_meta) <= 1) {
				wp_delete_post($group_id);
			} else {
				delete_post_meta($group_id, $meta_key, $post_id);
			}
		}
	}

	/**
	 * Create sidebar meta box for post types
	 *
	 * @since  3.3
	 * @param  WP_Post  $post
	 * @return void
	 */
	public static function create_meta_boxes($post) {

		$post_sidebars = array();
		foreach(self::_get_content_sidebars(array($post->ID)) as $sidebar) {
			$post_sidebars[$sidebar->ID] = $sidebar->ID;
		}

		$manager = CAS_App::instance()->manager();

		$host_meta = $manager->metadata()->get('host');
		foreach ($manager->sidebars as $sidebar) {
			$host_id = $host_meta->get_data($sidebar->ID);
			if(isset(self::$_theme_sidebars[$host_id])) {
				self::$_theme_sidebars[$host_id]['options'][$sidebar->ID] = array(
					'id' => $sidebar->ID,
					'text' => $sidebar->post_title.self::sidebar_states($sidebar)
				);
				if(isset($post_sidebars[$sidebar->ID])) {
					self::$_theme_sidebars[$host_id]['options'][$sidebar->ID]['select'] = 1;
				}
			}
		}

		$post_type = get_post_type_object($post->post_type);
		$content = array(
			__('Author')
		);
		if($post_type->hierarchical) {
			$content[] = __('Child Page','content-aware-sidebars');
		}
		if($post_type->name == 'page') {
			$content[] = __('Page Template','content-aware-sidebars');
		}
		$taxonomies = get_object_taxonomies($post,'objects');
		if($taxonomies) {
			foreach ($taxonomies as $tax) {
				$content[] = $tax->labels->singular_name;
			}
		}
		$content[] = __('Archive Page','content-aware-sidebars');

		$path = plugin_dir_path( __FILE__ ).'../view/';
		$view = WPCAView::make($path.'sidebars_quick_select.php',array(
			'post'     => $post,
			'sidebars' => self::$_theme_sidebars,
			'limit'    => 3,
			'content'  => $content,
			'singular' => $post_type->labels->singular_name,
			'nonce'    => wp_nonce_field(self::NONCE.$post->ID, self::NONCE, false, false)
		));

		add_meta_box(
			'cas-content-sidebars',
			__('Sidebars - Quick Select','content-aware-sidebars'),
			array($view, 'render'),
			$post->post_type,
			'side',
			'default'
		);
	}

	/**
	 * Get sidebar status for display
	 *
	 * @since  3.4.1
	 * @param  WP_Post  $post
	 * @return string
	 */
	public static function sidebar_states($post) {
		switch ($post->post_status) {
			case CAS_App::STATUS_ACTIVE:
				$status = '';
				break;
			case CAS_App::STATUS_SCHEDULED;
				$status = ' ('.__( 'Scheduled' ).')';
				break;
			default:
				$status = ' ('.__( 'Inactive', 'content-aware-sidebars').')';
				break;
		}
		return $status;
	}

	/**
	 * Register scripts and styles
	 * We register early to make sure our select2 comes first
	 *
	 * @since  3.5.2
	 * @param  string  $hook
	 * @return void
	 */
	public static function register_scripts($hook) {
		$screen = get_current_screen();
		$module = WPCACore::modules()->get(self::MODULE_NAME);

		if($screen->base == 'post' && $module->_post_types()->has($screen->post_type)) {
			wp_register_script(
				'select2',
				plugins_url('../lib/wp-content-aware-engine/assets/js/select2.min.js', __FILE__),
				array('jquery'),
				'4.0.3',
				false
			);
			wp_register_script('cas/sidebars/suggest', plugins_url('/js/suggest-sidebars.min.js', dirname(__FILE__)), array('select2'), CAS_App::PLUGIN_VERSION, true);
		}
	}

	/**
	 * Enqueue scripts and styles
	 * We enqueue later to make sure our CSS takes precedence
	 *
	 * @since  3.5.2
	 * @param  string  $hook
	 * @return void
	 */
	public static function enqueue_scripts($hook) {
		$screen = get_current_screen();
		$module = WPCACore::modules()->get(self::MODULE_NAME);

		if($screen->base == 'post' && $module->_post_types()->has($screen->post_type)) {
			wp_enqueue_style(CAS_App::META_PREFIX.'condition-groups');
			wp_enqueue_script('cas/sidebars/suggest');

			$labels = array(
				'createNew' => __('Create New','content-aware-sidebars'),
				'labelNew'  => __('New','content-aware-sidebars')
			);
			if(current_user_can(CAS_App::CAPABILITY)) {
				$labels['notFound'] = __('Type to Add New Sidebar','content-aware-sidebars');
			} else {
				$labels['notFound'] = __('No sidebars found','content-aware-sidebars');
			}
			wp_localize_script('cas/sidebars/suggest', 'CAS', $labels);
		}
	}

	/**
	 * Get sidebars for select post types
	 *
	 * @since  3.3
	 * @param  array  $posts
	 * @return array
	 */
	protected static function _get_content_sidebars($posts = null) {
		if(is_array($posts) && $posts) {
			global $wpdb;
			return $wpdb->get_results(
				"SELECT meta.meta_value, sidebars.ID, sidebars.post_title, groups.ID as group_id
				FROM $wpdb->posts sidebars
				INNER JOIN $wpdb->posts groups ON groups.post_parent = sidebars.ID
				INNER JOIN $wpdb->postmeta meta ON meta.post_id = groups.ID
				WHERE sidebars.post_status <> 'trash'
				AND sidebars.post_type = '".CAS_App::TYPE_SIDEBAR."'
				AND groups.post_status = '".WPCACore::STATUS_PUBLISHED."'
				AND meta.meta_key = '".WPCACore::PREFIX.self::MODULE_NAME."'
				AND meta.meta_value IN (".implode(",", $posts).")
				ORDER BY sidebars.post_title ASC"
			);
		}
		return false;
	}

}

//eol