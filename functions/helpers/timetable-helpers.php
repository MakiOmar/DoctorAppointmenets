<?php
/**
 * Timetable Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump, WordPress.DB.DirectDatabaseQuery.DirectQuery

/**
 * Get user timetable
 *
 * @param int    $user_id User ID.
 * @param string $booking_day Booking day date.
 * @param string $start_time Start time.
 */
function snks_get_timetable( $user_id = false, $booking_day = '', $start_time = '' ) {
	global $wpdb;
	// Prepare the query parameters.
	$user_id     = intval( $user_id );
	$booking_day = sanitize_text_field( $booking_day );
	$start_time  = sanitize_text_field( $start_time );

	// Generate a unique cache key.
	$cache_key = $user_id ? 'snks_timetable_' . $user_id . '_' . $booking_day . '_' . $start_time : 'snks_timetable_' . $booking_day . '_' . $start_time;

	$results = wp_cache_get( $cache_key );
	if ( false === $results ) {
		if ( $user_id ) {
			// Execute the query.
			$results = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d AND start_time = %s AND booking_day = %s",
					$user_id,
					$start_time,
					$booking_day
				)
			);
		} else {
			// Execute the query.
			$results = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE start_time = %s AND booking_day = %s",
					$start_time,
					$booking_day
				)
			);
		}

		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Get user timetables
 *
 * @param int $user_id User ID.
 * @return mixed
 */
function snks_get_user_timetables( $user_id ) {
	global $wpdb;
	// Prepare the query parameters.
	$user_id = intval( $user_id );

	// Generate a unique cache key.
	$cache_key = 'snks_user_timetables_' . $user_id;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {

		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
            FROM {$wpdb->prefix}snks_provider_timetable
            WHERE user_id = %d
			ORDER BY date_time ASC",
				$user_id
			)
		);
		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Get timetable by ID
 *
 * @param int   $column Column to query.
 * @param mixed $value Value to query.
 * @param mixed $placeholder Prepare placeholder.
 * @return mixed
 */
function snks_get_timetable_by( $column, $value, $placeholder = '%d' ) {
	global $wpdb;
	// Generate a unique cache key.
	$cache_key = 'snks_timetable_by_' . $column . '_' . $value;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
            FROM {$wpdb->prefix}snks_provider_timetable
            WHERE {$column} = {$placeholder}",
				$value
			)
		);
		//phpcs:enable
		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Will close other timetables if one of the same datetime is booked
 *
 * @param object $booked_timetable Booked timetable.
 * @return void
 */
function snks_close_others( $booked_timetable ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_provider_timetable';
	// phpcs:disable.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $table_name
			SET session_status = 'closed'
			WHERE date_time = %s
			AND order_id = 0",
			$booked_timetable->date_time		)
	);
	// phpcs:enable.
}
/**
 * If Timetable exists
 *
 * @param int    $user_id User ID.
 * @param string $date_time Date time.
 * @param string $day Day Abbreviation.
 * @param mixed  $starts Stat time.
 * @param mixed  $ends end time.
 * @return mixed
 */
function snks_timetable_exists( $user_id, $date_time, $day, $starts, $ends ) {
	global $wpdb;

	//phpcs:disable
	// Execute the query.
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
		FROM {$wpdb->prefix}snks_provider_timetable
		WHERE user_id = %d
		AND date_time = %s
		AND day = %s
		AND starts = %s
		AND ends = %s",
			$user_id,
			$date_time,
			$day,
			$starts,
			$ends
		)
	);
	return $results;
}
/**
 * Get open timetable by ID
 *
 * @param int   $column Column to query.
 * @param mixed $value Value to query.
 * @return mixed
 */
function snks_get_open_timetable_by( $column, $value ) {
	global $wpdb;
	// Generate a unique cache key.
	$cache_key = 'snks_open_timetable_by_' . $column;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Execute the query.
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
            FROM {$wpdb->prefix}snks_provider_timetable
            WHERE {$column} = %d
			AND session_status = %s",
				$value,
				'open'
			)
		);
		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Insert timetable
 *
 * @param array $data data to insert.
 * @return mixed
 */
