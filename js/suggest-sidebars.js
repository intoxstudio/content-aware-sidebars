/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
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
				$(this).select2({
					theme:'wpca',
					minimumInputLength: 0,
					closeOnSelect: true,//does not work properly on false
					allowClear:false,
					//maximumSelectionLength: 0,
					width:"100%",
					//multiple:true,//defined in html for 3.5 compat
					//tags: CAS.canCreate, defined in html for 3.5 compat
					escapeMarkup:function (m) {return m;},
					createTag: function (params) {
						var term = $.trim(params.term);
						if (term === '') {
							return null;
						}
						return {
							id: '_'+term.replace(",","__"),
							text: term,
							new:true
						}
					},
					templateSelection: function(term) {
						return (term.new ? "<b>("+CAS.labelNew+")</b> " : "") + term.text;
					},
					templateResult: function(term) {
						return (term.new ? "<b>"+CAS.createNew+":</b> " : "") + term.text;
					},
					templateNoMatches: function(term) {
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
