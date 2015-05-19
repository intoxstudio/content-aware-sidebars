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

//Remove all sidebars, meta and terms. Synced with condition groups
$sidebars = get_posts(array(
	"post_type" => "sidebar",
	"posts_per_page" => -1
));
foreach ($sidebars as $sidebar) {
	wp_delete_post($sidebar->ID,true);
}

// Remove user meta
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN('metaboxhidden_sidebar','closedpostboxes_sidebar')");
