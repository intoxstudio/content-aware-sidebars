<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/en/plugin/content-aware-sidebars-en/
Description: Manage and show sidebars according to the content being viewed.
Version: 2.6.3
Author: Joachim Jensen, Intox Studio
Author URI: http://www.intox.dk/
Text Domain: content-aware-sidebars
Domain Path: /lang/
License: GPLv3

	Content Aware Sidebars Plugin
	Copyright (C) 2011-2015 Joachim Jensen - jv@intox.dk

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

final class ContentAwareSidebars {
	
	/**
	 * Database version for update module
	 */
	const DB_VERSION           = '2.0';

	/**
	 * Plugin version
	 */
	const PLUGIN_VERSION       = '2.6.3';

	/**
	 * Prefix for data (keys) stored in database
	 */
	const PREFIX               = '_cas_';

	/**
	 * Prefix for sidebar id
	 */
	const SIDEBAR_PREFIX       = 'ca-sidebar-';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR         = 'sidebar';

	/**
	 * Post Type for sidebar groups
	 */
	const TYPE_CONDITION_GROUP = 'sidebar_group';

	/**
	 * Language domain
	 */
	const DOMAIN               = 'content-aware-sidebars';

	/**
	 * Capability to manage sidebars
	 */
	const CAPABILITY           = 'edit_theme_options';

	/**
	 * Sidebar metadata
	 * @var array
	 */
	private $metadata          = array();

	/**
	 * Store all sidebars here
	 * @var array
	 */
	private $sidebars          = array();

	/**
	 * Sidebars retrieved from database
	 * @var array
	 */
	private $sidebar_cache     = array();

	/**
	 * Modules for specific content or cases
	 * @var array
	 */
	private $modules           = array();

	/**
	 * Instance of class
	 * @var ContentAwareSidebars
	 */
	private static $_instance;

	/**
	 * Constructor
	 */
	public function __construct() {

		//__('Manage and show sidebars according to the content being viewed.',self::DOMAIN);
		//__('Content Aware Sidebars',self::DOMAIN);
		
		$this->_load_dependencies();

		spl_autoload_register(array($this,"autoload_modules"));

		// WordPress Hooks. Somewhat ordered by execution
		
		//For administration
		if(is_admin()) {
			
			add_action('wp_loaded', array(&$this,'db_update'));
			add_action('admin_enqueue_scripts', array(&$this,'load_admin_scripts'));
			add_action('delete_post', array(&$this,'remove_sidebar_widgets'));
			add_action('delete_post', array(&$this,'cascade_sidebar_delete'));
			add_action('save_post', array(&$this,'save_post'));
			add_action('add_meta_boxes_'.self::TYPE_SIDEBAR, array(&$this,'create_meta_boxes'));
			add_action('in_admin_header', array(&$this,'clear_admin_menu'),99);
			add_action('transition_post_status', array(&$this,'cascade_sidebar_status'),10,3);
			add_action('manage_'.self::TYPE_SIDEBAR.'_posts_custom_column', array(&$this,'admin_column_rows'),10,2);

			add_action('wp_ajax_cas_add_rule', array(&$this,'add_sidebar_rule_ajax'));
			add_action('wp_ajax_cas_remove_group', array(&$this,'remove_sidebar_group_ajax'));			

			add_filter('request', array(&$this,'admin_column_orderby'));
			add_filter('default_hidden_meta_boxes', array(&$this,'change_default_hidden'),10,2);
			add_filter('manage_'.self::TYPE_SIDEBAR.'_posts_columns', array(&$this,'admin_column_headers'),99);
			add_filter('manage_edit-'.self::TYPE_SIDEBAR.'_sortable_columns',array(&$this,'admin_column_sortable_headers'));
			add_filter('post_row_actions', array(&$this,'sidebar_row_actions'),10,2);
			add_filter('post_updated_messages', array(&$this,'sidebar_updated_messages'));
			add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array(&$this,'plugin_action_links'), 10, 4 );

		//For frontend
		} else {

			

		}

		add_shortcode( 'ca-sidebar', array($this,'sidebar_shortcode'));
		add_action('sidebars_widgets', array(&$this,'replace_sidebar'));
		add_action('wp_head',array(&$this,'sidebar_notify_theme_customizer'));

