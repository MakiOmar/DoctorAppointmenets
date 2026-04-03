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
	$results = false;
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

	$results = false;

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
	$current_user = snks_get_settings_doctor_id();
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

	$results = false;

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
 * @param string       $date Date to query.
 * @param string|false $period Period to filter, optional.
 * @return mixed
 */
function snks_get_timetable_by_date( $date, $period = false, $show_closed = true ) {
	global $wpdb;
	// Get the current user ID.
	$current_user_id = snks_get_settings_doctor_id();

	// Generate a unique cache key.
	$cache_key = 'snks_timetable_by_date_' . $date . '_user_' . $current_user_id . ( $period ? '_period_' . $period : '' );

	$results = false;
	//phpcs:disable
	if ( false === $results ) {
		// Start building the base SQL query with NOT IN for session_status.
		$sql = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		        WHERE DATE(date_time) = %s 
		        AND user_id = %d";
		if ( $show_closed ) {
			$sql .= " AND session_status NOT IN ( 'cancelled', 'completed', 'open', 'pending' )";
		} else {
			$sql .= " AND session_status NOT IN ( 'cancelled', 'completed', 'open', 'pending', 'closed' )";
		}
		// Add the period condition if provided.
		if ( $period ) {
			$sql .= ' AND period = %s';
		}

		// Prepare the SQL query based on whether $period is set.
		$query = $wpdb->prepare(
			$sql,
			$period ? array( $date, $current_user_id, $period ) : array( $date, $current_user_id )
		);

		// Execute the query.
		$results = $wpdb->get_results( $query );

		// Set the cache for the results.
		wp_cache_set( $cache_key, $results, '', 3600 );
	}
	//phpcs:enable
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
	$cache_key    = 'snks_get_closest_timetable_' . $user_id;
	$current_time = current_time( 'mysql' );
	$results      = false;

	if ( false === $results ) {
		//phpcs:disable
		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
				WHERE user_id = %s
				AND date_time > %s
				ORDER BY ABS(TIMESTAMPDIFF(SECOND, date_time, %s))
				LIMIT 1",
				$user_id,
				$current_time,
				$current_time
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

	$results = false;

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
 * @param object $booked_session Booked timetable.
 * @return void
 */
function snks_close_others( $booked_session ) {
	//phpcs:disable
	global $wpdb;

	// Table name.
	$table_name = $wpdb->prefix . 'snks_provider_timetable';

	// Extract session details.
	$date_time = $booked_session->date_time; // Full datetime (e.g., '2024-12-23 02:15:00').
	$base_hour = $booked_session->base_hour; // Base hour (e.g., '2:15:00').
	$starts    = $booked_session->starts;       // Start time (e.g., '2:15:00').
	$ends      = $booked_session->ends;           // End time (e.g., '2:45:00').
	$period    = $booked_session->period;       // Period (e.g., 30, 45, 60).

	// Close sessions based on the booking rules.
	if ( $period == 30 ) {
		// Handle 30-minute booking.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name
                 SET session_status = 'closed'
                 WHERE DATE(date_time) = DATE(%s)
				 AND order_id = 0
                   AND base_hour = %s
                   AND NOT (
                     (starts = %s OR ends = %s) AND period = 30
                   )
                   AND session_status = 'waiting'",
				$date_time,
				$base_hour,
				$ends,
				$starts
			)
		);

		// Log closing other slots for 30-minute booking.
		if ( function_exists( 'teamlog' ) ) {
			teamlog(
				array(
					'context'       => 'timetable_status_debug',
					'event'         => 'close_others',
					'source'        => 'snks_close_others',
					'period'        => (int) $period,
					'user_id'       => isset( $booked_session->user_id ) ? $booked_session->user_id : null,
					'booked_id'     => isset( $booked_session->ID ) ? $booked_session->ID : null,
					'date_time'     => $date_time,
					'base_hour'     => $base_hour,
					'starts'        => $starts,
					'ends'          => $ends,
					'rows_affected' => $wpdb->rows_affected,
					'mode'          => '30_minutes',
				)
			);
		}
	} else {
		// Handle 45 or 60-minute booking.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name
                 SET session_status = 'closed'
                 WHERE DATE(date_time) = DATE(%s)
				 AND order_id = 0
                 AND base_hour = %s
                 AND session_status = 'waiting'",
				$date_time,
				$base_hour
			)
		);

		// Log closing other slots for 45/60-minute booking.
		if ( function_exists( 'teamlog' ) ) {
			teamlog(
				array(
					'context'       => 'timetable_status_debug',
					'event'         => 'close_others',
					'source'        => 'snks_close_others',
					'period'        => (int) $period,
					'user_id'       => isset( $booked_session->user_id ) ? $booked_session->user_id : null,
					'booked_id'     => isset( $booked_session->ID ) ? $booked_session->ID : null,
					'date_time'     => $date_time,
					'base_hour'     => $base_hour,
					'starts'        => $starts,
					'ends'          => $ends,
					'rows_affected' => $wpdb->rows_affected,
					'mode'          => '45_or_60_minutes',
				)
			);
		}
	}
	//phpcs:enable
}


/**
 * Will set other timetables to be waiting for the same date and base_hour.
 *
 * @param object $booked_timetable Booked timetable.
 * @return void
 */
