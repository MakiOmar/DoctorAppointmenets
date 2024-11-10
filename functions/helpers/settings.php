<?php
/**
 * Settings helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Validate doctor settings and store all messages in a transient.
 *
 * If validation fails, store all messages in a transient.
 *
 * @param int $user_id User ID to retrieve doctor settings for.
 * @return bool False if validation fails, true otherwise.
 */
function snks_validate_doctor_settings( $user_id ) {
	$settings = snks_doctor_settings( $user_id );
	$pricings = snks_doctor_pricings( $user_id );
	$messages = array();

	// Check if attendance_type is empty.
	if ( empty( $settings['attendance_type'] ) ) {
		$messages[] = ' طريقة استخدام التطبيق غير محددة.';
	}

	// Check if attendance_type equals "both" or "offline" and clinics_list is empty.
	if (
		( 'both' === $settings['attendance_type'] || 'offline' === $settings['attendance_type'] ) &&
		empty( $settings['clinics_list'] )
	) {
		$messages[] = 'يجب إدخال قائمة العيادات عند اختيار نوع الحضور "أوفلاين فقط" أو "أونلاين وأوفلاين". على الأقل إسم العيادة.';
	}
	// Check if none of 60_minutes, 45_minutes, or 30_minutes are "on".
	if (
		( empty( $settings['60_minutes'] ) || 'false' === $settings['60_minutes'] ) &&
		( empty( $settings['45_minutes'] ) || 'false' === $settings['45_minutes'] ) &&
		( empty( $settings['30_minutes'] ) || 'false' === $settings['30_minutes'] )
	) {
		$messages[] = 'لم يتم تفعيل أي من مدد الجلسات (30، 45، 60 دقيقة).';
	}
	// Check if the corresponding 'others' value is not empty for the active time settings.
	if ( ! empty( $settings['30_minutes'] ) && 'false' !== $settings['30_minutes'] && empty( $pricings[30]['others'] ) ) {
		$messages[] = 'سعر الجلسات لمدة 30 دقيقة غير موجود.';
	}
	if ( ! empty( $settings['45_minutes'] ) && 'false' !== $settings['45_minutes'] && empty( $pricings[45]['others'] ) ) {
		$messages[] = 'سعر الجلسات لمدة 45 دقيقة غير موجود.';
	}
	if ( ! empty( $settings['60_minutes'] ) && 'false' !== $settings['60_minutes'] && empty( $pricings[60]['others'] ) ) {
		$messages[] = 'سعر الجلسات لمدة 60 دقيقة غير موجود.';
	}

	// If there are any messages, set the transient with the messages for the current user.
	if ( ! empty( $messages ) ) {
		set_transient( 'snks_doctor_message_' . $user_id, $messages, 60 );
		return false;
	}

	// Clear any existing transient if validation passes.
	delete_transient( 'snks_doctor_message_' . $user_id );

	return true;
}


/**
 * Return doctor settings
 *
 * @param mixed $user_id User's ID or false for current user.
 * @return array An array of settings if is a doctor.
 */
function snks_doctor_settings( $user_id = false ) {
	$settings = array();
	if ( ! $user_id ) {
		if ( snks_is_doctor() || current_user_can( 'manage_options' ) ) {
			$user_id = get_current_user_id();
		}
	}
	$settings['60_minutes']                = get_user_meta( $user_id, '60-minutes', true );
	$settings['45_minutes']                = get_user_meta( $user_id, '45-minutes', true );
	$settings['30_minutes']                = get_user_meta( $user_id, '30-minutes', true );
	$settings['enable_discount']           = get_user_meta( $user_id, 'enable_discount', true );
	$settings['discount_percent_30']       = get_user_meta( $user_id, 'discount_percent_30', true );
	$settings['discount_percent_45']       = get_user_meta( $user_id, 'discount_percent_45', true );
	$settings['discount_percent_60']       = get_user_meta( $user_id, 'discount_percent_60', true );
	$settings['to_be_old_number']          = get_user_meta( $user_id, 'to_be_old_number', true );
	$settings['to_be_old_unit']            = get_user_meta( $user_id, 'to_be_old_unit', true );
	$settings['allow_appointment_change']  = get_user_meta( $user_id, 'allow_appointment_change', true );
	$settings['free_change_before_number'] = get_user_meta( $user_id, 'free_change_before_number', true );
	$settings['free_change_before_unit']   = get_user_meta( $user_id, 'free_change_before_unit', true );
	$settings['appointment_change_fee']    = get_user_meta( $user_id, 'appointment_change_fee', true );
	$settings['before_change_number']      = get_user_meta( $user_id, 'before_change_number', true );
	$settings['before_change_unit']        = get_user_meta( $user_id, 'before_change_unit', true );
	$settings['block_if_before_number']    = get_user_meta( $user_id, 'block_if_before_number', true );
	$settings['block_if_before_unit']      = get_user_meta( $user_id, 'block_if_before_unit', true );
	$settings['attendance_type']           = get_user_meta( $user_id, 'attendance_type', true );
	$settings['clinics_list']              = get_user_meta( $user_id, 'clinics_list', true );
	$settings['form_days_count']           = get_user_meta( $user_id, 'form_days_count', true );
	$settings['off_days']                  = get_user_meta( $user_id, 'off_days', true );

	return $settings;
}

