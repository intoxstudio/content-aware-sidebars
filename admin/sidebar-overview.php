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

final class CAS_Sidebar_Overview extends CAS_Admin {

	/**
	 * Sidebar table
	 * @var CAS_Sidebar_List_Table
	 */
	public $table;

	/**
	 * Add filters and actions for admin dashboard
	 * e.g. AJAX calls
	 *
	 * @since  3.5
	 * @return void
	 */
	public function admin_hooks() {
		add_filter('set-screen-option',
			array($this,'set_screen_option'), 10, 3);
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
	 * Setup admin menus and get current screen
	 *
	 * @since  3.4
	 * @return string
	 */
	public function get_screen() {
		global $_wp_last_object_menu;

		$post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);

		add_menu_page( 
			$post_type_object->labels->name,
			$post_type_object->labels->name,
			$post_type_object->cap->edit_posts,
			CAS_App::BASE_SCREEN,
			array($this,'render_screen'),
			$post_type_object->menu_icon,
			++$_wp_last_object_menu
		);

		return add_submenu_page(
			CAS_App::BASE_SCREEN,
			$post_type_object->labels->name,
			$post_type_object->labels->all_items,
			$post_type_object->cap->edit_posts,
			CAS_App::BASE_SCREEN,
			array($this,'render_screen')
		);
	}


