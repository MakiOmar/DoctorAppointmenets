<?php
/**
 * Meeting room helpers
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Checks if a doctor has joined a room.
 *
 * @param int $room_id Room|timetable's ID.
 * @param int $doctor_id Docotor's ID.
 * @return bool
 */
function snks_doctor_has_joined( $room_id, $doctor_id ) {
	return get_transient( "doctor_has_joined_{$room_id}_{$doctor_id}" );
}

/**
 * Checks if a rochtah doctor has joined a rochtah session.
 *
 * @param int $booking_id Rochtah booking ID.
 * @return bool
 */
function snks_rochtah_doctor_has_joined( $booking_id ) {
	global $wpdb;
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT doctor_joined FROM {$wpdb->prefix}snks_rochtah_bookings WHERE id = %d",
		$booking_id
	) );
	return $booking && $booking->doctor_joined == 1;
}
