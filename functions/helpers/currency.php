<?php
/**
 * Currency helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'acrsw_currency' ) ) {
	/**
	 * Get currency information
	 *
	 * @param int|string $price Price.
	 * @param string     $egp_label EGP currency label.
	 * @return array
	 */
	function acrsw_currency( $price, $egp_label = 'ج.م' ) {
		$converted_price   = $price;
		$currency_label    = $egp_label;
		$currencies_labels = array(
			'EGP' => 'ج.م',
			'SAR' => 'ر.س',
			'AED' => 'د.إ',
			'KWD' => 'د.ك',
			'QAR' => 'ر.ق',
			'BHD' => 'د.ب',
			'OMR' => 'ر.ع',
			'EUR' => '€',
			'USD' => 'USD',
			'GBP' => 'GBP',
			'CAD' => 'CAD',
			'AUD' => 'AUD',
		);

		if ( class_exists( 'Currency_Exchange_Dashboard' ) ) {
			$selected_currency = isset( $_COOKIE['ced_selected_currency'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ced_selected_currency'] ) ) : 'EGP';
			$direction         = 'EGP' !== $selected_currency ? 'from' : 'to';
			$converted         = Currency_Exchange_Dashboard::convert_currency( $price, $direction );

			if ( null !== $converted ) {
				// Get the fractional part.
				$fraction = $converted - floor( $converted );

				// Apply custom rounding rules.
				if ( $fraction > 0 && $fraction <= 0.5 ) {
					$converted_price = floor( $converted ) + 0.5;
				} elseif ( $fraction > 0.5 ) {
					$converted_price = ceil( $converted );
				} else {
					$converted_price = floor( $converted );
				}

				// Format the number (show .0 for whole numbers, .5 for half numbers).
				$converted_price = number_format( $converted_price, ( floor( $converted_price ) != $converted_price ? 2 : 0 ) );

				if ( isset( $_COOKIE['ced_selected_currency'] ) ) {
					if ( isset( $currencies_labels[ strtoupper( $selected_currency ) ] ) ) {
						$currency_label = $currencies_labels[ strtoupper( $selected_currency ) ];
					} else {
						$currency_label = strtoupper( $selected_currency );
					}
				} else {
					$currency_label = $egp_label;
				}
			}
		}

		return array( $converted_price, $currency_label );
	}
}
