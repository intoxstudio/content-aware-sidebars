<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/en/plugin/content-aware-sidebars-en/
Description: Manage and show sidebars according to the content being viewed.
Version: 3.1
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

// Load dependencies
$cas_dir_path = plugin_dir_path( __FILE__ );

require($cas_dir_path.'/lib/wp-content-aware-engine/core.php');
require($cas_dir_path.'/app.php');
require($cas_dir_path.'/sidebar.php');
		
if(is_admin()) {
	require($cas_dir_path.'/lib/wp-db-updater/wp-db-updater.php');
	require($cas_dir_path.'/admin/db-updates.php');
	require($cas_dir_path.'/pointers.php');
	require($cas_dir_path.'/admin/sidebar-overview.php');
	require($cas_dir_path.'/admin/sidebar-edit.php');

}

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