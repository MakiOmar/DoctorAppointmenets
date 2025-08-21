<?php
/**
 * Temporary script to set therapist joined status for testing
 * Run this once to set the therapist as joined for session 232
 */

// Include WordPress
require_once('../../../wp-load.php');

// Session details from your database
$session_id = 232;
$therapist_id = 85; // From your database record

// Set the transient to mark therapist as joined
$transient_key = "doctor_has_joined_{$session_id}_{$therapist_id}";
$result = set_transient($transient_key, '1', 3600); // Expires in 1 hour

if ($result) {
    echo "✅ Successfully set therapist joined status!\n";
    echo "Session ID: {$session_id}\n";
    echo "Therapist ID: {$therapist_id}\n";
    echo "Transient Key: {$transient_key}\n";
    echo "Expires: 1 hour from now\n";
    
    // Verify it was set
    $check = get_transient($transient_key);
    echo "Verification: " . ($check ? "✅ Set" : "❌ Not set") . "\n";
} else {
    echo "❌ Failed to set therapist joined status\n";
}

// Clean up - delete this file after use
echo "\n⚠️  Remember to delete this file after testing!\n";
?>
