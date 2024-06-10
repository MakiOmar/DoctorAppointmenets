<?php
/**
 * Dynamically generate attandance types options
 *
 * @package Shrinks
 */

namespace Jet_Form_Builder\Generators;

defined( 'ABSPATH' ) || die();

/**
 * Dynamically generate options
 *
 * @package Nafea
 */
class Snks_Available_Attendance_Types extends Base {

	/**
	 * Returns generator ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get_attendance_types';
	}

	/**
	 * Returns generator name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Get attendance types', 'jet-form-builder' );
	}

	/**
	 * Returns generated options list
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function generate( $args ) {

		$result = snks_get_available_attendance_types_options();

		return $result;
	}
}

add_filter(
	'jet-form-builder/forms/options-generators',
	function ( $objects ) {
		$objects[] = new Snks_Available_Attendance_Types();
		return $objects;
	}
);
