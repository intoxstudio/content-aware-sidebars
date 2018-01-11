<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

$cas_fs = cas_fs();
$url = $cas_fs->addon_url('cas-totem');
?>
<div style="overflow: hidden; padding: 2px 0px;">
	<div class="wpca-pull-right">
		<a href="<?php echo esc_url($cas_fs->get_upgrade_url()); ?>" class="button button-cas-upgrade button-small" target="_blank"><?php _e('Upgrade to Pro','content-aware-sidebars'); ?></a>
	</div>
	<div style="line-height:24px;">
		<span class="cas-heart">❤</span> 
		<?php printf(__('New FREE Add-On: %s'), '<b><a href="'.esc_url($url).'">'.__('Totem - Display widgets in a popup box like Intercom','content-aware-sidebars').'</a></b>'); ?>
	</div>
</div>


