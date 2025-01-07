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
								/*$('.anony-content-slider').slick({
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
								// Fix dragging issue
								$('.anony-content-slider').on('afterChange', function(event, slick, currentSlide){
									// Check if it's the last slide
									if (currentSlide === slick.slideCount - slick.options.slidesToShow) {
										$('.anony-content-slider').slick('slickSetOption', 'swipe', false);
									} else {
										$('.anony-content-slider').slick('slickSetOption', 'swipe', true);
									}
								});*/

								$('.anony-content-slider').owlCarousel({
									rtl: true,
									loop: false,
									margin: 10,
									nav: true,
									dots: false,
									responsive: {
										0: {
											items: 4
										},
										480: {
											items: 6
										},
										768: {
											items: 6
										}
									}
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
					'click',
					".consulting-form #consulting-form-submit",
					function(event){
						let form = $(this).closest('form');
						if ( form.find('input[name="selected-hour"]:checked').length === 0 || form.find('input[name="current-month-day"]:checked').length === 0  ) {
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
							$('#consulting-form-submit-wrapper').show();
							$('html, body').animate({
								scrollTop: $('#consulting-form-submit-wrapper').offset().top
							}, 2000);
						}
					}
				);
			} );
		</script>
		<?php
	}
);
