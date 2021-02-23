<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */
?>
<div class="notice notice-success updated js-cas-notice-review is-dismissible">
<p>
<?php printf(
    __('Hey %s, you have used %s for some time now, and I hope you like it so far!', 'content-aware-sidebars'),
    '<strong>'.$current_user->display_name.'</strong>',
    '<strong>Content Aware Sidebars</strong>'
); ?>
<br>
<?php printf(
    __('May I ask you to %ssupport it with a 5-star rating%s? I have spent countless hours on the plugin, and this will help make it even better.', 'content-aware-sidebars'),
    '<strong><a target="_blank" href="https://wordpress.org/support/plugin/content-aware-sidebars/reviews/?rate=5#new-post" data-cas-rating="1" rel="noopener noreferrer">',
    '</a></strong>'
); ?>
<br><br>
<?php _e('Your help is much appreciated. Thank you,', 'content-aware-sidebars'); ?>
<br>
- Joachim Jensen
</p>
<p><a target="_blank" class="button-primary" href="https://wordpress.org/support/plugin/content-aware-sidebars/reviews/?rate=5#new-post" data-cas-rating="1" rel="noopener noreferrer"><?php _e('OK, you deserve it', 'content-aware-sidebars'); ?></a> <button class="button-secondary"><?php _e('No, not good enough', 'content-aware-sidebars'); ?></button> <button class="button-secondary" data-cas-rating="1"><?php _e('I already rated it', 'content-aware-sidebars'); ?></button></p>
</div>
