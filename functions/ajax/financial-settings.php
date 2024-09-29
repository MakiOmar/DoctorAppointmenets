<?php
/**
 * Financial settings ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_send_email_otp', 'send_email_otp' );
add_action( 'wp_ajax_nopriv_send_email_otp', 'send_email_otp' );

//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
/**
 * OTP AJAX
 *
 * @return void
 */
function send_email_otp() {
	// Ensure the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول لإرسال كود التحقق.' ) );
	}

	$user_id    = get_current_user_id();
	$user_info  = get_userdata( $user_id );
	$user_email = $user_info->user_email;

	// Generate a random 6-digit OTP.
	$otp = wp_rand( 100000, 999999 );

	// Store OTP in user meta with a timestamp.
	update_user_meta( $user_id, 'withdrawal_otp', $otp );
	update_user_meta( $user_id, 'withdrawal_otp_time', time() );

	// Send OTP to the user's email.
	$subject = 'كود التحقق لضبك إعداداتك المحاسبية';
	$message = 'كود التحقق الخاص بك هو: ' . $otp;
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>' );
	$sent    = wp_mail( $user_email, $subject, $message, $headers );

	if ( $sent ) {
		wp_send_json_success();
	} else {
		wp_send_json_error( array( 'message' => 'فشل في إرسال كود التحقق. يرجى المحاولة مرة أخرى.' ) );
	}
}


add_action( 'wp_ajax_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
add_action( 'wp_ajax_nopriv_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
/**
 * OTP Verification and Save withdrawal settings
 *
 * @return void
 */
function verify_otp_and_save_withdrawal() {
	// Verify nonce.
	if ( ! isset( $_POST['withdrawal_settings_nonce'] ) || ! wp_verify_nonce( $_POST['withdrawal_settings_nonce'], 'save_withdrawal_settings' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
	}

	// Ensure the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب عليك تسجيل الدخول لحفظ إعدادات السحب الخاصة بك.' ) );
	}

	// Get the current time based on WordPress timezone.
	$current_hour = current_time( 'H' ); // 'H' returns the hour in 24-hour format (e.g., 00-23).

	// Check if the current time is between 12 AM (00:00) and 9 AM (09:00).
	if ( $current_hour >= 0 && $current_hour < 9 ) {
		wp_send_json_error( array( 'message' => 'لا يمكن حفظ إعدادات السحب بين الساعة 12 صباحا و 9 صباحا.' ) );
	}

	$user_id = get_current_user_id();

	// Get OTP from user meta and check its validity.
	$otp_input  = isset( $_POST['otp_input'] ) ? sanitize_text_field( $_POST['otp_input'] ) : '';
	$stored_otp = get_user_meta( $user_id, 'withdrawal_otp', true );
	$otp_time   = get_user_meta( $user_id, 'withdrawal_otp_time', true );

	// Check if OTP matches and is within a valid time window (e.g., 5 minutes).
	$current_time = time();
	if ( $otp_input !== $stored_otp || ( $current_time - $otp_time ) > 300 ) { // 300 seconds = 5 minutes.
		wp_send_json_error( array( 'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية.' ) );
	}

	// OTP is valid, proceed with saving the withdrawal settings.
	$withdrawal_option = isset( $_POST['withdrawal_option'] ) ? sanitize_text_field( $_POST['withdrawal_option'] ) : '';
	$withdrawal_method = isset( $_POST['withdrawal_method'] ) ? sanitize_text_field( $_POST['withdrawal_method'] ) : '';

	// Save the withdrawal settings.
	$withdrawal_data = array(
		'withdrawal_option' => $withdrawal_option,
		'withdrawal_method' => $withdrawal_method,
	);

	// Save additional fields based on the withdrawal method selected.
	if ( 'bank_account' === $withdrawal_method ) {
		$withdrawal_data['account_holder_name'] = sanitize_text_field( $_POST['account_holder_name'] );
		$withdrawal_data['bank_name']           = sanitize_text_field( $_POST['bank_name'] );
		$withdrawal_data['bank_code']           = sanitize_text_field( $_POST['bank_code'] );
		$withdrawal_data['branch']              = sanitize_text_field( $_POST['branch'] );
		$withdrawal_data['account_number']      = sanitize_text_field( $_POST['account_number'] );
		$withdrawal_data['iban_number']         = sanitize_text_field( $_POST['iban_number'] );
	} elseif ( 'meza_card' === $withdrawal_method ) {
		$withdrawal_data['card_holder_name'] = sanitize_text_field( $_POST['card_holder_name'] );
		$withdrawal_data['meza_bank_name']   = sanitize_text_field( $_POST['meza_bank_name'] );
		$withdrawal_data['meza_card_number'] = sanitize_text_field( $_POST['meza_card_number'] );
	} elseif ( 'wallet' === $withdrawal_method ) {
		$withdrawal_data['wallet_holder_name'] = sanitize_text_field( $_POST['wallet_holder_name'] );
		$withdrawal_data['wallet_number']      = sanitize_text_field( $_POST['wallet_number'] );
	}

	update_user_meta( $user_id, 'withdrawal_settings', $withdrawal_data );

	// Clear OTP after successful submission.
	delete_user_meta( $user_id, 'withdrawal_otp' );
	delete_user_meta( $user_id, 'withdrawal_otp_time' );

	wp_send_json_success( array( 'message' => 'تم حفظ إعدادات السحب بنجاح.' ) );
}
