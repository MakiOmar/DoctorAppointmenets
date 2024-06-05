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
