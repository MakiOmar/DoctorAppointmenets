<?php
/**
 * Debug AI Transaction Issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔍 Debug AI Transaction Issues</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

global $wpdb;

// Test with appointment ID 411
$appointment_id = 411;

echo "<div class='result info'>";
echo "<h3>Step 1: Check Session Action Record</h3>";

$session_action = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
    $appointment_id
));

if ($session_action) {
    echo "<p>✅ Session action found:</p>";
    echo "<ul>";
    echo "<li>ID: {$session_action->ID}</li>";
    echo "<li>Case ID: {$session_action->case_id}</li>";
    echo "<li>Therapist ID: {$session_action->therapist_id}</li>";
    echo "<li>Patient ID: {$session_action->patient_id}</li>";
    echo "<li>AI Session Type: " . ($session_action->ai_session_type ?: 'NULL') . "</li>";
    echo "<li>Session Status: " . ($session_action->session_status ?: 'NULL') . "</li>";
    echo "<li>Attendance: {$session_action->attendance}</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ No session action found for appointment ID {$appointment_id}</p>";
    echo "</div>";
    return;
}
echo "</div>";

echo "<div class='result info'>";
echo "<h3>Step 2: Check Order Details</h3>";

$order_id = $session_action->case_id;
$order = wc_get_order($order_id);

if ($order) {
    echo "<p>✅ Order found:</p>";
    echo "<ul>";
    echo "<li>Order ID: {$order_id}</li>";
    echo "<li>Status: {$order->get_status()}</li>";
    echo "<li>Total: {$order->get_total()}</li>";
    echo "<li>Is AI Session: " . ($order->get_meta('is_ai_session') ? 'Yes' : 'No') . "</li>";
    echo "<li>From Jalsah AI: " . ($order->get_meta('from_jalsah_ai') ? 'Yes' : 'No') . "</li>";
    echo "<li>AI Session ID: " . ($order->get_meta('ai_session_id') ?: 'Not set') . "</li>";
    echo "<li>AI Therapist ID: " . ($order->get_meta('ai_therapist_id') ?: 'Not set') . "</li>";
    echo "<li>AI User ID: " . ($order->get_meta('ai_user_id') ?: 'Not set') . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Order not found for ID {$order_id}</p>";
    echo "</div>";
    return;
}
echo "</div>";

echo "<div class='result info'>";
echo "<h3>Step 3: Check Existing Transactions</h3>";

$existing_transaction = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_booking_transactions WHERE ai_session_id = %s",
    $appointment_id
));

if ($existing_transaction) {
    echo "<p class='warning'>⚠️ Transaction already exists:</p>";
    echo "<ul>";
    echo "<li>Transaction ID: {$existing_transaction->id}</li>";
    echo "<li>Amount: {$existing_transaction->amount}</li>";
    echo "<li>Transaction Type: {$existing_transaction->transaction_type}</li>";
    echo "<li>Transaction Time: {$existing_transaction->transaction_time}</li>";
    echo "<li>AI Session Type: " . ($existing_transaction->ai_session_type ?: 'NULL') . "</li>";
    echo "</ul>";
} else {
    echo "<p>ℹ️ No existing transaction found</p>";
}
echo "</div>";

echo "<div class='result info'>";
echo "<h3>Step 4: Test Profit Transfer Function</h3>";

if (function_exists('snks_execute_ai_profit_transfer')) {
    echo "<p>✅ snks_execute_ai_profit_transfer function exists</p>";
    
    // Test the function
    $result = snks_execute_ai_profit_transfer($appointment_id);
    
    echo "<h4>Function Result:</h4>";
    echo "<ul>";
    echo "<li>Success: " . ($result['success'] ? 'Yes' : 'No') . "</li>";
    echo "<li>Message: {$result['message']}</li>";
    if (isset($result['transaction_id'])) {
        echo "<li>Transaction ID: {$result['transaction_id']}</li>";
    }
    if (isset($result['profit_amount'])) {
        echo "<li>Profit Amount: {$result['profit_amount']}</li>";
    }
    if (isset($result['session_type'])) {
        echo "<li>Session Type: {$result['session_type']}</li>";
    }
    echo "</ul>";
    
    if ($result['success']) {
        echo "<p class='success'>✅ Profit transfer successful!</p>";
    } else {
        echo "<p class='error'>❌ Profit transfer failed: {$result['message']}</p>";
    }
} else {
    echo "<p class='error'>❌ snks_execute_ai_profit_transfer function not found</p>";
}
echo "</div>";

echo "<div class='result info'>";
echo "<h3>Step 5: Check Profit Settings</h3>";

$therapist_id = $session_action->therapist_id;
if (function_exists('snks_get_therapist_profit_settings')) {
    $settings = snks_get_therapist_profit_settings($therapist_id);
    echo "<p>✅ Profit settings for therapist {$therapist_id}:</p>";
    echo "<ul>";
    echo "<li>First Session Percentage: {$settings['first_session_percentage']}%</li>";
    echo "<li>Subsequent Session Percentage: {$settings['subsequent_session_percentage']}%</li>";
    echo "<li>Is Active: " . ($settings['is_active'] ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ snks_get_therapist_profit_settings function not found</p>";
}
echo "</div>";

echo "<div class='result success'>";
echo "<h3>🎯 Summary</h3>";
echo "<p>Debug completed. Check the results above to identify the issue.</p>";
echo "</div>";
?>
