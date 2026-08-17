<?php
/**
 * Session reminder notifications debug admin page.
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Register session notification debug tools under Jalsah AI.
 *
 * @return void
 */
function snks_session_notif_debug_admin_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		'Session Notif Debug',
		'Session Notif Debug',
		'manage_options',
		'snks-session-notif-debug',
		'snks_session_notif_debug_admin_page'
	);
}
add_action( 'admin_menu', 'snks_session_notif_debug_admin_menu', 80 );

/**
 * Render session notification debug page.
 *
 * @return void
 */
function snks_session_notif_debug_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$manual_report = null;

	if ( isset( $_POST['snks_session_notif_debug_save_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_session_notif_debug_save_nonce'] ) ), 'snks_session_notif_debug_save' ) ) {
		update_option( 'snks_session_notif_debug_enabled', isset( $_POST['snks_session_notif_debug_enabled'] ) ? '1' : '0', false );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Debug settings saved.', 'anony-shrinks' ) . '</p></div>';
	}

	if ( isset( $_POST['snks_session_notif_run_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_session_notif_run_nonce'] ) ), 'snks_session_notif_run' ) ) {
		if ( function_exists( 'snks_send_session_notifications' ) ) {
			$manual_report = snks_send_session_notifications( true );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cron handler ran. See report below.', 'anony-shrinks' ) . '</p></div>';
		}
	}

	if ( isset( $_POST['snks_session_notif_clear_log_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snks_session_notif_clear_log_nonce'] ) ), 'snks_session_notif_clear_log' ) ) {
		delete_option( 'snks_session_notif_debug_log' );
		delete_option( 'snks_session_notif_last_run' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Debug log cleared.', 'anony-shrinks' ) . '</p></div>';
	}

	$debug_on   = function_exists( 'snks_session_notif_debug_enabled' ) && snks_session_notif_debug_enabled();
	$last_run   = get_option( 'snks_session_notif_last_run', null );
	$log        = get_option( 'snks_session_notif_debug_log', array() );
	$next_cron  = wp_next_scheduled( 'snks_check_session_notifications' );
	$use_timers = function_exists( 'snks_should_use_jitsi_meeting_timers' ) && snks_should_use_jitsi_meeting_timers();
	$gm_active  = function_exists( 'snks_is_google_meet_active' ) && snks_is_google_meet_active();
	$wa         = function_exists( 'snks_get_whatsapp_notification_settings' ) ? snks_get_whatsapp_notification_settings() : array();
	$wa_on      = isset( $wa['enabled'] ) && (string) $wa['enabled'] === '1';
	$hour       = (int) current_time( 'H' );

	if ( ! is_array( $log ) ) {
		$log = array();
	}
	if ( is_array( $manual_report ) ) {
		$last_run = $manual_report;
	}
	?>
	<div class="wrap">
		<!-- Session reminder notification debug tools -->
		<h1><?php esc_html_e( 'Session notification debug', 'anony-shrinks' ); ?></h1>
		<p>
			<?php esc_html_e( 'Inspect why snks_check_session_notifications / snks_send_session_notifications did or did not send 24h / 1h reminders. Run from here or via WP Crontrol, then refresh this page.', 'anony-shrinks' ); ?>
		</p>

		<div class="card" style="max-width:960px;">
			<h2><?php esc_html_e( 'Live gates', 'anony-shrinks' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Site local time', 'anony-shrinks' ); ?></th><td><code><?php echo esc_html( current_time( 'mysql' ) ); ?></code> (hour <?php echo esc_html( (string) $hour ); ?>)</td></tr>
					<tr><th><?php esc_html_e( 'Google Meet active', 'anony-shrinks' ); ?></th><td><?php echo $gm_active ? 'yes (reminders still send)' : 'no'; ?></td></tr>
					<tr><th><?php esc_html_e( 'use_meeting_timers', 'anony-shrinks' ); ?></th><td><?php echo $use_timers ? 'yes' : 'no (Jitsi timers off; reminders still send)'; ?></td></tr>
					<tr><th><?php esc_html_e( 'WhatsApp notifications enabled', 'anony-shrinks' ); ?></th><td><?php echo $wa_on ? 'yes' : '<strong style="color:#b32d2e;">no</strong>'; ?></td></tr>
					<tr><th><?php esc_html_e( '24h hour gate (>= 9)', 'anony-shrinks' ); ?></th><td><?php echo $hour >= 9 ? 'pass' : '<strong style="color:#b32d2e;">fail (hour &lt; 9)</strong>'; ?></td></tr>
					<tr><th><?php esc_html_e( 'Next WP-Cron', 'anony-shrinks' ); ?></th><td><?php echo $next_cron ? esc_html( wp_date( 'Y-m-d H:i:s', $next_cron ) ) : esc_html__( 'not scheduled', 'anony-shrinks' ); ?></td></tr>
				</tbody>
			</table>
		</div>

		<div class="card" style="max-width:960px;margin-top:16px;">
			<h2><?php esc_html_e( 'Actions', 'anony-shrinks' ); ?></h2>
			<form method="post" style="display:inline-block;margin-right:12px;">
				<?php wp_nonce_field( 'snks_session_notif_run', 'snks_session_notif_run_nonce' ); ?>
				<?php submit_button( __( 'Run snks_send_session_notifications now', 'anony-shrinks' ), 'primary', 'submit', false ); ?>
			</form>
			<form method="post" style="display:inline-block;margin-right:12px;">
				<?php wp_nonce_field( 'snks_session_notif_clear_log', 'snks_session_notif_clear_log_nonce' ); ?>
				<?php submit_button( __( 'Clear debug log', 'anony-shrinks' ), 'secondary', 'submit', false ); ?>
			</form>
			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'snks_session_notif_debug_save', 'snks_session_notif_debug_save_nonce' ); ?>
				<label>
					<input name="snks_session_notif_debug_enabled" type="checkbox" value="1" <?php checked( $debug_on ); ?> />
					<?php esc_html_e( 'Keep history of last 20 runs (always stores last run)', 'anony-shrinks' ); ?>
				</label>
				<?php submit_button( __( 'Save', 'anony-shrinks' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card" style="max-width:960px;margin-top:16px;">
			<h2><?php esc_html_e( 'Last run', 'anony-shrinks' ); ?></h2>
			<?php if ( empty( $last_run ) || ! is_array( $last_run ) ) : ?>
				<p><?php esc_html_e( 'No run recorded yet. Trigger the cron or use the button above.', 'anony-shrinks' ); ?></p>
			<?php else : ?>
				<p><strong><?php echo esc_html( isset( $last_run['summary'] ) ? (string) $last_run['summary'] : '' ); ?></strong></p>
				<pre style="max-height:480px;overflow:auto;background:#1d2327;color:#f0f0f1;padding:12px;direction:ltr;text-align:left;"><?php echo esc_html( wp_json_encode( $last_run, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $log ) ) : ?>
			<div class="card" style="max-width:960px;margin-top:16px;">
				<h2><?php esc_html_e( 'Recent runs', 'anony-shrinks' ); ?></h2>
				<ol>
					<?php foreach ( $log as $entry ) : ?>
						<?php if ( ! is_array( $entry ) ) { continue; } ?>
						<li>
							<code><?php echo esc_html( isset( $entry['ran_at'] ) ? (string) $entry['ran_at'] : '' ); ?></code>
							—
							<?php echo esc_html( isset( $entry['summary'] ) ? (string) $entry['summary'] : '' ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
