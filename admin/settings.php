<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class CAS_Admin_Settings extends CAS_Admin
{
    /**
     * @inheritDoc
     */
    public function admin_hooks()
    {
    }

    /**
     * @inheritDoc
     */
    public function get_screen()
    {
        return add_submenu_page(
            CAS_App::BASE_SCREEN . '-bogus', //dont add menu item
            null,
            null,
            $this->authorize_user(),
            CAS_App::BASE_SCREEN . '-settings',
            [$this,'render_screen']
        );
    }

    /**
     * @inheritDoc
     */
    public function authorize_user()
    {
        $post_type_object = $this->get_sidebar_type();
        return $post_type_object->cap->edit_posts;
    }

    /**
     * @inheritDoc
     */
    public function prepare_screen()
    {
        $this->process_actions();
    }

    public function process_actions()
    {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if (!$action) {
            return;
        }

        check_admin_referer($action);

        $sendback = wp_get_referer();

        switch ($action) {
            case 'update_condition_type_cache':
                WPCACore::cache_condition_types();
                break;
            default:
                break;
        }

        wp_safe_redirect($sendback);
        exit();
    }

    public function render_screen()
    {
    }

    /**
     * @inheritDoc
     */
    public function add_scripts_styles()
    {
    }
}
