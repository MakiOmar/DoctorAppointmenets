<?php
/**
 * Redirects
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

// 682 : Dashboard, 1194: Consulting appointments, 328: My account, 1344: Profile, 1294: zego, 1194: consulting appointments, 1158: Consulting form, 1342: Programme.

add_action(
	'template_redirect',
	function () {
		if ( ! is_user_logged_in() && ( is_page( 1935 ) || is_page( 2444 ) ) ) {
			wp_redirect( site_url( '/my-account' ) );
			exit;
		}
	},
	10,
	2
);
