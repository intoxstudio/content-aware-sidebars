<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

//updated class for wp4.0 and below
echo '<div class="notice notice-success updated js-cas-notice-review">';
echo '<p>';
printf(__("Hey %s, it's Joachim from %s. You have used this free plugin for some time now, and I hope you like it!",'content-aware-sidebars'),
	'<strong>'.$current_user->display_name.'</strong>',
	'<strong>Content Aware Sidebars</strong>'
);
echo '<br>';
printf(__("I have spent countless hours developing it, and it would mean a lot to me if you %ssupport it with a quick review on WordPress.org.%s",'content-aware-sidebars'),
	'<strong><a target="_blank" href="https://wordpress.org/support/plugin/content-aware-sidebars/reviews/?rate=5#new-post">',
	'</a></strong>'
);
echo '</p>';
echo '<p><a target="_blank" class="button-primary" href="https://wordpress.org/support/plugin/content-aware-sidebars/reviews/?rate=5#new-post">'.__('Review Content Aware Sidebars','content-aware-sidebars').'</a> <button class="button-secondary">'.__("No thanks",'content-aware-sidebars').'</button></p>';
echo '</div>';
