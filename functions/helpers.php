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
		// Check if the property exists before accessing it
		if ( ! isset( $object->$member_name ) ) {
			// If property doesn't exist, use a default key
			$key = 'unknown';
		} else {
			$key = $object->$member_name;
		}
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

	if ( ! snks_is_doctor() ) {
		return;
	}
	$referer = add_query_arg( 'id', snks_get_settings_doctor_id(), home_url( '/account-setting' ) );
	// Sanitize the URL for use in an HTML attribute.
	$referer_safe = $referer;

	ob_start();
	if ( is_page( 'register' ) ) {
		?>
		<style>
			.anony-go-back{
				display: none;
			}
		</style>
		<?php
	}
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
	// Get all possible IP headers to check for VPN/proxy
	$ip_headers = array(
		'HTTP_CF_CONNECTING_IP',     // Cloudflare
		'HTTP_CLIENT_IP',            // Proxy
		'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
		'HTTP_X_FORWARDED',          // Proxy
		'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
		'HTTP_FORWARDED_FOR',        // Proxy
		'HTTP_FORWARDED',            // Proxy
		'REMOTE_ADDR'                // Standard
	);
	
	$ip = 'Unknown';
	foreach ( $ip_headers as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$candidate_ip = filter_var( $_SERVER[ $header ], FILTER_VALIDATE_IP );
			if ( $candidate_ip ) {
				$ip = $candidate_ip;
				error_log( '🌍 Country Detection - Found IP in header ' . $header . ': ' . $ip );
				break;
			}
		}
	}
	
	error_log( '🌍 Country Detection - Final IP Address: ' . $ip );
	
	// Log all available IP headers for debugging
	error_log( '🌍 Country Detection - All IP Headers: ' . print_r( array_map( function( $header ) {
		return $header . ': ' . ( $_SERVER[ $header ] ?? 'Not Set' );
	}, $ip_headers ), true ) );
	
	// If the IP address is not valid, return early.
	if ( $ip === 'Unknown' ) {
		error_log( '🌍 Country Detection - No valid IP address found' );
		return 'Unknown';
	}

	// API key and URL for IP lookup.
	$api_key = 'yBZHxURnxnHhONq'; // Replace with your actual API key.
	$api_url = sprintf( 'https://pro.ip-api.com/json/%s?key=%s&fields=countryCode', $ip, esc_attr( $api_key ) );

	error_log( '🌍 Country Detection - API URL: ' . $api_url );

	// Send request to the IP API using wp_remote_get.
	$response = wp_remote_get( $api_url );

	// Check for errors and validate the response.
	if ( is_wp_error( $response ) ) {
		error_log( '🌍 Country Detection - API Error: ' . $response->get_error_message() );
		return 'Unknown'; // Early return if there's an error in the response.
	}
	$country_codes = json_decode( COUNTRY_CURRENCIES, true );
	$country_code  = 'Unknown';
	// Retrieve the response body.
	$body                 = wp_remote_retrieve_body( $response );
	error_log( '🌍 Country Detection - API Response Body: ' . $body );
	
	$europe_country_codes = array( // phpcs:disable
			'AL', 'AD', 'AM', 'AT', 'AZ', 'BY', 'BE', 'BA', 'BG', 'HR',
			'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'GE', 'DE', 'GR', 'HU',
			'IS', 'IE', 'IT', 'KZ', 'XK', 'LV', 'LI', 'LT', 'LU', 'MT',
			'MD', 'MC', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU',
			'SM', 'RS', 'SK', 'SI', 'ES', 'SE', 'CH', 'TR', 'UA', 'GB', 'VA'
		);
		// phpcs:enable
	// Check if the body is not empty and contains serialized data.
	if ( ! empty( $body ) ) {
		$data = json_decode( $body ); // Using @ to suppress potential warnings.
		error_log( '🌍 Country Detection - Parsed JSON Data: ' . print_r( $data, true ) );
		
		// Check if the country code is present.
		if ( $data && isset( $data->countryCode ) ) { //phpcs:disable
			$country_code = sanitize_text_field( $data->countryCode );
			//phpcs:enable
			error_log( '🌍 Country Detection - Extracted Country Code: ' . $country_code );
			
			if ( $set_cookie ) {
				// Store the country code in a cookie for 24 hours.
				setcookie( 'country_code', $country_code, time() + DAY_IN_SECONDS, '/' ); // DAY_IN_SECONDS is a WordPress constant.
				if ( in_array( $country_code, array_keys( $country_codes ), true ) ) {
					$stored_currency = $country_codes[ $country_code ];
				} else {
					$stored_currency = in_array( $country_code, $europe_country_codes ) ? 'EUR' : 'USD';
				}
				setcookie( 'ced_selected_currency', $stored_currency, time() + DAY_IN_SECONDS, '/' ); // DAY_IN_SECONDS is a WordPress constant.
			}

			error_log( '🌍 Country Detection - Final Result: ' . $country_code );
			return $country_code;
		} else {
			error_log( '🌍 Country Detection - No countryCode in response data' );
		}
	} else {
		error_log( '🌍 Country Detection - Empty response body' );
	}
	
	error_log( '🌍 Country Detection - Returning Unknown' );
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
	'wp',
	function () {
		// Check if the country code is already stored in a cookie.
		//if ( ! isset( $_COOKIE['country_code'] ) ) {
			snks_get_country_code( true );
		//}
	}
);

