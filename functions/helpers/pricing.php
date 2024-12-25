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
 * @return int
 */
function snks_calculated_price( $user_id, $country, $period ) {
	$has_discount = snks_discount_eligible( $user_id );
	$latest_order = snks_latest_completed_order( $user_id );
	if ( $has_discount && $latest_order ) {
		$pricings = get_post_meta( $latest_order->get_id(), 'doctor_pricings', true );
		if ( ! $pricings || empty( $pricings ) ) {
			$pricings = snks_doctor_pricings( $user_id );
		}
	} else {
		$pricings = snks_doctor_pricings( $user_id );
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
 * @param int $user_id User's ID.
 * @return array
 */
function snks_doctor_pricings( $user_id ) {
	$available_periods = snks_get_periods( $user_id );
	$pricings          = array();
	foreach ( $available_periods as $period ) {
		$pricings[ $period ] = array(
			'countries' => get_user_meta( $user_id, $period . '_minutes_pricing', true ),
			'others'    => get_user_meta( $user_id, $period . '_minutes_pricing_others', true ),
		);
	}
	return $pricings;
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
		$to_be_old_number            = get_user_meta( $doctor_id, 'to_be_old_number', true );
		$to_be_old_unit              = get_user_meta( $doctor_id, 'to_be_old_unit', true );
		$multiply_base               = 24;
		if ( 'week' === $to_be_old_unit ) {
			$multiply_base = 7 * 24;
		} elseif ( 'month' === $to_be_old_unit ) {
			$multiply_base = 30 * 24;
		}
		$to_be_old_period = $to_be_old_number * $multiply_base;

		// Get the current datetime.
		$current_datetime = current_datetime();

		// Subtract the specified number of hours from the current datetime.
		$current_datetime->sub( new DateInterval( 'PT' . $to_be_old_period . 'H' ) );

		// Create a DateTime object for the given datetime.
		$compare_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $latest_completed_order_date, wp_timezone() );

		// Compare the given datetime with the current datetime.
		if ( $compare_datetime < $current_datetime ) {
			$has_discount = true;
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
function snks_session_total_price_old( $session_price, $attendance_type, $context = 'book' ) {

	if ( 'book' !== $context ) {
		$a = 0;
	} else {
		// Define A based on the attendance type.
		$a = 'online' === $attendance_type ? 2.28 : 1.14; // sms fees.
	}
	// Calculate B and C (both are the same for online and offline).
	$b = ( $session_price * 0.025 + 2 ) * 1.14; // Payment gateway.
	$c = ( $session_price * 0.005 ) * 1.14; // Payout.
	if ( 'book' === $context ) {
		// Determine D based on the session price and attendance type.
		if ( 'online' === $attendance_type ) {
			if ( $session_price < 50 ) {
				$d = 2.85; // Is jalash earnings.
			} elseif ( $session_price >= 50 && $session_price < 100 ) {
				$d = 5.7;
			} elseif ( $session_price >= 100 && $session_price < 200 ) {
				$d = 11.4;
			} elseif ( $session_price >= 200 && $session_price < 300 ) {
				$d = 13.68;
			} elseif ( $session_price >= 300 && $session_price < 400 ) {
				$d = 14.82;
			} elseif ( $session_price >= 400 && $session_price < 500 ) {
				$d = 15.96;
			} else {
				$d = 17.1;
			}
		} else {
			// Offline case.
			$d = $session_price < 50 ? 2.85 : 5.7;
		}
	} else {
		$d = 0;
	}

	// Calculate E based on A, B, C, D.
	$e = ( $a + $b + $c + $d ) * 0.025 * 1.03 * 1.14;

	// Calculate F (the final total).
	$f = $a + $b + $c + $d + $e;

	$service_fees = round( ( $f / 1.14 ), 2 );
	$vat          = round( ( $f - $service_fees ), 2 );
	$total        = $session_price + $service_fees + $vat;
	// Return the final session price including service fees and VAT.
	if ( $total < 5 ) {
		return array(
			'session_price' => 0,
			'service_fees'  => 4.38,
			'vat'           => 0.62,
			'total_price'   => 5,
		);
	} else {
		return array(
			'session_price' => $session_price,
			'service_fees'  => $service_fees,
			'vat'           => $vat,
			'total_price'   => $session_price + $service_fees + $vat,
		);
	}
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
	// Paymob accept.
	$p = ( $session_price * 0.0275 + 3 ) * 1.14; // Payment gateway.
	// Paymob send.
	$b = ( $session_price * 0.01 ) * 1.14;

	$pb = $b + $p;

	if ( 'book' === $context ) {
		// Determine D based on the session price and attendance type.
		if ( 'online' === $attendance_type ) {
			$a = 1.92;
			if ( $session_price < 50 ) {
				$c = 2.85; // Is jalash earnings.
			} elseif ( $session_price >= 50 && $session_price < 100 ) {
				$c = 5.7;
			} else {
				$c = 11.4;
			}
		} else {
			$a = 0.96;
			// Offline case.
			$c = 5.7;
		}
	} else {
		$a = 0;
		$c = 0;
	}

	$d = ( $pb + $c + $a ) * 0.0275 * 1.03 * 1.14;

	// Calculate F (the final total).
	$f = $pb + $d;

	$service_fees = round( ( $c + $a ), 2 );
	$paymob       = round( ( $f ), 2 );
	$total        = $session_price + $service_fees + $paymob;
	// Return the final session price including service fees and VAT.
	if ( $total < 1 ) {
		return array(
			'session_price' => 0,
			'service_fees'  => 0,
			'paymob'        => 0,
			'total_price'   => 0,
		);
	} elseif ( $total > 0 && $total < 5 ) {
		return array(
			'session_price' => $session_price,
			'service_fees'  => 5 - $session_price,
			'paymob'        => 0,
			'total_price'   => 5,
		);
	} else {
		return array(
			'session_price' => $session_price,
			'service_fees'  => $service_fees,
			'paymob'        => $paymob,
			'total_price'   => $total,
		);
	}
}
