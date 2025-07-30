<?php
/**
 * Centralized Therapist Applications Management
 * Applications serve as complete therapist profiles
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Enhanced Therapist Applications Page
 */
function snks_enhanced_ai_applications_page() {
	snks_load_ai_admin_styles();
	
	// Handle bulk actions
	if ( isset( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'bulk-applications' ) ) {
			$action = $_POST['action'];
			$application_ids = isset( $_POST['application_ids'] ) ? array_map( 'intval', $_POST['application_ids'] ) : [];
			
			if ( !empty( $application_ids ) ) {
				$processed = 0;
				
				foreach ( $application_ids as $app_id ) {
					$post = get_post( $app_id );
					if ( $post && $post->post_type === 'therapist_app' ) {
						switch ( $action ) {
							case 'approve':
								if ( $post->post_status === 'pending' ) {
									snks_approve_therapist_application( $app_id );
									$processed++;
								}
								break;
							case 'reject':
								wp_update_post( [
									'ID' => $app_id,
									'post_status' => 'rejected'
								] );
								$processed++;
								break;
							case 'delete':
								wp_delete_post( $app_id, true );
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
			$post = get_post( $app_id );
			if ( $post && $post->post_type === 'therapist_app' ) {
				switch ( $action ) {
					case 'approve':
						if ( $post->post_status === 'pending' ) {
							snks_approve_therapist_application( $app_id );
							echo '<div class="notice notice-success"><p>Application approved successfully!</p></div>';
						}
						break;
					case 'reject':
						wp_update_post( [
							'ID' => $app_id,
							'post_status' => 'rejected'
						] );
						echo '<div class="notice notice-success"><p>Application rejected.</p></div>';
						break;
					case 'view':
						snks_display_application_details( $app_id );
						return;
				}
			}
		}
	}
	
	// Get applications with filters
	$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
	$search_filter = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
	
	$args = [
		'post_type' => 'therapist_app',
		'post_status' => ['pending', 'publish', 'rejected'],
		'numberposts' => -1,
		'orderby' => 'date',
		'order' => 'DESC'
	];
	
	if ( $status_filter ) {
		$args['post_status'] = $status_filter;
	}
	
	if ( $search_filter ) {
		$args['s'] = $search_filter;
	}
	
	$applications = get_posts( $args );
	
	// Count by status
	$pending_count = count( get_posts( ['post_type' => 'therapist_app', 'post_status' => 'pending', 'numberposts' => -1] ) );
	$approved_count = count( get_posts( ['post_type' => 'therapist_app', 'post_status' => 'publish', 'numberposts' => -1] ) );
	$rejected_count = count( get_posts( ['post_type' => 'therapist_app', 'post_status' => 'rejected', 'numberposts' => -1] ) );
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
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&status=publish' ); ?>" 
			   class="nav-tab <?php echo $status_filter === 'publish' ? 'nav-tab-active' : ''; ?>">
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
							<?php $meta = get_post_meta( $app->ID ); ?>
							<tr>
								<th class="check-column">
									<input type="checkbox" name="application_ids[]" value="<?php echo $app->ID; ?>">
								</th>
								<td class="column-name">
									<strong>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=view&application_id=' . $app->ID . '&_wpnonce=' . wp_create_nonce( 'application_view_' . $app->ID ) ); ?>">
											<?php echo esc_html( $meta['name'][0] ?? $meta['name_en'][0] ?? 'Unknown' ); ?>
										</a>
									</strong>
									<?php if ( $meta['name_en'][0] && $meta['name_en'][0] !== $meta['name'][0] ) : ?>
										<br><small><?php echo esc_html( $meta['name_en'][0] ); ?></small>
									<?php endif; ?>
								</td>
								<td class="column-email">
									<?php echo esc_html( $meta['email'][0] ?? '' ); ?>
								</td>
								<td class="column-phone">
									<?php echo esc_html( $meta['phone'][0] ?? '' ); ?>
								</td>
								<td class="column-specialty">
									<?php echo esc_html( $meta['doctor_specialty'][0] ?? '' ); ?>
								</td>
								<td class="column-status">
									<?php
									$status = get_post_status( $app->ID );
									$status_labels = [
										'pending' => '<span class="status-pending">Pending</span>',
										'publish' => '<span class="status-approved">Active</span>',
										'rejected' => '<span class="status-rejected">Rejected</span>'
									];
									echo $status_labels[ $status ] ?? $status;
									?>
								</td>
								<td class="column-date">
									<?php echo get_the_date( 'Y-m-d H:i', $app->ID ); ?>
								</td>
								<td class="column-actions">
									<?php if ( $status === 'pending' ) : ?>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $app->ID . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $app->ID ) ); ?>" 
										   class="button button-small button-primary">Approve</a>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=reject&application_id=' . $app->ID . '&_wpnonce=' . wp_create_nonce( 'application_reject_' . $app->ID ) ); ?>" 
										   class="button button-small">Reject</a>
									<?php elseif ( $status === 'rejected' ) : ?>
										<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $app->ID . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $app->ID ) ); ?>" 
										   class="button button-small button-primary">Approve</a>
									<?php endif; ?>
									<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=view&application_id=' . $app->ID . '&_wpnonce=' . wp_create_nonce( 'application_view_' . $app->ID ) ); ?>" 
									   class="button button-small">View</a>
									<a href="<?php echo admin_url( 'post.php?post=' . $app->ID . '&action=edit' ); ?>" 
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
	$post = get_post( $application_id );
	if ( !$post || $post->post_type !== 'therapist_app' || $post->post_status !== 'pending' ) {
		return false;
	}
	
	$meta = get_post_meta( $application_id );
	$email = $meta['email'][0] ?? '';
	$phone = $meta['phone'][0] ?? '';
	$name = $meta['name'][0] ?? '';
	$name_en = $meta['name_en'][0] ?? '';
	$mode = $meta['password_mode'][0] ?? 'auto';
	$password = $meta['password'][0] ?? '';
	
	if ( $mode === 'auto' || empty( $password ) ) {
		$password = wp_generate_password( 8, false );
	}
	
	// Check if user already exists
	$existing_user = get_user_by( 'email', $email );
	if ( $existing_user ) {
		$user_id = $existing_user->ID;
	} else {
		// Create new user with minimal data
		if ( username_exists( $phone ) ) {
			return false;
		}
		$user_id = wp_create_user( $phone, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			return false;
		}
	}
	
	$user = get_user_by( 'id', $user_id );
	$user->set_role( 'doctor' );
	
	// Set only essential user meta for login purposes
	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'billing_email', $email );
	
	// Mark application as approved and link to user
	wp_update_post( [
		'ID' => $application_id,
		'post_status' => 'publish',
		'post_author' => $user_id
	] );
	
	// Notify user
	$email_subject = __( 'Your therapist application is approved' );
	$email_message = sprintf(
		__( 'Your account has been created successfully!' ) . "\n\n" .
		__( 'Username: %s' ) . "\n" .
		__( 'Password: %s' ) . "\n\n" .
		__( 'You can now log in to your account and start using the platform.' ),
		$phone,
		$password
	);
	wp_mail( $email, $email_subject, $email_message );
	
	return true;
}

