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
		wp_enqueue_style( 'slick', SNKS_URI . 'slick/slick.css', array(), time() );
		wp_enqueue_style( 'slick-theme', SNKS_URI . 'slick/slick-theme.css', array(), time() );
		wp_enqueue_script( 'slick', SNKS_URI . 'slick/slick.min.js', array( 'jquery' ), '4.6.13', true );
		wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array( 'jquery' ), time(), true );
	}
);

add_action(
	'wp_head',
	function () {
		//phpcs:disable
		if ( false === strpos( $_SERVER['REQUEST_URI'], '/org/' ) ) {
			return;
		}
		//phpcs:enable
		?>
		<!-- Meta Pixel Code -->
		<script>
			(function(f, b, e, v, n, t, s) {
				if (f.fbq) return;
				n = f.fbq = function() {
					n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
				};
				if (!f._fbq) f._fbq = n;
				n.push = n;
				n.loaded = !0;
				n.version = '2.0';
				n.queue = [];
				t = b.createElement(e);
				t.async = !0;
				t.src = v;
				s = b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t, s);
			})(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', '1256762418664304');
			fbq('track', 'PageView');
		</script>
		<noscript>
			<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1256762418664304&ev=PageView&noscript=1"/>
		</noscript>
		<!-- End Meta Pixel Code -->
		<?php
	},
	1
);
add_action(
	'wp_footer',
	function () {
		?>
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
			jQuery( document ).ready( function( $ ) {
				// Define the URL where you want to prompt the user
				const accountSettingUrlPath = '/account-setting';
				var confirmationMessage = "يرجى التأكد من حفظ الإعدادات، هل أنت متأكد؟";

				// Check if the current URL matches the account settings page
				if (window.location.pathname.includes(accountSettingUrlPath)) {
					
					var preventNavigation = false; // Flag to control when to prompt

					// Function to set preventNavigation to true when a form is changed
					function setFormChanged() {
						preventNavigation = true;
					}

					// Add event listeners for any interaction with form fields
					const confirmSave = document.querySelector('.snks-confirm');
					// Select all forms with the class 'jet-form-builder'
					var forms = document.querySelectorAll('.jet-form-builder');

					if (forms.length > 0) {
						forms.forEach(function (form) {
							// Add event listeners to all input, select, and textarea fields in each form
							form.querySelectorAll('input, select, textarea').forEach(function (field) {
								console.log('changed');
								field.addEventListener('input', setFormChanged);    // Text inputs
								field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
							});
						});
					}

					$(window).on('jet-popup/show-event/after-show', function(){
						var forms = document.querySelectorAll('.jet-form-builder');

						if (forms.length > 0) {
							forms.forEach(function (form) {
								// Add event listeners to all input, select, and textarea fields in each form
								form.querySelectorAll('input, select, textarea').forEach(function (field) {
									console.log('changed');
									field.addEventListener('input', setFormChanged);    // Text inputs
									field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
								});
							});
						}
					});

					if (confirmSave) {
						confirmSave.querySelectorAll('input, select, textarea').forEach(function (field) {
							field.addEventListener('input', setFormChanged);    // Text inputs
							field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
						});
					}

					// Prompt the user when trying to leave the page (refresh, close tab, etc.)
					window.addEventListener('beforeunload', function (e) {
						if (preventNavigation) {
							e.returnValue = confirmationMessage; // For most browsers
							return confirmationMessage;          // Some older browsers
						}
					});
					
					// Handle the case when the user clicks on a link within the page
					document.querySelectorAll('.popup-trigger').forEach(function (link) {
						link.addEventListener('click', function (e) {
							
						});
					});

					$( document ).on('click', '.jet-form-builder-repeater__new', function(){
						preventNavigation = true;
					});
				}
				$(window).on('jet-popup/show-event/after-show', function(){
					preventNavigation = false;
				});
				$( document ).on(
					'click',
					'.popup-trigger',
					function() {
						
						if (preventNavigation) {
							var confirmation = confirm(confirmationMessage);
							if (confirmation) {
								preventNavigation = false; // Allow navigation and turn off the beforeunload prompt
								$('.popup-trigger').removeClass('snks-active-popup');
								$(this).addClass('snks-active-popup');
								$(this).next('.trigger-popup').click();
							} else {
								$('.popup-trigger').removeClass('snks-active-popup');
							}
						} else if ( !preventNavigation && ! $(this).hasClass('snks-active-popup') ) {
							$('.popup-trigger').removeClass('snks-active-popup');
							$(this).addClass('snks-active-popup');
							$(this).next('.trigger-popup').click();
						}
					}
				);
				$(document).on("jet-form-builder/ajax/on-success", function(event, formData, response) {
					preventNavigation = false;
				});
				$(document).on(
					'click',
					'a',
					function(e) {
						if ( $(this).closest('#jet-theme-core-footer').length < 1 ) {
							return;
						}
						e.preventDefault();
					}
				);
				$('.jet-popup-target').on(
					'click',
					function() {
						if ( ($(this).closest('#jet-theme-core-footer').length < 1 && preventNavigation) || $(this).closest('#jet-theme-core-footer').length < 1 ) {
							return;
						}

						// Get the attached popup ID from the clicked item
						var attachedPopup = $(this).data('jet-popup');

						// Check if there is an open popup and its ID does not match the attached popup
						var openPopup = $('.jet-popup--show-state');
						if (openPopup.length && openPopup.attr('id') !== attachedPopup) {
							// Trigger the close button click on the currently open popup
							var closeButton = openPopup.find('.jet-popup__close-button');
							if (closeButton.length) {
								closeButton.click();
							}
						}

						// Slide #snks_account_settings to the right (off screen)
						$('#snks_account_settings').css({
							transform: 'translateX(100vw)', // Moves the element off-screen to the right
							display: 'block' // Ensure it's still visible (not hidden)
						});
					}
				);

				$('.snks-settings-tab a').on('click', function(event) {
					event.preventDefault();
				});

				$(document).on(
					'click',
					'.snks-settings-tab',
					function(){
						if (preventNavigation) {
							var confirmation = confirm(confirmationMessage);
							if ( confirmation) {
								var openPopup = $('.jet-popup--show-state');
								if (openPopup.length ) {
									// Trigger the close button click on the currently open popup
									var closeButton = openPopup.find('.jet-popup__close-button');
									if (closeButton.length) {
										closeButton.click();
									}
								}
								$('#snks_account_settings').css({
									transform: 'translateX(0)', // Moves the element back to its original position
									display: 'block' // Ensure it's visible
								});
								$('.popup-trigger').removeClass('snks-active-popup');
								preventNavigation = false;
							}
						} else {
							var openPopup = $('.jet-popup--show-state');
							if (openPopup.length ) {
								// Trigger the close button click on the currently open popup
								var closeButton = openPopup.find('.jet-popup__close-button');
								if (closeButton.length) {
									closeButton.click();
								}
							}
							$('#snks_account_settings').css({
								transform: 'translateX(0)', // Moves the element back to its original position
								display: 'block' // Ensure it's visible
							});
							$('.popup-trigger').removeClass('snks-active-popup');
						}
						

					}
				);
				$(document).on(
					'click',
					'.field-type-heading-field',
					function(){
						var parent = $(this).closest('.day-specific-form');
						$('.wp-block-columns-is-layout-flex', parent).toggle();
						$('.field-type-submit-field', parent).toggle();
					}
				);
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
			});
		</script>
		<?php
	},
	1
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery(document).ready(function($) {
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
					'a[href="/forget-password/"]',
					function(event) {
					event.preventDefault(); // Prevent the default link behavior

					// Add the 'processing' class to show the overlay
					$('.jet-form-builder').addClass('processing');

					// Get the selected login method (mobile or email)
					var loginWith = $('input[name="login_with"]:checked').val();
					var tempPhone = $('input[name="temp-phone"]').val();
					var username = $('#username').val();

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
							// Remove the 'processing' class to hide the overlay
							$('.jet-form-builder').removeClass('processing');
							Swal.fire({
								icon: 'success',
								title: 'تم',
								text: response.msg, // Assuming response.msg contains the message from the server
								confirmButtonText: 'غلق'
							});
						},
						error: function(xhr, status, error) {
							// Remove the 'processing' class to hide the overlay
							$('.jet-form-builder').removeClass('processing');
						}
					});
				});
			});

			jQuery( document ).ready( function( $ ) {
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
					'submit',
					'.doctor_actions',
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

						var doctorActions = $(this).serializeArray();
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'doctor_actions_nonce' ) ); ?>';
						doctorActions.push({ name: 'nonce', value: nonce });
						doctorActions.push({ name: 'action', value: 'session_doctor_actions' });
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: doctorActions,
							success: function(response) {
								location.reload();
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
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
		$redirect_after_meeting = 'session' === $timetable->purpose ? get_the_permalink( 682 ) : get_the_permalink( 1194 );
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
