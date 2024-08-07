<?php
/**
 * Rewrite
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Custom rewrite rule.
 *
 * @return void
 */
function snks_rewrite_rule() {
	add_rewrite_rule( '^your-clinic/(\w+)/?', 'index.php?doctor_id=$matches[1]', 'top' );
}
add_action( 'init', 'snks_rewrite_rule' );

add_filter(
	'query_vars',
	function ( $query_vars ) {
		$query_vars[] = 'doctor_id';
		return $query_vars;
	}
);
add_filter(
	'template_include',
	function ( $template ) {
		$user_id = get_query_var( 'doctor_id' );

		if ( $user_id ) {
			return SNKS_DIR . 'templates/page-your-clinic.php';
		}

		return $template;
	}
);
