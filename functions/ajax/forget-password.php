<?php
/**
 * Consulting form ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Handle the AJAX request for the "Forgot Password" functionality.
 */
function custom_forget_password_handler() {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
	$_req = $_POST;

	// Check the nonce for security.
	if ( ! isset( $_req['_wpnonce'] ) || ! wp_verify_nonce( $_req['_wpnonce'], 'forgetpassword' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	// Get the data from the AJAX request.
	$login_with = sanitize_text_field( $_req['login_with'] ?? '' );
	$phone      = sanitize_text_field( trim( $_req['phone'] ?? '' ) );
	$email      = sanitize_text_field( $_req['email'] ?? '' );

	// Validate input based on login method.
	if ( ( 'mobile' === $login_with && empty( $phone ) ) || ( 'email' === $login_with && empty( $email ) ) ) {
		wp_send_json_error( 'يرجى إدخال بيانات صحيحة.' );
	}

	// Find the user.
	$user = null;
	if ( 'mobile' === $login_with ) {
		$user = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
				'fields'     => 'all',
			)
		)[0] ?? get_user_by( 'login', $phone );
	} elseif ( 'email' === $login_with ) {
		$user = get_user_by( 'email', $email );
	}
	// Return error if the user is not found.
	if ( ! $user || empty( $user ) ) {
		wp_send_json_error( array( 'msg' => 'عفوا! لا يوجد مستخدم بهذه البيانات.' ) );
	}

	// Generate a new password and update it.
	$new_password = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
	wp_set_password( $new_password, $user->ID );

	// Handle notification.
	$msg = '';
	if ( 'mobile' === $login_with ) {
		//phpcs:disable Universal.Operators.DisallowShortTernary.Found
		$phone_to_use = get_user_meta( $user->ID, 'billing_phone', true ) ?: $user->user_login;
		if ( in_array( 'doctor', $user->roles, true ) && strpos( $phone_to_use, '+2' ) === false ) {
			$phone_to_use = '+2' . $phone_to_use;
		}

		$last_sms_time = get_user_meta( $user->ID, 'last_forget_sms_sent_time', true );
		$current_time  = strtotime( current_time( 'mysql' ) );

		if ( ! empty( $phone_to_use ) && ( empty( $last_sms_time ) || ( $current_time - $last_sms_time ) > 300 ) ) {
			send_sms_via_whysms( $phone_to_use, sprintf( 'كلمة السر الجديدة الخاصة بك: %s', $new_password ) );
			update_user_meta( $user->ID, 'last_forget_sms_sent_time', $current_time );
			$msg = 'تم إرسال كلمة المرور الجديدة إلى هاتفك.';
		} else {
			$msg = 'لا يمكن إرسال رسالة الآن. الرجاء المحاولة بعد 5 دقائق.';
		}
	} elseif ( 'email' === $login_with ) {
		$to      = $user->user_email;
		$subject = 'استعادة كلمة المرور';
		$message = sprintf(
			'<div style="direction:rtl;text-align:right"><p>تم تعيين كلمة مرور جديدة لحسابك: %s. كلمة المرور الجديدة هي: %s</p></div>',
			$user->user_login,
			$new_password
		);
		wp_mail( $to, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		$msg = 'تم إرسال كلمة المرور الجديدة إلى بريدك الإلكتروني.';
	}
	// Send the response.
	wp_send_json_success( array( 'msg' => $msg ) );
	die;
}

add_action( 'wp_ajax_custom_forget_password_action', 'custom_forget_password_handler' );
add_action( 'wp_ajax_nopriv_custom_forget_password_action', 'custom_forget_password_handler' );
