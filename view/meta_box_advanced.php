<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

if (!EMPTY_TRASH_DAYS) {
    $delete_text = __('Delete Permanently');
} else {
    $delete_text = __('Move to Trash');
}

?>

<table class="form-table cas-form-table" width="100%"><tbody>
    <tr>
        <td scope="row"><?php _e('Order'); ?></td>
        <td>
            <label for="menu_order" class="screen-reader-text"><?php _e('Order'); ?></label>
            <input type="number" value="<?php echo $post->menu_order; ?>" id="menu_order" size="4" name="menu_order">
        </td>
    </tr>

<?php if (current_user_can("delete_post", $post->ID)) : ?>
    <tr>
        <td scope="row"></td>
        <td>
            <a class="button button-cas-upgrade" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a>
        </td>
    </tr>
<?php endif; ?>
</table>