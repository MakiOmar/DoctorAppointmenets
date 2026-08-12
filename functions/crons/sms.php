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
 * Whether session-notification debug history is enabled.
 *
 * @return bool
 */
function snks_session_notif_debug_enabled() {
	return (string) get_option( 'snks_session_notif_debug_enabled', '1' ) === '1';
}

/**
 * Store one session-notification cron run report (newest first).
 *
 * @param array<string,mixed> $report Run report.
 * @return void
 */
function snks_session_notif_debug_store_run( array $report ) {
	update_option( 'snks_session_notif_last_run', $report, false );

	if ( ! snks_session_notif_debug_enabled() ) {
		return;
	}

	$log = get_option( 'snks_session_notif_debug_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, $report );
	$log = array_slice( $log, 0, 20 );
	update_option( 'snks_session_notif_debug_log', $log, false );
}

/**
 * Write a session-notification debug line to PHP error_log when WP_DEBUG_LOG is on.
 *
 * @param string              $message Short message.
 * @param array<string,mixed> $context Optional context.
 * @return void
 */
function snks_session_notif_debug_error_log( $message, array $context = array() ) {
	if ( ! ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
		return;
	}
	$line = '[SNKS session notif] ' . $message;
	if ( ! empty( $context ) ) {
		$line .= ' ' . wp_json_encode( $context );
	}
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $line );
}

/**
 * Normalize a send result for the debug report.
 *
 * @param mixed $result Send result (array, WP_Error, etc.).
 * @return array<string,mixed>
 */
function snks_session_notif_debug_normalize_result( $result ) {
	if ( is_wp_error( $result ) ) {
		return array(
			'ok'      => false,
			'code'    => $result->get_error_code(),
			'message' => $result->get_error_message(),
			'data'    => $result->get_error_data(),
		);
	}
	if ( is_array( $result ) ) {
		return array(
			'ok'   => true,
			'data' => $result,
		);
	}
	return array(
		'ok'   => null !== $result && false !== $result,
		'data' => $result,
	);
}

/**
 * Sends session notifications based on proximity to session time.
 *
 * This function checks for sessions in the next 24 hours or 1 hour,
 * and sends notifications accordingly. It ensures that notifications
 * are only sent once per time frame (24-hour and 1-hour).
 *
 * @param bool $force_debug Force storing a full debug report for this run.
 * @return array<string,mixed> Debug report for the run.
 */
