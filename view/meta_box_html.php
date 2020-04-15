<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

$data = CAS_App::instance()->manager()->metadata()->get('html')->get_data($post->ID, true);

$data_default = array_merge(array(
    'sidebar'       => '',
    'sidebar_class' => '',
    'widget'        => '',
    'widget_class'  => 'widget %2$s',
    'title'         => '',
    'title_class'   => 'widget-title'
), $data);

$sidebar_opts = array(
    'div'   => 'div',
    'aside' => 'aside',
    'ul'    => 'ul'
);
$widget_opts = array(
    'div'     => 'div',
    'li'      => 'li',
    'section' => 'section'
);
$widget_title_opts = array(
    'h2' => 'h2',
    'h3' => 'h3',
    'h4' => 'h4',
    'h5' => 'h5',
    'h6' => 'h6'
);

?>

<table class="form-table cas-form-table" width="100%"><tbody>
    <tr>
        <td scope="row"><?php _e('Sidebar'); ?></td>
        <td>
            <label class="cae-toggle">
                <input class="js-cas-html" type="checkbox" <?php checked(isset($data['sidebar'],$data['sidebar_class']), true); ?> data-target=".js-cas-html-sidebar" />
                <div class="cae-toggle-bar"></div>
            </label>
            <code style="display:inline-block;">
                <<select style="width:80px;" name="html[sidebar]" class="js-cas-html-sidebar">
                <?php foreach ($sidebar_opts as $k => $v) {
    echo '<option value="'.$k.'"'.selected($data_default['sidebar'], $k, false).'>'.$v.'</option>';
}
                ?>
                </select>
                class="<input type="text" name="html[sidebar_class]" value="<?php echo esc_html($data_default['sidebar_class']); ?>" class="js-cas-html-sidebar" />">
            </code>
        </td>
    </tr>
    <tr>
        <td scope="row"><?php _e('Widget'); ?></td>
        <td>
            <label class="cae-toggle">
                <input class="js-cas-html" type="checkbox" <?php checked(isset($data['widget'],$data['widget_class']), true); ?> data-target=".js-cas-html-widget" />
                <div class="cae-toggle-bar"></div>
            </label>
            <code style="display:inline-block;">
                <<select style="width:80px;" name="html[widget]" class="js-cas-html-widget">
                <?php foreach ($widget_opts as $k => $v) {
                    echo '<option value="'.$k.'"'.selected($data_default['widget'], $k, false).'>'.$v.'</option>';
                }
                ?>
                </select>
                class="<input type="text" name="html[widget_class]" value="<?php echo esc_html($data_default['widget_class']); ?>" class="js-cas-html-widget" />">
            </code>
        </td>
    </tr>
    <tr>
        <td scope="row"><?php _e('Widget Title'); ?></td>
        <td>
            <label class="cae-toggle">
                <input class="js-cas-html" type="checkbox" <?php checked(isset($data['title'],$data['title_class']), true); ?> data-target=".js-cas-html-widget-title" />
                <div class="cae-toggle-bar"></div>
            </label>
            <code style="display:inline-block;">
                <<select style="width:80px;" name="html[title]" class="js-cas-html-widget-title">
                <?php foreach ($widget_title_opts as $k => $v) {
                    echo '<option value="'.$k.'"'.selected($data_default['title'], $k, false).'>'.$v.'</option>';
                }
                ?>
                </select>
                class="<input type="text" name="html[title_class]" value="<?php echo esc_html($data_default['title_class']); ?>" class="js-cas-html-widget-title" />">
            </code>
        </td>
    </tr>
</tbody></table>
<p><?php _e('By default styling will be inherited from the Target Sidebar.', 'content-aware-sidebars'); ?></p>
