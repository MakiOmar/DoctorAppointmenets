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

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'URL', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Status', 'shrinks' ); ?></th>
					<th><?php esc_html_e( 'Assigned booking', 'shrinks' ); ?></th>
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
	</div>
	<?php
}
