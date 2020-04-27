/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

(function($) {
	"use strict";

	var cas_widgets = {

		$sidebarContainer: $(".widget-liquid-right"),
		$widgetContainer: $('#available-widgets'),

		/**
		 * Initiate
		 *
		 * @since  3.0
		 * @return {void}
		 */
		init: function() {

			this.openSidebarByURL();
			this.addSidebarToolbar();
			this.addWidgetSearch();
			this.enhancedWidgetManager();

		},

		/**
		 * Open widget area based on URL hash
		 *
		 * @since  3.7
		 * @return {void}
		 */
		openSidebarByURL: function() {
			if(window.location.hash) {
				var $sidebars = this.$sidebarContainer.find('.widgets-holder-wrap'),
					$openSidebar = $sidebars.has(window.location.hash);

				if($openSidebar.length) {
					//.sidebar-name-arrow is used in older wp versions
					$openSidebar.add($sidebars.first()).find('.handlediv,.sidebar-name-arrow').trigger('click');
				}
			}
		},

		/**
		 * Enable enhanced widget manager
		 *
		 * @since  3.6
		 * @return {void}
		 */
		enhancedWidgetManager: function() {
			if($('body').hasClass('cas-widget-manager')) {
				this.$widgetContainer.find('.widget').draggable('option','scroll',false);

				var that = this,
					$inactiveSidebars = $('#widgets-left .inactive-sidebar');
				$inactiveSidebars.toggle(this.$widgetContainer.hasClass('closed'));
				this.$widgetContainer.find('.sidebar-name').click(function(e) {
					$inactiveSidebars.toggle(that.$widgetContainer.hasClass('closed'));
				});
			}
		},

		/**
		 * Add search input for widgets
		 *
		 * @since 3.0
		 */
		addWidgetSearch: function() {
			var $widgets = $(".widget",this.$widgetContainer).get().reverse();
			$(".sidebar-description",this.$widgetContainer).prepend('<input type="search" class="js-cas-widget-filter cas-filter" placeholder="'+CASAdmin.filterWidgets+'...">');
			this.searchWidgetListener($widgets);
		},
		/**
		 * Listen to widget filter
		 *
		 * @since  3.0
		 * @return {void}
		 */
		searchWidgetListener: function($widgets) {
			var that = this,
				filterTimer,
				cachedFilter = "";
			this.$widgetContainer.on('input', '.js-cas-widget-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$($widgets).each(function(key,widget) {
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
			'<a href="admin.php?page=wpcas-edit" class="button button-primary">'+CASAdmin.addNew+'</a>'+
			'<div class="sidebars-toggle"><a href="#" title="'+CASAdmin.collapse+'" class="js-sidebars-toggle" data-toggle="0"><span class="dashicons dashicons-arrow-up-alt2"></span></a>'+
			'<a href="#" title="'+CASAdmin.expand+'" class="js-sidebars-toggle" data-toggle="1"><span class="dashicons dashicons-arrow-down-alt2"></span></a>'+
			'</div>'+
			'<input type="search" class="js-cas-filter cas-filter" placeholder="' + CASAdmin.filterSidebars + '...">' +
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
			this.$sidebarContainer.on('input', '.js-cas-filter',function(e) {
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
