<?php
/**
 * Rochtah Doctor Dashboard
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Rochtah Doctor Dashboard
 */
function snks_rochtah_doctor_dashboard() {
	snks_load_ai_admin_styles();
	
	global $wpdb;
	$current_user = wp_get_current_user();
	
	// Check if user has Rochtah doctor capabilities
	if ( ! current_user_can( 'manage_rochtah' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}
	
	// Handle prescription actions
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'write_prescription' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'write_prescription' ) ) {
			$booking_id = intval( $_POST['booking_id'] );
			$prescription_text = sanitize_textarea_field( $_POST['prescription_text'] );
			$medications = sanitize_textarea_field( $_POST['medications'] );
			$dosage_instructions = sanitize_textarea_field( $_POST['dosage_instructions'] );
			$doctor_notes = sanitize_textarea_field( $_POST['doctor_notes'] );
			
			// Update the booking with prescription
			$wpdb->update(
				$wpdb->prefix . 'snks_rochtah_bookings',
				array(
					'prescription_text' => $prescription_text,
					'medications' => $medications,
					'dosage_instructions' => $dosage_instructions,
					'doctor_notes' => $doctor_notes,
					'prescribed_by' => $current_user->ID,
					'prescribed_at' => current_time( 'mysql' ),
					'status' => 'prescribed'
				),
				array( 'id' => $booking_id ),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
			
			echo '<div class="notice notice-success"><p>Prescription written successfully!</p></div>';
		}
	}
	
	// Handle appointment status updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_status' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_booking_status' ) ) {
			$booking_id = intval( $_POST['booking_id'] );
			$new_status = sanitize_text_field( $_POST['new_status'] );
			
			$wpdb->update(
				$wpdb->prefix . 'snks_rochtah_bookings',
				array( 'status' => $new_status ),
				array( 'id' => $booking_id ),
				array( '%s' ),
				array( '%d' )
			);
			
			echo '<div class="notice notice-success"><p>Booking status updated successfully!</p></div>';
		}
	}
	
	// Get Rochtah bookings
	$bookings = $wpdb->get_results( "
		SELECT rb.*, u.display_name as patient_name, u.user_email as patient_email,
		       t.display_name as therapist_name, d.name as diagnosis_name
		FROM {$wpdb->prefix}snks_rochtah_bookings rb
		LEFT JOIN {$wpdb->users} u ON rb.patient_id = u.ID
		LEFT JOIN {$wpdb->users} t ON rb.therapist_id = t.ID
		LEFT JOIN {$wpdb->prefix}snks_diagnoses d ON rb.diagnosis_id = d.id
		ORDER BY rb.booking_date DESC, rb.booking_time DESC
	" );
	
	// Get statistics
	$total_bookings = count( $bookings );
	$pending_bookings = count( array_filter( $bookings, function( $b ) { return $b->status === 'pending'; } ) );
	$confirmed_bookings = count( array_filter( $bookings, function( $b ) { return $b->status === 'confirmed'; } ) );
	$prescribed_bookings = count( array_filter( $bookings, function( $b ) { return $b->status === 'prescribed'; } ) );
	?>
	
	<div class="wrap">
		<h1>Rochtah Doctor Dashboard</h1>
		<p>Welcome, Dr. <?php echo esc_html( $current_user->display_name ); ?>! Manage your Rochtah consultations and prescriptions here.</p>
		
		<!-- Statistics -->
		<div class="card">
			<h2>Quick Statistics</h2>
			<div class="stats-grid">
				<div class="stat-item">
					<h3><?php echo $total_bookings; ?></h3>
					<p>Total Bookings</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $pending_bookings; ?></h3>
					<p>Pending</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $confirmed_bookings; ?></h3>
					<p>Confirmed</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $prescribed_bookings; ?></h3>
					<p>Prescribed</p>
				</div>
			</div>
		</div>
		
		<!-- Bookings Table -->
		<div class="card">
			<h2>Rochtah Bookings</h2>
			<?php if ( $bookings ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Date & Time</th>
							<th>Patient</th>
							<th>Email</th>
							<th>Referring Therapist</th>
							<th>Diagnosis</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bookings as $booking ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $booking->booking_date ); ?></strong><br>
									<?php echo esc_html( $booking->booking_time ); ?>
								</td>
								<td><?php echo esc_html( $booking->patient_name ); ?></td>
								<td><?php echo esc_html( $booking->patient_email ); ?></td>
								<td><?php echo esc_html( $booking->therapist_name ); ?></td>
								<td><?php echo esc_html( $booking->diagnosis_name ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $booking->status ); ?>">
										<?php echo esc_html( ucfirst( $booking->status ) ); ?>
									</span>
								</td>
								<td>
									<?php if ( $booking->status === 'confirmed' ) : ?>
										<button class="button button-primary button-small" 
												onclick="openPrescriptionModal(<?php echo $booking->id; ?>, '<?php echo esc_js( $booking->patient_name ); ?>')">
											Write Prescription
										</button>
									<?php elseif ( $booking->status === 'prescribed' ) : ?>
										<button class="button button-secondary button-small" 
												onclick="viewPrescription(<?php echo $booking->id; ?>)">
											View Prescription
										</button>
									<?php endif; ?>
									
									<button class="button button-small" 
											onclick="openStatusModal(<?php echo $booking->id; ?>, '<?php echo esc_js( $booking->status ); ?>')">
										Update Status
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No Rochtah bookings found.</p>
			<?php endif; ?>
		</div>
	</div>
	
	<!-- Prescription Modal -->
	<div id="prescriptionModal" class="modal" style="display: none;">
		<div class="modal-content">
			<span class="close">&times;</span>
			<h2>Write Prescription</h2>
			<form method="post" id="prescriptionForm">
				<?php wp_nonce_field( 'write_prescription' ); ?>
				<input type="hidden" name="action" value="write_prescription">
				<input type="hidden" name="booking_id" id="booking_id">
				
				<table class="form-table">
					<tr>
						<th><label for="prescription_text">Prescription Text</label></th>
						<td>
							<textarea name="prescription_text" id="prescription_text" rows="4" class="large-text" required></textarea>
							<p class="description">Detailed prescription information</p>
						</td>
					</tr>
					<tr>
						<th><label for="medications">Medications</label></th>
						<td>
							<textarea name="medications" id="medications" rows="3" class="large-text" required></textarea>
							<p class="description">List of prescribed medications</p>
						</td>
					</tr>
					<tr>
						<th><label for="dosage_instructions">Dosage Instructions</label></th>
						<td>
							<textarea name="dosage_instructions" id="dosage_instructions" rows="3" class="large-text" required></textarea>
							<p class="description">How and when to take medications</p>
						</td>
					</tr>
					<tr>
						<th><label for="doctor_notes">Doctor Notes</label></th>
						<td>
							<textarea name="doctor_notes" id="doctor_notes" rows="3" class="large-text"></textarea>
							<p class="description">Additional notes for the patient</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Save Prescription' ); ?>
			</form>
		</div>
	</div>
	
	<!-- Status Update Modal -->
	<div id="statusModal" class="modal" style="display: none;">
		<div class="modal-content">
			<span class="close">&times;</span>
			<h2>Update Booking Status</h2>
			<form method="post" id="statusForm">
				<?php wp_nonce_field( 'update_booking_status' ); ?>
				<input type="hidden" name="action" value="update_status">
				<input type="hidden" name="booking_id" id="status_booking_id">
				
				<table class="form-table">
					<tr>
						<th><label for="new_status">New Status</label></th>
						<td>
							<select name="new_status" id="new_status" required>
								<option value="pending">Pending</option>
								<option value="confirmed">Confirmed</option>
								<option value="completed">Completed</option>
								<option value="cancelled">Cancelled</option>
								<option value="prescribed">Prescribed</option>
							</select>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Update Status' ); ?>
			</form>
		</div>
	</div>
	
	<style>
	.modal {
		display: none;
		position: fixed;
		z-index: 1000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0,0,0,0.4);
	}
	
	.modal-content {
		background-color: #fefefe;
		margin: 5% auto;
		padding: 20px;
		border: 1px solid #888;
		width: 80%;
		max-width: 600px;
		border-radius: 8px;
	}
	
	.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		cursor: pointer;
	}
	
	.close:hover {
		color: black;
	}
	
	.status-pending { color: #ffc107; font-weight: bold; }
	.status-confirmed { color: #007cba; font-weight: bold; }
	.status-completed { color: #28a745; font-weight: bold; }
	.status-cancelled { color: #dc3545; font-weight: bold; }
	.status-prescribed { color: #6f42c1; font-weight: bold; }
	</style>
	
	<script>
	function openPrescriptionModal(bookingId, patientName) {
		document.getElementById('booking_id').value = bookingId;
		document.getElementById('prescriptionModal').style.display = 'block';
		document.querySelector('#prescriptionModal h2').textContent = 'Write Prescription for ' + patientName;
	}
	
	function openStatusModal(bookingId, currentStatus) {
		document.getElementById('status_booking_id').value = bookingId;
		document.getElementById('new_status').value = currentStatus;
		document.getElementById('statusModal').style.display = 'block';
	}
	
	function viewPrescription(bookingId) {
		// This would open a modal to view the prescription
		alert('View prescription for booking ' + bookingId);
	}
	
	// Close modals when clicking X or outside
	document.querySelectorAll('.close').forEach(function(closeBtn) {
		closeBtn.onclick = function() {
			document.querySelectorAll('.modal').forEach(function(modal) {
				modal.style.display = 'none';
			});
		}
	});
	
	window.onclick = function(event) {
		document.querySelectorAll('.modal').forEach(function(modal) {
			if (event.target == modal) {
				modal.style.display = 'none';
			}
		});
	}
	</script>
	
	<?php
}

/**
 * Rochtah Doctor Management Page
 */
function snks_rochtah_doctor_management() {
	snks_load_ai_admin_styles();
	
	// Check if user has admin capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}
	
	// Handle role assignments
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'assign_rochtah_role' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'assign_rochtah_role' ) ) {
			$user_id = intval( $_POST['user_id'] );
			$user = get_user_by( 'ID', $user_id );
			
			if ( $user ) {
				$user->add_role( 'rochtah_doctor' );
				echo '<div class="notice notice-success"><p>Rochtah Doctor role assigned to ' . esc_html( $user->display_name ) . '</p></div>';
			}
		}
	}
	
	// Handle role removal
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'remove_rochtah_role' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'remove_rochtah_role' ) ) {
			$user_id = intval( $_POST['user_id'] );
			$user = get_user_by( 'ID', $user_id );
			
			if ( $user ) {
				$user->remove_role( 'rochtah_doctor' );
				echo '<div class="notice notice-success"><p>Rochtah Doctor role removed from ' . esc_html( $user->display_name ) . '</p></div>';
			}
		}
	}
	
	// Get all users
	$all_users = get_users( array( 'orderby' => 'display_name' ) );
	
	// Get Rochtah doctors
	$rochtah_doctors = get_users( array( 
		'role' => 'rochtah_doctor',
		'orderby' => 'display_name'
	) );
	
	// Get users who are not Rochtah doctors
	$non_rochtah_doctors = array_filter( $all_users, function( $user ) {
		return ! in_array( 'rochtah_doctor', $user->roles );
	} );
	?>
	
	<div class="wrap">
		<h1>Manage Rochtah Doctors</h1>
		<p>Assign or remove the Rochtah Doctor role from users. Rochtah Doctors can manage prescriptions and appointments.</p>
		
		<!-- Current Rochtah Doctors -->
		<div class="card">
			<h2>Current Rochtah Doctors</h2>
			<?php if ( $rochtah_doctors ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Username</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rochtah_doctors as $doctor ) : ?>
							<tr>
								<td><?php echo esc_html( $doctor->display_name ); ?></td>
								<td><?php echo esc_html( $doctor->user_email ); ?></td>
								<td><?php echo esc_html( $doctor->user_login ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'remove_rochtah_role' ); ?>
										<input type="hidden" name="action" value="remove_rochtah_role">
										<input type="hidden" name="user_id" value="<?php echo $doctor->ID; ?>">
										<button type="submit" class="button button-small button-link-delete" 
												onclick="return confirm('Are you sure you want to remove the Rochtah Doctor role from this user?')">
											Remove Role
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No Rochtah Doctors assigned yet.</p>
			<?php endif; ?>
		</div>
		
		<!-- Assign New Rochtah Doctor -->
		<div class="card">
			<h2>Assign Rochtah Doctor Role</h2>
			<form method="post">
				<?php wp_nonce_field( 'assign_rochtah_role' ); ?>
				<input type="hidden" name="action" value="assign_rochtah_role">
				
				<table class="form-table">
					<tr>
						<th><label for="user_id">Select User</label></th>
						<td>
							<select name="user_id" id="user_id" required>
								<option value="">Choose a user...</option>
								<?php foreach ( $non_rochtah_doctors as $user ) : ?>
									<option value="<?php echo $user->ID; ?>">
										<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">Select a user to assign the Rochtah Doctor role</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Assign Rochtah Doctor Role' ); ?>
			</form>
		</div>
		
		<!-- Role Information -->
		<div class="card">
			<h2>Rochtah Doctor Role Information</h2>
			<p><strong>Capabilities:</strong></p>
			<ul>
				<li>Manage Rochtah appointments and bookings</li>
				<li>Write and edit prescriptions</li>
				<li>View patient information</li>
				<li>Update booking status</li>
				<li>Upload prescription files</li>
			</ul>
			
			<p><strong>Access:</strong></p>
			<ul>
				<li>Rochtah Doctor Dashboard</li>
				<li>Patient booking management</li>
				<li>Prescription writing interface</li>
			</ul>
		</div>
	</div>
	
	<?php
} 