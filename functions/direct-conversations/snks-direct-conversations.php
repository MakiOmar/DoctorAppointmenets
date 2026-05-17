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
 * WhatsApp template option: therapist (e.g. chat_th), no named body variables.
 *
 * @return string
 */
function snks_dc_wa_tpl_therapist() {
	return (string) get_option( 'snks_whatsapp_template_dc_therapist', '' );
}

/**
 * WhatsApp template: patient receives first therapist message (e.g. chat_pt1); body {{chat_link}} + {{enter}}.
 *
 * @return string
 */
function snks_dc_wa_tpl_patient_first() {
	return (string) get_option( 'snks_whatsapp_template_dc_patient_first', '' );
}

/**
 * WhatsApp template: patient digest (e.g. chat_pt2); body {{chat_link}} only.
 *
 * @return string
 */
function snks_dc_wa_tpl_patient_digest() {
	return (string) get_option( 'snks_whatsapp_template_dc_patient_digest', '' );
}

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
 * Whether a string is mostly digits (phone / WhatsApp login).
 *
 * @param string $value Raw label.
 * @return bool
 */
function snks_direct_conversations_string_looks_like_phone( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return false;
	}
	$digits = preg_replace( '/\D+/', '', $value );
	return strlen( $digits ) >= 8 && strlen( $digits ) >= ( strlen( $value ) * 0.7 );
}

/**
 * Display name for a patient in therapist hub / lists (never raw phone as label).
 *
 * @param int $user_id Patient user ID.
 * @return string Name, or patient-{id} when no real name is stored.
 */
function snks_direct_conversations_patient_display_name( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return 'patient-0';
	}

	$candidates = array(
		trim( (string) get_user_meta( $user_id, 'first_name', true ) . ' ' . (string) get_user_meta( $user_id, 'last_name', true ) ),
		trim( (string) get_user_meta( $user_id, 'billing_first_name', true ) . ' ' . (string) get_user_meta( $user_id, 'billing_last_name', true ) ),
	);

	foreach ( $candidates as $full ) {
		if ( '' !== $full && ! snks_direct_conversations_string_looks_like_phone( $full ) ) {
			return $full;
		}
	}

	$user = get_userdata( $user_id );
	if ( $user ) {
		$display = trim( (string) $user->display_name );
		if ( '' !== $display && ! snks_direct_conversations_string_looks_like_phone( $display ) ) {
			return $display;
		}
		$login = trim( (string) $user->user_login );
		if ( '' !== $login && ! snks_direct_conversations_string_looks_like_phone( $login ) ) {
			return $login;
		}
	}

	return 'patient-' . $user_id;
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
 * Whether daily digest debug logging is enabled (admin option).
 *
 * @return bool
 */
function snks_dc_digest_debug_enabled() {
	return (string) get_option( 'snks_dc_digest_debug_enabled', '0' ) === '1';
}

/**
 * Append one digest run report to the debug log option (newest first).
 *
 * @param array<string,mixed> $report Run report from snks_direct_conversations_run_daily_digest().
 * @return void
 */
function snks_dc_digest_debug_store_run( array $report ) {
	$log = get_option( 'snks_dc_digest_debug_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, $report );
	$log = array_slice( $log, 0, 15 );
	update_option( 'snks_dc_digest_debug_log', $log, false );
}

/**
 * Write digest debug line to PHP error_log when WP_DEBUG_LOG is on.
 *
 * @param string              $message Short message.
 * @param array<string,mixed> $context Optional context.
 * @return void
 */
function snks_dc_digest_debug_error_log( $message, array $context = array() ) {
	if ( ! ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
		return;
	}
	$line = '[SNKS DC digest] ' . $message;
	if ( ! empty( $context ) ) {
		$line .= ' ' . wp_json_encode( $context );
	}
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $line );
}

/**
 * Build digest decision data for one user (no side effects).
 *
 * @param int $user_id User ID.
 * @return array<string,mixed>
 */
