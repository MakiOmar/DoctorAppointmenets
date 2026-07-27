<?php
/**
 * AI session next-appointment follow-up helpers.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create the next-appointments table.
 *
 * @return void
 */
function snks_create_ai_next_appointments_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_ai_next_appointments';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		session_id BIGINT(20) UNSIGNED NOT NULL,
		therapist_id BIGINT(20) UNSIGNED NOT NULL,
		patient_id BIGINT(20) UNSIGNED NOT NULL,
		appointment_datetime DATETIME NOT NULL,
		therapist_name VARCHAR(255) NOT NULL DEFAULT '',
		patient_name VARCHAR(255) NOT NULL DEFAULT '',
		patient_whatsapp VARCHAR(50) NOT NULL DEFAULT '',
		contacted TINYINT(1) NOT NULL DEFAULT 0,
		contacted_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		contacted_at DATETIME DEFAULT NULL,
		created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY session_id (session_id),
		KEY contacted (contacted),
		KEY appointment_datetime (appointment_datetime)
	) $collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'snks_ai_next_appointments_version', '1.0.0' );
}

add_action(
	'init',
	static function () {
		$current = get_option( 'snks_ai_next_appointments_version', '0.0.0' );
		if ( version_compare( $current, '1.0.0', '<' ) ) {
			snks_create_ai_next_appointments_table();
		}
	},
	5
);

/**
 * Whether a next-appointment already exists for a session.
 *
 * @param int $session_id Session ID.
 * @return bool
 */
function snks_ai_next_appointment_exists_for_session( $session_id ) {
	global $wpdb;

	$session_id = absint( $session_id );
	if ( ! $session_id ) {
		return false;
	}

	$table = $wpdb->prefix . 'snks_ai_next_appointments';
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE session_id = %d",
			$session_id
		)
	);

	return $count > 0;
}

/**
 * Site-wide payment number for follow-up WhatsApp messages.
 *
 * @return string
 */
function snks_ai_next_appointment_payment_number() {
	return (string) get_option( 'snks_followup_payment_number', '' );
}

/**
 * Normalize a Flatpickr/doctor-submitted datetime to MySQL wall-clock (no TZ shift).
 *
 * @param string $raw Raw datetime (Y-m-d H:i or Y-m-d H:i:s).
 * @return string|null MySQL datetime or null if invalid.
 */
function snks_ai_next_appointment_normalize_datetime( $raw ) {
	$raw = trim( (string) $raw );
	if ( preg_match( '/^(\d{4}-\d{2}-\d{2})[\sT](\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m ) ) {
		$hour   = (int) $m[2];
		$minute = (int) $m[3];
		$second = isset( $m[4] ) ? (int) $m[4] : 0;
		if ( $hour > 23 || $minute > 59 || $second > 59 ) {
			return null;
		}
		// Only :00, :15, :30, :45 are allowed (snap to nearest quarter-hour).
		$allowed = array( 0, 15, 30, 45 );
		if ( ! in_array( $minute, $allowed, true ) ) {
			$minute = (int) ( round( $minute / 15 ) * 15 );
			if ( 60 === $minute ) {
				$minute = 0;
				$hour   = ( $hour + 1 ) % 24;
			}
		}
		$normalized = sprintf(
			'%s %02d:%02d:%02d',
			$m[1],
			$hour,
			$minute,
			0
		);
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $normalized );
		if ( ! $dt || $dt->format( 'Y-m-d H:i:s' ) !== $normalized ) {
			return null;
		}
		return $normalized;
	}
	return null;
}

/**
 * Format Arabic date parts from a datetime string.
 * Treats stored value as site wall-clock (no wp_date timezone conversion).
 *
 * @param string $datetime MySQL datetime.
 * @return array{day:string,date:string,time:string,display:string}
 */
function snks_ai_next_appointment_format_parts( $datetime ) {
	$datetime = (string) $datetime;
	$dt       = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $datetime );
	if ( ! $dt ) {
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $datetime );
	}
	if ( ! $dt ) {
		return array(
			'day'     => '',
			'date'    => '',
			'time'    => '',
			'display' => $datetime,
		);
	}

	$day_map = array(
		'Sunday'    => 'الأحد',
		'Monday'    => 'الاثنين',
		'Tuesday'   => 'الثلاثاء',
		'Wednesday' => 'الأربعاء',
		'Thursday'  => 'الخميس',
		'Friday'    => 'الجمعة',
		'Saturday'  => 'السبت',
	);
	$english_day = $dt->format( 'l' );
	$day         = isset( $day_map[ $english_day ] ) ? $day_map[ $english_day ] : $english_day;

	$date_en = $dt->format( 'j F Y' );
	$date    = function_exists( 'localize_date_to_arabic' )
		? localize_date_to_arabic( $date_en )
		: $date_en;

	$hour   = (int) $dt->format( 'g' );
	$minute = $dt->format( 'i' );
	$ampm   = ( (int) $dt->format( 'G' ) ) >= 12 ? 'م' : 'ص';
	$time   = $hour . ':' . $minute . ' ' . $ampm;

	$display = sprintf(
		'%s الموافق %s الساعة %s',
		$day,
		$date,
		$time
	);

	return array(
		'day'     => $day,
		'date'    => $date,
		'time'    => $time,
		'display' => $display,
	);
}

