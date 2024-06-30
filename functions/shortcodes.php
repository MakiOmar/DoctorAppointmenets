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
	'snks_timetable_preview',
	function () {
		return snks_generate_preview();
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
