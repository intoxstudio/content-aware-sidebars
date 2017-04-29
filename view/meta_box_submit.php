<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
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

?>

<div class="cas-save">
	<div class="wpca-pull-right">
<?php if ( $post->post_status == 'auto-draft' ) {
	submit_button( __( 'Save' ), 'primary button-large', 'publish', false );
} else {
	submit_button( __( 'Update' ), 'primary button-large', 'save', false );
} ?>
	</div>
</div>
<ul class="cas-overview-actions">
	<li><span class="dashicons dashicons-post-status"></span> <?php _e("Status:"); ?>
	<strong><?php printf($stamp,$date); ?></strong> <a class="js-nav-link" href="#top#section-schedule"><?php _e('Edit'); ?></a>

<?php if ( post_type_supports($post->post_type, 'revisions') ) :
	$revisions = wp_get_post_revisions( $post->ID );
	$revision_id = key( $revisions );
?>
	<li><span class="dashicons dashicons-backup"></span>
		<?php printf( __( 'Widget Revisions: %s', 'content-aware-sidebars' ), '<b>' . number_format_i18n(count($revisions)) . '</b>' ); ?>
		<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $revision_id ) ); ?>" title="<?php esc_attr_e( 'Browse revisions' ); ?>"><?php _ex( 'Browse', 'revisions' ); ?></a>
	</li>
<?php elseif ($cas_fs->is_not_paying() ) : ?>
	<li><span class="dashicons dashicons-backup"></span>
		<?php printf( __( 'Widget Revisions: %s', 'content-aware-sidebars' ), '<b>0</b>' ); ?>
		<b><a href="<?php echo esc_url($cas_fs->get_upgrade_url()); ?>"><?php _e( 'Enable','content-aware-sidebars'); ?></a></b>
	</li>
<?php endif; ?>
</ul>