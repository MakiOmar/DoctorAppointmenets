<?php
/**
 * Bulk Diagnosis Assignment
 * 
 * This file provides functionality to bulk assign demo therapists to specific diagnoses
 * with random order points for better distribution in search results.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add bulk diagnosis assignment page to admin menu
 */
function snks_add_bulk_diagnosis_assignment_menu() {
	add_submenu_page(
		'jalsah-ai',
		'Bulk Diagnosis Assignment',
		'Bulk Assignment',
		'manage_options',
		'bulk-diagnosis-assignment',
		'snks_bulk_diagnosis_assignment_page'
	);
}

/**
 * Bulk Diagnosis Assignment Page
 */
function snks_bulk_diagnosis_assignment_page() {
	// Check if user has admin capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to access this page.' );
	}
	
	global $wpdb;
	
	// Handle form submissions
	if ( isset( $_POST['action'] ) ) {
		if ( $_POST['action'] === 'bulk_assign_diagnosis' && wp_verify_nonce( $_POST['_wpnonce'], 'bulk_assign_diagnosis' ) ) {
			$result = snks_bulk_assign_therapists_to_diagnosis( $_POST );
			if ( $result['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
				if ( ! empty( $result['details'] ) ) {
					echo '<div class="notice notice-info"><ul>';
					foreach ( $result['details'] as $detail ) {
						echo '<li>' . esc_html( $detail ) . '</li>';
					}
					echo '</ul></div>';
				}
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		} elseif ( $_POST['action'] === 'clear_diagnosis_assignments' && wp_verify_nonce( $_POST['_wpnonce'], 'clear_diagnosis_assignments' ) ) {
			$result = snks_clear_diagnosis_assignments( $_POST['diagnosis_id'] );
			if ( $result['success'] ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}
	}
	
	// Get available diagnoses
	$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name_en, name" );
	
	// Get demo therapists
	$demo_therapists = get_users( array(
		'meta_key' => 'is_demo_doctor',
		'meta_value' => '1',
		'role' => 'doctor',
		'orderby' => 'display_name'
	) );
	
	// Get all therapists (for comparison)
	$all_therapists = get_users( array(
		'role' => 'doctor',
		'orderby' => 'display_name'
	) );
	
	?>
	<div class="wrap">
		<h1>Bulk Diagnosis Assignment</h1>
		<p class="description">Assign demo therapists to specific diagnoses with random order points for better distribution.</p>
		
		<!-- Bulk Assignment Form -->
		<div class="card">
			<h2>Assign Demo Therapists to Diagnosis</h2>
			<form method="post">
				<?php wp_nonce_field( 'bulk_assign_diagnosis' ); ?>
				<input type="hidden" name="action" value="bulk_assign_diagnosis">
				
				<table class="form-table">
					<tr>
						<th><label for="diagnosis_id">Select Diagnosis</label></th>
						<td>
							<select id="diagnosis_id" name="diagnosis_id" required class="regular-text">
								<option value="">-- Select Diagnosis --</option>
								<?php foreach ( $diagnoses as $diagnosis ) : ?>
									<option value="<?php echo esc_attr( $diagnosis->id ); ?>">
										<?php echo esc_html( $diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name ); ?>
										<?php if ( $diagnosis->name_ar ) : ?>
											(<?php echo esc_html( $diagnosis->name_ar ); ?>)
										<?php endif; ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">Choose the diagnosis to assign therapists to</p>
						</td>
					</tr>
					<tr>
						<th><label for="therapist_count">Number of Therapists</label></th>
						<td>
							<input type="number" id="therapist_count" name="therapist_count" value="5" min="1" max="<?php echo count( $demo_therapists ); ?>" required class="small-text">
							<p class="description">Number of demo therapists to assign (max: <?php echo count( $demo_therapists ); ?>)</p>
						</td>
					</tr>
					<tr>
						<th><label for="min_rating">Minimum Rating</label></th>
						<td>
							<input type="number" id="min_rating" name="min_rating" value="4.0" min="1.0" max="5.0" step="0.1" required class="small-text">
							<p class="description">Minimum rating to assign (1.0 - 5.0)</p>
						</td>
					</tr>
					<tr>
						<th><label for="max_rating">Maximum Rating</label></th>
						<td>
							<input type="number" id="max_rating" name="max_rating" value="5.0" min="1.0" max="5.0" step="0.1" required class="small-text">
							<p class="description">Maximum rating to assign (1.0 - 5.0)</p>
						</td>
					</tr>
					<tr>
						<th><label for="order_range">Order Points Range</label></th>
						<td>
							<input type="number" id="order_range" name="order_range" value="100" min="1" max="1000" required class="small-text">
							<p class="description">Maximum order points to assign (therapists will get random points from 1 to this number)</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Assign Therapists to Diagnosis' ); ?>
			</form>
		</div>
		
		<!-- Clear Assignments Form -->
		<div class="card">
			<h2>Clear Diagnosis Assignments</h2>
			<form method="post">
				<?php wp_nonce_field( 'clear_diagnosis_assignments' ); ?>
				<input type="hidden" name="action" value="clear_diagnosis_assignments">
				
				<table class="form-table">
					<tr>
						<th><label for="clear_diagnosis_id">Select Diagnosis</label></th>
						<td>
							<select id="clear_diagnosis_id" name="diagnosis_id" required class="regular-text">
								<option value="">-- Select Diagnosis --</option>
								<?php foreach ( $diagnoses as $diagnosis ) : ?>
									<option value="<?php echo esc_attr( $diagnosis->id ); ?>">
										<?php echo esc_html( $diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name ); ?>
										<?php if ( $diagnosis->name_ar ) : ?>
											(<?php echo esc_html( $diagnosis->name_ar ); ?>)
										<?php endif; ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">Choose the diagnosis to clear all therapist assignments</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( 'Clear All Assignments', 'secondary' ); ?>
			</form>
		</div>
		
		<!-- Statistics -->
		<div class="card">
			<h2>Current Statistics</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Diagnosis</th>
						<th>Total Therapists</th>
						<th>Demo Therapists</th>
						<th>Average Rating</th>
						<th>Average Order Points</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $diagnoses as $diagnosis ) : ?>
						<?php
						// Get statistics for this diagnosis
						$stats = $wpdb->get_row( $wpdb->prepare(
							"SELECT 
								COUNT(*) as total_therapists,
								COUNT(CASE WHEN u.meta_value = '1' THEN 1 END) as demo_therapists,
								AVG(td.rating) as avg_rating,
								AVG(td.display_order) as avg_order
							FROM {$wpdb->prefix}snks_therapist_diagnoses td
							LEFT JOIN {$wpdb->users} u ON td.therapist_id = u.ID
							LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'is_demo_doctor'
							WHERE td.diagnosis_id = %d",
							$diagnosis->id
						) );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name ); ?></strong>
								<?php if ( $diagnosis->name_ar ) : ?>
									<br><small><?php echo esc_html( $diagnosis->name_ar ); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $stats->total_therapists ? $stats->total_therapists : 0 ); ?></td>
							<td><?php echo esc_html( $stats->demo_therapists ? $stats->demo_therapists : 0 ); ?></td>
							<td><?php echo esc_html( $stats->avg_rating ? number_format( $stats->avg_rating, 1 ) : 'N/A' ); ?></td>
							<td><?php echo esc_html( $stats->avg_order ? number_format( $stats->avg_order, 0 ) : 'N/A' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		
		<!-- Available Demo Therapists -->
		<div class="card">
			<h2>Available Demo Therapists (<?php echo count( $demo_therapists ); ?>)</h2>
			<?php if ( ! empty( $demo_therapists ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Specialty</th>
							<th>Current Diagnoses</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $demo_therapists as $therapist ) : ?>
							<?php
							$therapist_diagnoses = $wpdb->get_results( $wpdb->prepare(
								"SELECT d.name_en, d.name_ar, d.name 
								FROM {$wpdb->prefix}snks_therapist_diagnoses td
								JOIN {$wpdb->prefix}snks_diagnoses d ON td.diagnosis_id = d.id
								WHERE td.therapist_id = %d
								ORDER BY td.display_order ASC",
								$therapist->ID
							) );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $therapist->display_name ); ?></strong>
								</td>
								<td><?php echo esc_html( $therapist->user_email ); ?></td>
								<td><?php echo esc_html( get_user_meta( $therapist->ID, 'doctor_specialty', true ) ? get_user_meta( $therapist->ID, 'doctor_specialty', true ) : 'N/A' ); ?></td>
								<td>
									<?php if ( ! empty( $therapist_diagnoses ) ) : ?>
										<?php foreach ( $therapist_diagnoses as $diagnosis ) : ?>
											<span class="diagnosis-tag">
												<?php echo esc_html( $diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name ); ?>
											</span>
										<?php endforeach; ?>
									<?php else : ?>
										<span class="description">No diagnoses assigned</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="description">No demo therapists found. Please create some demo therapists first.</p>
			<?php endif; ?>
		</div>
	</div>
	
	<style>
	.diagnosis-tag {
		display: inline-block;
		background: #0073aa;
		color: white;
		padding: 2px 8px;
		border-radius: 3px;
		font-size: 11px;
		margin: 1px;
	}
	</style>
	<?php
}

