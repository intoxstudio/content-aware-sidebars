<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

$locale = get_locale();
global $cas_fs;
?>
    <img class="wpca-pull-right" style="border-radius:3px;" src="<?php echo $cas_fs->get_local_icon_url(); ?>" width="48" height="48" />
    <ul>
        <li><a href="https://dev.institute/docs/content-aware-sidebars/getting-started/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Get Started', 'content-aware-sidebars'); ?></a></li>
        <li><a href="https://dev.institute/docs/content-aware-sidebars/getting-started/display-sidebar-advanced/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Sidebar not displayed where expected?', 'content-aware-sidebars'); ?></a></li>
        <li><a href="https://dev.institute/docs/content-aware-sidebars/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Documentation & FAQ', 'content-aware-sidebars'); ?></a></li>
<?php if (!$cas_fs->can_use_premium_code()) : ?>
        <li><a href="https://wordpress.org/support/plugin/content-aware-sidebars/" target="_blank"><?php _e('Support Forums', 'content-aware-sidebars'); ?></a></li>
<?php if (stripos($locale, 'en') !== 0) : ?>
        <li><a href="https://dev.institute/translate-content-aware-sidebars/" target="_blank"><?php _e('Translate plugin &amp; get Pro version', 'content-aware-sidebars'); ?></a></li>
<?php endif; ?>
        <li class="wpca-pull-center"><a class="button button-primary" href="<?php echo esc_url(cas_fs()->get_upgrade_url()); ?>"><span class="cas-heart">❤</span>  <?php _e('Upgrade to Pro', 'content-aware-sidebars'); ?></a></li>
<?php endif; ?>
    </ul>