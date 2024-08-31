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
	$bookable_days = snks_timetables_unique_dates( $bookable_days_obj );

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
 * Get discount percent
 *
 * @param int        $user_id Doctor's ID.
 * @param string|int $period Session period.
 * @return int
 */
function snks_get_period_discount( $user_id, $period ) {
	$discount_percent = get_user_meta( $user_id, 'discount_percent' . $period, true );
	if ( ! $discount_percent || empty( $discount_percent ) ) {
		return 0;
	}
	return absint( $discount_percent );
}
/**
 * Render periods filter
 *
 * @param int $user_id User's ID.
 * @return void
 */
function snks_periods_filter( $user_id ) {
	$avialable_periods = snks_get_available_periods( $user_id );
	$country           = 'EG';
	$pricings          = snks_doctor_pricings( $user_id );
	$has_discount      = snks_discount_eligible( $user_id );
	if ( is_array( $avialable_periods ) ) {
		foreach ( $avialable_periods as $period ) {
			$discount_percent = snks_get_period_discount( $user_id, $period );
			$price            = get_price_by_period_and_country( $period, $country, $pricings );
			if ( ! empty( $discount_percent ) && is_numeric( $discount_percent ) && $has_discount ) {
				$price = $price - ( $price * ( absint( $discount_percent ) / 100 ) );
			}
			?>
			<span class="period_wrapper">
				<label for="period_<?php echo esc_attr( $period ); ?>"><?php printf( '%1$s %2$s ( %3$s %4$s )', esc_html( $period ), 'دقيقة', esc_html( $price ), 'جنيه' ); ?></label>
				<input id="period_<?php echo esc_attr( $period ); ?>" type="radio" name="period" value="<?php echo esc_attr( $period ); ?>" data-price="<?php echo esc_attr( $price ); ?>"/>
			</span>
			<?php
		}
	}
}
/**
 * Render form filter
 *
 * @param int $user_id User's ID.
 * @return string
 */
function snks_form_filter( $user_id ) {
	$html = '';
	ob_start();
	?>
	<div class="attendance_types_wrapper">
		<span class="attendance_type_wrapper">
			<label for="online_attendance_type">أونلاين</label>
			<input id="online_attendance_type" type="radio" name="attendance_type" value="online"/>
		</span>
		<span class="attendance_type_wrapper">
			<label for="offline_attendance_type">أوفلاين</label>
			<input id="offline_attendance_type" type="radio" name="attendance_type" value="offline"/>
		</span>
	</div>
	<div class="periods_wrapper"></div>
	<input type="hidden" name="filter_user_id" value="<?php echo esc_attr( $user_id ); ?>"/> 
	<?php
	$html .= ob_get_clean();
	return $html;
}
/**
 * Generate hourly form
 *
 * @param int    $user_id User's ID.
 * @param int    $period Session period.
 * @param string $price Price.
 * @param string $_attendance_type Attendance type.
 * @param string $edit_id ID for booking to be edited.
 * @return string
 */
