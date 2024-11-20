<?php
/**
 * Get clinics
 *
 * @package Shrinks
 */

namespace Jet_Form_Builder\Generators;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Jet_Form_Builder\Generators\Base' ) ) {
	return;
}
/**
 * Get clinics
 *
 * @package Nafea
 */
class Snks_Get_Clinics_By_Id extends Base {

	/**
	 * Returns generator ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get_clinics_by_id';
	}

	/**
	 * Returns generator name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Get form clinics', 'jet-form-builder' );
	}

	/**
	 * Returns generated options list
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function generate( $args ) {
		$user_id = snks_url_get_doctors_id();
		if ( ! $user_id ) {
			return;
		}
		$clinics_meta = get_user_meta( $user_id, 'clinics_list', true );
		$result       = array(
			array(
				'value' => '',
				'label' => 'حدد خياراً',
			),
		);
		if ( ! empty( $clinics_meta ) ) {
			foreach ( $clinics_meta as $index => $clinic ) {
				if ( empty( $clinic['uuid'] ) || ( isset( $clinic['disabled'] ) && 'on' === $clinic['disabled'] ) ) {
					continue;
				}
				$result[] = array(
					'value' => $clinic['uuid'],
					'label' => $clinic['clinic_title'],
				);
			}
		}

		return $result;
	}
}

add_filter(
	'jet-form-builder/forms/options-generators',
	function ( $objects ) {
		$objects[] = new Snks_Get_Clinics_By_Id();
		return $objects;
	}
);
