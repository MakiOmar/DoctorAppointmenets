<?php
/**
 * Direct conversations: persistence, notifications, digest, attachment policy.
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || exit;

/** Notification type: first message in a thread. */
define( 'SNKS_DIRECT_CONV_NOTIF_STARTED', 'direct_conversation_started' );

/** Notification type: daily digest of unread (in-app). */
define( 'SNKS_DIRECT_CONV_NOTIF_DIGEST', 'direct_conversation_daily_digest' );

/**
 * Table names.
 *
 * @return array{conv:string,msg:string}
 */
function snks_direct_conversations_tables() {
	global $wpdb;

	return array(
		'conv' => $wpdb->prefix . 'snks_direct_conversations',
		'msg'  => $wpdb->prefix . 'snks_direct_conversation_messages',
	);
}

/**
 * User has doctor or admin role.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_direct_conversations_is_doctor_user( $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user ) {
		return false;
	}
	return in_array( 'doctor', (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true );
}

/**
 * User has customer role (patient in WooCommerce sense).
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snks_direct_conversations_is_customer_user( $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user ) {
		return false;
	}
	return in_array( 'customer', (array) $user->roles, true );
}

/**
 * Get digest window in days.
 *
 * @return int
 */
function snks_direct_conversations_get_summary_days() {
	$n = (int) get_option( 'snks_conversation_unread_summary_days', 3 );
	return max( 1, min( 365, $n ) );
}

/**
 * Max upload bytes.
 *
 * @return int
 */
function snks_direct_conversations_get_max_upload_bytes() {
	$n = (int) get_option( 'snks_direct_conv_max_upload_bytes', 5242880 );
	return max( 1024, $n );
}

/**
 * Allowed MIME list (comma-separated).
 *
 * @return string[]
 */
function snks_direct_conversations_get_allowed_mimes() {
	$raw = (string) get_option( 'snks_direct_conv_allowed_mimes', 'image/jpeg,image/png,image/gif,application/pdf' );
	$parts = array_map( 'trim', explode( ',', $raw ) );
	return array_filter( array_unique( $parts ) );
}

/**
 * Validate attachment IDs against size and MIME allowlist.
 *
 * @param int[] $attachment_ids Attachment post IDs.
 * @return true|WP_Error
 */
function snks_direct_conversations_validate_attachments( $attachment_ids ) {
	if ( empty( $attachment_ids ) ) {
		return true;
	}
	$allowed = snks_direct_conversations_get_allowed_mimes();
	$max     = snks_direct_conversations_get_max_upload_bytes();

	foreach ( $attachment_ids as $aid ) {
		$aid = (int) $aid;
		if ( $aid <= 0 ) {
			return new WP_Error( 'invalid_attachment', 'Invalid attachment' );
		}
		$post = get_post( $aid );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new WP_Error( 'invalid_attachment', 'Invalid attachment' );
		}
		$mime = get_post_mime_type( $aid );
		if ( $allowed && ! in_array( $mime, $allowed, true ) ) {
			return new WP_Error( 'mime_not_allowed', 'File type not allowed' );
		}
		$path = get_attached_file( $aid );
		if ( $path && file_exists( $path ) && filesize( $path ) > $max ) {
			return new WP_Error( 'file_too_large', 'File exceeds maximum size' );
		}
	}
	return true;
}

/**
 * Get or create conversation between therapist and patient.
 *
 * @param int $therapist_user_id Therapist WP user ID.
 * @param int $patient_user_id   Patient WP user ID.
 * @return object|null Row object.
 */
