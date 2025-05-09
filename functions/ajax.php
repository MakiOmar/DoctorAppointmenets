<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

snks_require_all_files( SNKS_DIR . 'functions/ajax' );

/**
 * Logout ajax
 *
 * @return void
 */
function snks_logout() {
	// Verify nonce for security.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'snks_logout_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}

	if ( snks_is_doctor() ) {
		$redirect_url = '/doctor-login';
	} else {
		$redirect_url = '/login';
	}
	delete_transient( snks_form_data_transient_key() );
	// Log out the user and destroy session.
	wp_logout();
	wp_send_json_success( array( 'redirect_url' => home_url( $redirect_url ) ) );
}
add_action( 'wp_ajax_snks_logout', 'snks_logout' );
add_action( 'wp_ajax_nopriv_snks_logout', 'snks_logout' );

/**
 * Booking details change
 *
 * @return void
 */
function edit_patient_phone() {
	// Verify nonce for security.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edit_patient_phone' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$user_id = get_current_user_id();
	delete_transient( snks_form_data_transient_key() );
	wp_delete_user( $user_id );
	// Log out the user and destroy session.
	wp_logout();
	wp_send_json_success();
}
add_action( 'wp_ajax_edit_patient_phone', 'edit_patient_phone' );
