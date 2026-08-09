<?php
/**
 * WooCommerce HPOS-safe order helpers.
 *
 * Prefer wc_get_orders() / WC_Order::get_meta() over raw shop_order/postmeta SQL.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Whether custom order tables (HPOS) are in use as the authoritative store.
 *
 * @return bool
 */
function snks_wc_hpos_enabled() {
	return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

/**
 * Truthy meta values used for AI order flags.
 *
 * @return string[]
 */
function snks_wc_ai_order_truthy_values() {
	if ( function_exists( 'snks_ai_orders_truthy_values' ) ) {
		return snks_ai_orders_truthy_values();
	}
	return array( '1', 'true', 'yes' );
}

/**
 * Meta query fragment that matches AI (Jalsah) orders.
 *
 * @return array
 */
function snks_wc_ai_orders_meta_query() {
	$truthy = snks_wc_ai_order_truthy_values();

	return array(
		'relation' => 'OR',
		array(
			'key'     => 'from_jalsah_ai',
			'value'   => $truthy,
			'compare' => 'IN',
		),
		array(
			'key'     => 'is_ai_session',
			'value'   => $truthy,
			'compare' => 'IN',
		),
	);
}

/**
 * Normalize a WC status slug (strip leading wc- if present).
 *
 * @param string|array $status Status or list of statuses.
 * @return string|array
 */
function snks_wc_normalize_order_status( $status ) {
	if ( is_array( $status ) ) {
		return array_map( 'snks_wc_normalize_order_status', $status );
	}

	$status = (string) $status;
	if ( 0 === strpos( $status, 'wc-' ) ) {
		$status = substr( $status, 3 );
	}

	return $status;
}

/**
 * Fetch AI orders via the CRUD API (HPOS-safe).
 *
 * @param array $args Optional wc_get_orders args (merged with AI meta_query).
 * @return WC_Order[]|int[]|stdClass
 */
function snks_wc_get_ai_orders( $args = array() ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$caller_meta = isset( $args['meta_query'] ) ? $args['meta_query'] : null;
	unset( $args['meta_query'] );

	$defaults = array(
		'limit'   => 100,
		'orderby' => 'date',
		'order'   => 'DESC',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( isset( $args['status'] ) ) {
		$args['status'] = snks_wc_normalize_order_status( $args['status'] );
	}

	$ai_meta = snks_wc_ai_orders_meta_query();
	if ( $caller_meta ) {
		$args['meta_query'] = array(
			'relation' => 'AND',
			$ai_meta,
			$caller_meta,
		);
	} else {
		$args['meta_query'] = $ai_meta;
	}

	return wc_get_orders( $args );
}

/**
 * Count AI orders (HPOS-safe), optionally filtered by status.
 *
 * @param string|array|null $status Optional status slug(s).
 * @return int
 */
function snks_wc_count_ai_orders( $status = null ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return 0;
	}

	$args = array(
		'limit'    => 1,
		'paginate' => true,
		'return'   => 'ids',
	);

	if ( null !== $status && '' !== $status ) {
		$args['status'] = $status;
	}

	$result = snks_wc_get_ai_orders( $args );

	if ( is_object( $result ) && isset( $result->total ) ) {
		return (int) $result->total;
	}

	return is_array( $result ) ? count( $result ) : 0;
}

/**
 * Return AI order IDs (HPOS-safe).
 *
 * @param array $args Optional args (status, limit, etc.).
 * @return int[]
 */
function snks_wc_get_ai_order_ids( $args = array() ) {
	$args['return'] = 'ids';

	if ( ! isset( $args['limit'] ) ) {
		$args['limit'] = -1;
	}

	$ids = snks_wc_get_ai_orders( $args );

	if ( is_object( $ids ) && isset( $ids->orders ) ) {
		$ids = $ids->orders;
	}

	return array_map( 'absint', is_array( $ids ) ? $ids : array() );
}

/**
 * Read order meta via WC_Order (HPOS-safe). Falls back to get_post_meta only if WC is unavailable.
 *
 * @param int    $order_id Order ID.
 * @param string $key      Meta key.
 * @param bool   $single   Whether to return a single value.
 * @return mixed
 */
function snks_wc_get_order_meta( $order_id, $key, $single = true ) {
	$order_id = absint( $order_id );
	if ( ! $order_id ) {
		return $single ? '' : array();
	}

	if ( function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			return $order->get_meta( $key, $single );
		}
	}

	return get_post_meta( $order_id, $key, $single );
}

/**
 * Admin list URL for AI orders (HPOS or CPT).
 *
 * @return string
 */
function snks_wc_ai_orders_admin_url() {
	if ( snks_wc_hpos_enabled() ) {
		return admin_url( 'admin.php?page=wc-orders&snks_ai_order_filter=ai' );
	}

	return admin_url( 'edit.php?post_type=shop_order&snks_ai_order_filter=ai' );
}
