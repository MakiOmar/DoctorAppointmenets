<?php
/**
 * Time table
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

define( 'TIMETABLE_TABLE_NAME', 'snks_provider_timetable' );

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
        ID INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        client_id VARCHAR(255) NOT NULL,
        session_status VARCHAR(20) NOT NULL,
        day VARCHAR(20) NOT NULL,
        base_hour TIME NOT NULL,
        period INT(2) NOT NULL,
        date_time DATETIME NOT NULL,
        starts TIME NOT NULL,
        ends TIME NOT NULL,
		order_id INT(11) NOT NULL,
        PRIMARY KEY (ID)
    ) $collate";
	// Execute the SQL statement.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