/**
 * Bulk assign therapists to a specific diagnosis
 */
function snks_bulk_assign_therapists_to_diagnosis( $data ) {
	global $wpdb;
	
	// Validate required fields
	$required_fields = array( 'diagnosis_id', 'therapist_count', 'min_rating', 'max_rating', 'order_range' );
	foreach ( $required_fields as $field ) {
		if ( empty( $data[ $field ] ) ) {
			return array( 'success' => false, 'message' => "Missing required field: {$field}" );
		}
	}
	
	$diagnosis_id = intval( $data['diagnosis_id'] );
	$therapist_count = intval( $data['therapist_count'] );
	$min_rating = floatval( $data['min_rating'] );
	$max_rating = floatval( $data['max_rating'] );
	$order_range = intval( $data['order_range'] );
	
	// Validate ranges
	if ( $min_rating > $max_rating ) {
		return array( 'success' => false, 'message' => 'Minimum rating cannot be greater than maximum rating' );
	}
	
	if ( $min_rating < 1.0 || $max_rating > 5.0 ) {
		return array( 'success' => false, 'message' => 'Rating must be between 1.0 and 5.0' );
	}
	
	// Get diagnosis details
	$diagnosis = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_diagnoses WHERE id = %d",
		$diagnosis_id
	) );
	
	if ( ! $diagnosis ) {
		return array( 'success' => false, 'message' => 'Diagnosis not found' );
	}
	
	// Get available demo therapists
	$demo_therapists = get_users( array(
		'meta_key' => 'is_demo_doctor',
		'meta_value' => '1',
		'role' => 'doctor',
		'orderby' => 'RAND()',
		'number' => $therapist_count
	) );
	
	if ( empty( $demo_therapists ) ) {
		return array( 'success' => false, 'message' => 'No demo therapists found' );
	}
	
	// Clear existing assignments for this diagnosis
	$wpdb->delete(
		$wpdb->prefix . 'snks_therapist_diagnoses',
		array( 'diagnosis_id' => $diagnosis_id ),
		array( '%d' )
	);
	
	$success_count = 0;
	$error_count = 0;
	$results = array();
	
	// Generate random order points (ensure they're unique)
	$order_points = range( 1, $order_range );
	shuffle( $order_points );
	
	// Suitability messages in both languages
	$suitability_messages_en = array(
		'Specializes in evidence-based treatment for this condition.',
		'Experienced in providing compassionate care for this diagnosis.',
		'Offers personalized therapy approaches for this mental health concern.',
		'Skilled in helping patients manage and overcome this condition.',
		'Provides comprehensive treatment plans for this diagnosis.',
		'Expert in therapeutic interventions for this mental health issue.',
		'Dedicated to supporting patients through their recovery journey.',
		'Specialized training in treating this specific condition.',
		'Committed to evidence-based practice for optimal outcomes.',
		'Experienced in both individual and group therapy for this diagnosis.'
	);
	
	$suitability_messages_ar = array(
		'متخصص في العلاج القائم على الأدلة لهذه الحالة.',
		'خبرة في تقديم رعاية متعاطفة لهذا التشخيص.',
		'يقدم نهج علاجي مخصص لهذا الاهتمام بالصحة النفسية.',
		'ماهر في مساعدة المرضى على إدارة والتغلب على هذه الحالة.',
		'يوفر خطط علاج شاملة لهذا التشخيص.',
		'خبير في التدخلات العلاجية لهذه المشكلة النفسية.',
		'ملتزم بدعم المرضى في رحلة التعافي.',
		'تدريب متخصص في علاج هذه الحالة المحددة.',
		'ملتزم بالممارسة القائمة على الأدلة للحصول على أفضل النتائج.',
		'خبرة في العلاج الفردي والجماعي لهذا التشخيص.'
	);
	
	foreach ( $demo_therapists as $index => $therapist ) {
		// Generate random rating within the specified range
		$rating = round( $min_rating + ( mt_rand() / mt_getrandmax() ) * ( $max_rating - $min_rating ), 1 );
		
		// Get random order point
		$order_point = $order_points[ $index % count( $order_points ) ];
		
		// Get random suitability message
		$message_index = array_rand( $suitability_messages_en );
		$message_en = $suitability_messages_en[ $message_index ];
		$message_ar = $suitability_messages_ar[ $message_index ];
		
		// Insert assignment
		$result = $wpdb->insert(
			$wpdb->prefix . 'snks_therapist_diagnoses',
			array(
				'therapist_id' => $therapist->ID,
				'diagnosis_id' => $diagnosis_id,
				'rating' => $rating,
				'suitability_message_en' => $message_en,
				'suitability_message_ar' => $message_ar,
				'display_order' => $order_point
			),
			array( '%d', '%d', '%f', '%s', '%s', '%d' )
		);
		
		if ( $result !== false ) {
			$success_count++;
			$results['details'][] = "✅ Assigned: {$therapist->display_name} (Rating: {$rating}, Order: {$order_point})";
		} else {
			$error_count++;
			$results['details'][] = "❌ Failed: {$therapist->display_name}";
		}
	}
	
	if ( $success_count > 0 ) {
		$results['success'] = true;
		$results['message'] = "Successfully assigned {$success_count} therapists to '" . ($diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name) . "'. {$error_count} errors occurred.";
	} else {
		$results['success'] = false;
		$results['message'] = "Failed to assign any therapists. {$error_count} errors occurred.";
	}
	
	return $results;
}

