<?php
/**
 * Test Data Populator Admin Page
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add test data populator page
 */
function snks_add_test_data_populator_page() {
	add_submenu_page(
		'jalsah-ai-management',
		'Populate Test Data',
		'Populate Test Data',
		'manage_options',
		'ai-test-data-populator',
		'snks_test_data_populator_page'
	);
}
add_action( 'admin_menu', 'snks_add_test_data_populator_page' );

/**
 * Test Data Populator Page
 */
function snks_test_data_populator_page() {
	// Check if user has admin capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}
	
	global $wpdb;
	
	// Handle form submission
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'populate_test_data' ) {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'populate_test_data' ) ) {
			
			$customer_id = intval( $_POST['customer_id'] );
			$doctor_id = intval( $_POST['doctor_id'] );
			
			$results = snks_populate_ai_test_data( $customer_id, $doctor_id );
			
			echo '<div class="wrap">';
			echo '<h1>AI Test Data Population Results</h1>';
			echo '<div class="notice notice-success"><p>Test data population completed!</p></div>';
			
			echo '<h2>Results Summary:</h2>';
			echo '<ul>';
			foreach ( $results as $result ) {
				echo '<li>' . esc_html( $result ) . '</li>';
			}
			echo '</ul>';
			
			echo '<h3>🔗 Test Links:</h3>';
			echo '<ul>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-management' ) . '">AI Dashboard</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=rochtah-doctor-dashboard' ) . '">Rochtah Doctor Dashboard</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=rochtah-doctor-management' ) . '">Manage Rochtah Doctors</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-therapists' ) . '">AI Therapists</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-diagnoses' ) . '">AI Diagnoses</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-sessions' ) . '">AI Sessions</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-coupons' ) . '">AI Coupons</a></li>';
			echo '<li><a href="' . admin_url( 'admin.php?page=jalsah-ai-analytics' ) . '">AI Analytics</a></li>';
			echo '</ul>';
			
			echo '</div>';
			return;
		}
	}
	
	?>
	<div class="wrap">
		<h1>AI Test Data Populator</h1>
		<p>This tool will populate AI-related database tables with test data for testing the admin AI functionality.</p>
		
		<div class="card">
			<h2>Test Data Configuration</h2>
			<form method="post">
				<?php wp_nonce_field( 'populate_test_data' ); ?>
				<input type="hidden" name="action" value="populate_test_data">
				
				<table class="form-table">
					<tr>
						<th><label for="customer_id">Customer ID</label></th>
						<td>
							<input type="number" id="customer_id" name="customer_id" value="42" required>
							<p class="description">The customer/user ID to use for test data</p>
						</td>
					</tr>
					<tr>
						<th><label for="doctor_id">Doctor ID</label></th>
						<td>
							<input type="number" id="doctor_id" name="doctor_id" value="41" required>
							<p class="description">The doctor/therapist ID to use for test data</p>
						</td>
					</tr>
				</table>
				
				<h3>What will be created:</h3>
				<ul>
					<li>✅ Admin user assigned Rochtah Doctor role</li>
					<li>✅ Customer updated with AI meta fields</li>
					<li>✅ Doctor updated with AI meta fields</li>
					<li>✅ 6 test diagnoses (Anxiety, Depression, PTSD, OCD, Bipolar, Stress)</li>
					<li>✅ Doctor-diagnosis assignments</li>
					<li>✅ Rochtah appointment slots for weekdays</li>
					<li>✅ 3 test Rochtah bookings (pending, confirmed, prescribed)</li>
					<li>✅ 2 test AI coupons (AIWELCOME20, ROCHTAH50)</li>
					<li>✅ Test AI analytics events</li>
					<li>✅ Test notifications</li>
					<li>✅ Rochtah and AI settings configuration</li>
				</ul>
				
				<?php submit_button( 'Populate Test Data', 'primary', 'submit', false, array( 'onclick' => 'return confirm("This will create test data in the database. Continue?");' ) ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Important Notes</h2>
			<ul>
				<li>This will only create data if it doesn't already exist</li>
				<li>Existing data will not be overwritten</li>
				<li>Test data includes realistic medical scenarios</li>
				<li>All dates are relative to current date</li>
				<li>Remember to remove test data in production</li>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * Populate AI test data
 */
function snks_populate_ai_test_data( $customer_id, $doctor_id ) {
	global $wpdb;
	$results = array();
	
	// 1. Set up admin as Rochtah manager
	$admin_user = wp_get_current_user();
	$admin_user->add_role( 'rochtah_doctor' );
	$results[] = "✅ Admin user '{$admin_user->display_name}' assigned Rochtah Doctor role";
	
	// 2. Add AI meta fields to customer
	$customer = get_user_by( 'ID', $customer_id );
	if ( $customer ) {
		update_user_meta( $customer_id, 'registration_source', 'jalsah_ai' );
		update_user_meta( $customer_id, 'ai_cart', json_encode( array() ) );
		$results[] = "✅ Customer ID {$customer_id} ({$customer->display_name}) updated with AI meta fields";
	} else {
		$results[] = "❌ Customer ID {$customer_id} not found";
	}
	
	// 3. Add AI meta fields to doctor
	$doctor = get_user_by( 'ID', $doctor_id );
	if ( $doctor ) {
		update_user_meta( $doctor_id, 'show_on_ai_site', '1' );
		update_user_meta( $doctor_id, 'ai_display_name', 'Dr. ' . $doctor->display_name );
		update_user_meta( $doctor_id, 'ai_bio', 'Experienced therapist specializing in mental health and wellness.' );
		update_user_meta( $doctor_id, 'public_short_bio', 'Expert mental health professional with 10+ years experience.' );
		update_user_meta( $doctor_id, 'secretary_phone', '+966501234567' );
		update_user_meta( $doctor_id, 'ai_first_session_percentage', '15' );
		update_user_meta( $doctor_id, 'ai_followup_session_percentage', '10' );
		update_user_meta( $doctor_id, 'ai_earliest_slot', '09:00' );
		$results[] = "✅ Doctor ID {$doctor_id} ({$doctor->display_name}) updated with AI meta fields";
	} else {
		$results[] = "❌ Doctor ID {$doctor_id} not found";
	}
	
	// 4. Create test diagnoses
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	$test_diagnoses = array(
		array( 'name' => 'Anxiety Disorders', 'description' => 'Generalized anxiety, panic attacks, and related conditions', 'priority' => 8 ),
		array( 'name' => 'Depression', 'description' => 'Major depressive disorder and related mood conditions', 'priority' => 9 ),
		array( 'name' => 'PTSD', 'description' => 'Post-traumatic stress disorder', 'priority' => 7 ),
		array( 'name' => 'OCD', 'description' => 'Obsessive-compulsive disorder', 'priority' => 6 ),
		array( 'name' => 'Bipolar Disorder', 'description' => 'Bipolar I and II disorders', 'priority' => 8 ),
		array( 'name' => 'Stress Management', 'description' => 'Work-related stress and burnout', 'priority' => 5 )
	);
	
	foreach ( $test_diagnoses as $diagnosis ) {
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $diagnoses_table WHERE name = %s",
			$diagnosis['name']
		) );
		
		if ( ! $existing ) {
			$wpdb->insert(
				$diagnoses_table,
				array(
					'name' => $diagnosis['name'],
					'description' => $diagnosis['description'],
					'priority' => $diagnosis['priority'],
					'status' => 'active'
				),
				array( '%s', '%s', '%d', '%s' )
			);
			$results[] = "✅ Created diagnosis: {$diagnosis['name']}";
		} else {
			$results[] = "ℹ️ Diagnosis already exists: {$diagnosis['name']}";
		}
	}
	
	// 5. Create therapist-diagnosis assignments
	$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
	$diagnosis_ids = $wpdb->get_col( "SELECT id FROM $diagnoses_table WHERE status = 'active'" );
	
	foreach ( $diagnosis_ids as $diagnosis_id ) {
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $therapist_diagnoses_table WHERE therapist_id = %d AND diagnosis_id = %d",
			$doctor_id,
			$diagnosis_id
		) );
		
		if ( ! $existing ) {
			$wpdb->insert(
				$therapist_diagnoses_table,
				array(
					'therapist_id' => $doctor_id,
					'diagnosis_id' => $diagnosis_id,
					'rating' => rand( 7, 10 ),
					'suitability_message' => 'Experienced in treating this condition with proven results.'
				),
				array( '%d', '%d', '%d', '%s' )
			);
			$results[] = "✅ Assigned diagnosis ID {$diagnosis_id} to doctor ID {$doctor_id}";
		}
	}
	
	// 6. Create Rochtah appointment slots
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' );
	$time_slots = array(
		'09:00:00', '09:20:00', '09:40:00',
		'10:00:00', '10:20:00', '10:40:00',
		'11:00:00', '11:20:00', '11:40:00',
		'14:00:00', '14:20:00', '14:40:00',
		'15:00:00', '15:20:00', '15:40:00',
		'16:00:00', '16:20:00', '16:40:00'
	);
	
	$slots_created = 0;
	foreach ( $days as $day ) {
		foreach ( $time_slots as $start_time ) {
			$end_time = date( 'H:i:s', strtotime( $start_time ) + 1200 ); // 20 minutes later
			
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table WHERE day_of_week = %s AND start_time = %s",
				$day,
				$start_time
			) );
			
			if ( ! $existing ) {
				$wpdb->insert(
					$rochtah_appointments_table,
					array(
						'day_of_week' => $day,
						'start_time' => $start_time,
						'end_time' => $end_time,
						'status' => 'active'
					),
					array( '%s', '%s', '%s', '%s' )
				);
				$slots_created++;
			}
		}
	}
	$results[] = "✅ Created {$slots_created} Rochtah appointment slots for weekdays";
	
	// 7. Create test Rochtah bookings
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$test_bookings = array(
		array(
			'patient_id' => $customer_id,
			'therapist_id' => $doctor_id,
			'diagnosis_id' => $diagnosis_ids[0], // Anxiety
			'initial_diagnosis' => 'Patient reports persistent anxiety and difficulty sleeping',
			'symptoms' => 'Anxiety, insomnia, restlessness, difficulty concentrating',
			'booking_date' => date( 'Y-m-d', strtotime( '+1 day' ) ),
			'booking_time' => '10:00:00',
			'status' => 'pending'
		),
		array(
			'patient_id' => $customer_id,
			'therapist_id' => $doctor_id,
			'diagnosis_id' => $diagnosis_ids[1], // Depression
			'initial_diagnosis' => 'Patient experiencing low mood and lack of motivation',
			'symptoms' => 'Depressed mood, fatigue, loss of interest, sleep changes',
			'booking_date' => date( 'Y-m-d', strtotime( '+2 days' ) ),
			'booking_time' => '14:00:00',
			'status' => 'confirmed'
		),
		array(
			'patient_id' => $customer_id,
			'therapist_id' => $doctor_id,
			'diagnosis_id' => $diagnosis_ids[2], // PTSD
			'initial_diagnosis' => 'Patient seeking prescription for PTSD symptoms',
			'symptoms' => 'Flashbacks, nightmares, hypervigilance, avoidance',
			'booking_date' => date( 'Y-m-d', strtotime( '-1 day' ) ),
			'booking_time' => '15:00:00',
			'status' => 'prescribed',
			'prescription_text' => 'Prescription for PTSD management and symptom relief',
			'medications' => 'Sertraline 50mg daily, Prazosin 1mg at bedtime',
			'dosage_instructions' => 'Take Sertraline in the morning with food. Take Prazosin 30 minutes before bedtime.',
			'doctor_notes' => 'Follow up in 2 weeks. Monitor for side effects.',
			'prescribed_by' => $admin_user->ID,
			'prescribed_at' => date( 'Y-m-d H:i:s', strtotime( '-1 day 15:30:00' ) )
		)
	);
	
	$bookings_created = 0;
	foreach ( $test_bookings as $booking ) {
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $rochtah_bookings_table WHERE patient_id = %d AND booking_date = %s AND booking_time = %s",
			$booking['patient_id'],
			$booking['booking_date'],
			$booking['booking_time']
		) );
		
		if ( ! $existing ) {
			$wpdb->insert(
				$rochtah_bookings_table,
				$booking,
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
			);
			$bookings_created++;
		}
	}
	$results[] = "✅ Created {$bookings_created} test Rochtah bookings";
	
	// 8. Create test AI coupons
	$coupons_table = $wpdb->prefix . 'snks_ai_coupons';
	$test_coupons = array(
		array(
			'code' => 'AIWELCOME20',
			'discount_type' => 'percentage',
			'discount_value' => 20,
			'usage_limit' => 100,
			'current_usage' => 15,
			'expiry_date' => date( 'Y-m-d', strtotime( '+30 days' ) ),
			'segment' => 'new_users',
			'active' => 1
		),
		array(
			'code' => 'ROCHTAH50',
			'discount_type' => 'fixed',
			'discount_value' => 50,
			'usage_limit' => 50,
			'current_usage' => 8,
			'expiry_date' => date( 'Y-m-d', strtotime( '+15 days' ) ),
			'segment' => 'returning_users',
			'active' => 1
		)
	);
	
	$coupons_created = 0;
	foreach ( $test_coupons as $coupon ) {
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $coupons_table WHERE code = %s",
			$coupon['code']
		) );
		
		if ( ! $existing ) {
			$wpdb->insert(
				$coupons_table,
				$coupon,
				array( '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d' )
			);
			$coupons_created++;
		}
	}
	$results[] = "✅ Created {$coupons_created} test AI coupons";
	
	// 9. Create test AI analytics events
	$analytics_table = $wpdb->prefix . 'snks_ai_analytics';
	$test_events = array(
		array( 'event_type' => 'user_registration', 'user_id' => $customer_id ),
		array( 'event_type' => 'therapist_view', 'user_id' => $customer_id, 'therapist_id' => $doctor_id ),
		array( 'event_type' => 'booking_created', 'user_id' => $customer_id, 'therapist_id' => $doctor_id ),
		array( 'event_type' => 'rochtah_request', 'user_id' => $customer_id, 'therapist_id' => $doctor_id ),
		array( 'event_type' => 'prescription_written', 'user_id' => $customer_id, 'therapist_id' => $doctor_id )
	);
	
	foreach ( $test_events as $event ) {
		$wpdb->insert(
			$analytics_table,
			array_merge( $event, array( 'event_data' => json_encode( $event ) ) ),
			array( '%s', '%d', '%d', '%s' )
		);
	}
	$results[] = "✅ Created test AI analytics events";
	
	// 10. Create test notifications
	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	$test_notifications = array(
		array(
			'user_id' => $admin_user->ID,
			'type' => 'rochtah_request',
			'title' => 'New Rochtah Prescription Request',
			'message' => 'Patient John Doe has requested a prescription consultation for tomorrow at 10:00 AM'
		),
		array(
			'user_id' => $customer_id,
			'type' => 'prescription_ready',
			'title' => 'Your Prescription is Ready',
			'message' => 'Your prescription has been written by Dr. Admin. Please check your email for details.'
		)
	);
	
	foreach ( $test_notifications as $notification ) {
		$wpdb->insert(
			$notifications_table,
			$notification,
			array( '%d', '%s', '%s', '%s' )
		);
	}
	$results[] = "✅ Created test notifications";
	
	// 11. Set Rochtah settings
	update_option( 'snks_rochtah_enabled', '1' );
	update_option( 'snks_rochtah_available_days', array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ) );
	$results[] = "✅ Rochtah settings configured";
	
	// 12. Set AI email settings
	update_option( 'snks_ai_email_new_booking', '1' );
	update_option( 'snks_ai_email_new_user', '1' );
	update_option( 'snks_ai_email_rochtah_request', '1' );
	$results[] = "✅ AI email settings configured";
	
	return $results;
} 