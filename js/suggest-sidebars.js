/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2016 by Joachim Jensen
 */

(function($) {

	var cas_sidebars = {

		/**
		 * Add search suggest for sidebars
		 *
		 * @since 3.5
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
					allowClear:true,
					multiple: true,
					maximumSelectionSize: 1,
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
			// .on("select2-selecting",function(e) {
			// 	$elem.data("forceOpen",true);
			// })
			// .on("select2-close",function(e) {
			// 	if($elem.data("forceOpen")) {
			// 		e.preventDefault();
			// 		$elem.select2("open");
			// 		$elem.data("forceOpen",false);
			// 	}
			// });

		}

	};

	$(document).ready(function(){ cas_sidebars.suggestSidebars(); });

})(jQuery);
