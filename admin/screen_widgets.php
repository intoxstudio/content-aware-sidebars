<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
 */

if (!defined('CAS_App::PLUGIN_VERSION')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class CAS_Admin_Screen_Widgets extends CAS_Admin {

	/**
	 * Get current screen
	 *
	 * @since  3.4
	 * @return string
	 */
	public function get_screen() {
		return 'widgets.php';
	}

	/**
	 * Prepare screen load
	 *
	 * @since  3.4
	 * @return void
	 */
	public function prepare_screen() {
		add_action( 'dynamic_sidebar_before',
			array($this,'render_sidebar_controls'));
	}

	/**
	 * Render controls for custom sidebars
	 *
	 * @since  3.3
	 * @param  string  $index
	 * @return void
	 */
	public function render_sidebar_controls($index) {
		//trashed custom sidebars not included
		$sidebars = CAS_App::instance()->_manager->sidebars;
		if(isset($sidebars[$index])) {
			$sidebar = $sidebars[$index];
			$link = admin_url('post.php?post='.$sidebar->ID);
			$edit_link = admin_url('admin.php?page=wpcas&sidebar_id='.$sidebar->ID);

			switch($sidebar->post_status) {
				case CAS_App::STATUS_ACTIVE:
					$status = __('Active');
					break;
				case CAS_App::STATUS_SCHEDULED:
					$status = __('Scheduled');
					break;
				default:
					$status = __('Inactive');
			}
			?>
				<div class="cas-settings">
				<div class="sidebar-status">
					<input type="checkbox" class="sidebar-status-input sidebar-status-<?php echo $sidebar->post_status; ?>" id="cas-status-<?php echo $sidebar->ID; ?>" value="<?php echo $sidebar->ID; ?>" <?php checked($sidebar->post_status, CAS_App::STATUS_ACTIVE) ?> disabled="disabled">
					<label title="<?php echo $status; ?>" class="sidebar-status-label" for="cas-status-<?php echo $sidebar->ID; ?>">
					</label>
				</div>

				<a title="<?php esc_attr_e('Edit Sidebar','content-aware-sidebars') ?>" class="dashicons dashicons-admin-generic cas-sidebar-link" href="<?php echo $edit_link; ?>"></a><a title="<?php esc_attr_e('Revisions') ?>" class="cas-sidebar-link" href="<?php echo add_query_arg('action','cas-revisions',$link); ?>"><i class="dashicons dashicons-backup"></i> <?php _e('Revisions') ?></a>
				</div>
			<?php
		}
	}

	/**
	 * Register and enqueue scripts styles
	 * for screen
	 *
	 * @since 3.4
	 */
	public function add_scripts_styles() {
		wp_register_style('cas/admin/style', plugins_url('../css/style.css', __FILE__), array(), CAS_App::PLUGIN_VERSION);
		wp_enqueue_style('cas/admin/style');

		$sidebar = get_post_type_object(CAS_App::TYPE_SIDEBAR);

		wp_register_script('cas/admin/widgets', plugins_url('../js/widgets.min.js', __FILE__), array('jquery'), CAS_App::PLUGIN_VERSION, true);
		wp_enqueue_script('cas/admin/widgets');
		wp_localize_script( 'cas/admin/widgets', 'CASAdmin', array(
			'addNew'         => $sidebar->labels->add_new_item,
			'collapse'       => __('Collapse','content-aware-sidebars'),
			'expand'         => __('Expand','content-aware-sidebars'),
			'filterSidebars' => __('Filter Sidebars','content-aware-sidebars'),
			'filterWidgets'  => __('Filter Widgets', 'content-aware-sidebars')
		));
	}

}

//