<?php
/**
 * Cancel order
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! function_exists( 'WC' ) ) {
	return;
}

/**
 * Define cron interval
 *
 * @param array $schedules Schedules.
 * @return array
 */
function register_custom_cron_intervals( $schedules ) {
	$schedules['custom_15_minutes'] = array(
		'interval' => 900,
		'display'  => __( 'Every 15 Minutes' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'register_custom_cron_intervals' );

/**
 * Hook to schedule
 *
 * @return void
 */
function schedule_autocancel_wc_orders() {
	if ( ! wp_next_scheduled( 'autocancel_wc_orders_event' ) ) {
		wp_schedule_event( time(), 'every_minute', 'autocancel_wc_orders_event' );
	}
}
add_action( 'wp', 'schedule_autocancel_wc_orders' );

/**
 * Cancel pending orders
 *
 * @return void
 */
function snks_auto_cancel_wc_orders() {
	$query = array(
		'limit'   => 5,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array( 'pending', 'on-hold' ),
	);

	$orders = wc_get_orders( $query );
	foreach ( $orders as $order ) {
		$date     = new DateTime( $order->get_date_created(), wp_timezone() );
		$now      = current_datetime();
		$interval = $date->diff( $now );

		$minutes_diff = $interval->format( '%i' );
		if ( $minutes_diff > 20 ) {
			$order->set_status( 'cancelled', 'Cancelled for missing payment' );
			$order->save();
			$booking_id = $order->get_meta( 'booking_id', true );
			$timetable  = snks_get_timetable_by( 'ID', absint( $booking_id ) );
			if ( ! $timetable || 'open' === $timetable->session_status ) {
				return;
			}
			$updated = snks_update_timetable(
				absint( $booking_id ),
				array(
					'order_id' => 0,
				)
			);
			if ( $updated ) {
				snks_waiting_others( $timetable );
				delete_post_meta( $order->get_id(), 'booking_id' );
			}
		}
	}
}

add_action( 'autocancel_wc_orders_event', 'snks_auto_cancel_wc_orders' );
