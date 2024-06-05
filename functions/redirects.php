<?php
/**
 * Redirects
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

// 682 : Dashboard, 1194: Consulting appointments, 328: My account, 1344: Profile, 1294: zego, 1194: consulting appointments, 1158: Consulting form, 1342: Programme.

add_action(
	'wp_login',
	function ( $user_login, $user ) {
		if ( in_array( 'doctor', $user->roles, true ) ) {
			return;
		}
	},
	10,
	2
);
