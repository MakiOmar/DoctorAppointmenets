<?php
/**
 * Admin settings: direct conversations digest window, attachments, digest hour, app URL, WhatsApp template.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	if ( isset( $_POST['snks_dc_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_dc_settings_nonce'] ) ), 'snks_dc_settings' ) ) {
		update_option( 'snks_conversation_unread_summary_days', max( 1, min( 365, absint( $_POST['snks_conversation_unread_summary_days'] ?? 3 ) ) ) );
		update_option( 'snks_direct_conv_max_upload_bytes', max( 1024, absint( $_POST['snks_direct_conv_max_upload_bytes'] ?? 5242880 ) ) );
		update_option( 'snks_direct_conv_allowed_mimes', sanitize_text_field( wp_unslash( $_POST['snks_direct_conv_allowed_mimes'] ?? '' ) ) );
		update_option( 'snks_direct_conv_digest_hour', max( 0, min( 23, absint( $_POST['snks_direct_conv_digest_hour'] ?? 20 ) ) ) );
		update_option( 'snks_jalsah_ai_frontend_url', esc_url_raw( wp_unslash( $_POST['snks_jalsah_ai_frontend_url'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_direct_conversation', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_direct_conversation'] ?? '' ) ) );
		update_option( 'snks_whatsapp_template_direct_conversation_digest', sanitize_text_field( wp_unslash( $_POST['snks_whatsapp_template_direct_conversation_digest'] ?? '' ) ) );
		snks_direct_conversations_reschedule_digest_cron();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'anony-shrinks' ) . '</p></div>';
	}

	$days   = (int) get_option( 'snks_conversation_unread_summary_days', 3 );
	$maxb   = (int) get_option( 'snks_direct_conv_max_upload_bytes', 5242880 );
	$mimes  = (string) get_option( 'snks_direct_conv_allowed_mimes', 'image/jpeg,image/png,image/gif,application/pdf' );
	$hour   = (int) get_option( 'snks_direct_conv_digest_hour', 20 );
	$appurl = (string) get_option( 'snks_jalsah_ai_frontend_url', '' );
	$watpl  = (string) get_option( 'snks_whatsapp_template_direct_conversation', '' );
	$wadig  = (string) get_option( 'snks_whatsapp_template_direct_conversation_digest', '' );
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
					<td><input name="snks_direct_conv_max_upload_bytes" id="snks_direct_conv_max_upload_bytes" type="number" min="1024" value="<?php echo esc_attr( (string) $maxb ); ?>" class="regular-text" /></td>
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
					<th scope="row"><label for="snks_whatsapp_template_direct_conversation"><?php esc_html_e( 'WhatsApp template name (conversation started)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_direct_conversation" id="snks_whatsapp_template_direct_conversation" type="text" value="<?php echo esc_attr( $watpl ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Optional. Must match your WhatsApp Cloud API template. Body parameters: name, link.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_whatsapp_template_direct_conversation_digest"><?php esc_html_e( 'WhatsApp template name (daily unread digest)', 'anony-shrinks' ); ?></label></th>
					<td>
						<input name="snks_whatsapp_template_direct_conversation_digest" id="snks_whatsapp_template_direct_conversation_digest" type="text" value="<?php echo esc_attr( $wadig ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Optional. Sent once daily when user has unread direct messages older than the configured day threshold. Body parameters: count, days, link.', 'anony-shrinks' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
