<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

$activate_date = $post->post_status == CAS_App::STATUS_SCHEDULED ? $post->post_date : '';
$deactivate_date = get_post_meta($post->ID, CAS_App::META_PREFIX.'deactivate_time', true);

?>

<table class="form-table cas-form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row"><?php _e('Activate', 'content-aware-sidebars'); ?></th>
            <td>
                <span class="js-cas-activation">
                    <input type="text" name="sidebar_activate"
                        value="<?php echo $activate_date; ?>"
                        data-input
                        placeholder="<?php esc_attr_e('Select date', 'content-aware-sidebars'); ?>">
                    <div class="button-group">
                        <button type="button" class="button button-small" data-toggle><span
                                class="dashicons dashicons-calendar"></span></button>
                        <button type="button" class="button button-small" data-clear><span
                                class="dashicons dashicons-no-alt"></span></button>
                    </div>
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Deactivate', 'content-aware-sidebars'); ?></th>
            <td>
                <span class="js-cas-expiry">
                    <input type="text" name="sidebar_deactivate"
                        value="<?php echo $deactivate_date; ?>"
                        data-input
                        placeholder="<?php esc_attr_e('Never', 'content-aware-sidebars'); ?>">
                    <div class="button-group">
                        <button type="button" class="button button-small" data-toggle><span
                                class="dashicons dashicons-calendar"></span></button>
                        <button type="button" class="button button-small" data-clear><span
                                class="dashicons dashicons-no-alt"></span></button>
                    </div>
                </span>
            </td>
        </tr>
    </tbody>
</table>