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
	$status = isset( $timetable->session_status ) ? (string) $timetable->session_status : '';
	if ( in_array( $status, array( 'cancelled', 'waiting', 'pending', 'postponed' ), true ) ) {
		return false;
	}
	if ( ! in_array( $status, array( 'open', 'confirmed' ), true ) ) {
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
 * Whether missing Google Meet URL admin notices and emails are enabled.
 *
 * @return bool
 */
function snks_google_meet_missing_assignment_notify_enabled() {
	return '1' === get_option( 'snks_google_meet_missing_assignment_notify_enabled', '1' );
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
 * Build human-readable context for a session missing a Meet URL.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Session or booking ID.
 * @return array|null
 */
function snks_google_meet_missing_assignment_context( $type, $id ) {
	global $wpdb;

	$id   = absint( $id );
	$type = (string) $type;
	if ( ! $id || ! in_array( $type, array( 'timetable', 'rochtah' ), true ) ) {
		return null;
	}

	if ( 'timetable' === $type ) {
		if ( ! function_exists( 'snks_get_timetable_by' ) ) {
			return null;
		}
		$timetable = snks_get_timetable_by( 'ID', $id );
		if ( ! $timetable || ! snks_is_online_meeting_eligible( $timetable ) ) {
			return null;
		}
		$patient_name  = '';
		$therapist_name = '';
		if ( ! empty( $timetable->client_id ) && function_exists( 'snks_get_therapist_name' ) ) {
			$first = get_user_meta( $timetable->client_id, 'billing_first_name', true );
			$last  = get_user_meta( $timetable->client_id, 'billing_last_name', true );
			$patient_name = trim( $first . ' ' . $last );
		}
		if ( ! empty( $timetable->user_id ) && function_exists( 'snks_get_therapist_name' ) ) {
			$therapist_name = snks_get_therapist_name( $timetable->user_id );
		}
		return array(
			'type'           => 'timetable',
			'id'             => $id,
			'label'          => sprintf( __( 'Timetable #%d', 'shrinks' ), $id ),
			'patient_name'   => $patient_name,
			'therapist_name' => $therapist_name,
			'datetime'       => isset( $timetable->date_time ) ? $timetable->date_time : '',
			'order_id'       => isset( $timetable->order_id ) ? (int) $timetable->order_id : 0,
		);
	}

	$table   = $wpdb->prefix . 'snks_rochtah_bookings';
	$booking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT rb.*, t.display_name AS therapist_name
			FROM {$table} rb
			LEFT JOIN {$wpdb->users} t ON rb.therapist_id = t.ID
			WHERE rb.id = %d LIMIT 1",
			$id
		)
	);
	if ( ! $booking || 'confirmed' !== $booking->status ) {
		return null;
	}
	$patient_name = '';
	if ( ! empty( $booking->patient_id ) ) {
		$first = get_user_meta( $booking->patient_id, 'billing_first_name', true );
		$last  = get_user_meta( $booking->patient_id, 'billing_last_name', true );
		$patient_name = trim( $first . ' ' . $last );
	}
	$datetime = trim( (string) $booking->booking_date . ' ' . (string) $booking->booking_time );
	return array(
		'type'           => 'rochtah',
		'id'             => $id,
		'label'          => sprintf( __( 'Rochtah #%d', 'shrinks' ), $id ),
		'patient_name'   => $patient_name,
		'therapist_name' => isset( $booking->therapist_name ) ? (string) $booking->therapist_name : '',
		'datetime'       => $datetime,
		'order_id'       => 0,
	);
}

/**
 * Record and email admins: booked session has no Google Meet URL assigned.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Session or booking ID.
 * @return void
 */
