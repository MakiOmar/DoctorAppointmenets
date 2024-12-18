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
 * Function to group objects by a member name
 *
 * @param array  $objects An array of objects.
 * @param string $member_name Object's member name.
 * @return array
 */
function snks_group_objects_by( $objects, $member_name ) {
	$grouped = array();
	foreach ( $objects as $object ) {
		$key = $object->$member_name;
		if ( ! isset( $grouped[ $key ] ) ) {
			$grouped[ $key ] = array();
		}
		$grouped[ $key ][] = $object;
	}
	return $grouped;
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
		$date = current_datetime();
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
 * Get transient key
 *
 * @return string
 */
function snks_form_data_transient_key() {
	//phpcs:disable
	// $_COOKIE['booking_trans_key']
	return 'consulting_form_data_' . str_replace( '.', '', $_SERVER['REMOTE_ADDR'] );
	//phpcs:enable
}
/**
 * Go back
 *
 * @return string
 */
function snks_go_back() {
	if ( ! is_user_logged_in() && ! is_page( 'register' ) ) {
		return;
	}
	if ( ! snks_is_patient() ) {
		$referer = add_query_arg( 'id', snks_get_settings_doctor_id(), home_url( '/account-setting' ) );
	} else {
		$referer = home_url( '/my-bookings' );
	}

	// Sanitize the URL for use in an HTML attribute.
	$referer_safe = $referer;

	ob_start();
	?>
			<a class='anony-go-back' href="<?php echo esc_url( $referer_safe ); ?>">x</a>
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
 * Localized time
 *
 * @param string $time Time.
 * @return string
 */
function snks_localized_time( $time ) {
	return snks_localize_time( gmdate( 'h:i a', strtotime( $time ) ) );
}
/**
 * Localized datetime
 *
 * @param string $datetime Time.
 * @return string
 */
function snks_localized_datetime( $datetime ) {
	return str_replace( array( 'am', 'pm' ), array( 'ص', 'م' ), gmdate( 'Y-m-d h:i a', strtotime( $datetime ) ) );
}



/**
 * Human readable datetime diff
 *
 * @param string $date_time DateTime.
 * @param string $text If past date text.
 * @return string
 */
function snks_human_readable_datetime_diff( $date_time, $text = 'Start' ) {
	if ( snks_is_past_date( $date_time ) ) {
		$output = $text;
	} else {
		$output = snks_get_time_difference( $date_time, wp_timezone() );
	}
	return $output;
}
/**
 * Get an array of dates only from a list of timetables objects.
 *
 * @param array $bookable_days_obj Array of objects.
 * @return array
 */
function snks_timetables_unique_dates( $bookable_days_obj ) {
	$bookable_dates_times = wp_list_pluck( $bookable_days_obj, 'date_time' );
	return array_unique(
		array_map(
			function ( $value ) {
				return gmdate( 'Y-m-d', strtotime( $value ) );
			},
			$bookable_dates_times,
		)
	);
}
/**
 * Undocumented function
 *
 * @param string $to To Email.
 * @param string $title Email text.
 * @param string $sub_title Email text.
 * @param string $text_1 Email text.
 * @param string $text_2 Email text.
 * @param string $text_3 Email text.
 * @param string $button_text Email text.
 * @param string $button_url Email text.
 * @param string $after_button Email text.
 * @return mixed
 */
function snks_send_email( $to, $title, $sub_title, $text_1, $text_2, $text_3, $button_text, $button_url, $after_button = '' ) {
	ob_start();
	include SNKS_DIR . 'templates/email-template.php';
	$template = ob_get_clean();

	$message = str_replace(
		array(
			'{logo}',
			'{title}',
			'{sub_title}',
			'{content_placeholder}',
			'{text_1}',
			'{text_2}',
			'{text_3}',
			'{button_text}',
			'{button_url}',
			'{after_button}',
		),
		array(
			SNKS_LOGO,
			$title,
			$sub_title,
			SNKS_EMAIL_IMG,
			$text_1,
			$text_2,
			$text_3,
			$button_text,
			$button_url,
			$after_button,
		),
		$template
	);

	$to      = $to;
	$subject = $title . ' - ' . SNKS_APP_NAME;
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
	);
	return wp_mail( $to, $subject, $message, $headers );
}
/**
 * Retrieves the country code based on the user's IP address and stores it in a cookie.
 *
 * This function uses an external API to fetch the country code of the user's IP address and stores
 * the code in a cookie for 24 hours. It uses WordPress functions for making HTTP requests and handling responses.
 *
 * @param bool $set_cookie Weather to set cookie or not.
 * @return string
 */
function snks_get_country_code( $set_cookie = true ) {
	//phpcs:disable
	// Get the user's IP address, validating it for security.
	$ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
	//phpcs:enable
	// If the IP address is not valid, return early.
	if ( ! $ip ) {
		return 'Unknown';
	}

	// API key and URL for IP lookup.
	$api_key = 'yBZHxURnxnHhONq'; // Replace with your actual API key.
	$api_url = sprintf( 'https://pro.ip-api.com/json/%s?key=%s&fields=countryCode', $ip, esc_attr( $api_key ) );

	// Send request to the IP API using wp_remote_get.
	$response = wp_remote_get( $api_url );

	// Check for errors and validate the response.
	if ( is_wp_error( $response ) ) {
		return; // Early return if there's an error in the response.
	}
	$country_code = 'Unknown';
	// Retrieve the response body.
	$body = wp_remote_retrieve_body( $response );
	// Check if the body is not empty and contains serialized data.
	if ( ! empty( $body ) ) {
		$data = json_decode( $body ); // Using @ to suppress potential warnings.
		// Check if the country code is present.
		if ( $data && isset( $data->countryCode ) ) { //phpcs:disable
			$country_code = sanitize_text_field( $data->countryCode );
			//phpcs:enable
			if ( $set_cookie ) {
				// Store the country code in a cookie for 24 hours.
				setcookie( 'country_code', $country_code, time() + DAY_IN_SECONDS, '/' ); // DAY_IN_SECONDS is a WordPress constant.
			}

			return $country_code;
		}
	}
	snks_error_log( $country_code );
	return $country_code;
}

/**
 * Get country code
 *
 * @param bool $set_cookie Weather to set cookie or not.
 * @return string
 */
function snsk_ip_api_country( $set_cookie = true ) {
	// Check if the country code is already stored in a cookie.
	if ( isset( $_COOKIE['country_code'] ) ) {
		return sanitize_text_field( wp_unslash( $_COOKIE['country_code'] ) ); // Return the cached country code.
	}
	return snks_get_country_code( $set_cookie );
}

add_action(
	'init',
	function () {
		// Check if the country code is already stored in a cookie.
		if ( ! isset( $_COOKIE['country_code'] ) ) {
			snks_get_country_code( false );
		}
	}
);

/**
 * Get doctor's url
 *
 * @param mixed $user User object or User ID.
 * @return string
 */
function snks_encrypted_doctor_url( $user ) {
	if ( is_a( $user, 'WP_User' ) && $user ) {
		return site_url( '/7jz/' . $user->user_nicename );
	}
	$user_obj = get_user_by( 'id', absint( $user ) );
	if ( $user_obj ) {
		return site_url( '/7jz/' . $user_obj->user_nicename );
	}
	return '#';
}
/**
 * Get current doctors id form doctors page URL
 *
 * @return mixed
 */
function snks_url_get_doctors_id() {
	global $wp;
	$user_id = false;
	if ( isset( $wp->query_vars ) && isset( $wp->query_vars['doctor_id'] ) ) {
		$user = get_user_by( 'slug', $wp->query_vars['doctor_id'] );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}
	return $user_id;
}

/**
 * Calculate the time difference in seconds between the current time and a session's time.
 *
 * @param object $session Session object containing a `date_time` property.
 * @return int Time difference in seconds. Positive for future sessions, negative for past sessions.
 */
function snks_diff_seconds( $session ) {
	// Ensure the session object has a valid date_time property.
	if ( ! isset( $session->date_time ) || empty( $session->date_time ) ) {
		return 0; // Return 0 if date_time is missing or empty.
	}

	// Fetch the WordPress timezone.
	$timezone = wp_timezone();

	try {
		// Create a DateTime object for the session time in the WordPress timezone.
		$booking_dt_obj = new DateTime( $session->date_time, $timezone );
		// Get the current date and time in the WordPress timezone.
		$now = current_datetime();
		// Calculate the difference in seconds.
		return $booking_dt_obj->getTimestamp() - $now->getTimestamp();
	} catch ( Exception $e ) {
		return 0; // Return 0 in case of an error.
	}
}

/**
 * Get localized day name
 *
 * @param string $day Day name in Eng.
 * @return string
 */
function snks_localize_day( $day ) {
	$days_labels = json_decode( DAYS_ABBREVIATIONS, true );
	return $days_labels[ $day ];
}
/**
 * Localize an English date string to Arabic.
 *
 * @param string $date_string The date string in English format.
 * @return string The localized Arabic date string.
 */
function localize_date_to_arabic( $date_string ) {
	// English to Arabic day and month names.
	$days = array(
		'Saturday'  => 'السبت',
		'Sunday'    => 'الأحد',
		'Monday'    => 'الإثنين',
		'Tuesday'   => 'الثلاثاء',
		'Wednesday' => 'الأربعاء',
		'Thursday'  => 'الخميس',
		'Friday'    => 'الجمعة',
	);

	$months = array(
		'January'   => 'يناير',
		'February'  => 'فبراير',
		'March'     => 'مارس',
		'April'     => 'أبريل',
		'May'       => 'مايو',
		'June'      => 'يونيو',
		'July'      => 'يوليو',
		'August'    => 'أغسطس',
		'September' => 'سبتمبر',
		'October'   => 'أكتوبر',
		'November'  => 'نوفمبر',
		'December'  => 'ديسمبر',
	);

	// Replace English day names with Arabic.
	$localized_date = str_replace( array_keys( $days ), $days, $date_string );

	// Replace English month names with Arabic.
	$localized_date = str_replace( array_keys( $months ), $months, $localized_date );

	return $localized_date;
}

/**
 * Replace English time units with corresponding Arabic values.
 *
 * @param string $text The text containing English time units.
 * @return string The text with time units replaced by Arabic values.
 */
function snks_replace_time_units_to_arabic( $text ) {
	// Define an array with English time units as keys and Arabic values as their corresponding values.
	$english_to_arabic = array(
		'seconds' => 'ثواني',
		'second'  => 'ثانية',
		'minutes' => 'دقائق',
		'minute'  => 'دقيقة',
		'hours'   => 'ساعات',
		'hour'    => 'ساعة',
		'days'    => 'أيام',
		'day'     => 'يوم',
		'weeks'   => 'أسابيع',
		'week'    => 'أسبوع',
		'months'  => 'أشهر',
		'month'   => 'شهر',
		'years'   => 'سنوات',
		'year'    => 'سنة',
	);

	// Replace each occurrence of the English time units with the corresponding Arabic values.
	return str_replace( array_keys( $english_to_arabic ), array_values( $english_to_arabic ), $text );
}

/**
 * Generates a version 4 UUID.
 *
 * This function creates a universally unique identifier (UUID) version 4
 * based on random values. Each UUID generated will be unique and globally
 * identifiable.
 *
 * @return string The generated UUID in the format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.
 */
function snks_generate_uuid() {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		wp_rand( 0, 0xffff ),
		wp_rand( 0, 0xffff ),
		wp_rand( 0, 0xffff ),
		wp_rand( 0, 0x0fff ) | 0x4000,
		wp_rand( 0, 0x3fff ) | 0x8000,
		wp_rand( 0, 0xffff ),
		wp_rand( 0, 0xffff ),
		wp_rand( 0, 0xffff )
	);
}
