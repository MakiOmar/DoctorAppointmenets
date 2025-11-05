<?php
/**
 * Session Messages AJAX Handlers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Send message with attachments to patient
 */
add_action( 'wp_ajax_send_session_message', 'snks_send_session_message' );

function snks_send_session_message() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'session_message_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a doctor
	if ( ! snks_is_doctor() ) {
		wp_send_json_error( 'Access denied. Only doctors can send messages.' );
	}
	
	$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
	$client_id  = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
	$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	
	if ( ! $session_id || ! $client_id ) {
		wp_send_json_error( 'Missing session or client ID.' );
	}
	
	if ( empty( $message ) && empty( $_FILES['attachments'] ) ) {
		wp_send_json_error( 'يرجى إدخال رسالة أو إرفاق ملف' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Get session details and verify ownership
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE ID = %d",
		$session_id
	) );
	
	if ( ! $session ) {
		wp_send_json_error( 'Session not found.' );
	}
	
	if ( $session->user_id != get_current_user_id() ) {
		wp_send_json_error( 'Access denied. You can only send messages for your own sessions.' );
	}
	
	// Handle file uploads
	$attachment_ids = array();
	if ( ! empty( $_FILES['attachments'] ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		
		$files = $_FILES['attachments'];
		$file_count = count( $files['name'] );
		
		for ( $i = 0; $i < $file_count; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}
			
			$file = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);
			
			$_FILES = array( 'upload' => $file );
			$attachment_id = media_handle_upload( 'upload', 0 );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment_ids[] = $attachment_id;
			}
		}
	}
	
	// Insert message into database
	$messages_table = $wpdb->prefix . 'snks_session_messages';
	$insert_result = $wpdb->insert(
		$messages_table,
		array(
			'session_id'     => $session_id,
			'sender_id'      => get_current_user_id(),
			'recipient_id'   => $client_id,
			'sender_type'    => 'therapist',
			'message'        => $message,
			'attachment_ids' => ! empty( $attachment_ids ) ? wp_json_encode( $attachment_ids ) : null,
			'is_read'        => 0,
			'created_at'     => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
	);
	
	if ( $insert_result === false ) {
		wp_send_json_error( 'Failed to save message.' );
	}

	// WhatsApp: notify patient that a new message was sent by therapist (template: prescription1)
	if ( function_exists( 'snks_send_whatsapp_template_message' ) && function_exists( 'snks_get_whatsapp_notification_settings' ) ) {
		$settings = snks_get_whatsapp_notification_settings();
		$patient_phone = function_exists( 'snks_get_user_whatsapp' ) ? snks_get_user_whatsapp( $client_id ) : '';
		$doctor_name = function_exists( 'snks_get_therapist_name' ) ? snks_get_therapist_name( get_current_user_id() ) : wp_get_current_user()->display_name;
		if ( ! empty( $patient_phone ) && ! empty( $settings['template_prescription1'] ) ) {
			snks_send_whatsapp_template_message(
				$patient_phone,
				$settings['template_prescription1'],
				array( 'doctor' => $doctor_name )
			);
		}
	}
	
	wp_send_json_success( array(
		'message' => 'تم إرسال الرسالة بنجاح',
	) );
}

/**
 * Get session messages for a patient
 */
add_action( 'wp_ajax_get_session_messages', 'snks_get_session_messages' );

function snks_get_session_messages() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'session_messages_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a patient
	if ( ! snks_is_patient() ) {
		wp_send_json_error( 'Access denied. Only patients can view messages.' );
	}
	
	$limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 5;
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	
	global $wpdb;
	$messages_table = $wpdb->prefix . 'snks_session_messages';
	$timetable = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	$current_user_id = get_current_user_id();
	
	// Get messages for sessions where current user is the patient
	$messages = $wpdb->get_results( $wpdb->prepare(
		"SELECT m.*, s.date_time, s.user_id as therapist_id
		 FROM {$messages_table} m
		 INNER JOIN {$timetable} s ON m.session_id = s.ID
		 WHERE s.client_id = %d
		 ORDER BY m.created_at DESC
		 LIMIT %d OFFSET %d",
		$current_user_id,
		$limit,
		$offset
	) );
	
	// Format messages with attachment details
	$formatted_messages = array();
	foreach ( $messages as $message ) {
		$therapist = get_userdata( $message->therapist_id );
		$attachments = array();
		
		if ( ! empty( $message->attachment_ids ) ) {
			$attachment_ids = json_decode( $message->attachment_ids, true );
			foreach ( $attachment_ids as $att_id ) {
				$attachments[] = array(
					'id'        => $att_id,
					'url'       => wp_get_attachment_url( $att_id ),
					'name'      => basename( get_attached_file( $att_id ) ),
					'type'      => get_post_mime_type( $att_id ),
					'is_image'  => wp_attachment_is_image( $att_id ),
					'thumbnail' => wp_get_attachment_image_url( $att_id, 'thumbnail' ),
				);
			}
		}
		
		$formatted_messages[] = array(
			'id'            => $message->id,
			'session_id'    => $message->session_id,
			'message'       => $message->message,
			'attachments'   => $attachments,
			'is_read'       => (bool) $message->is_read,
			'created_at'    => $message->created_at,
			'read_at'       => $message->read_at,
			'therapist_name' => $therapist ? $therapist->display_name : 'Unknown',
			'session_date'  => gmdate( 'Y-m-d', strtotime( $message->date_time ) ),
			'session_time'  => gmdate( 'h:i a', strtotime( $message->date_time ) ),
		);
	}
	
	// Get unread count
	$unread_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$messages_table} m
		 INNER JOIN {$timetable} s ON m.session_id = s.ID
		 WHERE s.client_id = %d AND m.is_read = 0",
		$current_user_id
	) );
	
	wp_send_json_success( array(
		'messages'      => $formatted_messages,
		'unread_count'  => intval( $unread_count ),
		'has_more'      => count( $messages ) === $limit,
	) );
}

/**
 * Mark message as read
 */
add_action( 'wp_ajax_mark_message_read', 'snks_mark_message_read' );

function snks_mark_message_read() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'session_messages_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	
	// Check if user is a patient
	if ( ! snks_is_patient() ) {
		wp_send_json_error( 'Access denied.' );
	}
	
	$message_id = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
	
	if ( ! $message_id ) {
		wp_send_json_error( 'Missing message ID.' );
	}
	
	global $wpdb;
	$messages_table = $wpdb->prefix . 'snks_session_messages';
	$timetable = $wpdb->prefix . TIMETABLE_TABLE_NAME;
	
	// Verify message belongs to current user
	$message = $wpdb->get_row( $wpdb->prepare(
		"SELECT m.* FROM {$messages_table} m
		 INNER JOIN {$timetable} s ON m.session_id = s.ID
		 WHERE m.id = %d AND s.client_id = %d",
		$message_id,
		get_current_user_id()
	) );
	
	if ( ! $message ) {
		wp_send_json_error( 'Message not found or access denied.' );
	}
	
	// Update message as read
	$wpdb->update(
		$messages_table,
		array(
			'is_read' => 1,
			'read_at' => current_time( 'mysql' ),
		),
		array( 'id' => $message_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);
	
	wp_send_json_success( array( 'message' => 'Message marked as read' ) );
}

