<?php
/**
 * Demo Doctors Manager Admin Page
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add demo doctors manager page
 */
function snks_add_demo_doctors_manager_page() {
	// Try to add as submenu first
	add_submenu_page(
		'jalsah-ai-management',
		'Demo Doctors Manager',
		'Demo Doctors Manager',
		'manage_options',
		'demo-doctors-manager',
		'snks_demo_doctors_manager_page'
	);
	
	// Also add as standalone menu page for easier access
	add_menu_page(
		'Demo Doctors Manager',
		'Demo Doctors',
		'manage_options',
		'demo-doctors-manager',
		'snks_demo_doctors_manager_page',
		'dashicons-businessperson',
		31
	);
	
	// Debug: Log if function is called
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Demo Doctors Manager: Menu registration completed' );
	}
}
add_action( 'admin_menu', 'snks_add_demo_doctors_manager_page', 25 );

/**
 * Demo Doctors Manager Page
 */
function snks_demo_doctors_manager_page() {
	// Check if user has admin capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}
	
	global $wpdb;
	
	// Handle form submission for creating demo doctor
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'create_demo_doctor' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'create_demo_doctor' ) ) {
			
			$result = snks_create_demo_doctor( $_POST );
			
			if ( $result['success'] ) {
				echo '<div class="wrap">';
				echo '<h1>Demo Doctor Created Successfully!</h1>';
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
				
				echo '<h2>Created Doctor Details:</h2>';
				echo '<div class="card">';
				echo '<table class="form-table">';
				echo '<tr><th>Name:</th><td>' . esc_html( $_POST['name'] ) . '</td></tr>';
				echo '<tr><th>Email:</th><td>' . esc_html( $_POST['email'] ) . '</td></tr>';
				echo '<tr><th>Phone:</th><td>' . esc_html( $_POST['phone'] ) . '</td></tr>';
				echo '<tr><th>Specialty:</th><td>' . esc_html( $_POST['specialty'] ) . '</td></tr>';
				echo '<tr><th>Price (45 min):</th><td>$' . esc_html( $_POST['price'] ) . '</td></tr>';
				echo '<tr><th>Username:</th><td>' . esc_html( $_POST['phone'] ) . '</td></tr>';
				echo '<tr><th>Password:</th><td>' . esc_html( $_POST['password'] ) . '</td></tr>';
				echo '</table>';
				echo '</div>';
				
				echo '<h3>🔗 Quick Links:</h3>';
				echo '<ul>';
				echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-management' ) . '">AI Dashboard</a></li>';
				echo '<li><a href="' . admin_url( 'admin.php?page=ai-admin' ) . '">AI Therapist Settings</a></li>';
				echo '<li><a href="' . admin_url( 'users.php?role=doctor' ) . '">All Doctors</a></li>';
				echo '</ul>';
				
				echo '</div>';
				return;
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}
	}
	
	// Handle bulk demo doctor creation
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'create_bulk_demo_doctors' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'create_bulk_demo_doctors' ) ) {
			
			$count = intval( $_POST['count'] );
			$results = snks_create_bulk_demo_doctors( $count );
			
			echo '<div class="wrap">';
			echo '<h1>Bulk Demo Doctors Creation Results</h1>';
			
			if ( $results['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $results['message'] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $results['message'] ) . '</p></div>';
			}
			
			if ( ! empty( $results['details'] ) ) {
				echo '<h2>Creation Details:</h2>';
				echo '<ul>';
				foreach ( $results['details'] as $detail ) {
					echo '<li>' . esc_html( $detail ) . '</li>';
				}
				echo '</ul>';
			}
			
			echo '</div>';
			return;
		}
	}
	
	// Get existing demo doctors
	$demo_doctors = get_users( array(
		'role' => 'doctor',
		'meta_query' => array(
			array(
				'key' => 'is_demo_doctor',
				'value' => '1',
				'compare' => '='
			)
		),
		'orderby' => 'display_name'
	) );
	
	?>
	<div class="wrap">
		<h1>Demo Doctors Manager</h1>
		<p>Create demo doctors with 45-minute appointments for testing the Jalsah AI system.</p>
		
		<!-- Create Single Demo Doctor -->
		<div class="card">
			<h2>Create Single Demo Doctor</h2>
			<form method="post">
				<?php wp_nonce_field( 'create_demo_doctor' ); ?>
				<input type="hidden" name="action" value="create_demo_doctor">
				
				<table class="form-table">
					<tr>
						<th><label for="name">Doctor Name</label></th>
						<td>
							<input type="text" id="name" name="name" value="Dr. Sarah Johnson" required class="regular-text">
							<p class="description">Full name of the doctor</p>
						</td>
					</tr>
					<tr>
						<th><label for="name_en">English Name</label></th>
						<td>
							<input type="text" id="name_en" name="name_en" value="Dr. Sarah Johnson" required class="regular-text">
							<p class="description">English name for the doctor</p>
						</td>
					</tr>
					<tr>
						<th><label for="email">Email</label></th>
						<td>
							<input type="email" id="email" name="email" value="sarah.johnson@demo.com" required class="regular-text">
							<p class="description">Unique email address</p>
						</td>
					</tr>
					<tr>
						<th><label for="phone">Phone Number</label></th>
						<td>
							<input type="text" id="phone" name="phone" value="966501234567" required class="regular-text">
							<p class="description">Phone number (will be used as username)</p>
						</td>
					</tr>
					<tr>
						<th><label for="whatsapp">WhatsApp</label></th>
						<td>
							<input type="text" id="whatsapp" name="whatsapp" value="966501234567" required class="regular-text">
							<p class="description">WhatsApp number</p>
						</td>
					</tr>
					<tr>
						<th><label for="specialty">Specialty</label></th>
						<td>
							<input type="text" id="specialty" name="specialty" value="Clinical Psychologist" required class="regular-text">
							<p class="description">Professional specialty/title</p>
						</td>
					</tr>
					<tr>
						<th><label for="bio">Bio</label></th>
						<td>
							<textarea id="bio" name="bio" rows="4" class="large-text">Experienced clinical psychologist specializing in cognitive behavioral therapy and trauma treatment. With over 10 years of experience, I help clients overcome anxiety, depression, and PTSD through evidence-based therapeutic approaches.</textarea>
							<p class="description">Professional biography</p>
						</td>
					</tr>
					<tr>
						<th><label for="price">Session Price (45 min)</label></th>
						<td>
							<input type="number" id="price" name="price" value="150" min="50" max="500" required class="regular-text">
							<p class="description">Price for 45-minute session in USD</p>
						</td>
					</tr>
					<tr>
						<th><label for="password">Password</label></th>
						<td>
							<input type="text" id="password" name="password" value="demo123" required class="regular-text">
							<p class="description">Login password</p>
						</td>
					</tr>
					<tr>
						<th><label for="diagnoses">Specializations</label></th>
						<td>
							<select id="diagnoses" name="diagnoses[]" multiple class="regular-text">
								<?php
								$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
								foreach ( $diagnoses as $diagnosis ) {
									echo '<option value="' . esc_attr( $diagnosis->id ) . '">' . esc_html( $diagnosis->name ) . '</option>';
								}
								?>
							</select>
							<p class="description">Hold Ctrl/Cmd to select multiple specializations</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Create Demo Doctor' ); ?>
			</form>
		</div>
		
		<!-- Bulk Create Demo Doctors -->
		<div class="card">
			<h2>Bulk Create Demo Doctors</h2>
			<form method="post">
				<?php wp_nonce_field( 'create_bulk_demo_doctors' ); ?>
				<input type="hidden" name="action" value="create_bulk_demo_doctors">
				
				<table class="form-table">
					<tr>
						<th><label for="count">Number of Doctors</label></th>
						<td>
							<input type="number" id="count" name="count" value="5" min="1" max="20" required class="small-text">
							<p class="description">Number of demo doctors to create (1-20)</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Create Bulk Demo Doctors' ); ?>
			</form>
		</div>
		
		<!-- Existing Demo Doctors -->
		<div class="card">
			<h2>Existing Demo Doctors (<?php echo count( $demo_doctors ); ?>)</h2>
			<?php if ( ! empty( $demo_doctors ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Phone</th>
							<th>Specialty</th>
							<th>Price</th>
							<th>AI Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $demo_doctors as $doctor ) : ?>
							<tr>
								<td><?php echo esc_html( get_user_meta( $doctor->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $doctor->ID, 'billing_last_name', true ) ); ?></td>
								<td><?php echo esc_html( $doctor->user_email ); ?></td>
								<td><?php echo esc_html( get_user_meta( $doctor->ID, 'billing_phone', true ) ); ?></td>
								<td><?php echo esc_html( get_user_meta( $doctor->ID, 'doctor_specialty', true ) ); ?></td>
								<td>$<?php echo esc_html( get_user_meta( $doctor->ID, 'session_price', true ) ?: 'N/A' ); ?></td>
								<td>
									<?php 
									$ai_status = get_user_meta( $doctor->ID, 'show_on_ai_site', true );
									echo $ai_status ? '<span style="color: green;">✓ Active</span>' : '<span style="color: red;">✗ Inactive</span>';
									?>
								</td>
								<td>
									<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $doctor->ID ); ?>" class="button button-small">Edit</a>
									<a href="<?php echo admin_url( 'admin.php?page=ai-admin&therapist_id=' . $doctor->ID ); ?>" class="button button-small">AI Settings</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No demo doctors found. Create some using the form above.</p>
			<?php endif; ?>
		</div>
		
		<!-- Quick Actions -->
		<div class="card">
			<h2>Quick Actions</h2>
			<p>
				<a href="<?php echo admin_url( 'admin.php?page=ai-admin' ); ?>" class="button button-primary">Manage AI Therapist Settings</a>
				<a href="<?php echo admin_url( 'users.php?role=doctor' ); ?>" class="button">View All Doctors</a>
				<a href="<?php echo admin_url( 'admin.php?page=jalsah-ai-management' ); ?>" class="button">AI Dashboard</a>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Create a single demo doctor
 */
