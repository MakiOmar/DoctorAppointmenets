<?php
/**
 * Therapist Details AJAX Handler
 * 
 * Handles AJAX requests for fetching therapist details from application data
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API endpoint for therapist details
 */
function snks_register_therapist_details_rest_route() {
    register_rest_route('jalsah-ai/v1', '/therapists/(?P<id>\d+)/details', [
        'methods' => 'GET',
        'callback' => 'snks_get_therapist_details_rest',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);
}
add_action('rest_api_init', 'snks_register_therapist_details_rest_route');

/**
 * REST API callback for therapist details
 */
function snks_get_therapist_details_rest($request) {
    // Handle both WP_REST_Request object and array
    if ($request instanceof WP_REST_Request) {
        $therapist_id = intval($request->get_param('id') ?: ($request['id'] ?? 0));
    } else {
        // Fallback for array access
        $therapist_id = intval($request['id'] ?? 0);
    }
    
    if (!$therapist_id) {
        return new WP_Error('invalid_therapist_id', 'Invalid therapist ID', ['status' => 400]);
    }
    
    // First, check if there's an application record with this user_id to get the therapist name
    global $wpdb;
    $table_name = $wpdb->prefix . 'therapist_applications';
    $application_check = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name, name_en, email, user_id, status FROM $table_name WHERE user_id = %d LIMIT 1",
        $therapist_id
    ));
    
    // Debug information
    $debug_info = [
        'therapist_id' => $therapist_id,
    ];
    
    // If application exists, include therapist name in debug info
    if ($application_check) {
        $debug_info['application_id'] = $application_check->id;
        $debug_info['therapist_name'] = $application_check->name ?: ($application_check->name_en ?: 'Unknown');
        $debug_info['therapist_email'] = $application_check->email ?: 'no email';
        $debug_info['application_status'] = $application_check->status;
        $therapist_name_for_error = $debug_info['therapist_name'];
    } else {
        $therapist_name_for_error = 'Unknown';
    }
    
    // Check if therapist exists and has doctor role
    $therapist = get_user_by('ID', $therapist_id);
    
    $debug_info['user_exists'] = $therapist ? true : false;
    
    if (!$therapist) {
        // User doesn't exist - include therapist name if we found it in application table
        $error_message = sprintf(
            'Therapist user not found. Therapist Name: %s, Searched for user ID: %d',
            $therapist_name_for_error,
            $therapist_id
        );
        
        // Add additional context if application exists
        if ($application_check) {
            $error_message .= sprintf(
                '. Application ID: %d exists in database but user ID %d does not exist in WordPress.',
                $application_check->id,
                $therapist_id
            );
        }
        
        return new WP_Error(
            'therapist_not_found', 
            $error_message,
            array_merge(['status' => 404], $debug_info)
        );
    }
    
    // Get user details for debugging
    $user_name = $therapist->display_name ?: ($therapist->user_login ?: 'Unknown');
    $user_roles = $therapist->roles ? implode(', ', $therapist->roles) : 'none';
    $user_email = $therapist->user_email ?: 'no email';
    
    $debug_info['user_name'] = $user_name;
    $debug_info['user_email'] = $user_email;
    $debug_info['user_roles'] = $user_roles;
    
    if (!in_array('doctor', $therapist->roles)) {
        // User exists but doesn't have doctor role
        $error_message = sprintf(
            'User found but does not have doctor role. User ID: %d, Name: %s, Email: %s, Roles: %s',
            $therapist_id,
            $user_name,
            $user_email,
            $user_roles
        );
        return new WP_Error(
            'therapist_not_found', 
            $error_message,
            array_merge(['status' => 404], $debug_info)
        );
    }

    // Get the therapist's application from custom table (we already checked above, but now get full record)
    // Use the global $wpdb that was already declared
    // First, check if any application exists (regardless of status)
    $any_application = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name, name_en, status, email FROM $table_name WHERE user_id = %d LIMIT 1",
        $therapist_id
    ));
    
    if ($any_application) {
        $debug_info['application_exists'] = true;
        $debug_info['application_status'] = $any_application->status;
        $debug_info['application_name'] = $any_application->name ?: ($any_application->name_en ?: 'Unknown');
        $debug_info['application_email'] = $any_application->email ?: 'no email';
    } else {
        $debug_info['application_exists'] = false;
    }
    
    // Now check for approved application
    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND status = 'approved'",
        $therapist_id
    ));

    if (!$application) {
        // Application not found or not approved
        if ($any_application) {
            $error_message = sprintf(
                'Therapist application found but not approved. User ID: %d, Name: %s, Application Status: %s. Application Name: %s, Application Email: %s',
                $therapist_id,
                $user_name,
                $any_application->status,
                $debug_info['application_name'],
                $debug_info['application_email']
            );
        } else {
            $error_message = sprintf(
                'Therapist application not found in database. User ID: %d, Name: %s, Email: %s',
                $therapist_id,
                $user_name,
                $user_email
            );
        }
        return new WP_Error(
            'application_not_found', 
            $error_message,
            array_merge(['status' => 404], $debug_info)
        );
    }
    
    // Add application name to debug info for successful lookups
    $debug_info['application_name'] = $application->name ?: ($application->name_en ?: 'Unknown');

    // Get certificates
    $certificates = !empty($application->certificates) ? json_decode($application->certificates, true) : [];
    
    
    $certificates_data = [];

    foreach ($certificates as $cert_id) {
        $attachment = get_post($cert_id);
        if (!$attachment) {
            continue;
        }

        $file_url = wp_get_attachment_url($cert_id);
        $file_type = get_post_mime_type($cert_id);
        
        // Get file extension
        $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
        
        // Determine if it's an image or document
        $is_image = wp_attachment_is_image($cert_id);
        
        // Get file size
        $attached_file = get_attached_file($cert_id);
        $file_size = filesize($attached_file);
        $file_size_formatted = size_format($file_size, 2);
        
        // Get upload date
        $upload_date = get_the_date('Y-m-d', $cert_id);
        
        $cert_data = [
            'id' => $cert_id,
            'name' => $attachment->post_title ?: basename($file_url),
            'description' => $attachment->post_content ?: '',
            'url' => $file_url,
            'thumbnail_url' => $is_image ? wp_get_attachment_image_url($cert_id, 'thumbnail') : '',
            'is_image' => $is_image,
            'file_type' => $file_type,
            'file_extension' => strtoupper($file_extension),
            'file_size' => $file_size_formatted,
            'upload_date' => $upload_date,
            'alt_text' => get_post_meta($cert_id, '_wp_attachment_image_alt', true) ?: ''
        ];
        
        $certificates_data[] = $cert_data;
    }

    // Sort certificates by upload date (newest first)
    usort($certificates_data, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });
    

    // Build therapist details
    $therapist_details = [
        'id' => $therapist_id,
        'name' => $application->name,
        'name_en' => $application->name_en,
        'email' => $application->email,
        'phone' => $application->phone,
        'whatsapp' => $application->whatsapp,
        'specialty' => $application->doctor_specialty,
        'profile_image' => $application->profile_image,
        'identity_front' => $application->identity_front,
        'identity_back' => $application->identity_back,
        'certificates' => is_array($certificates_data) ? $certificates_data : [],
        'jalsah_ai_name' => snks_get_jalsah_ai_name($therapist_id),
        'application_date' => date('Y-m-d', strtotime($application->created_at)),
        'approval_date' => date('Y-m-d', strtotime($application->updated_at))
    ];
    

    return [
        'success' => true,
        'data' => $therapist_details
    ];
}

