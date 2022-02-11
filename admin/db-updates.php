<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

$cas_db_updater = CAS_App::instance()->get_updater();
$cas_db_updater->register_version_update('1.1', 'cas_update_to_11');
$cas_db_updater->register_version_update('2.0', 'cas_update_to_20');
$cas_db_updater->register_version_update('3.0', 'cas_update_to_30');
$cas_db_updater->register_version_update('3.1', 'cas_update_to_31');
$cas_db_updater->register_version_update('3.4', 'cas_update_to_34');
$cas_db_updater->register_version_update('3.5.1', 'cas_update_to_351');
$cas_db_updater->register_version_update('3.8', 'cas_update_to_38');
$cas_db_updater->register_version_update('3.15.2', 'cas_update_to_3152');
$cas_db_updater->register_version_update('3.17.1', 'cas_update_to_3171');

/**
 * Enable legacy date module and
 * negated conditions if in use
 *
 * Clear condition type cache
 *
 * @since 3.17.1
 *
 * @return bool
 */
function cas_update_to_3171()
{
    update_option('_ca_condition_type_cache', []);

    global $wpdb;

    $types = WPCACore::types()->all();

    $options = [
        'legacy.date_module'        => [],
        'legacy.negated_conditions' => []
    ];

    $options['legacy.date_module'] = array_flip((array)$wpdb->get_col("
        SELECT p.post_type FROM $wpdb->posts p
        INNER JOIN $wpdb->posts c on p.ID = c.post_parent
        INNER JOIN $wpdb->postmeta m on c.ID = m.post_id
        WHERE c.post_type = 'condition_group' AND m.meta_key = '_ca_date'
    "));

    $options['legacy.negated_conditions'] = array_flip((array)$wpdb->get_col("
        SELECT p.post_type FROM $wpdb->posts p
        INNER JOIN $wpdb->posts c on p.ID = c.post_parent
        WHERE c.post_type = 'condition_group' AND c.post_status = 'negated'
    "));

    foreach ($types as $type => $val) {
        foreach ($options as $option => $post_types) {
            if (isset($post_types[$type])) {
                WPCACore::save_option($type, $option, true);
            } elseif (WPCACore::get_option($type, $option, false)) {
                WPCACore::save_option($type, $option, false);
            }
        }
    }

    return true;
}

/**
 * Add -1 to condition groups with select terms
 *
 * @since 3.15.2
 *
 * @return bool
 */
function cas_update_to_3152()
{
    $taxonomies = array_map(function ($value) {
        return "'" . esc_sql($value) . "'";
    }, get_taxonomies(['public' => true]));

    if (empty($taxonomies)) {
        return true;
    }

    global $wpdb;

    $condition_group_ids = array_unique((array)$wpdb->get_col("
        SELECT p.ID FROM $wpdb->posts p
        INNER JOIN $wpdb->term_relationships r ON r.object_id = p.ID
        INNER JOIN $wpdb->term_taxonomy t ON t.term_taxonomy_id = r.term_taxonomy_id
        WHERE p.post_type = 'condition_group'
        AND t.taxonomy IN (" . implode(',', $taxonomies) . ')
    '));

    foreach ($condition_group_ids as $id) {
        add_post_meta($id, '_ca_taxonomy', '-1');
    }

    return true;
}

/**
 * Update to version 3.8
 *
 * @since  3.8
 * @return boolean
 */
function cas_update_to_38()
{
    global $wpdb;

    $time = time();

    $wpdb->query("
		UPDATE $wpdb->usermeta AS t
		INNER JOIN $wpdb->usermeta AS r ON t.user_id = r.user_id
		SET t.meta_value = '$time'
		WHERE t.meta_key = '{$wpdb->prefix}_ca_cas_tour'
		AND r.meta_key = '{$wpdb->prefix}_ca_cas_review'
		AND r.meta_value != '1'
		AND CAST(r.meta_value AS DECIMAL) <= 1522540800
	");

    $wpdb->query("
		DELETE FROM $wpdb->usermeta
		WHERE meta_key = '{$wpdb->prefix}_ca_cas_review'
		AND meta_value != '1'
		AND CAST(meta_value AS DECIMAL) <= 1522540800
	");

    return true;
}

/**
 * Update to version 3.5.1
 * Simplify auto select option
 *
 * @since  3.5.1
 * @return boolean
 */
function cas_update_to_351()
{
    global $wpdb;

    $group_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_value LIKE '_ca_sub_%'");
    foreach ($group_ids as $group_id) {
        add_post_meta($group_id, '_ca_autoselect', 1, true);
    }

    $wpdb->query("
		DELETE FROM $wpdb->postmeta
		WHERE meta_value LIKE '_ca_sub_%'
    ");

    return true;
}

/**
 * Version 3.3.3 -> 3.4
 * Inherit condition exposure from sidebar
 * Remove sidebar exposure
 *
 * @since  3.4
 * @return boolean
 */
function cas_update_to_34()
{
    global $wpdb;

    $wpdb->query("
		UPDATE $wpdb->posts AS c
		INNER JOIN $wpdb->posts AS s ON s.ID = c.post_parent
		INNER JOIN $wpdb->postmeta AS e ON e.post_id = s.ID
		SET c.menu_order = e.meta_value
		WHERE c.post_type = 'condition_group'
		AND e.meta_key = '_ca_exposure'
	");

    $wpdb->query("
		DELETE FROM $wpdb->postmeta
		WHERE meta_key = '_ca_exposure'
	");

    wp_cache_flush();

    return true;
}

/**
 * Version 3.0 -> 3.1
 * Remove flag about plugin tour for all users
 *
 * @since  3.1
 * @return boolean
 */
function cas_update_to_31()
{
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
function cas_update_to_30()
{
    global $wpdb;

    // Get all sidebars
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => 'sidebar',
        'post_status' => 'publish,pending,draft,future,private,trash'
    ]);

    if (!empty($posts)) {
        $wpdb->query("
			UPDATE $wpdb->posts
			SET post_type = 'condition_group', post_status = 'publish'
			WHERE post_type = 'sidebar_group'
		");

        $metadata = [
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
        ];

        foreach ($metadata as $old_key => $new_key) {
            $wpdb->query("
				UPDATE $wpdb->postmeta
				SET meta_key = '_ca_" . $new_key . "'
				WHERE meta_key = '_cas_" . $old_key . "'
			");
            switch ($new_key) {
                case 'author':
                case 'page_template':
                    $wpdb->query("
						UPDATE $wpdb->postmeta
						SET meta_value = '" . $new_key . "'
						WHERE meta_key = '_ca_" . $new_key . "'
						AND meta_value = '" . $old_key . "'
					");
                    break;
                case 'post_type':
                case 'taxonomy':
                    $wpdb->query("
						UPDATE $wpdb->postmeta
						SET meta_value = REPLACE(meta_value, '_cas_sub_', '_ca_sub_')
						WHERE meta_key = '_ca_" . $new_key . "'
						AND meta_value LIKE '_cas_sub_%'
					");
                    break;
                default:
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
function cas_update_to_20()
{
    global $wpdb;

    $module_keys = [
        'static',
        'post_types',
        'authors',
        'page_templates',
        'taxonomies',
        'language',
        'bb_profile',
        'bp_member'
    ];

    // Get all sidebars
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => 'sidebar',
        'post_status' => 'publish,pending,draft,future,private,trash'
    ]);
    if (!empty($posts)) {
        foreach ($posts as $post) {
            //Create new condition group
            $group_id = wp_insert_post([
                'post_status' => $post->post_status,
                'post_type'   => 'sidebar_group',
                'post_author' => $post->post_author,
                'post_parent' => $post->ID,
            ]);

            if ($group_id) {
                //Move module data to condition group
                $wpdb->query("
					UPDATE $wpdb->postmeta
					SET post_id = '" . $group_id . "'
					WHERE meta_key IN ('_cas_" . implode("','_cas_", $module_keys) . "')
					AND post_id = '" . $post->ID . "'
				");

                //Move term data to condition group
                $wpdb->query("
					UPDATE $wpdb->term_relationships
					SET object_id = '" . $group_id . "'
					WHERE object_id = '" . $post->ID . "'
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
function cas_update_to_11()
{
    $moduledata = [
        'static',
        'post_types',
        'authors',
        'page_templates',
        'taxonomies',
        'language'
    ];

    // Get all sidebars
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => 'sidebar',
        'post_status' => 'publish,pending,draft,future,private,trash'
    ]);

    if (!empty($posts)) {
        foreach ($posts as $post) {
            foreach ($moduledata as $field) {
                // Remove old serialized data and insert it again properly
                $old = get_post_meta($post->ID, '_cas_' . $field, true);
                if ($old != '') {
                    delete_post_meta($post->ID, '_cas_' . $field, $old);
                    foreach ((array)$old as $new_single) {
                        add_post_meta($post->ID, '_cas_' . $field, $new_single);
                    }
                }
            }
        }
    }

    return true;
}
