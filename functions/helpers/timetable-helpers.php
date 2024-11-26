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
 * Get user timetable.
 *
 * @param int    $user_id         User ID.
 * @param string $booking_day     Booking day date.
 * @param string $start_time      Start time.
 * @param string $attendance_type Attendance type.
 * @return object|null The timetable row or null if not found.
 */
function snks_get_timetable( $user_id = false, $booking_day = '', $start_time = '', $attendance_type = '' ) {
	global $wpdb;

	// Sanitize inputs.
	$user_id         = $user_id ? intval( $user_id ) : null;
	$booking_day     = sanitize_text_field( $booking_day );
	$start_time      = sanitize_text_field( $start_time );
	$attendance_type = sanitize_text_field( $attendance_type );

	// Generate a unique cache key.
	$cache_key = 'snks_timetable_';
	if ( $user_id ) {
		$cache_key .= $user_id;
	} else {
		$cache_key .= 'all';
	}
	$cache_key .= '_' . $booking_day . '_' . $start_time;
	if ( $attendance_type ) {
		$cache_key .= '_' . $attendance_type;
	} else {
		$cache_key .= '_all';
	}

	// Attempt to retrieve cached result.
	$results = wp_cache_get( $cache_key );
	if ( false === $results ) {
		// Build the base query.
		$query  = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE start_time = %s AND booking_day = %s";
		$params = array( $start_time, $booking_day );

		// Add user_id conditionally.
		if ( $user_id ) {
			$query   .= ' AND user_id = %d';
			$params[] = $user_id;
		}

		// Add attendance_type conditionally.
		if ( ! empty( $attendance_type ) ) {
			$query   .= ' AND attendance_type = %s';
			$params[] = $attendance_type;
		}
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_row( $wpdb->prepare( $query, ...$params ) );
		//phpcs:enable

		// Cache the results.
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
 * Check if a user is eligible to the timetable
 *
 * @param mixed $timetable_id Timetable's ID or An object of timetable can be supplied.
 * @return bool
 */
function snks_is_timetable_eligible( $timetable_id ) {
	$current_user = get_current_user_id();
	if ( is_numeric( $timetable_id ) ) {
		$timetable = snks_get_timetable_by( 'ID', $timetable_id );
	} else {
		$timetable = $timetable_id;
	}
	if ( ! $timetable || ! in_array( $current_user, array( absint( $timetable->user_id ), absint( $timetable->client_id ) ), true ) ) {
		return false;
	}
	return true;
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
 * Get timetable by date
 *
 * @param string $date Date to query.
 * @return mixed
 */
function snks_get_timetable_by_date( $date ) {
	global $wpdb;
	// Get the current user ID.
	$current_user_id = get_current_user_id();

	// Generate a unique cache key.
	$cache_key = 'snks_timetable_by_date_' . $date . '_user_' . $current_user_id;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
				 WHERE DATE(date_time) = %s 
				 AND user_id = %d 
				 AND session_status = %s",
				$date,
				$current_user_id,
				'waiting'
			)
		);

		// Set the cache for the results.
		wp_cache_set( $cache_key, $results, '', 3600 );
		//phpcs:enable
	}

	return $results;
}


/**
 * Get closest timetable
 *
 * @param string $user_id User's ID.
 * @return mixed
 */
function snks_get_closest_timetable( $user_id ) {
	global $wpdb;
	// Generate a unique cache key.
	$cache_key = 'snks_get_closest_timetable_' . $user_id;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
				WHERE user_id = %s
				AND date_time > NOW()
				ORDER BY ABS(TIMESTAMPDIFF(SECOND, date_time, NOW()))
				LIMIT 1",
				$user_id
			)
		);
		//phpcs:enable
		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Check if a doctor has timetable
 *
 * @param string $user_id User's ID.
 * @return bool
 */
