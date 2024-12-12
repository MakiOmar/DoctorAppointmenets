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
add_action(
	'admin_init',
	function () {
        //phpcs:disable WordPress.Security.NonceVerification.Missing
		$_req = $_POST;
		if ( isset( $_req['notification-switch'] ) && 'yes' === $_req['notification-switch'] && isset( $_req['notif_content'] ) && ! empty( $_req['notif_content'] ) ) {
			if ( ! isset( $_req['notif_user'] ) ) {
				$_req['notif_user'] = 0;
			}
			if ( empty( $_req['title'] ) ) {
				$_req['title'] = 'جلسة';
			}
			$link = ! empty( $_req['link'] ) ? $_req['link'] : '';
			// Use snks_insert_notification function if need to insert notification.
			// Check if the class does not already exist and if the constant ANOTF_DIR is defined.
			if ( class_exists( 'FbCloudMessaging\AnonyengineFirebase' ) ) {
				// Use the correct namespace to initialize the class.
				$firebase = new \FbCloudMessaging\AnonyengineFirebase();

				// Ensure that $_req parameters are sanitized before using them.
				$title   = sanitize_text_field( $_req['title'] );
				$content = sanitize_text_field( $_req['notif_content'] );
				$user_id = absint( $_req['notif_user'] );
				// Call the notifier method.
				$firebase->trigger_notifier( $title, $content, $user_id, $link );
			}
		}
	}
);