function snks_direct_conversations_digest_diagnose_user( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;
	$days    = snks_direct_conversations_get_summary_days();
	$out     = array(
		'user_id'              => $user_id,
		'is_therapist'         => snks_direct_conversations_is_doctor_user( $user_id ),
		'unread_total'         => snks_direct_conversations_unread_count( $user_id ),
		'unread_in_window'     => snks_direct_conversations_unread_in_digest_window( $user_id ),
		'unread_older_than_n'  => snks_direct_conversations_unread_older_than_threshold( $user_id ),
		'summary_days'         => $days,
		'digest_sent_today'    => false,
		'digest_notification_id' => null,
		'would_send_in_app'    => false,
		'would_send_whatsapp'  => false,
		'blockers'             => array(),
	);

	if ( $user_id <= 0 ) {
		$out['blockers'][] = 'invalid_user_id';
		return $out;
	}

	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	$day_start           = current_time( 'Y-m-d' ) . ' 00:00:00';
	$exists              = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$notifications_table} WHERE user_id = %d AND type = %s AND created_at >= %s LIMIT 1",
			$user_id,
			SNKS_DIRECT_CONV_NOTIF_DIGEST,
			$day_start
		)
	);
	if ( $exists ) {
		$out['digest_sent_today']         = true;
		$out['digest_notification_id']    = (int) $exists;
		$out['blockers'][]                = 'digest_already_sent_today';
	} elseif ( $out['unread_in_window'] <= 0 ) {
		$out['blockers'][] = 'no_unread_in_summary_window';
	} else {
		$out['would_send_in_app'] = true;
	}

	if ( $out['unread_older_than_n'] <= 0 ) {
		$out['blockers'][] = 'no_unread_older_than_threshold_for_whatsapp';
	} elseif ( (string) get_option( 'snks_ai_notifications_enabled', '1' ) !== '1' ) {
		$out['blockers'][] = 'ai_notifications_disabled';
	} elseif ( ! function_exists( 'snks_get_user_whatsapp' ) || ! function_exists( 'snks_send_whatsapp_template_message' ) ) {
		$out['blockers'][] = 'whatsapp_helpers_missing';
	} elseif ( ! snks_get_user_whatsapp( $user_id ) ) {
		$out['blockers'][] = 'no_whatsapp_phone';
	} elseif ( snks_direct_conversations_is_doctor_user( $user_id ) ) {
		if ( '' === snks_dc_wa_tpl_therapist() ) {
			$out['blockers'][] = 'whatsapp_template_dc_therapist_empty';
		} else {
			$out['would_send_whatsapp'] = true;
		}
	} elseif ( '' === snks_dc_wa_tpl_patient_digest() ) {
		$out['blockers'][] = 'whatsapp_template_dc_patient_digest_empty';
	} elseif ( ! snks_direct_conversations_patient_digest_whatsapp_params( $user_id ) ) {
		$out['blockers'][] = 'no_qualifying_conversation_for_chat_link';
	} else {
		$out['would_send_whatsapp'] = true;
	}

	return $out;
}

/**
 * Max upload bytes. 0 = no size limit (WordPress / server limits still apply).
 *
 * @return int
 */
function snks_direct_conversations_get_max_upload_bytes() {
	return max( 0, (int) get_option( 'snks_direct_conv_max_upload_bytes', 0 ) );
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
		if ( $max > 0 ) {
			$path = get_attached_file( $aid );
			if ( $path && file_exists( $path ) && filesize( $path ) > $max ) {
				return new WP_Error( 'file_too_large', 'File exceeds maximum size' );
			}
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
function snks_direct_conversations_patient_app_base_url() {
	if ( function_exists( 'snks_ai_get_primary_frontend_url' ) ) {
		$base = snks_ai_get_primary_frontend_url();
		if ( ! empty( $base ) ) {
			return trailingslashit( $base );
		}
	}
	// Legacy override (direct conversations screen) if general Frontend URLs is unset.
	$legacy = (string) get_option( 'snks_jalsah_ai_frontend_url', '' );
	if ( '' !== $legacy ) {
		return trailingslashit( $legacy );
	}
	return trailingslashit( home_url( '/' ) );
}

/**
 * Build deep link for Jalsah AI SPA (patient).
 *
 * @param int $conversation_id Conversation ID.
 * @return string
 */
function snks_direct_conversations_patient_app_link( $conversation_id ) {
	$base = snks_direct_conversations_patient_app_base_url();
	$cid  = (int) $conversation_id;
	if ( $cid > 0 ) {
		return $base . 'direct-conversations/' . $cid;
	}
	return $base . 'notifications';
}

/**
 * Password-protected guest entry URL (public_token only; no conversation id in path).
 *
 * @param string $public_token Conversation public token.
 * @return string
 */
function snks_direct_conversations_guest_entry_link( $public_token ) {
	$token = sanitize_text_field( (string) $public_token );
	if ( '' === $token ) {
		return snks_direct_conversations_patient_app_link( 0 );
	}
	return snks_direct_conversations_patient_app_base_url() . 'dc-access/' . rawurlencode( $token );
}

/**
 * Load conversation by public token.
 *
 * @param string $public_token Token from guest URL.
 * @return object|null
 */
function snks_direct_conversations_get_by_public_token( $public_token ) {
	global $wpdb;
	$t     = snks_direct_conversations_tables();
	$token = sanitize_text_field( (string) $public_token );
	if ( '' === $token ) {
		return null;
	}
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$t['conv']} WHERE public_token = %s LIMIT 1",
			$token
		)
	);
}

