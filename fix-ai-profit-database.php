<?php
/**
 * Fix AI Profit Database Schema
 * 
 * This script manually runs the database schema updates for the AI profit transfer system
 * 
 * @package Shrinks
 */

// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Ensure we're in admin context
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied' );
}

echo "<h1>🔧 Fix AI Profit Database Schema</h1>";

// Include the AI tables file
require_once( dirname( __FILE__ ) . '/includes/ai-tables.php' );

echo "<h2>Step 1: Creating AI Profit Settings Table</h2>";
try {
	// Drop table if it exists with wrong structure
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
	
	if ( $table_exists ) {
		echo "⚠️ Table exists, checking structure...<br>";
		// Check if therapist_id column has correct type
		$column_info = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'therapist_id'" );
		if ( $column_info && strpos( $column_info->Type, 'bigint' ) === false ) {
			echo "⚠️ Dropping table with incorrect structure...<br>";
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}
	}
	
	snks_create_ai_profit_settings_table();
	echo "✅ AI Profit Settings table created/updated successfully<br>";
} catch ( Exception $e ) {
	echo "❌ Error creating AI Profit Settings table: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 2: Adding AI Session Type Column</h2>";
try {
	snks_add_ai_session_type_column();
	echo "✅ AI Session Type column added successfully<br>";
	
	// Fix column types if needed
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_sessions_actions';
	
	// Check and fix therapist_id column type
	$therapist_column = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'therapist_id'" );
	if ( $therapist_column && strpos( $therapist_column->Type, 'bigint' ) === false ) {
		echo "⚠️ Fixing therapist_id column type...<br>";
		$wpdb->query( "ALTER TABLE {$table_name} MODIFY COLUMN therapist_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		echo "✅ therapist_id column type fixed<br>";
	}
	
	// Check and fix patient_id column type
	$patient_column = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'patient_id'" );
	if ( $patient_column && strpos( $patient_column->Type, 'bigint' ) === false ) {
		echo "⚠️ Fixing patient_id column type...<br>";
		$wpdb->query( "ALTER TABLE {$table_name} MODIFY COLUMN patient_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		echo "✅ patient_id column type fixed<br>";
	}
	
} catch ( Exception $e ) {
	echo "❌ Error adding AI Session Type column: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 3: Adding AI Transaction Metadata Columns</h2>";
try {
	snks_add_ai_transaction_metadata_columns();
	echo "✅ AI Transaction Metadata columns added successfully<br>";
	
	// Fix column types if needed
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_booking_transactions';
	
	// Check and fix ai_patient_id column type
	$patient_column = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'ai_patient_id'" );
	if ( $patient_column && strpos( $patient_column->Type, 'bigint' ) === false ) {
		echo "⚠️ Fixing ai_patient_id column type...<br>";
		$wpdb->query( "ALTER TABLE {$table_name} MODIFY COLUMN ai_patient_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		echo "✅ ai_patient_id column type fixed<br>";
	}
	
	// Check and fix ai_order_id column type
	$order_column = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'ai_order_id'" );
	if ( $order_column && strpos( $order_column->Type, 'bigint' ) === false ) {
		echo "⚠️ Fixing ai_order_id column type...<br>";
		$wpdb->query( "ALTER TABLE {$table_name} MODIFY COLUMN ai_order_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		echo "✅ ai_order_id column type fixed<br>";
	}
	
} catch ( Exception $e ) {
	echo "❌ Error adding AI Transaction Metadata columns: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 4: Adding Default Profit Settings</h2>";
try {
	snks_add_default_profit_settings();
	echo "✅ Default profit settings added successfully<br>";
} catch ( Exception $e ) {
	echo "❌ Error adding default profit settings: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 5: Verification</h2>";
global $wpdb;

// Check if tables and columns exist
$tables_to_check = array(
	$wpdb->prefix . 'snks_ai_profit_settings',
	$wpdb->prefix . 'snks_sessions_actions',
	$wpdb->prefix . 'snks_booking_transactions'
);

foreach ( $tables_to_check as $table ) {
	$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
	if ( $exists ) {
		echo "✅ {$table} - Exists<br>";
		
		// Check for AI-specific columns
		if ( $table === $wpdb->prefix . 'snks_sessions_actions' ) {
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}" );
			$column_names = array_column( $columns, 'Field' );
			
			$ai_columns = array( 'ai_session_type', 'therapist_id', 'patient_id' );
			foreach ( $ai_columns as $column ) {
				if ( in_array( $column, $column_names ) ) {
					echo "  ✅ Column {$column} - Exists<br>";
				} else {
					echo "  ❌ Column {$column} - Missing<br>";
				}
			}
		}
		
		if ( $table === $wpdb->prefix . 'snks_booking_transactions' ) {
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}" );
			$column_names = array_column( $columns, 'Field' );
			
			$ai_columns = array( 'ai_session_id', 'ai_session_type', 'ai_patient_id', 'ai_order_id' );
			foreach ( $ai_columns as $column ) {
				if ( in_array( $column, $column_names ) ) {
					echo "  ✅ Column {$column} - Exists<br>";
				} else {
					echo "  ❌ Column {$column} - Missing<br>";
				}
			}
		}
	} else {
		echo "❌ {$table} - Missing<br>";
	}
}

echo "<h2>🎯 Database Fix Complete</h2>";
echo "<p>The AI profit transfer system database schema has been updated.</p>";
echo "<p><a href='" . admin_url() . "' class='button'>← Back to Admin</a></p>";
echo "<p><a href='" . site_url() . '/wp-content/plugins/DoctorAppointmenets/test-ai-profit-integration.php' . "' class='button'>🧪 Run Integration Test</a></p>";
?>