function snks_create_demo_doctor( $data ) {
	// Validate required fields
	$required_fields = array( 'name', 'name_en', 'email', 'phone', 'whatsapp', 'specialty', 'price', 'password' );
	foreach ( $required_fields as $field ) {
		if ( empty( $data[ $field ] ) ) {
			return array( 'success' => false, 'message' => "Missing required field: {$field}" );
		}
	}
	
	// Check if email already exists
	if ( email_exists( $data['email'] ) ) {
		return array( 'success' => false, 'message' => 'Email already exists' );
	}
	
	// Check if phone already exists
	if ( username_exists( $data['phone'] ) ) {
		return array( 'success' => false, 'message' => 'Phone number already exists' );
	}
	
	// Create user
	$user_id = wp_create_user( $data['phone'], $data['password'], $data['email'] );
	if ( is_wp_error( $user_id ) ) {
		return array( 'success' => false, 'message' => $user_id->get_error_message() );
	}
	
	// Set user role
	$user = get_user_by( 'ID', $user_id );
	$user->set_role( 'doctor' );
	
	// Update user meta
	update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $data['name'] ) );
	update_user_meta( $user_id, 'billing_last_name', sanitize_text_field( $data['name_en'] ) );
	update_user_meta( $user_id, 'billing_phone', sanitize_text_field( $data['phone'] ) );
	update_user_meta( $user_id, 'whatsapp', sanitize_text_field( $data['whatsapp'] ) );
	update_user_meta( $user_id, 'doctor_specialty', sanitize_text_field( $data['specialty'] ) );
	update_user_meta( $user_id, 'session_price', intval( $data['price'] ) );
	update_user_meta( $user_id, 'bio', sanitize_textarea_field( $data['bio'] ) );
	
	// Mark as demo doctor
	update_user_meta( $user_id, 'is_demo_doctor', '1' );
	
	// Set AI-related meta fields
	update_user_meta( $user_id, 'show_on_ai_site', '1' );
	update_user_meta( $user_id, 'ai_display_name', sanitize_text_field( $data['name'] ) );
	update_user_meta( $user_id, 'ai_bio', sanitize_textarea_field( $data['bio'] ) );
	update_user_meta( $user_id, 'public_short_bio', sanitize_text_field( $data['specialty'] ) );
	update_user_meta( $user_id, 'secretary_phone', sanitize_text_field( $data['phone'] ) );
	update_user_meta( $user_id, 'ai_first_session_percentage', '15' );
	update_user_meta( $user_id, 'ai_followup_session_percentage', '10' );
	update_user_meta( $user_id, 'ai_earliest_slot', '09:00' );
	
	// Set price for 45-minute sessions
	update_user_meta( $user_id, 'price_45_min', intval( $data['price'] ) );
	update_user_meta( $user_id, 'price_60_min', intval( $data['price'] * 1.33 ) );
	update_user_meta( $user_id, 'price_90_min', intval( $data['price'] * 2 ) );
	
	// Assign diagnoses if selected
	if ( ! empty( $data['diagnoses'] ) && is_array( $data['diagnoses'] ) ) {
		global $wpdb;
		$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
		
		foreach ( $data['diagnoses'] as $diagnosis_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'snks_doctor_diagnoses',
				array(
					'doctor_id' => $user_id,
					'diagnosis_id' => intval( $diagnosis_id ),
					'rating' => rand( 4, 5 ), // Random rating between 4-5
					'suitability_message' => 'This therapist specializes in treating this condition with evidence-based approaches.'
				),
				array( '%d', '%d', '%f', '%s' )
			);
		}
	}
	
	// Create default availability slots (45-minute sessions)
	snks_create_demo_availability_slots( $user_id );
	
	return array( 
		'success' => true, 
		'message' => "Demo doctor '{$data['name']}' created successfully with 45-minute appointment slots." 
	);
}

