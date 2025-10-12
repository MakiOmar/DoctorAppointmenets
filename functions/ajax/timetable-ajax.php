<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_action(
	'wp_ajax_new_patient',
	function () {
		$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'new_patient_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$patient_id  = $_request['patientId'];
		$booking_id  = $_request['bookingId'];
		$clients_ids = $_request['clientsIds'];
		$clients_ids = explode( ',', $clients_ids );

		$clients_ids[] = $patient_id;
		$clients_ids   = implode( ',', $clients_ids );
		global $wpdb;
		$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table_name,
			array(
				'client_id' => $clients_ids,
			),
			array(
				'ID' => absint( $booking_id ),
			)
		);
		wp_send_json(
			array(
				'resp' => $updated,
			)
		);
		die;
	}
);

add_action(
	'wp_ajax_end_session',
	function () {
		$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'end_session_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		global $wpdb;
		$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table_name,
			array(
				'session_status' => 'completed',
			),
			array(
				'ID'      => absint( $_request['sessionID'] ),
				'user_id' => absint( $_request['doctorID'] ),
			)
		);
		wp_send_json(
			array(
				'resp' => $updated,
			)
		);
		die;
	}
);

add_action(
	'wp_ajax_cancel_appointment',
	function () {
		if ( ! snks_is_doctor() ) {
			return;
		}
		$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'cancel_appointment_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$booking = snks_get_timetable_by( 'ID', absint( $_request['bookingID'] ) );
		if ( ! $booking || empty( $booking ) ) {
			wp_send_json_error( 'Not found!' );
		}
		global $wpdb;
		$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table_name,
			array(
				'session_status' => 'cancelled',
			),
			array(
				'ID' => absint( $_request['bookingID'] ),
			)
		);
		if ( $updated && $booking->order_id > 0 ) {
			$order = wc_get_order( absint( $booking->order_id ) );
			if ( $order ) {
				$order->update_status( 'cancelled' );
				$order->save();
			}
		}
		wp_send_json(
			array(
				'resp' => $updated,
			)
		);
		die;
	}
);
add_action( 'wp_ajax_update_timetable_markup', 'update_timetable_markup_callback' );
/**
 * Update timetable
 *
 * @return void
 */
function update_timetable_markup_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'update_timetable_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	wp_send_json(
		array()
	);
	die;
}


add_action( 'wp_ajax_delete_timetable', 'delete_timetable_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function delete_timetable_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'delete_timetable_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$id     = absint( $_request['targrtID'] );
	$delete = snks_delete_timetable( $id );
	wp_send_json(
		array(
			'resp' => $delete,
		)
	);
	die;
}
/**
 * Create custom timetable
 *
 * @return void
 */
