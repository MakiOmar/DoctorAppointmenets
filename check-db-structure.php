<?php
/**
 * Check Database Structure
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

global $wpdb;

echo "<h2>Database Structure Check</h2>";

// Check sessions_actions table
echo "<h3>wpds_snks_sessions_actions table:</h3>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM wpds_snks_sessions_actions");
echo "<ul>";
foreach($columns as $col) {
    echo "<li><strong>{$col->Field}</strong> - {$col->Type}</li>";
}
echo "</ul>";

// Check booking_transactions table
echo "<h3>wpds_snks_booking_transactions table:</h3>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM wpds_snks_booking_transactions");
echo "<ul>";
foreach($columns as $col) {
    echo "<li><strong>{$col->Field}</strong> - {$col->Type}</li>";
}
echo "</ul>";

// Check ai_profit_settings table
echo "<h3>wpds_snks_ai_profit_settings table:</h3>";
$exists = $wpdb->get_var("SHOW TABLES LIKE 'wpds_snks_ai_profit_settings'") === 'wpds_snks_ai_profit_settings';
if ($exists) {
    $columns = $wpdb->get_results("SHOW COLUMNS FROM wpds_snks_ai_profit_settings");
    echo "<ul>";
    foreach($columns as $col) {
        echo "<li><strong>{$col->Field}</strong> - {$col->Type}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Table does not exist!</p>";
}
?>