/**
 * Clear all therapist assignments for a specific diagnosis
 */
function snks_clear_diagnosis_assignments( $diagnosis_id ) {
	global $wpdb;
	
	$diagnosis_id = intval( $diagnosis_id );
	
	// Get diagnosis details
	$diagnosis = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_diagnoses WHERE id = %d",
		$diagnosis_id
	) );
	
	if ( ! $diagnosis ) {
		return array( 'success' => false, 'message' => 'Diagnosis not found' );
	}
	
	// Count existing assignments
	$existing_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}snks_therapist_diagnoses WHERE diagnosis_id = %d",
		$diagnosis_id
	) );
	
	// Delete all assignments for this diagnosis
	$deleted = $wpdb->delete(
		$wpdb->prefix . 'snks_therapist_diagnoses',
		array( 'diagnosis_id' => $diagnosis_id ),
		array( '%d' )
	);
	
	if ( $deleted !== false ) {
		return array( 
			'success' => true, 
			'message' => "Successfully cleared {$deleted} therapist assignments for '" . ($diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name) . "'." 
		);
	} else {
		return array( 
			'success' => false, 
			'message' => "Failed to clear assignments for '" . ($diagnosis->name_en ? $diagnosis->name_en : $diagnosis->name) . "'." 
		);
	}
}