function snks_create_custom_timetable() {
	//phpcs:disable
	$_req = wp_unslash( $_POST );
	//phpcs:enable
	// Get 30 days timetable.
	$timetables     = snks_get_preview_timetable( false, true );
	$hour           = gmdate( 'H:i:s', strtotime( $_req['app_hour'] ) ); // Selected hour.
	$periods        = array_map( 'absint', explode( '-', $_req['app_choosen_period'] ) ); // Chosen periods.
	$expected_hours = snks_expected_hours( $periods, $hour ); // Expected hours.

	$tos = array();
	if ( ! empty( $expected_hours ) ) {
		foreach ( $expected_hours as $expected_hour ) {
			$expected_hour_to = gmdate( 'H:i', strtotime( $expected_hour['to'] ) );
			$tos[]            = $expected_hour_to;
		}
	}

	$tos = array_values( array_unique( $tos ) );

	// Selected day timetables.
	$day_timetables = isset( $timetables[ $_req['day'] ] ) ? $timetables[ $_req['day'] ] : false;
	if ( ! $day_timetables ) {
		wp_send_json_error( array( 'message' => 'هناك خطأ ما!' ) );
	}

	$date_timetables = array();
	foreach ( $day_timetables as $timetable ) {
		$_date = gmdate( 'Y-m-d', strtotime( $timetable['date_time'] ) );
		if ( $_date === $_req['date'] ) {
			if ( 'both' !== $_req['app_attendance_type'] ) {
				if ( $_req['app_attendance_type'] !== $timetable['attendance_type'] ) {
					continue;
				}

				$date_timetables[] = $timetable;
			} else {
				$date_timetables[] = $timetable;
			}
		}
	}
	$grouped_by_start = snks_group_by( 'starts', $date_timetables );
	$filtered_data    = array();
	$base_hours       = array();
	foreach ( $grouped_by_start as $start_time => $sessions ) {
		foreach ( $sessions as $session ) {
			$filtered_data[ $start_time ][] = gmdate( 'H:i', strtotime( $session['ends'] ) );
			if ( ! isset( $base_hours[ $start_time ] ) ) {
				$base_hours[ $start_time ] = $session['base_hour'];
			}
		}
	}
	$starts             = array_unique( array_column( $date_timetables, 'starts' ) );
	$starts             = array_map(
		function ( $item ) {
			return gmdate( 'H:i', strtotime( $item ) );
		},
		$starts
	);
	$ends               = array_unique( array_column( $date_timetables, 'ends' ) );
	$ends               = array_map(
		function ( $item ) {
			return gmdate( 'H:i', strtotime( $item ) );
		},
		$ends
	);
	$conflicts_list     = array();
	$selected_hour_time = strtotime( '1970-01-01 ' . $_req['app_hour'] );
	foreach ( $starts as $start ) {
		$start_time_base = $base_hours[ gmdate( 'H:i:s', strtotime( $start ) ) ];
		$start_time      = strtotime( '1970-01-01 ' . $start );
		if ( $selected_hour_time === $start_time ) {
			$start = gmdate( 'H:i:s', strtotime( $start ) );
			$_ends = $filtered_data[ $start ];
			foreach ( $tos as $to ) {
				if ( in_array( $to, $_ends, true ) ) {
					$conflicts_list[] = $to;
				}
			}
		} elseif ( $selected_hour_time < $start_time ) {

			foreach ( $tos as $to ) {
				$to_time = strtotime( '1970-01-01 ' . $to );
				// We need to check against base hour, but how.
				// Base hour for $start_time should not equal to $hour.
				if ( $to_time > $start_time && $start_time_base !== $hour ) {
					$conflicts_list[] = $to;
				}
			}
		}
	}
	$conflicts_list = array_unique( $conflicts_list );

	if ( ! empty( $conflicts_list ) ) {
		$conflicts_list = array_map(
			function ( $item ) {
				$localized = snks_localize_time( gmdate( 'h:i a', strtotime( $item ) ) );
				return $localized;
			},
			$conflicts_list
		);

		wp_send_json_error( array( 'message' => 'عفواً لايمكن إدخال الموعد! لديك تداخل هنا: ' . implode( ', ', $conflicts_list ) ) );
	}

	// No conflicts, save the timetable.
	$data = array();
	foreach ( $expected_hours as $expected_hour ) {
		$date_time = DateTime::createFromFormat( 'Y-m-d h:i a', $_req['date'] . ' ' . gmdate( 'h:i a', strtotime( $expected_hour['from'] ) ) );
		if ( $date_time ) {
			$date_time = $date_time->format( 'Y-m-d h:i a' );
		}
		$base = array(
			'user_id'         => snks_get_settings_doctor_id(),
			'session_status'  => 'waiting',
			'day'             => sanitize_text_field( $_req['day'] ),
			'base_hour'       => sanitize_text_field( $_req['app_hour'] ),
			'period'          => sanitize_text_field( $expected_hour['min'] ),
			'date_time'       => $date_time,
			'date'            => $_req['date'],
			'starts'          => gmdate( 'H:i:s', strtotime( $expected_hour['from'] ) ),
			'ends'            => gmdate( 'H:i:s', strtotime( $expected_hour['to'] ) ),
			'clinic'          => sanitize_text_field( $_req['app_clinic'] ),
			'attendance_type' => sanitize_text_field( $_req['app_attendance_type'] ),
		);
		if ( 'both' !== $_req['app_attendance_type'] ) {
			$data[ sanitize_text_field( $_req['day'] ) ][] = $base;
		} else {
			$base['attendance_type']                       = 'online';
			$data[ sanitize_text_field( $_req['day'] ) ][] = $base;

			$base['attendance_type']                       = 'offline';
			$data[ sanitize_text_field( $_req['day'] ) ][] = $base;
		}
		$inserting = $base;
		unset( $inserting['date'] );
		snks_insert_timetable( $inserting );
	}

	// Update the timetable data.
	$preview_timetables                 = snks_get_preview_timetable();
	$preview_timetables[ $_req['day'] ] = array_merge( $preview_timetables[ $_req['day'] ], $data[ $_req['day'] ] );
	snks_set_preview_timetable( $preview_timetables );

	wp_send_json_success( array( 'message' => 'Timetable successfully created.' ) );
}
add_action( 'wp_ajax_create_custom_timetable', 'snks_create_custom_timetable' );
add_action( 'wp_ajax_nopriv_create_custom_timetable', 'snks_create_custom_timetable' );

