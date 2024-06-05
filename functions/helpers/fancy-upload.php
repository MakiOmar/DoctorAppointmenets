<?php
/**
 * Fancy upload
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add custom user profile information
 *
 * @param int $id User ID.
 * @return mixed
 */
function snks_get_profile_image( $id ) {

	$profile_image = get_user_meta( $id, 'profile-image', true );

	if ( $profile_image && ! empty( $profile_image ) ) {
		return $profile_image;
	}

	return false;
}
