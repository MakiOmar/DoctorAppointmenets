<?php
/**
 * Settings helpers
 *
 * @package Shrinks
 */

use erguncaner\Table\Table;
use erguncaner\Table\TableColumn;
use erguncaner\Table\TableRow;
use erguncaner\Table\TableCell;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Return doctor settings
 *
 * @return array An array of settings if is a doctor.
 */
function snks_doctor_settings() {
	$settings = array();
	if ( snks_is_doctor() || current_user_can( 'manage_options' ) ) {
		$user_id                               = get_current_user_id();
		$settings['60_minutes']                = get_user_meta( $user_id, '60-minutes', true );
		$settings['45_minutes']                = get_user_meta( $user_id, '45-minutes', true );
		$settings['30_minutes']                = get_user_meta( $user_id, '30-minutes', true );
		$settings['enable_discount']           = get_user_meta( $user_id, 'enable_discount', true );
		$settings['discount_percent']          = get_user_meta( $user_id, 'discount_percent', true );
		$settings['to_be_old_number']          = get_user_meta( $user_id, 'to_be_old_number', true );
		$settings['to_be_old_unit']            = get_user_meta( $user_id, 'to_be_old_unit', true );
		$settings['allow_appointment_change']  = get_user_meta( $user_id, 'allow_appointment_change', true );
		$settings['free_change_before_number'] = get_user_meta( $user_id, 'free_change_before_number', true );
		$settings['free_change_before_unit']   = get_user_meta( $user_id, 'free_change_before_unit', true );
		$settings['block_if_before_number']    = get_user_meta( $user_id, 'block_if_before_number', true );
		$settings['block_if_before_unit']      = get_user_meta( $user_id, 'block_if_before_unit', true );
		$settings['attendance_type']           = get_user_meta( $user_id, 'attendance_type', true );
		$settings['clinics_list']              = get_user_meta( $user_id, 'clinics_list', true );
	}

	return $settings;
}

