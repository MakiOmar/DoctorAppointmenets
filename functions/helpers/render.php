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
 * Generates radio inputs for number of days
 *
 * @param string $days Number of days.
 * @param string $input_name Input name.
 * @return string
 */
function snks_generate_days( $days, $input_name ) {
	// Calculate the start and end dates.
	$current_date = gmdate( 'Y-m-d' );
	$today        = gmdate( 'Y-m-d' );
	$end_date     = gmdate( 'Y-m-d', strtotime( $days ) );

	$html = '';
	// Generate checkboxes for each date.
	while ( strtotime( $current_date ) <= strtotime( $end_date ) ) {
		$day_number = gmdate( 'j', strtotime( $current_date ) ); // Day of the month without leading zeros.
		$day_name   = gmdate( 'D', strtotime( $current_date ) ); // Full day name.
		$month_name = gmdate( 'F', strtotime( $current_date ) ); // Full day name.
		//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$html .= '<p class="anony-day-radio ' . $input_name . '"><label for="' . esc_attr( $current_date ) . '">';
		$html .= "<span>$day_number</span>";
		$html .= "<span>$day_name</span>";
		$html .= '</label>';
		$html .= sprintf(
			'<input id="%1$s" class="' . $input_name . '-radio" type="radio" name="' . $input_name . '" value="%2$s"></p>',
			esc_attr( $current_date ),
			esc_attr( $current_date )
		);
		//phpcs:enable.
		$current_date = gmdate( 'Y-m-d', strtotime( '+1 day', strtotime( $current_date ) ) );
	}

	return $html;
}

/**
 * Generates radio inputs for number of days
 *
 * @param int    $user_id User's ID.
 * @param mixed  $bookable_days_obj Bookable dates object.
 * @param string $input_name Input name.
 * @param string $period Input Period.
 * @param int    $days_count Number of days available in the form.
 * @return string
 */
function snks_generate_consulting_days( $user_id, $bookable_days_obj, $input_name, $period, $days_count = 30 ) {
	$bookable_dates_times = wp_list_pluck( $bookable_days_obj, 'date_time' );
	$bookable_days        = array_unique(
		array_map(
			function ( $value ) {
				return gmdate( 'Y-m-d', strtotime( $value ) );
			},
			$bookable_dates_times,
		)
	);

	$html            = '';
	$n_bookable_days = array_slice( $bookable_days, 0, $days_count );
	foreach ( $n_bookable_days as $index => $current_date ) {
		//phpcs:disable Universal.Operators.StrictComparisons.LooseEqual
		$day_number = gmdate( 'j', strtotime( $current_date ) ); // Day of the month without leading zeros.
		$day_name   = gmdate( 'D', strtotime( $current_date ) ); // Full day name.
		$month_name = gmdate( 'F', strtotime( $current_date ) ); // Full day name.
		//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$html .= '<div class="anony-content-slide anony-day-radio ' . $input_name . '"><label for="' . esc_attr( $current_date ) . '-' . $period . '">';
		$html .= "<span>$day_number</span>";
		$html .= "<span>$day_name</span>";
		$html .= '</label>';
		$html .= sprintf(
			'<input id="%1$s-%4$s" class="' . $input_name . '-radio" type="radio" name="' . $input_name . '" value="%2$s" data-user="%3$s" data-period="%4$s">',
			esc_attr( $current_date ),
			esc_attr( $current_date ),
			$user_id,
			$period
		);
		$html .= '</div>';

	}

	return $html;
}
/**
 * Generate hourly form
 *
 * @param int    $user_id User's ID.
 * @param int    $period Session period.
 * @param string $price Price.
 * @return string
 */