/**
 * Generate a new numeric guest access password and store its hash on the conversation.
 *
 * @param int $conversation_id Conversation ID.
 * @return string Plain password for WhatsApp {{enter}} (empty on failure).
 */
function snks_direct_conversations_rotate_guest_password( $conversation_id ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();
	$conversation_id = (int) $conversation_id;
	if ( $conversation_id <= 0 ) {
		return '';
	}

	$plain = (string) wp_rand( 100000, 999999 );
	$hash  = wp_hash_password( $plain );

	$updated = $wpdb->update(
		$t['conv'],
		array( 'guest_password_hash' => $hash ),
		array( 'id' => $conversation_id ),
		array( '%s' ),
		array( '%d' )
	);

	return $updated !== false ? $plain : '';
}

/**
 * Verify guest password for a conversation public token.
 *
 * @param string $public_token Public token from URL.
 * @param string $password     Password from user / WhatsApp.
 * @return object|null Conversation row when valid; null otherwise.
 */
function snks_direct_conversations_verify_guest_password( $public_token, $password ) {
	$conv = snks_direct_conversations_get_by_public_token( $public_token );
	if ( ! $conv || empty( $conv->guest_password_hash ) ) {
		return null;
	}
	$password = (string) $password;
	if ( '' === $password ) {
		return null;
	}
	if ( ! wp_check_password( $password, $conv->guest_password_hash ) ) {
		return null;
	}
	return $conv;
}

/**
 * Conversation used for patient digest WhatsApp (oldest unread past digest threshold).
 *
 * @param int $patient_user_id Patient user ID.
 * @return object|null
 */
function snks_direct_conversations_digest_wa_conversation_for_patient( $patient_user_id ) {
	global $wpdb;
	$t    = snks_direct_conversations_tables();
	$days = snks_direct_conversations_get_summary_days();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT c.* FROM {$t['conv']} c
			INNER JOIN {$t['msg']} m ON m.conversation_id = c.id
			WHERE c.patient_user_id = %d
				AND m.recipient_user_id = %d
				AND m.is_read = 0
				AND m.created_at <= ( NOW() - INTERVAL %d DAY )
			ORDER BY m.created_at ASC
			LIMIT 1",
			(int) $patient_user_id,
			(int) $patient_user_id,
			$days
		)
	);
}

/**
 * Build chat_pt1 WhatsApp body parameters: dc-access link + access password.
 *
 * @param int $conversation_id Conversation ID.
 * @return array{chat_link:string,enter:string}|null Null when conversation or token is missing.
 */
function snks_direct_conversations_patient_first_whatsapp_params( $conversation_id ) {
	global $wpdb;
	$t                 = snks_direct_conversations_tables();
	$conversation_id   = (int) $conversation_id;
	if ( $conversation_id <= 0 ) {
		return null;
	}

	$conv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['conv']} WHERE id = %d", $conversation_id ) );
	if ( ! $conv || empty( $conv->public_token ) ) {
		return null;
	}

	$enter = snks_direct_conversations_rotate_guest_password( $conversation_id );
	if ( '' === $enter ) {
		return null;
	}

	return array(
		'chat_link' => snks_direct_conversations_guest_entry_link( $conv->public_token ),
		'enter'     => $enter,
	);
}

