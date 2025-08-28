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
	
	// Add session action records for all attendees (default to 'yes' attendance)
	$attendee_ids = explode( ',', $attendees );
	foreach ( $attendee_ids as $attendee_id ) {
		$attendee_id = absint( $attendee_id );
		if ( $attendee_id > 0 ) {
			snks_insert_session_actions( $session_id, $attendee_id, 'yes' );
		}
	}
	
	// Check if this is an AI session and trigger profit calculation
	if ( snks_is_ai_session( $session_id ) ) {
		$profit_result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $profit_result['success'] ) {
			// Send notification
			snks_ai_session_completion_notification( $session_id, $profit_result );
			
			wp_send_json_success( array(
				'message' => 'Session marked as completed and profit transferred successfully.',
				'transaction_id' => $profit_result['transaction_id'],
				'profit_amount' => $profit_result['profit_amount']
			) );
		} else {
			wp_send_json_success( array(
				'message' => 'Session marked as completed but profit transfer failed: ' . $profit_result['message'],
				'profit_error' => $profit_result['message']
			) );
		}
	}
	
	wp_send_json_success( array( 'message' => 'Session marked as completed successfully.' ) );
}
