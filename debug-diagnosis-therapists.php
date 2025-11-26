<?php
/**
 * Debug script to check diagnosis-therapist relationships
 * Run this in WordPress admin or via WP-CLI
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

global $wpdb;

echo "<h2>Debug: Diagnosis ID 6 - Therapist Relationships</h2>\n";

// Check if diagnosis ID 6 exists
$diagnosis = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}snks_diagnoses WHERE id = %d",
    6
));

if (!$diagnosis) {
    echo "<p style='color: red;'>❌ Diagnosis ID 6 does not exist in snks_diagnoses table!</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ Diagnosis ID 6 exists:</p>\n";
echo "<ul>\n";
echo "<li>ID: {$diagnosis->id}</li>\n";
echo "<li>Name (EN): {$diagnosis->name_en}</li>\n";
echo "<li>Name (AR): {$diagnosis->name_ar}</li>\n";
echo "<li>Description (EN): {$diagnosis->description_en}</li>\n";
echo "<li>Description (AR): {$diagnosis->description_ar}</li>\n";
echo "</ul>\n";

// Check therapist-diagnosis relationships
$relationships = $wpdb->get_results($wpdb->prepare(
    "SELECT td.*, ta.name as therapist_name, ta.status, ta.show_on_ai_site 
     FROM {$wpdb->prefix}snks_therapist_diagnoses td
     LEFT JOIN {$wpdb->prefix}therapist_applications ta ON td.therapist_id = ta.user_id
     WHERE td.diagnosis_id = %d",
    6
));

echo "<h3>Therapist-Diagnosis Relationships for Diagnosis ID 6:</h3>\n";

if (empty($relationships)) {
    echo "<p style='color: red;'>❌ No therapists are assigned to diagnosis ID 6!</p>\n";
} else {
    echo "<p style='color: green;'>✅ Found " . count($relationships) . " therapist(s) assigned to diagnosis ID 6:</p>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f0f0f0;'>\n";
    echo "<th>Therapist ID</th>\n";
    echo "<th>Therapist Name</th>\n";
    echo "<th>Status</th>\n";
    echo "<th>Show on AI Site</th>\n";
    echo "<th>Display Order</th>\n";
    echo "<th>Frontend Order</th>\n";
    echo "</tr>\n";
    
    foreach ($relationships as $rel) {
        $status_color = $rel->status === 'approved' ? 'green' : 'red';
        $show_color = $rel->show_on_ai_site == 1 ? 'green' : 'red';
        
        echo "<tr>\n";
        echo "<td>{$rel->therapist_id}</td>\n";
        echo "<td>{$rel->therapist_name}</td>\n";
        echo "<td style='color: {$status_color};'>{$rel->status}</td>\n";
        echo "<td style='color: {$show_color};'>{$rel->show_on_ai_site}</td>\n";
        echo "<td>{$rel->display_order}</td>\n";
        echo "<td>{$rel->frontend_order}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Check what the API endpoint would return
echo "<h3>API Endpoint Query Simulation:</h3>\n";

$api_query = $wpdb->prepare(
    "SELECT ta.*, td.display_order, td.frontend_order FROM {$wpdb->prefix}therapist_applications ta
    JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON ta.user_id = td.therapist_id
    WHERE td.diagnosis_id = %d AND ta.status = 'approved' AND ta.show_on_ai_site = 1
    ORDER BY td.frontend_order ASC, td.display_order ASC, ta.name ASC",
    6
);

echo "<p><strong>Query:</strong></p>\n";
echo "<pre>" . htmlspecialchars($api_query) . "</pre>\n";

$api_results = $wpdb->get_results($api_query);

if (empty($api_results)) {
    echo "<p style='color: red;'>❌ API endpoint would return no results!</p>\n";
    
    // Let's check why - check each condition separately
    echo "<h4>Debugging API Query Conditions:</h4>\n";
    
    // Check 1: Are there any relationships at all?
    $any_relationships = $wpdb->get_results($wpdb->prepare(
        "SELECT COUNT(*) as count FROM {$wpdb->prefix}snks_therapist_diagnoses WHERE diagnosis_id = %d",
        6
    ));
    echo "<p>1. Total relationships for diagnosis 6: {$any_relationships[0]->count}</p>\n";
    
    // Check 2: Are the therapists approved?
    $approved_therapists = $wpdb->get_results($wpdb->prepare(
        "SELECT td.therapist_id, ta.status 
         FROM {$wpdb->prefix}snks_therapist_diagnoses td
         JOIN {$wpdb->prefix}therapist_applications ta ON td.therapist_id = ta.user_id
         WHERE td.diagnosis_id = %d",
        6
    ));
    
    if (!empty($approved_therapists)) {
        echo "<p>2. Therapist approval status:</p>\n";
        echo "<ul>\n";
        foreach ($approved_therapists as $therapist) {
            $status_color = $therapist->status === 'approved' ? 'green' : 'red';
            echo "<li>Therapist {$therapist->therapist_id}: <span style='color: {$status_color};'>{$therapist->status}</span></li>\n";
        }
        echo "</ul>\n";
    }
    
    // Check 3: Are the therapists set to show on AI site?
    $ai_site_therapists = $wpdb->get_results($wpdb->prepare(
        "SELECT td.therapist_id, ta.show_on_ai_site 
         FROM {$wpdb->prefix}snks_therapist_diagnoses td
         JOIN {$wpdb->prefix}therapist_applications ta ON td.therapist_id = ta.user_id
         WHERE td.diagnosis_id = %d",
        6
    ));
    
    if (!empty($ai_site_therapists)) {
        echo "<p>3. AI site visibility status:</p>\n";
        echo "<ul>\n";
        foreach ($ai_site_therapists as $therapist) {
            $show_color = $therapist->show_on_ai_site == 1 ? 'green' : 'red';
            echo "<li>Therapist {$therapist->therapist_id}: <span style='color: {$show_color};'>{$therapist->show_on_ai_site}</span></li>\n";
        }
        echo "</ul>\n";
    }
    
} else {
    echo "<p style='color: green;'>✅ API endpoint would return " . count($api_results) . " result(s)</p>\n";
    echo "<ul>\n";
    foreach ($api_results as $result) {
        echo "<li>Therapist {$result->user_id}: {$result->name} (Status: {$result->status}, Show on AI: {$result->show_on_ai_site})</li>\n";
    }
    echo "</ul>\n";
}

echo "<hr>\n";
echo "<p><strong>Summary:</strong> The issue is likely that either:</p>\n";
echo "<ol>\n";
echo "<li>No therapists are assigned to diagnosis ID 6</li>\n";
echo "<li>The assigned therapists are not approved</li>\n";
echo "<li>The assigned therapists are not set to show on AI site</li>\n";
echo "</ol>\n";
?>
