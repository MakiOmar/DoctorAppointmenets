<?php
/**
 * Pricing
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Calculate price.
 *
 * @param int    $user_id Doctor's ID.
 * @param string $country Country code.
 * @param int    $period Period.
 * @param string $attendance_type Attendance type (online/offline).
 * @return int
 */
function snks_calculated_price( $user_id, $country, $period, $attendance_type = 'online' ) {
	$has_discount = snks_discount_eligible( $user_id );
	$latest_order = snks_latest_completed_order( $user_id );
	if ( $has_discount && $latest_order ) {
		$pricings = $latest_order->get_meta( 'doctor_pricings', true );
		if ( ! $pricings || empty( $pricings ) ) {
			$pricings = snks_doctor_pricings( $user_id, $attendance_type );
		}
	} else {
		$pricings = snks_doctor_pricings( $user_id, $attendance_type );
	}
	$price = get_price_by_period_and_country( $period, $country, $pricings );

	return $price;
}

/**
 * Get country price by period
 *
 * @param string $period Period.
 * @param string $country_code Country code.
 * @param array  $data_array Data array.
 * @return mixed
 */
function get_price_by_period_and_country( $period, $country_code, $data_array ) {
	if ( is_array( $data_array ) && is_array( $data_array[ $period ]['countries'] ) ) {
		foreach ( $data_array[ $period ]['countries'] as $item ) {
			if ( $item['country_code'] === $country_code ) {
				return $item['price'];
			}
		}
		return $data_array[ $period ]['others'];
	} elseif ( is_numeric( $data_array[ $period ]['others'] ) ) {
		return $data_array[ $period ]['others'];
	}
	return 0;
}

/**
 * Get doctor pricings.
 *
 * @param int    $user_id User's ID.
 * @param string $attendance_type Attendance type (online/offline).
 * @return array
 */
function snks_doctor_pricings( $user_id, $attendance_type = 'online' ) {
	$available_periods = snks_get_periods( $user_id );
	$pricings = array();
	
	foreach ( $available_periods as $period ) {
		if ( 'offline' === $attendance_type ) {
			// Get offline pricing
			$offline_countries = get_user_meta( $user_id, $period . '_minutes_pricing_offline', true );
			$offline_others = get_user_meta( $user_id, $period . '_minutes_pricing_offline_others', true );
			
			// Get online pricing for fallback
			$online_countries = get_user_meta( $user_id, $period . '_minutes_pricing', true );
			$online_others = get_user_meta( $user_id, $period . '_minutes_pricing_others', true );
			
			// Use offline prices if set, otherwise fallback to online prices
			$pricings[ $period ] = array(
				'countries' => ! empty( $offline_countries ) ? $offline_countries : $online_countries,
				'others'    => ! empty( $offline_others ) ? $offline_others : $online_others,
			);
		} else {
			// Use online pricing fields (original fields)
			$pricings[ $period ] = array(
				'countries' => get_user_meta( $user_id, $period . '_minutes_pricing', true ),
				'others'    => get_user_meta( $user_id, $period . '_minutes_pricing_others', true ),
			);
		}
	}
	
	return $pricings;
}

/**
 * Get doctor offline pricings.
 *
 * @param int $user_id User's ID.
 * @return array
 */
function snks_doctor_offline_pricings( $user_id ) {
	return snks_doctor_pricings( $user_id, 'offline' );
}

/**
 * Get doctor online pricings.
 *
 * @param int $user_id User's ID.
 * @return array
 */
function snks_doctor_online_pricings( $user_id ) {
	return snks_doctor_pricings( $user_id, 'online' );
}

/**
 * Check if a customer is eligible for discount
 *
 * @param int   $doctor_id doctor's ID.
 * @param mixed $customer_id Customer's ID.
 * @return bool
 */
