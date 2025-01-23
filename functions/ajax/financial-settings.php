<?php
/**
 * Financial settings ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();


//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
/**
 * OTP AJAX with field validation
 *
 * @return void
 */
function send_email_otp() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول لإرسال كود التحقق.' ) );
	}

	if ( ! verify_nonce( 'withdrawal_settings_nonce', 'save_withdrawal_settings' ) ) {
		wp_send_json_error( array( 'message' => 'خطأ في التحقق' ) );
	}

	$user_id           = get_current_user_id();
	$withdrawal_method = sanitize_text_field( $_POST['withdrawal_method'] ?? '' );

	if ( ! validate_withdrawal_fields( $withdrawal_method, $_POST, $user_id ) ) {
		return;
	}

	if ( 'wallet' === $withdrawal_method && snks_check_wallet_exists( sanitize_text_field( $_POST['wallet_number'] ?? '' ), $user_id ) ) {
		wp_send_json_error( array( 'message' => 'عفوا! رقم المحفظة مسجل لدى حساب آخر.' ) );
	}

	$user_info = get_userdata( $user_id );
	$otp       = generate_otp( $user_id );

	$message = 'كود التحقق الخاص بك هو: ' . $otp;
	if ( send_otp_email_and_sms( $user_info, $message ) ) {
		wp_send_json_success();
	} else {
		wp_send_json_error( array( 'message' => 'فشل في إرسال كود التحقق. يرجى المحاولة مرة أخرى.' ) );
	}
}

/**
 * OTP Verification and Save Withdrawal Settings
 *
 * @return void
 */
