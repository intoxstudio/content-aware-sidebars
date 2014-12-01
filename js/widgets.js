/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_admin = {

		$sidebars: $(":not(.inactive) > div[id^='ca-sidebar']"),

		init: function() {

			this.addSidebarEditLink();

		},

		addSidebarEditLink: function() {

			this.$sidebars.each( function(e) {
				$this = $(this);
				var id = $this.attr('id').replace('ca-sidebar-','');
				var $sidebar = $this.closest('.widgets-holder-wrap');

				$sidebar.addClass('content-aware-sidebar');

				$this.find('.sidebar-description').append('<div class="cas-settings"><a title="'+CASAdmin.edit+'" class="cas-sidebar-link" href="post.php?post='+id+'&action=edit"><i class="dashicons dashicons-admin-generic"></i> '+CASAdmin.edit+'</a></div>');

			});
		}

	};

	$(document).ready(function(){ cas_admin.init(); });

})(jQuery);
