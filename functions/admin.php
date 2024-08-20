<?php
/**
 * Admin edits
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

// Add custom column to the users table.
add_filter(
	'manage_users_columns',
	function ( $columns ) {
		$columns['family_member'] = 'Patient/Family';
		return $columns;
	}
);

// Display meta value for custom column.
add_action(
	'manage_users_custom_column',
	function ( $value, $column_name, $user_id ) {
		if ( 'family_member' === $column_name ) {
			$user = get_user_by( 'id', $user_id );
			if ( in_array( 'family', $user->roles, true ) ) {
				$patient_id = get_user_meta( $user_id, 'patient-id', true );
				if ( ! empty( $patient_id ) ) {
					$value = '<a href="' . esc_url( add_query_arg( 'user_id', $patient_id, admin_url( '/user-edit.php' ) ) ) . '">Patient(' . $patient_id . ')</a>';
				}
			} elseif ( in_array( 'customer', $user->roles, true ) ) {
				$family_id = get_user_meta( $user_id, 'family-id', true );
				if ( ! empty( $family_id ) ) {
					$value = '<a href="' . esc_url( add_query_arg( 'user_id', $family_id, admin_url( '/user-edit.php' ) ) ) . '">Family(' . $family_id . ')</a>';
				}
			}
		}
		return $value;
	},
	10,
	3
);

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
