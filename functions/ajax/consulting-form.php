<?php
/**
 * Consulting form ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_fetch_start_times', 'fetch_start_times_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function fetch_start_times_callback() {

	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'fetch_start_times_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$attendance_type = sanitize_text_field( $_request['attendanceType'] );

	$date       = sanitize_text_field( $_request['slectedDay'] );
	$user_id    = sanitize_text_field( $_request['userID'] );
	$period     = sanitize_text_field( $_request['period'] );
	$availables = snks_user_appointments_by_date_period( $user_id, $date, $period );
	$html       = snks_render_consulting_hours( $availables, $attendance_type, $user_id );
	wp_send_json(
		array(
			'resp' => $html,
		)
	);
	die;
}

add_action( 'wp_ajax_get_booking_form', 'get_booking_form_callback' );

/**
 * Get booking form
 *
 * @return void
 */
function get_booking_form_callback() {
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'get_booking_form_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$settings               = snks_doctor_settings( absint( $_req['doctor_id'] ) );
	$doctor_attendance_type = $settings['attendance_type'];
	if ( 'online' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'online', 'both' ), true ) ) {
		echo '<p>عفواَ! الحجز أونلاين غير متاح حالياً</p>';
		die;
	}

	if ( 'offline' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'offline', 'both' ), true ) ) {
		echo '';
		die;
	}
	//phpcs:disable
	echo snks_generate_consulting_form( $_req['doctor_id'], $_req['period'], $_req['price'], $_req['attendanceType'] );
	//phpcs:enable
	die();
}

add_action( 'wp_ajax_get_periods', 'get_periods_callback' );

/**
 * Get booking form
 *
 * @return void
 */
function get_periods_callback() {
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'get_periods_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$settings               = snks_doctor_settings( absint( $_req['doctor_id'] ) );
	$doctor_attendance_type = $settings['attendance_type'];
	if ( 'online' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'online', 'both' ), true ) ) {
		echo '<p>عفواَ! الحجز أونلاين غير متاح حالياً</p>';
		die;
	}

	if ( 'offline' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'offline', 'both' ), true ) ) {
		echo wp_kses_post( snks_render_doctor_clinics( $_req['doctor_id'] ) );
		die;
	}
	//phpcs:disable
	echo snks_periods_filter( $_req['doctor_id'] );
	//phpcs:enable
	die();
}
