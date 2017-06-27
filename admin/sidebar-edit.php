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

final class CAS_Sidebar_Edit extends CAS_Admin {

	/**
	 * Intro tour manager
	 * @var WP_Pointer_Tour
	 */
	private $_tour_manager;

	/**
	 * Add filters and actions for admin dashboard
	 * e.g. AJAX calls
	 *
	 * @since  3.5
	 * @return void
	 */
	public function admin_hooks() {
		$this->_tour_manager = new WP_Pointer_Tour(CAS_App::META_PREFIX.'cas_tour');

		add_action('delete_post',
			array($this,'remove_sidebar_widgets'));
		add_action('save_post_'.CAS_App::TYPE_SIDEBAR,
			array($this,'save_post'),10,2);
		add_action('cas/admin/add_meta_boxes',
			array($this,'create_meta_boxes'));

		add_filter('wp_insert_post_data',
			array($this,'add_duplicate_title_suffix'),99,2);
		add_filter( 'get_edit_post_link',
			array($this,'get_edit_post_link'), 10, 3 );
		add_filter( 'get_delete_post_link',
			array($this,'get_delete_post_link'), 10, 3 );

		if (cas_fs()->is_not_paying() )  {
			add_action('wp_ajax_cas_dismiss_review_notice',
				array($this,'ajax_review_clicked'));
			add_filter('wpca/modules/list',
				array($this,'add_to_module_list'),99);
			add_action( 'admin_enqueue_scripts',
				array($this,'add_general_scripts_styles'));
			add_action( 'all_admin_notices',
				array($this,'admin_notice_review'));
		}
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
	 * Show extra elements in content type list
	 *
	 * @since 3.3
	 * @param array  $list
	 */
	public function add_to_module_list($list) {
		if(get_post_type() == CAS_App::TYPE_SIDEBAR) {
			$list[''] = array(
				'name' =>__('URLs (Pro Feature)','content-aware-sidebars'),
				'placeholder' => '',
				'default_value' => ''
			);
		}
		return $list;
	}

	/**
	 * Render conditons description
	 *
	 * @since  3.3
	 * @param  string  $post_type
	 * @return void
	 */
	public function show_description($post_type) {
		if($post_type == CAS_App::TYPE_SIDEBAR) {
			_e('Display this sidebar only on content that meets the following conditions:','content-aware-sidebars');
			echo '<p></p>';
		}
	}

	/**
	 * Set up admin menu and get current screen
	 *
	 * @since  3.4
	 * @return string
	 */
	public function get_screen() {
		$post_type_object = get_post_type_object(CAS_App::TYPE_SIDEBAR);
		return add_submenu_page(
			CAS_App::BASE_SCREEN,
			$post_type_object->labels->add_new_item,
			$post_type_object->labels->add_new,
			$post_type_object->cap->edit_posts,
			CAS_App::BASE_SCREEN.'-edit',
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
		return true;
	}

	/**
	 * Prepare screen load
	 *
	 * @since  3.4
	 * @return void
	 */
	public function prepare_screen() {

		global $nav_tabs, $post, $title, $active_post_lock;

		$post_type = CAS_App::TYPE_SIDEBAR;
		$post_type_object = get_post_type_object( $post_type );
		$post_id = isset($_REQUEST['sidebar_id']) ? $_REQUEST['sidebar_id'] : 0;

		//process actions
		$this->process_actions($post_id);

		if ( is_multisite() ) {
			add_action( 'admin_footer', '_admin_notice_post_locked' );
		} else {
			$check_users = get_users( array( 'fields' => 'ID', 'number' => 2 ) );
			if ( count( $check_users ) > 1 )
				add_action( 'admin_footer', '_admin_notice_post_locked' );
			unset( $check_users );
		}

		wp_enqueue_script('post');

		if ( wp_is_mobile() ) {
			wp_enqueue_script( 'jquery-touch-punch' );
		}

		// Add the local autosave notice HTML
		//add_action( 'admin_footer', '_local_storage_notice' );

		/**
		 * Edit mode
		 */
		if($post_id) {
			$post = get_post($post_id, OBJECT, 'edit');

			if ( ! $post )
				wp_die( __( 'The sidebar no longer exists.' ) );
			if ( ! current_user_can( 'edit_post', $post_id ) )
				wp_die( __( 'You are not allowed to edit this sidebar.' ) );
			if ( 'trash' == $post->post_status )
				wp_die( __( 'You cannot edit this sidebar because it is in the Trash. Please restore it and try again.' ) );

			if ( ! empty( $_GET['get-post-lock'] ) ) {
				check_admin_referer( 'lock-post_' . $post_id );
				wp_set_post_lock( $post_id );
				wp_redirect( get_edit_post_link( $post_id, 'url' ) );
				exit();
			}

			if ( ! wp_check_post_lock( $post->ID ) ) {
				$active_post_lock = wp_set_post_lock( $post->ID );
				//wp_enqueue_script('autosave');
			}

			$title = $post_type_object->labels->edit_item;

		/**
		 * New Mode
		 */
		} else {

			if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ) {
				wp_die(
					'<p>' . __( 'You are not allowed to create sidebars.', 'content-aware-sidebars' ) . '</p>',
					403
				);
			}

			//wp_enqueue_script( 'autosave' );

			$post = get_default_post_to_edit( $post_type, true );

			$title = $post_type_object->labels->add_new_item;

		}

		$nav_tabs = array(
			'conditions' => __('Conditions','content-aware-sidebars'),
			'schedule'   => __('Schedule'),
			'design'     => __('Design'),
			'advanced'   => __('Advanced')
		);
		$nav_tabs = apply_filters('cas/admin/nav-tabs', $nav_tabs);

		do_action( 'cas/admin/add_meta_boxes', $post );

		// foreach ($nav_tabs as $id => $label) {
		// 	do_action( 'do_meta_boxes', CAS_App::BASE_SCREEN.'-edit', 'section-'.$id, $post );
		// }
		//do_action( 'do_meta_boxes', CAS_App::BASE_SCREEN.'-edit', 'normal', $post );
		//do_action( 'do_meta_boxes', CAS_App::BASE_SCREEN.'-edit', 'side', $post );

		$screen = get_current_screen();

		$screen->add_help_tab( array( 
			'id'      => CAS_App::META_PREFIX.'help',
			'title'   => __('Condition Groups','content-aware-sidebars'),
			'content' => '<p>'.__('Each created condition group describe some specific content (conditions) that the current sidebar should be displayed with.','content-aware-sidebars').'</p>'.
				'<p>'.__('Content added to a condition group uses logical conjunction, while condition groups themselves use logical disjunction. '.
				'This means that content added to a group should be associated, as they are treated as such, and that the groups do not interfere with each other. Thus it is possible to have both extremely focused and at the same time distinct conditions.','content-aware-sidebars').'</p>',
		) );
		$screen->set_help_sidebar( '<h4>'.__('More Information').'</h4>'.
			'<p><a href="https://dev.institute/docs/content-aware-sidebars/faq/?utm_source=plugin&utm_medium=referral&utm_content=help-tab&utm_campaign=cas" target="_blank">'.__('FAQ','content-aware-sidebars').'</a></p>'.
			'<p><a href="http://wordpress.org/support/plugin/content-aware-sidebars" target="_blank">'.__('Forum Support','content-aware-sidebars').'</a></p>'
		);

	}

	/**
	 * Process actions
	 *
	 * @since  3.4
	 * @param  int  $post_id
	 * @return void
	 */
	public function process_actions($post_id) {
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		if ( isset( $_POST['deletepost'] ) )
			$action = 'delete';

		if($action && $post_id) {
			//wp_reset_vars( array( 'action' ) );
			$sendback = wp_get_referer();
			$sendback = remove_query_arg(
				array('action','trashed', 'untrashed', 'deleted', 'ids'), 
				$sendback
			);

			$post = get_post( $post_id );
			if ( ! $post ) {
				wp_die( __( 'The sidebar no longer exists.', 'content-aware-sidebars' ) );
			}

			switch($action) {
				case 'editpost':
					check_admin_referer('update-post_' . $post_id);

					$post_id = $this->update_sidebar_type();

					// Session cookie flag that the post was saved
					if ( isset( $_COOKIE['wp-saving-post'] ) && $_COOKIE['wp-saving-post'] === $post_id . '-check' ) {
						setcookie( 'wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
					}

					$status = get_post_status( $post_id );
					if(isset($_POST['original_post_status']) && $_POST['original_post_status'] == $status) {
						$message = 1;
					} else {
						switch ( $status ) {
							case CAS_App::STATUS_SCHEDULED:
								//gets scheduled
								$message = 9;
								break;
							case CAS_App::STATUS_INACTIVE:
								//gets deactivated
								$message = 10;
								break;
							case CAS_App::STATUS_ACTIVE:
								//gets activated
								$message = 6;
								break;
							default:
								$message = 1;
						}
					}

					$sendback = add_query_arg(array(
						'sidebar_id' => $post_id,
						'message'    => $message,
						'page'       => 'wpcas-edit'
					), $sendback);
					wp_safe_redirect($sendback);
					exit();
				case 'trash':
					check_admin_referer('trash-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to move this sidebar to the Trash.', 'content-aware-sidebars' ) );

					if ( $user_id = wp_check_post_lock( $post_id ) ) {
						$user = get_userdata( $user_id );
						wp_die( sprintf( __( 'You cannot move this sidebar to the Trash. %s is currently editing.', 'content-aware-sidebars' ), $user->display_name ) );
					}

					if ( ! wp_trash_post( $post_id ) )
						wp_die( __( 'Error in moving to Trash.' ) );

					$sendback = remove_query_arg('sidebar_id',$sendback);

					wp_safe_redirect(add_query_arg(
						array(
							'page'    => 'wpcas',
							'trashed' => 1,
							'ids'     => $post_id
						), $sendback ));
					exit();
				case 'untrash':
					check_admin_referer('untrash-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to restore this sidebar from the Trash.', 'content-aware-sidebars' ) );

					if ( ! wp_untrash_post( $post_id ) )
						wp_die( __( 'Error in restoring from Trash.' ) );

					wp_safe_redirect( add_query_arg('untrashed', 1, $sendback) );
					exit();
				case 'delete':
					check_admin_referer('delete-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to delete this sidebar.', 'content-aware-sidebars' ) );

					if ( ! wp_delete_post( $post_id, true ) )
						wp_die( __( 'Error in deleting.' ) );

					$sendback = remove_query_arg('sidebar_id',$sendback);
					wp_safe_redirect( add_query_arg(array(
						'page' => 'wpcas',
						'deleted' => 1
					), $sendback ));
					exit();
				default:
					do_action('cas/admin/action', $action, $post);
					break;
			}
		}
	}

	/**
	 * Render screen
	 *
	 * @since  3.4
	 * @return void
	 */
	public function render_screen() {

		global $nav_tabs, $post, $title, $active_post_lock;

		$post_type_object = get_post_type_object( $post->post_type );

		$message = false;
		if ( isset($_GET['message']) ) {
			$messages = $this->sidebar_updated_messages($post);
			$_GET['message'] = absint( $_GET['message'] );
			if ( isset($messages[$_GET['message']]) )
				$message = $messages[$_GET['message']];
		}

		$notice = false;
		$form_extra = '';
		if ( 'auto-draft' == $post->post_status ) {
			if (isset($_REQUEST['sidebar_id']) ) {
				$post->post_title = '';
			}
			//$autosave = false;
			$form_extra .= "<input type='hidden' id='auto_draft' name='auto_draft' value='1' />";
		}
		// else {
		// 	$autosave = wp_get_post_autosave( $post->ID );
		// }

		// Detect if there exists an autosave newer than the post and if that autosave is different than the post
		// if ( $autosave && mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
		// 	foreach ( _wp_post_revision_fields( $post ) as $autosave_field => $_autosave_field ) {
		// 		if ( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
		// 			$notice = sprintf( __( 'There is an autosave of this post that is more recent than the version below. <a href="%s">View the autosave</a>' ), get_edit_post_link( $autosave->ID ) );
		// 			break;
		// 		}
		// 	}
		// 	// If this autosave isn't different from the current post, begone.
		// 	if ( ! $notice )
		// 		wp_delete_post_revision( $autosave->ID );
		// 	unset($autosave_field, $_autosave_field);
		// }

		//Not only for decoration
		//Older wp versions inject updated message after first h2
		if (version_compare(get_bloginfo('version'), '4.3', '<')) {
			$tag = 'h2';
		} else {
			$tag = 'h1';
		}

		echo '<div class="wrap">';
		echo '<'.$tag.'>';
		echo esc_html( $title );
		if ( isset($_REQUEST['sidebar_id']) && current_user_can( $post_type_object->cap->create_posts ) ) {
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=wpcas-edit' ) ) . '" class="page-title-action add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
		}
		echo '</'.$tag.'>';
		if ( $message ) {
			echo '<div id="message" class="updated notice notice-success is-dismissible"><p>'.$message.'</p></div>';
		} 
		echo '<form name="post" action="admin.php?page=wpcas-edit" method="post" id="post">';
		$referer = wp_get_referer();
		wp_nonce_field('update-post_' . $post->ID);
		echo '<input type="hidden" id="user-id" name="user_ID" value="'.(int)get_current_user_id().'" />';
		echo '<input type="hidden" id="hiddenaction" name="action" value="editpost" />';
		echo '<input type="hidden" id="post_author" name="post_author" value="'.esc_attr($post->post_author).'" />';
		echo '<input type="hidden" id="original_post_status" name="original_post_status" value="'.esc_attr( $post->post_status).'" />';
		echo '<input type="hidden" id="referredby" name="referredby" value="'.($referer ? esc_url( $referer ) : '').'" />';
		echo '<input type="hidden" id="post_ID" name="sidebar_id" value="'.esc_attr($post->ID).'" />';
		if ( ! empty( $active_post_lock ) ) {
			echo '<input type="hidden" id="active_post_lock" value="'.esc_attr(implode( ':', $active_post_lock )).'" />';
		}
		if ( get_post_status( $post ) != CAS_App::STATUS_INACTIVE) {
			wp_original_referer_field(true, 'previous');
		}
		echo $form_extra;

		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-'.(1 == get_current_screen()->get_columns() ? '1' : '2').'">';
		echo '<div id="post-body-content">';
		echo '<div id="titlediv">';
		echo '<div id="titlewrap">';
		echo '<label class="screen-reader-text" id="title-prompt-text" for="title">'.__( 'Enter title here' ).'</label>';
		echo '<input type="text" name="post_title" size="30" value="'.esc_attr( $post->post_title ).'" id="title" spellcheck="true" autocomplete="off" />';
		echo '</div></div>';
		$this->render_section_nav($nav_tabs);
		echo '</div>';
		$this->render_sections($nav_tabs,$post,$post->post_type);
		echo '</div>';
		echo '<br class="clear" />';
		echo '</div></form></div>';
	}

	/**
	 * Render tab navigation
	 *
	 * @since  3.4
	 * @param  array  $tabs
	 * @return void
	 */
	public function render_section_nav($tabs) {
		echo '<h2 class="nav-tab-wrapper js-cas-tabs hide-if-no-js " style="padding-bottom:0;">';
		foreach ($tabs as $id => $label) {
			echo '<a class="js-nav-link nav-tab" href="#top#section-'.$id.'">'.$label.'</a>';
		}
		echo '</h2>';
	}

	/**
	 * Render meta box sections
	 *
	 * @since  3.4
	 * @param  array    $tabs
	 * @param  WP_Post  $post
	 * @param  string   $post_type
	 * @return void
	 */
	public function render_sections($tabs, $post, $post_type) {
		echo '<div id="postbox-container-1" class="postbox-container">';
		do_meta_boxes(CAS_App::BASE_SCREEN.'-edit', 'side', $post);
		echo '</div>';
		echo '<div id="postbox-container-2" class="postbox-container">';
		foreach ($tabs as $id => $label) {
			$name = 'section-'.$id;
			echo '<div id="'.$name.'" class="cas-section">';
			do_meta_boxes(CAS_App::BASE_SCREEN.'-edit', $name, $post);
			echo '</div>';
		}
		//boxes across sections
		do_meta_boxes(CAS_App::BASE_SCREEN.'-edit', 'normal', $post);
		echo '</div>';
	}

	/**
	 * Update sidebar post type
	 *
	 * @since  3.4
	 * @return int
	 */
	public function update_sidebar_type() {
		global $wpdb;
 
		$post_ID = (int) $_POST['sidebar_id'];
		$post = get_post( $post_ID );
		$post_data['post_type'] = CAS_App::TYPE_SIDEBAR;
		$post_data['ID'] = (int) $post_ID;
		$post_data['post_title'] = $_POST['post_title'];
		$post_data['comment_status'] = 'closed';
		$post_data['ping_status'] = 'closed';
		$post_data['post_author'] = get_current_user_id();
		$post_data['menu_order'] = intval($_POST['menu_order']);

		$ptype = get_post_type_object($post_data['post_type']);

		if ( !current_user_can( 'edit_post', $post_ID ) ) {
				wp_die( __('You are not allowed to edit this sidebar.', 'content-aware-sidebars' ));
		} elseif (! current_user_can( $ptype->cap->create_posts ) ) {
				return new WP_Error( 'edit_others_posts', __( 'You are not allowed to create sidebars.', 'content-aware-sidebars' ) );
		} elseif ( $post_data['post_author'] != $_POST['post_author'] 
			 && ! current_user_can( $ptype->cap->edit_others_posts ) ) {
			return new WP_Error( 'edit_others_posts', __( 'You are not allowed to edit this sidebar.', 'content-aware-sidebars' ) );
		}
	 
		if ( isset($_POST['post_status']) ) {
			 $post_data['post_status'] = CAS_App::STATUS_ACTIVE;
			//if sidebar has been future before, we need to reset date
			if($_POST['post_status'] != $_POST['original_post_status']) {
				$post_data['post_date'] = current_time( 'mysql' );
			}
		} elseif($_POST['sidebar_activate']) {
			$_POST['post_status'] = CAS_App::STATUS_SCHEDULED; //yoast seo expects this
			$post_data['post_status'] = CAS_App::STATUS_SCHEDULED;
			$post_data['post_date'] = $_POST['sidebar_activate'];
		} else {
			$_POST['post_status'] = CAS_App::STATUS_INACTIVE;
			$post_data['post_status'] = CAS_App::STATUS_INACTIVE;
		}

		if($post_data['post_status'] != CAS_App::STATUS_INACTIVE 
			&& $_POST['sidebar_deactivate']) {
			$this->reschedule_deactivation($post_ID,$_POST['sidebar_deactivate']);
		} else {
			$this->reschedule_deactivation($post_ID);
		}

		if(isset($post_data['post_date'])) {
			$post_data['post_date_gmt'] = get_gmt_from_date( $post_data['post_date'] );
		}
	 
		if ( post_type_supports( CAS_App::TYPE_SIDEBAR, 'revisions' ) ) {
			$revisions = wp_get_post_revisions( $post_ID, array(
				'order'          => 'ASC',
				'posts_per_page' => 1
			));
			$revision = current( $revisions );
			// Check if the revisions have been upgraded
			if ( $revisions && _wp_get_post_revision_version( $revision ) < 1 )
				_wp_upgrade_revisions_of_post( $post, wp_get_post_revisions( $post_ID ) );
		}
	 
		update_post_meta( $post_ID, '_edit_last', $post_data['post_author'] );
		$success = wp_update_post( $post_data );
		wp_set_post_lock( $post_ID );

		return $post_ID;
	}

	/**
	 * Handle schedule for deactivation
	 *
	 * @since  3.4
	 * @param  int    $post_id
	 * @param  string $time
	 * @return void
	 */
	public function reschedule_deactivation($post_id, $time = false) {
		$name = 'cas/event/deactivate';
		if (wp_next_scheduled($name,array($post_id)) !== false) {
			wp_clear_scheduled_hook($name,array($post_id));
		}

		if($time) {
			//Requires to be in GMT
			$utime = get_gmt_from_date($time,'U');
			wp_schedule_single_event($utime,$name,array($post_id));
			update_post_meta($post_id, CAS_App::META_PREFIX.'deactivate_time',$time);
		} else {
			delete_post_meta($post_id, CAS_App::META_PREFIX.'deactivate_time');
		}
	}

	/**
	 * Create update messages
	 * 
	 * @param  array  $messages 
	 * @return array           
	 */
	public function sidebar_updated_messages($post) {
		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets','content-aware-sidebars'));
		return array(
			1 => __('Sidebar updated.','content-aware-sidebars').$manage_widgets,
			6 => __('Sidebar activated.','content-aware-sidebars').$manage_widgets,
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>.','content-aware-sidebars'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime($post->post_date))).$manage_widgets,
			10 => __('Sidebar deactivated.','content-aware-sidebars').$manage_widgets,
		);
	}

	/**
	 * Set pointers for tour and enqueue script
	 *
	 * @since  3.3
	 * @return void
	 */
	private function create_pointers() {
		if($this->_tour_manager->user_has_finished_tour()) {
			return;
		}

		$this->_tour_manager->set_pointers(array(
			array(
				'content'   => sprintf( '<h3>%s</h3>%s',
					__( 'Get Started in 3 Easy Steps', 'content-aware-sidebars' ),
					wpautop(__( "You've just installed or updated Content Aware Sidebars. Awesome!\n\nYou can display sidebars on any page or in any context. If that is new to you, this 3 step interactive guide will show you just how easy it is.", 'content-aware-sidebars' ) )),
				'ref_id'    => '#titlediv',
				'position'  => array(
					'edge'      => 'top',
					'align'     => 'center'
				),
				'pointerWidth' => 400,
				'next' => __('Start Quick Tour','content-aware-sidebars'),
				'dismiss' => __('I know how to use it','content-aware-sidebars')
			),
			array(
				'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
					'1. '.__( 'Select Content Type', 'content-aware-sidebars' ),
					wpautop(__( "With this dropdown you can select on what conditions the sidebar should be displayed.\n\nContent Aware Sidebars has built-in support for many types of content and even other plugins!\n\nSelect something to continue the tour. You can change it later.", 'content-aware-sidebars' ) )),
				'ref_id'    => '.cas-group-new',
				'position'  => array(
					'edge'      => 'top',
					'align'     => 'center'
				),
				'prev' => false,
				'next' => '.js-wpca-add-or',
				'nextEvent' => 'change'
			),
			array(
				'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
					'2. '.__( 'Condition Groups', 'content-aware-sidebars' ),
					wpautop(__( "Click on the input field and select the content you want.\n\nIf you can't find the right content in the list, type something to search.\n\n You can add several types of content to the same group, try e.g. \"All Posts\" and an Author to target all posts written by that author. Awesome!", 'content-aware-sidebars' ) )),
				'ref_id'    => '#cas-groups > ul',
				'position'  => array(
					'edge'      => 'top',
					'align'     => 'center'
				)
			),
			array(
				'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
					'3. '.__( 'Options, options', 'content-aware-sidebars' ),
					wpautop(__( "Should the sidebar merge with a target sidebar or replace it? Maybe you want to insert it in your content with a shortcode.\n\nDisplay the sidebar for everyone or make it visible only for logged-in users.\n\n You are in control.", 'content-aware-sidebars' ) )),
				'ref_id'    => '#cas-options',
				'position'  => array(
					'edge'      => 'right',
					'align'     => 'top'
				)
			),
			array(
				'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
					__( 'Help and Support', 'content-aware-sidebars' ),
					wpautop(__( "That's it! Now you can start creating sidebars and display them on your own conditions.\n\nIf you need more help, click on the \"Help\" tab here.", 'content-aware-sidebars' ) )),
				'ref_id'    => '#contextual-help-link-wrap',
				'position'  => array(
					'edge'      => 'top',
					'align'     => 'right'
				),
				'dismiss' => __('Finish Tour','content-aware-sidebars')
			)
		));
		$this->_tour_manager->enqueue_scripts();
	}

	/**
	 * Meta boxes for sidebar edit
	 * @global object $post
	 * @return void 
	 */
	public function create_meta_boxes($post) {

		$this->create_pointers();
		CAS_App::instance()->manager()->populate_metadata();
		$path = plugin_dir_path( __FILE__ ).'../view/';

		$cas_fs = cas_fs();

		add_action('wpca/meta_box/before',
			array($this,'show_description'));

		$boxes = array();
		$boxes[] = array(
			'id'       => 'submitdiv',
			'title'    => __('Publish'),
			'view'     => 'submit',
			'context'  => 'side',
			'priority' => 'high'
		);
		$boxes[] = array(
			'id'       => 'cas-status',
			'title'    => __('Sidebar Status', 'content-aware-sidebars'),
			'view'     => 'status',
			'context'  => 'section-schedule',
			'priority' => 'default'
		);
		$boxes[] = array(
			'id'       => 'cas-widget-html',
			'title'    => __('Layout', 'content-aware-sidebars'),
			'view'     => 'html',
			'context'  => 'section-design',
			'priority' => 'default'
		);
		$boxes[] = array(
			'id'       => 'cas-advanced',
			'title'    => __('Advanced', 'content-aware-sidebars'),
			'view'     => 'advanced',
			'context'  => 'section-advanced',
			'priority' => 'default'
		);

		if ( $cas_fs->is_not_paying() ) {
			$view = $template = WPCAView::make($path.'conditions_after.php');
			add_action('wpca/meta_box/after',
				array($view,'render'));

			$boxes[] = array(
				'id'       => 'cas-plugin-links',
				'title'    => __('Content Aware Sidebars', 'content-aware-sidebars'),
				'view'     => 'support',
				'context'  => 'side',
				'priority' => 'default'
			);
			$boxes[] = array(
				'id'       => 'cas-schedule',
				'title'    => __('Time Schedule', 'content-aware-sidebars'),
				'view'     => 'schedule',
				'context'  => 'section-schedule',
				'priority' => 'default'
			);
		}

		//Options
		$boxes[] = array(
			'id'       => 'cas-options',
			'title'    => __('Options', 'content-aware-sidebars'),
			'callback' => 'meta_box_options',
			'context'  => 'side',
			'priority' => 'default'
		);

		//Add meta boxes
		foreach($boxes as $box) {
			if(isset($box['view'])) {
				$view = $template = WPCAView::make($path.'meta_box_'.$box['view'].'.php',array(
					'post'=> $post
				));
				$callback = array($view,'render');
			} else {
				$callback = array($this, $box['callback']);
			}
			add_meta_box(
				$box['id'],
				$box['title'],
				$callback,
				CAS_App::BASE_SCREEN.'-edit',
				$box['context'],
				$box['priority']
			);
		}

		//todo: refactor add of meta box
		//with new bootstrapper, legacy core might be loaded
		if(method_exists('WPCACore', 'render_group_meta_box')) {
			WPCACore::render_group_meta_box($post,CAS_App::BASE_SCREEN.'-edit','section-conditions','default');
		}

	}

	/**
	 * Admin notice for Plugin Review
	 *
	 * @since  3.1
	 * @return void
	 */
	public function admin_notice_review() {
		$has_reviewed = get_user_option(CAS_App::META_PREFIX.'cas_review');
		$tour_taken = (int) $this->_tour_manager->get_user_option();
		if($has_reviewed === false && $tour_taken && (time() - $tour_taken) >= WEEK_IN_SECONDS) {
			$path = plugin_dir_path( __FILE__ ).'../view/';
			$view = WPCAView::make($path.'notice_review.php',array(
				'current_user' => wp_get_current_user()
			))->render();
		}
	}

	/**
	 * Meta box for options
	 * @return void
	 */
	public function meta_box_options($post) {

		$columns = array(
			'handle',
			'host',
			'merge_pos'
		);

		foreach ($columns as $key => $value) {

			$id = is_numeric($key) ? $value : $key;

			echo '<span class="'.$id.'"><strong>' . CAS_App::instance()->manager()->metadata()->get($id)->get_title() . '</strong>';
			echo '<p>';
			$values = explode(',', $value);
			foreach ($values as $val) {
				$this->_form_field($val);
			}
			echo '</p></span>';
		}

		$visibility = CAS_App::instance()->manager()->metadata()->get('visibility');

		echo '<span>';
		echo '<strong>'.__('Visibility','content-aware-sidebars').'</strong>';
		echo '<p><label for="visibility" class="screen-reader-text">'.__('Visibility','content-aware-sidebars').'</label>';

		echo '<div><select style="width:250px;" class="js-cas-visibility" multiple="multiple"  name="visibility[]" data-value="'.implode(",", $visibility->get_data($post->ID,true,false)).'"></select></div>';
		
		echo '</p></span>';

	}

	/**
	 * Set review flag for user
	 *
	 * @since  3.1
	 * @return void
	 */
	public function ajax_review_clicked() {
		$dismiss = isset($_POST['dismiss']) ? (int)$_POST['dismiss'] : 0;
		if(!$dismiss) {
			$dismiss = time();
		}

		echo json_encode(update_user_option(get_current_user_id(),CAS_App::META_PREFIX.'cas_review', $dismiss));
		die();
	}

	/**
	 * Create form field for metadata
	 * @global object $post
	 * @param  array $setting 
	 * @return void 
	 */
	private function _form_field($setting) {

		$setting = CAS_App::instance()->manager()->metadata()->get($setting);
		$current = $setting->get_data(get_the_ID(),true);

		switch ($setting->get_input_type()) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $setting->get_id() . '">' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $setting->get_id() . '[]" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $setting->get_id() . '" value="' . $current . '" />' . "\n";
				break;
		}
	}
		
	/**
	 * Save meta values for post
	 * @param  int $post_id 
	 * @return void 
	 */
	public function save_post($post_id,$post) {

		//Verify nonce, check_admin_referer dies on false
		//TODO: check other nonce instead
		if(!(isset($_POST[WPCACore::NONCE]) 
			&& wp_verify_nonce($_POST[WPCACore::NONCE], WPCACore::PREFIX.$post_id)))
			return;

		// Check permissions
		if (!current_user_can(CAS_App::CAPABILITY, $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Update metadata
		foreach (CAS_App::instance()->manager()->metadata()->get_all() as $field) {
			$single = $field->get_input_type()!="multi";
			//$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : '';
			$old = $field->get_data($post_id,false,$single);

			//TODO: package update/delete in meta class
			if($single) {
				$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : '';
				if ($new != '' && $new != $old) {
					$field->update($post_id,$new);
				} elseif ($new == '' && $old != '') {
					$field->delete($post_id,$old);
				}
			} else {
				$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : array();
				$old = array_flip($old);
				foreach ($new as $meta) {
					if(isset($old[$meta])) {
						unset($old[$meta]);
					} else {
						add_post_meta($post_id, CAS_App::META_PREFIX.$field->get_id(), $meta);
					}
				}
				foreach ($old as $meta => $v) {
					$field->delete($post_id,$meta);
				}
			}

		}
	}

	/**
	 * Add suffix when creating sidebar with existing name
	 * Does not stop duplicate titles on update
	 *
	 * @since  3.4.3
	 * @param  array  $insert_data
	 * @param  array  $data
	 * @return array
	 */
	public function add_duplicate_title_suffix($insert_data, $data) {
		if($data['post_type'] == CAS_App::TYPE_SIDEBAR && !$data['ID']) {
			$sidebars = CAS_App::instance()->manager()->sidebars;
			$sidebar_titles = array();
			foreach ($sidebars as $sidebar) {
				$sidebar_titles[$sidebar->post_title] = 1;
			}
			//if title exists, add a suffix
			$i = 0;
			$title = wp_unslash($insert_data['post_title']);
			$new_title = $title;
			while(isset($sidebar_titles[$new_title])) {
				$new_title = $title.' ('.++$i.')';
			}
			if($i) {
				$insert_data['post_title'] = wp_slash($new_title);
			}
		}
		return $insert_data;
	}

	/**
	 * Remove widget when its sidebar is removed
	 * @param  int $post_id 
	 * @return void
	 */
	public function remove_sidebar_widgets($post_id) {

		// Authenticate and only continue on sidebar post type
		if (!current_user_can(CAS_App::CAPABILITY) || get_post_type($post_id) != CAS_App::TYPE_SIDEBAR)
			return;

		$id = CAS_App::SIDEBAR_PREFIX . $post_id;

		//Get widgets
		$sidebars_widgets = wp_get_sidebars_widgets();

		// Check if sidebar exists in database
		if (!isset($sidebars_widgets[$id]))
			return;

		// Remove widgets settings from sidebar
		foreach ($sidebars_widgets[$id] as $widget_id) {
			$widget_type = preg_replace('/-[0-9]+$/', '', $widget_id);
			$widget_settings = get_option('widget_' . $widget_type);
			$widget_id = substr($widget_id, strpos($widget_id, '-') + 1);
			if ($widget_settings && isset($widget_settings[$widget_id])) {
				unset($widget_settings[$widget_id]);
				update_option('widget_' . $widget_type, $widget_settings);
			}
		}

		// Remove sidebar
		unset($sidebars_widgets[$id]);
		wp_set_sidebars_widgets($sidebars_widgets);
	}

	/**
	 * Get sidebar edit link
	 * TODO: Consider changing post type _edit_link instead
	 *
	 * @since  3.4
	 * @param  string  $link
	 * @param  int     $post_id
	 * @param  string  $context
	 * @return string
	 */
	public function get_edit_post_link($link, $post_id, $context) {
		$post = get_post($post_id);
		if($post->post_type == CAS_App::TYPE_SIDEBAR) {
			$sep = '&';
			if($context == 'display') {
				$sep = '&amp;';
			}
			$link = admin_url('admin.php?page=wpcas-edit'.$sep.'sidebar_id='.$post_id);
		}
		return $link;
	}

	/**
	 * Get sidebar delete link
	 * TODO: Consider changing post type _edit_link instead
	 *
	 * @since  3.4
	 * @param  string   $link
	 * @param  int      $post_id
	 * @param  boolean  $force_delete
	 * @return string
	 */
	public function get_delete_post_link($link, $post_id, $force_delete) {
		$post = get_post($post_id);
		if($post->post_type == CAS_App::TYPE_SIDEBAR) {

			$action = ( $force_delete || !EMPTY_TRASH_DAYS ) ? 'delete' : 'trash';

			$link = add_query_arg(
				'action',
				$action,
				admin_url('admin.php?page=wpcas-edit&sidebar_id='.$post_id)
			);
			$link = wp_nonce_url( $link, "$action-post_{$post_id}" );
		}
		return $link;
	}

	/**
	 * Add general scripts to admin screens
	 *
	 * @since 3.4.1
	 */
	public function add_general_scripts_styles() {
		wp_register_script('cas/admin/general', plugins_url('../js/general.js', __FILE__), array('jquery'), CAS_App::PLUGIN_VERSION, true);
		wp_enqueue_script('cas/admin/general');
	}

	/**
	 * Register and enqueue scripts styles
	 * for screen
	 *
	 * @since 3.4
	 */
	public function add_scripts_styles() {

		WPCACore::enqueue_scripts_styles('');

		wp_register_script('flatpickr', plugins_url('../js/flatpickr.min.js', __FILE__), array(), '3.0.6', false);

		wp_register_script('cas/admin/edit', plugins_url('../js/cas_admin.min.js', __FILE__), array('jquery','flatpickr'), CAS_App::PLUGIN_VERSION, false);
		
		wp_register_style('flatpickr', plugins_url('../css/flatpickr.dark.min.css', __FILE__), array(), '2.3.4');
		wp_register_style('cas/admin/style', plugins_url('../css/style.css', __FILE__), array('flatpickr'), CAS_App::PLUGIN_VERSION);

		$visibility = array();
		foreach (CAS_App::instance()->_manager->metadata()->get('visibility')->get_input_list() as $k => $v) {
			$visibility[] = array(
				'id'   => $k,
				'text' => $v
			);
		}

		if(cas_fs()->is_not_paying()) {
			$visibility[] = array(
				'id' => 'pro',
				'text' => __('User Roles available in Pro','content-aware-sidebars'),
				'disabled' => true
			);
		}

		global $wp_locale;

		wp_enqueue_script('cas/admin/edit');
		wp_localize_script( 'cas/admin/edit', 'CASAdmin', array(
			'allVisibility'  => __('All Users','content-aware-sidebars'),
			'visibility' => $visibility,
			'weekdays' => array(
				'shorthand' => array_values($wp_locale->weekday_abbrev),
				'longhand' => array_values($wp_locale->weekday)
			),
			'months' => array(
				'shorthand' => array_values($wp_locale->month_abbrev),
				'longhand' => array_values($wp_locale->month)
			),
			'weekStart' => get_option('start_of_week',0),
			'dateFormat' => __( 'F j, Y' ) //default long date
		));

		wp_enqueue_style('cas/admin/style');

		//badgeos compat
		//todo: check that developers respond with a fix soon
		wp_register_script('badgeos-select2', '');
		wp_register_style( 'badgeos-select2-css', '');

	}

}

//eol