function verify_otp_and_save_withdrawal() {
	if ( ! verify_nonce( 'withdrawal_settings_nonce', 'save_withdrawal_settings' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب عليك تسجيل الدخول لحفظ إعدادات السحب الخاصة بك.' ) );
	}

	if ( ! validate_withdrawal_time() ) {
		wp_send_json_error( array( 'message' => 'لا يمكن حفظ إعدادات السحب بين الساعة 12 صباحا و 9 صباحا.' ) );
	}

	$user_id = get_current_user_id();

	if ( ! validate_otp( $user_id, sanitize_text_field( $_POST['otp_input'] ?? '' ) ) ) {
		return;
	}

	$withdrawal_method = sanitize_text_field( $_POST['withdrawal_method'] ?? '' );
	if ( ! validate_withdrawal_fields( $withdrawal_method, $_POST, $user_id ) ) {
		return;
	}

	save_withdrawal_settings( $user_id, $_POST, $withdrawal_method );

	wp_send_json_success( array( 'message' => 'تم حفظ إعدادات السحب بنجاح.' ) );
}

/**
 * Validate nonce.
 *
 * @param string $nonce_field Nonce field.
 * @param string $nonce_action Nonce action.
 *
 * @return bool
 */
function verify_nonce( $nonce_field, $nonce_action ) {
	return isset( $_POST[ $nonce_field ] ) && wp_verify_nonce( $_POST[ $nonce_field ], $nonce_action );
}

/**
 * Validate withdrawal time.
 *
 * @return bool
 */
function validate_withdrawal_time() {
	$current_hour = current_time( 'H' );
	return ! ( $current_hour >= 0 && $current_hour < 9 );
}

/**
 * Validate OTP.
 *
 * @param int    $user_id User ID.
 * @param string $otp_input OTP input.
 *
 * @return bool
 */
function validate_otp( $user_id, $otp_input ) {
	$stored_otp = get_user_meta( $user_id, 'withdrawal_otp', true );
	$otp_time   = get_user_meta( $user_id, 'withdrawal_otp_time', true );

	if ( $otp_input !== $stored_otp || ( time() - $otp_time ) > 300 ) {
		wp_send_json_error( array( 'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية.' ) );
		return false;
	}

	delete_user_meta( $user_id, 'withdrawal_otp' );
	delete_user_meta( $user_id, 'withdrawal_otp_time' );

	return true;
}

/**
 * Validate withdrawal fields.
 *
 * @param string $method Withdrawal method.
 * @param array  $data Form data.
 * @param int    $user_id User ID.
 *
 * @return bool
 */
function validate_withdrawal_fields( $method, $data, $user_id ) {
	$validation_rules = get_withdrawal_validation_rules();
	$required_fields  = get_required_withdrawal_fields();

	if ( isset( $required_fields[ $method ] ) ) {
		foreach ( $required_fields[ $method ] as $field ) {
			if ( empty( $data[ $field ] ) || ! preg_match( $validation_rules[ $field ]['pattern'], sanitize_text_field( $data[ $field ] ) ) ) {
				wp_send_json_error( array( 'message' => $validation_rules[ $field ]['message'] ) );
				return false;
			}
		}
	}

	return true;
}

/**
 * Save withdrawal settings.
 *
 * @param int    $user_id User ID.
 * @param array  $data Form data.
 * @param string $method Withdrawal method.
 */
function save_withdrawal_settings( $user_id, $data, $method ) {
	$settings = array( 'withdrawal_method' => $method );

	foreach ( $data as $key => $value ) {
		if ( array_key_exists( $key, get_withdrawal_validation_rules() ) ) {
			$settings[ $key ] = sanitize_text_field( $value );
		}
	}

	update_user_meta( $user_id, 'withdrawal_settings', $settings );
}

/**
 * Get withdrawal validation rules.
 *
 * @return array
 */
function get_withdrawal_validation_rules() {
	return array(
		'wallet_number'       => array(
			'pattern' => '/^\d{11}$/',
			'message' => 'رقم المحفظة يجب أن يتكون من 11 رقماً بالإنجليزية فقط.',
		),
		'wallet_owner_name'   => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'إسم صاحب المحفظة يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_holder_name' => array(
			'pattern' => '/^[A-Za-z ]+$/',
			'message' => 'اسم صاحب الحساب يجب أن يحتوي على أحرف إنجليزية فقط.',
		),
		'account_number'      => array(
			'pattern' => '/^\d+$/',
			'message' => 'رقم الحساب يجب أن يحتوي على أرقام فقط.',
		),
		'meza_card_number'    => array(
			'pattern' => '/^\d{16}$/',
			'message' => 'رقم البطاقة يجب أن يتكون من 16 رقماً بالإنجليزية فقط.',
		),
		// Add more rules as necessary.
	);
}

/**
 * Get required withdrawal fields.
 *
 * @return array
 */
function get_required_withdrawal_fields() {
	return array(
		'wallet'       => array( 'wallet_number', 'wallet_owner_name' ),
		'bank_account' => array( 'account_holder_name', 'bank_name', 'account_number' ),
		'meza_card'    => array( 'card_holder_first_name', 'meza_card_number' ),
	);
}

/**
 * Generate OTP.
 *
 * @param int $user_id User ID.
 *
 * @return int
 */
function generate_otp( $user_id ) {
	$otp = wp_rand( 100000, 999999 );
	update_user_meta( $user_id, 'withdrawal_otp', $otp );
	update_user_meta( $user_id, 'withdrawal_otp_time', time() );
	return $otp;
}

/**
 * Send OTP via email and SMS.
 *
 * @param WP_User $user_info User info object.
 * @param string  $message OTP message.
 *
 * @return bool
 */
function send_otp_email_and_sms( $user_info, $message ) {
	$subject = 'كود التحقق لضبط إعداداتك المحاسبية';
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);

	$email_sent = wp_mail( $user_info->user_email, $subject, $message, $headers );
	send_sms_via_whysms( $user_info->user_login, $message );

	return $email_sent;
}


add_action( 'wp_ajax_send_email_otp', 'send_email_otp' );
add_action( 'wp_ajax_nopriv_send_email_otp', 'send_email_otp' );
add_action( 'wp_ajax_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
add_action( 'wp_ajax_nopriv_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
