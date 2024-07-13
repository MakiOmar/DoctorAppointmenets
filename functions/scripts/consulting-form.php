<?php
/**
 * Consulting form scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$( '.consulting-forms-tab' ).on(
					'click',
					function () {
						$('form.consulting-form').hide();
						$('#' + $(this).data('target')).show();
					}
				);

				$('.consulting-form').each(
					function() {
						var thisForm = $( this );
						if ( $('.active-day').length < 1 ) {
							setInterval(
								function() {
									var label = thisForm.find( '.anony-day-radio:first' ).find('label');
									if ( ! label.hasClass( 'active-day' ) ) {
										label.click();
									}
								},
								800
							)
						}
					}
				);

				$(".consulting-form").on(
					'submit',
					function(event){
						if ( $( this ).find('input[name="selected-hour"]:checked').length === 0 || $( this ).find('input[name="current-month-day"]:checked').length === 0  ) {
							event.preventDefault();
							alert('فضلاً تأكد من أنك قمت بتحديد اليوم والساعة');
						}
					}
				);
			} );
		</script>
		<?php
	}
);

/**
 * Content slider
 */
add_action(
	'wp_footer',
	function () {
		?>
	<script>
		jQuery( document ).ready(
			function ($) {
				function snksTouchedInside( event, className ) {
					var targetElement     = event.target;
					var isInsideContainer = false;
					while (targetElement) {
						if (targetElement.classList.contains( className )) {
						isInsideContainer = true;
						break;
						}
						targetElement = targetElement.parentElement;
					}

					return isInsideContainer
				};
				if ( $('.anony-content-slider').length < 1 ) {
					return;
				}
				var slideWidth = $('.anony-content-slide').outerWidth();
				var slider     = $('.anony-content-slider');
				var contentSliderInterval;

				var infiniteLoop = true;
				var totalSlidesCount = $('.anony-content-slide').length;
				var offScreenSlides = 0;
				var margins = 0;
				
				if ( ! infiniteLoop ) {
					if ( totalSlidesCount > 7 ) {
						offScreenSlides = initialOffScreenCount = totalSlidesCount - 7;
					}
					if ( offScreenSlides == 0 ) {
						$('.anony-content-slider-next').hide();
						$('.anony-content-slider-prev').hide();
					}
				}
				$('.anony-content-slide').each( function() {
					margins = margins + parseFloat( $(this).css("marginRight").replace('px', '' ) ) + parseFloat( $(this).css("marginLeft").replace('px', '' ) );
				} );
				var itemsLength = $('.anony-content-slide').length;

				// Adjust the slider width.
				var sliderWidth = slideWidth * itemsLength + margins;
				slider.width(sliderWidth);
				// Set initial position.
				<?php if ( ! is_rtl() ) { ?>
				var initialPosition = -slideWidth;
				<?php } else { ?>
					var initialPosition = slideWidth;
				<?php } ?>
				// Slide to the next slide.
				$('.consulting-form .anony-content-slider-control').on('click','.anony-content-slider-next', function(e) {
					e.preventDefault();
					if ( offScreenSlides >= 0 ) {
						offScreenSlides = offScreenSlides - 1;
					}
					if ( offScreenSlides <= -1 ) {
						offScreenSlides = 0;
						return;
					}
					var $currentSlide = $('.anony-content-slide:first');
					var width = $currentSlide.outerWidth();

					slider.animate(
					{ 'margin-<?php echo ! is_rtl() ? 'left' : 'right'; ?>': '-=' + width },
					500
					);
				});

				// Slide to the previous slide.
				$('.consulting-form .anony-content-slider-control').on('click','.anony-content-slider-prev', function(e) {
					e.preventDefault();
					
					
					if ( offScreenSlides < initialOffScreenCount + 1 ) {
						offScreenSlides = offScreenSlides + 1;
					}
					
					if ( offScreenSlides > initialOffScreenCount ) {
						offScreenSlides = initialOffScreenCount;
						return;
					}
					
					var $currentSlide = $('.anony-content-slide:first');
					var width = $currentSlide.outerWidth();
					slider.animate(
					{ 'margin-<?php echo ! is_rtl() ? 'left' : 'right'; ?>': '+=' + width },
					500
					);				
				});
				$('.anony-content-slider-container').hover(
					function(){
						$(this).addClass('paused');
					},
					function(){
						$(this).removeClass('paused');
					}
				);

				let xDown = null;
				let yDown = null;

				// We use the touchstart event to capture the initial touch position (xDown and yDown variables).
				function handleTouchStart(event) {
					var element = event.target;
					var container = element.closest('.anony-content-slider-container');
					if (container) {
						$('.paused').removeClass('paused');
						clearInterval(contentSliderInterval);
						xDown = event.touches[0].clientX;
						yDown = event.touches[0].clientY;
					} else {
						xDown = null;
						yDown = null;
					}
				}

				// Calculate the horizontal distance (xDiff) and vertical distance (yDiff) between the initial touch position and the current touch position.
				function handleTouchMove(event) {
					if (!xDown || !yDown) {
						return;
					}

					const xUp = event.touches[0].clientX;
					const yUp = event.touches[0].clientY;

					const xDiff = xDown - xUp;
					const yDiff = yDown - yUp;

					/**
					 * If the horizontal distance (xDiff) is greater than the vertical distance (yDiff),
					 * We determine whether it's a swipe to the left or right based on the sign of xDiff.
					 * A negative xDiff indicates a swipe to the left, while a positive xDiff indicates a swipe to the right.
					 */
					if (Math.abs(xDiff) > Math.abs(yDiff)) {
						if (xDiff > 0) {
						// Swipe to the left
						$('.anony-content-slider-control').find('.anony-content-slider-prev').click();
						} else {
						// Swipe to the right
						$('.anony-content-slider-control').find('.anony-content-slider-next').click();
						}
					}

					// Reset values
					xDown = null;
					yDown = null;
				}

				function handleTouchEnd( event ) {
					if (!xDown || !yDown) {
						return;
					}
					contentSliderInterval = setInterval(
						function(){
							if ( $('.paused').length === 0 ) {
								$('.anony-content-slider-container').find('.anony-content-slider-next').click();
							}
						},
						5000
					);
				}

				document.addEventListener("touchstart", handleTouchStart, false);
				document.addEventListener("touchmove", handleTouchMove, false);
				document.addEventListener("touchend", handleTouchEnd, false);
			}
		);
	</script>
		<?php
	}
);