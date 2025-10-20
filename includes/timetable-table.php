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
        settings VARCHAR(255) NOT NULL,
        edit_order_id BIGINT(20) DEFAULT 0,
        notification_24hr_sent TINYINT(1) DEFAULT 0,
        notification_1hr_sent TINYINT(1) DEFAULT 0,
        PRIMARY KEY (ID)
    ) $collate";

	// Execute the SQL statement.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

add_action(
	'admin_init',
	function () {
		global $wpdb;
		$table_name  = $wpdb->prefix . TIMETABLE_TABLE_NAME;
		$column_name = 'settings';
        //phpcs:disable
		// Check if the column already exists.
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s',
				$table_name,
				$column_name,
				$wpdb->dbname // Gets the current database name.
			)
		);

		if ( empty( $column_exists ) ) {
			// If the column doesn't exist, add it.
			$wpdb->query(
				"ALTER TABLE $table_name ADD COLUMN settings VARCHAR(255) NOT NULL"
			);
		}
        //phpcs:enable
	}
);

/**
 * Add WhatsApp notification tracking columns for AI sessions
 */
function snks_add_whatsapp_notification_columns() {
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	$columns_to_add = array(
		'whatsapp_new_session_sent' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_doctor_notified' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_rosheta_activated' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_rosheta_booked' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_doctor_reminded' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_patient_now_sent' => 'TINYINT(1) DEFAULT 0',
	);
	
	//phpcs:disable
	foreach ( $columns_to_add as $column_name => $column_definition ) {
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s',
				$table_name,
				$column_name,
				$wpdb->dbname
			)
		);
		
		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $table_name ADD COLUMN $column_name $column_definition"
			);
		}
	}
	//phpcs:enable
}
add_action( 'admin_init', 'snks_add_whatsapp_notification_columns' );
