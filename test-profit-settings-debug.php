<?php
/**
 * Debug script for Profit Settings
 * 
 * This script will help us identify why the profit settings page is showing no content
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Profit Settings Debug</h1>";

// Test 1: Check if functions exist
echo "<h2>1. Function Availability Test</h2>";
$functions_to_test = array(
    'snks_profit_settings_page',
    'snks_get_all_therapists_with_profit_settings',
    'snks_get_profit_settings_statistics',
    'snks_update_therapist_profit_settings',
    'snks_get_therapist_profit_settings'
);

foreach ($functions_to_test as $function) {
    if (function_exists($function)) {
        echo "✅ $function - Available<br>";
    } else {
        echo "❌ $function - NOT FOUND<br>";
    }
}

// Test 2: Check database tables
echo "<h2>2. Database Tables Test</h2>";
global $wpdb;

$tables_to_check = array(
    $wpdb->prefix . 'snks_ai_profit_settings',
    $wpdb->prefix . 'snks_sessions_actions',
    $wpdb->prefix . 'snks_booking_transactions'
);

foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✅ $table - EXISTS<br>";
        
        // Count records
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "   Records: $count<br>";
    } else {
        echo "❌ $table - MISSING<br>";
    }
}

// Test 3: Check if there are any doctors
echo "<h2>3. Doctors Test</h2>";
$doctors = get_users(array('role' => 'doctor'));
echo "Total doctors found: " . count($doctors) . "<br>";

if (count($doctors) > 0) {
    echo "First 3 doctors:<br>";
    foreach (array_slice($doctors, 0, 3) as $doctor) {
        echo "- ID: {$doctor->ID}, Name: {$doctor->display_name}, Email: {$doctor->user_email}<br>";
    }
} else {
    echo "❌ No doctors found!<br>";
}

// Test 4: Try to call the main function
echo "<h2>4. Main Function Test</h2>";
if (function_exists('snks_profit_settings_page')) {
    echo "✅ Function exists, trying to call it...<br>";
    
    // Capture output
    ob_start();
    try {
        snks_profit_settings_page();
        $output = ob_get_clean();
        
        if (empty(trim($output))) {
            echo "❌ Function returned empty content<br>";
        } else {
            echo "✅ Function returned content (length: " . strlen($output) . ")<br>";
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h3>Output Preview:</h3>";
            echo substr($output, 0, 500) . "...";
            echo "</div>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Function threw exception: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Function doesn't exist<br>";
}

// Test 5: Check for errors
echo "<h2>5. Error Check</h2>";
$errors = error_get_last();
if ($errors) {
    echo "❌ Last error: " . print_r($errors, true) . "<br>";
} else {
    echo "✅ No errors detected<br>";
}

echo "<h2>Debug Complete</h2>";
?>
