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
				// Get the current time based on WordPress timezone.
				$current_hour   = current_time( 'H' ); // 'H' returns the current hour in 24-hour format (00-23).
				$doctor_earning = get_post_meta( $order->get_id(), '_main_price', true );
				// Check if the current time is between 12 AM and 9 AM0.
				if ( $current_hour >= 0 && $current_hour < 9 ) {
					// Get the current amount in temp_wallet.
					$current_temp_wallet = (float) get_user_meta( $timetable->user_id, 'temp_wallet', true );

					// Add the order total to the temp_wallet.
					$new_temp_wallet_balance = $current_temp_wallet + $doctor_earning;
					update_user_meta( $timetable->user_id, 'temp_wallet', $new_temp_wallet_balance );
				} else {
					// Directly credit the amount to the user's wallet outside the restricted time.
					snks_wallet_credit( $timetable->user_id, $doctor_earning, 'الدخل مقابل حجز موعد' );
				}
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

		$meta_data = $order->get_meta_data();

		if ( $meta_data ) {
			foreach ( $meta_data as $meta ) {
				if ( 'order_type' === $meta->key && 'edit-fees' === $meta->value ) {
					$html .= 'Connected Order: <a href="/wp-admin/post.php?post=' . $order->get_meta( 'connected_order' ) . '&action=edit">' . $order->get_meta( 'connected_order' ) . '</a><br>';
					$html .= 'Doctor ID: <a href="/wp-admin/user-edit.php?user_id=' . $order->get_meta( '_user_id' ) . '">' . $order->get_meta( '_user_id' ) . '</a><br>';
					$html .= 'Old Booking ID: ' . $order->get_meta( 'old_booking_id' ) . '<br>';
					$html .= 'New Booking ID: ' . $order->get_meta( 'new_booking_id' ) . '<br>';
					$html .= 'Order Type: ' . $order->get_meta( 'order_type' ) . '<br>';
				}
			}
		}
		echo wp_kses_post( $html );
	}
);
