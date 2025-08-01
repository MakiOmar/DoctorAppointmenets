<?php
/**
 * Manual script to create demo data for doctor 85
 * Run this file directly in the browser to create demo data
 */

// Include WordPress
require_once('../../../wp-load.php');

// Check if demo data already exists
global $wpdb;
$existing_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 85 AND settings LIKE '%ai_booking%'");

if ($existing_appointments > 0) {
    echo "Demo data already exists for doctor 85. Found {$existing_appointments} AI available slots.<br>";
    echo "You can test the booking system now!<br>";
} else {
    // Create demo data
    if (function_exists('snks_create_demo_booking_data')) {
        snks_create_demo_booking_data();
        echo "Demo data created successfully for doctor 85!<br>";
        echo "You can now test the booking system.<br>";
    } else {
        echo "Error: snks_create_demo_booking_data function not found.<br>";
    }
}

// Show available AI slots for doctor 85
echo "<h3>Available AI slots for doctor 85:</h3>";
$slots = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 85 AND session_status = 'waiting' AND client_id = 0 AND settings LIKE '%ai_booking%'");
if ($slots) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Day</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Period</th><th>Settings</th></tr>";
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
        echo "<td>{$slot->settings}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No available AI slots found for doctor 85.";
}

// Show any AI bookings for doctor 85 (if any patients have booked)
echo "<h3>AI bookings for doctor 85 (if any):</h3>";
$bookings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 85 AND client_id > 0 AND settings LIKE '%ai_booking%'");
if ($bookings) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Status</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Order ID</th><th>Settings</th></tr>";
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>{$booking->ID}</td>";
        echo "<td>{$booking->user_id}</td>";
        echo "<td>{$booking->client_id}</td>";
        echo "<td>{$booking->session_status}</td>";
        echo "<td>{$booking->date_time}</td>";
        echo "<td>{$booking->starts}</td>";
        echo "<td>{$booking->ends}</td>";
        echo "<td>{$booking->order_id}</td>";
        echo "<td>{$booking->settings}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No AI bookings found for doctor 85 yet.";
}

// Show all AI data for reference
echo "<h3>All AI timetable data (for reference):</h3>";
$all_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE settings LIKE '%ai_booking%' ORDER BY date_time");
if ($all_data) {
    echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Patient ID</th><th>Status</th><th>Day</th><th>Date/Time</th><th>Starts</th><th>Ends</th><th>Order ID</th><th>Settings</th></tr>";
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
        echo "<td>{$row->settings}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No AI data found in timetable.";
}
?> 