/**
 * Get doctor's URL using nickname
 *
 * @param mixed $user User object or User ID.
 * @return string
 */
function snks_encrypted_doctor_url( $user ) {
	// If $user is a WP_User object.
	if ( is_a( $user, 'WP_User' ) && $user ) {
		$nickname = get_user_meta( $user->ID, 'nickname', true );
		if ( $nickname ) {
			return site_url( '/therapist/' . $nickname );
		}
	}

	// If $user is a user ID.
	$user_obj = get_user_by( 'id', absint( $user ) );
	if ( $user_obj ) {
		$nickname = get_user_meta( $user_obj->ID, 'nickname', true );
		if ( $nickname ) {
			return site_url( '/therapist/' . $nickname );
		}
	}

	// Default URL if no valid user or nickname is found.
	return site_url( '/account-settings' );
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
		$user = get_user_by_nickname( $wp->query_vars['doctor_id'] );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}
	return $user_id;
}
/**
 * Validate if the given input is a phone number.
 *
 * @param string $username The input to validate.
 * @return bool True if valid phone number, false otherwise.
 */
function validate_phone_number( $username ) {
	// Sanitize the input.
	$username = sanitize_text_field( $username );

	// Regular expression for phone number validation (e.g., allows digits, spaces, and '+' for international format).
	$pattern = '/^\+?[0-9\s]*$/';

	// Return true if the input matches the pattern, false otherwise.
	return (bool) preg_match( $pattern, $username );
}
/**
 * Get a user by their nickname.
 *
 * @param string $nickname The nickname to search for.
 * @return WP_User|false The user object if found, or false if not.
 */
