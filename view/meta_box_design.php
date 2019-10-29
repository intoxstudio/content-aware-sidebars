<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

$url = 'https://dev.institute/wordpress-sidebars/pricing/?utm_source=plugin&utm_medium=popup&utm_content=design&utm_campaign=cas';
$sticky_url = 'https://dev.institute/docs/content-aware-sidebars/faq/sticky-sidebar-theme-support/?utm_source=plugin&utm_medium=link&utm_content=design&utm_campaign=cas';

?>

<table class="form-table cas-form-table" width="100%">
    <tbody>
        <tr>
            <td></td>
            <td><strong><?php _e('Layout', 'content-aware-sidebars'); ?></strong>
            </td>
        </tr>
        <tr>
            <td scope="row">
                <?php _e('Sticky', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <label class="cae-toggle js-cas-pro-notice"
                    data-url="<?php echo $url; ?>">
                    <input type="checkbox" value="1" disabled />
                    <div class="cae-toggle-bar"></div>
                </label>
                <p><a href="<?php echo $sticky_url; ?>"
                        target="_blank"><?php _e('Check Theme Support', 'content-aware-sidebars'); ?></a>
                </p>
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Columns', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number" value="1" readonly
                    data-url="<?php echo $url; ?>" /> <?php _e('columns', 'content-aware-sidebars'); ?>
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Gap', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number" value="" readonly
                    data-url="<?php echo $url; ?>" /> px
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Background Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Padding', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Top', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Right', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Bottom', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Left', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" /> px
            </td>
        </tr>
        <tr>
            <td></td>
            <td><strong><?php _e('Widget', 'content-aware-sidebars'); ?></strong>
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Background Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field testing-something" value="" readonly />
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Padding', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Top', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Right', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Bottom', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" />
                <input class="cas-input-sm js-cas-pro-notice" type="number"
                    placeholder="<?php _e('Left', 'content-aware-sidebars'); ?>"
                    value="" readonly
                    data-url="<?php echo $url; ?>" /> px
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Border Width', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number" value="" readonly
                    data-url="<?php echo $url; ?>" /> px
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Border Radius', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input class="cas-input-sm js-cas-pro-notice" type="number" value="" readonly
                    data-url="<?php echo $url; ?>" /> px
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Border Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
        <tr>
            <td></td>
            <td><strong><?php _e('Text', 'content-aware-sidebars'); ?></strong>
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Text Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Title Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Link Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
        <tr>
            <td scope="row"><?php _e('Link Hover Color', 'content-aware-sidebars'); ?>
            </td>
            <td>
                <input type="text" class="js-cas-color-field" value="" readonly />
            </td>
        </tr>
    </tbody>
</table>