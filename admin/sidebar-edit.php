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

final class CAS_Sidebar_Edit {

	private $_tour_manager;

	/**
	 * Constructor
	 *
	 * @since 3.1
	 */
	public function __construct() {

		$this->_tour_manager = new WP_Pointer_Tour(WPCACore::PREFIX.'cas_tour');

		add_action('delete_post',
			array($this,'remove_sidebar_widgets'));
		add_action('save_post_'.CAS_App::TYPE_SIDEBAR,
			array($this,'save_post'),10,2);
		add_action('add_meta_boxes_'.CAS_App::TYPE_SIDEBAR,
			array($this,'create_meta_boxes'));
		add_action('in_admin_header',
			array($this,'meta_box_whitelist'),99);

		add_action('wp_ajax_cas_dismiss_review_notice',
			array($this,'ajax_review_clicked'));

		add_action('wpca/meta_box/before',
			array($this,'show_description'));

		if ( cas_fs()->is_not_paying() )  {
			add_action('wpca/meta_box/after',
				array($this,'show_review_link'));
			add_filter('wpca/modules/list',
				array($this,'add_to_module_list'),99);
		}

		add_filter('post_updated_messages',
			array($this,'sidebar_updated_messages'));
		add_filter('bulk_post_updated_messages',
			array($this,'sidebar_updated_bulk_messages'), 10, 2 );
	}

	/**
	 * Show extra elements in content type list
	 *
	 * @since 3.3
	 * @param array  $list
	 */
	public function add_to_module_list($list) {
		if(get_post_type() == CAS_App::TYPE_SIDEBAR) {
			$list[''] = __('URLs (Available in Pro)','content-aware-sidebars');
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
			_e('Display this sidebar only on content that meets the following conditions:');
			echo '<p></p>';
		}
	}

