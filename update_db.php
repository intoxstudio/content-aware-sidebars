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
 * Run updates
 * @param  float $current_version 
 * @return boolean
 */
function cas_run_db_update($current_version) {

	if(current_user_can('update_plugins')) {
		// Get current plugin db version
		$installed_version = get_option('cas_db_version',0);

		// Database is up to date
		if($installed_version == $current_version)
			return true;

		$versions = array('0.8','1.1','2.0');

		//Launch updates
		foreach($versions as $version) {

			$return = false;

			if(version_compare($installed_version,$version,'<')) {
				$function = 'cas_update_to_'.str_replace('.','',$version);
				if(function_exists($function)) {
					$return = $function();
					// Update database on success
					if($return) {
						update_option('cas_db_version',$installed_version = $version);
					}
				}
			}
		}
		return $return;
	}
	return false;
}

/**
 * Version 1.1 -> 2.0
 * Moves module data for each sidebar to a condition group
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
		'post_type'       => ContentAwareSidebars::TYPE_SIDEBAR,
		'post_status'     => 'publish,pending,draft,future,private,trash'
	));
	if(!empty($posts)) {
		foreach($posts as $post) {

			//Create new condition group
			$group_id = wp_insert_post(array(
				'post_status'           => $post->post_status, 
				'post_type'             => ContentAwareSidebars::TYPE_CONDITION_GROUP,
				'post_author'           => $post->post_author,
				'post_parent'           => $post->ID,
			));

			if($group_id) {

				//Move module data to condition group
				$wpdb->query("
					UPDATE $wpdb->postmeta 
					SET post_id = '".$group_id."' 
					WHERE meta_key IN ('".ContentAwareSidebars::PREFIX.implode("','".ContentAwareSidebars::PREFIX,$module_keys)."')
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
		'post_type'       => ContentAwareSidebars::TYPE_SIDEBAR,
		'post_status'     => 'publish,pending,draft,future,private,trash'
	));
	
	if(!empty($posts)) {
		foreach($posts as $post) {
			foreach($moduledata as $field) {
				// Remove old serialized data and insert it again properly
				$old = get_post_meta($post->ID, ContentAwareSidebars::PREFIX.$field, true);
				if($old != '') {
					delete_post_meta($post->ID, ContentAwareSidebars::PREFIX.$field, $old);
					foreach((array)$old as $new_single) {
						add_post_meta($post->ID, ContentAwareSidebars::PREFIX.$field, $new_single);
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
	",ContentAwareSidebars::TYPE_SIDEBAR));

	//Check if there is any
	if(!empty($posts)) {
		//Update the meta keys
		foreach($metadata as $meta) {
			$wpdb->query("
				UPDATE $wpdb->postmeta 
				SET meta_key = '".ContentAwareSidebars::PREFIX.$meta."' 
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