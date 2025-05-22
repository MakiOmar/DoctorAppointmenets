<?php

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
				$converted_price = floor($converted) != $converted ? number_format($converted, 2) : number_format($converted, 0);
				snks_error_log( $currencies_labels );
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
