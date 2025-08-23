<?php
/**
 * Add AI Columns to Sessions Actions Table
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔧 Add AI Columns to Sessions Actions Table</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>";

global $wpdb;

$table_name = $wpdb->prefix . 'snks_sessions_actions';

echo "<div class='result info'>";
echo "<h3>Current Table Structure</h3>";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    echo "<p>✅ Table exists</p>";
    
    // Get current table structure
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    
    if ($columns) {
        echo "<h4>Current Columns:</h4>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column->Field} ({$column->Type})</li>";
        }
        echo "</ul>";
    }
    
    // Define required AI columns
    $required_columns = array(
        'therapist_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL',
        'patient_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL',
        'ai_session_type' => 'VARCHAR(20) DEFAULT NULL',
        'session_status' => 'VARCHAR(20) DEFAULT "open"',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    );
    
    echo "<h3>Adding Missing AI Columns</h3>";
    
    foreach ($required_columns as $column_name => $column_definition) {
        // Check if column exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE '{$column_name}'");
        
        if (!$column_exists) {
            echo "<h4>Adding column: {$column_name}</h4>";
            
            $sql = "ALTER TABLE {$table_name} ADD COLUMN {$column_name} {$column_definition}";
            $result = $wpdb->query($sql);
            
            if ($result !== false) {
                echo "<p class='success'>✅ Successfully added column: {$column_name}</p>";
            } else {
                echo "<p class='error'>❌ Failed to add column: {$column_name} - {$wpdb->last_error}</p>";
            }
        } else {
            echo "<p>ℹ️ Column {$column_name} already exists</p>";
        }
    }
    
    // Show final table structure
    echo "<h3>Final Table Structure</h3>";
    $final_columns = $wpdb->get_results("DESCRIBE {$table_name}");
    
    if ($final_columns) {
        echo "<h4>All Columns:</h4>";
        echo "<ul>";
        foreach ($final_columns as $column) {
            echo "<li>{$column->Field} ({$column->Type})</li>";
        }
        echo "</ul>";
    }
    
} else {
    echo "<p class='error'>❌ Table does not exist</p>";
}

echo "</div>";

echo "<div class='result success'>";
echo "<h3>🎯 Summary</h3>";
echo "<p>The sessions_actions table has been updated with AI columns. You can now:</p>";
echo "<ul>";
echo "<li>✅ Create session action records with AI metadata</li>";
echo "<li>✅ Track therapist and patient IDs</li>";
echo "<li>✅ Track AI session types</li>";
echo "<li>✅ Track session status</li>";
echo "<li>✅ Use timestamps for tracking</li>";
echo "</ul>";
echo "</div>";

echo "<div class='result info'>";
echo "<h3>📋 Next Steps</h3>";
echo "<ol>";
echo "<li>Run the fix script again to create the session action record</li>";
echo "<li>Test the AI appointment booking process</li>";
echo "<li>Verify profit transfer functionality</li>";
echo "</ol>";
echo "</div>";
?>
