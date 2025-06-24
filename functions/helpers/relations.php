<?php
/**
 * Helper: Get doctors by organization and specialty.
 *
 * @package CustomHelpers
 */

defined( 'ABSPATH' ) || exit;
//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * Normalize doctor objects into WP_User format.
 *
 * @param array $children_objects Raw doctor entries.
 * @param bool  $is_relation_query True if using anony_query_related_children format.
 *
 * @return WP_User[] Array of WP_User objects.
 */
function snks_normalize_doctor_objects( $children_objects, $is_relation_query = false ) {
	if ( empty( $children_objects ) ) {
		return array();
	}

	if ( $is_relation_query ) {
		// Convert child_object_id to WP_User object.
		$normalized = array();
		foreach ( $children_objects as $row ) {
			if ( ! empty( $row['child_object_id'] ) ) {
				$user = get_user_by( 'ID', absint( $row['child_object_id'] ) );
				if ( $user ) {
					$normalized[] = $user;
				}
			}
		}
		return $normalized;
	}

	// Already WP_User[] format (from get_doctors_by_org_and_specialty).
	return $children_objects;
}


/**
 * Get doctors (users) who belong to both a specific organization and a specialty.
 *
 * @param int $organization_id The organization post ID.
 * @param int $specialty_term_id The specialization term ID.
 *
 * @return array Array of WP_User objects.
 */
function get_doctors_by_org_and_specialty( $organization_id, $specialty_term_id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'jet_rel_default';

	$doctor_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT child_object_id
			FROM $table_name
			WHERE (rel_id = %d AND parent_object_id = %d)
			   OR (rel_id = %d AND parent_object_id = %d)
			GROUP BY child_object_id
			HAVING COUNT(DISTINCT rel_id) = 2
			",
			23,
			$organization_id, // Organization to doctor.
			24,
			$specialty_term_id // Doctor to specialty.
		)
	);

	if ( empty( $doctor_ids ) ) {
		return array();
	}

	$user_query = new WP_User_Query(
		array(
			'include' => $doctor_ids,
		)
	);

	return $user_query->get_results();
}

/**
 * Get the organization post assigned to a user (doctor).
 *
 * @param int $user_id User ID.
 *
 * @return WP_Post|null The organization post object, or null if not found.
 */
function snks_get_user_organization( $user_id ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'jet_rel_default';
	$rel_id = 23; // Organization to Doctor relation ID.

	$parent_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT parent_object_id FROM $table WHERE rel_id = %d AND child_object_id = %d LIMIT 1",
			$rel_id,
			$user_id
		)
	);

	if ( ! $parent_id ) {
		return null;
	}

	$organization = get_post( absint( $parent_id ) );

	if ( $organization && 'organization' === $organization->post_type ) {
		return $organization;
	}

	return null;
}
