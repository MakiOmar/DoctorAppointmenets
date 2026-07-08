<?php
/**
 * Rochetah Google Meet booking helpers.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create or update the rochtah meet bookings table.
 *
 * @return void
 */
function snks_create_rochtah_meet_bookings_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'jalsah_rochtah_meet_bookings';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		patient_id BIGINT(20) UNSIGNED NOT NULL,
		rochtah_doctor_id BIGINT(20) UNSIGNED NOT NULL,
		meet_url_id BIGINT(20) UNSIGNED DEFAULT NULL,
		meet_url VARCHAR(512) NOT NULL,
		appointment_datetime DATETIME NOT NULL,
		diagnosis_id INT(11) DEFAULT NULL,
		diagnosis_name VARCHAR(255) NOT NULL DEFAULT '',
		diagnosis_reasoning TEXT,
		created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
		wa_doctor_sent TINYINT(1) NOT NULL DEFAULT 0,
		wa_patient_sent TINYINT(1) NOT NULL DEFAULT 0,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY patient_id (patient_id),
		KEY rochtah_doctor_id (rochtah_doctor_id),
		KEY appointment_datetime (appointment_datetime),
		KEY status (status)
	) $collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'snks_rochtah_meet_bookings_version', '1.1.0' );
}

/**
 * Add meet_url_id column to bookings table if missing.
 *
 * @return void
 */
function snks_upgrade_rochtah_meet_bookings_schema() {
	global $wpdb;

	$table = $wpdb->prefix . 'jalsah_rochtah_meet_bookings';
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( ! $exists ) {
		return;
	}

	$column = $wpdb->get_row( "SHOW COLUMNS FROM {$table} LIKE 'meet_url_id'" );
	if ( ! $column ) {
		$wpdb->query( "ALTER TABLE {$table} ADD COLUMN meet_url_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER rochtah_doctor_id" );
	}
}

add_action(
	'init',
	static function () {
		$current = get_option( 'snks_rochtah_meet_bookings_version', '0.0.0' );
		if ( version_compare( $current, '1.0.0', '<' ) && function_exists( 'snks_create_rochtah_meet_bookings_table' ) ) {
			snks_create_rochtah_meet_bookings_table();
		}
		if ( version_compare( $current, '1.1.0', '<' ) ) {
			snks_upgrade_rochtah_meet_bookings_schema();
			update_option( 'snks_rochtah_meet_bookings_version', '1.1.0' );
		}
	},
	5
);

/**
 * Whether the user may access rochtah meet booking endpoints.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_rochtah_meet_user_can_access( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_rochtah' ) ) {
		return true;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	return in_array( 'secretary', (array) $user->roles, true );
}

/**
 * List rochtah doctors for the booking form.
 *
 * @return array<int, array<string, mixed>>
 */
function snks_rochtah_meet_data_doctors() {
	$doctors = get_users(
		array(
			'role'    => 'rochtah_doctor',
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);

	$result = array();
	foreach ( $doctors as $doctor ) {
		$first = get_user_meta( $doctor->ID, 'billing_first_name', true );
		$last  = get_user_meta( $doctor->ID, 'billing_last_name', true );
		$name  = trim( $first . ' ' . $last );
		if ( $name === '' ) {
			$name = $doctor->display_name;
		}
		$result[] = array(
			'id'   => (int) $doctor->ID,
			'name' => $name,
		);
	}

	return $result;
}

/**
 * Search registered patients by phone/email (no auto-create).
 *
 * @param string $q Search query.
 * @return array<int, array<string, mixed>>
 */
function snks_rochtah_meet_data_search_patient( $q ) {
	$q = sanitize_text_field( $q );
	if ( strlen( $q ) < 2 ) {
		return array();
	}

	global $wpdb;
	$like     = '%' . $wpdb->esc_like( $q ) . '%';
	$caps_key = $wpdb->get_blog_prefix() . 'capabilities';

	$users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT u.ID as id, u.user_email as email, u.display_name
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} caps ON u.ID = caps.user_id AND caps.meta_key = %s AND caps.meta_value LIKE %s
			 WHERE (
				u.user_email LIKE %s
				OR u.user_login LIKE %s
				OR u.ID IN (
					SELECT user_id FROM {$wpdb->usermeta}
					WHERE meta_key IN ('billing_phone','billing_whatsapp','billing_email','whatsapp') AND meta_value LIKE %s
				)
			 )
			 ORDER BY u.display_name ASC
			 LIMIT 10",
			$caps_key,
			'%customer%',
			$like,
			$like,
			$like
		)
	);

	$result = array();
	foreach ( $users as $u ) {
		$first = get_user_meta( $u->id, 'billing_first_name', true );
		$last  = get_user_meta( $u->id, 'billing_last_name', true );
		$phone = get_user_meta( $u->id, 'billing_whatsapp', true );
		if ( $phone === '' ) {
			$phone = get_user_meta( $u->id, 'whatsapp', true );
		}
		$name = trim( $first . ' ' . $last );
		if ( $name === '' ) {
			$name = $phone !== '' ? $phone : $u->display_name;
		}

		$result[] = array(
			'id'         => (int) $u->id,
			'email'      => $u->email,
			'name'       => $name,
			'first_name' => $first,
			'last_name'  => $last,
			'phone'      => $phone,
		);
	}

	return $result;
}