/**
 * Update session attendance after completion
 */
add_action( 'wp_ajax_update_session_attendance', 'snks_update_session_attendance' );

function snks_update_session_attendance() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'session_attendance_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a doctor
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Access denied. Only doctors can perform this action.' );
	}
	
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	$attendance = isset( $_POST['attendance'] ) ? sanitize_text_field( $_POST['attendance'] ) : '';
	
	if ( ! $session_id || ! in_array( $attendance, array( 'yes', 'no' ) ) ) {
		wp_send_json_error( 'Missing or invalid data.' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	$actions_table = $wpdb->prefix . 'snks_sessions_actions';
	
	// Get session details
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE ID = %d",
		$session_id
	) );
	
	if ( ! $session ) {
		wp_send_json_error( 'Session not found.' );
	}
	
	// Check if the current user is the doctor assigned to this session
	if ( $session->user_id != get_current_user_id() ) {
		wp_send_json_error( 'Access denied. You can only update your own sessions.' );
	}
	
	// Update attendance in sessions_actions table
	$attendee_ids = explode( ',', $session->client_id );
	foreach ( $attendee_ids as $attendee_id ) {
		$attendee_id = absint( $attendee_id );
		if ( $attendee_id > 0 ) {
			// Check if record exists
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$actions_table} WHERE session_id = %d AND user_id = %d",
				$session_id,
				$attendee_id
			) );
			
			if ( $existing ) {
				// Update existing record
				$wpdb->update(
					$actions_table,
					array( 'attendance' => $attendance ),
					array( 'session_id' => $session_id, 'user_id' => $attendee_id ),
					array( '%s' ),
					array( '%d', '%d' )
				);
			} else {
				// Insert new record
				snks_insert_session_actions( $session_id, $attendee_id, $attendance );
			}
		}
	}
	
	// Send email to admin
	$admin_email = get_option( 'admin_email' );
	$therapist = get_userdata( $session->user_id );
	$patient = get_userdata( $attendee_id );
	$attendance_status = $attendance === 'yes' ? 'حضر المريض' : 'لم يحضر المريض';
	
	$subject = 'تحديث حالة حضور الجلسة #' . $session_id;
	$message = "
	<div dir='rtl' style='font-family: Arial, sans-serif;'>
		<h2>تفاصيل الجلسة</h2>
		<p><strong>رقم الجلسة:</strong> {$session->ID}</p>
		<p><strong>المعالج:</strong> {$therapist->display_name} (ID: {$session->user_id})</p>
		<p><strong>المريض:</strong> {$patient->display_name} (ID: {$attendee_id})</p>
		<p><strong>تاريخ الجلسة:</strong> " . gmdate( 'Y-m-d', strtotime( $session->date_time ) ) . "</p>
		<p><strong>وقت الجلسة:</strong> {$session->starts} - {$session->ends}</p>
		<p><strong>المدة:</strong> {$session->period} دقيقة</p>
		<p><strong>نوع الحضور:</strong> {$session->attendance_type}</p>
		<hr>
		<p><strong>حالة الحضور:</strong> <span style='color: " . ( $attendance === 'yes' ? '#28a745' : '#dc3545' ) . ";'>{$attendance_status}</span></p>
	</div>
	";
	
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $admin_email, $subject, $message, $headers );
	
	wp_send_json_success( array(
		'message' => 'تم تحديث حالة الحضور وإرسال إشعار للإدارة',
	) );
}

/**
 * Get session details (for Roshtah requests)
 */
add_action( 'wp_ajax_get_session_details', 'snks_get_session_details_ajax' );

function snks_get_session_details_ajax() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'session_details_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a doctor
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Access denied. Only doctors can perform this action.' );
	}
	
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	
	if ( ! $session_id ) {
		wp_send_json_error( 'Missing session ID.' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get session details
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE ID = %d",
		$session_id
	) );
	
	if ( ! $session ) {
		wp_send_json_error( 'Session not found.' );
	}
	
	// Check if the current user is the doctor assigned to this session
	if ( $session->user_id != get_current_user_id() ) {
		wp_send_json_error( 'Access denied. You can only access your own sessions.' );
	}
	
	wp_send_json_success( array(
		'session_id' => $session->ID,
		'client_id'  => $session->client_id,
		'order_id'   => $session->order_id,
	) );
}