function snks_google_meet_notify_missing_assignment( $type, $id ) {
	if ( ! snks_is_google_meet_active() || ! snks_google_meet_missing_assignment_notify_enabled() ) {
		return;
	}
	if ( snks_get_assigned_google_meet_row( $type, $id ) ) {
		snks_google_meet_clear_missing_assignment_notice( $type, $id );
		return;
	}

	$context = snks_google_meet_missing_assignment_context( $type, $id );
	if ( ! $context ) {
		return;
	}

	$key     = $type . '_' . $id;
	$notices = get_option( 'snks_google_meet_missing_assignments', array() );
	if ( ! is_array( $notices ) ) {
		$notices = array();
	}
	$notices[ $key ] = array_merge(
		$context,
		array(
			'updated_at' => time(),
		)
	);
	update_option( 'snks_google_meet_missing_assignments', $notices, false );

	$dedupe_key = 'snks_google_meet_missing_email_' . $key;
	if ( get_transient( $dedupe_key ) ) {
		return;
	}

	$admin_url = admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' );
	$subject   = sprintf(
		'[Jalsah] Google Meet URL required — %s',
		$context['label']
	);
	$body      = sprintf(
		"A booked online session needs a Google Meet URL assigned.\n\nType: %s\nID: %d\nPatient: %s\nTherapist: %s\nDate/time: %s\nOrder ID: %s\n\nAssign a URL: %s\nTime: %s",
		$context['type'],
		$context['id'],
		$context['patient_name'] ? $context['patient_name'] : '-',
		$context['therapist_name'] ? $context['therapist_name'] : '-',
		$context['datetime'] ? $context['datetime'] : '-',
		$context['order_id'] ? (string) $context['order_id'] : '-',
		$admin_url,
		wp_date( 'Y-m-d H:i:s' )
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	foreach ( snks_google_meet_low_pool_email_recipients() as $email ) {
		wp_mail( $email, $subject, $body, $headers );
	}

	set_transient( $dedupe_key, 1, DAY_IN_SECONDS );
}

/**
 * Remove a missing-assignment admin notice after a URL is assigned.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Session or booking ID.
 * @return void
 */
function snks_google_meet_clear_missing_assignment_notice( $type, $id ) {
	$key     = (string) $type . '_' . absint( $id );
	$notices = get_option( 'snks_google_meet_missing_assignments', array() );
	if ( ! is_array( $notices ) || ! isset( $notices[ $key ] ) ) {
		return;
	}
	unset( $notices[ $key ] );
	update_option( 'snks_google_meet_missing_assignments', $notices, false );
	delete_transient( 'snks_google_meet_missing_email_' . $key );
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
	if ( is_array( $data ) && ! empty( $data['message'] ) ) {
		$url = admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' );
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> <a href="%s">%s</a></p></div>',
			esc_html( $data['message'] ),
			esc_url( $url ),
			esc_html__( 'Manage Google Meet URLs', 'shrinks' )
		);
	}

	if ( ! snks_google_meet_missing_assignment_notify_enabled() ) {
		return;
	}

	$missing = get_option( 'snks_google_meet_missing_assignments', array() );
	if ( ! is_array( $missing ) || empty( $missing ) ) {
		return;
	}

	$url = admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' );
	printf(
		'<div class="notice notice-error"><p><strong>%s</strong></p><ul style="list-style:disc;margin-left:1.5em;">',
		esc_html__( 'Booked sessions are waiting for a Google Meet URL:', 'shrinks' )
	);
	foreach ( array_slice( $missing, 0, 10, true ) as $row ) {
		$line = sprintf(
			'%s — %s — %s',
			isset( $row['label'] ) ? $row['label'] : '',
			isset( $row['patient_name'] ) && $row['patient_name'] ? $row['patient_name'] : __( 'Patient', 'shrinks' ),
			isset( $row['datetime'] ) && $row['datetime'] ? $row['datetime'] : ''
		);
		printf( '<li>%s</li>', esc_html( $line ) );
	}
	if ( count( $missing ) > 10 ) {
		printf(
			'<li>%s</li>',
			esc_html(
				sprintf(
					/* translators: %d: additional count */
					__( '…and %d more.', 'shrinks' ),
					count( $missing ) - 10
				)
			)
		);
	}
	printf(
		'</ul><p><a href="%s">%s</a></p></div>',
		esc_url( $url ),
		esc_html__( 'Assign Google Meet URLs', 'shrinks' )
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
 * Validate timetable or Rochtah target for manual Meet assignment.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Timetable ID or Rochtah booking ID.
 * @return true|WP_Error
 */
function snks_validate_google_meet_assign_target( $type, $id ) {
	global $wpdb;

	$id   = absint( $id );
	$type = (string) $type;
	if ( ! $id || ! in_array( $type, array( 'timetable', 'rochtah' ), true ) ) {
		return new WP_Error( 'invalid_target', __( 'Invalid assignment target.', 'shrinks' ) );
	}

	if ( 'timetable' === $type ) {
		if ( ! function_exists( 'snks_get_timetable_by' ) ) {
			return new WP_Error( 'missing_helper', __( 'Timetable helper is unavailable.', 'shrinks' ) );
		}
		$timetable = snks_get_timetable_by( 'ID', $id );
		if ( ! $timetable ) {
			return new WP_Error( 'timetable_not_found', __( 'Timetable session not found.', 'shrinks' ) );
		}
		if ( ! snks_is_online_meeting_eligible( $timetable ) ) {
			return new WP_Error(
				'timetable_not_eligible',
				__( 'This timetable session is not eligible for an online meeting (must be online, booked, and not cancelled).', 'shrinks' )
			);
		}
		return true;
	}

	$table   = $wpdb->prefix . 'snks_rochtah_bookings';
	$booking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, status FROM {$table} WHERE id = %d LIMIT 1",
			$id
		)
	);
	if ( ! $booking ) {
		return new WP_Error( 'rochtah_not_found', __( 'Rochtah booking not found.', 'shrinks' ) );
	}
	if ( 'confirmed' !== $booking->status ) {
		return new WP_Error(
			'rochtah_not_confirmed',
			__( 'Rochtah booking must be confirmed before assigning a Meet URL.', 'shrinks' )
		);
	}

	return true;
}