/**
 * Get patient AI diagnosis snapshot for preview.
 *
 * @param int $patient_id Patient user ID.
 * @return array|null
 */
function snks_rochtah_meet_data_patient_diagnosis( $patient_id ) {
	$patient_id = absint( $patient_id );
	if ( ! $patient_id || ! snks_rochtah_meet_is_registered_patient( $patient_id ) ) {
		return null;
	}

	$diagnosis = get_user_meta( $patient_id, 'ai_diagnosis_result', true );
	if ( empty( $diagnosis ) || ! is_array( $diagnosis ) ) {
		return null;
	}

	return array(
		'diagnosis_id'      => isset( $diagnosis['diagnosis_id'] ) ? (int) $diagnosis['diagnosis_id'] : null,
		'diagnosis_name'    => isset( $diagnosis['diagnosis_name'] ) ? (string) $diagnosis['diagnosis_name'] : '',
		'ai_diagnosis'      => isset( $diagnosis['ai_diagnosis'] ) ? (string) $diagnosis['ai_diagnosis'] : '',
		'reasoning'         => isset( $diagnosis['reasoning'] ) ? (string) $diagnosis['reasoning'] : '',
		'confidence'        => isset( $diagnosis['confidence'] ) ? (string) $diagnosis['confidence'] : '',
		'patient_summary'   => isset( $diagnosis['patient_summary'] ) ? (string) $diagnosis['patient_summary'] : '',
		'therapist_summary' => isset( $diagnosis['therapist_summary'] ) ? (string) $diagnosis['therapist_summary'] : '',
		'completed_at'      => isset( $diagnosis['completed_at'] ) ? (string) $diagnosis['completed_at'] : '',
	);
}

/**
 * Verify user is a registered customer patient.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_rochtah_meet_is_registered_patient( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	return in_array( 'customer', (array) $user->roles, true );
}

/**
 * Verify user is a rochtah doctor.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_rochtah_meet_is_rochtah_doctor( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	return in_array( 'rochtah_doctor', (array) $user->roles, true ) || user_can( $user_id, 'manage_rochtah' );
}

/**
 * Format patient display name.
 *
 * @param int $patient_id Patient ID.
 * @return string
 */
function snks_rochtah_meet_get_patient_name( $patient_id ) {
	$first = get_user_meta( $patient_id, 'billing_first_name', true );
	$last  = get_user_meta( $patient_id, 'billing_last_name', true );
	$name  = trim( $first . ' ' . $last );
	if ( $name !== '' ) {
		return $name;
	}
	$user = get_userdata( $patient_id );
	return $user ? $user->display_name : 'المريض';
}

/**
 * Format rochtah doctor display name.
 *
 * @param int $doctor_id Doctor user ID.
 * @return string
 */