function snks_direct_conversations_get_or_create( $therapist_user_id, $patient_user_id ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();
	$therapist_user_id = (int) $therapist_user_id;
	$patient_user_id   = (int) $patient_user_id;

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$t['conv']} WHERE therapist_user_id = %d AND patient_user_id = %d",
			$therapist_user_id,
			$patient_user_id
		)
	);
	if ( $row ) {
		return $row;
	}

	$token = strtolower( wp_generate_password( 32, false, false ) );
	$wpdb->insert(
		$t['conv'],
		array(
			'therapist_user_id' => $therapist_user_id,
			'patient_user_id'   => $patient_user_id,
			'public_token'      => $token,
		),
		array( '%d', '%d', '%s' )
	);
	$id = (int) $wpdb->insert_id;
	if ( ! $id ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['conv']} WHERE id = %d", $id ) );
}

/**
 * Build deep link for Jalsah AI SPA (patient).
 *
 * @param int $conversation_id Conversation ID.
 * @return string
 */
function snks_direct_conversations_patient_app_link( $conversation_id ) {
	$base = (string) get_option( 'snks_jalsah_ai_frontend_url', '' );
	if ( '' === $base ) {
		$base = trailingslashit( home_url( '/' ) );
	} else {
		$base = trailingslashit( $base );
	}
	$cid = (int) $conversation_id;
	if ( $cid > 0 ) {
		return $base . 'direct-conversations/' . $cid;
	}
	return $base . 'notifications';
}

/**
 * Notify other participant when first message is stored (in-app + optional WhatsApp).
 *
 * @param int $recipient_user_id Recipient.
 * @param int $conversation_id   Conversation ID.
 * @param int $sender_user_id     Sender user ID (for copy).
 * @return void
 */
function snks_direct_conversations_notify_conversation_started( $recipient_user_id, $conversation_id, $sender_user_id ) {
	$recipient_user_id = (int) $recipient_user_id;
	$conversation_id   = (int) $conversation_id;
	$sender_user_id    = (int) $sender_user_id;

	$sender = get_userdata( $sender_user_id );
	$name   = $sender ? $sender->display_name : '';

	$link = snks_direct_conversations_patient_app_link( $conversation_id );
	if ( snks_direct_conversations_is_doctor_user( $recipient_user_id ) ) {
		$link = add_query_arg(
			array(
				'snks_dc' => (int) $conversation_id,
			),
			home_url( '/' )
		);
	}

	$title   = __( 'New conversation', 'anony-shrinks' );
	$message = sprintf(
		/* translators: %s: sender display name */
		__( 'You have a new conversation from %s.', 'anony-shrinks' ),
		$name
	);

	if ( function_exists( 'snks_create_ai_notification' ) ) {
		snks_create_ai_notification( $recipient_user_id, SNKS_DIRECT_CONV_NOTIF_STARTED, $title, $message, $link );
	}

	$wa_enabled = (string) get_option( 'snks_ai_notifications_enabled', '1' ) === '1';
	$tpl        = (string) get_option( 'snks_whatsapp_template_direct_conversation', '' );
	if ( $wa_enabled && $tpl && function_exists( 'snks_get_user_whatsapp' ) && function_exists( 'snks_send_whatsapp_template_message' ) ) {
		$phone = snks_get_user_whatsapp( $recipient_user_id );
		if ( $phone ) {
			snks_send_whatsapp_template_message(
				$phone,
				$tpl,
				array(
					'name' => $name,
					'link' => $link,
				)
			);
		}
	}
}

/**
 * Insert a message and run first-message notification logic.
 *
 * @param int    $conversation_id Conversation ID.
 * @param int    $sender_user_id    Sender.
 * @param string $sender_type       therapist|patient.
 * @param string $body              Message body.
 * @param int[]  $attachment_ids    Attachment IDs (already uploaded).
 * @return int|WP_Error Message ID or error.
 */
