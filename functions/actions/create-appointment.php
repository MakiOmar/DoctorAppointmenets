<?php
/**
 * Create appointment
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();


add_action( 'woocommerce_payment_complete', 'snks_woocommerce_payment_complete_action' );

/**
 * Function for `woocommerce_payment_complete` action-hook.
 *
 * @param int $order_id Order's ID.
 * @return void
 * @throws Exception Exception.
 */
function snks_woocommerce_payment_complete_action( $order_id ) {
	$order       = wc_get_order( $order_id );
	$customer_id = $order->get_customer_id();
	$booking_id  = get_post_meta( $order_id, 'booking_id', true );

	try {
		if ( ! empty( $booking_id ) ) {
			$timetable = snks_get_timetable_by( 'ID', absint( $booking_id ) );
			if ( 'waiting' === $timetable->session_status ) {
				$updated = snks_update_timetable(
					$timetable->ID,
					array(
						'client_id'      => $customer_id,
						'session_status' => 'open',
						'order_id'       => $order_id,
						'settings'       => wp_json_encode( snks_timetable_settings( $timetable->user_id ) ),
					)
				);

				if ( $updated ) {
					// Existing logic for processing...
					$current_hour   = current_time( 'H' );
					$doctor_earning = get_post_meta( $order->get_id(), '_main_price', true );

					if ( $current_hour >= 0 && $current_hour < 9 ) {
						$current_temp_wallet     = (float) get_user_meta( $timetable->user_id, 'temp_wallet', true );
						$new_temp_wallet_balance = $current_temp_wallet + $doctor_earning;
						update_user_meta( $timetable->user_id, 'temp_wallet', $new_temp_wallet_balance );
					} else {
						snks_wallet_credit( $timetable->user_id, $doctor_earning, 'الدخل مقابل حجز موعد' );
					}

					snks_add_transaction( $timetable->user_id, $timetable->ID, 'add', $doctor_earning );
					snks_log_transaction( $timetable->user_id, $doctor_earning, 'add' );
					snks_insert_session_actions( $timetable->ID, $customer_id, 'no' );
					update_post_meta( $order_id, 'booking_id', $timetable->ID );
					update_post_meta( $order_id, 'doctor_id', $timetable->user_id );
					update_post_meta( $order_id, 'doctor_pricings', snks_doctor_pricings( $timetable->user_id ) );

					$message = sprintf(
						'تم حجز جلسة أونلاين يوم %1$s الموافق %2$s الساعه %3$s ويمكنك الدخول للجلسة في موعدها بالضغط هنا :%4$s',
						$timetable->day,
						gmdate( 'Y-m-d', strtotime( $timetable->date_time ) ),
						snks_localize_time( gmdate( 'H:i a', strtotime( $timetable->date_time ) ) ),
						esc_url( site_url( '/my-bookings' ) )
					);
					send_sms_via_whysms( $order->get_billing_phone(), $message );
				} else {
					snks_error_log( $order_id . ' :Failed to update timetable.' );
					throw new Exception( 'Failed to update timetable.' );
				}
			} else {
				snks_error_log( $order_id . ' :Timetable session status is not waiting.' );
				throw new Exception( 'Timetable session status is not waiting.' );
			}
		} else {
			snks_error_log( $order_id . ' :No booking ID found.' );
			throw new Exception( 'No booking ID found.' );
		}
	} catch ( Exception $e ) {
		// Log the failure for retry.
		$failed_orders = get_option( 'snks_failed_order_actions', array() );
		if ( ! in_array( $order_id, $failed_orders, true ) ) {
			$failed_orders[] = $order_id;
			update_option( 'snks_failed_order_actions', $failed_orders );
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
					'client_id'      => 0,
					'session_status' => $status,
					'order_id'       => 0,
				)
			);
			if ( $updated ) {
				if ( 'waiting' === $status ) {
					if ( $booking ) {
						snks_waiting_others( $booking );
					}
				}
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
					$html .= 'Old Booking ID: ' . $order->get_meta( 'booking_id' ) . '<br>';
					$html .= 'New Booking ID: ' . $order->get_meta( 'new_booking_id' ) . '<br>';
					$html .= 'Order Type: ' . $order->get_meta( 'order_type' ) . '<br>';
				}
			}
		}
		echo wp_kses_post( $html );
	}
);
