<?php
/**
 * Admin Manual Booking Helper
 *
 * Handles processing of admin-created manual bookings and appointment changes.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Last error set by snks_manual_booking_ensure_slot when it returns false (e.g. 'overlap').
 * Used by the API to return a specific error message.
 *
 * @var string|null
 */
$snks_manual_booking_ensure_slot_last_error = null;

/**
 * Overlapping timetable rows collected when last ensure_slot failed with overlap.
 *
 * @var array<int, array<string, mixed>>
 */
$snks_manual_booking_ensure_slot_overlapping_slots = array();

/**
 * Return the last error reason from snks_manual_booking_ensure_slot (e.g. 'overlap' or null).
 *
 * @return string|null
 */
function snks_manual_booking_ensure_slot_last_error() {
	global $snks_manual_booking_ensure_slot_last_error;
	return $snks_manual_booking_ensure_slot_last_error;
}

/**
 * Overlapping slots from the last snks_manual_booking_ensure_slot call that failed with overlap.
 *
 * @return array<int, array{slot_id:int,date:string,starts:string,ends:string,order_id:int,session_status:string}>
 */
function snks_manual_booking_ensure_slot_overlapping_slots() {
	global $snks_manual_booking_ensure_slot_overlapping_slots;
	return is_array( $snks_manual_booking_ensure_slot_overlapping_slots ) ? $snks_manual_booking_ensure_slot_overlapping_slots : array();
}

/**
 * Convert time string HH:MM:SS or HH:MM to minutes since midnight.
 *
 * @param string $time Time string.
 * @return int Minutes since midnight, or -1 on invalid.
 */
function snks_manual_booking_time_to_minutes( $time ) {
	if ( preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim( $time ), $m ) ) {
		$h = (int) $m[1];
		$min = (int) $m[2];
		if ( $h < 0 || $h > 23 || $min < 0 || $min > 59 ) {
			return -1;
		}
		return $h * 60 + $min;
	}
	return -1;
}

/**
 * Normalize a time string to HH:MM:SS.
 *
 * @param string $time Time input.
 * @return string Empty string if invalid.
 */
function snks_manual_booking_normalize_time_hms( $time ) {
	$time = trim( (string) $time );
	if ( preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $m ) ) {
		return sprintf( '%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset( $m[3] ) ? (int) $m[3] : 0 );
	}
	return '';
}

/**
 * Whether a timetable row is an empty online 45-minute waiting slot usable for manual booking.
 *
 * @param object $row Timetable row.
 * @return bool
 */
function snks_manual_booking_slot_is_reusable_waiting( $row ) {
	if ( ! $row || ! isset( $row->session_status ) || 'waiting' !== $row->session_status ) {
		return false;
	}
	if ( (int) $row->order_id > 0 || (int) $row->client_id > 0 ) {
		return false;
	}
	$settings = isset( $row->settings ) ? (string) $row->settings : '';
	if ( strpos( $settings, 'ai_booking:booked' ) !== false ) {
		return false;
	}
	if ( strpos( $settings, 'ai_booking:in_cart' ) !== false ) {
		return false;
	}
	if ( strpos( $settings, 'ai_booking:rescheduled_old_slot' ) !== false ) {
		return false;
	}
	if ( isset( $row->attendance_type ) && 'online' !== (string) $row->attendance_type ) {
		return false;
	}
	if ( isset( $row->period ) && 45 !== (int) $row->period ) {
		return false;
	}
	return true;
}

/**
 * Find a reusable waiting slot at the same start time (minute-accurate, not string equality).
 *
 * @param int    $therapist_id Therapist user ID.
 * @param string $date         Date Y-m-d.
 * @param string $time_hms     Start time HH:MM:SS.
 * @return object|null
 */
