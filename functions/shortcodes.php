<?php
/**
 * Shortcodes
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_shortcode(
	'snks_to_do_form',
	function () {
		$html  = snks_render_to_do_days( snks_get_to_do_days() );
		$html .= snks_generate_hourly_form( gmdate( 'Y-m-d' ) );
		return $html;
	}
);

add_shortcode(
	'snks_consulting_form',
	function () {
		return snks_generate_consulting_form();
	}
);

add_shortcode(
	'snks_patient_sessions',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'tense' => 'future',
			),
			$atts
		);
		return snks_render_sessions_listing( $atts['tense'] );
	}
);

add_shortcode(
	'snks_family_sessions',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'tense' => 'all',
			),
			$atts
		);
		return snks_render_sessions_listing( $atts['tense'], 'family' );
	}
);

add_shortcode(
	'snks_doctor_sessions',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'tense' => 'future',
			),
			$atts
		);
		return snks_render_sessions_listing( $atts['tense'], 'doctor' );
	}
);

add_shortcode(
	'snks_go_back',
	function () {
		return snks_go_back();
	}
);
