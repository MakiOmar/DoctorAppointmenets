<?php
/**
 * Test Reviews Script
 * 
 * This script tests the reviews system and rating calculations
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Reviews System Test</h1>";

global $wpdb;

// 1. Check if reviews exist
echo "<h2>1. Checking Reviews in Database</h2>";
$reviews = $wpdb->get_results("
    SELECT td.*, d.name as diagnosis_name, 
           CONCAT(u1.meta_value, ' ', u2.meta_value) as therapist_name
    FROM {$wpdb->prefix}snks_therapist_diagnoses td
    JOIN {$wpdb->prefix}snks_diagnoses d ON td.diagnosis_id = d.id
    JOIN {$wpdb->users} u ON td.therapist_id = u.ID
    JOIN {$wpdb->usermeta} u1 ON u.ID = u1.user_id AND u1.meta_key = 'billing_first_name'
    JOIN {$wpdb->usermeta} u2 ON u.ID = u2.user_id AND u2.meta_key = 'billing_last_name'
    LIMIT 10
");

if ($reviews) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Therapist</th><th>Diagnosis</th><th>Rating</th><th>Rating Type</th><th>Message</th></tr>";
    foreach ($reviews as $review) {
        echo "<tr>";
        echo "<td>" . esc_html($review->therapist_name) . "</td>";
        echo "<td>" . esc_html($review->diagnosis_name) . "</td>";
        echo "<td>" . esc_html($review->rating) . "</td>";
        echo "<td>" . gettype($review->rating) . "</td>";
        echo "<td>" . esc_html($review->suitability_message) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No reviews found in database.</p>";
}

// 2. Test rating calculation for a specific therapist
echo "<h2>2. Testing Rating Calculation</h2>";
$demo_doctors = get_users(array(
    'role' => 'doctor',
    'meta_query' => array(
        array(
            'key' => 'is_demo_doctor',
            'value' => '1',
            'compare' => '='
        )
    ),
    'number' => 1
));

if ($demo_doctors) {
    $therapist = $demo_doctors[0];
    $therapist_id = $therapist->ID;
    $therapist_name = get_user_meta($therapist_id, 'billing_first_name', true) . ' ' . get_user_meta($therapist_id, 'billing_last_name', true);
    
    echo "<p>Testing for therapist: <strong>" . esc_html($therapist_name) . "</strong> (ID: {$therapist_id})</p>";
    
    // Get diagnoses with ratings
    $diagnoses = $wpdb->get_results($wpdb->prepare("
        SELECT d.*, td.rating, td.suitability_message_en, td.suitability_message_ar 
        FROM {$wpdb->prefix}snks_diagnoses d
        JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON d.id = td.diagnosis_id
        WHERE td.therapist_id = %d
    ", $therapist_id));
    
    if ($diagnoses) {
        echo "<h3>Diagnoses with Ratings:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Diagnosis</th><th>Rating</th><th>Rating Type</th><th>Valid Rating?</th></tr>";
        
        $valid_ratings = array();
        foreach ($diagnoses as $diagnosis) {
            $rating = floatval($diagnosis->rating);
            $is_valid = $rating && !is_nan($rating) && $rating > 0;
            
            if ($is_valid) {
                $valid_ratings[] = $rating;
            }
            
            echo "<tr>";
            echo "<td>" . esc_html($diagnosis->name) . "</td>";
            echo "<td>" . esc_html($diagnosis->rating) . " (floatval: " . $rating . ")</td>";
            echo "<td>" . gettype($diagnosis->rating) . "</td>";
            echo "<td>" . ($is_valid ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($valid_ratings) {
            $average = array_sum($valid_ratings) / count($valid_ratings);
            echo "<p><strong>Valid Ratings:</strong> " . implode(', ', $valid_ratings) . "</p>";
            echo "<p><strong>Average Rating:</strong> " . number_format($average, 2) . "</p>";
        } else {
            echo "<p><strong>No valid ratings found!</strong></p>";
        }
    } else {
        echo "<p>No diagnoses found for this therapist.</p>";
    }
} else {
    echo "<p>No demo doctors found.</p>";
}

// 3. Test API endpoint
echo "<h2>3. Testing API Endpoint</h2>";
echo "<p>API URL: <a href='/wp-json/jalsah-ai/v1/therapists' target='_blank'>/wp-json/jalsah-ai/v1/therapists</a></p>";

// 4. Check if reviews were created
echo "<h2>4. Review Creation Status</h2>";
$total_reviews = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_therapist_diagnoses");
$demo_doctors_count = count(get_users(array(
    'role' => 'doctor',
    'meta_query' => array(
        array(
            'key' => 'is_demo_doctor',
            'value' => '1',
            'compare' => '='
        )
    )
)));

echo "<p>Total reviews in database: <strong>{$total_reviews}</strong></p>";
echo "<p>Demo doctors count: <strong>{$demo_doctors_count}</strong></p>";

if ($total_reviews > 0) {
    echo "<p>✅ Reviews exist in database</p>";
} else {
    echo "<p>❌ No reviews found. Please run the 'Create Demo Reviews' function in the admin.</p>";
}

echo "<hr>";
echo "<p><a href='/wp-admin/admin.php?page=demo-doctors-manager'>Go to Demo Doctors Manager</a></p>";
?> 