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
