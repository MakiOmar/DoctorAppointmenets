<?php
/**
 * Fix AI Order Meta
 * 
 * Fixes missing meta keys on AI orders to enable profit transfer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔧 Fix AI Order Meta</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

global $wpdb;

// Fix order ID 2412
$order_id = 2412;

echo "<div class='result info'>";
echo "<h3>Fixing AI Order Meta for Order ID: {$order_id}</h3>";

// Check if order exists
$order = wc_get_order($order_id);

if (!$order) {
    echo "<p class='error'>❌ Order not found for ID {$order_id}</p>";
    echo "</div>";
    return;
}

echo "<p>✅ Order found:</p>";
echo "<ul>";
echo "<li>Order ID: {$order_id}</li>";
echo "<li>Status: {$order->get_status()}</li>";
echo "<li>Total: {$order->get_total()}</li>";
echo "</ul>";

// Check current meta values
echo "<h4>Current Meta Values:</h4>";
$is_ai_session = $order->get_meta('is_ai_session');
$from_jalsah_ai = $order->get_meta('from_jalsah_ai');
$ai_session_id = $order->get_meta('ai_session_id');
$ai_therapist_id = $order->get_meta('ai_therapist_id');
$ai_user_id = $order->get_meta('ai_user_id');

echo "<ul>";
echo "<li>Is AI Session: " . ($is_ai_session ? 'Yes' : 'No') . "</li>";
echo "<li>From Jalsah AI: " . ($from_jalsah_ai ? 'Yes' : 'No') . "</li>";
echo "<li>AI Session ID: " . ($ai_session_id ?: 'Not set') . "</li>";
echo "<li>AI Therapist ID: " . ($ai_therapist_id ?: 'Not set') . "</li>";
echo "<li>AI User ID: " . ($ai_user_id ?: 'Not set') . "</li>";
echo "</ul>";

// Fix missing meta values
$fixes_applied = array();

// Fix is_ai_session if missing
if (!$is_ai_session) {
    $order->update_meta_data('is_ai_session', '1');
    $fixes_applied[] = 'is_ai_session = 1';
    echo "<p class='warning'>⚠️ Fixed: Set is_ai_session = 1</p>";
}

// Fix ai_session_id if missing (should be appointment ID 411)
if (!$ai_session_id) {
    $order->update_meta_data('ai_session_id', '411');
    $fixes_applied[] = 'ai_session_id = 411';
    echo "<p class='warning'>⚠️ Fixed: Set ai_session_id = 411</p>";
}

// Fix ai_therapist_id if missing (should be 115 based on previous info)
if (!$ai_therapist_id) {
    $order->update_meta_data('ai_therapist_id', '115');
    $fixes_applied[] = 'ai_therapist_id = 115';
    echo "<p class='warning'>⚠️ Fixed: Set ai_therapist_id = 115</p>";
}

// Fix ai_user_id if missing (should be customer ID)
if (!$ai_user_id) {
    $customer_id = $order->get_customer_id();
    $order->update_meta_data('ai_user_id', $customer_id);
    $fixes_applied[] = "ai_user_id = {$customer_id}";
    echo "<p class='warning'>⚠️ Fixed: Set ai_user_id = {$customer_id}</p>";
}

// Save the order if any fixes were applied
if (!empty($fixes_applied)) {
    $order->save();
    echo "<p class='success'>✅ Order meta updated successfully!</p>";
    echo "<p>Applied fixes:</p>";
    echo "<ul>";
    foreach ($fixes_applied as $fix) {
        echo "<li>{$fix}</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='info'>ℹ️ No fixes needed - all meta values are correct</p>";
}

// Show updated meta values
echo "<h4>Updated Meta Values:</h4>";
$is_ai_session = $order->get_meta('is_ai_session');
$from_jalsah_ai = $order->get_meta('from_jalsah_ai');
$ai_session_id = $order->get_meta('ai_session_id');
$ai_therapist_id = $order->get_meta('ai_therapist_id');
$ai_user_id = $order->get_meta('ai_user_id');

echo "<ul>";
echo "<li>Is AI Session: " . ($is_ai_session ? 'Yes' : 'No') . "</li>";
echo "<li>From Jalsah AI: " . ($from_jalsah_ai ? 'Yes' : 'No') . "</li>";
echo "<li>AI Session ID: " . ($ai_session_id ?: 'Not set') . "</li>";
echo "<li>AI Therapist ID: " . ($ai_therapist_id ?: 'Not set') . "</li>";
echo "<li>AI User ID: " . ($ai_user_id ?: 'Not set') . "</li>";
echo "</ul>";

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='test-order-completion-hook.php'>Test Order Completion Hook</a> - to see if the hook now works</li>";
echo "<li><a href='test-profit-transfer.php'>Test Profit Transfer Directly</a> - to test the profit transfer function</li>";
echo "<li><a href='debug-log-viewer.php'>Check Debug Logs</a> - to see the detailed process</li>";
echo "</ol>";
echo "</div>";

echo "<div class='result success'>";
echo "<h3>🎯 Expected Result</h3>";
echo "<p>After fixing the meta values, the order completion hook should:</p>";
echo "<ol>";
echo "<li>✅ Detect the order as an AI session</li>";
echo "<li>✅ Find the session ID (411)</li>";
echo "<li>✅ Trigger the profit transfer process</li>";
echo "<li>✅ Create a transaction record</li>";
echo "</ol>";
echo "</div>";
?>