	/**
	 * Render support description
	 *
	 * @since  3.3
	 * @param  string  $post_type
	 * @return void
	 */
	public function show_review_link($post_type) {
		//$url = cas_fs()->get_upgrade_url();
		$url = 'https://dev.institute/wordpress/sidebars-pro/pricing/?utm_source=plugin&utm_medium=referral&utm_content=upgrade-bottom&utm_campaign=cas';
		if($post_type == CAS_App::TYPE_SIDEBAR) {
			echo '<div style="overflow: hidden; padding: 2px 0px;">';
			echo '<div style="float:right;"><a href="'.esc_url($url).'" class="button button-cas-upgrade button-small" target="_blank">'.__('Upgrade to Pro','content-aware-sidebars').'</a></div>';
			echo '<div style="line-height:24px;">';
			echo '<span class="cas-heart">❤</span> ';
			printf(__('Like it? %1$sSupport the plugin with a %2$s Review%3$s','content-aware-sidebars'),'<b><a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?filter=5#postform">','5★','</a></b>');
			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Create update messages
	 * 
	 * @param  array  $messages 
	 * @return array           
	 */
	public function sidebar_updated_messages( $messages ) {
		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets','content-aware-sidebars'));
		$messages[CAS_App::TYPE_SIDEBAR] = array(
			0 => '',
			1 => __('Sidebar updated.','content-aware-sidebars').$manage_widgets,
			2 => '',
			3 => '',
			4 => __('Sidebar updated.','content-aware-sidebars'),
			5 => '',
			6 => __('Sidebar published.','content-aware-sidebars').$manage_widgets,
			7 => __('Sidebar saved.','content-aware-sidebars'),
			8 => __('Sidebar submitted.','content-aware-sidebars').$manage_widgets,
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>.','content-aware-sidebars'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime(get_the_ID()))).$manage_widgets,
			10 => __('Sidebar draft updated.','content-aware-sidebars'),
		);
		return $messages;
	}

	/**
	 * Create bulk update messages
	 *
	 * @since  3.0
	 * @param  array  $messages
	 * @param  array  $counts
	 * @return array
	 */
	public function sidebar_updated_bulk_messages( $messages, $counts ) {
		$manage_widgets = sprintf(' <a href="%1$s">%2$s</a>','widgets.php',__('Manage widgets','content-aware-sidebars'));
		$messages[CAS_App::TYPE_SIDEBAR] = array(
			'updated'   => _n( '%s sidebar updated.', '%s sidebars updated.', $counts['updated'] ).$manage_widgets,
			'locked'    => _n( '%s sidebar not updated, somebody is editing it.', '%s sidebars not updated, somebody is editing them.', $counts['locked'] ),
			'deleted'   => _n( '%s sidebar permanently deleted.', '%s sidebars permanently deleted.', $counts['deleted'] ),
			'trashed'   => _n( '%s sidebar moved to the Trash.', '%s sidebars moved to the Trash.', $counts['trashed'] ),
			'untrashed' => _n( '%s sidebar restored from the Trash.', '%s sidebars restored from the Trash.', $counts['untrashed'] ),
		);
		return $messages;
	}

	/**
	 * Remove unwanted meta boxes
	 * @return void 
	 */
	public function meta_box_whitelist() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		// Post type not set on all pages in WP3.1
		if(!(isset($screen->post_type) && $screen->post_type == CAS_App::TYPE_SIDEBAR && $screen->base == 'post'))
			return;

		// Names of whitelisted meta boxes
		$whitelist = array(
			'cas-plugin-links' => 'cas-plugin-links',
			'cas-upgrade-pro'   => 'cas-upgrade-pro',
			'cas-news'      => 'cas-news',
			'cas-support'   => 'cas-support',
			'cas-groups'    => 'cas-groups',
			'cas-rules'     => 'cas-rules',
			'cas-options'   => 'cas-options',
			'submitdiv'     => 'submitdiv',
			'revisionsdiv'  => 'revisionsdiv',
			'slugdiv'       => 'slugdiv',
		);

		// Loop through context (normal,advanced,side)
		foreach($wp_meta_boxes[CAS_App::TYPE_SIDEBAR] as $context_k => $context_v) {
			// Loop through priority (high,core,default,low)
			foreach($context_v as $priority_k => $priority_v) {
				// Loop through boxes
				foreach($priority_v as $box_k => $box_v) {
					// If box is not whitelisted, remove it
					if(!isset($whitelist[$box_k])) {
						$wp_meta_boxes[CAS_App::TYPE_SIDEBAR][$context_k][$priority_k][$box_k] = false;
						//unset($whitelist[$box_k]);
					}
				}
			}
		}
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
					wpautop(__( "Click on the input field and select the content you want.\n\nIf you can't find the right content in the list, type something to search.\n\n You can add several types of content to the same group, try e.g. \"All Posts\" and an Author to target all posts written by that author. Awesome!\n\nRemember to save the changes on each group.", 'content-aware-sidebars' ) )),
				'ref_id'    => '#cas-groups > ul',
				'position'  => array(
					'edge'      => 'top',
					'align'     => 'center'
				)
			),
			array(
				'content'   => sprintf( '<h3>%s</h3><p>%s</p>',
					'3. '.__( 'Options, options', 'content-aware-sidebars' ),
					wpautop(__( "Should the sidebar be displayed on singular pages and/or archives?\n\nShould it merge with another sidebar or replace it? Maybe you want to insert it manually in your content with a shortcode.\n\nSchedule the sidebar just like you do with posts and pages, or make it visible only for logged-in users.\n\n You are in control.", 'content-aware-sidebars' ) )),
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

		$boxes = array();

		$boxes[] = array(
			'id'       => 'submitdiv',
			'title'    => __('Publish'),
			'callback' => 'meta_box_submit',
			'context'  => 'side',
			'priority' => 'high'
		);

		if ( cas_fs()->is_not_paying() ) {

			// $boxes[] = array(
			// 	'id'       => 'cas-upgrade-pro',
			// 	'title'    => __('Content Aware Sidebars PRO', 'content-aware-sidebars'),
			// 	'callback' => 'meta_box_upgrade',
			// 	'context'  => 'side',
			// 	'priority' => 'low'
			// );

			$boxes[] = array(
				'id'       => 'cas-plugin-links',
				'title'    => __('Content Aware Sidebars', 'content-aware-sidebars'),
				'callback' => 'meta_box_support',
				'context'  => 'side',
				'priority' => 'default'
			);
		}
			//News
			// array(
			// 	'id'       => 'cas-news',
			// 	'title'    => __('Get a free Content Aware Sidebars Premium Bundle', 'content-aware-sidebars'),
			// 	'callback' => 'meta_box_news',
			// 	'context'  => 'normal',
			// 	'priority' => 'high'
			// ),
			//About
			// array(
			// 	'id'       => 'cas-support',
			// 	'title'    => __('Support the Author of Content Aware Sidebars', 'content-aware-sidebars'),
			// 	'callback' => 'meta_box_author_words',
			// 	'context'  => 'normal',
			// 	'priority' => 'high'
			// ),
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
			add_meta_box(
				$box['id'],
				$box['title'],
				array($this, $box['callback']),
				CAS_App::TYPE_SIDEBAR,
				$box['context'],
				$box['priority']
			);
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array( 
			'id'      => WPCACore::PREFIX.'help',
			'title'   => __('Condition Groups','content-aware-sidebars'),
			'content' => '<p>'.__('Each created condition group describe some specific content (conditions) that the current sidebar should be displayed with.','content-aware-sidebars').'</p>'.
				'<p>'.__('Content added to a condition group uses logical conjunction, while condition groups themselves use logical disjunction. '.
				'This means that content added to a group should be associated, as they are treated as such, and that the groups do not interfere with each other. Thus it is possible to have both extremely focused and at the same time distinct conditions.','content-aware-sidebars').'</p>',
		) );
		$screen->set_help_sidebar( '<h4>'.__('More Information').'</h4>'.
			'<p><a href="https://dev.institute/wordpress/sidebars-pro/faq/?utm_source=plugin&utm_medium=referral&utm_content=help-tab&utm_campaign=cas" target="_blank">'.__('FAQ','content-aware-sidebars').'</a></p>'.
			'<p><a href="http://wordpress.org/support/plugin/content-aware-sidebars" target="_blank">'.__('Forum Support','content-aware-sidebars').'</a></p>'
		);