/**
 * Inserts preview timetables into the database.
 *
 * @param mixed $user_id user ID or false.
 * @return array $errors Array of timetables that failed to insert.
 */
function snks_insert_preview_timetables( $user_id = false ) {
	$preview_timetables = snks_get_preview_timetable( $user_id );
	$errors             = array();

	if ( $preview_timetables && ! empty( $preview_timetables ) ) {
		foreach ( $preview_timetables as $preview_timetable ) {
			foreach ( $preview_timetable as $data ) {
				$dtime  = gmdate( 'Y-m-d H:i:s', strtotime( $data['date_time'] ) );
				$exists = snks_timetable_exists( get_current_user_id(), $dtime, $data['day'], $data['starts'], $data['ends'] );

				if ( empty( $exists ) ) {
					$inserting = array(
						'user_id'         => $data['user_id'],
						'session_status'  => $data['session_status'],
						'day'             => $data['day'],
						'base_hour'       => $data['base_hour'],
						'period'          => $data['period'],
						'date_time'       => $dtime,
						'starts'          => $data['starts'],
						'ends'            => $data['ends'],
						'clinic'          => $data['clinic'],
						'attendance_type' => $data['attendance_type'],
					);

					$inserted = snks_insert_timetable( $inserting );

					if ( ! $inserted ) {
						$errors[] = $data;
					}
				}
			}
		}
	}

	return $errors;
}

/**
 * Get doctor's available periods
 *
 * @param int    $user_id User's ID.
 * @param string $attendance_type Attendance type.
 * @return array
 */
function snks_get_available_periods( $user_id = false, $attendance_type = 'both' ) {
	$settings     = snks_doctor_settings( $user_id );
	$is_available = array();
	if ( snks_has_sessions_of_type( $user_id, $attendance_type, 60 ) && ( 'on' === $settings['60_minutes'] || 'true' === $settings['60_minutes'] ) ) {
		$is_available[] = 60;
	}
	if ( snks_has_sessions_of_type( $user_id, $attendance_type, 45 ) && ( 'on' === $settings['45_minutes'] || 'true' === $settings['45_minutes'] ) ) {
		$is_available[] = 45;
	}
	if ( snks_has_sessions_of_type( $user_id, $attendance_type, 30 ) && ( 'on' === $settings['30_minutes'] || 'true' === $settings['30_minutes'] ) ) {
		$is_available[] = 30;
	}
	sort( $is_available );
	return $is_available;
}
/**
 * Get doctor's periods
 *
 * @param int $user_id User's ID.
 * @return array
 */
function snks_get_periods( $user_id = false ) {
	$settings     = snks_doctor_settings( $user_id );
	$is_available = array();
	if ( ( 'on' === $settings['60_minutes'] || 'true' === $settings['60_minutes'] ) ) {
		$is_available[] = 60;
	}
	if ( ( 'on' === $settings['45_minutes'] || 'true' === $settings['45_minutes'] ) ) {
		$is_available[] = 45;
	}
	if ( ( 'on' === $settings['30_minutes'] || 'true' === $settings['30_minutes'] ) ) {
		$is_available[] = 30;
	}
	sort( $is_available );
	return $is_available;
}
/**
 * Check if a doctor has sessions of type.
 *
 * @param int    $user_id User's ID.
 * @param string $attendance_type Attendance type.
 * @param string $period period.
 * @return bool
 */
function snks_has_sessions_of_type( $user_id, $attendance_type, $period ) {
	global $wpdb;
	// Generate a unique cache key.
	$cache_key = 'snks_has_sessions_of_type_' . $attendance_type;
	//phpcs:disable
	// Execute the query.
	if ( 'both' === $attendance_type ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
			FROM {$wpdb->prefix}snks_provider_timetable
			WHERE user_id = %d
			AND period = %s
			AND session_status = %s",
				$user_id,
				$period,
				'waiting'
			)
		);
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
			FROM {$wpdb->prefix}snks_provider_timetable
			WHERE attendance_type = %s
			AND user_id = %d
			AND period = %s
			AND session_status = %s",
				$attendance_type,
				$user_id,
				$period,
				'waiting'
			)
		);
	}

	return $results;
}
/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods_options() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'true' === $settings['60_minutes'] || 'on' === $settings['60_minutes'] ) {
		$is_available[] = array(
			'value' => '60',
			'label' => '60 دقيقة',
		);
	}
	if ( 'true' === $settings['45_minutes'] || 'on' === $settings['45_minutes'] ) {
		$is_available[] = array(
			'value' => '45',
			'label' => '45 دقيقة',
		);
	}
	if ( 'true' === $settings['30_minutes'] || 'on' === $settings['30_minutes'] ) {
		$is_available[] = array(
			'value' => '30',
			'label' => '30 دقيقة',
		);
	}
	return $is_available;
}

