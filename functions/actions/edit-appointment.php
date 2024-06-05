<?php
/**
 * Edit appointment
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Save form data to session
 *
 * @return void
 */
add_action(
	'template_redirect',
	function () {
		if ( empty( $_POST['edit-booking-id'] ) ) {
			return;
		}
		if ( isset( $_POST ) && isset( $_POST['create_appointment_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['create_appointment_nonce'] ) ), 'create_appointment' ) && isset( $_POST['create-appointment'] ) ) {
			$_request = wp_unslash( $_POST );
			$booking  = snks_get_timetable_by( 'ID', absint( $_POST['edit-booking-id'] ) );
			if ( ! $booking || get_current_user_id() !== absint( $booking->client_id ) ) {
				wp_safe_redirect( add_query_arg( 'error', 'unknown', site_url( '/consulting-form' ) ) );
				exit;
			}
			$order_id      = $booking->order_id;
			$edited_before = get_post_meta( $order_id, 'booking-edited', true );
			if ( $edited_before && ! empty( $edited_before ) ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'edit-booking' => $_request['edit-booking-id'],
							'error'        => 'edit-limit',
						),
						site_url( '/consulting-form' )
					)
				);
				exit;
			}
			$updated = snks_update_timetable(
				$booking->ID,
				array(
					'booking_availability' => true,
					'client_id'            => 0,
					'order_id'             => 0,
				)
			);

			if ( $updated ) {
				$timetable = snks_get_timetable( false, $_request['current-month-day'], $_request['selected-hour'] );
				if ( $timetable->booking_availability ) {
					$updated = snks_update_timetable(
						$timetable->ID,
						array(
							'booking_availability' => false,
							'client_id'            => $booking->client_id,
							'order_id'             => $order_id,
						)
					);
					if ( $updated ) {
						update_post_meta( $order_id, 'booking-edited', '1' );
						update_post_meta( $order_id, 'booking_id', $timetable->ID );
						wp_safe_redirect( site_url( '/consulting-appointments' ) );
						exit;
					}
				}
			}
		}
	}
);
