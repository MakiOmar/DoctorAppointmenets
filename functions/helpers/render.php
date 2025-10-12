<?php
/**
 * Helpers
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

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_dump,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.SlowDBQuery.slow_db_query_meta_key

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
	$months_labels   = json_decode( MONTHS_FULL_NAMES, true );
	foreach ( $n_bookable_days as $index => $current_date ) {
		//phpcs:disable Universal.Operators.StrictComparisons.LooseEqual
		$day_number = gmdate( 'j', strtotime( $current_date ) ); // Day of the month without leading zeros.
		$day_name   = gmdate( 'D', strtotime( $current_date ) ); // Full day name.
		$month_name = gmdate( 'F', strtotime( $current_date ) ); // Full day name.
		//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$html .= '<div class="anony-content-slide item snks-bg anony-day-radio ' . $input_name . '"><label for="' . esc_attr( $current_date ) . '-' . $period . '">';
		$html .= "<span class='hacen_liner_print-outregular'>$months_labels[$month_name]</span>";
		$html .= "<span class='hacen_liner_print-outregular anony-day-number'>$day_number</span>";
		$html .= '<span class="hacen_liner_print-outregular">' . snks_localize_day( $day_name ) . '</span>';
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
 * @param int    $user_id User's ID.
 * @param string $attendance_type Attendance type.
 * @param mixed  $edit_booking Edit booking ID.
 * @return void
 */
function snks_periods_filter( $user_id, $attendance_type = 'both', $edit_booking = false ) {
	// Make sure to choose an appointment with the same period.
	if ( $edit_booking ) {
		$get_edit_booking = snks_get_timetable_by( 'ID', absint( $edit_booking ) );
		if ( $get_edit_booking ) {
			$avialable_periods = array( $get_edit_booking->period );
		}
	}
	if ( ! isset( $avialable_periods ) ) {
		$avialable_periods = snks_get_available_periods( $user_id, $attendance_type );
	}
	$country      = snsk_ip_api_country( false );
	
	// Determine which pricing to use based on attendance type
	$pricing_type = 'online'; // default
	if ( 'offline' === $attendance_type ) {
		$pricing_type = 'offline';
	} elseif ( 'both' === $attendance_type ) {
		// For 'both', we'll show online pricing by default, but this can be overridden by JavaScript
		$pricing_type = 'online';
	}
	
	$pricings     = snks_doctor_pricings( $user_id, $pricing_type );
	$has_discount = is_user_logged_in() ? snks_discount_eligible( $user_id ) : false;
	if ( is_array( $avialable_periods ) && ! empty( $avialable_periods ) ) {
		echo '<div class="anony-padding-10 anony-flex anony-space-between anony-full-width anony-grid-row">';
		foreach ( $avialable_periods as $period ) {
			$discount_percent = snks_get_period_discount( $user_id, $period );
			$price            = get_price_by_period_and_country( $period, $country, $pricings );
			if ( ! empty( $discount_percent ) && is_numeric( $discount_percent ) && $has_discount ) {
				$price = $price - ( $price * ( absint( $discount_percent ) / 100 ) );
			}
			[$converted_price, $currency_label] = acrsw_currency( $price, 'جنيه مصري' );
			?>
			<span class="period_wrapper anony-grid-col anony-grid-col-4">
				<label class="anony-inline-flex anony-flex-column flex-h-center flex-v-center anony-full-width" for="period_<?php echo esc_attr( $period ); ?>">
				<?php
				printf(
					'<span class="hacen_liner_print-outregular anony-flex anony-flex-start flex-v-center snks-period-label snks-bg-secondary anony-padding-5 anony-margin-3" id="snks-period-label-%1$s">%2$s %3$s %4$s</span>
					<span class="hacen_liner_print-outregular anony-flex flex-h-center flex-v-center snks-period-price snks-bg-secondary anony-padding-5 anony-margin-3">%5$s %6$s</span>',
					esc_attr( $period ),
					'جلسة',
					esc_html( $period ),
					'دقيقة',
					esc_html( $converted_price ),
					esc_html( $currency_label )
				);
				?>
								</label>
				<input id="period_<?php echo esc_attr( $period ); ?>" type="radio" name="period" value="<?php echo esc_attr( $period ); ?>" data-price="<?php echo esc_attr( $price ); ?>" data-pricing-type="<?php echo esc_attr( $pricing_type ); ?>"/>
			</span>
			<?php
		}
		echo '</div>';
	} else {
		echo '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
	}
}

/**
 * Generate preview
 *
 * @return string
 */
