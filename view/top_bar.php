<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */
$nav_core = [];
$nav_extra = [];

if (current_user_can($post_type->cap->edit_posts)) {
    $nav_core[] = [
        'title' => __('Elements', 'content-aware-sidebars'),
        'link'  => admin_url('admin.php?page=wpcas'),
    ];
}

if (current_user_can('edit_theme_options')) {
    $nav_core[] = [
        'title' => __('Widgets', 'content-aware-sidebars'),
        'link'  => admin_url('widgets.php'),
    ];
}

$nav_extra[] = [
    'title' => __('Documentation', 'content-aware-sidebars'),
    'link'  => 'https://dev.institute/docs/content-aware-sidebars/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=nav&amp;utm_campaign=cas',
    'meta'  => [
        'target' => '_blank',
        'rel'    => 'noopener'
    ]
];

if (!$freemius->can_use_premium_code()) {
    $nav_core[] = [
        'title' => '<span class="cas-heart">❤</span> ' . __('Unlock Features with Pro', 'content-aware-sidebars'),
        'link'  => $freemius->get_upgrade_url(),
        'meta'  => [
            'class' => 'cas-nav-upgrade',
        ]
    ];
    $nav_extra[] = [
        'title' => __('Support Forums', 'content-aware-sidebars'),
        'link'  => 'https://wordpress.org/support/plugin/content-aware-sidebars/',
        'meta'  => [
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ]
    ];
}

function cas_display_nav($items)
{
    foreach ($items as $item) {
        $meta = '';
        if (!isset($item['meta']['class'])) {
            $item['meta']['class'] = '';
        }
        $item['meta']['class'] .= ' cas-nav-link';
        foreach ($item['meta'] as $key => $value) {
            $meta .= ' ' . $key . '="' . $value . '"';
        }

        echo '<a href="' . esc_url($item['link']) . '"' . $meta . '>';
        echo $item['title'];
        echo '</a>';
    }
}

?>

<div class="cas-navbar">
    <img src="<?php echo $freemius->get_local_icon_url(); ?>" width="36" height="36" alt="" />
    <h2 class="screen-reader-text"><?php _e('Content Aware', 'content-aware-sidebars'); ?></h2>
    <div style="display: inline-block;vertical-align: middle;padding-left: 20px;">
        <?php cas_display_nav($nav_core); ?>
    </div>
    <div style="display: inline-block;vertical-align: middle;float:right;overflow: hidden;">
        <?php cas_display_nav($nav_extra); ?>
    </div>
</div>