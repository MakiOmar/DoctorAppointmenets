<?php
/**
 * Login lock.
 *
 * @package Jalsah
 */

defined( 'ABSPATH' ) || die();

/**
 * Adds custom rewrite rule for login endpoint.
 */
add_action(
	'init',
	function () {
		add_rewrite_rule( '^ibra/?$', 'index.php?custom_login=1', 'top' );
	}
);

/**
 * Adds custom query variable.
 *
 * @param array $vars Existing query variables.
 * @return array Modified query variables.
 */
function jalsah_custom_query_vars( $vars ) {
	$vars[] = 'custom_login';
	return $vars;
}
add_filter( 'query_vars', 'jalsah_custom_query_vars' );

/**
 * Handles rewrite endpoint for custom login.
 */
function jalsah_rewrite_endpoint() {
	$custom_login = get_query_var( 'custom_login' );

	if ( $custom_login ) {
		include ABSPATH . 'wp-login.php';
		exit;
	}
}
add_action( 'template_redirect', 'jalsah_rewrite_endpoint' );

/**
 * Secures the login process by redirecting direct access attempts.
 */
add_action(
	'login_init',
	function () {
		if ( isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
        // phpcs:disable
		if ( ! is_user_logged_in() && isset( $_SERVER['REQUEST_METHOD'] ) && isset( $_SERVER['REQUEST_URI'] )
			&& 'POST' !== $_SERVER['REQUEST_METHOD']
			&& ( false !== strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' )
				|| false !== strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) )
			&& ! is_admin()
		) {
			wp_safe_redirect( site_url( '/not-found' ) );
			exit;
		}
	}
    // phpcs:enable
);

/**
 * Customizes logout redirect URL.
 *
 * @return string Logout redirect URL.
 */
add_filter(
	'logout_redirect',
	function () {
		return site_url( '/ibra' );
	}
);
