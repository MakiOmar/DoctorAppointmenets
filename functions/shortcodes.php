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

add_shortcode(
	'snks_appointment_form',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'period' => '',
			),
			$atts
		);
		if ( ! in_array( $atts['period'], array( '60', '45', '30' ), true ) ) {
			return;
		}
		//phpcs:disable
		preg_match( '/\d+/', urldecode( $_SERVER[ 'REQUEST_URI' ] ), $match );
		if ( ! $match ) {
			return;
		}
		//phpcs:enable
		$user_id = array_shift( $match );
		return '<p>Please pay attention that you are using explict user ID of 36, you need to change this later in the code.</p><br>' . snks_generate_consulting_form( 36, absint( $atts['period'] ) );
	}
);
