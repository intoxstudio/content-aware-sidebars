<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CAS_Sidebar_List_Table extends WP_List_Table
{
    /**
     * Trash view
     * @var boolean
     */
    private $is_trash;

    private $visibility = [];

    public function __construct($args = [])
    {
        parent::__construct([
            'singular' => 'sidebar',
            'plural'   => 'sidebars',
            'ajax'     => false,
            'screen'   => isset($args['screen']) ? $args['screen'] : null
        ]);
    }

    /**
     * Load filtered sidebars for current query
     *
     * @since  3.4
     * @return void
     */
    public function prepare_items()
    {
        global $avail_post_stati, $wp_query;

        $this->_column_headers = $this->get_column_info();

        $avail_post_stati = get_available_post_statuses(CAS_App::TYPE_SIDEBAR);

        $per_page = $this->get_items_per_page('cas_sidebars_per_page');
        $current_page = $this->get_pagenum();

        $args = [
            'post_type'   => CAS_App::TYPE_SIDEBAR,
            'post_status' => [
                CAS_App::STATUS_ACTIVE,
                CAS_App::STATUS_INACTIVE,
                CAS_App::STATUS_SCHEDULED
            ],
            'posts_per_page'         => $per_page,
            'paged'                  => $current_page,
            'orderby'                => 'title',
            'order'                  => 'asc',
            'update_post_term_cache' => false
        ];

        if (isset($_REQUEST['s']) && strlen($_REQUEST['s'])) {
            $args['s'] = $_REQUEST['s'];
        }

        //Make sure post_status!=all if present to avoid auto-drafts
        if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] != 'all') {
            $args['post_status'] = $_REQUEST['post_status'];
        }

        if (isset($_REQUEST['orderby'])) {
            $meta = str_replace('meta_', '', $_REQUEST['orderby']);
            if ($meta != $_REQUEST['orderby']) {
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = CAS_App::META_PREFIX . $meta;
            } else {
                $args['orderby'] = $_REQUEST['orderby'];
            }
        }

        if (isset($_REQUEST['order'])) {
            $args['order'] = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
        }

        $wp_query = new WP_Query($args);

        if ($wp_query->found_posts || $current_page === 1) {
            $total_items = $wp_query->found_posts;
        } else {
            $post_counts = (array) wp_count_posts(CAS_App::TYPE_SIDEBAR);

            if (isset($_REQUEST['post_status']) && in_array($_REQUEST['post_status'], $avail_post_stati)) {
                $total_items = $post_counts[$_REQUEST['post_status']];
            } else {
                $total_items = array_sum($post_counts);

                // Subtract post types that are not included in the admin all list.
                foreach (get_post_stati(['show_in_admin_all_list' => false]) as $state) {
                    $total_items -= $post_counts[$state];
                }
            }
        }

        $this->items = $wp_query->posts;
        $this->is_trash = isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'trash';
        $this->set_pagination_args([
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'per_page'    => $per_page
        ]);

        //Make sure filter is run
        CAS_App::instance()->manager()->populate_metadata();
    }

    /**
     * Render on no items
     *
     * @since  3.4
     * @return void
     */
    public function no_items()
    {
        if ($this->is_trash) {
            echo get_post_type_object(CAS_App::TYPE_SIDEBAR)->labels->not_found_in_trash;
        } else {
            //todo show more text to get started
            echo get_post_type_object(CAS_App::TYPE_SIDEBAR)->labels->not_found;
        }
    }

    /**
     * Get link to view
     *
     * @since  3.4
     * @param  array   $args
     * @param  string  $label
     * @param  string  $class
     * @return string
     */
    public function get_view_link($args, $label, $class = '')
    {
        $screen = get_current_screen();
        $args['page'] = $screen->parent_base;
        $url = add_query_arg($args, 'admin.php');

        $class_html = '';
        if (!empty($class)) {
            $class_html = sprintf(
                ' class="%s"',
                esc_attr($class)
            );
        }

        return sprintf(
            '<a href="%s"%s>%s</a>',
            esc_url($url),
            $class_html,
            $label
        );
    }

    /**
     * Get views (sidebar statuses)
     *
     * @since  3.4
     * @return array
     */
    public function get_views()
    {
        global $locked_post_status, $avail_post_stati;

        if (!empty($locked_post_status)) {
            return [];
        }

        $status_links = [];
        $num_posts = wp_count_posts(CAS_App::TYPE_SIDEBAR); //do not include private
        $total_posts = array_sum((array) $num_posts);
        $class = '';

        // Subtract post types that are not included in the admin all list.
        foreach (get_post_stati(['show_in_admin_all_list' => false]) as $state) {
            $total_posts -= $num_posts->$state;
        }

        if (empty($class) && (!isset($_REQUEST['post_status']) || isset($_REQUEST['all_posts']))) {
            $class = 'current';
        }

        $all_inner_html = sprintf(
            _nx(
                'All <span class="count">(%s)</span>',
                'All <span class="count">(%s)</span>',
                $total_posts,
                'sidebars',
                'content-aware-sidebars'
            ),
            number_format_i18n($total_posts)
        );

        $status_links['all'] = $this->get_view_link([], $all_inner_html, $class);

        //no way to change post status per post type, replace here instead
        $label_replacement = [
            CAS_App::STATUS_ACTIVE   => _n_noop('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'content-aware-sidebars'),
            CAS_App::STATUS_INACTIVE => _n_noop('Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'content-aware-sidebars')
        ];

        foreach (get_post_stati(['show_in_admin_status_list' => true], 'objects') as $status) {
            $class = '';

            $status_name = $status->name;

            if (!in_array($status_name, $avail_post_stati) || empty($num_posts->$status_name)) {
                continue;
            }

            if (isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status']) {
                $class = 'current';
            }

            $status_args = [
                'post_status' => $status_name
            ];

            $label_count = $status->label_count;
            if (isset($label_replacement[$status->name])) {
                $label_count = $label_replacement[$status->name];
            }

            $status_label = sprintf(
                translate_nooped_plural($label_count, $num_posts->$status_name),
                number_format_i18n($num_posts->$status_name)
            );

            $status_links[$status_name] = $this->get_view_link($status_args, $status_label, $class);
        }

        return $status_links;
    }

    /**
     * Get bulk actions
     *
     * @since  3.4
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [];
        $post_type_obj = get_post_type_object(CAS_App::TYPE_SIDEBAR);

        if (current_user_can($post_type_obj->cap->edit_posts)) {
            if ($this->is_trash) {
                $actions['untrash'] = __('Restore');
            } else {
                $actions['activate'] = __('Activate');
                $actions['deactivate'] = __('Deactivate');
            }
        }

        if (current_user_can($post_type_obj->cap->delete_posts)) {
            if ($this->is_trash || !EMPTY_TRASH_DAYS) {
                $actions['delete'] = __('Delete Permanently');
            } else {
                $actions['trash'] = __('Move to Trash');
            }
        }

        //todo: add filter
        return $actions;
    }

    /**
     * Render extra table navigation and actions
     *
     * @since  3.4
     * @param  string  $which
     * @return void
     */
    public function extra_tablenav($which)
    {
        echo '<div class="alignleft actions">';
        if ($this->is_trash && current_user_can(get_post_type_object(CAS_App::TYPE_SIDEBAR)->cap->edit_others_posts)) {
            submit_button(__('Empty Trash'), 'apply', 'delete_all', false);
        }
        echo '</div>';
    }

    /**
     * Get current action
     *
     * @since  3.4
     * @return string
     */
    public function current_action()
    {
        if (isset($_REQUEST['delete_all']) || isset($_REQUEST['delete_all2'])) {
            return 'delete_all';
        }

        return parent::current_action();
    }

    /**
     * Get columns
     *
     * @since  3.4
     * @return array
     */
    public function get_columns()
    {
        $posts_columns = [];
        $posts_columns['cb'] = '<input type="checkbox" />';
        $posts_columns['title'] = _x('Title', 'column name');
        $posts_columns['handler'] = _x('Action', 'option', 'content-aware-sidebars');
        $posts_columns['widgets'] = __('Widgets');
        $posts_columns['visibility'] = __('Visibility', 'content-aware-sidebars');
        $posts_columns['status'] = __('Status');

        return apply_filters('cas/admin/columns', $posts_columns);
    }

    /**
     * Get sortable columns
     *
     * @since  3.4
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'title'   => ['title', true],
            'status'  => 'post_status',
            'handler' => 'meta_handle'
        ];
    }

    /**
     * Get default column name
     *
     * @since  3.4
     * @return string
     */
    protected function get_default_primary_column_name()
    {
        return 'title';
    }

    /**
     * Get classes for rows
     * Older WP versions do not add striped
     *
     * @since  3.4
     * @return array
     */
    public function get_table_classes()
    {
        return ['widefat', 'fixed', 'striped', $this->_args['plural']];
    }

    /**
     * Render checkbox column
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @return void
     */
    public function column_cb($post)
    {
        if (current_user_can('edit_post', $post->ID)): ?>
<label class="screen-reader-text"
    for="cb-select-<?php echo $post->ID; ?>"><?php
                printf(__('Select %s'), _draft_or_post_title($post)); ?></label>
<input id="cb-select-<?php echo $post->ID; ?>" type="checkbox"
    name="post[]" value="<?php echo $post->ID; ?>" />
<div class="locked-indicator"></div>
<?php endif;
    }

    /**
     * Render title column wrapper
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @param  array    $classes
     * @param  array    $data
     * @param  string   $primary
     * @return void
     */
    protected function _column_title($post, $classes, $data, $primary)
    {
        echo '<td class="' . $classes . ' page-title" ', $data, '>';
        $this->column_title($post);
        echo '</td>';
    }

    /**
     * Render title column
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @return void
     */
    public function column_title($post)
    {
        echo '<strong>';

        $can_edit_post = current_user_can('edit_post', $post->ID);
        $title = _draft_or_post_title($post);

        if ($can_edit_post && $post->post_status != 'trash') {
            printf(
                '<a class="" href="%s" aria-label="%s">%s</a>',
                get_edit_post_link($post->ID),
                /* translators: %s: post title */
                esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)),
                $title
            );
        } else {
            echo $title;
        }

        echo "</strong>\n";

        if ($can_edit_post && $post->post_status != 'trash') {
            $lock_holder = wp_check_post_lock($post->ID);

            if ($lock_holder) {
                $lock_holder = get_userdata($lock_holder);
                $locked_avatar = get_avatar($lock_holder->ID, 18);
                $locked_text = esc_html(sprintf(__('%s is currently editing'), $lock_holder->display_name));
            } else {
                $locked_avatar = $locked_text = '';
            }

            echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
        }

        echo $this->handle_row_actions($post, 'title', 'title');
    }

    /**
     * Render sidebar action column
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @return void
     */
    public function column_handler($post)
    {
        $metadata = CAS_App::instance()->manager()->metadata();
        $action = $metadata->get('handle');

        if ($action) {
            switch ($action->get_data($post->ID)) {
                case CAS_App::ACTION_REPLACE:
                case CAS_App::ACTION_MERGE:
                case CAS_App::ACTION_REPLACE_FORCED:
                    $return = $action->get_list_data($post->ID);
                    $data = [];
                    $hosts = $metadata->get('host')->get_data($post->ID, false, false);
                    if ($hosts) {
                        $list = $metadata->get('host')->get_input_list();
                        foreach ($hosts as $host) {
                            if (isset($list[$host])) {
                                $data[] = $list[$host];
                            }
                        }
                    }

                    if (empty($data)) {
                        $data[] = '<span style="color:red;">' . __('Target not found', 'content-aware-sidebars') . '</span>';
                    }
                    $return .= ':<br> ' . implode(', ', $data);

                    if ($action->get_data($post->ID) == 1) {
                        $pos = $metadata->get('merge_pos')->get_data($post->ID, true);
                        $pos_icon = $pos ? 'up' : 'down';
                        $pos_title = [
                            __('Add sidebar at the top during merge', 'content-aware-sidebars'),
                            __('Add sidebar at the bottom during merge', 'content-aware-sidebars')
                        ];
                        $return .= '<span title="' . $pos_title[$pos] . '" class="dashicons dashicons-arrow-' . $pos_icon . '-alt"></span>';
                    }
                    echo $return;
                    break;
                case CAS_App::ACTION_SHORTCODE:
                    echo $action->get_list_data($post->ID) . ':<br>';
                    echo "<input type='text' value='[ca-sidebar id=\"$post->ID\"]' readonly />";
                    break;
                default:
                    do_action('cas/admin/columns/action', $post, $action);
                    break;
            }
        }
    }

    /**
     * Render sidebar widgets column
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @return void
     */
    public function column_widgets($post)
    {
        $sidebars_widgets = wp_get_sidebars_widgets();
        $count = isset($sidebars_widgets[CAS_App::SIDEBAR_PREFIX . $post->ID]) ? count($sidebars_widgets[CAS_App::SIDEBAR_PREFIX . $post->ID]) : 0;
        echo '<a href="' . admin_url('widgets.php#' . CAS_App::SIDEBAR_PREFIX . $post->ID) . '" title="' . esc_attr__('Manage Widgets', 'content-aware-sidebars') . '">' . $count . '</a>';
    }

    /**
     * Render sidebar visibility column
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @return void
     */
    public function column_visibility($post)
    {
        $metadata = CAS_App::instance()->manager()->metadata()->get('visibility');
        if ($metadata) {
            $data = $metadata->get_data($post->ID, true, false);
            if ($data) {
                if (!$this->visibility) {
                    $visibility = $metadata->get_input_list();
                    foreach ($visibility as $key => $options) {
                        if (is_array($options)) {
                            $this->visibility = $options['options'] + $this->visibility;
                        } else {
                            $this->visibility[$key] = $options;
                        }
                    }
                }

                $list = $this->visibility;
                foreach ($data as $k => $v) {
                    if (!isset($list[$v])) {
                        continue;
                    }
                    $data[$k] = $list[$v];
                }
                echo implode(', ', $data);
                return;
            }
        }
        _e('All Users', 'content-aware-sidebars');
    }

    /**
     * Render sidebar status column
     *
     * @since  3.4
     * @param  WP_Post  $sidebar
     * @return void
     */
    public function column_status($sidebar)
    {
        $icon = '';
        switch ($sidebar->post_status) {
            case CAS_App::STATUS_ACTIVE:
                $status = __('Active', 'content-aware-sidebars');
                $deactivate_date = get_post_meta($sidebar->ID, CAS_App::META_PREFIX . 'deactivate_time', true);
                if ($deactivate_date) {
                    $t_time = mysql2date(get_option('date_format'), $deactivate_date);

                    $icon = sprintf(__('Until %s', 'content-aware-sidebars'), $t_time);
                }

                break;
            case CAS_App::STATUS_SCHEDULED:
                $status = __('Scheduled');

                $t_time = get_post_time(get_option('date_format'), false, $sidebar, true);
                $time_diff = time() - get_post_time('G', true, $sidebar);

                $icon = sprintf(__('Scheduled for %s', 'content-aware-sidebars'), $t_time);

                if ($time_diff > 0) {
                    $icon .= ' ' . __('Missed schedule') . '!';
                }

                break;
            default:
                $status = __('Inactive', 'content-aware-sidebars');
        }

        echo '<div class="sidebar-status">';
        echo '<input type="checkbox" class="sidebar-status-input sidebar-status-' . $sidebar->post_status . '"
            id="cas-status-' . $sidebar->ID . '" data-nonce="' . wp_create_nonce(CAS_Admin::NONCE_PREFIX_1CLICK . $sidebar->ID) . '"
            value="' . $sidebar->ID . '" ' . checked($sidebar->post_status, CAS_App::STATUS_ACTIVE, false) . '>';
        echo '<label title="' . $status . '" class="sidebar-status-label" for="cas-status-' . $sidebar->ID . '">';
        echo '</label>';
        echo '</div>';

        if ($icon) {
            echo '<span class="dashicons dashicons-clock" title="' . $icon . '">';
            echo '</span>';
            echo '<span class="screen-reader-text">' . $icon . '</span>';
        }
    }

    /**
     * Render arbitrary column
     *
     * @since  3.4
     * @param  WP_post  $post
     * @param  string   $column_name
     * @return void
     */
    public function column_default($post, $column_name)
    {
        do_action('cas/admin/columns/default', $post, $column_name);
    }

    /**
     * Render row
     *
     * @since  3.4
     * @param  WP_Post  $item
     * @return void
     */
    public function single_row($item)
    {
        $class = '';
        if ($item->post_status == CAS_App::STATUS_ACTIVE) {
            $class = ' class="active"';
        }
        echo '<tr' . $class . '>';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     * Get row actions
     *
     * @since  3.4
     * @param  WP_Post  $post
     * @param  string  $column_name
     * @param  string  $primary
     * @return string
     */
    protected function handle_row_actions($post, $column_name, $primary)
    {
        if ($primary !== $column_name) {
            return '';
        }

        $actions = [];
        $title = _draft_or_post_title();
        $cas_fs = cas_fs();

        if (current_user_can('edit_post', $post->ID) && $post->post_status != 'trash') {
            $actions['edit'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                get_edit_post_link($post->ID),
                /* translators: %s: sidebar title */
                esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)),
                __('Edit')
            );
            $actions['duplicate'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($cas_fs->get_upgrade_url()),
                /* translators: %s: sidebar title */
                esc_attr(sprintf(__('Duplicate %s', 'content-aware-sidebars'), $title)),
                __('Duplicate', 'content-aware-sidebars')
            );

            $link = admin_url('post.php?post=' . $post->ID);
            $actions['widget_revisions'] = '<a href="' . add_query_arg('action', 'cas-revisions', $link) . '" title="' . esc_attr__('Widget Revisions', 'content-aware-sidebars') . '">' . __('Widget Revisions', 'content-aware-sidebars') . '</a>';
        }

        if (current_user_can('delete_post', $post->ID)) {
            if ($post->post_status == 'trash') {
                $actions['untrash'] = sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    wp_nonce_url(get_edit_post_link($post->ID) . '&amp;action=untrash', 'untrash-post_' . $post->ID),
                    /* translators: %s: post title */
                    esc_attr(sprintf(__('Restore &#8220;%s&#8221; from the Trash'), $title)),
                    __('Restore')
                );
            } elseif (EMPTY_TRASH_DAYS) {
                $actions['trash'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post->ID),
                    /* translators: %s: post title */
                    esc_attr(sprintf(__('Move &#8220;%s&#8221; to the Trash'), $title)),
                    _x('Trash', 'verb')
                );
            }
            if ($post->post_status == 'trash' || !EMPTY_TRASH_DAYS) {
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post->ID, '', true),
                    /* translators: %s: post title */
                    esc_attr(sprintf(__('Delete &#8220;%s&#8221; permanently'), $title)),
                    __('Delete Permanently')
                );
            }
        }

        return $this->row_actions(
            apply_filters('cas/admin/row_actions', $actions, $post)
        );
    }
}