/**
 * Build chat_pt2 WhatsApp body parameters: SPA deep link to the digest conversation.
 *
 * @param int $patient_user_id Patient user ID.
 * @return array{chat_link:string}|null Null when no qualifying conversation.
 */
function snks_direct_conversations_patient_digest_whatsapp_params( $patient_user_id ) {
	$conv = snks_direct_conversations_digest_wa_conversation_for_patient( $patient_user_id );
	if ( ! $conv ) {
		return null;
	}

	return array(
		'chat_link' => snks_direct_conversations_patient_app_link( (int) $conv->id ),
	);
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
	if ( ! $wa_enabled || ! function_exists( 'snks_get_user_whatsapp' ) || ! function_exists( 'snks_send_whatsapp_template_message' ) ) {
		return;
	}
	$phone = snks_get_user_whatsapp( $recipient_user_id );
	if ( ! $phone ) {
		return;
	}

	// chat_th (therapist, static body) or chat_pt1 (patient, {{chat_link}} + {{enter}}). No fallback — empty option skips WhatsApp.
	$tpl    = '';
	$params = array();
	if ( snks_direct_conversations_is_doctor_user( $recipient_user_id ) ) {
		$tpl = snks_dc_wa_tpl_therapist();
	} else {
		$tpl = snks_dc_wa_tpl_patient_first();
		if ( '' !== $tpl ) {
			$params = snks_direct_conversations_patient_first_whatsapp_params( $conversation_id );
			if ( ! $params ) {
				$tpl = '';
			}
		}
	}

	if ( '' !== $tpl ) {
		snks_send_whatsapp_template_message( $phone, $tpl, $params );
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
 * Latest unread message per conversation for the recipient (therapist hub unread tab).
 * One row per patient/thread so the list does not repeat the same sender.
 *
 * @param int $user_id Recipient (therapist) user ID.
 * @param int $limit   Max conversations to return.
 * @param int $offset  Offset.
 * @return array<int,object>
 */
function snks_direct_conversations_inbox_unread_latest_per_conversation( $user_id, $limit = 10, $offset = 0 ) {
	global $wpdb;
	$t      = snks_direct_conversations_tables();
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
		INNER JOIN (
			SELECT m1.conversation_id, MAX(m1.id) AS latest_id
			FROM {$t['msg']} m1
			WHERE m1.recipient_user_id = %d AND m1.is_read = 0
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
 * Turn stored profile image value (attachment ID or absolute URL) into a public URL.
 *
 * @param mixed $raw Meta or column value.
 * @return string
 */
function snks_direct_conversations_resolve_profile_media_url( $raw ) {
	if ( null === $raw || '' === $raw ) {
		return '';
	}
	if ( is_numeric( $raw ) ) {
		$aid = (int) $raw;
		if ( $aid <= 0 ) {
			return '';
		}
		$url = wp_get_attachment_url( $aid );
		return $url ? $url : '';
	}
	if ( is_string( $raw ) && preg_match( '#^https?://#i', $raw ) ) {
		return esc_url_raw( $raw );
	}
	return '';
}

/**
 * Avatar URL for a user.
 *
 * Therapists (doctor/admin): same application profile_image as AI therapist card
 * (get_ai_therapist / format_ai_therapist_from_application): approved + show_on_ai_site = 1,
 * then any approved row; no ai_profile_image so chat never diverges from listing.
 * Customers: therapist_applications if present, then ai_profile_image meta, then Gravatar.
 *
 * @param int $user_id User ID.
 * @return string
 */
function snks_direct_conversations_user_avatar_url( $user_id ) {
	static $cache = array();
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}
	if ( array_key_exists( $user_id, $cache ) ) {
		return $cache[ $user_id ];
	}
	global $wpdb;
	$table = $wpdb->prefix . 'therapist_applications';

	$is_doctor = snks_direct_conversations_is_doctor_user( $user_id );

	if ( $is_doctor ) {
		// Same row shape as TherapistCard data from get_ai_therapist / search APIs.
		$profile_raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT profile_image FROM {$table} WHERE user_id = %d AND status = %s AND show_on_ai_site = 1 ORDER BY id DESC LIMIT 1",
				$user_id,
				'approved'
			)
		);
		$url = snks_direct_conversations_resolve_profile_media_url( $profile_raw );
		if ( '' !== $url ) {
			return $cache[ $user_id ] = $url;
		}
		// Approved application photo but not flagged for AI listing (internal / legacy).
		$profile_raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT profile_image FROM {$table} WHERE user_id = %d AND status = %s ORDER BY id DESC LIMIT 1",
				$user_id,
				'approved'
			)
		);
		$url = snks_direct_conversations_resolve_profile_media_url( $profile_raw );
		if ( '' !== $url ) {
			return $cache[ $user_id ] = $url;
		}
		$fallback = get_avatar_url( $user_id, array( 'size' => 128 ) );
		return $cache[ $user_id ] = $fallback ? $fallback : '';
	}

	$profile_raw = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT profile_image FROM {$table} WHERE user_id = %d AND status = %s ORDER BY id DESC LIMIT 1",
			$user_id,
			'approved'
		)
	);
	$url = snks_direct_conversations_resolve_profile_media_url( $profile_raw );
	if ( '' !== $url ) {
		return $cache[ $user_id ] = $url;
	}
	$ai_img = get_user_meta( $user_id, 'ai_profile_image', true );
	$url    = snks_direct_conversations_resolve_profile_media_url( $ai_img );
	if ( '' !== $url ) {
		return $cache[ $user_id ] = $url;
	}
	$fallback = get_avatar_url( $user_id, array( 'size' => 128 ) );
	return $cache[ $user_id ] = $fallback ? $fallback : '';
}

