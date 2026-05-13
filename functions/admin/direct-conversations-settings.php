<?php
/**
 * Admin settings: direct conversations digest window, attachments, digest hour, app URL, WhatsApp templates (therapist/patient roles).
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: send a test WhatsApp using the same templates/params as production (no global notification disable check).
 *
 * @return void
 */
function snks_dc_test_whatsapp_ajax() {
	if ( ! check_ajax_referer( 'snks_dc_wa_test', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'anony-shrinks' ) ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'anony-shrinks' ) ) );
	}
	if ( ! function_exists( 'snks_send_whatsapp_template_message' ) ) {
		wp_send_json_error( array( 'message' => __( 'WhatsApp sender is not available.', 'anony-shrinks' ) ) );
	}

	if ( ! function_exists( 'snks_dc_wa_tpl_therapist' ) && defined( 'SNKS_DIR' ) ) {
		require_once SNKS_DIR . 'functions/direct-conversations/snks-direct-conversations.php';
	}
	if ( ! function_exists( 'snks_dc_wa_tpl_therapist' ) ) {
		wp_send_json_error( array( 'message' => __( 'Direct conversation helpers are not loaded.', 'anony-shrinks' ) ) );
	}

	$event    = sanitize_key( wp_unslash( $_POST['dc_event'] ?? '' ) );
	$phone_in = sanitize_text_field( wp_unslash( $_POST['test_phone'] ?? '' ) );
	if ( '' === $phone_in ) {
		wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'anony-shrinks' ) ) );
	}

	$digits = preg_replace( '/[^\d+]/', '', $phone_in );
	$phone  = ltrim( $digits, '+' );
	if ( '' === $phone ) {
		wp_send_json_error( array( 'message' => __( 'Invalid phone number.', 'anony-shrinks' ) ) );
	}

	$tpl        = '';
	$params     = array();
	$event_name = '';

	switch ( $event ) {
		case 'therapist':
			// chat_th — static body, same as therapist first-message + digest WhatsApp branches.
			$event_name = 'chat_th';
			$tpl        = snks_dc_wa_tpl_therapist();
			$params     = array();
			break;
		case 'patient_first':
			// chat_pt1 — patient first therapist message (chat_link).
			$event_name = 'chat_pt1';
			$tpl        = snks_dc_wa_tpl_patient_first();
			$link       = isset( $_POST['chat_link'] ) ? esc_url_raw( wp_unslash( $_POST['chat_link'] ) ) : '';
			if ( '' === $link && function_exists( 'snks_direct_conversations_patient_app_link' ) ) {
				$link = snks_direct_conversations_patient_app_link( 0 );
			}
			if ( '' === $link ) {
				$link = trailingslashit( home_url( '/' ) ) . 'notifications';
			}
			$params = array( 'chat_link' => $link );
			break;
		case 'patient_digest':
			// chat_pt2 — patient digest WhatsApp (chat_link).
			$event_name = 'chat_pt2';
			$tpl        = snks_dc_wa_tpl_patient_digest();
			$link       = isset( $_POST['chat_link'] ) ? esc_url_raw( wp_unslash( $_POST['chat_link'] ) ) : '';
			if ( '' === $link && function_exists( 'snks_direct_conversations_patient_app_link' ) ) {
				$link = snks_direct_conversations_patient_app_link( 0 );
			}
			if ( '' === $link ) {
				$link = trailingslashit( home_url( '/' ) ) . 'notifications';
			}
			$params = array( 'chat_link' => $link );
			break;
		default:
			wp_send_json_error( array( 'message' => __( 'Invalid test type.', 'anony-shrinks' ) ) );
	}

	if ( '' === $tpl ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: example template key chat_th chat_pt1 or chat_pt2 */
					__( 'The template option for %s is empty. Save settings above first.', 'anony-shrinks' ),
					$event_name
				),
			)
		);
	}

	$result = snks_send_whatsapp_template_message( $phone, $tpl, $params );

	if ( is_wp_error( $result ) ) {
		$error_data     = $result->get_error_data();
		$detailed_error = __( 'WhatsApp API error: ', 'anony-shrinks' ) . $result->get_error_message();
		if ( isset( $error_data['response_body'] ) && is_string( $error_data['response_body'] ) ) {
			$detailed_error .= ' ' . $error_data['response_body'];
		}
		wp_send_json_error(
			array(
				'message'     => $detailed_error,
				'template'    => $tpl,
				'event'       => $event_name,
				'params_sent' => $params,
				'phone'       => $phone_in,
			)
		);
	}

	$message_id = isset( $result['messages'][0]['id'] ) ? $result['messages'][0]['id'] : null;

	wp_send_json_success(
		array(
			'message'      => __( 'Test message sent.', 'anony-shrinks' ),
			'message_id'   => $message_id,
			'template'     => $tpl,
			'event'        => $event_name,
			'params_sent'  => $params,
			'phone'        => $phone_in,
		)
	);
}
add_action( 'wp_ajax_snks_dc_test_whatsapp', 'snks_dc_test_whatsapp_ajax' );

