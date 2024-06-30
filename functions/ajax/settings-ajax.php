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
		if ( ! empty( $expected_hours ) ) {
			foreach ( $expected_hours as $expected_hour ) {
				$expected_hour_from = gmdate( 'H:i', strtotime( $expected_hour['from'] ) );
				$expected_hour_to   = gmdate( 'H:i', strtotime( $expected_hour['to'] ) );
				$hours[]            = $expected_hour_from;
				$hours[]            = $expected_hour_to;
				$html              .= sprintf( '<p class="expected-hour-text">من <span class="%1$s">%2$s</span> إلى <span class="%3$s">%4$s</span></p>', str_replace( array( ' ', ':' ), '-', $expected_hour_from ), esc_html( $expected_hour['from'] ), str_replace( array( ' ', ':' ), '-', $expected_hour_to ), esc_html( $expected_hour['to'] ) );
			}
		}
		$hours = array_values( array_unique( $hours ) );
		// Sort hours acsending.
		usort(
			$hours,
			function ( $a, $b ) {
				return strtotime( $a ) - strtotime( $b );
			}
		);
		wp_send_json(
			array(
				'resp'        => $html,
				'hours'       => $hours,
				'largestHour' => end( $hours ),
				'lowesttHour' => $hours[0],
				'limits'      => array( $hours[0], end( $hours ) ),
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
		unset( $preview_timetable[ $_req['slotDay'] ][ $_req['slotIndex'] ] );
		$update = update_user_meta( get_current_user_id(), 'preview_timetable', $preview_timetable );
		wp_send_json(
			array(
				'resp' => $update,
			),
		);

		die();
	}
);

add_action(
	'wp_ajax_insert_timetable',
	function () {
		if ( ! snks_is_doctor() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Doctor only.' );
		}
		$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'insert_timetable_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		$preview_timetable = snks_get_preview_timetable();
		if ( $preview_timetable && ! empty( $preview_timetable ) ) {
			foreach ( $preview_timetable as $data ) {
				if ( ! snks_timetable_exists( get_current_user_id(), $data['day'], $data['starts'], $data['ends'] ) ) {
					snks_insert_timetable( $data );
				}
			}
		}
		wp_send_json(
			array(
				'resp' => true,
			),
		);

		die();
	}
);
