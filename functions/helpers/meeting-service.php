<?php
/**
 * Live streaming meeting service (Jitsi vs Google Meet).
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Table name for Google Meet URL pool.
 *
 * @return string
 */
function snks_google_meet_urls_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'snks_google_meet_urls';
}

/**
 * Active live stream provider.
 *
 * @return string jitsi|google_meet
 */
function snks_get_live_stream_provider() {
	$provider = get_option( 'snks_live_stream_provider', 'jitsi' );
	return in_array( $provider, array( 'jitsi', 'google_meet' ), true ) ? $provider : 'jitsi';
}

/**
 * Whether Google Meet mode is enabled site-wide.
 *
 * @return bool
 */
function snks_is_google_meet_active() {
	return 'google_meet' === snks_get_live_stream_provider();
}

/**
 * Whether Jitsi timer/polling coordination should run.
 *
 * @return bool
 */
function snks_should_use_jitsi_meeting_timers() {
	return ! snks_is_google_meet_active();
}

/**
 * Normalize a Google Meet URL for storage.
 *
 * @param string $url Raw URL.
 * @return string
 */
function snks_normalize_google_meet_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	$parsed = wp_parse_url( $url );
	if ( empty( $parsed['host'] ) ) {
		return '';
	}
	$host = strtolower( $parsed['host'] );
	if ( 'meet.google.com' !== $host && 'www.meet.google.com' !== $host ) {
		return '';
	}
	$path = isset( $parsed['path'] ) ? $parsed['path'] : '';
	$path = untrailingslashit( $path );
	if ( '' === $path || '/' === $path ) {
		return '';
	}
	return 'https://meet.google.com' . $path;
}

/**
 * Validate Google Meet URL.
 *
 * @param string $url URL.
 * @return bool
 */
function snks_validate_google_meet_url( $url ) {
	return '' !== snks_normalize_google_meet_url( $url );
}

/**
 * Whether a timetable row is eligible for an online meeting assignment.
 *
 * @param object|null $timetable Timetable row.
 * @return bool
 */
function snks_is_online_meeting_eligible( $timetable ) {
	if ( ! $timetable || empty( $timetable->attendance_type ) || 'online' !== $timetable->attendance_type ) {
		return false;
	}
	if ( isset( $timetable->session_status ) && 'cancelled' === $timetable->session_status ) {
		return false;
	}
	$client_id = isset( $timetable->client_id ) ? absint( $timetable->client_id ) : 0;
	return $client_id > 0;
}

/**
 * Count available pool URLs.
 *
 * @return int
 */
function snks_google_meet_count_available() {
	global $wpdb;
	$table = snks_google_meet_urls_table_name();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'available'" );
}

/**
 * Get low-pool notification email recipients.
 *
 * @return array
 */
function snks_google_meet_low_pool_email_recipients() {
	$raw = get_option( 'snks_google_meet_low_pool_notify_emails', '' );
	$emails = array();
	if ( is_string( $raw ) && '' !== trim( $raw ) ) {
		foreach ( preg_split( '/[\s,;]+/', $raw ) as $part ) {
			$part = sanitize_email( trim( $part ) );
			if ( is_email( $part ) ) {
				$emails[] = $part;
			}
		}
	}
	if ( empty( $emails ) ) {
		$admin = get_option( 'admin_email' );
		if ( is_email( $admin ) ) {
			$emails[] = $admin;
		}
	}
	return array_unique( $emails );
}

/**
 * Maybe send low-pool admin notice + email.
 *
 * @return void
 */
