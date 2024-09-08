<?php
/**
 * Dynamically generate options
 *
 * @package Shrinks
 */

namespace Jet_Form_Builder\Generators;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Jet_Form_Builder\Generators\Base' ) ) {
	return;
}
/**
 * Dynamically generate options
 *
 * @package Nafea
 */
class Snks_Available_Periods extends Base {

	/**
	 * Returns generator ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get_available_periods';
	}

	/**
	 * Returns generator name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Get available periods', 'jet-form-builder' );
	}

	/**
	 * Returns generated options list
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function generate( $args ) {

		$result = snks_get_periods_possibilities_options();

		return $result;
	}
}

add_filter(
	'jet-form-builder/forms/options-generators',
	function ( $objects ) {
		$objects[] = new Snks_Available_Periods();
		return $objects;
	}
);
