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
    $therapist_id = intval($request['id']);
    
    // Check if therapist exists and has doctor role
    $therapist = get_user_by('ID', $therapist_id);
    if (!$therapist || !in_array('doctor', $therapist->roles)) {
        return new WP_Error('therapist_not_found', 'Therapist not found', ['status' => 404]);
    }

    // Get the therapist's application from custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'therapist_applications';
    
    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND status = 'approved'",
        $therapist_id
    ));

    if (!$application) {
        return new WP_Error('application_not_found', 'Therapist application not found', ['status' => 404]);
    }

    // Get certificates
    $certificates = !empty($application->certificates) ? json_decode($application->certificates, true) : [];
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
    
    error_log('SNKS Debug REST - Final therapist details: ' . print_r($therapist_details, true));

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