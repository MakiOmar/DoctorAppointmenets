<?php
/**
 * Daily auto-publisher for doctor preview timetables.
 *
 * Automates the same action as the front-end "#insert-timetable" (نشر) button,
 * which publishes a doctor's curated `preview_timetable` meta into the real
 * `snks_provider_timetable` table via snks_sync_preview_timetables_to_db().
 *
 * Runs daily starting at 00:00 (site-local time). To keep each cron tick light,
 * doctors are processed in small batches spread across the minutes following
 * midnight instead of all at once.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Number of doctors published per cron tick (keeps each request cheap).
 */
const SNKS_PUBLISH_PREVIEW_BATCH_SIZE = 10;

/**
 * Fetch the IDs of doctors that currently have a `preview_timetable` meta row.
 *
 * Emptiness of the (filtered) preview is checked later, per doctor, so this
 * query only needs to narrow the candidate set to doctors who have ever saved
 * a preview. Computed once per day and cached in a transient by the caller.
 *
 * @return int[] Doctor user IDs.
 */
function snks_get_doctor_ids_with_preview_timetable() {
	$doctor_ids = get_users(
		array(
			'role'         => 'doctor',
			'fields'       => 'ID',
			'meta_key'     => 'preview_timetable', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		)
	);

	return array_map( 'absint', (array) $doctor_ids );
}

/**
 * Cron callback: publish preview timetables for a batch of doctors.
 *
 * Guards ensure the whole run happens once per day and that each doctor is
 * processed at most once per day, even though the event fires every minute.
 *
 * @return void
 */
function snks_publish_preview_timetables_cron() {
	// Only operate in the window that starts at midnight; the batches below
	// spread the work across the minutes following 00:00.
	$current_time = current_time( 'H:i:s' );
	if ( $current_time > '04:00:00' ) {
		return;
	}

	$today = current_time( 'Y-m-d' );

	// Whole-run guard: nothing left to do for today.
	if ( get_transient( 'snks_publish_preview_done_' . $today ) ) {
		return;
	}

	// Build (and cache) the ordered queue of candidate doctors once per day.
	$queue_key  = 'snks_publish_preview_queue_' . $today;
	$doctor_ids = get_transient( $queue_key );
	if ( false === $doctor_ids ) {
		$doctor_ids = snks_get_doctor_ids_with_preview_timetable();
		set_transient( $queue_key, $doctor_ids, DAY_IN_SECONDS );
	}

	if ( empty( $doctor_ids ) ) {
		set_transient( 'snks_publish_preview_done_' . $today, 1, DAY_IN_SECONDS );
		return;
	}

	$offset_key = 'snks_publish_preview_offset_' . $today;
	$offset     = (int) get_transient( $offset_key );
	$batch      = array_slice( $doctor_ids, $offset, SNKS_PUBLISH_PREVIEW_BATCH_SIZE );

	// Queue exhausted: mark today's run complete and clean up the offset.
	if ( empty( $batch ) ) {
		set_transient( 'snks_publish_preview_done_' . $today, 1, DAY_IN_SECONDS );
		delete_transient( $offset_key );
		return;
	}

	foreach ( $batch as $doctor_id ) {
		$doctor_id = absint( $doctor_id );
		if ( ! $doctor_id ) {
			continue;
		}

		// Per-doctor daily guard to avoid double-processing.
		$done_key = 'snks_publish_preview_' . $doctor_id . '_' . $today;
		if ( get_transient( $done_key ) ) {
			continue;
		}

		// Mirror the button: use the filtered preview. Skip doctors whose
		// preview is empty so we never mass-delete their existing waiting
		// slots by publishing an empty schedule.
		$preview = snks_get_preview_timetable( $doctor_id );
		if ( empty( $preview ) || ! is_array( $preview ) ) {
			set_transient( $done_key, 1, DAY_IN_SECONDS );
			continue;
		}

		try {
			$sync_result = snks_sync_preview_timetables_to_db( $doctor_id, $preview );

			if ( empty( $sync_result['success'] ) && ! empty( $sync_result['errors'] ) ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'snks_publish_preview_timetables_cron: sync completed with errors for doctor %d: %s',
						$doctor_id,
						wp_json_encode( $sync_result['errors'] )
					)
				);
			}
		} catch ( \Throwable $e ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'snks_publish_preview_timetables_cron: failed for doctor %d: %s',
					$doctor_id,
					$e->getMessage()
				)
			);
		}

		set_transient( $done_key, 1, DAY_IN_SECONDS );
	}

	// Advance the queue offset for the next tick.
	set_transient( $offset_key, $offset + SNKS_PUBLISH_PREVIEW_BATCH_SIZE, DAY_IN_SECONDS );
}

// Schedule the event (fires every minute; the callback self-gates to the
// post-midnight window and to once-per-day processing).
if ( ! wp_next_scheduled( 'snks_publish_preview_timetables_event' ) ) {
	wp_schedule_event( time(), 'every_minute', 'snks_publish_preview_timetables_event' );
}

add_action( 'snks_publish_preview_timetables_event', 'snks_publish_preview_timetables_cron' );
