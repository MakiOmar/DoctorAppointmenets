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
 * OTP AJAX with field validation
 *
 * @return void
 */
function send_email_otp() {
	// Ensure the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول لإرسال كود التحقق.' ) );
	}
	// Verify nonce.
	if ( ! isset( $_POST['withdrawal_settings_nonce'] ) || ! wp_verify_nonce( $_POST['withdrawal_settings_nonce'], 'save_withdrawal_settings' ) ) {
		wp_send_json_error( array( 'message' => 'خطأ في التحقق' ) );
	}
	$user_id           = get_current_user_id();
	$withdrawal_method = isset( $_POST['withdrawal_method'] ) ? sanitize_text_field( $_POST['withdrawal_method'] ) : '';

	// Validation rules based on attributes.
	$validation_rules = array(
		'wallet_number'          => array(
			'pattern' => '/^\d{11}$/',
			'message' => 'رقم المحفظة يجب أن يتكون من 11 رقماً بالإنجليزية فقط.',
		),
		'wallet_owner_name'      => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'إسم صاحب المحفظة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_holder_name'    => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم صاحب الحساب يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_number'         => array(
			'pattern' => '/^\d+$/',
			'message' => 'رقم الحساب يجب أن يحتوي على أرقام فقط.',
		),
		'bank_name'              => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم البنك يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'card_holder_first_name' => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم صاحب البطاقة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'meza_card_number'       => array(
			'pattern' => '/^\d{16}$/',
			'message' => 'رقم البطاقة يجب أن يتكون من 16 رقماً بالإنجليزية فقط.',
		),
	);

	// Required fields based on the withdrawal method.
	$required_fields = array(
		'wallet'       => array( 'wallet_number', 'wallet_owner_name' ),
		'bank_account' => array( 'account_holder_name', 'bank_name', 'account_number' ),
		'meza_card'    => array( 'card_holder_first_name', 'meza_bank_code', 'meza_card_number' ),
	);

	if ( isset( $required_fields[ $withdrawal_method ] ) ) {
		foreach ( $required_fields[ $withdrawal_method ] as $field ) {
			// Check if the field is empty.
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( array( 'message' => 'يرجى إدخال جميع الحقول المطلوبة.' ) );
			}
			// Apply the pattern validation if defined.
			if ( isset( $validation_rules[ $field ]['pattern'] ) && ! preg_match( $validation_rules[ $field ]['pattern'], $_POST[ $field ] ) ) {
				wp_send_json_error( array( 'message' => $validation_rules[ $field ]['message'] ) );
			}
		}
	}

	// Check for wallet number duplication (for 'wallet' method only).
	if ( 'wallet' === $withdrawal_method && snks_check_wallet_exists( sanitize_text_field( $_POST['wallet_number'] ), $user_id ) ) {
		wp_send_json_error( array( 'message' => 'عفوا! رقم المحفظة مسجل لدى حساب آخر.' ) );
	}

	// Proceed to generate and send OTP if all fields are valid.
	$user_info  = get_userdata( $user_id );
	$user_email = $user_info->user_email;

	// Generate a random 6-digit OTP.
	$otp = wp_rand( 100000, 999999 );

	// Store OTP in user meta with a timestamp.
	update_user_meta( $user_id, 'withdrawal_otp', $otp );
	update_user_meta( $user_id, 'withdrawal_otp_time', time() );

	// Send OTP to the user's email.
	$subject = 'كود التحقق لضبط إعداداتك المحاسبية';
	$message = 'كود التحقق الخاص بك هو: ' . $otp;
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);
	$sent    = wp_mail( $user_email, $subject, $message, $headers );

	// Send OTP via SMS.
	send_sms_via_whysms( $user_info->user_login, $message );

	if ( $sent ) {
		wp_send_json_success();
	} else {
		wp_send_json_error( array( 'message' => 'فشل في إرسال كود التحقق. يرجى المحاولة مرة أخرى.' ) );
	}
}



add_action( 'wp_ajax_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
add_action( 'wp_ajax_nopriv_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
/**
 * OTP Verification and Save Withdrawal Settings
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

	// Validation rules based on attributes.
	$validation_rules = array(
		'wallet_number'          => array(
			'pattern' => '/^\d{11}$/',
			'message' => 'رقم المحفظة يجب أن يتكون من 11 رقماً بالإنجليزية فقط.',
		),
		'wallet_owner_name'      => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'إسم صاحب المحفظة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_holder_name'    => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم صاحب الحساب يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_number'         => array(
			'pattern' => '/^\d+$/',
			'message' => 'رقم الحساب يجب أن يحتوي على أرقام فقط.',
		),
		'iban_number'            => array(
			'pattern' => '/^\d{27}$/',
			'message' => 'رقم IBAN يجب أن يتكون من 27 رقماً بدون رمز الدولة.',
		),
		'card_holder_first_name' => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم صاحب البطاقة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'card_holder_last_name'  => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'الاسم الأخير لصاحب البطاقة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'meza_card_number'       => array(
			'pattern' => '/^\d{16}$/',
			'message' => 'رقم البطاقة يجب أن يتكون من 16 رقماً بالإنجليزية فقط.',
		),
	);

	// Required fields based on the withdrawal method.
	$required_fields = array(
		'bank_account' => array( 'account_holder_name', 'bank_name', 'account_number' ),
		'meza_card'    => array( 'card_holder_first_name', 'meza_bank_code', 'meza_card_number' ),
		'wallet'       => array( 'wallet_number', 'wallet_owner_name' ),
	);

	$withdrawal_method = isset( $_POST['withdrawal_method'] ) ? sanitize_text_field( $_POST['withdrawal_method'] ) : '';

	// Validate required fields for the selected withdrawal method.
	if ( isset( $required_fields[ $withdrawal_method ] ) ) {
		foreach ( $required_fields[ $withdrawal_method ] as $field ) {
			// Check if the field is empty.
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( array( 'message' => 'يرجى إدخال جميع الحقول المطلوبة. ' . $field ) );
			}
			// Apply the pattern validation if defined.
			if ( isset( $validation_rules[ $field ]['pattern'] ) && ! preg_match( $validation_rules[ $field ]['pattern'], $_POST[ $field ] ) ) {
				wp_send_json_error( array( 'message' => $validation_rules[ $field ]['message'] ) );
			}
		}
	}

	// Additional wallet-specific validation.
	if ( 'wallet' === $withdrawal_method && snks_check_wallet_exists( sanitize_text_field( $_POST['wallet_number'] ), $user_id ) ) {
		wp_send_json_error( array( 'message' => 'عفوا! رقم المحفظة مسجل لدى حساب آخر.' ) );
	}

	// Save the withdrawal settings.
	$withdrawal_data = array(
		'withdrawal_option' => isset( $_POST['withdrawal_option'] ) ? sanitize_text_field( $_POST['withdrawal_option'] ) : '',
		'withdrawal_method' => $withdrawal_method,
	);

	// Save all fields dynamically.
	foreach ( $_POST as $key => $value ) {
		if ( array_key_exists( $key, $validation_rules ) ) {
			$withdrawal_data[ $key ] = sanitize_text_field( $value );
		}
	}

	update_user_meta( $user_id, 'withdrawal_settings', $withdrawal_data );

	// Clear OTP after successful submission.
	delete_user_meta( $user_id, 'withdrawal_otp' );
	delete_user_meta( $user_id, 'withdrawal_otp_time' );

	wp_send_json_success( array( 'message' => 'تم حفظ إعدادات السحب بنجاح.' ) );
}
