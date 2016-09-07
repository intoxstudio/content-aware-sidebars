/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
 */

(function($) {

	var cas_sidebars = {

		init: function() {
			this.suggestSidebars();
			this.toggleSidebarInputs();
		},

		/**
		 * Toggle more sidebar inputs
		 *
		 * @since  3.3.1
		 * @return {void}
		 */
		toggleSidebarInputs: function() {
			$('.js-cas-more').click(function(e) {
				e.preventDefault();
				var $this = $(this),
					$toggle = $($this.data('toggle')),
					$icon = $this.children(":first");
				if($icon.hasClass('dashicons-arrow-down-alt2')) {
					$icon
					.addClass('dashicons-arrow-up-alt2')
					.removeClass('dashicons-arrow-down-alt2');
					$toggle.slideDown();
				} else {
					$icon
					.addClass('dashicons-arrow-down-alt2')
					.removeClass('dashicons-arrow-up-alt2');
					$toggle.slideUp();
				}
			});
		},

		/**
		 * Add search suggest for sidebars
		 *
		 * @since 3.3
		 */
		suggestSidebars: function() {
			var $elem = $('.js-cas-sidebars');
			$elem.each(function() {
				$this = $(this);
				$this.select2({
					containerCssClass:'cas-select2',
					dropdownCssClass: 'cas-select2',
					minimumInputLength: 0,
					closeOnSelect: true,//does not work properly on false
					allowClear:false,
					multiple: true,
					//maximumSelectionSize: 1,
					data:$this.data('sidebars'),
					width:"100%",
					//tokenSeparators: ['|'],
					// nextSearchTerm: function(selectedObject, currentSearchTerm) {
					// 	return currentSearchTerm;
					// },
					createSearchChoice:function(term, data) {
						if (CAS.canCreate && term && $(data).filter(function() {
						  return this.text.localeCompare(term) === 0;
						}).length === 0) {
						  return {
							id: '_'+term.replace(",","_"),
							text: term
						  };
						}
						return null;
					},
					formatSelection: function(term) {
						return (term.id > 0 ? "" : "<b>("+CAS.labelNew+")</b> ") + term.text;
					},
					formatResult: function(term) {
						return (term.id > 0 ? "" : "<b>"+CAS.createNew+":</b> ") + term.text;
					},
					formatNoMatches: function(term) {
						return CAS.notFound;
					}
				});
			});
		}
	};

	$(document).ready(function(){
		cas_sidebars.init();
	});

})(jQuery);
