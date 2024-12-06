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
			$date_timetables[] = $timetable;
		}
	}

	$starts = array_unique( array_column( $date_timetables, 'starts' ) );
	$starts = array_map(
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
		$start_time = strtotime( '1970-01-01 ' . $start );
		if ( $selected_hour_time === $start_time ) {
			$conflicts_list[] = $start;
		} elseif ( $selected_hour_time < $start_time ) {
			foreach ( $tos as $to ) {
				$to_time = strtotime( '1970-01-01 ' . $to );
				if ( $to_time > $start_time ) {
					$conflicts_list[] = $to;
				}
			}
		} elseif ( $selected_hour_time > $start_time ) {
			foreach ( $ends as $end ) {
				$end_time = strtotime( '1970-01-01 ' . $end );
				if ( $end_time > $selected_hour_time ) {
					$conflicts_list[] = $end;
				}
			}
		}
	}

	if ( ! empty( $conflicts_list ) ) {
		$conflicts_list = array_map(
			function ( $item ) {
				return gmdate( 'h:i a', strtotime( $item ) );
			},
			$conflicts_list
		);

		wp_send_json_error( array( 'message' => 'عفواً لايمكن إدخال الموعد! لديك تداخل هنا: ' . snks_localized_time( implode( ', ', $conflicts_list ) ) ) );
	}

	// No conflicts, save the timetable.
	$data = array();
	foreach ( $expected_hours as $expected_hour ) {
		$date_time = DateTime::createFromFormat( 'Y-m-d h:i a', $_req['date'] . ' ' . gmdate( 'h:i a', strtotime( $_req['app_hour'] ) ) );
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
	}

	// Update the timetable data.
	$preview_timetables                 = snks_get_preview_timetable();
	$preview_timetables[ $_req['day'] ] = array_merge( $preview_timetables[ $_req['day'] ], $data[ $_req['day'] ] );
	snks_set_preview_timetable( $preview_timetables );

	wp_send_json_success( array( 'message' => 'Timetable successfully created.' ) );
}
add_action( 'wp_ajax_create_custom_timetable', 'snks_create_custom_timetable' );
add_action( 'wp_ajax_nopriv_create_custom_timetable', 'snks_create_custom_timetable' );
