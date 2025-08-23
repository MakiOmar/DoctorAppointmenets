<?php
/**
 * Enhance AI Logging
 * 
 * Adds comprehensive logging to the AI booking process
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔧 Enhance AI Logging</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

echo "<div class='result info'>";
echo "<h3>Adding Comprehensive Logging to AI Booking Process</h3>";

// Add enhanced logging to the AI order completion function
$ai_integration_file = 'functions/ai-integration.php';
$content = file_get_contents($ai_integration_file);

// Enhanced logging for snks_handle_ai_order_completion
$enhanced_order_completion = '
function snks_handle_ai_order_completion( $order_id ) {
	error_log( "🔍 AI Order Completion Debug: Hook triggered for order_id = {$order_id}" );
	error_log( "🔍 AI Order Completion Debug: Hook function: snks_handle_ai_order_completion" );
	error_log( "🔍 AI Order Completion Debug: Current time: " . current_time("mysql") );
	
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		error_log( "❌ AI Order Completion Debug: Order not found for order_id = {$order_id}" );
		return;
	}
	
	error_log( "🔍 AI Order Completion Debug: Order found successfully" );
	error_log( "🔍 AI Order Completion Debug: Order status = " . $order->get_status() );
	error_log( "🔍 AI Order Completion Debug: Order total = " . $order->get_total() );
	error_log( "🔍 AI Order Completion Debug: Order customer ID = " . $order->get_customer_id() );
	
	// Check if this is an AI order (support both meta keys)
	$is_ai_session = $order->get_meta( "is_ai_session" );
	$from_jalsah_ai = $order->get_meta( "from_jalsah_ai" );
	
	error_log( "🔍 AI Order Completion Debug: Order meta - is_ai_session = " . ( $is_ai_session ? "Yes" : "No" ) . ", from_jalsah_ai = " . ( $from_jalsah_ai ? "Yes" : "No" ) );
	error_log( "🔍 AI Order Completion Debug: Raw meta values - is_ai_session = " . var_export($is_ai_session, true) . ", from_jalsah_ai = " . var_export($from_jalsah_ai, true) );
	
	if ( ! $is_ai_session && ! $from_jalsah_ai ) {
		error_log( "❌ AI Order Completion Debug: Not an AI order, exiting" );
		error_log( "❌ AI Order Completion Debug: Both meta keys are empty/false" );
		return;
	}
	
	error_log( "✅ AI Order Completion Debug: Confirmed AI order, proceeding with profit transfer" );
	
	// Get session ID from order meta
	$session_id = $order->get_meta( "ai_session_id" );
	error_log( "🔍 AI Order Completion Debug: Session ID from order meta = " . ( $session_id ?: "Not set" ) );
	
	if ( ! empty( $session_id ) ) {
		error_log( "🔍 AI Order Completion Debug: Calling snks_execute_ai_profit_transfer with session_id = {$session_id}" );
		
		// Check if function exists
		if ( ! function_exists( "snks_execute_ai_profit_transfer" ) ) {
			error_log( "❌ AI Order Completion Debug: snks_execute_ai_profit_transfer function not found!" );
			return;
		}
		
		// Trigger profit calculation
		$result = snks_execute_ai_profit_transfer( $session_id );
		
		error_log( "🔍 AI Order Completion Debug: Profit transfer result = " . print_r($result, true) );
		
		if ( $result["success"] ) {
			error_log( "✅ AI Profit Transfer from Order: Session ID {$session_id}, Transaction ID {$result["transaction_id"]}" );
		} else {
			error_log( "❌ AI Profit Transfer from Order Failed: Session ID {$session_id}, Reason: {$result["message"]}" );
		}
	} else {
		error_log( "❌ AI Order Completion Debug: No session ID found in order meta" );
		error_log( "❌ AI Order Completion Debug: All order meta keys: " . print_r($order->get_meta_data(), true) );
	}
}';

// Enhanced logging for snks_process_ai_order_status_change
$enhanced_status_change = '
function snks_process_ai_order_status_change($order_id, $old_status, $new_status) {
	error_log( "🔍 AI Order Status Change Debug: Hook triggered - order_id = {$order_id}, old_status = {$old_status}, new_status = {$new_status}" );
	error_log( "🔍 AI Order Status Change Debug: Hook function: snks_process_ai_order_status_change" );
	
	if (in_array($new_status, ["completed", "processing"])) {
		error_log( "🔍 AI Order Status Change Debug: Status is completed/processing, checking order" );
		
		$order = wc_get_order($order_id);
		
		if ($order) {
			error_log( "🔍 AI Order Status Change Debug: Order found successfully" );
			$is_ai_order = $order->get_meta("from_jalsah_ai");
			error_log( "🔍 AI Order Status Change Debug: from_jalsah_ai meta = " . var_export($is_ai_order, true) );
			
			if ($is_ai_order === "true" || $is_ai_order === true || $is_ai_order === "1" || $is_ai_order === 1) {
				error_log( "✅ AI Order Status Change Debug: Confirmed AI order, calling SNKS_AI_Orders::process_ai_order_payment" );
				SNKS_AI_Orders::process_ai_order_payment($order_id);
			} else {
				error_log( "❌ AI Order Status Change Debug: Not an AI order, skipping" );
			}
		} else {
			error_log( "❌ AI Order Status Change Debug: Order not found for order_id = {$order_id}" );
		}
	} else {
		error_log( "🔍 AI Order Status Change Debug: Status is not completed/processing, skipping" );
	}
}';

// Enhanced logging for snks_handle_ai_appointment_creation
$enhanced_appointment_creation = '
function snks_handle_ai_appointment_creation( $appointment_id, $appointment_data ) {
	error_log( "🔍 AI Appointment Creation Debug: Hook triggered - appointment_id = {$appointment_id}" );
	error_log( "🔍 AI Appointment Creation Debug: Hook function: snks_handle_ai_appointment_creation" );
	error_log( "🔍 AI Appointment Creation Debug: Appointment data = " . print_r($appointment_data, true) );
	
	// Check if this is an AI appointment
	if ( empty( $appointment_data["is_ai_session"] ) || ! $appointment_data["is_ai_session"] ) {
		error_log( "❌ AI Appointment Creation Debug: Not an AI appointment, is_ai_session is empty or false" );
		return;
	}
	
	error_log( "✅ AI Appointment Creation Debug: Confirmed AI appointment, proceeding" );
	
	// Get order ID from appointment
	$order_id = $appointment_data["order_id"] ?? 0;
	$therapist_id = $appointment_data["therapist_id"] ?? 0;
	$patient_id = $appointment_data["patient_id"] ?? 0;
	
	error_log( "🔍 AI Appointment Creation Debug: order_id = {$order_id}, therapist_id = {$therapist_id}, patient_id = {$patient_id}" );
	
	if ( $order_id && $therapist_id && $patient_id ) {
		error_log( "🔍 AI Appointment Creation Debug: All required data present, creating session action" );
		
		// Create session action record
		$session_action_id = snks_create_ai_session_action( $appointment_id, $order_id, $therapist_id, $patient_id );
		
		if ( $session_action_id ) {
			error_log( "✅ AI Appointment Creation Debug: Session action created successfully, ID = {$session_action_id}" );
			
			// Update order meta with session ID
			$order = wc_get_order( $order_id );
			if ( $order ) {
				error_log( "🔍 AI Appointment Creation Debug: Updating order meta" );
				$order->update_meta_data( "ai_session_id", $appointment_id );
				$order->update_meta_data( "ai_therapist_id", $therapist_id );
				$order->update_meta_data( "ai_user_id", $patient_id );
				$order->save();
				error_log( "✅ AI Appointment Creation Debug: Order meta updated successfully" );
			} else {
				error_log( "❌ AI Appointment Creation Debug: Failed to get order for order_id = {$order_id}" );
			}
		} else {
			error_log( "❌ AI Appointment Creation Debug: Failed to create session action" );
		}
	} else {
		error_log( "❌ AI Appointment Creation Debug: Missing required data - order_id = {$order_id}, therapist_id = {$therapist_id}, patient_id = {$patient_id}" );
	}
}';

// Replace the functions in the file
$content = preg_replace('/function snks_handle_ai_order_completion\([^)]*\)\s*\{[^}]*\}/s', $enhanced_order_completion, $content);
$content = preg_replace('/function snks_process_ai_order_status_change\([^)]*\)\s*\{[^}]*\}/s', $enhanced_status_change, $content);
$content = preg_replace('/function snks_handle_ai_appointment_creation\([^)]*\)\s*\{[^}]*\}/s', $enhanced_appointment_creation, $content);

// Write the enhanced content back to the file
if (file_put_contents($ai_integration_file, $content)) {
    echo "<p class='success'>✅ Enhanced logging added to AI integration functions</p>";
} else {
    echo "<p class='error'>❌ Failed to update AI integration file</p>";
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Create a new AI booking through the frontend</li>";
echo "<li>Change the order status to 'completed'</li>";
echo "<li>Check the debug logs at: <a href='debug-log-viewer.php'>Debug Log Viewer</a></li>";
echo "<li>Filter for 'AI Order' or 'AI Appointment' to see the detailed process</li>";
echo "</ol>";
echo "</div>";

echo "<div class='result success'>";
echo "<h3>🎯 What We're Logging Now</h3>";
echo "<ul>";
echo "<li>✅ All hook triggers with order/appointment IDs</li>";
echo "<li>✅ Order meta data and status</li>";
echo "<li>✅ Function calls and their results</li>";
echo "<li>✅ Success/failure of each step</li>";
echo "<li>✅ Detailed error messages</li>";
echo "</ul>";
echo "</div>";
?>