		if ( cas_fs()->is_not_paying() )  {
			add_action( 'admin_notices', array($this,'admin_notice_review'));
		}
	}

	/**
	 * Admin notice for Plugin Review
	 *
	 * @since  3.1
	 * @return void
	 */
	public function admin_notice_review() {
		$has_reviewed = get_user_option(WPCACore::PREFIX.'cas_review');
		if($has_reviewed === false) {
			$tour_taken = $this->_tour_manager->get_user_option();
			if($tour_taken && (time() - $tour_taken) >= WEEK_IN_SECONDS*2) {
				echo '<div class="update-nag notice js-cas-notice-review">';
				echo '<p>'.__('You have used this plugin for some time now. I hope you like it!','content-aware-sidebars').'</p>';
				echo '<p>'.sprintf('Please spend 2 minutes to support it with a %sreview on WordPress.org%s. Thank you.',
				'<strong><a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?filter=5#postform">',
				'</a></strong>').'</p>';
				echo '<br><p>- '.__('Joachim Jensen, developer of Content Aware Sidebars','content-aware-sidebars').'</p>';
				echo '<p><a target="_blank" class="button-primary" href="https://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?filter=5#postform">'.__('Review Plugin','content-aware-sidebars').'</a> <button class="button-secondary">'.__("I don't like it",'content-aware-sidebars').'</button></p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Meta box for upgrade notice
	 *
	 * @since  3.2
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function meta_box_upgrade($post) {
		?>
<ul>
  <li><span class="dashicons dashicons-yes"></span> <?php _e('Priority Email Support','content-aware-sidebars'); ?></li>
  <li><span class="dashicons dashicons-yes"></span> <?php _e('Sidebars on URLs + wildcards','content-aware-sidebars'); ?></li>
  <li><span class="dashicons dashicons-yes"></span> <?php _e('Widget Revisions','content-aware-sidebars'); ?></li>
  <li><span class="dashicons dashicons-yes"></span> <?php _e('and more...','content-aware-sidebars'); ?></li>
</ul>
<div style="text-align: center; margin: 0px auto;">
	<a href="<?php echo esc_url(cas_fs()->get_upgrade_url()); ?>" class="button button-large"><?php _e('Upgrade Now!','content-aware-sidebars'); ?></a>
</div>
		<?php
	}

	/**
	 * Meta box for news
	 * @version 2.5
	 * @return  void
	 */
	public function meta_box_news() {
?>
		<div style="overflow:hidden;">
			<div style="float:left;width:40%;overflow:hidden">
				<a target="_blank" href="https://www.transifex.com/projects/p/content-aware-sidebars/" class="button button-primary" style="width:100%;text-align:center;margin-bottom:10px;"><?php _e('Translate Now','content-aware-sidebars'); ?></a>
			</div>

		</div>
<?php
	}

	/**
	 * Meta box for options
	 * @return void
	 */
	public function meta_box_options($post) {

		$columns = array(
			'handle' => 'handle,host',
			'merge_pos',
			'exposure'
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
		echo '<strong>'.__('Visibility').'</strong>';
		echo '<p><label for="visibility" class="screen-reader-text">'.__('Visibility').'</label>';

		echo '<div><select style="width:250px;" class="js-cas-visibility" multiple="multiple"  name="visibility[]" data-value="'.implode(",", $visibility->get_data($post->ID,true,false)).'"></select></div>';
		
		echo '</p></span>';

		echo '<span>';
		echo '<strong>'.__('Order').'</strong>';
		echo '<p><label for="menu_order" class="screen-reader-text">'.__('Order').'</label>';
		echo '<input type="number" value="'.$post->menu_order.'" id="menu_order" size="4" name="menu_order"></p></span>';
	}

	/**
	 * Meta box for info and support
	 *
	 * @since  3.0
	 * @return void 
	 */
	public function meta_box_support() {
		$locale = get_locale();
?>
			<div style="overflow:hidden;">
				<ul>
					<li><a href="<?php echo esc_url(cas_fs()->get_upgrade_url()); ?>"><?php _e('Priority Email Support','content-aware-sidebars'); ?></a></li>
					<li><a href="https://wordpress.org/support/plugin/content-aware-sidebars/" target="_blank"><?php _e('Forum Support','content-aware-sidebars'); ?></a></li>
<?php if($locale != "en_US") : ?>
					<li><a href="https://www.transifex.com/projects/p/content-aware-sidebars/" target="_blank"><?php _e('Translate the plugin into your language','content-aware-sidebars'); ?></a></li>
<?php endif; ?>
					<li><a href="https://dev.institute/wordpress/sidebars-pro/faq/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=cas" target="_blank"><?php _e('Read the FAQ','content-aware-sidebars'); ?></a></li>
				</ul>
			</div>
		<?php
	}

	/**
	 * Meta box for author words
	 * @return void 
	 */
	public function meta_box_author_words() {
?>
			<div style="overflow:hidden;">
				<div style="float:left;width:40%;border-left:#ebebeb 1px solid;border-right:#ebebeb 1px solid;box-sizing:border-box;-moz-box-sizing:border-box;">
					<p><strong><?php _e('Or you could:','content-aware-sidebars'); ?></strong></p>
					<ul>
						<li><a href="http://wordpress.org/extend/plugins/content-aware-sidebars/" target="_blank"><?php _e('Translate the plugin into your language','content-aware-sidebars'); ?></a></li>
					</ul>
				</div>
				<div style="float:left;width:20%;">
					<p><a href="https://twitter.com/intoxstudio" class="twitter-follow-button" data-show-count="false">Follow @intoxstudio</a>
						<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
					<p>
						<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fintoxstudio&amp;width=450&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=false&amp;send=false&amp;appId=436031373100972" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:21px;" allowTransparency="true"></iframe>
					</p>
				</div>
			</div>
		<?php
	}
	
	/**
	 * Meta box to submit form fields
	 * Overwrites default meta box
	 *
	 * @see function post_submit_meta_box
	 *
	 * @since  3.2
	 * @param  WP_Post  $post
	 * @param  array    $args
	 * @return void
	 */
	public function meta_box_submit( $post, $args = array() ) {
		global $action;

		$post_type_object = get_post_type_object($post->post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
	?>
	<div class="submitbox" id="submitpost">

	<div id="minor-publishing">

	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
	<div style="display:none;">
	<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
	</div>

	<div id="minor-publishing-actions">
	<div id="save-action">
	<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status ) { ?>
	<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft'); ?>" class="button" />
	<span class="spinner"></span>
	<?php } elseif ( 'pending' == $post->post_status && $can_publish ) { ?>
	<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save as Pending'); ?>" class="button" />
	<span class="spinner"></span>
	<?php } ?>
	</div>
	<?php
	?>
	<div class="clear"></div>
	</div><!-- #minor-publishing-actions -->

	<div id="misc-publishing-actions">

	<div class="misc-pub-section misc-pub-post-status"><label for="post_status"><?php _e('Status:') ?></label>
	<span id="post-status-display">
	<?php
	switch ( $post->post_status ) {
		case 'private':
		case 'publish':
			_e('Published');
			break;
		case 'future':
			_e('Scheduled');
			break;
		case 'pending':
			_e('Pending Review');
			break;
		case 'draft':
		case 'auto-draft':
			_e('Draft');
			break;
	}
	?>
	</span>
	<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) { ?>
	<a href="#post_status" class="edit-post-status hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit status' ); ?></span></a>

	<div id="post-status-select" class="hide-if-js">
	<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ('auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
	<select name='post_status' id='post_status'>
	<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status ) : ?>
	<option<?php selected( $post->post_status, $post->post_status ); ?> value='publish'><?php _e('Published') ?></option>
	<?php elseif ( 'future' == $post->post_status ) : ?>
	<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e('Scheduled') ?></option>
	<?php endif; ?>
	<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e('Pending Review') ?></option>
	<?php if ( 'auto-draft' == $post->post_status ) : ?>
	<option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e('Draft') ?></option>
	<?php else : ?>
	<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e('Draft') ?></option>
	<?php endif; ?>
	</select>
	 <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e('OK'); ?></a>
	 <a href="#post_status" class="cancel-post-status hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
	</div>

	<?php } ?>
	</div><!-- .misc-pub-section -->

	<?php
	/* translators: Publish box date format, see http://php.net/date */
	$datef = __( 'M j, Y @ H:i' );
	if ( 0 != $post->ID ) {
		if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
			$stamp = __('Scheduled for: <b>%1$s</b>');
		} elseif ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
			$stamp = __('Published on: <b>%1$s</b>');
		} elseif ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
			$stamp = __('Publish <b>immediately</b>');
		} elseif ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
			$stamp = __('Schedule for: <b>%1$s</b>');
		} else { // draft, 1 or more saves, date specified
			$stamp = __('Publish on: <b>%1$s</b>');
		}
		$date = date_i18n( $datef, strtotime( $post->post_date ) );
	} else { // draft (no saves, and thus no date specified)
		$stamp = __('Publish <b>immediately</b>');
		$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
	}


	if ( post_type_supports($post->post_type, 'revisions') && 'auto-draft' != $post->post_status ) {
		$revisions = wp_get_post_revisions( $post->ID );
		$revision_count = count($revisions);

		// We should aim to show the revisions metabox only when there are revisions.
		if ( $revision_count > 1 ) {
			reset( $revisions ); // Reset pointer for key()
			$revision_id = key( $revisions );
 ?>
			<div class="misc-pub-section misc-pub-revisions">
				<?php printf( __( 'Widget Revisions: %s' ), '<b>' . number_format_i18n( $revision_count ) . '</b>' ); ?>
				<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $revision_id ) ); ?>"><span aria-hidden="true"><?php _ex( 'Browse', 'revisions' ); ?></span> <span class="screen-reader-text"><?php _e( 'Browse revisions' ); ?></span></a>
			</div>
			<?php
		}
	}
	elseif ( $post->post_status != 'auto-draft' && cas_fs()->is_not_paying() ) {
?>
		<div class="misc-pub-section misc-pub-revisions">
			<?php printf( __( 'Widget Revisions: %s' ), '<b><a href="'.esc_url(cas_fs()->get_upgrade_url()).'">'.__( 'Enable').'</a></b>' ); ?>
		</div>
		<?php
	}

	if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="timestamp">
		<?php printf($stamp, $date); ?></span>
		<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
		<fieldset id="timestampdiv" class="hide-if-js">
		<legend class="screen-reader-text"><?php _e( 'Date and time' ); ?></legend>
		<?php touch_time( ( $action === 'edit' ), 1 ); ?>
		</fieldset>
	</div><?php // /misc-pub-section ?>
	<?php endif; ?>

	</div>
	<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
	<div id="delete-action">
	<?php
	if ( current_user_can( "delete_post", $post->ID ) ) {
		if ( !EMPTY_TRASH_DAYS )
			$delete_text = __('Delete Permanently');
		else
			$delete_text = __('Move to Trash');
		?>
	<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
	} ?>
	</div>

	<div id="publishing-action">
	<span class="spinner"></span>
	<?php
	if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
		if ( $can_publish ) :
			if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule') ?>" />
			<?php submit_button( __( 'Schedule' ), 'primary button-large', 'publish', false ); ?>
	<?php	else : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
			<?php submit_button( __( 'Publish' ), 'primary button-large', 'publish', false ); ?>
	<?php	endif;
		else : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
			<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false ); ?>
	<?php
		endif;
	} else { ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
			<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update' ) ?>" />
	<?php
	} ?>
	</div>
	<div class="clear"></div>
	</div>
	</div>

	<?php
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

		echo json_encode(update_user_option(get_current_user_id(),WPCACore::PREFIX.'cas_review', $dismiss));
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

		// Save button pressed
		if (!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;

		//Verify nonce, check_admin_referer dies on false
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
						add_post_meta($post_id, WPCACore::PREFIX.$field->get_id(), $meta);
					}
				}
				foreach ($old as $meta => $v) {
					$field->delete($post_id,$meta);
				}
			}

		}
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

}

//eol