/**
 * Handle doctor actions form submission (mark session as completed)
 */
add_action( 'wp_ajax_session_doctor_actions', 'snks_handle_session_doctor_actions' );

function snks_handle_session_doctor_actions() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'doctor_actions_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a doctor
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Access denied. Only doctors can perform this action.' );
	}
	
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	$attendees = isset( $_POST['attendees'] ) ? sanitize_text_field( $_POST['attendees'] ) : '';
	
	if ( ! $session_id || ! $attendees ) {
		wp_send_json_error( 'Missing required data.' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get session details
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE ID = %d",
		$session_id
	) );
	
	if ( ! $session ) {
		wp_send_json_error( 'Session not found.' );
	}
	
	// Check if the current user is the doctor assigned to this session
	if ( $session->user_id != get_current_user_id() ) {
		wp_send_json_error( 'Access denied. You can only mark your own sessions as completed.' );
	}
	
	// Update session status to completed
	$update_result = $wpdb->update(
		$table_name,
		array(
			'session_status' => 'completed',
		),
		array(
			'ID' => $session_id,
		),
		array( '%s' ),
		array( '%d' )
	);
	
	if ( $update_result === false ) {
		wp_send_json_error( 'Failed to update session status.' );
	}
	
	// Don't add session action records here - will be added after attendance confirmation
	// This allows the therapist to specify whether patient attended or not
	
	// Check if this is an AI session and trigger profit calculation
	if ( snks_is_ai_session( $session_id ) ) {
		$profit_result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $profit_result['success'] ) {
			// Send notification
			snks_ai_session_completion_notification( $session_id, $profit_result );
			
			// For AI sessions, don't return a success message - go straight to Roshta prompt
			wp_send_json_success( array(
				'session_id' => $session_id,
				'client_id' => $session->client_id,
				'order_id' => $session->order_id,
				'is_ai_session' => true,
				'profit_transferred' => true,
				'transaction_id' => $profit_result['transaction_id'],
				'profit_amount' => $profit_result['profit_amount']
			) );
		} else {
			// For AI sessions with profit transfer failure, still go to Roshta prompt
			wp_send_json_success( array(
				'session_id' => $session_id,
				'client_id' => $session->client_id,
				'order_id' => $session->order_id,
				'is_ai_session' => true,
				'profit_transferred' => false,
				'profit_error' => $profit_result['message']
			) );
		}
	}
	
	// Check if this is an AI session using multiple methods
	$is_ai_session = false;
	
	// Method 1: Check session settings for ai_booking
	if ( strpos( $session->settings, 'ai_booking' ) !== false ) {
		$is_ai_session = true;
	}
	
	// Method 2: Check order meta
	if ( ! $is_ai_session && $session->order_id ) {
		$order = wc_get_order( $session->order_id );
		if ( $order ) {
			$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
			$is_ai_session_meta = $order->get_meta( 'is_ai_session' );
			if ( $from_jalsah_ai || $is_ai_session_meta ) {
				$is_ai_session = true;
			}
		}
	}
	
	// Return success with session info
	wp_send_json_success( array( 
		'message' => 'Session marked as completed successfully.',
		'session_id' => $session_id,
		'client_id' => $session->client_id,
		'order_id' => $session->order_id,
		'is_ai_session' => $is_ai_session,
		'session_settings' => $session->settings // For debugging
	) );
}

/**
 * Handle Roshta request after session completion
 */
add_action( 'wp_ajax_request_rochtah', 'snks_handle_session_rochtah_request' );