/**
 * Create bulk demo doctors
 */
function snks_create_bulk_demo_doctors( $count ) {
	$results = array();
	$success_count = 0;
	$error_count = 0;
	
	$demo_names = array(
		'Dr. Sarah Johnson', 'Dr. Michael Chen', 'Dr. Emily Rodriguez', 'Dr. David Thompson',
		'Dr. Lisa Park', 'Dr. James Wilson', 'Dr. Maria Garcia', 'Dr. Robert Brown',
		'Dr. Jennifer Lee', 'Dr. Christopher Davis', 'Dr. Amanda White', 'Dr. Daniel Martinez',
		'Dr. Rachel Green', 'Dr. Kevin Taylor', 'Dr. Nicole Anderson', 'Dr. Steven Clark',
		'Dr. Jessica Hall', 'Dr. Matthew Lewis', 'Dr. Samantha Walker', 'Dr. Andrew Young'
	);
	
	$specialties = array(
		'Clinical Psychologist', 'Psychiatrist', 'Counseling Psychologist', 'Marriage and Family Therapist',
		'Licensed Professional Counselor', 'Social Worker', 'Art Therapist', 'Cognitive Behavioral Therapist'
	);
	
	$diagnoses = array( 'Anxiety Disorders', 'Depression', 'PTSD', 'OCD', 'Bipolar Disorder', 'Stress Management' );
	
	for ( $i = 0; $i < $count; $i++ ) {
		$name = $demo_names[ $i % count( $demo_names ) ];
		$specialty = $specialties[ $i % count( $specialties ) ];
		$price = rand( 100, 300 );
		$phone_base = 966500000000 + $i;
		
		$data = array(
			'name' => $name,
			'name_en' => $name,
			'email' => 'demo.doctor' . $i . '@jalsah.app',
			'phone' => $phone_base,
			'whatsapp' => $phone_base,
			'specialty' => $specialty,
			'bio' => "Experienced {$specialty} with expertise in mental health treatment. Committed to providing compassionate care and evidence-based therapeutic approaches.",
			'price' => $price,
			'password' => 'demo123',
			'diagnoses' => array( rand( 1, 6 ) ) // Random diagnosis
		);
		
		$result = snks_create_demo_doctor( $data );
		
		if ( $result['success'] ) {
			$success_count++;
			$results['details'][] = "✅ Created: {$name} ({$specialty}) - \${$price}";
		} else {
			$error_count++;
			$results['details'][] = "❌ Failed: {$name} - {$result['message']}";
		}
	}
	
	if ( $success_count > 0 ) {
		$results['success'] = true;
		$results['message'] = "Successfully created {$success_count} demo doctors. {$error_count} failed.";
	} else {
		$results['success'] = false;
		$results['message'] = "Failed to create any demo doctors. {$error_count} errors occurred.";
	}
	
	return $results;
}

