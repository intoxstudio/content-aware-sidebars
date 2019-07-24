<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

defined('ABSPATH') || exit;

// Create a helper function for easy SDK access.
function cas_fs()
{
    global $cas_fs;

    if (! isset($cas_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/lib/freemius/start.php';

        $cas_fs = fs_dynamic_init(array(
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
            'menu'                => array(
                'slug'           => 'wpcas',
                'support'        => false,
                'contact'        => false,
                'affiliation'    => false
            )
        ));
    }

    return $cas_fs;
}

// Init Freemius.
cas_fs();
// Signal that SDK was initiated.
do_action('cas_fs_loaded');

global $cas_fs;

function cas_fs_connect_message_update(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __('Hey %1$s') . ',<br>' .
        __('Please help us improve %2$s by securely sharing some usage data with %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'content-aware-sidebars'),
        $user_first_name,
        '<b>' . $plugin_title . '</b>',
        '<b>' . $user_login . '</b>',
        $site_link,
        $freemius_link
    );
}

function cas_fs_get_plugin_icon()
{
    return dirname(__FILE__).'/assets/img/icon.png';
}

$cas_fs->add_filter('connect_message_on_update', 'cas_fs_connect_message_update', 10, 6);
$cas_fs->add_filter('connect_message', 'cas_fs_connect_message_update', 10, 6);
$cas_fs->add_filter('show_affiliate_program_notice', '__return_false');
$cas_fs->add_filter('plugin_icon', 'cas_fs_get_plugin_icon');

function cas_fs_upgrade()
{
    global $cas_fs;
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

function cas_fs_uninstall()
{
    require(plugin_dir_path(__FILE__).'/cas_uninstall.php');
}

if ($cas_fs->is_on()) {
    $cas_fs->add_action('after_uninstall', 'cas_fs_uninstall');
} elseif (is_admin()) {
    //after_uninstall is only run for new users
    register_uninstall_hook(plugin_dir_path(__FILE__).'content-aware-sidebars.php', 'cas_fs_uninstall');
}

if ($cas_fs->is__premium_only()) {
    //Launch PRO features
    if ($cas_fs->can_use_premium_code()) {
        require(plugin_dir_path(__FILE__).'/lib/content-aware-premium/app.php');
    }
}