function snks_generate_consulting_form( $user_id, $period, $price, $_attendance_type, $edit_id = '' ) {
	if ( ! $user_id ) {
		return 'Form error!';
	}
	$html       = '';
	$settings   = snks_doctor_settings( $user_id );
	$days_count = ! empty( $settings['form_days_count'] ) ? absint( $settings['form_days_count'] ) : 30;
	if ( $days_count > 30 ) {
		$days_count = 30;
	}
	$__for             = '+' . $days_count . ' day';
	$bookable_days_obj = get_bookable_dates( $user_id, $period, $__for, $_attendance_type );

	if ( empty( $bookable_days_obj ) ) {
		return '<p>عفواً! لا تتوفر مواعيد للحجز</p>';
	}

	$booking_id  = $edit_id;
	$submit_text = 'حجز موعد';
	//phpcs:disable
	if ( ! empty( $booking_id && is_numeric( $booking_id ) ) ) {
		$booking = snks_get_timetable_by( 'ID', absint( $booking_id ) );
		if ( ! $booking || get_current_user_id() !== absint( $booking->client_id ) ) {
			return '<p>عفواً! الموعد غير متاح أو ليس لديك صلاحية تحرير الموعد</p>';
		}
		$booking_id  = $booking->ID;
		$submit_text = 'تعديل الموعد';
	}
	//phpcs:enable
	$bookable_dates_times = wp_list_pluck( $bookable_days_obj, 'date_time' );
	$bookable_days        = array_unique(
		array_map(
			function ( $value ) {
				return gmdate( 'Y-m-d', strtotime( $value ) );
			},
			$bookable_dates_times,
		)
	);

	$submit_action = snks_encrypted_doctor_url( snks_url_get_doctors_id() );
	$submit_action = add_query_arg( 'direct_add_to_cart', '1', $submit_action );

	$n_bookable_days = array_unique( $bookable_days );
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
		$html .= '<form id="consulting-form-' . esc_attr( $period ) . '" class="consulting-form consulting-form-' . esc_attr( $period ) . '" action="' . $submit_action . '" method="post">';

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
		$html .= '<div id="snks-available-hours-wrapper">';
		$html .= '<h5>';
		$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path d="M20.75 13.25C20.75 18.08 16.83 22 12 22C7.17 22 3.25 18.08 3.25 13.25C3.25 8.42 7.17 4.5 12 4.5C16.83 4.5 20.75 8.42 20.75 13.25Z" stroke="#707070" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		<path d="M12 8V13" stroke="#707070" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		<path d="M9 2H15" stroke="#707070" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>';
		$html .= '&nbsp;تحديد وقت الحجز';
		$html .= '</h5>';
		$html .= '<div class="snks-available-hours"></div>';
		$html .= '</div>';
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
 * Render consulting hours items
 *
 * @param array $availables Available bookings.
 * @return string
 */
function snks_render_consulting_hours_items( $availables ) {
	$html = '';

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
	return $html;
}

/**
 * Render clinic
 *
 * @param array $clinic Clinic data.
 * @return string
 */
function snks_render_clinic( $clinic ) {
	$html  = '';
	$html .= '<ul class="offline-clinic-details">';
	$html .= sprintf( '<li><strong>%1$s : <strong>%2$s</li>', 'إسم العيادة', $clinic['clinic_title'] );
	$html .= sprintf( '<li><strong>%1$s : <strong>%2$s</li>', 'رقم السكرتارية', $clinic['clinic_phone'] );
	$html .= sprintf( '<li><strong>%1$s : <strong>%2$s</li>', 'العنوان', $clinic['clinic_address'] );
	$html .= sprintf( '<li><strong>%1$s : <strong><a href="%2$s">%2$s</a></li>', 'اللوكيشن', $clinic['google_map'] );
	$html .= '<ul>';
	return $html;
}
/**
 * Render offline clinics details
 *
 * @param array $availables Available bookings.
 * @param mixed $user_id User's ID.
 * @return string
 */
function snks_render_offline_clinics_details( $availables, $user_id = false ) {
	$html    = '';
	$grouped = snks_group_objects_by( $availables, 'clinic' );
	foreach ( $grouped as $clinic => $group ) {
		$clinic_details = snks_get_clinic( $clinic, $user_id );
		$html          .= snks_render_clinic( $clinic_details );
	}
	return $html;
}

/**
 * Render doctor clinics
 *
 * @param mixed $user_id User's ID.
 * @return string
 */
function snks_render_doctor_clinics( $user_id = false ) {
	$html    = '';
	$clinics = snks_get_clinics( $user_id );
	if ( is_array( $clinics ) && ! empty( $clinics ) ) {
		foreach ( $clinics as $clinic ) {
			$html .= snks_render_clinic( $clinic );
		}
	} else {
		echo '<p>عفواً! لا توجد معلومات عن العيادات حالياَ.</p>';
	}
	return $html;
}
/**
 * Render online consulting hours item
 *
 * @param array $availables Available bookings.
 * @return string
 */
function snks_render_online_consulting_hours( $availables ) {
	return snks_render_consulting_hours_items( $availables );
}

/**
 * Render offline consulting hours
 *
 * @param array $availables Available bookings.
 * @param mixed $user_id User's ID.
 * @return string
 */
function snks_render_offline_consulting_hours( $availables, $user_id = false ) {
	$html    = '';
	$grouped = snks_group_objects_by( $availables, 'clinic' );
	foreach ( $grouped as $clinic => $group ) {
		$clinic_details = snks_get_clinic( $clinic, $user_id );

		$html .= '<div>';
		$html .= '<h3>' . $clinic_details['clinic_title'] . '</h3>';
		$html .= snks_render_consulting_hours_items( $group );
		$html .= '<div>';
	}
	return $html;
}
/**
 * Render consulting form hours.
 *
 * @param array $availables An array of available hours.
 * @param array $_attendance_type Attendance type.
 * @param mixed $user_id Users ID.
 * @return string
 */
function snks_render_consulting_hours( $availables, $_attendance_type, $user_id = false ) {
	usort( $availables, 'snks_compare_times' );
	$html = '';
	if ( ! empty( $availables ) ) {

		if ( 'online' === $_attendance_type ) {
			$html .= '<ul>';
			$html .= snks_render_online_consulting_hours( $availables );
			$html .= '</ul>';
		}

		if ( 'offline' === $_attendance_type ) {
			$html .= '<ul>';
			$html .= snks_render_offline_consulting_hours( $availables, $user_id );
			$html .= '</ul>';
		}
	} else {
		$html = '<ul><li>' . esc_html__( 'Sorry! No available hour.' ) . '</li></ul>';
	}
	return $html;
}

/**
 * Render edit button
 *
 * @param int $booking_id Booking ID.
 * @param int $doctor_id Doctor's ID.
 * @return string
 */
function snks_edit_button( $booking_id, $doctor_id ) {
	return '<a href="' . add_query_arg( 'edit-booking', $booking_id, snks_encrypted_doctor_url( $doctor_id ) ) . '" title="تحرير" class="edit-booking"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M14.19 0H5.81C2.17 0 0 2.17 0 5.81V14.18C0 17.83 2.17 20 5.81 20H14.18C17.82 20 19.99 17.83 19.99 14.19V5.81C20 2.17 17.83 0 14.19 0ZM8.95 15.51C8.66 15.8 8.11 16.08 7.71 16.14L5.25 16.49C5.16 16.5 5.07 16.51 4.98 16.51C4.57 16.51 4.19 16.37 3.92 16.1C3.59 15.77 3.45 15.29 3.53 14.76L3.88 12.3C3.94 11.89 4.21 11.35 4.51 11.06L8.97 6.6C9.05 6.81 9.13 7.02 9.24 7.26C9.34 7.47 9.45 7.69 9.57 7.89C9.67 8.06 9.78 8.22 9.87 8.34C9.98 8.51 10.11 8.67 10.19 8.76C10.24 8.83 10.28 8.88 10.3 8.9C10.55 9.2 10.84 9.48 11.09 9.69C11.16 9.76 11.2 9.8 11.22 9.81C11.37 9.93 11.52 10.05 11.65 10.14C11.81 10.26 11.97 10.37 12.14 10.46C12.34 10.58 12.56 10.69 12.78 10.8C13.01 10.9 13.22 10.99 13.43 11.06L8.95 15.51ZM15.37 9.09L14.45 10.02C14.39 10.08 14.31 10.11 14.23 10.11C14.2 10.11 14.16 10.11 14.14 10.1C12.11 9.52 10.49 7.9 9.91 5.87C9.88 5.76 9.91 5.64 9.99 5.57L10.92 4.64C12.44 3.12 13.89 3.15 15.38 4.64C16.14 5.4 16.51 6.13 16.51 6.89C16.5 7.61 16.13 8.33 15.37 9.09Z" fill="#024059"/>
	</svg>
	</a>';
}
/**
 * Booking item template
 *
 * @param object $record Timetable object.
 * @return string
 */
function snks_booking_item_template( $record ) {
	ob_start();
	$details = array(
		'name'     => array(
			'icon' => '/wp-content/uploads/2024/08/card.png',
			'link' => '#',
		),
		'phone'    => array(
			'icon' => '/wp-content/uploads/2024/08/phone.png',
			'link' => 'tel:{phone}',
		),
		'whatsapp' => array(
			'icon' => '/wp-content/uploads/2024/08/whatsapp.png',
			'link' => 'https://wa.me/{whatsapp}',
		),

	);
	//phpcs:disable
	?>
	<div id="snks-booking-item-<?php echo esc_attr( $record->ID ) ?>" data-datetime="<?php echo esc_attr( $record->date_time ) ?>" class="snks-booking-item {status_class}">
		<div class="anony-grid-row">
			<div class="anony-grid-col anony-grid-col-2 snks-bg">
				<input type="checkbox" class="bulk-action-checkbox" name="bulk-action[]" data-date="<?php echo snks_localize_time( gmdate( 'Y-m-d h:i a', strtotime( str_replace(' ', 'T', $record->date_time ) ) ) ); ?>" data-doctor="<?php echo $record->user_id; ?>" data-patient="<?php echo $record->client_id; ?>" value="<?php echo $record->ID; ?>">

				<div class="attandance_type rotate-90" style="position:absolute;top:calc(50% - 15px);display: flex;align-items: center;">
					<strong style="font-size:20px;margin-left:5px">{attandance_type}</strong>
					<img style="max-width:35px;margin:0" src="{attandance_type_image}"/>
				</div>
			</div>

			<div class="anony-grid-col anony-grid-col-<?php echo 'online' === $record->attendance_type ? '8' : '10 snks-offline-border-radius'; ?>">

				<div class="anony-grid-row snks-booking-item-row">
					<div class="anony-grid-col anony-grid-col-3 snks-item-icons-bg anony-flex">
						<img class="anony-padding-5" src="/wp-content/uploads/2024/08/clock.png"/>
						
					</div>
					<div class="anony-grid-col anony-grid-col-9 anony-flex flex-h-center flex-v-center">
						<div class="anony-grid-row anony-full-height">
							<div class="anony-grid-col anony-grid-col-6 anony-full-height" style="padding-left:2px;font-size:18px">
								<div class="snks-secondary-bg anony-full-width anony-center-text anony-full-height anony-flex flex-h-center flex-v-center">{starts}</div>
							</div>
							
							<div class="anony-grid-col anony-grid-col-6 anony-full-height" style="padding-right:2px;font-size:18px">

								<div class="snks-secondary-bg anony-full-width anony-center-text anony-full-height anony-flex flex-h-center flex-v-center">{period} دقيقة</div>

							</div>
						</div>
			
					</div>
					
				</div>
				<?php foreach ( $details as $placeholder => $detail ) {
					?>
					<!--<?php echo $placeholder; ?>-->
					<div id="<?php echo $placeholder; ?>" class="anony-grid-row snks-booking-item-row">
						<div class="anony-grid-col anony-grid-col-3 snks-item-icons-bg anony-flex">
							<img class="anony-padding-5" src="<?php echo $detail['icon']; ?>"/>
						</div>
						<div class="anony-grid-col anony-grid-col-9 anony-flex flex-h-center flex-v-center snks-secondary-bg" style="margin-top:4px;">
							<a style="color:#024059;font-size:18px;font-weight:bold" href="<?php echo $detail['link']; ?>">{<?php echo $placeholder; ?>}</a>
						</div>
						
					</div>
					<!--/<?php echo $placeholder; ?>-->
				<?php } ?>
				<!--timer-->
				<div class="anony-grid-row snks-booking-item-row">
					<div class="anony-grid-col anony-grid-col anony-flex flex-h-center flex-v-center snks-secondary-bg" style="margin-top:4px;">
						<span style="color:#024059;font-size:18px;font-weight:bold">{snks_timer}</span>
					</div>
				</div>
				<!--/timer-->
			</div>
			<?php if ( 'online' === $record->attendance_type ) { ?>
			<div class="anony-grid-col anony-grid-col-2 snks-bg" style="border-top-left-radius:20px;border-bottom-left-radius:20px">
				<a class="snks-count-down rotate-90 anony-flex atrn-button snks-start-meeting" href="{button_url}" data-url="{room_url}" style="position:absolute;top:calc(50% - 15px);color:#fff">{button_text}</a>
			</div>
			<?php } ?>
		</div>
		<!--doctoraction-->
		<div class="anony-flex flex-h-center">
			<button data-title="تعديل" class="snks-change anony-padding-5 snks-bg" style="width:80px" data-id="<?php echo esc_attr( $record->ID ) ?>" data-date="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $record->date_time ) ) ) ?>">تعديل</button>
			<button class="snks-notes anony-padding-5 snks-bg" style="margin-right: 5px;width:80px" data-id="<?php echo esc_attr( $record->ID ) ?>">ملاحظات</button>
		</div>
		<!--/doctoraction-->
	</div>
	<?php
	//phpcs:enable
	return ob_get_clean();
}
/**
 * Template string replace
 *
 * @param object $record The Record.
 * @return string
 */
function template_str_replace( $record ) {
	$user_details = snks_user_details( $record->client_id );
	$button_text  = 'ابدأ الجلسة';

	$template              = snks_booking_item_template( $record );
	$attandance_type_image = SNKS_CAMERA;
	$attandance_type_text  = 'أونلاين';
	if ( 'offline' === $record->attendance_type ) {
		$attandance_type_image = SNKS_OFFLINE;
		$attandance_type_text  = 'أوفلاين';
	}
	$first_name = ! empty( $user_details['billing_first_name'] ) ? $user_details['billing_first_name'] : '';
	$last_name  = ! empty( $user_details['billing_last_name'] ) ? $user_details['billing_last_name'] : '';
	$phone      = ! empty( $user_details['billing_phone'] ) ? $user_details['billing_phone'] : '';
	$whatsapp   = '';
	if ( ! empty( $user_details['whatsapp'] ) ) {
		$whatsapp = $user_details['whatsapp'];
	} else {
		$template = preg_replace( '/<!--whatsapp-->.*?<!--\/whatsapp-->/s', '', $template );
	}
	$template = preg_replace( '/<!--timer-->.*?<!--\/timer-->/s', '', $template );

	return str_replace(
		array(
			'{session_id}',
			'{starts}',
			'{period}',
			'{attandance_type_image}',
			'{attandance_type}',
			'{name}',
			'{phone}',
			'{whatsapp}',
			'{button_url}',
			'{button_text}',
			'{status_class}',
		),
		array(
			$record->ID,
			str_replace( array( ' am', ' pm' ), array( ' ص', ' م' ), gmdate( 'g:i a', strtotime( $record->starts ) ) ),
			esc_html( $record->period ),
			$attandance_type_image,
			$attandance_type_text,
			esc_html( $first_name . ' ' . $last_name ),
			esc_html( $phone ),
			esc_html( $whatsapp ),
			esc_url( site_url( 'meeting-room/?room_id=' . $record->ID ) ),
			$button_text,
			'',
		),
		$template
	);
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
function patient_template_str_replace( $record, $edit, $_class, $room ) {
	if ( snks_is_patient() ) {
		$client_id = $record->client_id;
	}
	$user_details        = snks_user_details( $record->user_id );
	$button_text         = 'ابدأ الجلسة';
	$scheduled_timestamp = strtotime( $record->date_time );
	$current_timestamp   = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
	$room                = site_url( 'meeting-room/?room_id=' . $record->ID );

	if ( isset( $client_id ) && $current_timestamp > $scheduled_timestamp && ( $current_timestamp - $scheduled_timestamp ) > 60 * 15 ) {
		$_class = 'snks-time-passed';
		$room   = '#';
	}
	if ( isset( $client_id ) && $current_timestamp < $scheduled_timestamp ) {
		$_class = 'snks-remaining';
		$room   = '#';
	}
	if ( 'cancelled' === $record->session_status ) {
		$_class = 'snks-cancelled';
		$room   = '#';
	}
	$template              = snks_booking_item_template( $record );
	$attandance_type_image = SNKS_CAMERA;
	$attandance_type_text  = 'أونلاين';
	if ( 'offline' === $record->attendance_type ) {
		$attandance_type_image = SNKS_OFFLINE;
		$attandance_type_text  = 'أوفلاين';
	}

	$first_name = ! empty( $user_details['billing_first_name'] ) ? $user_details['billing_first_name'] : '';
	$last_name  = ! empty( $user_details['billing_last_name'] ) ? $user_details['billing_last_name'] : '';
	$phone      = ! empty( $user_details['billing_phone'] ) ? $user_details['billing_phone'] : '';
	$whatsapp   = '';
	$template   = preg_replace( '/<!--whatsapp-->.*?<!--\/whatsapp-->/s', '', $template );
	$template   = preg_replace( '/<!--doctoraction-->.*?<!--\/doctoraction-->/s', '', $template );
	return str_replace(
		array(
			'{session_id}',
			'{starts}',
			'{period}',
			'{attandance_type_image}',
			'{attandance_type}',
			'{name}',
			'{phone}',
			'{whatsapp}',
			'{button_url}',
			'{button_text}',
			'{snks_timer}',
			'{status_class}',
		),
		array(
			$record->ID,
			str_replace( array( ' am', ' pm' ), array( ' ص', ' م' ), gmdate( 'g:i a', strtotime( $record->starts ) ) ),
			esc_html( $record->period ),
			$attandance_type_image,
			$attandance_type_text,
			esc_html( $first_name . ' ' . $last_name ),
			esc_html( $phone ),
			esc_html( $whatsapp ),
			esc_url( $room ),
			$button_text,
			'<span class="snks-apointment-timer"></span>',
			$_class,
		),
		$template
	);
}
add_shortcode(
	'snks_doctor_change_appointment',
	function () {
		$output = '';
		if ( snks_is_doctor() && is_page( 'my-bookings' ) ) {
			$bookable_days_obj = get_all_bookable_dates( get_current_user_id() );
			$bookable_days     = snks_timetables_unique_dates( $bookable_days_obj );
			$output           .= '<form method="post" action="">';
			$output           .= '<select data-date="" id="change-to-date" name="change-to-date">';
			ob_start();
			echo '<option value="0">حدد التاريخ</option>';
			foreach ( $bookable_days as $date ) {
				?>
				<option value="<?php echo esc_html( $date ); ?>"><?php echo esc_html( $date ); ?></option>
				<?php
			}
			$output .= ob_get_clean();
			$output .= '</select>';
			$output .= '<div id="change-to-list"></div>';
			$output .= wp_nonce_field( 'change_appointment', 'change_appointment_nonce' );
			$output .= '<input type="text" style="display:none" id="old-appointment" name="old-appointment" value=""/>';
			$output .= '<input type="submit" class="snks=bg anony-padding-10 anony-full-width" name="submit" value="حفظ"/>';
		}
		return $output;
	}
);
/**
 * Generate bookings
 *
 * @return string
 */
function snks_generate_bookings() {
	$timetables = snks_get_doctor_sessions( 'all', 'open', true );
	if ( empty( $timetables ) ) {
		return '<p>ليس لديك حجوزات حتى الآن!</p>';
	}
	$days_sorted = array( 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri' );

	uksort(
		$timetables,
		function ( $a, $b ) use ( $days_sorted ) {
			$pos_a = array_search( $a, $days_sorted, true );
			$pos_b = array_search( $b, $days_sorted, true );
			return $pos_a - $pos_b;
		}
	);

	$days_labels = json_decode( DAYS_ABBREVIATIONS, true );
	$output      = '';

	if ( is_array( $timetables ) ) {
		$day_groups = snks_group_objects_by( $timetables, 'date' );
		$j          = true;
		foreach ( $day_groups as $date => $timetables ) {
			$main_date   = $date;
			$day         = gmdate( 'D', strtotime( $date ) );
			$output     .= '<div class="snks-timetable-accordion-wrapper" data-id="' . $date . '">';
			$output     .= '<div class="anony-grid-row snks-bg snks-timetable-accordion" data-id="' . $date . '">';
				$output .= '<div class="anony-grid-col anony-grid-col-1 snks-timetables-count anony-inline-flex flex-h-center flex-v-center anony-padding-5" style="background-color:#fff; color:#024059">';
				$output .= count( $timetables );
				$output .= '</div>';
				$output .= '<div class="anony-grid-col anony-grid-col-11 anony-inline-flex flex-h-center flex-v-center anony-padding-10">';
				$output .= $days_labels[ $day ] . '  ' . $date;
				$output .= '<div class="bulk-action-toggle">';
			if ( $j ) {
				$output .= '<span class="bulk-action-toggle-tip">';
				$output .= '<span class="bulk-action-toggle-tip-close">x</span>';
				$output .= 'تأجيل/تأخير';
				$output .= '</span>';
				$j       = false;
			}
				$output .= '<svg fill="#ffffff" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 325.051 325.051" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"/><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M162.523,86.532c-41.904,0-76,34.089-76,75.994c0,41.901,34.096,75.996,76,75.996s76-34.095,76-75.996 C238.523,120.621,204.427,86.532,162.523,86.532z M162.523,226.225c-35.128,0-63.702-28.571-63.702-63.699 c0-35.122,28.574-63.696,63.702-63.696c35.131,0,63.702,28.574,63.702,63.696C226.225,197.653,197.654,226.225,162.523,226.225z"/> <path d="M302.503,130.583h-11.505c-5.975-24.202-18.537-45.916-36.587-63.3l5.765-9.977c6.329-10.965-0.865-26.571-16.382-35.531 c-6.484-3.738-13.654-5.803-20.206-5.803c-8.473,0-15.3,3.419-18.74,9.37l-5.759,9.974c-23.749-6.83-49.345-6.83-73.121,0 l-5.755-9.974c-3.444-5.957-10.269-9.37-18.744-9.37c-6.548,0-13.724,2.065-20.203,5.803C65.752,30.729,58.55,46.33,64.879,57.3 l5.768,9.977c-18.065,17.39-30.615,39.104-36.593,63.312H22.554C9.908,130.589,0,144.613,0,162.525s9.908,31.945,22.554,31.945 h11.499c5.978,24.199,18.534,45.913,36.593,63.303l-5.768,9.98c-6.329,10.964,0.874,26.564,16.387,35.523 c6.479,3.735,13.655,5.801,20.203,5.801c8.476,0,15.3-3.423,18.744-9.367l5.755-9.968c23.77,6.827,49.372,6.827,73.115-0.013 l5.765,9.98c3.44,5.957,10.268,9.367,18.74,9.367c6.552,0,13.722-2.065,20.206-5.801c15.517-8.959,22.711-24.56,16.382-35.523 l-5.765-9.98c18.062-17.39,30.612-39.104,36.587-63.303h11.493c12.64,0,22.554-14.027,22.56-31.945 c0.007-8.043-2.041-15.69-5.764-21.539C315.089,134.375,308.965,130.583,302.503,130.583z M302.492,182.173h-16.399 c-2.918,0-5.428,2.054-6.017,4.906c-5.23,25.196-18.212,47.653-37.536,64.941c-2.168,1.94-2.684,5.141-1.225,7.656l8.215,14.226 c2.432,4.203-2.145,13.103-11.884,18.723c-8.779,5.092-19.636,5.272-22.146,0.938l-8.215-14.22 c-1.123-1.946-3.177-3.075-5.32-3.075c-0.643,0-1.297,0.108-1.928,0.307c-24.205,7.98-50.771,7.992-75.018,0.006 c-2.759-0.889-5.786,0.24-7.248,2.769l-8.202,14.214c-2.504,4.335-13.352,4.154-22.149-0.932 c-9.74-5.62-14.309-14.52-11.886-18.723l8.214-14.226c1.45-2.521,0.94-5.716-1.222-7.649 c-19.332-17.294-32.314-39.752-37.542-64.948c-0.594-2.853-3.11-4.899-6.02-4.899H22.548c-4.837,0-10.256-8.401-10.256-19.648 c0-11.241,5.419-19.639,10.256-19.639h16.405c2.916,0,5.432-2.047,6.02-4.902c5.233-25.208,18.215-47.667,37.539-64.948 c2.17-1.939,2.681-5.137,1.225-7.656l-8.214-14.222c-2.429-4.207,2.147-13.105,11.886-18.726 c8.797-5.083,19.639-5.269,22.149-0.934l8.203,14.216c1.456,2.528,4.488,3.672,7.248,2.774c24.232-7.974,50.801-7.974,75.03,0 c2.769,0.892,5.783-0.24,7.248-2.768L215.5,31.51c2.504-4.32,13.354-4.144,22.146,0.942c9.739,5.618,14.304,14.523,11.878,18.729 l-8.215,14.222c-1.453,2.522-0.938,5.716,1.225,7.656c19.323,17.282,32.306,39.74,37.53,64.945c0.601,2.852,3.11,4.9,6.022,4.9 h16.411c2.174,0,4.504,1.714,6.413,4.71c2.438,3.843,3.837,9.284,3.837,14.94C312.748,173.772,307.326,182.173,302.492,182.173z"/> </g> </g> </g></svg>';
				$output .= '</div>';
				$output .= '</div>';
			$output     .= '</div>';
			$output     .= '<div style="background-color:#dcdcdc;" class="anony-grid-row snks-timetable-accordion-actions anony-padding-10 anony-flex flex-h-center flex-v-center">';
			$output     .= '<div class="anony-grid-col anony-grid-col-6">';
			$output     .= '<button data-title="تأجيل" class="snks-postpon anony-curved-5 anony-padding-5 snks-bg anony-full-width">تأجيل الجلسات</button>';
			$output     .= '</div>';

			$output .= '<div class="anony-grid-col anony-grid-col-6">';
			$output .= '<button data-title="تأخير" class="snks-delay anony-curved-5 anony-padding-5 snks-bg anony-full-width">تأخير الجلسات</button>';
			$output .= '</div>';

			$output .= '</div>';
			$output .= '<div class="snks-timetable-accordion-content" style="background-color:#fff;padding:10px 0 10px 10px" id="' . $date . '">';

			foreach ( $day_groups as $date => $timetables ) {
				/**
				* Timetable queried object.
				*
				* @var object $data
				*/
				foreach ( $timetables as $data ) {
					$output .= template_str_replace( $data );
				}
			}
			$output .= '</div>';
		}
	}
	return $output;
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
 * @return string
 */
function snks_render_sessions_listing( $tense ) {

	$sessions = snks_get_patient_sessions( $tense );
	$edit     = '';
	$output   = '';
	if ( $sessions && is_array( $sessions ) ) {
		foreach ( $sessions as $session ) {
			$doctor_settings = snks_doctor_settings( $session->user_id );
			if ( snks_is_past_date( $session->date_time ) ) {
				$class = 'start';
				$room  = '#';
			} else {
				$class = 'remaining';
				$room  = '#';
				if ( 'on' === $doctor_settings['allow_appointment_change'] ) {
					$order_id      = $session->order_id;
					$edited_before = get_post_meta( $order_id, 'booking-edited', true );
					$class         = 'remaining';
					$room          = '#';
					$diff_seconds  = snks_diff_seconds( $session );
					// Compare the input date and time with the modified current date and time.
					if ( ! snks_is_doctor() && ( ! $edited_before || empty( $edited_before ) ) && $diff_seconds > snks_get_edit_before_seconds( $doctor_settings ) ) {
						$edit = snks_edit_button( $session->ID, $session->user_id );
					}
				}
			}
			$output .= patient_template_str_replace( $session, $edit, $class, $room );

			$output = str_replace( '{doctor_actions}', '', $output );
		}
	} else {
		$output = 'عفواَ ليس لديك حجوزات حاليا!';
	}
	return $output;
}

add_action(
	'wp_footer',
	function () {
		return;
	}
);