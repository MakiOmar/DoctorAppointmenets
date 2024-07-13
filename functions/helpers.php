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
 * Count date
 *
 * @param string $date Date.
 * @param array  $timetables Timetables.
 * @return int
 */
function snks_count_date( $date, $timetables ) {
	// Initialize the counter.
	$count = 0;

	// Loop through the array.
	foreach ( $timetables as $item ) {
		// Check if the date key exists and matches the date to check.
		if ( isset( $item['date'] ) && $item['date'] === $date ) {
			++$count;
		}
	}
	return $count;
}
/**
 * Function that groups an array of associative arrays by some key.
 *
 * @param string $key Property to sort by.
 * @param array  $data Array that stores multiple associative arrays.
 */
function snks_group_by( $key, $data ) {
	$result = array();

	foreach ( $data as $val ) {
		if ( array_key_exists( $key, $val ) ) {
			$result[ $val[ $key ] ][] = $val;
		} else {
			$result[''][] = $val;
		}
	}

	return $result;
}
/**
 * Days indexes
 *
 * @return array
 */
function snks_days_indexes() {
	$weekday_numbers  = array( 5, 0, 1, 2, 3, 4, 6 );
	$abbreviated_days = array();

	foreach ( $weekday_numbers as $number ) {
		$date = new DateTime();
		$date->setISODate( 2022, 1, $number + 1 ); // Set the ISO Week Date.
		$abbreviated_days[ $date->format( 'D' ) ] = $number;
	}
	return( $abbreviated_days );
}
/**
 * Sort $array_b items according to their position in $array_a
 *
 * @param array $array_a Array A.
 * @param array $array_b Array B.
 * @return array
 */
function snks_sort_days( $array_a, $array_b ) {
	// Custom comparison function to sort according to the position in array A.
	usort(
		$array_b,
		function ( $a, $b ) use ( $array_a ) {
			// Find the positions of $a and $b in array A.
			$pos_a = array_search( $a, $array_a, true );
			$pos_b = array_search( $b, $array_a, true );

			// Compare positions.
			return $pos_a - $pos_b;
		}
	);
	return $array_b;
}
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
 * Localize time
 *
 * @param string $time Time.
 * @return string
 */
function snks_localize_time( $time ) {
	return str_replace( array( 'am', 'pm' ), array( 'ص', 'م' ), $time );
}

/**
 * Get current doctors id form doctors page URL
 *
 * @return mixed
 */
function snks_url_get_doctors_id() {
	//phpcs:disable
	preg_match( '/\d+/', urldecode( $_SERVER[ 'REQUEST_URI' ] ), $match );
	if ( ! $match ) {
		return false;
	}
	//phpcs:enable
	$user_id = array_shift( $match );
	return $user_id;
}