function snks_direct_conversations_insert_message( $conversation_id, $sender_user_id, $sender_type, $body, $attachment_ids = array() ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$conversation_id = (int) $conversation_id;
	$sender_user_id    = (int) $sender_user_id;
	$sender_type       = 'therapist' === $sender_type ? 'therapist' : 'patient';

	$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['conv']} WHERE id = %d", $conversation_id ) );
	if ( ! $conv ) {
		return new WP_Error( 'not_found', 'Conversation not found' );
	}

	if ( (int) $conv->therapist_user_id !== $sender_user_id && (int) $conv->patient_user_id !== $sender_user_id ) {
		return new WP_Error( 'forbidden', 'Not a participant' );
	}
	if ( 'therapist' === $sender_type && (int) $conv->therapist_user_id !== $sender_user_id ) {
		return new WP_Error( 'forbidden', 'Invalid sender' );
	}
	if ( 'patient' === $sender_type && (int) $conv->patient_user_id !== $sender_user_id ) {
		return new WP_Error( 'forbidden', 'Invalid sender' );
	}

	$recipient = ( 'therapist' === $sender_type ) ? (int) $conv->patient_user_id : (int) $conv->therapist_user_id;

	$v = snks_direct_conversations_validate_attachments( $attachment_ids );
	if ( is_wp_error( $v ) ) {
		return $v;
	}

	$att_json = ! empty( $attachment_ids ) ? wp_json_encode( array_values( array_map( 'intval', $attachment_ids ) ) ) : null;

	$wpdb->insert(
		$t['msg'],
		array(
			'conversation_id'   => $conversation_id,
			'sender_user_id'    => $sender_user_id,
			'sender_type'       => $sender_type,
			'recipient_user_id' => $recipient,
			'body'              => wp_kses_post( $body ),
			'attachment_ids'    => $att_json,
			'is_read'           => 0,
		),
		array( '%d', '%d', '%s', '%d', '%s', '%s', '%d' )
	);
	$mid = (int) $wpdb->insert_id;
	if ( ! $mid ) {
		return new WP_Error( 'db', 'Could not save message' );
	}

	$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['msg']} WHERE conversation_id = %d", $conversation_id ) );
	// Conversation-start notification is patient-only and only when therapist sends first message.
	if ( 1 === $count && 'therapist' === $sender_type ) {
		snks_direct_conversations_notify_conversation_started( (int) $conv->patient_user_id, $conversation_id, $sender_user_id );
	}

	return $mid;
}

/**
 * Mark message read for recipient.
 *
 * @param int $message_id        Message ID.
 * @param int $recipient_user_id Must match recipient.
 * @return bool
 */
function snks_direct_conversations_mark_read( $message_id, $recipient_user_id ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$updated = $wpdb->update(
		$t['msg'],
		array(
			'is_read' => 1,
			'read_at' => current_time( 'mysql' ),
		),
		array(
			'id'                => (int) $message_id,
			'recipient_user_id' => (int) $recipient_user_id,
		),
		array( '%d', '%s' ),
		array( '%d', '%d' )
	);
	return (bool) $updated;
}

/**
 * Mark all unread messages in one conversation as read for recipient.
 *
 * @param int $conversation_id    Conversation ID.
 * @param int $recipient_user_id  Recipient user ID.
 * @return int Number of affected rows.
 */
function snks_direct_conversations_mark_conversation_read( $conversation_id, $recipient_user_id ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$t['msg']}
			SET is_read = 1, read_at = %s
			WHERE conversation_id = %d
				AND recipient_user_id = %d
				AND is_read = 0",
			current_time( 'mysql' ),
			(int) $conversation_id,
			(int) $recipient_user_id
		)
	);

	return max( 0, (int) $result );
}

/**
 * Unread count for user (all time, for UI badge).
 *
 * @param int $user_id User ID.
 * @return int
 */
function snks_direct_conversations_unread_count( $user_id ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$t['msg']} WHERE recipient_user_id = %d AND is_read = 0",
			(int) $user_id
		)
	);
}

/**
 * Unread messages in digest window for user.
 *
 * @param int $user_id User ID.
 * @return int Count of messages.
 */
function snks_direct_conversations_unread_in_digest_window( $user_id ) {
	global $wpdb;
	$t   = snks_direct_conversations_tables();
	$days = snks_direct_conversations_get_summary_days();

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$t['msg']} WHERE recipient_user_id = %d AND is_read = 0 AND created_at >= ( NOW() - INTERVAL %d DAY )",
			(int) $user_id,
			$days
		)
	);
}

