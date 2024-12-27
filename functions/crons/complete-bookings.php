<?php
/**
 * Force complete orders bookings
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Retry failed order actions via WP Cron.
 */
function snks_retry_failed_orders() {
	$failed_orders = get_option( 'snks_failed_order_actions', array() );

	foreach ( $failed_orders as $key => $order_id ) {
		try {
			snks_woocommerce_payment_complete_action( $order_id );
			unset( $failed_orders[ $key ] ); // Remove from the retry list if successful.
		} catch ( Exception $e ) {
			return;
		}
	}

	// Update the list of failed orders.
	update_option( 'snks_failed_order_actions', $failed_orders );
}
add_action( 'snks_retry_failed_orders_event', 'snks_retry_failed_orders' );

// Schedule the event if not already scheduled.
if ( ! wp_next_scheduled( 'snks_retry_failed_orders_event' ) ) {
	wp_schedule_event( time(), 'hourly', 'snks_retry_failed_orders_event' );
}