function snks_generate_consulting_form( $user_id, $period, $price ) {
	if ( ! $user_id ) {
		return 'Form error!';
	}
	$bookable_days_obj = get_bookable_dates( $user_id, $period );
	if ( empty( $bookable_days_obj ) ) {
		return '<p>عفواً! لا تتوفر مواعيد للحجز</p>';
	}
	if ( ! is_user_logged_in() ) {
		$html = '<p>سجل دخولك أولاً من فضلك</p>';
	} else {
		$clinics = array_unique(
			array_map(
				function ( $obj ) {
					return $obj->clinic;
				},
				$bookable_days_obj
			)
		);

		$settings   = snks_doctor_settings( $user_id );
		$days_count = ! empty( $settings['form_days_count'] ) ? absint( $settings['form_days_count'] ) : 30;

		$booking_id  = '';
		$submit_text = 'حجز موعد';
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['edit-booking'] ) && ! empty( $_GET['edit-booking'] && is_numeric( $_GET['edit-booking'] ) ) ) {
			$booking = snks_get_timetable_by( 'ID', absint( $_GET['edit-booking'] ) );
			if ( ! $booking || get_current_user_id() !== absint( $booking->client_id ) ) {
				return '<p>عفواً! الموعد غير متاح أو ليس لديك صلاحية تحرير الموعد</p>';
			}
			$booking_id  = $booking->ID;
			$submit_text = 'تعديل الموعد';
		}

		$bookable_dates_times = wp_list_pluck( $bookable_days_obj, 'date_time' );
		$bookable_days        = array_unique(
			array_map(
				function ( $value ) {
					return gmdate( 'Y-m-d', strtotime( $value ) );
				},
				$bookable_dates_times,
			)
		);

		$n_bookable_days = array_slice( array_unique( $bookable_days ), 0, 30 );
		if ( count( $n_bookable_days ) > 7 ) {
			$slider_class = ' anony-content-slider';
			$slider       = true;
		} else {
			$slider_class = '';
			$slider       = false;
		}
		if ( ! current_user_can( 'manage_options' ) && ! snks_is_patient() ) {
			$html = '<p>عفواً غير مسموح</p>';
		} else {
			$direction = is_rtl() ? 'true' : 'false';

			$html  = '';
			$html .= '<form id="consulting-form-' . esc_attr( $period ) . '" class="consulting-form consulting-form-' . esc_attr( $period ) . '" action="/?direct_add_to_cart" method="post">';
			$html .= '<select name="appointment-clinic">';
			$html .= '<option value="">حدد العيادة</option>';
			foreach ( $clinics as $clinic_key ) {
				$clinic = snks_get_clinic( $clinic_key, $user_id );
				$html  .= '<option value="' . esc_attr( $clinic_key ) . '">' . esc_html( $clinic['clinic_title'] ) . '</option>';
				snks_print_r( $clinic );
			}
			$html .= '</select>';
			$html .= '<h5>';
			$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 2V5" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M16 2V5" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M3.5 9.08984H20.5" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M22 19C22 19.75 21.79 20.46 21.42 21.06C20.73 22.22 19.46 23 18 23C16.99 23 16.07 22.63 15.37 22C15.06 21.74 14.79 21.42 14.58 21.06C14.21 20.46 14 19.75 14 19C14 16.79 15.79 15 18 15C19.2 15 20.27 15.53 21 16.36C21.62 17.07 22 17.99 22 19Z" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M16.44 19L17.43 19.99L19.56 18.02" stroke="#707070" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M21 8.5V16.36C20.27 15.53 19.2 15 18 15C15.79 15 14 16.79 14 19C14 19.75 14.21 20.46 14.58 21.06C14.79 21.42 15.06 21.74 15.37 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M11.9955 13.7002H12.0045" stroke="#707070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M8.29431 13.7002H8.30329" stroke="#707070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M8.29431 16.7002H8.30329" stroke="#707070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			';
			$html .= '&nbsp;تحديد تاريخ الحجز';
			$html .= '</h5>';
			$html .= '<div class="atrn-form-days anony-content-slider-container">';
			$html .= '<div class="days-container' . esc_attr( $slider_class ) . '">';
			$html .= snks_generate_consulting_days( $user_id, $bookable_days_obj, 'current-month-day', $period, $days_count );
			$html .= '</div>';
			$html .= '</div>';
			if ( $slider ) {
				$html .= '<div class="anony-content-slider-control">
					<a class="anony-content-slider-prev button">
						<span class="anony-greater-than anony-content-slider-nav">
							<span class="top"></span><span class="bottom"></span>
						</span>
					</a>
					<a class="anony-content-slider-next button">
						<span class="anony-smaller-than anony-content-slider-nav">
							<span class="top"></span><span class="bottom"></span>
						</span>
					</a>
				</div>';
			}

			$html .= '<h5>';
			$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M20.75 13.25C20.75 18.08 16.83 22 12 22C7.17 22 3.25 18.08 3.25 13.25C3.25 8.42 7.17 4.5 12 4.5C16.83 4.5 20.75 8.42 20.75 13.25Z" stroke="#707070" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M12 8V13" stroke="#707070" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M9 2H15" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>';
			$html .= '&nbsp;تحديد وقت الحجز';
			$html .= '</h5>';
			$html .= '<ul class="snks-available-hours"></ul>';
			$html .= '<hr>';
			$html .= '<div id="consulting-form-price"><span>سعر الإستشارة</span><span>' . $price . ' ' . get_woocommerce_currency_symbol() . '</span></div>';
			$html .= '<input type="hidden" name="create-appointment" value="create-appointment">';
			$html .= '<input type="hidden" id="edit-booking-id" name="edit-booking-id" value="' . $booking_id . '">';
			$html .= '<input type="hidden" id="user-id" name="user-id" value="' . $user_id . '">';
			$html .= '<input type="hidden" id="period" name="period" value="' . $period . '">';
			$html .= wp_nonce_field( 'create_appointment', 'create_appointment_nonce' );
			$html .= '<input type="submit" value="' . $submit_text . '">';
			$html .= '</form>';
		}
	}

	return $html;
}
/**
 * Generates radio inputs of current week
 *
 * @return string
 */
