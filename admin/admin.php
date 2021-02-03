<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

abstract class CAS_Admin
{
    const NONCE_PREFIX_1CLICK = '1click';

    /**
     * Screen identifier
     * @var string
     */
    protected $_screen;

    /**
     * @var int
     */
    protected $notification_count = 0;

    public function __construct()
    {
        if (is_admin()) {
            $this->add_action('admin_menu', 'add_menu', 99);
            $this->add_action('wp_ajax_cas_dismiss_review_notice', 'ajax_review_clicked');
            $this->admin_hooks();
        }
    }

    /**
     * @since 3.10
     *
     * @return WP_Post_Type
     */
    protected function get_sidebar_type()
    {
        return get_post_type_object(CAS_App::TYPE_SIDEBAR);
    }

    /**
     * Set up screen and menu if necessary
     *
     * @since 3.4
     */
    public function add_menu()
    {
        $this->_screen = $this->get_screen();
        $this->add_action('load-'.$this->_screen, 'load_screen');
    }

    /**
     * Add filters and actions for admin dashboard
     * e.g. AJAX calls
     *
     * @since  3.5
     * @return void
     */
    abstract public function admin_hooks();

    /**
     * Get current screen
     *
     * @since  3.4
     * @return string
     */
    abstract public function get_screen();

    /**
     * Prepare screen load
     *
     * @since 3.4
     *
     * @return void
     */
    abstract public function prepare_screen();

    /**
     * Authorize user for screen
     *
     * @since 3.5
     *
     * @return bool
     */
    abstract public function authorize_user();

    /**
     * Register and enqueue scripts styles
     * for screen
     *
     * @since 3.4
     */
    abstract public function add_scripts_styles();

    /**
     * Prepare plugin upgrade modal
     *
     * @since  3.4.1
     * @return void
     */
    public function load_screen()
    {
        if (!$this->authorize_user()) {
            wp_die(
                '<p>' . __('You do not have access to this screen.', 'content-aware-sidebars') . '</p>',
                403
            );
        }
        $this->prepare_screen();
        $this->add_action('admin_enqueue_scripts', 'add_general_scripts_styles', 11);
        if (!cas_fs()->can_use_premium_code()) {
            $this->add_action('all_admin_notices', 'admin_notice_review');
            $this->add_action('admin_footer', 'render_upgrade_modal');
            add_thickbox();
        }
    }

    /**
     * Add general scripts to admin screens
     *
     * @since 3.4.1
     */
    public function add_general_scripts_styles()
    {
        $this->enqueue_script('cas/admin/general', 'general', ['jquery'], '', true);
        wp_localize_script('cas/admin/general', 'CAS', [
            'showPopups'    => !cas_fs()->can_use_premium_code(),
            'enableConfirm' => __('This sidebar is already scheduled to be activated. Do you want to activate it now?', 'content-aware-sidebars')
        ]);
        $this->enqueue_style('cas/admin/style', 'style');
        $this->add_scripts_styles();
    }


    /**
     * Admin notice for Plugin Review
     *
     * @since  3.1
     * @return void
     */
    public function admin_notice_review()
    {
        $has_reviewed = get_user_option(CAS_App::META_PREFIX.'cas_review');

        if ($has_reviewed !== false) {
            return;
        }

        $tour_manager = new WP_Pointer_Tour(CAS_App::META_PREFIX.'cas_tour');
        $tour_taken = (int) $tour_manager->get_user_option();
        if ($tour_taken && (time() - $tour_taken) >= WEEK_IN_SECONDS) {
            $this->notification_count++;
            $path = plugin_dir_path(dirname(__FILE__)).'view/';
            WPCAView::make($path.'notice_review.php', [
                'current_user' => wp_get_current_user()
            ])->render();
        }
    }

    /**
     * Set review flag for user
     *
     * @since  3.1
     * @return void
     */
    public function ajax_review_clicked()
    {
        $dismiss = isset($_POST['dismiss']) ? (int)$_POST['dismiss'] : 0;
        if (!$dismiss) {
            $dismiss = time();
        }

        echo json_encode(update_user_option(get_current_user_id(), CAS_App::META_PREFIX.'cas_review', $dismiss));
        die();
    }