function snks_send_session_notifications( $force_debug = false ) {
	global $wpdb;
	// Use WordPress local time to match how date_time is stored in database
	$current_time      = current_time( 'mysql' );
	$current_timestamp = current_time( 'timestamp' );
	$current_hour      = (int) current_time( 'H' );

	// Use local time bounds (not UTC) for upcoming window checks.
	$time_24_hours = date( 'Y-m-d H:i:s', strtotime( '+24 hours', $current_timestamp ) );
	$time_23_hours = date( 'Y-m-d H:i:s', strtotime( '+23 hours', $current_timestamp ) );
	$time_1_hour   = date( 'Y-m-d H:i:s', strtotime( '+1 hour', $current_timestamp ) );

	$use_meeting_timers = function_exists( 'snks_should_use_jitsi_meeting_timers' ) && snks_should_use_jitsi_meeting_timers();
	$google_meet_active = function_exists( 'snks_is_google_meet_active' ) && snks_is_google_meet_active();
	$wa_settings        = function_exists( 'snks_get_whatsapp_notification_settings' )
		? snks_get_whatsapp_notification_settings()
		: array( 'enabled' => '0' );
	$wa_enabled         = isset( $wa_settings['enabled'] ) && (string) $wa_settings['enabled'] === '1';

	$report = array(
		'ran_at'             => current_time( 'mysql' ),
		'current_time'       => $current_time,
		'current_hour'       => $current_hour,
		'windows'            => array(
			'24h' => array( $time_23_hours, $time_24_hours ),
			'1h'  => array( $current_time, $time_1_hour ),
		),
		'gates'              => array(
			'use_meeting_timers' => $use_meeting_timers,
			'google_meet_active' => $google_meet_active,
			'whatsapp_enabled'   => $wa_enabled,
			'hour_ok_for_24h'    => $current_hour >= 9,
			'note'               => 'Online 24h/1h reminders require use_meeting_timers=true (i.e. Google Meet inactive). 24h also requires local hour >= 9.',
		),
		'query_candidates'   => 0,
		'sessions'           => array(),
		'sent_24h'           => 0,
		'sent_1h'            => 0,
		'skipped'            => 0,
		'flagged_without_send' => 0,
	);

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
		ORDER BY date_time ASC
		LIMIT 20
		",
		'open',
		$time_23_hours,    // between +23h
		$time_24_hours,    // and +24h
		0,                 // notification_24hr_sent = 0
		$current_time,     // start now
		$time_1_hour,      // up to +1h
		0                  // notification_1hr_sent = 0
	);

	$results = $wpdb->get_results( $query );
	//phpcs:enable

	$report['query_candidates'] = is_array( $results ) ? count( $results ) : 0;
	$report['last_query_error'] = $wpdb->last_error ? $wpdb->last_error : null;

	if ( empty( $results ) ) {
		$report['summary'] = 'No open sessions in 23-24h or 0-1h windows with unsent flags (LIMIT 20).';
		snks_session_notif_debug_store_run( $report );
		snks_session_notif_debug_error_log( 'no candidates', array( 'windows' => $report['windows'], 'gates' => $report['gates'] ) );
		if ( $force_debug && ! snks_session_notif_debug_enabled() ) {
			// Still ensure last_run is stored (already done above).
		}
		return $report;
	}

	// Process each result.
	foreach ( $results as $session ) {
		$row = array(
			'ID'                      => (int) $session->ID,
			'date_time'               => $session->date_time,
			'client_id'               => (int) $session->client_id,
			'user_id'                 => isset( $session->user_id ) ? (int) $session->user_id : 0,
			'attendance_type'         => isset( $session->attendance_type ) ? $session->attendance_type : '',
			'notification_24hr_sent'  => (int) $session->notification_24hr_sent,
			'notification_1hr_sent'   => (int) $session->notification_1hr_sent,
			'actions'                 => array(),
			'skips'                   => array(),
		);

		$time_diff     = snks_diff_seconds( $session );
		$billing_phone = get_user_meta( $session->client_id, 'billing_phone', true );
		$user          = get_user_by( 'id', $session->client_id );
		$row['time_diff_seconds'] = (int) $time_diff;
		$row['time_diff_hours']   = round( $time_diff / HOUR_IN_SECONDS, 2 );

		if ( empty( $billing_phone ) && $user ) {
			$billing_phone = $user->user_login;
			$row['phone_source'] = 'user_login';
		} else {
			$row['phone_source'] = empty( $billing_phone ) ? 'none' : 'billing_phone';
		}

		if ( empty( $billing_phone ) ) {
			$row['skips'][] = 'empty_billing_phone';
			$report['skipped']++;
			$report['sessions'][] = $row;
			continue;
		}

		if ( ! $user ) {
			$row['skips'][] = 'client_user_missing';
			$report['skipped']++;
			$report['sessions'][] = $row;
			continue;
		}

		if ( in_array( 'doctor', (array) $user->roles, true ) && strpos( $billing_phone, '+2' ) === false ) {
			$billing_phone = '+20' . $billing_phone;
			$row['phone_normalized'] = true;
		}
		$row['phone_masked'] = substr( preg_replace( '/\D/', '', $billing_phone ), -4 );

		// Check if this is an AI session
		// Method 1: Check settings field for ai_booking
		$is_ai_session = isset( $session->settings ) && strpos( $session->settings, 'ai_booking' ) !== false;
		$ai_detect     = $is_ai_session ? 'settings_ai_booking' : '';

		// Method 2: If not detected by settings, check order meta
		if ( ! $is_ai_session && isset( $session->order_id ) && $session->order_id > 0 ) {
			$order = wc_get_order( $session->order_id );
			if ( $order ) {
				$from_jalsah_ai    = $order->get_meta( 'from_jalsah_ai' );
				$is_ai_session_meta = $order->get_meta( 'is_ai_session' );
				$is_ai_session      = $from_jalsah_ai || $is_ai_session_meta;
				if ( $is_ai_session ) {
					$ai_detect = 'order_meta';
				}
			}
		}
		$row['is_ai_session'] = (bool) $is_ai_session;
		$row['ai_detect']     = $ai_detect ? $ai_detect : 'none';

		$skip_timed_online = ! $use_meeting_timers && 'online' === $session->attendance_type;

		// 24-hour reminder.
		// Check if session is 23-24 hours away
		// Only send after 9 AM to avoid confusion (if sent between midnight and 5 AM, "tomorrow" might be misinterpreted)
		$in_24h_window = ( $time_diff >= ( 23 * HOUR_IN_SECONDS ) && $time_diff <= DAY_IN_SECONDS );
		if ( $in_24h_window && ! $session->notification_24hr_sent ) {
			$can_send_24h = true;
			if ( $current_hour < 9 ) {
				$row['skips'][] = '24h_before_9am_local';
				$can_send_24h   = false;
			}
			if ( $skip_timed_online ) {
				$row['skips'][] = '24h_skip_online_while_google_meet_active';
				$can_send_24h   = false;
			}

			if ( $can_send_24h ) {
				$sent_24h     = false;
				$flagged_24h  = false;
				$send_channel = '';

				if ( $is_ai_session && function_exists( 'snks_send_whatsapp_template_message' ) && $use_meeting_timers ) {
					if ( $wa_enabled ) {
						$doctor_name = function_exists( 'snks_get_therapist_name' ) ? snks_get_therapist_name( $session->user_id ) : 'المعالج';
						$day_name    = function_exists( 'snks_get_arabic_day_name' ) ? snks_get_arabic_day_name( $session->date_time ) : '';
						$date        = snks_format_session_datetime( $session, 'Y-m-d' );
						$time        = snks_format_session_datetime( $session, 'h:i a' );
						$template    = isset( $wa_settings['template_patient_rem_24h'] ) ? $wa_settings['template_patient_rem_24h'] : 'patient_rem_24h';

						$wa_result = snks_send_whatsapp_template_message(
							$billing_phone,
							$template,
							array(
								'day'    => $day_name,
								'date'   => $date,
								'doctor' => $doctor_name,
								'time'   => $time,
							)
						);
						$send_channel = 'whatsapp_24h';
						$norm         = snks_session_notif_debug_normalize_result( $wa_result );
						$row['actions'][] = array(
							'type'     => 'send_24h_whatsapp',
							'template' => $template,
							'result'   => $norm,
						);
						$sent_24h = ! empty( $norm['ok'] );
					} else {
						$row['skips'][] = '24h_ai_whatsapp_disabled';
					}
				} elseif ( $is_ai_session && ! $use_meeting_timers ) {
					$row['skips'][] = '24h_ai_requires_meeting_timers';
				} elseif ( ! $is_ai_session ) {
					// Legacy SMS for non-AI sessions only
					if ( 'online' === $session->attendance_type ) {
						$meeting_link = function_exists( 'snks_get_notification_meeting_link' )
							? snks_get_notification_meeting_link( $session->ID )
							: ( function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( $session->ID ) : '' );
						if ( $meeting_link ) {
							$message = sprintf(
								'نذكرك بموعد جلستك غدا الساعه %1$s للدخول للجلسة:  %2$s',
								snks_localize_time( snks_format_session_datetime( $session, 'h:i a' ) ),
								$meeting_link
							);
						} else {
							$message = sprintf(
								'نذكرك بموعد جلستك غدا الساعه %1$s. سيتم إرسال رابط Google Meet بعد تعيينه.',
								snks_localize_time( snks_format_session_datetime( $session, 'h:i a' ) )
							);
						}
						$sms_result   = send_sms_via_whysms( $billing_phone, $message );
						$send_channel = 'sms_24h_online';
						$norm         = snks_session_notif_debug_normalize_result( $sms_result );
						$row['actions'][] = array(
							'type'   => 'send_24h_sms',
							'result' => $norm,
						);
						$sent_24h = ! empty( $norm['ok'] );
					} else {
						$message = sprintf(
							'نذكرك بموعد جلستك غدا الساعه %1$s',
							snks_localize_time( snks_format_session_datetime( $session, 'h:i a' ) )
						);
						$sms_result   = send_sms_via_whysms( $billing_phone, $message );
						$send_channel = 'sms_24h_offline';
						$norm         = snks_session_notif_debug_normalize_result( $sms_result );
						$row['actions'][] = array(
							'type'   => 'send_24h_sms',
							'result' => $norm,
						);
						$sent_24h = ! empty( $norm['ok'] );
					}
				} else {
					$row['skips'][] = '24h_no_channel_matched';
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
				$flagged_24h = true;
				$row['actions'][] = array(
					'type'    => 'flag_24hr_sent',
					'channel' => $send_channel,
					'sent'    => $sent_24h,
				);

				if ( $sent_24h ) {
					$report['sent_24h']++;
				} elseif ( $flagged_24h ) {
					$report['flagged_without_send']++;
					$row['skips'][] = '24h_flagged_without_successful_send';
				}
			}
		} elseif ( $in_24h_window && $session->notification_24hr_sent ) {
			$row['skips'][] = '24h_already_flagged';
		}

		// 1-hour reminder.
		$in_1h_window = ( 'online' === $session->attendance_type && $time_diff > 0 && $time_diff <= HOUR_IN_SECONDS );
		if ( $in_1h_window && ! $session->notification_1hr_sent ) {
			if ( ! $use_meeting_timers ) {
				$row['skips'][] = '1h_requires_meeting_timers_google_meet_blocks';
				$report['skipped']++;
			} else {
				$sent_1h      = false;
				$send_channel = '';

				if ( $is_ai_session && function_exists( 'snks_send_whatsapp_template_message' ) ) {
					if ( $wa_enabled ) {
						$template  = isset( $wa_settings['template_patient_rem_1h'] ) ? $wa_settings['template_patient_rem_1h'] : 'patient_rem_1h';
						$wa_result = snks_send_whatsapp_template_message(
							$billing_phone,
							$template,
							array()
						);
						$send_channel = 'whatsapp_1h';
						$norm         = snks_session_notif_debug_normalize_result( $wa_result );
						$row['actions'][] = array(
							'type'     => 'send_1h_whatsapp',
							'template' => $template,
							'result'   => $norm,
						);
						$sent_1h = ! empty( $norm['ok'] );
					} else {
						$row['skips'][] = '1h_ai_whatsapp_disabled';
					}
				} else {
					// Legacy SMS notification for non-AI sessions
					$meeting_link = function_exists( 'snks_get_notification_meeting_link' )
						? snks_get_notification_meeting_link( $session->ID )
						: ( function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( $session->ID ) : '' );
					if ( $meeting_link ) {
						$message = sprintf(
							'باقي أقل من ساعة على موعد الجلسة، رابط الدخول للجلسة:%s',
							$meeting_link
						);
					} else {
						$message = 'باقي أقل من ساعة على موعد الجلسة. سيتم إرسال رابط Google Meet بعد تعيينه.';
					}
					$sms_result   = send_sms_via_whysms( $billing_phone, $message );
					$send_channel = 'sms_1h';
					$norm         = snks_session_notif_debug_normalize_result( $sms_result );
					$row['actions'][] = array(
						'type'   => 'send_1h_sms',
						'result' => $norm,
					);
					$sent_1h = ! empty( $norm['ok'] );
				}

				$wpdb->update(
					$wpdb->prefix . 'snks_provider_timetable',
					array( 'notification_1hr_sent' => 1 ),
					array( 'ID' => $session->ID ),
					array( '%d' ),
					array( '%d' )
				);
				//phpcs:enable
				$row['actions'][] = array(
					'type'    => 'flag_1hr_sent',
					'channel' => $send_channel,
					'sent'    => $sent_1h,
				);

				if ( $sent_1h ) {
					$report['sent_1h']++;
				} else {
					$report['flagged_without_send']++;
					$row['skips'][] = '1h_flagged_without_successful_send';
				}
			}
		} elseif ( $time_diff > 0 && $time_diff <= HOUR_IN_SECONDS && 'online' !== $session->attendance_type ) {
			$row['skips'][] = '1h_offline_not_eligible';
		} elseif ( $in_1h_window && $session->notification_1hr_sent ) {
			$row['skips'][] = '1h_already_flagged';
		}

		if ( empty( $row['actions'] ) && empty( $row['skips'] ) ) {
			$row['skips'][] = 'matched_sql_but_outside_php_time_windows';
			$report['skipped']++;
		} elseif ( empty( $row['actions'] ) ) {
			$report['skipped']++;
		}

		$report['sessions'][] = $row;
	}

	$report['summary'] = sprintf(
		'candidates=%d sent_24h=%d sent_1h=%d skipped=%d flagged_without_send=%d meeting_timers=%s hour=%d',
		$report['query_candidates'],
		$report['sent_24h'],
		$report['sent_1h'],
		$report['skipped'],
		$report['flagged_without_send'],
		$use_meeting_timers ? 'yes' : 'no',
		$current_hour
	);

	snks_session_notif_debug_store_run( $report );
	snks_session_notif_debug_error_log( $report['summary'], array( 'gates' => $report['gates'] ) );

	return $report;
}

/**
 * Sends notifications for users with open bookings tomorrow.
 * Runs on hourly cron; only sends after 9:00 (site local time) to avoid early-morning pushes.
 */
function send_booking_notifications() {
	global $wpdb;
	$table = $wpdb->prefix . 'snks_provider_timetable'; // Ensure table prefix is used.

	$current_hour = (int) current_time( 'H' );
	if ( $current_hour < 20 ) {
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