function snks_generate_week_days() {

	return snks_generate_days( '+6 days', 'current-week-day' );
}

/**
 * Generate hourly form
 *
 * @param string $date Date of format Y-m-d.
 * @return string
 */
function snks_generate_hourly_form( $date ) {
	$enrolment_date = get_user_meta( get_current_user_id(), 'programme_enrolment_date', true );
	if ( ! $enrolment_date || empty( $enrolment_date ) ) {
		return;
	}
	$html  = '';
	$html .= '<form id="to-do-form" method="post">';
	$html .= '<table>';

	for ( $hour = 1; $hour <= 24; $hour++ ) {
		if ( $hour <= 12 ) {
			$label = $hour . ' ص';
		} else {
			$label = ( $hour - 12 ) . ' م';
		}
		// Translators: %s the hour.
		$hour_label = sprintf( 'الساعة %s', $label );
		$input_name = $date . '[' . $hour . ']';

		$html .= '<tr>';
		$html .= '<td>' . $hour_label . '</td>';
		$html .= '<td><input class="to-do-input" data-hour="' . $hour . '" type="text" name="' . $input_name . '"></td>';
		$html .= '</tr>';
	}

	$html .= '</table>';
	$html .= '<input type="hidden" name="action" value="update_to_do">';
	$html .= '<input type="submit" value="حفظ">';
	$html .= '</form>';

	return $html;
}
/**
 * Compare two times
 *
 * @param object $time1 Time object.
 * @param object $time2 Time object.
 * @return int
 */
function snks_compare_times( $time1, $time2 ) {
	$time1 = strtotime( $time1->starts );
	$time2 = strtotime( $time2->starts );

	if ( $time1 === $time2 ) {
		return 0;
	}

	return ( $time1 < $time2 ) ? -1 : 1;
}
/**
 * Render consulting form hours.
 *
 * @param array $availables An array of available hours.
 * @return string
 */
