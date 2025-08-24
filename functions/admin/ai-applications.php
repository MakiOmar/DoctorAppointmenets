<?php
/**
 * Custom Therapist Applications Management System
 * Admin-only interface without WordPress post types
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Table creation function moved to main plugin file

/**
 * Enhanced Therapist Applications Page
 */
function snks_enhanced_ai_applications_page() {
	snks_load_ai_admin_styles();
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	// Handle bulk actions
	if ( isset( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'bulk-applications' ) ) {
			$action = $_POST['action'];
			$application_ids = isset( $_POST['application_ids'] ) ? array_map( 'intval', $_POST['application_ids'] ) : [];
			
			if ( !empty( $application_ids ) ) {
				$processed = 0;
				
				foreach ( $application_ids as $app_id ) {
					$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $app_id ) );
					if ( $application ) {
						switch ( $action ) {
							case 'approve':
								if ( $application->status === 'pending' ) {
									snks_approve_therapist_application( $app_id );
									$processed++;
								}
								break;
							case 'reject':
								$wpdb->update( $table_name, ['status' => 'rejected'], ['id' => $app_id] );
								$processed++;
								break;
							case 'delete':
								$wpdb->delete( $table_name, ['id' => $app_id] );
								$processed++;
								break;
						}
					}
				}
				
				if ( $processed > 0 ) {
					echo '<div class="notice notice-success"><p>' . sprintf( '%d application(s) processed successfully.', $processed ) . '</p></div>';
				}
			}
		}
	}
	
	// Handle individual actions
	if ( isset( $_GET['action'] ) && isset( $_GET['application_id'] ) && isset( $_GET['_wpnonce'] ) ) {
		$action = $_GET['action'];
		$app_id = intval( $_GET['application_id'] );
		
		if ( wp_verify_nonce( $_GET['_wpnonce'], 'application_' . $action . '_' . $app_id ) ) {
			$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $app_id ) );
			if ( $application ) {
				switch ( $action ) {
					case 'approve':
						if ( $application->status === 'pending' ) {
							snks_approve_therapist_application( $app_id );
							echo '<div class="notice notice-success"><p>Application approved successfully!</p></div>';
						}
						break;
					case 'reject':
						$wpdb->update( $table_name, ['status' => 'rejected'], ['id' => $app_id] );
						echo '<div class="notice notice-success"><p>Application rejected.</p></div>';
						break;
					case 'view':
						snks_display_application_details( $app_id );
						return;
					case 'edit':
						snks_display_application_edit_form( $app_id );
						return;

				}
			}
		}
	}
	
	// Handle form submission for editing
	if ( isset( $_POST['save_application'] ) && isset( $_POST['application_id'] ) ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'save_application_' . $_POST['application_id'] ) ) {
			snks_save_application_data( $_POST['application_id'] );
			echo '<div class="notice notice-success"><p>Application updated successfully!</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
		}
	}
	

	
	// Get applications with filters
	$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
	$search_filter = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
	
	$where_clauses = [];
	$where_values = [];
	
	if ( $status_filter ) {
		$where_clauses[] = 'status = %s';
		$where_values[] = $status_filter;
	}
	
	if ( $search_filter ) {
		$where_clauses[] = '(name LIKE %s OR name_en LIKE %s OR email LIKE %s OR phone LIKE %s)';
		$where_values[] = '%' . $search_filter . '%';
		$where_values[] = '%' . $search_filter . '%';
		$where_values[] = '%' . $search_filter . '%';
		$where_values[] = '%' . $search_filter . '%';
	}
	
	$where_sql = '';
	if ( !empty( $where_clauses ) ) {
		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
	}
	
	$applications = $wpdb->get_results( $wpdb->prepare( 
		"SELECT * FROM $table_name $where_sql ORDER BY created_at DESC",
		$where_values
	) );
	
	// Count by status
	$pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'pending'" );
	$approved_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'approved'" );
	$rejected_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'" );
	?>
	
	<div class="wrap">
		<h1>Therapist Applications & Profiles</h1>
		
		<!-- Status Tabs -->
		<div class="nav-tab-wrapper">
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications' ); ?>" 
			   class="nav-tab <?php echo !$status_filter ? 'nav-tab-active' : ''; ?>">
				All <span class="count">(<?php echo count( $applications ); ?>)</span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&status=pending' ); ?>" 
			   class="nav-tab <?php echo $status_filter === 'pending' ? 'nav-tab-active' : ''; ?>">
				Pending <span class="count">(<?php echo $pending_count; ?>)</span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&status=approved' ); ?>" 
			   class="nav-tab <?php echo $status_filter === 'approved' ? 'nav-tab-active' : ''; ?>">
				Active Profiles <span class="count">(<?php echo $approved_count; ?>)</span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&status=rejected' ); ?>" 
			   class="nav-tab <?php echo $status_filter === 'rejected' ? 'nav-tab-active' : ''; ?>">
				Rejected <span class="count">(<?php echo $rejected_count; ?>)</span>
			</a>
		</div>
		
		<!-- Search and Filters -->
		<div class="tablenav top">
			<form method="get" class="alignleft actions">
				<input type="hidden" name="page" value="jalsah-ai-applications">
				<input type="text" name="s" value="<?php echo esc_attr( $search_filter ); ?>" placeholder="Search applications/profiles...">
				<?php submit_button( 'Search', 'secondary', 'search', false ); ?>
			</form>
		</div>
		
		<!-- Applications Table -->
		<form method="post">
			<?php wp_nonce_field( 'bulk-applications' ); ?>
			
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="action">
						<option value="-1">Bulk Actions</option>
						<option value="approve">Approve</option>
						<option value="reject">Reject</option>
						<option value="delete">Delete</option>
					</select>
					<?php submit_button( 'Apply', 'secondary', 'submit', false ); ?>
				</div>
			</div>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" id="cb-select-all-1">
						</td>
						<th class="manage-column column-name">Name</th>
						<th class="manage-column column-email">Email</th>
						<th class="manage-column column-phone">Phone</th>
						<th class="manage-column column-specialty">Specialty</th>
						<th class="manage-column column-status">Status</th>
						<th class="manage-column column-date">Date</th>
						<th class="manage-column column-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $applications ) ) : ?>
						<tr>
							<td colspan="8" class="no-items">No applications found.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $applications as $app ) : ?>
							<tr>
								<th class="check-column">
									<input type="checkbox" name="application_ids[]" value="<?php echo $app->id; ?>">
								</th>
								<td class="column-name">
									<strong>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=view&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_view_' . $app->id ) ); ?>">
											<?php echo esc_html( $app->name ?: $app->name_en ?: 'Unknown' ); ?>
										</a>
									</strong>
									<?php if ( $app->name_en && $app->name_en !== $app->name ) : ?>
										<br><small><?php echo esc_html( $app->name_en ); ?></small>
									<?php endif; ?>
								</td>
								<td class="column-email">
									<?php echo esc_html( $app->email ); ?>
								</td>
								<td class="column-phone">
									<?php echo esc_html( $app->phone ); ?>
								</td>
								<td class="column-specialty">
									<?php echo esc_html( $app->doctor_specialty ); ?>
								</td>
								<td class="column-status">
									<?php
									$status_labels = [
										'pending' => '<span class="status-pending">Pending</span>',
										'approved' => '<span class="status-approved">Active</span>',
										'rejected' => '<span class="status-rejected">Rejected</span>'
									];
									echo $status_labels[ $app->status ] ?? $app->status;
									?>
								</td>
								<td class="column-date">
									<?php echo date( 'Y-m-d H:i', strtotime( $app->created_at ) ); ?>
								</td>
								<td class="column-actions">
									<?php if ( $app->status === 'pending' ) : ?>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $app->id ) ); ?>" 
										   class="button button-small button-primary">Approve</a>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=reject&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_reject_' . $app->id ) ); ?>" 
										   class="button button-small">Reject</a>
									<?php elseif ( $app->status === 'rejected' ) : ?>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $app->id ) ); ?>" 
										   class="button button-small button-primary">Approve</a>
									<?php endif; ?>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=view&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_view_' . $app->id ) ); ?>" 
									   class="button button-small">View</a>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=edit&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_edit_' . $app->id ) ); ?>" 
									   class="button button-small">Edit Profile</a>

								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</form>
	</div>
	
	<style>
	.status-pending { color: #f56e28; font-weight: bold; }
	.status-approved { color: #46b450; font-weight: bold; }
	.status-rejected { color: #dc3232; font-weight: bold; }
	.count { background: #e5e5e5; padding: 2px 6px; border-radius: 10px; font-size: 11px; }
	</style>
	<?php
}

/**
 * Approve therapist application and create minimal user account
 */
function snks_approve_therapist_application( $application_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND status = 'pending'", $application_id ) );
	if ( !$application ) {
		return false;
	}
	
	// Check if user already exists
	$existing_user = get_user_by( 'email', $application->email );
	if ( $existing_user ) {
		$user_id = $existing_user->ID;
	} else {
		// Create new user with minimal data
		$password = wp_generate_password( 8, false );
		if ( username_exists( $application->phone ) ) {
			return false;
		}
		$user_id = wp_create_user( $application->phone, $password, $application->email );
		if ( is_wp_error( $user_id ) ) {
			return false;
		}
	}
	
	$user = get_user_by( 'id', $user_id );
	$user->set_role( 'doctor' );
	
	// Set only essential user meta for login purposes
	update_user_meta( $user_id, 'billing_phone', $application->phone );
	update_user_meta( $user_id, 'billing_email', $application->email );
	
	// Update application status and link to user
	$wpdb->update( $table_name, [
		'status' => 'approved',
		'user_id' => $user_id
	], ['id' => $application_id] );
	
	// Notify user
	$email_subject = __( 'Your therapist application is approved' );
	$email_message = sprintf(
		__( 'Your account has been created successfully!' ) . "\n\n" .
		__( 'Username: %s' ) . "\n" .
		__( 'Password: %s' ) . "\n\n" .
		__( 'You can now log in to your account and start using the platform.' ),
		$application->phone,
		$password
	);
	wp_mail( $application->email, $email_subject, $email_message );
	
	return true;
}

/**
 * Display application/profile details
 */
function snks_display_application_details( $application_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $application_id ) );
	if ( !$application ) {
		wp_die( 'Application not found.' );
	}
	?>
	<div class="wrap">
		<h1><?php echo $application->status === 'approved' ? 'Therapist Profile' : 'Application Details'; ?></h1>
		
		<div class="card">
			<h2>Basic Information</h2>
			<table class="form-table">
				<tr>
					<th>Name (Arabic)</th>
					<td><?php echo esc_html( $application->name ); ?></td>
				</tr>
				<tr>
					<th>Name (English)</th>
					<td><?php echo esc_html( $application->name_en ); ?></td>
				</tr>
				<tr>
					<th>Email</th>
					<td><?php echo esc_html( $application->email ); ?></td>
				</tr>
				<tr>
					<th>Phone</th>
					<td><?php echo esc_html( $application->phone ); ?></td>
				</tr>
				<tr>
					<th>WhatsApp</th>
					<td><?php echo esc_html( $application->whatsapp ); ?></td>
				</tr>
				<tr>
					<th>Specialty</th>
					<td><?php echo esc_html( $application->doctor_specialty ); ?></td>
				</tr>
				<tr>
					<th>Experience Years</th>
					<td><?php echo esc_html( $application->experience_years ); ?></td>
				</tr>
				<tr>
					<th>Education</th>
					<td><?php echo esc_html( $application->education ); ?></td>
				</tr>
				<tr>
					<th>Bio (Arabic)</th>
					<td><?php echo esc_html( $application->bio ); ?></td>
				</tr>
				<tr>
					<th>Bio (English)</th>
					<td><?php echo esc_html( $application->bio_en ); ?></td>
				</tr>
			</table>
		</div>
		
		<div class="card">
			<h2>Documents</h2>
			<table class="form-table">
				<tr>
					<th>Profile Image</th>
					<td>
						<?php if ( !empty( $application->profile_image ) ) : ?>
							<?php echo wp_get_attachment_image( $application->profile_image, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') ); ?>
						<?php else : ?>
							No image uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Identity Front</th>
					<td>
						<?php if ( !empty( $application->identity_front ) ) : ?>
							<?php 
							$attachment = get_post( $application->identity_front );
							$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->identity_front ) ) ) : 'Document';
							
							// Check if it's an image
							if ( wp_attachment_is_image( $application->identity_front ) ) {
								echo wp_get_attachment_image( $application->identity_front, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
							} else {
								echo '<a href="' . wp_get_attachment_url( $application->identity_front ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
							}
							?>
						<?php else : ?>
							No document uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Identity Back</th>
					<td>
						<?php if ( !empty( $application->identity_back ) ) : ?>
							<?php 
							$attachment = get_post( $application->identity_back );
							$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->identity_back ) ) ) : 'Document';
							
							// Check if it's an image
							if ( wp_attachment_is_image( $application->identity_back ) ) {
								echo wp_get_attachment_image( $application->identity_back, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
							} else {
								echo '<a href="' . wp_get_attachment_url( $application->identity_back ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
							}
							?>
						<?php else : ?>
							No document uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Certificates</th>
					<td>
						<?php
						$certificates = !empty( $application->certificates ) ? json_decode( $application->certificates, true ) : [];
						if ( !empty( $certificates ) ) :
							foreach ( $certificates as $cert_id ) :
								$attachment = get_post( $cert_id );
								if ( $attachment ) :
									$filename = $attachment->post_title ?: basename( wp_get_attachment_url( $cert_id ) );
									?>
									<div style="margin-bottom: 10px;">
										<a href="<?php echo esc_url( wp_get_attachment_url( $cert_id ) ); ?>" target="_blank">
											<?php echo esc_html( $filename ); ?>
										</a>
									</div>
									<?php
								endif;
							endforeach;
						else :
							echo 'No certificates uploaded';
						endif;
						?>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="card">
			<h2>AI Platform Settings</h2>
			<table class="form-table">
				<tr>
					<th>Show on AI Site</th>
					<td><?php echo $application->show_on_ai_site ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th>Rating</th>
					<td><?php echo number_format( $application->rating, 2 ); ?> / 5.00 (<?php echo $application->total_ratings; ?> ratings)</td>
				</tr>
				<tr>
					<th>AI Bio (Arabic)</th>
					<td><?php echo !empty( $application->ai_bio ) ? nl2br( esc_html( $application->ai_bio ) ) : '<span class="description">Not set</span>'; ?></td>
				</tr>
				<tr>
					<th>AI Bio (English)</th>
					<td><?php echo !empty( $application->ai_bio_en ) ? nl2br( esc_html( $application->ai_bio_en ) ) : '<span class="description">Not set</span>'; ?></td>
				</tr>
				<tr>
					<th>AI Certifications</th>
					<td><?php echo !empty( $application->ai_certifications ) ? nl2br( esc_html( $application->ai_certifications ) ) : '<span class="description">Not set</span>'; ?></td>
				</tr>
				<tr>
					<th>Earliest Slot</th>
					<td><?php echo $application->ai_earliest_slot; ?> days in advance</td>
				</tr>
			</table>
		</div>
		
		<div class="card">
			<h2>Diagnoses & Specializations</h2>
			<?php
			if ( $application->user_id ) {
				$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
				$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
				
				$therapist_diagnoses = $wpdb->get_results( $wpdb->prepare(
					"SELECT td.*, d.name as diagnosis_name, d.name_en, d.name_ar 
					FROM $therapist_diagnoses_table td 
					JOIN $diagnoses_table d ON td.diagnosis_id = d.id 
					WHERE td.therapist_id = %d 
					ORDER BY td.display_order ASC, COALESCE(d.name_en, d.name) ASC",
					$application->user_id
				) );
				
				if ( !empty( $therapist_diagnoses ) ) :
					?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 50px;">Points</th>
								<th>Diagnosis</th>
								<th style="width: 100px;">Rating</th>
								<th>Suitability Message (English)</th>
								<th>Suitability Message (Arabic)</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $therapist_diagnoses as $td ) : ?>
								<tr>
									<td><?php echo esc_html( $td->display_order ); ?></td>
									<td><strong><?php echo esc_html( $td->name_en ?: $td->diagnosis_name ?: 'Unnamed Diagnosis' ); ?></strong></td>
									<td><?php echo number_format( $td->rating, 1 ); ?> / 5.0</td>
									<td><?php echo !empty( $td->suitability_message_en ) ? esc_html( $td->suitability_message_en ) : '<span class="description">No message</span>'; ?></td>
									<td><?php echo !empty( $td->suitability_message_ar ) ? esc_html( $td->suitability_message_ar ) : '<span class="description">لا توجد رسالة</span>'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php
				else :
					?>
					<p class="description">No diagnoses assigned yet.</p>
					<?php
				endif;
			} else {
				echo '<p class="description">User ID not set. Cannot display diagnoses.</p>';
			}
			?>
		</div>
		
		<div class="card">
			<h2>Actions</h2>
			<?php if ( $application->status === 'pending' ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $application_id ) ); ?>" 
				   class="button button-primary">Approve Application</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=reject&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_reject_' . $application_id ) ); ?>" 
				   class="button">Reject Application</a>
			<?php elseif ( $application->status === 'rejected' ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $application_id ) ); ?>" 
				   class="button button-primary">Approve Application</a>
			<?php endif; ?>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=edit&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_edit_' . $application_id ) ); ?>" class="button">Edit Profile</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications' ); ?>" class="button">Back to Applications</a>
		</div>
	</div>
	<?php
}

