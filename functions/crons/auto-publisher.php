<?php
/**
 * Auto publisher
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Auto publish appointments for doctors in batches of 10 users per iteration.
 */
function snks_auto_publish_appointments_for_doctors() {
	$limit        = 10;
	$current_time = current_time( 'H:i:s' );
	// Only run between 00:00:00 and 06:00:00.
	if ( $current_time > '00:00:00' && $current_time < '06:00:00' ) {
			// Retrieve stored offset or initialize if not set.
		$offset = get_transient( 'snks_doctor_offset' );
		if ( false === $offset ) {
			$offset = 0;
		}

		$doctors = get_users(
			array(
				'role'   => 'doctor',
				'number' => $limit,
				'offset' => $offset,
				'fields' => 'ID', // Fetch only user IDs for efficiency.
			)
		);

		// If no more doctors, reset the offset to 0.
		if ( empty( $doctors ) ) {
			delete_transient( 'snks_doctor_offset' );
			return;
		}
		foreach ( $doctors as $doctor_id ) {
			$today         = gmdate( 'Y-m-d' );
			$last_run_key  = 'snks_last_run_' . $doctor_id;
			$last_run_date = get_transient( $last_run_key );

			if ( $last_run_date !== $today ) {
				snks_auto_publish_appointments( $doctor_id );
				set_transient( $last_run_key, $today, DAY_IN_SECONDS ); // Store for 24 hours.
			}
		}
		// Store the new offset for the next run.
		set_transient( 'snks_doctor_offset', $offset + $limit, HOUR_IN_SECONDS );
	}
}


// Schedule this function to run hourly between 00:00 and 01:00.
if ( ! wp_next_scheduled( 'snks_schedule_doctor_appointments' ) ) {
	wp_schedule_event( time(), 'every_minute', 'snks_schedule_doctor_appointments' );
}

// Hook the function to the scheduled event.
add_action( 'snks_schedule_doctor_appointments', 'snks_auto_publish_appointments_for_doctors' );
