<?php
/**
 * Coupons tables
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create the custom coupons table linked to doctor.
 *
 * @return void
 */
function snks_create_custom_coupons_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_custom_coupons';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		code VARCHAR(100) NOT NULL,
		discount_type ENUM('fixed', 'percent') NOT NULL DEFAULT 'fixed',
		discount_value DECIMAL(10,2) NOT NULL,
		expires_at DATETIME DEFAULT NULL,
		usage_limit INT DEFAULT NULL,
		doctor_id BIGINT(20) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY code (code)
	) $collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}



/**
 * Create the custom coupon usage tracking table.
 *
 * @return void
 */
function snks_create_coupon_usages_table() {
	global $wpdb;

	$table_name   = $wpdb->prefix . 'snks_custom_coupon_usages';
	$coupon_table = $wpdb->prefix . 'snks_custom_coupons';
	$collate      = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) NOT NULL AUTO_INCREMENT,
		coupon_id BIGINT(20) NOT NULL,
		user_id BIGINT(20) NOT NULL,
		timetable_id BIGINT(20) NOT NULL,
		order_id BIGINT(20) DEFAULT NULL,
		used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		FOREIGN KEY (coupon_id) REFERENCES $coupon_table(id) ON DELETE CASCADE
	) $collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
