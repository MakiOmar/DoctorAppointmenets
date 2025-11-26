<?php
// Simple test to check if the diagnosis_results_limit setting is working
require_once('wp-config.php');

echo "Testing diagnosis_results_limit setting...\n\n";

// Check if the setting exists
$current_limit = get_option('snks_ai_diagnosis_results_limit', 'NOT_SET');
echo "Current diagnosis_results_limit: " . $current_limit . "\n";

// Try to set a test value
$test_value = 15;
update_option('snks_ai_diagnosis_results_limit', $test_value);
echo "Set diagnosis_results_limit to: " . $test_value . "\n";

// Check if it was saved
$saved_limit = get_option('snks_ai_diagnosis_results_limit', 'NOT_SET');
echo "Retrieved diagnosis_results_limit: " . $saved_limit . "\n";

// Test the API response
$settings = array(
    'bilingual_enabled' => true,
    'default_language' => 'ar',
    'site_title' => 'Test',
    'site_description' => 'Test',
    'ratings_enabled' => true,
    'diagnosis_search_by_name' => true,
    'diagnosis_results_limit' => intval(get_option('snks_ai_diagnosis_results_limit', 10)),
);

echo "\nAPI Response would be:\n";
echo json_encode($settings, JSON_PRETTY_PRINT) . "\n";

echo "\nTest completed.\n";
?>
