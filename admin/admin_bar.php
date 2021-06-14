<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class CAS_Admin_Bar
{
    const NODE_ROOT = 'wpcas-tool';
    const NODE_THEME_AREAS = 'theme-areas';
    const NODE_CONDITION_TYPES = 'condition-types';
    const NODE_CUSTOM_SIDEBARS = 'custom-sidebars';

    const DOCS_MAP = [
        'author'        => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/authors/',
        'bb_profile'    => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/bbpress-user-profiles/',
        'bp_member'     => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/buddypress-profiles/',
        'bp_group'      => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/buddypress-groups/',
        'date'          => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/dates/',
        'language'      => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/languages/',
        'page_template' => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/page-templates/',
        'taxonomy'      => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/taxonomies/',
        'pods'          => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/pods-pages/',
        'post_type'     => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/post-types/',
        'url'           => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/urls/',
        'referrer'      => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/urls/',
        'static'        => 'https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/static-pages/',
    ];

    /**
     * @var array
     */
    private $theme_sidebars = [];

    /**
     * @var array
     */
    private $custom_sidebars = [];

    public function __construct()
    {
        add_action('admin_bar_init', [$this,'initiate']);
    }

    public function initiate()
    {
        if (!$this->authorize_user()) {
            return;
        }

        add_filter('is_active_sidebar', [$this,'detect_sidebars_via_active'], 10, 2);
        add_action('dynamic_sidebar_before', [$this,'detect_sidebars_via_render'], 10, 2);
        add_action('admin_bar_menu', [$this,'add_menu'], 99);
        add_action('wp_head', [$this,'print_styles']);
    }

    public function print_styles()
    {
        echo '<style type="text/css" media="screen">'."\n"; ?>
        #wp-admin-bar-wpcas-tool #wp-admin-bar-wpcas-tool-custom-sidebars {
            border-top:4px solid #75d7ef;
        }
        #wp-admin-bar-wpcas-tool .dashicons-welcome-widgets-menus {
            top:2px;
            margin:0!important;
        }
        #wp-admin-bar-wpcas-tool .wpcas-ok .ab-item {
            color:#8c8!important;
        }
        #wp-admin-bar-wpcas-tool .wpcas-warn .ab-item {
            color:#dba617!important;
        }
        #wp-admin-bar-wpcas-tool #wp-admin-bar-wpcas-tool-condition-types .ab-sub-wrapper {
            min-width:100%;
        }
        #wp-admin-bar-wpcas-tool #wp-admin-bar-wpcas-tool-condition-types .ab-icon {
            float:right!important;
            margin-right:0!important;
            font-size:14px!important;
        }
        <?php
        echo '</style>';
    }

    /**
     * @param WP_Admin_Bar $admin_bar
     * @param array $args
     * @param string $parent
     * @return self
     */
    private function add_node($admin_bar, $args, $parent = null)
    {
        if ($args['id'] !== self::NODE_ROOT) {
            $args['parent'] = self::NODE_ROOT . (!is_null($parent) ? '-'.$parent : '');
            $args['id'] = $args['parent'].'-'.$args['id'];
        }
        $admin_bar->add_node($args);

        return $this;
    }
 
    /**
     * @param WP_Admin_Bar $admin_bar
     * @return void
     */
    public function add_menu($admin_bar)
    {
        global $wp_registered_sidebars;
    
        $post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);

        $this
        ->add_node($admin_bar, [
            'id'    => self::NODE_ROOT,
            'title' => '<span class="ab-icon dashicons '.$post_type_object->menu_icon.'"></span>',
            'href'  => admin_url('admin.php?page=wpcas'),
            'meta'  => [
                'title' => __('Content Aware Sidebars', 'content-aware-sidebars')
            ]
        ])
        ->add_node($admin_bar, [
            'id'    => 'add_new',
            'title' => $post_type_object->labels->add_new,
            'href'  => admin_url('admin.php?page=wpcas-edit'),
        ])
        ->add_node($admin_bar, [
            'id'    => self::NODE_CONDITION_TYPES,
            'title' => __('Condition Types', 'content-aware-sidebars'),
        ])
        ->add_node($admin_bar, [
            'id'    => self::NODE_THEME_AREAS,
            'title' => __('Theme Areas', 'content-aware-sidebars')
        ]);

        $cache = get_option(WPCACore::OPTION_CONDITION_TYPE_CACHE, []);
        if (isset($cache[CAS_App::TYPE_SIDEBAR]) && !empty($cache[CAS_App::TYPE_SIDEBAR])) {
            $title = __('Cache Active', 'content-aware-sidebars');
            $link = null;
            $class = 'wpcas-ok';
        } else {
            $title = __('Activate Cache Now', 'content-aware-sidebars');
            $link = wp_nonce_url(admin_url('admin.php?page=wpcas-settings&action=update_condition_type_cache'), 'update_condition_type_cache');
            $class = 'wpcas-warn';
        }
        $this->add_node($admin_bar, [
            'id'    => 'condition_cache',
            'title' => $title.' &#9210;',
            'href'  => $link,
            'meta'  => [
                'class' => $class,
            ]
        ], self::NODE_CONDITION_TYPES);

        $args = [];
        foreach (WPCACore::get_conditional_modules('sidebar') as $module) {
            $title = $module->get_name();
            $link = '';
            if (array_key_exists($module->get_id(), self::DOCS_MAP)) {
                $title = '<span class="ab-icon dashicons dashicons-external"></span> '.$title;
                $link = self::DOCS_MAP[$module->get_id()].'?utm_source=plugin&amp;utm_medium=admin_bar&amp;utm_campaign=cas';
            }
            $args[] = [
                'id'    => $module->get_id(),
                'title' => $title,
                'href'  => $link,
                'meta'  => [
                    'target' => '_blank',
                    'rel'    => 'noopener'
                ]
            ];
        }
        $this->add_nodes($admin_bar, $args, self::NODE_CONDITION_TYPES);

        $args = [];
        foreach ($this->theme_sidebars as $index => $has_widgets) {
            $sidebar_name = isset($wp_registered_sidebars[$index]['name'])
                ? $wp_registered_sidebars[$index]['name']
                : $index;
            $args[] = [
                'id'    => $index,
                'title' => $sidebar_name . ($has_widgets ? '' : ' ('.__('Hidden').')'),
            ];
        }
        $this->add_nodes($admin_bar, $args, self::NODE_THEME_AREAS);

        $admin_bar->add_group([
            'id'     => self::NODE_ROOT.'-'.self::NODE_CUSTOM_SIDEBARS,
            'parent' => self::NODE_ROOT,
            'meta'   => [
                'class' => 'ab-sub-secondary'
            ]
        ]);

        $args = [];
        foreach ($this->custom_sidebars as $id => $has_widgets) {
            $sidebar = CAS_App::instance()->manager()->sidebars[$id];
            $args[] = [
                'id'    => $sidebar->ID,
                'title' => $sidebar->post_title,
                'href'  => get_edit_post_link($sidebar->ID)
            ];
        }
        $this->add_nodes($admin_bar, $args, self::NODE_CUSTOM_SIDEBARS);

        if (empty($this->custom_sidebars)) {
            $args = [
                'id'    => 'no_custom_sidebars',
                'title' => $post_type_object->labels->not_found
            ];
            $this->add_node($admin_bar, $args, self::NODE_CUSTOM_SIDEBARS);
        }

        //@todo: show custom sidebars that were not displayed on page
    }

    /**
     * @param bool $has_widgets
     * @param string $index
     * @return bool
     */
    public function detect_sidebars_via_active($has_widgets, $index)
    {
        $this->detect_sidebar($has_widgets, $index);
        return $has_widgets;
    }

    /**
     * @param string $index
     * @param bool $has_widgets
     * @return void
     */
    public function detect_sidebars_via_render($index, $has_widgets)
    {
        $this->detect_sidebar($has_widgets, $index);
    }

    /**
     * @param WP_Admin_Bar $admin_bar
     * @param array $nodes
     * @param string|null $parent
     * @return void
     */
    private function add_nodes($admin_bar, $nodes, $parent = null)
    {
        usort($nodes, [$this,'sort_nodes']);
        foreach ($nodes as $node_args) {
            $this->add_node($admin_bar, $node_args, $parent);
        }
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    private function sort_nodes($a, $b)
    {
        return strcasecmp($a['id'], $b['id']);
    }

    /**
     * @param bool $has_widgets
     * @param string $index
     * @return void
     */
    private function detect_sidebar($has_widgets, $index)
    {
        if (str_replace(CAS_App::SIDEBAR_PREFIX, '', $index) === $index) {
            $this->theme_sidebars[$index] = $has_widgets;
        } else {
            $this->custom_sidebars[$index] = $has_widgets;
        }
        $this->detect_sidebar_target($has_widgets, $index);
    }

    /**
     * @param bool $has_widgets
     * @param string $index
     * @return void
     */
    private function detect_sidebar_target($has_widgets, $index)
    {
        $host_map = CAS_App::instance()->manager()->get_replacement_map();
        if (isset($host_map[$index])) {
            $this->detect_sidebar($has_widgets, $host_map[$index]);
        }
    }

    /**
     * @return bool
     */
    private function authorize_user()
    {
        $post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);
        return current_user_can($post_type_object->cap->create_posts);
    }
}
