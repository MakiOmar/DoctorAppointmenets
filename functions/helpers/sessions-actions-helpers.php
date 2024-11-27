<?php
/**
 * Sessions actions Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump, WordPress.DB.DirectDatabaseQuery.DirectQuery


/**
 * Pospon appointment
 *
 * @param int    $id  Timtable ID.
 * @param int    $patient_id  patient's ID.
 * @param int    $doctor_id  Doctor's ID.
 * @param string $date  Appointment date.
 * @return void
 */
function snks_postpon_appointment( $id, $patient_id, $doctor_id, $date ) {
	$updated  = snks_update_timetable(
		$id,
		array(
			'session_status' => 'postponed',
		),
	);
	$nickname = get_user_meta( $doctor_id, 'nickname', true );
	if ( $updated ) {
		$user   = get_user_by( 'id', $patient_id );
		$doctor = get_user_by( 'id', $doctor_id );
		if ( ! $user || ! $doctor ) {
			return;
		}
		$after_button  = '<p style="Margin:0;line-height:36px;mso-line-height-rule:exactly;font-family:georgia, times, times new roman, serif;font-size:30px;font-style:normal;font-weight:normal;color:#023047">';
		$after_button  = '<b style="display:block;margin-top:20px;font-size:20px">';
		$after_button  = 'مع الطبيب';
		$after_button .= '<br>';
		$after_button .= get_user_meta( $doctor_id, 'billing_first_name', true ) . ' ' . get_user_meta( $doctor_id, 'billing_last_name', true );
		$after_button .= '</b>';
		$after_button .= '</p>';
		list($title, $sub_title, $text_1, $text_2, $text_3, $button_text, $button_url) = array(
			'تم تأجيل موعدك',
			'في جلسة',
			'نعتذر لك! الطبيب يبلغك بتأجيل موعد الجلسة الخاصة بك',
			'تم تأجيل الموعد الخاص بك',
			'يمكنك تغيير الموعد مجانا',
			'تعديل الموعد <img src="' . SNKS_ARROW . '"/> ' . snks_localize_day( gmdate( 'D', strtotime( $date ) ) ) . ' ' . $date,
			'#',
		);
		snks_send_email( $user->user_email, $title, $sub_title, $text_1, $text_2, $text_3, $button_text, $button_url, $after_button );
		$billing_phone = get_user_meta( $patient_id, 'billing_phone', true );
		$message       = sprintf(
			'نعتذر عن الغاء جلسة %1$s، لحجز موعد اخر مجانا : %2$s',
			gmdate( 'Y-d-m', strtotime( $date ) ),
			add_query_arg( 'edit-booking', $id, site_url( '7jz/' . $nickname ) )
		);
	}
	// this.
}

/**
 * Delay appointment
 *
 * @param int    $patient_id  patient's ID.
 * @param int    $doctor_id  Doctor's ID.
 * @param string $delay_period  Delay period.
 * @param string $date  Appointment date.
 * @param string $time  Appointment time.
 * @return void
 */
function snks_delay_appointment( $patient_id, $doctor_id, $delay_period, $date, $time ) {
	$user   = get_user_by( 'id', $patient_id );
	$doctor = get_user_by( 'id', $doctor_id );
	if ( ! $user || ! $doctor ) {
		return;
	}
	// Delete custom emaol code on commit 8072d24.
	$title    = 'تم تأخير موعدك';
	$new_hour = gmdate( 'h:i a', strtotime( "+$delay_period minutes", strtotime( $time ) ) );
	$to       = $user->user_email;
	$subject  = $title . ' - ' . SNKS_APP_NAME;
	$message  = sprintf(
		'نعتذر عن تاخير جلسة يوم %1$s لمدة %2$s ليصبح موعدها %3$s',
		gmdate( 'Y-d-m', strtotime( $date ) ),
		$delay_period . ' دقيقة',
		$new_hour
	);
	$headers  = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);
	return wp_mail( $to, $subject, $message, $headers );
}


/**
 * Get session actions
 *
 * @param int $session_id Session ID.
 * @param int $client_id Client ID.
 * @return mixed
 */
function snks_get_session_actions( $session_id, $client_id ) {
	global $wpdb;
	// Prepare the query parameters.
	$session_id = intval( $session_id );
	$client_id  = intval( $client_id );

	// Generate a unique cache key.
	$cache_key = 'snks_session_actions_' . $session_id . '_' . $client_id;
	$results   = wp_cache_get( $cache_key );
	if ( false === $results ) {
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
			FROM {$wpdb->prefix}snks_sessions_actions
			WHERE action_session_id = %d AND case_id = %d",
				$session_id,
				$client_id,
			)
		);
		wp_cache_set( $cache_key, $results, '', 3600 );
	}
	return $results;
}

/**
 * Get session actions
 *
 * @param int $session_id Session ID.
 * @return mixed
 */
function snks_get_session_actions_by( $session_id ) {
	global $wpdb;
	// Prepare the query parameters.
	$session_id = intval( $session_id );

	// Generate a unique cache key.
	$cache_key = 'snks_session_actions_' . $session_id;
	$results   = wp_cache_get( $cache_key );
	if ( false === $results ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_sessions_actions
				WHERE action_session_id = %d",
				$session_id,
			)
		);
		wp_cache_set( $cache_key, $results, '', 3600 );
	}
	return $results;
}

/**
 * Insert session actions
 *
 * @param int    $session_id Session ID.
 * @param int    $client_id Client ID.
 * @param string $attendance Attendance yes/no.
 * @return mixed
 */
function snks_insert_session_actions( $session_id, $client_id, $attendance ) {
	$get_session_actions = snks_get_session_actions( $session_id, $client_id );
	if ( $get_session_actions ) {
		return;
	}
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_sessions_actions';
	// Prepare the data for insertion.
	$data = array(
		'action_session_id' => absint( $session_id ),
		'case_id'           => absint( $client_id ),
		'attendance'        => sanitize_text_field( $attendance ),
	);

	// Insert the data into the table.
	$wpdb->insert( $table_name, $data );

	// Check if the insertion was successful.
	if ( $wpdb->last_error ) {
		return false; // Return false if there was an error.
	} else {
		return $wpdb->insert_id; // Return the inserted record ID.
	}
}


/**
 * Update session actions
 *
 * @param int    $session_id Session ID.
 * @param int    $client_id Client ID.
 * @param string $attendance Attendance yes/no.
 * @return mixed
 */
function snks_update_session_actions( $session_id, $client_id, $attendance ) {
	$get_session_actions = snks_get_session_actions( $session_id, $client_id );
	if ( ! $get_session_actions ) {
		return;
	}
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_sessions_actions';
	// Prepare the data for insertion.
	$data = array(
		'attendance' => sanitize_text_field( $attendance ),
	);

	// Insert the data into the table.
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update(
		$table_name,
		$data,
		array(
			'action_session_id' => absint( $session_id ),
			'case_id'           => absint( $client_id ),
		)
	);

	// Check if the insertion was successful.
	if ( $wpdb->last_error ) {
		return false; // Return false if there was an error.
	} else {
		return $wpdb->insert_id; // Return the inserted record ID.
	}
}