function snks_render_consulting_hours( $availables ) {
	usort( $availables, 'snks_compare_times' );
	$html = '';
	if ( ! empty( $availables ) ) {
		foreach ( $availables as $available ) {
			$id    = $available->ID;
			$html .= '<li class="available-time">';
			$html .= '<label for="' . esc_attr( $id ) . '">';
			$html .= sprintf( 'من %s إلى %s', esc_html( gmdate( 'h:i a', strtotime( $available->starts ) ) ), esc_html( gmdate( 'h:i a', strtotime( $available->ends ) ) ) );
			$html .= '<input id="' . esc_attr( $id ) . '" type="radio" class="hour-radio" name="selected-hour" value="' . esc_attr( $id ) . '"/>';
			$html .= '</lable>';
			$html .= '</li>';
		}
		$html = str_replace( array( ' am', ' pm' ), array( ' ص', ' م' ), $html );
	} else {
		$html = '<li>' . esc_html__( 'Sorry! No available hour.' ) . '</li>';
	}
	return $html;
}

/**
 * Render edit button
 *
 * @param int $booking_id Booking ID.
 * @return string
 */
function snks_edit_button( $booking_id ) {
	return '<a href="' . add_query_arg( 'edit-booking', $booking_id, site_url( '/consulting-form' ) ) . '" title="تحرير" class="edit-booking"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M14.19 0H5.81C2.17 0 0 2.17 0 5.81V14.18C0 17.83 2.17 20 5.81 20H14.18C17.82 20 19.99 17.83 19.99 14.19V5.81C20 2.17 17.83 0 14.19 0ZM8.95 15.51C8.66 15.8 8.11 16.08 7.71 16.14L5.25 16.49C5.16 16.5 5.07 16.51 4.98 16.51C4.57 16.51 4.19 16.37 3.92 16.1C3.59 15.77 3.45 15.29 3.53 14.76L3.88 12.3C3.94 11.89 4.21 11.35 4.51 11.06L8.97 6.6C9.05 6.81 9.13 7.02 9.24 7.26C9.34 7.47 9.45 7.69 9.57 7.89C9.67 8.06 9.78 8.22 9.87 8.34C9.98 8.51 10.11 8.67 10.19 8.76C10.24 8.83 10.28 8.88 10.3 8.9C10.55 9.2 10.84 9.48 11.09 9.69C11.16 9.76 11.2 9.8 11.22 9.81C11.37 9.93 11.52 10.05 11.65 10.14C11.81 10.26 11.97 10.37 12.14 10.46C12.34 10.58 12.56 10.69 12.78 10.8C13.01 10.9 13.22 10.99 13.43 11.06L8.95 15.51ZM15.37 9.09L14.45 10.02C14.39 10.08 14.31 10.11 14.23 10.11C14.2 10.11 14.16 10.11 14.14 10.1C12.11 9.52 10.49 7.9 9.91 5.87C9.88 5.76 9.91 5.64 9.99 5.57L10.92 4.64C12.44 3.12 13.89 3.15 15.38 4.64C16.14 5.4 16.51 6.13 16.51 6.89C16.5 7.61 16.13 8.33 15.37 9.09Z" fill="#12114F"/>
	</svg>
	</a>';
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
		$output = snks_get_time_difference( $date_time, wp_timezone_string() );
	}
	return $output;
}
/**
 * Template string replace
 *
 * @param object $record The Record.
 * @param string $edit The edit string.
 * @param string $_class Button class.
 * @param string $room Room url.
 * @return string
 */