function snks_google_meet_maybe_alert_low_pool() {
	if ( ! snks_is_google_meet_active() ) {
		delete_transient( 'snks_google_meet_low_pool_admin_notice' );
		update_option( 'snks_google_meet_low_pool_was_low', '0' );
		return;
	}

	if ( '1' !== get_option( 'snks_google_meet_low_pool_notify_enabled', '1' ) ) {
		return;
	}

	$threshold = max( 1, (int) get_option( 'snks_google_meet_low_pool_threshold', 10 ) );
	$available = snks_google_meet_count_available();

	if ( $available >= $threshold ) {
		update_option( 'snks_google_meet_low_pool_was_low', '0' );
		delete_transient( 'snks_google_meet_low_pool_admin_notice' );
		return;
	}

	$message = sprintf(
		/* translators: 1: available count, 2: threshold */
		__( 'Google Meet pool low: %1$d URLs available (threshold: %2$d).', 'shrinks' ),
		$available,
		$threshold
	);
	set_transient(
		'snks_google_meet_low_pool_admin_notice',
		array(
			'message'   => $message,
			'available' => $available,
			'threshold' => $threshold,
		),
		12 * HOUR_IN_SECONDS
	);

	$last_sent = (int) get_option( 'snks_google_meet_low_pool_last_notified_at', 0 );
	$now       = time();
	if ( $last_sent > 0 && ( $now - $last_sent ) < DAY_IN_SECONDS ) {
		return;
	}

	global $wpdb;
	$table    = snks_google_meet_urls_table_name();
	$assigned = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'assigned'" );
	$disabled = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'disabled'" );

	$admin_url = admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' );
	$subject   = $available === 0
		? sprintf( '[Jalsah] CRITICAL: Google Meet pool empty (%d available)', $available )
		: sprintf( '[Jalsah] Google Meet URLs running low (%d left)', $available );

	$body = sprintf(
		"Google Meet URL pool alert\n\nAvailable: %d\nAssigned: %d\nDisabled: %d\nThreshold: %d\n\nManage URLs: %s\nTime: %s",
		$available,
		$assigned,
		$disabled,
		$threshold,
		$admin_url,
		wp_date( 'Y-m-d H:i:s' )
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	foreach ( snks_google_meet_low_pool_email_recipients() as $email ) {
		wp_mail( $email, $subject, $body, $headers );
	}

	update_option( 'snks_google_meet_low_pool_last_notified_at', $now );
	update_option( 'snks_google_meet_low_pool_last_count', $available );
	update_option( 'snks_google_meet_low_pool_was_low', '1' );
}

/**
 * Admin notice for low Google Meet pool.
 *
 * @return void
 */
function snks_google_meet_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) || ! snks_is_google_meet_active() ) {
		return;
	}
	$data = get_transient( 'snks_google_meet_low_pool_admin_notice' );
	if ( ! is_array( $data ) || empty( $data['message'] ) ) {
		return;
	}
	$url = admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' );
	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> <a href="%s">%s</a></p></div>',
		esc_html( $data['message'] ),
		esc_url( $url ),
		esc_html__( 'Manage Google Meet URLs', 'shrinks' )
	);
}
add_action( 'admin_notices', 'snks_google_meet_admin_notices' );

/**
 * Bulk insert unique Google Meet URLs from line-separated text.
 *
 * @param string $text Textarea content.
 * @return array{inserted:int,skipped_duplicate:int,skipped_invalid:int}
 */
function snks_bulk_insert_google_meet_urls( $text ) {
	global $wpdb;
	$table   = snks_google_meet_urls_table_name();
	$lines   = preg_split( '/\r\n|\r|\n/', (string) $text );
	$seen    = array();
	$result  = array(
		'inserted'          => 0,
		'skipped_duplicate' => 0,
		'skipped_invalid'   => 0,
	);

	foreach ( $lines as $line ) {
		$normalized = snks_normalize_google_meet_url( $line );
		if ( '' === $normalized ) {
			++$result['skipped_invalid'];
			continue;
		}
		if ( isset( $seen[ $normalized ] ) ) {
			++$result['skipped_duplicate'];
			continue;
		}
		$seen[ $normalized ] = true;

		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE meet_url = %s LIMIT 1",
				$normalized
			)
		);
		if ( $exists > 0 ) {
			++$result['skipped_duplicate'];
			continue;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'meet_url' => $normalized,
				'status'   => 'available',
			),
			array( '%s', '%s' )
		);
		if ( $inserted ) {
			++$result['inserted'];
		}
	}

	snks_google_meet_maybe_alert_low_pool();
	return $result;
}

/**
 * Assign first available Meet URL to a session.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Timetable ID or Rochtah booking ID.
 * @return true|WP_Error
 */
