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
			// chat_pt1 — patient first therapist message: dc-access link + {{enter}} password.
			$event_name = 'chat_pt1';
			$tpl        = snks_dc_wa_tpl_patient_first();
			$test_cid   = isset( $_POST['test_conversation_id'] ) ? absint( $_POST['test_conversation_id'] ) : 0;
			$params     = array();
			if ( $test_cid > 0 && function_exists( 'snks_direct_conversations_patient_first_whatsapp_params' ) ) {
				$params = snks_direct_conversations_patient_first_whatsapp_params( $test_cid );
			}
			if ( empty( $params ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'For chat_pt1 test, enter a valid conversation ID below so a dc-access link and enter password can be generated.', 'anony-shrinks' ),
					)
				);
			}
			break;
		case 'patient_digest':
			// chat_pt2 — patient digest: dc-access/inbox link + fixed enter (same shape as chat_pt1).
			$event_name = 'chat_pt2';
			$tpl        = snks_dc_wa_tpl_patient_digest();
			$test_uid   = isset( $_POST['test_patient_user_id'] ) ? absint( $_POST['test_patient_user_id'] ) : 0;
			if ( $test_uid <= 0 && isset( $_POST['test_conversation_id'] ) ) {
				$test_cid = absint( $_POST['test_conversation_id'] );
				if ( $test_cid > 0 ) {
					global $wpdb;
					$t_conv = $wpdb->prefix . 'snks_direct_conversations';
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$test_uid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT patient_user_id FROM {$t_conv} WHERE id = %d", $test_cid ) );
				}
			}
			$params = array();
			if ( $test_uid > 0 && function_exists( 'snks_direct_conversations_patient_digest_whatsapp_params' ) ) {
				$params = snks_direct_conversations_patient_digest_whatsapp_params( $test_uid );
			}
			if ( empty( $params ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'For chat_pt2 test, enter a valid patient user ID (or conversation ID to resolve the patient). Patient must have unread in the digest window.', 'anony-shrinks' ),
					)
				);
			}
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
			chat_link: $("#snks_dc_wa_test_chat_link").val(),
			test_conversation_id: $("#snks_dc_wa_test_conversation_id").val(),
			test_patient_user_id: $("#snks_dc_wa_test_patient_user_id").val()
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
		update_option( 'snks_whatsapp_template_dc_therapist', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_therapist'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_dc_patient_first', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_patient_first'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_dc_patient_digest', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_dc_patient_digest'] ?? '' ) ) );
		update_option( 'snks_dc_digest_debug_enabled', isset( $_POST['snks_dc_digest_debug_enabled'] ) ? '1' : '0' );
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

	$digest_manual_report = null;
	$digest_diagnose      = null;

	if ( isset( $_POST['snks_dc_digest_run_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_digest_run_nonce'] ) ), 'snks_dc_digest_run' ) ) {
		if ( function_exists( 'snks_direct_conversations_run_daily_digest' ) ) {
			$digest_manual_report = snks_direct_conversations_run_daily_digest( true );
		}
	}

	if ( isset( $_POST['snks_dc_digest_clear_log_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_digest_clear_log_nonce'] ) ), 'snks_dc_digest_clear_log' ) ) {
		delete_option( 'snks_dc_digest_debug_log' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Digest debug log cleared.', 'anony-shrinks' ) . '</p></div>';
	}

	if ( isset( $_POST['snks_dc_digest_diagnose_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_digest_diagnose_nonce'] ) ), 'snks_dc_digest_diagnose' ) ) {
		$diag_uid = absint( $_POST['snks_dc_digest_diagnose_user_id'] ?? 0 );
		if ( $diag_uid > 0 && function_exists( 'snks_direct_conversations_digest_diagnose_user' ) ) {
			$digest_diagnose = snks_direct_conversations_digest_diagnose_user( $diag_uid );
		}
	}

	$days   = (int) get_option( 'snks_conversation_unread_summary_days', 3 );
	$maxb   = (int) get_option( 'snks_direct_conv_max_upload_bytes', 0 );
	$mimes  = (string) get_option( 'snks_direct_conv_allowed_mimes', 'image/jpeg,image/png,image/gif,application/pdf' );
	$hour   = (int) get_option( 'snks_direct_conv_digest_hour', 20 );
	$appurl = function_exists( 'snks_direct_conversations_patient_app_base_url' )
		? untrailingslashit( snks_direct_conversations_patient_app_base_url() )
		: ( function_exists( 'snks_ai_get_primary_frontend_url' ) ? snks_ai_get_primary_frontend_url() : '' );
	$wa_th  = (string) get_option( 'snks_whatsapp_template_dc_therapist', '' );
	$wa_pf  = (string) get_option( 'snks_whatsapp_template_dc_patient_first', '' );
	$wa_pd  = (string) get_option( 'snks_whatsapp_template_dc_patient_digest', '' );
	$sample_chat_link = function_exists( 'snks_direct_conversations_patient_app_link' )
		? snks_direct_conversations_patient_app_link( 0 )
		: trailingslashit( home_url( '/' ) ) . 'notifications';
	$digest_debug_on  = function_exists( 'snks_dc_digest_debug_enabled' ) && snks_dc_digest_debug_enabled();
	$digest_log       = get_option( 'snks_dc_digest_debug_log', array() );
	if ( ! is_array( $digest_log ) ) {
		$digest_log = array();
	}
	$digest_next_cron = wp_next_scheduled( 'snks_direct_conversations_daily_digest' );
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
					<th scope="row"><?php esc_html_e( 'Patient link base URL', 'anony-shrinks' ); ?></th>
					<td>
						<code class="large-text"><?php echo esc_html( $appurl ? trailingslashit( $appurl ) : '—' ); ?></code>
						<p class="description">
							<?php
							printf(
								/* translators: %s: admin settings page link */
								esc_html__( 'WhatsApp and in-app patient links use the first URL in %s (Frontend URLs).', 'anony-shrinks' ),
								'<a href="' . esc_url( admin_url( 'admin.php?page=jalsah-ai-settings' ) ) . '">' . esc_html__( 'Jalsah AI → General settings', 'anony-shrinks' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_therapist"><?php esc_html_e( 'WhatsApp: therapist template (chat_th)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_therapist" id="snks_whatsapp_template_dc_therapist" type="text" value="<?php echo esc_attr( $wa_th ); ?>" class="regular-text" placeholder="chat_th" />
						<p class="description"><?php esc_html_e( 'Sent for the therapist daily unread digest when there is unread within the summary window. Fixed body copy in Meta; send no named body variables from this plugin. If empty, no WhatsApp for that therapist digest event.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_patient_first"><?php esc_html_e( 'WhatsApp: patient first message from therapist (chat_pt1)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_patient_first" id="snks_whatsapp_template_dc_patient_first" type="text" value="<?php echo esc_attr( $wa_pf ); ?>" class="regular-text" placeholder="chat_pt1" />
						<p class="description"><?php esc_html_e( 'Sent on the first message in a thread when the therapist is the sender. Named body parameters: chat_link (password-protected dc-access URL) and enter (numeric access password). If empty, no WhatsApp for that patient event.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_dc_patient_digest"><?php esc_html_e( 'WhatsApp: patient daily unread digest (chat_pt2)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_dc_patient_digest" id="snks_whatsapp_template_dc_patient_digest" type="text" value="<?php echo esc_attr( $wa_pd ); ?>" class="regular-text" placeholder="chat_pt2" />
						<p class="description"><?php esc_html_e( 'Daily digest when unread within the summary window. Same variables as chat_pt1: chat_link (dc-access/inbox URL) and enter (fixed per-patient password). Opens the conversations list after guest login. If empty, no digest WhatsApp is sent to patients.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Digest debug logging', 'anony-shrinks' ); ?></th>
					<td>
						<label>
							<input name="snks_dc_digest_debug_enabled" type="checkbox" value="1" <?php checked( $digest_debug_on ); ?> />
							<?php esc_html_e( 'Log every cron digest run (last 15 runs stored)', 'anony-shrinks' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Also writes to PHP error_log when WP_DEBUG_LOG is enabled. Use the section below to run manually and diagnose users.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Daily digest debug', 'anony-shrinks' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'In-app and WhatsApp digest: unread messages sent within the last N days only, once per user per day. Common blockers: digest_already_sent_today, no_unread_in_summary_window, cron not running, missing phone or template.', 'anony-shrinks' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 960px; margin-bottom: 1em;">
			<tbody>
				<tr>
					<th scope="row" style="width: 220px;"><?php esc_html_e( 'WP-Cron hook', 'anony-shrinks' ); ?></th>
					<td><code>snks_direct_conversations_daily_digest</code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Next scheduled run (UTC)', 'anony-shrinks' ); ?></th>
					<td>
						<?php
						if ( $digest_next_cron ) {
							echo esc_html( gmdate( 'Y-m-d H:i:s', $digest_next_cron ) );
							echo ' <span class="description">(' . esc_html( wp_timezone_string() ) . ' site TZ, hour ' . esc_html( (string) $hour ) . ')</span>';
						} else {
							echo '<span style="color:#b32d2e;">' . esc_html__( 'Not scheduled — save settings or visit the site front end to register cron on init.', 'anony-shrinks' ) . '</span>';
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Debug logging', 'anony-shrinks' ); ?></th>
					<td><?php echo $digest_debug_on ? esc_html__( 'On', 'anony-shrinks' ) : esc_html__( 'Off (manual run still logs one report)', 'anony-shrinks' ); ?></td>
				</tr>
			</tbody>
		</table>

		<form method="post" style="margin-bottom: 1em;">
			<?php wp_nonce_field( 'snks_dc_digest_run', 'snks_dc_digest_run_nonce' ); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Run digest now (with debug report)', 'anony-shrinks' ); ?></button>
		</form>

		<form method="post" style="display: inline-block; margin-right: 8px;">
			<?php wp_nonce_field( 'snks_dc_digest_clear_log', 'snks_dc_digest_clear_log_nonce' ); ?>
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Clear debug log', 'anony-shrinks' ); ?></button>
		</form>

		<form method="post" style="display: inline-block; margin-top: 1em;">
			<?php wp_nonce_field( 'snks_dc_digest_diagnose', 'snks_dc_digest_diagnose_nonce' ); ?>
			<label for="snks_dc_digest_diagnose_user_id"><?php esc_html_e( 'Diagnose user ID', 'anony-shrinks' ); ?></label>
			<input type="number" name="snks_dc_digest_diagnose_user_id" id="snks_dc_digest_diagnose_user_id" class="small-text" min="1" step="1" value="<?php echo isset( $_POST['snks_dc_digest_diagnose_user_id'] ) ? esc_attr( (string) absint( $_POST['snks_dc_digest_diagnose_user_id'] ) ) : ''; ?>" />
			<button type="submit" class="button"><?php esc_html_e( 'Preview (no send)', 'anony-shrinks' ); ?></button>
		</form>

		<?php if ( is_array( $digest_diagnose ) ) : ?>
			<h3><?php esc_html_e( 'User diagnosis', 'anony-shrinks' ); ?></h3>
			<pre style="max-width: 960px; overflow: auto; background: #f6f7f7; padding: 12px; border: 1px solid #c3c4c7;"><?php echo esc_html( wp_json_encode( $digest_diagnose, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
		<?php endif; ?>

		<?php if ( is_array( $digest_manual_report ) ) : ?>
			<h3><?php esc_html_e( 'Last manual run', 'anony-shrinks' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: 1: candidates count, 2: in-app sent, 3: whatsapp sent */
					esc_html__( 'Candidates: %1$d — in-app sent: %2$d — WhatsApp sent: %3$d', 'anony-shrinks' ),
					(int) ( $digest_manual_report['candidates'] ?? 0 ),
					(int) ( $digest_manual_report['in_app_sent'] ?? 0 ),
					(int) ( $digest_manual_report['whatsapp_sent'] ?? 0 )
				);
				if ( ! empty( $digest_manual_report['abort'] ) ) {
					echo ' — <strong>' . esc_html__( 'Aborted:', 'anony-shrinks' ) . '</strong> <code>' . esc_html( (string) $digest_manual_report['abort'] ) . '</code>';
				}
				?>
			</p>
			<pre style="max-width: 960px; max-height: 480px; overflow: auto; background: #f6f7f7; padding: 12px; border: 1px solid #c3c4c7;"><?php echo esc_html( wp_json_encode( $digest_manual_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
		<?php endif; ?>

		<?php if ( ! empty( $digest_log ) ) : ?>
			<h3><?php esc_html_e( 'Stored debug log (newest first)', 'anony-shrinks' ); ?></h3>
			<?php foreach ( array_slice( $digest_log, 0, 5 ) as $idx => $run ) : ?>
				<details style="max-width: 960px; margin-bottom: 8px; border: 1px solid #c3c4c7; padding: 8px 12px; background: #fff;">
					<summary>
						<?php
						printf(
							/* translators: 1: run time, 2: trigger, 3: candidates */
							esc_html__( 'Run %1$d: %2$s (%3$s) — %4$d candidate(s), in-app %5$d, WhatsApp %6$d', 'anony-shrinks' ),
							(int) $idx + 1,
							esc_html( (string) ( $run['run_at'] ?? '?' ) ),
							esc_html( (string) ( $run['trigger'] ?? 'cron' ) ),
							(int) ( $run['candidates'] ?? 0 ),
							(int) ( $run['in_app_sent'] ?? 0 ),
							(int) ( $run['whatsapp_sent'] ?? 0 )
						);
						if ( ! empty( $run['abort'] ) ) {
							echo ' — <code>' . esc_html( (string) $run['abort'] ) . '</code>';
						}
						?>
					</summary>
					<pre style="overflow: auto; max-height: 360px; margin-top: 8px;"><?php echo esc_html( wp_json_encode( $run, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
				</details>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No digest debug runs stored yet. Enable logging above or click “Run digest now”.', 'anony-shrinks' ); ?></p>
		<?php endif; ?>

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
				<th scope="row"><label for="snks_dc_wa_test_patient_user_id"><?php esc_html_e( 'Patient user ID (chat_pt2 test)', 'anony-shrinks' ); ?></label></th>
				<td>
					<input type="number" id="snks_dc_wa_test_patient_user_id" class="small-text" min="1" step="1" placeholder="2297" />
					<p class="description"><?php esc_html_e( 'Required for chat_pt2: builds dc-access/inbox link + fixed enter for that patient (must have unread in digest window).', 'anony-shrinks' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="snks_dc_wa_test_conversation_id"><?php esc_html_e( 'Conversation ID (chat_pt1 test)', 'anony-shrinks' ); ?></label></th>
				<td>
					<input type="number" id="snks_dc_wa_test_conversation_id" class="small-text" min="1" step="1" placeholder="1" />
					<p class="description"><?php esc_html_e( 'Required for chat_pt1: dc-access link + rotated enter for that thread. Optional for chat_pt2: resolves patient user ID from conversation.', 'anony-shrinks' ); ?></p>
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
