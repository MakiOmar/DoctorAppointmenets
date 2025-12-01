<?php
/**
 * AI Settings Export/Import
 * 
 * Allows exporting and importing all Jalsah AI settings
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Menu registration moved to ai-admin-enhanced.php to ensure proper integration
 */

/**
 * Export all AI settings
 */
function snks_export_ai_settings() {
	global $wpdb;
	
	// Verify nonce
	if ( ! isset( $_POST['snks_export_nonce'] ) || ! wp_verify_nonce( $_POST['snks_export_nonce'], 'snks_export_ai_settings' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	
	$export_data = array(
		'version' => '1.0',
		'export_date' => current_time( 'mysql' ),
		'site_url' => get_site_url(),
		'options' => array(),
		'tables' => array(),
	);
	
	// Get all AI-related options - expanded to include all plugin settings
	$ai_options = $wpdb->get_results(
		"SELECT option_name, option_value FROM {$wpdb->options} 
		WHERE option_name LIKE 'snks_ai_%' 
		OR option_name LIKE 'snks_bilingual_%' 
		OR option_name LIKE 'snks_default_%'
		OR option_name LIKE 'snks_therapist_%'
		OR option_name LIKE 'snks_whatsapp_%'
		OR option_name LIKE 'snks_template_%'
		ORDER BY option_name"
	);
	
	foreach ( $ai_options as $option ) {
		$export_data['options'][ $option->option_name ] = maybe_unserialize( $option->option_value );
	}
	
	// Get global profit settings
	$global_profit = get_option( 'snks_ai_profit_global_settings', array() );
	if ( ! empty( $global_profit ) ) {
		$export_data['options']['snks_ai_profit_global_settings'] = $global_profit;
	}
	
	// Export therapist profit settings from database table
	$profit_settings_table = $wpdb->prefix . 'snks_ai_profit_settings';
	$profit_settings = $wpdb->get_results(
		"SELECT * FROM {$profit_settings_table}",
		ARRAY_A
	);
	
	if ( ! empty( $profit_settings ) ) {
		$export_data['tables']['snks_ai_profit_settings'] = $profit_settings;
	}
	
	// Export therapist applications from database table
	$applications_table = $wpdb->prefix . 'therapist_applications';
	$applications = $wpdb->get_results(
		"SELECT * FROM {$applications_table}",
		ARRAY_A
	);
	
	if ( ! empty( $applications ) ) {
		$export_data['tables']['therapist_applications'] = $applications;
	}
	
	// Set headers for download
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="jalsah-ai-settings-' . date( 'Y-m-d-His' ) . '.json"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );
	
	// Output JSON
	echo wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'admin_post_snks_export_ai_settings', 'snks_export_ai_settings' );

/**
 * Import AI settings
 */
function snks_import_ai_settings() {
	global $wpdb;
	
	// Verify nonce
	if ( ! isset( $_POST['snks_import_nonce'] ) || ! wp_verify_nonce( $_POST['snks_import_nonce'], 'snks_import_ai_settings' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	
	// Check if file was uploaded
	if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
		wp_redirect( add_query_arg( array( 'page' => 'jalsah-ai-export-import', 'error' => 'upload_failed' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	
	// Read file
	$file_content = file_get_contents( $_FILES['import_file']['tmp_name'] );
	$import_data = json_decode( $file_content, true );
	
	if ( ! $import_data || ! isset( $import_data['options'] ) ) {
		wp_redirect( add_query_arg( array( 'page' => 'jalsah-ai-export-import', 'error' => 'invalid_file' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	
	$imported_count = 0;
	$errors = array();
	
		// Import options
	if ( isset( $import_data['options'] ) && is_array( $import_data['options'] ) ) {
		foreach ( $import_data['options'] as $option_name => $option_value ) {
			// Only import AI-related options for safety (expanded to include all plugin settings)
			if ( strpos( $option_name, 'snks_ai_' ) === 0 || 
				 strpos( $option_name, 'snks_bilingual_' ) === 0 || 
				 strpos( $option_name, 'snks_default_' ) === 0 ||
				 strpos( $option_name, 'snks_therapist_' ) === 0 ||
				 strpos( $option_name, 'snks_whatsapp_' ) === 0 ||
				 strpos( $option_name, 'snks_template_' ) === 0 ) {
				
				$result = update_option( $option_name, $option_value );
				if ( $result !== false ) {
					$imported_count++;
				} else {
					$errors[] = sprintf( 'Failed to import option: %s', $option_name );
				}
			}
		}
	}
	
	// Import table data (therapist profit settings)
	if ( isset( $import_data['tables']['snks_ai_profit_settings'] ) && is_array( $import_data['tables']['snks_ai_profit_settings'] ) ) {
		$profit_settings_table = $wpdb->prefix . 'snks_ai_profit_settings';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM information_schema.tables 
			WHERE table_schema = %s AND table_name = %s",
			$wpdb->dbname,
			$profit_settings_table
		) );
		
		if ( $table_exists ) {
			// Clear existing data if requested
			if ( isset( $_POST['clear_existing_profit_settings'] ) && $_POST['clear_existing_profit_settings'] === '1' ) {
				$wpdb->query( "TRUNCATE TABLE {$profit_settings_table}" );
			}
			
			// Import profit settings
			foreach ( $import_data['tables']['snks_ai_profit_settings'] as $setting ) {
				// Remove id to allow auto-increment
				unset( $setting['id'] );
				
				// Check if therapist exists
				$therapist_exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT ID FROM {$wpdb->users} WHERE ID = %d",
					$setting['therapist_id']
				) );
				
				if ( $therapist_exists ) {
					// Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing records
					$result = $wpdb->replace(
						$profit_settings_table,
						$setting,
						array(
							'%d', // therapist_id
							'%f', // first_session_percentage
							'%f', // subsequent_session_percentage
							'%d', // is_active
						)
					);
					
					if ( $result === false ) {
						$errors[] = sprintf( 'Failed to import profit setting for therapist ID: %d', $setting['therapist_id'] );
					}
				} else {
					$errors[] = sprintf( 'Therapist ID %d does not exist, skipping profit setting', $setting['therapist_id'] );
				}
			}
		}
	}
	
	// Import table data (therapist applications)
	if ( isset( $import_data['tables']['therapist_applications'] ) && is_array( $import_data['tables']['therapist_applications'] ) ) {
		$applications_table = $wpdb->prefix . 'therapist_applications';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM information_schema.tables 
			WHERE table_schema = %s AND table_name = %s",
			$wpdb->dbname,
			$applications_table
		) );
		
		if ( $table_exists ) {
			// Clear existing data if requested
			if ( isset( $_POST['clear_existing_applications'] ) && $_POST['clear_existing_applications'] === '1' ) {
				$wpdb->query( "TRUNCATE TABLE {$applications_table}" );
			}
			
			// Import applications
			foreach ( $import_data['tables']['therapist_applications'] as $application ) {
				// Remove timestamps to allow auto-generation
				unset( $application['created_at'] );
				unset( $application['updated_at'] );
				
				// Check if application already exists by email or phone
				$existing = null;
				if ( ! empty( $application['email'] ) ) {
					$existing = $wpdb->get_row( $wpdb->prepare(
						"SELECT id FROM {$applications_table} WHERE email = %s",
						$application['email']
					) );
				} elseif ( ! empty( $application['phone'] ) ) {
					$existing = $wpdb->get_row( $wpdb->prepare(
						"SELECT id FROM {$applications_table} WHERE phone = %s",
						$application['phone']
					) );
				}
				
				if ( $existing ) {
					// Update existing application - remove id from data array
					$app_id = $existing->id;
					unset( $application['id'] );
					$result = $wpdb->update( $applications_table, $application, array( 'id' => $app_id ) );
				} else {
					// Insert new application - remove id to allow auto-increment
					unset( $application['id'] );
					$result = $wpdb->insert( $applications_table, $application );
				}
				
				if ( $result === false ) {
					$app_identifier = ! empty( $application['email'] ) ? $application['email'] : ( ! empty( $application['phone'] ) ? $application['phone'] : 'Unknown' );
					$errors[] = sprintf( 'Failed to import application: %s', $app_identifier );
				}
			}
		}
	}
	
	// Redirect with success/error message
	$redirect_args = array(
		'page' => 'jalsah-ai-export-import',
		'imported' => $imported_count,
	);
	
	if ( ! empty( $errors ) ) {
		$redirect_args['errors'] = count( $errors );
		$redirect_args['error_details'] = urlencode( implode( '; ', array_slice( $errors, 0, 5 ) ) );
	}
	
	wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_snks_import_ai_settings', 'snks_import_ai_settings' );

/**
 * Export/Import settings page
 */
function snks_ai_settings_export_import_page() {
	// Load AI admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle messages
	$error = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
	$imported = isset( $_GET['imported'] ) ? intval( $_GET['imported'] ) : 0;
	$errors_count = isset( $_GET['errors'] ) ? intval( $_GET['errors'] ) : 0;
	$error_details = isset( $_GET['error_details'] ) ? urldecode( sanitize_text_field( $_GET['error_details'] ) ) : '';
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Export/Import Jalsah AI Settings', 'anony-turn' ); ?></h1>
		
		<?php if ( $error === 'upload_failed' ) : ?>
			<div class="notice notice-error">
				<p><?php echo esc_html__( 'File upload failed. Please try again.', 'anony-turn' ); ?></p>
			</div>
		<?php endif; ?>
		
		<?php if ( $error === 'invalid_file' ) : ?>
			<div class="notice notice-error">
				<p><?php echo esc_html__( 'Invalid file format. Please upload a valid JSON export file.', 'anony-turn' ); ?></p>
			</div>
		<?php endif; ?>
		
		<?php if ( $imported > 0 ) : ?>
			<div class="notice notice-success">
				<p>
					<?php 
					printf(
						esc_html__( 'Successfully imported %d settings.', 'anony-turn' ),
						$imported
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		
		<?php if ( $errors_count > 0 ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php 
					printf(
						esc_html__( 'Import completed with %d errors.', 'anony-turn' ),
						$errors_count
					);
					?>
				</p>
				<?php if ( $error_details ) : ?>
					<p><strong><?php echo esc_html__( 'Error details:', 'anony-turn' ); ?></strong></p>
					<ul>
						<?php foreach ( explode( '; ', $error_details ) as $detail ) : ?>
							<li><?php echo esc_html( $detail ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2><?php echo esc_html__( 'Export Settings', 'anony-turn' ); ?></h2>
			<p><?php echo esc_html__( 'Export all Jalsah AI settings to a JSON file. This includes:', 'anony-turn' ); ?></p>
			<ul>
				<li><?php echo esc_html__( 'ChatGPT integration settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Bilingual settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Ratings and diagnosis settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Profit settings (global and therapist-specific)', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Rochtah integration settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'OTP Verification Settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'WhatsApp API Settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Therapist Registration Settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Notification Template Settings', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'Therapist Applications', 'anony-turn' ); ?></li>
				<li><?php echo esc_html__( 'All other AI-related options', 'anony-turn' ); ?></li>
			</ul>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'snks_export_ai_settings', 'snks_export_nonce' ); ?>
				<input type="hidden" name="action" value="snks_export_ai_settings">
				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Export Settings', 'anony-turn' ); ?>">
				</p>
			</form>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2><?php echo esc_html__( 'Import Settings', 'anony-turn' ); ?></h2>
			<p><?php echo esc_html__( 'Import settings from a previously exported JSON file. This will overwrite existing settings.', 'anony-turn' ); ?></p>
			
			<div class="notice notice-warning" style="margin: 15px 0;">
				<p><strong><?php echo esc_html__( 'Warning:', 'anony-turn' ); ?></strong> <?php echo esc_html__( 'Importing settings will overwrite your current configuration. Make sure to export your current settings first as a backup.', 'anony-turn' ); ?></p>
			</div>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'snks_import_ai_settings', 'snks_import_nonce' ); ?>
				<input type="hidden" name="action" value="snks_import_ai_settings">
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="import_file"><?php echo esc_html__( 'Select JSON File', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="file" name="import_file" id="import_file" accept=".json" required>
							<p class="description"><?php echo esc_html__( 'Select the JSON file exported from another installation.', 'anony-turn' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="clear_existing_profit_settings"><?php echo esc_html__( 'Profit Settings', 'anony-turn' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="clear_existing_profit_settings" id="clear_existing_profit_settings" value="1">
								<?php echo esc_html__( 'Clear existing therapist profit settings before importing', 'anony-turn' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'If unchecked, imported profit settings will be merged with existing ones (duplicates will be updated).', 'anony-turn' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="clear_existing_applications"><?php echo esc_html__( 'Therapist Applications', 'anony-turn' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="clear_existing_applications" id="clear_existing_applications" value="1">
								<?php echo esc_html__( 'Clear existing therapist applications before importing', 'anony-turn' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'If unchecked, imported applications will be merged with existing ones. Applications with matching email or phone will be updated instead of creating duplicates.', 'anony-turn' ); ?></p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Import Settings', 'anony-turn' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to import these settings? This will overwrite your current configuration.', 'anony-turn' ) ); ?>');">
				</p>
			</form>
		</div>
	</div>
	<?php
}

