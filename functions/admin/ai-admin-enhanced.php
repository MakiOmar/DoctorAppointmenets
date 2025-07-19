<?php
/**
 * Enhanced AI Admin Interface
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add enhanced AI admin menu
 */
function snks_add_enhanced_ai_admin_menu() {
	add_menu_page(
		'Jalsah AI Management',
		'Jalsah AI',
		'manage_options',
		'jalsah-ai-management',
		'snks_enhanced_ai_admin_page',
		'dashicons-brain',
		30
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Dashboard',
		'Dashboard',
		'manage_options',
		'jalsah-ai-management',
		'snks_enhanced_ai_admin_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Diagnoses',
		'Diagnoses',
		'manage_options',
		'jalsah-ai-diagnoses',
		'snks_enhanced_ai_diagnoses_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Therapist Profiles',
		'Therapist Profiles',
		'manage_options',
		'jalsah-ai-therapists',
		'snks_enhanced_ai_therapists_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Sessions & Attendance',
		'Sessions & Attendance',
		'manage_options',
		'jalsah-ai-sessions',
		'snks_enhanced_ai_sessions_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Coupons',
		'Coupons',
		'manage_options',
		'jalsah-ai-coupons',
		'snks_enhanced_ai_coupons_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Analytics',
		'Analytics',
		'manage_options',
		'jalsah-ai-analytics',
		'snks_enhanced_ai_analytics_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'ChatGPT Integration',
		'ChatGPT',
		'manage_options',
		'jalsah-ai-chatgpt',
		'snks_enhanced_ai_chatgpt_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'WhatsApp Integration',
		'WhatsApp',
		'manage_options',
		'jalsah-ai-whatsapp',
		'snks_enhanced_ai_whatsapp_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Rochtah Integration',
		'Rochtah',
		'manage_options',
		'jalsah-ai-rochtah',
		'snks_enhanced_ai_rochtah_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Email Settings',
		'Email Settings',
		'manage_options',
		'jalsah-ai-email',
		'snks_enhanced_ai_email_page'
	);

	add_submenu_page(
		'jalsah-ai-management',
		'Admin Tools',
		'Admin Tools',
		'manage_options',
		'jalsah-ai-tools',
		'snks_enhanced_ai_tools_page'
	);
}
add_action( 'admin_menu', 'snks_add_enhanced_ai_admin_menu' );

/**
 * Enhanced AI Admin Dashboard
 */
