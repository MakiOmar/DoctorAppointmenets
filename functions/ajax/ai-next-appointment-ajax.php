<?php
/**
 * AJAX: submit AI session next appointment (الموعد القادم).
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action( 'wp_ajax_snks_submit_next_appointment', 'snks_ajax_submit_next_appointment' );

/**
 * Handle doctor next-appointment submit.
 *
 * @return void
 */
function snks_ajax_submit_next_appointment() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snks_next_appointment_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'رمز الأمان غير صالح.' ) );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول.' ) );
	}

	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	$datetime   = isset( $_POST['appointment_datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_datetime'] ) ) : '';

	if ( ! function_exists( 'snks_ai_next_appointment_submit' ) ) {
		wp_send_json_error( array( 'message' => 'الخدمة غير متاحة.' ) );
	}

	$result = snks_ai_next_appointment_submit( $session_id, $datetime, get_current_user_id() );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => 'تم تسجيل الموعد القادم بنجاح.',
			'data'    => $result,
		)
	);
}
