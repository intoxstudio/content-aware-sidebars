<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

global $wp_locale;
$i = $start = get_option('start_of_week', 0);
$count = count($wp_locale->weekday);
$days = array_values($wp_locale->weekday);

?>

<p><?php _e('Display sidebar only in given time ranges on select days.', 'content-aware-sidebars'); ?></p>
<div>
<?php do {
    ?>
    <div>
        <label>
            <input type="checkbox" class="js-cas-pro-notice" data-url="https://dev.institute/wordpress-sidebars/pricing/?utm_source=plugin&utm_medium=popup&utm_content=time-schedule&utm_campaign=cas" /> <?php echo ucfirst($days[$i]); ?> (8:00 - 17:00)
        </label>
        <div class="cas-schedule-slide ui-slider ui-slider-horizontal ui-state-disabled ui-slider-disabled">
        <div class="ui-slider-range" style="left: 30%; width: 40%;"></div>
            <span class="ui-slider-handle ui-state-default" style="left: 30%;"></span>
            <span class="ui-slider-handle ui-state-default" style="left: 70%;"></span>
        </div>
    </div>
<?php
    $i = ($i + 1) % $count;
} while ($i != $start);
?>
</div>