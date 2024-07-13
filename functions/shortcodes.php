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
	function () {
		global $wp;
		$html  = '';
		$forms = '';
		if ( isset( $wp->query_vars ) && 'doctor' === $wp->query_vars['pagename'] ) {
			$user_id = snks_url_get_doctors_id();
			if ( $user_id ) {
				$avialable_periods = snks_get_available_periods( $user_id );
				$country           = 'EG';
				$pricings          = snks_doctor_pricings( $user_id );
				if ( is_array( $avialable_periods ) ) {
					$tabs = '<ul id="consulting-forms-tabs">';
					foreach ( $avialable_periods as $period ) {
						$price  = get_price_by_period_and_country( $period, $country, $pricings );
						$tabs  .= '<li class="consulting-forms-tab" data-target="consulting-form-' . esc_attr( $period ) . '">' . sprintf( '%1$s %2$s ( %3$s %4$s )', esc_html( $period ), 'دقيقة', esc_html( $price ), 'جنيه' ) . '</li>';
						$forms .= snks_generate_consulting_form( $user_id, absint( $period ), $price );
					}
					$tabs .= '</ul>';
				}
			}
		}
		if ( ! empty( $forms ) ) {
			$html = $tabs . '<div id="consulting-forms-container">' . $forms . '</div>';
		}
		$html .= '<p>Please note that we have used EG as default country temporarily</p>';
		return $html;
	}
);
