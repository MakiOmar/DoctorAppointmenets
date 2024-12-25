<?php
/**
 * Accounting
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();
// Hook into the template loader.
add_filter( 'woocommerce_locate_template', 'snks_thank_you_template', 100, 3 );

/**
 * Replace WooCommerce Thank You page template.
 *
 * @param string $template      The path of the template to load.
 * @param string $template_name The name of the template.
 *
 * @return string
 */
function snks_thank_you_template( $template, $template_name ) {
	// Define the plugin template directory.
	$plugin_path = SNKS_DIR . 'woocommerce/';
	// Check if we're looking for the thankyou template.
	if ( 'checkout/thankyou.php' === $template_name || 'checkout/form-pay.php' === $template_name ) {
		// If our custom template exists, load it instead of the default.
		if ( file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}
	}

	return $template;
}

add_action( 'template_redirect', 'remove_order_details_from_thank_you_page' );
/**
 * Remove order details
 *
 * @return void
 */
function remove_order_details_from_thank_you_page() {
	if ( is_wc_endpoint_url( 'order-received' ) ) {
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
	}
}
