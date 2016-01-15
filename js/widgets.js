/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_widgets = {

		$sidebarContainer: $(".widget-liquid-right"),
		$widgetContainer: $('#available-widgets'),
		$sidebars:null,
		$widgets:null,

		/**
		 * Initiate
		 *
		 * @since  3.0
		 * @return {void}
		 */
		init: function() {

			this.$sidebars = $("div[id^='ca-sidebar']", this.$sidebarContainer);
			this.$widgets = $(".widget",this.$widgetContainer).get().reverse();

			this.addSidebarEditLink();
			this.addSidebarToolbar();
			this.addWidgetSearch();

		},
		/**
		 * Add search input for widgets
		 *
		 * @since 3.0
		 */
		addWidgetSearch: function() {
			$(".sidebar-description",this.$widgetContainer).prepend('<input type="search" class="js-cas-widget-filter cas-filter-widget" placeholder="'+CASAdmin.filterWidgets+'...">');
			this.searchWidgetListener();
		},
		/**
		 * Listen to widget filter
		 *
		 * @since  3.0
		 * @return {void}
		 */
		searchWidgetListener: function() {
			var that = this,
				filterTimer,
				cachedFilter = "";
			this.$widgetContainer.on('keyup', '.js-cas-widget-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$(that.$widgets).each(function(key,widget) {
							var $widget = $(widget);
							if ($widget.find(".widget-title :nth-child(1)").text().search(new RegExp(filter, "i")) < 0) {
								$widget.fadeOut();
							} else {
								//CSS dependent on order, so move to top
								$widget.prependTo($widget.parent());
								$widget.fadeIn().css("display","");
							}
						});
					}, 250);
				}
			});
		},
		/**
		 * Add toolbar for sidebars
		 *
		 * @since 3.0
		 */
		addSidebarToolbar: function() {

			var box = '<div class="wp-filter cas-filter-sidebar">'+
			'<a href="post-new.php?post_type=sidebar" class="button button-primary">'+CASAdmin.addNew+'</a>'+
			'<input type="search" class="js-cas-filter" placeholder="'+CASAdmin.filterSidebars+'...">'+
			'</div>';

			this.$sidebarContainer.prepend(box);
			this.searchSidebarListener();

		},
		/**
		 * Listen to sidebar filter
		 *
		 * @since  3.0
		 * @return {void}
		 */
		searchSidebarListener: function() {
			var that = this,
				filterTimer,
				cachedFilter = "";
			this.$sidebarContainer.on('keyup', '.js-cas-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$(".widgets-holder-wrap",that.$sidebarContainer).each(function(key,sidebar) {
							var $sidebar = $(sidebar);
							if ($sidebar.find(".sidebar-name :nth-child(2)").text().search(new RegExp(filter, "i")) < 0) {
								$sidebar.fadeOut();
							} else {
								$sidebar.fadeIn();
							}
						});
					}, 250);
				}
			});
		},
		/**
		 * Add better management for
		 * each sidebar
		 *
		 * @since 3.0
		 */
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

	$(document).ready(function(){ cas_widgets.init(); });

})(jQuery);