/**
 * Get periods possibilities.
 *
 * @return array
 */
function snks_get_periods_possibilities() {
	$array        = snks_get_periods();
	$combinations = array();
	$array_count  = count( $array );
		// Generate all possible combinations.
	for ( $i = 0; $i < ( 1 << $array_count ); $i++ ) {
		$combination = array();
		for ( $j = 0; $j < $array_count; $j++ ) {
			if ( $i & ( 1 << $j ) ) {
				$combination[] = $array[ $j ];
			}
		}
		$combinations[] = $combination;
		// Create a duplicate of the combination and add 30, but only if the combination doesn't contain only 30.
		if ( in_array( 30, $combination, true ) && in_array( 60, $combination, true ) ) {
			$duplicate      = $combination;
			$duplicate[]    = 30;
			$combinations[] = $duplicate;
		}
	}
		// Sort the combinations in descending order.
		usort(
			$combinations,
			function ( $a, $b ) {
				return count( $a ) - count( $b );
			}
		);
		$possibilities = array();
		// Print the results.
	foreach ( $combinations as $combination ) {
		if ( count( $combination ) > 0 ) {
			// Sort the combinations in descending order.
			usort(
				$combination,
				function ( $a, $b ) {
					return $b - $a;
				}
			);
			$possibilities[] = $combination;
		}
	}
		return $possibilities;
}

/**
 * Get periods possibilities options.
 *
 * @return array
 */
function snks_get_periods_possibilities_options() {
	$possibilities = snks_get_periods_possibilities();
	$options       = array();
	foreach ( $possibilities as $possibility ) {
		if ( count( $possibility ) === 1 ) {
			$options[] = array(
				'value' => $possibility[0],
				'label' => $possibility[0] . ' دقيقة فقط ',
			);
		} elseif ( has_two_occurrences( $possibility, 30 ) ) {
				$occurrences = array();
				$remaining   = array();

			foreach ( $possibility as $number ) {
				if ( 30 === $number ) {
					$occurrences[] = $number;
				} else {
					$remaining[] = $number;
				}
			}
			$options[] = array(
				'value' => implode( '-', $possibility ),
				'label' => implode( ' دقيقة أو ', $remaining ) . ' دقيقة او ' . implode( ' دقيقة + ', $occurrences ) . ' دقيقة',
			);
		} else {
			$options[] = array(
				'value' => implode( '-', $possibility ),
				'label' => implode( ' دقيقة أو ', $possibility ) . ' دقيقة',
			);
		}
	}
	if ( ! empty( $options ) ) {
		array_unshift(
			$options,
			array(
				'value' => '',
				'label' => 'حدد مدد الحجز',
			)
		);
	}
	return $options;
}
/**
 * Get doctor's available attendance types options
 *
 * @return array
 */
function snks_get_available_attendance_types_options() {
	$settings     = snks_doctor_settings();
	$is_available = array(
		array(
			'value' => '',
			'label' => 'حدد خياراً',
		),
	);
	$online       = array(
		'value' => 'online',
		'label' => 'أونلاين',
	);
	$offline      = array(
		'value' => 'offline',
		'label' => 'عيادة',
	);
	$both      = array(
		'value' => 'both',
		'label' => 'أونلاين/عيادة',
	);
	if ( 'online' === $settings['attendance_type'] ) {
		$is_available[] = $online;
	} elseif ( 'offline' === $settings['attendance_type'] ) {
		$is_available[] = $offline;
	} elseif ( 'both' === $settings['attendance_type'] ) {
		$is_available[] = $online;
		$is_available[] = $offline;
		$is_available[] = $both;
	}
	return $is_available;
}
/**
 * Get clinics
 *
 * @param mixed $user_id User's id.
 * @return mixed
 */
function snks_get_clinics( $user_id = false ) {
	$settings = snks_doctor_settings( $user_id );
	if ( is_array( $settings['clinics_list'] ) && ! empty( $settings['clinics_list'] ) ) {
		return $settings['clinics_list'];
	}
	return false;
}

/**
 * Get clinic
 *
 * @param string $key Clinic array key.
 * @param mixed  $user_id User's id.
 * @return array
 */
