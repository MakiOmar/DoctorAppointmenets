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
	if ( ! $customer_id ) {
		$customer_id = get_current_user_id();
	}
	// Retrieve the orders for the customer.
	$orders = wc_get_orders(
		array(
			'customer'     => $customer_id,
			'status'       => 'completed',
			'limit'        => 1,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'meta_key'     => 'doctor_id',
			'meta_value'   => $doctors_id,
			'meta_compare' => '=',
		)
	);

	// Check if completed orders exist for the customer.
	if ( ! empty( $orders ) ) {
		$latest_order = reset( $orders );
		return $latest_order->get_date_completed()->date( 'Y-m-d H:i:s' );
	}
	return false;
}