function snks_manual_booking_find_reusable_waiting_slot( $therapist_id, $date, $time_hms ) {
	global $wpdb;

	$target_min = snks_manual_booking_time_to_minutes( $time_hms );
	if ( $target_min < 0 ) {
		return null;
	}

	$table = $wpdb->prefix . 'snks_provider_timetable';
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE user_id = %d AND DATE(date_time) = %s AND session_status = 'waiting' AND order_id = 0
			 AND (client_id = 0 OR client_id IS NULL) AND period = 45 AND attendance_type = 'online'
			 AND (settings NOT LIKE %s OR settings = '' OR settings IS NULL)
			 AND (settings NOT LIKE %s OR settings = '' OR settings IS NULL)
			 AND (settings NOT LIKE %s OR settings = '' OR settings IS NULL)
			 ORDER BY ID ASC",
			$therapist_id,
			$date,
			'%ai_booking:booked%',
			'%ai_booking:in_cart%',
			'%ai_booking:rescheduled_old_slot%'
		)
	);

	foreach ( (array) $rows as $row ) {
		if ( ! snks_manual_booking_slot_is_reusable_waiting( $row ) ) {
			continue;
		}
		if ( snks_manual_booking_time_to_minutes( $row->starts ) === $target_min ) {
			return $row;
		}
	}

	return null;
}

/**
 * Find timetable rows that overlap a time range on a given day (any status).
 *
 * @param int        $therapist_id     Therapist user ID.
 * @param string     $date             Date Y-m-d.
 * @param int        $start_min          Range start (minutes since midnight).
 * @param int        $end_min            Range end (exclusive).
 * @param int[]      $exclude_slot_ids Slot IDs to ignore (e.g. appointment being rescheduled).
 * @return object[] Conflicting rows keyed by slot ID.
 */
function snks_manual_booking_find_datetime_conflicts( $therapist_id, $date, $start_min, $end_min, $exclude_slot_ids = array() ) {
	global $wpdb;

	$therapist_id = absint( $therapist_id );
	$exclude_slot_ids = array_filter( array_map( 'absint', (array) $exclude_slot_ids ) );
	if ( ! $therapist_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || $start_min < 0 || $end_min <= $start_min ) {
		return array();
	}

	$table = $wpdb->prefix . 'snks_provider_timetable';
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, starts, ends, period, date_time, order_id, client_id, session_status FROM {$table}
			 WHERE user_id = %d AND DATE(date_time) = %s
			 ORDER BY starts ASC",
			$therapist_id,
			$date
		)
	);

	$conflicts = array();
	foreach ( (array) $rows as $row ) {
		$slot_id = isset( $row->ID ) ? absint( $row->ID ) : 0;
		if ( $slot_id < 1 || in_array( $slot_id, $exclude_slot_ids, true ) ) {
			continue;
		}
		$existing_start = snks_manual_booking_time_to_minutes( $row->starts );
		if ( $existing_start < 0 ) {
			continue;
		}
		$period      = isset( $row->period ) && (int) $row->period > 0 ? (int) $row->period : 45;
		$existing_end = $existing_start + $period;
		if ( $start_min < $existing_end && $existing_start < $end_min ) {
			$conflicts[ $slot_id ] = $row;
		}
	}

	return array_values( $conflicts );
}

/**
 * Whether an overlapping row blocks manual booking (active session).
 *
 * @param object $row Timetable row.
 * @return bool
 */
function snks_manual_booking_conflict_is_blocking( $row ) {
	$status = isset( $row->session_status ) ? sanitize_key( (string) $row->session_status ) : '';
	return in_array( $status, array( 'open', 'completed' ), true );
}

/**
 * Whether an overlapping unbooked row can be removed to make room (change-appointment flow).
 *
 * @param object $row Timetable row.
 * @return bool
 */
function snks_manual_booking_conflict_is_replaceable( $row ) {
	if ( snks_manual_booking_conflict_is_blocking( $row ) ) {
		return false;
	}
	if ( (int) ( $row->order_id ?? 0 ) > 0 || (int) ( $row->client_id ?? 0 ) > 0 ) {
		return false;
	}
	return true;
}

/**
 * Delete unbooked overlapping timetable rows (waiting, closed, etc.).
 *
 * @param object[] $rows Rows to remove.
 */
