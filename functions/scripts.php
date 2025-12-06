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
				// Dynamic positioning for rotated session start buttons based on actual text width
				$('.snks-start-meeting.rotate-90').each(function() {
					var $this = $(this);
					var $temp = $('<span>').html($this.html()).css({
						'visibility': 'hidden',
						'position': 'absolute',
						'white-space': 'nowrap',
						'font-family': $this.css('font-family'),
						'font-size': $this.css('font-size'),
						'font-weight': $this.css('font-weight')
					});
					$('body').append($temp);
					var textWidth = $temp.width();
					$temp.remove();
					
					// Ensure SVG elements maintain their exact styling
					$this.find('svg').css({
						'display': 'inline-block',
						'width': '20px',
						'height': '20px',
						'margin-left': '8px',
						'animation': 'spin 1s linear infinite',
						'vertical-align': 'middle',
						'position': 'absolute',
						'left': '-100%'
					});
					
					// Adjust positioning based on actual text width
					// For rotated text, we need to consider the height it will take when rotated
					var leftOffset = textWidth <= 80 ? '-35%' : '-25%';
					$this.css('left', leftOffset);
				});
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

			// Check if WP_DEBUG is enabled
			var wpDebugEnabled = <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'true' : 'false'; ?>;
			
			// Debug logging helper
			function debugLog(message) {
				if (wpDebugEnabled) {
					console.log(message);
				}
			}
			
			// Add CSS to ensure disabled buttons are not clickable and show loading effect
			function applyDisabledButtonStyles() {
				if (!$('#session-completion-disabled-style').length) {
					var css = `
						@keyframes pulse-waiting {
							0%, 100% { opacity: 0.4; }
							50% { opacity: 0.7; }
						}
						.snks-complete-session-btn:disabled, 
						.snks-complete-session-btn[disabled],
						.snks-send-message-btn:disabled,
						.snks-send-message-btn[disabled] { 
							pointer-events: none !important; 
							cursor: not-allowed !important;
							opacity: 0.6 !important;
						}
						.snks-button-waiting {
							animation: pulse-waiting 2s ease-in-out infinite;
							position: relative;
							overflow: hidden;
						}
						.snks-button-waiting::after {
							content: "";
							position: absolute;
							top: 0;
							left: -100%;
							width: 100%;
							height: 100%;
							background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
							animation: shimmer 2s infinite;
						}
						@keyframes shimmer {
							0% { left: -100%; }
							100% { left: 100%; }
						}
					`;
					$('<style id="session-completion-disabled-style">')
						.html(css)
						.appendTo('head');
					debugLog('💅 Applied disabled button styles with loading effect');
				}
			}
			
			// Initialize session completion button activation
			function initSessionCompletionCheck() {
				$('.doctor-actions').each(function() {
					var $doctorActions = $(this);
					var rawSessionEnd = $doctorActions.attr('data-session-end');
					var sessionEndTime = parseInt($doctorActions.data('session-end'));
					var $button = $doctorActions.find('.snks-complete-session-btn');
					var $sendMessageButton = $doctorActions.find('.snks-send-message-btn');
					var sessionId = $doctorActions.find('input[name="session_id"]').val();
					
					if (!sessionEndTime || !$button.length) {
						return;
					}
					
					// Function to check if session has ended
					function checkSessionEnd() {
						var currentTime = Math.floor(Date.now() / 1000);
						
						if (currentTime >= sessionEndTime) {
							// Session has ended - enable button
							$button.prop('disabled', false)
								.removeAttr('disabled')
								.removeAttr('style')
								.removeClass('snks-button-waiting')
								.attr('title', '');
							
							// Enable send message button if it exists
							if ($sendMessageButton.length) {
								$sendMessageButton.prop('disabled', false)
									.removeAttr('disabled')
									.removeAttr('style')
									.removeClass('snks-button-waiting')
									.attr('title', '');
							}
							return false; // Stop the interval
						} else {
							// Session hasn't ended - ensure buttons are disabled
							$button.prop('disabled', true)
								.attr('disabled', 'disabled')
								.addClass('snks-button-waiting')
								.css({
									'pointer-events': 'none !important',
									'cursor': 'not-allowed !important'
								});
							
							// Disable send message button if it exists
							if ($sendMessageButton.length) {
								$sendMessageButton.prop('disabled', true)
									.attr('disabled', 'disabled')
									.addClass('snks-button-waiting')
									.css({
										'pointer-events': 'none !important',
										'cursor': 'not-allowed !important'
									});
							}
							return true; // Continue the interval
						}
					}
					
					// Initial check - ensure buttons are in correct state on load
					var initialCurrentTime = Math.floor(Date.now() / 1000);
					var shouldContinue = checkSessionEnd();
					
					// If session hasn't ended, check every 10 seconds
					if (shouldContinue && initialCurrentTime < sessionEndTime) {
						var intervalId = setInterval(function() {
							if (!checkSessionEnd()) {
								clearInterval(intervalId);
							}
						}, 10000);
					}
				});
			}
			
			// Initialize checks on page load and immediately
			function initializeSessionButtons() {
				debugLog('🚀 Initializing session completion checks...');
				applyDisabledButtonStyles();
				initSessionCompletionCheck();
			}
			
			// Run immediately (in case DOM is already ready)
			if (document.readyState === 'loading') {
				$(document).ready(initializeSessionButtons);
			} else {
				// DOM is already ready, run immediately
				initializeSessionButtons();
			}
			
			// Function to attach completion handler
			function attachCompletionHandlerToButtons() {
				$(document).off('click.attendanceHandlerV3', '.doctor_actions .snks-complete-session-btn');
				$('.doctor_actions .snks-complete-session-btn').off('click.attendanceHandlerV3');
				
				$(document).on(
					'click.attendanceHandlerV3',
					'.doctor_actions .snks-complete-session-btn',
					function (e) {
						// Stop all propagation to prevent other handlers
						e.preventDefault();
						e.stopPropagation();
						e.stopImmediatePropagation();
						
						// Check if disabled
						if ($(this).prop('disabled') || $(this).attr('disabled')) {
							return false;
						}
						
						var form = $(this).closest('form');
						var doctorActions = form.serializeArray();
						var nonce = '<?php echo esc_js( wp_create_nonce( "doctor_actions_nonce" ) ); ?>';
						doctorActions.push({ name: 'nonce', value: nonce });
						doctorActions.push({ name: 'action', value: 'session_doctor_actions' });
						
						var sessionId = form.find('input[name="session_id"]').val();
						var clientId = form.find('input[name="attendees"]').val();
						
						// Show attendance question directly
						Swal.fire({
							title: 'هل حضر المريض الجلسة؟',
							html: `
								<div style="text-align: right; direction: rtl;">
									<div style="margin: 20px 0;">
										<label style="display: block; margin-bottom: 15px; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: border-color 0.3s;">
											<input type="radio" name="attendance" value="yes" style="margin-left: 10px;" checked>
											<span style="font-size: 14px;">حضر المريض الجلسة وحصل عليها دون مشاكل.</span>
										</label>
										<label style="display: block; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: border-color 0.3s;">
											<input type="radio" name="attendance" value="no" style="margin-left: 10px;">
											<span style="font-size: 14px;">لم يحضر المريض رغم تواجدي في الموعد وبقائي لمدة ربع ساعة على الأقل في انتظاره.</span>
										</label>
									</div>
								</div>
							`,
							showCloseButton: true,
							showCancelButton: true,
							cancelButtonText: 'إلغاء',
							confirmButtonText: 'تأكيد',
							confirmButtonColor: '#007cba',
							didOpen: () => {
								const labels = document.querySelectorAll('label');
								labels.forEach(label => {
									label.addEventListener('click', function() {
										labels.forEach(l => l.style.borderColor = '#ddd');
										this.style.borderColor = '#007cba';
									});
								});
								document.querySelector('input[name="attendance"]:checked').closest('label').style.borderColor = '#007cba';
							},
							preConfirm: () => {
								const attendance = document.querySelector('input[name="attendance"]:checked');
								if (!attendance) {
									Swal.showValidationMessage('يرجى اختيار حالة الحضور');
									return false;
								}
								return attendance.value;
							}
						}).then((attendanceResult) => {
							if (attendanceResult.isConfirmed) {
								var attendance = attendanceResult.value;
								doctorActions.push({ name: 'attendance', value: attendance });
								
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
									data: doctorActions,
									success: function(response) {
										if (response.success) {
											// Hide the complete session button after successful completion
											// Remove the form which contains the button
											var $doctorActionsWrapper = form.closest('.doctor-actions-wrapper');
											var isAiSession = $doctorActionsWrapper.data('is-ai-session') === 1 || $doctorActionsWrapper.data('is-ai-session') === '1';
											var sessionId = $doctorActionsWrapper.data('session-id');
											var clientId = $doctorActionsWrapper.data('client-id');
											
											form.remove();
											var successMessage = 'تم تحديد الجلسة كمكتملة بنجاح';
											if (attendance === 'no') {
												successMessage += ' وتم إرسال إشعار للإدارة بأن المريض لم يحضر';
											}
											Swal.fire({
												title: 'تم بنجاح!',
												text: successMessage,
												icon: 'success',
												confirmButtonText: 'حسناً'
											}).then(() => {
												// Show Roshtah button for AI sessions after confirmation
												if (isAiSession && sessionId && clientId) {
													// Check if Roshtah button doesn't already exist
													if ($doctorActionsWrapper.find('.snks-roshtah-request-btn').length === 0) {
														var rochtahButton = $('<button>', {
															class: 'snks-button snks-roshtah-request-btn',
															'data-session-id': sessionId,
															'data-client-id': clientId,
															css: {
																'margin-top': '10px',
																'background-color': '#28a745',
																'border-color': '#28a745'
															},
															text: 'إرسال لروشتا'
														});
														$doctorActionsWrapper.append(rochtahButton);
													}
												}
											});
										} else {
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
			}
			
			// Reinitialize checks after Jet popup is shown
			$(window).on('jet-popup/show-event/after-show', function(){
				debugLog('🎯 Jet popup shown - reinitializing session completion checks...');
				applyDisabledButtonStyles();
				initSessionCompletionCheck();
				// Reattach completion handler
				setTimeout(attachCompletionHandlerToButtons, 100);
			});
			
			// Reinitialize checks after Jet popup content is rendered
			$(window).on('jet-popup/render-content/render-custom-content', function(){
				debugLog('📄 Jet popup content rendered - reinitializing session completion checks...');
				applyDisabledButtonStyles();
				initSessionCompletionCheck();
				// Reattach completion handler
				setTimeout(attachCompletionHandlerToButtons, 500);
			});

			// Prevent ANY interaction with disabled buttons at the earliest possible moment
			// Handle disabled state check - but don't interfere with enabled button clicks
			$(document).on('mousedown mouseup click submit', '.doctor_actions .snks-complete-session-btn, .snks-send-message-btn, form.doctor_actions', function(e) {
				// Skip if this is a click on snks-complete-session-btn - let the dedicated handler take over
				if (e.type === 'click' && ($(this).hasClass('snks-complete-session-btn') || $(e.target).hasClass('snks-complete-session-btn'))) {
					// Don't interfere - let the dedicated click handler handle it
					return;
				}
				
				var $button = $(this);
				if ($(this).hasClass('snks-complete-session-btn') || $(this).hasClass('snks-send-message-btn')) {
					$button = $(this);
				} else {
					$button = $(this).find('.snks-complete-session-btn, .snks-send-message-btn');
				}
				
				if ($button.length && ($button.prop('disabled') || $button.attr('disabled') === 'disabled')) {
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
					debugLog('🛑 All events prevented - button is disabled');
					return false;
				}
			});
			
			// Prevent form submission - we handle it via AJAX in the click handler
			$(document).on('submit', 'form.doctor_actions', function(e) {
				var $button = $(this).find('.snks-complete-session-btn');
				// Always prevent default form submission - we handle it via AJAX
				e.preventDefault();
				e.stopImmediatePropagation();
				e.stopPropagation();
				if ($button.prop('disabled') || $button.attr('disabled')) {
					return false;
				}
				// If button is enabled, the click handler should have already handled it
				// But prevent form submission anyway
				return false;
			});
			
			// Attach completion handler on page load
			$(document).ready(function() {
				attachCompletionHandlerToButtons();
			});
				
				// Handle attendance confirmation button clicks
				$(document).on('click', '.snks-attendance-btn', function(e) {
					e.preventDefault();
					var sessionId = $(this).data('session-id');
					var clientId = $(this).data('client-id');
					var $button = $(this);
					
					
					if (!sessionId || !clientId) {
						console.error('❌ Missing session or client data');
						Swal.fire({
							title: 'خطأ!',
							text: 'بيانات الجلسة مفقودة',
							icon: 'error',
							confirmButtonText: 'حسناً'
						});
						return;
					}
					
					// Show attendance confirmation dialog
					Swal.fire({
						title: 'هل حضر المريض الجلسة؟',
						html: `
							<div style="text-align: right; direction: rtl;">
								<div style="margin: 20px 0;">
									<label style="display: block; margin-bottom: 15px; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: border-color 0.3s;">
										<input type="radio" name="attendance" value="yes" style="margin-left: 10px;" checked>
										<span style="font-size: 14px;">حضر المريض الجلسة وحصل عليها دون مشاكل.</span>
									</label>
									<label style="display: block; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: border-color 0.3s;">
										<input type="radio" name="attendance" value="no" style="margin-left: 10px;">
										<span style="font-size: 14px;">لم يحضر المريض رغم تواجدي في الموعد وبقائي لمدة ربع ساعة على الأقل في انتظاره.</span>
									</label>
								</div>
							</div>
						`,
						showCloseButton: true,
						confirmButtonText: 'تأكيد',
						confirmButtonColor: '#007cba',
						didOpen: () => {
							// Add click highlighting for radio labels
							const labels = document.querySelectorAll('label');
							labels.forEach(label => {
								label.addEventListener('click', function() {
									labels.forEach(l => l.style.borderColor = '#ddd');
									this.style.borderColor = '#007cba';
								});
							});
							// Highlight the checked one initially
							document.querySelector('input[name="attendance"]:checked').closest('label').style.borderColor = '#007cba';
						},
						preConfirm: () => {
							const attendance = document.querySelector('input[name="attendance"]:checked');
							if (!attendance) {
								Swal.showValidationMessage('يرجى اختيار حالة الحضور');
								return false;
							}
							return attendance.value;
						}
					}).then((attendanceResult) => {
						if (attendanceResult.isConfirmed) {
							// Send attendance status to backend
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								data: {
									action: 'update_session_attendance',
									session_id: sessionId,
									attendance: attendanceResult.value,
									nonce: '<?php echo esc_js( wp_create_nonce( "session_attendance_nonce" ) ); ?>'
								},
								success: function(attendanceResponse) {
									if (attendanceResponse.success) {
										Swal.fire({
											title: 'تم بنجاح!',
											text: 'تم تسجيل حالة الحضور وإرسال إشعار للإدارة',
											icon: 'success',
											confirmButtonText: 'حسناً'
										}).then(() => {
											// Hide the attendance button after successful update
											$button.hide();
										});
									} else {
										console.error('❌ Attendance update failed:', attendanceResponse.data);
										Swal.fire({
											title: 'خطأ!',
											text: attendanceResponse.data || 'حدث خطأ في تسجيل حالة الحضور',
											icon: 'error',
											confirmButtonText: 'حسناً'
										});
									}
								},
								error: function(xhr, status, error) {
									console.error('❌ AJAX error updating attendance:', error, xhr.responseText);
									Swal.fire({
										title: 'خطأ!',
										text: 'حدث خطأ في تسجيل حالة الحضور',
										icon: 'error',
										confirmButtonText: 'حسناً'
									});
								}
							});
						}
					});
				});
				
				// Handle send message button clicks
				$(document).on('click', '.snks-send-message-btn', function(e) {
					// Prevent action if button is disabled
					if ($(this).prop('disabled') || $(this).attr('disabled')) {
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
					
					e.preventDefault();
					var sessionId = $(this).data('session-id');
					var clientId = $(this).data('client-id');
					
					// Store original HTML for error recovery
					var originalHtml = `
						<div style="text-align: right; direction: rtl;">
							<div style="margin-bottom: 20px;">
								<label for="message_text" style="display: block; margin-bottom: 8px; font-weight: bold; color: #374151;">الرسالة:</label>
								<textarea id="message_text" style="width: 100%; height: 120px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; resize: vertical; font-family: inherit; transition: border-color 0.2s;" placeholder="اكتب رسالتك هنا..." onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
							</div>
							<div style="margin-bottom: 15px;">
								<label style="display: block; margin-bottom: 8px; font-weight: bold; color: #374151;">المرفقات (اختياري):</label>
								<div id="file-drop-zone" style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 30px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#6366f1'; this.style.background='#eef2ff'" onmouseout="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb'">
									<svg style="width: 48px; height: 48px; margin: 0 auto 12px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
									</svg>
									<p style="color: #6366f1; font-weight: 600; margin-bottom: 4px;">اضغط أو اسحب الملفات هنا</p>
									<p style="color: #6b7280; font-size: 13px;">صور، فيديوهات، أو مستندات (حتى 10 ملفات)</p>
									<input type="file" id="message_files" multiple accept="image/*,video/*,.pdf,.doc,.docx,.txt" style="display: none;">
								</div>
								<div id="file-preview" style="margin-top: 15px; display: none;"></div>
							</div>
						</div>
					`;
					
					// Function to initialize file handlers (reusable for error recovery)
					var selectedFiles = [];
					function initializeFileHandlers() {
						const dropZone = document.getElementById('file-drop-zone');
						const fileInput = document.getElementById('message_files');
						const filePreview = document.getElementById('file-preview');
						
						if (!dropZone || !fileInput) return;
						
						// Clear previous event listeners by cloning elements
						const newDropZone = dropZone.cloneNode(true);
						dropZone.parentNode.replaceChild(newDropZone, dropZone);
						const newFileInput = fileInput.cloneNode(true);
						fileInput.parentNode.replaceChild(newFileInput, fileInput);
						
						// Get new references
						const newDropZoneRef = document.getElementById('file-drop-zone');
						const newFileInputRef = document.getElementById('message_files');
						
						// Reset selected files
						selectedFiles = [];
						
						// Click to select files
						newDropZoneRef.addEventListener('click', () => newFileInputRef.click());
						
						// Drag and drop handlers
						newDropZoneRef.addEventListener('dragover', (e) => {
							e.preventDefault();
							newDropZoneRef.style.borderColor = '#6366f1';
							newDropZoneRef.style.background = '#eef2ff';
						});
						
						newDropZoneRef.addEventListener('dragleave', () => {
							newDropZoneRef.style.borderColor = '#d1d5db';
							newDropZoneRef.style.background = '#f9fafb';
						});
						
						newDropZoneRef.addEventListener('drop', (e) => {
							e.preventDefault();
							newDropZoneRef.style.borderColor = '#d1d5db';
							newDropZoneRef.style.background = '#f9fafb';
							handleFiles(e.dataTransfer.files);
						});
						
						newFileInputRef.addEventListener('change', (e) => {
							handleFiles(e.target.files);
						});
						
						function handleFiles(files) {
							selectedFiles = Array.from(files);
							displayFiles(selectedFiles);
						}
						
						function displayFiles(files) {
							const preview = document.getElementById('file-preview');
							if (!preview) return;
							
							if (files.length === 0) {
								preview.style.display = 'none';
								return;
							}
							
							preview.style.display = 'block';
							preview.innerHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">' + 
								files.map((file, index) => {
									const isImage = file.type.startsWith('image/');
									const fileUrl = isImage ? URL.createObjectURL(file) : '';
									const fileName = file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name;
									const fileSize = (file.size / 1024).toFixed(1) + ' KB';
									
									return `
										<div style="position: relative; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px; background: white; text-align: center;">
											${isImage ? 
												`<img src="${fileUrl}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; margin-bottom: 6px;">` :
												`<div style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 6px; margin: 0 auto 6px;">
													<svg style="width: 32px; height: 32px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
													</svg>
												</div>`
											}
											<p style="font-size: 11px; color: #374151; margin: 0; font-weight: 500;">${fileName}</p>
											<p style="font-size: 10px; color: #9ca3af; margin: 2px 0 0 0;">${fileSize}</p>
											<button onclick="removeFile(${index})" style="position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #ef4444; color: white; border: 2px solid white; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0;">×</button>
										</div>
									`;
								}).join('') + '</div>';
							
							// Make removeFile available globally
							window.removeFile = function(index) {
								selectedFiles.splice(index, 1);
								const dataTransfer = new DataTransfer();
								selectedFiles.forEach(file => dataTransfer.items.add(file));
								const fileInput = document.getElementById('message_files');
								if (fileInput) {
									fileInput.files = dataTransfer.files;
									displayFiles(selectedFiles);
								}
							};
						}
					}
					
					// Show message form with fancy file upload
					var messagePopup = Swal.fire({
						title: 'إرسال رسالة للمريض',
						html: originalHtml,
						showCloseButton: true,
						confirmButtonText: 'إرسال',
						confirmButtonColor: '#6366f1',
						showLoaderOnConfirm: false, // We'll handle loading manually
						allowOutsideClick: false, // Prevent closing by clicking outside
						allowEscapeKey: false, // Prevent closing with ESC key
						width: '600px',
						didOpen: () => {
							initializeFileHandlers();
						},
						preConfirm: () => {
							const message = document.getElementById('message_text').value.trim();
							const files = document.getElementById('message_files').files;
							
							if (!message && files.length === 0) {
								Swal.showValidationMessage('يرجى إدخال رسالة أو إرفاق ملف');
								return false;
							}
							
							// Show loading state with message
							Swal.showLoading();
							Swal.disableButtons();
							Swal.update({
								html: '<div style="text-align: center; padding: 20px;"><div class="swal2-loader"></div><p style="margin-top: 20px; font-size: 16px; color: #374151;">يرجى الإنتظار جاري رفع الملفات والإرسال...</p></div>',
								showConfirmButton: false,
								showCancelButton: false,
								showCloseButton: false
							});
							
							// Prepare form data with files
							var formData = new FormData();
							formData.append('action', 'send_session_message');
							formData.append('session_id', sessionId);
							formData.append('client_id', clientId);
							formData.append('message', message);
							formData.append('nonce', '<?php echo esc_html( wp_create_nonce( 'session_message_nonce' ) ); ?>');
							
							// Add files
							for (var i = 0; i < files.length; i++) {
								formData.append('attachments[]', files[i]);
							}
							
							// Return a promise that resolves when AJAX is complete
							return new Promise((resolve, reject) => {
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
									data: formData,
									processData: false,
									contentType: false,
									success: function(response) {
										if (response.success) {
											// Hide the send message button after successful send
											var $sendBtn = $('.snks-send-message-btn[data-session-id="' + sessionId + '"]');
											if ($sendBtn.length) {
												$sendBtn.hide();
											}
											// Resolve with success to close popup
											resolve(true);
										} else {
											// Reject to show error and keep popup open
											Swal.enableButtons();
											Swal.hideLoading();
											// Restore original form content
											Swal.update({
												html: originalHtml,
												showConfirmButton: true,
												showCancelButton: false,
												showCloseButton: true
											});
											// Re-initialize file handlers after restoring HTML
											setTimeout(function() {
												initializeFileHandlers();
											}, 100);
											Swal.showValidationMessage(response.data || 'حدث خطأ أثناء إرسال الرسالة');
											reject(new Error(response.data || 'حدث خطأ أثناء إرسال الرسالة'));
										}
									},
									error: function(xhr, status, error) {
										// Reject to show error and keep popup open
										Swal.enableButtons();
										Swal.hideLoading();
										// Restore original form content
										Swal.update({
											html: originalHtml,
											showConfirmButton: true,
											showCancelButton: false,
											showCloseButton: true
										});
										// Re-initialize file handlers after restoring HTML
										setTimeout(function() {
											initializeFileHandlers();
										}, 100);
										Swal.showValidationMessage('حدث خطأ أثناء إرسال الرسالة');
										reject(new Error('حدث خطأ أثناء إرسال الرسالة'));
									}
								});
							});
						}
					}).then((result) => {
						// Only show success message if we get here (popup will close automatically)
						if (result.value === true) {
							Swal.fire({
								title: 'تم بنجاح!',
								text: 'تم إرسال الرسالة للمريض',
								icon: 'success',
								confirmButtonText: 'حسناً'
							});
						}
					}).catch((error) => {
						// Error is already handled in preConfirm, popup stays open
						console.error('Error sending message:', error);
					});
				});
				
				// Handle Roshtah request button clicks (for therapists to request Roshtah for a patient)
				$(document).on('click', '.snks-roshtah-request-btn', function(e) {
					e.preventDefault();
					var sessionId = $(this).data('session-id');
					var clientId = $(this).data('client-id');
					var $button = $(this);
					
					// Store original HTML for form restoration
					var originalHtml = `
						<div style="text-align: right; direction: rtl;">
							<div style="margin-bottom: 15px;">
								<label for="initial_diagnosis" style="display: block; margin-bottom: 5px; font-weight: bold;">تشخيص المريض المبدئي حسب رؤيتك:</label>
								<textarea id="initial_diagnosis" style="width: 100%; height: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="اكتب التشخيص الأولي للمريض..."></textarea>
							</div>
							<div style="margin-bottom: 15px;">
								<label for="symptoms" style="display: block; margin-bottom: 5px; font-weight: bold;">الاعراض التي تعتقد انها بحاجه لادوية:</label>
								<textarea id="symptoms" style="width: 100%; height: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="اكتب الأعراض التي يعاني منها المريض..."></textarea>
							</div>
							<div style="margin-bottom: 15px;">
								<label for="reason_for_referral" style="display: block; margin-bottom: 5px; font-weight: bold;">سبب الإحالة للطبيب النفسي:</label>
								<textarea id="reason_for_referral" style="width: 100%; height: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="اشرح لماذا يحتاج المريض إلى استشارة طبيب نفسي..."></textarea>
							</div>
						</div>
					`;
					
					// Show diagnosis and symptoms form
					var rochtahPopup = Swal.fire({
						title: 'معلومات التشخيص والأعراض',
						html: originalHtml,
						showCloseButton: true,
						confirmButtonText: 'إرسال الطلب',
						confirmButtonColor: '#28a745',
						showLoaderOnConfirm: false, // We'll handle loading manually
						allowOutsideClick: false, // Prevent closing by clicking outside
						allowEscapeKey: false, // Prevent closing with ESC key
						preConfirm: () => {
							const initialDiagnosis = document.getElementById('initial_diagnosis').value.trim();
							const symptoms = document.getElementById('symptoms').value.trim();
							const reasonForReferral = document.getElementById('reason_for_referral').value.trim();
							
							if (!initialDiagnosis) {
								Swal.showValidationMessage('يرجى إدخال التشخيص الأولي');
								return false;
							}
							
							if (!symptoms) {
								Swal.showValidationMessage('يرجى إدخال الأعراض');
								return false;
							}
							
							if (!reasonForReferral) {
								Swal.showValidationMessage('يرجى إدخال سبب الإحالة');
								return false;
							}
							
							// Show loading state with custom message
							Swal.showLoading();
							Swal.disableButtons();
							Swal.update({
								title: 'يرجى الإنتظار جاري إرسال طلب روشتا...',
								html: '<div style="text-align: center; padding: 20px;"><div class="swal2-loader"></div><p style="margin-top: 20px; font-size: 16px; color: #374151;">يرجى الإنتظار جاري إرسال طلب روشتا...</p></div>',
								showConfirmButton: false,
								showCancelButton: false,
								showCloseButton: false
							});
							
							// Return a promise that resolves when AJAX is complete
							return new Promise((resolve, reject) => {
								// Get session and order details via AJAX first
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
									data: {
										action: 'get_session_details',
										session_id: sessionId,
										nonce: '<?php echo esc_html( wp_create_nonce( 'session_details_nonce' ) ); ?>'
									},
									success: function(sessionResponse) {
										if (sessionResponse.success) {
											// Send Roshta request with diagnosis data
											var rochtahNonce = '<?php echo esc_html( wp_create_nonce( 'rochtah_request_nonce' ) ); ?>';
											$.ajax({
												type: 'POST',
												url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
												data: {
													action: 'request_rochtah',
													session_id: sessionId,
													client_id: clientId,
													order_id: sessionResponse.data.order_id,
													initial_diagnosis: initialDiagnosis,
													symptoms: symptoms,
													reason_for_referral: reasonForReferral,
													nonce: rochtahNonce
												},
												success: function(rochtahResponse) {
													if (rochtahResponse.success) {
														// Hide the Roshtah button after successful request
														$button.hide();
														// Resolve with success to close popup
														resolve(true);
													} else {
														// Restore original popup content and show error
														Swal.update({
															title: 'معلومات التشخيص والأعراض',
															html: originalHtml,
															showConfirmButton: true,
															showCancelButton: false,
															showCloseButton: true
														});
														Swal.enableButtons();
														Swal.hideLoading();
														Swal.showValidationMessage(rochtahResponse.data || 'حدث خطأ أثناء إرسال طلب روشتا');
														reject(new Error(rochtahResponse.data || 'حدث خطأ أثناء إرسال طلب روشتا'));
													}
												},
												error: function(xhr, status, error) {
													// Restore original popup content and show error
													Swal.update({
														title: 'معلومات التشخيص والأعراض',
														html: originalHtml,
														showConfirmButton: true,
														showCancelButton: false,
														showCloseButton: true
													});
													Swal.enableButtons();
													Swal.hideLoading();
													Swal.showValidationMessage('حدث خطأ أثناء إرسال طلب روشتا');
													reject(new Error('حدث خطأ أثناء إرسال طلب روشتا'));
												}
											});
										} else {
											// Restore original popup content and show error
											Swal.update({
												title: 'معلومات التشخيص والأعراض',
												html: originalHtml,
												showConfirmButton: true,
												showCancelButton: false,
												showCloseButton: true
											});
											Swal.enableButtons();
											Swal.hideLoading();
											Swal.showValidationMessage('حدث خطأ في الحصول على معلومات الجلسة');
											reject(new Error('حدث خطأ في الحصول على معلومات الجلسة'));
										}
									},
									error: function(xhr, status, error) {
										// Restore original popup content and show error
										Swal.update({
											title: 'معلومات التشخيص والأعراض',
											html: originalHtml,
											showConfirmButton: true,
											showCancelButton: false,
											showCloseButton: true
										});
										Swal.enableButtons();
										Swal.hideLoading();
										Swal.showValidationMessage('حدث خطأ في الحصول على معلومات الجلسة');
										reject(new Error('حدث خطأ في الحصول على معلومات الجلسة'));
									}
								});
							});
						}
					}).then((result) => {
						// Only show success message if we get here (popup will close automatically)
						if (result.value === true) {
							Swal.fire({
								title: 'تم إرسال طلب روشتا!',
								text: 'سيتم إعلام المريض بطلب روشتا',
								icon: 'success',
								confirmButtonText: 'حسناً'
							});
						}
					}).catch((error) => {
						// Error is already handled in preConfirm, popup stays open
						console.error('Error sending rochtah request:', error);
					});
				});
				
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
