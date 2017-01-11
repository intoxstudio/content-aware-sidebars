/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

(function($) {

	var cas_general = {

		init: function() {
			this.upgradeNoticeHandler();
			this.reviewNoticeHandler();
		},

		upgradeNoticeHandler: function() {
			$('.js-cas-pro-notice').on('click',function(e) {
				e.preventDefault();
				$('.js-cas-pro-read-more').attr('href',$(this).data('url'));
				$('.js-cas-pro-popup').trigger('click');
			});
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
			$notice.on("click","a, button", function(e) {
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

	$(document).ready(function(){
		cas_general.init();
	});

})(jQuery);