function snks_waiting_others( $booked_timetable ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_provider_timetable';

    // phpcs:disable.
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table_name
             SET session_status = 'waiting'
             WHERE DATE(date_time) = DATE(%s)
               AND base_hour = %s
               AND order_id = 0
			   AND session_status != 'open'",
            $booked_timetable->date_time,
            $booked_timetable->base_hour
        )
    );

	// Log resetting other slots back to waiting for debugging timetable status issues.
	if ( function_exists( 'teamlog' ) ) {
		teamlog(
			array(
				'context'       => 'timetable_status_debug',
				'event'         => 'waiting_others',
				'source'        => 'snks_waiting_others',
				'user_id'       => isset( $booked_timetable->user_id ) ? $booked_timetable->user_id : null,
				'booked_id'     => isset( $booked_timetable->ID ) ? $booked_timetable->ID : null,
				'date_time'     => $booked_timetable->date_time,
				'base_hour'     => $booked_timetable->base_hour,
				'rows_affected' => $wpdb->rows_affected,
			)
		);
	}
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
 * Check if Timetable row exists with the same date_time and order_id > 0
 *
 * @param string $date_time Date time.
 * @param int    $user_id User id.
 * @return bool
 */
function snks_timetable_with_order_exists( $date_time, $user_id ) {
	global $wpdb;
	// phpcs:disable
	// Base query with user_id condition.
	$_query = "SELECT ID
              FROM {$wpdb->prefix}snks_provider_timetable
              WHERE date_time = %s
              AND order_id > 0
              AND user_id = %d";

	// Prepare the query.
	$prepared_query = $wpdb->prepare( $_query, $date_time, $user_id );

	// Execute the query.
	$exists = $wpdb->get_var( $prepared_query );
	// phpcs:enable

	return $exists;
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

	$results = false;

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
 * @param array     $data data to insert.
 * @param int|false $user_id User's ID.
 * @return mixed
 */
function snks_insert_timetable( $data, $user_id = false ) {
    if ( ! $user_id ) {
        $user_id = snks_get_settings_doctor_id();
    }

    // Convert date_time from 12-hour format to 24-hour format.
    if ( isset( $data['date_time'] ) ) {
        $data['date_time'] = date( 'Y-m-d H:i:s', strtotime( $data['date_time'] ) );
    }

    $exists = snks_timetable_exists( $user_id, $data['date_time'], $data['day'], $data['starts'], $data['ends'], $data['attendance_type'] );
    if ( ! empty( $exists ) ) {
        return false;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
    // Insert the data into the table.
    $wpdb->insert( $table_name, $data );

    // Check if the insertion was successful.
    if ( $wpdb->last_error ) {
        return false; // Return false if there was an error.
    } else {
		// Log closed slots on insert for debugging timetable status issues.
		if ( function_exists( 'teamlog' ) && isset( $data['session_status'] ) && 'closed' === $data['session_status'] ) {
			teamlog(
				array(
					'context'      => 'timetable_status_debug',
					'event'        => 'insert_closed_slot',
					'source'       => 'snks_insert_timetable',
					'insert_id'    => $wpdb->insert_id,
					'user_id'      => isset( $data['user_id'] ) ? $data['user_id'] : $user_id,
					'date_time'    => isset( $data['date_time'] ) ? $data['date_time'] : null,
					'day'          => isset( $data['day'] ) ? $data['day'] : null,
					'base_hour'    => isset( $data['base_hour'] ) ? $data['base_hour'] : null,
					'starts'       => isset( $data['starts'] ) ? $data['starts'] : null,
					'ends'         => isset( $data['ends'] ) ? $data['ends'] : null,
					'period'       => isset( $data['period'] ) ? $data['period'] : null,
					'attendance'   => isset( $data['attendance_type'] ) ? $data['attendance_type'] : null,
					'session_status' => $data['session_status'],
				)
			);
		}

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

	// Capture previous session data when we are about to change session_status.
	$previous_session = null;
	if ( isset( $data['session_status'] ) ) {
		$previous_session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, user_id, date_time, day, base_hour, starts, ends, period, attendance_type, session_status, order_id, client_id FROM {$table_name} WHERE ID = %d",
				$id
			)
		);
	}

	// Check if session_status is being changed to 'completed' for AI sessions
	$is_status_change_to_completed = isset( $data['session_status'] ) && $data['session_status'] === 'completed';
	
	// Get current session data to check if it's an AI session (before update)
	$current_session = null;
	if ( $is_status_change_to_completed ) {
		$current_session = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE ID = %d",
			$id
		) );
	}
	
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$updated = $wpdb->update(
		$table_name,
		$data,
		array(
			'ID' => $id,
		)
	);
	//phpcs:enable.

	// Log transitions to or from 'closed' for debugging timetable status issues.
	if ( $updated && isset( $data['session_status'] ) && function_exists( 'teamlog' ) ) {
		$new_session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, user_id, date_time, day, base_hour, starts, ends, period, attendance_type, session_status, order_id, client_id FROM {$table_name} WHERE ID = %d",
				$id
			)
		);

		$old_status = $previous_session ? $previous_session->session_status : null;
		$new_status = $data['session_status'];

		if ( 'closed' === $new_status || 'closed' === $old_status ) {
			teamlog(
				array(
					'context'        => 'timetable_status_debug',
					'event'          => 'update_session_status',
					'source'         => 'snks_update_timetable',
					'id'             => $id,
					'old_status'     => $old_status,
					'new_status'     => $new_status,
					'user_id'        => $new_session ? $new_session->user_id : ( $previous_session ? $previous_session->user_id : null ),
					'date_time'      => $new_session ? $new_session->date_time : ( $previous_session ? $previous_session->date_time : null ),
					'day'            => $new_session ? $new_session->day : ( $previous_session ? $previous_session->day : null ),
					'base_hour'      => $new_session ? $new_session->base_hour : ( $previous_session ? $previous_session->base_hour : null ),
					'starts'         => $new_session ? $new_session->starts : ( $previous_session ? $previous_session->starts : null ),
					'ends'           => $new_session ? $new_session->ends : ( $previous_session ? $previous_session->ends : null ),
					'period'         => $new_session ? $new_session->period : ( $previous_session ? $previous_session->period : null ),
					'attendance'     => $new_session ? $new_session->attendance_type : ( $previous_session ? $previous_session->attendance_type : null ),
					'order_id'       => $new_session ? $new_session->order_id : ( $previous_session ? $previous_session->order_id : null ),
					'client_id'      => $new_session ? $new_session->client_id : ( $previous_session ? $previous_session->client_id : null ),
				)
			);
		}
	}

	// After successful update, trigger earnings creation for AI sessions
	if ( $updated && $is_status_change_to_completed && $current_session ) {
		if ( $current_session->order_id > 0 && strpos( $current_session->settings, 'ai_booking' ) !== false ) {
			// Get updated session data
			$updated_session = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE ID = %d",
				$id
			) );
			
			if ( $updated_session && function_exists( 'snks_create_ai_earnings_from_timetable' ) ) {
				// Check if earnings transaction already exists for this specific session
				$existing_transaction = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}snks_booking_transactions 
					 WHERE ai_session_id = %d AND transaction_type = 'add'",
					$id
				) );
				
				// Also check by order_id AND session_id as secondary safeguard
				if ( ! $existing_transaction ) {
					$existing_transaction = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}snks_booking_transactions 
						 WHERE ai_order_id = %d AND ai_session_id = %d AND transaction_type = 'add'",
						$updated_session->order_id,
						$id
					) );
				}
				
				if ( ! $existing_transaction ) {
					// Try to find sessions_actions entry and use existing profit transfer function
					$actions_table = $wpdb->prefix . 'snks_sessions_actions';
					$session_action = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM {$actions_table} WHERE action_session_id = %d AND case_id = %d",
						$id,
						$updated_session->order_id
					) );
					
					if ( $session_action && function_exists( 'snks_execute_ai_profit_transfer' ) ) {
						snks_execute_ai_profit_transfer( $session_action->action_session_id );
					} else {
						// Fallback: create earnings directly from timetable data
						snks_create_ai_earnings_from_timetable( $updated_session );
					}
				}
			}
		}
	}
	
	return $updated;
}

