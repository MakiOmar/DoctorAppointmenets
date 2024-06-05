<?php
/**
 * Programme checkout
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'woocommerce_thankyou',
	function ( $order_id ) {
		$jet_form = get_post_meta( $order_id, '_jf_wc_details', true );
		if ( $jet_form && ! empty( $order_id ) && isset( $jet_form['form_data']['payment_for'] ) ) {
			$user_id  = get_current_user_id();
			$order    = wc_get_order( $order_id );
			$date_obj = $order->get_date_created();
		}
	}
);
