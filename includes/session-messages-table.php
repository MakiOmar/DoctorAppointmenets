<?php
/**
 * Session Messages and Attachments Table
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create session messages table
 */
function snks_create_session_messages_table() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_session_messages';
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		session_id bigint(20) NOT NULL,
		sender_id bigint(20) NOT NULL,
		sender_type enum('therapist','patient') NOT NULL,
		message text,
		attachment_ids text,
		is_read tinyint(1) DEFAULT 0,
		created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		read_at timestamp NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY session_id (session_id),
		KEY sender_id (sender_id),
		KEY sender_type (sender_type),
		KEY is_read (is_read),
		KEY created_at (created_at)
	) {$charset_collate};";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Hook into WordPress initialization
add_action( 'after_setup_theme', 'snks_create_session_messages_table' );