function snks_manual_booking_delete_replaceable_conflict_slots( $rows ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_provider_timetable';
	foreach ( (array) $rows as $row ) {
		if ( ! snks_manual_booking_conflict_is_replaceable( $row ) ) {
			continue;
		}
		$slot_id = isset( $row->ID ) ? absint( $row->ID ) : 0;
		if ( $slot_id < 1 ) {
			continue;
		}
		$wpdb->delete( $table, array( 'ID' => $slot_id ), array( '%d' ) );
	}
}

/**
 * Manual booking overlap policy: block open/completed (or any booked row); remove other overlaps.
 *
 * @param object[] $conflicts     Overlapping rows.
 * @param string   $date_fallback Date Y-m-d for error payload.
 * @return bool True when creation/booking may proceed.
 */
function snks_manual_booking_apply_change_appointment_conflict_policy( $conflicts, $date_fallback ) {
	$blocking    = array();
	$replaceable = array();

	foreach ( (array) $conflicts as $row ) {
		if ( snks_manual_booking_conflict_is_blocking( $row ) || (int) ( $row->order_id ?? 0 ) > 0 || (int) ( $row->client_id ?? 0 ) > 0 ) {
			$blocking[] = $row;
		} elseif ( snks_manual_booking_conflict_is_replaceable( $row ) ) {
			$replaceable[] = $row;
		}
	}

	if ( ! empty( $blocking ) ) {
		snks_manual_booking_register_overlap_failure( $blocking, $date_fallback );
		return false;
	}

	if ( ! empty( $replaceable ) ) {
		snks_manual_booking_delete_replaceable_conflict_slots( $replaceable );
	}

	return true;
}

/**
 * Build overlap payload for API/UI from a timetable row.
 *
 * @param object $row           Timetable row.
 * @param string $date_fallback Date if row date_time is missing.
 * @return array{slot_id:int,date:string,starts:string,ends:string,order_id:int,session_status:string}
 */
function snks_manual_booking_format_overlap_slot( $row, $date_fallback ) {
	$slot_id = isset( $row->ID ) ? absint( $row->ID ) : 0;
	$date_part = $date_fallback;
	if ( ! empty( $row->date_time ) && preg_match( '/^(\d{4}-\d{2}-\d{2})/', (string) $row->date_time, $dm ) ) {
		$date_part = $dm[1];
	}
	$existing_start_min = snks_manual_booking_time_to_minutes( $row->starts );
	$period             = isset( $row->period ) && (int) $row->period > 0 ? (int) $row->period : 45;
	$starts_raw         = isset( $row->starts ) ? trim( (string) $row->starts ) : '';
	$ends_raw           = isset( $row->ends ) ? trim( (string) $row->ends ) : '';
	if ( strlen( $starts_raw ) >= 5 ) {
		$starts_disp = substr( $starts_raw, 0, 5 );
	} else {
		$starts_disp = $starts_raw;
	}
	if ( strlen( $ends_raw ) >= 5 ) {
		$ends_disp = substr( $ends_raw, 0, 5 );
	} else {
		$end_min_calc = $existing_start_min >= 0 ? $existing_start_min + $period : 0;
		$ends_disp    = sprintf( '%02d:%02d', (int) floor( $end_min_calc / 60 ) % 24, $end_min_calc % 60 );
	}

	return array(
		'slot_id'        => $slot_id,
		'date'           => sanitize_text_field( $date_part ),
		'starts'         => sanitize_text_field( $starts_disp ),
		'ends'           => sanitize_text_field( $ends_disp ),
		'order_id'       => isset( $row->order_id ) ? absint( $row->order_id ) : 0,
		'session_status' => isset( $row->session_status ) ? sanitize_key( (string) $row->session_status ) : '',
	);
}

/**
 * Store overlap error globals for ensure_slot / booking handlers.
 *
 * @param object[] $conflicts     Conflicting timetable rows.
 * @param string   $date_fallback Date Y-m-d.
 */
function snks_manual_booking_register_overlap_failure( $conflicts, $date_fallback ) {
	global $snks_manual_booking_ensure_slot_last_error, $snks_manual_booking_ensure_slot_overlapping_slots;

	$snks_manual_booking_ensure_slot_last_error = 'overlap';
	$payload                                  = array();
	foreach ( (array) $conflicts as $row ) {
		$formatted = snks_manual_booking_format_overlap_slot( $row, $date_fallback );
		if ( $formatted['slot_id'] > 0 ) {
			$payload[ $formatted['slot_id'] ] = $formatted;
		}
	}
	$snks_manual_booking_ensure_slot_overlapping_slots = array_values( $payload );
}