function snks_handle_session_rochtah_request() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rochtah_request_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a doctor
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Access denied. Only doctors can perform this action.' );
	}
	
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	$client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$initial_diagnosis = isset( $_POST['initial_diagnosis'] ) ? sanitize_textarea_field( $_POST['initial_diagnosis'] ) : '';
	$symptoms = isset( $_POST['symptoms'] ) ? sanitize_textarea_field( $_POST['symptoms'] ) : '';
	$reason_for_referral = isset( $_POST['reason_for_referral'] ) ? sanitize_textarea_field( $_POST['reason_for_referral'] ) : '';
	
	if ( ! $session_id || ! $client_id || ! $order_id ) {
		wp_send_json_error( 'Missing required data.' );
	}
	
	if ( empty( $initial_diagnosis ) || empty( $symptoms ) || empty( $reason_for_referral ) ) {
		wp_send_json_error( 'Initial diagnosis, symptoms, and reason for referral are required.' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get session details
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE ID = %d AND user_id = %d",
		$session_id, get_current_user_id()
	) );
	
	if ( ! $session ) {
		wp_send_json_error( 'Session not found or access denied.' );
	}
	
	// Check if session is completed
	if ( $session->session_status !== 'completed' ) {
		wp_send_json_error( 'Session must be completed before requesting Roshta.' );
	}
	
	// Check if Roshta already requested for this session
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE session_id = %d AND status IN ('pending', 'confirmed')",
		$session_id
	) );
	
	if ( $existing_booking ) {
		wp_send_json_error( 'Roshta already requested for this session.' );
	}
	
	// Create Roshta booking record
	$insert_result = $wpdb->insert(
		$rochtah_bookings_table,
		array(
			'session_id' => $session_id,
			'patient_id' => $client_id,
			'therapist_id' => get_current_user_id(),
			'initial_diagnosis' => $initial_diagnosis,
			'symptoms' => $symptoms,
			'reason_for_referral' => $reason_for_referral,
			'booking_date' => current_time( 'Y-m-d' ),
			'booking_time' => current_time( 'H:i:s' ),
			'status' => 'pending',
			'created_at' => current_time( 'mysql' )
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	
	if ( $insert_result === false ) {
		wp_send_json_error( 'Failed to create Roshta request.' );
	}
	
	$rochtah_booking_id = $wpdb->insert_id;
	
	// Mark order as having Roshta requested
	update_post_meta( $order_id, '_ai_prescription_requested', 'true' );
	
	wp_send_json_success( array(
		'message' => 'Roshta request created successfully.',
		'rochtah_booking_id' => $rochtah_booking_id
	) );
}

/**
 * Get available dates for Roshta doctor
 */
add_action( 'wp_ajax_get_rochtah_available_dates', 'snks_get_session_rochtah_available_dates' );

function snks_get_session_rochtah_available_dates() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rochtah_dates_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'User not logged in.' );
	}
	
	$request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
	
	if ( ! $request_id ) {
		wp_send_json_error( 'Missing request ID.' );
	}
	
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	// Get the Roshta request
	$request = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d AND patient_id = %d",
		$request_id, get_current_user_id()
	) );
	
	if ( ! $request ) {
		wp_send_json_error( 'Request not found or access denied.' );
	}
	
	// Get Roshta doctor (user with role 'rochtah_doctor')
	$rochtah_doctors = get_users( array(
		'role' => 'rochtah_doctor',
		'number' => 1
	) );
	
	if ( empty( $rochtah_doctors ) ) {
		wp_send_json_error( 'No Roshta doctor available.' );
	}
	
	$rochtah_doctor = $rochtah_doctors[0];
	
	// Get available dates for the next 30 days
	$available_dates = array();
	$current_date = current_time( 'Y-m-d' );
	
	for ( $i = 1; $i <= 30; $i++ ) {
		$check_date = date( 'Y-m-d', strtotime( $current_date . ' +' . $i . ' days' ) );
		
		// Check if the doctor has any available slots on this date
		$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
		$slots = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name 
			 WHERE user_id = %d 
			 AND DATE(date_time) = %s 
			 AND (client_id = 0 OR client_id IS NULL)
			 AND session_status = 'open'
			 AND settings NOT LIKE '%ai_booking:booked%'
			 AND settings NOT LIKE '%ai_booking:rescheduled_old_slot%'
			 ORDER BY starts ASC",
			$rochtah_doctor->ID, $check_date
		) );
		
		if ( ! empty( $slots ) ) {
			$available_dates[] = array(
				'date' => $check_date,
				'formatted_date' => date( 'l, F j, Y', strtotime( $check_date ) )
			);
		}
	}
	
	wp_send_json_success( array(
		'available_dates' => $available_dates
	) );
}

/**
 * Get available time slots for Roshta doctor on specific date
 */
add_action( 'wp_ajax_get_rochtah_time_slots', 'snks_get_session_rochtah_time_slots' );

