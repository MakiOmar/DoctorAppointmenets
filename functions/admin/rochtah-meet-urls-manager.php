<?php
/**
 * Rochetah Google Meet URL pool admin.
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Register submenu under Jalsah AI.
 */
function snks_register_rochtah_meet_urls_admin_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Rochtah Meet URLs', 'shrinks' ),
		__( 'Rochtah Meet URLs', 'shrinks' ),
		'manage_options',
		'jalsah-ai-rochtah-meet-urls',
		'snks_rochtah_meet_urls_admin_page'
	);
}
add_action( 'admin_menu', 'snks_register_rochtah_meet_urls_admin_menu', 26 );

/**
 * Handle admin form posts.
 */
function snks_rochtah_meet_urls_handle_post() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( empty( $_POST['snks_rochtah_meet_urls_action'] ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'snks_rochtah_meet_urls' ) ) {
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['snks_rochtah_meet_urls_action'] ) );

	if ( 'bulk_add' === $action ) {
		$text   = isset( $_POST['snks_bulk_rochtah_meet_urls'] ) ? wp_unslash( $_POST['snks_bulk_rochtah_meet_urls'] ) : '';
		$result = snks_rochtah_meet_urls_bulk_insert( $text );
		add_settings_error(
			'snks_rochtah_meet_urls',
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
		$table = snks_rochtah_meet_urls_table_name();
		$wpdb->update(
			$table,
			array( 'status' => 'disabled' ),
			array( 'id' => absint( $_POST['url_id'] ), 'status' => 'available' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
		add_settings_error( 'snks_rochtah_meet_urls', 'disabled', __( 'URL disabled.', 'shrinks' ), 'success' );
		return;
	}

	if ( 'enable' === $action && ! empty( $_POST['url_id'] ) ) {
		global $wpdb;
		$table = snks_rochtah_meet_urls_table_name();
		$wpdb->update(
			$table,
			array( 'status' => 'available' ),
			array( 'id' => absint( $_POST['url_id'] ), 'status' => 'disabled' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
		add_settings_error( 'snks_rochtah_meet_urls', 'enabled', __( 'URL enabled.', 'shrinks' ), 'success' );
		return;
	}

	if ( 'unassign' === $action && ! empty( $_POST['url_id'] ) ) {
		$result = snks_rochtah_meet_unassign_url( absint( $_POST['url_id'] ) );
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'snks_rochtah_meet_urls', 'unassign', $result->get_error_message(), 'error' );
		} else {
			add_settings_error( 'snks_rochtah_meet_urls', 'unassigned', __( 'URL unassigned and returned to the pool.', 'shrinks' ), 'success' );
		}
		return;
	}

	if ( 'delete' === $action && ! empty( $_POST['url_id'] ) ) {
		$result = snks_rochtah_meet_delete_url( absint( $_POST['url_id'] ) );
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'snks_rochtah_meet_urls', 'delete', $result->get_error_message(), 'error' );
		} else {
			add_settings_error( 'snks_rochtah_meet_urls', 'deleted', __( 'URL deleted.', 'shrinks' ), 'success' );
		}
		return;
	}

	// Table bulk actions (enable / disable / unassign / delete).
	if ( 'bulk' === $action ) {
		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$url_ids     = isset( $_POST['url_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['url_ids'] ) ) : array();
		$url_ids     = array_values( array_filter( $url_ids ) );

		if ( empty( $bulk_action ) || '-1' === $bulk_action ) {
			add_settings_error( 'snks_rochtah_meet_urls', 'bulk', __( 'Select a bulk action.', 'shrinks' ), 'error' );
			return;
		}
		if ( empty( $url_ids ) ) {
			add_settings_error( 'snks_rochtah_meet_urls', 'bulk', __( 'Select at least one URL.', 'shrinks' ), 'error' );
			return;
		}

		$allowed = array( 'enable', 'disable', 'unassign', 'delete' );
		if ( ! in_array( $bulk_action, $allowed, true ) ) {
			add_settings_error( 'snks_rochtah_meet_urls', 'bulk', __( 'Invalid bulk action.', 'shrinks' ), 'error' );
			return;
		}

		$result  = array(
			'success' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);
		$message = '';
		$code    = 'bulk_' . $bulk_action;

		if ( 'enable' === $bulk_action ) {
			$result  = snks_rochtah_meet_urls_bulk_enable( $url_ids );
			$message = sprintf(
				/* translators: 1: enabled count 2: skipped count */
				__( 'Enabled %1$d URL(s). Skipped %2$d (not disabled).', 'shrinks' ),
				(int) $result['success'],
				(int) $result['skipped']
			);
		} elseif ( 'disable' === $bulk_action ) {
			$result  = snks_rochtah_meet_urls_bulk_disable( $url_ids );
			$message = sprintf(
				/* translators: 1: disabled count 2: skipped count */
				__( 'Disabled %1$d URL(s). Skipped %2$d (not available).', 'shrinks' ),
				(int) $result['success'],
				(int) $result['skipped']
			);
		} elseif ( 'unassign' === $bulk_action ) {
			$result  = snks_rochtah_meet_urls_bulk_unassign( $url_ids );
			$message = sprintf(
				/* translators: 1: unassigned count 2: skipped count */
				__( 'Unassigned %1$d URL(s). Skipped %2$d (not assigned).', 'shrinks' ),
				(int) $result['success'],
				(int) $result['skipped']
			);
		} elseif ( 'delete' === $bulk_action ) {
			$result  = snks_rochtah_meet_urls_bulk_delete( $url_ids );
			$message = sprintf(
				/* translators: 1: deleted count 2: skipped count */
				__( 'Deleted %1$d URL(s). Skipped %2$d (assigned or missing).', 'shrinks' ),
				(int) $result['success'],
				(int) $result['skipped']
			);
		}

		if ( ! empty( $result['errors'] ) ) {
			$message .= ' ' . implode( ' ', $result['errors'] );
			add_settings_error( 'snks_rochtah_meet_urls', $code, $message, 'warning' );
		} else {
			add_settings_error( 'snks_rochtah_meet_urls', $code, $message, 'success' );
		}
	}
}
add_action( 'admin_init', 'snks_rochtah_meet_urls_handle_post' );

/**
 * Render admin page.
 *
 * @return void
 */
function snks_rochtah_meet_urls_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table  = snks_rochtah_meet_urls_table_name();
	$filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
	$where  = '1=1';
	if ( in_array( $filter, array( 'available', 'assigned', 'disabled' ), true ) ) {
		$where = $wpdb->prepare( 'status = %s', $filter );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows      = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 500" );
	$available = snks_rochtah_meet_urls_count_available();
	$assigned  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'assigned'" );
	$disabled  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'disabled'" );

	settings_errors( 'snks_rochtah_meet_urls' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rochtah Meet URLs', 'shrinks' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Separate URL pool for Rochetah Google Meet bookings. Staff select from available URLs when creating a booking on the AI frontend.', 'shrinks' ); ?></p>

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

		<h2><?php esc_html_e( 'Add URLs (one per line)', 'shrinks' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
			<input type="hidden" name="snks_rochtah_meet_urls_action" value="bulk_add" />
			<p>
				<textarea name="snks_bulk_rochtah_meet_urls" rows="10" class="large-text code" placeholder="https://meet.google.com/abc-defg-hij"></textarea>
				<span class="description"><?php esc_html_e( 'One HTTPS Google Meet link per line.', 'shrinks' ); ?></span>
			</p>
			<?php submit_button( __( 'Add URLs', 'shrinks' ), 'secondary' ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'URL pool', 'shrinks' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-rochtah-meet-urls' ) ); ?>"><?php esc_html_e( 'All', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-rochtah-meet-urls&status=available' ) ); ?>"><?php esc_html_e( 'Available', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-rochtah-meet-urls&status=assigned' ) ); ?>"><?php esc_html_e( 'Assigned', 'shrinks' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-rochtah-meet-urls&status=disabled' ) ); ?>"><?php esc_html_e( 'Disabled', 'shrinks' ); ?></a>
		</p>

		<form method="post" id="snks-rochtah-meet-pool-form" onsubmit="return snksRochtahMeetBulkConfirm(this);">
			<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
			<input type="hidden" name="snks_rochtah_meet_urls_action" value="bulk" />
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<label for="snks-rochtah-bulk-action-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'shrinks' ); ?></label>
					<select name="bulk_action" id="snks-rochtah-bulk-action-top">
						<option value="-1"><?php esc_html_e( 'Bulk actions', 'shrinks' ); ?></option>
						<option value="enable"><?php esc_html_e( 'Enable', 'shrinks' ); ?></option>
						<option value="disable"><?php esc_html_e( 'Disable', 'shrinks' ); ?></option>
						<option value="unassign"><?php esc_html_e( 'Unassign', 'shrinks' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'shrinks' ); ?></option>
					</select>
					<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'shrinks' ); ?>" />
				</div>
			</div>
		</form>

		<table class="widefat striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" id="snks-rochtah-meet-select-all" form="snks-rochtah-meet-pool-form" aria-label="<?php esc_attr_e( 'Select all', 'shrinks' ); ?>" />
					</td>
					<th><?php esc_html_e( 'ID', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'URL', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Assigned booking', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'shrinks' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No URLs found.', 'shrinks' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="url_ids[]" form="snks-rochtah-meet-pool-form" value="<?php echo (int) $row->id; ?>" class="snks-rochtah-meet-url-cb" />
							</th>
							<td><?php echo (int) $row->id; ?></td>
							<td><a href="<?php echo esc_url( $row->meet_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->meet_url ); ?></a></td>
							<td><?php echo esc_html( $row->status ); ?></td>
							<td>
								<?php
								if ( ! empty( $row->assigned_booking_id ) ) {
									echo esc_html( sprintf( __( 'Booking #%d', 'shrinks' ), (int) $row->assigned_booking_id ) );
									if ( ! empty( $row->assigned_at ) ) {
										echo '<br><small>' . esc_html( $row->assigned_at ) . '</small>';
									}
								} else {
									echo '—';
								}
								?>
							</td>
							<td>
								<?php if ( 'available' === $row->status ) : ?>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
										<input type="hidden" name="snks_rochtah_meet_urls_action" value="disable" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Disable', 'shrinks' ); ?></button>
									</form>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this URL?', 'shrinks' ) ); ?>');">
										<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
										<input type="hidden" name="snks_rochtah_meet_urls_action" value="delete" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'shrinks' ); ?></button>
									</form>
								<?php elseif ( 'assigned' === $row->status ) : ?>
									<?php if ( ! empty( $row->assigned_booking_id ) ) : ?>
										<!-- Fetch meeting/WhatsApp details on click, not on page load -->
										<button type="button" class="button button-small snks-rochtah-meet-details" data-url-id="<?php echo (int) $row->id; ?>"><?php esc_html_e( 'Meeting details', 'shrinks' ); ?></button>
									<?php endif; ?>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Unassign this URL?', 'shrinks' ) ); ?>');">
										<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
										<input type="hidden" name="snks_rochtah_meet_urls_action" value="unassign" />
										<input type="hidden" name="url_id" value="<?php echo (int) $row->id; ?>" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Unassign', 'shrinks' ); ?></button>
									</form>
								<?php elseif ( 'disabled' === $row->status ) : ?>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'snks_rochtah_meet_urls' ); ?>
										<input type="hidden" name="snks_rochtah_meet_urls_action" value="enable" />
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

		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label for="snks-rochtah-bulk-action-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'shrinks' ); ?></label>
				<select id="snks-rochtah-bulk-action-bottom">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'shrinks' ); ?></option>
					<option value="enable"><?php esc_html_e( 'Enable', 'shrinks' ); ?></option>
					<option value="disable"><?php esc_html_e( 'Disable', 'shrinks' ); ?></option>
					<option value="unassign"><?php esc_html_e( 'Unassign', 'shrinks' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'shrinks' ); ?></option>
				</select>
				<button type="button" class="button action" id="snks-rochtah-bulk-apply-bottom"><?php esc_html_e( 'Apply', 'shrinks' ); ?></button>
			</div>
		</div>

		<!-- Meeting details modal: content loaded via AJAX on button click -->
		<div id="snks-rochtah-meet-details-modal" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.45);">
			<div style="background:#fff;max-width:860px;margin:5vh auto;max-height:90vh;overflow:auto;padding:20px;border-radius:4px;box-shadow:0 8px 24px rgba(0,0,0,.2);">
				<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
					<h2 style="margin:0;"><?php esc_html_e( 'Meeting details', 'shrinks' ); ?></h2>
					<button type="button" class="button" id="snks-rochtah-meet-details-close"><?php esc_html_e( 'Close', 'shrinks' ); ?></button>
				</div>
				<div id="snks-rochtah-meet-details-body" style="margin-top:16px;">
					<p><?php esc_html_e( 'Loading…', 'shrinks' ); ?></p>
				</div>
			</div>
		</div>

		<script>
		(function() {
			var poolForm = document.getElementById('snks-rochtah-meet-pool-form');
			var selectAll = document.getElementById('snks-rochtah-meet-select-all');
			if (selectAll) {
				selectAll.addEventListener('change', function() {
					document.querySelectorAll('.snks-rochtah-meet-url-cb').forEach(function(cb) {
						cb.checked = selectAll.checked;
					});
				});
			}
			window.snksRochtahMeetBulkConfirm = function(form) {
				var actionEl = form.querySelector('[name="bulk_action"]');
				var action = actionEl ? actionEl.value : '-1';
				if (action === '-1') {
					alert(<?php echo wp_json_encode( __( 'Select a bulk action.', 'shrinks' ) ); ?>);
					return false;
				}
				var checked = document.querySelectorAll('.snks-rochtah-meet-url-cb:checked');
				if (!checked.length) {
					alert(<?php echo wp_json_encode( __( 'Select at least one URL.', 'shrinks' ) ); ?>);
					return false;
				}
				if (action === 'delete') {
					return confirm(<?php echo wp_json_encode( __( 'Delete selected URL(s)? Assigned URLs will be skipped.', 'shrinks' ) ); ?>);
				}
				if (action === 'unassign') {
					return confirm(<?php echo wp_json_encode( __( 'Unassign selected URL(s) and return them to the available pool?', 'shrinks' ) ); ?>);
				}
				if (action === 'disable') {
					return confirm(<?php echo wp_json_encode( __( 'Disable selected available URL(s)?', 'shrinks' ) ); ?>);
				}
				if (action === 'enable') {
					return confirm(<?php echo wp_json_encode( __( 'Enable selected disabled URL(s)?', 'shrinks' ) ); ?>);
				}
				return true;
			};
			var bottomApply = document.getElementById('snks-rochtah-bulk-apply-bottom');
			var bottomSelect = document.getElementById('snks-rochtah-bulk-action-bottom');
			if (bottomApply && bottomSelect && poolForm) {
				bottomApply.addEventListener('click', function() {
					var topSelect = poolForm.querySelector('[name="bulk_action"]');
					if (topSelect) {
						topSelect.value = bottomSelect.value;
					}
					if (snksRochtahMeetBulkConfirm(poolForm)) {
						poolForm.submit();
					}
				});
			}

			var modal = document.getElementById('snks-rochtah-meet-details-modal');
			var body = document.getElementById('snks-rochtah-meet-details-body');
			var closeBtn = document.getElementById('snks-rochtah-meet-details-close');
			var detailsNonce = <?php echo wp_json_encode( wp_create_nonce( 'snks_rochtah_meet_booking_details' ) ); ?>;
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

			function closeDetails() {
				if (modal) {
					modal.style.display = 'none';
				}
			}
			if (closeBtn) {
				closeBtn.addEventListener('click', closeDetails);
			}
			if (modal) {
				modal.addEventListener('click', function(e) {
					if (e.target === modal) {
						closeDetails();
					}
				});
			}

			function escapeHtml(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function paramTable(params) {
				var html = '<table class="widefat striped"><thead><tr><th>Key</th><th>Raw</th><th>Sent</th></tr></thead><tbody>';
				Object.keys(params || {}).forEach(function(key) {
					var p = params[key] || {};
					var empty = p.empty ? ' style="color:#b32d2e;"' : '';
					html += '<tr' + empty + '><td><code>' + escapeHtml(key) + '</code></td><td>' +
						(p.raw === '' ? '<em>(empty)</em>' : escapeHtml(p.raw)) +
						'</td><td><code>' + escapeHtml(p.sent || '') + '</code></td></tr>';
				});
				html += '</tbody></table>';
				return html;
			}

			function renderDetails(data) {
				var wa = data.whatsapp || {};
				var doctor = wa.doctor || {};
				var patient = wa.patient || {};
				var debug = data.debug || {};
				var booking = data.booking || {};
				var html = '';
				html += '<p><strong>Booking #' + escapeHtml(booking.id || '') + '</strong> — ' + escapeHtml(booking.appointment_datetime || '') + ' — ' + escapeHtml(booking.status || '') + '</p>';
				html += '<h3>WhatsApp → doctor (rochtah_meet_doctor)</h3>';
				html += '<p>Template: <code>' + escapeHtml(doctor.template || '') + '</code> | To: ' + (doctor.to_found ? escapeHtml(doctor.to_masked) : '<strong style="color:#b32d2e;">missing</strong>') +
					' | Can send: ' + (doctor.can_send ? 'yes' : 'no') +
					' | Already sent: ' + (doctor.already_sent ? 'yes' : 'no') + '</p>';
				html += paramTable(doctor.params);
				html += '<h3>WhatsApp → patient (rochtah_meet_patient)</h3>';
				html += '<p>Template: <code>' + escapeHtml(patient.template || '') + '</code> | To: ' + (patient.to_found ? escapeHtml(patient.to_masked) : '<strong style="color:#b32d2e;">missing</strong>') +
					' | Can send: ' + (patient.can_send ? 'yes' : 'no') +
					' | Already sent: ' + (patient.already_sent ? 'yes' : 'no') + '</p>';
				html += paramTable(patient.params);
				html += '<h3>Debug</h3>';
				if (debug.issues && debug.issues.length) {
					html += '<p><strong>Issues:</strong> ' + escapeHtml(debug.issues.join(', ')) + '</p>';
				} else {
					html += '<p>No referral/diagnosis issues detected.</p>';
				}
				html += '<pre style="max-height:320px;overflow:auto;background:#1d2327;color:#f0f0f1;padding:12px;direction:ltr;text-align:left;">' +
					escapeHtml(JSON.stringify(debug, null, 2)) + '</pre>';
				body.innerHTML = html;
			}

			document.querySelectorAll('.snks-rochtah-meet-details').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var urlId = btn.getAttribute('data-url-id');
					if (!modal || !body) {
						return;
					}
					modal.style.display = 'block';
					body.innerHTML = '<p>Loading…</p>';
					var form = new FormData();
					form.append('action', 'snks_rochtah_meet_booking_details');
					form.append('nonce', detailsNonce);
					form.append('url_id', urlId);
					fetch(ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
						.then(function(res) { return res.json(); })
						.then(function(json) {
							if (!json || !json.success) {
								var msg = (json && json.data && json.data.message) ? json.data.message : 'Failed to load meeting details.';
								body.innerHTML = '<p style="color:#b32d2e;">' + escapeHtml(msg) + '</p>';
								return;
							}
							renderDetails(json.data);
						})
						.catch(function() {
							body.innerHTML = '<p style="color:#b32d2e;">Failed to load meeting details.</p>';
						});
				});
			});
		})();
		</script>
	</div>
	<?php
}

/**
 * AJAX: meeting details + WhatsApp preview for an assigned pool URL.
 *
 * @return void
 */
function snks_ajax_rochtah_meet_booking_details() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
	}
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'snks_rochtah_meet_booking_details' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$url_id = isset( $_POST['url_id'] ) ? absint( $_POST['url_id'] ) : 0;
	if ( ! function_exists( 'snks_rochtah_meet_get_meeting_details' ) ) {
		wp_send_json_error( array( 'message' => 'Helper not available' ), 500 );
	}

	$result = snks_rochtah_meet_get_meeting_details( $url_id );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_rochtah_meet_booking_details', 'snks_ajax_rochtah_meet_booking_details' );
