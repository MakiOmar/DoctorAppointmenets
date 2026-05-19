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
 * @param string $custom_ip Optional IP address to use instead of detecting from headers.
 * @return string
 */
function snks_get_country_code( $set_cookie = true, $custom_ip = null ) {
    // Use custom IP if provided, otherwise get the real client IP even behind proxies or Cloudflare
    if ( $custom_ip ) {
        $ip = filter_var( $custom_ip, FILTER_VALIDATE_IP );
    } else {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxies, Load balancers
            'HTTP_X_REAL_IP',        // Some reverse proxies
            'HTTP_CLIENT_IP',        // Generic
            'REMOTE_ADDR'            // Default fallback
        ];

        $ip = 'Unknown';
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip_list = explode( ',', $_SERVER[ $key ] ); // In case of multiple IPs
                $ip      = filter_var( trim( $ip_list[0] ), FILTER_VALIDATE_IP );
                if ( $ip ) {
                    break;
                }
            }
        }
    }


    // If no valid IP found, return Unknown
    if ( ! $ip ) {
        return 'Unknown';
    }

    // API key and URL for IP lookup
    $api_key = 'yBZHxURnxnHhONq';
    $api_url = sprintf( 'https://pro.ip-api.com/json/%s?key=%s&fields=countryCode', $ip, esc_attr( $api_key ) );

    // Send request to the IP API using wp_remote_get
    $response = wp_remote_get( $api_url );

    if ( is_wp_error( $response ) ) {
        return 'Unknown';
    }

    $country_codes = json_decode( COUNTRY_CURRENCIES, true );
    $country_code  = 'Unknown';

    // Retrieve the response body
    $body = wp_remote_retrieve_body( $response );

    $europe_country_codes = [
        'AL', 'AD', 'AM', 'AT', 'AZ', 'BY', 'BE', 'BA', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE',
        'FI', 'FR', 'GE', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'KZ', 'XK', 'LV', 'LI', 'LT',
        'LU', 'MT', 'MD', 'MC', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS',
        'SK', 'SI', 'ES', 'SE', 'CH', 'TR', 'UA', 'GB', 'VA'
    ];

    if ( ! empty( $body ) ) {
        $data = json_decode( $body );

        if ( $data && isset( $data->countryCode ) ) {
            $country_code = sanitize_text_field( $data->countryCode );

            if ( $set_cookie ) {
                if ( defined('IL_TO_EG') && IL_TO_EG && 'IL' === $country_code ) {
                    $country_code = 'EG';
                }

                // Store the country code in a cookie for 24 hours
                // Use SameSite=None for cross-site compatibility (frontend on separate domain)
                snks_set_cookie_with_partitioned( 'country_code', $country_code, time() + DAY_IN_SECONDS, '/', '', null, false, 'None' );

                // Determine stored currency
                if ( in_array( $country_code, array_keys( $country_codes ), true ) ) {
                    $stored_currency = $country_codes[ $country_code ];
                } else {
                    $stored_currency = in_array( $country_code, $europe_country_codes ) ? 'EUR' : 'USD';
                }

                if ( defined('IL_TO_EG') && IL_TO_EG && 'IL' === $country_code ) {
                    $stored_currency = 'EGP';
                }

                // Use SameSite=None for cross-site compatibility (frontend on separate domain)
                snks_set_cookie_with_partitioned( 'ced_selected_currency', $stored_currency, time() + DAY_IN_SECONDS, '/', '', null, false, 'None' );
            }

            if ( defined('IL_TO_EG') && IL_TO_EG && 'IL' === $country_code ) {
                $country_code = 'EG';
            }

            return $country_code;
        }
    }

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

/**
 * Delete cookie with all possible attribute combinations to prevent duplicates
 * This ensures old cookies with different attributes are removed before setting new ones
 *
 * @param string $name Cookie name.
 * @param string $path Cookie path.
 * @param string $domain Cookie domain (optional).
 */
