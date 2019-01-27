<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

$cas_fs = cas_fs();
/* translators: Publish box date format, see http://php.net/date */
$datef = __( 'M j, Y');
$date = date_i18n( $datef, strtotime( $post->post_date ) );

switch ($post->post_status) {
	case CAS_App::STATUS_SCHEDULED:
		$stamp = __('Activates on <b>%1$s</b>','content-aware-sidebars');
		break;
	case CAS_App::STATUS_ACTIVE:
		$stamp = __('Active','content-aware-sidebars');
		break;
	case CAS_App::STATUS_INACTIVE:
		$stamp = __('Inactive','content-aware-sidebars');
		break;
	default:
		$stamp = __('New','content-aware-sidebars');
		$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
		break;
}

$revision_count = 0;
if ( post_type_supports($post->post_type, 'revisions') ) {
	$revisions = wp_get_post_revisions( $post->ID );
	$revision_count = number_format_i18n(count($revisions));
}

?>

<div class="cas-save">
<?php if ( isset($_REQUEST['sidebar_id']) ) : ?>
	<a href="<?php echo add_query_arg('action','cas-revisions', admin_url('post.php?post='.$post->ID)); ?>" class="button button-secondary button-large" title="<?php _e('Widget Revisions','content-aware-sidebars'); ?>: <?php echo $revision_count; ?>">
		<span class="dashicons dashicons-backup" style="vertical-align: text-top;"></span>
		<span class="screen-reader-text"><?php _e('Widget Revisions','content-aware-sidebars'); ?></span>
	</a>
<?php endif; ?>

	<div class="wpca-pull-right">
<?php if ( $post->post_status == 'auto-draft' ) {
	submit_button( __( 'Create' ), 'primary button-large', 'publish', false );
} else {
	submit_button( __( 'Save' ), 'primary button-large', 'save', false );
} ?>
	</div>
</div>
<ul class="cas-overview-actions">
	<li style="overflow: hidden;">
		<span class="dashicons dashicons-calendar"></span> <strong><?php _e("Status",'content-aware-sidebars'); ?></strong>
		<div class="wpca-pull-right">
		<a class="js-nav-link" href="#top#section-schedule"><?php _e('Schedule'); ?></a>
		<label class="cae-toggle">
			<input class="js-cas-status" type="checkbox" name="post_status" value="<?php echo CAS_App::STATUS_ACTIVE; ?>" <?php checked( in_array($post->post_status,array(CAS_App::STATUS_ACTIVE,'auto-draft')),true); ?> />
			<div class="cae-toggle-bar"></div>
		</label>
	</div>
	</li>
	<li>
		<?php echo CAS_Sidebar_Edit::form_field('visibility', '', 'dashicons dashicons-visibility'); ?>
	</li>
</ul>