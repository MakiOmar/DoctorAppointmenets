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
		$converted_price = $price;
		$currency_label  = $egp_label;

		if ( class_exists( 'Currency_Exchange_Dashboard' ) ) {
			$selected_currency = isset( $_COOKIE['ced_selected_currency'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ced_selected_currency'] ) ) : 'EGP';
			$direction         = 'EGP' !== $selected_currency ? 'from' : 'to';
			$converted         = Currency_Exchange_Dashboard::convert_currency( $price, $direction );
			if ( null !== $converted ) {
				$converted_price = number_format( $converted, 2 );
				$currency_label  = isset( $_COOKIE['ced_selected_currency'] ) ? strtoupper( $selected_currency ) : $egp_label;
			}
		}

		return array( $converted_price, $currency_label );
	}
}
