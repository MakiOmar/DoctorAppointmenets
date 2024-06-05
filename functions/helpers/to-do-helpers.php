<?php
/**
 * To DO Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump, WordPress.DB.DirectDatabaseQuery.DirectQuery


/**
 * Get records by user ID, case Id and plan day.
 *
 * @param int    $user_id User ID.
 * @param int    $case_id Case ID.
 * @param string $date Plan date.
 * @return mixed
 */
function snks_get_records_by_user_case_date( $user_id, $case_id, $date ) {
	global $wpdb;
	// Prepare the query parameters.
	$user_id = intval( $user_id );
	$case_id = intval( $case_id );
	$date    = sanitize_text_field( $date );

	// Generate a unique cache key.
	$cache_key = 'snks_to_do_' . $user_id . '_' . $case_id . '_' . $date;

	$results = wp_cache_get( $cache_key );

	if ( false === $results ) {

		// Execute the query.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
            FROM {$wpdb->prefix}case_to_do
            WHERE user_id = %d AND case_id = %d AND DATE(plan_day) = %s",
				$user_id,
				$case_id,
				$date
			)
		);
		wp_cache_set( $cache_key, $results, '', 3600 );
	}

	return $results;
}

/**
 * Insert to do
 *
 * @param int    $user_id User ID.
 * @param int    $case_id Case ID.
 * @param string $date Plan date.
 * @param string $to_dos An array of hour=>plan.
 * @return mixed Record ID otherwise false.
 */
function snks_insert_to_do( $user_id, $case_id, $date, $to_dos ) {
	$date_exists = snks_get_records_by_user_case_date( $user_id, $case_id, $date );

	if ( $date_exists ) {
		return;
	}
	global $wpdb;
	$table_name = $wpdb->prefix . TO_DO_TABLE_NAME;

	// Prepare the data for insertion.
	$data = array(
		'case_id'  => intval( $case_id ),
		'user_id'  => intval( $user_id ),
		'plan_day' => sanitize_text_field( $date ),
		'to_dos'   => wp_json_encode( $to_dos ),
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
 * Update to do
 *
 * @param int    $user_id User ID.
 * @param int    $case_id Case ID.
 * @param string $date Plan date.
 * @param string $to_dos An array of hour=>plan.
 * @return mixed Record ID otherwise false.
 */
function snks_update_to_do( $user_id, $case_id, $date, $to_dos ) {
	$date_exists = snks_get_records_by_user_case_date( $user_id, $case_id, $date );

	if ( ! $date_exists ) {
		return;
	}
	global $wpdb;
	$table_name = $wpdb->prefix . TO_DO_TABLE_NAME;
	// Prepare the data for update.
	$data = array( 'to_dos' => wp_json_encode( $to_dos ) );

	$where = array(
		'user_id'  => intval( $user_id ),
		'case_id'  => intval( $case_id ),
		'plan_day' => sanitize_text_field( $date ),
	);
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->update( $table_name, $data, $where );
	//phpcs:enable.
	return $result;
}

/**
 * Generate to do days.
 *
 * @return array
 */
function snks_get_to_do_days() {
	$enrolment_date = get_user_meta( get_current_user_id(), 'programme_enrolment_date', true );
	if ( ! $enrolment_date || empty( $enrolment_date ) ) {
		return;
	}
	$start_date = $enrolment_date;
	$end_date   = gmdate( 'Y-m-d', strtotime( '+6 days' ) );
	// Create DateTime objects for start and end dates.
	$start = new DateTime( $start_date );
	$end   = new DateTime( $end_date );
	// Array to store the dates.
	$dates = array();
	// Generate the array of dates.
	while ( $start <= $end ) {
		$dates[] = $start->format( 'Y-m-d' );
		$start->modify( '+1 day' );
	}
	return array_map( 'array_reverse', array_reverse( array_chunk( array_reverse( $dates ), 7 ) ) );
}
