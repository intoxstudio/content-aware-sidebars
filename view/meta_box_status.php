<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

$activate_date = $post->post_status == CAS_App::STATUS_SCHEDULED ? $post->post_date : '';
$deactivate_date = get_post_meta($post->ID, CAS_App::META_PREFIX.'deactivate_time',true);

?>

<table class="form-table cas-form-table" width="100%"><tbody>
	<tr>
		<td scope="row"><?php _e("Status",'content-aware-sidebars'); ?></td>
		<td>
			<label class="cae-toggle">
				<input class="js-cas-status" type="checkbox" name="post_status" value="<?php echo CAS_App::STATUS_ACTIVE; ?>" <?php checked( in_array($post->post_status,array(CAS_App::STATUS_ACTIVE,'auto-draft')),true); ?> />
				<div class="cae-toggle-bar"></div>
			</label>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php _e("Activate",'content-aware-sidebars'); ?></td>
		<td>
			<span class="js-cas-activation">
				<input type="text" name="sidebar_activate" value="<?php echo $activate_date; ?>" data-input placeholder="<?php esc_attr_e('Select date','content-aware-sidebars'); ?>">
				<button type="button" class="button button-small" data-toggle><span class="dashicons dashicons-calendar"></span></button>
				<button type="button" class="button button-small" data-clear><span class="dashicons dashicons-no-alt"></span></button>
			</span>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php _e("Deactivate",'content-aware-sidebars'); ?></td>
		<td>
			<span class="js-cas-expiry">
				<input type="text" name="sidebar_deactivate" value="<?php echo $deactivate_date; ?>" data-input placeholder="<?php esc_attr_e('Never','content-aware-sidebars'); ?>">
				<button type="button" class="button button-small" data-toggle><span class="dashicons dashicons-calendar"></span></button>
				<button type="button" class="button button-small" data-clear><span class="dashicons dashicons-no-alt"></span></button>
			</span>
		</td>
	</tr>
</table>