function snks_get_clinic( $key, $user_id = false ) {
	$clinics = snks_get_clinics( $user_id );
	if ( $clinics ) {
		return $clinics[ $key ];
	}
	return false;
}

/**
 * Generate appointments dates
 *
 * @param array $week_days An array of week days abbreviations array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ).
 * @return array
 */
function snks_generate_appointments_dates( $week_days ) {

	// Initialize the result array.
	$result = array();

	// Get today's date.
	$start_date = new DateTime();

	for ( $i = 0; $i < 30; $i++ ) {
		// Get the current date.
		$current_date = clone $start_date;
		$current_date->modify( "+$i days" );

		// Get the abbreviation of the current day.
		$day_abbr = $current_date->format( 'D' );

		// Check if the abbreviation is in the provided days array.
		if ( in_array( $day_abbr, $week_days, true ) ) {
			// Add the formatted entry to the result array.
			$result[] = array(
				'day'   => $day_abbr,
				'label' => snks_localize_day( $day_abbr ),
				'date'  => $current_date->format( 'Y-m-d' ),
			);
		}
	}
	// Sort the array by date in ascending order.
	usort(
		$result,
		function ( $a, $b ) {
			return strtotime( $a['date'] ) - strtotime( $b['date'] );
		}
	);
	return $result;
}
/**
 * Get doctors appointments settings
 *
 * @return array
 */
function snks_get_appointments_settings() {
	$week_days = array_keys( json_decode( DAYS_ABBREVIATIONS, true ) );
	$user_id   = get_current_user_id();
	$settings  = array();
	foreach ( $week_days as $abb ) {
		$abb_settings = get_user_meta( $user_id, lcfirst( $abb ) . '_timetable', true );
		if ( ! $abb_settings || empty( $abb_settings ) ) {
			continue;
		}
			$settings[ $abb ] = $abb_settings;
	}
	return $settings;
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
			'min'  => $min,
		);

		if ( 30 === $min && has_two_occurrences( $mins, 30 ) ) {
			$start_hour = $end_hour;
		}
	}
	return $expected_hours;
}

/**
 * Generate datetime
 *
 * @param array  $app_settings Appointments settings.
 * @param string $day Day abbreviation.
 * @param string $appointment_hour Appointment hour.
 * @return mixed
 */
function snks_generate_date_time( $app_settings, $day, $appointment_hour ) {
	$week_days          = array_keys( $app_settings );
	$appointments_dates = snks_generate_appointments_dates( $week_days );
	$date_time          = false;
	foreach ( $appointments_dates as $appointments_date ) {
		if ( $day === $appointments_date['day'] ) {
			$date_time = DateTime::createFromFormat( 'Y-m-d h:i a', $appointments_date['date'] . ' ' . $appointment_hour );
			if ( $date_time ) {
				$date_time = $date_time->format( 'Y-m-d h:i a' );
			}
		}
	}
	return $date_time;
}
/**
 * Generate time table
 *
 * @return array
 */
function snks_generate_timetable() {
	// Get appointments settings.
	$app_settings = snks_get_appointments_settings();
	if (empty($app_settings)) {
        return array(); // Return early if no settings.
    }
	// Array to store appointments details.
	$data               = array();
	$user_id            = get_current_user_id();
	$week_days          = array_keys( $app_settings );
	$appointments_dates = snks_group_by( 'day', snks_generate_appointments_dates( $week_days ) );
	$off_days           = snks_get_off_days();

	foreach ( $appointments_dates as $day => $dates_details ) {
		// Day settings ( e.g. SAT ).
		$day_settings = $app_settings[ $day ];
		if (empty($day_settings)) {
            continue;
        }
		// Loop through generated dates.
		foreach ( $dates_details as $date_details ) {
			$date = $date_details['date'];
			foreach ( $day_settings as $details ) {
				if ( empty( $details['appointment_hour'] ) ) {
					continue;
				}
				// Get choosen periods.
				$periods = array_map( 'absint', explode( '-', $details['appointment_choosen_period'] ) );
				// String to time appointment hour.
				$appointment_hour = gmdate( 'h:i a', strtotime( $details['appointment_hour'] ) );
				// Get a list of expected hours at this day according to periods and appointment hour.
				$date_time = DateTime::createFromFormat( 'Y-m-d h:i a', $date . ' ' . $appointment_hour );
				$expected_hours = snks_expected_hours( $periods, $appointment_hour );
				foreach ( $expected_hours as $expected_hour ) {
					// Ensure the formatted date_time is valid and compare it with the current time.
					if ( $date_time && strtotime( $date_time->format('Y-m-d h:i a') ) > current_time( 'timestamp' ) ) {
						$formatted_date_time = $date_time->format('Y-m-d h:i a');
				
						$data[ sanitize_text_field( $day ) ][] = array(
							'user_id'         => $user_id,
							'session_status'  => in_array( $date, $off_days, true ) ? 'closed' : 'waiting',
							'day'             => sanitize_text_field( $day ),
							'base_hour'       => sanitize_text_field( $details['appointment_hour'] ),
							'period'          => sanitize_text_field( $expected_hour['min'] ),
							'date_time'       => $formatted_date_time,
							'date'            => $date,
							'starts'          => gmdate( 'H:i:s', strtotime( $expected_hour['from'] ) ),
							'ends'            => gmdate( 'H:i:s', strtotime( $expected_hour['to'] ) ),
							'clinic'          => sanitize_text_field( $details['appointment_clinic'] ),
							'attendance_type' => sanitize_text_field( $details['appointment_attendance_type'] ),
						);
					}
				}
				
			}
		}
	}
	
	return( $data );
}
/**
 * Set preview timetable
 *
 * @param array $data data to set.
 * @return mixed
 */
