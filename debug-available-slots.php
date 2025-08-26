<?php
// Debug script to check available slots
require_once('../../../wp-config.php');

global $wpdb;

echo "Checking available slots for therapist 211...\n\n";

// Check for available slots in the next 30 days
$available_dates = $wpdb->get_results($wpdb->prepare(
    "SELECT DISTINCT DATE(date_time) as date, COUNT(*) as slot_count
     FROM {$wpdb->prefix}snks_provider_timetable 
     WHERE user_id = %d 
     AND DATE(date_time) >= CURDATE()
     AND DATE(date_time) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     AND session_status = 'waiting' 
     AND order_id = 0
     AND attendance_type = 'online'
     GROUP BY DATE(date_time)
     ORDER BY DATE(date_time) ASC",
    211
));

echo "Available dates found: " . count($available_dates) . "\n\n";

if (count($available_dates) > 0) {
    echo "Available dates:\n";
    foreach ($available_dates as $date_row) {
        echo "- {$date_row->date} ({$date_row->slot_count} slots)\n";
    }
} else {
    echo "No available dates found.\n\n";
    
    // Check what slots exist for this therapist
    $all_slots = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(date_time) as date, session_status, order_id, attendance_type, settings
         FROM {$wpdb->prefix}snks_provider_timetable 
         WHERE user_id = %d 
         AND DATE(date_time) >= CURDATE()
         AND DATE(date_time) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY DATE(date_time) ASC",
        211
    ));
    
    echo "All slots for therapist 211 in next 30 days:\n";
    foreach ($all_slots as $slot) {
        echo "- {$slot->date}: status={$slot->session_status}, order_id={$slot->order_id}, type={$slot->attendance_type}, settings={$slot->settings}\n";
    }
}

echo "\nDone.\n";
?>
