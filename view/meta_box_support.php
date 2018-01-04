<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

$locale = get_locale();
?>
<div style="overflow:hidden;">
	<ul>
		<li><a href="https://dev.institute/docs/content-aware-sidebars/getting-started/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Get Started','content-aware-sidebars'); ?></a></li>
		<li><a href="https://dev.institute/docs/content-aware-sidebars/sidebar-conditions/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Sidebar not displayed where expected?','content-aware-sidebars'); ?></a></li>
		<li><a href="https://dev.institute/docs/content-aware-sidebars/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Documentation & FAQ','content-aware-sidebars'); ?></a></li>
		<!--<li><a href="<?php echo esc_url(cas_fs()->get_upgrade_url()); ?>"><?php _e('Priority Email Support','content-aware-sidebars'); ?></a></li>-->
		<li><a href="https://wordpress.org/support/plugin/content-aware-sidebars/" target="_blank"><?php _e('Forum Support','content-aware-sidebars'); ?></a></li>
<?php if($locale != "en_US") : ?>
		<li><a href="https://translate.wordpress.org/projects/wp-plugins/content-aware-sidebars" target="_blank"><?php _e('Translate the plugin into your language','content-aware-sidebars'); ?></a></li>
<?php endif; ?>
	</ul>
</div>