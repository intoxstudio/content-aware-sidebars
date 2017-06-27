<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

$cas_fs = cas_fs();
$url = 'https://dev.institute/wordpress/sidebars-pro/pricing/?utm_source=plugin&utm_medium=referral&utm_content=conditions-bottom&utm_campaign=cas';
?>
<div style="overflow: hidden; padding: 2px 0px;">
	<div class="wpca-pull-right">
		<a href="<?php echo esc_url($cas_fs->get_upgrade_url()); ?>" class="button button-cas-upgrade button-small" target="_blank"><?php _e('Upgrade to Pro','content-aware-sidebars'); ?></a>
	</div>
	<div style="line-height:24px;">
		<span class="cas-heart">❤</span> 
		<?php printf(__('Like it? %1$sGet the Business Plan today for less than %2$s per site!%3$s','content-aware-sidebars'),'<b><a target="_blank" href="'.esc_url($url).'">','$24','</a></b>'); ?>
	</div>
</div>