/**
 * Display application edit form
 */
function snks_display_application_edit_form( $application_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $application_id ) );
	if ( !$application ) {
		wp_die( 'Application not found.' );
	}
	
	// Enqueue media uploader
	wp_enqueue_media();
	?>
	<div class="wrap">
		<h1>Edit Therapist Profile</h1>
		
		<form method="post" action="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications' ); ?>">
			<?php wp_nonce_field( 'save_application_' . $application_id ); ?>
			<input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
			<input type="hidden" name="save_application" value="1">
			
			<div class="card">
				<h2>Basic Information</h2>
				<table class="form-table">
					<tr>
						<th><label for="name">Name (Arabic)</label></th>
						<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $application->name ); ?>" class="regular-text" autocomplete="name" /></td>
					</tr>
					<tr>
						<th><label for="name_en">Name (English)</label></th>
						<td><input type="text" id="name_en" name="name_en" value="<?php echo esc_attr( $application->name_en ); ?>" class="regular-text" autocomplete="name" /></td>
					</tr>
					<tr>
						<th><label for="email">Email</label></th>
						<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $application->email ); ?>" class="regular-text" autocomplete="email" /></td>
					</tr>
					<tr>
						<th><label for="phone">Phone</label></th>
						<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $application->phone ); ?>" class="regular-text" autocomplete="tel" /></td>
					</tr>
					<tr>
						<th><label for="whatsapp">WhatsApp</label></th>
						<td><input type="text" id="whatsapp" name="whatsapp" value="<?php echo esc_attr( $application->whatsapp ); ?>" class="regular-text" autocomplete="tel" /></td>
					</tr>
					<tr>
						<th><label for="doctor_specialty">Specialty</label></th>
						<td><input type="text" id="doctor_specialty" name="doctor_specialty" value="<?php echo esc_attr( $application->doctor_specialty ); ?>" class="regular-text" autocomplete="on" /></td>
					</tr>
					<tr>
						<th><label for="experience_years">Experience Years</label></th>
						<td><input type="number" id="experience_years" name="experience_years" value="<?php echo esc_attr( $application->experience_years ); ?>" class="small-text" autocomplete="on" /></td>
					</tr>
					<tr>
						<th><label for="education">Education</label></th>
						<td><textarea id="education" name="education" rows="3" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->education ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="bio">Bio (Arabic)</label></th>
						<td><textarea id="bio" name="bio" rows="4" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->bio ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="bio_en">Bio (English)</label></th>
						<td><textarea id="bio_en" name="bio_en" rows="4" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->bio_en ); ?></textarea></td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2>AI Platform Settings</h2>
				<table class="form-table">
					<tr>
						<th><label for="show_on_ai_site">Show on AI Site</label></th>
						<td>
							<input type="hidden" name="show_on_ai_site" value="0" />
							<input type="checkbox" id="show_on_ai_site" name="show_on_ai_site" value="1" <?php checked( $application->show_on_ai_site, 1 ); ?> />
							<label for="show_on_ai_site">Display this therapist on the AI platform</label>
						</td>
					</tr>
					<tr>
						<th><label for="rating">Rating</label></th>
						<td>
							<input type="number" id="rating" name="rating" value="<?php echo esc_attr( $application->rating ); ?>" class="small-text" step="0.01" min="0" max="5" />
							<p class="description">Overall rating (0.00 - 5.00)</p>
						</td>
					</tr>
					<tr>
						<th><label for="total_ratings">Total Ratings</label></th>
						<td>
							<input type="number" id="total_ratings" name="total_ratings" value="<?php echo esc_attr( $application->total_ratings ); ?>" class="small-text" min="0" />
							<p class="description">Number of ratings received</p>
						</td>
					</tr>
					<tr>
						<th><label for="ai_bio">AI Bio (Arabic)</label></th>
						<td><textarea id="ai_bio" name="ai_bio" rows="4" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->ai_bio ); ?></textarea>
						<p class="description">Bio specifically for AI platform display</p></td>
					</tr>
					<tr>
						<th><label for="ai_bio_en">AI Bio (English)</label></th>
						<td><textarea id="ai_bio_en" name="ai_bio_en" rows="4" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->ai_bio_en ); ?></textarea>
						<p class="description">Bio specifically for AI platform display</p></td>
					</tr>
					<tr>
						<th><label for="ai_certifications">AI Certifications</label></th>
						<td><textarea id="ai_certifications" name="ai_certifications" rows="3" class="large-text" autocomplete="on"><?php echo esc_textarea( $application->ai_certifications ); ?></textarea>
						<p class="description">Certifications to display on AI platform</p></td>
					</tr>
					<tr>
						<th><label for="ai_earliest_slot">Earliest Slot (Days)</label></th>
						<td>
							<input type="number" id="ai_earliest_slot" name="ai_earliest_slot" value="<?php echo esc_attr( $application->ai_earliest_slot ); ?>" class="small-text" min="0" />
							<p class="description">Minimum days in advance for booking (0 = same day)</p>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2>Documents</h2>
				<table class="form-table">
					<tr>
						<th><label for="profile_image">Profile Image</label></th>
						<td>
							<input type="hidden" id="profile_image" name="profile_image" value="<?php echo esc_attr( $application->profile_image ); ?>" />
							<div id="profile_image_preview">
								<?php if ( !empty( $application->profile_image ) ) : ?>
									<?php echo wp_get_attachment_image( $application->profile_image, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') ); ?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_image('profile_image')">Upload Image</button>
							<button type="button" class="button" onclick="snks_remove_image('profile_image')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="identity_front">Identity Front</label></th>
						<td>
							<input type="hidden" id="identity_front" name="identity_front" value="<?php echo esc_attr( $application->identity_front ); ?>" />
							<div id="identity_front_preview">
								<?php if ( !empty( $application->identity_front ) ) : ?>
									<?php 
									$attachment = get_post( $application->identity_front );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->identity_front ) ) ) : 'Document';
									
									// Check if it's an image
									if ( wp_attachment_is_image( $application->identity_front ) ) {
										echo wp_get_attachment_image( $application->identity_front, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . wp_get_attachment_url( $application->identity_front ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('identity_front')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('identity_front')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="identity_back">Identity Back</label></th>
						<td>
							<input type="hidden" id="identity_back" name="identity_back" value="<?php echo esc_attr( $application->identity_back ); ?>" />
							<div id="identity_back_preview">
								<?php if ( !empty( $application->identity_back ) ) : ?>
									<?php 
									$attachment = get_post( $application->identity_back );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->identity_back ) ) ) : 'Document';
									
									// Check if it's an image
									if ( wp_attachment_is_image( $application->identity_back ) ) {
										echo wp_get_attachment_image( $application->identity_back, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . wp_get_attachment_url( $application->identity_back ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('identity_back')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('identity_back')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="certificates">Certificates</label></th>
						<td>
							<input type="hidden" id="certificates" name="certificates" value="<?php echo esc_attr( $application->certificates ); ?>" />
							<div id="certificates_preview">
								<?php
								$certificates = !empty( $application->certificates ) ? json_decode( $application->certificates, true ) : [];
								if ( !empty( $certificates ) ) :
									foreach ( $certificates as $cert_id ) :
										$attachment = get_post( $cert_id );
										if ( $attachment ) :
											$filename = $attachment->post_title ?: basename( wp_get_attachment_url( $cert_id ) );
											?>
											<div style="margin-bottom: 10px;">
												<a href="<?php echo esc_url( wp_get_attachment_url( $cert_id ) ); ?>" target="_blank">
													<?php echo esc_html( $filename ); ?>
												</a>
											</div>
											<?php
										endif;
									endforeach;
								endif;
								?>
							</div>
							<button type="button" class="button" onclick="snks_upload_certificates()">Upload Certificates</button>
							<button type="button" class="button" onclick="snks_remove_certificates()">Remove All</button>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2>Diagnoses & Specializations</h2>
				<?php
				// Get all diagnoses
				$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
				$all_diagnoses = $wpdb->get_results( "SELECT * FROM $diagnoses_table ORDER BY COALESCE(name_en, name) ASC" );
				
						// Get current therapist diagnoses with order
		$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
		$current_diagnoses = [];
		if ( $application->user_id ) {
			$current_diagnoses = $wpdb->get_results( $wpdb->prepare(
				"SELECT diagnosis_id, rating, suitability_message, suitability_message_en, suitability_message_ar, display_order FROM $therapist_diagnoses_table WHERE therapist_id = %d ORDER BY display_order ASC, diagnosis_id ASC",
				$application->user_id
			) );
		}
				$current_diagnosis_ids = array_column( $current_diagnoses, 'diagnosis_id' );
				?>
				
				<table class="wp-list-table widefat fixed striped" id="diagnoses-table">
					<thead>
						<tr>
															<th style="width: 50px;">Points</th>
							<th style="width: 80px;">Active</th>
							<th>Diagnosis</th>
							<th style="width: 100px;">Rating</th>
							<th>Suitability Message (English)</th>
							<th>Suitability Message (Arabic)</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( !empty( $all_diagnoses ) ) : ?>
							<?php foreach ( $all_diagnoses as $diagnosis ) : ?>
								<?php
								$is_selected = in_array( $diagnosis->id, $current_diagnosis_ids );
								$current_rating = 0;
								$current_message = '';
								$current_message_en = '';
								$current_message_ar = '';
								$display_order = 0;
								
								if ( $is_selected ) {
									foreach ( $current_diagnoses as $td ) {
										if ( $td->diagnosis_id == $diagnosis->id ) {
											$current_rating = $td->rating;
											$current_message = $td->suitability_message;
											$current_message_en = $td->suitability_message_en;
											$current_message_ar = $td->suitability_message_ar;
											$display_order = $td->display_order;
											break;
										}
									}
								}
								?>
								<tr data-diagnosis-id="<?php echo $diagnosis->id; ?>">
									<td>
										<input type="number" name="diagnosis_order_<?php echo $diagnosis->id; ?>" 
											   value="<?php echo esc_attr( $display_order ); ?>" 
											   class="small-text" min="0" style="width: 50px;" />
									</td>
									<td>
										<input type="checkbox" name="diagnoses[]" value="<?php echo $diagnosis->id; ?>" 
											   <?php checked( $is_selected ); ?> class="diagnosis-checkbox" />
									</td>
									<td><strong><?php echo esc_html( $diagnosis->name_en ?: $diagnosis->name ?: 'Unnamed Diagnosis' ); ?></strong></td>
									<td>
										<input type="number" name="diagnosis_rating_<?php echo $diagnosis->id; ?>" 
											   value="<?php echo esc_attr( $current_rating ); ?>" 
											   step="0.1" min="0" max="5" class="small-text" style="width: 80px;" 
											   <?php echo !$is_selected ? 'disabled' : ''; ?> />
									</td>
									<td>
										<textarea name="diagnosis_message_en_<?php echo $diagnosis->id; ?>" 
												  rows="3" class="large-text" 
												  placeholder="Why is this doctor good at treating this diagnosis?"
												  <?php echo !$is_selected ? 'disabled' : ''; ?>><?php echo esc_textarea( $current_message_en ?: $current_message ); ?></textarea>
									</td>
									<td>
										<textarea name="diagnosis_message_ar_<?php echo $diagnosis->id; ?>" 
												  rows="3" class="large-text" 
												  placeholder="لماذا هذا الطبيب جيد في علاج هذا التشخيص؟"
												  <?php echo !$is_selected ? 'disabled' : ''; ?>><?php echo esc_textarea( $current_message_ar ); ?></textarea>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6">No diagnoses available. Please add diagnoses first.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
				<p class="description">Select diagnoses this therapist specializes in. For selected diagnoses, you can set a rating and suitability messages in both English and Arabic.</p>
			</div>
			
			<?php submit_button( 'Update Profile' ); ?>
		</form>
		
		<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications' ); ?>" class="button">Back to Applications</a>
	</div>
	
	<script>
	function snks_upload_image(field_id) {
		var frame = wp.media({
			title: 'Select Image',
			button: {
				text: 'Use this image'
			},
			multiple: false
		});
		
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			document.getElementById(field_id).value = attachment.id;
			
			// Handle different image sizes
			var imageUrl = '';
			if (attachment.sizes && attachment.sizes.thumbnail) {
				imageUrl = attachment.sizes.thumbnail.url;
			} else if (attachment.sizes && attachment.sizes.medium) {
				imageUrl = attachment.sizes.medium.url;
			} else {
				imageUrl = attachment.url;
			}
			
			document.getElementById(field_id + '_preview').innerHTML = '<img src="' + imageUrl + '" style="max-width: 150px; height: auto;" />';
		});
		
		frame.open();
	}
	
	function snks_remove_image(field_id) {
		document.getElementById(field_id).value = '';
		document.getElementById(field_id + '_preview').innerHTML = '';
	}
	
	function snks_upload_document(field_id) {
		var frame = wp.media({
			title: 'Select Document',
			button: {
				text: 'Use this document'
			},
			multiple: false
		});
		
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			document.getElementById(field_id).value = attachment.id;
			
			// Check if it's an image and display accordingly
			if (attachment.type === 'image') {
				var imageUrl = '';
				if (attachment.sizes && attachment.sizes.thumbnail) {
					imageUrl = attachment.sizes.thumbnail.url;
				} else if (attachment.sizes && attachment.sizes.medium) {
					imageUrl = attachment.sizes.medium.url;
				} else {
					imageUrl = attachment.url;
				}
				document.getElementById(field_id + '_preview').innerHTML = '<img src="' + imageUrl + '" style="max-width: 150px; height: auto;" />';
			} else {
				// Show filename and link for non-images
				var filename = attachment.filename || attachment.title || 'Document';
				document.getElementById(field_id + '_preview').innerHTML = '<a href="' + attachment.url + '" target="_blank">' + filename + '</a>';
			}
		});
		
		frame.open();
	}
	
	function snks_remove_document(field_id) {
		document.getElementById(field_id).value = '';
		document.getElementById(field_id + '_preview').innerHTML = '';
	}
	
	function snks_upload_certificates() {
		var frame = wp.media({
			title: 'Select Certificates',
			button: {
				text: 'Use these certificates'
			},
			multiple: true
		});
		
		frame.on('select', function() {
			var attachments = frame.state().get('selection').toJSON();
			var ids = attachments.map(function(attachment) { return attachment.id; });
			document.getElementById('certificates').value = JSON.stringify(ids);
			
			var preview = '';
			attachments.forEach(function(attachment) {
				var filename = attachment.filename || attachment.title || 'Certificate';
				preview += '<div style="margin-bottom: 10px;"><a href="' + attachment.url + '" target="_blank">' + filename + '</a></div>';
			});
			document.getElementById('certificates_preview').innerHTML = preview;
		});
		
		frame.open();
	}
	
	function snks_remove_certificates() {
		document.getElementById('certificates').value = '';
		document.getElementById('certificates_preview').innerHTML = '';
	}
	
	// Handle diagnoses checkboxes
	document.addEventListener('DOMContentLoaded', function() {
		const diagnosisCheckboxes = document.querySelectorAll('input[name="diagnoses[]"]');
		
		diagnosisCheckboxes.forEach(function(checkbox) {
			checkbox.addEventListener('change', function() {
				const diagnosisId = this.value;
				const row = this.closest('tr');
				const ratingField = row.querySelector('input[name="diagnosis_rating_' + diagnosisId + '"]');
				const messageEnField = row.querySelector('textarea[name="diagnosis_message_en_' + diagnosisId + '"]');
				const messageArField = row.querySelector('textarea[name="diagnosis_message_ar_' + diagnosisId + '"]');
				const orderField = row.querySelector('input[name="diagnosis_order_' + diagnosisId + '"]');
				
				if (this.checked) {
					// Enable fields
					if (ratingField) ratingField.disabled = false;
					if (messageEnField) messageEnField.disabled = false;
					if (messageArField) messageArField.disabled = false;
					if (orderField) orderField.disabled = false;
				} else {
					// Disable fields and clear values
					if (ratingField) {
						ratingField.disabled = true;
						ratingField.value = '';
					}
					if (messageEnField) {
						messageEnField.disabled = true;
						messageEnField.value = '';
					}
					if (messageArField) {
						messageArField.disabled = true;
						messageArField.value = '';
					}
					if (orderField) {
						orderField.disabled = true;
						orderField.value = '';
					}
				}
			});
			
			// Trigger change event on page load to set initial state
			checkbox.dispatchEvent(new Event('change'));
		});
	});
	</script>
	<?php
}