/**
 * Other participant in a conversation (for thread header in apps).
 *
 * @param object $conv           Row from snks_direct_conversations.
 * @param int    $viewer_user_id Current user.
 * @return array{user_id:int,name:string,avatar_url:string}
 */
function snks_direct_conversations_counterparty_for_viewer( $conv, $viewer_user_id ) {
	$viewer_user_id = (int) $viewer_user_id;
	$tid            = (int) $conv->therapist_user_id;
	$pid            = (int) $conv->patient_user_id;
	$other          = ( $tid === $viewer_user_id ) ? $pid : $tid;
	if ( $other <= 0 ) {
		return array(
			'user_id'    => 0,
			'name'       => '',
			'avatar_url' => '',
		);
	}
	$name = '';
	if ( function_exists( 'snks_get_therapist_name' ) && snks_direct_conversations_is_doctor_user( $other ) ) {
		$name = snks_get_therapist_name( $other );
	}
	if ( '' === $name ) {
		if ( snks_direct_conversations_is_customer_user( $other ) ) {
			$name = snks_direct_conversations_patient_display_name( $other );
		} else {
			$u = get_userdata( $other );
			if ( $u ) {
				$fn   = (string) get_user_meta( $other, 'first_name', true );
				$ln   = (string) get_user_meta( $other, 'last_name', true );
				$full = trim( $fn . ' ' . $ln );
				$name = '' !== $full ? $full : ( $u->display_name ? $u->display_name : $u->user_login );
			}
		}
	}
	return array(
		'user_id'    => $other,
		'name'       => $name,
		'avatar_url' => snks_direct_conversations_user_avatar_url( $other ),
	);
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
	if ( isset( $message->sender_user_id ) ) {
		$sid = (int) $message->sender_user_id;
		$message->sender_avatar_url = snks_direct_conversations_user_avatar_url( $sid );
		$stype = isset( $message->sender_type ) ? (string) $message->sender_type : '';
		if ( 'patient' === $stype || ( $sid > 0 && snks_direct_conversations_is_customer_user( $sid ) ) ) {
			$message->sender_name = snks_direct_conversations_patient_display_name( $sid );
		} elseif ( isset( $message->sender_name ) && snks_direct_conversations_string_looks_like_phone( $message->sender_name ) && $sid > 0 ) {
			$message->sender_name = snks_direct_conversations_patient_display_name( $sid );
		}
	} else {
		$message->sender_avatar_url = '';
	}
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
 * Patient label prefers first_name/last_name and billing name meta so phone logins do not dominate the modal.
 *
 * @param int $therapist_user_id Therapist ID.
 * @param int $limit             Max rows.
 * @return array
 */
function snks_direct_conversations_list_for_therapist( $therapist_user_id, $limit = 50 ) {
	global $wpdb;
	$t = snks_direct_conversations_tables();

	$list = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.*, m.body AS last_body, m.created_at AS last_at, m.sender_type AS last_sender_type,
				CASE
					WHEN CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, '')) != ' ' THEN TRIM(CONCAT(COALESCE(fn.meta_value, ''), ' ', COALESCE(ln.meta_value, '')))
					WHEN CONCAT(COALESCE(bfn.meta_value, ''), ' ', COALESCE(bln.meta_value, '')) != ' ' THEN TRIM(CONCAT(COALESCE(bfn.meta_value, ''), ' ', COALESCE(bln.meta_value, '')))
					WHEN u.display_name != '' THEN u.display_name
					ELSE u.user_login
				END AS patient_name
			FROM {$t['conv']} c
			LEFT JOIN (
				SELECT conversation_id, MAX(id) AS mid FROM {$t['msg']} GROUP BY conversation_id
			) latest ON latest.conversation_id = c.id
			LEFT JOIN {$t['msg']} m ON m.id = latest.mid
			LEFT JOIN {$wpdb->users} u ON u.ID = c.patient_user_id
			LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id = c.patient_user_id AND fn.meta_key = 'first_name'
			LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id = c.patient_user_id AND ln.meta_key = 'last_name'
			LEFT JOIN {$wpdb->usermeta} bfn ON bfn.user_id = c.patient_user_id AND bfn.meta_key = 'billing_first_name'
			LEFT JOIN {$wpdb->usermeta} bln ON bln.user_id = c.patient_user_id AND bln.meta_key = 'billing_last_name'
			WHERE c.therapist_user_id = %d
			ORDER BY COALESCE(m.created_at, c.updated_at) DESC
			LIMIT %d",
			(int) $therapist_user_id,
			(int) $limit
		)
	);
	foreach ( $list as $row ) {
		if ( isset( $row->patient_user_id ) ) {
			$pid = (int) $row->patient_user_id;
			$row->patient_name       = snks_direct_conversations_patient_display_name( $pid );
			$row->patient_avatar_url = snks_direct_conversations_user_avatar_url( $pid );
		} else {
			$row->patient_avatar_url = '';
		}
	}
	return $list;
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

	$rows = $wpdb->get_results( $sql );
	foreach ( $rows as $row ) {
		if ( isset( $row->patient_user_id ) ) {
			$pid = (int) $row->patient_user_id;
			$row->patient_name       = snks_direct_conversations_patient_display_name( $pid );
			$row->patient_avatar_url = snks_direct_conversations_user_avatar_url( $pid );
		} else {
			$row->patient_avatar_url = '';
		}
	}
	return $rows;
}