/**
 * Build WhatsApp follow-up message body.
 *
 * @param object $row Booking row.
 * @return string
 */
function snks_ai_next_appointment_whatsapp_message( $row ) {
	$parts           = snks_ai_next_appointment_format_parts( $row->appointment_datetime );
	$payment_number  = snks_ai_next_appointment_payment_number();
	$therapist_name  = (string) $row->therapist_name;
	$day             = $parts['day'];
	$date            = $parts['date'];
	$time            = $parts['time'];

	return "معالج حضرتك (*{$therapist_name}*) بلغنا إن موعد الجلسة القادمة هيكون (*{$day}*) الموافق (*{$date}*) الساعة (*{$time}*)، أستاذنك نأكد الحجز عشان مفيش عميل تاني يحجز الموعد.\n\nلتأكيد الحجز، يرجى تحويل مبلغ الجلسة إنستا باي أو فودافون كاش أو أي محفظة على رقم *{$payment_number}*، وإرسال سكرين التحويل لإتمام الحجز.";
}

/**
 * Digits-only phone for wa.me links.
 *
 * @param string $phone Phone with possible + / spaces.
 * @return string
 */
function snks_ai_next_appointment_wa_digits( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	return $digits ? $digits : '';
}

/**
 * Build wa.me URL with prefilled message.
 *
 * @param object $row Booking row.
 * @return string
 */
function snks_ai_next_appointment_whatsapp_url( $row ) {
	$digits = snks_ai_next_appointment_wa_digits( $row->patient_whatsapp );
	if ( ! $digits ) {
		return '';
	}
	$message = snks_ai_next_appointment_whatsapp_message( $row );
	return 'https://wa.me/' . $digits . '?text=' . rawurlencode( $message );
}

/**
 * Resolve display name for a user.
 *
 * @param int $user_id User ID.
 * @return string
 */
function snks_ai_next_appointment_user_name( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return '';
	}

	if ( function_exists( 'snks_get_therapist_name' ) ) {
		$name = snks_get_therapist_name( $user_id );
		if ( $name ) {
			return $name;
		}
	}

	$first = get_user_meta( $user_id, 'billing_first_name', true );
	$last  = get_user_meta( $user_id, 'billing_last_name', true );
	$name  = trim( $first . ' ' . $last );
	if ( $name !== '' ) {
		return $name;
	}

	$user = get_userdata( $user_id );
	return $user ? $user->display_name : '';
}

/**
 * Submit a next-appointment request from the doctor.
 *
 * @param int    $session_id           Session ID.
 * @param string $appointment_datetime Datetime string.
 * @param int    $created_by           Current user ID.
 * @return array|WP_Error
 */
