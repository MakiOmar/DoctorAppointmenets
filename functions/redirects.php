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
	}
);

/**
 * Redirect from WooCommerce My Account page to the home page.
 */
function snks_redirect_my_account_page() {
    // Check if the current page is the WooCommerce My Account page.
    if ( is_account_page() && ! is_user_logged_in() ) {
        wp_redirect( site_url('login') ); // Redirect to home page or specify another URL.
        exit;
    }
}
add_action( 'template_redirect', 'snks_redirect_my_account_page' );
