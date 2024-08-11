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
