<?php
/**
 * Rochetah Google Meet URL pool table.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create or update the rochtah meet URLs pool table.
 *
 * @return void
 */
function snks_create_rochtah_meet_urls_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_rochtah_meet_urls';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		meet_url VARCHAR(512) NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'available',
		assigned_booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
		assigned_at DATETIME DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY meet_url (meet_url(191)),
		KEY status (status),
		KEY assigned_booking_id (assigned_booking_id)
	) $collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'snks_rochtah_meet_urls_version', '1.0.0' );
}

add_action(
	'init',
	static function () {
		$current = get_option( 'snks_rochtah_meet_urls_version', '0.0.0' );
		if ( version_compare( $current, '1.0.0', '<' ) && function_exists( 'snks_create_rochtah_meet_urls_table' ) ) {
			snks_create_rochtah_meet_urls_table();
		}
	},
	5
);
