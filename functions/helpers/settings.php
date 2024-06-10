<?php
/**
 * Settings helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


/**
 * Return doctor settings
 *
 * @return array An array of settings if is a doctor.
 */
function snks_doctor_settings() {
	$settings = array();
	if ( snks_is_doctor() || current_user_can( 'manage_options' ) ) {
		$user_id                               = get_current_user_id();
		$settings['60_minutes']                = get_user_meta( $user_id, '60-minutes', true );
		$settings['45_minutes']                = get_user_meta( $user_id, '45-minutes', true );
		$settings['30_minutes']                = get_user_meta( $user_id, '30-minutes', true );
		$settings['enable_discount']           = get_user_meta( $user_id, 'enable_discount', true );
		$settings['discount_percent']          = get_user_meta( $user_id, 'discount_percent', true );
		$settings['to_be_old_number']          = get_user_meta( $user_id, 'to_be_old_number', true );
		$settings['to_be_old_unit']            = get_user_meta( $user_id, 'to_be_old_unit', true );
		$settings['allow_appointment_change']  = get_user_meta( $user_id, 'allow_appointment_change', true );
		$settings['free_change_before_number'] = get_user_meta( $user_id, 'free_change_before_number', true );
		$settings['free_change_before_unit']   = get_user_meta( $user_id, 'free_change_before_unit', true );
		$settings['block_if_before_number']    = get_user_meta( $user_id, 'block_if_before_number', true );
		$settings['block_if_before_unit']      = get_user_meta( $user_id, 'block_if_before_unit', true );
		$settings['online']                    = get_user_meta( $user_id, 'online', true );
		$settings['offline']                   = get_user_meta( $user_id, 'offline', true );
		$settings['both']                      = get_user_meta( $user_id, 'both', true );
		$settings['clinics_list']              = get_user_meta( $user_id, 'clinics_list', true );
	}

	return $settings;
}

/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['60_minutes'] ) {
		$is_available[] = 60;
	}
	if ( 'on' === $settings['45_minutes'] ) {
		$is_available[] = 45;
	}
	if ( 'on' === $settings['30_minutes'] ) {
		$is_available[] = 30;
	}
	return $is_available;
}

/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods_options() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['60_minutes'] ) {
		$is_available[] = array(
			'value' => '60',
			'label' => '60 دقيقة',
		);
	}
	if ( 'on' === $settings['45_minutes'] ) {
		$is_available[] = array(
			'value' => '45',
			'label' => '45 دقيقة',
		);
	}
	if ( 'on' === $settings['30_minutes'] ) {
		$is_available[] = array(
			'value' => '30',
			'label' => '30 دقيقة',
		);
	}
	return $is_available;
}
/**
 * Get doctor's available methods
 *
 * @return array
 */
function snks_get_available_attendance_types() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['online'] ) {
		$is_available[] = 'online';
	}
	if ( 'on' === $settings['offline'] ) {
		$is_available[] = 'offline';
	}
	if ( 'on' === $settings['both'] ) {
		$is_available[] = 'both';
	}
	return $is_available;
}

/**
 * Get doctor's available attendance types options
 *
 * @return array
 */
function snks_get_available_attendance_types_options() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['online'] ) {
		$is_available[] = array(
			'value' => 'online',
			'label' => 'أونلاين',
		);
	}
	if ( 'on' === $settings['offline'] ) {
		$is_available[] = array(
			'value' => 'offline',
			'label' => 'عيادة',
		);
	}
	if ( 'on' === $settings['both'] ) {
		$is_available[] = array(
			'value' => 'both',
			'label' => 'أونلاين وعيادة',
		);
	}
	return $is_available;
}

add_action(
	'wp_footer',
	'snks_get_available_attendance_types'
);