function snks_ai_next_appointment_submit( $session_id, $appointment_datetime, $created_by ) {
	global $wpdb;

	$session_id = absint( $session_id );
	$created_by = absint( $created_by );
	$raw        = sanitize_text_field( $appointment_datetime );

	if ( ! $session_id || $raw === '' ) {
		return new WP_Error( 'invalid_params', 'بيانات غير صالحة.' );
	}

	// Store the wall-clock time the doctor picked — do not convert via strtotime+wp_date (TZ shift).
	$appointment_datetime = snks_ai_next_appointment_normalize_datetime( $raw );
	if ( ! $appointment_datetime ) {
		return new WP_Error( 'invalid_datetime', 'تاريخ أو وقت غير صالح.' );
	}

	if ( snks_ai_next_appointment_exists_for_session( $session_id ) ) {
		return new WP_Error( 'already_exists', 'تم تسجيل الموعد القادم لهذه الجلسة مسبقاً.' );
	}

	$table_name = $wpdb->prefix . ( defined( 'TIMETABLE_TABLE_NAME' ) ? TIMETABLE_TABLE_NAME : 'snks_provider_timetable' );
	$session    = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE ID = %d",
			$session_id
		)
	);

	if ( ! $session ) {
		return new WP_Error( 'not_found', 'الجلسة غير موجودة.' );
	}

	$is_ai = isset( $session->settings ) && false !== strpos( (string) $session->settings, 'ai_booking' );
	if ( ! $is_ai && function_exists( 'snks_is_ai_session' ) ) {
		$is_ai = snks_is_ai_session( $session );
	}
	if ( ! $is_ai ) {
		return new WP_Error( 'not_ai', 'هذه الجلسة ليست جلسة ذكاء اصطناعي.' );
	}

	$therapist_id = absint( $session->user_id );
	$patient_id   = absint( $session->client_id );

	// Doctor may only submit for their own sessions (admins allowed).
	if ( $created_by && ! user_can( $created_by, 'manage_options' ) && (int) $created_by !== $therapist_id ) {
		return new WP_Error( 'forbidden', 'غير مسموح.' );
	}

	$patient_whatsapp = '';
	if ( $patient_id && function_exists( 'snks_get_user_whatsapp' ) ) {
		$wa = snks_get_user_whatsapp( $patient_id );
		$patient_whatsapp = $wa ? (string) $wa : '';
	}
	if ( $patient_whatsapp === '' && $patient_id ) {
		$patient_whatsapp = (string) get_user_meta( $patient_id, 'billing_whatsapp', true );
	}

	$table    = $wpdb->prefix . 'snks_ai_next_appointments';
	$inserted = $wpdb->insert(
		$table,
		array(
			'session_id'           => $session_id,
			'therapist_id'         => $therapist_id,
			'patient_id'           => $patient_id,
			'appointment_datetime' => $appointment_datetime,
			'therapist_name'       => snks_ai_next_appointment_user_name( $therapist_id ),
			'patient_name'         => snks_ai_next_appointment_user_name( $patient_id ),
			'patient_whatsapp'     => $patient_whatsapp,
			'contacted'            => 0,
			'created_by'           => $created_by,
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'db_error', 'فشل حفظ الموعد القادم.' );
	}

	$row_id = (int) $wpdb->insert_id;
	$row    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $row_id ) );

	return array(
		'id'                   => $row_id,
		'session_id'           => $session_id,
		'appointment_datetime' => $appointment_datetime,
		'display'              => snks_ai_next_appointment_format_parts( $appointment_datetime )['display'],
		'whatsapp_url'         => $row ? snks_ai_next_appointment_whatsapp_url( $row ) : '',
	);
}

/**
 * Pending (not contacted) follow-up count.
 *
 * @return int
 */
function snks_ai_next_appointment_pending_count() {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_ai_next_appointments';
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE contacted = 0" );
}

/**
 * List pending follow-ups for secretary UI.
 *
 * @return array{rows:array,pending_count:int}
 */
function snks_ai_next_appointment_list_pending() {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_ai_next_appointments';
	$rows  = $wpdb->get_results(
		"SELECT * FROM {$table} WHERE contacted = 0 ORDER BY appointment_datetime ASC"
	);

	$result = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$parts    = snks_ai_next_appointment_format_parts( $row->appointment_datetime );
			$result[] = array(
				'id'                   => (int) $row->id,
				'session_id'           => (int) $row->session_id,
				'therapist_id'         => (int) $row->therapist_id,
				'patient_id'           => (int) $row->patient_id,
				'therapist_name'       => (string) $row->therapist_name,
				'patient_name'         => (string) $row->patient_name,
				'patient_whatsapp'     => (string) $row->patient_whatsapp,
				'appointment_datetime' => (string) $row->appointment_datetime,
				'day'                  => $parts['day'],
				'date'                 => $parts['date'],
				'time'                 => $parts['time'],
				'display'              => $parts['display'],
				'payment_number'       => snks_ai_next_appointment_payment_number(),
				'whatsapp_message'     => snks_ai_next_appointment_whatsapp_message( $row ),
				'whatsapp_url'         => snks_ai_next_appointment_whatsapp_url( $row ),
				'created_at'           => (string) $row->created_at,
			);
		}
	}

	return array(
		'rows'          => $result,
		'pending_count' => count( $result ),
	);
}

/**
 * Mark a follow-up as contacted (تم).
 *
 * @param int $id         Row ID.
 * @param int $viewer_id  Secretary/admin user ID.
 * @return array|WP_Error
 */
function snks_ai_next_appointment_mark_contacted( $id, $viewer_id ) {
	global $wpdb;

	$id        = absint( $id );
	$viewer_id = absint( $viewer_id );
	if ( ! $id ) {
		return new WP_Error( 'invalid_id', 'معرف غير صالح.' );
	}

	$table = $wpdb->prefix . 'snks_ai_next_appointments';
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	if ( ! $row ) {
		return new WP_Error( 'not_found', 'السجل غير موجود.' );
	}

	if ( (int) $row->contacted === 1 ) {
		return array(
			'id'            => $id,
			'contacted'     => true,
			'pending_count' => snks_ai_next_appointment_pending_count(),
		);
	}

	$updated = $wpdb->update(
		$table,
		array(
			'contacted'    => 1,
			'contacted_by' => $viewer_id,
			'contacted_at' => current_time( 'mysql' ),
		),
		array( 'id' => $id ),
		array( '%d', '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		return new WP_Error( 'db_error', 'فشل تحديث الحالة.' );
	}

	return array(
		'id'            => $id,
		'contacted'     => true,
		'pending_count' => snks_ai_next_appointment_pending_count(),
	);
}
