<?php
/**
 * WooCommerce
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Override default WooCommerce templates and template parts from plugin.
 *
 * E.g.
 * Override template 'woocommerce/loop/result-count.php' with 'my-plugin/woocommerce/loop/result-count.php'.
 * Override template part 'woocommerce/content-product.php' with 'my-plugin/woocommerce/content-product.php'.
 *
 * Note: We used folder name 'woocommerce' in plugin to override all woocommerce templates and template parts.
 * You can change it as per your requirement.
 */
// Override Template Part's.
add_filter( 'wc_get_template_part', 'snks_override_woocommerce_template_part', 10, 3 );
// Override Template's.
add_filter( 'woocommerce_locate_template', 'snks_override_woocommerce_template', 10, 3 );
/**
 * Template Part's
 *
 * @param  string $template Default template file path.
 * @param  string $slug     Template file slug.
 * @param  string $name     Template file name.
 * @return string           Return the template part from plugin.
 */
function snks_override_woocommerce_template_part( $template, $slug, $name ) {
	$template_directory = SNKS_DIR . 'woocommerce/';
	if ( $name ) {
		$path = $template_directory . "{$slug}-{$name}.php";
	} else {
		$path = $template_directory . "{$slug}.php";
	}
	return file_exists( $path ) ? $path : $template;
}
/**
 * Template File
 *
 * @param  string $template      Default template file  path.
 * @param  string $template_name Template file name.
 * @return string                Return the template file from plugin.
 */
function snks_override_woocommerce_template( $template, $template_name ) {
	$template_directory = SNKS_DIR . 'woocommerce/';
	$path               = $template_directory . $template_name;
	return file_exists( $path ) ? $path : $template;
}

/**
 * Prevent changing order status from 'completed' to any other status.
 *
 * @param int    $order_id The order id.
 * @param string $old_status Previous order status.
 * @param string $new_status New order status.
 */
function prevent_completed_order_status_change( $order_id, $old_status, $new_status ) {
	$order = wc_get_order( $order_id );

	// If the old status is 'completed' and the new status is different, block the change.
	if ( 'completed' === $old_status && 'completed' !== $new_status ) {
		// Reset the status to 'completed'.
		$order->set_status( 'completed' );

		// Optionally add a note to the order.
		$order->add_order_note( __( 'Order status change from "completed" was blocked.', 'your-textdomain' ) );
		$order->save();
		// Prevent the status change by halting the process.
		return;
	}
}
add_action( 'woocommerce_order_status_changed', 'prevent_completed_order_status_change', 10, 3 );
