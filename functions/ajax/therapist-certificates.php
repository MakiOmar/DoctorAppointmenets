<?php
/**
 * Therapist Certificates AJAX Handler
 * 
 * Handles AJAX requests for fetching therapist certificates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for fetching therapist certificates
 */
function snks_ajax_get_therapist_certificates() {
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

    // Get certificates from user meta
    $certificates = get_user_meta($therapist_id, 'certificates', true);
    
    // If certificates is a string (serialized), unserialize it
    if (is_string($certificates)) {
        $certificates = maybe_unserialize($certificates);
    }
    
    // Ensure certificates is an array
    if (!is_array($certificates)) {
        $certificates = [];
    }

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

    wp_send_json_success([
        'data' => $certificates_data,
        'count' => count($certificates_data)
    ]);
}

// Register AJAX actions
add_action('wp_ajax_get_therapist_certificates', 'snks_ajax_get_therapist_certificates');
add_action('wp_ajax_nopriv_get_therapist_certificates', 'snks_ajax_get_therapist_certificates');

/**
 * REST API endpoint for therapist certificates
 */
function snks_register_therapist_certificates_rest_route() {
    register_rest_route('jalsah-ai/v1', '/therapists/(?P<id>\d+)/certificates', [
        'methods' => 'GET',
        'callback' => 'snks_get_therapist_certificates_rest',
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
add_action('rest_api_init', 'snks_register_therapist_certificates_rest_route');

/**
 * REST API callback for therapist certificates
 */
function snks_get_therapist_certificates_rest($request) {
    $therapist_id = intval($request['id']);
    
    // Debug logging
    error_log("Certificates Debug: Requested therapist ID: " . $therapist_id);
    
    // Check if therapist exists and has doctor role
    $therapist = get_user_by('ID', $therapist_id);
    error_log("Certificates Debug: Therapist user object: " . print_r($therapist, true));
    if ($therapist) {
        error_log("Certificates Debug: Therapist roles: " . print_r($therapist->roles, true));
    }
    if (!$therapist || !in_array('doctor', $therapist->roles)) {
        error_log("Certificates Debug: Therapist not found or not a doctor. User: " . print_r($therapist, true));
        return new WP_Error('therapist_not_found', 'Therapist not found', ['status' => 404]);
    }
    
    error_log("Certificates Debug: Therapist found: " . $therapist->display_name);

    // Get certificates from therapist_applications table
    global $wpdb;
    $table_name = $wpdb->prefix . 'therapist_applications';
    
    $application = $wpdb->get_row($wpdb->prepare(
        "SELECT certificates FROM $table_name WHERE user_id = %d AND status = 'approved'",
        $therapist_id
    ));
        
    if (!$application) {
        $certificates = [];
    } else {
        // Parse certificates from JSON
        $certificates = !empty($application->certificates) ? json_decode($application->certificates, true) : [];

    }
    
    // Ensure certificates is an array
    if (!is_array($certificates)) {
        $certificates = [];
    }
    
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

    return [
        'success' => true,
        'data' => $certificates_data,
        'count' => count($certificates_data)
    ];
} 