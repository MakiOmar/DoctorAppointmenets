<?php
/**
 * Admin Helper Functions
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Admin columns for therapist_application
add_filter('manage_therapist_app_posts_columns', function($columns) {
    $columns['status'] = __('Status');
    $columns['actions'] = __('Actions');
    return $columns;
});

add_action('manage_therapist_app_posts_custom_column', function($column, $post_id) {
    if ($column === 'status') {
        $status = get_post_status($post_id);
        $status_labels = [
            'pending' => '<span style="color: #f56e28;">Pending</span>',
            'publish' => '<span style="color: #46b450;">Approved</span>',
            'rejected' => '<span style="color: #dc3232;">Rejected</span>'
        ];
        echo $status_labels[$status] ?? $status;
    } elseif ($column === 'actions') {
        $status = get_post_status($post_id);
        if ($status === 'pending') {
            $url = wp_nonce_url(admin_url('edit.php?post_type=therapist_app&approve_therapist=' . $post_id), 'approve_therapist_' . $post_id);
            echo '<a href="' . $url . '" class="button button-small button-primary">Approve</a>';
        }
    }
}, 10, 2);

// Handle approve action
add_action('admin_init', function() {
    if (isset($_GET['approve_therapist']) && isset($_GET['_wpnonce'])) {
        $post_id = intval($_GET['approve_therapist']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'therapist_app' || $post->post_status !== 'pending') return;
        
        if (wp_verify_nonce($_GET['_wpnonce'], 'approve_therapist_' . $post_id)) {
            // Get application data
            $meta = get_post_meta($post_id);
            $email = $meta['email'][0] ?? '';
            $phone = $meta['phone'][0] ?? '';
            $name = $meta['name'][0] ?? '';
            $name_en = $meta['name_en'][0] ?? '';
            $whatsapp = $meta['whatsapp'][0] ?? '';
            $specialty = $meta['doctor_specialty'][0] ?? '';
            $profile_image = $meta['profile_image'][0] ?? '';
            $identity_front = $meta['identity_front'][0] ?? '';
            $identity_back = $meta['identity_back'][0] ?? '';
            $certificates = isset($meta['certificates']) ? maybe_unserialize($meta['certificates'][0]) : [];
            $mode = $meta['password_mode'][0] ?? 'auto';
            $password = $meta['password'][0] ?? '';
            
            if ($mode === 'auto' || empty($password)) {
                $password = wp_generate_password(8, false);
            }
            
            // Check if user already exists
            $existing_user = get_user_by('email', $email);
            if ($existing_user) {
                $user_id = $existing_user->ID;
            } else {
                // Create new user
                if (username_exists($phone)) {
                    return;
                }
                $user_id = wp_create_user($phone, $password, $email);
                if (is_wp_error($user_id)) {
                    return;
                }
            }
            
            $user = get_user_by('id', $user_id);
            $user->set_role('doctor');
            
            // Set Jalsah AI name using the therapist's name
            $jalsah_ai_name = !empty($name) ? $name : $name_en;
            update_user_meta($user_id, 'jalsah_ai_name', $jalsah_ai_name);
            
            // Update user meta with application data
            update_user_meta($user_id, 'billing_first_name', $name);
            update_user_meta($user_id, 'billing_last_name', $name_en);
            update_user_meta($user_id, 'billing_phone', $phone);
            update_user_meta($user_id, 'billing_email', $email);
            update_user_meta($user_id, 'whatsapp', $whatsapp);
            update_user_meta($user_id, 'doctor_specialty', $specialty);
            update_user_meta($user_id, 'profile_image', $profile_image);
            update_user_meta($user_id, 'identity_front', $identity_front);
            update_user_meta($user_id, 'identity_back', $identity_back);
            update_user_meta($user_id, 'certificates', $certificates);
            
            // Mark application as approved and set the author to the created user
            wp_update_post([
                'ID' => $post_id,
                'post_status' => 'publish',
                'post_author' => $user_id
            ]);
            
            // Notify user
            $email_subject = __('Your therapist application is approved');
            $email_message = sprintf(
                __('Your account has been created successfully!') . "\n\n" .
                __('Username: %s') . "\n" .
                __('Password: %s') . "\n" .
                __('Jalsah AI Name: %s') . "\n\n" .
                __('You can now log in to your account and start using the platform.'),
                $phone,
                $password,
                $jalsah_ai_name
            );
            wp_mail($email, $email_subject, $email_message);
            
            wp_redirect(admin_url('edit.php?post_type=therapist_app&approved=1'));
            exit;
        }
    }
});

/**
 * Get Jalsah AI name for a therapist
 * 
 * @param int $user_id The user ID
 * @return string The Jalsah AI name
 */
function snks_get_jalsah_ai_name($user_id) {
    $jalsah_ai_name = get_user_meta($user_id, 'jalsah_ai_name', true);
    
    // If Jalsah AI name is not set, fall back to first_name or display_name
    if (empty($jalsah_ai_name)) {
        $user = get_user_by('id', $user_id);
        if ($user) {
            $jalsah_ai_name = get_user_meta($user_id, 'first_name', true);
            if (empty($jalsah_ai_name)) {
                $jalsah_ai_name = $user->display_name;
            }
        }
    }
    
    return $jalsah_ai_name;
}

/**
 * Set Jalsah AI name for a therapist
 * 
 * @param int $user_id The user ID
 * @param string $name The Jalsah AI name
 */
function snks_set_jalsah_ai_name($user_id, $name) {
    update_user_meta($user_id, 'jalsah_ai_name', sanitize_text_field($name));
} 