/**
 * Standard failure array when a datetime conflict is detected.
 *
 * @return array{success:bool,message:string,overlap:bool,overlapping_slots:array}
 */
function snks_manual_booking_overlap_failure_result() {
	return array(
		'success'           => false,
		'message'           => __( 'هذا الموعد يتداخل مع موعد موجود. اختر وقتاً آخر.', 'shrinks' ),
		'overlap'           => true,
		'overlapping_slots' => snks_manual_booking_ensure_slot_overlapping_slots(),
	);
}

/**
 * Ensure a slot exists for therapist at date+time. Finds existing or creates new.
 * Used when admin selects "create new slot" with custom date and base hour.
 * Removes unbooked overlapping slots (waiting/closed) and only blocks open/completed (or booked rows).
 * Manual booking ignores therapist off_days and block_if_before settings.
 *
 * @param int        $therapist_id     Therapist user ID.
 * @param string     $date             Date Y-m-d.
 * @param string     $time             Start time (HH:MM or HH:MM:SS).
 * @param int[]|int  $exclude_slot_ids Slot ID(s) to exclude from overlap checks (e.g. current booking when rescheduling).
 * @return int|false Slot ID on success, false on failure.
 */
function snks_manual_booking_ensure_slot( $therapist_id, $date, $time, $exclude_slot_ids = array() ) {
	global $wpdb, $snks_manual_booking_ensure_slot_last_error, $snks_manual_booking_ensure_slot_overlapping_slots;

	$snks_manual_booking_ensure_slot_last_error        = null;
	$snks_manual_booking_ensure_slot_overlapping_slots = array();

	$therapist_id = absint( $therapist_id );
	$date         = sanitize_text_field( $date );
	$time         = sanitize_text_field( $time );
	if ( ! $therapist_id || ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		$snks_manual_booking_ensure_slot_last_error = 'invalid_params';
		return false;
	}
	$time = snks_manual_booking_normalize_time_hms( $time );
	if ( '' === $time ) {
		$snks_manual_booking_ensure_slot_last_error = 'invalid_time';
		return false;
	}

	$exclude_slot_ids = array_filter( array_map( 'absint', (array) $exclude_slot_ids ) );

	// Respect therapist visibility settings.
	$doctor_settings = snks_doctor_settings( $therapist_id );
	$attendance_type = isset( $doctor_settings['attendance_type'] ) ? (string) $doctor_settings['attendance_type'] : 'online';
	if ( 'offline' === $attendance_type ) {
		$snks_manual_booking_ensure_slot_last_error = 'attendance_type_offline';
		return false;
	}

	// Only allow creating 45-minute slots.
	$allow_45 = ! empty( $doctor_settings['45_minutes'] ) && ( 'on' === $doctor_settings['45_minutes'] || 'true' === (string) $doctor_settings['45_minutes'] );
	if ( ! $allow_45 ) {
		$snks_manual_booking_ensure_slot_last_error = 'period_45_disabled';
		return false;
	}

	// Manual booking: do not apply therapist off_days (secretary may book on those dates).

	$global_excluded = function_exists( 'snks_get_global_excluded_booking_dates' ) ? snks_get_global_excluded_booking_dates() : array();
	if ( in_array( $date, $global_excluded, true ) ) {
		$snks_manual_booking_ensure_slot_last_error = 'global_excluded_date';
		return false;
	}

	$days_count = ! empty( $doctor_settings['form_days_count'] ) ? absint( $doctor_settings['form_days_count'] ) : 30;
	if ( $days_count > 90 ) {
		$days_count = 90;
	}
	$today = current_time( 'Y-m-d' );
	$max_date_ts = strtotime( $today . ' +' . $days_count . ' days' );
	$max_date = $max_date_ts ? wp_date( 'Y-m-d', $max_date_ts ) : $today;
	if ( $date > $max_date ) {
		$snks_manual_booking_ensure_slot_last_error = 'outside_form_days_count';
		return false;
	}

	// Manual booking new-slot creation intentionally ignores block_if_before_number / block_if_before_unit
	// so admins can book inside the therapist's public cutoff window when needed.

	$date_time    = $date . ' ' . $time;
	$requested_ts = strtotime( $date_time );
	if ( false === $requested_ts ) {
		$snks_manual_booking_ensure_slot_last_error = 'invalid_datetime';
		return false;
	}

	$table = $wpdb->prefix . 'snks_provider_timetable';

	$new_start_min = snks_manual_booking_time_to_minutes( $time );
	if ( $new_start_min < 0 ) {
		$snks_manual_booking_ensure_slot_last_error = 'invalid_time';
		return false;
	}
	$new_end_min = $new_start_min + 45;

	$conflicts = snks_manual_booking_find_datetime_conflicts( $therapist_id, $date, $new_start_min, $new_end_min, $exclude_slot_ids );
	if ( ! snks_manual_booking_apply_change_appointment_conflict_policy( $conflicts, $date ) ) {
		return false;
	}

	$reusable = snks_manual_booking_find_reusable_waiting_slot( $therapist_id, $date, $time );
	if ( $reusable && isset( $reusable->ID ) ) {
		return (int) $reusable->ID;
	}

	// Create new slot. 45-minute session.
	$ts = $requested_ts;
	if ( false === $ts || $ts < time() ) {
		$snks_manual_booking_ensure_slot_last_error = 'past_slot';
		return false;
	}
	$day_name = gmdate( 'l', $ts );
	$ends_ts  = strtotime( '+45 minutes', $ts );
	$ends     = gmdate( 'H:i:s', $ends_ts );

	$insert = array(
		'user_id'         => $therapist_id,
		'client_id'       => 0,
		'session_status'  => 'waiting',
		'day'             => $day_name,
		'base_hour'       => $time,
		'period'          => 45,
		'date_time'       => gmdate( 'Y-m-d H:i:s', $ts ),
		'starts'          => $time,
		'ends'            => $ends,
		'clinic'          => '',
		'attendance_type' => 'online',
		'order_id'        => 0,
		'settings'        => '',
	);

	$inserted = $wpdb->insert( $table, $insert );
	if ( ! $inserted || $wpdb->last_error ) {
		$snks_manual_booking_ensure_slot_last_error = 'db_insert';
		return false;
	}
	return (int) $wpdb->insert_id;
}