function snks_enhanced_ai_admin_page() {
	global $wpdb;
	
	// Get statistics
	$total_therapists = count( get_users( array( 'role' => 'doctor' ) ) );
	$ai_therapists = count( get_users( array( 
		'role' => 'doctor',
		'meta_query' => array(
			array( 'key' => 'show_on_ai_site', 'value' => '1', 'compare' => '=' )
		)
	) ) );
	
	$total_diagnoses = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snks_diagnoses" );
	$total_ai_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE from_jalsah_ai = 1" );
	$total_ai_users = count( get_users( array( 
		'meta_query' => array(
			array( 'key' => 'registration_source', 'value' => 'jalsah_ai', 'compare' => '=' )
		)
	) ) );
	
	?>
	<div class="wrap">
		<h1>Jalsah AI Management Dashboard</h1>
		
		<div class="card">
			<h2>Quick Statistics</h2>
			<div class="stats-grid">
				<div class="stat-item">
					<h3><?php echo $total_therapists; ?></h3>
					<p>Total Therapists</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $ai_therapists; ?></h3>
					<p>AI-Enabled Therapists</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $total_diagnoses; ?></h3>
					<p>Diagnoses</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $total_ai_orders; ?></h3>
					<p>AI Orders</p>
				</div>
				<div class="stat-item">
					<h3><?php echo $total_ai_users; ?></h3>
					<p>AI Users</p>
				</div>
			</div>
		</div>

		<div class="card">
			<h2>Quick Actions</h2>
			<div class="quick-actions">
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-therapists' ); ?>" class="button button-primary">Manage Therapist Profiles</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-diagnoses' ); ?>" class="button button-secondary">Manage Diagnoses</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-sessions' ); ?>" class="button button-secondary">View Sessions</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-analytics' ); ?>" class="button button-secondary">View Analytics</a>
			</div>
		</div>

		<div class="card">
			<h2>Recent AI Activity</h2>
			<?php
			$recent_orders = $wpdb->get_results( "
				SELECT o.*, u.display_name as patient_name 
				FROM {$wpdb->prefix}wc_orders o 
				LEFT JOIN {$wpdb->users} u ON o.customer_id = u.ID 
				WHERE o.from_jalsah_ai = 1 
				ORDER BY o.date_created_gmt DESC 
				LIMIT 10
			" );
			
			if ( $recent_orders ) {
				echo '<table class="wp-list-table widefat fixed striped">';
				echo '<thead><tr><th>Order</th><th>Patient</th><th>Status</th><th>Date</th><th>Total</th></tr></thead>';
				echo '<tbody>';
				foreach ( $recent_orders as $order ) {
					echo '<tr>';
					echo '<td>#' . $order->id . '</td>';
					echo '<td>' . esc_html( $order->patient_name ) . '</td>';
					echo '<td>' . esc_html( $order->status ) . '</td>';
					echo '<td>' . esc_html( $order->date_created_gmt ) . '</td>';
					echo '<td>$' . esc_html( $order->total_amount ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			} else {
				echo '<p>No recent AI orders found.</p>';
			}
			?>
		</div>
	</div>

	<style>
	.stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		margin: 20px 0;
	}
	.stat-item {
		text-align: center;
		padding: 20px;
		background: #f9f9f9;
		border-radius: 8px;
	}
	.stat-item h3 {
		font-size: 2em;
		margin: 0;
		color: #0073aa;
	}
	.quick-actions {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		margin: 20px 0;
	}
	.card {
		background: white;
		padding: 20px;
		margin: 20px 0;
		border: 1px solid #ddd;
		border-radius: 8px;
	}
	</style>
	<?php
}

/**
 * Enhanced Therapist Profiles Page
 */
function snks_enhanced_ai_therapists_page() {
	global $wpdb;
	
	// Handle form submissions
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_therapist_profile' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_therapist_profile' ) ) {
			$therapist_id = intval( $_POST['therapist_id'] );
			
			// Update basic AI settings
			update_user_meta( $therapist_id, 'show_on_ai_site', isset( $_POST['show_on_ai_site'] ) ? '1' : '0' );
			update_user_meta( $therapist_id, 'ai_display_name', sanitize_text_field( $_POST['ai_display_name'] ) );
			update_user_meta( $therapist_id, 'ai_bio', sanitize_textarea_field( $_POST['ai_bio'] ) );
			update_user_meta( $therapist_id, 'public_short_bio', sanitize_textarea_field( $_POST['public_short_bio'] ) );
			update_user_meta( $therapist_id, 'secretary_phone', sanitize_text_field( $_POST['secretary_phone'] ) );
			update_user_meta( $therapist_id, 'ai_first_session_percentage', floatval( $_POST['ai_first_session_percentage'] ) );
			update_user_meta( $therapist_id, 'ai_followup_session_percentage', floatval( $_POST['ai_followup_session_percentage'] ) );
			
			// Handle diagnosis assignments
			$diagnoses = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}snks_diagnoses" );
			foreach ( $diagnoses as $diagnosis ) {
				$assigned_key = 'assigned_' . $diagnosis->id;
				$rank_key = 'rank_' . $diagnosis->id;
				$message_key = 'message_' . $diagnosis->id;
				
				if ( isset( $_POST[ $assigned_key ] ) ) {
					$rank = intval( $_POST[ $rank_key ] );
					$message = sanitize_textarea_field( $_POST[ $message_key ] );
					
					$wpdb->replace(
						$wpdb->prefix . 'snks_therapist_diagnoses',
						array(
							'therapist_id' => $therapist_id,
							'diagnosis_id' => $diagnosis->id,
							'rating' => $rank,
							'suitability_message' => $message,
						),
						array( '%d', '%d', '%f', '%s' )
					);
				} else {
					$wpdb->delete(
						$wpdb->prefix . 'snks_therapist_diagnoses',
						array(
							'therapist_id' => $therapist_id,
							'diagnosis_id' => $diagnosis->id,
						),
						array( '%d', '%d' )
					);
				}
			}
			
			echo '<div class="notice notice-success"><p>Therapist profile updated successfully!</p></div>';
		}
	}
	
	$therapists = get_users( array( 'role' => 'doctor' ) );
	$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
	?>
	<div class="wrap">
		<h1>AI Therapist Profiles</h1>
		
		<div class="card">
			<h2>Select Therapist</h2>
			<select id="therapist-selector" onchange="loadTherapistProfile(this.value)">
				<option value="">Select a therapist...</option>
				<?php foreach ( $therapists as $therapist ) : ?>
					<option value="<?php echo $therapist->ID; ?>">
						<?php echo esc_html( get_user_meta( $therapist->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $therapist->ID, 'billing_last_name', true ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div id="therapist-profile" style="display:none;">
			<form method="post">
				<?php wp_nonce_field( 'update_therapist_profile' ); ?>
				<input type="hidden" name="action" value="update_therapist_profile">
				<input type="hidden" name="therapist_id" id="therapist_id">
				
				<div class="card">
					<h2>Basic AI Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="show_on_ai_site">Show on AI Site</label></th>
							<td><input type="checkbox" id="show_on_ai_site" name="show_on_ai_site" value="1"></td>
						</tr>
						<tr>
							<th><label for="ai_display_name">AI Display Name</label></th>
							<td><input type="text" id="ai_display_name" name="ai_display_name" class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="ai_bio">AI Bio</label></th>
							<td><textarea id="ai_bio" name="ai_bio" rows="4" class="large-text"></textarea></td>
						</tr>
						<tr>
							<th><label for="public_short_bio">Public Short Bio</label></th>
							<td><textarea id="public_short_bio" name="public_short_bio" rows="3" class="large-text"></textarea></td>
						</tr>
						<tr>
							<th><label for="secretary_phone">Secretary Phone</label></th>
							<td><input type="text" id="secretary_phone" name="secretary_phone" class="regular-text"></td>
						</tr>
					</table>
				</div>
				
				<div class="card">
					<h2>Financial Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="ai_first_session_percentage">First Session Percentage</label></th>
							<td><input type="number" id="ai_first_session_percentage" name="ai_first_session_percentage" min="0" max="100" step="0.1" class="small-text">%</td>
						</tr>
						<tr>
							<th><label for="ai_followup_session_percentage">Follow-up Session Percentage</label></th>
							<td><input type="number" id="ai_followup_session_percentage" name="ai_followup_session_percentage" min="0" max="100" step="0.1" class="small-text">%</td>
						</tr>
					</table>
				</div>
				
				<div class="card">
					<h2>Diagnosis Assignments</h2>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Diagnosis</th>
								<th>Assigned</th>
								<th>Rank Points (0-100)</th>
								<th>Custom Message</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $diagnoses as $diagnosis ) : ?>
								<tr>
									<td><?php echo esc_html( $diagnosis->name ); ?></td>
									<td>
										<input type="checkbox" name="assigned_<?php echo $diagnosis->id; ?>" value="1" id="assigned_<?php echo $diagnosis->id; ?>">
									</td>
									<td>
										<input type="number" name="rank_<?php echo $diagnosis->id; ?>" min="0" max="100" class="small-text" value="0" id="rank_<?php echo $diagnosis->id; ?>">
									</td>
									<td>
										<textarea name="message_<?php echo $diagnosis->id; ?>" rows="2" class="large-text" id="message_<?php echo $diagnosis->id; ?>"></textarea>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				
				<?php submit_button( 'Update Therapist Profile' ); ?>
			</form>
		</div>
	</div>
	
	<script>
	function loadTherapistProfile(therapistId) {
		if (!therapistId) {
			document.getElementById('therapist-profile').style.display = 'none';
			return;
		}
		
		document.getElementById('therapist-profile').style.display = 'block';
		document.getElementById('therapist_id').value = therapistId;
		
		// Clear form fields
		clearProfileFields();
		
		// Load therapist data via AJAX
		jQuery.post(ajaxurl, {
			action: 'load_enhanced_therapist_profile',
			therapist_id: therapistId,
			nonce: '<?php echo wp_create_nonce( "load_enhanced_therapist_profile" ); ?>'
		}, function(response) {
			if (response.success) {
				var data = response.data;
				
				// Set basic fields
				document.getElementById('show_on_ai_site').checked = data.show_on_ai_site === '1';
				document.getElementById('ai_display_name').value = data.ai_display_name || '';
				document.getElementById('ai_bio').value = data.ai_bio || '';
				document.getElementById('public_short_bio').value = data.public_short_bio || '';
				document.getElementById('secretary_phone').value = data.secretary_phone || '';
				document.getElementById('ai_first_session_percentage').value = data.ai_first_session_percentage || 0;
				document.getElementById('ai_followup_session_percentage').value = data.ai_followup_session_percentage || 0;
				
				// Set diagnosis assignments
				if (data.diagnoses && data.diagnoses.length > 0) {
					data.diagnoses.forEach(function(diagnosis) {
						var assignedCheckbox = document.getElementById('assigned_' + diagnosis.diagnosis_id);
						var rankInput = document.getElementById('rank_' + diagnosis.diagnosis_id);
						var messageTextarea = document.getElementById('message_' + diagnosis.diagnosis_id);
						
						if (assignedCheckbox) assignedCheckbox.checked = true;
						if (rankInput) rankInput.value = diagnosis.rating || 0;
						if (messageTextarea) messageTextarea.value = diagnosis.suitability_message || '';
					});
				}
			}
		});
	}
	
	function clearProfileFields() {
		// Clear all form fields
		document.getElementById('show_on_ai_site').checked = false;
		document.getElementById('ai_display_name').value = '';
		document.getElementById('ai_bio').value = '';
		document.getElementById('public_short_bio').value = '';
		document.getElementById('secretary_phone').value = '';
		document.getElementById('ai_first_session_percentage').value = '0';
		document.getElementById('ai_followup_session_percentage').value = '0';
		
		// Clear diagnosis assignments
		var checkboxes = document.querySelectorAll('input[type="checkbox"][name^="assigned_"]');
		checkboxes.forEach(function(checkbox) {
			checkbox.checked = false;
		});
		
		var rankInputs = document.querySelectorAll('input[name^="rank_"]');
		rankInputs.forEach(function(input) {
			input.value = '0';
		});
		
		var messageTextareas = document.querySelectorAll('textarea[name^="message_"]');
		messageTextareas.forEach(function(textarea) {
			textarea.value = '';
		});
	}
	</script>
	<?php
}

