<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

global $wpdb;

// Remove db version
delete_option('cas_db_version');

//Remove sidebars, sidebar groups and (if not null) their terms and metadata
$wpdb->query("DELETE p.*,pm.*,tr.* FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id LEFT JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id WHERE p.post_type = 'sidebar' OR p.post_type = 'sidebar_group'");

// Remove user meta
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN('metaboxhidden_sidebar','closedpostboxes_sidebar')");