/**
 * Save application data
 */
function snks_save_application_data( $application_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$fields = [
		'name', 'name_en', 'email', 'phone', 'whatsapp', 'doctor_specialty',
		'experience_years', 'education', 'bio', 'bio_en',
		'profile_image', 'identity_front', 'identity_back', 'certificates',
		'rating', 'total_ratings', 'ai_bio', 'ai_bio_en', 'ai_certifications',
		'ai_earliest_slot', 'show_on_ai_site'
	];
	
	$data = [];
	foreach ( $fields as $field ) {
		if ( isset( $_POST[$field] ) ) {
			// Handle different field types
			switch ( $field ) {
				case 'rating':
				case 'total_ratings':
				case 'ai_earliest_slot':
					$data[$field] = floatval( $_POST[$field] );
					break;
				case 'show_on_ai_site':
					$data[$field] = isset( $_POST[$field] ) ? intval( $_POST[$field] ) : 0;
					break;
				default:
					$data[$field] = $_POST[$field];
					break;
			}
		}
	}
	
	$result = $wpdb->update( $table_name, $data, ['id' => $application_id] );
	
	if ( $result === false ) {
		wp_die( 'Error saving application data: ' . $wpdb->last_error );
	}
	
	// Also update user meta if user_id exists
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $table_name WHERE id = %d", $application_id ) );
	if ( $application && $application->user_id ) {
		update_user_meta( $application->user_id, 'show_on_ai_site', $data['show_on_ai_site'] ?? 0 );
		update_user_meta( $application->user_id, 'ai_bio', $data['ai_bio'] ?? '' );
		update_user_meta( $application->user_id, 'ai_bio_en', $data['ai_bio_en'] ?? '' );
		update_user_meta( $application->user_id, 'ai_certifications', $data['ai_certifications'] ?? '' );
		update_user_meta( $application->user_id, 'ai_earliest_slot', $data['ai_earliest_slot'] ?? 0 );
		
		// Handle diagnoses relationships
		$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
		
		// Get current diagnoses
		$current_diagnoses = $wpdb->get_col( $wpdb->prepare(
			"SELECT diagnosis_id FROM $therapist_diagnoses_table WHERE therapist_id = %d",
			$application->user_id
		) );
		
		// Get selected diagnoses from form
		$selected_diagnoses = isset( $_POST['diagnoses'] ) ? array_map( 'intval', $_POST['diagnoses'] ) : [];
		
		// Remove unselected diagnoses
		$diagnoses_to_remove = array_diff( $current_diagnoses, $selected_diagnoses );
		if ( !empty( $diagnoses_to_remove ) ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $therapist_diagnoses_table WHERE therapist_id = %d AND diagnosis_id IN (" . implode( ',', array_fill( 0, count( $diagnoses_to_remove ), '%d' ) ) . ")",
				array_merge( [$application->user_id], $diagnoses_to_remove )
			) );
		}
		
		// Add or update selected diagnoses
		foreach ( $selected_diagnoses as $diagnosis_id ) {
			$rating = isset( $_POST["diagnosis_rating_$diagnosis_id"] ) ? floatval( $_POST["diagnosis_rating_$diagnosis_id"] ) : 0;
			$message_en = isset( $_POST["diagnosis_message_en_$diagnosis_id"] ) ? sanitize_textarea_field( $_POST["diagnosis_message_en_$diagnosis_id"] ) : '';
			$message_ar = isset( $_POST["diagnosis_message_ar_$diagnosis_id"] ) ? sanitize_textarea_field( $_POST["diagnosis_message_ar_$diagnosis_id"] ) : '';
			$order = isset( $_POST["diagnosis_order_$diagnosis_id"] ) ? intval( $_POST["diagnosis_order_$diagnosis_id"] ) : 0;
			
			$wpdb->replace(
				$therapist_diagnoses_table,
				[
					'therapist_id' => $application->user_id,
					'diagnosis_id' => $diagnosis_id,
					'rating' => $rating,
					'suitability_message_en' => $message_en,
					'suitability_message_ar' => $message_ar,
					'display_order' => $order
				],
				['%d', '%d', '%f', '%s', '%s', '%d']
			);
		}
	}
}

 