/**
 * Delete timetable
 *
 * @param int $id ID.
 * @return bool
 */
function snks_delete_timetable( $id ) {
	global $wpdb;
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$table_name = $wpdb->prefix . 'snks_provider_timetable';

	// Check if the record exists and session_status is neither 'open' nor 'pending'.
	$record = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE ID = %d AND session_status NOT IN ('open', 'pending')",
			$id
		)
	);

	// Proceed with deletion if the record is found.
	if ( $record ) {
		$wpdb->delete(
			$table_name,
			array(
				'ID' => $id,
			),
			array( '%d' )
		);
        // phpcs:enable.
		if ( $wpdb->rows_affected > 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Build a stable timetable slot key for sync operations.
 *
 * @param array|object $row Timetable row.
 * @param bool         $include_attendance Whether to include attendance type.
 * @return string
 */
function snks_get_timetable_slot_sync_key( $row, $include_attendance = true ) {
	$row = (array) $row;

	$parts = array(
		isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0,
		isset( $row['date_time'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $row['date_time'] ) ) : '',
		isset( $row['day'] ) ? sanitize_text_field( $row['day'] ) : '',
		isset( $row['starts'] ) ? sanitize_text_field( $row['starts'] ) : '',
		isset( $row['ends'] ) ? sanitize_text_field( $row['ends'] ) : '',
	);

	if ( $include_attendance ) {
		$parts[] = isset( $row['attendance_type'] ) ? sanitize_text_field( $row['attendance_type'] ) : '';
	}

	return implode( '|', $parts );
}

/**
 * Build a stable timetable datetime key for sync decisions.
 *
 * @param array|object $row Timetable row.
 * @return string
 */
function snks_get_timetable_datetime_sync_key( $row ) {
	$row = (array) $row;

	return implode(
		'|',
		array(
			isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0,
			isset( $row['date_time'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $row['date_time'] ) ) : '',
		)
	);
}

/**
 * Create a compact snapshot for sync logging and response payloads.
 *
 * @param array|object $row Timetable row.
 * @return array
 */
function snks_get_timetable_sync_snapshot( $row ) {
	$row = (array) $row;

	return array(
		'id'              => isset( $row['ID'] ) ? absint( $row['ID'] ) : 0,
		'user_id'         => isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0,
		'date_time'       => isset( $row['date_time'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $row['date_time'] ) ) : '',
		'day'             => isset( $row['day'] ) ? sanitize_text_field( $row['day'] ) : '',
		'starts'          => isset( $row['starts'] ) ? sanitize_text_field( $row['starts'] ) : '',
		'ends'            => isset( $row['ends'] ) ? sanitize_text_field( $row['ends'] ) : '',
		'period'          => isset( $row['period'] ) ? absint( $row['period'] ) : 0,
		'base_hour'       => isset( $row['base_hour'] ) ? sanitize_text_field( $row['base_hour'] ) : '',
		'attendance_type' => isset( $row['attendance_type'] ) ? sanitize_text_field( $row['attendance_type'] ) : '',
		'session_status'  => isset( $row['session_status'] ) ? sanitize_text_field( $row['session_status'] ) : '',
		'order_id'        => isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0,
		'client_id'       => isset( $row['client_id'] ) ? absint( $row['client_id'] ) : 0,
	);
}

/**
 * Check whether a waiting row is safe to replace during preview sync.
 *
 * @param array|object $row Timetable row.
 * @return bool
 */
function snks_is_syncable_waiting_timetable_row( $row ) {
	$row = (array) $row;

	$session_status = isset( $row['session_status'] ) ? sanitize_text_field( $row['session_status'] ) : '';
	$order_id       = isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0;
	$client_id      = isset( $row['client_id'] ) ? absint( $row['client_id'] ) : 0;
	$settings       = isset( $row['settings'] ) ? (string) $row['settings'] : '';

	if ( 'waiting' !== $session_status ) {
		return false;
	}

	if ( $order_id > 0 || $client_id > 0 ) {
		return false;
	}

	if ( false !== strpos( $settings, 'ai_booking:in_cart' ) || false !== strpos( $settings, 'ai_booking:booked' ) ) {
		return false;
	}

	return true;
}

/**
 * Check whether a closed row can be restored during preview sync.
 *
 * @param array|object $row Timetable row.
 * @return bool
 */
function snks_is_reopenable_closed_timetable_row( $row ) {
	$row = (array) $row;

	$session_status = isset( $row['session_status'] ) ? sanitize_text_field( $row['session_status'] ) : '';
	$order_id       = isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0;
	$client_id      = isset( $row['client_id'] ) ? absint( $row['client_id'] ) : 0;
	$settings       = isset( $row['settings'] ) ? (string) $row['settings'] : '';

	if ( 'closed' !== $session_status ) {
		return false;
	}

	if ( $order_id > 0 || $client_id > 0 ) {
		return false;
	}

	if ( false !== strpos( $settings, 'ai_booking:in_cart' ) || false !== strpos( $settings, 'ai_booking:booked' ) ) {
		return false;
	}

	return true;
}

/**
 * Normalize one preview timetable row before syncing.
 *
 * @param array $data Preview row.
 * @param int   $user_id User ID.
 * @return array|WP_Error
 */
function snks_normalize_preview_timetable_row( $data, $user_id ) {
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_preview_row', 'Preview row must be an array.' );
	}

	$date_time = isset( $data['date_time'] ) ? strtotime( $data['date_time'] ) : false;
	if ( false === $date_time ) {
		return new WP_Error( 'invalid_preview_date', 'Preview row has an invalid date_time value.' );
	}

	$normalized = array(
		'user_id'         => absint( $user_id ),
		'session_status'  => ! empty( $data['session_status'] ) ? sanitize_text_field( $data['session_status'] ) : 'waiting',
		'day'             => isset( $data['day'] ) ? sanitize_text_field( $data['day'] ) : '',
		'base_hour'       => isset( $data['base_hour'] ) ? sanitize_text_field( $data['base_hour'] ) : '',
		'period'          => isset( $data['period'] ) ? absint( $data['period'] ) : 0,
		'date_time'       => gmdate( 'Y-m-d H:i:s', $date_time ),
		'starts'          => isset( $data['starts'] ) ? sanitize_text_field( $data['starts'] ) : '',
		'ends'            => isset( $data['ends'] ) ? sanitize_text_field( $data['ends'] ) : '',
		'clinic'          => isset( $data['clinic'] ) ? sanitize_text_field( $data['clinic'] ) : '',
		'attendance_type' => isset( $data['attendance_type'] ) ? sanitize_text_field( $data['attendance_type'] ) : '',
	);

	$required_fields = array( 'day', 'base_hour', 'period', 'starts', 'ends', 'attendance_type' );
	foreach ( $required_fields as $field ) {
		if ( empty( $normalized[ $field ] ) ) {
			return new WP_Error( 'missing_preview_field', sprintf( 'Preview row is missing %s.', $field ) );
		}
	}

	return $normalized;
}

/**
 * Fetch waiting rows that are safe for preview sync replacement.
 *
 * @param int $user_id User ID.
 * @return array
 */
function snks_get_syncable_waiting_timetables_by_user_id( $user_id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_provider_timetable';
	$user_id    = absint( $user_id );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE user_id = %d
			AND session_status = %s
			AND order_id = %d
			AND ( client_id = 0 OR client_id IS NULL )
			AND ( settings NOT LIKE %s OR settings = '' OR settings IS NULL )
			AND ( settings NOT LIKE %s OR settings = '' OR settings IS NULL )",
			$user_id,
			'waiting',
			0,
			'%ai_booking:in_cart%',
			'%ai_booking:booked%'
		)
	);
	// phpcs:enable
}

