<?php
/**
 * Test Appointment Hook
 * 
 * Manually triggers the appointment creation hook to test it
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🧪 Test Appointment Hook</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

echo "<div class='result info'>";
echo "<h3>Testing Appointment Creation Hook</h3>";

// Test with order ID 2417 (the recent AI order)
$order_id = 2417;

echo "<p>Testing with Order ID: {$order_id}</p>";

// Get order details
$order = wc_get_order($order_id);

if (!$order) {
    echo "<p class='error'>❌ Order not found for ID {$order_id}</p>";
    echo "</div>";
    return;
}

echo "<p class='success'>✅ Order found:</p>";
echo "<ul>";
echo "<li>Order ID: {$order_id}</li>";
echo "<li>Status: {$order->get_status()}</li>";
echo "<li>Total: {$order->get_total()}</li>";
echo "<li>Is AI Session: " . ($order->get_meta('is_ai_session') ? 'Yes' : 'No') . "</li>";
echo "<li>From Jalsah AI: " . ($order->get_meta('from_jalsah_ai') ? 'Yes' : 'No') . "</li>";
echo "</ul>";

// Check if the hook function exists
if (!function_exists('snks_handle_ai_appointment_creation')) {
    echo "<p class='error'>❌ snks_handle_ai_appointment_creation function not found</p>";
    echo "</div>";
    return;
}

echo "<p class='success'>✅ Hook function exists</p>";

// Create test appointment data
$appointment_id = 'test_' . time(); // Generate a unique test ID
$appointment_data = array(
    'is_ai_session' => true,
    'order_id' => $order_id,
    'therapist_id' => $order->get_meta('ai_therapist_id') ?: 115, // Default to 115 if not set
    'patient_id' => $order->get_meta('ai_user_id') ?: $order->get_customer_id(),
    'created_at' => current_time('mysql')
);

echo "<h4>Test Appointment Data:</h4>";
echo "<ul>";
echo "<li>Appointment ID: {$appointment_id}</li>";
echo "<li>Order ID: {$appointment_data['order_id']}</li>";
echo "<li>Therapist ID: {$appointment_data['therapist_id']}</li>";
echo "<li>Patient ID: {$appointment_data['patient_id']}</li>";
echo "<li>Is AI Session: " . ($appointment_data['is_ai_session'] ? 'Yes' : 'No') . "</li>";
echo "</ul>";

// Manually trigger the hook
echo "<h4>Triggering Appointment Creation Hook:</h4>";
snks_handle_ai_appointment_creation($appointment_id, $appointment_data);

echo "<p class='success'>✅ Hook triggered successfully!</p>";

// Check if session action was created
global $wpdb;
$session_action = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
    $appointment_id
));

if ($session_action) {
    echo "<p class='success'>✅ Session action created successfully!</p>";
    echo "<ul>";
    echo "<li>Session Action ID: {$session_action->ID}</li>";
    echo "<li>Case ID: {$session_action->case_id}</li>";
    echo "<li>Therapist ID: {$session_action->therapist_id}</li>";
    echo "<li>Patient ID: {$session_action->patient_id}</li>";
    echo "<li>AI Session Type: " . ($session_action->ai_session_type ?: 'NULL') . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Session action was not created</p>";
}

// Check if order meta was updated
$updated_order = wc_get_order($order_id);
$ai_session_id = $updated_order->get_meta('ai_session_id');
$ai_therapist_id = $updated_order->get_meta('ai_therapist_id');
$ai_user_id = $updated_order->get_meta('ai_user_id');

echo "<h4>Updated Order Meta:</h4>";
echo "<ul>";
echo "<li>AI Session ID: " . ($ai_session_id ?: 'Not set') . "</li>";
echo "<li>AI Therapist ID: " . ($ai_therapist_id ?: 'Not set') . "</li>";
echo "<li>AI User ID: " . ($ai_user_id ?: 'Not set') . "</li>";
echo "</ul>";

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Check the debug logs at: <a href='debug-log-viewer.php'>Debug Log Viewer</a></li>";
echo "<li>Filter for 'AI Appointment Creation' to see the hook execution</li>";
echo "<li>If the hook works, the issue is that it's not being triggered during real bookings</li>";
echo "<li>If the hook doesn't work, we need to fix the function</li>";
echo "</ol>";
echo "</div>";

echo "<div class='result success'>";
echo "<h3>🎯 Expected Result</h3>";
echo "<p>If the hook works correctly, you should see:</p>";
echo "<ul>";
echo "<li>✅ Session action record created</li>";
echo "<li>✅ Order meta updated with session ID</li>";
echo "<li>✅ Debug logs showing the process</li>";
echo "</ul>";
echo "</div>";
?>