/**
 * Unread messages older than configured threshold (days).
 *
 * @param int $user_id User ID.
 * @return int Count of unread old messages.
 */
function snks_direct_conversations_unread_older_than_threshold( $user_id ) {
	global $wpdb;
	$t    = snks_direct_conversations_tables();
	$days = snks_direct_conversations_get_summary_days();

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$t['msg']} WHERE recipient_user_id = %d AND is_read = 0 AND created_at <= ( NOW() - INTERVAL %d DAY )",
			(int) $user_id,
			$days
		)
	);
}

/**
 * Recent inbox rows for API (shape close to session-messages).
 *
 * @param int $user_id Recipient user ID.
 * @param int $limit   Limit.
 * @param int $offset  Offset.
 * @return array<int,object>
 */
function snks_direct_conversations_inbox_feed( $user_id, $limit = 5, $offset = 0 ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$locale = function_exists( 'snks_get_current_language' ) ? snks_get_current_language() : 'ar';

	$sql = $wpdb->prepare(
		"SELECT m.id, m.conversation_id, m.body AS message, m.attachment_ids, m.is_read, m.created_at, m.sender_user_id, m.sender_type,
			CASE
				WHEN m.sender_type = 'therapist' AND ta.id IS NOT NULL THEN
					CASE WHEN %s = 'ar' AND ta.name IS NOT NULL AND ta.name != '' THEN ta.name
						WHEN %s = 'en' AND ta.name_en IS NOT NULL AND ta.name_en != '' THEN ta.name_en
						ELSE ta.name END
				WHEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, '')) != ' ' THEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, ''))
				WHEN u.display_name != '' THEN u.display_name
				ELSE u.user_login
			END AS sender_name
		FROM {$t['msg']} m
		INNER JOIN {$t['conv']} c ON c.id = m.conversation_id
		LEFT JOIN {$wpdb->users} u ON u.ID = m.sender_user_id
		LEFT JOIN {$wpdb->prefix}therapist_applications ta ON ta.user_id = m.sender_user_id AND ta.status = 'approved'
		LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id = m.sender_user_id AND fn.meta_key = 'first_name'
		LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id = m.sender_user_id AND ln.meta_key = 'last_name'
		WHERE m.recipient_user_id = %d
		ORDER BY m.created_at DESC
		LIMIT %d OFFSET %d",
		$locale,
		$locale,
		(int) $user_id,
		(int) $limit,
		(int) $offset
	);

	return $wpdb->get_results( $sql );
}

/**
 * Latest incoming message per conversation for recipient.
 * Useful for notifications list to avoid duplicate rows per therapist.
 *
 * @param int    $user_id           Recipient user ID.
 * @param int    $limit             Limit.
 * @param int    $offset            Offset.
 * @param string $sender_type_filter Optional sender type filter.
 * @return array<int,object>
 */
