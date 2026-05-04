<?php
/**
 * Direct therapist–patient conversations tables.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create direct conversations tables (idempotent).
 *
 * @return void
 */
function snks_create_direct_conversations_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$conv            = $wpdb->prefix . 'snks_direct_conversations';
	$msg             = $wpdb->prefix . 'snks_direct_conversation_messages';

	$sql_conv = "CREATE TABLE {$conv} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		therapist_user_id bigint(20) unsigned NOT NULL,
		patient_user_id bigint(20) unsigned NOT NULL,
		public_token varchar(64) NOT NULL,
		guest_password_hash varchar(255) NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY therapist_patient (therapist_user_id, patient_user_id),
		UNIQUE KEY public_token (public_token),
		KEY patient_user_id (patient_user_id)
	) {$charset_collate};";

	$sql_msg = "CREATE TABLE {$msg} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		conversation_id bigint(20) unsigned NOT NULL,
		sender_user_id bigint(20) unsigned NOT NULL,
		sender_type varchar(20) NOT NULL,
		recipient_user_id bigint(20) unsigned NOT NULL,
		body longtext NULL,
		attachment_ids text NULL,
		is_read tinyint(1) NOT NULL DEFAULT 0,
		read_at datetime NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY conversation_id (conversation_id),
		KEY recipient_unread (recipient_user_id, is_read, created_at),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql_conv );
	dbDelta( $sql_msg );

	snks_maybe_add_ai_notification_link_column();
}

/**
 * Add link_url column to AI notifications if missing.
 *
 * @return void
 */
function snks_maybe_add_ai_notification_link_column() {
	global $wpdb;
	$table = $wpdb->prefix . 'snks_ai_notifications';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
	$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'link_url'" );
	if ( empty( $col ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table} ADD COLUMN link_url varchar(500) NULL AFTER message" );
	}
}

/**
 * Default options for direct conversations.
 *
 * @return void
 */
function snks_direct_conversations_register_defaults() {
	if ( false === get_option( 'snks_conversation_unread_summary_days', false ) ) {
		add_option( 'snks_conversation_unread_summary_days', 3 );
	}
	if ( false === get_option( 'snks_direct_conv_max_upload_bytes', false ) ) {
		add_option( 'snks_direct_conv_max_upload_bytes', 5242880 );
	}
	if ( false === get_option( 'snks_direct_conv_allowed_mimes', false ) ) {
		add_option( 'snks_direct_conv_allowed_mimes', 'image/jpeg,image/png,image/gif,application/pdf' );
	}
	if ( false === get_option( 'snks_direct_conv_digest_hour', false ) ) {
		add_option( 'snks_direct_conv_digest_hour', 20 );
	}
}

add_action( 'after_setup_theme', 'snks_create_direct_conversations_tables', 25 );
add_action( 'after_setup_theme', 'snks_direct_conversations_register_defaults', 26 );
