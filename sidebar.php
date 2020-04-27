<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class CAS_Sidebar_Manager
{

    /**
     * Sidebar metadata
     * @var WPCAObjectManager
     */
    protected $metadata;

    /**
     * Custom sidebars
     * @var array
     */
    public $sidebars = array();

    /**
     * Cache replaced sidebars
     * @var array
     */
    protected $replaced_sidebars = array();

    /**
     * Sidebar replacement map
     * @var array
     */
    protected $replace_map = array();

    /**
     * @var array
     * Constructor
     *
     * @since 3.1
     */
    public function __construct()
    {
        add_action(
            'wpca/loaded',
            array($this,'late_init')
        );
        add_action(
            'wp_head',
            array($this,'sidebar_notify_theme_customizer')
        );
        add_action(
            'init',
            array($this,'init_sidebar_type'),
            99
        );
        add_action(
            'widgets_init',
            array($this,'create_sidebars'),
            99
        );
        add_action(
            'wp_loaded',
            array($this,'set_sidebar_styles'),
            99
        );

        add_shortcode(
            'ca-sidebar',
            array($this,'sidebar_shortcode')
        );
    }

    /**
     * Initialize after WPCA has been loaded
     * Makes sure the SDK can be used in actions/filters
     * forcefully called earlier
     *
     * @since  3.4
     * @return void
     */
    public function late_init()
    {
        if (!is_admin()) {
            add_filter(
                'sidebars_widgets',
                array($this,'replace_sidebar')
            );
            add_filter(
                'wpca/posts/sidebar',
                array(__CLASS__,'filter_password_protection')
            );
            add_filter(
                'wpca/posts/sidebar',
                array($this,'filter_visibility')
            );
            add_filter(
                'cas/shortcode/display',
                array($this,'filter_shortcode_visibility'),
                10,
                2
            );
            add_action(
                'dynamic_sidebar_before',
                array($this,'render_sidebar_before'),
                9,
                2
            );
            add_action(
                'dynamic_sidebar_after',
                array($this,'render_sidebar_after'),
                99,
                2
            );
        }
    }

    /**
     * Get instance of metadata manager
     *
     * @since  3.0
     * @return WPCAObjectManager
     */
    public function metadata()
    {
        if (!$this->metadata) {
            $this->init_metadata();
        }
        return $this->metadata;
    }

    /**
     * Create post meta fields
     * @global array $wp_registered_sidebars
     * @return void
     */
    private function init_metadata()
    {
        $this->metadata = new WPCAObjectManager();
        $this->metadata
        ->add(new WPCAMeta(
            'visibility',
            __('User Visibility', 'content-aware-sidebars'),
            array(),
            'multi',
            array(
                'general' => array(
                    'label'   => 'General',
                    'options' => array(
                        -1 => __('Logged-in', 'content-aware-sidebars')
                    )
                )
            )
        ), 'visibility')
        ->add(new WPCAMeta(
            'handle',
            _x('Action', 'option', 'content-aware-sidebars'),
            0,
            'select',
            array(
                0 => __('Replace', 'content-aware-sidebars'),
                1 => __('Merge', 'content-aware-sidebars'),
                3 => __('Forced replace', 'content-aware-sidebars'),
                2 => __('Shortcode')
            ),
            __('Replace host sidebar, merge with it or add sidebar manually.', 'content-aware-sidebars')
        ), 'handle')
        ->add(new WPCAMeta(
            'host',
            __('Target Sidebar', 'content-aware-sidebars'),
            'sidebar-1',
            'select',
            array()
        ), 'host')
        ->add(new WPCAMeta(
            'merge_pos',
            __('Merge Position', 'content-aware-sidebars'),
            1,
            'select',
            array(
                __('Top', 'content-aware-sidebars'),
                __('Bottom', 'content-aware-sidebars')
            ),
            __('Place sidebar on top or bottom of host when merging.', 'content-aware-sidebars')
        ), 'merge_pos')
        ->add(new WPCAMeta(
            'html',
            __('HTML', 'content-aware-sidebars'),
            array(),
            'select',
            array('')
        ), 'html');
        apply_filters('cas/metadata/init', $this->metadata);
    }

    /**
     * Populate metadata with dynamic content
     * for use in admin
     *
     * @since  3.2
     * @return void
     */
    public function populate_metadata()
    {
        if ($this->metadata) {
            global $wp_registered_sidebars;

            // List of sidebars
            $sidebar_list = array();
            foreach ($wp_registered_sidebars as $sidebar) {
                $sidebar_list[$sidebar['id']] = $sidebar['name'];
            }

            // Remove ability to set self to host
            if (get_the_ID()) {
                unset($sidebar_list[CAS_App::SIDEBAR_PREFIX.get_the_ID()]);
            }
            $this->metadata->get('host')->set_input_list($sidebar_list);

            if (!cas_fs()->can_use_premium_code()) {
                $pro_label = ' (Pro)';
                $actions = $this->metadata->get('handle');
                $action_list = $actions->get_input_list();
                $action_list['__infuse'] = __('Infuse', 'content-aware-sidebars').$pro;
                $action_list['__after_paragraph'] = __('After Paragraph', 'content-aware-sidebars').$pro;
                $actions->set_input_list($action_list);
            }

            apply_filters('cas/metadata/populate', $this->metadata);
        }
    }

    /**
     * Create sidebar post type
     * Add it to content aware engine
     *
     * @return void
     */
    public function init_sidebar_type()
    {

        // Register the sidebar type
        register_post_type(CAS_App::TYPE_SIDEBAR, array(
            'labels' => array(
                'name'               => __('Sidebars', 'content-aware-sidebars'),
                'singular_name'      => __('Sidebar', 'content-aware-sidebars'),
                'add_new'            => _x('Add New', 'sidebar', 'content-aware-sidebars'),
                'add_new_item'       => __('Add New Sidebar', 'content-aware-sidebars'),
                'edit_item'          => __('Edit Sidebar', 'content-aware-sidebars'),
                'new_item'           => __('New Sidebar', 'content-aware-sidebars'),
                'all_items'          => __('All Sidebars', 'content-aware-sidebars'),
                'view_item'          => __('View Sidebar', 'content-aware-sidebars'),
                'search_items'       => __('Search Sidebars', 'content-aware-sidebars'),
                'not_found'          => __('No sidebars found', 'content-aware-sidebars'),
                'not_found_in_trash' => __('No sidebars found in Trash', 'content-aware-sidebars'),
                //wp-content-aware-engine specific
                'ca_title' => __('Where to display', 'content-aware-sidebars')
            ),
            'capabilities' => array(
                'edit_post'          => CAS_App::CAPABILITY,
                'read_post'          => CAS_App::CAPABILITY,
                'delete_post'        => CAS_App::CAPABILITY,
                'edit_posts'         => CAS_App::CAPABILITY,
                'delete_posts'       => CAS_App::CAPABILITY,
                'edit_others_posts'  => CAS_App::CAPABILITY,
                'publish_posts'      => CAS_App::CAPABILITY,
                'read_private_posts' => CAS_App::CAPABILITY
            ),
            'public'              => false,
            'hierarchical'        => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'supports'            => array('title','page-attributes'),
            'menu_icon'           => 'dashicons-welcome-widgets-menus',
            'can_export'          => false,
            'delete_with_user'    => false
        ));

        WPCACore::types()->add(CAS_App::TYPE_SIDEBAR);
    }

    /**
     * Add sidebars to widgets area
     * Triggered in widgets_init to save location for each theme
     * @return void
     */
    public function create_sidebars()
    {
        $sidebars = get_posts(array(
            'numberposts' => -1,
            'post_type'   => CAS_App::TYPE_SIDEBAR,
            'post_status' => array(
                CAS_App::STATUS_ACTIVE,
                CAS_App::STATUS_INACTIVE,
                CAS_App::STATUS_SCHEDULED
            ),
            'orderby' => 'title',
            'order'   => 'ASC'
        ));

        //Register sidebars to add them to the list
        foreach ($sidebars as $post) {
            $this->sidebars[CAS_App::SIDEBAR_PREFIX.$post->ID] = $post;
            register_sidebar(array(
                'name'           => $post->post_title ? $post->post_title : __('(no title)'),
                'id'             => CAS_App::SIDEBAR_PREFIX.$post->ID,
                'before_sidebar' => '',
                'after_sidebar'  => ''
            ));
        }
    }

    /**
     * Set styles of created sidebars
     *
     * @since 3.6
     */
    public function set_sidebar_styles()
    {
        global $wp_registered_sidebars;

        //todo: only for manual
        $default_styles = array(
            'before_widget' => '<div id="%1$s" class="widget-container %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>'
        );
        $has_host = array(0 => 1,1 => 1,3 => 1);
        $metadata = $this->metadata();

        foreach ($this->sidebars as $id => $post) {
            $args = $default_styles;

            if (isset($has_host[$metadata->get('handle')->get_data($post->ID)])) {
                //Set style from host to fix when content aware sidebar
                //is called directly by other sidebar managers
                $host_id = $metadata->get('host')->get_data($post->ID);
                if (isset($wp_registered_sidebars[$host_id])) {
                    foreach (array(
                        'before_widget',
                        'after_widget',
                        'before_title',
                        'after_title',
                        'before_sidebar',
                        'after_sidebar'
                    ) as $pos) {
                        if (isset($wp_registered_sidebars[$host_id][$pos])) {
                            $args[$pos] = $wp_registered_sidebars[$host_id][$pos];
                        }
                    }
                }
            }

            $wp_registered_sidebars[$id] = array_merge($wp_registered_sidebars[$id], $args);
        }
    }

    /**
     * Replace or merge a sidebar with content aware sidebars.
     * @since  .
     * @param  array    $sidebars_widgets
     * @return array
     */
    public function replace_sidebar($sidebars_widgets)
    {

        //customizer requires sidebars_widgets filter. cache for repeat calls
        if ($this->replaced_sidebars) {
            return $this->replaced_sidebars;
        }

        $posts = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);

        if ($posts) {
            global $wp_registered_sidebars;

            $metadata = $this->metadata();
            $has_host = array(0 => 1,1 => 1,3 => 1);

            foreach ($posts as $post) {
                $id = CAS_App::SIDEBAR_PREFIX . $post->ID;
                $host = $metadata->get('host')->get_data($post->ID);

                // Check for correct handling and if host exist
                if (!isset($has_host[$post->handle]) || !isset($sidebars_widgets[$host])) {
                    continue;
                }

                // Sidebar might not have any widgets. Get it anyway!
                if (!isset($sidebars_widgets[$id])) {
                    $sidebars_widgets[$id] = array();
                }

                // If handle is merge or if handle is replace and host has already been replaced
                if ($post->handle == 1 || ($post->handle == 0 && isset($handled_already[$host]))) {
                    //do not merge forced replace
                    //todo: maybe reverse order of fetched sidebars instead?
                    if (isset($handled_already[$host]) && $handled_already[$host] == 3) {
                        continue;
                    }
                    if ($metadata->get('merge_pos')->get_data($post->ID)) {
                        $sidebars_widgets[$host] = array_merge($sidebars_widgets[$host], $sidebars_widgets[$id]);
                    } else {
                        $sidebars_widgets[$host] = array_merge($sidebars_widgets[$id], $sidebars_widgets[$host]);
                    }
                } else {
                    $sidebars_widgets[$host] = $sidebars_widgets[$id];
                    $handled_already[$host] = $post->handle;
                }

                //last replacement will take priority
                //todo: extend to work for widgets too
                $this->replace_map[$host] = $id;
            }
            $this->replaced_sidebars = $sidebars_widgets;
        }
        return $sidebars_widgets;
    }

    /**
     * Show manually handled content aware sidebars
     * @global array $_wp_sidebars_widgets
     * @param  string|array $args
     * @return void
     */
    public function manual_sidebar($args)
    {
        global $_wp_sidebars_widgets;

        // Grab args or defaults
        $args = wp_parse_args($args, array(
            'include' => '',
            'before'  => '',
            'after'   => ''
        ));
        extract($args, EXTR_SKIP);

        // Get sidebars
        $posts = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);
        if (!$posts) {
            return;
        }

        // Handle include argument
        if (!empty($include)) {
            if (!is_array($include)) {
                $include = explode(',', $include);
            }
            // Fast lookup
            $include = array_flip($include);
        }

        $i = $host = 0;
        foreach ($posts as $post) {
            $id = CAS_App::SIDEBAR_PREFIX . $post->ID;

            // Check for manual handling, if sidebar exists and if id should be included
            if ($post->handle != 2 || !isset($_wp_sidebars_widgets[$id]) || (!empty($include) && !isset($include[$post->ID]))) {
                continue;
            }

            // Merge if more than one. First one is host.
            if ($i > 0) {
                if ($this->metadata()->get('merge_pos')->get_data($post->ID)) {
                    $_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host], $_wp_sidebars_widgets[$id]);
                } else {
                    $_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id], $_wp_sidebars_widgets[$host]);
                }
            } else {
                $host = $id;
            }
            $i++;
        }

        if ($host) {
            echo $before;
            dynamic_sidebar($host);
            echo $after;
        }
    }

    /**
     * Display sidebar with shortcode
     * @version 2.5
     * @param   array     $atts
     * @param   string    $content
     * @return  string
     */
    public function sidebar_shortcode($atts, $content = '')
    {
        $a = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $id = CAS_App::SIDEBAR_PREFIX.esc_attr($a['id']);

        //if sidebar is in replacement map, shortcode is called wrongly
        //todo: check for handle instead?
        if (isset($this->sidebars[$id]) && $this->sidebars[$id]->post_status == CAS_App::STATUS_ACTIVE && !isset($this->replace_map[$id]) && is_active_sidebar($id) && apply_filters('cas/shortcode/display', true, $a['id'])) {
            ob_start();
            do_action('cas/shortcode/before', $a['id']);
            dynamic_sidebar($id);
            $content = ob_get_clean();
        }
        return $content;
    }

    /**
     * Get styles from nested sidebars
     *
     * @since  3.7
     * @param  string  $i
     * @param  array   $styles
     * @return array
     */
    public function get_sidebar_styles($i)
    {
        $styles = array();

        $metadata = $this->metadata()->get('html');
        while ($i) {
            if (isset($this->sidebars[$i])) {
                $style = apply_filters('cas/sidebar/html', $metadata->get_data($this->sidebars[$i]->ID), $this->sidebars[$i]->ID);
                if ($style) {
                    $styles = array_merge($styles, $style);
                    $styles['widget_id'] = '%1$s';
                    $styles['sidebar_id'] = CAS_App::SIDEBAR_PREFIX.$this->sidebars[$i]->ID;
                }
            }
            $i = isset($this->replace_map[$i]) ? $this->replace_map[$i] : false;
        }

        return $styles;
    }

    /**
     * Render html if present before sidebar
     *
     * @since  3.6
     * @param  string   $i
     * @param  boolean  $has_widgets
     * @return void
     */
    public function render_sidebar_before($i, $has_widgets)
    {
        global $wp_registered_sidebars;

        //Get nested styles
        $html = $this->get_sidebar_styles($i);
        if ($html) {
            $styles = $wp_registered_sidebars[$i];
            //Set user styles
            foreach (array(
                'widget',
                'title',
                'sidebar'
            ) as $pos) {
                if (isset($html[$pos],$html[$pos.'_class'])) {
                    $e = esc_html($html[$pos]);
                    $class = esc_html($html[$pos.'_class']);
                    $id = '';
                    if (isset($html[$pos.'_id'])) {
                        $id = ' id="'.$html[$pos.'_id'].'"';
                    }
                    $styles['before_'.$pos] = '<'.$e.$id.' class="'.$class.'">';
                    $styles['after_'.$pos] = "</$e>";
                }
            }
            $wp_registered_sidebars[$i] = $styles;
        }

        if ($has_widgets && isset($wp_registered_sidebars[$i]['before_sidebar'])) {
            echo $wp_registered_sidebars[$i]['before_sidebar'];
        }
    }

    /**
     * Render html if present after sidebar
     *
     * @since  3.6
     * @param  string   $i
     * @param  boolean  $has_widgets
     * @return void
     */
    public function render_sidebar_after($i, $has_widgets)
    {
        global $wp_registered_sidebars;
        if ($has_widgets && isset($wp_registered_sidebars[$i]['after_sidebar'])) {
            echo $wp_registered_sidebars[$i]['after_sidebar'];
        }
    }

    /**
     * Filter out all sidebars if post is password protected
     *
     * @since  3.7
     * @param  array  $sidebars
     * @return array
     */
    public static function filter_password_protection($sidebars)
    {
        if (is_singular() && post_password_required()) {
            return array();
        }
        return $sidebars;
    }

    /**
     * Filter out sidebars based on current user
     *
     * @since  3.7
     * @param  array  $sidebars
     * @return array
     */
    public function filter_visibility($sidebars)
    {
        if ($sidebars) {
            $metadata = $this->metadata()->get('visibility');

            //temporary filter until WPCACore allows filtering
            $user_visibility = is_user_logged_in() ? array(-1) : array();
            $user_visibility = apply_filters('cas/user_visibility', $user_visibility);
            foreach ($sidebars as $id => $sidebar) {
                $visibility = $metadata->get_data($id, true, false);

                // Check visibility
                if ($visibility && !array_intersect($visibility, $user_visibility)) {
                    unset($sidebars[$id]);
                }
            }
        }
        return $sidebars;
    }

    /**
     * Filter shortcode sidebar based on current user
     *
     * @since  3.7.1
     * @param  boolean  $retval
     * @param  int  $id
     * @return boolean
     */
    public function filter_shortcode_visibility($retval, $id)
    {
        if ($retval) {
            $metadata = $this->metadata()->get('visibility');

            //temporary filter until WPCACore allows filtering
            $user_visibility = is_user_logged_in() ? array(-1) : array();
            $user_visibility = apply_filters('cas/user_visibility', $user_visibility);

            $visibility = $metadata->get_data($id, true, false);

            // Check visibility
            if ($visibility && !array_intersect($visibility, $user_visibility)) {
                $retval = false;
            }
        }
        return $retval;
    }


    /**
     * Runs is_active_sidebar for sidebars
     * Widget management in Theme Customizer
     * expects this
     *
     * @global type $wp_customize
     * @since  2.2
     * @return void
     */
    public function sidebar_notify_theme_customizer()
    {
        global $wp_customize;
        if (!empty($wp_customize)) {
            $sidebars = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);
            if ($sidebars) {
                foreach ($sidebars as $sidebar) {
                    is_active_sidebar(CAS_App::SIDEBAR_PREFIX . $sidebar->ID);
                }
            }
        }
    }
}