function snks_direct_conversations_inbox_feed_latest_per_conversation( $user_id, $limit = 5, $offset = 0, $sender_type_filter = '' ) {
	global $wpdb;
	$t      = snks_direct_conversations_tables();
	$locale = function_exists( 'snks_get_current_language' ) ? snks_get_current_language() : 'ar';
	$where_sender = '';
	if ( 'therapist' === $sender_type_filter || 'patient' === $sender_type_filter ) {
		$where_sender = $wpdb->prepare( ' AND m1.sender_type = %s', $sender_type_filter );
	}

	$sql = $wpdb->prepare(
		"SELECT m.id, m.conversation_id, m.body AS message, m.attachment_ids, m.is_read, m.created_at, m.sender_user_id, m.sender_type,
			CASE
				WHEN m.sender_type = 'therapist' AND ta.id IS NOT NULL THEN
					CASE WHEN %s = 'ar' AND ta.name IS NOT NULL AND ta.name != '' THEN ta.name
						WHEN %s = 'en' AND ta.name_en IS NOT NULL AND ta.name_en != '' THEN ta.name_en
						ELSE ta.name END
				WHEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, '')) != ' ' THEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, ''))
				WHEN u.display_name != '' THEN u.display_name
				ELSE u.user_login
			END AS sender_name
		FROM {$t['msg']} m
		INNER JOIN (
			SELECT m1.conversation_id, MAX(m1.id) AS latest_id
			FROM {$t['msg']} m1
			WHERE m1.recipient_user_id = %d {$where_sender}
			GROUP BY m1.conversation_id
		) latest ON latest.latest_id = m.id
		LEFT JOIN {$wpdb->users} u ON u.ID = m.sender_user_id
		LEFT JOIN {$wpdb->prefix}therapist_applications ta ON ta.user_id = m.sender_user_id AND ta.status = 'approved'
		LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id = m.sender_user_id AND fn.meta_key = 'first_name'
		LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id = m.sender_user_id AND ln.meta_key = 'last_name'
		ORDER BY m.created_at DESC
		LIMIT %d OFFSET %d",
		$locale,
		$locale,
		(int) $user_id,
		(int) $limit,
		(int) $offset
	);

	return $wpdb->get_results( $sql );
}

/**
 * Format message row for JSON (attachments expanded).
 *
 * @param object $message DB row.
 * @return object
 */
function snks_direct_conversations_format_message_row( $message ) {
	if ( ! empty( $message->attachment_ids ) ) {
		$ids = json_decode( $message->attachment_ids, true );
		$message->attachments = array();
		if ( is_array( $ids ) ) {
			foreach ( $ids as $attachment_id ) {
				$attachment = get_post( (int) $attachment_id );
				if ( $attachment ) {
					$message->attachments[] = array(
						'id'   => (int) $attachment_id,
						'name' => $attachment->post_title ? $attachment->post_title : basename( (string) get_attached_file( (int) $attachment_id ) ),
						'url'  => wp_get_attachment_url( (int) $attachment_id ),
						'type' => get_post_mime_type( (int) $attachment_id ),
					);
				}
			}
		}
	} else {
		$message->attachments = array();
	}
	if ( isset( $message->body ) && ! isset( $message->message ) ) {
		$message->message = $message->body;
	}
	$message->conversation_id = (int) $message->conversation_id;
	return $message;
}

/**
 * Messages in thread for participant (paginated, chronological ASC for UI).
 *
 * @param int $conversation_id Conversation ID.
 * @param int $user_id         Participant (therapist or patient).
 * @param int $limit           Limit.
 * @param int $offset          Offset from end (optional) — use DESC then reverse in caller; simpler: ASC with high limit.
 * @return array
 */
function snks_direct_conversations_thread_messages( $conversation_id, $user_id, $limit = 100, $offset = 0 ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['conv']} WHERE id = %d", (int) $conversation_id ) );
	if ( ! $conv ) {
		return array();
	}
	if ( (int) $conv->therapist_user_id !== (int) $user_id && (int) $conv->patient_user_id !== (int) $user_id ) {
		return array();
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$t['msg']} WHERE conversation_id = %d ORDER BY created_at ASC, id ASC LIMIT %d OFFSET %d",
			(int) $conversation_id,
			(int) $limit,
			(int) $offset
		)
	);
	foreach ( $rows as $r ) {
		snks_direct_conversations_format_message_row( $r );
	}
	return $rows;
}

/**
 * Messages newer than a given id (for incremental / low-traffic polling).
 *
 * @param int $conversation_id Conversation ID.
 * @param int $user_id         Participant.
 * @param int $after_id        Return rows with id > this (0 = full thread up to limit).
 * @param int $limit           Max rows (capped).
 * @return array<int,object>
 */
