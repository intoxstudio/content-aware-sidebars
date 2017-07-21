<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: https://dev.institute/wordpress/sidebars-pro/
Description: Unlimited custom sidebars and widget areas for any post, page, category etc.
Version: 3.6.2
Author: Joachim Jensen
Author URI: https://dev.institute
Text Domain: content-aware-sidebars
Domain Path: /lang/
License: GPLv3

	Content Aware Sidebars Plugin
	Copyright (C) 2011-2017 Joachim Jensen - jv@intox.dk

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

* @fs_premium_only /lib/content-aware-premium/
*/

if (!defined('ABSPATH')) {
	exit;
}

if(!class_exists('CAS_App')) {

	// Load dependencies
	$cas_dir_path = plugin_dir_path( __FILE__ );

	require($cas_dir_path.'lib/wp-content-aware-engine/bootstrap.php');
	require($cas_dir_path.'app.php');

	if(is_admin()) {
		require($cas_dir_path.'lib/wp-db-updater/wp-db-updater.php');
		require($cas_dir_path.'lib/wp-pointer-tour/wp-pointer-tour.php');
		require($cas_dir_path.'admin/db-updates.php');
		require($cas_dir_path.'admin/admin.php');
		require($cas_dir_path.'admin/post_type_sidebar.php');
		require($cas_dir_path.'admin/sidebar-list-table.php');
		require($cas_dir_path.'admin/sidebar-overview.php');
		require($cas_dir_path.'admin/sidebar-edit.php');
		require($cas_dir_path.'admin/screen_widgets.php');
	}

	require($cas_dir_path.'sidebar.php');
	require($cas_dir_path.'freemius.php');

	// Launch plugin
	CAS_App::instance();

	/**
	 * Template wrapper to display content aware sidebars
	 *
	 * @since  3.0
	 * @param  array|string  $args 
	 * @return void 
	 */
	function ca_display_sidebar($args = array()) {
		CAS_App::instance()->manager()->manual_sidebar($args);
	}

}



//eol