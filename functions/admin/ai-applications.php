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
								// Allow approving both pending and rejected applications
								if ( $application->status === 'pending' || $application->status === 'rejected' ) {
									$result = snks_approve_therapist_application( $app_id );
									if ( ! is_wp_error( $result ) ) {
									$processed++;
									}
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
						// Allow approving both pending and rejected applications
						if ( $application->status === 'pending' || $application->status === 'rejected' ) {
							$result = snks_approve_therapist_application( $app_id );
							if ( is_wp_error( $result ) ) {
								echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
							} else {
							echo '<div class="notice notice-success"><p>Application approved successfully!</p></div>';
							}
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
					case 'create_user':
						$result = snks_create_or_link_user_from_application( $app_id );
						if ( is_wp_error( $result ) ) {
							echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
						} else {
							echo '<div class="notice notice-success"><p>' . esc_html( $result ) . '</p></div>';
						}
						break;
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
	
	// Only use prepare() if we have placeholders, otherwise use direct query
	if ( !empty( $where_values ) ) {
	$applications = $wpdb->get_results( $wpdb->prepare( 
		"SELECT * FROM $table_name $where_sql ORDER BY created_at DESC",
		$where_values
	) );
	} else {
		$applications = $wpdb->get_results( 
			"SELECT * FROM $table_name $where_sql ORDER BY created_at DESC"
		);
	}
	
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
									<?php
									$create_user_url = admin_url(
										'admin.php?page=jalsah-ai-applications&action=create_user&application_id=' . $app->id .
										'&_wpnonce=' . wp_create_nonce( 'application_create_user_' . $app->id )
									);
									$export_app_url = admin_url(
										'admin-post.php?action=snks_export_single_application&application_id=' . $app->id .
										'&_wpnonce=' . wp_create_nonce( 'snks_export_single_application_' . $app->id )
									);
									?>
									<a href="<?php echo esc_url( $create_user_url ); ?>" 
									   class="button button-small">
										<?php echo $app->user_id ? 'Link Existing User' : 'Create User'; ?>
									</a>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=view&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_view_' . $app->id ) ); ?>" 
									   class="button button-small">View</a>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=edit&application_id=' . $app->id . '&_wpnonce=' . wp_create_nonce( 'application_edit_' . $app->id ) ); ?>" 
									   class="button button-small">Edit Profile</a>
									<a href="<?php echo esc_url( $export_app_url ); ?>"
									   class="button button-small">
										Export
									</a>
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
	
	// Allow approving both pending and rejected applications
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND (status = 'pending' OR status = 'rejected')", $application_id ) );
	if ( !$application ) {
		return false;
	}
	
	// Check if user already exists
	$existing_user = get_user_by( 'email', $application->email );
	if ( $existing_user ) {
		$user_id = $existing_user->ID;
	} else {
		// Before creating a new user, enforce uniqueness across username, email, WhatsApp and billing phone
		$phone    = ! empty( $application->phone ) ? sanitize_text_field( $application->phone ) : '';
		$email    = ! empty( $application->email ) ? sanitize_email( $application->email ) : '';
		$whatsapp = ! empty( $application->whatsapp ) ? sanitize_text_field( $application->whatsapp ) : '';

		// Username (we use phone as username) - direct username check is enough here
		if ( $phone && username_exists( $phone ) ) {
			return new WP_Error( 'snks_username_exists', __( 'Cannot approve application: a user with this phone (username) already exists.', 'anony-shrinks' ) );
		}

		// Email
		if ( $email && email_exists( $email ) ) {
			return new WP_Error( 'snks_email_exists', __( 'Cannot approve application: a user with this email already exists.', 'anony-shrinks' ) );
		}

		// WhatsApp (check whatsapp, billing_whatsapp and billing_phone meta) with normalization
		if ( $whatsapp ) {
			$normalized_whatsapp = snks_normalize_phone_for_comparison( $whatsapp );

			$potential_whatsapp_users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta}
					 WHERE meta_key IN ('whatsapp','billing_whatsapp','billing_phone')
					 AND meta_value LIKE %s",
					'%' . $wpdb->esc_like( $normalized_whatsapp ) . '%'
				)
			);

			if ( ! empty( $potential_whatsapp_users ) ) {
				foreach ( $potential_whatsapp_users as $row ) {
					if ( snks_normalize_phone_for_comparison( $row->meta_value ) === $normalized_whatsapp ) {
						return new WP_Error( 'snks_whatsapp_exists', __( 'Cannot approve application: a user with this WhatsApp number already exists.', 'anony-shrinks' ) );
					}
				}
			}
		}

		// Billing phone (normalize to handle presence/absence of country code)
		if ( $phone ) {
			$normalized_phone = snks_normalize_phone_for_comparison( $phone );

			$potential_billing_phone_users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta}
					 WHERE meta_key = 'billing_phone'
					 AND meta_value LIKE %s",
					'%' . $wpdb->esc_like( $normalized_phone ) . '%'
				)
			);

			if ( ! empty( $potential_billing_phone_users ) ) {
				foreach ( $potential_billing_phone_users as $row ) {
					if ( snks_normalize_phone_for_comparison( $row->meta_value ) === $normalized_phone ) {
						return new WP_Error( 'snks_billing_phone_exists', __( 'Cannot approve application: a user with this billing phone already exists.', 'anony-shrinks' ) );
		}
				}
			}
		}

		// Create new user with minimal data if all checks pass
		$password = wp_generate_password( 8, false );
		$user_id = wp_create_user( $application->phone, $password, $application->email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
	}
	
	$user = get_user_by( 'id', $user_id );
	$user->set_role( 'doctor' );
	
	// Set only essential user meta for login purposes
	update_user_meta( $user_id, 'billing_phone', $application->phone );
	update_user_meta( $user_id, 'billing_email', $application->email );
	update_user_meta( $user_id, 'first_name', $application->name );
	update_user_meta( $user_id, 'billing_first_name', $application->name );
	
	// Update application status and link to user
	$wpdb->update( $table_name, [
		'status' => 'approved',
		'user_id' => $user_id
	], ['id' => $application_id] );
	
	return true;
}

