<?php
/**
 * Meeting room ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_doctor_presence', 'doctor_presence_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function doctor_presence_callback() {

	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'doctor_presence_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$transient = get_transient( "doctor_has_joined_{$_request['roomID']}_{$_request['doctorID']}" );
	if ( ! $transient ) {
		$transient     = set_transient( "doctor_has_joined_{$_request['roomID']}_{$_request['doctorID']}", '1' );
		$session       = snks_get_timetable_by( 'ID', absint( $_request['roomID'] ) );
		$billing_phone = get_user_meta( $session->client_id, 'billing_phone', true );
		$message       = sprintf(
			'المعالج جاهز لبدء الجلسة،  اضغط هنا للدخول:%s',
			'www.jalsah.link'
		);
		if ( empty( $billing_phone ) ) {
			$user          = get_user_by( 'id', $session->client_id );
			$billing_phone = $user->user_login;
		}
		send_sms_via_whysms( $billing_phone, $message );
	}
	wp_send_json(
		array(
			'resp' => $transient,
		)
	);
	die;
}

add_action( 'wp_ajax_doctor_has_joind', 'doctor_has_joind_callback' );
/**
 * Check attendance
 *
 * @return void
 */
function doctor_has_joind_callback() {

	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'doctor_has_joind_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}

	wp_send_json(
		array(
			'resp' => get_transient( "doctor_has_joined_{$_request['roomID']}_{$_request['doctorID']}" ),
		)
	);
	die;
}
