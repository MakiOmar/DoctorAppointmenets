<?php
/**
 * Roles
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Send SMS using WhySMS API.
 *
 * @param string $recipient The recipient's phone number.
 * @param string $message The message content.
 * @param string $type Message type.
 *
 * @return array|WP_Error Response data or error on failure.
 */
function send_sms_via_whysms( $recipient, $message, $type = 'plain' ) {
	// API URL.
	$url = 'https://bulk.whysms.com/api/v3/sms/send';

	// Set up the request headers.
	$headers = array(
		'Authorization' => 'Bearer ' . WHYSMS_TOKEN,
		'Content-Type'  => 'application/json',
		'Accept'        => 'application/json',
	);

	// Prepare the body of the request.
	$body = array(
		'recipient' => $recipient,
		'sender_id' => WHYSMS_SENDER_ID,
		'type'      => $type,
		'message'   => $message,
	);

	// Make the POST request.
	$response = wp_remote_post(
		$url,
		array(
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'method'  => 'POST',
		)
	);

	// Check for errors.
	if ( is_wp_error( $response ) ) {
		return $response; // Return the WP_Error on failure.
	}

	// Decode and return the response body.
	$response_body = wp_remote_retrieve_body( $response );
	return json_decode( $response_body, true );
}
