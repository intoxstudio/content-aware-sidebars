/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_admin = {

		$container: $(".widget-liquid-right"),
		$sidebars: $("div[id^='ca-sidebar']", this.$container),

		init: function() {

			this.addSidebarEditLink();
			this.addFilterBox();

		},

		addFilterBox: function() {

			var box = '<div class="wp-filter" style="margin: 10px 0px; vertical-align: middle;">'+
			'<a href="post-new.php?post_type=sidebar" class="button button-primary" style="margin: 12px 0 11px">'+CASAdmin.addNew+'</a>'+
  '<input type="search" class="js-cas-filter" placeholder="'+CASAdmin.filter+'..." style="margin: 12px 0 11px;float: right;">'+
'</div>';

			this.$container.prepend(box);
			this.filterListener();

		},

		filterListener: function() {
			var that = this, filterTimer, cachedFilter = "";
			this.$container.on('keyup', '.js-cas-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$(".widgets-holder-wrap",that.$container).each(function(key,sidebar) {
							var $sidebar = $(sidebar);
							if ($sidebar.find("h3").first().text().search(new RegExp(filter, "i")) < 0) {
								$sidebar.fadeOut();
							} else {
								$sidebar.fadeIn();
							}
						});
					}, 250);
				}
			});
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