/**
 * Localized explanation for the last failed snks_manual_booking_ensure_slot call (excluding overlap).
 *
 * @return string Empty string if no specific reason.
 */
function snks_manual_booking_ensure_slot_failure_message() {
	$reason = snks_manual_booking_ensure_slot_last_error();
	if ( ! $reason || 'overlap' === $reason ) {
		return '';
	}
	switch ( $reason ) {
		case 'invalid_params':
			return __( 'تاريخ أو معالج غير صالح.', 'shrinks' );
		case 'invalid_time':
			return __( 'صيغة الوقت غير صالحة.', 'shrinks' );
		case 'invalid_datetime':
			return __( 'التاريخ والوقت غير صالحين.', 'shrinks' );
		case 'attendance_type_offline':
			return __( 'هذا المعالج يقدم جلسات حضورية فقط؛ لا يمكن إنشاء موعد أونلاين.', 'shrinks' );
		case 'period_45_disabled':
			return __( 'جلسات 45 دقيقة أونلاين غير مفعّلة لهذا المعالج.', 'shrinks' );
		case 'off_day':
			return __( 'هذا اليوم مُعلّم كيوم عطلة للمعالج.', 'shrinks' );
		case 'global_excluded_date':
			return __( 'هذا التاريخ مستبعد من الحجز (عطلة رسمية أو إعداد عام).', 'shrinks' );
		case 'outside_form_days_count':
			return __( 'التاريخ خارج نطاق الأيام المتاحة في إعدادات المعالج.', 'shrinks' );
		case 'blocked_if_before':
			return __( 'الوقت المختار أبكر من الحد الأدنى المسموح للحجز لهذا المعالج.', 'shrinks' );
		case 'past_slot':
			return __( 'لا يمكن حجز موعد في وقت مضى.', 'shrinks' );
		case 'db_insert':
			return __( 'تعذر حفظ الموعد في قاعدة البيانات. حاول مرة أخرى.', 'shrinks' );
		default:
			return __( 'تعذر إنشاء الموعد. راجع التاريخ والوقت وإعدادات المعالج.', 'shrinks' );
	}
}

