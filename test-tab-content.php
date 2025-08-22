<?php
/**
 * Test Tab Content
 * 
 * This script will test the tab content rendering in WordPress admin context
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in admin
if (!is_admin()) {
    wp_die('This script must be run from admin context');
}

echo "<h1>Tab Content Test</h1>";

// Test the financial tab rendering
echo "<h2>Testing Financial Tab - Profit Settings</h2>";

// Include the admin file
require_once('functions/admin/ai-admin-enhanced.php');

// Test the function directly
if (function_exists('snks_render_financial_tab')) {
    echo "<div style='border: 2px solid blue; padding: 20px; margin: 20px 0;'>";
    echo "<h3>Direct Function Call:</h3>";
    
    ob_start();
    snks_render_financial_tab('profit-settings');
    $output = ob_get_clean();
    
    if (empty(trim($output))) {
        echo "<p style='color: red;'>❌ Function returned empty content</p>";
    } else {
        echo "<p style='color: green;'>✅ Function returned content (length: " . strlen($output) . ")</p>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>";
        echo "<h4>Content Preview:</h4>";
        echo htmlspecialchars(substr($output, 0, 1000));
        echo "...";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ Function snks_render_financial_tab not found</p>";
}

// Test the profit settings page function directly
echo "<h2>Testing Profit Settings Page Function</h2>";
if (function_exists('snks_profit_settings_page')) {
    echo "<div style='border: 2px solid green; padding: 20px; margin: 20px 0;'>";
    echo "<h3>Direct Profit Settings Page Call:</h3>";
    
    ob_start();
    snks_profit_settings_page();
    $output = ob_get_clean();
    
    if (empty(trim($output))) {
        echo "<p style='color: red;'>❌ Function returned empty content</p>";
    } else {
        echo "<p style='color: green;'>✅ Function returned content (length: " . strlen($output) . ")</p>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>";
        echo "<h4>Content Preview:</h4>";
        echo htmlspecialchars(substr($output, 0, 1000));
        echo "...";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ Function snks_profit_settings_page not found</p>";
}

// Test the include method
echo "<h2>Testing Include Method</h2>";
echo "<div style='border: 2px solid orange; padding: 20px; margin: 20px 0;'>";
echo "<h3>Include Method Test:</h3>";

$include_path = plugin_dir_path(__FILE__) . 'functions/admin/profit-settings.php';
if (file_exists($include_path)) {
    echo "<p style='color: green;'>✅ File exists: $include_path</p>";
    
    ob_start();
    include_once($include_path);
    if (function_exists('snks_profit_settings_page')) {
        snks_profit_settings_page();
    }
    $output = ob_get_clean();
    
    if (empty(trim($output))) {
        echo "<p style='color: red;'>❌ Include method returned empty content</p>";
    } else {
        echo "<p style='color: green;'>✅ Include method returned content (length: " . strlen($output) . ")</p>";
    }
} else {
    echo "<p style='color: red;'>❌ File not found: $include_path</p>";
}
echo "</div>";

echo "<h2>Test Complete</h2>";
?>
