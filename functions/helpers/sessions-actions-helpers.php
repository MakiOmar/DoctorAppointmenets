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
