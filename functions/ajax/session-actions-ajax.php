<?php
/**
 * Session actions
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_appointment_action', 'appointment_action_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function appointment_action_callback() {
	if ( ! snks_is_doctor() && ! snks_is_clinic_manager() ) {
		wp_send_json_error( 'Doctor only.' );
	}
	$errors = array();
	$_req   = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'appointment_action_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	if ( '.snks-postpon' === $_req['ele'] ) {
		foreach ( $_req['IDs'] as $data ) {
			$sent = snks_postpon_appointment( $data['ID'], $data['patientID'], $data['doctorID'], $data['date'] );
			if ( ! $sent ) {
				$errors[] = $data['ID'];
			}
		}
	}
	if ( '.snks-delay' === $_req['ele'] ) {
		if ( $_req['delayBy'] ) {
			foreach ( $_req['IDs'] as $data ) {
				$sent = snks_delay_appointment( $data['patientID'], $data['doctorID'], $_req['delayBy'], $data['date'] );
				if ( ! $sent ) {
					$errors[] = $data['ID'];
				}
			}
		} else {
			wp_send_json_error( 'لايمكن تأخير الجلسات' );
		}
	}
	if ( empty( $errors ) ) {
		wp_send_json_success( 'تم الإشعار بنجاح' );
	} else {
		wp_send_json_success( 'تم الإشعار بنجاح' );
	}

	die();
}

add_action( 'wp_ajax_appointment_change_date', 'appointment_change_date_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function appointment_change_date_callback() {
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Doctor only.' );
	}
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'appointment_change_date_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$old_timetable = snks_get_timetable_by( 'ID', $_req['oldAppointment'] );
	$old_date      = gmdate( 'Y-m-d', strtotime( $old_timetable->date_time ) );
	if ( $old_date === $_req['date'] ) {
		$show_closed = true;
	} else {
		$show_closed = false;
	}
	$timetables = snks_get_timetable_by_date( $_req['date'], $old_timetable->period, $show_closed );
	if ( $timetables ) {
		foreach ( $timetables as $appointment ) {
			$attendance = 'online' === $appointment->attendance_type ? 'أونلاين' : 'أوفلاين';
			echo '<div name="appointment">';
			echo '<input type="radio" id="change-to-this-date-' . esc_attr( $appointment->ID ) . '" name="change-to-this-date" value="' . esc_attr( $appointment->ID ) . '">';
			//phpcs:disable
			echo '<label for="change-to-this-date-' . esc_attr( $appointment->ID ) . '">' . esc_html( $appointment->period . ' دقيقة ' . snks_localized_time( $appointment->starts ) . ' - ' . snks_localized_time( $appointment->ends ) ) . ' - ' . $attendance . '</label>';
			//phpcs:enable
			echo '</div>';
		}
		die;
	}
	echo '<p>عفواُ لا توجد مواعيد متاحة في هذا اليوم</p>';
	die();
}
