<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
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
	 * Authorize user for screen
	 *
	 * @since  3.5
	 * @return boolean
	 */
	public function authorize_user() {
		return true;
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
		add_filter( 'admin_body_class',
			array($this,'widget_manager_class'));

		global $wp_registered_sidebars;

		$manager = CAS_App::instance()->manager();
		$manager->populate_metadata();

		$has_host = array(0=>1,1=>1,3=>1);

		foreach($manager->sidebars as $post) {
			$id = CAS_App::SIDEBAR_PREFIX.$post->ID;
			$handle_meta = $manager->metadata()->get('handle');

			$args = array();
			$args['description'] = $handle_meta->get_list_data($post->ID,true);

			if (isset($has_host[$handle_meta->get_data($post->ID)])) {
				$host = $manager->metadata()->get('host')->get_list_data($post->ID,false);
				$args['description'] .= ': ' . ($host ? $host :  __('Please update Host Sidebar', 'content-aware-sidebars') );
			}

			$wp_registered_sidebars[$id] = array_merge($wp_registered_sidebars[$id],$args);
		}
	}

	/**
	 * Add filters and actions for admin dashboard
	 * e.g. AJAX calls
	 *
	 * @since  3.5
	 * @return void
	 */
	public function admin_hooks() {
		add_action( 'wp_ajax_cas_sidebar_status',
			array($this,'ajax_set_sidebar_status'));
	}

	/**
	 * Add filters and actions for frontend
	 *
	 * @since  3.5
	 * @return void
	 */
	public function frontend_hooks() {

	}

	/**
	 * Set post type status on AJAX
	 *
	 * @since  3.5
	 * @return void
	 */
	public function ajax_set_sidebar_status() {

		//todo:validate nonce?

		if(!isset($_POST['sidebar_id'],$_POST['status'])) {
			wp_send_json_error('msg');
		}

		if(!current_user_can(CAS_App::CAPABILITY,$_POST['sidebar_id'])) {
			wp_send_json_error('msg');
		}
		
		$data = array();
		$status = filter_var($_POST['status'], FILTER_VALIDATE_BOOLEAN);
		if($status) {
			$data = array(
				'ID'            => $_POST['sidebar_id'],
				'post_status'   => CAS_App::STATUS_ACTIVE,
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', true )
			);
		} else {
			$data = array(
				'ID'          => $_POST['sidebar_id'],
				'post_status' => CAS_App::STATUS_INACTIVE
			);
		}

		if(!wp_update_post($data)) {
			wp_send_json_error('msg');
		}

		$data['title'] = $status ? __('Active','content-aware-sidebars') : __('Inactive','content-aware-sidebars');
		//$data['message'] = sprintf(__('Status set to %s'),$data['title']);

		wp_send_json_success($data);
	}

	/**
	 * Add body class to enable widget manager
	 *
	 * @since  3.6
	 * @param  string  $classes
	 * @return string
	 */
	public function widget_manager_class($classes) {
		if(version_compare(get_bloginfo('version'), '4.7', '>=')) {
			$classes .= ' cas-widget-manager ';
		}
		return $classes;
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
		$sidebars = CAS_App::instance()->manager()->sidebars;
		if(isset($sidebars[$index])) {
			$sidebar = $sidebars[$index];
			$link = admin_url('post.php?post='.$sidebar->ID);
			$edit_link = admin_url('admin.php?page=wpcas-edit&sidebar_id='.$sidebar->ID);

			switch($sidebar->post_status) {
				case CAS_App::STATUS_ACTIVE:
					$status = __('Active','content-aware-sidebars');
					break;
				case CAS_App::STATUS_SCHEDULED:
					$status = __('Scheduled');
					break;
				default:
					$status = __('Inactive','content-aware-sidebars');
			}
			?>
				<div class="cas-settings">
				<div class="sidebar-status">
					<input type="checkbox" class="sidebar-status-input sidebar-status-<?php echo $sidebar->post_status; ?>" id="cas-status-<?php echo $sidebar->ID; ?>" value="<?php echo $sidebar->ID; ?>" <?php checked($sidebar->post_status, CAS_App::STATUS_ACTIVE) ?>>
					<label title="<?php echo $status; ?>" class="sidebar-status-label" for="cas-status-<?php echo $sidebar->ID; ?>">
					</label>
				</div>

				<a title="<?php esc_attr_e('Edit Sidebar','content-aware-sidebars') ?>" class="dashicons dashicons-admin-generic cas-sidebar-link" href="<?php echo $edit_link; ?>"></a><a title="<?php esc_attr_e('Revisions') ?>" class="js-cas-pro-notice cas-sidebar-link" data-url="https://dev.institute/wordpress/sidebars-pro/pricing/?utm_source=plugin&utm_medium=popup&utm_content=widget-revisions&utm_campaign=cas" href="<?php echo add_query_arg('action','cas-revisions',$link); ?>"><i class="dashicons dashicons-backup"></i> <?php _e('Revisions') ?></a>
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
		wp_enqueue_style('cas/admin/style', plugins_url('../css/style.css', __FILE__), array(), CAS_App::PLUGIN_VERSION);

		$sidebar = get_post_type_object(CAS_App::TYPE_SIDEBAR);

		wp_enqueue_script('cas/admin/widgets', plugins_url('../js/widgets.min.js', __FILE__), array('jquery'), CAS_App::PLUGIN_VERSION, true);
		wp_localize_script( 'cas/admin/widgets', 'CASAdmin', array(
			'addNew'         => $sidebar->labels->add_new_item,
			'collapse'       => __('Collapse','content-aware-sidebars'),
			'expand'         => __('Expand','content-aware-sidebars'),
			'filterSidebars' => __('Filter Sidebars','content-aware-sidebars'),
			'filterWidgets'  => __('Filter Widgets', 'content-aware-sidebars'),
			'enableConfirm'  => __('This sidebar is already scheduled to be activated. Do you want to activate it now?', 'content-aware-sidebars')
		));
	}

}

//