<?php
/**
 * AJAX for therapist Elementor hub (WordPress cookie auth).
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SNKS_DIR . 'functions/direct-conversations/snks-direct-conversations.php';

/**
 * Verify nonce and doctor.
 *
 * @return true|void Stops with wp_send_json_error on failure.
 */
function snks_direct_conv_ajax_guard() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'snks_direct_conv_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}
	if ( ! is_user_logged_in() || ! snks_is_doctor() ) {
		wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
	}
	return true;
}

add_action( 'wp_ajax_snks_direct_conv_badge', 'snks_ajax_snks_direct_conv_badge' );
function snks_ajax_snks_direct_conv_badge() {
	snks_direct_conv_ajax_guard();
	$uid = get_current_user_id();
	wp_send_json_success(
		array(
			'unread_count' => snks_direct_conversations_unread_count( $uid ),
		)
	);
}

add_action( 'wp_ajax_snks_direct_conv_recent', 'snks_ajax_snks_direct_conv_recent' );
function snks_ajax_snks_direct_conv_recent() {
	snks_direct_conv_ajax_guard();
	$uid   = get_current_user_id();
	$limit = isset( $_POST['limit'] ) ? min( 20, max( 1, absint( $_POST['limit'] ) ) ) : 10;
	$rows  = snks_direct_conversations_inbox_feed( $uid, $limit, 0 );
	foreach ( $rows as $m ) {
		snks_direct_conversations_format_message_row( $m );
	}
	wp_send_json_success( array( 'messages' => $rows ) );
}

add_action( 'wp_ajax_snks_direct_conv_list', 'snks_ajax_snks_direct_conv_list' );
function snks_ajax_snks_direct_conv_list() {
	snks_direct_conv_ajax_guard();
	$uid = get_current_user_id();
	$list = snks_direct_conversations_list_for_therapist( $uid, 100 );
	wp_send_json_success( array( 'conversations' => $list ) );
}

add_action( 'wp_ajax_snks_direct_conv_thread', 'snks_ajax_snks_direct_conv_thread' );
function snks_ajax_snks_direct_conv_thread() {
	snks_direct_conv_ajax_guard();
	$cid = isset( $_POST['conversation_id'] ) ? absint( $_POST['conversation_id'] ) : 0;
	if ( ! $cid ) {
		wp_send_json_error( array( 'message' => 'Missing conversation_id' ), 400 );
	}
	$list = snks_direct_conversations_thread_messages( $cid, get_current_user_id(), 200, 0 );
	wp_send_json_success( array( 'messages' => $list ) );
}

add_action( 'wp_ajax_snks_direct_conv_send', 'snks_ajax_snks_direct_conv_send' );
function snks_ajax_snks_direct_conv_send() {
	snks_direct_conv_ajax_guard();
	$cid = isset( $_POST['conversation_id'] ) ? absint( $_POST['conversation_id'] ) : 0;
	$pid = isset( $_POST['patient_user_id'] ) ? absint( $_POST['patient_user_id'] ) : 0;
	$body = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '';
	$att_json = isset( $_POST['attachment_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['attachment_ids'] ) ) : '';
	$att = array();
	if ( $att_json !== '' ) {
		$dec = json_decode( $att_json, true );
		if ( is_array( $dec ) ) {
			$att = array_map( 'absint', $dec );
		}
	}
	$uid = get_current_user_id();

	if ( $cid ) {
		$res = snks_direct_conversations_insert_message( $cid, $uid, 'therapist', $body, $att );
	} elseif ( $pid ) {
		$conv = snks_direct_conversations_get_or_create( $uid, $pid );
		if ( ! $conv ) {
			wp_send_json_error( array( 'message' => 'Could not open conversation' ), 500 );
		}
		$res = snks_direct_conversations_insert_message( (int) $conv->id, $uid, 'therapist', $body, $att );
	} else {
		wp_send_json_error( array( 'message' => 'conversation_id or patient_user_id required' ), 400 );
	}

	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
	}
	wp_send_json_success( array( 'message_id' => (int) $res ) );
}

add_action( 'wp_ajax_snks_direct_conv_mark_read', 'snks_ajax_snks_direct_conv_mark_read' );
function snks_ajax_snks_direct_conv_mark_read() {
	snks_direct_conv_ajax_guard();
	$mid = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
	if ( ! $mid ) {
		wp_send_json_error( array( 'message' => 'Missing message_id' ), 400 );
	}
	snks_direct_conversations_mark_read( $mid, get_current_user_id() );
	wp_send_json_success( array( 'ok' => true ) );
}

add_action( 'wp_ajax_snks_direct_conv_upload', 'snks_ajax_snks_direct_conv_upload' );
function snks_ajax_snks_direct_conv_upload() {
	snks_direct_conv_ajax_guard();
	if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => 'No file' ), 400 );
	}
	$file = $_FILES['file'];
	if ( ! empty( $file['error'] ) ) {
		wp_send_json_error( array( 'message' => 'Upload error' ), 400 );
	}
	$max = snks_direct_conversations_get_max_upload_bytes();
	if ( ! empty( $file['size'] ) && (int) $file['size'] > $max ) {
		wp_send_json_error( array( 'message' => 'File too large' ), 400 );
	}
	$allowed = snks_direct_conversations_get_allowed_mimes();
	$check   = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	$mime    = isset( $check['type'] ) ? $check['type'] : '';
	if ( $allowed && ( ! $mime || ! in_array( $mime, $allowed, true ) ) ) {
		wp_send_json_error( array( 'message' => 'File type not allowed' ), 400 );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$_FILES['snks_dc_upload'] = $file;
	$aid = media_handle_upload( 'snks_dc_upload', 0 );
	if ( is_wp_error( $aid ) ) {
		wp_send_json_error( array( 'message' => $aid->get_error_message() ), 400 );
	}
	wp_send_json_success(
		array(
			'id'   => (int) $aid,
			'name' => get_the_title( $aid ),
			'url'  => wp_get_attachment_url( $aid ),
		)
	);
}