function snks_has_timetable( $user_id ) {
	global $wpdb;
	// Generate a unique cache key.
	$cache_key = 'snks_has_timetable_' . $user_id;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
				WHERE user_id = %s
				AND date_time > NOW()
				LIMIT 1",
				$user_id
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
 * @param string $attendance_type attendance type.
 * @return mixed
 */
function snks_timetable_exists( $user_id, $date_time, $day, $starts, $ends, $attendance_type = '' ) {
	global $wpdb;
	//phpcs:disable
	// Base query.
	$_query = "SELECT *
              FROM {$wpdb->prefix}snks_provider_timetable
              WHERE user_id = %d
              AND date_time = %s
              AND day = %s
              AND starts = %s
              AND ends = %s";

	// Add attendance_type condition if not empty.
	if ( ! empty( $attendance_type ) ) {
		$_query         .= ' AND attendance_type = %s';
		$prepared_query = $wpdb->prepare( $_query, $user_id, $date_time, $day, $starts, $ends, $attendance_type );
	} else {
		$prepared_query = $wpdb->prepare( $_query, $user_id, $date_time, $day, $starts, $ends );
	}
	// Execute the query.
	$results = $wpdb->get_results( $prepared_query );
	//phpcs:enable

	return $results;
}

/**
 * Checks if a timetable record exists for a user with the given date_time and session_status = 'open'.
 *
 * @param int    $user_id   User ID.
 * @param string $date_time Date and time.
 * @return bool True if a record exists, false otherwise.
 */
function snks_timetable_open_session_exists( $user_id, $date_time ) {
	global $wpdb;

	// Define the query to check if a record exists.
	$_query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}snks_provider_timetable
        WHERE user_id = %d
        AND date_time = %s
        AND session_status = %s
    ";
	//phpcs:disable
	// Prepare and execute the query.
	$record_count = $wpdb->get_var( $wpdb->prepare( $_query, $user_id, $date_time, 'open' ) );
	//phpcs:enable
	// Return true if any record exists, otherwise false.
	return ( $record_count > 0 );
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
 * Deletes all records in the wpds_snks_provider_timetable table for a given user ID
 * where session_status is not equal to 'waiting' and optionally filters by attendance_type.
 *
 * @param int          $user_id The ID of the user whose records should be deleted.
 * @param string|false $attendance_type The attendance type to filter by, or false for no filter.
 */
function snks_delete_waiting_sessions_by_user_id( $user_id, $attendance_type = false ) {
	global $wpdb;

	// Sanitize the user ID.
	$user_id = intval( $user_id );

	// Define the table name with the WordPress table prefix.
	$table_name = $wpdb->prefix . 'snks_provider_timetable';

	// Build the base SQL query.
	$sql = "DELETE FROM $table_name WHERE user_id = %d AND session_status = %s";

	// Add the attendance_type condition if provided.
	$params = array( $user_id, 'waiting' );
	if ( $attendance_type ) {
		$sql     .= ' AND attendance_type = %s';
		$params[] = $attendance_type;
	}
	//phpcs:disable
	// Execute the query with prepared statement.
	$wpdb->query( $wpdb->prepare( $sql, $params ) );
	//phpcs:enable
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
	//phpcs:disable
	// Fetch doctor settings.
	$doctor_settings = snks_doctor_settings( $user_id );

	// Calculate seconds before blocking.
	$seconds_before_block = 0;
	if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
		$number               = $doctor_settings['block_if_before_number'];
		$unit                 = $doctor_settings['block_if_before_unit'];
		$base                 = ( 'day' === $unit ) ? 24 : 1;
		$seconds_before_block = $number * $base * 3600;
	}

	// Calculate current and end datetime.
	$current_datetime = date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $seconds_before_block ) );
	$end_datetime     = date_i18n( 'Y-m-d H:i:s', strtotime( $_for, strtotime( $current_datetime ) ) );

	// Cache key for the query.
	$cache_key = 'bookable-dates-' . $current_datetime . '-' . $period;
	$results   = wp_cache_get( $cache_key );

	// Set the default order.
	$_order = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

	if ( ! $results ) {
		// Fetch off-days from doctor settings.
		$off_days = isset( $doctor_settings['off_days'] ) ? explode( ',', $doctor_settings['off_days'] ) : array();

		// Prepare the off-days for SQL query.
		$off_days_placeholder = '';
		if ( ! empty( $off_days ) ) {
			$off_days_placeholder = implode( ',', array_fill( 0, count( $off_days ), '%s' ) );
		}

		// Common query parameters.
		$query_params = array(
			$user_id,
			$period,
			$current_datetime,
			$end_datetime,
			'waiting',
			0,
		);

		// Build the SQL query with dynamic conditions.
		$attendance_condition = ( 'both' === $attendance_type ) ? '' : $wpdb->prepare( 'AND attendance_type = %s', $attendance_type );
		$off_days_condition   = ( ! empty( $off_days ) ) ? "AND DATE(date_time) NOT IN ({$off_days_placeholder}) " : '';

		$sql = "
			SELECT *
			FROM {$wpdb->prefix}snks_provider_timetable
			WHERE user_id = %d
			AND period = %d
			AND date_time BETWEEN %s AND %s
			AND session_status = %s
			AND order_id = %d
			$attendance_condition
			$off_days_condition
			ORDER BY date_time {$_order}
		";

		// Merge off-days into query params.
		$query_params = array_merge( $query_params, $off_days );

		// Prepare and execute the query.
		$_query  = $wpdb->prepare( $sql, $query_params );
		$results = $wpdb->get_results( $_query );

		// Cache the results.
		wp_cache_set( $cache_key, $results );
		//phpcs:enable
	}

	return $results;
}