function snks_direct_conversations_thread_messages_since( $conversation_id, $user_id, $after_id = 0, $limit = 50 ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['conv']} WHERE id = %d", (int) $conversation_id ) );
	if ( ! $conv ) {
		return array();
	}
	if ( (int) $conv->therapist_user_id !== (int) $user_id && (int) $conv->patient_user_id !== (int) $user_id ) {
		return array();
	}

	$after_id = absint( $after_id );
	$limit    = min( 100, max( 1, absint( $limit ) ) );

	if ( $after_id < 1 ) {
		return snks_direct_conversations_thread_messages( $conversation_id, $user_id, $limit, 0 );
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$t['msg']} WHERE conversation_id = %d AND id > %d ORDER BY id ASC LIMIT %d",
			(int) $conversation_id,
			$after_id,
			$limit
		)
	);
	foreach ( $rows as $r ) {
		snks_direct_conversations_format_message_row( $r );
	}
	return $rows;
}

/**
 * List conversations for therapist with last message preview.
 *
 * @param int $therapist_user_id Therapist ID.
 * @param int $limit             Max rows.
 * @return array
 */
function snks_direct_conversations_list_for_therapist( $therapist_user_id, $limit = 50 ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.*, m.body AS last_body, m.created_at AS last_at, m.sender_type AS last_sender_type,
				u.display_name AS patient_name
			FROM {$t['conv']} c
			LEFT JOIN (
				SELECT conversation_id, MAX(id) AS mid FROM {$t['msg']} GROUP BY conversation_id
			) latest ON latest.conversation_id = c.id
			LEFT JOIN {$t['msg']} m ON m.id = latest.mid
			LEFT JOIN {$wpdb->users} u ON u.ID = c.patient_user_id
			WHERE c.therapist_user_id = %d
			ORDER BY COALESCE(m.created_at, c.updated_at) DESC
			LIMIT %d",
			(int) $therapist_user_id,
			(int) $limit
		)
	);
}

/**
 * Recent patients who booked with therapist (for quick new-message start).
 *
 * @param int $therapist_user_id Therapist user ID.
 * @param int $limit             Max rows.
 * @return array<int,object>
 */
function snks_direct_conversations_recent_booked_patients_for_therapist( $therapist_user_id, $limit = 20 ) {
	global $wpdb;
	$t                = snks_direct_conversations_tables();
	$therapist_user_id = absint( $therapist_user_id );
	$limit            = min( 100, max( 1, absint( $limit ) ) );
	$tt               = $wpdb->prefix . 'snks_provider_timetable';
	$um               = $wpdb->usermeta;
	$users            = $wpdb->users;

	$sql = $wpdb->prepare(
		"SELECT p.client_id AS patient_user_id,
			MAX(p.date_time) AS last_booked_at,
			c.id AS conversation_id,
			CASE
				WHEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, '')) != ' ' THEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, ''))
				WHEN u.display_name != '' THEN u.display_name
				ELSE u.user_login
			END AS patient_name
		FROM {$tt} p
		LEFT JOIN {$users} u ON u.ID = p.client_id
		LEFT JOIN {$um} fn ON fn.user_id = p.client_id AND fn.meta_key = 'first_name'
		LEFT JOIN {$um} ln ON ln.user_id = p.client_id AND ln.meta_key = 'last_name'
		LEFT JOIN {$t['conv']} c ON c.therapist_user_id = p.user_id AND c.patient_user_id = p.client_id
		WHERE p.user_id = %d
			AND p.client_id > 0
			AND p.session_status != 'cancelled'
		GROUP BY p.client_id
		ORDER BY last_booked_at DESC
		LIMIT %d",
		$therapist_user_id,
		$limit
	);

	return $wpdb->get_results( $sql );
}

/**
 * Daily digest: one in-app notification per user per day if unread in window.
 *
 * @return void
 */
