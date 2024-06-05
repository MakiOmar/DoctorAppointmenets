<?php
/**
 * Session actions
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_session_doctor_actions', 'session_doctor_actions_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function session_doctor_actions_callback() {
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Doctor only.' );
	}
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'doctor_actions_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$attendees = explode( ',', $_req['attendees'] );
	unset( $_req['attendees'] );

	$session_id = $_req['session_id'];
	unset( $_req['session_id'] );
	unset( $_req['nonce'] );
	unset( $_req['action'] );
	foreach ( $attendees as $client ) {
		if ( isset( $_req[ 'has_attended_' . $client ] ) ) {
			if ( ! snks_get_session_actions( $session_id, $client ) ) {
				snks_insert_session_actions( $session_id, $client, $_req[ 'has_attended_' . $client ] );
			} else {
				snks_update_session_actions( $session_id, $client, $_req[ 'has_attended_' . $client ] );
			}
		}
	}
}

add_action( 'wp_ajax_session_attendance', 'session_attendance_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function session_attendance_callback() {
	if ( ! snks_is_patient() ) {
		wp_send_json_error( 'Patient only.' );
	}
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'session_attendance_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$session_action = snks_get_session_actions( absint( $_req['SessioID'] ), get_current_user_id() );

	if ( $session_action ) {
		$attendance = 'yes' === $session_action->attendance ? true : false;
	} else {
		$attendance = false;
	}

	wp_send_json( array( 'resp' => $attendance ) );
	die();
}