function template_str_replace( $record, $edit, $_class, $room ) {
	if ( snks_is_patient() ) {
		$client_id = get_current_user_id();
	}
	$button_text         = snks_human_readable_datetime_diff( $record->date_time, 'إبدأ الإستشارة' );
	$scheduled_timestamp = strtotime( $record->date_time );
	$current_timestamp   = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );

	if ( isset( $client_id ) && $current_timestamp > $scheduled_timestamp && ( $current_timestamp - $scheduled_timestamp ) > 60 * 15 ) {
		$button_text     = 'عفواً! تجاوزت الموعد.';
		$_class          = 'remaining';
		$session_actions = snks_get_session_actions( $record->ID, $client_id );
		if ( $session_actions ) {
			if ( 'yes' === $session_actions->attendance ) {
				$button_text = 'حضر';
				$_class     .= ' success';
			} elseif ( 'no' === $session_actions->attendance ) {
				$button_text = 'لم يحضر';
				$_class     .= ' error';
			}
		}
		$room = '#';
	}
	if ( 'cancelled' === $record->session_status ) {
		$button_text = 'ملغي';
		$room        = '#';
	}
	$template = do_shortcode( '[elementor-template id="1239"]' );
	$title    = '';
	if ( ! empty( $record->session_title ) ) {
		$title = '<div class="session-title"><p>' . $record->session_title . '</p><span></span></div>';
	}
	return str_replace(
		array(
			'{session_id}',
			'{doctor_name}',
			'{doctor_specialty}',
			'{listing_date}',
			'{listing_hour}',
			'{edit_listing}',
			'{button_url}',
			'{button_text}',
			'{button_class}',
			'{session_title}',
			'{room_url}',
			'{doctor_profile_image}',
		),
		array(
			$record->ID,
			snks_get_doctor_name( $record->user_id ),
			esc_html( get_user_meta( $record->user_id, 'specialty', true ) ),
			$record->booking_day,
			str_replace( array( ' am', ' pm' ), array( ' ص', ' م' ), gmdate( 'g:i a', strtotime( $record->date_time ) ) ),
			$edit,
			esc_url( $room ),
			$button_text,
			$_class,
			$title,
			esc_url( site_url( '/zego?room_id=' . $record->ID ) ),
			esc_url( get_user_meta( $record->user_id, 'profile-image', true ) ),
		),
		$template
	);
}

/**
 * Doctor actions
 *
 * @param object $session Session object.
 * @return string
 */
function snks_doctor_actions( $session ) {
	if ( ! $session ) {
		return '';
	}
	$attendees = explode( ',', $session->client_id );
	$output    = '';
	if ( ! empty( $attendees ) ) {
		$output .= '<table class="doctor-actions">';
		$output .= '<form class="doctor_actions" method="post" action="">';
		$output .= '<thead><tr><th>المريض</th><th>حضر</th><th>لم يحضر</th></tr></thead>';
		$output .= '<tbody>';
		foreach ( $attendees as $index => $client ) {
			$session_action = snks_get_session_actions( $session->ID, absint( $client ) );
			$yes_checked    = '';
			$no_checked     = '';
			if ( $session_action ) {
				$yes_checked = checked( 'yes', $session_action->attendance, false );
				$no_checked  = checked( 'no', $session_action->attendance, false );
			}

			$output .= '<tr>';
			$output .= '<td>' . get_user_meta( absint( $client ), 'nickname', true ) . '</td>';
			$output .= '<td>';
			$output .= '<input id="has-attended-' . $index . '" type="radio" name="has_attended_' . $client . '" value="yes" ' . $yes_checked . '/>';
			$output .= '</td>';
			$output .= '<td>';
			$output .= '<input id="has-attended--' . $index . '" type="radio" name="has_attended_' . $client . '" value="no" ' . $no_checked . '/>';
			$output .= '</label>';
			$output .= '</tr>';
		}
		$output .= '</tbody>';
		$output .= '<tfoot>';
		$output .= '<tr><td colspan="3"><input type="hidden" name="attendees" value="' . $session->client_id . '">';
		$output .= '<tr><td colspan="3"><input type="hidden" name="session_id" value="' . $session->ID . '">';
		$output .= '<input class="snks-button table-form-button" type="submit" name="doctor-actions" value="إرسال"></td></tr>';
		$output .= '</tfoot>';
		$output .= '</form>';
		$output .= '</table>';
	}
	if ( 'cancelled' !== $session->session_status && 'completed' !== $session->session_status ) {
		$output .= '<a href="#" class="snks-button snks-cancel-appointment" data-id="' . $session->ID . '">إلغاء</a>';
	}
	return $output;
}
/**
 * Render patient sessions
 *
 * @param string $tense Past/Future records.
 * @param string $_for  For patient|family|doctor.
 * @return string
 */