// AJAX handler for loading enhanced therapist profile
function snks_load_enhanced_therapist_profile() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'load_enhanced_therapist_profile' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}
	
	$therapist_id = intval( $_POST['therapist_id'] );
	
	$data = array(
		'show_on_ai_site' => get_user_meta( $therapist_id, 'show_on_ai_site', true ),
		'ai_display_name' => get_user_meta( $therapist_id, 'ai_display_name', true ),
		'ai_bio' => get_user_meta( $therapist_id, 'ai_bio', true ),
		'public_short_bio' => get_user_meta( $therapist_id, 'public_short_bio', true ),
		'secretary_phone' => get_user_meta( $therapist_id, 'secretary_phone', true ),
		'ai_first_session_percentage' => get_user_meta( $therapist_id, 'ai_first_session_percentage', true ),
		'ai_followup_session_percentage' => get_user_meta( $therapist_id, 'ai_followup_session_percentage', true ),
		'diagnoses' => array(),
	);
	
	global $wpdb;
	$diagnoses = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_therapist_diagnoses WHERE therapist_id = %d",
		$therapist_id
	) );
	
	foreach ( $diagnoses as $diagnosis ) {
		$data['diagnoses'][] = array(
			'diagnosis_id' => $diagnosis->diagnosis_id,
			'rating' => $diagnosis->rating,
			'suitability_message' => $diagnosis->suitability_message,
		);
	}
	
	wp_send_json_success( $data );
}
add_action( 'wp_ajax_load_enhanced_therapist_profile', 'snks_load_enhanced_therapist_profile' ); 

/**
 * Enhanced Sessions & Attendance Page
 */
