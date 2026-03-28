<?php
/**
 * WooCommerce Analytics (Orders report): filter by admin manual booking meta (HPOS-safe).
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read manual-booking filter from the current request (WC Admin passes it as a query arg).
 *
 * @return string '', 'only', or 'exclude'.
 */
function snks_wc_analytics_manual_booking_request_mode() {
	if ( empty( $_GET['snks_manual_booking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$v = sanitize_text_field( wp_unslash( $_GET['snks_manual_booking'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( in_array( $v, array( 'only', 'exclude' ), true ) ) {
		return $v;
	}
	return '';
}

/**
 * SQL EXISTS subquery: order has admin_manual_booking meta set (matches plugin storage).
 *
 * @param string $stats_table Full prefixed wc_order_stats table name.
 * @return string SQL fragment without leading AND.
 */
function snks_wc_analytics_manual_booking_exists_fragment( $stats_table ) {
	global $wpdb;

	$key = 'admin_manual_booking';
	if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		$meta_table = $wpdb->prefix . 'wc_orders_meta';
		$id_col     = 'order_id';
	} else {
		$meta_table = $wpdb->postmeta;
		$id_col     = 'post_id';
	}

	return $wpdb->prepare(
		"EXISTS ( SELECT 1 FROM {$meta_table} snks_mbm WHERE snks_mbm.{$id_col} = {$stats_table}.order_id AND snks_mbm.meta_key = %s AND snks_mbm.meta_value IN ( '1', 'true', 'yes' ) )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$key
	);
}

/**
 * @param array $clauses Where clause fragments.
 * @return array
 */
function snks_wc_analytics_manual_booking_filter_where_clauses( $clauses ) {
	if ( ! is_array( $clauses ) ) {
		$clauses = array();
	}

	$mode = isset( $GLOBALS['snks_wc_analytics_manual_booking_mode'] ) ? $GLOBALS['snks_wc_analytics_manual_booking_mode'] : '';
	if ( ! in_array( $mode, array( 'only', 'exclude' ), true ) ) {
		return $clauses;
	}

	global $wpdb;
	$stats_table = $wpdb->prefix . 'wc_order_stats';
	$exists      = snks_wc_analytics_manual_booking_exists_fragment( $stats_table );

	if ( 'only' === $mode ) {
		$clauses[] = 'AND ' . $exists;
	} else {
		$clauses[] = 'AND NOT ' . $exists;
	}

	return $clauses;
}

/**
 * Merge filter into analytics query args so report caches vary by selection.
 *
 * @param array $args Query args.
 * @return array
 */
function snks_wc_analytics_manual_booking_merge_query_args( $args ) {
	if ( ! is_array( $args ) ) {
		$args = array();
	}
	$mode                                           = snks_wc_analytics_manual_booking_request_mode();
	$args['snks_manual_booking']                    = $mode;
	$GLOBALS['snks_wc_analytics_manual_booking_mode'] = $mode;
	return $args;
}

if ( ! function_exists( 'snks_wc_analytics_manual_booking_bootstrap' ) ) {
	/**
	 * Hooks WooCommerce Analytics integration.
	 */
	function snks_wc_analytics_manual_booking_bootstrap() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		add_filter( 'woocommerce_analytics_orders_stats_query_args', 'snks_wc_analytics_manual_booking_merge_query_args', 5 );
		add_filter( 'woocommerce_analytics_orders_query_args', 'snks_wc_analytics_manual_booking_merge_query_args', 5 );

		add_filter( 'woocommerce_analytics_clauses_where_orders_stats_total', 'snks_wc_analytics_manual_booking_filter_where_clauses' );
		add_filter( 'woocommerce_analytics_clauses_where_orders_stats_interval', 'snks_wc_analytics_manual_booking_filter_where_clauses' );
		add_filter( 'woocommerce_analytics_clauses_where_orders_subquery', 'snks_wc_analytics_manual_booking_filter_where_clauses' );

		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( empty( $_GET['page'] ) || 'wc-admin' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return;
				}
				if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'view_woocommerce_reports' ) ) {
					return;
				}
				if ( ! wp_script_is( 'wc-admin-app', 'registered' ) ) {
					return;
				}
				$path = SNKS_DIR . 'assets/js/wc-analytics-manual-booking-filter.js';
				if ( ! is_readable( $path ) ) {
					return;
				}
				$js = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( false === $js ) {
					return;
				}
				wp_add_inline_script( 'wc-admin-app', $js, 'before' );
			},
			20
		);
	}
	add_action( 'woocommerce_init', 'snks_wc_analytics_manual_booking_bootstrap' );
}

/**
 * Totals for admin manual bookings (all time, parent orders in order stats).
 *
 * @return array{count:int,net_total:float}
 */
function snks_get_manual_booking_report_totals() {
	global $wpdb;

	if ( ! function_exists( 'WC' ) ) {
		return array(
			'count'     => 0,
			'net_total' => 0.0,
		);
	}

	$stats_table = $wpdb->prefix . 'wc_order_stats';
	if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		$meta_table = $wpdb->prefix . 'wc_orders_meta';
		$join       = "INNER JOIN {$meta_table} snks_m ON snks_m.order_id = {$stats_table}.order_id AND snks_m.meta_key = 'admin_manual_booking' AND snks_m.meta_value IN ('1','true','yes')";
	} else {
		$meta_table = $wpdb->postmeta;
		$join       = "INNER JOIN {$meta_table} snks_m ON snks_m.post_id = {$stats_table}.order_id AND snks_m.meta_key = 'admin_manual_booking' AND snks_m.meta_value IN ('1','true','yes')";
	}

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row(
		"SELECT COUNT(*) AS order_count, COALESCE(SUM({$stats_table}.net_total), 0) AS net_total
		FROM {$stats_table}
		{$join}
		WHERE {$stats_table}.parent_id = 0"
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return array(
		'count'     => (int) ( $row->order_count ?? 0 ),
		'net_total' => (float) ( $row->net_total ?? 0 ),
	);
}