function snks_assign_google_meet_url( $type, $id ) {
	global $wpdb;

	$id   = absint( $id );
	$type = (string) $type;
	if ( ! $id || ! in_array( $type, array( 'timetable', 'rochtah' ), true ) ) {
		return new WP_Error( 'invalid_assign', __( 'Invalid meeting assignment.', 'shrinks' ) );
	}

	$table = snks_google_meet_urls_table_name();
	$wpdb->query( 'START TRANSACTION' );

	$row = $wpdb->get_row(
		"SELECT * FROM {$table} WHERE status = 'available' ORDER BY id ASC LIMIT 1 FOR UPDATE"
	);

	if ( ! $row ) {
		$wpdb->query( 'ROLLBACK' );
		snks_google_meet_maybe_alert_low_pool();
		return new WP_Error(
			'meet_pool_empty',
			__( 'No Google Meet URLs available. Please contact the administrator.', 'shrinks' ),
			array( 'status' => 503 )
		);
	}

	$assigned_at = current_time( 'mysql' );
	$row_id      = (int) $row->id;

	if ( 'timetable' === $type ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ok = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'assigned', assigned_at = %s, assigned_timetable_id = %d, assigned_rochtah_booking_id = NULL WHERE id = %d AND status = 'available'",
				$assigned_at,
				$id,
				$row_id
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ok = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'assigned', assigned_at = %s, assigned_rochtah_booking_id = %d, assigned_timetable_id = NULL WHERE id = %d AND status = 'available'",
				$assigned_at,
				$id,
				$row_id
			)
		);
	}
	if ( false === $ok ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'assign_failed', __( 'Failed to assign Google Meet URL.', 'shrinks' ) );
	}

	$wpdb->query( 'COMMIT' );
	snks_google_meet_maybe_alert_low_pool();
	return true;
}

/**
 * Release Meet URL assigned to a session.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Timetable ID or Rochtah booking ID.
 * @return void
 */
function snks_release_google_meet_url( $type, $id ) {
	global $wpdb;

	$id    = absint( $id );
	$table = snks_google_meet_urls_table_name();
	if ( ! $id ) {
		return;
	}

	if ( 'timetable' === $type ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'available', assigned_timetable_id = NULL, assigned_rochtah_booking_id = NULL, assigned_at = NULL WHERE assigned_timetable_id = %d AND status = 'assigned'",
				$id
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'available', assigned_timetable_id = NULL, assigned_rochtah_booking_id = NULL, assigned_at = NULL WHERE assigned_rochtah_booking_id = %d AND status = 'assigned'",
				$id
			)
		);
	}

	snks_google_meet_maybe_alert_low_pool();
}

/**
 * Get assigned Meet URL row for a session.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   ID.
 * @return object|null
 */
function snks_get_assigned_google_meet_row( $type, $id ) {
	global $wpdb;

	$id    = absint( $id );
	$table = snks_google_meet_urls_table_name();
	if ( ! $id ) {
		return null;
	}

	if ( 'timetable' === $type ) {
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE assigned_timetable_id = %d AND status = 'assigned' LIMIT 1",
				$id
			)
		);
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE assigned_rochtah_booking_id = %d AND status = 'assigned' LIMIT 1",
			$id
		)
	);
}

/**
 * Ensure Google Meet URL is assigned when provider is Meet (idempotent).
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   ID.
 * @return true|WP_Error
 */
function snks_ensure_session_meeting_assigned( $type, $id ) {
	if ( ! snks_is_google_meet_active() ) {
		return true;
	}

	if ( snks_get_assigned_google_meet_row( $type, $id ) ) {
		return true;
	}

	return snks_assign_google_meet_url( $type, $id );
}

/**
 * Build meeting payload for a timetable session.
 *
 * @param int $timetable_id Timetable ID.
 * @return array
 */
