<?php
/**
 * Check Sessions Actions Table Structure
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔍 Check Sessions Actions Table Structure</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>";

global $wpdb;

$table_name = $wpdb->prefix . 'snks_sessions_actions';

echo "<div class='result info'>";
echo "<h3>Table Structure for: {$table_name}</h3>";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    echo "<p>✅ Table exists</p>";
    
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    
    if ($columns) {
        echo "<h4>Columns:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column->Field}</td>";
            echo "<td>{$column->Type}</td>";
            echo "<td>{$column->Null}</td>";
            echo "<td>{$column->Key}</td>";
            echo "<td>{$column->Default}</td>";
            echo "<td>{$column->Extra}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Could not get table structure</p>";
    }
    
    // Check sample data
    echo "<h4>Sample Data (first 5 records):</h4>";
    $sample_data = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 5");
    
    if ($sample_data) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys((array)$sample_data[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        
        foreach ($sample_data as $row) {
            echo "<tr>";
            foreach ((array)$row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in table</p>";
    }
    
} else {
    echo "<p>❌ Table does not exist</p>";
}

echo "</div>";

// Test insertion with different attendance values
echo "<div class='result info'>";
echo "<h3>Test Insertion with Different Attendance Values</h3>";

$test_values = ['yes', 'no', 'pending', 'attended', 'absent'];

foreach ($test_values as $test_value) {
    echo "<h4>Testing attendance value: '{$test_value}'</h4>";
    
    $test_data = array(
        'action_session_id' => '999',
        'case_id' => 999,
        'attendance' => $test_value
    );
    
    $result = $wpdb->insert(
        $table_name,
        $test_data,
        array('%s', '%d', '%s')
    );
    
    if ($result) {
        echo "<p class='success'>✅ Successfully inserted '{$test_value}'</p>";
        // Clean up test data
        $wpdb->delete($table_name, array('action_session_id' => '999'));
    } else {
        echo "<p class='error'>❌ Failed to insert '{$test_value}': {$wpdb->last_error}</p>";
    }
}

echo "</div>";
?>
