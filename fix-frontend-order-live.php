<?php
/**
 * Fix Frontend Order for Live Server
 * Run this script to check and fix frontend_order values
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h2>🔍 Checking Current Frontend Order Values</h2>";

// Check current values
$current_values = $wpdb->get_results("
    SELECT therapist_id, diagnosis_id, frontend_order, display_order 
    FROM {$wpdb->prefix}snks_therapist_diagnoses 
    WHERE diagnosis_id = 6 
    ORDER BY display_order ASC
");

echo "<h3>Current Values:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Therapist ID</th><th>Diagnosis ID</th><th>Display Order</th><th>Frontend Order</th></tr>";

foreach ($current_values as $row) {
    echo "<tr>";
    echo "<td>{$row->therapist_id}</td>";
    echo "<td>{$row->diagnosis_id}</td>";
    echo "<td>{$row->display_order}</td>";
    echo "<td>{$row->frontend_order}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🔧 Fixing Frontend Order Values...</h3>";

// Update frontend_order based on display_order
$update_result = $wpdb->query("
    UPDATE {$wpdb->prefix}snks_therapist_diagnoses 
    SET frontend_order = CASE 
        WHEN display_order = 100 THEN 1
        WHEN display_order = 200 THEN 2
        ELSE frontend_order
    END
    WHERE diagnosis_id = 6
");

if ($update_result !== false) {
    echo "<p style='color: green;'>✅ Update completed! {$update_result} rows affected.</p>";
} else {
    echo "<p style='color: red;'>❌ Update failed: " . $wpdb->last_error . "</p>";
}

echo "<h3>🔍 Checking Updated Values:</h3>";

// Check updated values
$updated_values = $wpdb->get_results("
    SELECT therapist_id, diagnosis_id, frontend_order, display_order 
    FROM {$wpdb->prefix}snks_therapist_diagnoses 
    WHERE diagnosis_id = 6 
    ORDER BY frontend_order ASC
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Therapist ID</th><th>Diagnosis ID</th><th>Display Order</th><th>Frontend Order</th></tr>";

foreach ($updated_values as $row) {
    echo "<tr>";
    echo "<td>{$row->therapist_id}</td>";
    echo "<td>{$row->diagnosis_id}</td>";
    echo "<td>{$row->display_order}</td>";
    echo "<td>{$row->frontend_order}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🎯 Expected Result:</h3>";
echo "<ul>";
echo "<li>Therapist with display_order = 100 should have frontend_order = 1</li>";
echo "<li>Therapist with display_order = 200 should have frontend_order = 2</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Refresh your diagnosis results page</li>";
echo "<li>Check if the frontend now shows correct frontend_order values</li>";
echo "<li>Delete this file after testing</li>";
echo "</ol>";
?>
