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
							?>
							<a href="<?php echo wp_get_attachment_url( $application->identity_front ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a>
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
							?>
							<a href="<?php echo wp_get_attachment_url( $application->identity_back ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a>
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
		
		<form method="post" action="">
			<?php wp_nonce_field( 'save_application_' . $application_id ); ?>
			<input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
			<input type="hidden" name="save_application" value="1">
			
			<div class="card">
				<h2>Basic Information</h2>
				<table class="form-table">
					<tr>
						<th><label for="name">Name (Arabic)</label></th>
						<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $application->name ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="name_en">Name (English)</label></th>
						<td><input type="text" id="name_en" name="name_en" value="<?php echo esc_attr( $application->name_en ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="email">Email</label></th>
						<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $application->email ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="phone">Phone</label></th>
						<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $application->phone ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="whatsapp">WhatsApp</label></th>
						<td><input type="text" id="whatsapp" name="whatsapp" value="<?php echo esc_attr( $application->whatsapp ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="doctor_specialty">Specialty</label></th>
						<td><input type="text" id="doctor_specialty" name="doctor_specialty" value="<?php echo esc_attr( $application->doctor_specialty ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="experience_years">Experience Years</label></th>
						<td><input type="number" id="experience_years" name="experience_years" value="<?php echo esc_attr( $application->experience_years ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th><label for="education">Education</label></th>
						<td><textarea id="education" name="education" rows="3" class="large-text"><?php echo esc_textarea( $application->education ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="bio">Bio (Arabic)</label></th>
						<td><textarea id="bio" name="bio" rows="4" class="large-text"><?php echo esc_textarea( $application->bio ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="bio_en">Bio (English)</label></th>
						<td><textarea id="bio_en" name="bio_en" rows="4" class="large-text"><?php echo esc_textarea( $application->bio_en ); ?></textarea></td>
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
									?>
									<a href="<?php echo wp_get_attachment_url( $application->identity_front ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a>
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
									?>
									<a href="<?php echo wp_get_attachment_url( $application->identity_back ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a>
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
			
			// Show filename and link
			var filename = attachment.filename || attachment.title || 'Document';
			document.getElementById(field_id + '_preview').innerHTML = '<a href="' + attachment.url + '" target="_blank">' + filename + '</a>';
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
		'profile_image', 'identity_front', 'identity_back', 'certificates'
	];
	
	$data = [];
	foreach ( $fields as $field ) {
		if ( isset( $_POST[$field] ) ) {
			$data[$field] = $_POST[$field];
		}
	}
	
	$wpdb->update( $table_name, $data, ['id' => $application_id] );
}

// Table creation handled in main plugin file 