/**
 * Get all bookable dates
 *
 * @param int    $user_id User's ID.
 * @param string $_for Period to get dates for.
 * @param string $attendance_type Attendance type.
 * @return mixed
 */
function get_all_bookable_dates( $user_id, $_for = '+1 month', $attendance_type = 'both' ) {
	global $wpdb;
	$doctor_settings      = snks_doctor_settings( $user_id );
	$seconds_before_block = 0;
	if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
		$number               = $doctor_settings['block_if_before_number'];
		$unit                 = $doctor_settings['block_if_before_unit'];
		$base                 = 'day' === $unit ? 24 : 1;
		$seconds_before_block = $number * $base * 3600;
	}
	//phpcs:disable WordPress.DateTime.CurrentTimeTimestamp.Requested
	$current_datetime = date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $seconds_before_block ) );
	$end_datetime     = date_i18n( 'Y-m-d H:i:s', strtotime( $_for, strtotime( $current_datetime ) ) );
	$cache_key        = 'bookable-dates-' . $current_datetime;
	$results          = wp_cache_get( $cache_key );//phpcs:disable
	$_order    = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

	if ( ! $results ) {
		if ( 'both' === $attendance_type ) {
			$_query = $wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d
				AND date_time
				BETWEEN %s AND %s
				AND session_status = %s
				AND order_id = %d
				ORDER BY date_time {$_order}",
				$user_id,
				$current_datetime,
				$end_datetime,
				'waiting',
				0
			);
		} else {
			$_query = $wpdb->prepare(
				"SELECT *
				FROM {$wpdb->prefix}snks_provider_timetable
				WHERE user_id = %d
				AND date_time
				BETWEEN %s AND %s
				AND attendance_type = %s
				AND session_status = %s
				AND order_id = %d
				ORDER BY date_time {$_order}",
				$user_id,
				$current_datetime,
				$end_datetime,
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
 * @param string|null $attendance_type Attendance_type.
 * @return mixed
 */
function snks_user_appointments_by_date_period( $user_id, $date, $period, $attendance_type = null ) {
    global $wpdb;
    //$current_date = date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) + ( 2 * 3600 )  );
    $cache_key = 'dates-appointments-' . $user_id . '-' . $date . '-' . $period . '-' . $attendance_type;
    $results   = wp_cache_get( $cache_key ); // phpcs:disable
    $_order    = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';
    
    if ( ! $results ) {
        // Prepare base SQL query
        $sql = "SELECT *
                FROM {$wpdb->prefix}snks_provider_timetable
                WHERE user_id = %d
                AND period = %d
                AND DATE(date_time) = %s
                AND order_id = 0";

        // Add attendance_type condition if provided
        if ( $attendance_type !== null ) {
            $sql .= " AND attendance_type = %s";
        }

        // Add order by clause
        $sql .= " ORDER BY date_time {$_order}";

        // Prepare query with parameters
        $query_params = [
            $user_id,
            absint( $period ),
            $date,
        ];

        if ( $attendance_type !== null ) {
            $query_params[] = sanitize_text_field( $attendance_type );
        }

        $results = $wpdb->get_results( $wpdb->prepare( $sql, ...$query_params ) );

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
		$query  .= " ORDER BY date_time ASC";
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
 * @var    object $time_zone  an object of DateTimeZone.
 * @var    string $converted_date Store formated timestamp.
 * @var    object $date           object of formated date/time according to timezone.
 * @var    object $current_date   object of current date/time.
 * @var    string $diff           stors formated date/time difference.
 * @return string
 */
function snks_get_time_difference( $datetime, $time_zone ) {

	$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime, $time_zone );

	$current_date = current_datetime();

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

	$date    = new DateTime( $datetime, wp_timezone() );
	$current = current_datetime();
	if ( $date > $current ) {
		return false; // date hasn't been passed.
	}

	return true; // date has been passed.
}
