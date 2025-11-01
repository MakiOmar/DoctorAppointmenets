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

	// Handle form submissions
	if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['rochtah_slots_manager_nonce'], 'rochtah_slots_manager_action' ) ) {
		if ( $_POST['action'] === 'save_day_template' ) {
			$day = sanitize_text_field( $_POST['day'] );
			$slots_json = sanitize_textarea_field( $_POST['slots_json'] );
			
			// Save day template
			$day_templates = get_option( 'snks_rochtah_day_templates', array() );
			$day_templates[ $day ] = json_decode( $slots_json, true );
			update_option( 'snks_rochtah_day_templates', $day_templates );
			
			echo '<div class="notice notice-success"><p>Day template saved successfully!</p></div>';
		} elseif ( $_POST['action'] === 'publish_slots_manual' ) {
			// Manual publish - publish next 3 weeks
			$published = snks_publish_rochtah_slots_from_templates();
			if ( $published ) {
				echo '<div class="notice notice-success"><p>Slots published successfully! Published ' . $published . ' slots.</p></div>';
				update_option( 'snks_rochtah_last_publish_date', current_time( 'Y-m-d' ) );
			} else {
				echo '<div class="notice notice-error"><p>No slots were published. Please check your day templates.</p></div>';
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

	$days = array(
		'Monday' => 'Monday',
		'Tuesday' => 'Tuesday',
		'Wednesday' => 'Wednesday',
		'Thursday' => 'Thursday',
		'Friday' => 'Friday',
		'Saturday' => 'Saturday',
		'Sunday' => 'Sunday'
	);

	// Generate time slots (20-minute intervals from 8:00 AM to 8:00 PM)
	$time_slots = array();
	for ( $hour = 8; $hour < 20; $hour++ ) {
		for ( $minute = 0; $minute < 60; $minute += 20 ) {
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

	// Check if should publish (3 weeks = 21 days)
	$should_publish = ( $days_since_publish >= 21 );

	// Get published slots count
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$published_slots_count = $wpdb->get_var(
		"SELECT COUNT(*) FROM $rochtah_appointments_table 
		WHERE is_template = 0 AND slot_date >= CURDATE() AND status = 'active'"
	);

	?>
	<div class="wrap">
		<h1>Rochtah Slots Manager</h1>

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
				<p style="color: #28a745;"><strong>✓ OK:</strong> Next publish in <?php echo esc_html( 21 - $days_since_publish ); ?> days.</p>
			<?php endif; ?>

			<form method="post" style="margin-top: 15px;">
				<?php wp_nonce_field( 'rochtah_slots_manager_action', 'rochtah_slots_manager_nonce' ); ?>
				<input type="hidden" name="action" value="publish_slots_manual">
				<?php submit_button( 'Publish Slots Now (Next 3 Weeks)', 'primary', '', false ); ?>
			</form>
		</div>

		<!-- Day Templates -->
		<div class="card" style="margin-top: 20px;">
			<h2>Day Template Slots</h2>
			<p class="description">Configure time slots for each day of the week. These templates will be used to automatically publish slots every 3 weeks.</p>
			
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
			$published_slots = $wpdb->get_results(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE is_template = 0 AND slot_date >= CURDATE() AND slot_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
				ORDER BY slot_date ASC, start_time ASC"
			);
			?>
			
			<?php if ( $published_slots ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Day</th>
							<th>Time</th>
							<th>Status</th>
							<th>Bookings</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $published_slots as $slot ) : ?>
							<tr>
								<td><?php echo esc_html( $slot->slot_date ); ?></td>
								<td><?php echo esc_html( $slot->day_of_week ); ?></td>
								<td><?php echo esc_html( date( 'g:i A', strtotime( $slot->start_time ) ) ); ?> - <?php echo esc_html( date( 'g:i A', strtotime( $slot->end_time ) ) ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $slot->status ); ?>">
										<?php echo esc_html( ucfirst( $slot->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $slot->current_bookings ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
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
				end_time: '08:20'
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
			let minutes = parseInt(timeParts[1]) + 20;
			
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
 * Publish rochtah slots from day templates
 * Creates slots for the next 3 weeks based on day templates
 */
function snks_publish_rochtah_slots_from_templates() {
	global $wpdb;
	
	$day_templates = get_option( 'snks_rochtah_day_templates', array() );
	
	if ( empty( $day_templates ) ) {
		return false;
	}

	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$slots_published = 0;
	
	// Publish slots for the next 3 weeks (21 days)
	$current_date = current_time( 'Y-m-d' );
	
	for ( $day_offset = 0; $day_offset < 21; $day_offset++ ) {
		$target_date = date( 'Y-m-d', strtotime( "+$day_offset days", strtotime( $current_date ) ) );
		$day_of_week = date( 'l', strtotime( $target_date ) );
		
		// Check if we have a template for this day
		if ( ! isset( $day_templates[ $day_of_week ] ) || empty( $day_templates[ $day_of_week ] ) ) {
			continue;
		}
		
		$template_slots = $day_templates[ $day_of_week ];
		
		// Check if slots already exist for this date
		$existing_slots = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $rochtah_appointments_table 
			WHERE slot_date = %s AND is_template = 0",
			$target_date
		) );
		
		// Skip if slots already exist for this date
		if ( $existing_slots > 0 ) {
			continue;
		}
		
		// Create slots for this date based on template
		foreach ( $template_slots as $template_slot ) {
			// Check if this specific slot already exists
			$slot_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $rochtah_appointments_table 
				WHERE slot_date = %s AND start_time = %s AND is_template = 0",
				$target_date,
				$template_slot['start_time']
			) );
			
			if ( $slot_exists > 0 ) {
				continue;
			}
			
			// Insert new slot
			$wpdb->insert(
				$rochtah_appointments_table,
				array(
					'day_of_week' => $day_of_week,
					'slot_date' => $target_date,
					'start_time' => $template_slot['start_time'],
					'end_time' => $template_slot['end_time'],
					'status' => 'active',
					'is_template' => 0
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d' )
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
	
	// Check if 3 weeks (21 days) have passed
	$last_publish_timestamp = strtotime( $last_publish_date );
	$current_timestamp = current_time( 'timestamp' );
	$days_since_publish = floor( ( $current_timestamp - $last_publish_timestamp ) / DAY_IN_SECONDS );
	
	if ( $days_since_publish >= 21 ) {
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
		'Rochtah Slots',
		'manage_options',
		'rochtah-slots-manager',
		'snks_rochtah_slots_manager'
	);
}
add_action( 'admin_menu', 'snks_add_rochtah_slots_manager_menu', 20 );

