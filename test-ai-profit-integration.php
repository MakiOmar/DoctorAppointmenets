<?php
/**
 * Test AI Profit Integration
 * 
 * This script tests the AI profit transfer system integration
 * 
 * @package Shrinks
 */

// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Ensure we're in admin context
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied' );
}

echo "<h1>🧪 AI Profit Integration Test</h1>";

// Test 1: Check if profit calculator functions exist
echo "<h2>Test 1: Core Functions Availability</h2>";

// Check AI profit system version
$ai_profit_version = get_option( 'snks_ai_profit_system_version', 'Not installed' );
echo "<p><strong>AI Profit System Version:</strong> {$ai_profit_version}</p>";
$functions_to_test = array(
	'snks_get_therapist_profit_settings',
	'snks_calculate_session_profit',
	'snks_is_first_session',
	'snks_add_ai_session_transaction',
	'snks_execute_ai_profit_transfer',
	'snks_is_ai_session',
	'snks_get_ai_session_profit_stats',
	'snks_update_therapist_profit_settings'
);

foreach ( $functions_to_test as $function ) {
	if ( function_exists( $function ) ) {
		echo "✅ {$function} - Available<br>";
	} else {
		echo "❌ {$function} - Missing<br>";
	}
}

// Test 2: Check database tables
echo "<h2>Test 2: Database Tables</h2>";
global $wpdb;

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

// Test 3: Check therapist profit settings
echo "<h2>Test 3: Therapist Profit Settings</h2>";
$therapists = get_users( array( 'role' => 'doctor', 'number' => 5 ) );

if ( empty( $therapists ) ) {
	echo "⚠️ No therapists found with 'doctor' role<br>";
} else {
	echo "Found " . count( $therapists ) . " therapists<br>";
	
	foreach ( $therapists as $therapist ) {
		$settings = snks_get_therapist_profit_settings( $therapist->ID );
		if ( $settings ) {
			echo "✅ Therapist {$therapist->display_name} (ID: {$therapist->ID}) - Settings: {$settings['first_session_percentage']}% / {$settings['subsequent_session_percentage']}%<br>";
		} else {
			echo "⚠️ Therapist {$therapist->display_name} (ID: {$therapist->ID}) - Using default settings<br>";
		}
	}
}

// Test 4: Check AI session statistics
echo "<h2>Test 4: AI Session Statistics</h2>";
if ( function_exists( 'snks_get_ai_session_statistics' ) ) {
	$stats = snks_get_ai_session_statistics();
	echo "📊 AI Session Statistics:<br>";
	echo "- Total Sessions: {$stats['total_sessions']}<br>";
	echo "- Completed Sessions: {$stats['completed_sessions']}<br>";
	echo "- Total Profit: {$stats['total_profit']} ج.م<br>";
	echo "- Today's Sessions: {$stats['today_sessions']}<br>";
	echo "- Today's Profit: {$stats['today_profit']} ج.م<br>";
	echo "- Completion Rate: {$stats['completion_rate']}%<br>";
} else {
	echo "❌ snks_get_ai_session_statistics function not available<br>";
}

// Test 5: Check admin pages
echo "<h2>Test 5: Admin Pages</h2>";
$admin_pages = array(
	'profit-settings' => 'إعدادات الأرباح',
	'therapist-earnings' => 'أرباح المعالجين'
);

foreach ( $admin_pages as $page => $title ) {
	$url = admin_url( "admin.php?page={$page}" );
	echo "🔗 <a href='{$url}' target='_blank'>{$title}</a><br>";
}

// Test 6: Check hooks and actions
echo "<h2>Test 6: Hooks and Actions</h2>";
$hooks_to_check = array(
	'wp_ajax_end_ai_session',
	'woocommerce_order_status_completed',
	'woocommerce_order_status_processing',
	'snks_session_action_updated'
);

foreach ( $hooks_to_check as $hook ) {
	$callbacks = has_action( $hook );
	if ( $callbacks ) {
		echo "✅ Hook {$hook} - Has callbacks<br>";
	} else {
		echo "⚠️ Hook {$hook} - No callbacks registered<br>";
	}
}

// Test 7: Check existing AI sessions
echo "<h2>Test 7: Existing AI Sessions</h2>";
$ai_sessions = $wpdb->get_results( "
	SELECT * FROM {$wpdb->prefix}snks_sessions_actions 
	WHERE ai_session_type IS NOT NULL 
	LIMIT 5
" );

if ( empty( $ai_sessions ) ) {
	echo "ℹ️ No AI sessions found in database<br>";
} else {
	echo "Found " . count( $ai_sessions ) . " AI sessions:<br>";
	foreach ( $ai_sessions as $session ) {
		echo "- Session ID: {$session->action_session_id}, Type: {$session->ai_session_type}, Status: {$session->session_status}<br>";
	}
}

// Test 8: Check existing AI transactions
echo "<h2>Test 8: Existing AI Transactions</h2>";
$ai_transactions = $wpdb->get_results( "
	SELECT * FROM {$wpdb->prefix}snks_booking_transactions 
	WHERE ai_session_id IS NOT NULL 
	LIMIT 5
" );

if ( empty( $ai_transactions ) ) {
	echo "ℹ️ No AI transactions found in database<br>";
} else {
	echo "Found " . count( $ai_transactions ) . " AI transactions:<br>";
	foreach ( $ai_transactions as $transaction ) {
		echo "- Transaction ID: {$transaction->id}, Session ID: {$transaction->ai_session_id}, Amount: {$transaction->amount} ج.م<br>";
	}
}

echo "<h2>🎯 Test Summary</h2>";
echo "The AI Profit Integration system has been tested. Check the results above for any issues.<br>";
echo "<br><strong>Next Steps:</strong><br>";
echo "1. If all tests pass ✅, the system is ready for use<br>";
echo "2. If any tests fail ❌, check the implementation<br>";
echo "3. Test with actual AI session completion<br>";
echo "4. Monitor profit calculations and transactions<br>";

echo "<br><a href='" . admin_url() . "' class='button'>← Back to Admin</a>";
?>
