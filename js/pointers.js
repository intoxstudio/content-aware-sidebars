/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_pointers = {

		$overlay: $('<div style="background:rgba(0,0,0,0.4);z-index:1000;height:100%;position:fixed;width:100%;"></div>'),
		$pointers: [],
		currentPointer: 0,

		init: function() {
			this.createPointers();
		},

		pointerSettings: function(settings) {
			var pointerSettings = {
				buttons: function (event, t) {
					var $closeButton;
					if(settings.dismiss !== false || typeof settings.dismiss == "string") {
						$closeButton = $('<a style="margin:0 5px;" class="button-secondary">' + (typeof settings.dismiss == "string" ? settings.dismiss : CASPointers.close) + '</a>');
						$closeButton.bind('click.pointer', function (e) {
							e.preventDefault();
							cas_pointers.currentPointer = CASPointers.pointers.length-1;
							t.element.pointer('close');
						});
					}
					return $closeButton;
				},
				close: this.finishTour
			};
			return $.extend(settings,pointerSettings);
		},

		finishTour: function(e,t) {
			if(cas_pointers.currentPointer == CASPointers.pointers.length-1) {
				cas_pointers.$overlay.remove();
				$.ajax({
					url: ajaxurl,
					data:{
						'action': 'cas_finish_tour',
						//'nonce': cas_admin.nonce,
					},
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
					},
					error: function(xhr, desc, e) {
						console.log(xhr.responseText);
					}
				});
			}
		},

		createPointers: function() {
			var i = 0, ilen = CASPointers.pointers.length;
			for(i; i < ilen;i++) {
				CASPointers.pointers[i] = this.pointerSettings(CASPointers.pointers[i]);
				var $widget = $(CASPointers.pointers[i].ref_id);
				$widget.pointer(CASPointers.pointers[i]);
				this.$pointers.push($widget);
			}
			this.$overlay.prependTo('body');
			this.openPointer();
		},

		openPointer: function() {

			if(this.currentPointer >= CASPointers.pointers.length) return;

			this.finishTour();

			var i = this.currentPointer,
				$widget = this.$pointers[i],
				$nextButton;
			
			$widget.css("z-index",1001);
			$widget.pointer('open');
			if(CASPointers.pointers[i].next !== false || typeof CASPointers.pointers[i].next == "string") {
				$nextButton = $('<a style="margin:0 5px;" class="button-primary">' + (typeof CASPointers.pointers[i].next == "string" ? CASPointers.pointers[i].next : CASPointers.next) + '</a>');
				$widget.pointer('widget').find('.wp-pointer-buttons').append($nextButton);
				$nextButton.bind('click.pointer', this.continueTour);
			}
			$('html, body').animate({
				scrollTop: $widget.offset().top-50
			}, 1000);
		},

		continueTour: function(e) {
			e.preventDefault();
			cas_pointers.$pointers[cas_pointers.currentPointer].css("z-index","auto");
			cas_pointers.$pointers[cas_pointers.currentPointer].pointer('close');
			cas_pointers.currentPointer++;
			cas_pointers.openPointer();
		}

	};

	$(window).bind('load.wp-pointers', cas_pointers.init());

})(jQuery);
