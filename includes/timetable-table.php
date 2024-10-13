<?php
/**
 * Time table
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create the custom table.
 *
 * @return void
 */
function snks_create_timetable_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	$collate    = $wpdb->get_charset_collate();

	// SQL statement to create the table.
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        ID BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) DEFAULT 0,
        client_id BIGINT(20) DEFAULT 0,
        session_status VARCHAR(20) NOT NULL,
        day VARCHAR(20) NOT NULL,
        base_hour TIME NOT NULL,
        period INT(2) NOT NULL,
        date_time DATETIME NOT NULL,
        starts TIME NOT NULL,
        ends TIME NOT NULL,
        clinic VARCHAR(255) NOT NULL,
        attendance_type VARCHAR(255) NOT NULL,
        order_id BIGINT(20) DEFAULT 0,
        edit_order_id BIGINT(20) DEFAULT 0,
        notification_24hr_sent TINYINT(1) DEFAULT 0,
        notification_1hr_sent TINYINT(1) DEFAULT 0,
        PRIMARY KEY (ID)
    ) $collate";

	// Execute the SQL statement.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