/**
 * Fetch user timetable rows for a sync date window.
 *
 * @param int         $user_id User ID.
 * @param string|bool $from Start datetime.
 * @param string|bool $to End datetime.
 * @return array
 */
function snks_get_user_timetables_for_sync_window( $user_id, $from = false, $to = false ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_provider_timetable';
	$user_id    = absint( $user_id );

	if ( $from && $to ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$table_name}
				WHERE user_id = %d
				AND date_time BETWEEN %s AND %s
				ORDER BY date_time ASC",
				$user_id,
				$from,
				$to
			)
		);
		// phpcs:enable
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE user_id = %d
			ORDER BY date_time ASC",
			$user_id
		)
	);
	// phpcs:enable
}

/**
 * Sync preview timetable rows into the database safely.
 *
 * @param int|false        $user_id User ID.
 * @param array|false|null $preview_timetables Preview timetable rows.
 * @return array
 */
function snks_sync_preview_timetables_to_db( $user_id = false, $preview_timetables = false ) {
	if ( ! $user_id ) {
		$user_id = snks_get_settings_doctor_id();
	}

	$user_id = absint( $user_id );

	$result = array(
		'success'   => true,
		'inserted'  => array(),
		'updated'   => array(),
		'deleted'   => array(),
		'skipped'   => array(),
		'preserved' => array(),
		'errors'    => array(),
		'summary'   => array(),
	);

	if ( ! $user_id ) {
		$result['errors'][] = array(
			'reason' => 'invalid_user',
		);
		$result['success']  = false;
		$result['summary']  = array(
			'inserted'  => 0,
			'updated'   => 0,
			'deleted'   => 0,
			'skipped'   => 0,
			'preserved' => 0,
			'errors'    => 1,
		);

		return $result;
	}

	if ( false === $preview_timetables ) {
		$preview_timetables = snks_get_preview_timetable( $user_id );
	}

	$desired_rows       = array();
	$date_times         = array();
	$validation_failed  = false;

	if ( is_array( $preview_timetables ) ) {
		foreach ( $preview_timetables as $day_preview_timetable ) {
			if ( ! is_array( $day_preview_timetable ) ) {
				continue;
			}

			foreach ( $day_preview_timetable as $data ) {
				$normalized = snks_normalize_preview_timetable_row( $data, $user_id );

				if ( is_wp_error( $normalized ) ) {
					$result['errors'][] = array(
						'reason' => $normalized->get_error_message(),
						'slot'   => is_array( $data ) ? $data : array(),
					);
					$validation_failed  = true;
					continue;
				}

				$key = snks_get_timetable_slot_sync_key( $normalized );
				if ( isset( $desired_rows[ $key ] ) ) {
					$result['skipped'][] = array(
						'reason' => 'duplicate_preview_slot',
						'slot'   => snks_get_timetable_sync_snapshot( $normalized ),
					);
					continue;
				}

				$desired_rows[ $key ] = $normalized;
				$date_times[]         = $normalized['date_time'];
			}
		}
	} elseif ( ! empty( $preview_timetables ) ) {
		$result['errors'][] = array(
			'reason' => 'invalid_preview_payload',
		);
		$validation_failed  = true;
	}

	if ( $validation_failed ) {
		$result['success'] = false;
		$result['summary'] = array(
			'inserted'  => 0,
			'updated'   => 0,
			'deleted'   => 0,
			'skipped'   => count( $result['skipped'] ),
			'preserved' => 0,
			'errors'    => count( $result['errors'] ),
		);

		return $result;
	}

	$existing_syncable_waiting_rows = snks_get_syncable_waiting_timetables_by_user_id( $user_id );
	$existing_syncable_by_key       = array();

	foreach ( $existing_syncable_waiting_rows as $existing_row ) {
		$existing_syncable_by_key[ snks_get_timetable_slot_sync_key( $existing_row ) ] = $existing_row;
	}

	$existing_window_rows   = array();
	$existing_window_by_key = array();
	$existing_full_by_key   = array();
	$open_datetime_keys     = array();

	if ( ! empty( $date_times ) ) {
		sort( $date_times );
		$existing_window_rows = snks_get_user_timetables_for_sync_window( $user_id, reset( $date_times ), end( $date_times ) );

		foreach ( $existing_window_rows as $existing_row ) {
			$time_key = snks_get_timetable_slot_sync_key( $existing_row, false );
			$full_key = snks_get_timetable_slot_sync_key( $existing_row );
			$datetime_key = snks_get_timetable_datetime_sync_key( $existing_row );
			if ( ! isset( $existing_window_by_key[ $time_key ] ) ) {
				$existing_window_by_key[ $time_key ] = array();
			}

			$existing_window_by_key[ $time_key ][] = $existing_row;

			if ( ! isset( $existing_full_by_key[ $full_key ] ) ) {
				$existing_full_by_key[ $full_key ] = array();
			}

			$existing_full_by_key[ $full_key ][] = $existing_row;

			if ( isset( $existing_row->session_status ) && 'open' === $existing_row->session_status ) {
				$open_datetime_keys[ $datetime_key ] = true;
			}
		}
	}

	$protected_time_keys = array();

	foreach ( $desired_rows as $desired_row ) {
		$time_key        = snks_get_timetable_slot_sync_key( $desired_row, false );
		$datetime_key    = snks_get_timetable_datetime_sync_key( $desired_row );
		$same_time_rows  = isset( $existing_window_by_key[ $time_key ] ) ? $existing_window_by_key[ $time_key ] : array();
		$has_open_at_time = isset( $open_datetime_keys[ $datetime_key ] );

		foreach ( $same_time_rows as $existing_row ) {
			if ( snks_is_reopenable_closed_timetable_row( $existing_row ) && ! $has_open_at_time ) {
				continue;
			}

			if ( ! snks_is_syncable_waiting_timetable_row( $existing_row ) ) {
				$protected_time_keys[ $time_key ] = true;
				break;
			}
		}
	}

	foreach ( $existing_syncable_waiting_rows as $existing_row ) {
		$full_key = snks_get_timetable_slot_sync_key( $existing_row );
		$time_key = snks_get_timetable_slot_sync_key( $existing_row, false );

		if ( isset( $protected_time_keys[ $time_key ] ) || ! isset( $desired_rows[ $full_key ] ) ) {
			$deleted = snks_delete_timetable( $existing_row->ID );

			if ( $deleted ) {
				$result['deleted'][] = array(
					'id'     => absint( $existing_row->ID ),
					'reason' => isset( $protected_time_keys[ $time_key ] ) ? 'protected_existing_slot' : 'not_in_preview',
					'slot'   => snks_get_timetable_sync_snapshot( $existing_row ),
				);
			} else {
				$result['errors'][] = array(
					'reason' => 'delete_failed',
					'slot'   => snks_get_timetable_sync_snapshot( $existing_row ),
				);
			}
		}
	}

	foreach ( $desired_rows as $full_key => $desired_row ) {
		$time_key      = snks_get_timetable_slot_sync_key( $desired_row, false );
		$datetime_key  = snks_get_timetable_datetime_sync_key( $desired_row );
		$has_open_at_time = isset( $open_datetime_keys[ $datetime_key ] );

		if ( isset( $protected_time_keys[ $time_key ] ) ) {
			$result['preserved'][] = array(
				'reason' => 'protected_existing_slot',
				'slot'   => snks_get_timetable_sync_snapshot( $desired_row ),
			);
			continue;
		}

		if ( isset( $existing_syncable_by_key[ $full_key ] ) ) {
			$result['preserved'][] = array(
				'id'     => absint( $existing_syncable_by_key[ $full_key ]->ID ),
				'reason' => 'already_waiting',
				'slot'   => snks_get_timetable_sync_snapshot( $desired_row ),
			);
			continue;
		}

		if ( isset( $existing_full_by_key[ $full_key ] ) ) {
			foreach ( $existing_full_by_key[ $full_key ] as $existing_row ) {
				if ( snks_is_reopenable_closed_timetable_row( $existing_row ) && ! $has_open_at_time ) {
					$updated = snks_update_timetable(
						$existing_row->ID,
						array(
							'session_status' => 'waiting',
							'client_id'      => 0,
							'order_id'       => 0,
						)
					);

					if ( $updated ) {
						$result['updated'][] = array(
							'id'     => absint( $existing_row->ID ),
							'reason' => 'reopened_closed_without_open_match',
							'slot'   => snks_get_timetable_sync_snapshot(
								array_merge(
									(array) $existing_row,
									array(
										'session_status' => 'waiting',
										'client_id'      => 0,
										'order_id'       => 0,
									)
								)
							),
						);
					} else {
						$result['errors'][] = array(
							'reason' => 'reopen_closed_failed',
							'slot'   => snks_get_timetable_sync_snapshot( $existing_row ),
						);
					}

					continue 2;
				}
			}
		}

		$inserted = snks_insert_timetable( $desired_row, $user_id );

		if ( $inserted ) {
			$result['inserted'][] = array(
				'id'   => absint( $inserted ),
				'slot' => snks_get_timetable_sync_snapshot( $desired_row ),
			);
		} else {
			$result['errors'][] = array(
				'reason' => 'insert_failed',
				'slot'   => snks_get_timetable_sync_snapshot( $desired_row ),
			);
		}
	}

	$result['success'] = empty( $result['errors'] );
	$result['summary'] = array(
		'inserted'  => count( $result['inserted'] ),
		'updated'   => count( $result['updated'] ),
		'deleted'   => count( $result['deleted'] ),
		'skipped'   => count( $result['skipped'] ),
		'preserved' => count( $result['preserved'] ),
		'errors'    => count( $result['errors'] ),
	);

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
	$sql = "DELETE FROM $table_name WHERE user_id = %d AND session_status = %s AND order_id = %d";

	// Add the attendance_type condition if provided.
	$params = array( $user_id, 'waiting', 0 );
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

    // Set the default order.
    $_order = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

    // Fetch off-days from doctor settings and merge with global excluded booking dates (e.g. holidays).
    $off_days = isset( $doctor_settings['off_days'] ) ? explode( ',', $doctor_settings['off_days'] ) : array();
    $off_days = array_map( 'trim', $off_days );
    $global_excluded = function_exists( 'snks_get_global_excluded_booking_dates' ) ? snks_get_global_excluded_booking_dates() : array();
    $off_days = array_values( array_unique( array_filter( array_merge( $off_days, $global_excluded ) ) ) );

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
        '%ai_booking%', // parameter for NOT LIKE
    );

    // Build the SQL query with dynamic conditions.
    $attendance_condition       = ( 'both' === $attendance_type ) ? '' : $wpdb->prepare( 'AND attendance_type = %s', $attendance_type );
    $off_days_condition         = ( ! empty( $off_days ) ) ? "AND DATE(date_time) NOT IN ({$off_days_placeholder}) " : '';
    $disabled_clinics_condition = '';
    $enabled_clinics_condition  = '';

    // Apply clinic conditions only if attendance type is NOT online.
    if ( $attendance_type !== 'online' ) {
        // Fetch disabled clinics.
        $disabled_clinics = snks_disabled_clinics( $user_id );
        if ( ! empty( $disabled_clinics ) ) {
            $disabled_clinics_placeholder = implode( ',', array_fill( 0, count( $disabled_clinics ), '%s' ) );
            $disabled_clinics_condition   = "AND clinic NOT IN ({$disabled_clinics_placeholder})";
        }

        // Fetch enabled clinics.
        $enabled_clinics = snks_enabled_clinics( $user_id );
        if ( ! empty( $enabled_clinics ) ) {
            $enabled_clinics_placeholder = implode( ',', array_fill( 0, count( $enabled_clinics ), '%s' ) );
            $enabled_clinics_condition   = "AND clinic IN ({$enabled_clinics_placeholder})";
        }
    }

    // Add NOT LIKE condition for settings to exclude ai_booking
    $sql = "
        SELECT *
        FROM {$wpdb->prefix}snks_provider_timetable timetable
        WHERE user_id = %d
        AND period = %d
        AND date_time BETWEEN %s AND %s
        AND session_status = %s
        AND order_id = %d
        AND settings NOT LIKE %s
        $attendance_condition
        $off_days_condition
        $disabled_clinics_condition
        $enabled_clinics_condition
        ORDER BY date_time {$_order}
    ";

    // Merge off-days, disabled clinics, and enabled clinics into query params.
    $query_params = array_merge( $query_params, $off_days );

    if ( $attendance_type !== 'online' ) {
        $query_params = array_merge( $query_params, $disabled_clinics, $enabled_clinics );
    }

    // Prepare and execute the query.
    $_query  = $wpdb->prepare( $sql, $query_params );
    $results = $wpdb->get_results( $_query );

    return $results;
}