function snks_delete_cookie_all_combinations( $name, $path = '/', $domain = '' ) {
	if ( headers_sent() ) {
		return false;
	}

	$past_time = time() - 3600; // Set expiration to past time to delete
	
	// Delete cookie with all possible attribute combinations
	$combinations = array(
		// Secure + SameSite=None + Partitioned
		array( 'secure' => true, 'samesite' => 'None', 'partitioned' => true ),
		// Secure + SameSite=None (no Partitioned)
		array( 'secure' => true, 'samesite' => 'None', 'partitioned' => false ),
		// Secure + SameSite=Lax + Partitioned
		array( 'secure' => true, 'samesite' => 'Lax', 'partitioned' => true ),
		// Secure + SameSite=Lax (no Partitioned)
		array( 'secure' => true, 'samesite' => 'Lax', 'partitioned' => false ),
		// Not Secure + SameSite=Lax
		array( 'secure' => false, 'samesite' => 'Lax', 'partitioned' => false ),
		// Not Secure (no SameSite)
		array( 'secure' => false, 'samesite' => '', 'partitioned' => false ),
		// Secure (no SameSite)
		array( 'secure' => true, 'samesite' => '', 'partitioned' => false ),
	);
	
	foreach ( $combinations as $combo ) {
		$cookie_parts = array(
			sprintf( '%s=', rawurlencode( $name ) ), // Empty value to delete
		);
		
		$cookie_parts[] = sprintf( 'expires=%s', gmdate( 'D, d M Y H:i:s \G\M\T', $past_time ) );
		
		if ( ! empty( $path ) ) {
			$cookie_parts[] = sprintf( 'path=%s', $path );
		}
		
		if ( ! empty( $domain ) ) {
			$cookie_parts[] = sprintf( 'domain=%s', $domain );
		}
		
		if ( $combo['secure'] ) {
			$cookie_parts[] = 'Secure';
		}
		
		if ( ! empty( $combo['samesite'] ) ) {
			$cookie_parts[] = sprintf( 'SameSite=%s', $combo['samesite'] );
		}
		
		if ( $combo['partitioned'] ) {
			$cookie_parts[] = 'Partitioned';
		}
		
		$cookie_header = implode( '; ', $cookie_parts );
		header( sprintf( 'Set-Cookie: %s', $cookie_header ), false );
	}
	
	// Also try standard setcookie() deletion (for cookies set without custom attributes)
	setcookie( $name, '', $past_time, $path, $domain );
	setcookie( $name, '', $past_time, $path, $domain, true ); // With Secure
	setcookie( $name, '', $past_time, $path, $domain, false, true ); // With HttpOnly
	
	return true;
}

/**
 * Set cookie with Partitioned attribute for cross-site compatibility
 *
 * @param string $name Cookie name.
 * @param string $value Cookie value.
 * @param int    $expires Expiration timestamp.
 * @param string $path Cookie path.
 * @param string $domain Cookie domain (optional).
 * @param bool   $secure Whether cookie should only be sent over HTTPS.
 * @param bool   $httponly Whether cookie should be HTTP-only.
 * @param string $samesite SameSite attribute value (Lax, Strict, None).
 */
