<?php
/**
 * Debug
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump, WordPress.DB.DirectDatabaseQuery.DirectQuery
/**
 * Neat print
 *
 * @param mixed $_print Print.
 * @return void
 */
function snks_print_r( $_print ) {
	echo '<pre style="direction:ltr;text-align:left">';
	print_r( $_print );
	echo '</pre>';
}

/**
 * Neat error log print
 *
 * @param mixed $_print Print.
 * @return void
 */
function snks_error_log( $_print ) {
	error_log( print_r( $_print, true ) );
}
