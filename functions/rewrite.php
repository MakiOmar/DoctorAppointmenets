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
	add_rewrite_rule( '^7jz/(\w+)/?', 'index.php?doctor_id=$matches[1]', 'top' );
	add_rewrite_rule( '^org/(\w+)/(\d+)/?', 'index.php?org=$matches[1]&term_id=$matches[2]', 'top' );
}
add_action( 'init', 'snks_rewrite_rule' );

add_filter(
	'query_vars',
	function ( $query_vars ) {
		$query_vars[] = 'doctor_id';
		$query_vars[] = 'term_id';
		$query_vars[] = 'org';
		return $query_vars;
	}
);
add_filter(
	'template_include',
	function ( $template ) {
		$user_id = get_query_var( 'doctor_id' );
		$term_id = get_query_var( 'term_id' );

		if ( $term_id ) {
			return SNKS_DIR . 'templates/page-org-doctors.php';
		}

		if ( $user_id ) {
			return SNKS_DIR . 'templates/page-your-clinic.php';
		}

		return $template;
	}
);
