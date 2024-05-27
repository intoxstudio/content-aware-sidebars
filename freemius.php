<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

if (!defined('CAS_IS_PLAYGROUND_PREVIEW')) {
    define('CAS_IS_PLAYGROUND_PREVIEW', false);
}

// Create a helper function for easy SDK access.
function cas_fs()
{
    global $cas_fs;

    if (!isset($cas_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/lib/freemius/start.php';

        $cas_fs = fs_dynamic_init([
            'id'                  => '259',
            'slug'                => 'content-aware-sidebars',
            'type'                => 'plugin',
            'public_key'          => 'pk_75513325effa77f024565ef74c9d6',
            'is_premium'          => true,
            'premium_suffix'      => 'Pro',
            'has_premium_version' => true,
            'has_addons'          => false,
            'has_paid_plans'      => true,
            'has_affiliation'     => 'selected',
            'menu'                => [
                'slug'        => 'wpcas',
                'support'     => false,
                'contact'     => false,
                'affiliation' => false
            ],
            'opt_in_moderation' => [
                'new'       => true,
                'updates'   => false,
                'localhost' => false,
            ],
            'anonymous_mode' => CAS_IS_PLAYGROUND_PREVIEW,
        ]);
        $cas_fs->add_filter('connect-header', function ($text) use ($cas_fs) {
            return '<h2>' .
                sprintf(
                    __('Thank you for installing %s!', 'content-aware-sidebars'),
                    esc_html($cas_fs->get_plugin_name())
                ) . '</h2>';
        });
        $cas_fs->add_filter('connect_message_on_update', 'cas_fs_connect_message_update', 10, 6);
        $cas_fs->add_filter('connect_message', 'cas_fs_connect_message_update', 10, 6);
        $cas_fs->add_filter('show_affiliate_program_notice', '__return_false');
        $cas_fs->add_filter('plugin_icon', 'cas_fs_get_plugin_icon');
        $cas_fs->add_filter('permission_extensions_default', '__return_true');
        $cas_fs->add_filter('hide_freemius_powered_by', '__return_true');
    }

    return $cas_fs;
}

function cas_fs_connect_message_update(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __('Please help us improve the plugin by securely sharing some basic WordPress environment info. If you skip this, that\'s okay! %2$s will still work just fine.', 'content-aware-sidebars'),
        $user_first_name,
        $plugin_title,
        $user_login,
        $site_link,
        $freemius_link
    );
}

function cas_fs_get_plugin_icon()
{
    return dirname(__FILE__) . '/assets/img/icon.png';
}

function cas_fs_upgrade()
{
    $cas_fs = cas_fs();
    $flag = 'cas_pro';
    $upgrade_flag = (int)$cas_fs->can_use_premium_code();

    if ($upgrade_flag != (int)get_option($flag, 0)) {
        if (!$upgrade_flag) {
            //downgrade
            update_option($flag, $upgrade_flag);
        }
        if ($cas_fs->is__premium_only()) {
            //because upgrade script is behind paywall
            //we need to fire this early, triggers on second page load after upgrade
            if ($upgrade_flag) {
                //upgrade
                //listen to update_option_cas_pro
                update_option($flag, $upgrade_flag);
            }
        }
    }
}
add_action('admin_init', 'cas_fs_upgrade', 999);

// Init Freemius.
$cas_fs = cas_fs();

new CAS_Admin_Screen_Account($cas_fs);

if (!$cas_fs->can_use_premium_code()) {
    function cas_fs_uninstall()
    {
        require plugin_dir_path(__FILE__) . '/cas_uninstall.php';
    }

    if ($cas_fs->is_on()) {
        $cas_fs->add_action('after_uninstall', 'cas_fs_uninstall');
    } elseif (is_admin()) {
        //after_uninstall is only run for new users
        register_uninstall_hook(plugin_dir_path(__FILE__) . 'content-aware-sidebars.php', 'cas_fs_uninstall');
    }
}

if ($cas_fs->can_use_premium_code__premium_only()) {
    require plugin_dir_path(__FILE__) . '/lib/content-aware-premium/app.php';
}

// Signal that SDK was initiated.
do_action('cas_fs_loaded');