    /**
     * Render plugin upgrade modal
     *
     * @since  3.4.1
     * @return void
     */
    public function render_upgrade_modal()
    {
        __('Enhance your sidebars and widget areas with: %s and more.', 'content-aware-sidebars');
        __('Sync widgets across themes', 'content-aware-sidebars');
        $features = [
            __('Extra Display Conditions', 'content-aware-sidebars'),
            __('Insert Widget Areas in Theme Hooks', 'content-aware-sidebars'),
            __('Widget Area Designer', 'content-aware-sidebars'),
            __('Automatic Widgets Backup', 'content-aware-sidebars'),
            //__('Visibility for Roles','content-aware-sidebars'),
            __('Time & Weekday Schedule', 'content-aware-sidebars'),
            __('Widget Cleaner', 'content-aware-sidebars'),
            __('and so much more...', 'content-aware-sidebars')
        ];
        echo '<a style="display:none;" class="thickbox js-cas-pro-popup" href="#TB_inline?width=600&amp;height=350&amp;inlineId=pro-popup-notice" title="'.__('Content Aware Sidebars Pro', 'content-aware-sidebars').'"></a>';
        echo '<div id="pro-popup-notice" style="display:none;">';
        echo '<img style="margin-top:15px;" class="alignright" src="'.plugins_url('assets/img/icon.png', dirname(__FILE__)).'" width="128" height="128" />';
        echo '
		<h2>'.__('Get All Features With Content Aware Sidebars Pro', 'content-aware-sidebars').'</h2>';
        echo '<ul>';
        foreach ($features as $feature) {
            echo '<li><strong>+ '.$feature.'</strong></li>';
        }
        echo '</ul>';
        echo '<p>'.__('You can upgrade without leaving the admin panel by clicking below.', 'content-aware-sidebars');
        echo '<br />'.__('Free updates and email support included.', 'content-aware-sidebars').'</p>';
        echo '<p><a class="button-primary" target="_blank" href="'.esc_url(cas_fs()->get_upgrade_url()).'">'.__('Upgrade Now', 'content-aware-sidebars').'</a> <a href="" class="button-secondary js-cas-pro-read-more" target="_blank" rel="noopener" href="">'.__('Read More', 'content-aware-sidebars').'</a></p>';
        echo '</div>';
    }

    /**
     * @since 3.10
     * @param string $tag
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return void
     */
    protected function add_action($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }
        add_action($tag, $callback, $priority, $accepted_args);
    }

    /**
     * @since 3.10
     * @param string $tag
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return void
     */
    protected function add_filter($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }
        add_filter($tag, $callback, $priority, $accepted_args);
    }

    /**
     * @since 3.10
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param bool $in_footer
     * @param string $ver
     *
     * @return void
     */
    protected function enqueue_script($handle, $filename, $deps = [], $ver = '', $in_footer = false)
    {
        $this->register_script($handle, $filename, $deps, $ver, $in_footer);
        wp_enqueue_script($handle);
    }

    /**
     * @since 3.10
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param bool $in_footer
     * @param string $ver
     *
     * @return void
     */
    protected function register_script($handle, $filename, $deps = [], $ver = '', $in_footer = false)
    {
        $suffix = '.min.js';
        if ($ver === '') {
            $ver = CAS_App::PLUGIN_VERSION;
        }
        wp_register_script($handle, plugins_url('assets/js/'.$filename.$suffix, dirname(__FILE__)), $deps, $ver, $in_footer);
    }

    /**
     * @since 3.10
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param string $ver
     *
     * @return void
     */
    protected function enqueue_style($handle, $filename, $deps = [], $ver = '')
    {
        $this->register_style($handle, $filename, $deps, $ver);
        wp_enqueue_style($handle);
    }

    /**
     * @since 3.10
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param string $ver
     *
     * @return void
     */
    protected function register_style($handle, $filename, $deps = [], $ver = '')
    {
        $suffix = '.css';
        if ($ver === '') {
            $ver = CAS_App::PLUGIN_VERSION;
        }
        wp_enqueue_style($handle, plugins_url('assets/css/'.$filename.$suffix, dirname(__FILE__)), $deps, $ver);
    }
}
