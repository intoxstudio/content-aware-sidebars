<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class CAS_Quick_Select
{
    const MODULE_NAME = 'post_type';
    const NONCE = '_cas_nonce';

    protected static $_theme_sidebars = [];

    public function __construct()
    {
        new CAS_Post_Type_Sidebar();
        add_action(
            'current_screen',
            [__CLASS__,'load_screen']
        );
    }

    /**
     * Load Quick Select on post screen
     *
     * @since  3.7
     * @param  WP_Screen  $screen
     * @return void
     */
    public static function load_screen($screen)
    {
        //We are on the post edit screen
        if ($screen->base == 'post' && $screen->post_type) {
            $module = WPCACore::types()->get(CAS_App::TYPE_SIDEBAR)->get(self::MODULE_NAME);
            if (!$module) {
                return;
            }

            $legacy_removal = !has_action('admin_init', ['CAS_Post_Type_Sidebar','initiate']);

            if ($legacy_removal) {
                _deprecated_hook(
                    "remove_action('admin_init', array('CAS_Post_Type_Sidebar', 'initiate'))",
                    '3.7',
                    "add_filter('cas/module/quick_select', '__return_false')"
                );
            }

            $enable = apply_filters(
                'cas/module/quick_select',
                !$legacy_removal,
                $screen->post_type
            );

            if (!$enable) {
                return;
            }

            $post_types = $module->post_types();
            self::get_theme_sidebars();
            if (isset($post_types[$screen->post_type]) && self::$_theme_sidebars) {
                add_action(
                    'add_meta_boxes_' . $screen->post_type,
                    [__CLASS__,'create_meta_boxes']
                );
                add_action(
                    'save_post_' . $screen->post_type,
                    [__CLASS__,'save_post_sidebars'],
                    10,
                    2
                );
                add_action(
                    'admin_enqueue_scripts',
                    [__CLASS__,'register_scripts'],
                    8
                );
                add_action(
                    'admin_enqueue_scripts',
                    [__CLASS__,'enqueue_scripts'],
                    11
                );
            }
        }
    }

    /**
     * Gather theme sidebars for later use
     *
     * @since  3.3
     * @return void
     */
    public static function get_theme_sidebars()
    {
        global $wp_registered_sidebars;

        $cas_sidebars = CAS_App::instance()->manager()->sidebars;

        foreach ($wp_registered_sidebars as $sidebar) {
            if (!isset($cas_sidebars[$sidebar['id']])) {
                self::$_theme_sidebars[$sidebar['id']] = [
                    'label'   => $sidebar['name'],
                    'options' => []
                ];
            }
        }
    }

    /**
     * Save sidebars and conditions for post
     *
     * @since  3.3
     * @param  int      $post_id
     * @param  WP_Post  $post
     * @return void
     */
    public static function save_post_sidebars($post_id, $post)
    {
        if (in_array($post_id, self::get_special_post_ids())) {
            return;
        }

        if (!(isset($_POST[self::NONCE])
            && wp_verify_nonce($_POST[self::NONCE], self::NONCE . $post_id))) {
            return;
        }

        // Check post permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ($post->post_status == 'auto-draft') {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $meta_key = WPCACore::PREFIX . self::MODULE_NAME;
        $new = isset($_POST['cas_sidebars']) ? $_POST['cas_sidebars'] : [];

        $relations = self::_get_content_sidebars($post);
        $user_can_create_sidebar = current_user_can(CAS_App::CAPABILITY);

        foreach ($new as $host => $sidebar_ids) {
            foreach ($sidebar_ids as $sidebar_id) {
                //Post has sidebar already
                if (isset($relations[$sidebar_id])) {
                    unset($relations[$sidebar_id]);
                //Discard empty
                } elseif ($sidebar_id) {
                    $condition_group_id = 0;

                    //New sidebar
                    //check permissions here
                    if ($sidebar_id[0] == '_') {
                        if ($user_can_create_sidebar) {
                            $id = wp_insert_post([
                                'post_title'  => str_replace('__', ',', substr($sidebar_id, 1)),
                                'post_status' => CAS_App::STATUS_INACTIVE,
                                'post_type'   => CAS_App::TYPE_SIDEBAR
                            ]);
                            if ($id) {
                                //wp_insert_post does not handle meta before WP4.4
                                add_post_meta($id, WPCACore::PREFIX . 'host', $host);
                                $condition_group_id = WPCACore::add_condition_group($id);
                            }
                        }
                    } else {
                        //Add post to group with other posts
                        $id = intval($sidebar_id);
                        $condition_groups = get_posts([
                            'posts_per_page' => 1,
                            'meta_key'       => $meta_key,
                            'meta_value'     => $post->post_type,
                            'meta_compare'   => '!=',
                            'post_parent'    => $id,
                            'post_type'      => WPCACore::TYPE_CONDITION_GROUP,
                            'post_status'    => WPCACore::STATUS_PUBLISHED
                        ]);
                        if ($condition_groups) {
                            $condition_group_id = $condition_groups[0]->ID;
                        } else {
                            $condition_group_id = WPCACore::add_condition_group($id);
                        }
                    }

                    if ($condition_group_id) {
                        add_post_meta($condition_group_id, $meta_key, $post_id);
                    }
                }
            }
        }

        //remove old relations
        //todo: sanity check if post is added to several groups?
        $sidebars = CAS_App::instance()->manager()->sidebars;
        $host_meta = CAS_App::instance()->manager()->metadata()->get('host');
        foreach ($relations as $sidebar_id => $group_id) {
            if (isset($sidebars[CAS_App::SIDEBAR_PREFIX . $sidebar_id])) {
                $host_ids = $host_meta->get_data($sidebar_id, false, false);
                foreach ($host_ids as $host_id) {
                    if (!isset(self::$_theme_sidebars[$host_id])) {
                        continue 2;
                    }
                }
            }

            //group with no post_type meta will be removed
            //even if it has other meta (unlikely)
            $group_meta = get_post_meta($group_id, $meta_key);
            if (count($group_meta) <= 1) {
                wp_delete_post($group_id);
            } else {
                delete_post_meta($group_id, $meta_key, $post_id);
            }
        }
    }

    /**
     * Create sidebar meta box for post types
     *
     * @since  3.3
     * @param  WP_Post  $post
     * @return void
     */
    public static function create_meta_boxes($post)
    {
        if (in_array($post->ID, self::get_special_post_ids())) {
            return;
        }

        $post_sidebars = self::_get_content_sidebars($post);

        $manager = CAS_App::instance()->manager();
        $host_meta = $manager->metadata()->get('host');
        foreach ($manager->sidebars as $sidebar) {
            $host_ids = $host_meta->get_data($sidebar->ID, false, false);
            foreach ($host_ids as $host_id) {
                if (isset(self::$_theme_sidebars[$host_id])) {
                    self::$_theme_sidebars[$host_id]['options'][$sidebar->ID] = [
                        'id'   => $sidebar->ID,
                        'text' => $sidebar->post_title . self::sidebar_states($sidebar)
                    ];
                    if (isset($post_sidebars[$sidebar->ID])) {
                        self::$_theme_sidebars[$host_id]['options'][$sidebar->ID]['select'] = 1;
                    }
                }
            }
        }

        $post_type = get_post_type_object($post->post_type);
        $content = [
            __('Author')
        ];
        if ($post_type->hierarchical) {
            $content[] = __('Child Page', 'content-aware-sidebars');
        }
        if ($post_type->name == 'page') {
            $content[] = __('Page Template', 'content-aware-sidebars');
        }
        $taxonomies = get_object_taxonomies($post, 'objects');
        if ($taxonomies) {
            foreach ($taxonomies as $tax) {
                $content[] = $tax->labels->singular_name;
            }
        }
        $content[] = __('Archive Page', 'content-aware-sidebars');

        $path = plugin_dir_path(dirname(__FILE__)) . 'view/';
        $view = WPCAView::make($path . 'sidebars_quick_select.php', [
            'post'     => $post,
            'sidebars' => self::$_theme_sidebars,
            'limit'    => 3,
            'content'  => $content,
            'singular' => $post_type->labels->singular_name,
            'nonce'    => wp_nonce_field(self::NONCE . $post->ID, self::NONCE, false, false)
        ]);

        add_meta_box(
            'cas-content-sidebars',
            __('Sidebars - Quick Select', 'content-aware-sidebars'),
            [$view, 'render'],
            $post->post_type,
            'side'
        );
    }

    /**
     * Get sidebar status for display
     *
     * @since  3.4.1
     * @param  WP_Post  $post
     * @return string
     */
    public static function sidebar_states($post)
    {
        switch ($post->post_status) {
            case CAS_App::STATUS_ACTIVE:
                $status = '';
                break;
            case CAS_App::STATUS_SCHEDULED:
                $status = ' (' . __('Scheduled') . ')';
                break;
            default:
                $status = ' (' . __('Inactive', 'content-aware-sidebars') . ')';
                break;
        }
        return $status;
    }

    /**
     * Register scripts and styles
     * We register early to make sure our select2 comes first
     *
     * @since  3.5.2
     * @param  string  $hook
     * @return void
     */
    public static function register_scripts($hook)
    {
        wp_register_script(
            'select2',
            plugins_url('lib/wp-content-aware-engine/assets/js/select2.min.js', dirname(__FILE__)),
            ['jquery'],
            '4.0.3'
        );
        wp_register_script('cas/sidebars/suggest', plugins_url('assets/js/suggest-sidebars.min.js', dirname(__FILE__)), ['select2'], CAS_App::PLUGIN_VERSION, true);
    }

    /**
     * Enqueue scripts and styles
     * We enqueue later to make sure our CSS takes precedence
     *
     * @since  3.5.2
     * @param  string  $hook
     * @return void
     */
    public static function enqueue_scripts($hook)
    {
        wp_enqueue_style(CAS_App::META_PREFIX . 'condition-groups');
        wp_enqueue_script('cas/sidebars/suggest');

        $labels = [
            'createNew' => __('Create New', 'content-aware-sidebars'),
            'labelNew'  => __('New', 'content-aware-sidebars')
        ];
        if (current_user_can(CAS_App::CAPABILITY)) {
            $labels['notFound'] = __('Type to Add New Sidebar', 'content-aware-sidebars');
        } else {
            $labels['notFound'] = __('No sidebars found', 'content-aware-sidebars');
        }
        wp_localize_script('cas/sidebars/suggest', 'CAS', $labels);
    }

    /**
     * @since 3.10.1
     *
     * @return array
     */
    protected static function get_special_post_ids()
    {
        $special_post_ids = [
            get_option('page_on_front'),
            get_option('page_for_posts'),
        ];

        if (defined('WC_VERSION')) {
            $special_post_ids[] = get_option('woocommerce_shop_page_id');
        }

        return $special_post_ids;
    }

    /**
     * Get sidebars for select post types
     *
     * @since  3.3
     * @param  WP_Post  $post
     * @return array
     */
    protected static function _get_content_sidebars($post)
    {
        $sidebars = [];
        if ($post) {
            global $wpdb;
            $query = $wpdb->get_results($wpdb->prepare(
                "SELECT s.ID, g.ID as group_id
				FROM $wpdb->posts s
				INNER JOIN $wpdb->posts g ON g.post_parent = s.ID
				INNER JOIN $wpdb->postmeta sm ON sm.post_id = s.ID AND sm.meta_key = '" . WPCACore::PREFIX . "handle'
				INNER JOIN $wpdb->postmeta gm ON gm.post_id = g.ID AND gm.meta_key = '" . WPCACore::PREFIX . self::MODULE_NAME . "'
				WHERE s.post_status <> 'trash'
				AND s.post_type = '" . CAS_App::TYPE_SIDEBAR . "'
				AND sm.meta_value IN (" . CAS_App::ACTION_REPLACE . ',' . CAS_App::ACTION_MERGE . ',' . CAS_App::ACTION_REPLACE_FORCED . ")
				AND g.post_status IN ('" . WPCACore::STATUS_PUBLISHED . "','" . WPCACore::STATUS_OR . "')
				AND gm.meta_value = %d
				ORDER BY s.post_title ASC",
                $post->ID
            ));
            if ($query) {
                foreach ($query as $sidebar) {
                    $sidebars[$sidebar->ID] = $sidebar->group_id;
                }
            }
        }
        return $sidebars;
    }
}

/**
 * Backwards compat for users disabling quick select
 * with remove_action('admin_init', array('CAS_Post_Type_Sidebar', 'initiate'))
 *
 * @deprecated  3.7
 * @see add_filter('cas/module/quick_select', ...)
 */
class CAS_Post_Type_Sidebar
{
    public function __construct()
    {
        add_action('admin_init', [__CLASS__,'initiate']);
    }

    public static function initiate()
    {
    }
}