/**
 * Daily digest: one in-app notification per user per day if unread in window.
 *
 * @param bool $force_debug_log Store a full report even when the debug option is off (admin manual run).
 * @return array<string,mixed>|null Run report when debug logging is on or $force_debug_log is true; otherwise null.
 */
function snks_direct_conversations_run_daily_digest( $force_debug_log = false ) {
	global $wpdb;

	$days     = snks_direct_conversations_get_summary_days();
	$log_run  = $force_debug_log || snks_dc_digest_debug_enabled();
	$next_ts  = wp_next_scheduled( 'snks_direct_conversations_daily_digest' );
	$report   = array(
		'run_at'           => current_time( 'mysql' ),
		'run_at_gmt'       => current_time( 'mysql', true ),
		'timezone'         => wp_timezone_string(),
		'summary_days'     => $days,
		'digest_hour'      => (int) get_option( 'snks_direct_conv_digest_hour', 20 ),
		'next_cron_utc'    => $next_ts ? gmdate( 'Y-m-d H:i:s', $next_ts ) : null,
		'trigger'          => $force_debug_log ? 'admin_manual' : 'cron',
		'candidates'       => 0,
		'in_app_sent'      => 0,
		'whatsapp_sent'    => 0,
		'users'            => array(),
		'abort'            => null,
	);

	$finish = static function ( array $rep ) use ( $log_run, $force_debug_log ) {
		if ( $log_run || $force_debug_log ) {
			snks_dc_digest_debug_store_run( $rep );
			snks_dc_digest_debug_error_log( 'run complete', array( 'abort' => $rep['abort'], 'candidates' => $rep['candidates'] ) );
			return $rep;
		}
		return null;
	};

	if ( ! function_exists( 'snks_create_ai_notification' ) ) {
		$report['abort'] = 'missing_snks_create_ai_notification';
		return $finish( $report );
	}

	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	$t_msg               = snks_direct_conversations_tables()['msg'];

	// Users with at least one unread message in window.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name controlled.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT recipient_user_id FROM {$t_msg} WHERE is_read = 0 AND created_at >= ( NOW() - INTERVAL %d DAY )",
			$days
		)
	);
	$report['candidates'] = is_array( $user_ids ) ? count( $user_ids ) : 0;

	if ( empty( $user_ids ) ) {
		$report['abort'] = 'no_users_with_unread_in_window';
		return $finish( $report );
	}

	foreach ( $user_ids as $uid ) {
		$uid    = (int) $uid;
		$entry  = array(
			'user_id'             => $uid,
			'is_therapist'        => snks_direct_conversations_is_doctor_user( $uid ),
			'unread_in_window'    => 0,
			'unread_older_than_n' => 0,
			'in_app'              => array( 'status' => 'pending' ),
			'whatsapp'            => array( 'status' => 'pending' ),
		);

		if ( $uid <= 0 ) {
			$entry['in_app']['status']  = 'skipped';
			$entry['in_app']['reason']  = 'invalid_user_id';
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'invalid_user_id';
			$report['users'][]           = $entry;
			continue;
		}

		$n_window                      = snks_direct_conversations_unread_in_digest_window( $uid );
		$entry['unread_in_window']     = $n_window;
		$entry['unread_older_than_n']  = snks_direct_conversations_unread_older_than_threshold( $uid );

		if ( $n_window <= 0 ) {
			$entry['in_app']['status']   = 'skipped';
			$entry['in_app']['reason']   = 'zero_unread_in_window';
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'zero_unread_in_window';
			$report['users'][]           = $entry;
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
			$entry['in_app']['status']  = 'skipped';
			$entry['in_app']['reason']  = 'digest_already_sent_today';
			$entry['in_app']['existing_notification_id'] = (int) $exists;
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'digest_already_sent_today';
			$report['users'][]           = $entry;
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

		$before_max_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(id), 0) FROM {$notifications_table} WHERE user_id = %d",
				$uid
			)
		);

		snks_create_ai_notification( $uid, SNKS_DIRECT_CONV_NOTIF_DIGEST, $title, $message, $link );

		$new_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$notifications_table} WHERE user_id = %d AND type = %s AND id > %d ORDER BY id DESC LIMIT 1",
				$uid,
				SNKS_DIRECT_CONV_NOTIF_DIGEST,
				$before_max_id
			)
		);

		if ( $new_id > 0 ) {
			$entry['in_app']['status']          = 'sent';
			$entry['in_app']['notification_id'] = $new_id;
			++$report['in_app_sent'];
		} else {
			$entry['in_app']['status']  = 'failed';
			$entry['in_app']['reason']  = 'insert_not_verified';
			$entry['in_app']['db_error'] = $wpdb->last_error ? $wpdb->last_error : '';
		}

		// Optional WhatsApp daily digest for unread messages older than threshold.
		$old_unread = (int) $entry['unread_older_than_n'];
		if ( $old_unread <= 0 ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'no_unread_older_than_threshold';
			$entry['whatsapp']['note']   = 'WhatsApp requires unread messages older than summary_days (' . $days . '). In-app may still send.';
			$report['users'][] = $entry;
			continue;
		}

		$wa_enabled = (string) get_option( 'snks_ai_notifications_enabled', '1' ) === '1';
		if ( ! $wa_enabled ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'ai_notifications_disabled';
			$report['users'][]           = $entry;
			continue;
		}
		if ( ! function_exists( 'snks_get_user_whatsapp' ) || ! function_exists( 'snks_send_whatsapp_template_message' ) ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'whatsapp_helpers_missing';
			$report['users'][]           = $entry;
			continue;
		}

		$phone = snks_get_user_whatsapp( $uid );
		if ( ! $phone ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'no_whatsapp_phone';
			$report['users'][]           = $entry;
			continue;
		}
		$entry['whatsapp']['phone_masked'] = substr( $phone, 0, 4 ) . '***';

		$tpl    = '';
		$params = array();
		if ( snks_direct_conversations_is_doctor_user( $uid ) ) {
			$tpl = snks_dc_wa_tpl_therapist();
		} else {
			$tpl = snks_dc_wa_tpl_patient_digest();
			if ( '' !== $tpl ) {
				$params = snks_direct_conversations_patient_digest_whatsapp_params( $uid );
				if ( ! $params ) {
					$tpl = '';
				}
			}
		}

		$entry['whatsapp']['template'] = $tpl;

		if ( '' === $tpl ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = snks_direct_conversations_is_doctor_user( $uid )
				? 'whatsapp_template_dc_therapist_empty'
				: ( snks_dc_wa_tpl_patient_digest() === '' ? 'whatsapp_template_dc_patient_digest_empty' : 'no_qualifying_conversation_for_chat_link' );
			$report['users'][]           = $entry;
			continue;
		}

		if ( ! snks_direct_conversations_is_doctor_user( $uid ) && empty( $params ) ) {
			$entry['whatsapp']['status'] = 'skipped';
			$entry['whatsapp']['reason'] = 'empty_template_params';
			$report['users'][]           = $entry;
			continue;
		}

		$entry['whatsapp']['params'] = $params;
		$wa_result                   = snks_send_whatsapp_template_message( $phone, $tpl, $params );

		if ( is_wp_error( $wa_result ) ) {
			$entry['whatsapp']['status'] = 'failed';
			$entry['whatsapp']['reason'] = $wa_result->get_error_code();
			$entry['whatsapp']['error']  = $wa_result->get_error_message();
		} else {
			$entry['whatsapp']['status']     = 'sent';
			$entry['whatsapp']['message_id'] = isset( $wa_result['messages'][0]['id'] ) ? $wa_result['messages'][0]['id'] : null;
			++$report['whatsapp_sent'];
		}

		$report['users'][] = $entry;
	}

	return $finish( $report );
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

