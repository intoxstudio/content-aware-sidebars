<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class CAS_App
{
    const PLUGIN_VERSION_KEY = 'cas_db_version';
    const PLUGIN_VERSION = '3.16.2';

    /**
     * Prefix for sidebar id
     */
    const SIDEBAR_PREFIX = 'ca-sidebar-';

    /**
     * Post Type for sidebars
     */
    const TYPE_SIDEBAR = 'sidebar';

    /**
     * Sidebar statuses
     */
    const STATUS_ACTIVE = 'publish';
    const STATUS_INACTIVE = 'draft';
    const STATUS_SCHEDULED = 'future';

    const ACTION_REPLACE = 0;
    const ACTION_MERGE = 1;
    const ACTION_SHORTCODE = 2;
    const ACTION_REPLACE_FORCED = 3;

    /**
     * Capability to manage sidebars
     */
    const CAPABILITY = 'edit_theme_options';

    /**
     * Base admin screen name
     */
    const BASE_SCREEN = 'wpcas';

    /**
     * Prefix for metadata keys
     */
    const META_PREFIX = '_ca_';

    /**
     * @var CAS_Sidebar_Manager
     */
    private $manager;

    /**
     * @var WP_DB_Updater
     */
    private $db_updater;

    /**
     * @var CAS_App
     */
    private static $_instance;

    /**
     * Instantiates and returns class singleton
     *
     * @return CAS_App
     */
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->manager = new CAS_Sidebar_Manager();

        $this->db_updater = new WP_DB_Updater(self::PLUGIN_VERSION_KEY, self::PLUGIN_VERSION, true);

        if (is_admin()) {
            new CAS_Sidebar_Overview();
            new CAS_Sidebar_Edit();
            new CAS_Quick_Select();
            new CAS_Admin_Screen_Widgets();
            new CAS_Admin_Settings();
        } else {
            new CAS_Admin_Bar();
        }

        $this->add_actions();
        $this->add_filters();
    }

    /**
     * @since  3.7.8
     * @return WP_DB_Updater
     */
    public function get_updater()
    {
        return $this->db_updater;
    }

    /**
     * @return CAS_Sidebar_Manager
     */
    public function manager()
    {
        return $this->manager;
    }

    /**
     * Add actions to queues
     *
     * @since  3.1
     * @return void
     */
    protected function add_actions()
    {
        add_action(
            'init',
            [$this,'load_textdomain']
        );
        add_action(
            'cas/event/deactivate',
            [$this,'scheduled_deactivation']
        );

        if (is_admin()) {
            add_action(
                'plugins_loaded',
                [$this,'redirect_revision_link']
            );
            add_action(
                'admin_menu',
                [$this, 'admin_menu_upsell'],
                999
            );
        }
    }

    public function admin_menu_upsell()
    {
        $cas_fs = cas_fs();
        if (!$cas_fs->can_use_premium_code()) {
            global $submenu;
            $submenu['wpcas'][] = [
                 __('Widget Cleaner', 'content-aware-sidebars'). ' (Pro)',
                 CAS_App::CAPABILITY,
                 $cas_fs->get_upgrade_url()
            ];
        }
    }

    /**
     * Add filters to queues
     *
     * @since  3.1
     * @return void
     */
    protected function add_filters()
    {
        if (is_admin()) {
            $file = plugin_basename(plugin_dir_path(__FILE__)).'/content-aware-sidebars.php';
            add_filter(
                'plugin_action_links_'.$file,
                [$this,'plugin_action_links'],
                99,
                4
            );
            /**
             * gutenberg disables widgets screen without user consent,
             * reenable by popular demand for now
             */
            add_filter('gutenberg_use_widgets_block_editor', '__return_false');
        }
    }

    /**
     * Load textdomain
     *
     * @since  3.0
     * @return void
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('content-aware-sidebars', false, dirname(plugin_basename(__FILE__)).'/lang/');
    }

    /**
     * Add actions to plugin in Plugins screen
     *
     * @version 2.4
     * @param   array     $actions
     * @param   string    $plugin_file
     * @param   [type]    $plugin_data
     * @param   [type]    $context
     * @return  array
     */
    public function plugin_action_links($actions, $plugin_file, $plugin_data, $context)
    {
        global $cas_fs;

        $new_actions = [];

        $new_actions['docs'] = '<a href="https://dev.institute/docs/content-aware-sidebars/?utm_source=plugin&utm_medium=referral&utm_content=plugin-list&utm_campaign=cas" target="_blank" rel="noopener">'.__('Docs & FAQ', 'content-aware-sidebars').'</a>';
        $new_actions['support'] = '<a href="'.esc_url($cas_fs->contact_url()).'">'.__('Premium Support', 'content-aware-sidebars').'</a>';

        if (!$cas_fs->has_active_valid_license()) {
            $new_actions['support'] = '<a href="'.esc_url($cas_fs->get_upgrade_url()).'">'.__('Premium Support', 'content-aware-sidebars').'</a>';
            unset($actions['upgrade']);
        }
        unset($actions['addons']);

        return array_merge($new_actions, $actions);
    }

    /**
     * Callback for scheduled deactivation
     *
     * @since  3.4
     * @param  int   $post_id
     * @return void
     */
    public function scheduled_deactivation($post_id)
    {
        $success = wp_update_post([
            'ID'          => $post_id,
            'post_status' => self::STATUS_INACTIVE
        ]);
        if ($success) {
            delete_post_meta($post_id, self::META_PREFIX.'deactivate_time');
        }
    }

    /**
     * Redirect revision link to upgrade
     *
     * @since  3.2
     * @return void
     */
    public function redirect_revision_link()
    {
        global $pagenow;
        if ($pagenow == 'post.php'
            && isset($_GET['action'],$_GET['post'])
            && $_GET['action'] == 'cas-revisions') {
            wp_safe_redirect(cas_fs()->get_upgrade_url());
            exit;
        }
    }
}