function snks_insert_timetable( $data ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
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
 * Update timetable data by ID.
 *
 * @param int   $id Row iD.
 * @param array $data Data to be updated.
 * @return bool
 */
function snks_update_timetable( $id, $data ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$updated = $wpdb->update(
		$table_name,
		$data,
		array(
			'ID' => $id,
		)
	);
	return $updated;
    //phpcs:enable.
}

/**
 * Delete timetable
 *
 * @param int $id ID.
 * @return bool
 */
function snks_delete_timetable( $id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_provider_timetable';
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->delete( $table_name, array( 'ID' => $id ), array( '%d' ) );
	// phpcs:enable.
	if ( $wpdb->rows_affected > 0 ) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

/**
 * Get bookable dates
 *
 * @param int    $user_id User's ID.
 * @param int    $period Session period.
 * @param string $_for Period to get dates for.
 * @param string $attendance_type Attendance type.
 * @return mixed
 */
function get_bookable_dates( $user_id, $period, $_for = '+1 month', $attendance_type = 'both' ) {
	global $wpdb;
	//phpcs:disable WordPress.DateTime.CurrentTimeTimestamp.Requested
	$current_date = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
	$end_date     = date_i18n( 'Y-m-d H:i:s', strtotime( $_for, strtotime( $current_date ) ) );
	$cache_key    = 'bookable-dates-' . $current_date . '-' . $period;
	$results      = wp_cache_get( $cache_key );//phpcs:disable
	$_order    = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';
	
	if ( ! $results ) {
		if ( 'both' === $attendance_type ) {
			$_query = $wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d
				AND period = %d
				AND date_time
				BETWEEN %s AND %s
				AND session_status = %s
				AND order_id = %d
				ORDER BY date_time {$_order}",
				$user_id,
				$period,
				$current_date,
				$end_date,
				'waiting',
				0
			);
		} else {
			$_query = $wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d
				AND period = %d
				AND date_time
				BETWEEN %s AND %s
				AND attendance_type = %s
				AND session_status = %s
				AND order_id = %d
				ORDER BY date_time {$_order}",
				$user_id,
				$period,
				$current_date,
				$end_date,
				$attendance_type,
				'waiting',
				0
			);
		}
		$results = $wpdb->get_results( $_query );
		wp_cache_set( $cache_key, $results );
	}
	return $results;
}

/**
 * Get users appointments by date
 *
 * @param int    $user_id User's ID.
 * @param string $date Date.
 * @param int    $period Period.
 * @return mixed
 */
function snks_user_appointments_by_date_period( $user_id, $date, $period ) {
	global $wpdb;
	//$current_date = date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) + ( 2 * 3600 )  );
	$cache_key = 'dates-appoointments-' . $user_id . '-' . $date . '-' . $period;
	$results   = wp_cache_get( $cache_key );//phpcs:disable
	$_order    = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';
	if ( ! $results ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d
				AND period = %d
				AND DATE(date_time) = %s
				AND order_id = 0
				ORDER BY date_time {$_order}",
				$user_id,
				absint( $period ),
				$date
				//$current_date
			)
		);

		wp_cache_set( $cache_key, $results );
	}
	return $results;
}

/**
 * Get bookable date times
 *
 * @param string $date Date.
 * @return mixed
 */
function get_bookable_date_available_times( $date ) {
	global $wpdb;
	$current_date = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 2 * 3600 )  );
	$cache_key = 'bookable-date-times-' . $date;
	$results = wp_cache_get( $cache_key );

	if ( ! $results ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT start_time
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE booking_day = %s
				AND booking_availability = %d
				AND date_time > %s",
				$date,
				1, // 1 represents true for booking_availability.
				$current_date
			)
		);

		wp_cache_set( $cache_key, $results );
	}

	return $results;
}

/**
 * Get doctor sessions
 *
 * @param string $tense past|future|all.
 * @param string $status waiting|open|closed|cancelled.
 * @param bool   $ordered True for ordered.
 * @return mixed
 */
