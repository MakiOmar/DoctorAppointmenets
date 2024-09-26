<?php
/**
 * Transactions table
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


/**
 * Create custom booking transactions table.
 */
function snks_create_transactions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;
	$collate    = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        timetable_id bigint(20) NOT NULL,
        transaction_type varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        transaction_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        processed_for_withdrawal TINYINT(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
