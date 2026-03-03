<?php
/**
 * Rochtah Slots Manager
 * Manages day template slots and automatic slot publishing
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Rochtah Slots Manager Admin Page
 */
function snks_rochtah_slots_manager() {
	global $wpdb;
	$current_user = wp_get_current_user();

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_rochtah' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}

	// Load unified Jalsah AI admin styles if available
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	// Toggle slot status (activate/deactivate)
	if (
		isset( $_GET['action'], $_GET['slot_id'] )
		&& in_array( $_GET['action'], array( 'deactivate_slot', 'activate_slot' ), true )
	) {
		$slot_id = intval( $_GET['slot_id'] );
		$action  = sanitize_key( $_GET['action'] );

		if ( $slot_id > 0 && wp_verify_nonce( $_GET['_wpnonce'], $action . '_' . $slot_id ) ) {

			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';

			// Safety: do not deactivate booked slots
			if ( $action === 'deactivate_slot' ) {
				$current_bookings = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT current_bookings FROM $rochtah_appointments_table WHERE id = %d",
						$slot_id
					)
				);

				if ( intval( $current_bookings ) > 0 ) {
					wp_safe_redirect( admin_url( 'admin.php?page=rochtah-slots-manager&toggle_error=booked' ) );
					exit;
				}
			}

			$new_status = ( $action === 'deactivate_slot' ) ? 'inactive' : 'active';

			$updated = $wpdb->update(
				$rochtah_appointments_table,
				array( 'status' => $new_status ),
				array( 'id' => $slot_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				wp_safe_redirect(
					admin_url( 'admin.php?page=rochtah-slots-manager&toggled=' . $new_status )
				);
				exit;
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=rochtah-slots-manager&toggle_error=invalid' ) );
		exit;
	}

	// Handle Rochtah general settings (Enable Rochtah, payment, price, publish window)
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_rochtah_settings' && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'update_rochtah_settings' ) ) {
		update_option( 'snks_ai_rochtah_enabled', isset( $_POST['enabled'] ) ? '1' : '0' );
		update_option( 'snks_rochtah_payment_enabled', isset( $_POST['rochtah_payment_enabled'] ) ? '1' : '0' );
		$rochtah_price = isset( $_POST['rochtah_price'] ) ? floatval( $_POST['rochtah_price'] ) : 0;
		update_option( 'snks_rochtah_price', $rochtah_price );
		$publish_weeks = isset( $_POST['rochtah_publish_weeks'] ) ? intval( $_POST['rochtah_publish_weeks'] ) : 3;
		if ( $publish_weeks <= 0 ) {
			$publish_weeks = 1;
		}
		if ( $publish_weeks > 12 ) {
			$publish_weeks = 12;
		}
		update_option( 'snks_rochtah_publish_weeks', $publish_weeks );
		echo '<div class="notice notice-success is-dismissible"><p>Rochtah settings updated successfully.</p></div>';
	}

	// Handle form submissions (day templates, publish, bulk status updates)
	if ( isset( $_POST['action'] ) && ! empty( $_POST['rochtah_slots_manager_nonce'] ) && wp_verify_nonce( $_POST['rochtah_slots_manager_nonce'], 'rochtah_slots_manager_action' ) ) {
		if ( $_POST['action'] === 'save_day_template' ) {
			$day = sanitize_text_field( $_POST['day'] );
			// Use wp_unslash on raw POST to avoid breaking JSON with added slashes; do not over-sanitize structured JSON
			$slots_json_raw = isset( $_POST['slots_json'] ) ? wp_unslash( $_POST['slots_json'] ) : '[]';
			
			// Decode slots
			$slots = json_decode( $slots_json_raw, true );
			$original_count = is_array( $slots ) ? count( $slots ) : 0;
			
			// Remove duplicate slots (same start_time and end_time)
			if ( is_array( $slots ) && ! empty( $slots ) ) {
				$unique_slots = array();
				$seen_slots = array();
				
				foreach ( $slots as $slot ) {
					if ( ! isset( $slot['start_time'] ) || ! isset( $slot['end_time'] ) ) {
						continue;
					}
					
					// Create a unique key based on start_time and end_time
					$slot_key = $slot['start_time'] . '-' . $slot['end_time'];
					
					// Only add if we haven't seen this combination before
					if ( ! isset( $seen_slots[ $slot_key ] ) ) {
						$seen_slots[ $slot_key ] = true;
						$unique_slots[] = $slot;
					}
				}
				
				$slots = $unique_slots;
			} else {
				$slots = array();
			}
			
			// Count duplicates removed
			$duplicate_count = $original_count - ( is_array( $slots ) ? count( $slots ) : 0 );
			
			// Save day template
			$day_templates = get_option( 'snks_rochtah_day_templates', array() );
			$day_templates[ $day ] = $slots;
			update_option( 'snks_rochtah_day_templates', $day_templates );
			
			if ( $duplicate_count > 0 ) {
				echo '<div class="notice notice-success"><p>Day template saved successfully! Removed ' . $duplicate_count . ' duplicate slot(s).</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>Day template saved successfully!</p></div>';
			}
		} elseif ( $_POST['action'] === 'publish_slots_manual' ) {
			// Manual publish - publish next configured window
			$published = snks_publish_rochtah_slots_from_templates();
			if ( $published ) {
				echo '<div class="notice notice-success"><p>Slots published successfully! Published ' . $published . ' slots.</p></div>';
				update_option( 'snks_rochtah_last_publish_date', current_time( 'Y-m-d' ) );
			} else {
				echo '<div class="notice notice-error"><p>No slots were published. Please check your day templates.</p></div>';
			}
		} elseif ( $_POST['action'] === 'bulk_update_slots' ) {
			$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
			$slot_ids    = isset( $_POST['slot_ids'] ) ? (array) $_POST['slot_ids'] : array();

			if ( empty( $bulk_action ) || ! in_array( $bulk_action, array( 'activate', 'deactivate' ), true ) ) {
				echo '<div class="notice notice-error"><p>Please choose a valid bulk action.</p></div>';
			} elseif ( empty( $slot_ids ) ) {
				echo '<div class="notice notice-error"><p>Please select at least one slot.</p></div>';
			} else {
				global $wpdb;
				$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';

				$new_status      = ( 'activate' === $bulk_action ) ? 'active' : 'inactive';
				$updated_count   = 0;
				$skipped_booked  = 0;

				foreach ( $slot_ids as $raw_id ) {
					$slot_id = intval( $raw_id );
					if ( $slot_id <= 0 ) {
						continue;
					}

					// When deactivating, do not deactivate slots that already have bookings
					if ( 'inactive' === $new_status ) {
						$current_bookings = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT current_bookings FROM $rochtah_appointments_table WHERE id = %d",
								$slot_id
							)
						);

						if ( intval( $current_bookings ) > 0 ) {
							$skipped_booked++;
							continue;
						}
					}

					$updated = $wpdb->update(
						$rochtah_appointments_table,
						array( 'status' => $new_status ),
						array( 'id' => $slot_id ),
						array( '%s' ),
						array( '%d' )
					);

					if ( false !== $updated ) {
						$updated_count += (int) $updated;
					}
				}

				if ( $updated_count > 0 ) {
					echo '<div class="notice notice-success"><p>Bulk action completed. Updated ' . intval( $updated_count ) . ' slot(s).</p></div>';
				} else {
					echo '<div class="notice notice-warning"><p>No slots were updated.</p></div>';
				}

				if ( $skipped_booked > 0 ) {
					echo '<div class="notice notice-info"><p>Skipped ' . intval( $skipped_booked ) . ' slot(s) because they already have bookings.</p></div>';
				}
			}
		}
	}

	// Handle delete template
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_template' && isset( $_GET['day'] ) ) {
		if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_template_' . $_GET['day'] ) ) {
			$day = sanitize_text_field( $_GET['day'] );
			$day_templates = get_option( 'snks_rochtah_day_templates', array() );
			unset( $day_templates[ $day ] );
			update_option( 'snks_rochtah_day_templates', $day_templates );
			echo '<div class="notice notice-success"><p>Template deleted successfully!</p></div>';
		}
	}

	// Rochtah general settings (used by paid prescription and publish window)
	$rochtah_enabled = get_option( 'snks_ai_rochtah_enabled', '0' );
	$rochtah_payment_enabled = get_option( 'snks_rochtah_payment_enabled', '0' );
	$rochtah_price = get_option( 'snks_rochtah_price', 0 );
	$rochtah_price = is_numeric( $rochtah_price ) ? floatval( $rochtah_price ) : 0;
	$rochtah_publish_weeks = get_option( 'snks_rochtah_publish_weeks', 3 );
	$rochtah_publish_weeks = is_numeric( $rochtah_publish_weeks ) ? intval( $rochtah_publish_weeks ) : 3;
	if ( $rochtah_publish_weeks <= 0 ) {
		$rochtah_publish_weeks = 1;
	} elseif ( $rochtah_publish_weeks > 12 ) {
		$rochtah_publish_weeks = 12;
	}
	$rochtah_publish_days = snks_rochtah_get_publish_days();

	$days = array(
		'Monday' => 'Monday',
		'Tuesday' => 'Tuesday',
		'Wednesday' => 'Wednesday',
		'Thursday' => 'Thursday',
		'Friday' => 'Friday',
		'Saturday' => 'Saturday',
		'Sunday' => 'Sunday'
	);

	// Generate time slots (15-minute intervals from 8:00 AM to 8:00 PM)
	$time_slots = array();
	for ( $hour = 8; $hour < 20; $hour++ ) {
		for ( $minute = 0; $minute < 60; $minute += 15 ) {
			$time_slots[] = sprintf( '%02d:%02d', $hour, $minute );
		}
	}

	// Get existing day templates
	$day_templates = get_option( 'snks_rochtah_day_templates', array() );

	// Get last publish date
	$last_publish_date = get_option( 'snks_rochtah_last_publish_date', '' );
	$days_since_publish = 0;
	if ( $last_publish_date ) {
		$last_publish_timestamp = strtotime( $last_publish_date );
		$current_timestamp = current_time( 'timestamp' );
		$days_since_publish = floor( ( $current_timestamp - $last_publish_timestamp ) / DAY_IN_SECONDS );
	}

	// Check if should publish (based on configured window)
	$should_publish = ( $days_since_publish >= $rochtah_publish_days );

	// Get published slots count
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	// Check if is_template column exists
	$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $rochtah_appointments_table LIKE 'is_template'" );
	$is_template_condition = ! empty( $column_exists ) ? "is_template = 0 AND " : "";
	
	$published_slots_count = $wpdb->get_var(
		"SELECT COUNT(*) FROM $rochtah_appointments_table 
		WHERE $is_template_condition slot_date >= CURDATE() AND status = 'active'"
	);

	?>
	<div class="wrap">
		<h1>Rochtah Slots Manager</h1>
		<?php if ( isset( $_GET['toggled'] ) && $_GET['toggled'] === 'inactive' ) : ?>
			<div class="notice notice-success"><p>Slot deactivated (set inactive).</p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['toggled'] ) && $_GET['toggled'] === 'active' ) : ?>
			<div class="notice notice-success"><p>Slot restored (set active).</p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['toggle_error'] ) && $_GET['toggle_error'] === 'booked' ) : ?>
			<div class="notice notice-error"><p>Cannot deactivate a slot that has bookings.</p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['toggle_error'] ) && $_GET['toggle_error'] === 'invalid' ) : ?>
			<div class="notice notice-error"><p>Invalid toggle request.</p></div>
		<?php endif; ?>

		<!-- Rochtah General Settings (Enable, payment, price) -->
		<div class="card" style="margin-top: 20px;">
			<h2>Rochtah Settings</h2>
			<form method="post">
				<?php wp_nonce_field( 'update_rochtah_settings' ); ?>
				<input type="hidden" name="action" value="update_rochtah_settings">
				<table class="form-table">
					<tr>
						<th><label for="rochtah_enabled">Enable Rochtah</label></th>
						<td><input type="checkbox" id="rochtah_enabled" name="enabled" value="1" <?php checked( $rochtah_enabled, '1' ); ?>></td>
					</tr>
					<tr>
						<th><label for="rochtah_payment_enabled">Enable payment for Rochtah service</label></th>
						<td><input type="checkbox" id="rochtah_payment_enabled" name="rochtah_payment_enabled" value="1" <?php checked( $rochtah_payment_enabled, '1' ); ?>></td>
					</tr>
					<tr class="rochtah-price-row" style="<?php echo $rochtah_payment_enabled === '1' ? '' : 'display:none;'; ?>">
						<th><label for="rochtah_price">Rochtah consultation price (EGP)</label></th>
						<td><input type="number" id="rochtah_price" name="rochtah_price" value="<?php echo esc_attr( $rochtah_price ); ?>" min="0" step="0.01" class="small-text"></td>
					</tr>
					<tr>
						<th><label for="rochtah_publish_weeks">Publish window (weeks)</label></th>
						<td>
							<input type="number"
							       id="rochtah_publish_weeks"
							       name="rochtah_publish_weeks"
							       value="<?php echo esc_attr( $rochtah_publish_weeks ); ?>"
							       min="1"
							       max="12"
							       step="1"
							       class="small-text">
							<p class="description">
								How many weeks ahead to publish slots when running the publisher (manual or automatic).
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Rochtah Settings' ); ?>
			</form>
			<script>
			(function() {
				var cb = document.getElementById('rochtah_payment_enabled');
				var row = document.querySelector('.rochtah-price-row');
				if (cb && row) { cb.addEventListener('change', function() { row.style.display = this.checked ? '' : 'none'; }); }
			})();
			</script>
		</div>

		<!-- Publishing Status -->
		<div class="card" style="margin-top: 20px;">
			<h2>Publishing Status</h2>
			<?php if ( $last_publish_date ) : ?>
				<p><strong>Last Publish Date:</strong> <?php echo esc_html( $last_publish_date ); ?></p>
				<p><strong>Days Since Last Publish:</strong> <?php echo esc_html( $days_since_publish ); ?> days</p>
			<?php else : ?>
				<p><strong>Status:</strong> No slots have been published yet.</p>
			<?php endif; ?>
			
			<p><strong>Published Active Slots (Future):</strong> <?php echo esc_html( $published_slots_count ); ?></p>
			
			<?php if ( $should_publish ) : ?>
				<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0;">
					<p style="margin: 0;"><strong>⚠️ Ready to Publish:</strong> It's been <?php echo esc_html( $days_since_publish ); ?> days since last publish. You should publish new slots now!</p>
				</div>
			<?php else : ?>
				<?php $days_remaining = max( 0, $rochtah_publish_days - $days_since_publish ); ?>
				<p style="color: #28a745;">
					<strong>✓ OK:</strong>
					Next publish in
					<?php echo esc_html( $days_remaining ); ?>
					day<?php echo $days_remaining === 1 ? '' : 's'; ?>.
				</p>
			<?php endif; ?>

			<form method="post" style="margin-top: 15px;">
				<?php wp_nonce_field( 'rochtah_slots_manager_action', 'rochtah_slots_manager_nonce' ); ?>
				<input type="hidden" name="action" value="publish_slots_manual">
				<?php
				$publish_label_weeks = max( 1, intval( $rochtah_publish_weeks ) );
				$button_label        = sprintf(
					'Publish Slots Now (Next %d Week%s)',
					$publish_label_weeks,
					$publish_label_weeks === 1 ? '' : 's'
				);
				submit_button( $button_label, 'primary', '', false );
				?>
			</form>
		</div>

		<!-- Day Templates -->
		<div class="card" style="margin-top: 20px;">
			<h2>Day Template Slots</h2>
			<p class="description">
				Configure time slots for each day of the week. These templates will be used to automatically publish slots
				for the next <?php echo esc_html( $rochtah_publish_weeks ); ?> week<?php echo $rochtah_publish_weeks === 1 ? '' : 's'; ?>.
			</p>
			
			<?php foreach ( $days as $day_key => $day_name ) : 
				$day_template = isset( $day_templates[ $day_key ] ) ? $day_templates[ $day_key ] : array();
				?>
				<div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 4px;">
					<h3><?php echo esc_html( $day_name ); ?> Template</h3>
					
					<?php if ( ! empty( $day_template ) ) : ?>
						<p><strong>Current Slots:</strong></p>
						<ul>
							<?php foreach ( $day_template as $slot ) : ?>
								<li><?php echo esc_html( $slot['start_time'] ); ?> - <?php echo esc_html( $slot['end_time'] ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p>
							<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=rochtah-slots-manager&action=delete_template&day=' . $day_key ), 'delete_template_' . $day_key ); ?>" 
							   class="button button-small button-link-delete"
							   onclick="return confirm('Are you sure you want to delete this template?')">
								Delete Template
							</a>
						</p>
					<?php endif; ?>

					<button type="button" class="button button-secondary edit-template-btn" data-day="<?php echo esc_attr( $day_key ); ?>" data-day-name="<?php echo esc_attr( $day_name ); ?>">
						<?php echo empty( $day_template ) ? 'Add Template' : 'Edit Template'; ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Published Slots -->
		<div class="card" style="margin-top: 20px;">
			<h2>Published Slots (Next 30 Days)</h2>
			<?php
			// Use same is_template condition check
			$published_slots = $wpdb->get_results(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE $is_template_condition slot_date >= CURDATE() 
				AND slot_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
				AND status IN ('active','inactive')
				ORDER BY slot_date ASC, start_time ASC"
			);
			?>
			
			<?php if ( $published_slots ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'rochtah_slots_manager_action', 'rochtah_slots_manager_nonce' ); ?>
					<input type="hidden" name="action" value="bulk_update_slots">
					<div style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
						<select name="bulk_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'shrinks' ); ?></option>
							<option value="activate"><?php esc_html_e( 'Activate selected slots', 'shrinks' ); ?></option>
							<option value="deactivate"><?php esc_html_e( 'Deactivate selected slots', 'shrinks' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'shrinks' ), 'secondary', 'apply-bulk', false ); ?>
					</div>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width:30px;">
									<input type="checkbox" id="rochtah-select-all-slots">
								</th>
								<th>Date</th>
								<th>Day</th>
								<th>Time</th>
								<th>Status</th>
								<th>Bookings</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $published_slots as $slot ) : ?>
								<tr style="<?php echo ( $slot->status === 'inactive' ) ? 'opacity:0.55;' : ''; ?>">

									<td>
										<input type="checkbox" name="slot_ids[]" value="<?php echo esc_attr( intval( $slot->id ) ); ?>" class="rochtah-slot-checkbox">
									</td>
									<td><?php echo esc_html( $slot->slot_date ); ?></td>
									<td><?php echo esc_html( $slot->day_of_week ); ?></td>
									<td><?php echo esc_html( date( 'g:i A', strtotime( $slot->start_time ) ) ); ?> - <?php echo esc_html( date( 'g:i A', strtotime( $slot->end_time ) ) ); ?></td>
									<td>
										<span class="status-<?php echo esc_attr( $slot->status ); ?>">
											<?php echo esc_html( ucfirst( $slot->status ) ); ?>
										</span>
										<?php if ( $slot->status === 'inactive' ) : ?>
											<span style="margin-left:8px; padding:2px 6px; background:#eee; border-radius:10px; font-size:12px;">Hidden</span>
										<?php endif; ?>
									</td>

									<td><?php echo esc_html( $slot->current_bookings ); ?></td>
									<td>
										<?php
											$slot_id = intval( $slot->id );
											$is_inactive = ( $slot->status === 'inactive' );

											$toggle_action = $is_inactive ? 'activate_slot' : 'deactivate_slot';
											$toggle_label  = $is_inactive ? 'Restore' : 'Deactivate';

											$toggle_url = wp_nonce_url(
												admin_url( 'admin.php?page=rochtah-slots-manager&action=' . $toggle_action . '&slot_id=' . $slot_id ),
												$toggle_action . '_' . $slot_id
											);
										?>
										<a href="<?php echo esc_url( $toggle_url ); ?>"
										class="button button-small <?php echo $is_inactive ? 'button-primary' : 'button-secondary'; ?>"
										onclick="return confirm('<?php echo esc_js( $is_inactive ? 'Restore this slot (set active)?' : 'Deactivate this slot (set inactive)?' ); ?>');">
											<?php echo esc_html( $toggle_label ); ?>
										</a>
									</td>


								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
				<script>
				(function() {
					var selectAll = document.getElementById('rochtah-select-all-slots');
					if (!selectAll) return;
					selectAll.addEventListener('change', function() {
						var checkboxes = document.querySelectorAll('.rochtah-slot-checkbox');
						for (var i = 0; i < checkboxes.length; i++) {
							checkboxes[i].checked = selectAll.checked;
						}
					});
				})();
				</script>
			<?php else : ?>
				<p>No published slots found for the next 30 days.</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Template Editor Modal -->
	<div id="template-editor-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000; padding: 50px;">
		<div style="background: white; max-width: 800px; margin: 0 auto; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
			<h2 id="modal-day-name">Edit Template</h2>
			<form id="template-form" method="post">
				<?php wp_nonce_field( 'rochtah_slots_manager_action', 'rochtah_slots_manager_nonce' ); ?>
				<input type="hidden" name="action" value="save_day_template">
				<input type="hidden" name="day" id="modal-day">
				<input type="hidden" name="slots_json" id="slots-json">
				
				<div id="slots-container" style="margin: 20px 0;">
					<!-- Slots will be added here dynamically -->
				</div>
				
				<button type="button" id="add-slot-btn" class="button">Add Time Slot</button>
				
				<div style="margin-top: 20px;">
					<?php submit_button( 'Save Template', 'primary', '', false ); ?>
					<button type="button" id="cancel-modal-btn" class="button" style="margin-right: 10px;">Cancel</button>
				</div>
			</form>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		let currentSlots = [];
		let timeSlots = <?php echo wp_json_encode( $time_slots ); ?>;

		// Open template editor
		$('.edit-template-btn').on('click', function() {
			const day = $(this).data('day');
			const dayName = $(this).data('day-name');
			const existingTemplate = <?php echo wp_json_encode( $day_templates ); ?>;
			
			$('#modal-day').val(day);
			$('#modal-day-name').text(dayName + ' Template');
			
			// Load existing template or start fresh
			currentSlots = existingTemplate[day] || [];
			renderSlots();
			
			$('#template-editor-modal').show();
		});

		// Close modal
		$('#cancel-modal-btn').on('click', function() {
			$('#template-editor-modal').hide();
		});

		// Add slot
		$('#add-slot-btn').on('click', function() {
			currentSlots.push({
				start_time: '08:00',
				end_time: '08:15'
			});
			renderSlots();
		});

		// Remove slot
		$(document).on('click', '.remove-slot-btn', function() {
			const index = $(this).data('index');
			currentSlots.splice(index, 1);
			renderSlots();
		});

		// Update slot
		$(document).on('change', '.slot-start-time, .slot-end-time', function() {
			const index = $(this).data('index');
			const field = $(this).hasClass('slot-start-time') ? 'start_time' : 'end_time';
			currentSlots[index][field] = $(this).val();
		});

		// Render slots
		function renderSlots() {
			const container = $('#slots-container');
			container.empty();

			if (currentSlots.length === 0) {
				container.html('<p style="color: #666;">No slots added yet. Click "Add Time Slot" to add one.</p>');
			} else {
				currentSlots.forEach(function(slot, index) {
					const slotHtml = `
						<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
							<div>
								<label style="display: block; margin-bottom: 5px;"><strong>Start Time:</strong></label>
								<select class="slot-start-time" data-index="${index}" style="width: 150px;">
									${timeSlots.map(t => `<option value="${t}" ${slot.start_time === t ? 'selected' : ''}>${t}</option>`).join('')}
								</select>
							</div>
							<div>
								<label style="display: block; margin-bottom: 5px;"><strong>End Time:</strong></label>
								<select class="slot-end-time" data-index="${index}" style="width: 150px;">
									${timeSlots.map(t => `<option value="${t}" ${slot.end_time === t ? 'selected' : ''}>${t}</option>`).join('')}
								</select>
							</div>
							<button type="button" class="button remove-slot-btn" data-index="${index}" style="margin-top: 20px;">Remove</button>
						</div>
					`;
					container.append(slotHtml);
				});
			}

			// Update hidden JSON field
			$('#slots-json').val(JSON.stringify(currentSlots));
		}

		// Auto-calculate end time when start time changes
		$(document).on('change', '.slot-start-time', function() {
			const index = $(this).data('index');
			const startTime = $(this).val();
			const timeParts = startTime.split(':');
			let hours = parseInt(timeParts[0]);
			let minutes = parseInt(timeParts[1]) + 15;
			
			if (minutes >= 60) {
				hours += 1;
				minutes -= 60;
			}
			
			const endTime = sprintf('%02d:%02d', hours, minutes);
			if (timeSlots.includes(endTime)) {
				$(`.slot-end-time[data-index="${index}"]`).val(endTime);
				currentSlots[index].end_time = endTime;
				$('#slots-json').val(JSON.stringify(currentSlots));
			}
		});

		// Submit form
		$('#template-form').on('submit', function(e) {
			if (currentSlots.length === 0) {
				e.preventDefault();
				alert('Please add at least one time slot.');
				return false;
			}
			$('#slots-json').val(JSON.stringify(currentSlots));
		});
	});
	</script>
	<?php
}

