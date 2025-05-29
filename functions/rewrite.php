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
 * Custom rewrite rules.
 *
 * @return void
 */
function snks_rewrite_rule() {
	add_rewrite_rule( '^therapist/(\w+)/?', 'index.php?doctor_id=$matches[1]', 'top' );
	add_rewrite_rule( '^org/(\w+)/(\d+)/?', 'index.php?org=$matches[1]&_term_id=$matches[2]', 'top' );

	// ✅ New rule for specialties
	add_rewrite_rule( '^org/specialties/(\w+)/?', 'index.php?org=$matches[1]&is_specialties=1', 'top' );
}
add_action( 'init', 'snks_rewrite_rule' );

add_filter(
	'query_vars',
	function ( $query_vars ) {
		$query_vars[] = 'doctor_id';
		$query_vars[] = '_term_id';
		$query_vars[] = 'org';
		$query_vars[] = 'is_specialties'; // ✅ Register the new variable
		return $query_vars;
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		$user_id        = get_query_var( 'doctor_id' );
		$term_id        = get_query_var( '_term_id' );
		$is_specialties = get_query_var( 'is_specialties' );

		if ( $is_specialties ) {
			return SNKS_DIR . 'templates/specialties.php';
		}

		if ( $term_id ) {
			return SNKS_DIR . 'templates/page-org-doctors.php';
		}

		if ( $user_id ) {
			return SNKS_DIR . 'templates/page-your-clinic.php';
		}

		if ( is_singular( 'organization' ) ) {
			$custom_template = SNKS_DIR . 'templates/single-organization.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}
);
