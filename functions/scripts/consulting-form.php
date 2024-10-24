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
		global $wp;
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				var contentSliderInterval, initialOffScreenCount;
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

				function initContentSlider() {
					return;
					if ( $('.anony-content-slider').length < 1 ) {
						return;
					}
					var slideWidth = $('.anony-content-slide').outerWidth();
					var slider     = $('.anony-content-slider');

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
				}

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
				// Function to get the value of a query parameter from the URL.
				function getQueryParamValue(param) {
					const urlParams = new URLSearchParams(window.location.search);
					return urlParams.get(param);
				}
				function getBookingForm(){
					// Check if the 'edit-booking' query parameter exists
					const editBookingId = getQueryParamValue('edit-booking') ?  getQueryParamValue('edit-booking') : '' ;
					var attendanceType  = $('input[name=attendance_type]:checked').val();
					var periodClicked   = $('input[name=period]:checked');
					var period          = $('input[name=period]:checked').val();
					var doctor_id       = $('input[name=filter_user_id]').val();
					var nonce           = '<?php echo esc_html( wp_create_nonce( 'get_booking_form_nonce' ) ); ?>';
					$('.snks-period-label' ).removeClass('snks-light-bg').addClass('snks-bg-secondary');
					$('#snks-period-label-' + period ).addClass('snks-light-bg').removeClass('snks-bg-secondary');
					if ( typeof attendanceType !== 'undefined' && typeof period !== 'undefined' ) {
						var price = $('input[name=period]:checked').data('price');
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								attendanceType: attendanceType,
								period        : period,
								doctor_id     : doctor_id,
								price         : price,
								editBookingId : editBookingId,
								action        : 'get_booking_form',
							},
							beforeSend: function() {
								$('label').removeClass('snks-loading');
								periodClicked.prev('label').addClass('snks-loading');
							},
							success: function(response) {
								$( '#consulting-forms-container' ).html( response );
								if ( $('.consulting-form').length > 0 ) {
									$('html, body').animate({
										scrollTop: $('.consulting-form').offset().top
									}, 1000);
								}
								$('.anony-content-slider').slick({
									slidesToShow: 3,
									slidesToScroll: 1,
									autoplay: false,
									arrows: true,
									rtl: true,
									infinite:false,
									responsive: [
										{
											breakpoint: 480,
											settings: {
												slidesToShow: 3
											}
										}
									]
								});
							},
							complete: function() {
								periodClicked.prev('label').removeClass('snks-loading');
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				}
				<?php
				if ( isset( $wp->query_vars['doctor_id'] ) ) {
					?>
				$(document).on(
					'change',
					'input[name=attendance_type]',
					function() {
						$('#consulting-forms-container').html('');
						$('.periods_wrapper').html('');
						const editBookingId = getQueryParamValue('edit-booking') ?  getQueryParamValue('edit-booking') : '' ;
						var attendanceTypeClicked = $(this);
						var attendanceType = $(this).val();
						var doctor_id       = $('input[name=filter_user_id]').val();
						var nonce           = '<?php echo esc_html( wp_create_nonce( 'get_periods_nonce' ) ); ?>';
						$('input[name=attendance_type]').closest('.attendance_type_wrapper').removeClass('active');
						$('input[name=attendance_type]:checked').closest('.attendance_type_wrapper').addClass('active');
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								attendanceType: attendanceType,
								doctor_id     : doctor_id,
								editBookingId : editBookingId,
								action        : 'get_periods',
							},
							beforeSend: function() {
								$('label').removeClass('snks-loading');
								attendanceTypeClicked.prev('label').addClass('snks-loading');
							},
							success: function(response) {
								var periods_wrapper = $('.periods_wrapper');
								if ( response.includes('clinic_template') ) {
									periods_wrapper.removeClass('snks-bg');
									periods_wrapper.css({
										'background-color': '#d9f4ff',
											'padding': '0 30px'
									});
								}
								periods_wrapper.html( response );
								periods_wrapper.css('opacity', '1');
								$('html, body').animate({
									scrollTop: periods_wrapper.offset().top
								}, 1000);
							},
							complete: function() {
								attendanceTypeClicked.prev('label').removeClass('snks-loading');
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
				<?php } ?>
				$(document).on(
					'change',
					'input[name=attendance_type],input[name=period]',
					function() {
						getBookingForm();
					}
				);

				$(document).on(
					'submit',
					".consulting-form",
					function(event){
						if ( $( this ).find('input[name="selected-hour"]:checked').length === 0 || $( this ).find('input[name="current-month-day"]:checked').length === 0  ) {
							event.preventDefault();
							Swal.fire({
								icon: 'warning',
								title: 'تنبيه',
								text: 'فضلاً تأكد من أنك قمت بتحديد اليوم والساعة', // The original alert message
								confirmButtonText: 'موافق'
							});
						}

						if (!$('#terms-conditions').is(':checked')) {
							event.preventDefault();
							Swal.fire({
								icon: 'error',
								title: 'خطأ',
								text: 'يرجى الموافقة على الشروط والأحكام حتى تستطيع المتابعة!', // The original message
								confirmButtonText: 'موافق'
							});
						}
					}
				);
				$( 'body' ).on(
					'change',
					'.current-month-day-radio',
					function () {
						var parentForm = $( this ).closest('.consulting-form');
						$( '.anony-day-radio', parentForm ).find('label').removeClass( 'active-day' );
						if ($(this).is(':checked')) {
							if ( ! $( this ).prev('label').hasClass( 'active-day' ) ) {
								$( this ).prev('label').addClass( 'active-day' );
							}
						}
						var dayClicked = $(this);
						var attendanceType = $('input[name=attendance_type]:checked').val();
						var slectedDay = $(this).val();
						var userID     = $(this).data('user');
						var period     = $(this).data('period');
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'fetch_start_times_nonce' ) ); ?>';
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slectedDay: slectedDay,
								userID    : userID,
								period    : period,
								attendanceType    : attendanceType,
								action    : 'fetch_start_times',
							},
							beforeSend: function() {
								$('label').removeClass('snks-loading');
								dayClicked.prev('label').addClass('snks-loading');
							},
							success: function(response) {
								$( '.snks-available-hours', $( '.consulting-form-' + period ) ).html( response.resp );
								$('#snks-available-hours-wrapper').show();
								$('html, body').animate({
									scrollTop: $('#snks-available-hours-wrapper').offset().top
								}, 1000);
							},
							complete: function() {
								dayClicked.prev('label').removeClass('snks-loading');
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});

					}
				);
				$( 'body' ).on(
					'change',
					'.hour-radio',
					function () {
						$( '.available-time' ).removeClass( 'active-hour' );
						if ($(this).is(':checked')) {
							$( this ).closest('.available-time').addClass( 'active-hour' );
							$('#consulting-form-submit').show();
							$('html, body').animate({
								scrollTop: $('#consulting-form-submit').offset().top
							}, 2000);
						}
					}
				);
			} );
		</script>
		<?php
	}
);