/**
 * Scripts for WhatsApp test UI (direct conversations settings screen only).
 *
 * @param string $hook Current admin hook suffix.
 * @return void
 */
function snks_dc_settings_admin_enqueue( $hook ) {
	if ( false === strpos( $hook, 'jalsah-ai-direct-conversations' ) ) {
		return;
	}
	wp_enqueue_script( 'jquery' );
	wp_localize_script(
		'jquery',
		'snksDcWaTest',
		array(
			'ajaxUrl'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'nonce'        => wp_create_nonce( 'snks_dc_wa_test' ),
			// translators: Fallback when AJAX response is malformed.
			'errorAjax'    => __( 'Network error. Could not reach the server.', 'anony-shrinks' ),
			// translators: When wp_send_json_error body is unexpected.
			'errorUnknown' => __( 'Request failed.', 'anony-shrinks' ),
		)
	);
	wp_add_inline_script(
		'jquery',
		'
jQuery(function($) {
	$(".snks-dc-wa-test-btn").on("click", function(e) {
		e.preventDefault();
		var $btn = $(this);
		var $out = $("#snks_dc_wa_test_result");
		$btn.prop("disabled", true);
		$out.show().removeClass("notice-success notice-error").addClass("notice").html("<p>' . esc_js( __( 'Sending…', 'anony-shrinks' ) ) . '</p>");
		$.post( snksDcWaTest.ajaxUrl, {
			action: "snks_dc_test_whatsapp",
			nonce: snksDcWaTest.nonce,
			dc_event: $btn.data("event"),
			test_phone: $("#snks_dc_wa_test_phone").val(),
			chat_link: $("#snks_dc_wa_test_chat_link").val()
		}).done(function(resp) {
			$btn.prop("disabled", false);
			if (resp.success && resp.data) {
				var d = resp.data;
				var extra = "";
				if (d.message_id) { extra += " ID: " + d.message_id; }
				if (d.params_sent && Object.keys(d.params_sent).length) {
					extra += " Parameters: " + JSON.stringify(d.params_sent);
				}
				$out.removeClass("notice-error").addClass("notice-success").html("<p>" + d.message + extra + "</p>");
			} else {
				var msg = (resp.data && resp.data.message) ? resp.data.message : snksDcWaTest.errorUnknown;
				$out.removeClass("notice-success").addClass("notice-error").html("<p>" + msg + "</p>");
			}
		}).fail(function() {
			$btn.prop("disabled", false);
			$out.removeClass("notice-success").addClass("notice-error").html("<p>" + snksDcWaTest.errorAjax + "</p>");
		});
	});
});
',
		'after'
	);
}
add_action( 'admin_enqueue_scripts', 'snks_dc_settings_admin_enqueue' );

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'jalsah-ai-management',
			__( 'Direct conversations', 'anony-shrinks' ),
			__( 'Direct conversations', 'anony-shrinks' ),
			'manage_options',
			'jalsah-ai-direct-conversations',
			'snks_direct_conversations_settings_page'
		);
	},
	25
);

/**
 * Reschedule digest cron after hour change.
 *
 * @return void
 */
function snks_direct_conversations_reschedule_digest_cron() {
	while ( $ts = wp_next_scheduled( 'snks_direct_conversations_daily_digest' ) ) {
		wp_unschedule_event( $ts, 'snks_direct_conversations_daily_digest' );
	}
	if ( function_exists( 'snks_direct_conversations_digest_next_timestamp' ) ) {
		wp_schedule_event( snks_direct_conversations_digest_next_timestamp(), 'daily', 'snks_direct_conversations_daily_digest' );
	}
}

/**
 * Render settings page.
 *
 * @return void
 */
