<?php
/**
 * Check Session Action
 * 
 * Checks if session action record exists and creates it if missing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔍 Check Session Action</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

global $wpdb;

// Check appointment ID 411
$appointment_id = 411;
$order_id = 2412;

echo "<div class='result info'>";
echo "<h3>Checking Session Action for Appointment ID: {$appointment_id}</h3>";

// Check if session action exists
$session_action = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
    $appointment_id
));

if ($session_action) {
    echo "<p class='success'>✅ Session action found:</p>";
    echo "<ul>";
    echo "<li>ID: {$session_action->ID}</li>";
    echo "<li>Case ID: {$session_action->case_id}</li>";
    echo "<li>Therapist ID: " . ($session_action->therapist_id ?: 'NULL') . "</li>";
    echo "<li>Patient ID: " . ($session_action->patient_id ?: 'NULL') . "</li>";
    echo "<li>AI Session Type: " . ($session_action->ai_session_type ?: 'NULL') . "</li>";
    echo "<li>Session Status: " . ($session_action->session_status ?: 'NULL') . "</li>";
    echo "<li>Attendance: {$session_action->attendance}</li>";
    echo "</ul>";
} else {
    echo "<p class='warning'>⚠️ No session action found for appointment ID {$appointment_id}</p>";
    
    // Get order details
    $order = wc_get_order($order_id);
    if ($order) {
        $therapist_id = $order->get_meta('ai_therapist_id') ?: $order->get_meta('therapist_id');
        $patient_id = $order->get_meta('ai_user_id') ?: $order->get_customer_id();
        
        echo "<h4>Creating Session Action Record:</h4>";
        echo "<ul>";
        echo "<li>Appointment ID: {$appointment_id}</li>";
        echo "<li>Order ID: {$order_id}</li>";
        echo "<li>Therapist ID: " . ($therapist_id ?: 'Not found') . "</li>";
        echo "<li>Patient ID: " . ($patient_id ?: 'Not found') . "</li>";
        echo "</ul>";
        
        if ($therapist_id && $patient_id) {
            // Create session action record
            $session_data = array(
                'action_session_id' => $appointment_id,
                'case_id' => $order_id,
                'therapist_id' => $therapist_id,
                'patient_id' => $patient_id,
                'ai_session_type' => NULL, // Will be calculated when profit is transferred
                'session_status' => 'open',
                'attendance' => 'yes',
                'created_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'snks_sessions_actions',
                $session_data,
                array('%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                echo "<p class='success'>✅ Session action created successfully! ID: {$wpdb->insert_id}</p>";
            } else {
                echo "<p class='error'>❌ Failed to create session action</p>";
                echo "<p>Database error: " . $wpdb->last_error . "</p>";
            }
        } else {
            echo "<p class='error'>❌ Cannot create session action - missing therapist or patient ID</p>";
        }
    } else {
        echo "<p class='error'>❌ Order not found</p>";
    }
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='fix-ai-order-meta.php'>Fix AI Order Meta</a> - to fix missing meta keys</li>";
echo "<li><a href='test-order-completion-hook.php'>Test Order Completion Hook</a> - to test the complete flow</li>";
echo "<li><a href='test-profit-transfer.php'>Test Profit Transfer Directly</a> - to test the profit transfer function</li>";
echo "</ol>";
echo "</div>";
?>
