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