function snks_enhanced_ai_sessions_page() {
	global $wpdb;
	
	// Handle attendance updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_attendance' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_attendance' ) ) {
			$session_id = intval( $_POST['session_id'] );
			$attendance = sanitize_text_field( $_POST['attendance'] );
			$case_id = intval( $_POST['case_id'] );
			
			// Update or insert attendance record
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %d",
				$session_id
			) );
			
			if ( $existing ) {
				$wpdb->update(
					$wpdb->prefix . 'snks_sessions_actions',
					array( 'attendance' => $attendance ),
					array( 'action_session_id' => $session_id ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'snks_sessions_actions',
					array(
						'action_session_id' => $session_id,
						'case_id' => $case_id,
						'attendance' => $attendance,
					),
					array( '%d', '%d', '%s' )
				);
			}
			
			echo '<div class="notice notice-success"><p>Attendance updated successfully!</p></div>';
		}
	}
	
	// Get filter parameters
	$filter_ai_only = isset( $_GET['ai_only'] ) ? $_GET['ai_only'] : '';
	$filter_attendance = isset( $_GET['attendance'] ) ? $_GET['attendance'] : '';
	$filter_therapist = isset( $_GET['therapist'] ) ? intval( $_GET['therapist'] ) : 0;
	$filter_date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
	
	// Build query
	$where_conditions = array();
	$where_values = array();
	
	if ( $filter_ai_only ) {
		$where_conditions[] = "o.from_jalsah_ai = 1";
	}
	
	if ( $filter_attendance ) {
		$where_conditions[] = "sa.attendance = %s";
		$where_values[] = $filter_attendance;
	}
	
	if ( $filter_therapist ) {
		$where_conditions[] = "t.user_id = %d";
		$where_values[] = $filter_therapist;
	}
	
	if ( $filter_date ) {
		$where_conditions[] = "DATE(t.date_time) = %s";
		$where_values[] = $filter_date;
	}
	
	$where_clause = '';
	if ( ! empty( $where_conditions ) ) {
		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
	}
	
	$query = "
		SELECT t.*, o.from_jalsah_ai, sa.attendance, sa.case_id,
		       u.display_name as therapist_name,
		       c.display_name as patient_name
		FROM {$wpdb->prefix}snks_provider_timetable t
		LEFT JOIN {$wpdb->prefix}wc_orders o ON t.order_id = o.id
		LEFT JOIN {$wpdb->prefix}snks_sessions_actions sa ON t.ID = sa.action_session_id
		LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
		LEFT JOIN {$wpdb->users} c ON t.client_id = c.ID
		{$where_clause}
		ORDER BY t.date_time DESC
		LIMIT 100
	";
	
	if ( ! empty( $where_values ) ) {
		$query = $wpdb->prepare( $query, $where_values );
	}
	
	$sessions = $wpdb->get_results( $query );
	$therapists = get_users( array( 'role' => 'doctor' ) );
	?>
	<div class="wrap">
		<h1>AI Sessions & Attendance</h1>
		
		<div class="card">
			<h2>Filters</h2>
			<form method="get" class="filters-form">
				<input type="hidden" name="page" value="jalsah-ai-sessions">
				
				<label>
					<input type="checkbox" name="ai_only" value="1" <?php checked( $filter_ai_only, '1' ); ?>>
					AI Sessions Only
				</label>
				
				<label>
					Attendance Status:
					<select name="attendance">
						<option value="">All</option>
						<option value="yes" <?php selected( $filter_attendance, 'yes' ); ?>>Attended</option>
						<option value="no" <?php selected( $filter_attendance, 'no' ); ?>>Did Not Attend</option>
					</select>
				</label>
				
				<label>
					Therapist:
					<select name="therapist">
						<option value="">All Therapists</option>
						<?php foreach ( $therapists as $therapist ) : ?>
							<option value="<?php echo $therapist->ID; ?>" <?php selected( $filter_therapist, $therapist->ID ); ?>>
								<?php echo esc_html( get_user_meta( $therapist->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $therapist->ID, 'billing_last_name', true ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				
				<label>
					Date:
					<input type="date" name="date" value="<?php echo esc_attr( $filter_date ); ?>">
				</label>
				
				<button type="submit" class="button">Apply Filters</button>
			</form>
		</div>
		
		<div class="card">
			<h2>Sessions</h2>
			<?php if ( $sessions ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Session ID</th>
							<th>Date & Time</th>
							<th>Therapist</th>
							<th>Patient</th>
							<th>Status</th>
							<th>AI Session</th>
							<th>Attendance</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sessions as $session ) : ?>
							<tr>
								<td>#<?php echo $session->ID; ?></td>
								<td><?php echo esc_html( $session->date_time ); ?></td>
								<td><?php echo esc_html( $session->therapist_name ); ?></td>
								<td><?php echo esc_html( $session->patient_name ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $session->session_status ); ?>">
										<?php echo esc_html( ucfirst( $session->session_status ) ); ?>
									</span>
								</td>
								<td>
									<?php if ( $session->from_jalsah_ai ) : ?>
										<span class="ai-badge">AI</span>
									<?php endif; ?>
								</td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'update_attendance' ); ?>
										<input type="hidden" name="action" value="update_attendance">
										<input type="hidden" name="session_id" value="<?php echo $session->ID; ?>">
										<input type="hidden" name="case_id" value="<?php echo $session->case_id ?: $session->ID; ?>">
										<select name="attendance" onchange="this.form.submit()">
											<option value="">Not Set</option>
											<option value="yes" <?php selected( $session->attendance, 'yes' ); ?>>Attended</option>
											<option value="no" <?php selected( $session->attendance, 'no' ); ?>>Did Not Attend</option>
										</select>
									</form>
								</td>
								<td>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-sessions&action=view&id=' . $session->ID ); ?>" class="button button-small">View</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No sessions found matching the current filters.</p>
			<?php endif; ?>
		</div>
	</div>

	<style>
	.filters-form {
		display: flex;
		gap: 20px;
		align-items: center;
		flex-wrap: wrap;
	}
	.filters-form label {
		display: flex;
		align-items: center;
		gap: 5px;
	}
	.ai-badge {
		background: #28a745;
		color: white;
		padding: 2px 6px;
		border-radius: 3px;
		font-size: 11px;
		font-weight: bold;
	}
	.status-waiting { color: #ffc107; }
	.status-open { color: #007cba; }
	.status-past { color: #6c757d; }
	</style>
	<?php
}

/**
 * Enhanced Coupons Page
 */
function snks_enhanced_ai_coupons_page() {
	global $wpdb;
	
	// Handle coupon creation/editing
	if ( isset( $_POST['action'] ) ) {
		if ( $_POST['action'] === 'create_coupon' && wp_verify_nonce( $_POST['_wpnonce'], 'create_ai_coupon' ) ) {
			$code = sanitize_text_field( $_POST['code'] );
			$discount_type = sanitize_text_field( $_POST['discount_type'] );
			$discount_value = floatval( $_POST['discount_value'] );
			$usage_limit = intval( $_POST['usage_limit'] );
			$expiry_date = sanitize_text_field( $_POST['expiry_date'] );
			$segment = sanitize_text_field( $_POST['segment'] );
			
			$wpdb->insert(
				$wpdb->prefix . 'snks_ai_coupons',
				array(
					'code' => $code,
					'discount_type' => $discount_type,
					'discount_value' => $discount_value,
					'usage_limit' => $usage_limit,
					'current_usage' => 0,
					'expiry_date' => $expiry_date,
					'segment' => $segment,
					'active' => 1,
				),
				array( '%s', '%s', '%f', '%d', '%d', '%s', '%s', '%d' )
			);
			
			echo '<div class="notice notice-success"><p>Coupon created successfully!</p></div>';
		}
		
		if ( $_POST['action'] === 'delete_coupon' && wp_verify_nonce( $_POST['_wpnonce'], 'delete_ai_coupon' ) ) {
			$coupon_id = intval( $_POST['coupon_id'] );
			$wpdb->delete( $wpdb->prefix . 'snks_ai_coupons', array( 'id' => $coupon_id ), array( '%d' ) );
			echo '<div class="notice notice-success"><p>Coupon deleted successfully!</p></div>';
		}
	}
	
	$coupons = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_ai_coupons ORDER BY created_at DESC" );
	?>
	<div class="wrap">
		<h1>AI Coupons Management</h1>
		
		<div class="card">
			<h2>Create New Coupon</h2>
			<form method="post" class="coupon-form">
				<?php wp_nonce_field( 'create_ai_coupon' ); ?>
				<input type="hidden" name="action" value="create_coupon">
				
				<table class="form-table">
					<tr>
						<th><label for="code">Coupon Code</label></th>
						<td><input type="text" id="code" name="code" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="discount_type">Discount Type</label></th>
						<td>
							<select id="discount_type" name="discount_type" required>
								<option value="percentage">Percentage</option>
								<option value="fixed">Fixed Amount</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="discount_value">Discount Value</label></th>
						<td><input type="number" id="discount_value" name="discount_value" min="0" step="0.01" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="usage_limit">Usage Limit</label></th>
						<td><input type="number" id="usage_limit" name="usage_limit" min="0" class="regular-text" value="0" title="0 = unlimited"></td>
					</tr>
					<tr>
						<th><label for="expiry_date">Expiry Date</label></th>
						<td><input type="date" id="expiry_date" name="expiry_date" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="segment">Segment</label></th>
						<td>
							<select id="segment" name="segment">
								<option value="">All Users</option>
								<option value="new_users">New Users Only</option>
								<option value="returning_users">Returning Users Only</option>
								<option value="specific_diagnosis">Specific Diagnosis</option>
							</select>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Create Coupon' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Existing Coupons</h2>
			<?php if ( $coupons ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Code</th>
							<th>Type</th>
							<th>Value</th>
							<th>Usage</th>
							<th>Expiry</th>
							<th>Segment</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $coupons as $coupon ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $coupon->code ); ?></strong></td>
								<td><?php echo esc_html( ucfirst( $coupon->discount_type ) ); ?></td>
								<td>
									<?php 
									if ( $coupon->discount_type === 'percentage' ) {
										echo esc_html( $coupon->discount_value ) . '%';
									} else {
										echo '$' . esc_html( $coupon->discount_value );
									}
									?>
								</td>
								<td>
									<?php 
									if ( $coupon->usage_limit > 0 ) {
										echo esc_html( $coupon->current_usage ) . '/' . esc_html( $coupon->usage_limit );
									} else {
										echo esc_html( $coupon->current_usage ) . ' (unlimited)';
									}
									?>
								</td>
								<td>
									<?php 
									if ( $coupon->expiry_date ) {
										echo esc_html( $coupon->expiry_date );
									} else {
										echo 'No expiry';
									}
									?>
								</td>
								<td><?php echo esc_html( $coupon->segment ?: 'All Users' ); ?></td>
								<td>
									<span class="status-<?php echo $coupon->active ? 'active' : 'inactive'; ?>">
										<?php echo $coupon->active ? 'Active' : 'Inactive'; ?>
									</span>
								</td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'delete_ai_coupon' ); ?>
										<input type="hidden" name="action" value="delete_coupon">
										<input type="hidden" name="coupon_id" value="<?php echo $coupon->id; ?>">
										<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure you want to delete this coupon?')">Delete</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No coupons found.</p>
			<?php endif; ?>
		</div>
	</div>

	<style>
	.coupon-form .form-table th {
		width: 150px;
	}
	.status-active { color: #28a745; }
	.status-inactive { color: #6c757d; }
	</style>
	<?php
} 

/**
 * Enhanced Analytics Page
 */
function snks_enhanced_ai_analytics_page() {
	global $wpdb;
	
	// Get analytics data
	$total_ai_users = count( get_users( array( 
		'meta_query' => array(
			array( 'key' => 'registration_source', 'value' => 'jalsah_ai', 'compare' => '=' )
		)
	) ) );
	
	$total_ai_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE from_jalsah_ai = 1" );
	$completed_ai_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE from_jalsah_ai = 1 AND status = 'completed'" );
	
	// Get retention data
	$retention_data = $wpdb->get_results( "
		SELECT t.user_id, u.display_name as therapist_name, COUNT(DISTINCT o.customer_id) as repeat_patients
		FROM {$wpdb->prefix}snks_provider_timetable t
		JOIN {$wpdb->prefix}wc_orders o ON t.order_id = o.id
		JOIN {$wpdb->users} u ON t.user_id = u.ID
		WHERE o.from_jalsah_ai = 1 AND o.status = 'completed'
		GROUP BY t.user_id
		HAVING repeat_patients > 1
		ORDER BY repeat_patients DESC
		LIMIT 10
	" );
	
	// Get diagnosis booking data
	$diagnosis_bookings = $wpdb->get_results( "
		SELECT d.name, COUNT(*) as booking_count
		FROM {$wpdb->prefix}snks_diagnoses d
		JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON d.id = td.diagnosis_id
		JOIN {$wpdb->prefix}snks_provider_timetable t ON td.therapist_id = t.user_id
		JOIN {$wpdb->prefix}wc_orders o ON t.order_id = o.id
		WHERE o.from_jalsah_ai = 1
		GROUP BY d.id
		ORDER BY booking_count DESC
	" );
	
	?>
	<div class="wrap">
		<h1>AI Analytics & Reporting</h1>
		
		<div class="analytics-grid">
			<div class="card">
				<h2>Overview</h2>
				<div class="stats-grid">
					<div class="stat-item">
						<h3><?php echo $total_ai_users; ?></h3>
						<p>Total AI Users</p>
					</div>
					<div class="stat-item">
						<h3><?php echo $total_ai_orders; ?></h3>
						<p>Total AI Orders</p>
					</div>
					<div class="stat-item">
						<h3><?php echo $completed_ai_orders; ?></h3>
						<p>Completed Orders</p>
					</div>
					<div class="stat-item">
						<h3><?php echo $total_ai_orders > 0 ? round( ( $completed_ai_orders / $total_ai_orders ) * 100, 1 ) : 0; ?>%</h3>
						<p>Completion Rate</p>
					</div>
				</div>
			</div>
			
			<div class="card">
				<h2>Retention Leaderboard</h2>
				<p>Therapists with the most repeat patients:</p>
				<?php if ( $retention_data ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Rank</th>
								<th>Therapist</th>
								<th>Repeat Patients</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $retention_data as $index => $therapist ) : ?>
								<tr>
									<td>#<?php echo $index + 1; ?></td>
									<td><?php echo esc_html( $therapist->therapist_name ); ?></td>
									<td><?php echo esc_html( $therapist->repeat_patients ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>No retention data available yet.</p>
				<?php endif; ?>
			</div>
			
			<div class="card">
				<h2>Diagnosis Booking Trends</h2>
				<?php if ( $diagnosis_bookings ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Diagnosis</th>
								<th>Bookings</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $diagnosis_bookings as $diagnosis ) : ?>
								<tr>
									<td><?php echo esc_html( $diagnosis->name ); ?></td>
									<td><?php echo esc_html( $diagnosis->booking_count ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>No diagnosis booking data available yet.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<style>
	.analytics-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
		gap: 20px;
		margin: 20px 0;
	}
	</style>
	<?php
}

/**
 * ChatGPT Integration Page
 */
function snks_enhanced_ai_chatgpt_page() {
	// Handle settings updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_chatgpt_settings' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_chatgpt_settings' ) ) {
			update_option( 'snks_ai_chatgpt_api_key', sanitize_text_field( $_POST['api_key'] ) );
			update_option( 'snks_ai_chatgpt_model', sanitize_text_field( $_POST['model'] ) );
			update_option( 'snks_ai_chatgpt_prompt', sanitize_textarea_field( $_POST['prompt'] ) );
			update_option( 'snks_ai_chatgpt_max_tokens', intval( $_POST['max_tokens'] ) );
			update_option( 'snks_ai_chatgpt_temperature', floatval( $_POST['temperature'] ) );
			
			echo '<div class="notice notice-success"><p>ChatGPT settings updated successfully!</p></div>';
		}
	}
	
	// Handle test request
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'test_chatgpt' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'test_chatgpt' ) ) {
			$test_result = snks_test_chatgpt_integration( $_POST['test_prompt'] );
			echo '<div class="notice notice-info"><p><strong>Test Result:</strong> ' . esc_html( $test_result ) . '</p></div>';
		}
	}
	
	$api_key = get_option( 'snks_ai_chatgpt_api_key', '' );
	$model = get_option( 'snks_ai_chatgpt_model', 'gpt-3.5-turbo' );
	$prompt = get_option( 'snks_ai_chatgpt_prompt', 'Based on the patient symptoms, recommend the most suitable diagnosis from the available list.' );
	$max_tokens = get_option( 'snks_ai_chatgpt_max_tokens', 150 );
	$temperature = get_option( 'snks_ai_chatgpt_temperature', 0.7 );
	?>
	<div class="wrap">
		<h1>ChatGPT Integration</h1>
		
		<div class="card">
			<h2>API Configuration</h2>
			<form method="post">
				<?php wp_nonce_field( 'update_chatgpt_settings' ); ?>
				<input type="hidden" name="action" value="update_chatgpt_settings">
				
				<table class="form-table">
					<tr>
						<th><label for="api_key">OpenAI API Key</label></th>
						<td><input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="model">Model</label></th>
						<td>
							<select id="model" name="model">
								<option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo</option>
								<option value="gpt-4" <?php selected( $model, 'gpt-4' ); ?>>GPT-4</option>
								<option value="gpt-4-turbo" <?php selected( $model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="prompt">System Prompt</label></th>
						<td><textarea id="prompt" name="prompt" rows="4" class="large-text"><?php echo esc_textarea( $prompt ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="max_tokens">Max Tokens</label></th>
						<td><input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr( $max_tokens ); ?>" min="1" max="4000" class="small-text"></td>
					</tr>
					<tr>
						<th><label for="temperature">Temperature</label></th>
						<td><input type="number" id="temperature" name="temperature" value="<?php echo esc_attr( $temperature ); ?>" min="0" max="2" step="0.1" class="small-text"></td>
					</tr>
				</table>
				
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Test Integration</h2>
			<form method="post">
				<?php wp_nonce_field( 'test_chatgpt' ); ?>
				<input type="hidden" name="action" value="test_chatgpt">
				
				<table class="form-table">
					<tr>
						<th><label for="test_prompt">Test Prompt</label></th>
						<td><textarea id="test_prompt" name="test_prompt" rows="3" class="large-text" placeholder="Enter patient symptoms to test diagnosis recommendation..."></textarea></td>
					</tr>
				</table>
				
				<?php submit_button( 'Test ChatGPT', 'secondary' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Available Diagnoses</h2>
			<?php
			global $wpdb;
			$diagnoses = $wpdb->get_results( "SELECT name FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
			if ( $diagnoses ) {
				echo '<ul>';
				foreach ( $diagnoses as $diagnosis ) {
					echo '<li>' . esc_html( $diagnosis->name ) . '</li>';
				}
				echo '</ul>';
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Test ChatGPT Integration
 */
function snks_test_chatgpt_integration( $test_prompt ) {
	$api_key = get_option( 'snks_ai_chatgpt_api_key' );
	$model = get_option( 'snks_ai_chatgpt_model', 'gpt-3.5-turbo' );
	$system_prompt = get_option( 'snks_ai_chatgpt_prompt' );
	$max_tokens = get_option( 'snks_ai_chatgpt_max_tokens', 150 );
	$temperature = get_option( 'snks_ai_chatgpt_temperature', 0.7 );
	
	if ( ! $api_key ) {
		return 'Error: API key not configured';
	}
	
	// Get available diagnoses
	global $wpdb;
	$diagnoses = $wpdb->get_results( "SELECT name FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
	$diagnosis_list = array();
	foreach ( $diagnoses as $diagnosis ) {
		$diagnosis_list[] = $diagnosis->name;
	}
	
	$data = array(
		'model' => $model,
		'messages' => array(
			array(
				'role' => 'system',
				'content' => $system_prompt . ' Available diagnoses: ' . implode( ', ', $diagnosis_list )
			),
			array(
				'role' => 'user',
				'content' => $test_prompt
			)
		),
		'max_tokens' => $max_tokens,
		'temperature' => $temperature
	);
	
	$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type' => 'application/json'
		),
		'body' => json_encode( $data ),
		'timeout' => 30
	) );
	
	if ( is_wp_error( $response ) ) {
		return 'Error: ' . $response->get_error_message();
	}
	
	$body = wp_remote_retrieve_body( $response );
	$result = json_decode( $body, true );
	
	if ( isset( $result['choices'][0]['message']['content'] ) ) {
		return $result['choices'][0]['message']['content'];
	} else {
		return 'Error: Invalid response from OpenAI API';
	}
}

/**
 * WhatsApp Integration Page
 */
function snks_enhanced_ai_whatsapp_page() {
	// Handle settings updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_whatsapp_settings' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_whatsapp_settings' ) ) {
			update_option( 'snks_ai_whatsapp_access_token', sanitize_text_field( $_POST['access_token'] ) );
			update_option( 'snks_ai_whatsapp_phone_number_id', sanitize_text_field( $_POST['phone_number_id'] ) );
			update_option( 'snks_ai_whatsapp_business_account_id', sanitize_text_field( $_POST['business_account_id'] ) );
			
			echo '<div class="notice notice-success"><p>WhatsApp settings updated successfully!</p></div>';
		}
	}
	
	// Handle template updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_template' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_whatsapp_template' ) ) {
			$template_name = sanitize_text_field( $_POST['template_name'] );
			$template_content = sanitize_textarea_field( $_POST['template_content'] );
			update_option( 'snks_ai_whatsapp_template_' . $template_name, $template_content );
			echo '<div class="notice notice-success"><p>Template updated successfully!</p></div>';
		}
	}
	
	$access_token = get_option( 'snks_ai_whatsapp_access_token', '' );
	$phone_number_id = get_option( 'snks_ai_whatsapp_phone_number_id', '' );
	$business_account_id = get_option( 'snks_ai_whatsapp_business_account_id', '' );
	
	$templates = array(
		'booking_confirmation' => 'Booking Confirmation',
		'reschedule_alert' => 'Reschedule Alert',
		'reminder_22h' => '22h Reminder',
		'reminder_1h' => '1h Reminder',
		'therapist_joined' => 'Therapist Joined Session',
		'prescription_requested' => 'Prescription Requested',
		'marketing_campaign' => 'Marketing Campaign'
	);
	?>
	<div class="wrap">
		<h1>WhatsApp Cloud API Integration</h1>
		
		<div class="card">
			<h2>API Configuration</h2>
			<form method="post">
				<?php wp_nonce_field( 'update_whatsapp_settings' ); ?>
				<input type="hidden" name="action" value="update_whatsapp_settings">
				
				<table class="form-table">
					<tr>
						<th><label for="access_token">Access Token</label></th>
						<td><input type="password" id="access_token" name="access_token" value="<?php echo esc_attr( $access_token ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="phone_number_id">Phone Number ID</label></th>
						<td><input type="text" id="phone_number_id" name="phone_number_id" value="<?php echo esc_attr( $phone_number_id ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="business_account_id">Business Account ID</label></th>
						<td><input type="text" id="business_account_id" name="business_account_id" value="<?php echo esc_attr( $business_account_id ); ?>" class="regular-text"></td>
					</tr>
				</table>
				
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Message Templates</h2>
			<?php foreach ( $templates as $template_key => $template_name ) : ?>
				<div class="template-section">
					<h3><?php echo esc_html( $template_name ); ?></h3>
					<form method="post">
						<?php wp_nonce_field( 'update_whatsapp_template' ); ?>
						<input type="hidden" name="action" value="update_template">
						<input type="hidden" name="template_name" value="<?php echo esc_attr( $template_key ); ?>">
						
						<textarea name="template_content" rows="4" class="large-text" placeholder="Enter template content..."><?php echo esc_textarea( get_option( 'snks_ai_whatsapp_template_' . $template_key, '' ) ); ?></textarea>
						
						<p class="description">
							Available variables: {{patient_name}}, {{therapist_name}}, {{session_date}}, {{session_time}}, {{diagnosis}}, {{prescription_link}}
						</p>
						
						<?php submit_button( 'Update Template', 'secondary' ); ?>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<style>
	.template-section {
		margin-bottom: 30px;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}
	.template-section h3 {
		margin-top: 0;
	}
	</style>
	<?php
}

/**
 * Rochtah Integration Page
 */
function snks_enhanced_ai_rochtah_page() {
	// Handle settings updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_rochtah_settings' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_rochtah_settings' ) ) {
			update_option( 'snks_ai_rochtah_enabled', isset( $_POST['enabled'] ) ? '1' : '0' );
			update_option( 'snks_ai_rochtah_available_days', serialize( $_POST['available_days'] ) );
			update_option( 'snks_ai_rochtah_time_ranges', serialize( $_POST['time_ranges'] ) );
			
			echo '<div class="notice notice-success"><p>Rochtah settings updated successfully!</p></div>';
		}
	}
	
	$enabled = get_option( 'snks_ai_rochtah_enabled', '0' );
	$available_days = unserialize( get_option( 'snks_ai_rochtah_available_days', serialize( array() ) ) );
	$time_ranges = unserialize( get_option( 'snks_ai_rochtah_time_ranges', serialize( array() ) ) );
	
	$days = array(
		'monday' => 'Monday',
		'tuesday' => 'Tuesday',
		'wednesday' => 'Wednesday',
		'thursday' => 'Thursday',
		'friday' => 'Friday',
		'saturday' => 'Saturday',
		'sunday' => 'Sunday'
	);
	?>
	<div class="wrap">
		<h1>Rochtah Integration</h1>
		
		<div class="card">
			<h2>General Settings</h2>
			<form method="post">
				<?php wp_nonce_field( 'update_rochtah_settings' ); ?>
				<input type="hidden" name="action" value="update_rochtah_settings">
				
				<table class="form-table">
					<tr>
						<th><label for="enabled">Enable Rochtah</label></th>
						<td><input type="checkbox" id="enabled" name="enabled" value="1" <?php checked( $enabled, '1' ); ?>></td>
					</tr>
				</table>
				
				<h3>Available Days</h3>
				<?php foreach ( $days as $day_key => $day_name ) : ?>
					<label>
						<input type="checkbox" name="available_days[]" value="<?php echo esc_attr( $day_key ); ?>" 
							<?php checked( in_array( $day_key, $available_days ) ); ?>>
						<?php echo esc_html( $day_name ); ?>
					</label><br>
				<?php endforeach; ?>
				
				<h3>Time Ranges</h3>
				<?php foreach ( $days as $day_key => $day_name ) : ?>
					<div class="time-range-section">
						<h4><?php echo esc_html( $day_name ); ?></h4>
						<label>
							Start Time:
							<input type="time" name="time_ranges[<?php echo esc_attr( $day_key ); ?>][start]" 
								value="<?php echo esc_attr( $time_ranges[ $day_key ]['start'] ?? '09:00' ); ?>">
						</label>
						<label>
							End Time:
							<input type="time" name="time_ranges[<?php echo esc_attr( $day_key ); ?>][end]" 
								value="<?php echo esc_attr( $time_ranges[ $day_key ]['end'] ?? '17:00' ); ?>">
						</label>
					</div>
				<?php endforeach; ?>
				
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Rochtah Doctor Dashboard</h2>
			<?php
			global $wpdb;
			$rochtah_bookings = $wpdb->get_results( "
				SELECT rb.*, u.display_name as patient_name, u.user_email as patient_email,
				       t.display_name as therapist_name, d.name as diagnosis_name
				FROM {$wpdb->prefix}snks_rochtah_bookings rb
				LEFT JOIN {$wpdb->users} u ON rb.patient_id = u.ID
				LEFT JOIN {$wpdb->users} t ON rb.therapist_id = t.ID
				LEFT JOIN {$wpdb->prefix}snks_diagnoses d ON rb.diagnosis_id = d.id
				WHERE rb.status = 'confirmed'
				ORDER BY rb.booking_date, rb.booking_time
			" );
			?>
			
			<?php if ( $rochtah_bookings ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Time</th>
							<th>Patient</th>
							<th>Email</th>
							<th>Referring Therapist</th>
							<th>Diagnosis</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rochtah_bookings as $booking ) : ?>
							<tr>
								<td><?php echo esc_html( $booking->booking_date ); ?></td>
								<td><?php echo esc_html( $booking->booking_time ); ?></td>
								<td><?php echo esc_html( $booking->patient_name ); ?></td>
								<td><?php echo esc_html( $booking->patient_email ); ?></td>
								<td><?php echo esc_html( $booking->therapist_name ); ?></td>
								<td><?php echo esc_html( $booking->diagnosis_name ); ?></td>
								<td>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-rochtah&action=write_prescription&id=' . $booking->id ); ?>" class="button button-small">Write Prescription</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No confirmed Rochtah bookings found.</p>
			<?php endif; ?>
		</div>
	</div>

	<style>
	.time-range-section {
		margin: 10px 0;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 3px;
	}
	.time-range-section label {
		margin-right: 20px;
	}
	</style>
	<?php
}

/**
 * Email Settings Page
 */
function snks_enhanced_ai_email_page() {
	// Handle settings updates
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_email_settings' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_email_settings' ) ) {
			update_option( 'snks_ai_email_new_booking', isset( $_POST['new_booking'] ) ? '1' : '0' );
			update_option( 'snks_ai_email_new_user', isset( $_POST['new_user'] ) ? '1' : '0' );
			update_option( 'snks_ai_email_rochtah_request', isset( $_POST['rochtah_request'] ) ? '1' : '0' );
			
			// Update email templates
			update_option( 'snks_ai_email_new_booking_template', sanitize_textarea_field( $_POST['new_booking_template'] ) );
			update_option( 'snks_ai_email_new_user_template', sanitize_textarea_field( $_POST['new_user_template'] ) );
			update_option( 'snks_ai_email_rochtah_template', sanitize_textarea_field( $_POST['rochtah_template'] ) );
			
			echo '<div class="notice notice-success"><p>Email settings updated successfully!</p></div>';
		}
	}
	
	$new_booking = get_option( 'snks_ai_email_new_booking', '1' );
	$new_user = get_option( 'snks_ai_email_new_user', '1' );
	$rochtah_request = get_option( 'snks_ai_email_rochtah_request', '1' );
	
	$new_booking_template = get_option( 'snks_ai_email_new_booking_template', 'Your booking has been confirmed with {{therapist_name}} on {{session_date}} at {{session_time}}.' );
	$new_user_template = get_option( 'snks_ai_email_new_user_template', 'Welcome to Jalsah AI! Your account has been created successfully.' );
	$rochtah_template = get_option( 'snks_ai_email_rochtah_template', 'Your prescription request has been received. Please confirm to proceed with the Rochtah consultation.' );
	?>
	<div class="wrap">
		<h1>Email Notification Settings</h1>
		
		<div class="card">
			<h2>Email Notifications</h2>
			<form method="post">
				<?php wp_nonce_field( 'update_email_settings' ); ?>
				<input type="hidden" name="action" value="update_email_settings">
				
				<table class="form-table">
					<tr>
						<th><label for="new_booking">New AI Booking</label></th>
						<td><input type="checkbox" id="new_booking" name="new_booking" value="1" <?php checked( $new_booking, '1' ); ?>></td>
					</tr>
					<tr>
						<th><label for="new_user">New AI User Registration</label></th>
						<td><input type="checkbox" id="new_user" name="new_user" value="1" <?php checked( $new_user, '1' ); ?>></td>
					</tr>
					<tr>
						<th><label for="rochtah_request">Rochtah Request</label></th>
						<td><input type="checkbox" id="rochtah_request" name="rochtah_request" value="1" <?php checked( $rochtah_request, '1' ); ?>></td>
					</tr>
				</table>
				
				<h3>Email Templates</h3>
				
				<h4>New Booking Template</h4>
				<textarea name="new_booking_template" rows="4" class="large-text"><?php echo esc_textarea( $new_booking_template ); ?></textarea>
				
				<h4>New User Template</h4>
				<textarea name="new_user_template" rows="4" class="large-text"><?php echo esc_textarea( $new_user_template ); ?></textarea>
				
				<h4>Rochtah Request Template</h4>
				<textarea name="rochtah_template" rows="4" class="large-text"><?php echo esc_textarea( $rochtah_template ); ?></textarea>
				
				<p class="description">
					Available variables: {{patient_name}}, {{therapist_name}}, {{session_date}}, {{session_time}}, {{diagnosis}}, {{prescription_link}}
				</p>
				
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Admin Tools Page
 */
