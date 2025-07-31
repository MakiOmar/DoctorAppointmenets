<?php
/**
 * Debug script to check total_ratings in database
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'therapist_applications';

echo "<h2>Debug: Checking total_ratings in database</h2>";

// Check if column exists
$column_exists = $wpdb->get_results( $wpdb->prepare( 
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
    DB_NAME,
    $table_name,
    'total_ratings'
) );

echo "<p><strong>Column exists:</strong> " . (empty($column_exists) ? 'NO' : 'YES') . "</p>";

// Get all applications with their total_ratings
$applications = $wpdb->get_results("SELECT id, user_id, name, rating, total_ratings FROM $table_name");

echo "<h3>All applications:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Rating</th><th>Total Ratings</th><th>Type</th></tr>";

foreach ($applications as $app) {
    echo "<tr>";
    echo "<td>" . $app->id . "</td>";
    echo "<td>" . $app->user_id . "</td>";
    echo "<td>" . htmlspecialchars($app->name) . "</td>";
    echo "<td>" . $app->rating . "</td>";
    echo "<td>" . $app->total_ratings . "</td>";
    echo "<td>" . gettype($app->total_ratings) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test the API endpoint
echo "<h3>Testing API endpoint:</h3>";
$api_url = home_url('/api/ai/therapists');
echo "<p>API URL: <a href='$api_url' target='_blank'>$api_url</a></p>";

// Make a direct API call
$response = wp_remote_get($api_url);
if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    echo "<h4>API Response:</h4>";
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<p>Error calling API: " . $response->get_error_message() . "</p>";
}
?> 