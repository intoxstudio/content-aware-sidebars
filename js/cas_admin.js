/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

(function($) {
	"use strict";

	var cas_options = {

		current_section: 0,
		sections: [],

		init: function() {
			this.tabController();
			this.actionOptionHandler();
			this.suggestVisibility();
			this.initSidebarActivation();

			$('.js-cas-color-field').wpColorPicker();

			$('.js-cas-html').on('change',function(e) {
				var $this = $(this);
				$($this.data('target')).attr('disabled',!$this.is(':checked'));
			}).trigger('change');
		},

		initSidebarActivation: function() {
			flatpickr.l10ns.default.weekdays = CASAdmin.weekdays;
			flatpickr.l10ns.default.months = CASAdmin.months;
			flatpickr.l10ns.default.firstDayOfWeek = CASAdmin.weekStart;

			var showFulltime = CASAdmin.timeFormat.toLowerCase().indexOf('a') === -1;

			var activate = flatpickr('.js-cas-activation',{
				wrap: true,
				clickOpens: true,
				enableTime: true,
				time_24hr: showFulltime,
				allowInput: true,
				enableSeconds: true,
				//altInput: true,
				//altFormat: CASAdmin.dateFormat + ' @ H:i:S',
				onChange: function(dateObj, dateStr, instance) {
					console.log("activate");
					if(dateStr || deactivate.config.minDate) {
						deactivate.set("minDate", dateStr ? new Date(dateObj).fp_incr(1) : null);
					}
					if(dateStr) {
						$toggle.prop('checked',false);
					} else if(!$toggle.is(':checked')) {
						deactivate.clear();
					}
				}
			}),
			deactivate = flatpickr('.js-cas-expiry',{
				wrap: true,
				clickOpens: true,
				enableTime: true,
				time_24hr: showFulltime,
				allowInput: true,
				enableSeconds: true,
				//altInput: true,
				//altFormat: CASAdmin.dateFormat + ' @ H:i:S',
				onChange: function(dateObj, dateStr, instance) {
					console.log("deactivate");
					if(dateStr || activate.config.maxDate) {
						activate.set("maxDate", dateStr ? new Date(dateObj).fp_incr(-1) : null);
					}
				}
			}),
			$toggle = $('.js-cas-status');

			$toggle.on('change',function(e) {
				if($(this).is(':checked')) {
					activate.clear();
				} else if(!activate.selectedDates.length) {
					deactivate.clear();
				}
			});
		},

		/**
		 * Initiate tabs dynamically
		 *
		 * @since  3.4
		 * @return {void}
		 */
		initTabSections: function() {
			$(".js-cas-tabs").find(".nav-tab").each(function() {
				var start = this.href.lastIndexOf("#");
				if(start >= 0) {
					var section = this.href.substr(start);
					cas_options.sections.push(section);
					$(section).hide();
				}
			});
		},

		/**
		 * Manage tab clicks
		 *
		 * @since  3.4
		 * @return {void}
		 */
		tabController: function() {
			this.initTabSections();
			this.setCurrentSection(window.location.hash);
			$("#poststuff")
			.on("click",".js-nav-link",function(e) {
				cas_options.setCurrentSection(this.href);
			});
		},

		/**
		 * Find section index based on
		 * hash in a URL string
		 *
		 * @since  3.4
		 * @param  {string} url
		 * @return {int}
		 */
		findSectionByURL: function(url) {
			var section = this.sections.indexOf(url.substring(url.lastIndexOf("#")));
			return section >= 0 ? section : null;
		},

		/**
		 * Set and display current section and tab
		 * hide previous current section
		 *
		 * @since 3.4
		 * @param {string} url
		 */
		setCurrentSection: function(url) {
			var section = this.findSectionByURL(url) || 0,
				$tabs = $(".js-cas-tabs").find(".nav-tab");
			if($tabs.eq(section).is(":visible")) {
				$(this.sections[this.current_section]).hide();
				$tabs.eq(this.current_section).removeClass("nav-tab-active");
				this.current_section = section;
				$(this.sections[this.current_section]).show();
				$tabs.eq(this.current_section).addClass("nav-tab-active");

				$('#_cas_section').val('#top'+this.sections[this.current_section]);
			}
		},

		/**
		 * Selection of handle will control the
		 * accessibility of options dependent on its value
		 *
		 * @since  3.7
		 * @return {void}
		 */
		actionOptionHandler: function() {
			var $options = $('#cas-options'),
				$actions = $options.find('.js-cas-action');
			$options.on('change','.js-cas-handle',function(){
				var action = $(this);

				// $actions.each(function(i,ui) {
				// 	var $this = $(this),
				// 	show = $this.hasClass('js-cas-action-'+action.val());
				// 	$this.toggle(show).find('input,select').attr('disabled',!show);
				// });

				var $show = $actions.filter('.js-cas-action-'+action.val());
				$actions.not($show).hide().find('input,select').attr('disabled',true);
				$show.fadeIn('fast').find('input,select').attr('disabled',false);

			});
			$options.find('.js-cas-handle').trigger('change');
		},

		suggestVisibility: function() {
			var $elem = $('.js-cas-visibility');
			$elem.select2({
				theme:'wpca',
				placeholder: CASAdmin.allVisibility,
				minimumInputLength: 0,
				closeOnSelect: true,//does not work properly on false
				allowClear:false,
				//multiple: true,
				//width:"resolve",
				nextSearchTerm: function(selectedObject, currentSearchTerm) {
					return currentSearchTerm;
				},
				data: CASAdmin.visibility
			})
			.on("select2:selecting",function(e) {
				$elem.data("forceOpen",true);
			})
			.on("select2:close",function(e) {
				if($elem.data("forceOpen")) {
					e.preventDefault();
					$elem.select2("open");
					$elem.data("forceOpen",false);
				}
			});
			//select3.5 compat for setting value by id
			if($elem.data('value')) {
				$elem.val($elem.data('value').toString().split(',')).trigger('change');
			}
		}
	};

	$(document).ready(function(){
		cas_options.init();
	});

})(jQuery);