function snks_direct_conversations_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! function_exists( 'snks_direct_conversations_patient_app_link' ) && defined( 'SNKS_DIR' ) ) {
		require_once SNKS_DIR . 'functions/direct-conversations/snks-direct-conversations.php';
	}

	if ( isset( $_POST['snks_dc_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_settings_nonce'] ) ), 'snks_dc_settings' ) ) {
		update_option( 'snks_conversation_unread_summary_days', max( 1, min( 365, absint( $_POST['snks_conversation_unread_summary_days'] ?? 3 ) ) ) );
		update_option( 'snks_direct_conv_max_upload_bytes', max( 0, absint( $_POST['snks_direct_conv_max_upload_bytes'] ?? 0 ) ) );
		update_option( 'snks_direct_conv_allowed_mimes', sanitize_text_field( wp_unslash( $_POST['snks_direct_conv_allowed_mimes'] ?? '' ) ) );
		update_option( 'snks_direct_conv_digest_hour', max( 0, min( 23, absint( $_POST['snks_direct_conv_digest_hour'] ?? 20 ) ) ) );
		update_option( 'snks_jalsah_ai_frontend_url', esc_url_raw( wp_unslash( $_POST['snks_jalsah_ai_frontend_url'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_dc_therapist', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_therapist'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_dc_patient_first', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_patient_first'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_dc_patient_digest', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_patient_digest'] ?? '' ) ) );
		snks_direct_conversations_reschedule_digest_cron();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'anony-shrinks' ) . '</p></div>';
	}

	if ( isset( $_POST['snks_dc_clear_messaging_test'] ) && isset( $_POST['snks_dc_clear_messaging_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_clear_messaging_nonce'] ) ), 'snks_dc_clear_messaging_test' ) ) {
		if ( ! function_exists( 'snks_direct_conversations_admin_clear_messaging_test_flags' ) && defined( 'SNKS_DIR' ) ) {
			require_once SNKS_DIR . 'functions/direct-conversations/snks-direct-conversations.php';
		}
		if ( function_exists( 'snks_direct_conversations_admin_clear_messaging_test_flags' ) ) {
			$r = snks_direct_conversations_admin_clear_messaging_test_flags();
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: deleted in-app rows, 2: direct DM rows marked read, 3: session message rows marked read */
					__( 'Messaging test flags cleared. Removed %1$d direct-chat in-app notification row(s); marked %2$d direct message(s) and %3$d session message(s) as read.', 'anony-shrinks' ),
					(int) $r['deleted_ai_notifications'],
					(int) $r['direct_messages_marked_read'],
					(int) $r['session_messages_marked_read']
				)
			);
			echo '</p></div>';
		}
	}

	$days   = (int) get_option( 'snks_conversation_unread_summary_days', 3 );
	$maxb   = (int) get_option( 'snks_direct_conv_max_upload_bytes', 0 );
	$mimes  = (string) get_option( 'snks_direct_conv_allowed_mimes', 'image/jpeg,image/png,image/gif,application/pdf' );
	$hour   = (int) get_option( 'snks_direct_conv_digest_hour', 20 );
	$appurl = (string) get_option( 'snks_jalsah_ai_frontend_url', '' );
	$wa_th  = (string) get_option( 'snks_whatsapp_template_dc_therapist', '' );
	$wa_pf  = (string) get_option( 'snks_whatsapp_template_dc_patient_first', '' );
	$wa_pd  = (string) get_option( 'snks_whatsapp_template_dc_patient_digest', '' );
	$sample_chat_link = function_exists( 'snks_direct_conversations_patient_app_link' )
		? snks_direct_conversations_patient_app_link( 0 )
		: trailingslashit( home_url( '/' ) ) . 'notifications';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Direct conversations', 'anony-shrinks' ); ?></h1>
		<p><?php esc_html_e( 'Digest notifications include only unread messages within the configured day window. Immediate notifications are sent only when a new conversation starts (first message).', 'anony-shrinks' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'snks_dc_settings', 'snks_dc_settings_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="snks_conversation_unread_summary_days"><?php esc_html_e( 'Unread summary window (days)', 'anony-shrinks' ); ?></label></th>
					<td><input name="snks_conversation_unread_summary_days" id="snks_conversation_unread_summary_days" type="number" min="1" max="365" value="<?php echo esc_attr( (string) $days ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_direct_conv_digest_hour"><?php esc_html_e( 'Daily digest hour (0–23, site timezone)', 'anony-shrinks' ); ?></label></th>
					<td><input name="snks_direct_conv_digest_hour" id="snks_direct_conv_digest_hour" type="number" min="0" max="23" value="<?php echo esc_attr( (string) $hour ); ?>" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_direct_conv_max_upload_bytes"><?php esc_html_e( 'Max attachment size (bytes)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_direct_conv_max_upload_bytes" id="snks_direct_conv_max_upload_bytes" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $maxb ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Use 0 for no plugin-side size limit (host and PHP upload_max_filesize still apply).', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_direct_conv_allowed_mimes"><?php esc_html_e( 'Allowed MIME types (comma-separated)', 'anony-shrinks' ); ?></label></th>
					<td><input name="snks_direct_conv_allowed_mimes" id="snks_direct_conv_allowed_mimes" type="text" value="<?php echo esc_attr( $mimes ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_jalsah_ai_frontend_url"><?php esc_html_e( 'Jalsah AI frontend base URL', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_jalsah_ai_frontend_url" id="snks_jalsah_ai_frontend_url" type="url" value="<?php echo esc_attr( $appurl ); ?>" class="large-text" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use the main site URL for patient deep links.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_therapist"><?php esc_html_e( 'WhatsApp: therapist template (chat_th)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_therapist" id="snks_whatsapp_template_dc_therapist" type="text" value="<?php echo esc_attr( $wa_th ); ?>" class="regular-text" placeholder="chat_th" />
						<p class="description"><?php esc_html_e( 'Sent when the client sends the first message in a thread and for the therapist daily unread digest when old unread exceeds the threshold. Fixed body copy in Meta; send no named body variables from this plugin. If empty, no WhatsApp for those therapist events.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_patient_first"><?php esc_html_e( 'WhatsApp: patient first message from therapist (chat_pt1)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_patient_first" id="snks_whatsapp_template_dc_patient_first" type="text" value="<?php echo esc_attr( $wa_pf ); ?>" class="regular-text" placeholder="chat_pt1" />
						<p class="description"><?php esc_html_e( 'Sent on the first message in a thread when the therapist is the sender. WhatsApp Cloud API body named parameter must be: chat_link (conversation deep link). If empty, no WhatsApp for that patient event.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_patient_digest"><?php esc_html_e( 'WhatsApp: patient daily unread digest (chat_pt2)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_patient_digest" id="snks_whatsapp_template_dc_patient_digest" type="text" value="<?php echo esc_attr( $wa_pd ); ?>" class="regular-text" placeholder="chat_pt2" />
						<p class="description"><?php esc_html_e( 'Daily digest WhatsApp when the patient has unread messages past the digest threshold. Named body parameter: chat_link (typically app notifications or conversation entry URL). If empty, no digest WhatsApp is sent to patients.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Test WhatsApp templates', 'anony-shrinks' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Sends one template message per click using the same template names and body parameters as production. Does not require the global AI notifications toggle to be on. Configure WhatsApp Cloud API credentials under Registration Settings.', 'anony-shrinks' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="snks_dc_wa_test_phone"><?php esc_html_e( 'Test phone number', 'anony-shrinks' ); ?></label></th>
				<td>
					<!-- Test recipient: full international number, with or without + -->
					<input type="text" id="snks_dc_wa_test_phone" class="regular-text" placeholder="2010xxxxxxxx" autocomplete="off" />
					<p class="description"><?php esc_html_e( 'E.164 style (e.g. 2010…). Same format as other WhatsApp tests on this site.', 'anony-shrinks' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="snks_dc_wa_test_chat_link"><?php esc_html_e( 'Sample chat_link (patient templates only)', 'anony-shrinks' ); ?></label></th>
				<td>
					<input type="url" id="snks_dc_wa_test_chat_link" class="large-text" value="<?php echo esc_attr( $sample_chat_link ); ?>" />
					<p class="description"><?php esc_html_e( 'Used for chat_pt1 and chat_pt2 tests (named parameter chat_link). Ignored for chat_th.', 'anony-shrinks' ); ?></p>
				</td>
			</tr>
		</table>
		<p>
			<button type="button" class="button snks-dc-wa-test-btn" data-event="therapist"><?php esc_html_e( 'Send test: chat_th (therapist)', 'anony-shrinks' ); ?></button>
			<button type="button" class="button snks-dc-wa-test-btn" data-event="patient_first"><?php esc_html_e( 'Send test: chat_pt1 (patient first message)', 'anony-shrinks' ); ?></button>
			<button type="button" class="button snks-dc-wa-test-btn" data-event="patient_digest"><?php esc_html_e( 'Send test: chat_pt2 (patient digest)', 'anony-shrinks' ); ?></button>
		</p>
		<!-- Test result feedback (shown after Send test) -->
		<div id="snks_dc_wa_test_result" class="notice" style="display: none; max-width: 720px; padding: 8px 12px;"></div>

		<hr />

		<h2><?php esc_html_e( 'Testing: clear messaging notification flags', 'anony-shrinks' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Removes in-app notifications for direct chat (new conversation + daily digest), marks every direct-conversation message as read, and marks every therapist–patient session inbox message as read. Use only on staging or when you intentionally want a clean slate; this affects all users.', 'anony-shrinks' ); ?>
		</p>
		<form method="post" onsubmit="return window.confirm(<?php echo wp_json_encode( __( 'Clear all messaging notification flags and unread state for every user? This cannot be undone.', 'anony-shrinks' ) ); ?>);">
			<?php wp_nonce_field( 'snks_dc_clear_messaging_test', 'snks_dc_clear_messaging_nonce' ); ?>
			<input type="hidden" name="snks_dc_clear_messaging_test" value="1" />
			<p>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Clear messaging test flags', 'anony-shrinks' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
