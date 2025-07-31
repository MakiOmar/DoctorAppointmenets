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

// Show existing data for user 85 (patient)
echo "<h3>Current appointments for patient 85:</h3>";
$appointments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = 85");
if ($appointments) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Status</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Order ID</th></tr>";
    foreach ($appointments as $apt) {
        echo "<tr>";
        echo "<td>{$apt->ID}</td>";
        echo "<td>{$apt->user_id}</td>";
        echo "<td>{$apt->client_id}</td>";
        echo "<td>{$apt->session_status}</td>";
        echo "<td>{$apt->date_time}</td>";
        echo "<td>{$apt->starts}</td>";
        echo "<td>{$apt->ends}</td>";
        echo "<td>{$apt->order_id}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No appointments found for patient 85.";
}

// Show available slots for doctor 1
echo "<h3>Available slots for doctor 1:</h3>";
$slots = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 1 AND session_status = 'waiting' AND client_id = 0");
if ($slots) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Day</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Period</th></tr>";
    foreach ($slots as $slot) {
        echo "<tr>";
        echo "<td>{$slot->ID}</td>";
        echo "<td>{$slot->user_id}</td>";
        echo "<td>{$slot->client_id}</td>";
        echo "<td>{$slot->day}</td>";
        echo "<td>{$slot->date_time}</td>";
        echo "<td>{$slot->starts}</td>";
        echo "<td>{$slot->ends}</td>";
        echo "<td>{$slot->period}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No available slots found for doctor 1.";
}

// Show all data for reference
echo "<h3>All timetable data (for reference):</h3>";
$all_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable ORDER BY date_time");
if ($all_data) {
    echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Status</th><th>Day</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Order ID</th></tr>";
    foreach ($all_data as $row) {
        echo "<tr>";
        echo "<td>{$row->ID}</td>";
        echo "<td>{$row->user_id}</td>";
        echo "<td>{$row->client_id}</td>";
        echo "<td>{$row->session_status}</td>";
        echo "<td>{$row->day}</td>";
        echo "<td>{$row->date_time}</td>";
        echo "<td>{$row->starts}</td>";
        echo "<td>{$row->ends}</td>";
        echo "<td>{$row->order_id}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found in timetable.";
}
?> 