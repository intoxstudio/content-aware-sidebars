<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

if (!defined('CAS_App::PLUGIN_VERSION')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

$cas_db_updater = new WP_DB_Updater("cas_db_version",CAS_App::PLUGIN_VERSION);
$cas_db_updater->register_version_update("0.8","cas_update_to_08");
$cas_db_updater->register_version_update("1.1","cas_update_to_11");
$cas_db_updater->register_version_update("2.0","cas_update_to_20");
$cas_db_updater->register_version_update("3.0","cas_update_to_30");
$cas_db_updater->register_version_update("3.1","cas_update_to_31");

/**
 * Version 3.0 -> 3.1
 * Remove flag about plugin tour for all users
 *
 * @since  3.1
 * @return boolean
 */
function cas_update_to_31() {
	global $wpdb;
	$wpdb->query("
		DELETE FROM $wpdb->usermeta
		WHERE meta_key = '{$wpdb->prefix}_ca_cas_tour'
	");
	return true;
}

/**
 * Version 2.0 -> 3.0
 * Data key prefices will use that from WP Content Aware Engine
 * Condition group post type made generic
 * Module id convention made consistent
 *
 * @since  3.0
 * @return boolean
 */
function cas_update_to_30() {
	global $wpdb;

	// Get all sidebars
	$posts = get_posts(array(
		'numberposts'     => -1,
		'post_type'       => 'sidebar',
		'post_status'     => 'publish,pending,draft,future,private,trash'
	));

	if(!empty($posts)) {

		$wpdb->query("
			UPDATE $wpdb->posts
			SET post_type = 'condition_group', post_status = 'publish'
			WHERE post_type = 'sidebar_group'
		");

		$metadata = array(
			'post_types'     => 'post_type',
			'taxonomies'     => 'taxonomy',
			'authors'        => 'author',
			'page_templates' => 'page_template',
			'static'         => 'static',
			'bb_profile'     => 'bb_profile',
			'bp_member'      => 'bp_member',
			'date'           => 'date',
			'language'       => 'language',
			'exposure'       => 'exposure',
			'handle'         => 'handle',
			'host'           => 'host',
			'merge-pos'      => 'merge_pos'
		);
		
		foreach($metadata as $old_key => $new_key) {
			$wpdb->query("
				UPDATE $wpdb->postmeta 
				SET meta_key = '_ca_".$new_key."' 
				WHERE meta_key = '_cas_".$old_key."'
			");
			switch($new_key) {
				case "author":
				case "page_template":
					$wpdb->query("
						UPDATE $wpdb->postmeta 
						SET meta_value = '".$new_key."' 
						WHERE meta_key = '_ca_".$new_key."' 
						AND meta_value = '".$old_key."'
					");
					break;
				case "post_type":
				case "taxonomy":
					$wpdb->query("
						UPDATE $wpdb->postmeta 
						SET meta_value = REPLACE(meta_value, '_cas_sub_', '_ca_sub_') 
						WHERE meta_key = '_ca_".$new_key."' 
						AND meta_value LIKE '_cas_sub_%'
					");
					break;
			}
		}

		// Clear cache for new meta keys
		wp_cache_flush();
	}

	return true;
}

/**
 * Version 1.1 -> 2.0
 * Moves module data for each sidebar to a condition group
 * 
 * @author Joachim Jensen <jv@intox.dk>
 * @since  2.0
 * @return boolean
 */
function cas_update_to_20() {
	global $wpdb;

	$module_keys = array(
		'static',
		'post_types',
		'authors',
		'page_templates',
		'taxonomies',
		'language',
		'bb_profile',
		'bp_member'
	);

	// Get all sidebars
	$posts = get_posts(array(
		'numberposts'     => -1,
		'post_type'       => 'sidebar',
		'post_status'     => 'publish,pending,draft,future,private,trash'
	));
	if(!empty($posts)) {
		foreach($posts as $post) {

			//Create new condition group
			$group_id = wp_insert_post(array(
				'post_status'           => $post->post_status, 
				'post_type'             => 'sidebar_group',
				'post_author'           => $post->post_author,
				'post_parent'           => $post->ID,
			));

			if($group_id) {

				//Move module data to condition group
				$wpdb->query("
					UPDATE $wpdb->postmeta 
					SET post_id = '".$group_id."' 
					WHERE meta_key IN ('_cas_".implode("','_cas_",$module_keys)."')
					AND post_id = '".$post->ID."'
				");

				//Move term data to condition group
				$wpdb->query("
					UPDATE $wpdb->term_relationships 
					SET object_id = '".$group_id."' 
					WHERE object_id = '".$post->ID."'
				");

			}

		}		
	}

	return true;

}

/**
 * Version 0.8 -> 1.1
 * Serialized metadata gets their own rows
 * 
 * @return boolean 
 */
function cas_update_to_11() {
	
	$moduledata = array(
		'static',
		'post_types',
		'authors',
		'page_templates',
		'taxonomies',
		'language'
	);
	
	// Get all sidebars
	$posts = get_posts(array(
		'numberposts'     => -1,
		'post_type'       => 'sidebar',
		'post_status'     => 'publish,pending,draft,future,private,trash'
	));
	
	if(!empty($posts)) {
		foreach($posts as $post) {
			foreach($moduledata as $field) {
				// Remove old serialized data and insert it again properly
				$old = get_post_meta($post->ID, '_cas_'.$field, true);
				if($old != '') {
					delete_post_meta($post->ID, '_cas_'.$field, $old);
					foreach((array)$old as $new_single) {
						add_post_meta($post->ID, '_cas_'.$field, $new_single);
					}
				}
			}
		}
	}
	
	return true;
}

/**
 * Version 0 -> 0.8
 * Introduces database version management, adds preficed keys to metadata
 * 
 * @global object $wpdb
 * @return boolean 
 */
function cas_update_to_08() {
	global $wpdb;
	
	$metadata = array(
		'post_types',
		'taxonomies',
		'authors',
		'page_templates',
		'static',
		'exposure',
		'handle',
		'host',
		'merge-pos'
	);

	// Get all sidebars
	$posts = $wpdb->get_col($wpdb->prepare("
		SELECT ID 
		FROM $wpdb->posts 
		WHERE post_type = %s
	",'sidebar'));

	//Check if there is any
	if(!empty($posts)) {
		//Update the meta keys
		foreach($metadata as $meta) {
			$wpdb->query("
				UPDATE $wpdb->postmeta 
				SET meta_key = '_cas_".$meta."' 
				WHERE meta_key = '".$meta."' 
				AND post_id IN(".implode(',',$posts).")
			");
		}
		// Clear cache for new meta keys
		wp_cache_flush();
	}

	return true;
}

//eol