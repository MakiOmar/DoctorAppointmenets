<?php
/**
 * Bilingual Migration Script
 * 
 * This script adds bilingual support to the AI admin tables
 * Run this once to migrate existing data to support both English and Arabic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function snks_migrate_to_bilingual() {
	global $wpdb;
	
	// Start transaction
	$wpdb->query( 'START TRANSACTION' );
	
	try {
		// 1. Migrate diagnoses table
		$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
		
		// Check if columns already exist
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$diagnoses_table}" );
		$column_names = array_column( $columns, 'Field' );
		
		// Add new columns if they don't exist
		if ( ! in_array( 'name_en', $column_names ) ) {
			$wpdb->query( "ALTER TABLE {$diagnoses_table} ADD COLUMN name_en VARCHAR(255) AFTER name" );
			$wpdb->query( "ALTER TABLE {$diagnoses_table} ADD COLUMN name_ar VARCHAR(255) AFTER name_en" );
			$wpdb->query( "ALTER TABLE {$diagnoses_table} ADD COLUMN description_en TEXT AFTER description" );
			$wpdb->query( "ALTER TABLE {$diagnoses_table} ADD COLUMN description_ar TEXT AFTER description_en" );
			
			// Migrate existing data
			$wpdb->query( "UPDATE {$diagnoses_table} SET name_en = name WHERE name_en IS NULL OR name_en = ''" );
			$wpdb->query( "UPDATE {$diagnoses_table} SET description_en = description WHERE description_en IS NULL OR description_en = ''" );
		}
		
		// 2. Migrate therapist_diagnoses table
		$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
		
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$therapist_diagnoses_table}" );
		$column_names = array_column( $columns, 'Field' );
		
		// Add new columns if they don't exist
		if ( ! in_array( 'suitability_message_en', $column_names ) ) {
			$wpdb->query( "ALTER TABLE {$therapist_diagnoses_table} ADD COLUMN suitability_message_en TEXT AFTER suitability_message" );
			$wpdb->query( "ALTER TABLE {$therapist_diagnoses_table} ADD COLUMN suitability_message_ar TEXT AFTER suitability_message_en" );
			
			// Migrate existing data
			$wpdb->query( "UPDATE {$therapist_diagnoses_table} SET suitability_message_en = suitability_message WHERE suitability_message_en IS NULL OR suitability_message_en = ''" );
		}
		
		// 3. Add bilingual user meta fields
		// This will be handled by the admin interface when users update their profiles
		
		// 4. Test the migration by checking if tables exist
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$diagnoses_table}'" ) ) {
			throw new Exception( 'Diagnoses table does not exist. Please ensure AI tables are created first.' );
		}
		
		// Commit transaction
		$wpdb->query( 'COMMIT' );
		
		return array(
			'success' => true,
			'message' => 'Bilingual migration completed successfully!'
		);
		
	} catch ( Exception $e ) {
		// Rollback transaction
		$wpdb->query( 'ROLLBACK' );
		
		return array(
			'success' => false,
			'message' => 'Migration failed: ' . $e->getMessage()
		);
	}
}

// Migration page is now registered in the main AI admin menu

function snks_bilingual_migration_page() {
	// Load admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	?>
	<div class="wrap">
		<h1>Bilingual Migration</h1>
		
		<div class="card">
			<h2>Database Migration for Bilingual Support</h2>
			<p>This migration will add bilingual support to your AI admin tables, allowing you to enter content in both English and Arabic.</p>
			
			<div class="notice notice-warning">
				<p><strong>Important:</strong> Please backup your database before running this migration!</p>
			</div>
			
			<form method="post">
				<?php wp_nonce_field( 'bilingual_migration' ); ?>
				<input type="hidden" name="action" value="run_bilingual_migration">
				
				<p>
					<button type="submit" class="button button-primary" onclick="return confirm('Are you sure you want to run the bilingual migration? Please ensure you have a database backup.')">
						Run Bilingual Migration
					</button>
				</p>
			</form>
		</div>
		
		<?php
		// Handle migration
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'run_bilingual_migration' ) {
			if ( wp_verify_nonce( $_POST['_wpnonce'], 'bilingual_migration' ) ) {
				$result = snks_migrate_to_bilingual();
				
				if ( $result['success'] ) {
					echo '<div class="notice notice-success"><p>' . esc_html( $result['message'] ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
				}
			}
		}
		?>
		
		<div class="card">
			<h2>Current Database Status</h2>
			<?php
			global $wpdb;
			$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
			$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
			
			$diagnoses_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$diagnoses_table}'" );
			$therapist_diagnoses_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$therapist_diagnoses_table}'" );
			
			if ( $diagnoses_exists ) {
				$diagnoses_columns = $wpdb->get_results( "SHOW COLUMNS FROM {$diagnoses_table}" );
				$diagnoses_column_names = array_column( $diagnoses_columns, 'Field' );
				$has_bilingual_diagnoses = in_array( 'name_en', $diagnoses_column_names );
			} else {
				$has_bilingual_diagnoses = false;
			}
			
			if ( $therapist_diagnoses_exists ) {
				$therapist_columns = $wpdb->get_results( "SHOW COLUMNS FROM {$therapist_diagnoses_table}" );
				$therapist_column_names = array_column( $therapist_columns, 'Field' );
				$has_bilingual_therapist = in_array( 'suitability_message_en', $therapist_column_names );
			} else {
				$has_bilingual_therapist = false;
			}
			?>
			<ul>
				<li><strong>Diagnoses Table:</strong> 
					<?php if ( $diagnoses_exists ): ?>
						✅ Exists
						<?php if ( $has_bilingual_diagnoses ): ?>
							✅ Bilingual columns present
						<?php else: ?>
							❌ Needs migration
						<?php endif; ?>
					<?php else: ?>
						❌ Does not exist
					<?php endif; ?>
				</li>
				<li><strong>Therapist Diagnoses Table:</strong> 
					<?php if ( $therapist_diagnoses_exists ): ?>
						✅ Exists
						<?php if ( $has_bilingual_therapist ): ?>
							✅ Bilingual columns present
						<?php else: ?>
							❌ Needs migration
						<?php endif; ?>
					<?php else: ?>
						❌ Does not exist
					<?php endif; ?>
				</li>
			</ul>
		</div>
		
		<div class="card">
			<h2>What This Migration Does</h2>
			<ul>
				<li><strong>Diagnoses Table:</strong> Adds <code>name_en</code>, <code>name_ar</code>, <code>description_en</code>, <code>description_ar</code> columns</li>
				<li><strong>Therapist Diagnoses Table:</strong> Adds <code>suitability_message_en</code>, <code>suitability_message_ar</code> columns</li>
				<li><strong>User Meta:</strong> New bilingual fields will be created when therapists update their profiles</li>
				<li><strong>Data Preservation:</strong> Existing data will be migrated to English fields</li>
			</ul>
		</div>
		
		<div class="card">
			<h2>After Migration</h2>
			<p>Once the migration is complete, you can:</p>
			<ul>
				<li>Edit diagnoses to add Arabic translations</li>
				<li>Update therapist profiles with bilingual content</li>
				<li>Add Arabic suitability messages for diagnosis assignments</li>
			</ul>
		</div>
	</div>
	<?php
} 