<?php
/**
 * Patient appointments
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Get customers latest order date
 *
 * @param mixed $doctors_id Doctors ID.
 * @param mixed $customer_id Users ID.
 * @return mixed
 */
function snks_latest_completed_order_date( $doctors_id, $customer_id = false ) {
	$latest_order = snks_latest_completed_order( $doctors_id, $customer_id );
	// Check if completed orders exist for the customer.
	if ( $latest_order ) {
		$date_paid = $latest_order->get_date_paid();
		if ( $date_paid ) {
			return $date_paid->date( 'Y-m-d H:i:s' );
		}
	}
	return false;
}

/**
 * Get customers latest order
 *
 * @param mixed $doctors_id Doctors ID.
 * @param mixed $customer_id Users ID.
 * @return mixed
 */
function snks_latest_completed_order( $doctors_id, $customer_id = false ) {
	if ( ! $customer_id ) {
		$customer_id = get_current_user_id();
	}
	// Retrieve the orders for the customer.
	$orders = wc_get_orders(
		array(
			'customer'     => $customer_id,
			'status'       => array( 'wc-completed', 'wc-processing' ),
			'limit'        => 1,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'meta_key'     => 'doctor_id',
			'meta_value'   => $doctors_id,
			'meta_compare' => '=',
		)
	);// phpcs:ensable
	// Check if completed orders exist for the customer.
	if ( ! empty( $orders ) ) {
		$latest_order = reset( $orders );
		return $latest_order;
	}
	return false;
}