function snks_set_preview_timetable( $data ) {
	update_user_meta( get_current_user_id(), 'preview_timetable', $data );
}
/**
 * Get preview timetable
 *
 * @param mixed $user_d User ID or false.
 * @return mixed
 */
/**
 * Get preview timetable filtered by attendance type.
 *
 * @param mixed $user_id User ID or false.
 * @param bool $full If true, returns full timetable, otherwise returns only active attendance type.
 * @return mixed
 */
function snks_get_preview_timetable( $user_id = false, $full = false ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    // Get the user's preview timetable.
    $timetable = get_user_meta( $user_id, 'preview_timetable', true );
	if ( $full ) {
		return $timetable;
	}
    // Fetch the doctor's settings.
    $doctor_settings = snks_doctor_settings( $user_id );
	$available_periods = snks_get_periods( $user_id );
    // Check if the doctor has an attendance_type setting.
    if ( ! empty( $doctor_settings['attendance_type'] ) && is_array( $timetable ) ) {
        // Filter timetable based on the attendance_type.
        foreach ( $timetable as $day => &$sessions ) {
            $sessions = array_filter( $sessions, function( $session ) use ( $doctor_settings, $available_periods ) {
                return ( $session['attendance_type'] === $doctor_settings['attendance_type'] || 'both' === $doctor_settings['attendance_type'] ) && in_array( $session['period'], $available_periods );
            });
        }

        // Remove any empty days after filtering.
        $timetable = array_filter( $timetable );
    }

    return $timetable;
}


/**
 * Preview actions
 *
 * @param string $day Preview timetable day.
 * @param int    $index Preview timetable index.
 * @param string $target Action target.
 * @return string
 */
function snks_preview_actions( $day, $index, $target = 'meta' ) {
	$html = '';
	if ( 'meta' === $target ) {
		$html .= '<a href="#" class="button delete-slot" data-index="' . $index . '" data-day="' . $day . '">Delete</a>';
	} else {
		// This will allow patient to book another free appointment. A messsage should be sent to the user to inform him about this.
		$html .= '<a href="#" class="button postpon-booking" data-index="' . $index . '" data-day="' . $day . '">تأجيل</a>';
		// This will allow send a message to the patient that his booking has been delayed for x minutes.
		$html .= '<a href="#" class="button delay-booking" data-index="' . $index . '" data-day="' . $day . '">تأخير</a>';
		$html .= '<a href="' . add_query_arg( 'room_id', $index, site_url( '/meeting-room' ) ) . '" class="button start-meeting">ابدأ الجلسة</a>';
	}
	return $html;
}
/**
 * Get period before free edit booking will be not possible.
 *
 * @param array $doctor_settings Doctor's settings.
 * @return int
 */
function snks_get_free_edit_before_seconds( $doctor_settings ) {
	$number = $doctor_settings['free_change_before_number'];
	$unit   = $doctor_settings['free_change_before_unit'];
	$base   = 'day' === $unit ? 24 : 1;
	return $number * $base * 3600;
}

/**
 * Get period before edit booking will be not possible.
 *
 * @param array $doctor_settings Doctor's settings.
 * @return int
 */
function snks_get_edit_before_seconds( $doctor_settings ) {
	$number = $doctor_settings['before_change_number'];
	$unit   = $doctor_settings['before_change_unit'];
	switch ( $unit ) {
		case 'week':
			$base = 7 * 24;
			break;
		case 'month':
			$base = 30 * 24;
			break;
		case 'day':
			$base = 24;
			break;
		default:
			$base = 1;
	}
	return absint( $number ) * $base * 3600;
}

add_action(
	'jet-form-builder/custom-action/after_session_settings',
	function () {
		$timetables = snks_generate_timetable();
		if ( is_array( $timetables ) ) {
			snks_set_preview_timetable( $timetables );
		}
	}
);

