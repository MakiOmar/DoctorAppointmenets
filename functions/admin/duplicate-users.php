<?php
/**
 * Duplicate Users Scanner
 *
 * Adds a page under the core Users menu to scan for users that share
 * duplicated data (username, email, WhatsApp, billing WhatsApp, billing phone),
 * using the same normalization logic as the AI registration/approval flows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register "Duplicate Users" page under the Users menu.
 */
function snks_register_duplicate_users_page() {
	add_users_page(
		__( 'Duplicate Users', 'anony-shrinks' ),
		__( 'Duplicate Users', 'anony-shrinks' ),
		'list_users',
		'snks-duplicate-users',
		'snks_duplicate_users_page'
	);
}
add_action( 'admin_menu', 'snks_register_duplicate_users_page' );

/**
 * Render Duplicate Users admin page.
 */
function snks_duplicate_users_page() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'anony-shrinks' ) );
	}

	$nonce = wp_create_nonce( 'snks_duplicate_users_scan' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Duplicate Users Scanner', 'anony-shrinks' ); ?></h1>

		<p style="max-width: 800px;">
			<?php esc_html_e( 'This tool scans all WordPress users and finds accounts that share duplicated data such as username, email, WhatsApp, or billing phone (after normalizing phone numbers to ignore country codes).', 'anony-shrinks' ); ?>
		</p>

		<div id="snks-duplicate-users-controls" style="margin: 20px 0;">
			<button
				id="snks-start-duplicate-scan"
				class="button button-primary"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
			>
				<?php esc_html_e( 'Start Duplicate Scan', 'anony-shrinks' ); ?>
			</button>
			<span id="snks-duplicate-scan-status" style="margin-left: 10px;"></span>
		</div>

		<div id="snks-duplicate-scan-progress" style="display:none; max-width: 600px; margin: 20px 0;">
			<div style="background:#f0f0f0;border-radius:4px;overflow:hidden;">
				<div id="snks-duplicate-progress-bar" style="background:#2271b1;height:20px;width:0%;transition:width 0.3s;"></div>
			</div>
			<p id="snks-duplicate-progress-text" style="margin-top:8px;"></p>
		</div>

		<div id="snks-duplicate-users-results" style="margin-top:30px;"></div>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var $startBtn   = $('#snks-start-duplicate-scan');
			var $status     = $('#snks-duplicate-scan-status');
			var $progress   = $('#snks-duplicate-scan-progress');
			var $progressBar= $('#snks-duplicate-progress-bar');
			var $progressTx = $('#snks-duplicate-progress-text');
			var $results    = $('#snks-duplicate-users-results');

			var scanInProgress = false;
			var totalUsers = 0;

			function processQueue() {
				if (!scanInProgress) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'snks_process_duplicate_users_queue',
						nonce: $startBtn.data('nonce')
					},
					success: function(response) {
						if (!response || !response.success) {
							scanInProgress = false;
							$status.text(response && response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Error while scanning.', 'anony-shrinks' ) ); ?>').css('color', '#d63638');
							return;
						}

						var data = response.data;
						totalUsers = data.total || totalUsers;

						var processed  = data.processed || 0;
						var percentage = totalUsers > 0 ? Math.round((processed / totalUsers) * 100) : 0;

						$progressBar.css('width', percentage + '%');
						$progressTx.text(data.message || '');

						if (data.completed) {
							scanInProgress = false;
							$status.text('<?php echo esc_js( __( 'Scan completed.', 'anony-shrinks' ) ); ?>').css('color', '#00a32a');
							$startBtn.prop('disabled', false);

							if (data.html) {
								$results.html(data.html);
							} else {
								$results.html('<p><?php echo esc_js( __( 'No duplicates were found.', 'anony-shrinks' ) ); ?></p>');
							}
						} else {
							setTimeout(processQueue, 600);
						}
					},
					error: function() {
						scanInProgress = false;
						$status.text('<?php echo esc_js( __( 'Network error while scanning.', 'anony-shrinks' ) ); ?>').css('color', '#d63638');
						$startBtn.prop('disabled', false);
					}
				});
			}

			$startBtn.on('click', function(e) {
				e.preventDefault();
				if (scanInProgress) {
					return;
				}

				$results.empty();
				$status.text('<?php echo esc_js( __( 'Initializing scan...', 'anony-shrinks' ) ); ?>').css('color', '');
				$progress.show();
				$progressBar.css('width', '0%');
				$progressTx.text('');
				$startBtn.prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'snks_start_duplicate_users_scan',
						nonce: $startBtn.data('nonce')
					},
					success: function(response) {
						if (!response || !response.success) {
							$status.text(response && response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Failed to start scan.', 'anony-shrinks' ) ); ?>').css('color', '#d63638');
							$startBtn.prop('disabled', false);
							return;
						}

						totalUsers    = response.data.total || 0;
						scanInProgress = true;
						$status.text('<?php echo esc_js( __( 'Scan running...', 'anony-shrinks' ) ); ?>').css('color', '');
						processQueue();
					},
					error: function() {
						$status.text('<?php echo esc_js( __( 'Network error while starting scan.', 'anony-shrinks' ) ); ?>').css('color', '#d63638');
						$startBtn.prop('disabled', false);
					}
				});
			});
		});
	</script>
	<?php
}

