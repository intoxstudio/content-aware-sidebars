<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

CAS_Sidebar_Edit::form_field('handle');
CAS_Sidebar_Edit::form_field(
    'host',
    'js-cas-action js-cas-action-0 js-cas-action-1 js-cas-action-3'
);

echo "<div class='js-cas-action js-cas-action-2'><strong>".__('Shortcode')."</strong><p><input type='text' readonly value='[ca-sidebar id=\"$post->ID\"]' /></p></div>";

do_action('cas/sidebar/options', $post);

CAS_Sidebar_Edit::form_field(
    'merge_pos',
    'js-cas-action js-cas-action-0 js-cas-action-1'
);
