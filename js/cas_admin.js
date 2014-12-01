/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_options = {

		sidebarID: $('#current_sidebar').val(),

		init: function() {

			this.addHandleListener();

		},

		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.1
		 */
		addHandleListener: function() {
			var host = $("select[name='host']");
			var code = $('<p>Shortcode:</p><code>[ca-sidebar id='+this.sidebarID+']</code>'+
				'<p>Template Tag:</p><code>display_ca_sidebar();</code>');
			var merge_pos = $('span.merge-pos');
			host.parent().append(code);
			$("select[name='handle']").change(function(){
				var handle = $(this);
				host.attr("disabled", handle.val() == 2);
				if(handle.val() == 2) {
					host.hide();
					code.show();
				} else {
					host.show();
					code.hide();
				}
				if(handle.val() == 3) {
					merge_pos.hide();
				} else {
					merge_pos.show();
				}
			}).change(); //fire change event on page load
		},

	};

	$(document).ready(function(){ cas_options.init(); });

})(jQuery);
