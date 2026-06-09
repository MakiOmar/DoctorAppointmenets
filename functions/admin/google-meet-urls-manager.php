<?php
/**
 * Google Meet URLs admin manager.
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Register submenu under Jalsah AI.
 */
function snks_register_google_meet_urls_admin_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Google Meet URLs', 'shrinks' ),
		__( 'Google Meet URLs', 'shrinks' ),
		'manage_options',
		'jalsah-ai-google-meet-urls',
		'snks_google_meet_urls_admin_page'
	);
}
add_action( 'admin_menu', 'snks_register_google_meet_urls_admin_menu', 25 );

/**
 * Handle form submissions.
 */
function snks_google_meet_urls_handle_post() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( empty( $_POST['snks_google_meet_action'] ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'snks_google_meet_urls' ) ) {
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['snks_google_meet_action'] ) );

	if ( 'save_settings' === $action ) {
		$provider = isset( $_POST['snks_live_stream_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['snks_live_stream_provider'] ) ) : 'jitsi';
		if ( ! in_array( $provider, array( 'jitsi', 'google_meet' ), true ) ) {
			$provider = 'jitsi';
		}
		update_option( 'snks_live_stream_provider', $provider );
		update_option( 'snks_google_meet_low_pool_threshold', max( 1, absint( $_POST['snks_google_meet_low_pool_threshold'] ?? 10 ) ) );
		update_option( 'snks_google_meet_low_pool_notify_enabled', ! empty( $_POST['snks_google_meet_low_pool_notify_enabled'] ) ? '1' : '0' );
		update_option( 'snks_google_meet_low_pool_notify_emails', sanitize_textarea_field( wp_unslash( $_POST['snks_google_meet_low_pool_notify_emails'] ?? '' ) ) );
		add_settings_error( 'snks_google_meet', 'saved', __( 'Settings saved.', 'shrinks' ), 'success' );
		snks_google_meet_maybe_alert_low_pool();
		return;
	}

	if ( 'bulk_add' === $action ) {
		$text = isset( $_POST['snks_bulk_meet_urls'] ) ? wp_unslash( $_POST['snks_bulk_meet_urls'] ) : '';
		$result = snks_bulk_insert_google_meet_urls( $text );
		add_settings_error(
			'snks_google_meet',
			'bulk',
			sprintf(
				/* translators: 1: inserted 2: dupes 3: invalid */
				__( 'Inserted %1$d URL(s). Skipped %2$d duplicate(s), %3$d invalid line(s).', 'shrinks' ),
				$result['inserted'],
				$result['skipped_duplicate'],
				$result['skipped_invalid']
			),
			'success'
		);
		return;
	}

	if ( 'disable' === $action && ! empty( $_POST['url_id'] ) ) {
		global $wpdb;
		$table = snks_google_meet_urls_table_name();
		$wpdb->update(
			$table,
			array( 'status' => 'disabled' ),
			array( 'id' => absint( $_POST['url_id'] ), 'status' => 'available' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
		snks_google_meet_maybe_alert_low_pool();
		add_settings_error( 'snks_google_meet', 'disabled', __( 'URL disabled.', 'shrinks' ), 'success' );
		return;
	}

	if ( 'enable' === $action && ! empty( $_POST['url_id'] ) ) {
		global $wpdb;
		$table = snks_google_meet_urls_table_name();
		$wpdb->update(
			$table,
			array( 'status' => 'available' ),
			array( 'id' => absint( $_POST['url_id'] ), 'status' => 'disabled' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
		snks_google_meet_maybe_alert_low_pool();
		add_settings_error( 'snks_google_meet', 'enabled', __( 'URL enabled.', 'shrinks' ), 'success' );
		return;
	}

	if ( 'unassign' === $action && ! empty( $_POST['url_id'] ) ) {
		$result = snks_unassign_google_meet_url( absint( $_POST['url_id'] ) );
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'snks_google_meet', 'unassign', $result->get_error_message(), 'error' );
		} else {
			add_settings_error( 'snks_google_meet', 'unassigned', __( 'URL unassigned and returned to the pool.', 'shrinks' ), 'success' );
		}
		return;
	}

	if ( 'manual_assign' === $action ) {
		$url_id     = isset( $_POST['url_id'] ) ? absint( $_POST['url_id'] ) : 0;
		$assign_type = isset( $_POST['assign_target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['assign_target_type'] ) ) : '';
		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;

		$result = snks_assign_google_meet_url_manual( $url_id, $assign_type, $session_id );
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'snks_google_meet', 'manual_assign', $result->get_error_message(), 'error' );
		} else {
			add_settings_error(
				'snks_google_meet',
				'manual_assigned',
				sprintf(
					/* translators: 1: URL id 2: type 3: session id */
					__( 'URL #%1$d assigned to %2$s #%3$d.', 'shrinks' ),
					$url_id,
					$assign_type,
					$session_id
				),
				'success'
			);
		}
		return;
	}

	if ( 'assign_session' === $action ) {
		$assign_type = isset( $_POST['assign_target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['assign_target_type'] ) ) : '';
		$session_id  = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;

		$result = snks_assign_google_meet_url( $assign_type, $session_id );
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'snks_google_meet', 'assign_session', $result->get_error_message(), 'error' );
		} else {
			add_settings_error(
				'snks_google_meet',
				'assigned_session',
				sprintf(
					/* translators: 1: type 2: session id */
					__( 'First available URL assigned to %1$s #%2$d.', 'shrinks' ),
					$assign_type,
					$session_id
				),
				'success'
			);
		}
		return;
	}

	if ( 'delete' === $action && ! empty( $_POST['url_id'] ) ) {
		global $wpdb;
		$table = snks_google_meet_urls_table_name();
		$wpdb->delete(
			$table,
			array(
				'id'     => absint( $_POST['url_id'] ),
				'status' => 'available',
			),
			array( '%d', '%s' )
		);
		snks_google_meet_maybe_alert_low_pool();
		add_settings_error( 'snks_google_meet', 'deleted', __( 'URL deleted.', 'shrinks' ), 'success' );
	}
}
add_action( 'admin_init', 'snks_google_meet_urls_handle_post' );

/**
 * Render admin page.
 *
 * @return void
 */
function snks_google_meet_urls_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table = snks_google_meet_urls_table_name();

	$filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
	$where  = '1=1';
	if ( in_array( $filter, array( 'available', 'assigned', 'disabled' ), true ) ) {
		$where = $wpdb->prepare( 'status = %s', $filter );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 500" );

	$available = snks_google_meet_count_available();
	$assigned  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'assigned'" );
	$disabled  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'disabled'" );
	$threshold = max( 1, (int) get_option( 'snks_google_meet_low_pool_threshold', 10 ) );
	$provider  = snks_get_live_stream_provider();

	settings_errors( 'snks_google_meet' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Google Meet URLs', 'shrinks' ); ?></h1>

		<?php if ( snks_is_google_meet_active() && $available < $threshold ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Low pool warning:', 'shrinks' ); ?></strong>
					<?php
					printf(
						/* translators: 1: count 2: threshold */
						esc_html__( '%1$d available URLs (threshold: %2$d).', 'shrinks' ),
						(int) $available,
						(int) $threshold
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: 1: available 2: assigned 3: disabled */
				esc_html__( 'Available: %1$d | Assigned: %2$d | Disabled: %3$d', 'shrinks' ),
				(int) $available,
				(int) $assigned,
				(int) $disabled
			);
			?>
		</p>

		<h2><?php esc_html_e( 'Live stream settings', 'shrinks' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
			<input type="hidden" name="snks_google_meet_action" value="save_settings" />
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Provider', 'shrinks' ); ?></th>
					<td>
						<select name="snks_live_stream_provider" id="snks_live_stream_provider">
							<option value="jitsi" <?php selected( $provider, 'jitsi' ); ?>><?php esc_html_e( 'Jitsi (default)', 'shrinks' ); ?></option>
							<option value="google_meet" <?php selected( $provider, 'google_meet' ); ?>><?php esc_html_e( 'Google Meet (replaces Jitsi)', 'shrinks' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'When Google Meet is enabled, Jitsi embeds and timer-based join flows are disabled site-wide for online sessions.', 'shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Low-pool threshold', 'shrinks' ); ?></th>
					<td>
						<input type="number" min="1" name="snks_google_meet_low_pool_threshold" value="<?php echo esc_attr( $threshold ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Notify when available URLs are less than this number.', 'shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Email alerts', 'shrinks' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="snks_google_meet_low_pool_notify_enabled" value="1" <?php checked( get_option( 'snks_google_meet_low_pool_notify_enabled', '1' ), '1' ); ?> />
							<?php esc_html_e( 'Send email when pool is low', 'shrinks' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notification emails', 'shrinks' ); ?></th>
					<td>
						<textarea name="snks_google_meet_low_pool_notify_emails" rows="2" class="large-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"><?php echo esc_textarea( get_option( 'snks_google_meet_low_pool_notify_emails', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Comma-separated. Empty uses site admin email.', 'shrinks' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save settings', 'shrinks' ) ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Add URLs (one per line)', 'shrinks' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
			<input type="hidden" name="snks_google_meet_action" value="bulk_add" />
			<p>
				<textarea name="snks_bulk_meet_urls" rows="10" class="large-text code" placeholder="https://meet.google.com/abc-defg-hij"></textarea>
			</p>
			<?php submit_button( __( 'Add URLs', 'shrinks' ), 'secondary' ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Manual assignment', 'shrinks' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Link a pool URL to a booked online timetable session or a confirmed Rochtah booking. If the session already has a URL, it will be replaced.', 'shrinks' ); ?></p>
		<form method="post" class="snks-google-meet-manual-assign" style="margin-bottom:1.5em;padding:1em;background:#fff;border:1px solid #c3c4c7;max-width:720px;">
			<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
			<input type="hidden" name="snks_google_meet_action" value="manual_assign" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="snks_manual_url_id"><?php esc_html_e( 'Pool URL ID', 'shrinks' ); ?></label></th>
					<td>
						<input type="number" min="1" name="url_id" id="snks_manual_url_id" class="small-text" required />
						<p class="description"><?php esc_html_e( 'ID from the pool table below (must be available).', 'shrinks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_manual_assign_type"><?php esc_html_e( 'Assign to', 'shrinks' ); ?></label></th>
					<td>
						<select name="assign_target_type" id="snks_manual_assign_type" required>
							<option value="timetable"><?php esc_html_e( 'Timetable session', 'shrinks' ); ?></option>
							<option value="rochtah"><?php esc_html_e( 'Rochtah booking', 'shrinks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_manual_session_id"><?php esc_html_e( 'Session / booking ID', 'shrinks' ); ?></label></th>
					<td>
						<input type="number" min="1" name="session_id" id="snks_manual_session_id" class="small-text" required />
						<p class="description"><?php esc_html_e( 'Timetable row ID or Rochtah booking ID.', 'shrinks' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Assign URL', 'shrinks' ), 'primary', 'submit', false ); ?>
		</form>

		<form method="post" class="snks-google-meet-auto-assign" style="margin-bottom:1.5em;padding:1em;background:#fff;border:1px solid #c3c4c7;max-width:720px;">
			<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
			<input type="hidden" name="snks_google_meet_action" value="assign_session" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="snks_auto_assign_type"><?php esc_html_e( 'Assign to', 'shrinks' ); ?></label></th>
					<td>
						<select name="assign_target_type" id="snks_auto_assign_type" required>
							<option value="timetable"><?php esc_html_e( 'Timetable session', 'shrinks' ); ?></option>
							<option value="rochtah"><?php esc_html_e( 'Rochtah booking', 'shrinks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snks_auto_session_id"><?php esc_html_e( 'Session / booking ID', 'shrinks' ); ?></label></th>
					<td>
						<input type="number" min="1" name="session_id" id="snks_auto_session_id" class="small-text" required />
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Assign first available URL', 'shrinks' ), 'secondary', 'submit', false ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'URL pool', 'shrinks' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-google-meet-urls' ) ); ?>"><?php esc_html_e( 'All', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-google-meet-urls&status=available' ) ); ?>"><?php esc_html_e( 'Available', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-google-meet-urls&status=assigned' ) ); ?>"><?php esc_html_e( 'Assigned', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-google-meet-urls&status=disabled' ) ); ?>"><?php esc_html_e( 'Disabled', 'shrinks' ); ?></a>
		</p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'URL', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Assigned to', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'shrinks' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No URLs found.', 'shrinks' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo (int) $row->id; ?></td>
							<td><a href="<?php echo esc_url( $row->meet_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->meet_url ); ?></a></td>
							<td><?php echo esc_html( $row->status ); ?></td>
							<td>
								<?php
								if ( ! empty( $row->assigned_timetable_id ) ) {
									echo esc_html( sprintf( __( 'Timetable #%d', 'shrinks' ), (int) $row->assigned_timetable_id ) );
								} elseif ( ! empty( $row->assigned_rochtah_booking_id ) ) {
									echo esc_html( sprintf( __( 'Rochtah #%d', 'shrinks' ), (int) $row->assigned_rochtah_booking_id ) );
								} else {
									echo '—';
								}
								if ( ! empty( $row->assigned_at ) ) {
									echo '<br><small>' . esc_html( $row->assigned_at ) . '</small>';
								}
								?>
							</td>
							<td>
								<?php if ( 'available' === $row->status ) : ?>
									<form method="post" style="display:inline-flex;flex-wrap:wrap;gap:4px;align-items:center;margin-bottom:4px;">
										<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
										<input type="hidden" name="snks_google_meet_action" value="manual_assign" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<select name="assign_target_type" aria-label="<?php esc_attr_e( 'Assign to', 'shrinks' ); ?>">
											<option value="timetable"><?php esc_html_e( 'Timetable', 'shrinks' ); ?></option>
											<option value="rochtah"><?php esc_html_e( 'Rochtah', 'shrinks' ); ?></option>
										</select>
										<input type="number" min="1" name="session_id" class="small-text" placeholder="<?php esc_attr_e( 'ID', 'shrinks' ); ?>" required style="width:72px;" />
										<button type="submit" class="button button-small button-primary"><?php esc_html_e( 'Assign', 'shrinks' ); ?></button>
									</form>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
										<input type="hidden" name="snks_google_meet_action" value="disable" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Disable', 'shrinks' ); ?></button>
									</form>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this URL?', 'shrinks' ) ); ?>');">
										<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
										<input type="hidden" name="snks_google_meet_action" value="delete" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'shrinks' ); ?></button>
									</form>
								<?php elseif ( 'assigned' === $row->status ) : ?>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Unassign this URL? The session will no longer have this Meet link until a new one is assigned.', 'shrinks' ) ); ?>');">
										<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
										<input type="hidden" name="snks_google_meet_action" value="unassign" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Unassign', 'shrinks' ); ?></button>
									</form>
								<?php elseif ( 'disabled' === $row->status ) : ?>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'snks_google_meet_urls' ); ?>
										<input type="hidden" name="snks_google_meet_action" value="enable" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Enable', 'shrinks' ); ?></button>
									</form>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
