<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

$url = 'https://dev.institute/wordpress/sidebars-pro/pricing/?utm_source=plugin&utm_medium=referral&utm_content=upgrade-bottom&utm_campaign=cas';
?>
<div style="overflow: hidden; padding: 2px 0px;">
	<div style="float:right;">
		<a href="<?php echo esc_url($url); ?>" class="button button-cas-upgrade button-small" target="_blank"><?php _e('Upgrade to Pro','content-aware-sidebars'); ?></a>
	</div>
	<div style="line-height:24px;">
		<span class="cas-heart">❤</span> 
		<?php printf(__('Like it? %1$sGet Pro today for less than %2$s/month!%3$s','content-aware-sidebars'),'<b><a target="_blank" href="https://wordpress.org/support/plugin/content-aware-sidebars/reviews/?rate=5#new-post">','$5','</a></b>'); ?>
	</div>
</div>