/**
 * Assign a specific pool URL to a timetable or Rochtah booking (manual admin / code).
 *
 * @param int    $url_id     Pool row ID.
 * @param string $type       timetable|rochtah.
 * @param int    $session_id Timetable ID or Rochtah booking ID.
 * @return true|WP_Error
 */
function snks_assign_google_meet_url_manual( $url_id, $type, $session_id ) {
	global $wpdb;

	$url_id     = absint( $url_id );
	$session_id = absint( $session_id );
	$type       = (string) $type;

	$valid = snks_validate_google_meet_assign_target( $type, $session_id );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	if ( ! $url_id ) {
		return new WP_Error( 'invalid_url', __( 'Invalid Google Meet URL ID.', 'shrinks' ) );
	}

	$existing = snks_get_assigned_google_meet_row( $type, $session_id );
	if ( $existing && (int) $existing->id === $url_id ) {
		return true;
	}

	if ( $existing ) {
		$released = snks_unassign_google_meet_url( (int) $existing->id );
		if ( is_wp_error( $released ) ) {
			return $released;
		}
	}

	$table       = snks_google_meet_urls_table_name();
	$assigned_at = current_time( 'mysql' );

	$wpdb->query( 'START TRANSACTION' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND status = 'available' LIMIT 1 FOR UPDATE",
			$url_id
		)
	);

	if ( ! $row ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error(
			'url_not_available',
			__( 'This Google Meet URL is not available (already assigned or disabled).', 'shrinks' )
		);
	}

	if ( 'timetable' === $type ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ok = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'assigned', assigned_at = %s, assigned_timetable_id = %d, assigned_rochtah_booking_id = NULL WHERE id = %d AND status = 'available'",
				$assigned_at,
				$session_id,
				$url_id
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ok = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'assigned', assigned_at = %s, assigned_rochtah_booking_id = %d, assigned_timetable_id = NULL WHERE id = %d AND status = 'available'",
				$assigned_at,
				$session_id,
				$url_id
			)
		);
	}

	if ( ! $ok ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'assign_failed', __( 'Failed to assign Google Meet URL.', 'shrinks' ) );
	}

	$wpdb->query( 'COMMIT' );
	snks_google_meet_maybe_alert_low_pool();
	snks_google_meet_clear_missing_assignment_notice( $type, $session_id );

	/**
	 * Fires after a Google Meet URL is manually assigned to a session.
	 *
	 * @param int    $url_id     Pool row ID.
	 * @param string $type       timetable|rochtah.
	 * @param int    $session_id Timetable or Rochtah booking ID.
	 * @param object $row        Assigned pool row.
	 */
	do_action( 'snks_google_meet_assigned', $url_id, $type, $session_id, $row );

	return true;
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

	$valid = snks_validate_google_meet_assign_target( $type, $id );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	if ( snks_get_assigned_google_meet_row( $type, $id ) ) {
		return true;
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
	snks_google_meet_clear_missing_assignment_notice( $type, $id );

	/**
	 * Fires after a Google Meet URL is assigned to a session.
	 *
	 * @param int    $row_id Pool row ID.
	 * @param string $type   timetable|rochtah.
	 * @param int    $id     Timetable or Rochtah booking ID.
	 * @param object $row    Assigned pool row.
	 */
	do_action( 'snks_google_meet_assigned', $row_id, $type, $id, $row );

	return true;
}