/**
 * Get number of days to publish based on configured weeks.
 *
 * @return int
 */
function snks_rochtah_get_publish_days() {
	$publish_weeks = get_option( 'snks_rochtah_publish_weeks', 3 );
	$publish_weeks = is_numeric( $publish_weeks ) ? intval( $publish_weeks ) : 3;

	if ( $publish_weeks <= 0 ) {
		$publish_weeks = 1;
	} elseif ( $publish_weeks > 12 ) {
		$publish_weeks = 12;
	}

	return $publish_weeks * 7;
}

/**
 * Publish rochtah slots from day templates
 * Creates slots for the next N days (derived from configured weeks) based on day templates
 */
function snks_publish_rochtah_slots_from_templates() {
	global $wpdb;
	
	$day_templates = get_option( 'snks_rochtah_day_templates', array() );
	
	if ( empty( $day_templates ) ) {
		return false;
	}

	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	
	// Check if is_template column exists
	$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $rochtah_appointments_table LIKE 'is_template'" );
	$has_is_template_column = ! empty( $column_exists );
	$is_template_condition = $has_is_template_column ? "is_template = 0 AND " : "";
	
	$slots_published = 0;
	
	// Publish slots for the configured window (default 3 weeks)
	$publish_days  = snks_rochtah_get_publish_days();
	$current_date = current_time( 'Y-m-d' );
	
	for ( $day_offset = 0; $day_offset < $publish_days; $day_offset++ ) {
		$target_date = date( 'Y-m-d', strtotime( "+$day_offset days", strtotime( $current_date ) ) );
		$day_of_week = date( 'l', strtotime( $target_date ) );
		
		// Check if we have a template for this day
		if ( ! isset( $day_templates[ $day_of_week ] ) || empty( $day_templates[ $day_of_week ] ) ) {
			continue;
		}
		
		$template_slots = $day_templates[ $day_of_week ];
		
		
		// Create slots for this date based on template
		foreach ( $template_slots as $template_slot ) {
			// Check if this specific slot already exists
			$slot_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $rochtah_appointments_table 
				WHERE $is_template_condition slot_date = %s AND start_time = %s AND end_time = %s",
				$target_date,
				$template_slot['start_time'],
				$template_slot['end_time']
			) );

			
			if ( $slot_exists > 0 ) {
				continue;
			}
			
			// Prepare insert data
			$insert_data = array(
				'day_of_week' => $day_of_week,
				'slot_date' => $target_date,
				'start_time' => $template_slot['start_time'],
				'end_time' => $template_slot['end_time'],
				'status' => 'active'
			);
			$insert_format = array( '%s', '%s', '%s', '%s', '%s' );
			
			// Add is_template only if column exists
			if ( $has_is_template_column ) {
				$insert_data['is_template'] = 0;
				$insert_format[] = '%d';
			}
			
			// Insert new slot
			$wpdb->insert(
				$rochtah_appointments_table,
				$insert_data,
				$insert_format
			);
			
			if ( $wpdb->insert_id ) {
				$slots_published++;
			}
		}
	}
	
	return $slots_published;
}

