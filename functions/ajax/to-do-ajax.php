<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_update_to_do', 'update_to_do_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function update_to_do_callback() {
	if ( ! snks_is_patient() ) {
		wp_send_json_error( 'Patient only.' );
	}
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'update_to_do_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$user_id = get_current_user_id();
	unset( $_request['action'] );
	foreach ( $_request as $day => $to_do ) {
		$date_exists = snks_get_records_by_user_case_date( $user_id, 0, $day );
		if ( $date_exists ) {
			snks_update_to_do( $user_id, 0, $day, $to_do );
		} else {
			snks_insert_to_do( $user_id, 0, $day, $to_do );
		}
	}
}

add_action( 'wp_ajax_fetch_to_do', 'fetch_to_do_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function fetch_to_do_callback() {
	if ( ! snks_is_patient() ) {
		wp_send_json_error( 'Patient only.' );
	}
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'fetch_to_do_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$user_id = get_current_user_id();
	$to_do   = snks_get_records_by_user_case_date( $user_id, 0, $_request['slectedDay'] );
	if ( ! empty( $to_do ) ) {
		wp_send_json(
			array(
				'toDo' => $to_do[0]->to_dos,
			),
			200,
			JSON_UNESCAPED_UNICODE
		);

		die();
	}
}