/**
 * Clear messaging-related test state (admin only): in-app notifications for direct chat,
 * unread flags on direct messages, and unread flags on therapist–patient session messages.
 *
 * @return array{deleted_ai_notifications:int,direct_messages_marked_read:int,session_messages_marked_read:int}
 */
function snks_direct_conversations_admin_clear_messaging_test_flags() {
	global $wpdb;

	$out = array(
		'deleted_ai_notifications'       => 0,
		'direct_messages_marked_read'   => 0,
		'session_messages_marked_read'   => 0,
	);

	$notif = $wpdb->prefix . 'snks_ai_notifications';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $notif ) ) === $notif ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
		$out['deleted_ai_notifications'] = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$notif} WHERE type IN (%s, %s)",
				SNKS_DIRECT_CONV_NOTIF_STARTED,
				SNKS_DIRECT_CONV_NOTIF_DIGEST
			)
		);
	}

	$t      = snks_direct_conversations_tables();
	$dc_msg = $t['msg'];
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $dc_msg ) ) === $dc_msg ) {
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
		$out['direct_messages_marked_read'] = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$dc_msg} SET is_read = 1, read_at = %s WHERE is_read = 0",
				$now
			)
		);
	}

	$sess = $wpdb->prefix . 'snks_session_messages';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $sess ) ) === $sess ) {
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
		$out['session_messages_marked_read'] = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$sess} SET is_read = 1, read_at = %s WHERE is_read = 0",
				$now
			)
		);
	}

	return $out;
}