/**
 * Process admin manual booking (new appointment).
 *
 * @param int    $patient_id     Patient user ID.
 * @param int    $therapist_id   Therapist user ID.
 * @param int    $slot_id        Available timetable slot ID.
 * @param string $country_code   Country code for pricing.
 * @param float|null $amount_override Optional session price (EGP); when set, used as order total and for profit on completion instead of therapist country price.
 * @param string $first_name     Patient first name (billing).
 * @param string $last_name      Patient last name (billing).
 * @return array{success:bool, message:string, order_id?:int}
 */
function snks_process_admin_manual_booking( $patient_id, $therapist_id, $slot_id, $country_code = 'EG', $amount_override = null, $first_name = '', $last_name = '' ) {
	global $wpdb;

	$patient_id   = absint( $patient_id );
	$therapist_id = absint( $therapist_id );
	$slot_id      = absint( $slot_id );

	if ( ! $patient_id || ! $therapist_id || ! $slot_id ) {
		$missing = array();
		if ( ! $patient_id ) {
			$missing[] = __( 'المريض', 'shrinks' );
		}
		if ( ! $therapist_id ) {
			$missing[] = __( 'المعالج', 'shrinks' );
		}
		if ( ! $slot_id ) {
			$missing[] = __( 'الموعد / خانة الوقت', 'shrinks' );
		}
		/* translators: %s: comma-separated list of missing fields */
		return array( 'success' => false, 'message' => sprintf( __( 'بيانات ناقصة: %s.', 'shrinks' ), implode( '، ', $missing ) ) );
	}

	$patient = get_userdata( $patient_id );
	if ( ! $patient ) {
		return array( 'success' => false, 'message' => __( 'المريض غير موجود.', 'shrinks' ) );
	}

	$first_name = trim( (string) $first_name );
	$last_name  = trim( (string) $last_name );
	if ( $first_name === '' || $last_name === '' ) {
		return array( 'success' => false, 'message' => __( 'يجب إدخال الاسم الأول واسم العائلة للمريض.', 'shrinks' ) );
	}

	$therapist = get_userdata( $therapist_id );
	if ( ! $therapist ) {
		return array( 'success' => false, 'message' => __( 'المعالج غير موجود.', 'shrinks' ) );
	}

	// Ensure patient billing name is set/updated.
	update_user_meta( $patient_id, 'billing_first_name', $first_name );
	update_user_meta( $patient_id, 'billing_last_name', $last_name );

	$slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE ID = %d AND session_status = 'waiting' AND order_id = 0 
		 AND (client_id = 0 OR client_id IS NULL) 
		 AND user_id = %d",
		$slot_id,
		$therapist_id
	) );

	if ( ! $slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد غير متاح أو تم حجزه.', 'shrinks' ) );
	}

	$slot_date = '';
	if ( ! empty( $slot->date_time ) && preg_match( '/^(\d{4}-\d{2}-\d{2})/', (string) $slot->date_time, $dm ) ) {
		$slot_date = $dm[1];
	}
	$slot_start_min = snks_manual_booking_time_to_minutes( $slot->starts );
	$period         = isset( $slot->period ) && $slot->period > 0 ? (int) $slot->period : 45;
	if ( $slot_start_min >= 0 && $slot_date ) {
		$book_conflicts = snks_manual_booking_find_datetime_conflicts(
			$therapist_id,
			$slot_date,
			$slot_start_min,
			$slot_start_min + $period,
			array( $slot_id )
		);
		if ( ! snks_manual_booking_apply_change_appointment_conflict_policy( $book_conflicts, $slot_date ) ) {
			return snks_manual_booking_overlap_failure_result();
		}
	}

	if ( $amount_override !== null && $amount_override > 0 ) {
		$session_amount = floatval( $amount_override );
		if ( empty( $country_code ) ) {
			$country_code = 'EG';
		}
	} else {
		if ( empty( $country_code ) ) {
			return array( 'success' => false, 'message' => __( 'يرجى إختيار السعر أو إدخال سعر مخصص.', 'shrinks' ) );
		}
		$pricing = snks_get_ai_therapist_price( $therapist_id, $country_code, $period );
		$session_amount = isset( $pricing['original_price'] ) ? floatval( $pricing['original_price'] ) : 0;
		if ( $session_amount <= 0 ) {
			$session_amount = SNKS_AI_Products::get_default_session_price();
		}
	}

	$order = SNKS_AI_Orders::create_admin_manual_order( $patient_id, $slot_id, $session_amount, $country_code );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return array( 'success' => false, 'message' => __( 'فشل إنشاء الطلب.', 'shrinks' ) );
	}

	$result = SNKS_AI_Orders::book_slot_for_order( $slot_id, $order->get_id(), $patient_id, 'admin_manual_booking:1' );
	if ( ! $result ) {
		$order->set_status( 'cancelled' );
		$order->save();
		return array( 'success' => false, 'message' => __( 'فشل ربط الموعد بالطلب.', 'shrinks' ) );
	}

	// Therapist AI profit is credited when the session is marked completed (same as non-manual AI), not at booking time.
	// Session action and order meta (ai_therapist_id, ai_user_id) are set by snks_handle_ai_appointment_creation on snks_appointment_created.

	// Send notifications.
	SNKS_AI_Orders::send_ai_order_notifications( $order->get_id() );

	return array(
		'success'  => true,
		'message'  => __( 'تم الحجز بنجاح.', 'shrinks' ),
		'order_id' => $order->get_id(),
	);
}

