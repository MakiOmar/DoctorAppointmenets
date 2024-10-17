<?php
/**
 * Redirects
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action(
	'template_redirect',
	function () {
		global $wp;
		//phpcs:disable
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '7jz' ) && ( ! isset( $wp->query_vars ) || empty( $wp->query_vars['doctor_id'] ) ) ) {
			wp_redirect( site_url() );
			exit;
		}

		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/org/' ) && ( ! isset( $wp->query_vars ) || ( empty( $wp->query_vars['organization'] ) && empty( $wp->query_vars['term_id'] ) ) ) ) {
			wp_redirect( site_url() );
			exit;
		}

		// Check if the current page is the WooCommerce My Account page.
		if ( is_account_page() && ! is_user_logged_in() ) {
			wp_redirect( site_url('login') );
			exit;
		}
	
		if ( is_page('account-setting') && ! is_user_logged_in() ) {
			wp_redirect( site_url('doctor-login') );
			exit;
		}
		if ( ( is_page('account-setting') ) && snks_is_patient() ) {
			wp_redirect( site_url('my-bookings') );
			exit;
		}
		// Make sure complete all required settings
		if ( snks_is_doctor() && is_page('add-appointments') && ! snks_validate_doctor_settings( get_current_user_id() ) ) {
			wp_redirect( add_query_arg( 'error', 'complete-settings', site_url('account-setting') ) );
			exit;
		}
	}
);
