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
 * @param int    $patient_id  patient's ID.
 * @param int    $doctor_id  Doctor's ID.
 * @param string $delay_period  Delay period.
 * @param string $date  Appointment date.
 * @return void
 */
function snks_delay_appointment( $patient_id, $doctor_id, $delay_period, $date ) {
	$user   = get_user_by( 'id', $patient_id );
	$doctor = get_user_by( 'id', $doctor_id );
	if ( ! $user || ! $doctor ) {
		return;
	}
	$after_button  = '<p style="Margin:0;line-height:36px;mso-line-height-rule:exactly;font-family:georgia, times, times new roman, serif;font-size:30px;font-style:normal;font-weight:normal;color:#023047">';
	$after_button  = '<b style="display:block;margin-top:20px;font-size:20px">';
	$after_button  = 'مع الطبيب';
	$after_button .= '<br>';
	$after_button .= get_user_meta( $doctor_id, 'billing_first_name', true ) . ' ' . get_user_meta( $doctor_id, 'billing_last_name', true );
	$after_button .= '</b>';
	$after_button .= '</p>';
	ob_start();
	include SNKS_DIR . 'templates/email-template.php';
	$template = ob_get_clean();
	list($title, $sub_title, $text_1, $text_2, $text_3, $button_text, $button_url) = array(
		'تم تأخير موعدك',
		'في جلسة',
		'نعتذر لك! الطبيب يبلغك بتأخير الموعد قليلاَ',
		'تم تأخير الموعد الخاص بك لمدة',
		$delay_period . ' دقيقة',
		'للموعد ' . snks_localize_time( gmdate( 'Y-m-d h:i a', strtotime( $date ) ) ),
		'#',
	);
	snks_send_email( $user->user_email, $title, $sub_title, $text_1, $text_2, $text_3, $button_text, $button_url, $after_button );
}
