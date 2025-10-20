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
	$time_24_hours = gmdate( 'Y-m-d H:i:s', strtotime( '+24 hours', current_time( 'timestamp' ) ) );
	$time_23_hours = gmdate( 'Y-m-d H:i:s', strtotime( '+23 hours', current_time( 'timestamp' ) ) );
	$time_1_hour   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 hour', current_time( 'timestamp' ) ) );
	//phpcs:disable
	// Query to get up to 50 sessions happening in the next 24 hours or 1 hour where notifications haven't been sent.
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
        SELECT * FROM {$wpdb->prefix}snks_provider_timetable
        WHERE session_status = %s
        AND ( ( date_time <= %s AND date_time >= %s AND notification_24hr_sent = %d )
        OR ( date_time <= %s AND date_time >= %s AND notification_1hr_sent = %d ) )
        LIMIT 20
        ",
			'open',
			$time_24_hours,
			$time_23_hours,
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
		if ( empty( $billing_phone ) && $user ) {
			$billing_phone = $user->user_login;
		}
		if ( ! empty( $billing_phone ) ) {
			if ( in_array( 'doctor', $user->roles, true ) && strpos( $billing_phone, '+2' ) === false ) {
				$billing_phone = '+20' . $billing_phone;
			}
			
			// Check if this is an AI session
			$is_ai_session = function_exists( 'snks_is_ai_session' ) && snks_is_ai_session( $session );
			
			// 24-hour reminder.
			if ( $time_diff > 82800 && $time_diff <= 86400 && ! $session->notification_24hr_sent ) { // 82800 = 23 hrs, 86400 = 24 hrs
				if ( $is_ai_session && function_exists( 'snks_send_whatsapp_template_message' ) ) {
					// Send WhatsApp template notification for AI sessions
					$settings = function_exists( 'snks_get_whatsapp_notification_settings' ) ? snks_get_whatsapp_notification_settings() : array( 'enabled' => '0' );
					
					if ( $settings['enabled'] == '1' ) {
						// Get doctor name
						$doctor = get_user_by( 'id', $session->user_id );
						$doctor_name = $doctor ? $doctor->display_name : 'المعالج';
						
						// Format date and time
						$day_name = function_exists( 'snks_get_arabic_day_name' ) ? snks_get_arabic_day_name( $session->date_time ) : '';
						$date = gmdate( 'Y-m-d', strtotime( $session->date_time ) );
						$time = gmdate( 'h:i a', strtotime( $session->date_time ) );
						
						// Send via WhatsApp template
						snks_send_whatsapp_template_message(
							$billing_phone,
							$settings['template_patient_rem_24h'],
							array( $doctor_name, $day_name, $date, $time )
						);
					}
				} elseif ( 'online' === $session->attendance_type ) {
					// Legacy SMS notification for non-AI sessions
					$message = sprintf(
						'نذكرك بموعد جلستك غدا الساعه %1$s للدخول للجلسة:  %2$s',
						snks_localize_time( gmdate( 'h:i a', strtotime( $session->date_time ) ) ),
						'www.jalsah.link'
					);
					send_sms_via_whysms( $billing_phone, $message );
				} else {
					$message = sprintf(
						'نذكرك بموعد جلستك غدا الساعه %1$s',
						snks_localize_time( gmdate( 'h:i a', strtotime( $session->date_time ) ) ),
					);
					send_sms_via_whysms( $billing_phone, $message );
				}

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
			if ( 'online' === $session->attendance_type && $time_diff <= 3600 && ! $session->notification_1hr_sent ) {
				if ( $is_ai_session && function_exists( 'snks_send_whatsapp_template_message' ) ) {
					// Send WhatsApp template notification for AI sessions
					$settings = function_exists( 'snks_get_whatsapp_notification_settings' ) ? snks_get_whatsapp_notification_settings() : array( 'enabled' => '0' );
					
					if ( $settings['enabled'] == '1' ) {
						// Send via WhatsApp template (no parameters for this template)
						snks_send_whatsapp_template_message(
							$billing_phone,
							$settings['template_patient_rem_1h'],
							array()
						);
					}
				} else {
					// Legacy SMS notification for non-AI sessions
					$message = sprintf(
						'باقي أقل من ساعة على موعد الجلسة، رابط الدخول للجلسة:%s',
						'www.jalsah.link'
					);
					send_sms_via_whysms( $billing_phone, $message );
				}
				
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

/**
 * Sends notifications for users with open bookings tomorrow.
 */
function send_booking_notifications() {
	global $wpdb;
	$table = $wpdb->prefix . 'snks_provider_timetable'; // Ensure table prefix is used.

	// Ensure function runs only between 23:00 and 23:59.
	$current_hour = (int) current_time( 'H' );
	if ( $current_hour < 23 ) {
		return;
	}

	// Get tomorrow's date.
	$tomorrow_date = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
	//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// Get tomorrow's open bookings, grouped by user_id, with a count of bookings.
	$users = $wpdb->get_results(
		$wpdb->prepare(
			"
            SELECT user_id, COUNT(*) as open_bookings 
            FROM $table
            WHERE DATE(date_time) = %s
            AND session_status = 'open'
            GROUP BY user_id
            ",
			$tomorrow_date // Fetch bookings for tomorrow.
		)
	);

	if ( empty( $users ) ) {
		return;
	}

	foreach ( $users as $user ) {
		$user_id       = intval( $user->user_id );
		$open_bookings = intval( $user->open_bookings );

		// Validate user_id and booking count.
		if ( empty( $user_id ) || $open_bookings <= 0 ) {
			continue;
		}

		// Transient key to check if the user has already been notified today.
		$transient_key = 'notified_user_' . $user_id . '_' . current_time( 'Y-m-d' );

		if ( get_transient( $transient_key ) ) {
			continue; // Skip if already notified.
		}

		// Check if Firebase class exists before sending notifications.
		if ( class_exists( 'FbCloudMessaging\AnonyengineFirebase' ) ) {
			$firebase = new \FbCloudMessaging\AnonyengineFirebase();

			// Call the notifier method with proper data.
			$notification_title   = esc_html__( 'جلساتك غدا', 'your-text-domain' );
			$notification_message = sprintf(
				// translators: Sessions count.
				esc_html__( 'لديك غدا عدد %s جلسات حتى الآن.', 'your-text-domain' ),
				$open_bookings
			);
			// Trigger the notification.
			$firebase->trigger_notifier( $notification_title, $notification_message, $user_id, '' );
		}

		// Set transient to mark the user as notified for 24 hours.
		set_transient( $transient_key, true, DAY_IN_SECONDS );
	}
}


/**
 * Schedules the booking notification event if not already scheduled.
 */
function schedule_hourly_booking_notifications() {
	if ( ! wp_next_scheduled( 'send_hourly_booking_notifications' ) ) {
		wp_schedule_event( time(), 'hourly', 'send_hourly_booking_notifications' );
	}
}
add_action( 'wp', 'schedule_hourly_booking_notifications' );
add_action( 'send_hourly_booking_notifications', 'send_booking_notifications' );