add_action(
	'jet-form-builder/custom-action/update_doctor_profile',
	function ( $request ) {
		$current_user_id = get_current_user_id();
		$current_user_info = get_userdata( $current_user_id );
		$current_hashed_password = $current_user_info->user_pass;
		$current_username = $current_user_info->user_login;
		if ( empty( $request['profile-image'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى إضافة الصورة الشخصية' );
		}

		if ( empty( $request['email'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'البريد الإلكتروني لايمكن أن يكون فارغاً' );
		}
		if ( empty( $request['username'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'رقم التليفون لايمكن أن يكون فارغاً' );
		}  elseif ( username_exists( $request['username'] ) && $request['username'] !== $current_username ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'رقم التليفون موجود بالفعل' );
		}

		if ( empty( $request['password'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى إدخال كلمة مرورك الحالية لاستكمال تحديث البيانات' );
		} elseif ( ! wp_check_password( $request['password'], $current_hashed_password, $current_user_id ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'كلمة المرور الحالية غير صحيحة' );
		}
	}
);

add_action(
	'jet-form-builder/custom-action/update_doctor_phone',
	function ( $request ) {
		if ( ! empty( $request['username'] ) ) {
			// Get the current user.
			$current_user_id = get_current_user_id();
			global $wpdb;
			$wpdb->update(
				$wpdb->users,
				array( 'user_login' => $request['username'] ),
				array( 'ID' => $current_user_id )
			);
		}
		// If new password is passed, update the user's password.
		if ( ! empty( $request['new-password'] ) ) {
			// Sanitize and update the password.
			$new_password = sanitize_text_field( $request['new-password'] );
			wp_set_password( $new_password, $current_user_id );
		}
	}
);

/**
 * Get off days
 *
 * @param mixed $user_id User ID.
 * @return mixed
 */
function snks_get_off_days( $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$off_days = get_user_meta( get_current_user_id(), 'off_days', true );
	if ( ! $off_days || empty( $off_days ) ) {
		return array();
	}
	$off_days = str_replace( ' ', '', $off_days );
	return explode( ',', $off_days );
}

/**
 * Render conflicts
 *
 * @param string $day Day name (e.g. Sat).
 * @return string
 */
function snks_render_conflicts( $day ) {
	$html = '';
	//phpcs:disable
	if ( isset( $_GET['day'] ) && $day !== $_GET['day'] ) {
		return $html;
	}
	if ( ! empty( $_GET['conflicts'] ) ) {
		$html .= '<p class="conflict-error" style="color:red">';
		$conflicts = urldecode( $_GET['conflicts'] );
		$conflicts = explode( '-', $conflicts );
		$conflicts = array_map(
			function ( $item ) {
				return snks_localize_time( gmdate( 'h:i a', strtotime( $item ) ) );
			},
			$conflicts
		);
		//phpcs:enable
		$html .= sprintf(
			'لديك تداخل في هذه المواعيد ( %s )',
			implode( ' , ', $conflicts )
		);
		$html .= '</p>';
	}
	return $html;
}

/**
 * Get hours in range
 *
 * @param array $target_hours Hours to check.
 * @param array $check_hours Hours to check against.
 * @return array
 */
function snks_get_hours_in_range( $target_hours, $check_hours ) {
	$hours_in_range = array();
	$target_hours   = array_values( $target_hours );
	$check_hours    = array_values( $check_hours );
	if ( empty( $check_hours ) || empty( $target_hours ) ) {
		return $hours_in_range;
	}
	$count = count( $check_hours );
	foreach ( $target_hours as $target_hour ) {
		$target_timestamp = strtotime( $target_hour );
		for ( $i = 0; $i < $count - 1; $i++ ) {
			$start_time      = $check_hours[ $i ];
			$end_time        = $check_hours[ $i + 1 ];
			$start_timestamp = strtotime( $start_time );
			$end_timestamp   = strtotime( $end_time );
			if ( $target_timestamp >= $start_timestamp && $target_timestamp < $end_timestamp ) {
				$hours_in_range[] = $target_hour;
				break;
			}
		}
	}

	return $hours_in_range;
}
/**
 * Get available credit
 *
 * @return mixed
 */
function snks_get_avaialable_credit() {
	// Return credit without currency.
	return preg_replace( '/[^\d]/', '', snks_get_wallet_balance( get_current_user_id() ) );
}

/**
 * Get Withdrawal amount
 *
 * @return mixed
 */
function snks_get_withdrawal_credit() {
	return 0;
}

add_action(
	'jet-form-builder/custom-action/settings_validate',
	function ( $request ) {
		$user_id = get_current_user_id();
		// Check if attendance_type is empty.
		if ( empty( $request['attendance_type'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( ' طريقة استخدام التطبيق غير محددة.' );
		}

		// Loop through clinics_list and check if all values are empty.
		foreach ( $request['clinics_list'] as $key => $clinic ) {
			if ( empty( array_filter( $clinic ) ) ) {
				unset( $request['clinics_list'][ $key ] );
			}
		}
		// Check if attendance_type equals "both" or "offline" and clinics_list is empty.
		if (
		( 'both' === $request['attendance_type'] || 'offline' === $request['attendance_type'] )
		) {
			if ( empty( $request['clinics_list'] ) ) {
				throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يجب إدخال قائمة العيادات عند اختيار نوع الحضور "أوفلاين فقط" أو "أونلاين وأوفلاين". على الأقل إسم العيادة.' );
			}

			// Check if any sub-array in 'clinics_list' has an empty 'clinic_title'.
			if ( ! empty( $request['clinics_list'] ) ) {
				foreach ( $request['clinics_list'] as $clinic ) {
					if ( empty( $clinic['clinic_title'] ) ) {
						throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يجب إدخال اسم العيادة لكل عيادة موجودة في القائمة عند اختيار نوع الحضور "أوفلاين فقط" أو "أونلاين وأوفلاين".' );
					}
				}
			}
		}
		// Check if none of 60_minutes, 45_minutes, or 30_minutes are "on".
		if ( ( 'on' !== $request['60-minutes'] ) && ( 'on' !== $request['45-minutes'] ) && ( 'on' !== $request['30-minutes'] ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'لم يتم تفعيل أي من مدد الجلسات (30، 45، 60 دقيقة).' );
		}
		if ( 'on' === $request['30-minutes'] ) {
			$pricings[30] = array(
				'countries' => get_user_meta( $user_id, '30_minutes_pricing', true ),
				'others'    => get_user_meta( $user_id, '30_minutes_pricing_others', true ),
			);
		}
		if ( 'on' === $request['45-minutes'] ) {
			$pricings[45] = array(
				'countries' => get_user_meta( $user_id, '45_minutes_pricing', true ),
				'others'    => get_user_meta( $user_id, '45_minutes_pricing_others', true ),
			);
		}
		if ( 'on' === $request['60-minutes'] ) {
			$pricings[60] = array(
				'countries' => get_user_meta( $user_id, '60_minutes_pricing', true ),
				'others'    => get_user_meta( $user_id, '60_minutes_pricing_others', true ),
			);
		}
		// Check if the corresponding 'others' value is not empty for the active time request.
		if (
			( 'on' === $request['30-minutes'] && empty( $pricings[30]['others'] ) ) ||
			( 'on' === $request['45-minutes'] && empty( $pricings[45]['others'] ) ) ||
			( 'on' === $request['60-minutes'] && empty( $pricings[60]['others'] ) )
		) {

			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى إدخال أسعار الجلسات.' );
		}
		$edit_before      = snks_get_edit_before_seconds( $request );
		$free_edit_before = snks_get_free_edit_before_seconds( $request );
		if ( $edit_before > $free_edit_before ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً! لابد أن تكون فترة التغيير المجاني أكبر من الفترة التي تحددها لآخر موعد لتعديل الحجز' );
		}
	},
	0
);

add_action(
	'jet-form-builder/custom-action/validate_pricings',
	function ( $request ) {
		foreach ( $request as $key => $pricing ) {

			// Check if the key contains '_minutes_pricing'.
			if ( strpos( $key, '_minutes_pricing' ) !== false ) {
				$country_codes = array();
				if ( is_array( $pricing ) ) {
					// Loop through each entry in the pricing array.
					foreach ( $pricing as $entry ) {
						$country_code = $entry['country_code'];

						// Check if the country code already exists.
						if ( in_array( $country_code, $country_codes, true ) ) {
							// Return true if a duplicate is found.
							throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً! لديك أكثر من سعر لنفس الدولة' );
						}
						// Check if price is empty or missing.
						if ( empty( $entry['price'] ) ) {
							// Throw an exception if the price is not provided.
							throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً! الدول المضافة ليس بها سعر.' );
						}
						// Add the country code to the list.
						$country_codes[] = $country_code;
					}
				}
			}
			if ( strpos( $key, 'minutes_pricing_others' ) !== false ) {
				if ( empty( $request[ $key ] ) ) {
					throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً سعر باقي الدول إلزامي.' );
				}
			}
		}
	},
	0
);
/**
 * Colors form
 *
 * @return string
 */
function snks_clinic_colors_form() {
	$images         = range( 1, 20 );
	$clinic_color   = get_user_meta( get_current_user_id(), 'clinic_colors', true );
	$image_base_url = '/wp-content/uploads/2024/09';
	$nonce          = wp_create_nonce( 'clinic_colors_nonce' ); // Generate a nonce for validation.
	ob_start();
	// HTML form with inline script for AJAX.
	//phpcs:disable
	?>
	<form id="clinic-colors-form" class="snks-confirm" action="" method="post">
		<div class="clinic-colors-grid">
			<?php foreach ( $images as $image ) : ?>
				<div class="clinic-color-item">
					<input type="radio" name="clinic_color" id="color-<?php echo $image; ?>" value="<?php echo $image; ?>" hidden>
					<label for="color-<?php echo $image; ?>">
						<img src="<?php echo esc_url( $image_base_url . "/$image.png" ); ?>" alt="Color <?php echo $image; ?>" class="clinic-color-image<?php echo $image == absint( $clinic_color ) ? ' selected' : '' ?>">
					</label>
				</div>
			<?php endforeach; ?>
		</div>
		<button class="anony-full-width" type="submit" name="submit_clinic_color">حفظ</button>
		<div id="clinic-colors-response"></div> <!-- Response message container -->
	</form>

	<style>
		#clinic-colors-form button{
			margin-top: 20px;
			border-radius: 5px;
		}
		.clinic-colors-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 10px;
		}
		.clinic-color-item {
			text-align: center;
		}
		.clinic-color-image {
			cursor: pointer;
			max-width: 100px;
			max-height: 100px;
			border: 2px solid transparent;
			transition: border-color 0.3s;
		}
		input[type="radio"]:checked + label .clinic-color-image {
			border-color: #0073aa; /* Highlight border color when selected */
		}
		.clinic-color-label input:checked + .clinic-color-image,
        .clinic-color-image.selected {
			border: 5px solid #716c6c!important;
			border-radius: 50%;
        }
		.clinic-color-response {
			text-align: center;
			padding: 10px;
			margin-top: 10px;
			border-radius: 5px;
		}
		.clinic-color-response.success{
			color:green;
			border: 1px solid green;
		}
		.clinic-color-response.error{
			color:red;
			border: 1px solid red;
		}
	</style>
<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$(document).on(
						'submit',
						'#clinic-colors-form',
						function (e) {
						e.preventDefault();

						const selectedColor = $('input[name="clinic_color"]:checked').val();
						const nonce = '<?php echo $nonce; ?>'; // Nonce passed to the script

						$.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: 'POST',
							data: {
								action: 'clinic_colors_submit',
								clinic_color: selectedColor,
								nonce: nonce
							},
							success: function (response) {
								if (response.success) {
									$('#clinic-colors-response').html('<p class="clinic-color-response success">' + response.data.message + '</p>');
								} else {
									$('#clinic-colors-response').html('<p class="clinic-color-response error">' + response.data.message + '</p>');
								}
							},
							error: function () {
								$('#clinic-colors-response').html('<p style="color: red;">حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.</p>');
							}
						});
					});
					$(document).on(
						'change',
						'input[name="clinic_color"]',
						function(){
							$('.clinic-color-image').removeClass('selected'); // Remove selected class from all images
							$('input[name="clinic_color"]:checked').closest('.clinic-color-item').find('img').addClass('selected');
						}
					);
				});
			</script>
	<?php
	return ob_get_clean();
}
//phpcs:enable
add_shortcode( 'clinic_colors_form', 'snks_clinic_colors_form' );
/**
 * AJAX handler for saving the selected color
 *
 * @return void
 */
function handle_clinic_colors_ajax_submission() {
	check_ajax_referer( 'clinic_colors_nonce', 'nonce' ); // Validate the nonce.

	if ( ! isset( $_POST['clinic_color'] ) ) {
		wp_send_json_error( array( 'message' => 'الرجاء اختيار اللون.' ) );
	}

	$selected_color = sanitize_text_field( wp_unslash( $_POST['clinic_color'] ) );
	$user_id        = get_current_user_id();

	if ( $user_id ) {
		update_user_meta( $user_id, 'clinic_colors', $selected_color );
		wp_send_json_success( array( 'message' => 'تم اختيار اللون وحفظه بنجاح!' ) );
	} else {
		wp_send_json_error( array( 'message' => 'خطأ: لم يقوم المستخدم بتسجيل الدخول.' ) );
	}
}
add_action( 'wp_ajax_clinic_colors_submit', 'handle_clinic_colors_ajax_submission' );
add_action( 'wp_ajax_nopriv_clinic_colors_submit', 'handle_clinic_colors_ajax_submission' );

add_filter(
	'elementor/frontend/the_content',
	function ( $content ) {
		$post_id = get_the_ID();

		if ( 1935 === $post_id && false !== strpos( $content, '3939ceb3' ) ) {
			$content = '<div id="snks_account_settings">' . $content . '</div>';
		}

		return $content;
	},
	10
);