/**
 * Display application/profile details
 */
function snks_display_application_details( $application_id ) {
	$post = get_post( $application_id );
	if ( !$post || $post->post_type !== 'therapist_app' ) {
		wp_die( 'Application not found.' );
	}
	
	$meta = get_post_meta( $application_id );
	$status = get_post_status( $application_id );
	?>
	<div class="wrap">
		<h1><?php echo $status === 'publish' ? 'Therapist Profile' : 'Application Details'; ?></h1>
		
		<div class="card">
			<h2>Basic Information</h2>
			<table class="form-table">
				<tr>
					<th>Name (Arabic)</th>
					<td><?php echo esc_html( $meta['name'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Name (English)</th>
					<td><?php echo esc_html( $meta['name_en'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Email</th>
					<td><?php echo esc_html( $meta['email'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Phone</th>
					<td><?php echo esc_html( $meta['phone'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>WhatsApp</th>
					<td><?php echo esc_html( $meta['whatsapp'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Specialty</th>
					<td><?php echo esc_html( $meta['doctor_specialty'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Experience Years</th>
					<td><?php echo esc_html( $meta['experience_years'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Education</th>
					<td><?php echo esc_html( $meta['education'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Bio (Arabic)</th>
					<td><?php echo esc_html( $meta['bio'][0] ?? '' ); ?></td>
				</tr>
				<tr>
					<th>Bio (English)</th>
					<td><?php echo esc_html( $meta['bio_en'][0] ?? '' ); ?></td>
				</tr>
			</table>
		</div>
		
		<div class="card">
			<h2>Documents</h2>
			<table class="form-table">
				<tr>
					<th>Profile Image</th>
					<td>
						<?php if ( !empty( $meta['profile_image'][0] ) ) : ?>
							<?php echo wp_get_attachment_image( $meta['profile_image'][0], 'thumbnail' ); ?>
						<?php else : ?>
							No image uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Identity Front</th>
					<td>
						<?php if ( !empty( $meta['identity_front'][0] ) ) : ?>
							<a href="<?php echo wp_get_attachment_url( $meta['identity_front'][0] ); ?>" target="_blank">View Document</a>
						<?php else : ?>
							No document uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Identity Back</th>
					<td>
						<?php if ( !empty( $meta['identity_back'][0] ) ) : ?>
							<a href="<?php echo wp_get_attachment_url( $meta['identity_back'][0] ); ?>" target="_blank">View Document</a>
						<?php else : ?>
							No document uploaded
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Certificates</th>
					<td>
						<?php
						$certificates = isset( $meta['certificates'] ) ? maybe_unserialize( $meta['certificates'][0] ) : [];
						if ( !empty( $certificates ) ) :
							foreach ( $certificates as $cert_id ) :
								$url = wp_get_attachment_url( $cert_id );
								if ( $url ) :
									?>
									<div style="margin-bottom: 10px;">
										<a href="<?php echo esc_url( $url ); ?>" target="_blank">
											<?php echo esc_html( basename( $url ) ); ?>
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
			<?php if ( $post->post_status === 'pending' ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $application_id ) ); ?>" 
				   class="button button-primary">Approve Application</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=reject&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_reject_' . $application_id ) ); ?>" 
				   class="button">Reject Application</a>
			<?php elseif ( $post->post_status === 'rejected' ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications&action=approve&application_id=' . $application_id . '&_wpnonce=' . wp_create_nonce( 'application_approve_' . $application_id ) ); ?>" 
				   class="button button-primary">Approve Application</a>
			<?php endif; ?>
			<a href="<?php echo admin_url( 'post.php?post=' . $application_id . '&action=edit' ); ?>" class="button">Edit Profile</a>
			<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-applications' ); ?>" class="button">Back to Applications</a>
		</div>
	</div>
	<?php
}

/**
 * Add custom meta boxes to therapist application edit page
 */
function snks_add_therapist_application_meta_boxes() {
	add_meta_box(
		'therapist_profile_data',
		'Therapist Profile Data',
		'snks_therapist_profile_meta_box',
		'therapist_app',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'snks_add_therapist_application_meta_boxes' );

/**
 * Therapist profile meta box content
 */
function snks_therapist_profile_meta_box( $post ) {
	wp_nonce_field( 'save_therapist_profile', 'therapist_profile_nonce' );
	
	$meta = get_post_meta( $post->ID );
	?>
	<table class="form-table">
		<tr>
			<th><label for="name">Name (Arabic)</label></th>
			<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $meta['name'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="name_en">Name (English)</label></th>
			<td><input type="text" id="name_en" name="name_en" value="<?php echo esc_attr( $meta['name_en'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="email">Email</label></th>
			<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $meta['email'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="phone">Phone</label></th>
			<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $meta['phone'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="whatsapp">WhatsApp</label></th>
			<td><input type="text" id="whatsapp" name="whatsapp" value="<?php echo esc_attr( $meta['whatsapp'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="doctor_specialty">Specialty</label></th>
			<td><input type="text" id="doctor_specialty" name="doctor_specialty" value="<?php echo esc_attr( $meta['doctor_specialty'][0] ?? '' ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="experience_years">Experience Years</label></th>
			<td><input type="number" id="experience_years" name="experience_years" value="<?php echo esc_attr( $meta['experience_years'][0] ?? '' ); ?>" class="small-text" /></td>
		</tr>
		<tr>
			<th><label for="education">Education</label></th>
			<td><textarea id="education" name="education" rows="3" class="large-text"><?php echo esc_textarea( $meta['education'][0] ?? '' ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="bio">Bio (Arabic)</label></th>
			<td><textarea id="bio" name="bio" rows="4" class="large-text"><?php echo esc_textarea( $meta['bio'][0] ?? '' ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="bio_en">Bio (English)</label></th>
			<td><textarea id="bio_en" name="bio_en" rows="4" class="large-text"><?php echo esc_textarea( $meta['bio_en'][0] ?? '' ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="profile_image">Profile Image</label></th>
			<td>
				<input type="hidden" id="profile_image" name="profile_image" value="<?php echo esc_attr( $meta['profile_image'][0] ?? '' ); ?>" />
				<div id="profile_image_preview">
					<?php if ( !empty( $meta['profile_image'][0] ) ) : ?>
						<?php echo wp_get_attachment_image( $meta['profile_image'][0], 'thumbnail' ); ?>
					<?php endif; ?>
				</div>
				<button type="button" class="button" onclick="snks_upload_image('profile_image')">Upload Image</button>
				<button type="button" class="button" onclick="snks_remove_image('profile_image')">Remove</button>
			</td>
		</tr>
		<tr>
			<th><label for="identity_front">Identity Front</label></th>
			<td>
				<input type="hidden" id="identity_front" name="identity_front" value="<?php echo esc_attr( $meta['identity_front'][0] ?? '' ); ?>" />
				<div id="identity_front_preview">
					<?php if ( !empty( $meta['identity_front'][0] ) ) : ?>
						<a href="<?php echo wp_get_attachment_url( $meta['identity_front'][0] ); ?>" target="_blank">View Document</a>
					<?php endif; ?>
				</div>
				<button type="button" class="button" onclick="snks_upload_document('identity_front')">Upload Document</button>
				<button type="button" class="button" onclick="snks_remove_document('identity_front')">Remove</button>
			</td>
		</tr>
		<tr>
			<th><label for="identity_back">Identity Back</label></th>
			<td>
				<input type="hidden" id="identity_back" name="identity_back" value="<?php echo esc_attr( $meta['identity_back'][0] ?? '' ); ?>" />
				<div id="identity_back_preview">
					<?php if ( !empty( $meta['identity_back'][0] ) ) : ?>
						<a href="<?php echo wp_get_attachment_url( $meta['identity_back'][0] ); ?>" target="_blank">View Document</a>
					<?php endif; ?>
				</div>
				<button type="button" class="button" onclick="snks_upload_document('identity_back')">Upload Document</button>
				<button type="button" class="button" onclick="snks_remove_document('identity_back')">Remove</button>
			</td>
		</tr>
		<tr>
			<th><label for="certificates">Certificates</label></th>
			<td>
				<input type="hidden" id="certificates" name="certificates" value="<?php echo esc_attr( $meta['certificates'][0] ?? '' ); ?>" />
				<div id="certificates_preview">
					<?php
					$certificates = isset( $meta['certificates'] ) ? maybe_unserialize( $meta['certificates'][0] ) : [];
					if ( !empty( $certificates ) ) :
						foreach ( $certificates as $cert_id ) :
							$url = wp_get_attachment_url( $cert_id );
							if ( $url ) :
								?>
								<div style="margin-bottom: 10px;">
									<a href="<?php echo esc_url( $url ); ?>" target="_blank">
										<?php echo esc_html( basename( $url ) ); ?>
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
			document.getElementById(field_id + '_preview').innerHTML = '<img src="' + attachment.sizes.thumbnail.url + '" />';
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
			document.getElementById(field_id + '_preview').innerHTML = '<a href="' + attachment.url + '" target="_blank">View Document</a>';
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
				preview += '<div style="margin-bottom: 10px;"><a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a></div>';
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
 * Save therapist profile data
 */
function snks_save_therapist_profile( $post_id ) {
	if ( !isset( $_POST['therapist_profile_nonce'] ) || !wp_verify_nonce( $_POST['therapist_profile_nonce'], 'save_therapist_profile' ) ) {
		return;
	}
	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	
	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	
	$fields = [
		'name', 'name_en', 'email', 'phone', 'whatsapp', 'doctor_specialty',
		'experience_years', 'education', 'bio', 'bio_en',
		'profile_image', 'identity_front', 'identity_back', 'certificates'
	];
	
	foreach ( $fields as $field ) {
		if ( isset( $_POST[$field] ) ) {
			$value = $_POST[$field];
			if ( $field === 'certificates' && !empty( $value ) ) {
				$value = json_decode( $value, true );
			}
			update_post_meta( $post_id, $field, $value );
		}
	}
}
add_action( 'save_post', 'snks_save_therapist_profile' ); 