<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

$cas_fs = cas_fs();
$url = 'https://dev.institute/wordpress-sidebars/pricing/?utm_source=plugin&utm_medium=popup&utm_content=revisions&utm_campaign=cas';

$revision_count = 0;
if (post_type_supports($post->post_type, 'revisions')) {
    $revisions = wp_get_post_revisions($post->ID);
    $revision_count = number_format_i18n(count($revisions));
}

?>

<div class="cas-save">
<?php if (isset($_REQUEST['sidebar_id'])) : ?>
    <a href="<?php echo add_query_arg('action', 'cas-revisions', admin_url('post.php?post='.$post->ID)); ?>" class="button button-secondary button-large js-cas-pro-notice" title="<?php _e('Widget Revisions', 'content-aware-sidebars'); ?>: <?php echo $revision_count; ?>" data-url="<?php echo $url; ?>">
        <span class="dashicons dashicons-backup" style="vertical-align: text-top;"></span>
        <span class="screen-reader-text"><?php _e('Widget Revisions', 'content-aware-sidebars'); ?></span>
    </a>
<?php endif; ?>

    <div class="wpca-pull-right">
<?php if ($post->post_status == 'auto-draft') {
    submit_button(__('Create'), 'primary button-large', 'publish', false);
} else {
    submit_button(__('Save'), 'primary button-large', 'save', false);
} ?>
    </div>
</div>
<ul class="cas-overview-actions">
    <li style="overflow: hidden;">
        <span class="dashicons dashicons-calendar"></span> <strong><?php _e('Status', 'content-aware-sidebars'); ?></strong>
        <div class="wpca-pull-right">
        <a class="js-nav-link" href="#top#section-schedule"><?php _ex('Schedule', 'verb', 'content-aware-sidebars'); ?></a>
        <label class="cae-toggle">
            <input class="js-cas-status" type="checkbox" name="post_status" value="<?php echo CAS_App::STATUS_ACTIVE; ?>" <?php checked(in_array($post->post_status, [CAS_App::STATUS_ACTIVE,'auto-draft']), true); ?> />
            <div class="cae-toggle-bar"></div>
        </label>
    </div>
    </li>
    <li>
        <?php CAS_Sidebar_Edit::form_field('visibility', '', 'dashicons dashicons-visibility'); ?>
    </li>
</ul>