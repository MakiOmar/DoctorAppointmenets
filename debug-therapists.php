<?php
// Debug script to check therapist data
require_once('../../../wp-config.php');

global $wpdb;

echo "=== THERAPIST DEBUG ===\n";

// Check therapist_applications table
$table_name = $wpdb->prefix . 'therapist_applications';

echo "\n1. Total therapists in therapist_applications:\n";
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "Total: $total\n";

echo "\n2. Approved therapists:\n";
$approved = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
echo "Approved: $approved\n";

echo "\n3. Therapists with show_on_ai_site = 1:\n";
$ai_site = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE show_on_ai_site = 1");
echo "Show on AI site: $ai_site\n";

echo "\n4. Approved AND show_on_ai_site = 1:\n";
$both = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND show_on_ai_site = 1");
echo "Both conditions: $both\n";

echo "\n5. Sample of approved therapists:\n";
$samples = $wpdb->get_results("SELECT user_id, name, status, show_on_ai_site FROM $table_name WHERE status = 'approved' LIMIT 5");
foreach ($samples as $sample) {
    echo "ID: {$sample->user_id}, Name: {$sample->name}, Status: {$sample->status}, AI Site: {$sample->show_on_ai_site}\n";
}

echo "\n6. All statuses in table:\n";
$statuses = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table_name GROUP BY status");
foreach ($statuses as $status) {
    echo "Status: {$status->status}, Count: {$status->count}\n";
}

echo "\n7. All show_on_ai_site values:\n";
$ai_values = $wpdb->get_results("SELECT show_on_ai_site, COUNT(*) as count FROM $table_name GROUP BY show_on_ai_site");
foreach ($ai_values as $ai_value) {
    echo "show_on_ai_site: " . ($ai_value->show_on_ai_site ?? 'NULL') . ", Count: {$ai_value->count}\n";
}

echo "\n=== END DEBUG ===\n";
?>