/**
 * Create demo availability slots for 45-minute sessions
 */
function snks_create_demo_availability_slots( $doctor_id ) {
	global $wpdb;
	
	// Get current date and create slots for next 30 days
	$start_date = current_time( 'Y-m-d' );
	$end_date = date( 'Y-m-d', strtotime( '+30 days' ) );
	
	$time_slots = array(
		'09:00', '09:45', '10:30', '11:15', '12:00', '12:45',
		'14:00', '14:45', '15:30', '16:15', '17:00', '17:45'
	);
	
	$current_date = $start_date;
	while ( $current_date <= $end_date ) {
		// Skip weekends (Saturday = 6, Sunday = 0)
		$day_of_week = date( 'w', strtotime( $current_date ) );
		if ( $day_of_week != 0 && $day_of_week != 6 ) {
			foreach ( $time_slots as $time ) {
				$wpdb->insert(
					$wpdb->prefix . 'snks_available_periods',
					array(
						'doctor_id' => $doctor_id,
						'date' => $current_date,
						'time' => $time,
						'duration' => 45,
						'status' => 'available',
						'created_at' => current_time( 'mysql' )
					),
					array( '%d', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}
		$current_date = date( 'Y-m-d', strtotime( $current_date . ' +1 day' ) );
	}
} 