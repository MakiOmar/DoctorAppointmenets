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
	'snks_bookings',
	function () {
		return snks_generate_bookings();
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
	'snks_go_back',
	function () {
		return snks_go_back();
	}
);
add_shortcode(
	'snks_appointment_form',
	function () {
		if ( ! is_user_logged_in() ) {
			return '<p>سجل دخولك أولاً من فضلك</p>';
		}
		$html    = '';
		$user_id = snks_url_get_doctors_id();
		if ( ! $user_id ) {
			return 'بيانات الطبيب غير صحيحة';
		}
		$html .= snks_form_filter( $user_id );
		$html .= '<div id="consulting-forms-container"></div>';
		$html .= '<p>Please note that we have used EG as default country temporarily</p>';
		return $html;
	}
);
