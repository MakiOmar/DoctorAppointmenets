<?php
/**
 * SMS Notifications
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Schedules an event if not already scheduled.
 */
if ( ! wp_next_scheduled( 'snks_check_session_notifications' ) ) {
	wp_schedule_event( time(), 'every_minute', 'snks_check_session_notifications' );
}

/**
 * Hook to perform the task of sending notifications.
 */
add_action( 'snks_check_session_notifications', 'snks_send_session_notifications' );

/**
 * Sends session notifications based on proximity to session time.
 *
 * This function checks for sessions in the next 24 hours or 1 hour,
 * and sends notifications accordingly. It ensures that notifications
 * are only sent once per time frame (24-hour and 1-hour).
 */
function snks_send_session_notifications() {
	global $wpdb;
	$current_time  = current_time( 'mysql' );
	$time_24_hours = gmdate( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );
	$time_1_hour   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );
	//phpcs:disable
	// Query to get up to 50 sessions happening in the next 24 hours or 1 hour where notifications haven't been sent.
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
        SELECT * FROM {$wpdb->prefix}snks_provider_timetable
        WHERE session_status = %s
		AND attendance_type = 'online'
        AND ( ( date_time <= %s AND date_time >= %s AND notification_24hr_sent = %d )
        OR ( date_time <= %s AND date_time >= %s AND notification_1hr_sent = %d ) )
        LIMIT 20
        ",
			'open',
			$time_24_hours,
			$current_time,
			0,
			$time_1_hour,
			$current_time,
			0
		)
	);
	//phpcs:enable
	// Process each result.
	foreach ( $results as $session ) {
		$time_diff     = strtotime( $session->date_time ) - strtotime( $current_time );
		$billing_phone = get_user_meta( $session->client_id, 'billing_phone', true );
		$user          = get_user_by( 'id', $session->client_id );
		if ( empty( $billing_phone ) ) {
			$billing_phone = $user->user_login;
		}
		if ( ! empty( $billing_phone ) ) {
			if ( in_array( 'doctor', $user->roles, true ) && strpos( $billing_phone, '+2' ) === false ) {
				$billing_phone = '+20' . $billing_phone;
			}
			// 24-hour reminder.
			if ( $time_diff <= 86400 && ! $session->notification_24hr_sent ) {

				$message = sprintf(
					'نذكرك بموعد جلستك غدا الساعه %1$s . رابط الدخول للجلسة:  %2$s',
					snks_localize_time( gmdate( 'H:i a', strtotime( $session->date_time ) ) ),
					'www.jalsah.link'
				);
				send_sms_via_whysms( $billing_phone, $message );
				//phpcs:disable
				$wpdb->update(
					$wpdb->prefix . 'snks_provider_timetable',
					array( 'notification_24hr_sent' => 1 ),
					array( 'ID' => $session->ID ),
					array( '%d' ),
					array( '%d' )
				);
			}

			// 1-hour reminder.
			if ( $time_diff <= 3600 && ! $session->notification_1hr_sent ) {
				$message = sprintf(
					'باقي ساعة على موعد الجلسة، رابط الدخول للجلسة:%s',
					'www.jalsah.link'
				);
				send_sms_via_whysms( $billing_phone, $message );
				$wpdb->update(
					$wpdb->prefix . 'snks_provider_timetable',
					array( 'notification_1hr_sent' => 1 ),
					array( 'ID' => $session->ID ),
					array( '%d' ),
					array( '%d' )
				);
				//phpcs:enable
			}
		}
	}
}
