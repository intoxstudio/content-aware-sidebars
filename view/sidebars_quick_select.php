<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

$i = 0;
$user_can = current_user_can(CAS_App::CAPABILITY);
echo $nonce;
foreach ($sidebars as $id => $sidebar) :
    if ($i == $limit) : ?>
        <div class="cas-more" style="display:none;">
    <?php endif; ?>

    <div>
        <label style="display:block;padding:8px 0 4px;font-weight:bold;" for="ca_sidebars_'<?php echo $id; ?>"><?php echo $sidebar['label']; ?></label>
        <select style="width:100%;" id="ca_sidebars_<?php echo $id; ?>" class="js-cas-sidebars" name="cas_sidebars[<?php echo $id; ?>][]" multiple data-tags="<?php echo $user_can; ?>" data-placeholder="<?php esc_attr_e('Default'); ?>">
    <?php foreach ($sidebar['options'] as $sidebar_option) : ?>
        <option value="<?php echo $sidebar_option['id']; ?>"<?php selected(isset($sidebar_option['select'])); ?>><?php echo $sidebar_option['text']; ?></option>
    <?php endforeach;
    if ($user_can) : ?>
        <option value="0" disabled="disabled"><?php _e('Type to Add New Sidebar', 'content-aware-sidebars'); ?></option>
    <?php endif; ?>
        </select>
    </div>
<?php
    $i++;
endforeach;

if ($i > $limit) : ?>
    </div>
    <div style="text-align:center;"><button class="js-cas-more button button-small" data-toggle=".cas-more"><span class="dashicons dashicons-arrow-down-alt2"></span></button></div>
<?php endif; ?>

<p class="howto"><?php echo sprintf(__('Note: Selected Sidebars are displayed on this %s specifically.', 'content-aware-sidebars'), strtolower($singular)) . sprintf(
    __('Display sidebars per %s etc. with the %s.', 'content-aware-sidebars'),
    strtolower(implode(', ', array_slice($content, 0, 3))),
    '<a href="' . admin_url('admin.php?page=wpcas') . '">' . __('Sidebar Manager', 'content-aware-sidebars') . '</a>'
); ?>
</p>
