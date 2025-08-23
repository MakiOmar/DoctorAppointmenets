<?php
/**
 * Test Order Completion Hook
 * 
 * Manually triggers the WooCommerce order completion hook to test AI profit transfer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🧪 Test Order Completion Hook</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>";

global $wpdb;

// Test with order ID 2412 (the AI order)
$order_id = 2412;

echo "<div class='result info'>";
echo "<h3>Testing Order Completion Hook for Order ID: {$order_id}</h3>";

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
echo "<li>Is AI Session: " . ($order->get_meta('is_ai_session') ? 'Yes' : 'No') . "</li>";
echo "<li>From Jalsah AI: " . ($order->get_meta('from_jalsah_ai') ? 'Yes' : 'No') . "</li>";
echo "<li>AI Session ID: " . ($order->get_meta('ai_session_id') ?: 'Not set') . "</li>";
echo "</ul>";

// Check if the hook function exists
if (!function_exists('snks_handle_ai_order_completion')) {
    echo "<p class='error'>❌ snks_handle_ai_order_completion function not found</p>";
    echo "</div>";
    return;
}

echo "<p>✅ Hook function exists, testing...</p>";

// Manually trigger the hook
echo "<h4>Triggering Order Completion Hook:</h4>";
snks_handle_ai_order_completion($order_id);

echo "<p class='success'>✅ Hook triggered successfully!</p>";
echo "<p>Check the debug logs to see what happened.</p>";

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Check the debug logs at: <a href='debug-log-viewer.php' target='_blank'>Debug Log Viewer</a></li>";
echo "<li>Filter for 'AI Order Completion' to see the hook execution</li>";
echo "<li>If the hook is working, you should see profit transfer logs</li>";
echo "<li>If not, check if the order has the correct meta keys</li>";
echo "</ol>";
echo "</div>";

echo "<div class='result info'>";
echo "<h3>Manual Test Alternative</h3>";
echo "<p>If the hook doesn't work, you can also test the profit transfer directly:</p>";
echo "<a href='test-profit-transfer.php' class='button button-primary'>Test Profit Transfer Directly</a>";
echo "</div>";
?>
