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
 * Get country price by period
 *
 * @param string $period Period.
 * @param string $country_code Country code.
 * @param array  $data_array Data array.
 * @return mixed
 */
function get_price_by_period_and_country( $period, $country_code, $data_array ) {
	if ( is_array( $data_array ) && isset( $data_array[ $period ]['countries'] ) ) {
		foreach ( $data_array[ $period ]['countries'] as $item ) {
			if ( $item['country_code'] === $country_code ) {
				return $item['price'];
			}
		}
	}
	return $data_array[ $period ]['others'];
}
/**
 * Get doctor pricings.
 *
 * @param int $user_id User's ID.
 * @return array
 */
function snks_doctor_pricings( $user_id ) {
	$available_periods = snks_get_available_periods( $user_id );
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
		$current_datetime = new DateTime();

		// Subtract the specified number of hours from the current datetime.
		$current_datetime->sub( new DateInterval( 'PT' . $to_be_old_period . 'H' ) );

		// Create a DateTime object for the given datetime.
		$compare_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $latest_completed_order_date );
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
