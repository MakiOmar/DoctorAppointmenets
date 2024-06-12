<?php
/**
 * Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump, WordPress.DB.DirectDatabaseQuery.DirectQuery

/**
 * Helper that require all files in a folder/subfolders once.
 *
 * @param string $dir Directory path.
 * @return void
 */
function snks_require_all_files( $dir ) {
	foreach ( glob( "$dir/*" ) as $path ) {
		if ( preg_match( '/\.php$/', $path ) ) {
			require_once $path; // It's a PHP file, so just require it.
		} elseif ( is_dir( $path ) ) {
			snks_require_all_files( $path ); // It's a subdir, so call the same function for this subdir.
		}
	}
}

snks_require_all_files( SNKS_DIR . 'functions/helpers' );
/**
 * Go back
 *
 * @return string
 */
function snks_go_back() {

	$referer      = wp_get_referer();
	$referer_safe = esc_url( $referer );

	ob_start();
	?>
	<a class='anony-go-back' href="<?php echo esc_url( $referer_safe ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16" fill="none"><path d="M10.086 11.9619L14.048 7.99991L10.086 4.03691" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.95101 8L13.937 8" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</a>
	<?php
	return ob_get_clean();
}
/**
 * Check if array has two occurrences
 *
 * @param array $arr Array.
 * @param int   $element search element.
 * @return boolean
 */
function has_two_occurrences( $arr, $element ) {
	$keys = array_keys( $arr, $element, true );
	return count( $keys ) === 2;
}
/**
 * Get expected hours
 *
 * @param array  $mins Available periods.
 * @param string $start_hour Start hour.
 * @return array
 */
function snks_expected_hours( $mins, $start_hour ) {
	$expected_hours = array();
	foreach ( $mins as $min ) {

		// Convert start time to minutes.
		$start_minutes = strtotime( $start_hour ) / 60;
		// Add the duration to the start time.
		$end_hour = $start_minutes + $min;

		$end_hour         = gmdate( 'h:i a', $end_hour * 60 );
		$expected_hours[] = array(
			'from' => $start_hour,
			'to'   => $end_hour,
		);

		if ( 30 === $min && has_two_occurrences( $mins, 30 ) ) {
			$start_hour = $end_hour;
		}
	}
	return $expected_hours;
}
add_action(
	'wp_footer',
	function () {
		snks_print_r( snks_expected_hours( array( 60, 45, 30, 30 ), '12 pm' ) );
	}
);
