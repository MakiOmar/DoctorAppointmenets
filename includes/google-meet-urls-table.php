<?php
/**
 * Google Meet URL pool table.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create or update the Google Meet URLs table.
 *
 * @return void
 */
function snks_create_google_meet_urls_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_google_meet_urls';
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		meet_url VARCHAR(512) NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'available',
		assigned_timetable_id BIGINT(20) UNSIGNED DEFAULT NULL,
		assigned_rochtah_booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
		assigned_at DATETIME DEFAULT NULL,
		notes VARCHAR(255) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY meet_url (meet_url(191)),
		KEY status (status),
		KEY assigned_timetable_id (assigned_timetable_id),
		KEY assigned_rochtah_booking_id (assigned_rochtah_booking_id)
	) $collate";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

add_action(
	'admin_init',
	static function () {
		if ( function_exists( 'snks_create_google_meet_urls_table' ) ) {
			snks_create_google_meet_urls_table();
		}
	}
);
