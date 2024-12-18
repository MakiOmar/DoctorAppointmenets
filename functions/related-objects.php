<?php
/**
 * Related objects
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Get related objects.
 *
 * @param string  $cache_key Cache key.
 * @param string  $select What to select.
 * @param string  $where Where clause.
 * @param int     $object_id Object ID.
 * @param int     $rel_id relation ID.
 * @param boolean $separate_table if data is stored in separate table.
 * @return mixed
 */
function anony_get_related_objects( $cache_key, $select, $where, $object_id, $rel_id, $separate_table = false ) {
	global $wpdb;

	$table_suffix = ( $separate_table ) ? $rel_id : 'default';

	$results = false;

	if ( false === $results ) {
        //phpcs:disable
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT {$select} 
				FROM 
					{$wpdb->prefix}jet_rel_{$table_suffix} t
				WHERE 
					t.rel_id = %d
				AND t.{$where} = %d",
				$rel_id,
				$object_id
			),
			ARRAY_A
		);
        //phpcs:enable
		if ( $results && ! empty( $results ) && ! is_null( $results ) ) {
			wp_cache_set( $cache_key, $results );
		}
	}

	$data = array();

	if ( $results && ! empty( $results ) && ! is_null( $results ) ) {
		foreach ( $results as $result ) {
				$obj                  = new stdClass();
				$obj->child_object_id = $result[ $select ];
				$data[]               = $obj;
		}
	}

	return $data;
}

/**
 * Query related children
 *
 * @param int     $object_id Object ID.
 * @param int     $rel_id relation ID.
 * @param boolean $separate_table if data is stored in separate table.
 * @return array
 */
function anony_query_related_children( $object_id, $rel_id, $separate_table = false ) {
	global $wpdb;

	$cache_key = 'anony_get_related_children_' . $object_id;

	return anony_get_related_objects( $cache_key, 'child_object_id', 'parent_object_id', $object_id, $rel_id, $separate_table );
}

/**
 * Query related parent
 *
 * @param int     $object_id Object ID.
 * @param int     $rel_id relation ID.
 * @param boolean $separate_table if data is stored in separate table.
 * @return array
 */
function anony_query_related_parent( $object_id, $rel_id, $separate_table = false ) {
	global $wpdb;

	$cache_key = 'anony_get_related_parent_' . $object_id;

	return anony_get_related_objects( $cache_key, 'parent_object_id', 'child_object_id', $object_id, $rel_id, $separate_table );
}