function snks_enhanced_ai_tools_page() {
	// Handle switch user action
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'switch_user' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'switch_user' ) ) {
			$user_id = intval( $_POST['user_id'] );
			$user = get_user_by( 'ID', $user_id );
			
			if ( $user ) {
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
				echo '<div class="notice notice-success"><p>Switched to user: ' . esc_html( $user->display_name ) . '</p></div>';
			}
		}
	}
	
	// Get AI users
	$ai_users = get_users( array( 
		'meta_query' => array(
			array( 'key' => 'registration_source', 'value' => 'jalsah_ai', 'compare' => '=' )
		),
		'number' => 50
	) );
	?>
	<div class="wrap">
		<h1>Admin Tools</h1>
		
		<div class="card">
			<h2>Switch User</h2>
			<form method="post">
				<?php wp_nonce_field( 'switch_user' ); ?>
				<input type="hidden" name="action" value="switch_user">
				
				<label>
					Select User:
					<select name="user_id" required>
						<option value="">Choose a user...</option>
						<?php foreach ( $ai_users as $user ) : ?>
							<option value="<?php echo $user->ID; ?>">
								<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				
				<?php submit_button( 'Switch to User', 'secondary' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>User Filters</h2>
			<p>Use these filters in the Users page to find specific users:</p>
			<ul>
				<li><strong>AI Users:</strong> <code>registration_source = jalsah_ai</code></li>
				<li><strong>AI Therapists:</strong> <code>show_on_ai_site = 1</code></li>
				<li><strong>Users with specific diagnosis:</strong> Check therapist-diagnosis assignments</li>
			</ul>
		</div>
		
		<div class="card">
			<h2>Quick Actions</h2>
			<div class="quick-actions">
				<a href="<?php echo admin_url( 'users.php?meta_key=registration_source&meta_value=jalsah_ai' ); ?>" class="button">View AI Users</a>
				<a href="<?php echo admin_url( 'users.php?role=doctor&meta_key=show_on_ai_site&meta_value=1' ); ?>" class="button">View AI Therapists</a>
				<a href="<?php echo admin_url( 'edit.php?post_type=shop_order&meta_key=from_jalsah_ai&meta_value=1' ); ?>" class="button">View AI Orders</a>
			</div>
		</div>
	</div>

	<style>
	.quick-actions {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
	}
	</style>
	<?php
} 