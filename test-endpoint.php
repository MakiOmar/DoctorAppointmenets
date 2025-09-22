<?php
// Test script to check if the user-diagnosis-results endpoint is working

// Simulate WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Test the rewrite rules
global $wp_rewrite;
$wp_rewrite->flush_rules();

echo "Rewrite rules flushed.\n";

// Check if the endpoint exists
$endpoint = get_query_var('ai_endpoint');
echo "Current ai_endpoint query var: " . ($endpoint ? $endpoint : 'not set') . "\n";

// Test the URL pattern
$test_url = '/api/ai/user-diagnosis-results';
echo "Testing URL: $test_url\n";

// Check if the rewrite rule matches
$rules = get_option('rewrite_rules');
echo "Rewrite rules containing 'user-diagnosis-results':\n";
foreach ($rules as $pattern => $replacement) {
    if (strpos($pattern, 'user-diagnosis-results') !== false) {
        echo "Pattern: $pattern -> $replacement\n";
    }
}

echo "\nDone.\n";
?>