function snks_render_sessions_listing( $tense, $_for = 'patient' ) {
	if ( 'patient' === $_for ) {
		$sessions = snks_get_patient_sessions( $tense );
	} else {
		$sessions = snks_get_doctor_sessions( $tense );
	}
	$edit   = '<a href="#">' . esc_html__( 'Edit', 'anony_shrinks' ) . '</a>';
	$output = '';
	if ( $sessions && is_array( $sessions ) ) {
		$template = do_shortcode( '[elementor-template id="1239"]' );
		$edit     = '';
		foreach ( $sessions as $session ) {
			if ( snks_is_past_date( $session->date_time ) ) {
				$class = 'start';
				$room  = add_query_arg( 'room_id', $session->ID, site_url( '/zego' ) );
			} else {
				$class = 'remaining';
				$room  = '#';
			}
			$output .= template_str_replace( $session, $edit, $class, $room );

			if ( 'doctor' === $_for ) {
				$doctor_actions = snks_doctor_actions( $session );
				$output         = str_replace( '{doctor_actions}', $doctor_actions, $output );
			} else {
				$output = str_replace( '{doctor_actions}', '', $output );
			}
		}
	} else {
		$output = esc_html__( 'No sessions found.', 'anony_shrinks' );
	}
	return $output;
}
/**
 * Render to do days
 *
 * @param array $days Days.
 * @return string
 */
function snks_render_to_do_days( $days ) {
	if ( ! is_array( $days ) ) {
		return '';
	}
	$today      = gmdate( 'Y-m-d' );
	$html       = '<div id="todo-pages-container">';
	$input_name = 'current-week-day';
	// Generate checkboxes for each date.
	foreach ( $days as $key => $value ) {
		if ( ( count( $days ) - 1 ) === $key ) {
			$class = ' active-todo-page';
		} else {
			$class = '';
		}
		$html .= '<div id="todo-page-' . $key . '" class="todo-page' . $class . '">';
		foreach ( $value as $index => $day ) {
			if ( $day === $today ) {
				$class = ' current-day';
			} else {
				$class = '';
			}
			$day_number = gmdate( 'j', strtotime( $day ) ); // Day of the month without leading zeros.
			$day_name   = gmdate( 'D', strtotime( $day ) ); // Full day name.
			$month_name = gmdate( 'F', strtotime( $day ) ); // Full day name.
			//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			$html .= '<p class="anony-day-radio ' . $input_name . '"><label for="to-do-' . esc_attr( $day ) . '">';
			$html .= "<span>$day_number</span>";
			$html .= "<span>$day_name</span>";
			$html .= '</label>';
			$html .= sprintf(
				'<input id="to-do-%1$s" class="' . $input_name . '-radio' . $class . '" type="radio" name="' . $input_name . '" value="%2$s"></p>',
				esc_attr( $day ),
				esc_attr( $day )
			);
			//phpcs:enable.
		}
		$html .= '</div>';
	}
	$html .= '<div class="anony-content-slider-control">
				<a class="anony-content-slider-prev button">
					<span class="anony-greater-than anony-content-slider-nav">
						<span class="top"></span><span class="bottom"></span>
					</span>
				</a>
				<a class="anony-content-slider-next button">
					<span class="anony-smaller-than anony-content-slider-nav">
						<span class="top"></span><span class="bottom"></span>
					</span>
				</a>
			</div>';
	$html .= '</div>';
	return $html;
}
