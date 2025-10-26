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
	error_log( '[Notification Cron] snks_send_session_notifications started' );
	
	global $wpdb;
	// Use GMT timestamp to match how date_time is stored in database
	$current_timestamp = time();
	$current_time = gmdate( 'Y-m-d H:i:s', $current_timestamp );
	$time_24_hours = gmdate( 'Y-m-d H:i:s', strtotime( '+24 hours', $current_timestamp ) );
	$time_23_hours = gmdate( 'Y-m-d H:i:s', strtotime( '+23 hours', $current_timestamp ) );
	$time_1_hour   = gmdate( 'Y-m-d H:i:s', strtotime( '+1 hour', $current_timestamp ) );
	
	error_log( '[Notification Cron] Current time: ' . $current_time );
	error_log( '[Notification Cron] Timezone: ' . get_option( 'timezone_string' ) );
	error_log( '[Notification Cron] 24 hours window: ' . $time_23_hours . ' to ' . $time_24_hours );
	error_log( '[Notification Cron] 1 hour window: ' . $current_time . ' to ' . $time_1_hour );
	//phpcs:disable
	// Query to get sessions happening between 23-24 hours from now OR 0-1 hour from now
	// For 24hr reminder: Find sessions where current time is 23-24 hours before the session
	// For 1hr reminder: Find sessions where current time is 0-1 hour before the session
	$query = $wpdb->prepare(
		"
		SELECT * FROM {$wpdb->prefix}snks_provider_timetable
		WHERE session_status = %s
		AND (
			( date_time >= %s AND date_time <= %s AND notification_24hr_sent = %d )
			OR
			( date_time >= %s AND date_time <= %s AND notification_1hr_sent = %d )
		)
		LIMIT 20
		",
		'open',
		$current_time,     // start now
		$time_24_hours,    // up to +24h
		0,                 // notification_24hr_sent = 0
		$current_time,     // start now
		$time_1_hour,      // up to +1h
		0                  // notification_1hr_sent = 0
	);
	
	error_log( '[Notification Cron] Query: ' . $query );
	
	$results = $wpdb->get_results( $query );
	//phpcs:enable
	
	error_log( '[Notification Cron] Sessions found: ' . count( $results ) );
	
	// Process each result.
	foreach ( $results as $session ) {
		error_log( '[Notification Cron] Processing session ID: ' . $session->ID . ', date_time: ' . $session->date_time );
		$time_diff     = strtotime( $session->date_time ) - strtotime( $current_time );
		error_log( '[Notification Cron] Session ' . $session->ID . ' time_diff: ' . $time_diff . ' seconds (' . round( $time_diff / 3600, 2 ) . ' hours)' );
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
			// Method 1: Check settings field for ai_booking
			$is_ai_session = isset( $session->settings ) && strpos( $session->settings, 'ai_booking' ) !== false;
			
			// Method 2: If not detected by settings, check order meta
			if ( ! $is_ai_session && isset( $session->order_id ) && $session->order_id > 0 ) {
				$order = wc_get_order( $session->order_id );
				if ( $order ) {
					$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
					$is_ai_session_meta = $order->get_meta( 'is_ai_session' );
					$is_ai_session = $from_jalsah_ai || $is_ai_session_meta;
				}
			}
			
			error_log( '[Notification Cron] Session ' . $session->ID . ' is AI session: ' . ( $is_ai_session ? 'Yes' : 'No' ) );
			
			// 24-hour reminder.
			// Check if session is 19-24 hours away
			if ( $time_diff >= 68400 && $time_diff <= 86400 && ! $session->notification_24hr_sent ) { // 68400 = 19 hrs, 86400 = 24 hrs
				error_log( '[Notification Cron] Session ' . $session->ID . ' is eligible for 24hr notification (time_diff: ' . $time_diff . ' seconds)' );
				if ( $is_ai_session && function_exists( 'snks_send_whatsapp_template_message' ) ) {
					error_log( '[Notification Cron] Session ' . $session->ID . ' is AI session, sending WhatsApp notification' );
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
						error_log( '[Notification Cron] Sending WhatsApp notification to: ' . $billing_phone );
						$result = snks_send_whatsapp_template_message(
							$billing_phone,
							$settings['template_patient_rem_24h'],
						array( 'day' => $day_name, 'date' => $date, 'doctor' => $doctor_name, 'time' => $time )
						);
						error_log( '[Notification Cron] WhatsApp notification result: ' . ( is_wp_error( $result ) ? 'WP_Error' : 'Success' ) );
					}
				} elseif ( ! $is_ai_session ) {
					// Legacy SMS for non-AI sessions only
					error_log( '[Notification Cron] Session ' . $session->ID . ' - Not AI session, sending SMS' );
					if ( 'online' === $session->attendance_type ) {
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
				} else {
					error_log( '[Notification Cron] Session ' . $session->ID . ' - AI session but WhatsApp not sent (disabled or function missing)' );
				}

				//phpcs:disable
				$wpdb->update(
					$wpdb->prefix . 'snks_provider_timetable',
					array( 'notification_24hr_sent' => 1 ),
					array( 'ID' => $session->ID ),
					array( '%d' ),
					array( '%d' )
				);
				//phpcs:enable
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
