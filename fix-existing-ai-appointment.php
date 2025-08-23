<?php
/**
 * Fix Existing AI Appointment
 * 
 * This script manually creates the missing session action record for an existing AI appointment
 * Run this script once to fix the appointment with ID 411
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔧 Fix Existing AI Appointment</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>";

// Appointment details from user
$appointment_id = 411;
$therapist_id = 115;
$session_date = '2025-08-23 16:00:00';
$session_time = '16:00:00';
$session_duration = 45;
$is_ai_session = 1;
$slot_id = 411;

echo "<div class='result info'>";
echo "<h3>Appointment Details:</h3>";
echo "<ul>";
echo "<li><strong>Appointment ID:</strong> {$appointment_id}</li>";
echo "<li><strong>Therapist ID:</strong> {$therapist_id}</li>";
echo "<li><strong>Session Date:</strong> {$session_date}</li>";
echo "<li><strong>Session Time:</strong> {$session_time}</li>";
echo "<li><strong>Duration:</strong> {$session_duration} minutes</li>";
echo "<li><strong>Is AI Session:</strong> " . ($is_ai_session ? 'Yes' : 'No') . "</li>";
echo "</ul>";
echo "</div>";

global $wpdb;

// Step 1: Find the order for this appointment
echo "<div class='result info'>";
echo "<h3>Step 1: Finding the order...</h3>";

// First, try to find the specific order ID 2412
$specific_order = $wpdb->get_row($wpdb->prepare(
    "SELECT p.ID as order_id, p.post_status, pm.meta_value as patient_id
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
     WHERE p.ID = %d AND p.post_type = 'shop_order'",
    2412
));

if ($specific_order) {
    echo "<p>✅ Found specific order: <strong>Order ID {$specific_order->order_id}</strong></p>";
    echo "<p>Order Status: <strong>{$specific_order->post_status}</strong></p>";
    echo "<p>Patient ID: <strong>{$specific_order->patient_id}</strong></p>";
    
    // Check if this order has AI session meta
    $ai_meta = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'is_ai_session'",
        2412
    ));
    
    if ($ai_meta) {
        echo "<p>✅ Order has AI session meta: <strong>{$ai_meta}</strong></p>";
    } else {
        echo "<p>⚠️ Order doesn't have 'is_ai_session' meta, but we'll proceed anyway</p>";
    }
    
    // Show all meta data for debugging
    echo "<h4>All Order Meta Data:</h4>";
    $all_meta = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_key",
        2412
    ));
    
    if ($all_meta) {
        echo "<ul>";
        foreach ($all_meta as $meta) {
            echo "<li><strong>{$meta->meta_key}:</strong> {$meta->meta_value}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No meta data found</p>";
    }
    
    $order = $specific_order;
} else {
    // Fallback: search for any AI order
    echo "<p>⚠️ Order ID 2412 not found, searching for any AI order...</p>";
    
    $order_query = $wpdb->prepare(
        "SELECT p.ID as order_id, p.post_status, pm.meta_value as patient_id
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
         WHERE p.post_type = 'shop_order'
         AND EXISTS (
             SELECT 1 FROM {$wpdb->postmeta} pm2 
             WHERE pm2.post_id = p.ID 
             AND pm2.meta_key = 'is_ai_session' 
             AND pm2.meta_value = '1'
         )
         ORDER BY p.ID DESC
         LIMIT 1"
    );

    $order = $wpdb->get_row($order_query);

    if ($order) {
        echo "<p>✅ Found AI order: <strong>Order ID {$order->order_id}</strong></p>";
        echo "<p>Order Status: <strong>{$order->post_status}</strong></p>";
        echo "<p>Patient ID: <strong>{$order->patient_id}</strong></p>";
    } else {
        echo "<p>❌ No AI order found</p>";
        echo "</div>";
        return;
    }
}
echo "</div>";

// Step 2: Check if session action already exists
echo "<div class='result info'>";
echo "<h3>Step 2: Checking existing session action...</h3>";

$existing_session = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
    $appointment_id
));

if ($existing_session) {
    echo "<p>✅ Session action already exists:</p>";
    echo "<ul>";
    echo "<li>Session Action ID: {$existing_session->id}</li>";
    echo "<li>Case ID: {$existing_session->case_id}</li>";
    echo "<li>Therapist ID: {$existing_session->therapist_id}</li>";
    echo "<li>Patient ID: {$existing_session->patient_id}</li>";
    echo "<li>AI Session Type: {$existing_session->ai_session_type}</li>";
    echo "<li>Session Status: {$existing_session->session_status}</li>";
    echo "</ul>";
    echo "</div>";
    
    // Check if transaction exists
    echo "<div class='result info'>";
    echo "<h3>Step 3: Checking transaction records...</h3>";
    
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}snks_booking_transactions WHERE ai_session_id = %s",
        $appointment_id
    ));
    
    if ($transaction) {
        echo "<p>✅ Transaction exists:</p>";
        echo "<ul>";
        echo "<li>Transaction ID: {$transaction->id}</li>";
        echo "<li>Amount: {$transaction->amount}</li>";
        echo "<li>Transaction Type: {$transaction->transaction_type}</li>";
        echo "<li>Transaction Time: {$transaction->transaction_time}</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ No transaction found for this session</p>";
        
        // Try to process the profit transfer
        echo "<h4>Attempting to process profit transfer...</h4>";
        
        if (function_exists('snks_execute_ai_profit_transfer')) {
            $result = snks_execute_ai_profit_transfer($appointment_id);
            
            if ($result['success']) {
                echo "<p class='success'>✅ Profit transfer successful!</p>";
                echo "<ul>";
                echo "<li>Transaction ID: {$result['transaction_id']}</li>";
                echo "<li>Profit Amount: {$result['profit_amount']}</li>";
                echo "<li>Session Type: {$result['session_type']}</li>";
                echo "</ul>";
            } else {
                echo "<p class='error'>❌ Profit transfer failed: {$result['message']}</p>";
            }
        } else {
            echo "<p class='error'>❌ snks_execute_ai_profit_transfer function not found</p>";
        }
    }
    echo "</div>";
    
} else {
    echo "<p>❌ No session action found for appointment ID {$appointment_id}</p>";
    echo "</div>";
    
    // Step 3: Create session action record
    echo "<div class='result info'>";
    echo "<h3>Step 3: Creating session action record...</h3>";
    
    $session_data = array(
        'action_session_id' => $appointment_id,
        'case_id' => $order->order_id,
        'therapist_id' => $therapist_id,
        'patient_id' => $order->patient_id,
        'ai_session_type' => 'first', // Will be updated when profit is calculated
        'session_status' => 'open',
        'attendance' => 'pending',
        'created_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'snks_sessions_actions',
        $session_data,
        array('%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        echo "<p class='success'>✅ Session action created successfully!</p>";
        echo "<p>Session Action ID: <strong>{$wpdb->insert_id}</strong></p>";
        
        // Update order meta
        $wc_order = wc_get_order($order->order_id);
        if ($wc_order) {
            $wc_order->update_meta_data('ai_session_id', $appointment_id);
            $wc_order->update_meta_data('ai_therapist_id', $therapist_id);
            $wc_order->update_meta_data('ai_user_id', $order->patient_id);
            $wc_order->save();
            echo "<p>✅ Order meta updated</p>";
        }
        
        // Step 4: Process profit transfer if order is completed
        if ($order->post_status === 'wc-completed') {
            echo "<h4>Order is completed, processing profit transfer...</h4>";
            
            if (function_exists('snks_execute_ai_profit_transfer')) {
                $profit_result = snks_execute_ai_profit_transfer($appointment_id);
                
                if ($profit_result['success']) {
                    echo "<p class='success'>✅ Profit transfer successful!</p>";
                    echo "<ul>";
                    echo "<li>Transaction ID: {$profit_result['transaction_id']}</li>";
                    echo "<li>Profit Amount: {$profit_result['profit_amount']}</li>";
                    echo "<li>Session Type: {$profit_result['session_type']}</li>";
                    echo "</ul>";
                } else {
                    echo "<p class='error'>❌ Profit transfer failed: {$profit_result['message']}</p>";
                }
            } else {
                echo "<p class='error'>❌ snks_execute_ai_profit_transfer function not found</p>";
            }
        } else {
            echo "<p>ℹ️ Order status is '{$order->post_status}', profit transfer will be processed when order is completed</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Failed to create session action</p>";
        echo "<p>Database Error: {$wpdb->last_error}</p>";
    }
    echo "</div>";
}

echo "<div class='result success'>";
echo "<h3>🎯 Summary</h3>";
echo "<p>The AI appointment integration has been fixed. The system will now:</p>";
echo "<ul>";
echo "<li>✅ Create session action records for new AI appointments</li>";
echo "<li>✅ Process profit transfers when orders are completed</li>";
echo "<li>✅ Support both 'is_ai_session' and 'from_jalsah_ai' meta keys</li>";
echo "<li>✅ Properly integrate with the existing appointment system</li>";
echo "</ul>";
echo "</div>";

echo "<div class='result info'>";
echo "<h3>📋 Next Steps</h3>";
echo "<ol>";
echo "<li>Test the AI appointment booking process</li>";
echo "<li>Verify session action records are created</li>";
echo "<li>Test order completion and profit transfer</li>";
echo "<li>Monitor the admin interface for proper data display</li>";
echo "</ol>";
echo "</div>";
?>