function get_user_by_nickname( $nickname ) {
	// Sanitize the nickname input.
	$nickname = sanitize_text_field( $nickname );

	// Query users with the given nickname.
	$user_query = new WP_User_Query(
		array(
			'meta_key'   => 'nickname',
			'meta_value' => $nickname,
			'number'     => 1, // Limit to 1 user.
		)
	);

	// Get the results.
	$users = $user_query->get_results();

	// Return the first user if found, otherwise false.
	return ! empty( $users ) ? $users[0] : false;
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
	$translations = array(
		'Saturday'  => 'السبت',
		'Sunday'    => 'الأحد',
		'Monday'    => 'الإثنين',
		'Tuesday'   => 'الثلاثاء',
		'Wednesday' => 'الأربعاء',
		'Thursday'  => 'الخميس',
		'Friday'    => 'الجمعة',
		'Mon'       => 'الإثنين',
		'Tue'       => 'الثلاثاء',
		'Wed'       => 'الأربعاء',
		'Thu'       => 'الخميس',
		'Fri'       => 'الجمعة',
		'Sat'       => 'السبت',
		'Sun'       => 'الأحد',
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
	$localized_date = str_replace( array_keys( $translations ), $translations, $date_string );
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

/**
 * Get Bank Names for Dropdowns
 *
 * @return array
 */
function get_bank_list() {
	return array(
		''     => 'حدد البنك',
		'NBE'  => 'البنك الأهلي المصري',
		'MISR' => 'بنك مصر',
		'BDC'  => 'بنك القاهرة',
		'BOA'  => 'بنك الإسكندرية',
		'CIB'  => 'CIB',
		'ADIB' => 'مصرف أبوظبي الإسلامي',
		'AAIB' => 'البنك العربي الإفريقي الدولي',
		'POST' => 'البريد المصري',
		'EALB' => 'البنك العقاري المصري العربي',
		'EGB'  => 'EG bank',
		'EDBE' => 'البنك المصري لتنمية الصادرات',
		'FAIB' => 'بنك فيصل الإسلامي',
		'HDB'  => 'بنك التعمير والإسكان',
		'IDB'  => 'بنك التنمية الصناعية',
		'SCB'  => 'بنك قناة السويس',
		'AUB'  => 'البنك الأهلي المتحد',
		'ABK'  => 'البنك الأهلي الكويتي',
		'ABRK' => 'بنك البركة',
		'ARAB' => 'البنك العربي',
		'ABC'  => 'ABC',
		'ARIB' => 'المصرف العربي الدولي',
		'AIB'  => 'AIB',
		'BBE'  => 'بنك التجاري وفا',
		'AUDI' => 'بنك عوده',
		'BLOM' => 'بنك بلوم',
		'CITI' => 'Citibank',
		'CAE'  => 'كريدي أجريكول',
		'ENBD' => 'بنك الإمارات دبي الوطني',
		'FAB'  => 'بنك أبوظبي الأول',
		'HSBC' => 'HSBC',
		'MASH' => 'بنك المشرق',
		'MIDB' => 'MIDBank',
		'NSB'  => 'بنك ناصر الاجتماعي',
		'NBG'  => 'البنك الأهلي اليوناني',
		'NBK'  => 'بنك الكويت الوطني',
		'QNB'  => 'QNB',
		'SAIB' => 'SAIB',
		'PDAC' => 'The Principal Bank For Development And Agri',
		'UB'   => 'المصرف المتحد',
		'UNB'  => 'بنك الإتحاد الوطني',
	);
}
/**
 * Create or update a custom log file called 'team-log.log' in WordPress.
 *
 * @param string $message The message to be logged.
 */
function teamlog( $message ) {
	// Load the WordPress Filesystem API.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	// Initialize the WP_Filesystem.
	global $wp_filesystem;
	if ( is_null( $wp_filesystem ) ) {
		WP_Filesystem();
	}

	// Set the log file path inside the wp-content directory.
	$log_file_path = WP_CONTENT_DIR . '/team-log.log';

	// Format the log entry with a timestamp.
	$log_entry = '[' . current_time( 'Y-m-d H:i:s' ) . '] ' . print_r( $message, true ) . PHP_EOL;

	// Check if the file exists.
	if ( ! $wp_filesystem->exists( $log_file_path ) ) {
		// If the file doesn't exist, create it and add the first log entry.
		$wp_filesystem->put_contents( $log_file_path, $log_entry );
	} else {
		// If the file exists, append the log entry.
		$existing_log = $wp_filesystem->get_contents( $log_file_path );
		$wp_filesystem->put_contents( $log_file_path, $existing_log . $log_entry );
	}
}
/**
 * Apply timetable settings dynamically
 *
 * @param int|false $user_id User's ID otherwise,false.
 * @param int       $start_offset Number of days from today to start generating appointments.
 * @param int       $days_count Number of appointment dates to generate.
 * @return void
 */
function apply_timetable_settings( $user_id = false, $start_offset = 0, $days_count = 90 ) {
	$timetables = snks_generate_timetable( $start_offset, $days_count );
	if ( is_array( $timetables ) ) {
		snks_set_preview_timetable( $timetables, $user_id );
	}
}
/**
 * Automatically publishes appointments for a user.
 *
 * @param int $user_id The user ID.
 */
function snks_auto_publish_appointments( $user_id ) {
	$day_timetables     = snks_generate_timetable( 91, 8, $user_id );
	$preview_timetables = snks_get_preview_timetable( $user_id );

	if ( ! is_array( $preview_timetables ) ) {
		return;
	}

	foreach ( $day_timetables as $day => $timetables ) {
		foreach ( $timetables as $timetable ) {
			$temp = $timetable;
			unset( $timetable['date'] );
			snks_insert_timetable( $timetable, $user_id );

			if ( isset( $preview_timetables[ $day ] ) ) {
				// Check if $temp already exists in the array.
				$exists = false;
				foreach ( $preview_timetables[ $day ] as $existing_timetable ) {
					if ( $existing_timetable === $temp ) {
						$exists = true;
						break;
					}
				}
				if ( ! $exists ) {
					$preview_timetables[ $day ][] = $temp;
				}
			} else {
				$preview_timetables[ $day ] = array( $temp );
			}
		}
	}

	snks_set_preview_timetable( $preview_timetables, $user_id );
}

/**
 * Check if bilingual support is enabled
 *
 * @return bool
 */
function snks_is_bilingual_enabled() {
	// Force refresh cache for this option
	wp_cache_delete( 'snks_ai_bilingual_enabled', 'options' );
	return get_option( 'snks_ai_bilingual_enabled', '1' ) === '1';
}

/**
 * Get the default language for the AI site
 *
 * @return string 'ar' for Arabic, 'en' for English
 */
function snks_get_default_language() {
	// Force refresh cache for this option
	wp_cache_delete( 'snks_ai_default_language', 'options' );
	return get_option( 'snks_ai_default_language', 'ar' );
}

/**
 * Get the current language for the AI site
 * If bilingual is disabled, returns the default language
 * If bilingual is enabled, tries to get from user preference or defaults to default language
 *
 * @return string 'ar' for Arabic, 'en' for English
 */
function snks_get_current_language() {
	if ( ! snks_is_bilingual_enabled() ) {
		return snks_get_default_language();
	}
	
	// Try to get from user preference (localStorage in frontend)
	if ( isset( $_GET['locale'] ) ) {
		$locale = sanitize_text_field( $_GET['locale'] );
		if ( in_array( $locale, array( 'ar', 'en' ) ) ) {
			return $locale;
		}
	}
	
	// Try to get from session/cookie
	if ( isset( $_COOKIE['jalsah_locale'] ) ) {
		$locale = sanitize_text_field( $_COOKIE['jalsah_locale'] );
		if ( in_array( $locale, array( 'ar', 'en' ) ) ) {
			return $locale;
		}
	}
	
	// Default to the configured default language
	return snks_get_default_language();
}

/**
 * Get bilingual site title
 *
 * @param string $locale Optional locale, defaults to current language
 * @return string
 */
function snks_get_site_title( $locale = null ) {
	if ( ! $locale ) {
		$locale = snks_get_current_language();
	}
	
	if ( $locale === 'ar' ) {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_site_title_ar', 'options' );
		return get_option( 'snks_ai_site_title_ar', 'جلسة الذكية - دعم الصحة النفسية' );
	} else {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_site_title_en', 'options' );
		return get_option( 'snks_ai_site_title_en', 'Jalsah AI - Mental Health Support' );
	}
}

/**
 * Get bilingual site description
 *
 * @param string $locale Optional locale, defaults to current language
 * @return string
 */
function snks_get_site_description( $locale = null ) {
	if ( ! $locale ) {
		$locale = snks_get_current_language();
	}
	
	if ( $locale === 'ar' ) {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_site_description_ar', 'options' );
		return get_option( 'snks_ai_site_description_ar', 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.' );
	} else {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_site_description_en', 'options' );
		return get_option( 'snks_ai_site_description_en', 'Professional AI-powered mental health support and therapy sessions.' );
	}
}

/**
 * Get diagnosis results limit
 *
 * @return int
 */
function snks_get_diagnosis_results_limit() {
	// Force refresh cache for this option
	wp_cache_delete( 'snks_ai_diagnosis_results_limit', 'options' );
	$limit = get_option( 'snks_ai_diagnosis_results_limit', 10 );
	
	return intval( $limit );
}

/**
 * Get show more button enabled setting
 *
 * @return bool
 */
function snks_get_show_more_button_enabled() {
	// Force refresh cache for this option
	wp_cache_delete( 'snks_ai_show_more_button_enabled', 'options' );
	$enabled = get_option( 'snks_ai_show_more_button_enabled', '1' );
	
	return $enabled === '1';
}

/**
 * Get bilingual appointment change terms
 *
 * @param string $locale Optional locale, defaults to current language
 * @return string
 */
function snks_get_appointment_change_terms( $locale = null ) {
	if ( ! $locale ) {
		$locale = snks_get_current_language();
	}
	
	if ( $locale === 'ar' ) {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_appointment_change_terms_ar', 'options' );
		return get_option( 'snks_ai_appointment_change_terms_ar', 'يمكنك تغيير موعدك مرة واحدة فقط قبل الموعد الحالي بـ 24 ساعة فقط، وليس بعد ذلك. تغيير الموعد مجاني.' );
	} else {
		// Force refresh cache for this option
		wp_cache_delete( 'snks_ai_appointment_change_terms_en', 'options' );
		return get_option( 'snks_ai_appointment_change_terms_en', 'You can only change your appointment once before the current appointment by 24 hours only, not after. Change appointment is free.' );
	}
}

/**
 * Check if appointment can be rescheduled or cancelled (24 hours before)
 *
 * @param object $appointment Appointment object
 * @return bool
 */
function snks_can_modify_appointment( $appointment ) {
	if ( ! $appointment || ! isset( $appointment->date_time ) ) {
		return false;
	}
	
	$appointment_time = strtotime( $appointment->date_time );
	$current_time = current_time( 'timestamp' );
	
	// Can modify up to 24 hours before (86400 seconds = 24 hours)
	return ( $appointment_time - $current_time ) > 86400;
}

/**
 * Get time remaining until appointment (in seconds)
 *
 * @param object $appointment Appointment object
 * @return int Seconds remaining, negative if appointment has passed
 */
function snks_get_appointment_time_remaining( $appointment ) {
	if ( ! $appointment || ! isset( $appointment->date_time ) ) {
		return 0;
	}
	
	$appointment_time = strtotime( $appointment->date_time );
	$current_time = current_time( 'timestamp' );
	
	return $appointment_time - $current_time;
}

/**
 * Check if AI appointment can be edited (24 hours before)
 *
 * @param object $appointment Appointment object
 * @return bool
 */
function snks_can_edit_ai_appointment( $appointment ) {
	if ( ! $appointment || ! isset( $appointment->date_time ) ) {
		return false;
	}
	
	// Check if this is an AI booking
	if ( strpos( $appointment->settings, 'ai_booking' ) === false ) {
		return true; // Not an AI booking, use regular validation
	}
	
	$appointment_time = strtotime( $appointment->date_time );
	$current_time = current_time( 'timestamp' );
	
	// AI appointments can be edited up to 24 hours before (86400 seconds = 24 hours)
	return ( $appointment_time - $current_time ) > 86400;
}

/**
 * Get AI appointment edit time remaining (in seconds)
 *
 * @param object $appointment Appointment object
 * @return int Seconds remaining until edit deadline, negative if past deadline
 */
function snks_get_ai_appointment_edit_time_remaining( $appointment ) {
	if ( ! $appointment || ! isset( $appointment->date_time ) ) {
		return 0;
	}
	
	// Check if this is an AI booking
	if ( strpos( $appointment->settings, 'ai_booking' ) === false ) {
		return 0; // Not an AI booking, use regular validation
	}
	
	$appointment_time = strtotime( $appointment->date_time );
	$current_time = current_time( 'timestamp' );
	
	// Return time remaining until 24 hours before appointment
	return ( $appointment_time - $current_time ) - 86400;
}

/**
 * Validate 15-minute rule for marking patient absence
 * 
 * @param string $session_date_time Session date and time
 * @param string $attendance Attendance status ('yes' or 'no')
 * @return array Validation result with success status and error message
 */
function snks_validate_absence_15_minute_rule($session_date_time, $attendance = 'yes') {
	// If marking as attended, no validation needed
	if ($attendance === 'yes') {
		return array(
			'success' => true,
			'message' => ''
		);
	}
	
	// Check if 15 minutes have passed since the session start time
	$session_start_time = strtotime($session_date_time);
	$current_time = current_time('timestamp');
	$minutes_passed = ($current_time - $session_start_time) / 60;
	
	if ($minutes_passed < 15) {
		$remaining_minutes = ceil(15 - $minutes_passed);
		return array(
			'success' => false,
			'message' => sprintf(
				'Cannot mark patient as absent yet. Please wait %d more minute(s) before ending the session due to patient absence.',
				$remaining_minutes
			),
			'minutes_passed' => round($minutes_passed, 1),
			'required_minutes' => 15,
			'remaining_minutes' => $remaining_minutes
		);
	}
	
	return array(
		'success' => true,
		'message' => ''
	);
}

