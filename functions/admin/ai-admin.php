<?php
/**
 * AI Admin Interface
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add AI admin menu - DISABLED (Using Enhanced Version)
 */
/*
function snks_add_ai_admin_menu() {
	add_menu_page(
		'Jalsah AI Management',
		'Jalsah AI',
		'manage_options',
		'snks-ai-management',
		'snks_ai_admin_page',
		'dashicons-brain',
		30
	);
	
	add_submenu_page(
		'snks-ai-management',
		'Diagnoses',
		'Diagnoses',
		'manage_options',
		'snks-ai-diagnoses',
		'snks_ai_diagnoses_page'
	);
	
	add_submenu_page(
		'snks-ai-management',
		'Therapist AI Settings',
		'Therapist Settings',
		'manage_options',
		'snks-ai-therapists',
		'snks_ai_therapists_page'
	);
}
add_action( 'admin_menu', 'snks_add_ai_admin_menu' );
*/

/**
 * AI Admin Page
 */
function snks_ai_admin_page() {
	?>
	<div class="wrap">
		<h1>Jalsah AI Management</h1>
		<div class="card">
			<h2>AI Platform Overview</h2>
			<p>Manage the Jalsah AI platform integration. This system allows therapists to be featured on the AI platform with specific diagnoses and ratings.</p>
			
			<h3>Quick Stats</h3>
			<?php
			$ai_therapists = get_users( array(
				'role' => 'doctor',
				'meta_query' => array(
					array(
						'key' => 'show_on_ai_site',
						'value' => '1',
						'compare' => '='
					)
				),
				'count_total' => true
			) );
			
			global $wpdb;
			$diagnoses_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snks_diagnoses" );
			$assignments_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snks_therapist_diagnoses" );
			?>
			<ul>
				<li><strong>AI Therapists:</strong> <?php echo count( $ai_therapists ); ?></li>
				<li><strong>Diagnoses:</strong> <?php echo $diagnoses_count; ?></li>
				<li><strong>Therapist-Diagnosis Assignments:</strong> <?php echo $assignments_count; ?></li>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * AI Diagnoses Page
 */
function snks_ai_diagnoses_page() {
	global $wpdb;
	
	// Handle form submissions
	if ( isset( $_POST['action'] ) ) {
		if ( $_POST['action'] === 'add_diagnosis' && wp_verify_nonce( $_POST['_wpnonce'], 'add_diagnosis' ) ) {
			$name = sanitize_text_field( $_POST['diagnosis_name'] );
			$description = sanitize_textarea_field( $_POST['diagnosis_description'] );
			
			$wpdb->insert(
				$wpdb->prefix . 'snks_diagnoses',
				array(
					'name' => $name,
					'description' => $description,
				),
				array( '%s', '%s' )
			);
			
			echo '<div class="notice notice-success"><p>Diagnosis added successfully!</p></div>';
		}
		
		if ( $_POST['action'] === 'delete_diagnosis' && wp_verify_nonce( $_POST['_wpnonce'], 'delete_diagnosis' ) ) {
			$diagnosis_id = intval( $_POST['diagnosis_id'] );
			$wpdb->delete( $wpdb->prefix . 'snks_diagnoses', array( 'id' => $diagnosis_id ), array( '%d' ) );
			echo '<div class="notice notice-success"><p>Diagnosis deleted successfully!</p></div>';
		}
		
		// Handle frontend order recalculation
		if ( $_POST['action'] === 'recalculate_frontend_orders' && wp_verify_nonce( $_POST['_wpnonce'], 'recalculate_frontend_orders' ) ) {
			$result = snks_calculate_all_frontend_orders();
			if ( $result ) {
				echo '<div class="notice notice-success"><p>Frontend orders recalculated successfully!</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>Error recalculating frontend orders.</p></div>';
			}
		}
	}
	
	// Get diagnoses
	$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
	?>
	<div class="wrap">
		<h1>AI Diagnoses Management</h1>
		
		<!-- Debug Information -->
		<?php if (function_exists('snks_calculate_all_frontend_orders')): ?>
			<div class="notice notice-info">
				<p>✅ Frontend order calculation function is available.</p>
			</div>
		<?php else: ?>
			<div class="notice notice-error">
				<p>❌ Frontend order calculation function is NOT available.</p>
			</div>
		<?php endif; ?>
		
		<?php
		// Check if frontend_order column exists
		$table_name = $wpdb->prefix . 'snks_therapist_diagnoses';
		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
		$column_names = array_column($columns, 'Field');
		$has_frontend_order = in_array('frontend_order', $column_names);
		?>
		
		<?php if ($has_frontend_order): ?>
			<div class="notice notice-info">
				<p>✅ Frontend order column exists in database.</p>
			</div>
		<?php else: ?>
			<div class="notice notice-error">
				<p>❌ Frontend order column does NOT exist in database.</p>
			</div>
		<?php endif; ?>
		
		<!-- Recalculate Frontend Orders Button -->
		<div class="card" style="max-width: 600px; margin-bottom: 20px;">
			<h2>Frontend Order Management</h2>
			<p>Recalculate the frontend order positions for all therapists based on their display_order values.</p>
			<form method="post" style="margin-top: 10px;">
				<?php wp_nonce_field( 'recalculate_frontend_orders' ); ?>
				<input type="hidden" name="action" value="recalculate_frontend_orders">
				<button type="submit" class="button button-primary">Recalculate All Frontend Orders</button>
			</form>
		</div>
		
		<div class="card">
			<h2>Add New Diagnosis</h2>
			<form method="post">
				<?php wp_nonce_field( 'add_diagnosis' ); ?>
				<input type="hidden" name="action" value="add_diagnosis">
				
				<table class="form-table">
					<tr>
						<th><label for="diagnosis_name">Diagnosis Name</label></th>
						<td><input type="text" id="diagnosis_name" name="diagnosis_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="diagnosis_description">Description</label></th>
						<td><textarea id="diagnosis_description" name="diagnosis_description" rows="3" class="large-text"></textarea></td>
					</tr>
				</table>
				
				<?php submit_button( 'Add Diagnosis' ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2>Existing Diagnoses</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Name</th>
						<th>Description</th>
						<th>Therapists</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $diagnoses as $diagnosis ) : ?>
						<?php
						$therapists_count = $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->prefix}snks_therapist_diagnoses WHERE diagnosis_id = %d",
							$diagnosis->id
						) );
						?>
						<tr>
							<td><?php echo esc_html( $diagnosis->name ); ?></td>
							<td><?php echo esc_html( $diagnosis->description ); ?></td>
							<td><?php echo $therapists_count; ?> therapists</td>
							<td>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'delete_diagnosis' ); ?>
									<input type="hidden" name="action" value="delete_diagnosis">
									<input type="hidden" name="diagnosis_id" value="<?php echo $diagnosis->id; ?>">
									<button type="submit" class="button button-small" onclick="return confirm('Are you sure?')">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

