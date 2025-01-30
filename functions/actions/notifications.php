<?php
/**
 * Notifications
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();
/**
 * Insert notification
 *
 * @param string  $notification_message Message.
 * @param string  $link Link.
 * @param integer $user_id User ID.
 * @return mixed
 */
function snks_insert_notification( $notification_message, $link = '', $user_id = 0 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'anony_notifications';
	// Prepare the data to be inserted.
	$data = array(
		'user_id'    => $user_id,
		'message'    => $notification_message,
		'created_at' => current_time( 'mysql' ),
	);
	// Insert the notification into the database.
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	return $wpdb->insert( $table_name, $data );
}
