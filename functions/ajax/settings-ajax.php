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
		$base_date = '1970-01-01';

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

		$html       = '';
		$hours      = array();
		$to_hours   = array();
		$hours_info = array(); // لمعرفة اليوم الخاص بكل ساعة (0 = نفس اليوم, 1 = اليوم التالي)

		if ( ! empty( $expected_hours ) ) {
			foreach ( $expected_hours as $expected_hour ) {
				$from_timestamp = strtotime( $base_date . ' ' . $expected_hour['from'] );
				$to_timestamp   = strtotime( $base_date . ' ' . $expected_hour['to'] );

				// Adjust `to` if it is logically the next day.
				$to_cross_day = false;
				if ( $to_timestamp <= $from_timestamp ) {
					$to_timestamp += 86400; // Add 24 hours (next day)
					$to_cross_day  = true;
				}

				$expected_hour_from = gmdate( 'H:i', $from_timestamp );
				$expected_hour_to   = gmdate( 'H:i', $to_timestamp );

				$hours[]    = $expected_hour_from;
				$hours[]    = $expected_hour_to;
				$to_hours[] = $expected_hour_to;

				// سجل اليوم الافتراضي لكل ساعة
				$hours_info[ $expected_hour_from ] = 0; // from دائماً نفس اليوم
				$hours_info[ $expected_hour_to ]   = $to_cross_day ? 1 : 0; // to قد يكون في اليوم التالي

				$html .= sprintf(
					'<p class="expected-hour-text">من <span class="%1$s">%2$s</span> إلى <span class="%3$s">%4$s</span></p>',
					str_replace( array( ' ', ':' ), '-', $expected_hour_from ),
					esc_html( $expected_hour['from'] ),
					str_replace( array( ' ', ':' ), '-', $expected_hour_to ),
					esc_html( $expected_hour['to'] )
				);
			}
		}

		// Remove duplicates and sort logically
		$hours = array_values( array_unique( $hours ) );

		usort(
			$hours,
			function ( $a, $b ) use ( $base_date, $hours_info ) {
				$a_day_offset = isset( $hours_info[ $a ] ) ? $hours_info[ $a ] : 0;
				$b_day_offset = isset( $hours_info[ $b ] ) ? $hours_info[ $b ] : 0;

				$a_timestamp = strtotime( $base_date . ' ' . $a ) + ( $a_day_offset * 86400 );
				$b_timestamp = strtotime( $base_date . ' ' . $b ) + ( $b_day_offset * 86400 );

				return $a_timestamp - $b_timestamp;
			}
		);

		// تحديد الـ lowest و largest بناءً على منطق from/to وليس بعد usort
		$first_from = isset( $expected_hours[0]['from'] ) ? gmdate( 'H:i', strtotime( $base_date . ' ' . $expected_hours[0]['from'] ) ) : '';
		$first_to   = isset( $expected_hours[0]['to'] ) ? gmdate( 'H:i', strtotime( $base_date . ' ' . $expected_hours[0]['to'] ) ) : '';

		// تعديل to لو كان عبر يوم جديد
		if ( isset( $expected_hours[0] ) && strtotime( $base_date . ' ' . $expected_hours[0]['to'] ) <= strtotime( $base_date . ' ' . $expected_hours[0]['from'] ) ) {
			$first_to = gmdate( 'H:i', strtotime( $base_date . ' ' . $expected_hours[0]['to'] ) + 86400 );
		}

		wp_send_json(
			array(
				'resp'        => $html,
				'hours'       => $hours,
				'largestHour' => $first_to,
				'lowesttHour' => $first_from,
				'limits'      => array( $first_from, $first_to ),
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
