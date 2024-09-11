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
