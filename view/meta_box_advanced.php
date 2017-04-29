<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if ( !EMPTY_TRASH_DAYS ) {
	$delete_text = __('Delete Permanently');
} else {
	$delete_text = __('Move to Trash');
}

?>
<div>
	<strong><?php _e('Order'); ?></strong>
	<p>
		<label for="menu_order" class="screen-reader-text"><?php _e('Order'); ?></label>
		<input type="number" value="<?php echo $post->menu_order; ?>" id="menu_order" size="4" name="menu_order">
	</p>
</div>
<?php if ( current_user_can( "delete_post", $post->ID ) ) : ?>
	<div style="overflow:hidden;">
		<a class="wpca-pull-right cas-delete" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a>
	</div>
<?php endif; ?>