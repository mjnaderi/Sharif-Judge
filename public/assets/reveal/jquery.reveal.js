/**
 * jQuery Reveal Plugin 1.0
 * www.ZURB.com
 * Copyright 2010, ZURB
 * Free to use under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * This code has been changed by Mohammad Javad Naderi <mjnaderi@gmail.com> for Sharif Judge
 */

(function ($) {

	// Extend and Execute
	$.fn.reveal = function (options) {
		var defaults = {
			animation: 'fadeAndPop', //fade, fadeAndPop, none
			animationspeed: 300, //how fast animtions are
			closeonbackgroundclick: true, //if you click background will modal close?
			dismissmodalclass: 'close-reveal-modal', //the class of a button or element that will close an open modal
			on_close_modal: function () {},
			on_finish_modal: function () {}
		};

		//Extend dem' options
		var options = $.extend({}, defaults, options);

		return this.each(function () {

			// Global Variables
			var modal = $(this),
				topMeasure = parseInt(modal.css('top')),
				topOffset = modal.height() + topMeasure,
				locked = false,
				modalBG = $('.reveal-modal-bg');

			// Create Modal BG
			if (modalBG.length == 0) {
				modalBG = $('<div class="reveal-modal-bg" />').insertAfter(modal);
			}

			//Entrance Animations
			modal.bind('reveal:open', function () {
				modalBG.unbind('click.modalEvent');
				$('.' + options.dismissmodalclass).unbind('click.modalEvent');
				if (!locked) {
					lockModal();
					if (options.animation == "fadeAndPop") {
						modal.css({'top': $(document).scrollTop() - topOffset, 'opacity': 0, 'visibility': 'visible'});
						modalBG.fadeIn(options.animationspeed);
						modal.animate({
							"top": $(document).scrollTop() + topMeasure + 'px',
							"opacity": 1
						}, options.animationspeed, unlockModal());
					}
					if (options.animation == "fade") {
						modal.css({'opacity': 0, 'visibility': 'visible', 'top': $(document).scrollTop() + topMeasure});
						modalBG.fadeIn(options.animationspeed);
						modal.animate({
							"opacity": 1
						}, options.animationspeed, unlockModal());
					}
					if (options.animation == "none") {
						modal.css({'visibility': 'visible', 'top': $(document).scrollTop() + topMeasure});
						modalBG.css({"display": "block"});
						unlockModal()
					}
				}
				modal.unbind('reveal:open');
			});

			//Closing Animation
			modal.bind('reveal:close', function () {
				if (!locked) {
					lockModal();
					if (options.animation == "fadeAndPop") {
						modalBG.fadeOut(options.animationspeed);
						modal.animate({
							"top": $(document).scrollTop() - topOffset + 'px',
							"opacity": 0
						}, options.animationspeed, function () {
							modal.css({'top': topMeasure, 'opacity': 1, 'visibility': 'hidden'});
							unlockModal();
							options.on_finish_modal();
						});
					}
					else if (options.animation == "fade") {
						modalBG.fadeOut(options.animationspeed);
						modal.animate({
							"opacity": 0
						}, options.animationspeed, function () {
							modal.css({'opacity': 1, 'visibility': 'hidden', 'top': topMeasure});
							unlockModal();
							options.on_finish_modal();
						});
					}
					else if (options.animation == "none") {
						modal.css({'visibility': 'hidden', 'top': topMeasure});
						modalBG.css({'display': 'none'});
						options.on_finish_modal();
					}
					options.on_close_modal();
				}
				modal.unbind('reveal:close');
			});

			/* Open and add Closing Listeners */
			//Open Modal Immediately
			modal.trigger('reveal:open')

			//Close Modal Listeners
			var closeButton = $('.' + options.dismissmodalclass).bind('click.modalEvent', function () {
				modal.trigger('reveal:close')
			});
			if (options.closeonbackgroundclick) {
				modalBG.css({"cursor": "pointer"})
				modalBG.bind('click.modalEvent', function () {
					modal.trigger('reveal:close')
				});
			}
			$('body').keyup(function (e) {
				if (e.which === 27) {
					modal.trigger('reveal:close');
				} // 27 = Escape key
			});

			 // Animations Locks
			function unlockModal() {
				locked = false;
			}
			function lockModal() {
				locked = true;
			}

		});//each call
	}//orbit plugin call
})(jQuery);