	/**
	 * Authorize user for screen
	 *
	 * @since  3.5
	 * @return boolean
	 */
	public function authorize_user() {
		$post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);
		return current_user_can( $post_type_object->cap->edit_posts );
	}

	/**
	 * Prepare screen load
	 *
	 * @since  3.4
	 * @return void
	 */
	public function prepare_screen() {

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option'  => 'cas_sidebars_per_page'
		));

		$this->table = new CAS_Sidebar_List_Table();
		$this->process_actions();//todo:add func to table to actions
		$this->table->prepare_items();

	}

	/**
	 * Render screen
	 *
	 * @since  3.4
	 * @return void
	 */
	public function render_screen() {
		$post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);

		//Not only for decoration
		//Older wp versions inject updated message after first h2
		if (version_compare(get_bloginfo('version'), '4.3', '<')) {
			$tag = 'h2';
		} else {
			$tag = 'h1';
		}

		echo '<div class="wrap">';
		echo '<'.$tag.'>';
		echo esc_html( $post_type_object->labels->name );
		
		if ( current_user_can( $post_type_object->cap->create_posts ) ) {
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=wpcas-edit' ) ) . '" class="add-new-h2 page-title-action">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
		}
		if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
			/* translators: %s: search keywords */
			printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
		}

		echo '</'.$tag.'>';

		$this->bulk_messages();

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'locked', 'skipped', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );

		$this->table->views();

		echo '<form id="posts-filter" method="get">';

		$this->table->search_box( $post_type_object->labels->search_items, 'post' );

		echo '<input type="hidden" name="page" value="wpcas" />';
		echo '<input type="hidden" name="post_status" class="post_status_page" value="'.(!empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : 'all').'" />';

		$this->table->display(); 

		echo '</form></div>';
	}

	/**
	 * Process actions
	 *
	 * @since  3.4
	 * @return void
	 */
	public function process_actions() {

		$post_type = CAS_App::TYPE_SIDEBAR;
		$doaction = $this->table->current_action();

		if ( $doaction ) {

			check_admin_referer('bulk-sidebars');

			$sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'locked', 'ids'), wp_get_referer() );

			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			if ( 'delete_all' == $doaction ) {
				global $wpdb;
				$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type=%s AND post_status = %s", CAS_App::TYPE_SIDEBAR, 'trash' ) );
				
				$doaction = 'delete';
			} elseif ( isset( $_REQUEST['ids'] ) ) {
				$post_ids = explode( ',', $_REQUEST['ids'] );
			} elseif ( !empty( $_REQUEST['post'] ) ) {
				$post_ids = array_map('intval', $_REQUEST['post']);
			}

			if ( !isset( $post_ids ) ) {
				wp_redirect( $sendback );
				exit;
			}

			switch ( $doaction ) {
				case 'trash':
					$trashed = $locked = 0;

					foreach ( (array) $post_ids as $post_id ) {
						if ( !current_user_can( 'delete_post', $post_id) )
							wp_die( __('You are not allowed to move this item to the Trash.') );

						if ( wp_check_post_lock( $post_id ) ) {
							$locked++;
							continue;
						}

						if ( !wp_trash_post($post_id) )
							wp_die( __('Error in moving to Trash.') );

						$trashed++;
					}

					$sendback = add_query_arg( array('trashed' => $trashed, 'ids' => join(',', $post_ids), 'locked' => $locked ), $sendback );
					break;
				case 'untrash':
					$untrashed = 0;
					foreach ( (array) $post_ids as $post_id ) {
						if ( !current_user_can( 'delete_post', $post_id) )
							wp_die( __('You are not allowed to restore this item from the Trash.') );

						if ( !wp_untrash_post($post_id) )
							wp_die( __('Error in restoring from Trash.') );

						$untrashed++;
					}
					$sendback = add_query_arg('untrashed', $untrashed, $sendback);
					break;
				case 'delete':
					$deleted = 0;
					foreach ( (array) $post_ids as $post_id ) {
						$post_del = get_post($post_id);

						if ( !current_user_can( 'delete_post', $post_id ) )
							wp_die( __('You are not allowed to delete this item.') );

						if ( !wp_delete_post($post_id) )
							wp_die( __('Error in deleting.') );
						
						$deleted++;
					}
					$sendback = add_query_arg('deleted', $deleted, $sendback);
					break;
			}

			$sendback = remove_query_arg( array('action', 'action2', 'post_status', 'post', 'bulk_edit'), $sendback );

			wp_safe_redirect($sendback);
			exit;
		} elseif ( ! empty($_REQUEST['_wp_http_referer']) ) {
			wp_safe_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI']) ) );
			exit;
		}

	}

	/**
	 * Set screen options on save
	 *
	 * @since 3.4
	 * @param string  $status
	 * @param string  $option
	 * @param string  $value
	 */
	public function set_screen_option($status, $option, $value) {
		if ($option == 'cas_sidebars_per_page') {
			return $value;
		}
		return $status;
	}

	public function bulk_messages() {

		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets','content-aware-sidebars'));

		$bulk_messages = array(
			'updated'   => _n_noop( '%s sidebar updated.', '%s sidebars updated.', 'content-aware-sidebars'),
			'locked'    => _n_noop( '%s sidebar not updated, somebody is editing it.', '%s sidebars not updated, somebody is editing them.', 'content-aware-sidebars'),
			'deleted'   => _n_noop( '%s sidebar permanently deleted.', '%s sidebars permanently deleted.', 'content-aware-sidebars'),
			'trashed'   => _n_noop( '%s sidebar moved to the Trash.', '%s sidebars moved to the Trash.', 'content-aware-sidebars'),
			'untrashed' => _n_noop( '%s sidebar restored from the Trash.', '%s sidebars restored from the Trash.', 'content-aware-sidebars'),
		);
		$bulk_messages = apply_filters('cas/admin/bulk_messages',$bulk_messages);

		$messages = array();
		foreach ( $bulk_messages as $key => $message ) {
			if(isset($_REQUEST[$key] )) {
				$count = absint( $_REQUEST[$key] );
				if($count) {
					$messages[] = sprintf(
						translate_nooped_plural($message, $count ),
						number_format_i18n( $count )
					);

					if ( $key == 'trashed' && isset( $_REQUEST['ids'] ) ) {
						$ids = preg_replace( '/[^0-9,]/', '', $_REQUEST['ids'] );
						$messages[] = '<a href="' . esc_url( wp_nonce_url( "admin.php?page=wpcas&doaction=undo&action=untrash&ids=$ids", "bulk-sidebars" ) ) . '">' . __('Undo') . '</a>';
					}
				}
			}
		}

		if ( $messages )
			echo '<div id="message" class="updated notice is-dismissible"><p>' . join( ' ', $messages ) . '</p></div>';
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
	}

}

//eol