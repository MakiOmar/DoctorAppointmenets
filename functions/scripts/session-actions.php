<?php
/**
 * Session actions
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Pospon appointment
 *
 * @param int $id  Timtable ID.
 * @return void
 */
function snks_postpon_appointment( $id ) {
	// this.
}

/**
 * Delay appointment
 *
 * @param int $id  Timtable ID.
 * @param int $delay_period  Delay period.
 * @return void
 */
function snks_delay_appointment( $id, $delay_period, $date ) {
	$user = get_user_by( 'id', $id );
	if ( ! $user ) {
		return;
	}
	ob_start();
	include SNKS_DIR . 'templates/email-template.php';
	$template = ob_get_clean();

	$message = str_replace(
		array(
			'{logo}',
			'{title}',
			'{sub_title}',
			'{content_placeholder}',
			'{text_1}',
			'{text_2}',
			'{text_3}',
			'{button_text}',
			'{button_url}',
		),
		array(
			SNKS_LOGO,
			'تم تأخير موعدك',
			'في جلسة',
			SNKS_EMAIL_IMG,
			'نعتذر لك! الطبيب يبلغك بتأخير الموعد قليلاَ',
			'تم تأخير الموعد الخاص بك لمدة',
			$delay_period . ' دقيقة',
			'للموعد ' . $date,
			'#',
		),
		$template
	);

	$to      = $user->user_email;
	$subject = 'تم تأخير موعدك في ' . SNKS_APP_NAME;
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);
	$emailed = wp_mail( $to, $subject, $message, $headers );
}
