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
		wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array( 'jquery' ), null, true );
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
		<script>
			jQuery( document ).ready( function( $ ) {
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
							alert(response.msg); // Example response handling
						},
						error: function(xhr, status, error) {
							// Remove the 'processing' class to hide the overlay
							$('.jet-form-builder').removeClass('processing');

							//alert('Error: ' + error);
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
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
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
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
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
								alert('Error:', error);
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
						if ( confirm( 'هل أنت متأكد؟' ) !== true ) {
							return;
						}
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
		
		// Define the URL where you want to prompt the user
		const accountSettingUrlPath = '/account-setting';
		var confirmationMessage = "يرجى التأكد من حفظ الإعدادات، هل أنت متأكد؟";

		// Check if the current URL matches the account settings page
		if (window.location.pathname.includes(accountSettingUrlPath)) {
			
			let preventNavigation = false; // Flag to control when to prompt

			// Function to set preventNavigation to true when a form is changed
			function setFormChanged() {
				preventNavigation = true;
			}

			// Add event listeners for any interaction with form fields
			const form = document.querySelector('.jet-form-builder');
			if (form) {
				form.querySelectorAll('input, select, textarea').forEach(function (field) {
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
			document.querySelectorAll('a').forEach(function (link) {
				link.addEventListener('click', function (e) {
					if (preventNavigation) {
						var confirmation = confirm(confirmationMessage);
						if (!confirmation) {
							e.preventDefault(); // Stop the link navigation if the user cancels
						} else {
							preventNavigation = false; // Allow navigation and turn off the beforeunload prompt
						}
					}
				});
			});
		}
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