		//For both
		add_action('init', array(&$this,'deploy_modules'));
		add_action('init', array(&$this,'init_sidebar_type'),99);
		add_action('widgets_init', array(&$this,'create_sidebars'),99);
		add_action('wp_loaded', array(&$this,'update_sidebars'),99);
		
	}

	/**
	 * Display sidebar with shortcode
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.5
	 * @param   array     $atts
	 * @param   string    $content
	 * @return  string
	 */
	public function sidebar_shortcode( $atts, $content = null ) {
		$a = shortcode_atts( array(
			'id' => 0,
		), $atts );
		
		$id = 'ca-sidebar-'.esc_attr($a['id']);
		ob_start();
		dynamic_sidebar($id);
		return ob_get_clean();
	}


	/**
	 * Add actions to plugin in Plugins screen
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.4
	 * @param   array     $actions
	 * @param   string    $plugin_file
	 * @param   [type]    $plugin_data
	 * @param   [type]    $context
	 * @return  array
	 */
	public function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {

		$new_actions = array(
			'<a href="http://www.intox.dk/en/plugin/content-aware-sidebars-en/faq/" target="_blank">'.__('FAQ',self::DOMAIN).'</a>'
		);

		return array_merge($new_actions,$actions);
	}

	/**
	 * Runs is_active_sidebar for sidebars
	 * Widget management in Theme Customizer
	 * expects this
	 * @author Joachim Jensen <jv@intox.dk>
	 * @global type $wp_customize
	 * @since  2.2
	 * @return void
	 */
	public function sidebar_notify_theme_customizer() {
		global $wp_customize;
		if(!empty($wp_customize)) {
			$sidebars = $this->get_sidebars();
			if($sidebars) {
				foreach($sidebars as $sidebar) {
					is_active_sidebar(self::SIDEBAR_PREFIX . $sidebar->ID);
				}
			}
		}
	}

	/**
	 * Instantiates and returns class singleton
	 * @return ContentAwareSidebars 
	 */
	public static function instance() {
		if(!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Deploy modules
	 * @return void 
	 */
	public function deploy_modules() {

		load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)).'/lang/');
		
		// List builtin modules
		$modules = array(
			'static'        => true,
			'post_type'     => true,
			'author'        => true,
			'page_template' => true,
			'taxonomy'      => true,
			'date'          => true,
			'bbpress'       => function_exists('bbp_get_version'),	// bbPress
			'bp_member'     => defined('BP_VERSION'),				// BuddyPress
			'polylang'      => defined('POLYLANG_VERSION'),			// Polylang
			'qtranslate'    => defined('QT_SUPPORTED_WP_VERSION'),	// qTranslate
			'transposh'     => defined('TRANSPOSH_PLUGIN_VER'),		// Transposh Translation Filter
			'wpml'          => class_exists('SitePress')			// WPML Multilingual Blog/CMS
		);
		//Let developers remove builtin modules or add their own
		$modules = apply_filters('cas-module-pre-deploy',$modules);
		
		// Forge modules
		foreach($modules as $name => $enabled) {
			if($enabled) {
				if(is_bool($enabled)) {
					$class = 'CASModule_'.$name;
					$obj = new $class;
				} else if(class_exists((string)$enabled)) {
					$obj = new $enabled;
					if(!($obj instanceof CASModule)) {
						continue;
					}
				} else {
					continue;
				}
				
				$this->modules[$obj->get_id()] = $obj; 
			}
		}
		
	}
	
	/**
	 * Create post meta fields
	 * @global array $wp_registered_sidebars 
	 * @return void 
	 */
	private function _init_metadata() {
		global $wp_registered_sidebars;

		// List of sidebars
		$sidebar_list = array();
		foreach($wp_registered_sidebars as $sidebar) {
			$sidebar_list[$sidebar['id']] = $sidebar['name'];
		}

		// Meta fields
		$this->metadata['exposure'] = array(
			'name' => __('Exposure', self::DOMAIN),
			'id'   => 'exposure',
			'desc' => '',
			'val'  => 1,
			'type' => 'select',
			'list' => array(
				__('Singular', self::DOMAIN),
				__('Singular & Archive', self::DOMAIN),
				__('Archive', self::DOMAIN)
			)
		);
		$this->metadata['handle'] = array(
			'name' => _x('Handle','option', self::DOMAIN),
			'id'   => 'handle',
			'desc' => __('Replace host sidebar, merge with it or add sidebar manually.', self::DOMAIN),
			'val'  => 0,
			'type' => 'select',
			'list' => array(
				0 => __('Replace', self::DOMAIN),
				1 => __('Merge', self::DOMAIN),
				2 => __('Manual', self::DOMAIN),
				3 => __('Forced replace',self::DOMAIN)
			)
		);
		$this->metadata['host']	= array(
			'name' => __('Host Sidebar', self::DOMAIN),
			'id'   => 'host',
			'desc' => '',
			'val'  => 'sidebar-1',
			'type' => 'select',
			'list' => $sidebar_list
		);
		$this->metadata['merge-pos'] = array(
			'name' => __('Merge position', self::DOMAIN),
			'id'   => 'merge-pos',
			'desc' => __('Place sidebar on top or bottom of host when merging.', self::DOMAIN),
			'val'  => 1,
			'type' => 'select',
			'list' => array(
				__('Top', self::DOMAIN),
				__('Bottom', self::DOMAIN)
			)
		);
		
	}
	
	/**
	 * Create sidebar post type and filter group post type
	 * @return void 
	 */
	public function init_sidebar_type() {

		$capabilities = array(
			'edit_post'          => self::CAPABILITY,
			'read_post'          => self::CAPABILITY,
			'delete_post'        => self::CAPABILITY,
			'edit_posts'         => self::CAPABILITY,
			'delete_posts'       => self::CAPABILITY,
			'edit_others_posts'  => self::CAPABILITY,
			'publish_posts'      => self::CAPABILITY,
			'read_private_posts' => self::CAPABILITY
		);
		
		// Register the sidebar type
		register_post_type(self::TYPE_SIDEBAR,array(
			'labels'        => array(
				'name'               => __('Sidebars', self::DOMAIN),
				'singular_name'      => __('Sidebar', self::DOMAIN),
				'add_new'            => _x('Add New', 'sidebar', self::DOMAIN),
				'add_new_item'       => __('Add New Sidebar', self::DOMAIN),
				'edit_item'          => __('Edit Sidebar', self::DOMAIN),
				'new_item'           => __('New Sidebar', self::DOMAIN),
				'all_items'          => __('All Sidebars', self::DOMAIN),
				'view_item'          => __('View Sidebar', self::DOMAIN),
				'search_items'       => __('Search Sidebars', self::DOMAIN),
				'not_found'          => __('No sidebars found', self::DOMAIN),
				'not_found_in_trash' => __('No sidebars found in Trash', self::DOMAIN)
			),
			'capabilities'  => $capabilities,
			'show_ui'       => true,
			'show_in_menu'  => true, //current_user_can(self::CAPABILITY),
			'query_var'     => false,
			'rewrite'       => false,
			'menu_position' => 25.099, //less probable to be overwritten
			'supports'      => array('title','page-attributes'),
			'menu_icon'     => version_compare(get_bloginfo('version'), '3.8' ,'>=') ? 'dashicons-welcome-widgets-menus' : plugins_url('/img/icon-16.png', __FILE__ )
		));
		
		// Register the condition group type
		register_post_type(self::TYPE_CONDITION_GROUP,array(
			'labels'       => array(
				'name'               => __('Condition Groups', self::DOMAIN),
				'singular_name'      => __('Condition Group', self::DOMAIN),
				'add_new'            => _x('Add New', 'group', self::DOMAIN),
				'add_new_item'       => __('Add New Group', self::DOMAIN),
				'edit_item'          => _x('Edit', 'group', self::DOMAIN),
				'new_item'           => '',
				'all_items'          => '',
				'view_item'          => '',
				'search_items'       => '',
				'not_found'          => '',
				'not_found_in_trash' => ''
			),
			'capabilities' => $capabilities,
			'show_ui'      => false,
			'show_in_menu' => false,
			'query_var'    => false,
			'rewrite'      => false,
			'supports'     => array('author'), //prevents fallback
		));
	}
	
	/**
	 * Create update messages
	 * @global object $post
	 * @param  array  $messages 
	 * @return array           
	 */
	public function sidebar_updated_messages( $messages ) {
		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets',self::DOMAIN));
		$messages[self::TYPE_SIDEBAR] = array(
			0 => '',
			1 => __('Sidebar updated.',self::DOMAIN).$manage_widgets,
			2 => '',
			3 => '',
			4 => __('Sidebar updated.',self::DOMAIN),
			5 => '',
			6 => __('Sidebar published.',self::DOMAIN).$manage_widgets,
			7 => __('Sidebar saved.',self::DOMAIN),
			8 => __('Sidebar submitted.',self::DOMAIN).$manage_widgets,
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>.',self::DOMAIN),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime(get_the_ID()))).$manage_widgets,
			10 => __('Sidebar draft updated.',self::DOMAIN),
		);
		return $messages;
	}

	/**
	 * Add sidebars to widgets area
	 * Triggered in widgets_init to save location for each theme
	 * @return void
	 */
	public function create_sidebars() {
		$this->sidebars = get_posts(array(
			'numberposts' => -1,
			'post_type'   => self::TYPE_SIDEBAR,
			'post_status' => array('publish','private','future')
		));

		//Register sidebars to add them to the list
		foreach($this->sidebars as $post) {
			register_sidebar( array(
				'name' => $post->post_title,
				'id'   => self::SIDEBAR_PREFIX.$post->ID
			));
		}
	}
	
	/**
	 * Update the created sidebars with metadata
	 * @return void 
	 */
	public function update_sidebars() {

		//Init metadata
		$this->_init_metadata();

		//Now reregister sidebars with proper content
		foreach($this->sidebars as $post) {

			$handle = get_post_meta($post->ID,self::PREFIX . 'handle', true);
			$sidebar_args = array(
				"name"        => $post->post_title,
				"description" => isset($this->metadata['handle']['list'][$handle]) ? $this->metadata['handle']['list'][$handle] : false,
				"id"          => self::SIDEBAR_PREFIX.$post->ID
			);

			if(!$sidebar_args["description"]) {
				continue;
			}

			$sidebar_args["before_widget"] = '<li id="%1$s" class="widget-container %2$s">';
			$sidebar_args["after_widget"] = '</li>';
			$sidebar_args["before_title"] = '<h3 class="widget-title">';
			$sidebar_args["after_title"] = '</h3>';

			if ($handle != 2) {
				$host_id = get_post_meta($post->ID, self::PREFIX . 'host', true);
				$host = isset($this->metadata['host']['list'][$host_id]) ? $this->metadata['host']['list'][$host_id] : false;
				
				$sidebar_args["description"] .= ": " . ($host ? $host :  __('Please update Host Sidebar', self::DOMAIN) );

				//Set style from host to fix when content aware sidebar
				//is called directly by other sidebar managers
				//does not work recursively
				global $wp_registered_sidebars;
				if(isset($wp_registered_sidebars[$host_id]) && $wp_registered_sidebars[$host_id]) {
					$sidebar_args["before_widget"] = $wp_registered_sidebars[$host_id]["before_widget"];
					$sidebar_args["after_widget"] = $wp_registered_sidebars[$host_id]["after_widget"];
					$sidebar_args["before_title"] = $wp_registered_sidebars[$host_id]["before_title"];
					$sidebar_args["after_title"] = $wp_registered_sidebars[$host_id]["after_title"];
				}
			}

			register_sidebar($sidebar_args);
		}
	}

	/**
	 * Add admin column headers
	 * @param  array $columns 
	 * @return array          
	 */
	public function admin_column_headers($columns) {
		// Totally discard current columns and rebuild
		return array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'handle'    => _x('Handle','option', self::DOMAIN),
			'merge-pos' => __('Merge position', self::DOMAIN),
			'widgets'   => __('Widgets'),
			'date'      => $columns['date']
		);
	}
		
	/**
	 * Make some columns sortable
	 * @param  array $columns 
	 * @return array
	 */
	public function admin_column_sortable_headers($columns) {
		return array_merge(
			array(
				'handle'    => 'handle',
				'merge-pos' => 'merge-pos'
			), $columns
		);
	}
	
	/**
	 * Manage custom column sorting
	 * @param  array $vars 
	 * @return array 
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'], array('exposure', 'handle', 'merge-pos'))) {
			$vars = array_merge($vars, array(
				'meta_key' => self::PREFIX . $vars['orderby'],
				'orderby'  => 'meta_value'
			));
		}
		return $vars;
	}
	
	/**
	 * Add admin column rows
	 * @param  string $column_name 
	 * @param  int $post_id
	 * @return void
	 */
	public function admin_column_rows($column_name, $post_id) {

		if($column_name == 'widgets') {
			$sidebars_widgets = wp_get_sidebars_widgets();
			echo (isset($sidebars_widgets[self::SIDEBAR_PREFIX . $post_id]) ? count($sidebars_widgets[self::SIDEBAR_PREFIX . $post_id]) : 0);
			return;
		} 

		// Load metadata
		if (!$this->metadata)
			$this->_init_metadata();

		$current = get_post_meta($post_id, self::PREFIX . $column_name, true);
		$retval = "";

		if(isset($this->metadata[$column_name]['list'][$current])) {

			$retval = $this->metadata[$column_name]['list'][$current];
			
			if ($column_name == 'handle' && $current != 2) {
				$host = get_post_meta($post_id, self::PREFIX . 'host', true);
				$retval .= ": " . (isset($this->metadata['host']['list'][$host]) ? $this->metadata['host']['list'][$host] : '<span style="color:red;">' . __('Please update Host Sidebar', self::DOMAIN) . '</span>');
			}

		}
		
		echo $retval;
	}
	
	/**
	 * Remove widget when its sidebar is removed
	 * @param  int $post_id 
	 * @return void
	 */
	public function remove_sidebar_widgets($post_id) {

		// Authenticate and only continue on sidebar post type
		if (!current_user_can(self::CAPABILITY) || get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		$id = self::SIDEBAR_PREFIX . $post_id;

		//Get widgets
		$sidebars_widgets = wp_get_sidebars_widgets();

		// Check if sidebar exists in database
		if (!isset($sidebars_widgets[$id]))
			return;

		// Remove widgets settings from sidebar
		foreach ($sidebars_widgets[$id] as $widget_id) {
			$widget_type = preg_replace('/-[0-9]+$/', '', $widget_id);
			$widget_settings = get_option('widget_' . $widget_type);
			$widget_id = substr($widget_id, strpos($widget_id, '-') + 1);
			if ($widget_settings && isset($widget_settings[$widget_id])) {
				unset($widget_settings[$widget_id]);
				update_option('widget_' . $widget_type, $widget_settings);
			}
		}

		// Remove sidebar
		unset($sidebars_widgets[$id]);
		wp_set_sidebars_widgets($sidebars_widgets);
	}

	/**
	 * Delete condition groups for
	 * a sidebar that is deleted
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @param  int    $post_id
	 * @return void
	 */
	public function cascade_sidebar_delete($post_id) {

		// Authenticate and only continue on sidebar post type
		if (!current_user_can(self::CAPABILITY) || get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		global $wpdb;
		$groups = (array)$wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = '%d'", $post_id));
		foreach($groups as $group_id) {
			//Takes care of metadata and terms too
			wp_delete_post($group_id,true);
		}

	}
	
	/**
	 * Add admin rows actions
	 * @param  array   $actions
	 * @param  WP_Post $post
	 * @return array
	 */
	public function sidebar_row_actions($actions, $post) {
		if ($post->post_type == self::TYPE_SIDEBAR && $post->post_status != 'trash') {
			$new_actions['mng_widgets'] = '<a href="widgets.php" title="' . esc_attr__('Manage Widgets', self::DOMAIN) . '">' . __('Manage Widgets', self::DOMAIN) . '</a>';
			//Append new actions just before trash action
			array_splice($actions, -1, 0, $new_actions);
		}
		return $actions;
	}

	/**
	 * Replace or merge a sidebar with content aware sidebars.
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  .
	 * @param  array    $sidebars_widgets
	 * @return array
	 */
	public function replace_sidebar($sidebars_widgets) {

		$posts = $this->get_sidebars();
		if (!$posts)
			return $sidebars_widgets;

		foreach ($posts as $post) {

			$id = self::SIDEBAR_PREFIX . $post->ID;
			$host = get_post_meta($post->ID, self::PREFIX . 'host', true);

			// Check for correct handling and if host exist
			if ($post->handle == 2 || !isset($sidebars_widgets[$host]))
				continue;

			// Sidebar might not have any widgets. Get it anyway!
			if (!isset($sidebars_widgets[$id]))
				$sidebars_widgets[$id] = array();

			// If handle is merge or if handle is replace and host has already been replaced
			if ($post->handle == 1 || ($post->handle == 0 && isset($handled_already[$host]))) {
				if (get_post_meta($post->ID, self::PREFIX . 'merge-pos', true))
					$sidebars_widgets[$host] = array_merge($sidebars_widgets[$host], $sidebars_widgets[$id]);
				else
					$sidebars_widgets[$host] = array_merge($sidebars_widgets[$id], $sidebars_widgets[$host]);
			} else {
				$sidebars_widgets[$host] = $sidebars_widgets[$id];
				$handled_already[$host] = 1;
			}
			
		}
		return $sidebars_widgets;
	}
	
	/**
	 * Show manually handled content aware sidebars
	 * @global array $_wp_sidebars_widgets
	 * @param  string|array $args 
	 * @return void 
	 */
	public function manual_sidebar($args) {
		global $_wp_sidebars_widgets;

		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'include' => '',
			'before'  => '<div id="sidebar" class="widget-area"><ul class="xoxo">',
			'after'   => '</ul></div>'
		));
		extract($args, EXTR_SKIP);

		// Get sidebars
		$posts = $this->get_sidebars();
		if (!$posts)
			return;

		// Handle include argument
		if (!empty($include)) {
			if (!is_array($include))
				$include = explode(',', $include);
			// Fast lookup
			$include = array_flip($include);
		}

		$i = $host = 0;
		foreach ($posts as $post) {

			$id = self::SIDEBAR_PREFIX . $post->ID;

			// Check for manual handling, if sidebar exists and if id should be included
			if ($post->handle != 2 || !isset($_wp_sidebars_widgets[$id]) || (!empty($include) && !isset($include[$post->ID])))
				continue;

			// Merge if more than one. First one is host.
			if ($i > 0) {
				if (get_post_meta($post->ID, self::PREFIX . 'merge-pos', true))
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host], $_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id], $_wp_sidebars_widgets[$host]);
			} else {
				$host = $id;
			}
			$i++;
		}

		if ($host) {
			echo $before;
			dynamic_sidebar($host);
			echo $after;
		}
	}

	/**
	 * Query sidebars according to content
	 * @global type $wpdb
	 * @return array|boolean 
	 */
	public function get_sidebars() {
		global $wpdb, $wp_query, $post;

		if(($wp_query->query == null && $post == null) || is_admin() || post_password_required())
			return false;
		
		// Return cache if present
		if(!empty($this->sidebar_cache)) {
			if($this->sidebar_cache[0] == false)
				return false;
			else
				return $this->sidebar_cache;
		}

		$context_data['WHERE'] = $context_data['JOIN'] = $context_data['EXCLUDE'] = array();
		$context_data = apply_filters('cas-context-data',$context_data);

		// Check if there are any rules for this type of content
		if(empty($context_data['WHERE']))
			return false;

		$context_data['WHERE'][] = "posts.post_type = '".self::TYPE_CONDITION_GROUP."'";
		$context_data['WHERE'][] = "posts.post_status ".(current_user_can('read_private_posts') ? "IN('publish','private')" : "= 'publish'")."";

		//Syntax changed in MySQL 5.5 and MariaDB 10.0 (reports as version 5.5)
		$wpdb->query('SET'.(version_compare($wpdb->db_version(), '5.5', '>=') ? '' : ' OPTION').' SQL_BIG_SELECTS = 1');

		$sidebars_in_context = $wpdb->get_results("
			SELECT
				posts.ID, posts.post_parent
			FROM $wpdb->posts posts
			".implode(' ',$context_data['JOIN'])."
			WHERE
			".implode(' AND ',$context_data['WHERE'])."
		",OBJECT_K);

		$valid = array();

		//Force update of meta cache to prevent lazy loading
		update_meta_cache('post',array_keys($sidebars_in_context));

		//Exclude sidebars that have unrelated content in same group
		foreach($sidebars_in_context as $key => $sidebar) {
			$valid[$sidebar->ID] = $sidebar->post_parent;
			//TODO: move to modules
			foreach($context_data['EXCLUDE'] as $exclude) {
				//quick fix to check for taxonomies terms
				if($exclude == 'taxonomies') {
					if($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE object_id = '{$sidebar->ID}'") > 0) {
						unset($valid[$sidebar->ID]);
						break;						
					}
				}
				if(get_post_custom_values(self::PREFIX . $exclude, $sidebar->ID) !== null) {
					unset($valid[$sidebar->ID]);
					break;
				}
			}
			
		}

		if(!empty($valid)) {

			$context_data = array();
			$context_data['JOIN'][] = "INNER JOIN $wpdb->postmeta handle ON handle.post_id = posts.ID AND handle.meta_key = '".self::PREFIX."handle'";
			$context_data['JOIN'][] = "INNER JOIN $wpdb->postmeta exposure ON exposure.post_id = posts.ID AND exposure.meta_key = '".self::PREFIX."exposure'";
			$context_data['WHERE'][] = "posts.post_type = '".self::TYPE_SIDEBAR."'";
			$context_data['WHERE'][] = "exposure.meta_value ".(is_archive() || is_home() ? '>' : '<')."= '1'";
			$context_data['WHERE'][] = "posts.post_status ".(current_user_can('read_private_posts') ? "IN('publish','private')" : "= 'publish'")."";
			$context_data['WHERE'][] = "posts.ID IN(".implode(',',$valid).")";

			$this->sidebar_cache = $wpdb->get_results("
				SELECT
					posts.ID,
					handle.meta_value handle
				FROM $wpdb->posts posts
				".implode(' ',$context_data['JOIN'])."
				WHERE
				".implode(' AND ',$context_data['WHERE'])."
				ORDER BY posts.menu_order ASC, handle.meta_value DESC, posts.post_date DESC
			");
			
		}
		
		// Return proper cache. If query was empty, tell the cache.
		return (empty($this->sidebar_cache) ? $this->sidebar_cache[0] = false : $this->sidebar_cache);
		
	}

	/**
	 * Remove unwanted meta boxes
	 * @return void 
	 */
	public function clear_admin_menu() {
		global $wp_meta_boxes;

		$screen = get_current_screen();		

		// Post type not set on all pages in WP3.1
		if(!(isset($screen->post_type) && $screen->post_type == self::TYPE_SIDEBAR && $screen->base == 'post'))
			return;

		// Names of whitelisted meta boxes
		$whitelist = array(
			'cas-news'      => 'cas-news',
			'cas-support'   => 'cas-support',
			'cas-groups'    => 'cas-groups',
			'cas-rules'     => 'cas-rules',
			'cas-options'   => 'cas-options',
			'submitdiv'     => 'submitdiv',
			'slugdiv'       => 'slugdiv'
		);

		// Loop through context (normal,advanced,side)
		foreach($wp_meta_boxes[self::TYPE_SIDEBAR] as $context_k => $context_v) {
			// Loop through priority (high,core,default,low)
			foreach($context_v as $priority_k => $priority_v) {
				// Loop through boxes
				foreach($priority_v as $box_k => $box_v) {
					// If box is not whitelisted, remove it
					if(!isset($whitelist[$box_k])) {
						$wp_meta_boxes[self::TYPE_SIDEBAR][$context_k][$priority_k][$box_k] = false;
						//unset($whitelist[$box_k]);
					}
				}
			}
		}
	}

	/**
	 * Meta boxes for sidebar edit
	 * @global object $post
	 * @return void 
	 */
	public function create_meta_boxes() {
		
		// Remove ability to set self to host
		if(get_the_ID())
			unset($this->metadata['host']['list'][self::SIDEBAR_PREFIX.get_the_ID()]);

		$boxes = array(
			//News
			array(
				'id'       => 'cas-news',
				'title'    => __('Get a free Content Aware Sidebars Premium Bundle', self::DOMAIN),
				'callback' => 'meta_box_news',
				'context'  => 'normal',
				'priority' => 'high'
			),
			//About
			// array(
			// 	'id'       => 'cas-support',
			// 	'title'    => __('Support the Author of Content Aware Sidebars', self::DOMAIN),
			// 	'callback' => 'meta_box_author_words',
			// 	'context'  => 'normal',
			// 	'priority' => 'high'
			// ),
			//Content
			array(
				'id'       => 'cas-rules',
				'title'    => __('Content', self::DOMAIN),
				'callback' => 'meta_box_rules',
				'context'  => 'normal',
				'priority' => 'high'
			),
			//Options
			array(
				'id'       => 'cas-options',
				'title'    => __('Options', self::DOMAIN),
				'callback' => 'meta_box_options',
				'context'  => 'side',
				'priority' => 'default'
			),
		);

		//Add meta boxes
		foreach($boxes as $box) {
			add_meta_box(
				$box['id'],
				$box['title'],
				array(&$this, $box['callback']),
				self::TYPE_SIDEBAR,
				$box['context'],
				$box['priority']
			);
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array( 
			'id'      => self::PREFIX.'help',
			'title'   => __('Condition Groups',self::DOMAIN),
			'content' => '<p>'.__('Each created condition group describe some specific content (conditions) that the current sidebar should be displayed with.',self::DOMAIN).'</p>'.
				'<p>'.__('Content added to a condition group uses logical conjunction, while condition groups themselves use logical disjunction. '.
				'This means that content added to a group should be associated, as they are treated as such, and that the groups do not interfere with each other. Thus it is possible to have both extremely focused and at the same time distinct conditions.',self::DOMAIN).'</p>',
		) );
		$screen->set_help_sidebar( '<h4>'.__('More Information').'</h4>'.
			'<p><a href="http://www.intox.dk/en/plugin/content-aware-sidebars-en/faq/" target="_blank">'.__('FAQ',self::DOMAIN).'</a></p>'.
			'<p><a href="http://wordpress.org/support/plugin/content-aware-sidebars" target="_blank">'.__('Get Support',self::DOMAIN).'</a></p>'
		);

	}
	
	/**
	 * Hide some meta boxes from start
	 * @param  array $hidden 
	 * @param  object $screen 
	 * @return array 
	 */
	public function change_default_hidden($hidden, $screen) {

		if ($screen->post_type == self::TYPE_SIDEBAR && get_user_option('metaboxhidden_sidebar') === false) {

			$hidden_meta_boxes = array('pageparentdiv');
			$hidden = array_merge($hidden, $hidden_meta_boxes);

			$user = wp_get_current_user();
			update_user_option($user->ID, 'metaboxhidden_sidebar', $hidden, true);
		}
		return $hidden;
	}

	/**
	 * Meta box for news
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.5
	 * @return  void
	 */
	public function meta_box_news() {
		// Use nonce for verification. Unique per sidebar
		wp_nonce_field(self::SIDEBAR_PREFIX.get_the_ID(), '_ca-sidebar-nonce');
		echo '<input type="hidden" id="current_sidebar" value="'.get_the_ID().'" />';
?>
		<div style="overflow:hidden;">
			<div style="float:left;width:40%;overflow:hidden">
				<p><?php _e('Translate Content Aware Sidebars into your language and become a BETA tester of the upcoming Premium Bundle*!',self::DOMAIN); ?></p>
				<a target="_blank" href="https://www.transifex.com/projects/p/content-aware-sidebars/" class="button button-primary" style="width:100%;text-align:center;margin-bottom:10px;"><?php _e('Translate Now',self::DOMAIN); ?></a>
				<a href="mailto:translate@intox.dk?subject=Premium Bundle BETA tester" class="button button-primary" style="width:100%;text-align:center;margin-bottom:10px;"><?php _e('Get Premium Bundle',self::DOMAIN); ?></a>
				<p><small>(*) <?php _e('Single-site use. BETA implies it is not recommended for production sites.',self::DOMAIN); ?></small></p>
			</div>
			<div style="float:left;width:60%;box-sizing:border-box;-moz-box-sizing:border-box;padding-left:25px;">
				<p><strong><?php _e('Partial Feature List',self::DOMAIN); ?></strong></p>
				<ul class="cas-feature-list">
					<li><?php _e('Select and create sidebars in the Post Editing Screens',self::DOMAIN); ?></li>
					<li><?php _e('Display sidebars with URLs using wildcards',self::DOMAIN); ?></li>
					<li><?php _e('Display sidebars with User Roles',self::DOMAIN); ?></li>
					<li><?php _e('Display sidebars with BuddyPress User Groups',self::DOMAIN); ?></li>
					<li><?php _e('Sidebars column in Post Type and Taxonomy Overview Screens',self::DOMAIN); ?></li>
				</ul>
			</div>

		</div>
<?php
	}

	/**
	 * Meta box for content rules
	 * @return void 
	 */
	public function meta_box_rules() {

		$groups = $this->_get_sidebar_groups(null,false);

		echo '<div id="cas-container">'."\n";
		echo '<div id="cas-accordion" class="accordion-container postbox'.(empty($groups) ? ' accordion-disabled' : '').'">'."\n";
		echo '<ul class="outer-border">';
		do_action('cas-module-admin-box');
		echo '</ul>';
		echo '</div>'."\n";
		echo '<div id="cas-groups" class="postbox'.(empty($groups) ? '' : ' cas-has-groups').'">'."\n";
		echo '<div class="cas-groups-header"><h3>'.__('Condition Groups',self::DOMAIN).'</h3><input type="button" class="button button-primary js-cas-group-new" value="'.__('Add New Group',self::DOMAIN).'" /></div>';
		echo '<div class="cas-groups-body"><p>'.__('Click to edit a group or create a new one. Select content on the left to add it. In each group, you can combine different types of associated content.',self::DOMAIN).'</p>';
		echo '<strong>'.__('Display sidebar with',self::DOMAIN).':</strong>';

		$i = 0;

		echo '<ul>';
		echo '<li class="cas-no-groups">'.__('No content. Please add at least one condition group to make the sidebar content aware.',self::DOMAIN).'</li>';
		foreach($groups as $group) {

			echo '<li class="cas-group-single'.($i == 0 ? ' cas-group-active' : '').'"><div class="cas-group-body">
			<span class="cas-group-control cas-group-control-active">
			<input type="button" class="button js-cas-group-save" value="'.__('Save',self::DOMAIN).'" /> | <a class="js-cas-group-cancel" href="#">'.__('Cancel',self::DOMAIN).'</a>
			</span>
			<span class="cas-group-control">
			<a class="js-cas-group-edit" href="#">'._x('Edit','group',self::DOMAIN).'</a> | <a class="submitdelete js-cas-group-remove" href="#">'.__('Remove',self::DOMAIN).'</a>
			</span>
			<div class="cas-content">';
			do_action('cas-module-print-data',$group->ID);
			echo '</div>
			<input type="hidden" class="cas_group_id" name="cas_group_id" value="'.$group->ID.'" />';

			echo '</div>';

			echo '<div class="cas-group-sep">'.__('Or',self::DOMAIN).'</div>';

			echo '</li>';	
			$i++;
		}
		echo '</ul>';

		
		echo '</div>';
		echo '<div class="cas-groups-footer">';
		echo '<input type="button" class="button button-primary js-cas-group-new" value="'.__('Add New Group',self::DOMAIN).'" />';
		echo '</div>';
		echo '</div>'."\n";
		echo '</div>'."\n";
		
	}

	/**
	 * Insert new condition group for sidebar
	 * Uses current sidebar per default
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @param  WP_Post|int    $post
	 * @return int
	 */
	private function _add_sidebar_group($post_id = null) {
		$post = get_post($post_id);

		$status = $post->post_status;
		//Make sure to go from auto-draft to draft
		if($status == 'auto-draft') {
			$status = 'draft';
			wp_update_post( array(
				'ID'          => $post->ID,
				'post_status' => $status
			));
		}

		return wp_insert_post(array(
			'post_status' => $status, 
			'post_type'   => self::TYPE_CONDITION_GROUP,
			'post_author' => $post->post_author,
			'post_parent' => $post->ID,
		));
	}

	/**
	 * Get condition groups for sidebar
	 * Uses current sidebar per default
	 * Creates the first group if necessary
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @param  WP_Post|int    $post_id
	 * @param  boolean        $create_first
	 * @return array
	 */
	private function _get_sidebar_groups($post_id = null, $create_first = false) {
		$post = get_post($post_id);

		$groups = get_posts(array(
			'posts_per_page'   => -1,
			'post_type'        => self::TYPE_CONDITION_GROUP,
			'post_parent'      => $post->ID,
			'post_status'      => 'any',
			'order'            => 'ASC'
		));
		if($groups == null && $create_first) {
			$group = $this->_add_sidebar_group($post);
			$groups[] = get_post($group);
		}

		return $groups;

	}

	/**
	 * AJAX call to add filters to a group
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return void
	 */
	public function add_sidebar_rule_ajax() {

		$response = array();

		try {
			if(!isset($_POST['current_id']) || 
				!check_ajax_referer(self::SIDEBAR_PREFIX.$_POST['current_id'],'token',false)) {
				$response = __('Unauthorized request',self::DOMAIN);
				throw new Exception("Forbidden",403);
			}

			//Make sure some rules are sent
			if(!isset($_POST['cas_condition'])) {
				$response = __('Condition group cannot be empty',self::DOMAIN);
				throw new Exception("Internal Server Error",500);
			}

			//If ID was not sent at this point, it is a new group
			if(!isset($_POST['cas_group_id'])) {
				$post_id = $this->_add_sidebar_group(intval($_POST['current_id']));
				$response['new_post_id'] = $post_id;
			} else {
				$post_id = intval($_POST['cas_group_id']);
			}

			do_action('cas-module-save-data',$post_id);

			$response['message'] = __('Condition group saved',self::DOMAIN);

			echo json_encode($response);
			
		} catch(Exception $e) {
			header("HTTP/1.1 ".$e->getCode()." ".$e->getMessage());
			echo $response;
		}
		die();
	}

	/**
	 * AJAX call to remove group from a sidebar
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return void
	 */
	public function remove_sidebar_group_ajax() {

		$response = "";

		try {
			if(!isset($_POST['current_id'],$_POST['cas_group_id'])) {
				$response = __('Unauthorized request',self::DOMAIN);
				throw new Exception("Forbidden",403);
			}	

			if(!check_ajax_referer(self::SIDEBAR_PREFIX.$_POST['current_id'],'token',false)) {
				$response = __('Unauthorized request',self::DOMAIN);
				throw new Exception("Forbidden",403);
			}

			if(wp_delete_post(intval($_POST['cas_group_id']), true) === false) {
				$response = __('Condition group could not be removed',self::DOMAIN);
				throw new Exception("Internal Server Error",500);
			}

			echo json_encode(array(
				'message' => __('Condition group removed',self::DOMAIN)
			));
			
		} catch(Exception $e) {
			header("HTTP/1.1 ".$e->getCode()." ".$e->getMessage());
			echo $response;
		}
		die();
	}

	/**
	 * Whenever a sidebar changes post_status
	 * Cascade that status to its groups
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @param  string    $new_status
	 * @param  string    $old_status
	 * @param  WP_Post   $post
	 * @return void
	 */
	public function cascade_sidebar_status($new_status, $old_status, $post) {
		if($post->post_type == self::TYPE_SIDEBAR && $old_status != $new_status) {
			global $wpdb;
			$wpdb->query("
				UPDATE $wpdb->posts
				SET post_status = '".$new_status."' 
				WHERE post_parent = '".$post->ID."' AND post_type = '".self::TYPE_CONDITION_GROUP."'
			");
		}	
	}
	
	/**
	 * Meta box for options
	 * @return void
	 */
	public function meta_box_options() {

		$columns = array(
			'exposure',
			'handle' => 'handle,host',
			'merge-pos'
		);

		foreach ($columns as $key => $value) {

			$id = is_numeric($key) ? $value : $key;

			echo '<span class="'.$id.'"><strong>' . $this->metadata[$id]['name'] . '</strong>';
			echo '<p>';
			$values = explode(',', $value);
			foreach ($values as $val) {
				$this->_form_field($val);
			}
			echo '</p></span>';
		}

		global $post; 

		echo '<span>';
		echo '<strong>'.__('Order').'</strong>';
		echo '<p><label for="menu_order" class="screen-reader-text">'.__('Order').'</label>';
		echo '<input type="number" value="'.$post->menu_order.'" id="menu_order" size="4" name="menu_order"></p></span>';
	}
		
	/**
	 * Meta box for author words
	 * @return void 
	 */
	public function meta_box_author_words() {

		// Use nonce for verification. Unique per sidebar
		wp_nonce_field(self::SIDEBAR_PREFIX.get_the_ID(), '_ca-sidebar-nonce');
		echo '<input type="hidden" id="current_sidebar" value="'.get_the_ID().'" />';
?>
			<div style="overflow:hidden;">
				<div style="float:left;width:40%;overflow:hidden">
					<p><strong><?php _e('If you love this plugin, please consider donating to support future development.', self::DOMAIN); ?></strong></p>
					<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=KPZHE6A72LEN4&amp;lc=US&amp;item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" 
						target="_blank" title="PayPal - The safer, easier way to pay online!">
							<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" width="147" height="47" alt="PayPal - The safer, easier way to pay online!">	
						</a>
						
					</p>
				</div>
				<div style="float:left;width:40%;border-left:#ebebeb 1px solid;border-right:#ebebeb 1px solid;box-sizing:border-box;-moz-box-sizing:border-box;">
					<p><strong><?php _e('Or you could:',self::DOMAIN); ?></strong></p>
					<ul>
						<li><a href="http://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?rate=5#postform" target="_blank"><?php _e('Rate the plugin on WordPress.org',self::DOMAIN); ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/content-aware-sidebars/" target="_blank"><?php _e('Link to the plugin page',self::DOMAIN); ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/content-aware-sidebars/" target="_blank"><?php _e('Translate the plugin into your language',self::DOMAIN); ?></a></li>
					</ul>
				</div>
				<div style="float:left;width:20%;">
					<p><a href="https://twitter.com/intoxstudio" class="twitter-follow-button" data-show-count="false">Follow @intoxstudio</a>
						<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
					<p>
						<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fintoxstudio&amp;width=450&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=false&amp;send=false&amp;appId=436031373100972" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:21px;" allowTransparency="true"></iframe>
					</p>
				</div>
			</div>
		<?php
	}
		
	/**
	 * Create form field for metadata
	 * @global object $post
	 * @param  array $setting 
	 * @return void 
	 */
	private function _form_field($setting) {

		$meta = get_post_meta(get_the_ID(), self::PREFIX . $setting, true);
		$setting = $this->metadata[$setting];
		$current = $meta != '' ? $meta : $setting['val'];
		switch ($setting['type']) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $setting['id'] . '">' . "\n";
				foreach ($setting['list'] as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting['list'] as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $setting['id'] . '[]" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $setting['id'] . '" value="' . $current . '" />' . "\n";
				break;
		}
	}
		
	/**
	 * Save meta values for post
	 * @param  int $post_id 
	 * @return void 
	 */
	public function save_post($post_id) {

		// Save button pressed
		if (!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;

		// Only sidebar type
		if (get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		// Verify nonce
		if (!check_admin_referer(self::SIDEBAR_PREFIX.$post_id, '_ca-sidebar-nonce'))
			return;

		// Check permissions
		if (!current_user_can(self::CAPABILITY, $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Load metadata
		$this->_init_metadata();

		// Update metadata
		foreach ($this->metadata as $field) {
			$new = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';
			$old = get_post_meta($post_id, self::PREFIX . $field['id'], true);

			if ($new != '' && $new != $old) {
				update_post_meta($post_id, self::PREFIX . $field['id'], $new);
			} elseif ($new == '' && $old != '') {
				delete_post_meta($post_id, self::PREFIX . $field['id'], $old);
			}
		}
	}

	/**
	 * Database data update module
	 * @return void 
	 */
	public function db_update() {
		cas_run_db_update(self::DB_VERSION);
	}

	/**
	 * Load scripts and styles for administration
	 * @param  string $hook 
	 * @return void 
	 */
	public function load_admin_scripts($hook) {

		$current_screen = get_current_screen();

		if($current_screen->post_type == self::TYPE_SIDEBAR) {
			
			wp_register_script('cas_admin_script', plugins_url('/js/cas_admin.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);
			
			wp_register_style('cas_admin_style', plugins_url('/css/style.css', __FILE__), array(), self::PLUGIN_VERSION);

			//Sidebar editor
			if ($current_screen->base == 'post') {

				if(!wp_script_is('accordion','registered')) {
					wp_register_script('accordion', plugins_url('/js/accordion.min.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);
				}
				wp_enqueue_script('accordion');
				wp_enqueue_script('cas_admin_script');
				wp_localize_script( 'cas_admin_script', 'CASAdmin', array(
					'save'          => __('Save',self::DOMAIN),
					'cancel'        => __('Cancel',self::DOMAIN),
					'or'            => __('Or',self::DOMAIN),
					'edit'          => _x('Edit','group',self::DOMAIN),
					'remove'        => __('Remove',self::DOMAIN),
					'confirmRemove' => __('Remove this group and its contents permanently?',self::DOMAIN),
					'noResults'     => __('No results found.',self::DOMAIN),
					'confirmCancel' => __('The current group has unsaved changes. Do you want to continue and discard these changes?', self::DOMAIN)
				));
				wp_enqueue_style('cas_admin_style');
			//Sidebar overview
			} else if ($hook == 'edit.php') {
				wp_enqueue_style('cas_admin_style');
			}			
		} else if($current_screen->base == 'widgets') {
			wp_register_style('cas_admin_style', plugins_url('/css/style.css', __FILE__), array(), self::PLUGIN_VERSION);
			wp_enqueue_style('cas_admin_style');

			wp_register_script('cas_admin_widgets', plugins_url('/js/widgets.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);
			wp_enqueue_script('cas_admin_widgets');
			wp_localize_script( 'cas_admin_widgets', 'CASAdmin', array(
				'edit' => __('Edit Sidebar', self::DOMAIN)
			));

		}

	}
	
	/**
	 * Load dependencies
	 * @return void
	 */
	private function _load_dependencies() {
		$path = plugin_dir_path( __FILE__ );
		require($path.'/walker.php');
		require($path.'/update_db.php');
	}

	/**
	 * Autoload module class files
	 * @author  Joachim Jensen <jv@intox.dk>
	 * @version 2.5
	 * @param   string    $class
	 * @return  boolean
	 */
	public function autoload_modules($class) {
		$path = plugin_dir_path( __FILE__ );
		if($class == 'CASModule') {
			require_once($path . "modules/" . strtolower($class) . ".php");
		} else if(strpos($class, "CASModule") !== false) {
			require_once($path . "modules/" . str_replace("CASModule_", "", $class) . ".php");
		}
	}

}

// Launch plugin
ContentAwareSidebars::instance();

/**
 * Template wrapper to display content aware sidebars
 * @param  array|string  $args 
 * @return void 
 */
function display_ca_sidebar($args = array()) {
	ContentAwareSidebars::instance()->manual_sidebar($args);
}

//eol