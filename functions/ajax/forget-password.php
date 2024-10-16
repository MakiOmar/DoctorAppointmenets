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
	$login_with = isset( $_req['login_with'] ) ? sanitize_text_field( $_req['login_with'] ) : '';
	$phone      = isset( $_req['phone'] ) ? sanitize_text_field( trim( $_req['phone'] ) ) : '';
	$email      = isset( $_req['email'] ) ? sanitize_text_field( $_req['email'] ) : '';

	// Server-side validation.
	if ( ( 'mobile' === $login_with ) && empty( $phone ) ) {
		wp_send_json_error( 'الرجاء إدخال رقم هاتف صالح أو استخدم البريد الإلكتروني بدلاً من ذلك.' );
	} elseif ( ( 'email' === $login_with ) && empty( $email ) ) {
		wp_send_json_error( 'الرجاء إدخال عنوان بريد إلكتروني صالح أو استخدام الهاتف بدلاً من ذلك.' );
	}
	$user = get_user_by( 'login', $email );
	// Find the user based on the login method.
	if ( ! $user ) {
		$user = get_user_by( 'email', $email );
	}
	// If the user is not found, return an error.
	if ( ! $user ) {
		wp_send_json_error( 'عفوا! لايوجد مستخدم بهذه البيانات' );
	}

	// Generate a new random 6-digit password.
	$new_password = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
	// Update the user's password.
	wp_set_password( $new_password, $user->ID );

	// Prepare the email content.
	$to       = $user->user_email;
	$subject  = 'استعادة كلمة المرور';
	$message  = '<div style="direction:rtl;text-align:right"><p>';
	$message .= sprintf( 'تم تعيين كلمة مرور جديدة لحسابك: %s. كلمة المرور الجديدة هي: %s', $user->user_login, $new_password );
	$message .= '</p></div>';
	$headers  = array( 'Content-Type: text/html; charset=UTF-8' );

	// Send the email.
	wp_mail( $to, $subject, $message, $headers );

	// Get the user's billing phone.
	$billing_phone = get_user_meta( $user->ID, 'billing_phone', true );
	$msg           = 'تم إرسال كلمة المرور الجديدة إلى بريدك الإلكتروني.';
	if ( ! empty( $billing_phone ) ) {
		// Check the last SMS sent timestamp.
		$last_sms_time = get_user_meta( $user->ID, 'last_forget_sms_sent_time', true );
		$current_time  = strtotime( current_time( 'mysql' ) ); // Convert current time to a Unix timestamp.

		// If the last SMS was sent more than 5 minutes ago, send a new SMS.
		if ( empty( $last_sms_time ) || ( $current_time - $last_sms_time ) > 300 ) {
			send_sms_via_whysms( $billing_phone, sprintf( 'كلمة السر الجديدة الخاصة بك: %s', $new_password ) );

			// Update the last SMS sent time.
			update_user_meta( $user->ID, 'last_forget_sms_sent_time', $current_time );
			// End the response with success.
			$msg = 'تم إرسال كلمة المرور الجديدة إلى تليفونك وبريدك الإلكتروني.';

		} else {
			// End the response with success.
			$msg = 'تم الإرسال على البريد ولكن لا يمكن إرسال رسالة للتليفون الآن. الرجاء المحاولة بعد 5 دقائق.';
		}
	}

	// End the response with success.
	wp_send_json(
		array(
			'msg' => $msg,
		)
	);
	die;
}

add_action( 'wp_ajax_custom_forget_password_action', 'custom_forget_password_handler' );
add_action( 'wp_ajax_nopriv_custom_forget_password_action', 'custom_forget_password_handler' );
