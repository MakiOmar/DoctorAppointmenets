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
	}
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$('.attandance_type', $('.snks-booking-item')).css('right', 'calc(50% - ' + ($('.attandance_type', $('.snks-booking-item')).outerWidth( ) / 2 ) + 'px)');
				$('.snks-start-meeting').css('right', 'calc(50% - ' + ($('.snks-start-meeting').outerWidth( ) / 2 ) + 'px)');
				$('<span class="snks-switcher-text switcher-no">لا</span>').insertBefore('#allow_appointment_change');
				$('<span class="snks-switcher-text switcher-yes">نعم</span>').insertAfter('#allow_appointment_change');
				$(document).on(
					'click',
					'.jet-form-builder-message--error',
					function(){
						$(this).hide();
					}
				);
				$('.snks-count-down').each(
					function () {
						var countdownElement = $(this);
						console.log(countdownElement.text());
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
				$('body').height( $(window).height() );

				$('.doctor_actions').on(
					'submit',
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
				$( '.snks-cancel-appointment' ).on(
					'click',
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
	'wp_footer',
	function () {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$url_params = wp_unslash( $_GET );
		if ( ( ! snks_is_patient() ) || empty( $url_params['room_id'] ) ) {
			return;
		}
		$dashboard  = get_the_permalink( 682 );
		$session_id = $url_params['room_id'];
		?>
		<script>
			jQuery( document ).ready(
				function ( $ ) {
					const sessionID = <?php echo absint( $session_id ); ?>;
					// Perform nonce check.
					var nonce     = '<?php echo esc_html( wp_create_nonce( 'session_attendance_nonce' ) ); ?>';
					const dashboard = '<?php echo esc_url( $dashboard ); ?>';
					const data = {
							action    : 'session_attendance',
							SessioID  : sessionID,
							nonce     : nonce
					}
					setInterval(
						function() {
							// Send AJAX request.
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
								data: data,
								success: function(response) {
									if( response.resp ) {
										window.location.href = dashboard;
									}
								},
								error: function(xhr, status, error) {
									alert('Error:', error);
								}
							});
						},
						10000
					)
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
				$('#snks-insert_timetable').on(
					'click',
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
