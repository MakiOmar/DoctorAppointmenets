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
	
	// Check if user has Rochtah doctor capabilities or is admin
	if ( ! current_user_can( 'manage_rochtah' ) && ! current_user_can( 'manage_options' ) ) {
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
	
	// Update therapist names using the helper function
	if ( function_exists( 'snks_get_therapist_name' ) ) {
		foreach ( $bookings as $booking ) {
			if ( ! empty( $booking->therapist_id ) ) {
				$booking->therapist_name = snks_get_therapist_name( $booking->therapist_id );
			}
		}
	}
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
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bookings as $booking ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $booking->booking_date ); ?></strong><br>
									<?php 
									// Convert time to 12-hour format
									if ( ! empty( $booking->booking_time ) ) {
										$time_24 = $booking->booking_time;
										// Handle formats like "14:30" or "14:30:00"
										if ( preg_match( '/^(\d{1,2}):(\d{2})/', $time_24, $matches ) ) {
											$hour = intval( $matches[1] );
											$minute = $matches[2];
											$period = ( $hour >= 12 ) ? 'PM' : 'AM';
											$hour_12 = ( $hour > 12 ) ? $hour - 12 : ( $hour == 0 ? 12 : $hour );
											$time_12 = sprintf( '%d:%s %s', $hour_12, $minute, $period );
											echo esc_html( $time_12 );
										} else {
											echo esc_html( $booking->booking_time );
										}
									} else {
										echo esc_html( $booking->booking_time );
									}
									?>
								</td>
								<td><?php echo esc_html( $booking->patient_name ); ?></td>
								<td><?php echo esc_html( $booking->patient_email ); ?></td>
								<td><?php echo esc_html( $booking->therapist_name ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $booking->status ); ?>">
										<?php echo esc_html( ucfirst( $booking->status ) ); ?>
									</span>
								</td>
								<td>
									<button class="button button-small toggle-actions" 
											onclick="toggleActions(<?php echo $booking->id; ?>)"
											id="toggle-btn-<?php echo $booking->id; ?>">
										⚙️ Actions
									</button>
									
									<div class="actions-container" id="actions-<?php echo $booking->id; ?>" style="display: none; margin-top: 10px;">
									<?php if ( $booking->status === 'confirmed' ) : ?>
										<button class="button button-primary button-small" 
													onclick="openPrescriptionModal(<?php echo $booking->id; ?>, '<?php echo esc_js( $booking->patient_name ); ?>')"
													style="margin: 2px;">
											Write Prescription
										</button>
											<br>
											<button class="button button-secondary button-small" 
													onclick="joinRochtahMeeting(<?php echo $booking->id; ?>)"
													style="background-color: #28a745; border-color: #28a745; color: white; margin: 2px;">
												🎥 Join Meeting
											</button>
											<br>
											<button class="button button-warning button-small" 
													onclick="resetRochtahBooking(<?php echo $booking->id; ?>)"
													style="background-color: #ff9800; border-color: #ff9800; color: white; margin: 2px;">
												🔄 Reset Booking
											</button>
											<br>
									<?php elseif ( $booking->status === 'prescribed' ) : ?>
										<button class="button button-secondary button-small" 
													onclick="viewPrescription(<?php echo $booking->id; ?>)"
													style="margin: 2px;">
											View Prescription
										</button>
											<br>
										<?php endif; ?>
										
										<?php if ( function_exists( 'snks_add_rochtah_referral_reason_button' ) ) : ?>
											<div style="margin: 2px;">
												<?php echo snks_add_rochtah_referral_reason_button( $booking ); ?>
											</div>
									<?php endif; ?>
									
									<button class="button button-small" 
												onclick="openStatusModal(<?php echo $booking->id; ?>, '<?php echo esc_js( $booking->status ); ?>')"
												style="margin: 2px;">
										Update Status
									</button>
									</div>
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
	
	<!-- Rochtah Meeting Modal -->
	<div id="rochtahMeetingModal" class="modal" style="display: none;">
		<div class="modal-content rochtah-meeting-modal">
			<span class="close">&times;</span>
			<h2>Rochtah Session - Join Meeting</h2>
			<div id="meetingDetails" style="margin-bottom: 15px; padding: 12px; background-color: #f0f6ff; border-radius: 6px; border: 1px solid #d6ebff;">
				<div id="sessionInfo"></div>
			</div>
			<div id="rochtah-meeting-container" style="width: 100%; height: 500px; background-color: #1a1a1a; border-radius: 8px; overflow: hidden; border: 2px solid #ddd; position: relative;">
				<div id="rochtah-doctor-meeting" style="width: 100%; height: 100%; min-height: 500px;"></div>
				<div id="jitsi-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center; display: none;">
					<div style="margin-bottom: 10px;">🔄 Loading Jitsi Meeting...</div>
					<div style="font-size: 14px; opacity: 0.8;">Please wait while we connect you to the meeting</div>
				</div>
			</div>
			<div style="margin-top: 15px; text-align: center;">
				<button id="startMeetingBtn" class="button button-primary" onclick="startRochtahDoctorMeeting()">
					🎥 Start Meeting
				</button>
				<button class="button" onclick="closeMeetingModal()">
					❌ Close
				</button>
			</div>
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
		position: relative;
		max-height: 90vh;
		overflow-y: auto;
	}
	
	.rochtah-meeting-modal {
		width: 95% !important;
		max-width: 1200px !important;
		margin: 2% auto !important;
		max-height: 95vh !important;
		padding: 15px !important;
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
	
	.actions-container {
		background-color: #f8f9fa;
		border: 1px solid #dee2e6;
		border-radius: 4px;
		padding: 10px;
		margin-top: 5px;
	}
	
	.toggle-actions {
		transition: all 0.3s ease;
	}
	
	.toggle-actions:hover {
		background-color: #0073aa !important;
		border-color: #0073aa !important;
		color: white !important;
	}
	
	.prescription-modal .modal-content,
	#prescriptionModal .modal-content {
		width: 90% !important;
		max-width: 700px !important;
		height: 80vh !important;
		max-height: 600px !important;
		margin: 2% auto !important;
		display: flex !important;
		flex-direction: column !important;
	}
	
	.prescription-modal-header,
	#prescriptionModal .prescription-modal-header {
		flex-shrink: 0 !important;
		padding-bottom: 15px !important;
		border-bottom: 1px solid #e5e7eb !important;
		margin-bottom: 20px !important;
	}
	
	.prescription-modal-body,
	#prescriptionModal .prescription-modal-body {
		flex: 1 !important;
		overflow-y: auto !important;
		padding-right: 10px !important;
		margin-right: -10px !important;
	}
	
	.prescription-modal-footer,
	#prescriptionModal .prescription-modal-footer {
		flex-shrink: 0 !important;
		padding-top: 20px !important;
		border-top: 1px solid #e5e7eb !important;
		margin-top: 20px !important;
		text-align: center !important;
	}
	
	/* Custom scrollbar for the modal body */
	.prescription-modal-body::-webkit-scrollbar,
	#prescriptionModal .prescription-modal-body::-webkit-scrollbar {
		width: 8px;
	}
	
	.prescription-modal-body::-webkit-scrollbar-track,
	#prescriptionModal .prescription-modal-body::-webkit-scrollbar-track {
		background: #f1f1f1;
		border-radius: 4px;
	}
	
	.prescription-modal-body::-webkit-scrollbar-thumb,
	#prescriptionModal .prescription-modal-body::-webkit-scrollbar-thumb {
		background: #c1c1c1;
		border-radius: 4px;
	}
	
	.prescription-modal-body::-webkit-scrollbar-thumb:hover,
	#prescriptionModal .prescription-modal-body::-webkit-scrollbar-thumb:hover {
		background: #a8a8a8;
	}
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
		// Show loading state
		showPrescriptionModal('View/Edit Prescription', '<div style="text-align: center; padding: 40px;">Loading prescription...</div>');
		
		// Fetch prescription data
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'get_rochtah_prescription',
				booking_id: bookingId,
				nonce: '<?php echo wp_create_nonce( 'rochtah_prescription' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					const header = `
						<h2>View/Edit Prescription</h2>
						<span class="close" onclick="closePrescriptionModal()">&times;</span>
					`;
					
					const body = `
						<form id="editPrescriptionForm">
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Patient:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">${data.patient_name}</div>
							</div>
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Booking Date:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">${data.booking_date} at ${data.booking_time}</div>
							</div>
							<div style="margin-bottom: 20px;">
								<label for="edit_prescription_text" style="font-weight: bold; display: block; margin-bottom: 8px;">Prescription Text:</label>
								<textarea id="edit_prescription_text" name="prescription_text" style="width: 100%; height: 120px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;" required>${data.prescription_text || ''}</textarea>
							</div>
							<div style="margin-bottom: 20px;">
								<label for="edit_medications" style="font-weight: bold; display: block; margin-bottom: 8px;">Medications:</label>
								<textarea id="edit_medications" name="medications" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;">${data.medications || ''}</textarea>
							</div>
							<div style="margin-bottom: 20px;">
								<label for="edit_dosage_instructions" style="font-weight: bold; display: block; margin-bottom: 8px;">Dosage Instructions:</label>
								<textarea id="edit_dosage_instructions" name="dosage_instructions" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;">${data.dosage_instructions || ''}</textarea>
							</div>
							<div style="margin-bottom: 20px;">
								<label for="edit_doctor_notes" style="font-weight: bold; display: block; margin-bottom: 8px;">Doctor Notes:</label>
								<textarea id="edit_doctor_notes" name="doctor_notes" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical;">${data.doctor_notes || ''}</textarea>
							</div>
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Prescribed By:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">${data.prescribed_by_name || 'Not prescribed yet'}</div>
							</div>
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Prescribed At:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">${data.prescribed_at || 'Not prescribed yet'}</div>
							</div>
						</form>
					`;
					
					const footer = `
						<button type="button" onclick="updatePrescription(${bookingId})" class="button button-primary" style="margin-right: 10px;">
							Update Prescription
						</button>
						<button type="button" onclick="closePrescriptionModal()" class="button">
							Cancel
						</button>
					`;
					
					showPrescriptionModal(header, body, footer);
				} else {
					showModal('Error', `<p>Error loading prescription: ${response.data}</p>`);
				}
			},
			error: function() {
				showModal('Error', '<p>Failed to load prescription data.</p>');
			}
		});
	}

	function updatePrescription(bookingId) {
		// Get form data
		const prescriptionText = document.getElementById('edit_prescription_text').value;
		const medications = document.getElementById('edit_medications').value;
		const dosageInstructions = document.getElementById('edit_dosage_instructions').value;
		const doctorNotes = document.getElementById('edit_doctor_notes').value;
		
		// Validate required field
		if (!prescriptionText.trim()) {
			alert('Prescription text is required.');
			return;
		}
		
		// Show saving state
		showPrescriptionModal(
			'<h2>Updating Prescription</h2><span class="close" onclick="closePrescriptionModal()">&times;</span>',
			'<div style="text-align: center; padding: 40px;">Saving prescription...</div>'
		);
		
		// Send update request
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'update_rochtah_prescription',
				booking_id: bookingId,
				prescription_text: prescriptionText,
				medications: medications,
				dosage_instructions: dosageInstructions,
				doctor_notes: doctorNotes,
				nonce: '<?php echo wp_create_nonce( 'save_prescription' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					showPrescriptionModal(
						'<h2>Success</h2><span class="close" onclick="closePrescriptionModal()">&times;</span>',
						'<p>Prescription updated successfully!</p>',
						'<button onclick="closePrescriptionModal(); location.reload();" class="button button-primary">OK</button>'
					);
				} else {
					showPrescriptionModal(
						'<h2>Error</h2><span class="close" onclick="closePrescriptionModal()">&times;</span>',
						`<p>Error updating prescription: ${response.data}</p>`,
						'<button onclick="closePrescriptionModal();" class="button">Close</button>'
					);
				}
			},
			error: function() {
				showPrescriptionModal(
					'<h2>Error</h2><span class="close" onclick="closePrescriptionModal()">&times;</span>',
					'<p>Failed to update prescription.</p>',
					'<button onclick="closePrescriptionModal();" class="button">Close</button>'
				);
			}
		});
	}

	// Show prescription modal with fixed height and scrollbar
	function showPrescriptionModal(header, body, footer = '') {
		// Create or update the prescription modal
		let modal = document.getElementById('prescriptionModal');
		if (!modal) {
			modal = document.createElement('div');
			modal.id = 'prescriptionModal';
			document.body.appendChild(modal);
		}
		
		// Ensure correct classes are applied
		modal.className = 'modal prescription-modal';
		
		modal.innerHTML = `
			<div class="modal-content" style="width: 90%; max-width: 700px; height: 80vh; max-height: 600px; margin: 2% auto; display: flex; flex-direction: column;">
				<div class="prescription-modal-header" style="flex-shrink: 0; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
					${header}
				</div>
				<div class="prescription-modal-body" style="flex: 1; overflow-y: auto; padding-right: 10px; margin-right: -10px;">
					${body}
				</div>
				${footer ? `<div class="prescription-modal-footer" style="flex-shrink: 0; padding-top: 20px; border-top: 1px solid #e5e7eb; margin-top: 20px; text-align: center;">${footer}</div>` : ''}
			</div>
		`;
		
		modal.style.display = 'block';
		
		// Close modal when clicking outside of it
		modal.onclick = function(event) {
			if (event.target === modal) {
				closePrescriptionModal();
			}
		};
	}

	// Close prescription modal
	function closePrescriptionModal() {
		const modal = document.getElementById('prescriptionModal');
		if (modal) {
			modal.style.display = 'none';
		}
	}
	
	function showReferralReason(bookingId) {
		// Show loading state
		showModal('Referral Reason', '<div style="text-align: center; padding: 40px;">Loading...</div>');
		
		// Fetch referral reason data
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'get_rochtah_referral_reason',
				booking_id: bookingId,
				nonce: '<?php echo wp_create_nonce( 'rochtah_referral_reason' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					const content = `
						<div style="margin-top: 16px;">
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Preliminary Diagnosis:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; margin-top: 8px; line-height: 1.6; white-space: pre-wrap;">${data.preliminary_diagnosis || 'N/A'}</div>
							</div>
							<div style="margin-bottom: 20px;">
								<label style="font-weight: bold; display: block; margin-bottom: 8px;">Symptoms:</label>
								<div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; margin-top: 8px; line-height: 1.6; white-space: pre-wrap;">${data.symptoms || 'N/A'}</div>
							</div>
						</div>
					`;
					showModal('Reason for Referral', content);
				} else {
					showModal('Error', 'Failed to load referral reason');
				}
			},
			error: function() {
				showModal('Error', 'Failed to load referral reason');
			}
		});
	}
	
	function showModal(title, content) {
		// Create modal HTML
		const modalHtml = `
			<div id="referralModal" class="modal" style="display: block;">
				<div class="modal-content">
					<span class="close">&times;</span>
					<h2>${title}</h2>
					<div>${content}</div>
				</div>
			</div>
		`;
		
		// Remove existing modal if any
		const existingModal = document.getElementById('referralModal');
		if (existingModal) {
			existingModal.remove();
		}
		
		// Add new modal
		document.body.insertAdjacentHTML('beforeend', modalHtml);
		
		// Add event listener for close button
		document.querySelector('#referralModal .close').onclick = function() {
			document.getElementById('referralModal').style.display = 'none';
		};
		
		// Add event listener for clicking outside modal
		document.getElementById('referralModal').onclick = function(event) {
			if (event.target == this) {
				this.style.display = 'none';
			}
		};
	}

	// Rochtah Meeting Variables
	let currentMeetingDetails = null;
	let doctorMeetingAPI = null;

	function joinRochtahMeeting(bookingId) {
		// Show loading state
		document.getElementById('sessionInfo').innerHTML = '<div style="text-align: center;">Loading meeting details...</div>';
		document.getElementById('rochtahMeetingModal').style.display = 'block';
		
		// Fetch meeting details
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'get_rochtah_meeting_details_doctor',
				booking_id: bookingId,
				nonce: '<?php echo wp_create_nonce( 'rochtah_meeting_doctor' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					currentMeetingDetails = response.data;
					displayMeetingDetails(currentMeetingDetails);
				} else {
					document.getElementById('sessionInfo').innerHTML = '<div style="color: #dc3545;">Failed to load meeting details: ' + (response.data || 'Unknown error') + '</div>';
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading meeting details:', error);
				document.getElementById('sessionInfo').innerHTML = '<div style="color: #dc3545;">Failed to load meeting details. Please try again.</div>';
			}
		});
	}

	function resetRochtahBooking(bookingId) {
		if (!confirm('Are you sure you want to reset this booking? The patient will be able to book again.')) {
			return;
		}
		
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'reset_rochtah_booking',
				request_id: bookingId,
				nonce: '<?php echo wp_create_nonce( 'reset_rochtah_booking_nonce' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					alert('Booking reset successfully!');
					location.reload();
				} else {
					alert('Failed to reset booking: ' + (response.data || 'Unknown error'));
				}
			},
			error: function(xhr, status, error) {
				console.error('Error resetting booking:', error);
				alert('Failed to reset booking. Please try again.');
			}
		});
	}

	function displayMeetingDetails(details) {
		const sessionInfo = document.getElementById('sessionInfo');
		sessionInfo.innerHTML = `
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
				<div>
					<strong>📅 Date:</strong><br>
					${details.booking_date}
				</div>
				<div>
					<strong>🕐 Time:</strong><br>
					${details.booking_time}
				</div>
				<div>
					<strong>🏠 Room:</strong><br>
					${details.room_name}
				</div>
				<div>
					<strong>👤 Patient:</strong><br>
					${details.patient_name || 'N/A'}
				</div>
			</div>
		`;
		
		// Mark doctor as joined
		markDoctorJoined(details.booking_id);
		
		// Auto-start the meeting
		setTimeout(() => {
			startRochtahDoctorMeeting();
		}, 1000);
	}
	
	function markDoctorJoined(bookingId) {
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'mark_rochtah_doctor_joined',
				booking_id: bookingId,
				nonce: '<?php echo wp_create_nonce( 'mark_doctor_joined' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					console.log('Doctor marked as joined successfully');
				} else {
					console.error('Failed to mark doctor as joined:', response.data);
				}
			},
			error: function(xhr, status, error) {
				console.error('Error marking doctor as joined:', error);
			}
		});
	}

	function startRochtahDoctorMeeting() {
		if (!currentMeetingDetails) {
			alert('No meeting details available');
			return;
		}
		
		// Show loading state
		const loadingDiv = document.getElementById('jitsi-loading');
		const startBtn = document.getElementById('startMeetingBtn');
		if (loadingDiv) loadingDiv.style.display = 'block';
		if (startBtn) {
			startBtn.disabled = true;
			startBtn.textContent = '🔄 Loading...';
		}
		
		// Clean up any existing meeting
		if (doctorMeetingAPI) {
			try {
				doctorMeetingAPI.dispose();
			} catch (e) {
				console.warn('Error disposing previous meeting:', e);
			}
			doctorMeetingAPI = null;
		}
		
		// Check if JitsiMeetExternalAPI is already available
		if (typeof JitsiMeetExternalAPI !== 'undefined') {
			initializeRochtahDoctorJitsiMeeting();
			return;
		}
		
		// Load Jitsi external API script
		const script = document.createElement('script');
		script.src = 'https://s.jalsah.app/external_api.js';
		script.onload = () => {
			console.log('Jitsi external API loaded from main server');
			setTimeout(() => {
				initializeRochtahDoctorJitsiMeeting();
			}, 500); // Give it a moment to initialize
		};
		script.onerror = (error) => {
			console.warn('Failed to load from main server, trying fallback:', error);
			// Try fallback server
			const fallbackScript = document.createElement('script');
			fallbackScript.src = 'https://meet.jit.si/external_api.js';
			fallbackScript.onload = () => {
				console.log('Jitsi external API loaded from fallback server');
				setTimeout(() => {
					initializeRochtahDoctorJitsiMeeting();
				}, 500);
			};
			fallbackScript.onerror = () => {
				console.error('Failed to load Jitsi from both servers');
				if (loadingDiv) loadingDiv.style.display = 'none';
				if (startBtn) {
					startBtn.disabled = false;
					startBtn.textContent = '🎥 Start Meeting';
				}
				alert('Failed to load meeting service. Please check your internet connection and try again.');
			};
			document.head.appendChild(fallbackScript);
		};
		document.head.appendChild(script);
	}

	function initializeRochtahDoctorJitsiMeeting() {
		if (!currentMeetingDetails) {
			console.error('No meeting details available');
			return;
		}
		
		console.log('Current meeting details:', currentMeetingDetails);
		const roomName = currentMeetingDetails.room_name;
		const userName = '<?php echo esc_js( $current_user->display_name ); ?>' || 'Doctor';
		
		console.log('Initializing Jitsi meeting with room:', roomName, 'user:', userName);
		
		const options = {
			parentNode: document.querySelector('#rochtah-doctor-meeting'),
			roomName: roomName,
			width: '100%',
			height: '100%',
			configOverwrite: {
				prejoinPageEnabled: false,
				startWithAudioMuted: false,
				startWithVideoMuted: false,
				disableAudioLevels: false,
				enableClosePage: true,
				enableWelcomePage: false,
				participantsPane: {
					enabled: true,
					hideModeratorSettingsTab: false,
					hideMoreActionsButton: false,
					hideMuteAllButton: false
				},
				toolbarButtons: [
					'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
					'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 
					'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
					'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
				]
			},
			interfaceConfigOverwrite: {
				prejoinPageEnabled: false,
				APP_NAME: 'Jalsah Rochtah Doctor',
				DEFAULT_BACKGROUND: "#1a1a1a",
				SHOW_JITSI_WATERMARK: false,
				HIDE_DEEP_LINKING_LOGO: true,
				SHOW_BRAND_WATERMARK: false,
				SHOW_WATERMARK_FOR_GUESTS: false,
				SHOW_POWERED_BY: false,
				DISPLAY_WELCOME_FOOTER: false,
				JITSI_WATERMARK_LINK: 'https://jalsah.app',
				PROVIDER_NAME: 'Jalsah',
				DEFAULT_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
				DEFAULT_WELCOME_PAGE_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
				TOOLBAR_ALWAYS_VISIBLE: true,
				TOOLBAR_BUTTONS: [
					'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
					'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 
					'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
					'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
				]
			}
		};
		
		const loadingDiv = document.getElementById('jitsi-loading');
		const startBtn = document.getElementById('startMeetingBtn');
		
		try {
			console.log('Initializing Jitsi meeting with room:', roomName);
			
			// Try the main Jitsi server first
			try {
				doctorMeetingAPI = new JitsiMeetExternalAPI("s.jalsah.app", options);
				console.log('Connected to main Jitsi server');
			} catch (serverError) {
				console.warn('Main server failed, trying fallback:', serverError);
				// Fallback to meet.jit.si if main server fails
				doctorMeetingAPI = new JitsiMeetExternalAPI("meet.jit.si", options);
				console.log('Connected to fallback Jitsi server');
			}
			
			// Set display name
			doctorMeetingAPI.executeCommand('displayName', userName);
			
			// Add event listeners
			doctorMeetingAPI.addListener('videoConferenceJoined', () => {
				console.log('Doctor joined Rochtah meeting successfully');
				if (loadingDiv) loadingDiv.style.display = 'none';
				if (startBtn) startBtn.style.display = 'none';
			});
			
			doctorMeetingAPI.addListener('videoConferenceLeft', () => {
				console.log('Doctor left Rochtah meeting');
				closeMeetingModal();
			});
			
			doctorMeetingAPI.addListener('readyToClose', () => {
				console.log('Jitsi ready to close');
				closeMeetingModal();
			});
			
			// Hide loading after a short delay (meeting should start loading)
			setTimeout(() => {
				if (loadingDiv) loadingDiv.style.display = 'none';
				if (startBtn) {
					startBtn.textContent = '🎥 Meeting Active';
					startBtn.disabled = true;
				}
			}, 2000);
			
		} catch (error) {
			console.error('Error initializing Rochtah doctor Jitsi meeting:', error);
			if (loadingDiv) loadingDiv.style.display = 'none';
			if (startBtn) {
				startBtn.disabled = false;
				startBtn.textContent = '🎥 Start Meeting';
			}
			alert('Failed to start meeting. Please try again or check your internet connection.');
		}
	}

	function closeMeetingModal() {
		// Clean up Jitsi meeting
		if (doctorMeetingAPI) {
			try {
				doctorMeetingAPI.dispose();
			} catch (e) {
				console.warn('Error disposing Jitsi meeting:', e);
			}
			doctorMeetingAPI = null;
		}
		
		// Reset variables
		currentMeetingDetails = null;
		
		// Reset UI elements
		const startBtn = document.getElementById('startMeetingBtn');
		const loadingDiv = document.getElementById('jitsi-loading');
		
		if (startBtn) {
			startBtn.style.display = 'inline-block';
			startBtn.disabled = false;
			startBtn.textContent = '🎥 Start Meeting';
		}
		
		if (loadingDiv) {
			loadingDiv.style.display = 'none';
		}
		
		// Hide modal
		document.getElementById('rochtahMeetingModal').style.display = 'none';
	}

	// Toggle actions visibility
	function toggleActions(bookingId) {
		const actionsContainer = document.getElementById('actions-' + bookingId);
		const toggleBtn = document.getElementById('toggle-btn-' + bookingId);
		
		if (actionsContainer.style.display === 'none' || actionsContainer.style.display === '') {
			actionsContainer.style.display = 'block';
			toggleBtn.textContent = '📤 Hide Actions';
			toggleBtn.style.backgroundColor = '#dc3545';
			toggleBtn.style.borderColor = '#dc3545';
			toggleBtn.style.color = 'white';
		} else {
			actionsContainer.style.display = 'none';
			toggleBtn.textContent = '⚙️ Actions';
			toggleBtn.style.backgroundColor = '';
			toggleBtn.style.borderColor = '';
			toggleBtn.style.color = '';
		}
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
				// Also add administrator role
				if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
					$user->add_role( 'administrator' );
				}
				echo '<div class="notice notice-success"><p>Rochtah Doctor and Administrator roles assigned to ' . esc_html( $user->display_name ) . '</p></div>';
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