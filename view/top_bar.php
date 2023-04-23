<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2023 by Joachim Jensen
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
    'title' => '<span class="dashicons dashicons-welcome-learn-more"></span> ' . __('Docs', 'content-aware-sidebars'),
    'link'  => 'https://dev.institute/docs/content-aware-sidebars/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=nav&amp;utm_campaign=cas',
    'meta'  => [
        'target' => '_blank',
        'rel'    => 'noopener'
    ]
];

$label = '';
if (!$freemius->can_use_premium_code()) {
    $nav_core[] = [
        'title' => '<span class="dashicons dashicons-superhero-alt"></span> ' . __('Get more features with PRO', 'content-aware-sidebars'),
        'link'  => $freemius->get_upgrade_url(),
        'meta'  => [
            'class' => 'cas-nav-upgrade',
        ]
    ];
    $nav_extra[] = [
        'title' => '<span class="dashicons dashicons-sos"></span> ' . __('Forums', 'content-aware-sidebars'),
        'link'  => 'https://wordpress.org/support/plugin/content-aware-sidebars/',
        'meta'  => [
            'target' => '_blank',
            'rel'    => 'noopener noreferrer',
        ]
    ];
} else {
    if ($freemius->is__premium_only()) {
        $label = '<span class="cas-nav-pro">PRO</span>';
    }
    if (!$freemius->is_whitelabeled()) {
        $nav_extra[] = [
            'title' => '<span class="dashicons dashicons-sos"></span> ' . __('Support', 'content-aware-sidebars'),
            'link'  => $freemius->contact_url()
        ];
    }
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
    <h2>
        <span class="screen-reader-text"><?php _e('Content Aware', 'content-aware-sidebars'); ?></span>
        <?php echo $label; ?>
    </h2>
    <div style="display: inline-block;vertical-align: middle;padding-left: 20px;">
        <?php cas_display_nav($nav_core); ?>
    </div>
    <div style="display: inline-block;vertical-align: middle;float:right;overflow: hidden;">
        <?php cas_display_nav($nav_extra); ?>
    </div>
</div>