function snks_set_cookie_with_partitioned( $name, $value, $expires = 0, $path = '/', $domain = '', $secure = null, $httponly = false, $samesite = 'None' ) {
	if ( headers_sent() ) {
		return false;
	}

	// CRITICAL: Delete existing cookies with all possible attribute combinations first
	// This prevents duplicate cookies when attributes change (e.g., Secure flag, SameSite, Partitioned)
	snks_delete_cookie_all_combinations( $name, $path, $domain );

	// Default secure to true if site is using SSL
	if ( $secure === null ) {
		$secure = is_ssl();
	}
	
	// For cross-site cookies, SameSite=None REQUIRES Secure flag
	// Browsers will reject SameSite=None cookies without Secure
	if ( $samesite === 'None' ) {
		// Force Secure=true when using SameSite=None (required by browsers)
		// If site is not HTTPS, browser will reject the cookie, but we set it correctly
		if ( ! $secure ) {
			error_log( sprintf( '[Cookie Warning] Cookie %s with SameSite=None requires Secure flag. Setting Secure=true. Site must use HTTPS for cross-site cookies to work.', $name ) );
		}
		$secure = true; // Always set Secure when SameSite=None
	}

	// Build cookie header manually to include Partitioned attribute
	$cookie_parts = array(
		sprintf( '%s=%s', rawurlencode( $name ), rawurlencode( $value ) ),
	);

	if ( $expires > 0 ) {
		$cookie_parts[] = sprintf( 'expires=%s', gmdate( 'D, d M Y H:i:s \G\M\T', $expires ) );
	}

	if ( ! empty( $path ) ) {
		$cookie_parts[] = sprintf( 'path=%s', $path );
	}

	if ( ! empty( $domain ) ) {
		$cookie_parts[] = sprintf( 'domain=%s', $domain );
	}

	if ( $secure ) {
		$cookie_parts[] = 'Secure';
	}

	if ( $httponly ) {
		$cookie_parts[] = 'HttpOnly';
	}

	if ( ! empty( $samesite ) ) {
		$cookie_parts[] = sprintf( 'SameSite=%s', $samesite );
	}

	// Add Partitioned attribute for cross-site compatibility
	$cookie_parts[] = 'Partitioned';

	$cookie_header = implode( '; ', $cookie_parts );
	
	// Use header() to set cookie with Partitioned attribute
	// Note: We don't call setcookie() because it would overwrite our custom header
	header( sprintf( 'Set-Cookie: %s', $cookie_header ), false );

	// Manually set $_COOKIE superglobal for backward compatibility
	// This allows PHP code to read the cookie value in the same request
	$_COOKIE[ $name ] = $value;

	return true;
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
 * Extract date_time string from supported session argument types.
 *
 * @param mixed $session Timetable row, array, or date_time string.
 * @return string
 */
function snks_get_session_date_time_string( $session ) {
	if ( is_object( $session ) ) {
		return isset( $session->date_time ) ? (string) $session->date_time : '';
	}

	if ( is_array( $session ) ) {
		return isset( $session['date_time'] ) ? (string) $session['date_time'] : '';
	}

	return (string) $session;
}

/**
 * Whether a parsed DateTime matches the source string for a given format.
 *
 * @param DateTime $datetime Parsed datetime.
 * @param string   $input    Original input.
 * @param string   $format   Format used for parsing.
 * @return bool
 */
function snks_datetime_matches_format( DateTime $datetime, $input, $format ) {
	$errors = DateTime::getLastErrors();

	if ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) {
		return false;
	}

	// 12-hour formats may differ in leading zeros; timestamp validation is sufficient.
	if ( false !== strpos( $format, 'a' ) || false !== strpos( $format, 'A' ) ) {
		return true;
	}

	return $datetime->format( $format ) === trim( $input );
}

/**
 * Parse session date_time in the WordPress timezone.
 *
 * @param string $date_time Raw date_time from DB.
 * @return DateTime|null
 */
