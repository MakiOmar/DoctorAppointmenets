<?php
/**
 * Consulting form ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

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
	echo snks_generate_consulting_form( $_req['doctor_id'], $_req['period'], $_req['price'] );
    die();
}