/**
 * Process admin change appointment (move to new slot).
 *
 * @param int $existing_booking_id Current timetable slot ID (booked).
 * @param int $new_slot_id         New available timetable slot ID.
 * @return array{success:bool, message:string}
 */
function snks_process_admin_change_appointment( $existing_booking_id, $new_slot_id ) {
	global $wpdb;

	$existing_booking_id = absint( $existing_booking_id );
	$new_slot_id         = absint( $new_slot_id );

	if ( ! $existing_booking_id || ! $new_slot_id ) {
		$missing = array();
		if ( ! $existing_booking_id ) {
			$missing[] = __( 'الموعد الحالي', 'shrinks' );
		}
		if ( ! $new_slot_id ) {
			$missing[] = __( 'الموعد الجديد', 'shrinks' );
		}
		return array( 'success' => false, 'message' => sprintf( __( 'بيانات ناقصة: %s.', 'shrinks' ), implode( '، ', $missing ) ) );
	}

	if ( $existing_booking_id === $new_slot_id ) {
		return array( 'success' => false, 'message' => __( 'الموعد الجديد مطابق للموعد الحالي.', 'shrinks' ) );
	}

	$old_slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d AND session_status = 'open' AND client_id > 0 AND order_id > 0",
		$existing_booking_id
	) );

	if ( ! $old_slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد الحالي غير موجود أو غير محجوز.', 'shrinks' ) );
	}

	$patient_id   = (int) $old_slot->client_id;
	$therapist_id = (int) $old_slot->user_id;
	$order_id    = (int) $old_slot->order_id;

	$new_slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE ID = %d AND session_status = 'waiting' AND order_id = 0 
		 AND (client_id = 0 OR client_id IS NULL) AND user_id = %d",
		$new_slot_id,
		$therapist_id
	) );

	if ( ! $new_slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد الجديد غير متاح.', 'shrinks' ) );
	}

	$new_slot_date = '';
	if ( ! empty( $new_slot->date_time ) && preg_match( '/^(\d{4}-\d{2}-\d{2})/', (string) $new_slot->date_time, $ndm ) ) {
		$new_slot_date = $ndm[1];
	}
	$new_start_min = snks_manual_booking_time_to_minutes( $new_slot->starts );
	$new_period    = isset( $new_slot->period ) && (int) $new_slot->period > 0 ? (int) $new_slot->period : 45;
	if ( $new_start_min >= 0 && $new_slot_date ) {
		$change_conflicts = snks_manual_booking_find_datetime_conflicts(
			$therapist_id,
			$new_slot_date,
			$new_start_min,
			$new_start_min + $new_period,
			array( $existing_booking_id, $new_slot_id )
		);
		if ( ! snks_manual_booking_apply_change_appointment_conflict_policy( $change_conflicts, $new_slot_date ) ) {
			return snks_manual_booking_overlap_failure_result();
		}
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return array( 'success' => false, 'message' => __( 'الطلب غير موجود.', 'shrinks' ) );
	}

	$wpdb->query( 'START TRANSACTION' );

	try {
		// Release old slot and reset all notification/state columns to initial state.
		$wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			array(
				'session_status'                      => 'waiting',
				'client_id'                           => 0,
				'order_id'                            => 0,
				'settings'                            => str_replace( 'admin_manual_booking:1', '', $old_slot->settings ),
				'notification_24hr_sent'              => 0,
				'notification_1hr_sent'               => 0,
				'whatsapp_new_session_sent'           => 0,
				'whatsapp_doctor_notified'             => 0,
				'whatsapp_rosheta_activated'          => 0,
				'whatsapp_rosheta_booked'             => 0,
				'whatsapp_doctor_reminded'            => 0,
				'whatsapp_patient_now_sent'           => 0,
				'whatsapp_appointment_changed'        => 0,
				'whatsapp_therapist_appointment_changed' => 0,
			),
			array( 'ID' => $existing_booking_id ),
			array( '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' ),
			array( '%d' )
		);

		// Book new slot with same order.
		$settings = 'ai_booking:completed admin_manual_booking:1';
		$booked = SNKS_AI_Orders::book_slot_for_order(
			$new_slot_id,
			$order_id,
			$patient_id,
			'admin_manual_booking:1',
			array( 'skip_meet_assign' => true )
		);
		if ( ! $booked ) {
			throw new Exception( __( 'فشل حجز الموعد الجديد.', 'shrinks' ) );
		}

		if ( function_exists( 'snks_transfer_google_meet_url_timetable' ) ) {
			$meet_transfer = snks_transfer_google_meet_url_timetable( $existing_booking_id, $new_slot_id );
			if ( is_wp_error( $meet_transfer ) ) {
				throw new Exception( $meet_transfer->get_error_message() );
			}
		}

		// Update order item slot_id (find the item for this appointment).
		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$item_slot = (int) $item->get_meta( 'slot_id', true );
			if ( $item_slot === $existing_booking_id ) {
				$item->update_meta_data( 'slot_id', $new_slot_id );
				$item->update_meta_data( 'appointment_id', $new_slot_id );
				$item->update_meta_data( 'session_date', $new_slot->date_time );
				$item->update_meta_data( 'session_time', $new_slot->starts );
				$item->save();
				break;
			}
		}

		$wpdb->query( 'COMMIT' );

		// Send notification with new meeting link.
		SNKS_AI_Orders::send_ai_order_notifications( $order_id );

		// Send WhatsApp appointment-change notifications and set timetable flags on the new slot.
		$old_date = date( 'Y-m-d', strtotime( $old_slot->date_time ) );
		$old_time = $old_slot->starts;
		$new_date = date( 'Y-m-d', strtotime( $new_slot->date_time ) );
		$new_time = $new_slot->starts;
		if ( function_exists( 'snks_send_appointment_change_notification' ) ) {
			snks_send_appointment_change_notification( $new_slot_id, $old_date, $old_time, $new_date, $new_time, $patient_id, true );
		}
		if ( function_exists( 'snks_send_therapist_appointment_change_notification' ) ) {
			snks_send_therapist_appointment_change_notification( $new_slot_id, $old_date, $old_time, $new_date, $new_time, $patient_id );
		}

		return array( 'success' => true, 'message' => __( 'تم تغيير الموعد بنجاح.', 'shrinks' ), 'slot_id' => $new_slot_id );
	} catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		return array( 'success' => false, 'message' => $e->getMessage() );
	}
}
