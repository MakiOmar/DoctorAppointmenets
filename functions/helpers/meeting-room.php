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
