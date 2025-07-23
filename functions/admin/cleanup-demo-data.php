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
    
    if (!empty($demo_doctors)) {
        $results['details'][] = "Found " . count($demo_doctors) . " old demo doctors to remove";
        
        // Step 2: Remove existing demo reviews first
        foreach ($demo_doctors as $doctor) {
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'snks_therapist_diagnoses',
                array('therapist_id' => $doctor->ID),
                array('%d')
            );
            
            if ($deleted !== false) {
                $results['details'][] = "✅ Removed reviews for " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
            } else {
                $error_count++;
                $results['details'][] = "❌ Failed to remove reviews for " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
            }
        }
        
        // Step 3: Remove demo doctors completely
        foreach ($demo_doctors as $doctor) {
            $deleted = wp_delete_user($doctor->ID);
            
            if ($deleted) {
                $success_count++;
                $results['details'][] = "✅ Removed demo doctor: " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
            } else {
                $error_count++;
                $results['details'][] = "❌ Failed to remove demo doctor: " . get_user_meta($doctor->ID, 'billing_first_name', true) . " " . get_user_meta($doctor->ID, 'billing_last_name', true);
            }
        }
    } else {
        $results['details'][] = "No existing demo doctors found";
    }
    
    // Step 4: Create new demo doctors with Arabic content
    $results['details'][] = "Creating new demo doctors with Arabic content...";
    $create_result = snks_create_bulk_demo_doctors(5); // Create 5 new demo doctors
    
    if ($create_result['success']) {
        $results['details'][] = "✅ " . $create_result['message'];
        $success_count += 5;
    } else {
        $results['details'][] = "❌ " . $create_result['message'];
        $error_count++;
    }
    
    // Step 5: Create demo reviews for new doctors
    $results['details'][] = "Creating demo reviews with Arabic content...";
    $regenerate_result = snks_create_demo_reviews();
    
    if ($regenerate_result['success']) {
        $results['details'][] = "✅ " . $regenerate_result['message'];
    } else {
        $results['details'][] = "❌ " . $regenerate_result['message'];
        $error_count++;
    }
    
    // Step 6: Summary
    if ($error_count === 0) {
        $results['success'] = true;
        $results['message'] = "Successfully cleaned up old demo data and created new Arabic demo content. All ratings are properly capped at 5.0.";
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
                <li><strong>Delete all existing demo doctors and their reviews</strong> from the database</li>
                <li>Create 5 new demo doctors with Arabic names and content</li>
                <li>Generate new demo reviews with proper ratings (4.0-5.0) in Arabic</li>
                <li>Only affect demo data (real data is safe)</li>
            </ul>
            
            <p><strong>This action cannot be undone!</strong></p>
        </div>
        
        <div class="card">
            <h2>Why Cleanup Demo Data?</h2>
            <p>The existing demo data contains ratings above 5.0 (like 5.6, 5.8), which are illogical for a 5-star rating system. This cleanup will:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Remove all old demo doctors with English names</li>
                <li>Create new demo doctors with Arabic names and content</li>
                <li>Ensure all ratings are between 4.0 and 5.0</li>
                <li>Provide culturally appropriate Arabic demo data</li>
                <li>Fix the display issues on the frontend</li>
                <li>Make the demo more professional and realistic</li>
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