function snks_get_doctor_sessions( $tense, $status = 'waiting', $ordered = false ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$cache_key = 'doctor-' . $tense . '-sessions-' . $user_id;
	$results   = wp_cache_get( $cache_key );
	$operator  = 'past' === $tense ? '<' : '>';
	if ( ! $results ) {
		$query = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = %d And session_status= %s";
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( 'all' !== $tense ) {
			$query .= " AND date_time {$operator}= CURRENT_TIMESTAMP()";
		}
		if ( $ordered ) {
			$query .= " AND order_id != 0";
		}
		$query .= " ORDER BY date_time ASC";
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$user_id,
				$status
			)
		);
		wp_cache_set( $cache_key, $results );
	}
	$temp = [];
	if ( $results && is_array( $results ) ) {
		foreach( $results as $result ){
			$result->date = gmdate( 'Y-m-d', strtotime( $result->date_time ) );
			$temp[] = $result;
		}
		$results = $temp;
	}
	return $results;
}
/**
 * Get patient bookings
 *
 * @return mixed
 */
function snks_get_patient_bookings( $user_id = false ) {
	global $wpdb;
	if ( ! $user_id ) {
		$user_id   = get_current_user_id();
	}
	$cache_key = 'patient-bookings-' . $user_id;
	$results   = wp_cache_get( $cache_key );
	if ( ! $results ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE client_id = %d
				AND session_status = %s
				ORDER BY ID DESC
				Limit 1",
				$user_id,
				'open'
			)
		);

		wp_cache_set( $cache_key, $results );
	}
	return $results;
}

/**
 * Get patient sessions
 *
 * @param string $tense past|future records.
 * @return mixed
 */
function snks_get_patient_sessions( $tense ) {
	global $wpdb;
	$user_id = get_current_user_id();

	$cache_key = 'patient-' . $tense . '-sessions-' . $user_id;
	$results   = wp_cache_get( $cache_key );
	$operator  = 'past' === $tense ? '<' : '>';
	if ( ! $results ) {
		$query = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = %d";
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( 'all' !== $tense ) {
			$query .= " AND date_time {$operator}= CURRENT_TIMESTAMP()";
		}
		$query .= " ORDER BY date_time ASC";
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$user_id
			)
		);
		wp_cache_set( $cache_key, $results );
	}
	return $results;
}

/**
 * Get formated date/time difference e.g. 2 days and 3 hours and 40 minutes and 25 seconds.
 *
 * @param  string $datetime       DateTime.
 * @param  string $time_zone      timezone you want to use for conversion.
 * @var    object $set_time_zone  an object of DateTimeZone.
 * @var    string $converted_date Store formated timestamp.
 * @var    object $date           object of formated date/time according to timezone.
 * @var    object $current_date   object of current date/time.
 * @var    string $diff           stors formated date/time difference.
 * @return string
 */
function snks_get_time_difference( $datetime, $time_zone ) {
	$set_time_zone = new DateTimeZone( $time_zone );

	$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime, $set_time_zone );

	$current_date = new DateTime();

	$diff = $date->diff( $current_date );

	$diff_seconds = $diff->s + $diff->i * 60 + $diff->h * 3600 + $diff->days * 86400;

	// Check if the difference is less than or equal to 5 minutes
	if ( $diff_seconds <= 5 * 60 ) {
		// Calculate the difference in seconds
		return $diff_seconds;
	} else {
		// Format the difference in the original format
		$formatted_diff = $diff->format( 'باقي %a يوم و %H ساعة و %i دقيقة و %s ثانية' );
		return $formatted_diff;
	}
}

/**
 * Check if a date is in the past.
 *
 * @param string $datetime Date time string.
 * @return bool
 */
function snks_is_past_date( $datetime ) {

	$date    = new DateTime( $datetime );
	$current = new DateTime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
	if ( $date > $current ) {
		return false; // date hasn't been passed.
	}

	return true; // date has been passed.
}
