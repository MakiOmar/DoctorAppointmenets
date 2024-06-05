<?php
/**
 * Zego
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'zego_user_name',
	function ( $user_name ) {
		$user = wp_get_current_user();
		if ( in_array( 'family', $user->roles, true ) || in_array( 'customer', $user->roles, true ) ) {
			return get_user_meta( get_current_user_id(), 'nickname', true );
		} elseif ( in_array( 'doctor', $user->roles, true ) ) {
			return get_user_meta( get_current_user_id(), 'first_name', true );
		}
		return $user_name;
	}
);

add_filter(
	'anzgc_zegocloud_init_check',
	function ( $check ) {
		if ( ! is_page( 'zego' ) ) {
			return false;
		}
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$url_params = wp_unslash( $_GET );
		$timetable  = snks_get_timetable_by( 'ID', absint( $url_params['room_id'] ) );
		if ( ! $timetable ) {
			return false;
		}
		$user_id   = get_current_user_id();
		$clients   = explode( ',', $timetable->client_id );
		$clients[] = $timetable->user_id;
		$clients   = array_map( 'absint', $clients );
		if ( ! in_array( $user_id, $clients, true ) ) {
			return false;
		}

		$time_lock = false;
		if ( $time_lock ) {
			//phpcs:disable WordPress.DateTime.CurrentTimeTimestamp.Requested
			$scheduled_timestamp = strtotime( $timetable->date_time );
			$current_timestamp   = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
			if ( $scheduled_timestamp > $current_timestamp ) {
				return false;
			}
			if ( $current_timestamp > $scheduled_timestamp && ( $current_timestamp - $scheduled_timestamp ) > 60 * 15 ) {
				return false;
			}
		}
		return $check;
	}
);
add_filter(
	'anzgc_zegocloud_host_role',
	function ( $role ) {
		$role = 'provider';
		return $role;
	}
);

add_filter(
	'anzgc_zegocloud_attandee_role',
	function ( $role ) {
		$role = 'customer';
		return $role;
	}
);

add_filter(
	'anzgc_zegocloud_current_role',
	function ( $role ) {
		if ( snks_is_doctor() ) {
			return 'provider';
		}
	}
);

add_filter(
	'anzgc_zegocloud_session_seetings',
	function ( $settings ) {
		if ( ! snks_is_doctor() ) {
			$settings['showMyCameraToggleButton']     = false;
			$settings['showAudioVideoSettingsButton'] = false;
			$settings['showScreenSharingButton']      = false;
		}
		return $settings;
	}
);
