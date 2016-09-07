/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
 */

(function($) {

	var cas_widgets = {

		$sidebarContainer: $(".widget-liquid-right"),
		$widgetContainer: $('#available-widgets'),
		$widgets:null,

		/**
		 * Initiate
		 *
		 * @since  3.0
		 * @return {void}
		 */
		init: function() {

			this.addSidebarToolbar();
			this.addWidgetSearch();

		},
		/**
		 * Add search input for widgets
		 *
		 * @since 3.0
		 */
		addWidgetSearch: function() {
			this.$widgets = $(".widget",this.$widgetContainer).get().reverse();
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
			'<a href="#" class="js-sidebars-toggle sidebars-toggle" data-toggle="0">'+CASAdmin.collapse+'</a>'+
			'<a href="#" class="js-sidebars-toggle sidebars-toggle" data-toggle="1">'+CASAdmin.expand+'</a>'+
			'</div>';

			this.$sidebarContainer.prepend(box);
			this.searchSidebarListener();
			this.addSidebarToggle();

		},

		/**
		 * Toggle all sidebars
		 *
		 * @since 3.3
		 */
		addSidebarToggle: function() {
			var $document = $(document),
				$sidebars = this.$sidebarContainer.find('.widgets-holder-wrap');
			$('body').on('click','.js-sidebars-toggle', function(e) {
				e.preventDefault();
				
				var open = !!$(this).data("toggle");

				$sidebars
				.toggleClass('closed',!open);
				if(open) {
					$sidebars.children('.widgets-sortables').sortable('refresh');
				}

				$document.triggerHandler('wp-pin-menu');
				
				//$sidebars.click();
			})
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
		}

	};

	$(document).ready(function(){
		cas_widgets.init();
	});

})(jQuery);