/**
 * Check and auto-publish rochtah slots if needed
 * This function runs daily via cron
 */
function snks_check_and_publish_rochtah_slots() {
	$last_publish_date = get_option( 'snks_rochtah_last_publish_date', '' );
	
	// If never published, publish now
	if ( empty( $last_publish_date ) ) {
		$published = snks_publish_rochtah_slots_from_templates();
		if ( $published ) {
			update_option( 'snks_rochtah_last_publish_date', current_time( 'Y-m-d' ) );
		}
		return;
	}
	
	// Check if the configured window has passed since last publish
	$last_publish_timestamp = strtotime( $last_publish_date );
	$current_timestamp = current_time( 'timestamp' );
	$days_since_publish = floor( ( $current_timestamp - $last_publish_timestamp ) / DAY_IN_SECONDS );
	
	$publish_days = snks_rochtah_get_publish_days();
	if ( $days_since_publish >= $publish_days ) {
		$published = snks_publish_rochtah_slots_from_templates();
		if ( $published ) {
			update_option( 'snks_rochtah_last_publish_date', current_time( 'Y-m-d' ) );
		}
	}
}

/**
 * Schedule daily cron job to check and publish slots
 */
if ( ! wp_next_scheduled( 'snks_check_rochtah_slots_daily' ) ) {
	wp_schedule_event( time(), 'daily', 'snks_check_rochtah_slots_daily' );
}
add_action( 'snks_check_rochtah_slots_daily', 'snks_check_and_publish_rochtah_slots' );

/**
 * Add admin menu for Rochtah Slots Manager
 */
function snks_add_rochtah_slots_manager_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		'Rochtah Slots Manager',
		'Rochtah slots management',
		'manage_options',
		'rochtah-slots-manager',
		'snks_rochtah_slots_manager'
	);
}
add_action( 'admin_menu', 'snks_add_rochtah_slots_manager_menu', 20 );

