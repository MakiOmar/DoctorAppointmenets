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
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			return;
		}
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
		
		if ( is_page('doctor-login') && is_user_logged_in() && ( snks_is_doctor() || snks_is_clinic_manager() ) ) {
			wp_redirect( add_query_arg( 'id', snks_get_settings_doctor_id(), home_url( '/account-setting' ) ) );
			exit;
		}
		

		if ( ( is_page('account-setting') ) ) {
			if ( ! is_user_logged_in() ) {
				wp_redirect( site_url('doctor-login') );
			}
			if ( snks_is_patient() ) {
				wp_redirect( site_url('my-bookings') );
				exit;
			}
			if( ( snks_is_doctor() || snks_is_clinic_manager() ) && empty( $_GET['id'] ) ) {
				wp_redirect( site_url() );
				exit;
			}
		}
	}
);

add_filter(
	'jet-popup/ajax-request/get-elementor-content',
	function($content, $popup_data) {
		if ( 4085 === absint( $popup_data['popup_id'] ) && ! snks_validate_doctor_settings( snks_get_settings_doctor_id() ) ) {
				$content = do_shortcode('[elementor-template id="4122"]');
		}
		return $content;
	},
	9999,
	2
);