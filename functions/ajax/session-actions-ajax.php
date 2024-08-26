<?php
/**
 * Session actions
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_appointment_action', 'appointment_action_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function appointment_action_callback() {
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Doctor only.' );
	}
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'appointment_action_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	if ( '.snks-postpon' === $_req['ele'] ) {
		foreach ( $_req['IDs'] as $data ) {
			snks_postpon_appointment( $data['ID'], $data['patientID'], $data['doctorID'], $data['date'] );
		}
	}

	if ( '.snks-delay' === $_req['ele'] ) {
		foreach ( $_req['IDs'] as $data ) {
			snks_delay_appointment( $data['patientID'], $data['doctorID'], $_req['delayBy'], $data['date'] );
		}
	}
	wp_send_json(
		array(
			'resp' => $_req,
		),
	);

	die();
}
