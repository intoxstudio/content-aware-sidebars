<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

$metadata = CAS_App::instance()->manager()->metadata();

?>

<table class="form-table cas-form-table" role="presentation">
    <tbody>
    <tr>
        <td><?php $setting = $metadata->get('protected_content'); echo $setting->get_title(); ?></td>
        <td>
            <?php CAS_Sidebar_Edit::form_field($setting); ?>
            <p class="description"><?php _e('Display even before password is entered.', 'content-aware-sidebars'); ?></p>
        </td>
    </tr>
    <tr>
        <td><?php _e('Order'); ?></td>
        <td>
            <label for="menu_order" class="screen-reader-text"><?php _e('Order'); ?></label>
            <input type="number" value="<?php echo $post->menu_order; ?>" id="menu_order" size="4" name="menu_order">
        </td>
    </tr>
    </tbody>
</table>