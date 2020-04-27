/*!
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

(function($) {
	"use strict";

	var cas_general = {

		init: function() {
			this.toggleSidebarStatus();

			if(CAS.showPopups) {
				this.upgradeNoticeHandler();
				this.reviewNoticeHandler();
			}
		},

		/**
		 * Call backend on 1-click activation
		 *
		 * @since  3.3
		 * @return {void}
		 */
		toggleSidebarStatus: function () {
			$(".sidebar-status").on('change', 'input.sidebar-status-input', function (e) {
				var $this = $(this),
					status = $this.is(':checked');

				if ($this.hasClass('sidebar-status-future') && !confirm(CAS.enableConfirm)) {
					$this.attr('checked', !status);
					e.preventDefault();
					return false;;
				}

				$.post(
					ajaxurl,
					{
						'action': 'cas_sidebar_status',
						'sidebar_id': $this.val(),
						'token': $this.attr('data-nonce'),
						'status': status
					},
					function (response) {
						if (response.success) {
							//change title attr
							$this.next().attr('title', response.data.title);
							$this.removeClass('sidebar-status-future');
						} else {
							alert(response.data);
							$this.attr('checked', !status);
						}
					}
				);
			});
		},

		upgradeNoticeHandler: function() {
			$('.js-cas-pro-notice.button').attr('disabled', true);
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
			var $notice = $(".js-cas-notice-review");
			$notice.on("click","a, button", function(e) {
				var $this = $(this);
				$.ajax({
					url: ajaxurl,
					data:{
						'action': 'cas_dismiss_review_notice',
						'dismiss': $this.data("cas-rating") ? 1 : 0
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