function snks_direct_conversations_run_daily_digest() {
	global $wpdb;
	if ( ! function_exists( 'snks_create_ai_notification' ) ) {
		return;
	}

	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	$t_msg               = snks_direct_conversations_tables()['msg'];
	$days                = snks_direct_conversations_get_summary_days();

	// Users with at least one unread message in window.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name controlled.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT recipient_user_id FROM {$t_msg} WHERE is_read = 0 AND created_at >= ( NOW() - INTERVAL %d DAY )",
			$days
		)
	);
	if ( empty( $user_ids ) ) {
		return;
	}

	foreach ( $user_ids as $uid ) {
		$uid = (int) $uid;
		if ( $uid <= 0 ) {
			continue;
		}
		$n_window = snks_direct_conversations_unread_in_digest_window( $uid );
		if ( $n_window <= 0 ) {
			continue;
		}

		$day_start = current_time( 'Y-m-d' ) . ' 00:00:00';
		$exists    = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$notifications_table} WHERE user_id = %d AND type = %s AND created_at >= %s LIMIT 1",
				$uid,
				SNKS_DIRECT_CONV_NOTIF_DIGEST,
				$day_start
			)
		);
		if ( $exists ) {
			continue;
		}

		$title   = __( 'Unread messages summary', 'anony-shrinks' );
		$message = sprintf(
			/* translators: %d: unread count, %d: days */
			_n(
				'You have %1$d unread message in the last %2$d days.',
				'You have %1$d unread messages in the last %2$d days.',
				$n_window,
				'anony-shrinks'
			),
			$n_window,
			$days
		);
		if ( snks_direct_conversations_is_doctor_user( $uid ) ) {
			$link = add_query_arg( array( 'snks_dc_digest' => '1' ), home_url( '/' ) );
		} else {
			$link = snks_direct_conversations_patient_app_link( 0 );
		}

		snks_create_ai_notification( $uid, SNKS_DIRECT_CONV_NOTIF_DIGEST, $title, $message, $link );

		// Optional WhatsApp daily digest for unread messages older than threshold.
		$old_unread = snks_direct_conversations_unread_older_than_threshold( $uid );
		if ( $old_unread <= 0 ) {
			continue;
		}
		$wa_enabled = (string) get_option( 'snks_ai_notifications_enabled', '1' ) === '1';
		$tpl        = (string) get_option( 'snks_whatsapp_template_direct_conversation_digest', '' );
		if ( ! $wa_enabled || '' === $tpl || ! function_exists( 'snks_get_user_whatsapp' ) || ! function_exists( 'snks_send_whatsapp_template_message' ) ) {
			continue;
		}
		$phone = snks_get_user_whatsapp( $uid );
		if ( ! $phone ) {
			continue;
		}
		snks_send_whatsapp_template_message(
			$phone,
			$tpl,
			array(
				'count' => (string) $old_unread,
				'days'  => (string) $days,
				'link'  => $link,
			)
		);
	}
}

/**
 * Next digest run timestamp (local site timezone).
 *
 * @return int Unix timestamp.
 */
function snks_direct_conversations_digest_next_timestamp() {
	$hour = (int) get_option( 'snks_direct_conv_digest_hour', 20 );
	$hour = max( 0, min( 23, $hour ) );
	$tz   = wp_timezone();
	$now  = new DateTime( 'now', $tz );
	$run  = new DateTime( 'today', $tz );
	$run->setTime( $hour, 0, 0 );
	if ( $run <= $now ) {
		$run->modify( '+1 day' );
	}
	return $run->getTimestamp();
}

/**
 * Schedule daily digest cron.
 *
 * @return void
 */
function snks_direct_conversations_schedule_digest_cron() {
	if ( wp_next_scheduled( 'snks_direct_conversations_daily_digest' ) ) {
		return;
	}
	wp_schedule_event( snks_direct_conversations_digest_next_timestamp(), 'daily', 'snks_direct_conversations_daily_digest' );
}

add_action( 'snks_direct_conversations_daily_digest', 'snks_direct_conversations_run_daily_digest' );
add_action( 'init', 'snks_direct_conversations_schedule_digest_cron', 30 );