/**
 * Unassign a Google Meet URL by pool row ID and return it to the available pool.
 *
 * @param int   $url_id Pool row ID in snks_google_meet_urls.
 * @param array $args   Optional: silent (bool) skip missing-assignment admin alert.
 * @return true|WP_Error
 */
function snks_unassign_google_meet_url( $url_id, $args = array() ) {
	global $wpdb;

	$url_id = absint( $url_id );
	$silent = ! empty( $args['silent'] );
	$table  = snks_google_meet_urls_table_name();
	if ( ! $url_id ) {
		return new WP_Error( 'invalid_id', __( 'Invalid Google Meet URL ID.', 'shrinks' ) );
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			$url_id
		)
	);

	if ( ! $row ) {
		return new WP_Error( 'not_found', __( 'Google Meet URL not found.', 'shrinks' ) );
	}

	if ( 'assigned' !== $row->status ) {
		return new WP_Error( 'not_assigned', __( 'This URL is not currently assigned.', 'shrinks' ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET status = 'available', assigned_timetable_id = NULL, assigned_rochtah_booking_id = NULL, assigned_at = NULL WHERE id = %d AND status = 'assigned'",
			$url_id
		)
	);

	if ( ! $updated ) {
		return new WP_Error( 'unassign_failed', __( 'Failed to unassign Google Meet URL.', 'shrinks' ) );
	}

	snks_google_meet_maybe_alert_low_pool();

	/**
	 * Fires after a Google Meet URL is manually or programmatically unassigned.
	 *
	 * @param int    $url_id Pool row ID.
	 * @param object $row    Row snapshot before unassign.
	 */
	do_action( 'snks_google_meet_unassigned', $url_id, $row );

	if ( ! $silent ) {
		if ( ! empty( $row->assigned_timetable_id ) ) {
			snks_google_meet_notify_missing_assignment( 'timetable', (int) $row->assigned_timetable_id );
		} elseif ( ! empty( $row->assigned_rochtah_booking_id ) ) {
			snks_google_meet_notify_missing_assignment( 'rochtah', (int) $row->assigned_rochtah_booking_id );
		}
	}

	return true;
}

/**
 * Delete a Google Meet URL from the pool.
 *
 * @param int  $url_id          Pool row ID.
 * @param bool $allow_assigned    When false, only available/disabled rows can be deleted.
 * @return true|WP_Error
 */
function snks_delete_google_meet_url( $url_id, $allow_assigned = false ) {
	global $wpdb;

	$url_id = absint( $url_id );
	$table  = snks_google_meet_urls_table_name();
	if ( ! $url_id ) {
		return new WP_Error( 'invalid_id', __( 'Invalid Google Meet URL ID.', 'shrinks' ) );
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			$url_id
		)
	);

	if ( ! $row ) {
		return new WP_Error( 'not_found', __( 'Google Meet URL not found.', 'shrinks' ) );
	}

	if ( 'assigned' === $row->status && ! $allow_assigned ) {
		return new WP_Error(
			'assigned_delete_blocked',
			__( 'Assigned URLs must be unassigned before deletion, or use bulk delete.', 'shrinks' )
		);
	}

	$deleted = $wpdb->delete( $table, array( 'id' => $url_id ), array( '%d' ) );
	if ( ! $deleted ) {
		return new WP_Error( 'delete_failed', __( 'Failed to delete Google Meet URL.', 'shrinks' ) );
	}

	snks_google_meet_maybe_alert_low_pool();

	/**
	 * Fires after a Google Meet URL row is removed from the pool.
	 *
	 * @param int    $url_id Pool row ID.
	 * @param object $row    Row snapshot before delete.
	 */
	do_action( 'snks_google_meet_deleted', $url_id, $row );

	return true;
}

/**
 * Bulk unassign Google Meet URLs.
 *
 * @param array $url_ids Pool row IDs.
 * @return array{success:int,skipped:int,errors:array}
 */
function snks_bulk_unassign_google_meet_urls( $url_ids ) {
	$url_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $url_ids ) ) ) );
	$result  = array(
		'success' => 0,
		'skipped' => 0,
		'errors'  => array(),
	);

	foreach ( $url_ids as $url_id ) {
		$outcome = snks_unassign_google_meet_url( $url_id );
		if ( is_wp_error( $outcome ) ) {
			if ( 'not_assigned' === $outcome->get_error_code() ) {
				++$result['skipped'];
			} else {
				$result['errors'][] = sprintf(
					/* translators: 1: URL id 2: error message */
					__( 'URL #%1$d: %2$s', 'shrinks' ),
					$url_id,
					$outcome->get_error_message()
				);
			}
			continue;
		}
		++$result['success'];
	}

	return $result;
}

