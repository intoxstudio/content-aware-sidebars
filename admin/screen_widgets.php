<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class CAS_Admin_Screen_Widgets extends CAS_Admin
{

    /**
     * Get current screen
     *
     * @since  3.4
     * @return string
     */
    public function get_screen()
    {
        return 'widgets.php';
    }

    /**
     * Authorize user for screen
     *
     * @since  3.5
     * @return boolean
     */
    public function authorize_user()
    {
        return true;
    }

    /**
     * Prepare screen load
     *
     * @since  3.4
     * @return void
     */
    public function prepare_screen()
    {
        $this->add_action('dynamic_sidebar_before', 'render_sidebar_controls');
        $this->add_filter('admin_body_class', 'widget_manager_class');

        global $wp_registered_sidebars;

        $manager = CAS_App::instance()->manager();
        $manager->populate_metadata();

        $has_host = [0 => 1,1 => 1,3 => 1];

        foreach ($manager->sidebars as $id => $post) {
            $handle_meta = $manager->metadata()->get('handle');

            $args = [];
            $args['description'] = $handle_meta->get_list_data($post->ID, true);

            if (isset($has_host[$handle_meta->get_data($post->ID)])) {
                $hosts = $manager->metadata()->get('host')->get_data($post->ID, false, false);
                if ($hosts) {
                    $list = $manager->metadata()->get('host')->get_input_list();
                    $data = [];
                    foreach ($hosts as $host) {
                        if (!isset($list[$host])) {
                            $data[] = __('Target not found', 'content-aware-sidebars');
                        } else {
                            $data[] = $list[$host];
                        }
                    }
                    $args['description'] .= ': ' .  implode(', ', $data);
                }
            }

            $wp_registered_sidebars[$id] = array_merge($wp_registered_sidebars[$id], $args);
        }
    }

    /**
     * Add filters and actions for admin dashboard
     * e.g. AJAX calls
     *
     * @since  3.5
     * @return void
     */
    public function admin_hooks()
    {
        $this->add_action('wp_ajax_cas_sidebar_status', 'ajax_set_sidebar_status');
    }

    /**
     * Set post type status on AJAX
     *
     * @since  3.5
     * @return void
     */
    public function ajax_set_sidebar_status()
    {
        if (!isset($_POST['sidebar_id'],$_POST['status'],$_POST['token'])) {
            wp_send_json_error('400 Bad Request');
        }

        $sidebar_id = $_POST['sidebar_id'];

        if (!wp_verify_nonce($_POST['token'], CAS_Admin::NONCE_PREFIX_1CLICK.$sidebar_id)) {
            wp_send_json_error('403 Forbidden');
        }

        if (!current_user_can(CAS_App::CAPABILITY, $sidebar_id)) {
            wp_send_json_error('401 Unauthorized');
        }

        $data = [];
        $status = filter_var($_POST['status'], FILTER_VALIDATE_BOOLEAN);
        if ($status) {
            $data = [
                'ID'            => $sidebar_id,
                'post_status'   => CAS_App::STATUS_ACTIVE,
                'post_date'     => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', true)
            ];
        } else {
            $data = [
                'ID'          => $sidebar_id,
                'post_status' => CAS_App::STATUS_INACTIVE
            ];
        }

        if (!wp_update_post($data)) {
            wp_send_json_error('409 Conflict');
        }

        $data['title'] = $status ? __('Active', 'content-aware-sidebars') : __('Inactive', 'content-aware-sidebars');
        //$data['message'] = sprintf(__('Status set to %s'),$data['title']);

        wp_send_json_success($data);
    }

    /**
     * Add body class to enable widget manager
     *
     * @since  3.6
     * @param  string  $classes
     * @return string
     */
    public function widget_manager_class($classes)
    {
        $enhanced_enabled = apply_filters('cas/module/widget_manager', true);
        if ($enhanced_enabled && version_compare(get_bloginfo('version'), '4.7', '>=')) {
            $classes .= ' cas-widget-manager ';
        }
        return $classes;
    }

    /**
     * Render controls for custom sidebars
     *
     * @since  3.3
     * @param  string  $index
     * @return void
     */
    public function render_sidebar_controls($index)
    {
        //trashed custom sidebars not included
        $sidebars = CAS_App::instance()->manager()->sidebars;
        if (isset($sidebars[$index])) {
            $sidebar = $sidebars[$index];
            $link = admin_url('post.php?post='.$sidebar->ID);
            $edit_link = admin_url('admin.php?page=wpcas-edit&sidebar_id='.$sidebar->ID);
            $more_link = esc_url('https://dev.institute/wordpress-sidebars/pricing/?utm_source=plugin&utm_medium=popup&utm_content=widget-revisions&utm_campaign=cas');

            switch ($sidebar->post_status) {
                case CAS_App::STATUS_ACTIVE:
                    $status = __('Active', 'content-aware-sidebars');
                    break;
                case CAS_App::STATUS_SCHEDULED:
                    $status = __('Scheduled');
                    break;
                default:
                    $status = __('Inactive', 'content-aware-sidebars');
            }

            echo '<div class="cas-settings">';
            echo '<div class="sidebar-status">';
            echo '<input type="checkbox" class="sidebar-status-input sidebar-status-'.$sidebar->post_status.'"
                id="cas-status-'.$sidebar->ID.'" data-nonce="'.wp_create_nonce(CAS_Admin::NONCE_PREFIX_1CLICK.$sidebar->ID).'"
                value="'.$sidebar->ID.'" '.checked($sidebar->post_status, CAS_App::STATUS_ACTIVE, false).'>';
            echo '<label title="'.$status.'" class="sidebar-status-label" for="cas-status-'.$sidebar->ID.'">';
            echo '</label>';
            echo '</div>';
            echo '<a title="'.esc_attr__('Widget Revisions', 'content-aware-sidebars').'" class="js-cas-pro-notice cas-sidebar-link dashicons dashicons-backup" data-url="'.$more_link.'" href="'.add_query_arg('action', 'cas-revisions', $link).'"></a>';
            echo '<a title="'.esc_attr__('Sidebar Conditions', 'content-aware-sidebars').'" class="dashicons dashicons-visibility cas-sidebar-link" href="'.$edit_link.'"></a>';
            echo '<a title="'.esc_attr__('Schedule Sidebar', 'content-aware-sidebars').'" class="dashicons dashicons-calendar cas-sidebar-link" href="'.$edit_link.'#top#section-schedule"></a>';
            echo '<a title="'.esc_attr__('Design Sidebar', 'content-aware-sidebars').'" class="dashicons dashicons-admin-appearance cas-sidebar-link" href="'.$edit_link.'#top#section-design"></a>';
            echo '<a title="'.esc_attr__('Edit Sidebar', 'content-aware-sidebars').'" class="dashicons dashicons-admin-generic cas-sidebar-link" href="'.$edit_link.'"></a>';
            echo '</div>';
        }
    }

    /**
     * Register and enqueue scripts styles
     * for screen
     *
     * @since 3.4
     */
    public function add_scripts_styles()
    {
        $this->enqueue_script('cas/admin/widgets', 'widgets', ['jquery'], '', true);
        wp_localize_script('cas/admin/widgets', 'CASAdmin', [
            'addNew'         => $this->get_sidebar_type()->labels->add_new_item,
            'collapse'       => __('Collapse', 'content-aware-sidebars'),
            'expand'         => __('Expand', 'content-aware-sidebars'),
            'filterSidebars' => __('Search Sidebars', 'content-aware-sidebars'),
            'filterWidgets'  => __('Search Widgets', 'content-aware-sidebars')
        ]);
    }
}
