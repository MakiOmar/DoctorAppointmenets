<?php
/**
 * Package subscriptions table.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create the package subscriptions table.
 *
 * @return void
 */
function snks_create_package_subscriptions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_package_subscriptions';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		patient_id BIGINT(20) UNSIGNED NOT NULL,
		therapist_id BIGINT(20) UNSIGNED NOT NULL,
		package_type TINYINT(3) UNSIGNED NOT NULL,
		package_price DECIMAL(12,2) NOT NULL DEFAULT 0,
		session_price DECIMAL(12,2) NOT NULL DEFAULT 0,
		payment_method VARCHAR(64) NOT NULL DEFAULT '',
		remaining_sessions INT(11) NOT NULL DEFAULT 0,
		status VARCHAR(20) NOT NULL DEFAULT 'active',
		subscribed_at DATETIME NOT NULL,
		cancelled_at DATETIME NULL DEFAULT NULL,
		created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		KEY patient_therapist (patient_id, therapist_id),
		KEY status_idx (status),
		KEY subscribed_at_idx (subscribed_at)
	) $collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
