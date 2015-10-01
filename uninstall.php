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

//Remove all sidebars, groups, meta and terms.
$sidebars = get_posts(array(
	"post_type"      => "sidebar",
	"posts_per_page" => -1
));
foreach ($sidebars as $sidebar) {
	$groups = get_posts(array(
		"post_parent"    => $sidebar->ID,
		"post_type"      => "condition_group",
		"posts_per_page" => -1
	));
	foreach($groups as $group) {
		wp_delete_post($group->ID,true);
	}
	wp_delete_post($sidebar->ID,true);
}

// Remove user meta
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN('metaboxhidden_sidebar','closedpostboxes_sidebar','managesidebarcolumnshidden','".$wpdb->prefix."_ca_cas_tour')");