function snks_rochtah_meet_get_doctor_name( $doctor_id ) {
	$first = get_user_meta( $doctor_id, 'billing_first_name', true );
	$last  = get_user_meta( $doctor_id, 'billing_last_name', true );
	$name  = trim( $first . ' ' . $last );
	if ( $name !== '' ) {
		return $name;
	}
	if ( function_exists( 'snks_get_therapist_name' ) ) {
		$therapist_name = snks_get_therapist_name( $doctor_id );
		if ( $therapist_name ) {
			return $therapist_name;
		}
	}
	$user = get_userdata( $doctor_id );
	return $user ? $user->display_name : 'الطبيب';
}

/**
 * Pool table name for rochtah meet URLs.
 *
 * @return string
 */
function snks_rochtah_meet_urls_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'snks_rochtah_meet_urls';
}

/**
 * Count available rochtah meet URLs in the pool.
 *
 * @return int
 */
function snks_rochtah_meet_urls_count_available() {
	global $wpdb;
	$table = snks_rochtah_meet_urls_table_name();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'available'" );
}

/**
 * List available pool URLs for the booking form.
 *
 * @return array<int, array<string, mixed>>
 */
function snks_rochtah_meet_data_available_urls() {
	global $wpdb;
	$table = snks_rochtah_meet_urls_table_name();
	$rows  = $wpdb->get_results(
		"SELECT id, meet_url FROM {$table} WHERE status = 'available' ORDER BY id ASC LIMIT 500"
	);
	$result = array();
	foreach ( $rows as $row ) {
		$result[] = array(
			'id'       => (int) $row->id,
			'meet_url' => (string) $row->meet_url,
		);
	}
	return $result;
}

/**
 * Bulk insert rochtah meet URLs.
 *
 * @param string $text Newline-separated URLs.
 * @return array{inserted:int,skipped_duplicate:int,skipped_invalid:int}
 */
function snks_rochtah_meet_urls_bulk_insert( $text ) {
	global $wpdb;
	$table  = snks_rochtah_meet_urls_table_name();
	$lines  = preg_split( '/\r\n|\r|\n/', (string) $text );
	$seen   = array();
	$result = array(
		'inserted'          => 0,
		'skipped_duplicate' => 0,
		'skipped_invalid'   => 0,
	);

	foreach ( $lines as $line ) {
		$normalized = function_exists( 'snks_normalize_meeting_pool_url' )
			? snks_normalize_meeting_pool_url( $line )
			: esc_url_raw( trim( $line ) );
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

	return $result;
}

/**
 * Assign a pool URL to a rochtah meet booking.
 *
 * @param int $url_id     Pool row ID.
 * @param int $booking_id Booking ID.
 * @return true|WP_Error
 */
function snks_rochtah_meet_assign_url( $url_id, $booking_id ) {
	global $wpdb;

	$url_id     = absint( $url_id );
	$booking_id = absint( $booking_id );
	if ( ! $url_id || ! $booking_id ) {
		return new WP_Error( 'invalid_assign', 'Invalid URL or booking ID' );
	}

	$table       = snks_rochtah_meet_urls_table_name();
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
		return new WP_Error( 'url_not_available', 'This Google Meet URL is not available' );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ok = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET status = 'assigned', assigned_at = %s, assigned_booking_id = %d WHERE id = %d AND status = 'available'",
			$assigned_at,
			$booking_id,
			$url_id
		)
	);

	if ( ! $ok ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'assign_failed', 'Failed to assign Google Meet URL' );
	}

	$wpdb->query( 'COMMIT' );
	return true;
}

/**
 * Unassign a pool URL and return it to available status.
 *
 * @param int $url_id Pool row ID.
 * @return true|WP_Error
 */
function snks_rochtah_meet_unassign_url( $url_id ) {
	global $wpdb;

	$url_id = absint( $url_id );
	if ( ! $url_id ) {
		return new WP_Error( 'invalid_url', 'Invalid URL ID' );
	}

	$table = snks_rochtah_meet_urls_table_name();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ok = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET status = 'available', assigned_booking_id = NULL, assigned_at = NULL WHERE id = %d AND status = 'assigned'",
			$url_id
		)
	);

	if ( ! $ok ) {
		return new WP_Error( 'unassign_failed', 'URL is not assigned or could not be unassigned' );
	}

	return true;
}

