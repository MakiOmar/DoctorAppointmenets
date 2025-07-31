<?php
/**
 * Manual script to create demo data for user 85
 * Run this file directly in the browser to create demo data
 */

// Include WordPress
require_once('../../../wp-load.php');

// Check if demo data already exists
global $wpdb;
$existing_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = 85");

if ($existing_appointments > 0) {
    echo "Demo data already exists for user 85. Found {$existing_appointments} appointments.<br>";
    echo "You can test the booking system now!<br>";
} else {
    // Create demo data
    if (function_exists('snks_create_demo_booking_data')) {
        snks_create_demo_booking_data();
        echo "Demo data created successfully for user 85!<br>";
        echo "You can now test the booking system.<br>";
    } else {
        echo "Error: snks_create_demo_booking_data function not found.<br>";
    }
}

// Show existing data for user 85
echo "<h3>Current data for user 85:</h3>";
$appointments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = 85");
if ($appointments) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Therapist ID</th><th>Status</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Order ID</th></tr>";
    foreach ($appointments as $apt) {
        echo "<tr>";
        echo "<td>{$apt->ID}</td>";
        echo "<td>{$apt->user_id}</td>";
        echo "<td>{$apt->session_status}</td>";
        echo "<td>{$apt->date_time}</td>";
        echo "<td>{$apt->starts}</td>";
        echo "<td>{$apt->ends}</td>";
        echo "<td>{$apt->order_id}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No appointments found for user 85.";
}

// Show available slots for therapist 1
echo "<h3>Available slots for therapist 1:</h3>";
$slots = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 1 AND session_status = 'waiting' AND client_id = 0");
if ($slots) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Day</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Period</th></tr>";
    foreach ($slots as $slot) {
        echo "<tr>";
        echo "<td>{$slot->ID}</td>";
        echo "<td>{$slot->day}</td>";
        echo "<td>{$slot->date_time}</td>";
        echo "<td>{$slot->starts}</td>";
        echo "<td>{$slot->ends}</td>";
        echo "<td>{$slot->period}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No available slots found for therapist 1.";
}
?> 