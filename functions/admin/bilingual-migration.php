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
		
		// Commit transaction
		$wpdb->query( 'COMMIT' );
		
		return true;
		
	} catch ( Exception $e ) {
		// Rollback on error
		$wpdb->query( 'ROLLBACK' );
		error_log( 'Bilingual migration error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Check if bilingual migration is needed
 */
function snks_check_bilingual_migration() {
	global $wpdb;
	
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	
	// Check if columns exist
	$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$diagnoses_table}" );
	$column_names = array_column( $columns, 'Field' );
	
	$missing_columns = array();
	
	if ( ! in_array( 'name_en', $column_names ) ) {
		$missing_columns[] = 'name_en';
	}
	if ( ! in_array( 'name_ar', $column_names ) ) {
		$missing_columns[] = 'name_ar';
	}
	if ( ! in_array( 'description_en', $column_names ) ) {
		$missing_columns[] = 'description_en';
	}
	if ( ! in_array( 'description_ar', $column_names ) ) {
		$missing_columns[] = 'description_ar';
	}
	
	return $missing_columns;
}

/**
 * Admin page to run migration
 */
function snks_bilingual_migration_page() {
	global $wpdb;
	
	// Handle migration
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'run_migration' && wp_verify_nonce( $_POST['_wpnonce'], 'run_bilingual_migration' ) ) {
		$result = snks_migrate_to_bilingual();
		
		if ( $result ) {
			echo '<div class="notice notice-success"><p>Bilingual migration completed successfully!</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>Migration failed. Please check the error logs.</p></div>';
		}
	}
	
	// Check current status
	$missing_columns = snks_check_bilingual_migration();
	$needs_migration = ! empty( $missing_columns );
	
	?>
	<div class="wrap">
		<h1>Bilingual Migration</h1>
		
		<div class="card">
			<h2>Database Status</h2>
			
			<?php if ( $needs_migration ) : ?>
				<div class="notice notice-warning">
					<p><strong>Migration Required:</strong> The following columns are missing from the database:</p>
					<ul>
						<?php foreach ( $missing_columns as $column ) : ?>
							<li><code><?php echo esc_html( $column ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				</div>
				
				<form method="post">
					<?php wp_nonce_field( 'run_bilingual_migration' ); ?>
					<input type="hidden" name="action" value="run_migration">
					<p class="description">
						This will add the missing bilingual columns and migrate existing data. 
						Existing English content will be copied to the new English columns.
					</p>
					<?php submit_button( 'Run Migration', 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<div class="notice notice-success">
					<p><strong>✅ All bilingual columns are present!</strong> No migration is needed.</p>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="card">
			<h2>What This Migration Does</h2>
			<ul>
				<li><strong>Adds bilingual columns:</strong> <code>name_en</code>, <code>name_ar</code>, <code>description_en</code>, <code>description_ar</code></li>
				<li><strong>Migrates existing data:</strong> Copies current English content to the new English columns</li>
				<li><strong>Preserves existing data:</strong> No data will be lost during migration</li>
				<li><strong>Updates therapist diagnoses:</strong> Adds bilingual suitability message columns</li>
			</ul>
		</div>
		
		<div class="card">
			<h2>Current Table Structure</h2>
			<?php
			$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$diagnoses_table}" );
			?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Column</th>
						<th>Type</th>
						<th>Null</th>
						<th>Key</th>
						<th>Default</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $columns as $column ) : ?>
						<tr>
							<td><code><?php echo esc_html( $column->Field ); ?></code></td>
							<td><?php echo esc_html( $column->Type ); ?></td>
							<td><?php echo esc_html( $column->Null ); ?></td>
							<td><?php echo esc_html( $column->Key ); ?></td>
							<td><?php echo esc_html( $column->Default ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Add admin menu for migration
 */
function snks_add_bilingual_migration_menu() {
	add_submenu_page(
		'tools.php',
		'Bilingual Migration',
		'Bilingual Migration',
		'manage_options',
		'bilingual-migration',
		'snks_bilingual_migration_page'
	);
}
add_action( 'admin_menu', 'snks_add_bilingual_migration_menu' );

/**
 * Auto-run migration on plugin activation if needed
 */
function snks_auto_migrate_bilingual() {
	$missing_columns = snks_check_bilingual_migration();
	
	if ( ! empty( $missing_columns ) ) {
		$result = snks_migrate_to_bilingual();
		
		if ( $result ) {
			error_log( 'Auto-migration: Bilingual columns added successfully' );
		} else {
			error_log( 'Auto-migration: Failed to add bilingual columns' );
		}
	}
}

// Run auto-migration on plugin load
add_action( 'init', 'snks_auto_migrate_bilingual' ); 