<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */
?>

<div id="cas-account" class="postbox">
    <h3>
        <a href="https://dev.institute/account/" rel="noreferrer noopener" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php _e('Account Dashboard', 'content-aware-sidebars'); ?>
        </a>
    </h3>
    <span><?php echo implode(' &bull; ', $list); ?></span>
</div>