function snks_get_session_meeting_for_timetable( $timetable_id ) {
	$timetable_id = absint( $timetable_id );
	$shortlink    = function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( $timetable_id ) : '';

	if ( snks_is_google_meet_active() ) {
		$row = snks_get_assigned_google_meet_row( 'timetable', $timetable_id );
		$join_url = $row ? $row->meet_url : '';
		return array(
			'provider'             => 'google_meet',
			'join_url'             => $join_url,
			'google_meet_join_url' => $join_url,
			'shortlink'            => $shortlink,
			'session_link'         => $shortlink,
			'room_name'            => '',
			'timetable_id'         => $timetable_id,
			'live_stream_provider' => 'google_meet',
			'use_meeting_timers'   => false,
		);
	}

	$room_name = $timetable_id ? ( (string) $timetable_id . ' جلسة' ) : '';
	return array(
		'provider'             => 'jitsi',
		'join_url'             => $shortlink,
		'google_meet_join_url' => '',
		'shortlink'            => $shortlink,
		'session_link'         => $shortlink,
		'room_name'            => $room_name,
		'timetable_id'         => $timetable_id,
		'live_stream_provider' => 'jitsi',
		'use_meeting_timers'   => true,
	);
}

/**
 * Build meeting payload for a Rochtah booking.
 *
 * @param int $booking_id Rochtah booking ID.
 * @return array
 */
function snks_get_session_meeting_for_rochtah( $booking_id ) {
	$booking_id = absint( $booking_id );

	if ( snks_is_google_meet_active() ) {
		$row      = snks_get_assigned_google_meet_row( 'rochtah', $booking_id );
		$join_url = $row ? $row->meet_url : '';
		return array(
			'provider'             => 'google_meet',
			'join_url'             => $join_url,
			'meeting_url'          => $join_url,
			'google_meet_join_url' => $join_url,
			'room_name'            => '',
			'booking_id'           => $booking_id,
			'live_stream_provider' => 'google_meet',
			'use_meeting_timers'   => false,
		);
	}

	$room_name   = '';
	$meeting_url = '';
	if ( function_exists( 'snks_generate_rochtah_meeting_link' ) ) {
		$details     = snks_generate_rochtah_meeting_link( $booking_id );
		$room_name   = isset( $details['room_name'] ) ? $details['room_name'] : '';
		$meeting_url = isset( $details['meeting_url'] ) ? $details['meeting_url'] : '';
	}

	return array(
		'provider'             => 'jitsi',
		'join_url'             => $meeting_url,
		'meeting_url'          => $meeting_url,
		'google_meet_join_url' => '',
		'room_name'            => $room_name,
		'booking_id'           => $booking_id,
		'live_stream_provider' => 'jitsi',
		'use_meeting_timers'   => true,
	);
}

/**
 * Hook: assign Meet URL when AI appointment is created (online only).
 *
 * @param int   $slot_id Slot ID.
 * @param array $data    Appointment data.
 * @return void
 */
function snks_meeting_service_on_appointment_created( $slot_id, $data ) {
	$slot_id = absint( $slot_id );
	if ( ! $slot_id || ! snks_is_google_meet_active() ) {
		return;
	}
	$timetable = function_exists( 'snks_get_timetable_by' ) ? snks_get_timetable_by( 'ID', $slot_id ) : null;
	if ( ! snks_is_online_meeting_eligible( $timetable ) ) {
		return;
	}
	$result = snks_ensure_session_meeting_assigned( 'timetable', $slot_id );
	if ( is_wp_error( $result ) ) {
		error_log( 'Google Meet assign failed for timetable ' . $slot_id . ': ' . $result->get_error_message() );
	}
}
add_action( 'snks_appointment_created', 'snks_meeting_service_on_appointment_created', 5, 2 );

/**
 * Assign Meet URL when a Rochtah booking is confirmed.
 *
 * @param int $booking_id Rochtah booking ID.
 * @return true|WP_Error
 */
function snks_meeting_on_rochtah_confirmed( $booking_id ) {
	if ( ! snks_is_google_meet_active() ) {
		return true;
	}
	return snks_ensure_session_meeting_assigned( 'rochtah', absint( $booking_id ) );
}

/**
 * REST: expose live stream settings for frontend.
 */
function snks_register_live_stream_settings_rest_route() {
	register_rest_route(
		'jalsah-ai/v1',
		'/live-stream-settings',
		array(
			'methods'             => 'GET',
			'callback'            => static function () {
				return rest_ensure_response(
					array(
						'provider'           => snks_get_live_stream_provider(),
						'google_meet_active' => snks_is_google_meet_active(),
						'use_meeting_timers' => snks_should_use_jitsi_meeting_timers(),
					)
				);
			},
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'snks_register_live_stream_settings_rest_route' );
