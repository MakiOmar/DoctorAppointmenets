<?php
/**
 * Custom call backs
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_filter(
	'jet-engine/listings/allowed-callbacks',
	function ( $callbacks ) {

		$callbacks['snks_get_doctor_profile_image'] = 'Doctor image';
		$callbacks['snks_get_doctor_name']          = 'Doctor name';

		return $callbacks;
	}
);

/**
 * Get doctor's id.
 *
 * @param int $id Id.
 * @return string
 */
function snks_get_doctor_profile_image( $id ) {
	if ( is_numeric( $id ) ) {
		$image_url = get_user_meta( $id, 'user_profile_image', true );
		if ( ! empty( $image_url ) ) {
			return '<img src="' . $image_url . '"/>';
		}
	}
}
/**
 * Get doctor's name.
 *
 * @param int $id Id.
 * @return string
 */
function snks_get_doctor_name( $id ) {
	if ( is_numeric( $id ) ) {
		$user_data = get_userdata( $id );
		if ( ! $user_data ) {
			return;
		}
		$doctor_name = $user_data->first_name . ' ' . $user_data->last_name;
		return $doctor_name;
	}
}