/**
 * Get session action with provider timetable
 *
 * @param int $session_id  Timetable ID (mapped to action_session_id)
 * @return object|null
 */
function snks_get_session_action_with_timetable( int $session_id ): ?array {
	global $wpdb;

	$actions_table   = "{$wpdb->prefix}snks_sessions_actions";
	$timetable_table = "{$wpdb->prefix}snks_provider_timetable";

	return $wpdb->get_row(
		$wpdb->prepare(
			"
			SELECT
				a.*,
				t.*
			FROM {$actions_table} AS a
			LEFT JOIN {$timetable_table} AS t
				ON t.ID = a.action_session_id
			WHERE a.action_session_id = %d
			LIMIT 1
			",
			$session_id
		),
		ARRAY_A
	);
}


/**
 * Get all bookable dates.
 *
 * @param int    $user_id         User's ID.
 * @param string $_for            Period to get dates for. Default is '+1 month'.
 * @param string $attendance_type Attendance type. Default is 'both'.
 * @return mixed Results array or false on failure.
 */
function get_all_bookable_dates( $user_id, $_for = '+1 month', $attendance_type = 'both' ) {
	global $wpdb;

	// Fetch doctor settings for the user.
	$doctor_settings      = snks_doctor_settings( $user_id );
	$seconds_before_block = 0;

	if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
		$number               = (int) $doctor_settings['block_if_before_number'];
		$unit                 = $doctor_settings['block_if_before_unit'];
		$base                 = ( 'day' === $unit ) ? 24 : 1; // Convert unit to hours if 'day'.
		$seconds_before_block = $number * $base * 3600;
	}

	// Set the date range for the query.
	$current_datetime = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $seconds_before_block );
	$end_datetime     = date_i18n( 'Y-m-d H:i:s', strtotime( $_for, strtotime( $current_datetime ) ) );
	//phpcs:disable
	// Build a cache key to optimize repeated queries.
	$cache_key = sprintf(
		'bookable-dates-%d-%s-%s-%s',
		$user_id,
		md5( $current_datetime . $end_datetime ),
		$attendance_type,
		sanitize_text_field( $_GET['order'] ?? 'ASC' )
	);
	//phpcs:enable
	// Attempt to fetch results from cache.
	$results = false;

	if ( false === $results ) {
		// Build the base query.
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$status_condition = snks_is_doctor() ? "session_status IN ('waiting', 'closed')" : "session_status = 'waiting'";
		$query            = $wpdb->prepare(
			"SELECT *
			FROM {$wpdb->prefix}snks_provider_timetable
			WHERE user_id = %d
			AND {$status_condition}
			AND order_id = %d",
			$user_id,
			0
		);

		// Conditionally add date range only if the user is a patient.
		if ( snks_is_patient() ) {
			// Add date range condition for patients.
			$query .= $wpdb->prepare(
				' AND date_time BETWEEN %s AND %s',
				$current_datetime,
				$end_datetime
			);
		} else {
			// Add condition for non-patients.
			$query .= $wpdb->prepare(
				' AND date_time > %s',
				current_time( 'mysql' ) // Corrected typo.
			);
		}

		// Add attendance type condition if applicable.
		if ( 'both' !== $attendance_type ) {
			$query .= $wpdb->prepare( ' AND attendance_type = %s', $attendance_type );
		}
		//phpcs:disable
		// Append ordering.
		$order  = in_array($_GET['order'] ?? 'ASC', ['ASC', 'DESC'], true) ? ($_GET['order'] ?? 'ASC') : 'ASC';
		$query .= " ORDER BY date_time {$order}";
		// Execute the query and cache the results.
		$results = $wpdb->get_results( $query );
		//phpcs:enable
		wp_cache_set( $cache_key, $results, '', HOUR_IN_SECONDS );
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
	$current_date = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 2 * 3600 ) );
	$cache_key    = 'bookable-date-times-' . $date;
	$results      = false;

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
	$user_id         = snks_get_settings_doctor_id();
	$operator        = 'past' === $tense ? '<' : '>';
	$order           = 'past' === $tense ? 'DESC' : 'ASC';
	$compare_against = gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );

	// Include 'open' sessions and 'completed' AI sessions
	$query = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = %d";
	
	// Add status condition
	if ( $status === 'open' ) {
		// Include 'open' sessions OR 'completed' sessions (both AI and regular)
		$query .= " AND (session_status = 'open' OR session_status = 'completed')";
	} else {
		$query .= " AND session_status = %s";
	}
	
	//phpcs:disable
	if ( 'all' !== $tense ) {
		$query .= $wpdb->prepare( " AND date_time {$operator} %s", $compare_against );
	}
	if ( $ordered ) {
		$query .= ' AND order_id != 0';
	}
	$query  .= " ORDER BY date_time {$order}";

	// Prepare query with appropriate parameters
	if ( $status === 'open' ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$user_id
			)
		);
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$user_id,
				$status
			)
		);
	}
	//phpcs:enable
	
	// Filter out AI sessions for patients
	if ( $results && is_array( $results ) && ! snks_is_doctor() ) {
		$filtered_results = array();
		foreach ( $results as $result ) {
			// Check if this session is an AI session
			if ( ! snks_is_ai_session( $result->ID ) ) {
				$result->date = gmdate( 'Y-m-d', strtotime( $result->date_time ) );
				$filtered_results[] = $result;
			}
		}
		$results = $filtered_results;
	} else {
		$temp = array();
		if ( $results && is_array( $results ) ) {
			foreach ( $results as $result ) {
				$result->date = gmdate( 'Y-m-d', strtotime( $result->date_time ) );
				$temp[]       = $result;
			}
			$results = $temp;
		}
	}
	
	return $results;
}

