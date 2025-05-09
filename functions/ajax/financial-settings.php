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
	if ( empty( $_POST['withdrawal_method'] ) ) {
		wp_send_json_error( array( 'message' => 'يرجى تحديد طريقة السحب' ) );
	}
	if ( empty( $_POST['withdrawal_option'] ) ) {
		wp_send_json_error( array( 'message' => 'يرجى تحديد نظام السحب' ) );
	}

	$user_id           = get_current_user_id();
	$withdrawal_method = sanitize_text_field( $_POST['withdrawal_method'] ?? '' );
	if ( ! validate_withdrawal_fields( $withdrawal_method, $_POST ) ) {
		wp_send_json_error( array( 'message' => 'عفوا! يوجد خطأ بالبيانات المدخلة..' ) );
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
	if ( empty( $_POST['withdrawal_method'] ) ) {
		wp_send_json_error( array( 'message' => 'يرجى تحديد طريقة السحب' ) );
	}
	if ( empty( $_POST['withdrawal_option'] ) ) {
		wp_send_json_error( array( 'message' => 'يرجى تحديد نظام السحب' ) );
	}
	$user_id = get_current_user_id();

	if ( ! validate_otp( $user_id, sanitize_text_field( $_POST['otp_input'] ?? '' ) ) ) {
		return;
	}

	$withdrawal_method = sanitize_text_field( $_POST['withdrawal_method'] ?? '' );
	if ( ! validate_withdrawal_fields( $withdrawal_method, $_POST ) ) {
		return;
	}

	save_withdrawal_settings( $user_id, $_POST );

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
 *
 * @return bool
 */
function validate_withdrawal_fields( $method, $data ) {
	$validation_rules = get_withdrawal_validation_rules();
	$required_fields  = get_required_withdrawal_fields();

	if ( isset( $required_fields[ $method ] ) ) {
		foreach ( $required_fields[ $method ] as $field ) {
			if ( empty( $data[ $field ] ) || isset( $validation_rules[ $field ]['pattern'] ) && ! preg_match( $validation_rules[ $field ]['pattern'], sanitize_text_field( $data[ $field ] ) ) ) {
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
 * @param int   $user_id User ID.
 * @param array $data Form data.
 */
function save_withdrawal_settings( $user_id, $data ) {
	$withdrawal_option = isset( $data['withdrawal_option'] ) ? sanitize_text_field( $data['withdrawal_option'] ) : '';
	$withdrawal_method = isset( $data['withdrawal_method'] ) ? sanitize_text_field( $data['withdrawal_method'] ) : '';
	$settings          = array(
		'withdrawal_method' => $withdrawal_method,
		'withdrawal_option' => $withdrawal_option,
	);

	foreach ( $data as $key => $value ) {
		$settings[ $key ] = sanitize_text_field( $value );

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
	$subject = 'Jalsah code:';
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);
	if ( snks_is_doctor() ) {
		wp_mail( $user_info->user_email, $subject, $message, $headers );
	}
	$phone_to_use = get_user_meta( $user_info->ID, 'billing_phone', true );
	if ( ! $phone_to_use || empty( $phone_to_use ) ) {
		$phone_to_use = $user_info->user_login;
	}

	if ( strpos( $phone_to_use, '+2' ) === false ) {
		$phone_to_use = '+20' . $phone_to_use;
	}
	send_sms_via_whysms( $phone_to_use, $message );

	return true;
}


add_action( 'wp_ajax_send_email_otp', 'send_email_otp' );
add_action( 'wp_ajax_nopriv_send_email_otp', 'send_email_otp' );
add_action( 'wp_ajax_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );
add_action( 'wp_ajax_nopriv_verify_otp_and_save_withdrawal', 'verify_otp_and_save_withdrawal' );

add_action( 'wp_ajax_process_manual_withdrawal', 'handle_manual_withdrawal_ajax' );

/**
 * Handle manual withdrawal processing for the current user.
 *
 * @return void
 */
function handle_manual_withdrawal_ajax() {
	// Validate nonce for security.
	check_ajax_referer( 'process_withdrawal_nonce', 'security' );

	// Get the current user.
	$current_user = wp_get_current_user();

	if ( ! $current_user || 0 === $current_user->ID ) {
		wp_send_json_error(
			array(
				'msg' => 'لم يتم التعرف على المستخدم الحالي. يرجى تسجيل الدخول.',
			)
		);
	}

	// Check the user's withdrawal settings.
	$withdrawal_settings = get_user_meta( $current_user->ID, 'withdrawal_settings', true );

	if ( empty( $withdrawal_settings ) ) {
		wp_send_json_error(
			array(
				'msg' => 'ليس لديك إعدادات سحب مؤهلة.',
			)
		);
	}
	if ( 'manual_withdrawal' !== $withdrawal_settings['withdrawal_option'] ) {
		wp_send_json_error(
			array(
				'msg' => 'يرجى عمل حفظ أولاً ثم إعادة طلب السحب..',
			)
		);
	}

	// Prepare data for withdrawal.
	global $wpdb;
	$current_date         = current_time( 'mysql' );
	$current_day_of_week  = gmdate( 'w', strtotime( $current_date ) );
	$current_day_of_month = gmdate( 'j', strtotime( $current_date ) );
	$table_name           = $wpdb->prefix . TRNS_TABLE_NAME;

	// Process the withdrawal for the current user.
	$processed = process_user_withdrawal( $current_user->ID, $current_day_of_week, $current_day_of_month, $current_date, $table_name, true );
	if ( $processed['success'] ) {
		wp_send_json_success(
			array(
				'msg' => $processed['msg'],
			)
		);
	} else {
		wp_send_json_error(
			array(
				'msg' => $processed['msg'],
			)
		);
	}
	die;
}
