<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/en/plugin/content-aware-sidebars-en/
Description: Manage and show sidebars according to the content being viewed.
Version: 3.0.1
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
	const DB_VERSION           = '3.0.1';

	/**
	 * Plugin version
	 */
	const PLUGIN_VERSION       = '3.0.1';

	/**
	 * Prefix for sidebar id
	 */
	const SIDEBAR_PREFIX       = 'ca-sidebar-';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR         = 'sidebar';

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
	 * @var WPCAObjectManager
	 */
	private $metadata;

	/**
	 * Store all sidebars here
	 * @var array
	 */
	private $sidebars          = array();

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
		
		//For administration
		if(is_admin()) {

			add_action('wp_loaded',
				array(&$this,'db_update'));
			add_action('admin_enqueue_scripts',
				array(&$this,'load_admin_scripts'));
			add_action('delete_post',
				array(&$this,'remove_sidebar_widgets'));
			add_action('save_post',
				array(&$this,'save_post'));
			add_action('add_meta_boxes_'.self::TYPE_SIDEBAR,
				array(&$this,'create_meta_boxes'));
			add_action('in_admin_header',
				array(&$this,'clear_admin_menu'),99);
			add_action('manage_'.self::TYPE_SIDEBAR.'_posts_custom_column',
				array(&$this,'admin_column_rows'),10,2);

			add_filter('request',
				array(&$this,'admin_column_orderby'));
			add_filter('manage_'.self::TYPE_SIDEBAR.'_posts_columns',
				array(&$this,'admin_column_headers'),99);
			add_filter('manage_edit-'.self::TYPE_SIDEBAR.'_sortable_columns',
				array(&$this,'admin_column_sortable_headers'));
			add_filter('post_row_actions',
				array(&$this,'sidebar_row_actions'),10,2);
			add_filter('post_updated_messages',
				array(&$this,'sidebar_updated_messages'));
			add_filter( 'bulk_post_updated_messages',
				array(&$this,'sidebar_updated_bulk_messages'), 10, 2 );
			add_filter('plugin_action_links_'.plugin_basename(__FILE__),
				array(&$this,'plugin_action_links'), 10, 4 );

		}

		add_action('sidebars_widgets', array(&$this,'replace_sidebar'));
		add_action('wp_head',array(&$this,'sidebar_notify_theme_customizer'));
		add_action('init', array(&$this,'load_textdomain'));
		add_action('init', array(&$this,'init_sidebar_type'),99);
		add_action('widgets_init', array(&$this,'create_sidebars'),99);
		add_action('wp_loaded', array(&$this,'update_sidebars'),99);

		add_shortcode( 'ca-sidebar', array($this,'sidebar_shortcode'));

	}

	/**
	 * Display sidebar with shortcode
	 * @version 2.5
	 * @param   array     $atts
	 * @param   string    $content
	 * @return  string
	 */
	public function sidebar_shortcode( $atts, $content = null ) {
		$a = shortcode_atts( array(
			'id' => 0,
		), $atts );
		
		$id = self::SIDEBAR_PREFIX.esc_attr($a['id']);
		ob_start();
		dynamic_sidebar($id);
		return ob_get_clean();
	}


	/**
	 * Add actions to plugin in Plugins screen
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
	 * 
	 * @global type $wp_customize
	 * @since  2.2
	 * @return void
	 */
	public function sidebar_notify_theme_customizer() {
		global $wp_customize;
		if(!empty($wp_customize)) {
			$sidebars = WPCACore::get_posts(self::TYPE_SIDEBAR);
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
	 * Load textdomain
	 *
	 * @since  3.0
	 * @return void 
	 */
	public function load_textdomain() {
		load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)).'/lang/');
	}

	/**
	 * Get instance of metadata manager
	 *
	 * @since  3.0
	 * @return WPCAObjectManager
	 */
	private function metadata() {
		if(!$this->metadata) {
			$this->metadata = new WPCAObjectManager();
		}
		return $this->metadata;
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

		$this->metadata()
		->add(new WPCAMeta(
			'exposure',
			__('Exposure', self::DOMAIN),
			1,
			'select',
			array(
				__('Singular', self::DOMAIN),
				__('Singular & Archive', self::DOMAIN),
				__('Archive', self::DOMAIN)
			)
		),'exposure')
		->add(new WPCAMeta(
			'handle',
			_x('Handle','option', self::DOMAIN),
			0,
			'select',
			array(
				0 => __('Replace', self::DOMAIN),
				1 => __('Merge', self::DOMAIN),
				2 => __('Manual', self::DOMAIN),
				3 => __('Forced replace',self::DOMAIN)
			),
			__('Replace host sidebar, merge with it or add sidebar manually.', self::DOMAIN)
		),'handle')
		->add(new WPCAMeta(
			'host',
			__('Host Sidebar', self::DOMAIN),
			'sidebar-1',
			'select',
			$sidebar_list
		),'host')
		->add(new WPCAMeta(
			'merge_pos',
			__('Merge Position', self::DOMAIN),
			1,
			'select',
			array(
				__('Top', self::DOMAIN),
				__('Bottom', self::DOMAIN)
			),
			__('Place sidebar on top or bottom of host when merging.', self::DOMAIN)
		),'merge_pos');
	}
	
	/**
	 * Create sidebar post type
	 * Add it to content aware engine
	 * 
	 * @return void 
	 */
	public function init_sidebar_type() {
		
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
				'not_found_in_trash' => __('No sidebars found in Trash', self::DOMAIN),
				//wp-content-aware-engine specific
				'ca_title'           => __('Display sidebar with',self::DOMAIN),
				'ca_not_found'       => __('No content. Please add at least one condition group to make the sidebar content aware.',self::DOMAIN)
			),
			'capabilities'  => array(
				'edit_post'          => self::CAPABILITY,
				'read_post'          => self::CAPABILITY,
				'delete_post'        => self::CAPABILITY,
				'edit_posts'         => self::CAPABILITY,
				'delete_posts'       => self::CAPABILITY,
				'edit_others_posts'  => self::CAPABILITY,
				'publish_posts'      => self::CAPABILITY,
				'read_private_posts' => self::CAPABILITY
			),
			'show_ui'       => true,
			'show_in_menu'  => true,
			'query_var'     => false,
			'rewrite'       => false,
			'menu_position' => 25.099, //less probable to be overwritten
			'supports'      => array('title','page-attributes'),
			'menu_icon'     => 'dashicons-welcome-widgets-menus'
		));

		WPCACore::post_types()->add(self::TYPE_SIDEBAR);
	}
	
	/**
	 * Create update messages
	 * 
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
	 * Create bulk update messages
	 *
	 * @since  3.0
	 * @param  array  $messages
	 * @param  array  $counts
	 * @return array
	 */
	public function sidebar_updated_bulk_messages( $messages, $counts ) {
		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets',self::DOMAIN));
		$messages[self::TYPE_SIDEBAR] = array(
			'updated'   => _n( '%s sidebar updated.', '%s sidebars updated.', $counts['updated'] ).$manage_widgets,
			'locked'    => _n( '%s sidebar not updated, somebody is editing it.', '%s sidebars not updated, somebody is editing them.', $counts['locked'] ),
			'deleted'   => _n( '%s sidebar permanently deleted.', '%s sidebars permanently deleted.', $counts['deleted'] ),
			'trashed'   => _n( '%s sidebar moved to the Trash.', '%s sidebars moved to the Trash.', $counts['trashed'] ),
			'untrashed' => _n( '%s sidebar restored from the Trash.', '%s sidebars restored from the Trash.', $counts['untrashed'] ),
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
	 * 
	 * @since  [since]
	 * @return void
	 */
	public function update_sidebars() {

		//Init metadata
		$this->_init_metadata();

		//Now reregister sidebars with proper content
		foreach($this->sidebars as $post) {

			$sidebar_args = array(
				"name"        => $post->post_title,
				"description" => $this->metadata()->get('handle')->get_list_data($post->ID,false),
				"id"          => self::SIDEBAR_PREFIX.$post->ID
			);

			if(!$sidebar_args["description"]) {
				continue;
			}

			$sidebar_args["before_widget"] = '<li id="%1$s" class="widget-container %2$s">';
			$sidebar_args["after_widget"] = '</li>';
			$sidebar_args["before_title"] = '<h4 class="widget-title">';
			$sidebar_args["after_title"] = '</h4>';

			if ($this->metadata()->get('handle')->get_data($post->ID) != 2) {
				$host = $this->metadata()->get('host')->get_list_data($post->ID,false);
				$sidebar_args["description"] .= ": " . ($host ? $host :  __('Please update Host Sidebar', self::DOMAIN) );

				//Set style from host to fix when content aware sidebar
				//is called directly by other sidebar managers
				global $wp_registered_sidebars;
				$host_id = $this->metadata()->get('host')->get_data($post->ID);
				if($wp_registered_sidebars[$host_id]) {
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
			'merge_pos' => __('Merge position', self::DOMAIN),
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
				'merge_pos' => 'merge_pos'
			), $columns
		);
	}
	
	/**
	 * Manage custom column sorting
	 * @param  array $vars 
	 * @return array 
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'], array('exposure', 'handle', 'merge_pos'))) {
			$vars = array_merge($vars, array(
				'meta_key' => WPCACore::PREFIX . $vars['orderby'],
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

		$retval = $this->metadata()->get($column_name);

		if($retval) {

			$retval = $retval->get_list_data($post_id);

			$data = $this->metadata()->get($column_name)->get_data($post_id);
			
			if ($column_name == 'handle' && $data != 2) {
				$host = $this->metadata()->get('host')->get_list_data($post_id);
				$retval .= ": " . ($host ? $host : '<span style="color:red;">' . __('Please update Host Sidebar', self::DOMAIN) . '</span>');
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
	 * @since  .
	 * @param  array    $sidebars_widgets
	 * @return array
	 */
	public function replace_sidebar($sidebars_widgets) {

		$posts = WPCACore::get_posts(self::TYPE_SIDEBAR);
		if ($posts) {
			foreach ($posts as $post) {

				$id = self::SIDEBAR_PREFIX . $post->ID;
				$host = $this->metadata()->get('host')->get_data($post->ID);

				// Check for correct handling and if host exist
				if ($post->handle == 2 || !isset($sidebars_widgets[$host]))
					continue;

				// Sidebar might not have any widgets. Get it anyway!
				if (!isset($sidebars_widgets[$id]))
					$sidebars_widgets[$id] = array();

				// If handle is merge or if handle is replace and host has already been replaced
				if ($post->handle == 1 || ($post->handle == 0 && isset($handled_already[$host]))) {
					if ($this->metadata()->get('merge_pos')->get_data($post->ID))
						$sidebars_widgets[$host] = array_merge($sidebars_widgets[$host], $sidebars_widgets[$id]);
					else
						$sidebars_widgets[$host] = array_merge($sidebars_widgets[$id], $sidebars_widgets[$host]);
				} else {
					$sidebars_widgets[$host] = $sidebars_widgets[$id];
					$handled_already[$host] = 1;
				}
				
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
		$posts = WPCACore::get_posts(self::TYPE_SIDEBAR);
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
				if ($this->metadata()->get('merge_pos')->get_data($post->ID))
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
			'cas-plugin-links' => 'cas-plugin-links',
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
		if(get_the_ID()) {
			$sidebar_list = $this->metadata()->get('host')->get_input_list();
			unset($sidebar_list[self::SIDEBAR_PREFIX.get_the_ID()]);
			$this->metadata()->get('host')->set_input_list($sidebar_list);
		}

		$boxes = array(
			array(
				'id'       => 'cas-plugin-links',
				'title'    => __('Content Aware Sidebars', self::DOMAIN),
				'callback' => 'meta_box_support',
				'context'  => 'side',
				'priority' => 'high'
			),
			//News
			// array(
			// 	'id'       => 'cas-news',
			// 	'title'    => __('Get a free Content Aware Sidebars Premium Bundle', self::DOMAIN),
			// 	'callback' => 'meta_box_news',
			// 	'context'  => 'normal',
			// 	'priority' => 'high'
			// ),
			//About
			// array(
			// 	'id'       => 'cas-support',
			// 	'title'    => __('Support the Author of Content Aware Sidebars', self::DOMAIN),
			// 	'callback' => 'meta_box_author_words',
			// 	'context'  => 'normal',
			// 	'priority' => 'high'
			// ),
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
			'id'      => WPCACore::PREFIX.'help',
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
	 * Meta box for news
	 * @version 2.5
	 * @return  void
	 */
	public function meta_box_news() {
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
	 * Meta box for options
	 * @return void
	 */
	public function meta_box_options() {

		$columns = array(
			'exposure',
			'handle' => 'handle,host',
			'merge_pos'
		);

		foreach ($columns as $key => $value) {

			$id = is_numeric($key) ? $value : $key;

			echo '<span class="'.$id.'"><strong>' . $this->metadata()->get($id)->get_title() . '</strong>';
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
	 * Meta box for info and support
	 *
	 * @since  3.0
	 * @return void 
	 */
	public function meta_box_support() {
		$locale = get_locale();
?>
			<div style="overflow:hidden;">
				<ul>
					<li><a href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?rate=5#postform" target="_blank"><?php _e('Give a review on WordPress.org',self::DOMAIN); ?></a></li>
<?php if($locale != "en_US") : ?>
					<li><a href="https://www.transifex.com/projects/p/content-aware-sidebars/" target="_blank"><?php _e('Translate the plugin into your language',self::DOMAIN); ?></a></li>
<?php endif; ?>
					<li><a href="http://www.intox.dk/en/plugin/content-aware-sidebars-en/faq/" target="_blank"><?php _e('Read the FAQ',self::DOMAIN); ?></a></li>
					<li><a href="https://wordpress.org/support/plugin/content-aware-sidebars/" target="_blank"><?php _e('Get Support',self::DOMAIN); ?></a></li>
				</ul>
			</div>
		<?php
	}

	/**
	 * Meta box for author words
	 * @return void 
	 */
	public function meta_box_author_words() {
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

		$setting = $this->metadata()->get($setting);
		$current = $setting->get_data(get_the_ID(),true);

		switch ($setting->get_input_type()) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $setting->get_id() . '">' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $setting->get_id() . '[]" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $setting->get_id() . '" value="' . $current . '" />' . "\n";
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
		if (!check_admin_referer(WPCACore::PREFIX.$post_id, WPCACore::NONCE))
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
		foreach ($this->metadata()->get_all() as $field) {
			$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : '';
			$old = $field->get_data($post_id);

			if ($new != '' && $new != $old) {
				$field->update($post_id,$new);
			} elseif ($new == '' && $old != '') {
				$field->delete($post_id,$old);
			}
		}
	}

	/**
	 * Database data update module
	 * @return void 
	 */
	public function db_update() {
		new CASPointerManager();
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
			
			wp_register_script('cas/admin/edit', plugins_url('/js/cas_admin.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);
			
			wp_register_style('cas/admin/style', plugins_url('/css/style.css', __FILE__), array(), self::PLUGIN_VERSION);

			//Sidebar editor
			if ($current_screen->base == 'post') {
				wp_enqueue_script('cas/admin/edit');
				wp_enqueue_style('cas/admin/style');
			//Sidebar overview
			} else if ($hook == 'edit.php') {
				wp_enqueue_style('cas/admin/style');
			}			
		} else if($current_screen->base == 'widgets') {
			wp_register_style('cas/admin/style', plugins_url('/css/style.css', __FILE__), array(), self::PLUGIN_VERSION);
			wp_enqueue_style('cas/admin/style');

			$sidebar = get_post_type_object(self::TYPE_SIDEBAR);

			wp_register_script('cas/admin/widgets', plugins_url('/js/widgets.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, true);
			wp_enqueue_script('cas/admin/widgets');
			wp_localize_script( 'cas/admin/widgets', 'CASAdmin', array(
				'edit'           => $sidebar->labels->edit_item,
				'addNew'         => $sidebar->labels->add_new_item,
				'filterSidebars' => __("Filter Sidebars",self::DOMAIN),
				'filterWidgets'  => __("Filter Widgets", self::DOMAIN)
			));

		}

	}
	
	/**
	 * Load dependencies
	 * @return void
	 */
	private function _load_dependencies() {
		$path = plugin_dir_path( __FILE__ );
		require($path.'/update_db.php');
		require($path.'/pointers.php');
		require($path.'/lib/wp-content-aware-engine/core.php');
	}

}

// Launch plugin
ContentAwareSidebars::instance();

/**
 * Template wrapper to display content aware sidebars
 *
 * @since  3.0
 * @param  array|string  $args 
 * @return void 
 */
function ca_display_sidebar($args = array()) {
	ContentAwareSidebars::instance()->manual_sidebar($args);
}

/**
 * Template wrapper to display content aware sidebars
 *
 * @deprecated 3.0           ca_display_sidebar()
 * @param      array|string  $args 
 * @return     void 
 */
function display_ca_sidebar($args = array()) {
	_deprecated_function( __FUNCTION__, '3.0', 'ca_display_sidebar()' );
	ca_display_sidebar($args);
}

//eol