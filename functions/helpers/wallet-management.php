<?php
/**
 * Wallet management
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! function_exists( 'WC' ) ) {
	return;
}
/**
 * Add user credit to Tera Wallet
 *
 * @param int    $user_id User's ID.
 * @param int    $credit Credit.
 * @param string $description Description.
 * @return void
 */
function snks_wallet_credit( $user_id, $credit, $description = '' ) {
	if ( function_exists( 'woo_wallet' ) ) {
		woo_wallet()->wallet->credit( $user_id, $credit, $description );
	}
}

/**
 * Set user wallet balance to zero in Tera Wallet
 *
 * @param int    $user_id User's ID.
 * @param string $description Description.
 * @return void
 */
function snks_wallet_reset( $user_id, $description = '' ) {
	if ( function_exists( 'woo_wallet' ) ) {
		// Get the user's current wallet balance.
		$current_balance = woo_wallet()->wallet->get_wallet_balance( $user_id, 'edit' );

		// Check if there is any balance to reset.
		if ( $current_balance > 0 ) {
			// Debit the entire balance to set it to zero.
			woo_wallet()->wallet->debit( $user_id, $current_balance, $description );
		}
	}
}

/**
 * Get user wallet balance from Tera Wallet
 *
 * @param int $user_id User's ID.
 * @return float Wallet balance.
 */
function snks_get_wallet_balance( $user_id ) {
	if ( function_exists( 'woo_wallet' ) ) {
		// Get the user's current wallet balance.
		return woo_wallet()->wallet->get_wallet_balance( $user_id, 'edit' );
	}
	return 0;
}