/**
 * Delete a pool URL (available or disabled only).
 *
 * @param int $url_id Pool row ID.
 * @return true|WP_Error
 */
function snks_rochtah_meet_delete_url( $url_id ) {
	global $wpdb;

	$url_id = absint( $url_id );
	$table  = snks_rochtah_meet_urls_table_name();
	$row    = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, status FROM {$table} WHERE id = %d LIMIT 1",
			$url_id
		)
	);

	if ( ! $row ) {
		return new WP_Error( 'not_found', 'URL not found' );
	}
	if ( 'assigned' === $row->status ) {
		return new WP_Error( 'assigned', 'Cannot delete an assigned URL. Unassign it first.' );
	}

	$wpdb->delete( $table, array( 'id' => $url_id ), array( '%d' ) );
	return true;
}

/**
 * Get pool row by ID.
 *
 * @param int $url_id Pool row ID.
 * @return object|null
 */
function snks_rochtah_meet_get_url_row( $url_id ) {
	global $wpdb;
	$url_id = absint( $url_id );
	if ( ! $url_id ) {
		return null;
	}
	$table = snks_rochtah_meet_urls_table_name();
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			$url_id
		)
	);
}

/**
 * Create a rochtah meet booking and send WhatsApp notifications.
 *
 * @param array $input     Request payload.
 * @param int   $created_by Creator user ID.
 * @return array|WP_Error
 */
