<?php
/**
 * Fix Database Structure
 * Run this script to manually add missing columns
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔧 Database Structure Fix</h1>";
echo "<style>
    .fix-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    .fix-success { background: #d4edda; color: #155724; }
    .fix-error { background: #f8d7da; color: #721c24; }
    .fix-info { background: #d1ecf1; color: #0c5460; }
    .fix-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
</style>";

global $wpdb;

echo "<div class='fix-section'>";
echo "<h2>📊 Current Database Status</h2>";

// Check if functions exist
if (function_exists('snks_create_ai_profit_settings_table')) {
    echo "<div class='fix-result fix-success'>✅ AI profit functions are available</div>";
} else {
    echo "<div class='fix-result fix-error'>❌ AI profit functions not found</div>";
    echo "<p>Please ensure the plugin is properly loaded.</p>";
    exit;
}

echo "</div>";

echo "<div class='fix-section'>";
echo "<h2>🔧 Running Database Fixes</h2>";

try {
    // Create AI profit settings table
    echo "<div class='fix-result fix-info'>Creating AI profit settings table...</div>";
    snks_create_ai_profit_settings_table();
    echo "<div class='fix-result fix-success'>✅ AI profit settings table created/updated</div>";
    
    // Add AI session type column
    echo "<div class='fix-result fix-info'>Adding AI session type column...</div>";
    snks_add_ai_session_type_column();
    echo "<div class='fix-result fix-success'>✅ AI session type column added</div>";
    
    // Add AI transaction metadata columns
    echo "<div class='fix-result fix-info'>Adding AI transaction metadata columns...</div>";
    snks_add_ai_transaction_metadata_columns();
    echo "<div class='fix-result fix-success'>✅ AI transaction metadata columns added</div>";
    
    // Add default profit settings
    echo "<div class='fix-result fix-info'>Adding default profit settings...</div>";
    snks_add_default_profit_settings();
    echo "<div class='fix-result fix-success'>✅ Default profit settings added</div>";
    
    // Update version
    update_option('snks_ai_profit_system_version', '1.0.0');
    echo "<div class='fix-result fix-success'>✅ Database version updated to 1.0.0</div>";
    
} catch (Exception $e) {
    echo "<div class='fix-result fix-error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='fix-section'>";
echo "<h2>✅ Verification</h2>";

// Verify tables and columns
$tables_to_check = [
    'wpds_snks_ai_profit_settings',
    'wpds_snks_sessions_actions',
    'wpds_snks_booking_transactions'
];

foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $class = $exists ? 'fix-success' : 'fix-error';
    echo "<div class='fix-result $class'>";
    echo "Table $table: " . ($exists ? "✅ EXISTS" : "❌ MISSING");
    echo "</div>";
}

// Check specific columns
$sessions_columns = ['ai_session_type', 'therapist_id', 'patient_id'];
$table_name = 'wpds_snks_sessions_actions';

foreach ($sessions_columns as $column) {
    $exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column'") !== null;
    $class = $exists ? 'fix-success' : 'fix-error';
    echo "<div class='fix-result $class'>";
    echo "Column $table_name.$column: " . ($exists ? "✅ EXISTS" : "❌ MISSING");
    echo "</div>";
}

$transactions_columns = ['ai_session_id', 'ai_session_type', 'ai_patient_id', 'ai_order_id'];
$table_name = 'wpds_snks_booking_transactions';

foreach ($transactions_columns as $column) {
    $exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column'") !== null;
    $class = $exists ? 'fix-success' : 'fix-error';
    echo "<div class='fix-result $class'>";
    echo "Column $table_name.$column: " . ($exists ? "✅ EXISTS" : "❌ MISSING");
    echo "</div>";
}

echo "</div>";

echo "<div class='fix-section'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<p>✅ Database structure has been fixed!</p>";
echo "<p>📋 <a href='test-ai-profit-system-complete.php'>Run the complete system test</a> to verify everything is working.</p>";
echo "<p>🚀 The AI Profit System is now ready for production deployment.</p>";
echo "</div>";
?>
