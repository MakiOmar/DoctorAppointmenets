<?php
/**
 * Test Script: Check Database Table Contents
 */

require_once('../../../wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'therapist_applications';

echo "<h2>Database Table Check</h2>";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "✅ Table '$table_name' exists<br><br>";
    
    // Count applications by status
    $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    $approved = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
    $rejected = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");
    
    echo "<h3>Application Counts:</h3>";
    echo "Pending: $pending<br>";
    echo "Approved: $approved<br>";
    echo "Rejected: $rejected<br><br>";
    
    // Show all applications
    $applications = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    echo "<h3>All Applications:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Specialty</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($applications as $app) {
        echo "<tr>";
        echo "<td>{$app->id}</td>";
        echo "<td>{$app->name}</td>";
        echo "<td>{$app->email}</td>";
        echo "<td>{$app->phone}</td>";
        echo "<td>{$app->doctor_specialty}</td>";
        echo "<td>{$app->status}</td>";
        echo "<td>{$app->created_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "❌ Table '$table_name' does not exist<br>";
    echo "Please deactivate and reactivate the plugin to create the table.";
}
?> 