/**
 * AI Therapists Page
 */
function snks_ai_therapists_page() {
	global $wpdb;
	
	// Handle form submissions
	if ( isset( $_POST['action'] ) ) {
		if ( $_POST['action'] === 'update_therapist_ai' && wp_verify_nonce( $_POST['_wpnonce'], 'update_therapist_ai' ) ) {
			$therapist_id = intval( $_POST['therapist_id'] );
			$show_on_ai = isset( $_POST['show_on_ai_site'] ) ? '1' : '0';
			$ai_bio = sanitize_textarea_field( $_POST['ai_bio'] );
			

			
			update_user_meta( $therapist_id, 'show_on_ai_site', $show_on_ai );
			update_user_meta( $therapist_id, 'ai_bio', $ai_bio );
			
			// Handle diagnosis assignments
			$diagnoses = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}snks_diagnoses" );
			$assigned_count = 0;
			
			foreach ( $diagnoses as $diagnosis ) {
				$rating_key = 'rating_' . $diagnosis->id;
				$message_key = 'message_' . $diagnosis->id;
				$assigned_key = 'assigned_' . $diagnosis->id;
				
				if ( isset( $_POST[ $assigned_key ] ) ) {
					$rating = isset( $_POST[ $rating_key ] ) ? floatval( $_POST[ $rating_key ] ) : 0;
					$message = isset( $_POST[ $message_key ] ) ? sanitize_textarea_field( $_POST[ $message_key ] ) : '';
					
					// Validate rating
					if ( $rating < 0 ) $rating = 0;
					if ( $rating > 5 ) $rating = 5;
					

					
					// Insert or update assignment
					$result = $wpdb->replace(
						$wpdb->prefix . 'snks_therapist_diagnoses',
						array(
							'therapist_id' => $therapist_id,
							'diagnosis_id' => $diagnosis->id,
							'rating' => $rating,
							'suitability_message' => $message,
						),
						array( '%d', '%d', '%f', '%s' )
					);
					
					if ( $result !== false ) {
						$assigned_count++;
					}
				} else {
					// Remove assignment
					$delete_result = $wpdb->delete(
						$wpdb->prefix . 'snks_therapist_diagnoses',
						array(
							'therapist_id' => $therapist_id,
							'diagnosis_id' => $diagnosis->id,
						),
						array( '%d', '%d' )
					);
					

				}
			}
			
			echo '<div class="notice notice-success"><p>Therapist AI settings updated successfully! (' . $assigned_count . ' diagnosis assignments saved)</p></div>';
		}
	}
	
	// Get therapists
	$therapists = get_users( array( 'role' => 'doctor' ) );
	$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
	?>
	<div class="wrap">
		<h1>AI Therapist Settings</h1>
		
		<div class="card">
			<h2>Select Therapist</h2>
			<select id="therapist-selector" onchange="loadTherapistSettings(this.value)">
				<option value="">Select a therapist...</option>
				<?php foreach ( $therapists as $therapist ) : ?>
					<option value="<?php echo $therapist->ID; ?>">
						<?php echo esc_html( get_user_meta( $therapist->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $therapist->ID, 'billing_last_name', true ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div id="therapist-settings" style="display:none;">
			<form method="post">
				<?php wp_nonce_field( 'update_therapist_ai' ); ?>
				<input type="hidden" name="action" value="update_therapist_ai">
				<input type="hidden" name="therapist_id" id="therapist_id">
				
				<div class="card">
					<h2>AI Platform Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="show_on_ai_site">Show on AI Site</label></th>
							<td><input type="checkbox" id="show_on_ai_site" name="show_on_ai_site" value="1"></td>
						</tr>
						<tr>
							<th><label for="ai_bio">AI Bio</label></th>
							<td><textarea id="ai_bio" name="ai_bio" rows="4" class="large-text"></textarea></td>
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
								<th>Rating (0-5)</th>
								<th>Suitability Message</th>
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
										<input type="number" name="rating_<?php echo $diagnosis->id; ?>" min="0" max="5" step="0.1" class="small-text" value="0" id="rating_<?php echo $diagnosis->id; ?>">
									</td>
									<td>
										<textarea name="message_<?php echo $diagnosis->id; ?>" rows="2" class="large-text" id="message_<?php echo $diagnosis->id; ?>"></textarea>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				
				<?php submit_button( 'Update AI Settings' ); ?>
			</form>
		</div>
	</div>
	
	<script>
	function loadTherapistSettings(therapistId) {
		if (!therapistId) {
			document.getElementById('therapist-settings').style.display = 'none';
			return;
		}
		
		// Show settings form
		document.getElementById('therapist-settings').style.display = 'block';
		document.getElementById('therapist_id').value = therapistId;
		
		// Clear all form fields first
		clearFormFields();
		
		// Load therapist data via AJAX
		jQuery.post(ajaxurl, {
			action: 'load_therapist_ai_settings',
			therapist_id: therapistId,
			nonce: '<?php echo wp_create_nonce( "load_therapist_ai_settings" ); ?>'
		}, function(response) {
			if (response.success) {
				var data = response.data;
				
				// Set basic fields
				document.getElementById('show_on_ai_site').checked = data.show_on_ai_site === '1';
				document.getElementById('ai_bio').value = data.ai_bio || '';
				
				// Set diagnosis assignments
				if (data.diagnoses && data.diagnoses.length > 0) {
					data.diagnoses.forEach(function(diagnosis) {
						var assignedCheckbox = document.getElementById('assigned_' + diagnosis.diagnosis_id);
						var ratingInput = document.getElementById('rating_' + diagnosis.diagnosis_id);
						var messageTextarea = document.getElementById('message_' + diagnosis.diagnosis_id);
						
						if (assignedCheckbox) {
							assignedCheckbox.checked = true;
						}
						if (ratingInput) {
							ratingInput.value = diagnosis.rating || 0;
						}
						if (messageTextarea) {
							messageTextarea.value = diagnosis.suitability_message || '';
						}
					});
				}
			}
		});
	}
	
	function clearFormFields() {
		// Clear all checkboxes
		var checkboxes = document.querySelectorAll('input[type="checkbox"][name^="assigned_"]');
		checkboxes.forEach(function(checkbox) {
			checkbox.checked = false;
		});
		
		// Clear all rating inputs
		var ratingInputs = document.querySelectorAll('input[name^="rating_"]');
		ratingInputs.forEach(function(input) {
			input.value = '0';
		});
		
		// Clear all message textareas
		var messageTextareas = document.querySelectorAll('textarea[name^="message_"]');
		messageTextareas.forEach(function(textarea) {
			textarea.value = '';
		});
		
		// Clear basic fields
		document.getElementById('show_on_ai_site').checked = false;
		document.getElementById('ai_bio').value = '';
	}
	</script>
	<?php
}

/**
 * AJAX handler for loading therapist AI settings
 */
function snks_load_therapist_ai_settings() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'load_therapist_ai_settings' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}
	
	$therapist_id = intval( $_POST['therapist_id'] );
	

	
	$data = array(
		'show_on_ai_site' => get_user_meta( $therapist_id, 'show_on_ai_site', true ),
		'ai_bio' => get_user_meta( $therapist_id, 'ai_bio', true ),
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
add_action( 'wp_ajax_load_therapist_ai_settings', 'snks_load_therapist_ai_settings' ); 

/**
 * Automatically recalculate frontend_order when therapist diagnosis assignment is updated
 */
function snks_auto_recalculate_frontend_order($therapist_id, $diagnosis_id) {
	// Recalculate frontend_order for this specific diagnosis
	snks_calculate_frontend_order_for_diagnosis($diagnosis_id);
}

/**
 * Hook to recalculate frontend_order when therapist diagnosis assignment is updated
 */
add_action('snks_therapist_diagnosis_updated', 'snks_auto_recalculate_frontend_order', 10, 2);

/**
 * Hook to recalculate frontend_order when therapist diagnosis assignment is added
 */
add_action('snks_therapist_diagnosis_added', 'snks_auto_recalculate_frontend_order', 10, 2);

/**
 * Hook to recalculate frontend_order when therapist diagnosis assignment is deleted
 */
add_action('snks_therapist_diagnosis_deleted', 'snks_auto_recalculate_frontend_order', 10, 2); 

/**
 * Initialize frontend_order for existing data if not set
 */
function snks_initialize_frontend_orders() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_therapist_diagnoses';
	
	// Check if frontend_order column exists and has data
	$has_frontend_order = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE frontend_order > 0");
	
	if ($has_frontend_order == 0) {
		// Initialize frontend_order for all existing data
		snks_calculate_all_frontend_orders();
		return true;
	}
	
	return false;
}

/**
 * Hook to initialize frontend_order on admin page load
 */
add_action('admin_init', 'snks_initialize_frontend_orders'); 