function snks_generate_preview() {
	$timetables = snks_get_preview_timetable();
	$output     = '';
	if ( empty( $timetables ) ) {
		$output .= '<p style="text-align:center;padding:20px 10px">لم تقم بإضافة مواعيد</p>';
	} else {
		$off_days     = snks_get_off_days();
		$days_indexes = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$days_sorted  = array( 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri' );

		uksort(
			$timetables,
			function ( $a, $b ) use ( $days_sorted ) {
				$pos_a = array_search( $a, $days_sorted, true );
				$pos_b = array_search( $b, $days_sorted, true );
				return $pos_a - $pos_b;
			}
		);

		if ( is_array( $timetables ) ) {
			foreach ( $timetables as $day => $timetable ) {
				$output     .= '<div class="preview-container">';
				$date_groups = snks_group_by( 'date', $timetable );
				// https://github.com/erguncaner/table.
				// First create a table.
				$table = new Table(
					array(
						'id'    => $day . '-preview-timetable',
						'class' => 'preview-timetable',
					)
				);
				// Create table columns with a column key and column object.
				$table->addColumn( 'day', new TableColumn( 'تبدأ من' ) );
				$table->addColumn( 'ends', new TableColumn( 'تنتهي عند' ) );
				$table->addColumn( 'period', new TableColumn( 'المدة' ) );
				$table->addColumn( 'attendance', new TableColumn( 'عيادة/أونلاين' ) );
				$table->addColumn( 'actions', new TableColumn( 'الخيارات' ) );
				$position = 0;
				foreach ( $date_groups as $date => $details ) {
					if ( in_array( $date, $off_days, true ) ) {
						// is_off  ' snks-is-off'.
						continue;
					} else {
						$is_off = '';
					}
					if ( count( $details ) > 1 || count( $details ) == 1 ) {
						// Associate cells with columns.
						$cells = array(
							'day' => new TableCell( snks_localize_day( $day ) . ' ' . $date, array( 'colspan' => '5' ) ),
						);
						// define row attributes.
						$attrs = array(
							'id'          => 'timetable-tab-' . $day . '-' . $position,
							'class'       => 'timetable-preview-tab' . $is_off,
							'data-target' => 'timetable-' . $date,
						);
						$table->addRow( new TableRow( $cells, $attrs ) );
						++$position;
					}
					$class = count( $details ) > 1 || count( $details ) == 1 ? ' timetable-preview-item' : '';
					foreach ( $details as $data ) {
						$index = array_search( $data, $timetable, true );
						if ( in_array( $date, $off_days, true ) ) {
							$actions = 'أجازة';
						} else {
							$actions = snks_preview_actions( $data['day'], $index );
						}
						if ( 'offline' === $data['attendance_type'] ) {
							$clinic = snks_get_clinic( $data['clinic'] );
							if ( ! $clinic ) {
								continue;
							}
							$ttendance = $clinic['clinic_title'];
						} else {
							$ttendance = 'أونلاين';
						}
						// Associate cells with columns.
						$cells = array(
							'day'        => new TableCell( snks_localize_time( gmdate( 'h:i a', strtotime( $data['starts'] ) ) ), array( 'data-label' => 'تبدأ من' ) ),
							'ends'       => new TableCell( snks_localize_time( gmdate( 'h:i a', strtotime( $data['ends'] ) ) ), array( 'data-label' => 'تنتهي عند' ) ),
							'period'     => new TableCell( $data['period'], array( 'data-label' => 'المدة' ) ),
							'attendance' => new TableCell( $ttendance, array( 'data-label' => 'الحضور' ) ),
							'actions'    => new TableCell( $actions, array( 'data-label' => 'الخيارت' ) ),
						);
						// define row attributes.
						$attrs = array(
							'id'    => 'timetable-' . $data['day'] . '-' . $index,
							'class' => 'timetable-' . $date . $class . $is_off,
						);
						$table->addRow( new TableRow( $cells, $attrs ) );
					}
				}
				// Finally generate html.
				$output .= $table->html();
				$output .= snks_render_conflicts( $data['day'] );
				$output .= '<a data-day="' . $data['day'] . '" data-day-label="' . snks_localize_day( $data['day'] ) . '" data-day-index="' . array_search( $data['day'], $days_indexes, true ) . '" class="custom-timetabl-trigger snks-bg anony-default-padding anony-default-margin-top rounded anony-full-width anony-center-text">إضافة موعد ليوم ' . snks_localize_day( $data['day'] ) . '</a>';
				$output .= '</div>';
			}
		}
	}
	$output .= '<br/><center>هل أنت جاهز للنشر؟</center><br/>';
	$output .= '<center><button id="insert-timetable">نشر</button></center>';
	$output .= '<center id="insert-timetable-msg"></center>';
	return $output;
}
add_action(
	'wp_footer',
	function () {
		if ( snks_is_patient() ) {
			return;
		}
		?>
		<input type="hidden" id="doctor-off-days" value="<?php echo implode( ',', snks_get_off_days() ); ?>"/>
		<input type="hidden" id="custom-timetable-day" value=""/>
		<a href="#" id="custom-timetabl-trigger"></a>
		<a href="#" id="snks-change-trigger"></a>
		<?php
	}
);
/**
 * Render periods filter
 *
 * @param int $user_id User's ID.
 * @return void
 */
function snks_listing_periods( $user_id ) {
	$avialable_periods = snks_get_periods( $user_id );
	$country           = snsk_ip_api_country( false );
	$online_pricings   = snks_doctor_online_pricings( $user_id );
	$offline_pricings  = snks_doctor_offline_pricings( $user_id );
	
	if ( ! empty( $online_pricings ) && is_array( $avialable_periods ) ) {
		echo '<div class="anony-padding-10 anony-flex flex-h-center flex-v-center anony-full-width">';
		foreach ( $avialable_periods as $period ) {
			$online_price = get_price_by_period_and_country( $period, $country, $online_pricings );
			$offline_price = get_price_by_period_and_country( $period, $country, $offline_pricings );
			?>
			<span class="anony-grid-col hacen_liner_print-outregular anony-flex flex-h-center flex-v-center anony-padding-5 anony-margin-5" style="background-color:#fff;border-radius:25px;width: 180px;font-size: 16px;">
				<?php printf( '%1$s %2$s | أونلاين: %3$s | أوفلاين: %4$s', esc_html( $period ), 'د', esc_html( $online_price ), esc_html( $offline_price ) ); ?>
			</span>
			<?php
		}
		echo '</div>';
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
	global $wp;
	$dark_color = ! empty( $_COOKIE['dark_color'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dark_color'] ) ) : '#024059';
	if ( isset( $wp->query_vars ) && isset( $wp->query_vars['doctor_id'] ) ) {
		$clinic_color   = get_user_meta( snks_url_get_doctors_id(), 'clinic_colors', true );
		if ( $clinic_color && '' !== $clinic_color ) {
			$clinics_colors = json_decode( CLINICS_COLORS );
			$clinic_colors  = 'color_' . $clinic_color;
			$dark_color     = esc_attr( $clinics_colors->$clinic_colors[1] );
		}
	}
	?>
	<div class="attendance_types_wrapper">
		<span class="attendance_type_wrapper">
			<label for="online_attendance_type" class="hacen_liner_print-outregular color-change-trigger">
				<img class="snks-light-icon" src="/wp-content/uploads/2024/09/camera-light.png"/>
				<span class="attendance_type_text">جلسة أونلاين</span>
			</label>
			<input id="online_attendance_type" type="radio" name="attendance_type" value="online"/>
		</span>

		<span class="attendance_type_wrapper">
			<label for="offline_attendance_type" class="hacen_liner_print-outregular color-change-trigger">
				<img class="snks-light-icon" src="/wp-content/uploads/2024/09/hand-light.png"/>
				<img class="snks-dark-icon" src="/wp-content/uploads/2024/09/hand-dark.png" style="display: none;"/> 
				<span class="attendance_type_text">جلسة عيادة</span>
			</label>
			<input id="offline_attendance_type" type="radio" name="attendance_type" value="offline"/>
		</span>
	</div>
	<div class="periods_wrapper snks-bg snks-separator anony-full-width"></div>
	<input type="hidden" name="filter_user_id" value="<?php echo esc_attr( $user_id ); ?>"/> 
	<script>
		applyParentImageColorChange(".color-change-trigger", "click", "<?php echo $dark_color; ?>", true);
	</script>
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
	if ( $days_count > 90 ) {
		$days_count = 90;
	}
	$__for             = '+' . $days_count . ' day';
	$bookable_days_obj = get_bookable_dates( $user_id, $period, $__for, $_attendance_type );
	if ( empty( $bookable_days_obj ) ) {
		return '<p>عفواً! لا تتوفر مواعيد للحجز</p>';
	}

	$booking_id  = $edit_id;
	$submit_text = 'حجز الموعد';
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

	$n_bookable_days = array_unique( $bookable_days );
	if ( count( $n_bookable_days ) > 3 ) {
		$slider_class = ' anony-content-slider';
		$slider       = true;
	} else {
		$slider_class = '';
		$slider       = false;
	}

	$direction = is_rtl() ? 'true' : 'false';

	$html  = '';
	$html .= '<form id="consulting-form-' . esc_attr( $period ) . '" class="consulting-form consulting-form-' . esc_attr( $period ) . '" action="' . $submit_action . '" method="post">';

	$html .= '<h5 class="snks-bg anony-padding-5 anony-center-text" style="min-width:75%;border-top-left-radius:10px;border-top-right-radius:10px;padding: 10px 0;">';
	$html .= ' إختر موعد الحجز';
	$html .= '</h5>';
	$html .= '<div class="atrn-form-days anony-content-slider-container">';
	$html .= '<div class="owl-carousel days-container' . esc_attr( $slider_class ) . '">';
	$html .= snks_generate_consulting_days( $user_id, $bookable_days_obj, 'current-month-day', $period, $days_count );
	$html .= '</div>';
	$html .= '</div>';
	$html .= '<hr style="margin:20px 0">';
	$html .= '<div id="snks-available-hours-wrapper">';
	$html .= '<p class="anony-center-text" style="margin-bottom: 10px;">( جميع المواعيد بتوقيت مصر )</p>';
	$html .= '<div class="snks-available-hours"></div>';
	$html .= '</div>';
	$html .= '<input type="hidden" name="create-appointment" value="create-appointment">';
	$html .= '<input type="hidden" id="edit-booking-id" name="edit-booking-id" value="' . $booking_id . '">';
	$html .= '<input type="hidden" id="user-id" name="user-id" value="' . $user_id . '">';
	$html .= '<input type="hidden" id="period" name="period" value="' . $period . '">';
	$html .= '<div id="consulting-form-submit-wrapper">';
	$html .= '<div class="hacen_liner_print-outregular snks-color" style="display: flex;align-items: baseline;"><input type="checkbox" id="terms-conditions" name="terms-conditions" value="yes">';
	$html .= '<a href="/terms.html" style="margin-right:10px" target="_blank">';
	$html .= 'أوافق على الشروط والأحكام وسياسة الاستخدام.';
	$html .= '</a></div>';
	$html .= wp_nonce_field( 'create_appointment', 'create_appointment_nonce' );
	$html .= '<input id="consulting-form-submit" style="margin-top:18px" type="submit" value="' . $submit_text . '">';
	$html .= '<input type="hidden" name="appointment_add_to_cart" value="1">';
	$html .= '</div>';
	$html .= '</form>';

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
		$html .= '<li class="available-time anony-grid-col anony-grid-col-5 anony-padding-3">';
		$html .= '<label class="hacen_liner_print-outregular" for="hour-radio-' . esc_attr( $id ) . '">';

		$html .= sprintf( '%1$s %2$s %3$s', esc_html( gmdate( 'h:i a', strtotime( $available->starts ) ) ), 'إلى', esc_html( gmdate( 'h:i a', strtotime( $available->ends ) ) ) );
		$html .= '<input id="hour-radio-' . esc_attr( $id ) . '" type="radio" class="hour-radio" name="selected-hour" value="' . esc_attr( $id ) . '"/>';
		$html .= '</label>';
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
	if ( ! is_array( $clinic ) ) {
		return '';
	}
	$html = do_shortcode( '[elementor-template id="3023"]' );

	if ( empty( $clinic['google_map'] ) || '#' === $clinic['google_map'] ) {
		// Load the HTML content into a DOMDocument.
		$dom = new DOMDocument();
		$dom->loadHTML( $html );

		// Get elements with the class "clinic_google_map" and remove them.
		$xpath    = new DOMXPath( $dom );
		$elements = $xpath->query( '//div[contains(@class, "clinic_google_map")]' );
		foreach ( $elements as $element ) {
			//phpcs:disable
			$element->parentNode->removeChild( $element );
			//phpcs:enable
		}

		// Output the modified HTML.
		$html = $dom->saveHTML();
	}

	return str_replace(
		array(
			'{clinic_title}',
			'{clinic_phone}',
			'clinic_phone',
			'{clinic_address}',
			'{google_map}',
			'google_map',
		),
		array(
			esc_html( $clinic['clinic_title'] ),
			esc_html( $clinic['clinic_phone'] ),
			esc_html( $clinic['clinic_phone'] ),
			esc_html( $clinic['clinic_address'] ),
			esc_html( $clinic['google_map'] ),
			esc_html( $clinic['google_map'] ),
		),
		$html
	);
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
		$html = '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
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
		if ( ! $clinic_details ) {
			continue;
		}
		$html .= '<li class="offline-clinic-hours">';
		$html .= '<h3 style="color:#024059;text-align:center">' . $clinic_details['clinic_title'] . '</h3>';
		$html .= '<a class="showNextClinic" style="color:#024059;text-align:center">';
		$html .= '( العنوان ورقم التليفون )';
		$html .= '</a>';
		$html .= '<div class="next-clinic-details">' . snks_render_clinic( $clinic_details ) . '</div>';
		$html .= '<ul class="anony-grid-row">';
		$html .= snks_render_consulting_hours_items( $group );
		$html .= '</ul>';
		$html .= '</li>';
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
			$html .= '<ul class="anony-grid-row">';
			$html .= snks_render_consulting_hours_items( $availables );
			$html .= '</ul>';
		}

		if ( 'offline' === $_attendance_type ) {
			$html .= '<ul>';
			$html .= snks_render_offline_consulting_hours( $availables, $user_id );
			$html .= '</ul>';
		}
	} else {
		$html .= '<ul><li>' . esc_html__( 'Sorry! No available hour.' ) . '</li></ul>';
	}
	$html .= '<hr style="margin:20px 0">';
	return $html;
}

/**
 * Render booking details
 *
 * @param array $form_data Booking details.
 * @param bool  $is_booking If used within booking process.
 * @return string
 */
function snks_booking_details( $form_data, $is_booking = true ) {
	if ( empty( $form_data['booking_id'] ) ) {
		return;
	}
	$booking = snks_get_timetable_by( 'ID', $form_data['booking_id'] );
	if ( ! $booking ) {
		return;
	}
	$doctor_details = snks_user_details( $booking->user_id );
	$user           = get_user_by( 'id', $booking->user_id );
	$doctor_url     = snks_encrypted_doctor_url( $user );
	$first_name     = ! empty( $doctor_details['billing_first_name'] ) ? $doctor_details['billing_first_name'] : '';
	$last_name      = ! empty( $doctor_details['billing_last_name'] ) ? $doctor_details['billing_last_name'] : '';
	$name           = $first_name . ' ' . $last_name;
	// Format the booking date and time.
	$booking_date   = gmdate( 'l j F Y', strtotime( $form_data['booking_day'] ) ); // e.g., Saturday 24 October 2024.
	$booking_date   = localize_date_to_arabic( $booking_date );
	$booking_time   = $form_data['booking_hour'];
	$session_type   = 'online' === $booking->attendance_type ? 'أونلاين' : 'أوفلاين';
	$clinic_details = snks_get_clinic( $booking->clinic, $booking->user_id );
	// Generate the table HTML.
	ob_start();
	?>
	<table class="consulting-session-table">
		<tr>
			<td class="consulting-session-label">اسـم المعـالـج</td>
			<td class="consulting-session-data"><?php echo esc_html( $name ); ?></td>
		</tr>
		<tr>
			<td class="consulting-session-label">رابط المعـالـج</td>
			<td class="consulting-session-data"><a href="<?php echo esc_url( $doctor_url ); ?>" target="_blank">إضغط هنا</td>
		</tr>
		<tr>
			<td class="consulting-session-label">نــوع الجـلسـة</td>
			<td class="consulting-session-data"><?php echo esc_html( $session_type ); ?></td>
		</tr>
		<tr>
			<td class="consulting-session-label">مــدة الجـلسـة</td>
			<td class="consulting-session-data"><?php echo esc_html( $form_data['_period'] ); ?> دقيقة</td>
		</tr>
		<tr>
			<td class="consulting-session-label">تاريـخ الجلسـة</td>
			<td class="consulting-session-data"><?php echo esc_html( $booking_date ); ?></td>
		</tr>
		<tr>
			<td class="consulting-session-label">توقيت الجلسة</td>
			<td class="consulting-session-data"><?php echo esc_html( $booking_time ); ?></td>
		</tr>
		<?php if ( 'offline' === $booking->attendance_type ) { ?>
		<tr>
			<td class="consulting-session-label">تفاصيل العيادة</td>
			<td class="consulting-session-data">
				<a href="#" class="clinic-popup">إضغط هنا</a>
				<div class="clinic-detail">
					<span class="close-clinic-popup">x</span>
					<?php
					echo wp_kses_post( snks_render_clinic( $clinic_details ) );
					?>
				</div>
			</td>
		</tr>
		<?php } ?>
		<?php
		if ( ! $is_booking ) {
			?>
			<!--edit_button-->
		<?php } ?>
		<!--start_button-->
	</table>
	<?php

	return ob_get_clean();
}
/**
 * Displays the user's personal information.
 */
function snks_user_info() {
	?>
	<div style="text-align: center;">
		<h3 class="elementor-heading-title elementor-size-default snks-dynamic-bg-darker" 
			style="display: inline-block; margin: 0 0 20px 0; padding: 10px 10px 17px; border-radius: 8px; text-align: center; color: #fff;">
			بياناتك الشخصية
		</h3>
	</div>
	<table class="consulting-session-table">
		<tr>
			<td class="consulting-session-label"><?php esc_html_e( 'الإسـم', 'text-domain' ); ?></td>
			<td class="consulting-session-data">
				<?php echo esc_html( get_user_meta( get_current_user_id(), 'billing_first_name', true ) ); ?>
			</td>
		</tr>
		<tr>
			<td class="consulting-session-label"><?php esc_html_e( 'رقم الموبايل', 'text-domain' ); ?></td>
			<td class="consulting-session-data" style="direction: ltr;">
				<?php echo esc_html( get_user_meta( get_current_user_id(), 'billing_phone', true ) ); ?>
			</td>
		</tr>
	</table>
	<?php
	$current_user_id = get_current_user_id();
	$phone_edit_lock = get_user_meta( $current_user_id, 'phone_edit_lock', true );
	if ( ! $phone_edit_lock || empty( $phone_edit_lock ) ) {
		$edit_patient_phone_nonce = wp_create_nonce( 'edit_patient_phone' );
		?>
		<div style="text-align: center;">
		<a id="edit_patient_phone" data-nonce="<?php echo esc_attr( $edit_patient_phone_nonce ); ?>" class="elementor-heading-title elementor-size-default snks-dynamic-bg-darker"  style="display: inline-block; margin: 0 0 20px 0; padding: 10px 10px 17px; border-radius: 8px; text-align: center; color: #fff;">
		تعديل
		</a>
		</div>
		<?php
	}
	?>
	<?php
}

/**
 * Render edit button
 *
 * @param int    $booking_id Booking ID.
 * @param int    $doctor_id Doctor's ID.
 * @param mixed  $session_settings Doctor's session specific settings.
 * @param string $button_text Button text.
 * @return string
 */
function snks_edit_button( $booking_id, $doctor_id, $session_settings = false, $button_text = 'تغيير موعد الجلسة' ) {
	if ( $session_settings && ! empty( $session_settings ) ) {
		$doctor_settings = json_decode( $session_settings, true );
	} else {
		$doctor_settings = snks_doctor_settings( $doctor_id );
	}
	$free_change_before = $doctor_settings['free_change_before_number'] . ' ' . $doctor_settings['free_change_before_unit'];
	$paid_change_before = $doctor_settings['before_change_number'] . ' ' . $doctor_settings['before_change_unit'];
	$paid_change_fees   = $doctor_settings['appointment_change_fee'];
	if ( 'تغيير موعد الجلسة مجاناً' === $button_text || 'تم الغاء الموعد، احجز موعد آخر مجانا' === $button_text ) {
		$html = '';
		if ( 'تم الغاء الموعد، احجز موعد آخر مجانا' === $button_text ) {
			$html       .= '<p style="color:#fff;background-color:red;padding:10px">تم تأجيل الموعد</p>';
			$button_text = 'تغيير موعد الجلسة مجاناً';
		}
		$html .= snks_replace_time_units_to_arabic(
			sprintf(
				'<a 
				class="anony-padding-5 snks-button" 
				style="display:inline-flex" 
				href="%1$s" 
				>' . $button_text . '</a>',
				add_query_arg( 'edit-booking', $booking_id, snks_encrypted_doctor_url( $doctor_id ) )
			)
		);
		return $html;
	}
	return snks_replace_time_units_to_arabic(
		sprintf(
			'<a 
			class="anony-padding-5 snks-button edit-booking" 
			style="display:inline-flex" 
			href="#" 
			data-href="%1$s" 
			data-free_change_before="%2$s" 
			data-paid_change_before="%3$s" 
			data-paid_change_fees="%4$s" 
			data-no_change_period="%3$s">' . $button_text . '</a>',
			add_query_arg( 'edit-booking', $booking_id, snks_encrypted_doctor_url( $doctor_id ) ),
			$free_change_before,
			$paid_change_before,
			$paid_change_fees,
		)
	);
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
			<div class="anony-grid-col anony-grid-col-2 snks-bg" style="max-width:60px">
				<?php if ( ! snks_is_ai_session_booking( $record ) ) : ?>
				<input type="checkbox" class="bulk-action-checkbox" name="bulk-action[]" data-date="<?php echo snks_localize_time( gmdate( 'Y-m-d h:i a', strtotime( str_replace(' ', 'T', $record->date_time ) ) ) ); ?>" data-doctor="<?php echo $record->user_id; ?>" data-patient="<?php echo $record->client_id; ?>" value="<?php echo $record->ID; ?>">
				<?php endif; ?>

				<div class="attandance_type rotate-90" style="position:absolute;top:calc(50% - 15px);left:-25%;display: flex;align-items: center;">
					<strong style="font-size:20px;margin-left:5px">{attandance_type}</strong>
					<img style="max-width:35px;margin:0" src="{attandance_type_image}"/>
				</div>
				<?php if ( snks_is_ai_session_booking( $record ) ) : ?>
				<div class="ai-session-flag" style="position:absolute;top:calc(100%);right:0;display: flex;align-items: center;background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);padding: 5px 10px;border-radius: 15px;color: white;font-weight: bold;font-size: 12px;box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
					<span style="margin-right: 5px;">🤖</span>
					<span>AI</span>
				</div>
				<?php endif; ?>
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
							<a style="color:#024059;font-size:18px;font-weight:bold<?php echo 'phone' === $placeholder ? ';direction:ltr' : ''; ?>" href="<?php echo $detail['link']; ?>">{<?php echo $placeholder; ?>}</a>
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
				<!--diagnosis-->
				<div class="anony-grid-row snks-booking-item-row">
					<div class="anony-grid-col anony-grid-col-3 snks-item-icons-bg anony-flex">
						<img class="anony-padding-5" src="/wp-content/uploads/2024/08/card.png"/>
					</div>
					<div class="anony-grid-col anony-grid-col-9 anony-flex flex-h-center flex-v-center snks-secondary-bg" style="margin-top:4px;">
						<span style="color:#024059;font-size:18px;font-weight:bold">التشخيص: {diagnosis}</span>
					</div>
				</div>
				<!--/diagnosis-->
			</div>
			<?php if ( 'online' === $record->attendance_type && false === strpos( $_SERVER['HTTP_REFERER'], 'room_id' ) ) { ?>
			<div class="snks-appointment-button anony-grid-col anony-grid-col-2 snks-bg">
				<a class="snks-count-down rotate-90 anony-flex atrn-button snks-start-meeting" href="{button_url}" data-url="{room_url}" style="position:absolute;top:calc(50% - 15px);left:-50%;color:#fff">{button_text}</a>
			</div>
			<?php } ?>
		</div>
		<?php if ( isset($_REQUEST['data']['page_url']) && strpos($_REQUEST['data']['page_url'], 'room_id=') !== false) { ?>
		
		<?php } else {
			?>
			<!--doctoraction-->
		<div class="anony-flex flex-h-center">
			<?php if ( ! snks_is_ai_session_booking( $record ) ) : ?>
			<button data-title="تعديل" class="snks-change anony-padding-5 snks-bg" style="width:80px;margin-left:5px" data-id="<?php echo esc_attr( $record->ID ) ?>" data-time="<?php echo esc_attr( gmdate( 'H:i a', strtotime( $record->date_time ) ) ) ?>" data-date="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $record->date_time ) ) ) ?>">تعديل</button>
			<?php endif; ?>
			<?php if ( ! snks_is_clinic_manager() && isset( $_SERVER['HTTP_REFERER'] ) && false === strpos( $_SERVER['HTTP_REFERER'], 'room_id' ) ) { ?>
			<!--<button class="snks-notes anony-padding-5 snks-bg" style="margin-right: 5px;width:80px" data-id="<?php echo esc_attr( $record->ID ) ?>">ملاحظات</button>-->
			<?php } ?>
		</div>
		<?php echo snks_doctor_actions( $record ); ?>
			<?php
		} ?>
		<!--/doctoraction-->
		<!--patientaction-->
		<div class="anony-flex flex-h-center">
			{patient_edit}
		</div>
		<!--/patientaction-->
	</div>
	<?php
	//phpcs:enable
	return ob_get_clean();
}
/**
 * Check if a session is an AI session
 *
 * @param object $record The Record.
 * @return bool
 */
function snks_is_ai_session_booking( $record ) {
	if ( ! $record || ! isset( $record->order_id ) || empty( $record->order_id ) ) {
		return false;
	}
	
	$order = wc_get_order( $record->order_id );
	if ( ! $order ) {
		return false;
	}
	
	$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
	$is_ai_session = $order->get_meta( 'is_ai_session' );
	
	return $from_jalsah_ai || $is_ai_session;
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
	
	// Check if this is an AI session and if it's too early to join
	$is_ai_session = snks_is_ai_session( $record->ID );
	$scheduled_timestamp = strtotime( $record->date_time );
	$current_timestamp = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
	$time_difference = $current_timestamp - $scheduled_timestamp;
	$is_too_early = $time_difference < 0; // Current time is before scheduled time
	
	// Set button URL and status class for AI sessions that are too early
	$button_url = site_url( 'meeting-room/?room_id=' . $record->ID );
	$room = $button_url; // Set room URL same as button URL
	$status_class = '';
	
	// For AI sessions that are too early, disable the button
	if ( $is_ai_session && $is_too_early ) {
		$button_text = 'الجلسة لم تبدأ بعد';
		$button_url = '#';
		$room = '#'; // Also disable room URL for disabled sessions
		$status_class = 'snks-disabled';
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
	$phone      = '';
	$whatsapp   = '';
	
	// Hide phone and WhatsApp for AI bookings
	if ( $is_ai_session ) {
		$template = preg_replace( '/<!--phone-->.*?<!--\/phone-->/s', '', $template );
		$template = preg_replace( '/<!--whatsapp-->.*?<!--\/whatsapp-->/s', '', $template );
	} else {
		// Show phone and WhatsApp for regular bookings
		$phone = ! empty( $user_details['billing_phone'] ) ? $user_details['billing_phone'] : '';
		if ( ! empty( $user_details['whatsapp'] ) ) {
			$whatsapp = $user_details['whatsapp'];
		} else {
			$template = preg_replace( '/<!--whatsapp-->.*?<!--\/whatsapp-->/s', '', $template );
		}
		if ( empty( $phone ) ) {
			$template = preg_replace( '/<!--phone-->.*?<!--\/phone-->/s', '', $template );
		}
	}
	
	// Get diagnosis for AI sessions
	$diagnosis_name = '';
	if ( $is_ai_session ) {
		$diagnosis_result = get_user_meta( $record->client_id, 'ai_diagnosis_result', true );
		if ( $diagnosis_result && isset( $diagnosis_result['diagnosis_name'] ) ) {
			$diagnosis_name = $diagnosis_result['diagnosis_name'];
		} else {
			$diagnosis_name = 'غير متوفر';
		}
	} else {
		// Hide diagnosis row for non-AI sessions
		$template = preg_replace( '/<!--diagnosis-->.*?<!--\/diagnosis-->/s', '', $template );
	}
	
	// Keep timer for AI sessions that are too early
	if ( ! ( $is_ai_session && $is_too_early ) ) {
		$template = preg_replace( '/<!--timer-->.*?<!--\/timer-->/s', '', $template );
	}
	$template = preg_replace( '/<!--patientaction-->.*?<!--\/patientaction-->/s', '', $template );

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
			'{room_url}',
			'{button_text}',
			'{snks_timer}',
			'{status_class}',
			'{diagnosis}',
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
			$button_url,
			$room,
			$button_text,
			// Show timer for AI sessions that are too early
			( $is_ai_session && $is_too_early ) ? '<span class="snks-apointment-timer"></span>' : '',
			$status_class,
			esc_html( $diagnosis_name ),
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
	
	// Check if this is an AI session
	$is_ai_session = snks_is_ai_session( $record->ID );
	
	// For AI sessions, add additional time validation to prevent early joining
	$time_difference = $current_timestamp - $scheduled_timestamp;
	$is_too_early = $time_difference < 0; // Current time is before scheduled time
	
	if (
		( isset( $client_id ) && $current_timestamp > $scheduled_timestamp && ( $current_timestamp - $scheduled_timestamp ) > 60 * 15 )
		|| ( isset( $client_id ) && $current_timestamp < $scheduled_timestamp )
		|| ( 'cancelled' === $record->session_status )
		|| ( $is_ai_session && $is_too_early ) // Prevent AI session joining before scheduled time
	) {
		$_class = 'snks-disabled';
		$room   = '#';
		
		// For AI sessions that are too early, show a specific message
		if ( $is_ai_session && $is_too_early ) {
			$button_text = 'الجلسة لم تبدأ بعد';
		}
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
	
	// Hide edit button for AI sessions
	$patient_edit = $is_ai_session ? '' : snks_edit_button( $record->ID, $record->user_id, $record->settings );
	
	// Get diagnosis for AI sessions (from patient's perspective)
	$diagnosis_name = '';
	if ( $is_ai_session && isset( $client_id ) ) {
		$diagnosis_result = get_user_meta( $client_id, 'ai_diagnosis_result', true );
		if ( $diagnosis_result && isset( $diagnosis_result['diagnosis_name'] ) ) {
			$diagnosis_name = $diagnosis_result['diagnosis_name'];
		} else {
			$diagnosis_name = 'غير متوفر';
		}
	} else {
		// Hide diagnosis row for non-AI sessions
		$template = preg_replace( '/<!--diagnosis-->.*?<!--\/diagnosis-->/s', '', $template );
	}
	
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
			'{room_url}',
			'{button_text}',
			'{snks_timer}',
			'{status_class}',
			'{patient_edit}',
			'{diagnosis}',
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
			esc_url( $room ),
			$button_text,
			'<span class="snks-apointment-timer"></span>',
			$_class,
			$patient_edit,
			esc_html( $diagnosis_name ),
		),
		$template
	);
}
add_shortcode(
	'snks_doctor_change_appointment',
	function () {
		$output = '';
		if ( ( snks_is_doctor() || snks_is_clinic_manager() ) && ( is_page( 'account-setting' ) || is_page( 'meeting-room' ) ) ) {
			$bookable_days_obj = get_all_bookable_dates( snks_get_settings_doctor_id() );
			$bookable_days     = snks_timetables_unique_dates( $bookable_days_obj );
			$output           .= '<form id="doctor-change-appointment" method="post">';
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
			$output .= '<div id="change-to-list" class="change-to-list"></div>';
			$output .= wp_nonce_field( 'change_appointment', 'change_appointment_nonce' );
			$output .= '<input type="text" style="display:none" id="old-appointment" name="old-appointment" value=""/>';
			$output .= '<input id="doctor-change-appointment-submit" type="submit" class="snks-bg anony-padding-10 anony-full-width" name="submit" value="حفظ"/>';
		}
		return $output;
	}
);
/**
 * Get doctor rules
 *
 * @param int $user_id User's ID.
 * @return array
 */
function snks_timetable_settings( $user_id ) {
	$doctor_settings = snks_doctor_settings( $user_id );

	return array(
		'free_change_before_number' => $doctor_settings['free_change_before_number'],
		'free_change_before_unit'   => $doctor_settings['free_change_before_unit'],
		'before_change_number'      => $doctor_settings['before_change_number'],
		'before_change_unit'        => $doctor_settings['before_change_unit'],
		'appointment_change_fee'    => $doctor_settings['appointment_change_fee'],
		'block_if_before_number'    => $doctor_settings['block_if_before_number'],
		'block_if_before_unit'      => $doctor_settings['block_if_before_unit'],
		'allow_appointment_change'  => $doctor_settings['allow_appointment_change'],
		'pricing'                   => $doctor_settings['pricing'],
		'country'                   => snsk_ip_api_country(),
	);
}
/**
 * Render doctor rules
 *
 * @param int $user_id User's ID.
 * @return string
 */
function snks_doctor_rules( $user_id ) {
	$doctor_settings    = snks_doctor_settings( $user_id );
	$free_change_before = $doctor_settings['free_change_before_number'] . ' ' . $doctor_settings['free_change_before_unit'];
	$paid_change_before = $doctor_settings['before_change_number'] . ' ' . $doctor_settings['before_change_unit'];
	$paid_change_fees   = $doctor_settings['appointment_change_fee'];
	$no_change_period   = $paid_change_before;
	$html               = '<div class="clinic-rules anony-flex anony-flex-column flex-h-center anony-default-border-radius anony-default-padding snks-light-bg">';
	if ( 'on' !== $doctor_settings['allow_appointment_change'] ) {
		$html .= '<p>لا يمكن تغيير موعد الجلسة بعد الحجز.</p>';
	} else {
		$html .= '<h1>شروط تغيير مواعيد الجلسات</h1>';
		$html .= '<p>';
		$html .= 'يمكنك تغيير موعد الجلسة مجاناً في حالة تغييرها قبل موعدها بـ {free_change_before} وبعد ذلك يتم فرض رسوم على تغيير موعدها بقيمة {paid_change_fees}% من ثمن الجلسة ولا يمكنك تغيير موعد الجلسة قبل موعدها بـ {no_change_period}';
		$html .= '</p>';
		$html .= '<p>';
		$html .= 'مع العلم انه يمكنك تغيير موعد جلستك مره واحده فقط.';
		$html .= '</p>';
	}
	$html .= '</div>';
	return snks_replace_time_units_to_arabic(
		str_replace(
			array(
				'{free_change_before}',
				'{paid_change_period}',
				'{paid_change_fees}',
				'{no_change_period}',
			),
			array(
				$free_change_before,
				$paid_change_before,
				$paid_change_fees,
				$no_change_period,
			),
			$html
		)
	);
}

/**
 * Generate bookings
 *
 * @param  array  $_timetables Timetables array.
 * @param  string $tens Tense.
 * @return string
 */
function snks_render_bookings( $_timetables, $tens ) {
	$days_sorted = array( 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri' );
	// Check if there are no bookings.
	if ( empty( $_timetables ) ) {
		return '<p class="anony-center-text" style="margin-top: 30px;">ليس لديك حجوزات حتى الآن!</p>';
	}
	$day_groups   = snks_group_objects_by( $_timetables, 'date' );
	$current_date = current_time( 'Y-m-d' );
	// Start building HTML.
	ob_start();
	?>
	<div id="my-bookings-container">
		<?php
		$j = true; // Variable to check the first occurrence for bulk action tip.
		$t = true;
		foreach ( $day_groups as $date => $timetables ) {
			$day = gmdate( 'D', strtotime( $date ) ); // Get the day from the date.
			?>
			<div class="snks-timetable-accordion-wrapper <?php echo esc_html( $tens ); ?>" data-id="<?php echo esc_attr( $date ); ?>">
				<div class="anony-grid-row snks-bg snks-timetable-accordion<?php echo $t && 'past' !== $tens ? ' snks-active-accordion' : ''; ?>" data-id="<?php echo esc_attr( $date ); ?>">

					<div class="anony-grid-col anony-grid-col-1 snks-timetables-count anony-inline-flex flex-h-center flex-v-center anony-padding-5" style="background-color:#fff; color:#024059">
						<?php echo count( $timetables ); ?>
					</div>
					<div class="anony-grid-col anony-grid-col-11 anony-inline-flex flex-h-center flex-v-center anony-padding-10">
						<?php echo $current_date === $date ? 'اليوم' : esc_html( snks_localize_day( $day ) . ' ' . $date ); ?>
						<?php if ( 'past' !== $tens ) { ?>
							<div class="bulk-action-toggle">
								<?php if ( $j && ! isset( $_COOKIE['hide-delay-tip'] ) ) : ?>
									<span class="bulk-action-toggle-tip">
										<span class="bulk-action-toggle-tip-close">x</span>
										تأجيل/تأخير
									</span>
									<?php $j = false; ?>
								<?php endif; ?>
								<svg fill="#ffffff" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 325.051 325.051" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"/><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M162.523,86.532c-41.904,0-76,34.089-76,75.994c0,41.901,34.096,75.996,76,75.996s76-34.095,76-75.996 C238.523,120.621,204.427,86.532,162.523,86.532z M162.523,226.225c-35.128,0-63.702-28.571-63.702-63.699 c0-35.122,28.574-63.696,63.702-63.696c35.131,0,63.702,28.574,63.702,63.696C226.225,197.653,197.654,226.225,162.523,226.225z"/> <path d="M302.503,130.583h-11.505c-5.975-24.202-18.537-45.916-36.587-63.3l5.765-9.977c6.329-10.965-0.865-26.571-16.382-35.531 c-6.484-3.738-13.654-5.803-20.206-5.803c-8.473,0-15.3,3.419-18.74,9.37l-5.759,9.974c-23.749-6.83-49.345-6.83-73.121,0 l-5.755-9.974c-3.444-5.957-10.269-9.37-18.744-9.37c-6.548,0-13.724,2.065-20.203,5.803C65.752,30.729,58.55,46.33,64.879,57.3 l5.768,9.977c-18.065,17.39-30.615,39.104-36.593,63.312H22.554C9.908,130.589,0,144.613,0,162.525s9.908,31.945,22.554,31.945 h11.499c5.978,24.199,18.534,45.913,36.593,63.303l-5.768,9.98c-6.329,10.964,0.874,26.564,16.387,35.523 c6.479,3.735,13.655,5.801,20.203,5.801c8.476,0,15.3-3.423,18.744-9.367l5.755-9.968c23.77,6.827,49.372,6.827,73.115-0.013 l5.765,9.98c3.44,5.957,10.268,9.367,18.74,9.367c6.552,0,13.722-2.065,20.206-5.801c15.517-8.959,22.711-24.56,16.382-35.523 l-5.765-9.98c18.062-17.39,30.612-39.104,36.587-63.303h11.493c12.64,0,22.554-14.027,22.56-31.945 c0.007-8.043-2.041-15.69-5.764-21.539C315.089,134.375,308.965,130.583,302.503,130.583z M302.492,182.173h-16.399 c-2.918,0-5.428,2.054-6.017,4.906c-5.23,25.196-18.212,47.653-37.536,64.941c-2.168,1.94-2.684,5.141-1.225,7.656l8.215,14.226 c2.432,4.203-2.145,13.103-11.884,18.723c-8.779,5.092-19.636,5.272-22.146,0.938l-8.215-14.22 c-1.123-1.946-3.177-3.075-5.32-3.075c-0.643,0-1.297,0.108-1.928,0.307c-24.205,7.98-50.771,7.992-75.018,0.006 c-2.759-0.889-5.786,0.24-7.248,2.769l-8.202,14.214c-2.504,4.335-13.352,4.154-22.149-0.932 c-9.74-5.62-14.309-14.52-11.886-18.723l8.214-14.226c1.45-2.521,0.94-5.716-1.222-7.649 c-19.332-17.294-32.314-39.752-37.542-64.948c-0.594-2.853-3.11-4.899-6.02-4.899H22.548c-4.837,0-10.256-8.401-10.256-19.648 c0-11.241,5.419-19.639,10.256-19.639h16.405c2.916,0,5.432-2.047,6.02-4.902c5.233-25.208,18.215-47.667,37.539-64.948 c2.17-1.939,2.681-5.137,1.225-7.656l-8.214-14.222c-2.429-4.207,2.147-13.105,11.886-18.726 c8.797-5.083,19.639-5.269,22.149-0.934l8.203,14.216c1.456,2.528,4.488,3.672,7.248,2.774c24.232-7.974,50.801-7.974,75.03,0 c2.769,0.892,5.783-0.24,7.248-2.768L215.5,31.51c2.504-4.32,13.354-4.144,22.146,0.942c9.739,5.618,14.304,14.523,11.878,18.729 l-8.215,14.222c-1.453,2.522-0.938,5.716,1.225,7.656c19.323,17.282,32.306,39.74,37.53,64.945c0.601,2.852,3.11,4.9,6.022,4.9 h16.411c2.174,0,4.504,1.714,6.413,4.71c2.438,3.843,3.837,9.284,3.837,14.94C312.748,173.772,307.326,182.173,302.492,182.173z"/> </g> </g> </g></svg>
							</div>
						<?php } ?>
						<span class="snks-timetable-accordion-toggle">
							<svg width="20px" height="20px" fill="#fff" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
							<g>
							<title>arrowhead</title>
							<g data-name="Layer 2">
							<g data-name="invisible box">
							<rect width="48" height="48" fill="none"/>
							</g>
							<g data-name="icons Q2">
							<path d="M24,46A22,22,0,1,0,2,24,21.9,21.9,0,0,0,24,46ZM14.3,19.8A2.5,2.5,0,0,1,16,19H32a2.5,2.5,0,0,1,1.7.8,2.1,2.1,0,0,1-.4,2.7l-7.9,7.9a1.9,1.9,0,0,1-2.8,0l-7.9-7.9A2.1,2.1,0,0,1,14.3,19.8Z"/>
							</g>
							</g>
							</g>
							</svg>
						</span>
					</div>

				</div>

				<div class="snks-timetable-accordion-content-wrapper">
					<?php if ( 'past' !== $tens ) { ?>
					<div style="background-color:#dcdcdc;" class="anony-grid-row snks-timetable-accordion-actions anony-padding-10 anony-flex flex-h-center flex-v-center">
						<div class="anony-grid-col anony-grid-col-6">
							<button data-title="تأجيل" data-action=".snks-postpon" class="snks-booking-bulk-action snks-postpon anony-curved-5 anony-padding-5 snks-bg anony-full-width">تأجيل الجلسات</button>
						</div>
						<div class="anony-grid-col anony-grid-col-6">
							<button data-title="تأخير" data-action=".snks-delay" class="snks-booking-bulk-action snks-delay anony-curved-5 anony-padding-5 snks-bg anony-full-width">تأخير الجلسات</button>
						</div>
					</div>
					<?php } ?>
					<div class="snks-timetable-accordion-content" style="background-color:#fff;padding:10px 0 10px 10px" id="<?php echo esc_attr( $date ); ?>">
					<?php foreach ( $timetables as $data ) : ?>
						<div class="snks-booking-item-wrapper">
							<?php
							$output = template_str_replace( $data );
							// Remove doctor actions for past bookings EXCEPT for AI sessions
							if ( 'past' === $tens && ! snks_is_ai_session( $data->ID ) ) {
								$output = preg_replace( '/<!--doctoraction-->.*?<!--\/doctoraction-->/s', '', $output );
							}
							//phpcs:disable
							echo $output;
							//phpcs:enable

							?>
								<?php
								//phpcs:disable
								if ( 'past' !== $tens && isset( $_SERVER['HTTP_REFERER'] ) && false === strpos( $_SERVER['HTTP_REFERER'], 'room_id' ) ) { 
								//phpcs:enable
									?>
								<div class="snks-notes-form anony-padding-10">
									<?php
									//phpcs:disable
									$session_notes = get_posts(
										array(
											'post_type'    => 'session_notes',
											'meta_key'     => 'session_id',
											'meta_value'   => $data->ID,
											'meta_compare' => '=',
										)
									);
									if ( empty( $session_notes ) ) {
										echo str_replace( '{session_id}', $data->ID, do_shortcode( '[user_insert_session_notes]' ) );
									} else {
										$session_note = $session_notes[0];
										echo str_replace( '{session_id}', $data->ID, do_shortcode( '[user_edit_session_notes id="' . $session_note->ID . '"]' ) );
									}
									//phpcs:enable
									?>
								</div>
								<?php } ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php
			$t = false;
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}
/**
 * Generate bookings helper
 *
 * @param array $past Array of timetables.
 * @param array $current_timetables Array of timetables.
 * @return string
 */
function snks_generate_the_bookings( $past, $current_timetables ) {
	//phpcs:disable
	ob_start();
	echo do_shortcode( '[notification_box]' );
	
	// Add Roshta section for patients
	$rochtah_section = '';
	if ( snks_is_patient() ) {
		$current_user_id = get_current_user_id();
		$rochtah_requests = snks_get_patient_rochtah_requests( $current_user_id );
		$rochtah_section = snks_render_rochtah_section( $rochtah_requests );
	}
	?>
	<div class="snks-tabs">
		<ul class="snks-tabs-nav">
			<li class="snks-tab-item snks-active" data-snks-tab="future">الجلسات الحالية</li>
			<li class="snks-tab-item" data-snks-tab="past">الجلسات السابقة</li>
		</ul>
		<div class="snks-tabs-content">
			<div id="snks-tab-future" class="snks-tab-panel snks-active">
				<?php echo $current_timetables; ?>
			</div>
			<div id="snks-tab-past" class="snks-tab-panel">
				<?php echo $past; ?>
			</div>
		</div>
	</div>
	
	<?php if ( ! empty( $rochtah_section ) ) { ?>
		<?php echo $rochtah_section; ?>
	<?php } ?>
	
	<?php if( snks_is_patient() ) { ?>
		<div class="anony-center-text">
			<p>هل لديك مشاكل تقنية؟ اضغط هنا</p>
			<a href="https://wa.me/201127145676" traget="_blank" title="خدمة عملاء جلسة" style="text-align: center;display: flex;"><img style="max-width: 150px;" src="https://jalsah.app/wp-content/uploads/2025/04/IMG_2418.png" alt="خدمة عملاء جلسة"></a>
		</div>
	<?php
	}

	//phpcs:enable
	return ob_get_clean();
}
/**
 * Check if current doctor is an AI therapist
 *
 * @return bool
 */
function snks_is_current_doctor_ai_therapist() {
	if ( ! snks_is_doctor() ) {
		return false;
	}
	
	$current_user_id = get_current_user_id();
	$show_on_ai_site = get_user_meta( $current_user_id, 'show_on_ai_site', true );
	
	return $show_on_ai_site === '1';
}

/**
 * Get AI therapist completed sessions
 *
 * @return array
 */
function snks_get_ai_therapist_completed_sessions() {
	if ( ! snks_is_current_doctor_ai_therapist() ) {
		return array();
	}
	
	$current_user_id = get_current_user_id();
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get completed AI sessions for this therapist
	// Include sessions with status 'completed' OR settings containing 'ai_booking:completed'
	$sessions = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table_name} 
		 WHERE user_id = %d 
		 AND (
			(session_status = 'completed' AND settings LIKE '%ai_booking%')
			OR (settings LIKE '%ai_booking:completed%')
		 )
		 ORDER BY date_time DESC",
		$current_user_id
	) );
	
	// Add date property to each session for proper grouping
	if ( $sessions && is_array( $sessions ) ) {
		foreach ( $sessions as $session ) {
			$session->date = gmdate( 'Y-m-d', strtotime( $session->date_time ) );
		}
	}
	
	return $sessions ?: array();
}

/**
 * Get patient Roshta requests
 *
 * @param int $patient_id Patient ID
 * @return array
 */
function snks_get_patient_rochtah_requests( $patient_id ) {
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$requests = $wpdb->get_results( $wpdb->prepare(
		"SELECT rb.*, pt.date_time, pt.starts, pt.ends, pt.user_id as therapist_id
		 FROM {$rochtah_bookings_table} rb
		 LEFT JOIN {$wpdb->prefix}snks_provider_timetable pt ON rb.session_id = pt.ID
		 WHERE rb.client_id = %d 
		 AND rb.status IN ('pending', 'confirmed')
		 ORDER BY rb.created_at DESC",
		$patient_id
	) );
	
	return $requests ?: array();
}

/**
 * Render Roshta section for patient appointments
 *
 * @param array $rochtah_requests Array of Roshta requests
 * @return string
 */
function snks_render_rochtah_section( $rochtah_requests ) {
	if ( empty( $rochtah_requests ) ) {
		return '';
	}
	
	$output = '<div class="rochtah-section" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007cba;">';
	$output .= '<h3 style="margin: 0 0 10px 0; color: #007cba;">طلب روشتا (وصف دواء)</h3>';
	
	foreach ( $rochtah_requests as $request ) {
		$therapist_name = get_user_meta( $request->therapist_id, 'nickname', true );
		$session_date = gmdate( 'Y-m-d', strtotime( $request->date_time ) );
		$session_time = gmdate( 'h:i a', strtotime( $request->starts ) );
		
		$output .= '<div class="rochtah-request" style="margin: 10px 0; padding: 10px; background: white; border-radius: 5px;">';
		$output .= '<p style="margin: 0 0 8px 0;"><strong>الجلسة:</strong> ' . $session_date . ' - ' . $session_time . '</p>';
		$output .= '<p style="margin: 0 0 8px 0;"><strong>المعالج:</strong> ' . $therapist_name . '</p>';
		$output .= '<p style="margin: 0 0 15px 0; color: #666;">تم إرسال طلب روشتا من معالجك. يمكنك الآن حجز استشارة مجانية لمدة 15 دقيقة مع طبيب نفسي لوصف الدواء المناسب.</p>';
		
		if ( $request->status === 'pending' ) {
			$output .= '<button class="snks-button book-rochtah-btn" data-request-id="' . $request->id . '" style="background: #007cba; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">حجز موعد مجاني</button>';
		} elseif ( $request->status === 'confirmed' ) {
			$output .= '<p style="margin: 0; color: #28a745;"><strong>تم حجز الموعد بنجاح!</strong></p>';
		}
		
		$output .= '</div>';
	}
	
	$output .= '</div>';
	
	return $output;
}

/**
 * Generate bookings
 *
 * @return string
 */
function snks_generate_bookings() {
	// Use date-based filtering for both AI and regular therapists
	// Past = sessions before today, Future = sessions from today onwards
	$past = snks_render_bookings( snks_get_doctor_sessions( 'past', 'open', true ), 'past' );
	$current_timetables = snks_render_bookings( snks_get_doctor_sessions( 'future', 'open', true ), 'future' );
	return snks_generate_the_bookings( $past, $current_timetables );
}
add_shortcode( 'snks_bookings', 'snks_generate_bookings' );


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
	
	// Check if this is an AI session
	$is_ai_session = snks_is_ai_session( $session->ID );
	
	// Check if session is already completed
	$is_completed = $session->session_status === 'completed';
	
	if ( ! empty( $attendees ) ) {
		// Calculate session end time (start datetime + period in minutes)
		// Don't specify timezone - database time is already in the correct timezone
		$session_datetime = new DateTime( $session->date_time );
		$period_minutes   = isset( $session->period ) ? intval( $session->period ) : 45;
		$session_datetime->add( new DateInterval( 'PT' . $period_minutes . 'M' ) );
		$session_end_timestamp = $session_datetime->getTimestamp();
		$current_timestamp     = current_time( 'timestamp' );
		
		// Check if session has ended
		$is_session_ended = $current_timestamp >= $session_end_timestamp;
		
		$output .= '<div class="doctor-actions doctor-actions-wrapper" data-session-end="' . esc_attr( $session_end_timestamp ) . '">';
		
		// Mark as Completed button - only show if not already completed
		if ( ! $is_completed ) {
			// Prepare button attributes
			$button_disabled = $is_session_ended ? '' : 'disabled="disabled"';
			$button_class    = 'snks-button table-form-button snks-complete-session-btn';
			if ( ! $is_session_ended ) {
				$button_class .= ' snks-button-waiting';
			}
			$button_title    = $is_session_ended ? '' : 'سيتم تفعيل الزر تلقائياً عند انتهاء الجلسة';
			$button_style    = $is_session_ended ? '' : 'style="pointer-events: none !important; cursor: not-allowed !important;"';
			
			$output .= '<form class="doctor_actions" method="post" action="">';
			$output .= '<input type="hidden" name="attendees" value="' . $session->client_id . '">';
			$output .= '<input type="hidden" name="session_id" value="' . $session->ID . '">';
			$output .= '<input class="' . $button_class . '" type="submit" name="doctor-actions" value="تحديد كمكتملة" ' . $button_disabled . ' ' . $button_style . ' title="' . esc_attr( $button_title ) . '">';
			$output .= '</form>';
		}
		
		// Attendance confirmation button - only show for completed sessions
		if ( $is_completed ) {
			$output .= '<button class="snks-button snks-attendance-btn" data-session-id="' . esc_attr( $session->ID ) . '" data-client-id="' . esc_attr( $session->client_id ) . '" style="background-color: #007cba; border-color: #007cba;">هل حضر المريض الجلسة؟</button>';
		}
		
		// Roshtah Request button (only for AI sessions and only if session has ended or completed)
		if ( $is_ai_session && ( $is_session_ended || $is_completed ) ) {
			$output .= '<button class="snks-button snks-roshtah-request-btn" data-session-id="' . esc_attr( $session->ID ) . '" data-client-id="' . esc_attr( $session->client_id ) . '" style="margin-top: 10px; background-color: #28a745; border-color: #28a745;">إرسال لروشتا</button>';
		}
		
		$output .= '</div>';
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
	$output   = '';
	if ( $sessions && is_array( $sessions ) ) {
		foreach ( $sessions as $session ) {
			$edit            = '';
			$start           = '';
			$doctor_settings = json_decode( $session->settings, true );
			if ( ! $doctor_settings || empty( $doctor_settings ) ) {
				$doctor_settings = snks_doctor_settings( $session->user_id );
			}
			$room = add_query_arg( 'room_id', $session->ID, home_url( '/meeting-room' ) );
			if ( snks_is_doctor() ) {
				$room = add_query_arg( 'id', $session->user_id, $room );
			}
			if ( 'postponed' === $session->session_status && ! snks_is_doctor() ) {
				$edit = '<tr><td style="background-color: #024059 !important;border: 1px solid #024059;" colspan="2">' . snks_edit_button( $session->ID, $session->user_id, $session->settings, 'تم الغاء الموعد، احجز موعد آخر مجانا' ) . '</td></tr>';
			} elseif ( isset( $doctor_settings['allow_appointment_change'] ) && 'on' === $doctor_settings['allow_appointment_change'] ) {
				$order_id = $session->order_id;
				$order    = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$edited_before = $order->get_meta( 'booking-edited', true );
				$class         = 'remaining';
				$diff_seconds  = snks_diff_seconds( $session );
				// Compare the input date and time with the modified current date and time.
				if ( ! snks_is_doctor() && ( ! $edited_before || empty( $edited_before ) ) && $diff_seconds > snks_get_edit_before_seconds( $doctor_settings ) ) {
					$free_edit_before_seconds = snks_get_free_edit_before_seconds( $doctor_settings );
					if ( $diff_seconds > $free_edit_before_seconds ) {
						$edit_button = snks_edit_button( $session->ID, $session->user_id, $session->settings, 'تغيير موعد الجلسة مجاناً' );
					} else {
						$edit_button = snks_edit_button( $session->ID, $session->user_id, $session->settings );
					}
					$edit = '<tr><td style="background-color: #024059 !important;border: 1px solid #024059;" colspan="2">' . $edit_button . '</td></tr>';
				}
			}

			if ( snks_is_past_date( $session->date_time ) ) {
				$class = 'start';
			} else {
				$class = 'remaining';
			}
			$session_details = array(
				'booking_day'  => gmdate( 'Y-m-d', strtotime( $session->date_time ) ),
				'booking_hour' => snks_localize_time(
					sprintf(
						/* translators: 1: start time, 2: end time */
						esc_html__( 'من %1$s إلى %2$s', 'text-domain' ),
						esc_html( gmdate( 'h:i a', strtotime( $session->starts ) ) ),
						esc_html( gmdate( 'h:i a', strtotime( $session->ends ) ) )
					)
				),
				'booking_id'   => $session->ID,
				'_period'      => $session->period,
			);
			if ( 'online' === $session->attendance_type && 'postponed' !== $session->session_status ) {
				$start = '<tr><td style="background-color: #024059 !important;border: 1px solid #024059;border-top-color:#fff;" colspan="2">
					<a class="snks-count-down anony-flex atrn-button snks-start-meeting flex-h-center anony-padding-5" href="' . $room . '" data-url="' . $room . '">ابدأ الجلسة</a>
				</td></tr>';
			}
			$output .= ' <div id="snks-booking-item-' . esc_attr( $session->ID ) . '" data-datetime="' . esc_attr( $session->date_time ) . '" class="snks-booking-item snks-patient-booking-item ' . $class . '"> ';
			$output .= str_replace(
				array( '<!--edit_button-->', '<!--start_button-->' ),
				array( $edit, $start ),
				snks_booking_details( $session_details, false )
			);
			
			// Add prescription button for AI sessions
			if ( function_exists( 'snks_add_ai_prescription_button' ) ) {
				$prescription_button = snks_add_ai_prescription_button( $session->ID, $session );
				if ( $prescription_button ) {
					$output .= '<div class="ai-prescription-section">';
					$output .= '<h4>' . __( 'Prescription Services', 'shrinks' ) . '</h4>';
					$output .= $prescription_button;
					$output .= '</div>';
				}
			}
			
			$output .= '</div>';

		}
	} else {
		$output  = '<p class="anony-center-text">';
		$output .= 'عفواَ ليس لديك حجوزات حاليا!';
		$output .= '</p>';
	}
	return $output;
}
/**
 * Get doctor experience
 *
 * @param int $user_id User's ID.
 * @return string
 */
function snks_get_doctor_experiences( $user_id ) {
	$user_details = snks_user_details( $user_id );
	$output       = '';
	$about_me     = maybe_unserialize( $user_details['about-me'] );
	if ( ! empty( $about_me ) && is_array( $about_me ) ) {
		$arr     = array_column( $about_me, 'experience' );
		$output .= '<ul class="snks-about-me hacen_liner_print-outregular">';
		foreach ( $arr as $exp ) {
			$output .= '<li>' . wp_kses_post( $exp ) . '</li>';
		}
		$output .= '</ul>';
	}
	return $output;
}
/**
 * Renders accordion
 *
 * @param  array $data Accordion data.
 * @return string
 */
function anony_accordion( $data ) {
	$output = '';
	if ( ! empty( $data ) ) {
		ob_start();
		?>
		<div id="anony-accordion-wrapper">
			<div class="anony-grid-row flex-h-center">
				<div class="anony-accordion-container anony-grid-col">
					<?php
					foreach ( $data as $item ) {
						?>
						<div class="anony-accordion-item">
							<button class="anony-accordion-header anony-center-text">
							<span style="position: relative;top: -2px;font-size:23px"><?php echo esc_html( $item['title'] ); ?></span> <span class="anony-accordion-icon"><span class="anony-arrow-down"></span></span>
							</button>
							<div class="anony-accordion-content">
								<?php echo wp_kses_post( $item['content'] ); ?>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			document.querySelectorAll('.anony-accordion-header').forEach(button => {
				button.addEventListener('click', (e) => {
					e.preventDefault();
					const accordionContent = button.nextElementSibling;

					button.classList.toggle('active');

					if (button.classList.contains('active')) {
						accordionContent.style.maxHeight = accordionContent.scrollHeight + 30 + 'px';
						accordionContent.style.padding = '15px';
					} else {
						accordionContent.style.maxHeight = 0;
						accordionContent.style.padding = '0px';
					}

					// Close other open accordion items.
					document.querySelectorAll('.anony-accordion-header').forEach(otherButton => {
						if (otherButton !== button) {
							otherButton.classList.remove('active');
							otherButton.nextElementSibling.style.maxHeight = 0;
						}
					});
				});
			});
		</script>
		<?php
		$output .= ob_get_clean();
	}

	return $output;
}

add_action(
	'wp_footer',
	function () {
		return;
	}
);
/**
 * Org styles
 *
 * @param string $main_color Color.
 * @param string $secondary_color Color.
 * @param string $header_color Color.
 * @param string $secondary_color Color.
 * @return void
 */
function snks_org_styles( $main_color, $secondary_color, $header_color = false, $footer_color = '#fff' ) {
	if ( ! $header_color ) {
		$header_color = $main_color;
	}
	?>
	<style>
		body{
			background-color: <?php echo esc_html( $main_color ); ?>!important
		}
		#org-container{
			max-width: 428px;
			margin: auto;
			border-right: 2px solid #fff;
			border-bottom: 2px solid #fff;
			border-left: 2px solid #fff;
		}
		.org-header { background: <?php echo esc_html( $header_color ); ?>; text-align: center; padding: 30px 15px; }
		.org-logo img { max-width: 160px; border-radius: 50%; }
		.org-slogan-section { text-align: center; padding: 30px 20px; }
		.org-slogan-section h2 { margin: 10px;background: <?php echo esc_html( $main_color ); ?>; display: inline-block; padding: 10px 20px; border-radius: 12px; font-size: 1.4rem; }
		.org-slogan-section p { margin-top: 15px; font-size: 1.1rem; line-height: 1.8; }
		.org-green-section {padding: 40px 15px; text-align: center; }
		.org-green-section h3{color: <?php echo esc_html( $secondary_color ); ?>;display: inline-flex;border-radius: 10px;background-color: #fff;padding: 10px;margin:0}
		.org-contact-section { padding: 40px 15px; text-align: center; }
		.org-contact-icons .contacts img { width: 40px; margin: 10px; }
		.org-contact-icons .socials img { width: 30px; margin: 3px 10px;}
		.org-contact-icons .socials,.org-contact-icons .socials a {display: inline-flex;justify-content: center;align-items: center;}
		.green-link-card{display: flex; flex-direction: column; align-items: center; padding: 20px; border-radius: 5px; max-width: 180px; text-align: center; color: #229944; text-decoration: none;}
		.green-link-card > span{
			color:#fff;
			font-size: 19px;
		}
		.org-footer *{
			color: <?php echo esc_html( $main_color ); ?>!important;
		}
		.org-footer .booking-form-subfooter {
			background: <?php echo esc_html( $footer_color ); ?>!important;
		}
	</style>
	<?php
}
/**
 * Gets the style attribute for a section with background and text colors.
 *
 * @param string $bg_color    The background color value.
 * @param string $text_color  The text color value.
 * @param string $fallback_bg Fallback background color if $bg_color is empty.
 * @return string The style attribute string.
 */
function snks_get_section_style( $bg_color, $text_color, $fallback_bg ) {
	$bg  = ! empty( $bg_color ) ? $bg_color : $fallback_bg;
	$txt = ! empty( $text_color ) ? $text_color : '#ffffff';
	return "style='background-color: {$bg}!important; color: {$txt}!important;'";
}
/**
 * Gets the style attribute for a section with background and text colors.
 *
 * @param string $bg_color    The background color value.
 * @param string $text_color  The text color value.
 * @param string $fallback_bg Fallback background color if $bg_color is empty.
 * @return string The style attribute string.
 */
function snks_get_header_style( $bg_color, $text_color, $fallback_bg ) {
	$bg  = ! empty( $bg_color ) ? $bg_color : $fallback_bg;
	$txt = ! empty( $text_color ) ? $text_color : '#ffffff';
	return "style='background-color: {$bg}; color: {$txt}; padding: 10px; border-radius: 5px;display:inline-flex'";
}

/**
 * Render doctor listing grid.
 *
 * @param WP_User[] $users Array of WP_User objects.
 *
 * @return void
 */
function snks_render_doctor_listing( $users ) {
	if ( empty( $users ) || ! is_array( $users ) ) {
		return;
	}
	?>
	<div class="snks-doctor-listing anony-grid-row anony-padding-10">
		<?php
		foreach ( $users as $user ) {
			$user_id             = $user->ID;
			$user_details        = snks_user_details( $user_id );
			$doctor_url          = snks_encrypted_doctor_url( $user_id );
			$closest_appointment = snks_get_closest_timetable( $user_id );
			$profile_image       = get_user_meta( $user_id, 'profile-image', true );

			if ( empty( $profile_image ) ) {
				$profile_image = '/wp-content/uploads/2024/08/portrait-3d-male-doctor_23-2151107083.avif';
			} elseif ( is_numeric( $profile_image ) ) {
				$image_src     = wp_get_attachment_image_src( absint( $profile_image ), 'full' );
				$profile_image = $image_src[0];
			}
			?>
			<div class="snks-doctor-listing-item anony-grid-col anony-grid-col-6 anony-padding-10">
				<div class="item-content">
					<div class="snks-profile-image-wrapper">
						<a href="<?php echo esc_url( $doctor_url ); ?>" target="_blank">
							<div class="snks-tear-shap-wrapper">
								<div class="snks-tear-shap" style="background-image: url('<?php echo esc_url( $profile_image ); ?>'); background-position: center; background-repeat: repeat; background-size: cover;"></div>
								<div class="snks-tear-shap sub anony-box-shadow"></div>
							</div>
						</a>
					</div>

					<div class="org-profile-details">
						<h1 class="kacstqurnkacstqurn snks-white-text" style="font-size:18px;text-align:center;height:45px">
							<?php echo esc_html( $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] ); ?>
						</h1>
					</div>

					<div class="snks-listing-periods anony-full-width" style="margin-bottom: 20px;">
						<?php snks_listing_periods( $user_id ); ?>
					</div>
					<?php
					/*
					<div class="snks-listing-closest-date anony-full-width anony-center-text">
						<h5 class="hacen_liner_print-outregular snks-white-text anony-padding-10">أول موعد متاح للحجز</h5>
						<span class="anony-grid-col hacen_liner_print-outregular anony-flex anony-flex-column flex-h-center flex-v-center anony-padding-10 anony-margin-5" style="background-color:#fff;border-radius:25px;font-size: 17px;width: 160px;margin: auto;height: 85px;">
							<?php if ( is_array( $closest_appointment ) && ! empty( $closest_appointment ) ) : ?>
								<span>
									<?php
									printf(
										'%1$s | %2$s',
										esc_html( snks_localize_day( $closest_appointment[0]->day ) ),
										esc_html( gmdate( 'Y-m-d', strtotime( $closest_appointment[0]->date_time ) ) )
									);
									?>
								</span>
								<div class="main_color_bg anony-margin-5" style="width:100%;height:2px;"></div>
								<span>
									<?php
									printf(
										'%1$s %2$s',
										'الساعة',
										esc_html( snks_localized_time( $closest_appointment[0]->starts ) )
									);
									?>
								</span>
							<?php else : ?>
								غير متاح
							<?php endif; ?>
						</span>
					</div>
					<div style="background-color:#fff;width:100%;height:2px;margin:20px 0"></div>
					*/
					?>
					<a href="<?php echo esc_url( $doctor_url ); ?>" target="_blank" class="main_color_text anony-inline-flex anony-padding-3 flex-h-center" style="background-color:#fff;border-radius:25px;font-size: 18px;width: 80%;">
						إحجز موعد
					</a>
				</div>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Echo the organization header HTML (logo with permalink).
 *
 * @param int $org_id Organization post ID.
 */
function snks_print_org_header( $org_id ) {
	if ( ! $org_id || get_post_type( $org_id ) !== 'organization' ) {
		return;
	}
	?>
	<div class="org-header">
		<div class="org-logo">
			<a href="<?php echo esc_url( get_the_permalink( $org_id ) ); ?>" target="_self">
			<?php
			if ( has_post_thumbnail( $org_id ) ) {
				echo get_the_post_thumbnail( $org_id, 'medium' );
			}
			?>
			</a>
		</div>
	</div>
	<?php
}
