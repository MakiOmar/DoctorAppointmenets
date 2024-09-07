<?php
/**
 * Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! function_exists( 'anony_elementor_custom_fonts_group' ) ) {
	/**
	 * Add to fonts group
	 *
	 * @param array $font_groups Fonts group.
	 * @return array
	 */
	function anony_elementor_custom_fonts_group( $font_groups ) {
		$font_groups['jalsah'] = esc_html__( 'Jalsah', 'jalsah' );
		return $font_groups;
	}
}
add_filter( 'elementor/fonts/groups', 'anony_elementor_custom_fonts_group', 99 );

if ( ! function_exists( 'anony_elementor_custom_fonts' ) ) {
	/**
	 * Add fonts to elementor
	 *
	 * @param array $fonts Fonts' array.
	 * @return array
	 */
	function anony_elementor_custom_fonts( $fonts ) {

		$fonts['pt_bold_headingregular']       = 'jalsah';
		$fonts['hacen_liner_print-outregular'] = 'jalsah';
		$fonts['castle_tbook']                 = 'jalsah';
		return $fonts;
	}
}
add_filter( 'elementor/fonts/additional_fonts', 'anony_elementor_custom_fonts', 999 );
