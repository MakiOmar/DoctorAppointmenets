<?php
/**
 * Scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'select2-css', SNKS_URI . 'assets/css/select2.min.css', false, '4.1.0' );
		wp_enqueue_script( 'select2-js', SNKS_URI . 'assets/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
		// https://flatpickr.js.org/examples/.
		wp_enqueue_style( 'flatpickr', SNKS_URI . 'assets/css/flatpickr.min.css', false, '4.6.13' );
		wp_enqueue_script( 'flatpickr', SNKS_URI . 'assets/js/flatpickr.min.js', array( 'jquery' ), '4.6.13', true );
		wp_enqueue_script( 'flatpickr-ar', SNKS_URI . 'assets/js/flatepickr-ar.js', array( 'flatpickr' ), '4.6.13', true );
		wp_enqueue_style( 'shrinks-responsive', SNKS_URI . 'assets/css/responsive.min.css', array(), time() );
		wp_enqueue_style( 'shrinks-general', SNKS_URI . 'assets/css/general.css', array(), time() );
		// Enqueue Owl Carousel CSS.
		wp_enqueue_style(
			'owl-carousel-css',
			'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',
			array(),
			'2.3.4'
		);

		// Enqueue Owl Carousel Theme CSS (optional).
		wp_enqueue_style(
			'owl-carousel-theme',
			'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css',
			array( 'owl-carousel-css' ),
			'2.3.4'
		);

		// Enqueue Owl Carousel JS.
		wp_enqueue_script(
			'owl-carousel-js',
			'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
			array( 'jquery' ),
			'2.3.4',
			true
		);
		wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array( 'jquery' ), time(), true );
	}
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				let observer = new MutationObserver(function (mutations, observerInstance) {
					const closeButton = document.getElementById('close-notification-box');
					const notificationBox = document.getElementById('custom-notification-box');

					if (closeButton && notificationBox) {
						closeButton.addEventListener('click', function () {
							notificationBox.style.display = 'none';
							document.cookie = 'notification_box_closed=true; max-age=' + (30 * 24 * 60 * 60) + '; path=/';
						});
						observerInstance.disconnect(); // Stop watching once found and attached
					}
				});

				observer.observe(document.body, {
					childList: true,
					subtree: true
				});
			});
		</script>
	<script>
		document.querySelectorAll("#wallet_number, #account_number, #meza_card_number, #otp_input, #phone").forEach((input) => {
				input.addEventListener("beforeinput", function(e) {
					const nextVal = 
						e.target.value.substring(0, e.target.selectionStart) +
						(e.data ?? '') +
						e.target.value.substring(e.target.selectionEnd);

					if (!/^\d*$/.test(nextVal)) {
						e.preventDefault();
					}
				});
			});
		function calculate() {
			let sessionPrice = parseFloat(document.getElementById("sessionPrice").value);

			if (isNaN(sessionPrice) || sessionPrice <= 0) {
				alert("من فضلك أدخل سعر جلسة صحيح.");
				return;
			}

			// Offline calculations
			let A_offline = (sessionPrice * 0.025 + 2) * 1.14;
			let B_offline = (sessionPrice * 0.001) * 1.14;
			let C_offline = 5.13 + 0.96;
			let D_offline = (A_offline + B_offline + C_offline) * 0.025 * 1.03 * 1.14;
			let F_offline = A_offline + B_offline + D_offline;
			let G_offline = A_offline + B_offline + C_offline + D_offline + sessionPrice;

			// Online calculations
			let C_online;
			if (sessionPrice <= 49.999) C_online = 3.99 + 1.92;
			else if (sessionPrice <= 99.999) C_online = 6.56 + 1.92;
			else if (sessionPrice <= 199.999) C_online = 13.68 + 1.92;
			else if (sessionPrice <= 299.999) C_online = 15.39 + 1.92;
			else if (sessionPrice <= 399.999) C_online = 17.1 + 1.92;
			else if (sessionPrice <= 499.999) C_online = 17.67 + 1.92;
			else if (sessionPrice <= 599.999) C_online = 18.24 + 1.92;
			else C_online = 19.38 + 1.92;

			let A_online = (sessionPrice * 0.025 + 2) * 1.14;
			let B_online = (sessionPrice * 0.001) * 1.14;
			let D_online = (A_online + B_online + C_online) * 0.025 * 1.03 * 1.14;
			let F_online = A_online + B_online + D_online;
			let G_online = A_online + B_online + C_online + D_online + sessionPrice;

			// Display results
			document.getElementById("offlinePrice").innerText = sessionPrice.toFixed(2) + " ج.م";
			document.getElementById("offlineExpenses").innerText = (C_offline + F_offline).toFixed(2) + " ج.م";
			document.getElementById("offlineTotal").innerText = G_offline.toFixed(2) + " ج.م";
			document.getElementById("onlinePrice").innerText = sessionPrice.toFixed(2) + " ج.م";
			document.getElementById("onlineExpenses").innerText = (C_online + F_online).toFixed(2) + " ج.م";
			document.getElementById("onlineTotal").innerText = G_online.toFixed(2) + " ج.م";
		}

		function handleEnter(event) {
			if (event.key === "Enter") {
				document.getElementById("calculateBtn").click();
			}
		}
	</script>
	<script type="text/javascript">
		// Function to convert Arabic numbers to English numbers.
		function toEnglishNumbers(input) {
			const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
			const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

			arabicNumbers.forEach((num, index) => {
				input = input.replace(new RegExp(num, 'g'), englishNumbers[index]);
			});

			return input;
		}

		// Apply the function on keyup using jQuery, excluding password fields.
		jQuery(document).ready(function($) {
			$(document).on(
				'click',
				'.field-type-heading-field',
				function(){
					var parent = $(this).closest('.day-specific-form');
					$('.wp-block-columns-is-layout-flex', parent).toggle();
					$('.field-type-submit-field', parent).toggle();
				}
			);
			$(document).on(
				'click',
				'.clinic-popup',
				function(e){
					e.preventDefault();
					$(this).next('.clinic-detail').show();
				}
			);
			$(document).on(
				'click',
				'.close-clinic-popup',
				function(){
					$(this).closest('.clinic-detail').hide();
				}
			);
			$("#password").on("focus", function () {
				$(this).val(""); // Clear the value on focus
			});
			$.fn.justShowErrorpopup = function ( msg ) {
			
				$('#error-container').append(msg);
				$('.trigger-error').trigger('click');
			}
			
			$.fn.justShowSurepopup = function ( msg, content ) {
				$('#sure-container').empty();
				$('#sure-of').empty();

				$('#sure-container').append(msg);
				$('#sure-of').append(content);
				$('.trigger-sure').trigger('click');
			}
			$(document).on(
				'keyup',
				'input:not([type="password"])',
				function() {
				const currentValue = $(this).val();
				const englishValue = toEnglishNumbers(currentValue);
				$(this).val(englishValue);
			});

			$(document).on(
				'keyup',
				'#iban_number',
			function() {
				// Replace any non-numeric characters with an empty string.
				this.value = this.value.replace(/[^0-9]/g, '');
			});
		});
	</script>
		<?php
	},
	100
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			window.addEventListener('beforeunload', function () {
				var pageTransition = document.querySelector('e-page-transition');
				if (pageTransition) {
					pageTransition.style.display = 'block';
				}
			});
			jQuery(document).ready(function($) {
				$(document).on(
					'click',
					'.showNextClinic',
					function(e){
						e.preventDefault();
						$(this).next('.next-clinic-details').toggle();
					}
				);
				$(document).on(
					'click',
					'.jet-form-builder-message',
					function(){
						$(this).hide();
					}
				);
				// Event listener for the "Forg
				$(document).on(
					'click',
					'.snks-timetable-accordion-toggle',
					function(){
						var parent = $(this).closest('.snks-timetable-accordion');
						if ( parent.hasClass('snks-active-accordion') ) {
							parent.next( '.snks-timetable-accordion-content-wrapper' ).slideUp();
							parent.removeClass('snks-active-accordion');
						} else {
							parent.addClass('snks-active-accordion');
							parent.next( '.snks-timetable-accordion-content-wrapper' ).slideDown();
						}
					}
				);
				// Event listener for the "Forgot Password" link
				$(document).on(
					'click',
					'#forget-password',
					function(event) {
						event.preventDefault(); // Prevent the default link behavior

						// Get the selected login method (mobile or email)
						var loginWith = $('input[name="login_with"]:checked').val();
						var tempPhoneVal = $('input[name="temp-phone"]').val();
						var tempPhone;
						if ( $('#temp-phone_country_code').length > 0 && tempPhoneVal !== '' ) {
							tempPhone = $('#temp-phone_country_code').val() + tempPhoneVal;
						} else {
							tempPhone = tempPhoneVal
						}
						var username = $('#username').val();
						if ( loginWith === 'mobile' && tempPhone === '' ) {
							Swal.fire({
								icon: 'error',
								title: 'عفواً',
								text: 'قم بإدخال رقم التليفون', // Assuming response.msg contains the message from the server
								confirmButtonText: 'غلق'
							});
							return;
						}

						if ( loginWith === 'email' && username === '' ) {
							Swal.fire({
								icon: 'error',
								title: 'عفواً',
								text: 'قم بإدخال  البريد الإلكتروني', // Assuming response.msg contains the message from the server
								confirmButtonText: 'غلق'
							});
							return;
						}
						// Add the 'processing' class to show the overlay
						$('.jet-form-builder').addClass('processing');

						// Proceed with the AJAX call without client-side validation
						$.ajax({
							url: '/wp-admin/admin-ajax.php', // WordPress AJAX handler
							type: 'POST',
							data: {
								action: 'custom_forget_password_action', // Custom action name
								login_with: loginWith,
								phone: tempPhone,
								email: username,
								_wpnonce: '<?php echo esc_attr( wp_create_nonce( 'forgetpassword' ) ); ?>' // Include the nonce for security
							},
							success: function(response) {
								var icon , title;
								if ( response.success ){
									icon = 'success';
									title = 'تم';
								} else {
									icon = 'error';
									title = 'خطأ';
								}
								Swal.fire({
									icon: icon,
									title: title,
									text: response.data.msg, // Assuming response.msg contains the message from the server
									confirmButtonText: 'غلق'
								});
							},
							complete: function(){
								$('.jet-form-builder').removeClass('processing');
							},
							error: function(xhr, status, error) {								
							}
						});
					}
			);
			});
			jQuery( document ).ready( function( $ ) {
				$(document).on(
					'click',
					'.snks-tab-item',
					function () {
						// Remove active class from all tabs and panels
						$('.snks-tab-item').removeClass('snks-active');
						$('.snks-tab-panel').removeClass('snks-active');

						// Add active class to the clicked tab and corresponding panel
						$(this).addClass('snks-active');
						const tabName = $(this).data('snks-tab');
						$('#snks-tab-' + tabName).addClass('snks-active');
					}
				);
				$('.attandance_type', $('.snks-booking-item')).css('right', 'calc(50% - ' + ($('.attandance_type', $('.snks-booking-item')).outerWidth( ) / 2 ) + 'px)');
				$('.snks-start-meeting').css('right', 'calc(50% - ' + ($('.snks-start-meeting').outerWidth( ) / 2 ) + 'px)');
				$('<span class="snks-switcher-text switcher-no">لا</span>').insertBefore('#allow_appointment_change');
				$('<span class="snks-switcher-text switcher-yes">نعم</span>').insertAfter('#allow_appointment_change');
				$(document).on(
					'click',
					'.jet-form-builder-message',
					function(){
						$('.jet-form-builder-message').hide();
					}
				);

				$('.snks-count-down').each(
					function () {
						var countdownElement = $(this);
						if ( isNaN( parseInt(countdownElement.text()) ) ) {
							return;
						}
						var countdownValue = parseInt(countdownElement.text());

						var countdownInterval = setInterval(function() {
							countdownValue--;
							countdownElement.text(countdownValue + ' ثانية');
							if (countdownValue === 0) {
								clearInterval(countdownInterval);
								countdownElement.attr( 'href', countdownElement.data('url') );
								countdownElement.addClass('start');
								countdownElement.text( 'إبدأ الآن' );

							}
						}, 1000); // Update the countdown every second (1000 milliseconds)
					}
				);

				$(document).on(
					'click',
					'.doctor_actions .snks-button',
					function (e) {
						e.preventDefault();
						
						// Get the parent form of the clicked button
						var form = $(this).closest('form');
						// Serialize the form data
						var doctorActions = form.serializeArray();
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'doctor_actions_nonce' ) ); ?>';
						doctorActions.push({ name: 'nonce', value: nonce });
						doctorActions.push({ name: 'action', value: 'session_doctor_actions' });
						
						Swal.fire({
							title: 'هل أنت متأكد من تحديد الجلسة كمكتملة؟',
							text: "لا يمكنك التراجع بعد ذلك!",
							icon: 'question',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#6b7280',
							confirmButtonText: 'نعم، حدد كمكتملة',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (result.isConfirmed) {
								// Send AJAX request only if user confirms
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
									data: doctorActions,
									success: function(response) {
										console.log('=== DEBUG: Session completion response ===');
										console.log('Response:', response);
										console.log('Response.data:', response.data);
										console.log('is_ai_session:', response.data.is_ai_session);
										console.log('message:', response.data.message);
										
										if (response.success) {
											// Ask about Roshta if this is an AI session
											if (response.data.is_ai_session) {
												console.log('=== DEBUG: Showing Roshta prompt for AI session ===');
												Swal.fire({
													title: 'هل تريد إرسال المريض لاستشارة روشتا؟',
													text: 'هل تعتقد أن المريض يحتاج لاستشارة مع طبيب نفسي لوصف دواء؟',
													icon: 'question',
													showCancelButton: true,
													confirmButtonColor: '#3085d6',
													cancelButtonColor: '#6b7280',
													confirmButtonText: 'نعم، أرسل لروشتا',
													cancelButtonText: 'لا، شكراً'
												}).then((rochtahResult) => {
													console.log('=== DEBUG: Roshta prompt result ===');
													console.log('Result:', rochtahResult);
													console.log('isConfirmed:', rochtahResult.isConfirmed);
													
													if (rochtahResult.isConfirmed) {
														console.log('=== DEBUG: User confirmed Roshta, sending request ===');
														// Send Roshta request
														var rochtahNonce = '<?php echo esc_html( wp_create_nonce( 'rochtah_request_nonce' ) ); ?>';
														$.ajax({
															type: 'POST',
															url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
															data: {
																action: 'request_rochtah',
																session_id: response.data.session_id,
																client_id: response.data.client_id,
																order_id: response.data.order_id,
																nonce: rochtahNonce
															},
															success: function(rochtahResponse) {
																console.log('=== DEBUG: Roshta request response ===');
																console.log('RochtahResponse:', rochtahResponse);
																
																if (rochtahResponse.success) {
																	console.log('=== DEBUG: Roshta request successful, showing success message ===');
																	Swal.fire({
																		title: 'تم إرسال طلب روشتا!',
																		text: 'سيتم إعلام المريض بطلب روشتا',
																		icon: 'success',
																		confirmButtonText: 'حسناً'
																	}).then(() => {
																		console.log('=== DEBUG: Roshta success message confirmed, would reload here ===');
																		// location.reload();
																	});
																} else {
																	console.log('=== DEBUG: Roshta request failed, showing error message ===');
																	Swal.fire({
																		title: 'خطأ!',
																		text: rochtahResponse.data || 'حدث خطأ أثناء إرسال طلب روشتا',
																		icon: 'error',
																		confirmButtonText: 'حسناً'
																	}).then(() => {
																		console.log('=== DEBUG: Roshta error message confirmed, would reload here ===');
																		// location.reload();
																	});
																}
															},
															error: function(xhr, status, error) {
																console.error('=== DEBUG: Roshta request AJAX error ===');
																console.error('Error:', error);
																console.error('Status:', status);
																console.error('XHR:', xhr);
																Swal.fire({
																	title: 'خطأ!',
																	text: 'حدث خطأ أثناء إرسال طلب روشتا',
																	icon: 'error',
																	confirmButtonText: 'حسناً'
																}).then(() => {
																	console.log('=== DEBUG: Roshta AJAX error message confirmed, would reload here ===');
																	// location.reload();
																});
															}
														});
													} else {
														console.log('=== DEBUG: User declined Roshta, showing completion message ===');
														Swal.fire({
															title: 'تم بنجاح!',
															text: response.data.message || 'تم تحديد الجلسة كمكتملة بنجاح',
															icon: 'success',
															confirmButtonText: 'حسناً'
														}).then(() => {
															console.log('=== DEBUG: Completion message confirmed after declining Roshta, would reload here ===');
															// location.reload();
														});
													}
												});
											} else {
												console.log('=== DEBUG: Not an AI session, showing completion message ===');
												Swal.fire({
													title: 'تم بنجاح!',
													text: response.data.message || 'تم تحديد الجلسة كمكتملة بنجاح',
													icon: 'success',
													confirmButtonText: 'حسناً'
												}).then(() => {
													console.log('=== DEBUG: Non-AI session completion message confirmed, would reload here ===');
													// location.reload();
												});
											}
										} else {
											console.log('=== DEBUG: Session completion failed ===');
											console.log('Error response:', response);
											Swal.fire({
												title: 'خطأ!',
												text: response.data || 'حدث خطأ أثناء تحديد الجلسة كمكتملة',
												icon: 'error',
												confirmButtonText: 'حسناً'
											});
										}
									},
									error: function(xhr, status, error) {
										console.error('Error:', error);
										Swal.fire({
											title: 'خطأ!',
											text: 'حدث خطأ أثناء تحديد الجلسة كمكتملة',
											icon: 'error',
											confirmButtonText: 'حسناً'
										});
									}
								});
							}
						});
					}
				);
				
				// Handle Roshta booking button clicks
				$(document).on('click', '.book-rochtah-btn', function(e) {
					e.preventDefault();
					var requestId = $(this).data('request-id');
					
					// Show calendar for available dates
					showRochtahCalendar(requestId);
				});
				
				// Function to show Roshta calendar
				function showRochtahCalendar(requestId) {
					// Get available dates for Roshta doctor
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						data: {
							action: 'get_rochtah_available_dates',
							request_id: requestId,
							nonce: '<?php echo esc_html( wp_create_nonce( 'rochtah_dates_nonce' ) ); ?>'
						},
						success: function(response) {
							if (response.success && response.data.available_dates) {
								showRochtahDatePicker(response.data.available_dates, requestId);
							} else {
								Swal.fire({
									title: 'لا توجد مواعيد متاحة',
									text: 'عذراً، لا توجد مواعيد متاحة حالياً لطبيب روشتا',
									icon: 'info',
									confirmButtonText: 'حسناً'
								});
							}
						},
						error: function() {
							Swal.fire({
								title: 'خطأ!',
								text: 'حدث خطأ أثناء جلب المواعيد المتاحة',
								icon: 'error',
								confirmButtonText: 'حسناً'
							});
						}
					});
				}
				
				// Function to show date picker
				function showRochtahDatePicker(availableDates, requestId) {
					var dateOptions = availableDates.map(function(date) {
						return {
							text: date.formatted_date,
							value: date.date
						};
					});
					
					Swal.fire({
						title: 'اختر التاريخ',
						input: 'select',
						inputOptions: dateOptions.reduce(function(acc, option) {
							acc[option.value] = option.text;
							return acc;
						}, {}),
						showCancelButton: true,
						confirmButtonText: 'التالي',
						cancelButtonText: 'إلغاء',
						inputValidator: function(value) {
							if (!value) {
								return 'يجب اختيار تاريخ';
							}
						}
					}).then((result) => {
						if (result.isConfirmed) {
							showRochtahTimeSlots(result.value, requestId);
						}
					});
				}
				
				// Function to show time slots
				function showRochtahTimeSlots(selectedDate, requestId) {
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						data: {
							action: 'get_rochtah_time_slots',
							request_id: requestId,
							date: selectedDate,
							nonce: '<?php echo esc_html( wp_create_nonce( 'rochtah_slots_nonce' ) ); ?>'
						},
						success: function(response) {
							if (response.success && response.data.available_slots) {
								var slotOptions = response.data.available_slots.map(function(slot) {
									return {
										text: slot.time,
										value: slot.slot_id
									};
								});
								
								Swal.fire({
									title: 'اختر الوقت',
									input: 'select',
									inputOptions: slotOptions.reduce(function(acc, option) {
										acc[option.value] = option.text;
										return acc;
									}, {}),
									showCancelButton: true,
									confirmButtonText: 'حجز الموعد',
									cancelButtonText: 'إلغاء',
									inputValidator: function(value) {
										if (!value) {
											return 'يجب اختيار وقت';
										}
									}
								}).then((result) => {
									if (result.isConfirmed) {
										bookRochtahAppointment(requestId, selectedDate, result.value);
									}
								});
							} else {
								Swal.fire({
									title: 'لا توجد أوقات متاحة',
									text: 'عذراً، لا توجد أوقات متاحة في هذا التاريخ',
									icon: 'info',
									confirmButtonText: 'حسناً'
								});
							}
						},
						error: function() {
							Swal.fire({
								title: 'خطأ!',
								text: 'حدث خطأ أثناء جلب الأوقات المتاحة',
								icon: 'error',
								confirmButtonText: 'حسناً'
							});
						}
					});
				}
				
				// Function to book Roshta appointment
				function bookRochtahAppointment(requestId, date, slotId) {
					Swal.fire({
						title: 'تأكيد الحجز',
						text: 'هل أنت متأكد من حجز هذا الموعد؟',
						icon: 'question',
						showCancelButton: true,
						confirmButtonText: 'نعم، احجز',
						cancelButtonText: 'إلغاء'
					}).then((result) => {
						if (result.isConfirmed) {
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								data: {
									action: 'book_rochtah_appointment',
									request_id: requestId,
									date: date,
									slot_id: slotId,
									nonce: '<?php echo esc_html( wp_create_nonce( 'rochtah_booking_nonce' ) ); ?>'
								},
								success: function(response) {
									if (response.success) {
										Swal.fire({
											title: 'تم الحجز بنجاح!',
											text: response.data.message,
											icon: 'success',
											confirmButtonText: 'حسناً'
										}).then(() => {
											location.reload();
										});
									} else {
										Swal.fire({
											title: 'خطأ!',
											text: response.data || 'حدث خطأ أثناء الحجز',
											icon: 'error',
											confirmButtonText: 'حسناً'
										});
									}
								},
								error: function() {
									Swal.fire({
										title: 'خطأ!',
										text: 'حدث خطأ أثناء الحجز',
										icon: 'error',
										confirmButtonText: 'حسناً'
									});
								}
							});
						}
					});
				}
				$( document ).on(
					'click',
					'.snks-cancel-appointment',
					function (e) {
						e.preventDefault();
						Swal.fire({
							title: 'هل أنت متأكد؟',
							text: "لا يمكنك التراجع بعد ذلك!",
							icon: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'نعم، أنا متأكد',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (!result.isConfirmed) {
								return;
							}
						});

						var clicked   = $(this);
						var bookingID = $(this).data('id');
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'cancel_appointment_nonce' ) ); ?>';
						
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								action    : 'cancel_appointment',
								bookingID : bookingID,
								nonce     : nonce,
							},
							success: function(response) {
								if ( response.resp ) {
									clicked.text('تم');
									window.location.reload();
								}
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
			} );
		</script>
		<?php
	}
);

add_action(
	'wp_footer',
	function () {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$url_params = wp_unslash( $_GET );
		if ( ! snks_is_doctor() || empty( $url_params['room_id'] ) ) {
			return;
		}
		$timetable = snks_get_timetable_by( 'ID', absint( $url_params['room_id'] ) );
		if ( ! $timetable ) {
			return;
		}
		$session_id      = absint( $url_params['room_id'] );
		$current_doctor  = get_current_user_id();
		$session_user_id = $timetable->user_id;
		if ( absint( $session_user_id ) !== $current_doctor ) {
			return;
		}
		$redirect_after_meeting = add_query_arg( 'id', snks_get_settings_doctor_id(), home_url( '/account-setting' ) );
		?>
		<script>
			jQuery( document ).ready(
				function ( $ ) {
					document.querySelector("body").addEventListener("sessionEnded", (event) => {
						if ( confirm( 'هل تريد تحديد الجلسة كمكتمل؟' ) === false ) {
							return;
						}
						const sessionID = <?php echo esc_html( $session_id ); ?>;
						const doctorID  = <?php echo esc_html( $current_doctor ); ?>;

						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'end_session_nonce' ) ); ?>';
						const data = {
								action    : 'end_session',
								sessionID : sessionID,
								doctorID  : doctorID,
								nonce     : nonce
						}
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								window.location.href = '<?php echo esc_url( $redirect_after_meeting ); ?>';
							},
							error: function(xhr, status, error) {
								Swal.fire({
									icon: 'error',
									title: 'خطأ',
									text: 'حدث خطأ: ' + error.statusText,
									confirmButtonText: 'غلق'
								});
							}
						});
					});
				}
			);
		</script>
		<?php
	}
);

add_action(
	'admin_footer',
	function () {
		$screen = get_current_screen();
		if ( 'user-edit' !== $screen->base ) {
			return;
		}
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$(document).on(
					'click',
					'#snks-insert_timetable',
					function ( e ) {
						e.preventDefault();
						if ( '' === $( '#user_timetable_date' ).val() || '' ===  $( '#user_timetable_time' ).val() || '' === $( '#user_id' ).val() ) {
							return;
						}
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'update_timetable_nonce' ) ); ?>';
						const data = {
								action : 'update_timetable_markup',
								date   : $( '#user_timetable_date' ).val(),
								time   : $( '#user_timetable_time' ).val(),
								userID : $( '#user_id' ).val(),
								purpose : $( '#user_timetable_purpose' ).val(),
								patientID : $( '#user_timetable_patient_id' ).val(),
								sessionTitle : $( '#user_timetable_session_title' ).val(),
								nonce  : nonce
						}
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								if ( response.resp && '' !== response.html ) {
									$( '#current-user-timetable' ).append( response.html );
									$( '.insert-error' ).hide();
								} else {
									$( '.insert-error' ).show();
									setTimeout(
										function () {
											$( '.insert-error' ).hide();
										},
										2000
									);
								}
								// Handle the response data as needed.
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);

				$('body').on(
					'click',
					'.timetable-action',
					function (e) {
						e.preventDefault();
						Swal.fire({
							title: 'هل أنت متأكد؟',
							text: "لا يمكنك التراجع بعد ذلك!",
							icon: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'نعم، أنا متأكد',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (!result.isConfirmed) {
								return;
							}
						});

						// Perform nonce check.
						var clicked = $( this );
						var nonce   = '<?php echo esc_html( wp_create_nonce( 'delete_timetable_nonce' ) ); ?>';
						var data    = {
							targrtID : $( this ).data('id'),
							action   : $( this ).data('action'),
							nonce  : nonce
						}

						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								if ( response.resp ){
									clicked.closest( 'tr' ).remove();
								}
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);

			} );
		</script>
		<?php
	}
);

add_action(
	'admin_footer',
	function () {
		?>
		<script>
			jQuery(document).ready(function($) {
				$('.preview-holder').each(function() {
					var url = $(this).attr('data-url-attr');
					var id = $(this).attr('data-id-attr');
					if (url !== '') {
						$(this).wrap('<a href=\"' + url + '\" target=\"_blank\"></a>');
						$('.centered', $(this)).html('<img src=\"' + url + '\">');
					} else if (id !== '') {
						$(this).wrap('<a href=\"' + id + '\" target=\"_blank\"></a>');
						$('.centered', $(this)).html('<img src=\"' + id + '\">');
					}
					$(document).on(
						'.cx-remove-image',
						function(){
							$(this).closest('.cx-image-wrap').html('');
						}
					);
				});
			});
		</script>
		<?php
	}
);

add_action(
	'phone_input_scripts',
	function ( $atts ) {
		if ( 'yes' === $atts['hide_target'] ) {
			echo "$('input[name=" . esc_attr( $atts['target'] ) . "]').closest('.jet-form-builder-row').hide();";
		}
	}
);

add_action(
	'wp_footer',
	function () {
		?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$(document).on('click', '#pricing-details-toggle', function(){
				const $icon = $(this);
				// Toggle rotate class
				if ($icon.hasClass('rotate')) {
					$icon.removeClass('rotate');
				} else {
					$icon.addClass('rotate');
				}
				$('#pricing-details').toggle();
			});
			$(document).on("jet-form-builder/ajax/on-success", function(event, formData, response) {
				// Check if personal details form
				if ( response[0].dataset.formId == '2077' ) {
					window.location.href = '<?php echo esc_url( add_query_arg( 'status', 'success', site_url( '/register/' ) ) ); ?>';
				}
			});
			$(document).on('click', '#snks-logout', function (e) {
				e.preventDefault();
				var redirect = $(this).data('href');
				Swal.fire({
					title: 'هل تريد تسجيل الخروج؟',
					text: 'سيتم تسجيل خروجك الآن.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'نعم، سجل خروجي',
					cancelButtonText: 'إلغاء'
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							data: {
								action: 'snks_logout',
								nonce: '<?php echo esc_attr( wp_create_nonce( 'snks_logout_nonce' ) ); ?>'
							},
							success: function (response) {
								if (response.success) {
									window.location.href = response.data.redirect_url; // Reload the page after successful logout
								} else {
									alert('خطأ: ' + response.data);
								}
							},
							error: function () {
								alert('تعذر إتمام الطلب. حاول مرة أخرى لاحقًا.');
							}
						});
					}
				});
			});
		});
		<?php if ( snks_is_patient() ) { ?>
		jQuery(document).ready(function ($) {
			$('#uname').val('').trigger('change');
			$(document).on('click', '#edit_patient_phone', function (e) {
				e.preventDefault();
				var nonce = $(this).data('nonce');
				
				Swal.fire({
					title: 'هل تريد تعديل بياناتك',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'نعم، قم بالتعديل',
					cancelButtonText: 'إلغاء'
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							data: {
								action: 'edit_patient_phone',
								nonce: nonce
							},
							success: function (response) {
								if (response.success) {
									window.location.href = $( '#selected_doctor_url' ).val();
								}
							},
							error: function () {
								Swal.fire('حدث خطأ ما', 'حاول مرة أخرى لاحقًا.', 'error');
							}
						});
					}
				});
			});
		});
		<?php } ?>
	</script>
		<?php
	}
);

add_action(
	'wp_head',
	function () {
		//phpcs:disable
		if ( is_page( 'doctor-login' ) || is_page( 'register' ) || is_page( 5170 ) || ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], '/org/' ) ) ) {
			//phpcs:enable
			?>
			<!-- Meta Pixel Code -->
			<script>
			!function(f,b,e,v,n,t,s)
			{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s)}(window, document,'script',
			'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', '572699531895875');
			fbq('track', 'PageView');
			</script>
			<noscript><img height="1" width="1" style="display:none"
			src="https://www.facebook.com/tr?id=572699531895875&ev=PageView&noscript=1"
			/></noscript>
			<!-- End Meta Pixel Code -->
			<?php
		}
	},
	999
);
add_action(
	'wp_footer',
	function () {
		global $wp;
		$dark_color = ! empty( $_COOKIE['dark_color'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dark_color'] ) ) : '#024059';
		if ( isset( $wp->query_vars ) && isset( $wp->query_vars['doctor_id'] ) ) {
			$clinic_color   = get_user_meta( snks_url_get_doctors_id(), 'clinic_colors', true );
			if ( $clinic_color && '' !== $clinic_color ) {
				$clinics_colors = json_decode( CLINICS_COLORS );
				$clinic_colors  = 'color_' . $clinic_color;
				$dark_color     = esc_attr( $clinics_colors->$clinic_colors[1] );
			}
		}
		?>
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				applyParentImageColorChange(".color-change-trigger-load", "load", "<?php echo esc_html( $dark_color ); ?>", false);
				applyParentImageColorChange(".shap-head", "load", "<?php echo esc_html( $dark_color ); ?>", false);
				applyParentImageColorChange(".popup-trigger", "click", "#024059", true);
				applyParentImageColorChange(".snks-settings-tab", "load", "#024059", false);
				<?php
				if ( is_page( 'my-bookings' ) ) {
					?>
					applyParentImageColorChange(".snks-my-bookings", "load", "#024059", false);
					<?php
				}

				if ( is_page( 'my-profile' ) ) {
					?>
					applyParentImageColorChange(".snks-my-profile", "load", "#024059", false);
					<?php
				}
				?>
			});
		</script>
		<?php
	}
);
add_action(
	'wp_head',
	function () {
		?>
		<script>
			/**
			 * Dynamically recolors PNG images inside a parent container.
			 * @param {string} parentSelector - The class of the parent element that triggers the change.
			 * @param {string} action - The trigger event: "click", "hover", or "load".
			 * @param {string} color - The target color in hex format (e.g., "#FF5733").
			 * @param {boolean} resetOthers - Whether to reset other images to their original state.
			 */
			function applyParentImageColorChange(parentSelector, action, color, resetOthers) {
				document.querySelectorAll(parentSelector).forEach(parent => {
					let img = parent.querySelector("img"); // Target any image inside the parent
					if (!img) return; // If no image is found, exit function

					function recolorImage() {
						// Reset all previous images (only if resetOthers is true)
						if (resetOthers) {
							document.querySelectorAll(parentSelector).forEach(otherParent => {
								let otherImg = otherParent.querySelector("img");
								let otherCanvas = otherParent.querySelector("canvas");
								if (otherCanvas && otherImg) {
									otherCanvas.style.display = "none"; // Hide canvas
									otherImg.style.display = "inline"; // Show original image
								}
							});
						}

						// Ensure the image is fully loaded before modifying it
						if (!img.complete) {
							img.onload = function () {
								recolorImage(); // Run function again after image loads
							};
							return;
						}

						// Create or reuse a canvas
						let canvas = parent.querySelector("canvas");
						if (!canvas) {
							canvas = document.createElement("canvas");
							canvas.style.display = "none"; // Hide by default
							img.parentNode.insertBefore(canvas, img); // Prepend canvas before the image
						}
						let ctx = canvas.getContext("2d");

						// Get high-resolution image dimensions
						let imgWidth = img.naturalWidth;
						let imgHeight = img.naturalHeight;
						canvas.width = imgWidth;
						canvas.height = imgHeight;

						// Draw the image onto the canvas
						ctx.drawImage(img, 0, 0, imgWidth, imgHeight);
						let imageData = ctx.getImageData(0, 0, imgWidth, imgHeight);
						let data = imageData.data;

						let r = parseInt(color.substring(1, 3), 16);
						let g = parseInt(color.substring(3, 5), 16);
						let b = parseInt(color.substring(5, 7), 16);

						// Loop through pixels and recolor non-transparent areas
						for (let i = 0; i < data.length; i += 4) {
							if (data[i+3] > 0) {  // If pixel is not transparent
								data[i] = r;   // Red
								data[i+1] = g; // Green
								data[i+2] = b; // Blue
							}
						}

						// Apply the new color
						ctx.putImageData(imageData, 0, 0);

						// Ensure the canvas matches displayed image size
						canvas.style.width = img.width + "px";
						canvas.style.height = img.height + "px";

						// Hide the original image and show the canvas
						img.style.display = "none";
						canvas.style.display = "inline-block";
					}

					// Apply event listener based on action type
					if (action === "click") {
						parent.addEventListener("click", recolorImage);
					} else if (action === "hover") {
						parent.addEventListener("mouseenter", recolorImage);
						parent.addEventListener("mouseleave", function () {
							// Reset to original state on mouse leave
							let canvas = parent.querySelector("canvas");
							if (canvas) canvas.style.display = "none";
							img.style.display = "inline";
						});
					} else if (action === "load") {
						recolorImage(); // Apply color change immediately on page load
					}
				});
			}
		</script>
		<?php
	},
	999
);