function snks_get_session_rochtah_time_slots() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rochtah_slots_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'User not logged in.' );
	}
	
	$request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
	$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
	
	if ( ! $request_id || ! $date ) {
		wp_send_json_error( 'Missing required data.' );
	}
	
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	// Get the Roshta request
	$request = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d AND patient_id = %d",
		$request_id, get_current_user_id()
	) );
	
	if ( ! $request ) {
		wp_send_json_error( 'Request not found or access denied.' );
	}
	
	// Get Roshta doctor
	$rochtah_doctors = get_users( array(
		'role' => 'rochtah_doctor',
		'number' => 1
	) );
	
	if ( empty( $rochtah_doctors ) ) {
		wp_send_json_error( 'No Roshta doctor available.' );
	}
	
	$rochtah_doctor = $rochtah_doctors[0];
	
	// Get available slots for the selected date
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	$slots = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $table_name 
		 WHERE user_id = %d 
		 AND DATE(date_time) = %s 
		 AND (client_id = 0 OR client_id IS NULL)
		 AND session_status = 'open'
		 AND settings NOT LIKE '%ai_booking:booked%'
		 AND settings NOT LIKE '%ai_booking:rescheduled_old_slot%'
		 ORDER BY starts ASC",
		$rochtah_doctor->ID, $date
	) );
	
	$available_slots = array();
	$current_time = current_time('H:i:s');
	$is_today = ($date === current_time('Y-m-d'));
	
	foreach ( $slots as $slot ) {
		// Skip past slots for today
		if ( $is_today && $slot->starts <= $current_time ) {
			continue;
		}
		
		$available_slots[] = array(
			'slot_id' => $slot->ID,
			'time' => gmdate( 'h:i a', strtotime( $slot->starts ) ) . ' - ' . gmdate( 'h:i a', strtotime( $slot->ends ) )
		);
	}
	
	wp_send_json_success( array(
		'available_slots' => $available_slots
	) );
}

/**
 * Book Roshta appointment
 */
add_action( 'wp_ajax_book_rochtah_appointment', 'snks_book_session_rochtah_appointment' );

function snks_book_session_rochtah_appointment() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rochtah_booking_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'User not logged in.' );
	}
	
	$request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
	$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
	$slot_id = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
	
	if ( ! $request_id || ! $date || ! $slot_id ) {
		wp_send_json_error( 'Missing required data.' );
	}
	
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get the Roshta request
	$request = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d AND patient_id = %d AND status = 'pending'",
		$request_id, get_current_user_id()
	) );
	
	if ( ! $request ) {
		wp_send_json_error( 'Request not found or access denied.' );
	}
	
	// Get the slot
	$slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE ID = %d AND (client_id = 0 OR client_id IS NULL)",
		$slot_id
	) );
	
	if ( ! $slot ) {
		wp_send_json_error( 'Slot not available.' );
	}
	
	// Check if user already has a Roshta appointment
	$existing_appointment = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE patient_id = %d AND status = 'confirmed'",
		get_current_user_id()
	) );
	
	if ( $existing_appointment ) {
		wp_send_json_error( 'You already have a Roshta appointment booked.' );
	}
	
	// Book the slot
	$update_result = $wpdb->update(
		$table_name,
		array(
			'client_id' => get_current_user_id(),
			'settings' => $slot->settings . 'ai_booking:booked'
		),
		array(
			'ID' => $slot_id
		),
		array( '%d', '%s' ),
		array( '%d' )
	);
	
	if ( $update_result === false ) {
		wp_send_json_error( 'Failed to book appointment.' );
	}
	
	// Update Roshta request status
	$update_request = $wpdb->update(
		$rochtah_bookings_table,
		array(
			'status' => 'confirmed',
			'appointment_id' => $slot_id,
			'updated_at' => current_time( 'mysql' )
		),
		array(
			'id' => $request_id
		),
		array( '%s', '%d', '%s' ),
		array( '%d' )
	);
	
	if ( $update_request === false ) {
		wp_send_json_error( 'Failed to update request status.' );
	}
	
	wp_send_json_success( array(
		'message' => 'تم حجز موعد روشتا بنجاح! سيتم إعلامك بموعد الجلسة.',
		'appointment_id' => $slot_id,
		'appointment_date' => $date,
		'appointment_time' => gmdate( 'h:i a', strtotime( $slot->starts ) )
	) );
}
