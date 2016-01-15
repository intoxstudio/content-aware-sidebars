/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_options = {

		sidebarID: $('#current_sidebar').val(),

		init: function() {

			this.addHandleListener();
			this.reviewNoticeHandler();

		},

		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 * 
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

		/**
		 * Handle clicks on review notice
		 * Sends dismiss event to backend
		 *
		 * @since  3.1
		 * @return {void}
		 */
		reviewNoticeHandler: function() {
			$notice = $(".js-cas-notice-review");
			$("#wpbody-content").on("click","a, button", function(e) {
				$this = $(this);
				$.ajax({
					url: ajaxurl,
					data:{
						'action': 'cas_dismiss_review_notice',
						'dismiss': $this.attr("href") ? 1 : 0
					},
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
						$notice.fadeOut(400,function() {
							$notice.remove();
						});
					},
					error: function(xhr, desc, e) {
						console.log(xhr.responseText);
					}
				});
			});
		}
	};

	$(document).ready(function(){ cas_options.init(); });

})(jQuery);