/**
 * Create or link a user account from a therapist application
 *
 * - Works for any application status (pending/approved/rejected)
 * - Avoids duplicate users: reuses existing user by email if found
 */
function snks_create_or_link_user_from_application( $application_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $application_id ) );
	if ( ! $application ) {
		return new WP_Error( 'snks_app_not_found', 'Application not found.' );
	}
	
	// Email is required for deterministic linking without duplicates.
	if ( empty( $application->email ) ) {
		return new WP_Error( 'snks_missing_email', 'Application email is missing, cannot create or link user.' );
	}
	
	$user_id = 0;
	$is_new_user = false;
	$linked_by = 'email'; // Track what we linked by
	
	// 1) Try to find existing user by email.
	$existing_by_email = get_user_by( 'email', $application->email );
	if ( $existing_by_email ) {
		$user_id = $existing_by_email->ID;
		$linked_by = 'email';
	} else {
		// 2) Try to find by phone (username)
		if ( ! empty( $application->phone ) ) {
			$phone = sanitize_text_field( $application->phone );
			$existing_by_phone = get_user_by( 'login', $phone );
			if ( $existing_by_phone ) {
				$user_id = $existing_by_phone->ID;
				$linked_by = 'phone (username)';
			}
		}
		
		// 3) Try to find by WhatsApp
		if ( ! $user_id && ! empty( $application->whatsapp ) ) {
			$whatsapp = sanitize_text_field( $application->whatsapp );
			$normalized_whatsapp = snks_normalize_phone_for_comparison( $whatsapp );

			$potential_whatsapp_users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta}
					 WHERE meta_key IN ('whatsapp','billing_whatsapp','billing_phone')
					 AND meta_value LIKE %s",
					'%' . $wpdb->esc_like( $normalized_whatsapp ) . '%'
				)
			);

			if ( ! empty( $potential_whatsapp_users ) ) {
				foreach ( $potential_whatsapp_users as $row ) {
					if ( snks_normalize_phone_for_comparison( $row->meta_value ) === $normalized_whatsapp ) {
						$user_id = $row->user_id;
						$linked_by = 'WhatsApp number';
						break;
					}
				}
			}
		}
		
		// 4) Try to find by billing phone
		if ( ! $user_id && ! empty( $application->phone ) ) {
			$phone = sanitize_text_field( $application->phone );
			$normalized_phone = snks_normalize_phone_for_comparison( $phone );

			$potential_billing_phone_users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta}
					 WHERE meta_key = 'billing_phone'
					 AND meta_value LIKE %s",
					'%' . $wpdb->esc_like( $normalized_phone ) . '%'
				)
			);

			if ( ! empty( $potential_billing_phone_users ) ) {
				foreach ( $potential_billing_phone_users as $row ) {
					if ( snks_normalize_phone_for_comparison( $row->meta_value ) === $normalized_phone ) {
						$user_id = $row->user_id;
						$linked_by = 'billing phone';
						break;
					}
				}
			}
		}
		
		// 5) Create new user if none found
		if ( ! $user_id ) {
			if ( empty( $application->phone ) ) {
				return new WP_Error( 'snks_missing_phone', 'Application phone is missing, cannot create user.' );
			}
			
			$password = wp_generate_password( 8, false );
			$user_id  = wp_create_user( $application->phone, $password, $application->email );
			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'snks_user_create_failed', 'Failed to create user: ' . $user_id->get_error_message() );
			}
			$is_new_user = true;
		}
	}
	
	// Ensure role is doctor and update meta (for both new and existing users)
	$user = get_user_by( 'id', $user_id );
	if ( $user ) {
		$user->set_role( 'doctor' );
		
		// Basic meta
		update_user_meta( $user_id, 'billing_phone', $application->phone );
		update_user_meta( $user_id, 'billing_email', $application->email );
		update_user_meta( $user_id, 'first_name', $application->name );
		update_user_meta( $user_id, 'billing_first_name', $application->name );
	}
	
	// Link application to user (do not change status here)
	$wpdb->update(
		$table_name,
		array( 'user_id' => $user_id ),
		array( 'id' => $application_id ),
		array( '%d' ),
		array( '%d' )
	);
	
	// Message reflects whether we reused or created.
	if ( $is_new_user ) {
		$action_label = 'created';
	} else {
		$action_label = "linked (found by {$linked_by})";
	}
	
	return sprintf(
		'User #%d has been %s and linked to this application.',
		$user_id,
		$action_label
	);
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
	
	$role_labels = array(
		'psychiatrist' => 'طبيب نفسي',
		'clinical_psychologist' => 'أخصائي نفسي إكلينيكي',
	);
	$role_label = isset( $role_labels[ $application->role ] ) ? $role_labels[ $application->role ] : ( $application->role ?: '—' );
	
	$cp_moh_label = '';
	if ( 'clinical_psychologist' === $application->role ) {
		if ( 'yes' === $application->cp_moh_license ) {
			$cp_moh_label = 'نعم';
		} elseif ( 'no' === $application->cp_moh_license ) {
			$cp_moh_label = 'لا';
		} else {
			$cp_moh_label = '—';
		}
	}
	
	$preferred_groups = array();
	if ( ! empty( $application->preferred_groups ) ) {
		$decoded_groups = json_decode( $application->preferred_groups, true );
		if ( is_array( $decoded_groups ) ) {
			$preferred_groups = array_filter( array_map( 'trim', $decoded_groups ) );
		}
	}
	
	$therapy_courses = array();
	if ( ! empty( $application->therapy_courses ) ) {
		$decoded_courses = json_decode( $application->therapy_courses, true );
		if ( is_array( $decoded_courses ) ) {
			$therapy_courses = $decoded_courses;
		}
	}
	
	$diagnoses_children = array();
	if ( ! empty( $application->diagnoses_children ) ) {
		$decoded_children = json_decode( $application->diagnoses_children, true );
		if ( is_array( $decoded_children ) ) {
			$diagnoses_children = array_filter( $decoded_children );
		}
	}
	
	$diagnoses_adult = array();
	if ( ! empty( $application->diagnoses_adult ) ) {
		$decoded_adult = json_decode( $application->diagnoses_adult, true );
		if ( is_array( $decoded_adult ) ) {
			$diagnoses_adult = array_filter( $decoded_adult );
		}
	}
	
	$render_document = function( $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			echo 'No document uploaded';
			return;
		}
		
		$attachment = get_post( $attachment_id );
		$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $attachment_id ) ) ) : 'Document';
		$original_url = wp_get_attachment_url( $attachment_id );
		
		if ( wp_attachment_is_image( $attachment_id ) ) {
			echo '<a href="' . esc_url( $original_url ) . '" target="_blank" style="display: inline-block;">';
			echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'style' => 'max-width: 150px; height: auto; cursor: pointer;' ) );
			echo '</a>';
		} else {
			echo '<a href="' . esc_url( $original_url ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
		}
	};
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
					<th>Email</th>
					<td><?php echo $application->email ? esc_html( $application->email ) : '—'; ?></td>
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
					<th>Role</th>
					<td><?php echo esc_html( $role_label ); ?></td>
				</tr>
				<?php if ( ! empty( $application->psychiatrist_rank ) ) : ?>
				<tr>
					<th>Psychiatrist Rank</th>
					<td><?php echo esc_html( $application->psychiatrist_rank ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $application->psych_origin ) ) : ?>
				<tr>
					<th>Psychology Origin</th>
					<td><?php echo esc_html( $application->psych_origin ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( 'clinical_psychologist' === $application->role ) : ?>
				<tr>
					<th>MOH License</th>
					<td><?php echo esc_html( $cp_moh_label ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th>Specialty</th>
					<td><?php echo esc_html( $application->doctor_specialty ); ?></td>
				</tr>
			</table>
		</div>
		
		<?php if ( $application->experience_years || $application->education || $application->bio || $application->bio_en ) : ?>
		<div class="card">
			<h2>Additional Profile Details</h2>
			<table class="form-table">
				<?php if ( $application->experience_years ) : ?>
				<tr>
					<th>Experience Years</th>
					<td><?php echo esc_html( $application->experience_years ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $application->education ) : ?>
				<tr>
					<th>Education</th>
					<td><?php echo esc_html( $application->education ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $application->bio ) : ?>
				<tr>
					<th>Bio (Arabic)</th>
					<td><?php echo esc_html( $application->bio ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $application->bio_en ) : ?>
				<tr>
					<th>Bio (English)</th>
					<td><?php echo esc_html( $application->bio_en ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php endif; ?>
		
		<div class="card">
			<h2>Expertise & Preferences</h2>
			<table class="form-table">
				<tr>
					<th>Preferred Groups</th>
					<td>
						<?php
						if ( ! empty( $preferred_groups ) ) {
							echo esc_html( implode( '، ', $preferred_groups ) );
						} else {
							echo '—';
						}
						?>
					</td>
				</tr>
				<tr>
					<th>Therapy Courses / Experiences</th>
					<td>
						<?php if ( ! empty( $therapy_courses ) ) : ?>
							<ul>
								<?php foreach ( $therapy_courses as $course ) : ?>
									<?php
									$school = isset( $course['school'] ) ? $course['school'] : '';
									$place = isset( $course['place'] ) ? $course['place'] : '';
									$year  = isset( $course['year'] ) ? $course['year'] : '';
									$details = array_filter( array( $place, $year ) );
									?>
									<li><?php echo esc_html( $school ); ?><?php echo ! empty( $details ) ? ' — ' . esc_html( implode( ' / ', $details ) ) : ''; ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Diagnoses (Children)</th>
					<td>
						<?php
						if ( ! empty( $diagnoses_children ) ) {
							echo esc_html( implode( '، ', $diagnoses_children ) );
						} else {
							echo '—';
						}
						?>
					</td>
				</tr>
				<tr>
					<th>Diagnoses (Adults)</th>
					<td>
						<?php
						if ( ! empty( $diagnoses_adult ) ) {
							echo esc_html( implode( '، ', $diagnoses_adult ) );
						} else {
							echo '—';
						}
						?>
					</td>
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
							<?php 
							$profile_image_url = wp_get_attachment_url( $application->profile_image );
							?>
							<a href="<?php echo esc_url( $profile_image_url ); ?>" target="_blank" style="display: inline-block;">
								<?php echo wp_get_attachment_image( $application->profile_image, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto; cursor: pointer;') ); ?>
							</a>
						<?php else : ?>
							No image uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Identity Front</th>
					<td><?php $render_document( $application->identity_front ); ?></td>
				</tr>
				<tr>
					<th>Identity Back</th>
					<td><?php $render_document( $application->identity_back ); ?></td>
				</tr>
				<tr>
					<th>Graduate Certificate</th>
					<td><?php $render_document( $application->graduate_certificate ); ?></td>
				</tr>
				<tr>
					<th>Practice License</th>
					<td><?php $render_document( $application->practice_license ); ?></td>
				</tr>
				<tr>
					<th>Syndicate Card</th>
					<td><?php $render_document( $application->syndicate_card ); ?></td>
				</tr>
				<tr>
					<th>Rank Certificate</th>
					<td><?php $render_document( $application->rank_certificate ); ?></td>
				</tr>
				<tr>
					<th>Clinical Graduate Certificate</th>
					<td><?php $render_document( $application->cp_graduate_certificate ); ?></td>
				</tr>
				<tr>
					<th>Highest Clinical Degree</th>
					<td><?php $render_document( $application->cp_highest_degree ); ?></td>
				</tr>
				<tr>
					<th>MOH License Document</th>
					<td><?php $render_document( $application->cp_moh_license_file ); ?></td>
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
									$cert_url = wp_get_attachment_url( $cert_id );
									?>
									<div style="margin-bottom: 10px;">
										<?php if ( wp_attachment_is_image( $cert_id ) ) : ?>
											<a href="<?php echo esc_url( $cert_url ); ?>" target="_blank" style="display: inline-block;">
												<?php echo wp_get_attachment_image( $cert_id, 'thumbnail', false, array( 'style' => 'max-width: 150px; height: auto; cursor: pointer;' ) ); ?>
											</a>
										<?php else : ?>
											<a href="<?php echo esc_url( $cert_url ); ?>" target="_blank">
												<?php echo esc_html( $filename ); ?>
											</a>
										<?php endif; ?>
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
			<?php
			$create_user_url = admin_url(
				'admin.php?page=jalsah-ai-applications&action=create_user&application_id=' . $application_id .
				'&_wpnonce=' . wp_create_nonce( 'application_create_user_' . $application_id )
			);
			$export_app_url = admin_url(
				'admin-post.php?action=snks_export_single_application&application_id=' . $application_id .
				'&_wpnonce=' . wp_create_nonce( 'snks_export_single_application_' . $application_id )
			);
			?>
			<a href="<?php echo esc_url( $create_user_url ); ?>" class="button">
				<?php echo $application->user_id ? 'Link Existing User' : 'Create User'; ?>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=edit&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_edit_' . $application_id ) ); ?>" class="button">Edit Profile</a>
			<a href="<?php echo esc_url( $export_app_url ); ?>" class="button">
				Export Application
			</a>
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
	
	$preferred_groups_value = '';
	if ( ! empty( $application->preferred_groups ) ) {
		$groups_decoded = json_decode( $application->preferred_groups, true );
		if ( is_array( $groups_decoded ) ) {
			$preferred_groups_value = wp_json_encode( $groups_decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		}
	}
	
	$therapy_courses_value = '';
	if ( ! empty( $application->therapy_courses ) ) {
		$courses_decoded = json_decode( $application->therapy_courses, true );
		if ( is_array( $courses_decoded ) ) {
			$therapy_courses_value = wp_json_encode( $courses_decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		}
	}
	
	$diagnoses_children_value = '';
	if ( ! empty( $application->diagnoses_children ) ) {
		$children_decoded = json_decode( $application->diagnoses_children, true );
		if ( is_array( $children_decoded ) ) {
			$diagnoses_children_value = wp_json_encode( $children_decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		}
	}
	
	$diagnoses_adult_value = '';
	if ( ! empty( $application->diagnoses_adult ) ) {
		$adult_decoded = json_decode( $application->diagnoses_adult, true );
		if ( is_array( $adult_decoded ) ) {
			$diagnoses_adult_value = wp_json_encode( $adult_decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		}
	}
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
				<h2>Professional Details</h2>
				<table class="form-table">
					<tr>
						<th><label for="role">Role</label></th>
						<td>
							<select id="role" name="role">
								<option value="">Select role</option>
								<option value="psychiatrist" <?php selected( $application->role, 'psychiatrist' ); ?>>طبيب نفسي</option>
								<option value="clinical_psychologist" <?php selected( $application->role, 'clinical_psychologist' ); ?>>أخصائي نفسي إكلينيكي</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="psychiatrist_rank">Psychiatrist Rank</label></th>
						<td><input type="text" id="psychiatrist_rank" name="psychiatrist_rank" value="<?php echo esc_attr( $application->psychiatrist_rank ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="psych_origin">Psychology Origin</label></th>
						<td><input type="text" id="psych_origin" name="psych_origin" value="<?php echo esc_attr( $application->psych_origin ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="cp_moh_license">MOH License</label></th>
						<td>
							<select id="cp_moh_license" name="cp_moh_license">
								<option value="" <?php selected( $application->cp_moh_license, '' ); ?>>—</option>
								<option value="yes" <?php selected( $application->cp_moh_license, 'yes' ); ?>>نعم</option>
								<option value="no" <?php selected( $application->cp_moh_license, 'no' ); ?>>لا</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="preferred_groups">Preferred Groups (JSON)</label></th>
						<td>
							<textarea id="preferred_groups" name="preferred_groups" rows="4" class="large-text code"><?php echo esc_textarea( $preferred_groups_value ); ?></textarea>
							<p class="description">JSON array of preferred groups (leave empty to clear).</p>
						</td>
					</tr>
					<tr>
						<th><label for="therapy_courses">Therapy Courses (JSON)</label></th>
						<td>
							<textarea id="therapy_courses" name="therapy_courses" rows="6" class="large-text code"><?php echo esc_textarea( $therapy_courses_value ); ?></textarea>
							<p class="description">JSON array of courses. Example: [{"school":"School","place":"Location","year":"2023"}]</p>
						</td>
					</tr>
					<tr>
						<th><label for="diagnoses_children">Diagnoses (Children) JSON</label></th>
						<td>
							<textarea id="diagnoses_children" name="diagnoses_children" rows="4" class="large-text code"><?php echo esc_textarea( $diagnoses_children_value ); ?></textarea>
							<p class="description">JSON array of diagnoses (leave empty to clear).</p>
						</td>
					</tr>
					<tr>
						<th><label for="diagnoses_adult">Diagnoses (Adults) JSON</label></th>
						<td>
							<textarea id="diagnoses_adult" name="diagnoses_adult" rows="4" class="large-text code"><?php echo esc_textarea( $diagnoses_adult_value ); ?></textarea>
							<p class="description">JSON array of diagnoses (leave empty to clear).</p>
						</td>
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
						<th><label for="graduate_certificate">Graduate Certificate</label></th>
						<td>
							<input type="hidden" id="graduate_certificate" name="graduate_certificate" value="<?php echo esc_attr( $application->graduate_certificate ); ?>" />
							<div id="graduate_certificate_preview">
								<?php if ( ! empty( $application->graduate_certificate ) ) : ?>
									<?php 
									$attachment = get_post( $application->graduate_certificate );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->graduate_certificate ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->graduate_certificate ) ) {
										echo wp_get_attachment_image( $application->graduate_certificate, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->graduate_certificate ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('graduate_certificate')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('graduate_certificate')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="practice_license">Practice License</label></th>
						<td>
							<input type="hidden" id="practice_license" name="practice_license" value="<?php echo esc_attr( $application->practice_license ); ?>" />
							<div id="practice_license_preview">
								<?php if ( ! empty( $application->practice_license ) ) : ?>
									<?php 
									$attachment = get_post( $application->practice_license );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->practice_license ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->practice_license ) ) {
										echo wp_get_attachment_image( $application->practice_license, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->practice_license ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('practice_license')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('practice_license')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="syndicate_card">Syndicate Card</label></th>
						<td>
							<input type="hidden" id="syndicate_card" name="syndicate_card" value="<?php echo esc_attr( $application->syndicate_card ); ?>" />
							<div id="syndicate_card_preview">
								<?php if ( ! empty( $application->syndicate_card ) ) : ?>
									<?php 
									$attachment = get_post( $application->syndicate_card );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->syndicate_card ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->syndicate_card ) ) {
										echo wp_get_attachment_image( $application->syndicate_card, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->syndicate_card ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('syndicate_card')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('syndicate_card')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="rank_certificate">Rank Certificate</label></th>
						<td>
							<input type="hidden" id="rank_certificate" name="rank_certificate" value="<?php echo esc_attr( $application->rank_certificate ); ?>" />
							<div id="rank_certificate_preview">
								<?php if ( ! empty( $application->rank_certificate ) ) : ?>
									<?php 
									$attachment = get_post( $application->rank_certificate );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->rank_certificate ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->rank_certificate ) ) {
										echo wp_get_attachment_image( $application->rank_certificate, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->rank_certificate ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('rank_certificate')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('rank_certificate')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="cp_graduate_certificate">Clinical Graduate Certificate</label></th>
						<td>
							<input type="hidden" id="cp_graduate_certificate" name="cp_graduate_certificate" value="<?php echo esc_attr( $application->cp_graduate_certificate ); ?>" />
							<div id="cp_graduate_certificate_preview">
								<?php if ( ! empty( $application->cp_graduate_certificate ) ) : ?>
									<?php 
									$attachment = get_post( $application->cp_graduate_certificate );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->cp_graduate_certificate ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->cp_graduate_certificate ) ) {
										echo wp_get_attachment_image( $application->cp_graduate_certificate, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->cp_graduate_certificate ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('cp_graduate_certificate')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('cp_graduate_certificate')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="cp_highest_degree">Highest Clinical Degree</label></th>
						<td>
							<input type="hidden" id="cp_highest_degree" name="cp_highest_degree" value="<?php echo esc_attr( $application->cp_highest_degree ); ?>" />
							<div id="cp_highest_degree_preview">
								<?php if ( ! empty( $application->cp_highest_degree ) ) : ?>
									<?php 
									$attachment = get_post( $application->cp_highest_degree );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->cp_highest_degree ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->cp_highest_degree ) ) {
										echo wp_get_attachment_image( $application->cp_highest_degree, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->cp_highest_degree ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('cp_highest_degree')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('cp_highest_degree')">Remove</button>
						</td>
					</tr>
					<tr>
						<th><label for="cp_moh_license_file">MOH License Document</label></th>
						<td>
							<input type="hidden" id="cp_moh_license_file" name="cp_moh_license_file" value="<?php echo esc_attr( $application->cp_moh_license_file ); ?>" />
							<div id="cp_moh_license_file_preview">
								<?php if ( ! empty( $application->cp_moh_license_file ) ) : ?>
									<?php 
									$attachment = get_post( $application->cp_moh_license_file );
									$filename = $attachment ? ( $attachment->post_title ?: basename( wp_get_attachment_url( $application->cp_moh_license_file ) ) ) : 'Document';
									if ( $attachment && wp_attachment_is_image( $application->cp_moh_license_file ) ) {
										echo wp_get_attachment_image( $application->cp_moh_license_file, 'thumbnail', false, array('style' => 'max-width: 150px; height: auto;') );
									} else {
										echo '<a href="' . esc_url( wp_get_attachment_url( $application->cp_moh_license_file ) ) . '" target="_blank">' . esc_html( $filename ) . '</a>';
									}
									?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" onclick="snks_upload_document('cp_moh_license_file')">Upload Document</button>
							<button type="button" class="button" onclick="snks_remove_document('cp_moh_license_file')">Remove</button>
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
		'role', 'psychiatrist_rank', 'psych_origin', 'cp_moh_license',
		'experience_years', 'education', 'bio', 'bio_en',
		'profile_image', 'identity_front', 'identity_back',
		'graduate_certificate', 'practice_license', 'syndicate_card', 'rank_certificate',
		'cp_graduate_certificate', 'cp_highest_degree', 'cp_moh_license_file',
		'certificates', 'therapy_courses', 'preferred_groups', 'diagnoses_children', 'diagnoses_adult',
		'rating', 'total_ratings', 'ai_bio', 'ai_bio_en', 'ai_certifications',
		'ai_earliest_slot', 'show_on_ai_site'
	];
	
	$data = [];
	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$value = wp_unslash( $_POST[ $field ] );
			
			switch ( $field ) {
				case 'rating':
					$data[ $field ] = floatval( $value );
					break;
				case 'total_ratings':
				case 'ai_earliest_slot':
					$data[ $field ] = intval( $value );
					break;
				case 'show_on_ai_site':
					$data[ $field ] = intval( $value );
					break;
				case 'profile_image':
				case 'identity_front':
				case 'identity_back':
				case 'graduate_certificate':
				case 'practice_license':
				case 'syndicate_card':
				case 'rank_certificate':
				case 'cp_graduate_certificate':
				case 'cp_highest_degree':
				case 'cp_moh_license_file':
					$data[ $field ] = '' === $value ? null : intval( $value );
					break;
				case 'therapy_courses':
					$value = trim( $value );
					if ( '' === $value ) {
						$data[ $field ] = null;
					} else {
						$decoded = json_decode( $value, true );
						$data[ $field ] = is_array( $decoded ) ? wp_json_encode( $decoded ) : null;
					}
					break;
				case 'preferred_groups':
				case 'diagnoses_children':
				case 'diagnoses_adult':
					$value = trim( $value );
					if ( '' === $value ) {
						$data[ $field ] = null;
					} else {
						$decoded = json_decode( $value, true );
						if ( is_array( $decoded ) ) {
							$decoded = array_values( array_filter( $decoded ) );
							$data[ $field ] = ! empty( $decoded ) ? wp_json_encode( $decoded ) : null;
						} else {
							$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $value ) ) );
							$data[ $field ] = ! empty( $lines ) ? wp_json_encode( $lines ) : null;
						}
					}
					break;
				default:
					$data[ $field ] = $value;
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
			
			// Trigger hooks to recalculate frontend_order for removed diagnoses
			foreach ( $diagnoses_to_remove as $diagnosis_id ) {
				do_action( 'snks_therapist_diagnosis_deleted', $application->user_id, $diagnosis_id );
			}
		}
		
		// Add or update selected diagnoses
		foreach ( $selected_diagnoses as $diagnosis_id ) {
			$rating = isset( $_POST["diagnosis_rating_$diagnosis_id"] ) ? floatval( $_POST["diagnosis_rating_$diagnosis_id"] ) : 0;
			$message_en = isset( $_POST["diagnosis_message_en_$diagnosis_id"] ) ? sanitize_textarea_field( $_POST["diagnosis_message_en_$diagnosis_id"] ) : '';
			$message_ar = isset( $_POST["diagnosis_message_ar_$diagnosis_id"] ) ? sanitize_textarea_field( $_POST["diagnosis_message_ar_$diagnosis_id"] ) : '';
			$order = isset( $_POST["diagnosis_order_$diagnosis_id"] ) ? intval( $_POST["diagnosis_order_$diagnosis_id"] ) : 0;
			
			$result = $wpdb->replace(
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
			
			// Trigger hook to recalculate frontend_order
			if ( $result !== false ) {
				do_action( 'snks_therapist_diagnosis_updated', $application->user_id, $diagnosis_id );
			}
		}
	}
}

 