/**
 * Initialize duplicate users scan queue.
 */
function snks_start_duplicate_users_scan() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to run this scan.', 'anony-shrinks' ),
			)
		);
	}

	check_ajax_referer( 'snks_duplicate_users_scan', 'nonce' );

	// Get all user IDs
	$user_ids = get_users(
		array(
			'fields' => 'ID',
		)
	);

	if ( empty( $user_ids ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'No users found to scan.', 'anony-shrinks' ),
			)
		);
	}

	$queue_key = 'snks_duplicate_users_queue';

	set_transient(
		$queue_key,
		array(
			'pending'   => array_map( 'intval', $user_ids ),
			'processed' => 0,
			'total'     => count( $user_ids ),
			// Maps for finding duplicates
			'maps'      => array(
				'username'         => array(),
				'email'            => array(),
				'whatsapp'         => array(),
				'billing_whatsapp' => array(),
				'billing_phone'    => array(),
			),
		),
		HOUR_IN_SECONDS
	);

	wp_send_json_success(
		array(
			'message' => __( 'Scan initialized.', 'anony-shrinks' ),
			'total'   => count( $user_ids ),
		)
	);
}
add_action( 'wp_ajax_snks_start_duplicate_users_scan', 'snks_start_duplicate_users_scan' );

/**
 * Process duplicate users scan queue in batches.
 */
function snks_process_duplicate_users_queue() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to run this scan.', 'anony-shrinks' ),
			)
		);
	}

	check_ajax_referer( 'snks_duplicate_users_scan', 'nonce' );

	$queue_key = 'snks_duplicate_users_queue';
	$state     = get_transient( $queue_key );

	if ( empty( $state ) || empty( $state['pending'] ) ) {
		// Nothing to process
		wp_send_json_success(
			array(
				'completed' => true,
				'total'     => isset( $state['total'] ) ? (int) $state['total'] : 0,
				'processed' => isset( $state['processed'] ) ? (int) $state['processed'] : 0,
				'html'      => snks_render_duplicate_users_results_from_maps( isset( $state['maps'] ) ? $state['maps'] : array() ),
				'message'   => __( 'Scan completed.', 'anony-shrinks' ),
			)
		);
	}

	$pending   = isset( $state['pending'] ) ? (array) $state['pending'] : array();
	$processed = isset( $state['processed'] ) ? (int) $state['processed'] : 0;
	$total     = isset( $state['total'] ) ? (int) $state['total'] : 0;
	$maps      = isset( $state['maps'] ) ? $state['maps'] : array();

	$batch_size = 100;
	$batch_ids  = array_splice( $pending, 0, $batch_size );

	if ( ! function_exists( 'snks_normalize_phone_for_comparison' ) ) {
		require_once SNKS_DIR . 'functions/helpers/accounts.php';
	}

	foreach ( $batch_ids as $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			continue;
		}

		// Username and email
		$username = $user->user_login;
		$email    = $user->user_email;

		if ( ! empty( $username ) ) {
			$key = strtolower( $username );
			if ( ! isset( $maps['username'][ $key ] ) ) {
				$maps['username'][ $key ] = array();
			}
			$maps['username'][ $key ][] = $user_id;
		}

		if ( ! empty( $email ) ) {
			$key = strtolower( $email );
			if ( ! isset( $maps['email'][ $key ] ) ) {
				$maps['email'][ $key ] = array();
			}
			$maps['email'][ $key ][] = $user_id;
		}

		// Phone-like fields
		$whatsapp          = get_user_meta( $user_id, 'whatsapp', true );
		$billing_whatsapp  = get_user_meta( $user_id, 'billing_whatsapp', true );
		$billing_phone_raw = get_user_meta( $user_id, 'billing_phone', true );

		if ( ! empty( $whatsapp ) ) {
			$normalized = snks_normalize_phone_for_comparison( $whatsapp );
			if ( $normalized ) {
				if ( ! isset( $maps['whatsapp'][ $normalized ] ) ) {
					$maps['whatsapp'][ $normalized ] = array();
				}
				$maps['whatsapp'][ $normalized ][] = $user_id;
			}
		}

		if ( ! empty( $billing_whatsapp ) ) {
			$normalized = snks_normalize_phone_for_comparison( $billing_whatsapp );
			if ( $normalized ) {
				if ( ! isset( $maps['billing_whatsapp'][ $normalized ] ) ) {
					$maps['billing_whatsapp'][ $normalized ] = array();
				}
				$maps['billing_whatsapp'][ $normalized ][] = $user_id;
			}
		}

		if ( ! empty( $billing_phone_raw ) ) {
			$normalized = snks_normalize_phone_for_comparison( $billing_phone_raw );
			if ( $normalized ) {
				if ( ! isset( $maps['billing_phone'][ $normalized ] ) ) {
					$maps['billing_phone'][ $normalized ] = array();
				}
				$maps['billing_phone'][ $normalized ][] = $user_id;
			}
		}

		$processed++;
	}

	// Update state
	$state['pending']   = $pending;
	$state['processed'] = $processed;
	$state['maps']      = $maps;

	set_transient( $queue_key, $state, HOUR_IN_SECONDS );

	$completed = empty( $pending );

	wp_send_json_success(
		array(
			'completed' => $completed,
			'total'     => $total,
			'processed' => $processed,
			'message'   => $completed ? __( 'Scan completed.', 'anony-shrinks' ) : sprintf(
				/* translators: 1: processed, 2: total */
				__( 'Processed %1$d of %2$d users...', 'anony-shrinks' ),
				$processed,
				$total
			),
			'html'      => $completed ? snks_render_duplicate_users_results_from_maps( $maps ) : '',
		)
	);
}
add_action( 'wp_ajax_snks_process_duplicate_users_queue', 'snks_process_duplicate_users_queue' );

