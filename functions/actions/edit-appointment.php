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
			$_request    = wp_unslash( $_POST );
			$_doctor_url = snks_encrypted_doctor_url( $_request['user_id'] );
			$booking     = snks_get_timetable_by( 'ID', absint( $_POST['edit-booking-id'] ) );
			if ( ! $booking || get_current_user_id() !== absint( $booking->client_id ) ) {
				wp_safe_redirect( add_query_arg( 'error', 'unknown', $_doctor_url ) );
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
						$_doctor_url
					)
				);
				exit;
			}
			$prev_date = gmdate( 'Y-m-d', strtotime( $booking->date_time ) );
			$updated   = snks_update_timetable(
				$booking->ID,
				array(
					'client_id' => 0,
					'order_id'  => 0,
				)
			);
			if ( $updated ) {
				$timetable = snks_get_timetable_by( 'ID', absint( $_request['selected-hour'] ) );
				$new_date  = gmdate( 'Y-m-d', strtotime( $timetable->date_time ) );
				if ( $new_date === $prev_date ) {
					$prev_date_starts = strtotime( '1970-01-01 ' . $booking->starts );
					$prev_date_ends   = strtotime( '1970-01-01 ' . $booking->ends );
					$new_date_starts  = strtotime( '1970-01-01 ' . $timetable->starts );
					if ( $prev_date_starts < $new_date_starts && $prev_date_ends > $new_date_starts ) {
						$status = 'closed';
					} else {
						$status = 'waiting';
					}
					snks_update_timetable(
						$booking->ID,
						array(
							'session_status' => $status,
						)
					);
				}
				//phpcs:disable
				if ( 0 == $timetable->order_id ) {
					//phpcs:enable
					$updated = snks_update_timetable(
						$timetable->ID,
						array(
							'session_status' => 'open',
							'client_id'      => $booking->client_id,
							'order_id'       => $order_id,
						)
					);
					if ( $updated ) {
						update_post_meta( $order_id, 'booking-edited', '1' );
						update_post_meta( $order_id, 'booking_id', $timetable->ID );
						$order      = wc_get_order( $order_id );
						$line_items = $order->get_items();
						// Loop through each line item.
						foreach ( $line_items as $item_id => $item ) {
							wc_update_order_item_meta( $item_id, 'booking_id', $timetable->ID );
						}
						wp_safe_redirect( site_url( '/my-account/my-appointments/' ) );
						exit;
					}
				}
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'edit-booking' => $_request['edit-booking-id'],
						'error'        => 'unknown',
					),
					$_doctor_url
				)
			);
			exit;
		}
	}
);
