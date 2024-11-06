<?php
/**
 * Delete account
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * AJAX handler to send verification code to user's email.
 */
function send_verification_code() {
	check_ajax_referer( 'delete_account_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب أن تكون مسجل الدخول للقيام بهذا الإجراء.' ) );
	}

	$user              = wp_get_current_user();
	$verification_code = wp_rand( 100000, 999999 ); // Generate a 6-digit code.

	// Store verification code in user meta.
	update_user_meta( $user->ID, 'delete_account_verification_code', $verification_code );

	// Send verification code to user's email.
	wp_mail( $user->user_email, 'رمز تأكيد حذف الحساب', "رمز التحقق الخاص بك هو: $verification_code" );

	wp_send_json_success( array( 'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.' ) );
}
add_action( 'wp_ajax_send_verification_code', 'send_verification_code' );

/**
 * AJAX handler to verify code and delete the user account.
 */
function verify_and_delete_account() {
	check_ajax_referer( 'delete_account_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب أن تكون مسجل الدخول للقيام بهذا الإجراء.' ) );
	}

	$user_id = get_current_user_id();
	//phpcs:disable
	$entered_code = sanitize_text_field( $_POST['verification_code'] );
	//phpcs:enable
	$stored_code = get_user_meta( $user_id, 'delete_account_verification_code', true );

	if ( $entered_code === $stored_code ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );

		wp_send_json_success( array( 'message' => 'تم حذف حسابك بنجاح.' ) );
	} else {
		wp_send_json_error( array( 'message' => 'رمز التحقق غير صحيح. حاول مرة أخرى.' ) );
	}
}
add_action( 'wp_ajax_verify_and_delete_account', 'verify_and_delete_account' );
