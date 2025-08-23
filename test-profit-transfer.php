<?php
/**
 * Test Profit Transfer Function
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🧪 Test Profit Transfer Function</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>";

global $wpdb;

// Test with appointment ID 411
$appointment_id = 411;

echo "<div class='result info'>";
echo "<h3>Testing Profit Transfer for Appointment ID: {$appointment_id}</h3>";

// Check if function exists
if (!function_exists('snks_execute_ai_profit_transfer')) {
    echo "<p class='error'>❌ snks_execute_ai_profit_transfer function not found</p>";
    echo "</div>";
    return;
}

echo "<p>✅ Function exists, testing...</p>";

// Test the function
$result = snks_execute_ai_profit_transfer($appointment_id);

echo "<h4>Result:</h4>";
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
    
    // Check if transaction was actually created
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}snks_booking_transactions WHERE ai_session_id = %s",
        $appointment_id
    ));
    
    if ($transaction) {
        echo "<p class='success'>✅ Transaction found in database:</p>";
        echo "<ul>";
        echo "<li>Transaction ID: {$transaction->id}</li>";
        echo "<li>Amount: {$transaction->amount}</li>";
        echo "<li>Transaction Type: {$transaction->transaction_type}</li>";
        echo "<li>Transaction Time: {$transaction->transaction_time}</li>";
        echo "<li>AI Session Type: " . ($transaction->ai_session_type ?: 'NULL') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Transaction not found in database</p>";
    }
} else {
    echo "<p class='error'>❌ Profit transfer failed: {$result['message']}</p>";
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Debug Information</h3>";

// Check session action
$session_action = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
    $appointment_id
));

if ($session_action) {
    echo "<p>Session Action:</p>";
    echo "<ul>";
    echo "<li>AI Session Type: " . ($session_action->ai_session_type ?: 'NULL') . "</li>";
    echo "<li>Therapist ID: {$session_action->therapist_id}</li>";
    echo "<li>Patient ID: {$session_action->patient_id}</li>";
    echo "</ul>";
}

// Check order
$order = wc_get_order($session_action->case_id ?? 0);
if ($order) {
    echo "<p>Order:</p>";
    echo "<ul>";
    echo "<li>Is AI Session: " . ($order->get_meta('is_ai_session') ? 'Yes' : 'No') . "</li>";
    echo "<li>From Jalsah AI: " . ($order->get_meta('from_jalsah_ai') ? 'Yes' : 'No') . "</li>";
    echo "<li>Total: {$order->get_total()}</li>";
    echo "</ul>";
}

echo "</div>";
?>