/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['60_minutes'] || 'true' === $settings['60_minutes'] ) {
		$is_available[] = 60;
	}
	if ( 'on' === $settings['45_minutes'] || 'true' === $settings['45_minutes'] ) {
		$is_available[] = 45;
	}
	if ( 'on' === $settings['30_minutes'] || 'true' === $settings['30_minutes'] ) {
		$is_available[] = 30;
	}
	return $is_available;
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
	$array        = snks_get_available_periods();
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
		if ( count( $combination ) > 1 && in_array( 30, $combination, true ) && array( 45, 30 ) !== $array && array( 30, 45 ) !== $array ) {
			$duplicate      = $combination;
			$duplicate[]    = 30;
			$combinations[] = $duplicate;
		}
	}
		// Sort the combinations in descending order.
		usort(
			$combinations,
			function ( $a, $b ) {
				return count( $b ) - count( $a );
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
	$is_available = array();
	$online       = array(
		'value' => 'online',
		'label' => 'أونلاين',
	);
	$offline      = array(
		'value' => 'offline',
		'label' => 'عيادة',
	);
	if ( 'online' === $settings['attendance_type'] ) {
		$is_available[] = $online;
	} elseif ( 'offline' === $settings['attendance_type'] ) {
		$is_available[] = $offline;
	} elseif ( 'both' === $settings['attendance_type'] ) {
		$is_available[] = $online;
		$is_available[] = $offline;
	}
	return $is_available;
}
/**
 * Get clinics
 *
 * @return array
 */
function snks_get_clinics() {
	$settings = snks_doctor_settings();
	if ( ! empty( $settings['clinics_list'] ) ) {
		return $settings['clinics_list'];
	}
	return false;
}

/**
 * Get clinic
 *
 * @param string $key Clinic array key.
 * @return array
 */
function snks_get_clinic( $key ) {
	$clinics = snks_get_clinics();
	if ( $clinics && ! empty( $clinics[ $key ] ) ) {
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
	$days_labels     = json_decode( DAYS_ABBREVIATIONS, true );
	$next_seven_days = array();
	$today           = gmdate( 'D' ); // Get current day abbreviation.
	$current_index   = array_search( $today, $week_days, true ); // Get index of current day.
	$count           = count( $week_days ) > 7 ? 7 : count( $week_days );
	for ( $i = 0; $i < $count; $i++ ) {
		$next_day_index    = ( $current_index + $i ) % 7;
		$next_day          = gmdate( 'Y-m-d', strtotime( 'next ' . $week_days[ $next_day_index ] ) );
		$next_seven_days[] = array(
			'day'   => $week_days[ $next_day_index ],
			'label' => $days_labels[ $week_days[ $next_day_index ] ],
			'date'  => $next_day,
		);
	}
	// Sort the array by date in ascending order.
	usort(
		$next_seven_days,
		function ( $a, $b ) {
			return strtotime( $a['date'] ) - strtotime( $b['date'] );
		}
	);
	return $next_seven_days;
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
		if ( $abb_settings && ! empty( $abb_settings ) ) {
			$settings[ $abb ] = $abb_settings;
		}
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
	$app_settings = snks_get_appointments_settings();
	$data         = array();
	$user_id      = get_current_user_id();
	if ( ! empty( $app_settings ) ) {
		foreach ( $app_settings as $day => $app_setting ) {
			foreach ( $app_setting as $details ) {
				if ( empty( $details['appointment_hour'] ) ) {
					continue;
				}
				$periods          = array_map( 'absint', explode( '-', $details['appointment_choosen_period'] ) );
				$appointment_hour = gmdate( 'h:i a', strtotime( $details['appointment_hour'] ) );
				$expected_hours   = snks_expected_hours( $periods, $appointment_hour );
				foreach ( $expected_hours as $expected_hour ) {
					$date_time = snks_generate_date_time( $app_settings, $day, $appointment_hour );
					if ( ! $date_time ) {
						continue;
					}
					$data[] = array(
						'user_id'        => $user_id,
						'session_status' => 'waiting',
						'day'            => sanitize_text_field( $day ),
						'base_hour'      => sanitize_text_field( $details['appointment_hour'] ),
						'period'         => sanitize_text_field( $expected_hour['min'] ),
						'date_time'      => $date_time,
						'starts'         => gmdate( 'H:i:s', strtotime( $expected_hour['from'] ) ),
						'ends'           => gmdate( 'H:i:s', strtotime( $expected_hour['to'] ) ),
						'clinic'         => sanitize_text_field( $details['appointment_clinic'] ),
					);
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
 * @return mixed
 */
function snks_get_preview_timetable() {
	return get_user_meta( get_current_user_id(), 'preview_timetable', true );
}

/**
 * Preview actions
 *
 * @param int $index Preview pimetable index.
 * @return string
 */
function snks_preview_actions( $index ) {
	$html  = '';
	$html .= '<a href="#" class="button delete-slot" data-index="' . $index . '">Delete</a>';
	return $html;
}
/**
 * Generate preview
 *
 * @return string
 */
function snks_generate_preview() {
	$timetables = snks_get_preview_timetable();
	if ( empty( $timetables ) ) {
		return '<p>لم تقم بإضافة مواعيد</p>';
	}
	// First create a table.
	$table = new Table(
		array(
			'id' => 'preview-timetable',
		)
	);
	// Create table columns with a column key and column object.
	$table->addColumn( 'day', new TableColumn( 'اليوم' ) );
	$table->addColumn( 'datetime', new TableColumn( 'التاريخ والوقت' ) );
	$table->addColumn( 'starts', new TableColumn( 'تبدأ من' ) );
	$table->addColumn( 'ends', new TableColumn( 'تنتهي عند' ) );
	$table->addColumn( 'period', new TableColumn( 'المدة' ) );
	$table->addColumn( 'clinic', new TableColumn( 'العيادة' ) );
	$table->addColumn( 'actions', new TableColumn( 'الخيارات' ) );
	$clinics     = snks_get_clinics();
	$days_labels = json_decode( DAYS_ABBREVIATIONS, true );
	if ( is_array( $timetables ) ) {
		foreach ( $timetables as $index => $data ) {
			// Associate cells with columns.
			$cells = array(
				'day'      => new TableCell( $days_labels[ $data['day'] ], array( 'data-label' => 'اليوم' ) ),
				'datetime' => new TableCell( gmdate( 'Y-m-d', strtotime( $data['date_time'] ) ), array( 'data-label' => 'التاريخ والوقت' ) ),
				'starts'   => new TableCell( snks_localize_time( gmdate( 'h:i a', strtotime( $data['starts'] ) ) ), array( 'data-label' => 'تبدأ من' ) ),
				'ends'     => new TableCell( snks_localize_time( gmdate( 'h:i a', strtotime( $data['ends'] ) ) ), array( 'data-label' => 'تنتهي عند' ) ),
				'period'   => new TableCell( $data['period'], array( 'data-label' => 'المدة' ) ),
				'clinic'   => new TableCell( $clinics[ $data['clinic'] ]['clinic_title'], array( 'data-label' => 'العيادة' ) ),
				'actions'  => new TableCell( snks_preview_actions( $index ), array( 'data-label' => 'الخيارت' ) ),
			);

			// define row attributes.
			$attrs = array(
				'id' => 'timetable-' . $index,
			);

			$table->addRow( new TableRow( $cells, $attrs ) );
		}
	}
	// Finally generate html.
	$output  = $table->html();
	$output .= '<br/><center>هل أنت جاهز للنشر؟</center><br/>';
	$output .= '<center><button id="insert-timetable">نشر</button></center>';
	$output .= '<center id="insert-timetable-msg"></center>';
	return $output;
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
