<?php
/**
 * Sessions actions Table
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
function snks_create_snks_sessions_actions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_sessions_actions';
	$collate    = $wpdb->get_charset_collate();

	// SQL statement to create the table.
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        ID INT(11) NOT NULL AUTO_INCREMENT,
        action_session_id INT(11) NOT NULL,
		case_id INT(11) NOT NULL,
        attendance VARCHAR(3) NOT NULL,
        PRIMARY KEY (ID)
    ) $collate";
	// Execute the SQL statement.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