/**
 * Get patient bookings
 *
 * @param int|false $user_id User's ID.
 * @return mixed
 */
function snks_get_patient_bookings( $user_id = false ) {
	global $wpdb;
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$cache_key = 'patient-bookings-' . $user_id;
	$results   = false;
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

	$operator        = 'past' === $tense ? '<=' : '>';
	$compare_against = gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );
	$query           = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = %d AND session_status IN ('postponed', 'open')";
	//phpcs:disable
	if ( 'all' !== $tense ) {
		$query .= " AND date_time {$operator} '{$compare_against}'";
	}
	$query  .= ' ORDER BY date_time ASC';

	$results = $wpdb->get_results(
		$wpdb->prepare(
			$query,
			$user_id
		)
	);
	//phpcs:enable
	
	// Filter out AI sessions for patients
	if ( $results && is_array( $results ) ) {
		$filtered_results = array();
		foreach ( $results as $result ) {
			// Check if this session is an AI session
			if ( ! snks_is_ai_session( $result->ID ) ) {
				$filtered_results[] = $result;
			}
		}
		$results = $filtered_results;
	}
	
	return $results;
}


/**
 * Get formated date/time difference e.g. 2 days and 3 hours and 40 minutes and 25 seconds.
 *
 * @param  string $datetime       DateTime.
 * @param  object $time_zone  an object of DateTimeZone.
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

	// Check if the difference is less than or equal to 5 minutes.
	if ( $diff_seconds <= 5 * 60 ) {
		// Calculate the difference in seconds.
		return $diff_seconds;
	} else {
		// Format the difference in the original format.
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