/**
 * AJAX handler for fetching therapist details
 */
function snks_ajax_get_therapist_details() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'snks_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Get therapist ID
    $therapist_id = intval($_POST['therapist_id'] ?? 0);
    if (!$therapist_id) {
        wp_send_json_error(['message' => 'Invalid therapist ID']);
        return;
    }

    // Check if therapist exists and has doctor role
    $therapist = get_user_by('ID', $therapist_id);
    if (!$therapist || !in_array('doctor', $therapist->roles)) {
        wp_send_json_error(['message' => 'Therapist not found']);
        return;
    }

    // Get the therapist's application
    $application = get_posts([
        'post_type' => 'therapist_app',
        'post_author' => $therapist_id,
        'post_status' => 'publish',
        'numberposts' => 1
    ]);

    if (empty($application)) {
        wp_send_json_error(['message' => 'Therapist application not found']);
        return;
    }

    $app = $application[0];
    $meta = get_post_meta($app->ID);

    // Get certificates
    $certificates = isset($meta['certificates']) ? maybe_unserialize($meta['certificates'][0]) : [];
    $certificates_data = [];

    foreach ($certificates as $cert_id) {
        $attachment = get_post($cert_id);
        if (!$attachment) continue;

        $file_url = wp_get_attachment_url($cert_id);
        $file_type = get_post_mime_type($cert_id);
        
        // Get file extension
        $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
        
        // Determine if it's an image or document
        $is_image = wp_attachment_is_image($cert_id);
        
        // Get file size
        $file_size = filesize(get_attached_file($cert_id));
        $file_size_formatted = size_format($file_size, 2);
        
        // Get upload date
        $upload_date = get_the_date('Y-m-d', $cert_id);
        
        $certificates_data[] = [
            'id' => $cert_id,
            'name' => $attachment->post_title ?: basename($file_url),
            'description' => $attachment->post_content ?: '',
            'url' => $file_url,
            'thumbnail_url' => $is_image ? wp_get_attachment_image_url($cert_id, 'thumbnail') : '',
            'is_image' => $is_image,
            'file_type' => $file_type,
            'file_extension' => strtoupper($file_extension),
            'file_size' => $file_size_formatted,
            'upload_date' => $upload_date,
            'alt_text' => get_post_meta($cert_id, '_wp_attachment_image_alt', true) ?: ''
        ];
    }

    // Sort certificates by upload date (newest first)
    usort($certificates_data, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });

    // Build therapist details
    $therapist_details = [
        'id' => $therapist_id,
        'name' => $meta['name'][0] ?? '',
        'name_en' => $meta['name_en'][0] ?? '',
        'email' => $meta['email'][0] ?? '',
        'phone' => $meta['phone'][0] ?? '',
        'whatsapp' => $meta['whatsapp'][0] ?? '',
        'specialty' => $meta['doctor_specialty'][0] ?? '',
        'profile_image' => $meta['profile_image'][0] ?? '',
        'identity_front' => $meta['identity_front'][0] ?? '',
        'identity_back' => $meta['identity_back'][0] ?? '',
        'certificates' => is_array($certificates_data) ? $certificates_data : [],
        'jalsah_ai_name' => snks_get_jalsah_ai_name($therapist_id),
        'application_date' => get_the_date('Y-m-d', $app->ID),
        'approval_date' => get_the_modified_date('Y-m-d', $app->ID)
    ];

    wp_send_json_success([
        'data' => $therapist_details
    ]);
}

// Register AJAX actions
add_action('wp_ajax_get_therapist_details', 'snks_ajax_get_therapist_details');
add_action('wp_ajax_nopriv_get_therapist_details', 'snks_ajax_get_therapist_details'); 