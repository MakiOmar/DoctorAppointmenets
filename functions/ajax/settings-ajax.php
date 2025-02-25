<?php
/**
 * Ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action(
	'wp_ajax_expected_hours_output',
	function () {
		if ( ! snks_is_doctor() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Doctor only.' );
		}
		$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'expected_hours_output_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$periods        = array_map( 'absint', explode( '-', $_req['selectedPeriods'] ) );
		$hour           = gmdate( 'h:i a', strtotime( $_req['selectedHour'] . ':00' ) );
		$expected_hours = snks_expected_hours( $periods, $hour );
		$html           = '';
		$hours          = array();
		$to_hours       = array();

		if ( ! empty( $expected_hours ) ) {
			foreach ( $expected_hours as $expected_hour ) {
				$from_timestamp = strtotime( $expected_hour['from'] );
				$to_timestamp   = strtotime( $expected_hour['to'] );

				// Adjust `to` timestamp if it's logically the next day.
				if ( $to_timestamp <= $from_timestamp ) {
					$to_timestamp += 86400; // Add 24 hours in seconds to `to`.
				}

				$expected_hour_from = gmdate( 'H:i', $from_timestamp );
				$expected_hour_to   = gmdate( 'H:i', $to_timestamp );

				$hours[]    = $expected_hour_from;
				$hours[]    = $expected_hour_to;
				$to_hours[] = $expected_hour_to;

				$html .= sprintf(
					'<p class="expected-hour-text">من <span class="%1$s">%2$s</span> إلى <span class="%3$s">%4$s</span></p>',
					str_replace( array( ' ', ':' ), '-', $expected_hour_from ),
					esc_html( $expected_hour['from'] ),
					str_replace( array( ' ', ':' ), '-', $expected_hour_to ),
					esc_html( $expected_hour['to'] )
				);
			}
		}

		// Remove duplicates and sort hours logically.
		$hours = array_values( array_unique( $hours ) );

		// Sort hours, treating "next-day" times (e.g., `00:00`) as larger than same-day times.
		usort(
			$hours,
			function ( $a, $b ) {
				$a_timestamp = strtotime( $a );
				$b_timestamp = strtotime( $b );

				// Treat `00:00` as the next day's time if it's earlier than other times.
				if ( $a_timestamp < strtotime( '12:00' ) ) {
					$a_timestamp += 86400; // Add 24 hours in seconds.
				}
				if ( $b_timestamp < strtotime( '12:00' ) ) {
					$b_timestamp += 86400; // Add 24 hours in seconds.
				}

				return $a_timestamp - $b_timestamp;
			}
		);
		wp_send_json(
			array(
				'resp'        => $html,
				'hours'       => $hours,
				'largestHour' => end( $hours ),
				'lowesttHour' => $hours[0],
				'limits'      => array( $hours[0], end( $hours ) ),
				'tos'         => $to_hours,
				'periods'     => $periods,
			)
		);

		die();
	}
);


add_action(
	'wp_ajax_delete_slot',
	function () {
		if ( ! snks_is_doctor() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Doctor only.' );
		}
		$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'delete_slot_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$preview_timetable = snks_get_preview_timetable();

		$solt   = $preview_timetable[ $_req['slotDay'] ][ $_req['slotIndex'] ];
		$exists = snks_timetable_exists( snks_get_settings_doctor_id(), gmdate( 'Y-m-d H:i:s', strtotime( $solt['date_time'] ) ), $solt['day'], $solt['starts'], $solt['ends'], $solt['attendance_type'] );
		unset( $preview_timetable[ $_req['slotDay'] ][ $_req['slotIndex'] ] );
		$update  = update_user_meta( snks_get_settings_doctor_id(), 'preview_timetable', $preview_timetable );
		$deleted = array();
		if ( ! empty( $exists ) ) {
			foreach ( $exists as $record ) {
				snks_delete_timetable( $record->ID );
				$deleted[] = $record->ID;
			}
		}
		wp_send_json(
			array(
				'resp'    => $update,
				'deleted' => implode( '-', $deleted ),
			),
		);

		die();
	}
);

add_action(
	'wp_ajax_insert_timetable',
	function () {
		if ( ! snks_is_doctor() && ! snks_is_clinic_manager() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Doctor only.' );
		}
		$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'insert_timetable_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$user_id            = snks_get_settings_doctor_id();
		$preview_timetables = snks_get_preview_timetable();
		$errors             = array();
		snks_delete_waiting_sessions_by_user_id( $user_id );
		if ( $preview_timetables && ! empty( $preview_timetables ) ) {
			foreach ( $preview_timetables as $day_preview_timetable ) {
				foreach ( $day_preview_timetable as $data ) {
					$dtime = gmdate( 'Y-m-d H:i:s', strtotime( $data['date_time'] ) );

					$ordered = snks_timetable_with_order_exists( $dtime, $data['user_id'] );
					if ( $ordered ) {
						continue;
					}
					$exists            = snks_timetable_exists( $user_id, $dtime, $data['day'], $data['starts'], $data['ends'] );
					$data['date_time'] = $dtime;
					unset( $data['date'] );

					if ( empty( $exists ) ) {
						snks_insert_timetable( $data );
					} else {
						foreach ( $exists as $timetable ) {
							//phpcs:disable
							if (  ( ! in_array( $timetable->session_status, array( 'open' ), true ) ) && $data['attendance_type'] != $timetable->session_status ) {
								//phpcs:enable
								snks_insert_timetable( $data );
								break;
							}
						}
					}
				}
			}
		}
		wp_send_json(
			array(
				'resp'   => true,
				'errors' => $errors,
			),
		);

		die();
	}
);
add_action(
	'wp_ajax_get_preview_tables',
	function () {
		if ( ! snks_is_doctor() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Doctor only.' );
		}
		//phpcs:disable
		echo snks_generate_preview();
		//phpcs:enable
		die();
	}
);

/**
 * Handles AJAX request to check for open sessions by UUID.
 *
 * @return void
 */
function snks_check_uuid_open_session() {
	check_ajax_referer( 'snks_nonce', 'security' );

	// Ensure UUID is provided.
	if ( empty( $_POST['baseHour'] || $_POST['baseHourId'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request parameters.', 'textdomain' ) ) );
	}
	$_req = $_POST;
	// Sanitize input.
	$base_hour    = sanitize_text_field( $_req['baseHour'] );
	$base_hour    = $base_hour . ':00';
	$base_hour_id = sanitize_text_field( $_req['baseHourId'] );
	$base_hour_id = explode( '_', $base_hour_id );
	$day          = ucfirst( $base_hour_id[0] );

	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_provider_timetable';
	//phpcs:disable
	// If no open session exists, delete records where session_status is "waiting".
	$delete_query = $wpdb->prepare(
		"DELETE FROM $table_name WHERE day = %s AND base_hour = %s AND session_status = %s",
		$day,
		$base_hour,
		'waiting'
	);

	$wpdb->query( $delete_query );

	wp_send_json_success();
}

add_action( 'wp_ajax_check_open_session', 'snks_check_uuid_open_session' );
