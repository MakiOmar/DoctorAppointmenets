<?php
/**
 * Cleanup and Regenerate Demo Data
 * 
 * This script removes existing demo data and regenerates it with proper ratings
 * that are capped at 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function snks_cleanup_and_regenerate_demo_data() {
    global $wpdb;
    
    // Check if user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $results = array();
    $success_count = 0;
    $error_count = 0;
    
    // Step 1: Get all demo doctors
    $demo_doctors = get_users(array(
        'role' => 'doctor',
        'meta_query' => array(
            array(
                'key' => 'is_demo_doctor',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    if (empty($demo_doctors)) {
        return array('success' => false, 'message' => 'No demo doctors found.');
    }
    
    $results['details'][] = "Found " . count($demo_doctors) . " demo doctors";
    
    // Step 2: Remove existing demo reviews
    foreach ($demo_doctors as $doctor) {
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'snks_therapist_diagnoses',
            array('therapist_id' => $doctor->ID),
            array('%d')
        );
        
        if ($deleted !== false) {
            $success_count++;
            $results['details'][] = "✅ Removed existing reviews for " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
        } else {
            $error_count++;
            $results['details'][] = "❌ Failed to remove reviews for " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
        }
    }
    
    // Step 3: Regenerate demo reviews with proper ratings
    $regenerate_result = snks_create_demo_reviews();
    
    if ($regenerate_result['success']) {
        $results['details'][] = "✅ " . $regenerate_result['message'];
    } else {
        $results['details'][] = "❌ " . $regenerate_result['message'];
        $error_count++;
    }
    
    // Step 4: Summary
    if ($error_count === 0) {
        $results['success'] = true;
        $results['message'] = "Successfully cleaned up and regenerated demo data. All ratings are now properly capped at 5.0.";
    } else {
        $results['success'] = false;
        $results['message'] = "Completed with {$error_count} errors. Some data may need manual cleanup.";
    }
    
    return $results;
}

// Add admin page for cleanup
function snks_add_cleanup_demo_data_page() {
    add_menu_page(
        'Cleanup Demo Data',
        'Cleanup Demo Data',
        'manage_options',
        'cleanup-demo-data',
        'snks_cleanup_demo_data_page',
        'dashicons-admin-tools',
        30
    );
}
add_action('admin_menu', 'snks_add_cleanup_demo_data_page');

function snks_cleanup_demo_data_page() {
    $message = '';
    $message_type = '';
    
    // Handle form submission
    if (isset($_POST['action']) && $_POST['action'] === 'cleanup_demo_data') {
        if (wp_verify_nonce($_POST['_wpnonce'], 'cleanup_demo_data')) {
            $result = snks_cleanup_and_regenerate_demo_data();
            
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        } else {
            $message = 'Security check failed.';
            $message_type = 'error';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Cleanup and Regenerate Demo Data</h1>
        
        <?php if ($message): ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>⚠️ Important Notice</h2>
            <p>This action will:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><strong>Delete all existing demo reviews</strong> from the database</li>
                <li>Regenerate new demo reviews with proper ratings (4.0-5.0)</li>
                <li>Keep all demo doctors and their profiles</li>
                <li>Only affect demo data (real data is safe)</li>
            </ul>
            
            <p><strong>This action cannot be undone!</strong></p>
        </div>
        
        <div class="card">
            <h2>Why Cleanup Demo Data?</h2>
            <p>The existing demo data contains ratings above 5.0 (like 5.6, 5.8), which are illogical for a 5-star rating system. This cleanup will:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Ensure all ratings are between 4.0 and 5.0</li>
                <li>Provide more realistic demo data in Arabic</li>
                <li>Fix the display issues on the frontend</li>
                <li>Make the demo more professional and culturally appropriate</li>
                <li>Include Arabic names, specialties, and review messages</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Perform Cleanup</h2>
            <form method="post">
                <?php wp_nonce_field('cleanup_demo_data'); ?>
                <input type="hidden" name="action" value="cleanup_demo_data">
                
                <p>Click the button below to clean up and regenerate demo data:</p>
                
                <?php submit_button('Cleanup and Regenerate Demo Data', 'primary', 'submit', false, array('onclick' => 'return confirm("Are you sure you want to delete all existing demo reviews and regenerate them? This action cannot be undone.")')); ?>
            </form>
        </div>
    </div>
    <?php
} 