/**
 * Bulk delete Google Meet URLs from the pool.
 *
 * @param array $url_ids         Pool row IDs.
 * @param bool  $allow_assigned    Allow deleting assigned rows (sessions lose their Meet link).
 * @return array{success:int,skipped:int,errors:array}
 */
function snks_bulk_delete_google_meet_urls( $url_ids, $allow_assigned = true ) {
	$url_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $url_ids ) ) ) );
	$result  = array(
		'success' => 0,
		'skipped' => 0,
		'errors'  => array(),
	);

	foreach ( $url_ids as $url_id ) {
		$outcome = snks_delete_google_meet_url( $url_id, $allow_assigned );
		if ( is_wp_error( $outcome ) ) {
			if ( 'assigned_delete_blocked' === $outcome->get_error_code() ) {
				++$result['skipped'];
			} else {
				$result['errors'][] = sprintf(
					/* translators: 1: URL id 2: error message */
					__( 'URL #%1$d: %2$s', 'shrinks' ),
					$url_id,
					$outcome->get_error_message()
				);
			}
			continue;
		}
		++$result['success'];
	}

	return $result;
}

/**
 * Release Meet URL assigned to a session.
 *
 * @param string $type timetable|rochtah.
 * @param int    $id   Timetable ID or Rochtah booking ID.
 * @return void
 */
function snks_release_google_meet_url( $type, $id ) {
	$row = snks_get_assigned_google_meet_row( $type, $id );
	if ( $row ) {
		snks_unassign_google_meet_url( (int) $row->id );
	}
}

/**
 * Move an assigned Google Meet URL from one timetable to another (appointment change/reschedule).
 * Does not pull a new URL from the pool. Clears any Meet assignment on the old timetable.
 *
 * @param int $from_timetable_id Previous timetable ID.
 * @param int $to_timetable_id   New timetable ID.
 * @return true|WP_Error
 */
function snks_transfer_google_meet_url_timetable( $from_timetable_id, $to_timetable_id ) {
	if ( ! snks_is_google_meet_active() ) {
		return true;
	}

	global $wpdb;

	$from_timetable_id = absint( $from_timetable_id );
	$to_timetable_id   = absint( $to_timetable_id );
	$table             = snks_google_meet_urls_table_name();

	if ( ! $from_timetable_id || ! $to_timetable_id ) {
		return new WP_Error( 'invalid_id', __( 'Invalid timetable ID for Google Meet transfer.', 'shrinks' ) );
	}

	if ( $from_timetable_id === $to_timetable_id ) {
		return true;
	}

	$row = snks_get_assigned_google_meet_row( 'timetable', $from_timetable_id );
	if ( ! $row ) {
		// Reschedule without a prior Meet URL: remove any auto-assigned URL on the new slot; do not assign fresh.
		snks_release_google_meet_url( 'timetable', $to_timetable_id );
		return true;
	}

	$existing_on_new = snks_get_assigned_google_meet_row( 'timetable', $to_timetable_id );
	if ( $existing_on_new && (int) $existing_on_new->id !== (int) $row->id ) {
		$unassign = snks_unassign_google_meet_url( (int) $existing_on_new->id, array( 'silent' => true ) );
		if ( is_wp_error( $unassign ) ) {
			return $unassign;
		}
	}

	$wpdb->query( 'START TRANSACTION' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET assigned_timetable_id = %d, assigned_at = %s WHERE id = %d AND status = 'assigned' AND assigned_timetable_id = %d",
			$to_timetable_id,
			current_time( 'mysql' ),
			(int) $row->id,
			$from_timetable_id
		)
	);

	if ( ! $updated ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'transfer_failed', __( 'Failed to transfer Google Meet URL to the new appointment.', 'shrinks' ) );
	}

	$wpdb->query( 'COMMIT' );

	if ( function_exists( 'snks_migrate_meeting_token_timetable' ) ) {
		snks_migrate_meeting_token_timetable( $from_timetable_id, $to_timetable_id );
	}

	snks_google_meet_clear_missing_assignment_notice( 'timetable', $to_timetable_id );

	/**
	 * Fires after a Google Meet URL is moved from one timetable to another.
	 *
	 * @param int $row_id            Pool row ID.
	 * @param int $from_timetable_id Old timetable ID.
	 * @param int $to_timetable_id   New timetable ID.
	 */
	do_action( 'snks_google_meet_transferred', (int) $row->id, $from_timetable_id, $to_timetable_id );

	return true;
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
 * Meeting link for outbound notifications (SMS, WhatsApp, email).
 * Returns direct Google Meet URL when Meet is active, otherwise the app shortlink.
 *
 * @param int $timetable_id Timetable ID.
 * @return string
 */
