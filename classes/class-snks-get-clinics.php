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
class Snks_Get_Clinics extends Base {

	/**
	 * Returns generator ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get_clinics';
	}

	/**
	 * Returns generator name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Get clinics', 'jet-form-builder' );
	}

	/**
	 * Returns generated options list
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function generate( $args ) {
		$clinics_meta = get_user_meta( get_current_user_id(), 'clinics_list', true );
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
		} else {
			$result = array(
				array(
					'value' => '',
					'label' => 'فضلاً قم بإضافة عيادات',
				),
			);
		}

		return $result;
	}
}

add_filter(
	'jet-form-builder/forms/options-generators',
	function ( $objects ) {
		$objects[] = new Snks_Get_Clinics();
		return $objects;
	}
);