function snks_rochtah_meet_submit_booking( $input, $created_by ) {
	global $wpdb;

	$patient_id        = isset( $input['patient_id'] ) ? absint( $input['patient_id'] ) : 0;
	$rochtah_doctor_id = isset( $input['rochtah_doctor_id'] ) ? absint( $input['rochtah_doctor_id'] ) : 0;
	$meet_url_id       = isset( $input['meet_url_id'] ) ? absint( $input['meet_url_id'] ) : 0;
	$appointment_raw   = isset( $input['appointment_datetime'] ) ? sanitize_text_field( $input['appointment_datetime'] ) : '';

	if ( ! $patient_id || ! snks_rochtah_meet_is_registered_patient( $patient_id ) ) {
		return new WP_Error( 'invalid_patient', 'Patient must be a registered user' );
	}
	if ( ! $rochtah_doctor_id || ! snks_rochtah_meet_is_rochtah_doctor( $rochtah_doctor_id ) ) {
		return new WP_Error( 'invalid_doctor', 'Invalid rochtah doctor' );
	}
	if ( ! $meet_url_id ) {
		return new WP_Error( 'invalid_meet_url', 'Please select a Google Meet URL from the pool' );
	}

	$url_row = snks_rochtah_meet_get_url_row( $meet_url_id );
	if ( ! $url_row || 'available' !== $url_row->status ) {
		return new WP_Error( 'invalid_meet_url', 'Selected Google Meet URL is not available' );
	}
	$meet_url = (string) $url_row->meet_url;
	if ( $appointment_raw === '' ) {
		return new WP_Error( 'invalid_datetime', 'Appointment date and time are required' );
	}

	$timestamp = strtotime( $appointment_raw );
	if ( ! $timestamp ) {
		return new WP_Error( 'invalid_datetime', 'Invalid appointment date and time' );
	}
	$appointment_datetime = wp_date( 'Y-m-d H:i:s', $timestamp );

	$diagnosis_snapshot = snks_rochtah_meet_data_patient_diagnosis( $patient_id );
	$diagnosis_id       = null;
	$diagnosis_name     = '';
	$diagnosis_reason   = '';
	if ( is_array( $diagnosis_snapshot ) ) {
		$diagnosis_id     = $diagnosis_snapshot['diagnosis_id'];
		$diagnosis_name   = $diagnosis_snapshot['diagnosis_name'];
		$diagnosis_reason = $diagnosis_snapshot['reasoning'];
	}

	$table = $wpdb->prefix . 'jalsah_rochtah_meet_bookings';
	$inserted = $wpdb->insert(
		$table,
		array(
			'patient_id'           => $patient_id,
			'rochtah_doctor_id'    => $rochtah_doctor_id,
			'meet_url_id'          => $meet_url_id,
			'meet_url'             => $meet_url,
			'appointment_datetime' => $appointment_datetime,
			'diagnosis_id'         => $diagnosis_id,
			'diagnosis_name'       => $diagnosis_name,
			'diagnosis_reasoning'  => $diagnosis_reason,
			'created_by'           => absint( $created_by ),
			'status'               => 'scheduled',
		),
		array( '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'db_error', 'Failed to create booking' );
	}

	$booking_id = (int) $wpdb->insert_id;

	$assigned = snks_rochtah_meet_assign_url( $meet_url_id, $booking_id );
	if ( is_wp_error( $assigned ) ) {
		$wpdb->delete( $table, array( 'id' => $booking_id ), array( '%d' ) );
		return $assigned;
	}

	$wa_flags = array(
		'wa_doctor_sent'  => 0,
		'wa_patient_sent' => 0,
	);

	if ( function_exists( 'snks_send_rochtah_meet_doctor_notification' ) ) {
		if ( snks_send_rochtah_meet_doctor_notification( $booking_id ) ) {
			$wa_flags['wa_doctor_sent'] = 1;
		}
	}
	if ( function_exists( 'snks_send_rochtah_meet_patient_notification' ) ) {
		if ( snks_send_rochtah_meet_patient_notification( $booking_id ) ) {
			$wa_flags['wa_patient_sent'] = 1;
		}
	}

	$wpdb->update(
		$table,
		$wa_flags,
		array( 'id' => $booking_id ),
		array( '%d', '%d' ),
		array( '%d' )
	);

	return array(
		'booking_id'           => $booking_id,
		'patient_id'           => $patient_id,
		'rochtah_doctor_id'    => $rochtah_doctor_id,
		'meet_url_id'          => $meet_url_id,
		'meet_url'             => $meet_url,
		'appointment_datetime' => $appointment_datetime,
		'diagnosis_name'       => $diagnosis_name,
		'wa_doctor_sent'       => (bool) $wa_flags['wa_doctor_sent'],
		'wa_patient_sent'      => (bool) $wa_flags['wa_patient_sent'],
	);
}

/**
 * Whether the user is a rochtah doctor only (not admin/secretary).
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_rochtah_meet_user_is_doctor_only( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || user_can( $user_id, 'manage_options' ) ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	if ( in_array( 'secretary', (array) $user->roles, true ) ) {
		return false;
	}
	return user_can( $user_id, 'manage_rochtah' ) || in_array( 'rochtah_doctor', (array) $user->roles, true );
}

/**
 * List rochtah meet bookings for the management page.
 *
 * @param array $args Query args: page, per_page, status, q, viewer_id.
 * @return array{rows: array<int, array<string, mixed>>, total: int}
 */
function snks_rochtah_meet_data_list_bookings( $args = array() ) {
	global $wpdb;

	$page     = max( 1, absint( $args['page'] ?? 1 ) );
	$per_page = max( 1, min( 100, absint( $args['per_page'] ?? 20 ) ) );
	$offset   = ( $page - 1 ) * $per_page;
	$status   = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
	$viewer_id = absint( $args['viewer_id'] ?? 0 );
	$q        = isset( $args['q'] ) ? sanitize_text_field( $args['q'] ) : '';

	$table  = $wpdb->prefix . 'jalsah_rochtah_meet_bookings';
	$where  = array( '1=1' );
	$params = array();

	if ( $viewer_id && snks_rochtah_meet_user_is_doctor_only( $viewer_id ) ) {
		$where[]  = 'b.rochtah_doctor_id = %d';
		$params[] = $viewer_id;
	}

	if ( $status && in_array( $status, array( 'scheduled', 'completed', 'cancelled' ), true ) ) {
		$where[]  = 'b.status = %s';
		$params[] = $status;
	}

	if ( $q !== '' ) {
		$patients = snks_rochtah_meet_data_search_patient( $q );
		$ids      = array_map(
			static function ( $p ) {
				return (int) $p['id'];
			},
			$patients
		);
		if ( empty( $ids ) ) {
			return array(
				'rows'  => array(),
				'total' => 0,
			);
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$where[]      = "b.patient_id IN ({$placeholders})";
		$params       = array_merge( $params, $ids );
	}

	$where_sql = implode( ' AND ', $where );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_sql = "SELECT COUNT(*) FROM {$table} b WHERE {$where_sql}";
	$total     = empty( $params )
		? (int) $wpdb->get_var( $count_sql )
		: (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

	$list_params   = $params;
	$list_params[] = $per_page;
	$list_params[] = $offset;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$list_sql = "SELECT b.* FROM {$table} b WHERE {$where_sql} ORDER BY b.appointment_datetime DESC LIMIT %d OFFSET %d";
	$rows     = empty( $params )
		? $wpdb->get_results( $wpdb->prepare( $list_sql, $per_page, $offset ) )
		: $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ) );

	if ( ! is_array( $rows ) ) {
		return array(
			'rows'  => array(),
			'total' => 0,
		);
	}

	$result = array();
	foreach ( $rows as $row ) {
		$patient_id = (int) $row->patient_id;
		$doctor_id  = (int) $row->rochtah_doctor_id;
		$phone      = $patient_id ? get_user_meta( $patient_id, 'billing_whatsapp', true ) : '';
		if ( $phone === '' && $patient_id ) {
			$phone = get_user_meta( $patient_id, 'whatsapp', true );
		}
		if ( $phone === '' && $patient_id ) {
			$phone = get_user_meta( $patient_id, 'billing_phone', true );
		}

		$result[] = array(
			'id'                   => (int) $row->id,
			'patient_id'           => $patient_id,
			'patient_name'         => snks_rochtah_meet_get_patient_name( $patient_id ),
			'patient_phone'        => $phone,
			'rochtah_doctor_id'     => $doctor_id,
			'rochtah_doctor_name'  => snks_rochtah_meet_get_doctor_name( $doctor_id ),
			'meet_url_id'          => (int) $row->meet_url_id,
			'meet_url'             => (string) $row->meet_url,
			'appointment_datetime' => (string) $row->appointment_datetime,
			'diagnosis_name'       => (string) $row->diagnosis_name,
			'diagnosis_reasoning'  => (string) $row->diagnosis_reasoning,
			'status'               => (string) $row->status,
			'wa_doctor_sent'       => (bool) $row->wa_doctor_sent,
			'wa_patient_sent'      => (bool) $row->wa_patient_sent,
			'created_at'           => (string) $row->created_at,
		);
	}

	return array(
		'rows'  => $result,
		'total' => $total,
	);
}

/**
 * Update booking status (cancel or complete).
 *
 * @param int    $booking_id Booking ID.
 * @param string $status     New status.
 * @param int    $viewer_id  User performing the action.
 * @return true|WP_Error
 */
function snks_rochtah_meet_update_booking_status( $booking_id, $status, $viewer_id ) {
	global $wpdb;

	$booking_id = absint( $booking_id );
	$viewer_id  = absint( $viewer_id );
	$status     = sanitize_text_field( $status );

	if ( ! in_array( $status, array( 'scheduled', 'completed', 'cancelled' ), true ) ) {
		return new WP_Error( 'invalid_status', 'Invalid status' );
	}

	$table   = $wpdb->prefix . 'jalsah_rochtah_meet_bookings';
	$booking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			$booking_id
		)
	);

	if ( ! $booking ) {
		return new WP_Error( 'not_found', 'Booking not found' );
	}

	if ( snks_rochtah_meet_user_is_doctor_only( $viewer_id ) && (int) $booking->rochtah_doctor_id !== $viewer_id ) {
		return new WP_Error( 'forbidden', 'You cannot manage this booking' );
	}

	if ( $booking->status === $status ) {
		return true;
	}

	if ( 'cancelled' === $status && ! empty( $booking->meet_url_id ) ) {
		snks_rochtah_meet_unassign_url( (int) $booking->meet_url_id );
	}

	$updated = $wpdb->update(
		$table,
		array( 'status' => $status ),
		array( 'id' => $booking_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		return new WP_Error( 'update_failed', 'Failed to update booking status' );
	}

	return true;
}