function snks_get_notification_meeting_link( $timetable_id ) {
	$timetable_id = absint( $timetable_id );
	if ( ! $timetable_id ) {
		return '';
	}

	if ( ! snks_is_google_meet_active() ) {
		return function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( $timetable_id ) : '';
	}

	$row = snks_get_assigned_google_meet_row( 'timetable', $timetable_id );
	if ( $row && ! empty( $row->meet_url ) ) {
		return $row->meet_url;
	}

	snks_google_meet_notify_missing_assignment( 'timetable', $timetable_id );
	return '';
}

/**
 * Meeting link for Rochtah notifications.
 *
 * @param int $booking_id Rochtah booking ID.
 * @return string
 */
function snks_get_notification_meeting_link_for_rochtah( $booking_id ) {
	$booking_id = absint( $booking_id );
	if ( ! $booking_id ) {
		return '';
	}

	if ( ! snks_is_google_meet_active() ) {
		if ( function_exists( 'snks_get_session_meeting_for_rochtah' ) ) {
			$meeting = snks_get_session_meeting_for_rochtah( $booking_id );
			if ( ! empty( $meeting['meeting_url'] ) ) {
				return $meeting['meeting_url'];
			}
			if ( ! empty( $meeting['join_url'] ) ) {
				return $meeting['join_url'];
			}
		}
		return '';
	}

	$row = snks_get_assigned_google_meet_row( 'rochtah', $booking_id );
	if ( $row && ! empty( $row->meet_url ) ) {
		return $row->meet_url;
	}

	snks_google_meet_notify_missing_assignment( 'rochtah', $booking_id );
	return '';
}

/**
 * Build meeting payload for a timetable session.
 *
 * @param int $timetable_id Timetable ID.
 * @return array
 */
function snks_get_session_meeting_for_timetable( $timetable_id ) {
	$timetable_id = absint( $timetable_id );

	if ( snks_is_google_meet_active() ) {
		$row = snks_get_assigned_google_meet_row( 'timetable', $timetable_id );
		$join_url = $row ? $row->meet_url : '';
		if ( ! $join_url && $timetable_id ) {
			snks_google_meet_notify_missing_assignment( 'timetable', $timetable_id );
		}
		return array(
			'provider'             => 'google_meet',
			'join_url'             => $join_url,
			'google_meet_join_url' => $join_url,
			'shortlink'            => '',
			'session_link'         => $join_url,
			'room_name'            => '',
			'timetable_id'         => $timetable_id,
			'live_stream_provider' => 'google_meet',
			'use_meeting_timers'   => false,
		);
	}

	$shortlink = function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( $timetable_id ) : '';
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
		if ( ! $join_url && $booking_id ) {
			snks_google_meet_notify_missing_assignment( 'rochtah', $booking_id );
		}
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
	if ( ! $slot_id || ! snks_is_google_meet_active() || ! empty( $data['skip_meet_assign'] ) ) {
		return;
	}
	$timetable = function_exists( 'snks_get_timetable_by' ) ? snks_get_timetable_by( 'ID', $slot_id ) : null;
	if ( ! snks_is_online_meeting_eligible( $timetable ) ) {
		return;
	}
	$result = snks_ensure_session_meeting_assigned( 'timetable', $slot_id );
	if ( is_wp_error( $result ) ) {
		error_log( 'Google Meet assign failed for timetable ' . $slot_id . ': ' . $result->get_error_message() );
		snks_google_meet_notify_missing_assignment( 'timetable', $slot_id );
		return;
	}
	if ( ! snks_get_assigned_google_meet_row( 'timetable', $slot_id ) ) {
		snks_google_meet_notify_missing_assignment( 'timetable', $slot_id );
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
	$booking_id = absint( $booking_id );
	$result     = snks_ensure_session_meeting_assigned( 'rochtah', $booking_id );
	if ( is_wp_error( $result ) || ! snks_get_assigned_google_meet_row( 'rochtah', $booking_id ) ) {
		snks_google_meet_notify_missing_assignment( 'rochtah', $booking_id );
	}
	return $result;
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