/**
 * Render HTML for duplicate users results based on collected maps.
 *
 * @param array $maps Aggregated maps of values to user IDs.
 * @return string HTML output.
 */
function snks_render_duplicate_users_results_from_maps( $maps ) {
	if ( empty( $maps ) || ! is_array( $maps ) ) {
		return '<p>' . esc_html__( 'No duplicates were found.', 'anony-shrinks' ) . '</p>';
	}

	$sections = array(
		'username'         => __( 'Duplicate Usernames', 'anony-shrinks' ),
		'email'            => __( 'Duplicate Emails', 'anony-shrinks' ),
		'whatsapp'         => __( 'Duplicate WhatsApp Numbers (normalized)', 'anony-shrinks' ),
		'billing_whatsapp' => __( 'Duplicate Billing WhatsApp Numbers (normalized)', 'anony-shrinks' ),
		'billing_phone'    => __( 'Duplicate Billing Phone Numbers (normalized)', 'anony-shrinks' ),
	);

	$has_duplicates = false;
	ob_start();

	foreach ( $sections as $key => $label ) {
		if ( empty( $maps[ $key ] ) || ! is_array( $maps[ $key ] ) ) {
			continue;
		}

		$groups = array_filter(
			$maps[ $key ],
			static function ( $user_ids ) {
				return is_array( $user_ids ) && count( $user_ids ) > 1;
			}
		);

		if ( empty( $groups ) ) {
			continue;
		}

		$has_duplicates = true;
		?>
		<div class="card" style="margin-bottom: 20px; max-width: 900px;">
			<h2><?php echo esc_html( $label ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Value', 'anony-shrinks' ); ?></th>
						<th><?php esc_html_e( 'Users', 'anony-shrinks' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $groups as $value => $user_ids ) : ?>
					<tr>
						<td><code><?php echo esc_html( $value ); ?></code></td>
						<td>
							<?php
							$links = array();
							foreach ( $user_ids as $uid ) {
								$user = get_userdata( $uid );
								if ( ! $user ) {
									continue;
								}
								$edit_link = get_edit_user_link( $uid );
								$links[]   = sprintf(
									'<a href="%s" target="_blank">%s (ID #%d)</a>',
									esc_url( $edit_link ),
									esc_html( $user->user_login ),
									(int) $uid
								);
							}
							echo implode( '<br>', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	if ( ! $has_duplicates ) {
		return '<p>' . esc_html__( 'No duplicates were found.', 'anony-shrinks' ) . '</p>';
	}

	return ob_get_clean();
}


