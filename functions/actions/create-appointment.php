<?php
/**
 * Create appointment
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();


add_action( 'woocommerce_order_status_completed', 'snks_woocommerce_payment_complete_action' );

/**
 * Function for `woocommerce_payment_complete` action-hook.
 *
 * @param int $order_id Order's ID.
 * @return void
 */
function snks_woocommerce_payment_complete_action( $order_id ) {

	$order        = wc_get_order( $order_id );
	$customer_id  = $order->get_customer_id();
	$booking_day  = get_post_meta( $order_id, 'booking_day', true );
	$booking_hour = get_post_meta( $order_id, 'booking_hour', true );
	$booking_id   = get_post_meta( $order_id, 'booking_id', true );
	if ( ! empty( $booking_id ) ) {
		$timetable = snks_get_timetable_by( 'ID', absint( $booking_id ) );
		if ( 'waiting' === $timetable->session_status ) {
			$updated = snks_update_timetable(
				$timetable->ID,
				array(
					'client_id'      => $customer_id,
					'session_status' => 'open',
					'order_id'       => $order_id,
				)
			);
			if ( $updated ) {
				snks_close_others( $timetable );
				snks_insert_session_actions( $timetable->ID, $customer_id, 'no' );
				update_post_meta( $order_id, 'booking_id', $timetable->ID );
				update_post_meta( $order_id, 'doctor_id', $timetable->user_id );
			}
		}
	}
}

add_action(
	'woocommerce_order_status_cancelled',
	function ( $order_id ) {
		$booking_id = get_post_meta( $order_id, 'booking_id', true );
		$booking    = snks_get_timetable_by( 'ID', $booking_id );
		if ( ! $booking || empty( $booking ) ) {
			return;
		}
		if ( snks_is_past_date( $booking->date_time ) ) {
			$status = 'cancelled';
		} else {
			$status = 'waiting';
		}
		if ( ! empty( $booking_id ) ) {
			$updated = snks_update_timetable(
				absint( $booking_id ),
				array(
					'booking_availability' => true,
					'client_id'            => 0,
					'session_status'       => $status,
					'order_id'             => 0,
				)
			);
			if ( $updated ) {
				delete_post_meta( $order_id, 'booking_id' );
			}
		}
	}
);

// Add custom order meta to the edit order screen after billing details.
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( $order ) {
		$booking_id = get_post_meta( $order->get_id(), 'booking_id', true );
		$html       = '';
		if ( ! empty( $booking_id ) ) {
			ob_start();
			$html .= '<p><strong>Booking ID:</strong> ' . esc_html( $booking_id ) . '</p>';
			ob_end_clean();
		}
		echo wp_kses_post( $html );
	}
);
