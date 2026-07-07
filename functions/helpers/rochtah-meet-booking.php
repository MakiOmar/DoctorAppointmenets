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

	update_option( 'snks_rochtah_meet_bookings_version', '1.0.0' );
}

add_action(
	'init',
	static function () {
		$current = get_option( 'snks_rochtah_meet_bookings_version', '0.0.0' );
		if ( version_compare( $current, '1.0.0', '<' ) && function_exists( 'snks_create_rochtah_meet_bookings_table' ) ) {
			snks_create_rochtah_meet_bookings_table();
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
	$meet_url          = isset( $input['meet_url'] ) ? esc_url_raw( trim( $input['meet_url'] ) ) : '';
	$appointment_raw   = isset( $input['appointment_datetime'] ) ? sanitize_text_field( $input['appointment_datetime'] ) : '';

	if ( ! $patient_id || ! snks_rochtah_meet_is_registered_patient( $patient_id ) ) {
		return new WP_Error( 'invalid_patient', 'Patient must be a registered user' );
	}
	if ( ! $rochtah_doctor_id || ! snks_rochtah_meet_is_rochtah_doctor( $rochtah_doctor_id ) ) {
		return new WP_Error( 'invalid_doctor', 'Invalid rochtah doctor' );
	}
	if ( $meet_url === '' || ! filter_var( $meet_url, FILTER_VALIDATE_URL ) ) {
		return new WP_Error( 'invalid_meet_url', 'Valid Google Meet URL is required' );
	}
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
			'meet_url'             => $meet_url,
			'appointment_datetime' => $appointment_datetime,
			'diagnosis_id'         => $diagnosis_id,
			'diagnosis_name'       => $diagnosis_name,
			'diagnosis_reasoning'  => $diagnosis_reason,
			'created_by'           => absint( $created_by ),
			'status'               => 'scheduled',
		),
		array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'db_error', 'Failed to create booking' );
	}

	$booking_id = (int) $wpdb->insert_id;

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
		'meet_url'             => $meet_url,
		'appointment_datetime' => $appointment_datetime,
		'diagnosis_name'       => $diagnosis_name,
		'wa_doctor_sent'       => (bool) $wa_flags['wa_doctor_sent'],
		'wa_patient_sent'      => (bool) $wa_flags['wa_patient_sent'],
	);
}
