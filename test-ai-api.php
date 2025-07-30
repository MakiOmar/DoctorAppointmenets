<?php
/**
 * Test AI API Endpoints
 * 
 * This file helps test if the AI API endpoints are working correctly
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>AI API Endpoint Test</h1>";

// Test 1: Check if rewrite rules are working
echo "<h2>1. Testing Rewrite Rules</h2>";
$rewrite_rules = get_option('rewrite_rules');
$ai_rules = array_filter($rewrite_rules, function($rule, $pattern) {
    return strpos($pattern, 'api/ai') !== false;
}, ARRAY_FILTER_USE_BOTH);

if (empty($ai_rules)) {
    echo "<p style='color: red;'>❌ No AI API rewrite rules found!</p>";
    echo "<p>This means the rewrite rules need to be flushed.</p>";
} else {
    echo "<p style='color: green;'>✅ AI API rewrite rules found:</p>";
    echo "<ul>";
    foreach ($ai_rules as $pattern => $rule) {
        echo "<li><strong>$pattern</strong> → $rule</li>";
    }
    echo "</ul>";
}

// Test 2: Check if AI Integration class is loaded
echo "<h2>2. Testing AI Integration Class</h2>";
if (class_exists('SNKS_AI_Integration')) {
    echo "<p style='color: green;'>✅ SNKS_AI_Integration class exists</p>";
} else {
    echo "<p style='color: red;'>❌ SNKS_AI_Integration class not found</p>";
}

// Test 3: Check query vars
echo "<h2>3. Testing Query Vars</h2>";
$query_vars = get_query_vars();
if (isset($query_vars['ai_endpoint'])) {
    echo "<p style='color: green;'>✅ ai_endpoint query var is registered</p>";
} else {
    echo "<p style='color: red;'>❌ ai_endpoint query var not found</p>";
}

// Test 4: Manual endpoint test
echo "<h2>4. Manual Endpoint Test</h2>";
echo "<p>Try accessing these URLs in your browser:</p>";
echo "<ul>";
echo "<li><a href='/api/ai/ping' target='_blank'>/api/ai/ping</a></li>";
echo "<li><a href='/api/ai/test' target='_blank'>/api/ai/test</a></li>";
echo "<li><a href='/api/ai/debug' target='_blank'>/api/ai/debug</a></li>";
echo "</ul>";

// Test 5: Flush rewrite rules
echo "<h2>5. Flush Rewrite Rules</h2>";
echo "<form method='post'>";
echo "<input type='submit' name='flush_rules' value='Flush Rewrite Rules' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
echo "</form>";

if (isset($_POST['flush_rules'])) {
    flush_rewrite_rules();
    echo "<p style='color: green;'>✅ Rewrite rules flushed successfully!</p>";
    echo "<p>Please refresh this page to see the updated rules.</p>";
}

// Test 6: Check if the AI integration is instantiated
echo "<h2>6. Check AI Integration Instance</h2>";
global $wp_filter;
if (isset($wp_filter['rest_api_init'])) {
    echo "<p style='color: green;'>✅ REST API hooks are registered</p>";
} else {
    echo "<p style='color: red;'>❌ No REST API hooks found</p>";
}

echo "<h2>7. Debug Information</h2>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
echo "<p><strong>Home URL:</strong> " . get_home_url() . "</p>";
echo "<p><strong>Plugin URL:</strong> " . plugins_url() . "</p>";

echo "<h2>8. Next Steps</h2>";
echo "<ol>";
echo "<li>If rewrite rules are missing, click 'Flush Rewrite Rules' above</li>";
echo "<li>Test the manual endpoints in your browser</li>";
echo "<li>Check your browser's developer tools for any JavaScript errors</li>";
echo "<li>Verify that your frontend is making requests to the correct URL</li>";
echo "</ol>";
?> 