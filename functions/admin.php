<?php
/**
 * Admin edits
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_filter(
	'user_profile_update_errors',
	function ( $errors, $update, $user ) {
		//phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( $update && isset( $_POST['nickname'] ) ) {
			$new_nickname = sanitize_text_field( wp_unslash( $_POST['nickname'] ) );

			$existing_user = get_user_by( 'slug', $new_nickname );

			if ( $existing_user && $existing_user->ID !== $user->ID ) {
				$errors->add( 'nickname_exists', __( 'Nickname already exists. Please choose a different one.', 'text-domain' ) );
			}
		}
		//phpcs:enable
		return $errors;
	},
	10,
	3
);

add_action(
	'user_register',
	function ( $user_id ) {
		$user     = get_user_by( 'id', $user_id );
		$nickname = $user->user_nicename;

		$existing_user = get_user_by( 'slug', $nickname );

		if ( $existing_user && $existing_user->ID !== $user_id ) {
			$unique_id = '_' . time();
			wp_update_user(
				array(
					'ID'            => $user_id,
					'user_nicename' => $nickname . $unique_id,
				)
			);
		}
	},
	10,
	1
);

/**
 * Customizes the "From" name and email address in outgoing WordPress emails.
 *
 * @package SNKS_Custom_Email
 */

/**
 * Changes the "From" email address in outgoing emails.
 *
 * @return string The customized email address.
 */
function snks_wp_mail_from() {
	return get_option( 'admin_email' ); // Replace with your desired email address.
}
add_filter( 'wp_mail_from', 'snks_wp_mail_from' );

/**
 * Changes the "From" name in outgoing emails.
 *
 * @return string The customized "From" name.
 */
function snks_wp_mail_from_name() {
	return bloginfo( 'name' ); // Replace with your desired "From" name.
}
add_filter( 'wp_mail_from_name', 'snks_wp_mail_from_name' );
