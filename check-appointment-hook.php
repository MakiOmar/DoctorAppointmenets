<?php
/**
 * Check Appointment Hook
 * 
 * Checks if the appointment creation hook is being triggered
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>🔍 Check Appointment Hook</h1>";
echo "<style>
    .result { margin: 20px 0; padding: 15px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
    .warning { background: #fff3cd; color: #856404; }
</style>";

echo "<div class='result info'>";
echo "<h3>Checking Appointment Creation Hook</h3>";

// Check if the hook is registered
$hooks = $GLOBALS['wp_filter']['snks_appointment_created'] ?? null;

if ($hooks) {
    echo "<p class='success'>✅ Hook 'snks_appointment_created' is registered</p>";
    echo "<p>Number of callbacks: " . count($hooks->callbacks) . "</p>";
    
    foreach ($hooks->callbacks as $priority => $callbacks) {
        echo "<p>Priority {$priority}:</p>";
        foreach ($callbacks as $callback) {
            echo "<ul>";
            echo "<li>Function: " . (is_array($callback['function']) ? get_class($callback['function'][0]) . '::' . $callback['function'][1] : $callback['function']) . "</li>";
            echo "<li>Accepted Args: {$callback['accepted_args']}</li>";
            echo "</ul>";
        }
    }
} else {
    echo "<p class='error'>❌ Hook 'snks_appointment_created' is NOT registered</p>";
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Checking Recent Appointments</h3>";

global $wpdb;

// Check for recent appointments
$recent_appointments = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
    WHERE client_id > 0 AND order_id > 0
    ORDER BY date_time DESC 
    LIMIT 5
");

if ($recent_appointments) {
    echo "<p class='success'>✅ Found recent appointments:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Order ID</th><th>Therapist ID</th><th>Patient ID</th><th>Session Status</th><th>Date Time</th><th>Settings</th></tr>";
    
    foreach ($recent_appointments as $appointment) {
        echo "<tr>";
        echo "<td>{$appointment->ID}</td>";
        echo "<td>{$appointment->order_id}</td>";
        echo "<td>{$appointment->user_id}</td>";
        echo "<td>{$appointment->client_id}</td>";
        echo "<td>{$appointment->session_status}</td>";
        echo "<td>{$appointment->date_time}</td>";
        echo "<td>{$appointment->settings}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No recent appointments found</p>";
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Checking Session Actions</h3>";

// Check for session actions
$session_actions = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}snks_sessions_actions 
    ORDER BY created_at DESC 
    LIMIT 5
");

if ($session_actions) {
    echo "<p class='success'>✅ Found session actions:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Session ID</th><th>Case ID</th><th>Therapist ID</th><th>Patient ID</th><th>AI Session Type</th><th>Created At</th></tr>";
    
    foreach ($session_actions as $action) {
        echo "<tr>";
        echo "<td>{$action->ID}</td>";
        echo "<td>{$action->action_session_id}</td>";
        echo "<td>{$action->case_id}</td>";
        echo "<td>" . ($action->therapist_id ?: 'NULL') . "</td>";
        echo "<td>" . ($action->patient_id ?: 'NULL') . "</td>";
        echo "<td>" . ($action->ai_session_type ?: 'NULL') . "</td>";
        echo "<td>{$action->created_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No session actions found</p>";
}

echo "</div>";

echo "<div class='result info'>";
echo "<h3>Manual Test</h3>";
echo "<p>To test if the hook works, you can manually trigger it:</p>";
echo "<a href='test-appointment-hook.php' class='button button-primary'>Test Appointment Hook</a>";
echo "</div>";
?>