function snks_discount_eligible( $doctor_id, $customer_id = false ) {
	if ( ! $customer_id ) {
		$customer_id = get_current_user_id();
	}
	$has_discount = false;
	if ( snks_pricing_discount_enabled( $doctor_id ) ) {
		$latest_completed_order_date = snks_latest_completed_order_date( $doctor_id, $customer_id );
		if ( ! $latest_completed_order_date ) {
			return false;
		}
		$to_be_old_number = get_user_meta( $doctor_id, 'to_be_old_number', true );
		$to_be_old_unit   = get_user_meta( $doctor_id, 'to_be_old_unit', true );
		$multiply_base    = 24;
		if ( 'week' === $to_be_old_unit ) {
			$multiply_base = 7 * 24;
		} elseif ( 'month' === $to_be_old_unit ) {
			$multiply_base = 30 * 24;
		}
		$to_be_old_period = $to_be_old_number * $multiply_base;
		// Get the current datetime.
		$current_datetime = current_datetime();

		// Create a DateTime object for the given datetime.
		$compare_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $latest_completed_order_date, wp_timezone() );

		// Check the difference in hours.
		if ( $compare_datetime ) {
			$interval         = $compare_datetime->diff( $current_datetime );
			$hours_difference = ( $interval->days * 24 ) + $interval->h;

			if ( $hours_difference <= $to_be_old_period ) {
				$has_discount = true;
			}
		}
	}

	return $has_discount;
}

/**
 * Check if pricing discount is enabled
 *
 * @param mixed $user_id Users ID.
 * @return bool
 */
function snks_pricing_discount_enabled( $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$enable_discount = get_user_meta( $user_id, 'enable_discount', true );
	return 'on' === $enable_discount;
}

/**
 * Session price formula
 *
 * @param mixed  $session_price  Session original price.
 * @param string $attendance_type Whether it is online or offline.
 * @param string $context Calculation context weather if is first time book or edit. Accepts book|any.
 * @return mixed
 */
function snks_session_total_price( $session_price, $attendance_type, $context = 'book' ) {
	/**
	 * $x = $session_price
	 * $a = kasheir gateway ( Receiving )
	 * $b = Kasheir payout ( sending )
	 * $c = Jalsah commission
	 * $d = Receiving fees of a + b + c
	 */

	$a = ( $session_price * 0.025 + 2 ) * 1.14;
	$b = ( $session_price * 0.001 ) * 1.14;
	// For editing.
	if ( 'book' !== $context ) {
		$c = 0;
		// For Booking.
	} elseif ( 'online' === $attendance_type ) {
		if ( $session_price < 5 ) {
			$c = 0;
		} elseif ( $session_price > 5 && $session_price < 50 ) {
			$c = 3.99 + 1.92;
		} elseif ( $session_price >= 50 && $session_price < 100 ) {
			$c = 6.56 + 1.92;
		} elseif ( $session_price >= 100 && $session_price < 200 ) {
			$c = 13.68 + 1.92;
		} elseif ( $session_price >= 200 && $session_price < 300 ) {
			$c = 15.39 + 1.92;
		} elseif ( $session_price >= 300 && $session_price < 400 ) {
			$c = 17.1 + 1.92;
		} elseif ( $session_price >= 400 && $session_price < 500 ) {
			$c = 17.67 + 1.92;
		} elseif ( $session_price >= 500 && $session_price < 600 ) {
			$c = 18.25 + 1.92;
		} else {
			$c = 19.38 + 1.92;
		}
	} else {
		$c = 5.13 + 0.96;
	}
	$d = ( $a + $b + $c ) * 0.025 * 1.03 * 1.14;

	// Calculate F (the final total).
	$f = $a + $b + $c + $d + $session_price;

	$service_fees = ceil( $c * 100 ) / 100;
	$kasheir_fees = ceil( ( $a + $b + $d ) * 100 ) / 100;
	$total        = $f;
	// Return the final session price including service fees and kasheir_fees.
	return array(
		'session_price' => $session_price,
		'service_fees'  => $service_fees,
		'paymob'        => $kasheir_fees,
		'total_price'   => ceil( $f * 100 ) / 100,
	);
}
