<?php
/**
 * Debug API Endpoints
 * 
 * This script helps debug API endpoint issues by testing various endpoints
 * and showing detailed information about requests and responses.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 API Endpoints Debug Tool</h1>";

// Test basic WordPress functionality
echo "<h2>📋 WordPress Environment Check</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
echo "<p><strong>Plugin Active:</strong> " . (is_plugin_active('DoctorAppointmenets/anony-shrinks.php') ? '✅ Yes' : '❌ No') . "</p>";

// Test rewrite rules
echo "<h2>🔄 Rewrite Rules Check</h2>";
$rewrite_rules = get_option('rewrite_rules');
$ai_rules = array_filter($rewrite_rules, function($rule, $pattern) {
    return strpos($pattern, 'api/ai') !== false;
}, ARRAY_FILTER_USE_BOTH);

if (empty($ai_rules)) {
    echo "<p style='color: red;'>❌ No AI API rewrite rules found!</p>";
    echo "<p>Flushing rewrite rules...</p>";
    flush_rewrite_rules();
    echo "<p>✅ Rewrite rules flushed. Please refresh this page.</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($ai_rules) . " AI API rewrite rules:</p>";
    foreach ($ai_rules as $pattern => $rule) {
        echo "<p><code>$pattern</code> → <code>$rule</code></p>";
    }
}

// Test CORS headers
echo "<h2>🌐 CORS Headers Test</h2>";
echo "<p>Testing CORS headers for API requests...</p>";

// Simulate API request
$_SERVER['REQUEST_URI'] = '/api/ai/ping';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture headers
ob_start();
$ai_integration = new SNKS_AI_Integration();
$ai_integration->handle_cors();
$cors_output = ob_get_clean();

echo "<p><strong>CORS Headers Output:</strong></p>";
echo "<pre>" . htmlspecialchars($cors_output) . "</pre>";

// Test API endpoints directly
echo "<h2>🔗 Direct API Endpoint Tests</h2>";

$endpoints_to_test = [
    'ping' => 'GET',
    'auth' => 'POST',
    'auth/register' => 'POST',
    'therapists' => 'GET',
    'diagnoses' => 'GET'
];

foreach ($endpoints_to_test as $endpoint => $method) {
    echo "<h3>Testing: $method /api/ai/$endpoint</h3>";
    
    // Set up request
    $_SERVER['REQUEST_URI'] = "/api/ai/$endpoint";
    $_SERVER['REQUEST_METHOD'] = $method;
    
    if ($method === 'POST') {
        // Simulate POST data
        $post_data = [
            'email' => 'test@example.com',
            'password' => 'testpassword'
        ];
        $_POST = $post_data;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
    }
    
    // Capture response
    ob_start();
    try {
        $ai_integration->handle_ai_requests();
        $response = ob_get_clean();
        echo "<p style='color: green;'>✅ Endpoint responded:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } catch (Exception $e) {
        $response = ob_get_clean();
        echo "<p style='color: red;'>❌ Endpoint error:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        if ($response) {
            echo "<p>Response output:</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    }
}

// Test JWT functionality
echo "<h2>🔐 JWT Token Test</h2>";
try {
    $test_user_id = 1; // Test with user ID 1
    $token = $ai_integration->generate_jwt_token($test_user_id);
    echo "<p style='color: green;'>✅ JWT token generated successfully</p>";
    echo "<p><strong>Token:</strong> " . substr($token, 0, 50) . "...</p>";
    
    // Test token verification
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
    $verified_user_id = $ai_integration->verify_jwt_token();
    echo "<p style='color: green;'>✅ JWT token verified successfully</p>";
    echo "<p><strong>Verified User ID:</strong> $verified_user_id</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ JWT error: " . $e->getMessage() . "</p>";
}

// Test email functionality
echo "<h2>📧 Email Functionality Test</h2>";
$test_email = 'test@example.com';
$test_code = '123456';

try {
    $email_sent = $ai_integration->send_verification_email(1, $test_code);
    if ($email_sent) {
        echo "<p style='color: green;'>✅ Email sent successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Email sending failed (might be server configuration)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email error: " . $e->getMessage() . "</p>";
}

// Test database connectivity
echo "<h2>🗄️ Database Connectivity Test</h2>";
global $wpdb;
try {
    $result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p><strong>Total Users:</strong> $result</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test user creation
echo "<h2>👤 User Creation Test</h2>";
try {
    $test_user_data = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'age' => 25,
        'email' => 'test' . time() . '@example.com',
        'phone' => '+1234567890',
        'whatsapp' => '+1234567890',
        'country' => 'Egypt',
        'password' => 'testpassword123'
    ];
    
    // Test registration
    $_POST = $test_user_data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/ai/auth/register';
    
    ob_start();
    $ai_integration->ai_register();
    $registration_response = ob_get_clean();
    
    echo "<p style='color: green;'>✅ Registration test completed</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($registration_response) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Registration error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔧 Environment Information</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>Plugin Directory:</strong> " . plugin_dir_path(__FILE__) . "</p>";
echo "<p><strong>Current Time:</strong> " . current_time('mysql') . "</p>";

echo "<h2>📝 Recommendations</h2>";
echo "<ul>";
echo "<li>Check if your server supports sending emails (SMTP configuration)</li>";
echo "<li>Ensure rewrite rules are properly configured</li>";
echo "<li>Verify that the JWT library is properly installed</li>";
echo "<li>Check server error logs for any PHP errors</li>";
echo "<li>Test with a simple endpoint first (like /api/ai/ping)</li>";
echo "</ul>";

echo "<h2>🧪 Manual Testing</h2>";
echo "<p>Test these URLs manually in your browser:</p>";
echo "<ul>";
echo "<li><a href='" . get_site_url() . "/api/ai/ping' target='_blank'>" . get_site_url() . "/api/ai/ping</a></li>";
echo "<li><a href='" . get_site_url() . "/api/ai/debug' target='_blank'>" . get_site_url() . "/api/ai/debug</a></li>";
echo "</ul>";

echo "<p><strong>Note:</strong> This debug script should be removed after testing.</p>";
?>
