<?php
/**
 * Debug Diagnoses Table
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>Diagnoses Table Debug</h1>";

// Check if table exists
$table_name = $wpdb->prefix . 'snks_diagnoses';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if (!$table_exists) {
    echo "<p style='color: red;'>❌ Table '$table_name' does not exist!</p>";
    exit;
}

echo "<p style='color: green;'>✅ Table '$table_name' exists</p>";

// Check table structure
echo "<h2>Table Structure:</h2>";
$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . $column->Field . "</td>";
    echo "<td>" . $column->Type . "</td>";
    echo "<td>" . $column->Null . "</td>";
    echo "<td>" . $column->Key . "</td>";
    echo "<td>" . $column->Default . "</td>";
    echo "<td>" . $column->Extra . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check sample data
echo "<h2>Sample Data (first 5 records):</h2>";
$diagnoses = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5");
if ($diagnoses) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Name EN</th><th>Name AR</th><th>Description</th></tr>";
    foreach ($diagnoses as $diagnosis) {
        echo "<tr>";
        echo "<td>" . $diagnosis->id . "</td>";
        echo "<td>" . ($diagnosis->name ?? 'NULL') . "</td>";
        echo "<td>" . ($diagnosis->name_en ?? 'NULL') . "</td>";
        echo "<td>" . ($diagnosis->name_ar ?? 'NULL') . "</td>";
        echo "<td>" . ($diagnosis->description ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No diagnoses found in table</p>";
}

// Check therapist diagnoses table
echo "<h2>Therapist Diagnoses Table:</h2>";
$therapist_table = $wpdb->prefix . 'snks_therapist_diagnoses';
$therapist_exists = $wpdb->get_var("SHOW TABLES LIKE '$therapist_table'");

if ($therapist_exists) {
    echo "<p style='color: green;'>✅ Table '$therapist_table' exists</p>";
    
    // Check sample therapist diagnoses
    $therapist_diagnoses = $wpdb->get_results("
        SELECT td.*, d.name as diagnosis_name, d.name_en, d.name_ar 
        FROM $therapist_table td 
        LEFT JOIN $table_name d ON td.diagnosis_id = d.id 
        LIMIT 5
    ");
    
    if ($therapist_diagnoses) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Therapist ID</th><th>Diagnosis ID</th><th>Diagnosis Name</th><th>Name EN</th><th>Name AR</th><th>Rating</th></tr>";
        foreach ($therapist_diagnoses as $td) {
            echo "<tr>";
            echo "<td>" . $td->therapist_id . "</td>";
            echo "<td>" . $td->diagnosis_id . "</td>";
            echo "<td>" . ($td->diagnosis_name ?? 'NULL') . "</td>";
            echo "<td>" . ($td->name_en ?? 'NULL') . "</td>";
            echo "<td>" . ($td->name_ar ?? 'NULL') . "</td>";
            echo "<td>" . $td->rating . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No therapist diagnoses found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Table '$therapist_table' does not exist!</p>";
}
?> 