function snks_parse_session_datetime( $date_time ) {
	$date_time = trim( (string) $date_time );

	if ( '' === $date_time ) {
		return null;
	}

	$timezone = wp_timezone();
	$formats  = array( 'Y-m-d H:i:s', 'Y-m-d h:i a', 'Y-m-d g:i a' );

	foreach ( $formats as $format ) {
		$booking_dt_obj = DateTime::createFromFormat( $format, $date_time, $timezone );
		if ( $booking_dt_obj instanceof DateTime && snks_datetime_matches_format( $booking_dt_obj, $date_time, $format ) ) {
			return $booking_dt_obj;
		}
	}

	try {
		return new DateTime( $date_time, $timezone );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Get the Unix timestamp for a session start time in the WordPress timezone.
 *
 * @param object|array|string $session Session row, array with date_time, or date_time string.
 * @return int Unix timestamp, or 0 when unavailable.
 */
function snks_get_session_start_timestamp( $session ) {
	$date_time = snks_get_session_date_time_string( $session );

	if ( '' === $date_time ) {
		return 0;
	}

	$booking_dt_obj = snks_parse_session_datetime( $date_time );

	return $booking_dt_obj instanceof DateTime ? $booking_dt_obj->getTimestamp() : 0;
}

/**
 * Get the Unix timestamp for session end (start + period minutes) in the WordPress timezone.
 *
 * @param object|string $session Session object or date_time string.
 * @param int|null      $period_minutes Optional period override in minutes.
 * @return int Unix timestamp, or 0 when unavailable.
 */
function snks_get_session_end_timestamp( $session, $period_minutes = null ) {
	$start = snks_get_session_start_timestamp( $session );

	if ( $start <= 0 ) {
		return 0;
	}

	if ( null === $period_minutes ) {
		if ( is_object( $session ) && isset( $session->period ) ) {
			$period_minutes = (int) $session->period;
		} elseif ( is_array( $session ) && isset( $session['period'] ) ) {
			$period_minutes = (int) $session['period'];
		} else {
			$period_minutes = 45;
		}
	}

	return $start + ( max( 0, (int) $period_minutes ) * MINUTE_IN_SECONDS );
}

/**
 * Current Unix timestamp aligned with WordPress timezone (site "now").
 *
 * @return int
 */
function snks_get_current_timestamp() {
	return current_datetime()->getTimestamp();
}

/**
 * Full timing state for a session (single source of truth for PHP + data attributes).
 *
 * @param object|string $session Session row or date_time string.
 * @return array{
 *     start: int,
 *     end: int,
 *     now: int,
 *     diff_seconds: int,
 *     is_too_early: bool,
 *     is_active: bool,
 *     is_ended: bool
 * }
 */
function snks_get_session_timing( $session ) {
	$start = snks_get_session_start_timestamp( $session );
	$end   = snks_get_session_end_timestamp( $session );
	$now   = snks_get_current_timestamp();

	if ( $start <= 0 ) {
		return array(
			'start'        => 0,
			'end'          => 0,
			'now'          => $now,
			'diff_seconds' => 0,
			'is_too_early' => false,
			'is_active'    => false,
			'is_ended'     => false,
		);
	}

	if ( $end <= 0 ) {
		$end = $start;
	}

	$diff               = $start - $now;
	$seconds_since_start = max( 0, $now - $start );

	return array(
		'start'               => $start,
		'end'                 => $end,
		'now'                 => $now,
		'diff_seconds'        => $diff,
		'seconds_since_start' => $seconds_since_start,
		'is_too_early'        => $diff > 0,
		'is_active'           => $now >= $start && $now < $end,
		'is_ended'            => $now >= $end,
	);
}

/**
 * Whether the session start time is still in the future.
 *
 * @param object|string $session Session row or date_time string.
 * @return bool
 */
function snks_is_session_too_early( $session ) {
	return snks_get_session_timing( $session )['is_too_early'];
}

/**
 * Whether the session start time has passed (includes currently active and ended).
 *
 * @param object|string $session Session row or date_time string.
 * @return bool
 */
function snks_is_session_started( $session ) {
	$timing = snks_get_session_timing( $session );

	return $timing['start'] > 0 && $timing['diff_seconds'] <= 0;
}

/**
 * Whether the session is in progress (started, not yet ended).
 *
 * @param object|string $session Session row or date_time string.
 * @return bool
 */
function snks_is_session_active( $session ) {
	return snks_get_session_timing( $session )['is_active'];
}

/**
 * Whether the session end time has passed.
 *
 * @param object|string $session Session row or date_time string.
 * @return bool
 */
function snks_is_session_ended( $session ) {
	return snks_get_session_timing( $session )['is_ended'];
}

/**
 * HTML data attributes for booking cards (timers / JS use server timestamps).
 *
 * @param object|array $session Timetable row or array with date_time and period.
 * @return string
 */
function snks_session_timing_data_attrs( $session ) {
	$timing    = snks_get_session_timing( $session );
	$date_time = snks_get_session_date_time_string( $session );

	if ( is_object( $session ) && isset( $session->period ) ) {
		$period = (int) $session->period;
	} elseif ( is_array( $session ) && isset( $session['period'] ) ) {
		$period = (int) $session['period'];
	} else {
		$period = 45;
	}

	return sprintf(
		'data-datetime="%s" data-start-ts="%d" data-end-ts="%d" data-period="%d"',
		esc_attr( $date_time ),
		(int) $timing['start'],
		(int) $timing['end'],
		$period
	);
}

/**
 * Format session date (Y-m-d) using WordPress timezone.
 *
 * @param mixed  $session Session row, array, or date_time string.
 * @param string $format PHP date format.
 * @return string
 */
function snks_format_session_datetime( $session, $format = 'Y-m-d H:i:s' ) {
	$date_time = snks_get_session_date_time_string( $session );
	$parsed    = snks_parse_session_datetime( $date_time );

	if ( ! $parsed instanceof DateTime ) {
		return '';
	}

	return $parsed->format( $format );
}

/**
 * Calculate the time difference in seconds between the current time and a session's time.
 *
 * @param object|array|string $session Session row, array with date_time, or date_time string.
 * @return int Time difference in seconds. Positive for future sessions, negative for past sessions.
 */
function snks_diff_seconds( $session ) {
	return snks_get_session_timing( $session )['diff_seconds'];
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
		'minutes' => 'دقيقة',
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
 * Create or update a custom log file on a daily basis.
 * Logs are written to wp-content/team-log-YYYY-MM-DD.log (one file per day).
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

	// Daily log file: team-log-YYYY-MM-DD.log
	$date_suffix   = current_time( 'Y-m-d' );
	$log_file_path = WP_CONTENT_DIR . '/team-log-' . $date_suffix . '.log';

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
		return get_option( 'snks_ai_appointment_change_terms_ar', 'يرجى العلم أنه يمكنك تغيير موعد الحجز مره واحده فقط بعد الحجز بحد أقصى 24 ساعة قبل موعد الجلسة المحجوزه، ولا يمكن تغييرها بعد ذلك، كمان أن قيمة الحجز غير مستردة.' );
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

	return snks_diff_seconds( $appointment ) > DAY_IN_SECONDS;
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

	return snks_diff_seconds( $appointment );
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
	
	// Check if this is an AI booking.
	if ( ! isset( $appointment->settings ) || strpos( $appointment->settings, 'ai_booking' ) === false ) {
		return true;
	}

	return snks_diff_seconds( $appointment ) > DAY_IN_SECONDS;
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
	
	if ( ! isset( $appointment->settings ) || strpos( $appointment->settings, 'ai_booking' ) === false ) {
		return 0;
	}

	return snks_diff_seconds( $appointment ) - DAY_IN_SECONDS;
}

/**
 * Sanitize Arabic text for ChatGPT API
 * Removes diacritics, formatting characters, and normalizes Unicode
 * This prevents confusion in ChatGPT model with Arabic text containing special characters
 *
 * @param string $text The Arabic text to sanitize
 * @return string The sanitized text
 */
function snks_sanitize_arabic_text( $text ) {
	if ( empty( $text ) || ! is_string( $text ) ) {
		return $text;
	}

	// Normalize Unicode to NFC form (Canonical Decomposition, followed by Canonical Composition)
	// This ensures consistent character representation
	if ( class_exists( 'Normalizer' ) && method_exists( 'Normalizer', 'normalize' ) ) {
		try {
			$text = Normalizer::normalize( $text, Normalizer::FORM_C );
		} catch ( Exception $e ) {
			// If normalization fails, continue without it
		}
	}

	// Remove Arabic diacritics/vowel marks (harakat)
	// Range: \u064B-\u065F (Arabic diacritics), \u0670 (Arabic letter superscript alef)
	// Range: \u06D6-\u06ED (Arabic diacritics)
	$text = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text );

	// Remove bidirectional formatting characters (can confuse text processing)
	// \u200E (LEFT-TO-RIGHT MARK), \u200F (RIGHT-TO-LEFT MARK)
	// \u202A-\x{202E} (Bidirectional text formatting)
	$text = preg_replace( '/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $text );

	// Remove Arabic tatweel (kashida) character (ـ) - Unicode U+0640
	// Used for text justification in Arabic
	$text = preg_replace( '/\x{0640}/u', '', $text );

	// Remove carriage returns (\r) and newlines (\n)
	$text = str_replace( array( "\r\n", "\r", "\n" ), ' ', $text );

	// Normalize whitespace: replace multiple spaces/tabs with single space
	$text = preg_replace( '/\s+/u', ' ', $text );

	// Trim leading and trailing whitespace
	$text = trim( $text );

	return $text;
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
	
	$session_start_time = snks_get_session_start_timestamp( $session_date_time );
	if ( $session_start_time <= 0 ) {
		return array(
			'success' => true,
			'message' => '',
		);
	}

	$minutes_passed = ( snks_get_current_timestamp() - $session_start_time ) / 60;
	
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

