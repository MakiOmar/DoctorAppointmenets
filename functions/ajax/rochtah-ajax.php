<?php
/**
 * Rochtah AJAX Handlers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Handle Rochtah prescription request
 */
function snks_handle_rochtah_request() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_request' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'User must be logged in' );
	}
	
	global $wpdb;
	$current_user = wp_get_current_user();
	
	// Get form data
	$therapist_id = intval( $_POST['therapist_id'] );
	$diagnosis_id = intval( $_POST['diagnosis_id'] );
	$initial_diagnosis = sanitize_textarea_field( $_POST['initial_diagnosis'] );
	$symptoms = sanitize_textarea_field( $_POST['symptoms'] );
	$reason_for_referral = sanitize_textarea_field( $_POST['reason_for_referral'] );
	$preferred_date = sanitize_text_field( $_POST['preferred_date'] );
	$preferred_time = sanitize_text_field( $_POST['preferred_time'] );
	
	// Validate required fields
	if ( ! $therapist_id || ! $diagnosis_id || ! $initial_diagnosis || ! $symptoms || ! $reason_for_referral ) {
		wp_send_json_error( 'All fields are required' );
	}
	
	// Check if Rochtah is enabled
	$rochtah_enabled = get_option( 'snks_rochtah_enabled', '0' );
	if ( ! $rochtah_enabled ) {
		wp_send_json_error( 'Rochtah service is not available' );
	}
	
	// Check available days
	$available_days = get_option( 'snks_rochtah_available_days', array() );
	$day_of_week = date( 'l', strtotime( $preferred_date ) );
	if ( ! in_array( $day_of_week, $available_days ) ) {
		wp_send_json_error( 'Selected date is not available for Rochtah consultations' );
	}
	
	// Check if time slot is available
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$available_slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_appointments_table 
		WHERE day_of_week = %s 
		AND start_time <= %s 
		AND end_time > %s 
		AND status = 'active'",
		$day_of_week,
		$preferred_time,
		$preferred_time
	) );
	
	if ( ! $available_slot ) {
		wp_send_json_error( 'Selected time slot is not available' );
	}
	
	// Check if slot is already booked
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE booking_date = %s 
		AND booking_time = %s 
		AND status IN ('pending', 'confirmed')",
		$preferred_date,
		$preferred_time
	) );
	
	if ( $existing_booking ) {
		wp_send_json_error( 'This time slot is already booked' );
	}
	
	// Create the booking
	$booking_id = $wpdb->insert(
		$rochtah_bookings_table,
		array(
			'patient_id' => $current_user->ID,
			'therapist_id' => $therapist_id,
			'diagnosis_id' => $diagnosis_id,
			'initial_diagnosis' => $initial_diagnosis,
			'symptoms' => $symptoms,
			'reason_for_referral' => $reason_for_referral,
			'booking_date' => $preferred_date,
			'booking_time' => $preferred_time,
			'status' => 'pending'
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	
	if ( $booking_id ) {
		// Send notification to Rochtah doctors
		$rochtah_doctors = get_users( array( 'role' => 'rochtah_doctor' ) );
		foreach ( $rochtah_doctors as $doctor ) {
			snks_create_ai_notification(
				$doctor->ID,
				'rochtah_request',
				'New Rochtah Prescription Request',
				'Patient ' . $current_user->display_name . ' has requested a prescription consultation for ' . $preferred_date . ' at ' . $preferred_time
			);
		}
		
		// Send email notification if enabled
		if ( get_option( 'snks_ai_email_rochtah_request', '1' ) ) {
			$template = get_option( 'snks_ai_email_rochtah_template', 'Your prescription request has been received. Please confirm to proceed with the Rochtah consultation.' );
			$message = str_replace(
				array( '{{patient_name}}', '{{therapist_name}}', '{{session_date}}', '{{session_time}}', '{{diagnosis}}' ),
				array( $current_user->display_name, get_the_author_meta( 'display_name', $therapist_id ), $preferred_date, $preferred_time, get_the_title( $diagnosis_id ) ),
				$template
			);
			
			wp_mail( $current_user->user_email, 'Rochtah Prescription Request Received', $message );
		}
		
		wp_send_json_success( array(
			'message' => 'Rochtah prescription request submitted successfully!',
			'booking_id' => $booking_id
		) );
	} else {
		wp_send_json_error( 'Failed to create booking. Please try again.' );
	}
}
add_action( 'wp_ajax_rochtah_request', 'snks_handle_rochtah_request' );
add_action( 'wp_ajax_nopriv_rochtah_request', 'snks_handle_rochtah_request' );

/**
 * Get available Rochtah time slots
 */
function snks_get_rochtah_time_slots() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_slots' ) ) {
		wp_die( 'Security check failed' );
	}
	
	$date = sanitize_text_field( $_POST['date'] );
	$day_of_week = date( 'l', strtotime( $date ) );
	
	global $wpdb;
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	// Get available slots for the day
	$available_slots = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $rochtah_appointments_table 
		WHERE day_of_week = %s AND status = 'active' 
		ORDER BY start_time",
		$day_of_week
	) );
	
	$time_slots = array();
	foreach ( $available_slots as $slot ) {
		// Check if slot is booked
		$booked = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $rochtah_bookings_table 
			WHERE booking_date = %s 
			AND booking_time = %s 
			AND status IN ('pending', 'confirmed')",
			$date,
			$slot->start_time
		) );
		
		$time_slots[] = array(
			'start_time' => $slot->start_time,
			'end_time' => $slot->end_time,
			'available' => $booked == 0
		);
	}
	
	wp_send_json_success( $time_slots );
}
add_action( 'wp_ajax_get_rochtah_slots', 'snks_get_rochtah_time_slots' );
add_action( 'wp_ajax_nopriv_get_rochtah_slots', 'snks_get_rochtah_time_slots' );

/**
 * Update Rochtah booking status
 */
function snks_update_rochtah_booking_status() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'update_rochtah_status' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check if user has Rochtah doctor capabilities
	if ( ! current_user_can( 'manage_rochtah' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	$new_status = sanitize_text_field( $_POST['status'] );
	
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$updated = $wpdb->update(
		$rochtah_bookings_table,
		array( 'status' => $new_status ),
		array( 'id' => $booking_id ),
		array( '%s' ),
		array( '%d' )
	);
	
	if ( $updated !== false ) {
		wp_send_json_success( 'Booking status updated successfully' );
	} else {
		wp_send_json_error( 'Failed to update booking status' );
	}
}
add_action( 'wp_ajax_update_rochtah_status', 'snks_update_rochtah_booking_status' );

/**
 * Save prescription
 */
function snks_save_rochtah_prescription() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'save_prescription' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Check if user has Rochtah doctor capabilities
	if ( ! current_user_can( 'manage_rochtah' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	$prescription_text = sanitize_textarea_field( $_POST['prescription_text'] );
	$medications = sanitize_textarea_field( $_POST['medications'] );
	$dosage_instructions = sanitize_textarea_field( $_POST['dosage_instructions'] );
	$doctor_notes = sanitize_textarea_field( $_POST['doctor_notes'] );
	
	// Handle file uploads
	$attachment_ids = array();
	if ( ! empty( $_FILES['attachments'] ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		
		$files = $_FILES['attachments'];
		$file_count = count( $files['name'] );
		
		for ( $i = 0; $i < $file_count; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}
			
			$file = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);
			
			$_FILES = array( 'upload' => $file );
			$attachment_id = media_handle_upload( 'upload', 0 );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment_ids[] = $attachment_id;
			}
		}
	}
	
	global $wpdb;
	$current_user = wp_get_current_user();
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$update_data = array(
		'prescription_text' => $prescription_text,
		'medications' => $medications,
		'dosage_instructions' => $dosage_instructions,
		'doctor_notes' => $doctor_notes,
		'prescribed_by' => $current_user->ID,
		'prescribed_at' => current_time( 'mysql' ),
		'status' => 'prescribed'
	);
	
	// Add attachment IDs if any
	if ( ! empty( $attachment_ids ) ) {
		$update_data['attachment_ids'] = wp_json_encode( $attachment_ids );
	}
	
	$format = array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' );
	if ( ! empty( $attachment_ids ) ) {
		$format[] = '%s';
	}
	
	$updated = $wpdb->update(
		$rochtah_bookings_table,
		$update_data,
		array( 'id' => $booking_id ),
		$format,
		array( '%d' )
	);
	
	if ( $updated !== false ) {
		// Get booking details for notification
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $rochtah_bookings_table WHERE id = %d",
			$booking_id
		) );
		
		if ( $booking ) {
			$patient = get_user_by( 'ID', $booking->patient_id );
			
			// Send notification to patient
			snks_create_ai_notification(
				$booking->patient_id,
				'prescription_ready',
				'Your Prescription is Ready',
				'Your prescription has been written by Dr. ' . $current_user->display_name . '. Please check your email for details.'
			);
			
			// Send email to patient
			$subject = 'Your Prescription is Ready';
			$message = "Dear " . $patient->display_name . ",\n\n";
			$message .= "Your prescription has been written by Dr. " . $current_user->display_name . ".\n\n";
			$message .= "Medications:\n" . $medications . "\n\n";
			$message .= "Dosage Instructions:\n" . $dosage_instructions . "\n\n";
			$message .= "Doctor Notes:\n" . $doctor_notes . "\n\n";
			$message .= "Thank you for using our service.\n\n";
			$message .= "Best regards,\nJalsah Team";
			
			wp_mail( $patient->user_email, $subject, $message );
		}
		
		wp_send_json_success( 'Prescription saved successfully' );
	} else {
		wp_send_json_error( 'Failed to save prescription' );
	}
}
add_action( 'wp_ajax_save_prescription', 'snks_save_rochtah_prescription' ); 

add_action('wp_ajax_nopriv_register_therapist', 'snks_ajax_register_therapist');
function snks_ajax_register_therapist() {
    error_log('Therapist application: start');
    // Get password mode from settings
    $mode = get_option('jalsah_therapist_registration_password_mode', 'auto');
    // Check required fields
    $required = ['name', 'name_en', 'email', 'phone', 'whatsapp', 'doctor_specialty'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            error_log('Therapist application: missing field ' . $field);
            wp_send_json_error(['message' => 'Missing required field: ' . $field]);
        }
    }
    // Email validation
    if (!is_email($_POST['email'])) {
        error_log('Therapist application: invalid email');
        wp_send_json_error(['message' => 'Invalid email address']);
    }
    // Handle file uploads
    $uploads = [];
    $file_fields = ['profile_image', 'identity_front', 'identity_back'];
    foreach ($file_fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $attachment_id = media_handle_upload($field, 0);
            if (is_wp_error($attachment_id)) {
                error_log('Therapist application: file upload failed for ' . $field);
                wp_send_json_error(['message' => 'File upload failed: ' . $field]);
            }
            $uploads[$field] = $attachment_id;
        }
    }
    error_log('Therapist application: file uploads done');
    // Handle certificates (multiple)
    $certificates = [];
    if (!empty($_FILES['certificates'])) {
        foreach ($_FILES['certificates']['name'] as $i => $name) {
            if (!empty($name)) {
                $file = [
                    'name'     => $_FILES['certificates']['name'][$i],
                    'type'     => $_FILES['certificates']['type'][$i],
                    'tmp_name' => $_FILES['certificates']['tmp_name'][$i],
                    'error'    => $_FILES['certificates']['error'][$i],
                    'size'     => $_FILES['certificates']['size'][$i],
                ];
                $_FILES['single_certificate'] = $file;
                $cert_id = media_handle_upload('single_certificate', 0);
                if (!is_wp_error($cert_id)) {
                    $certificates[] = $cert_id;
                } else {
                    error_log('Therapist application: certificate upload failed at index ' . $i);
                }
            }
        }
    }
    error_log('Therapist application: certificates done');
    // Store in custom database table
    global $wpdb;
    $table_name = $wpdb->prefix . 'therapist_applications';
    
    $application_data = [
        'name' => sanitize_text_field($_POST['name']),
        'name_en' => sanitize_text_field($_POST['name_en']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'whatsapp' => sanitize_text_field($_POST['whatsapp']),
        'doctor_specialty' => sanitize_text_field($_POST['doctor_specialty']),
        'experience_years' => intval($_POST['experience_years'] ?? 0),
        'education' => sanitize_textarea_field($_POST['education'] ?? ''),
        'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
        'bio_en' => sanitize_textarea_field($_POST['bio_en'] ?? ''),
        'profile_image' => $uploads['profile_image'] ?? null,
        'identity_front' => $uploads['identity_front'] ?? null,
        'identity_back' => $uploads['identity_back'] ?? null,
        'certificates' => !empty($certificates) ? json_encode($certificates) : null,
        'status' => 'pending'
    ];
    
    error_log('Therapist application: before database insert');
    $result = $wpdb->insert($table_name, $application_data);
    error_log('Therapist application: after database insert, result=' . print_r($result, true));
    
    if ($result === false) {
        error_log('Therapist application: database insert failed: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to submit application.']);
    }
    
    $application_id = $wpdb->insert_id;
    error_log('Therapist application: success');
    wp_send_json_success(['message' => 'Application submitted and pending approval.']);
}

/**
 * Get prescription data for viewing/editing
 */
function snks_get_rochtah_prescription() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_prescription' ) ) {
		wp_send_json_error( 'Security check failed' );
	}
	
	// Check if user has Rochtah doctor capabilities or is patient viewing own prescription
	$booking_id = intval( $_POST['booking_id'] );
	$current_user_id = get_current_user_id();
	
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d",
		$booking_id
	) );
	
	if ( ! $booking ) {
		wp_send_json_error( 'Prescription not found' );
	}
	
	// Allow access if user is rochtah doctor/admin or if user is the patient
	$has_access = (
		current_user_can( 'manage_rochtah' ) ||
		current_user_can( 'manage_options' ) ||
		$booking->patient_id == $current_user_id
	);
	
	if ( ! $has_access ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	// Get booking with prescription data and related info
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT rb.*, 
		        u.display_name as patient_name, 
		        u.user_email as patient_email,
		        prescriber.display_name as prescribed_by_name
		FROM $rochtah_bookings_table rb
		LEFT JOIN {$wpdb->users} u ON rb.patient_id = u.ID
		LEFT JOIN {$wpdb->users} prescriber ON rb.prescribed_by = prescriber.ID
		WHERE rb.id = %d",
		$booking_id
	) );
	
	if ( ! $booking ) {
		wp_send_json_error( 'Booking not found' );
	}
	
	// Format attachments
	$attachments = array();
	if ( ! empty( $booking->attachment_ids ) ) {
		$attachment_ids = json_decode( $booking->attachment_ids, true );
		if ( is_array( $attachment_ids ) ) {
			foreach ( $attachment_ids as $att_id ) {
				$attachments[] = array(
					'id'        => $att_id,
					'url'       => wp_get_attachment_url( $att_id ),
					'name'      => basename( get_attached_file( $att_id ) ),
					'type'      => get_post_mime_type( $att_id ),
					'is_image'  => wp_attachment_is_image( $att_id ),
					'thumbnail' => wp_get_attachment_image_url( $att_id, 'thumbnail' ),
				);
			}
		}
	}
	
	wp_send_json_success( array(
		'patient_name' => $booking->patient_name,
		'patient_email' => $booking->patient_email,
		'booking_date' => $booking->booking_date,
		'booking_time' => date( 'g:i A', strtotime( $booking->booking_time ) ),
		'prescription_text' => $booking->prescription_text,
		'medications' => $booking->medications,
		'dosage_instructions' => $booking->dosage_instructions,
		'doctor_notes' => $booking->doctor_notes,
		'prescribed_by_name' => $booking->prescribed_by_name,
		'prescribed_at' => $booking->prescribed_at ? date( 'Y-m-d H:i:s', strtotime( $booking->prescribed_at ) ) : null,
		'status' => $booking->status,
		'attachments' => $attachments,
		'initial_diagnosis' => $booking->initial_diagnosis,
		'symptoms' => $booking->symptoms,
		'reason_for_referral' => $booking->reason_for_referral
	) );
}
add_action( 'wp_ajax_get_rochtah_prescription', 'snks_get_rochtah_prescription' );

/**
 * Update existing prescription
 */
function snks_update_rochtah_prescription() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'save_prescription' ) ) {
		wp_send_json_error( 'Security check failed' );
	}
	
	// Check if user has Rochtah doctor capabilities
	if ( ! current_user_can( 'manage_rochtah' ) && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	$prescription_text = sanitize_textarea_field( $_POST['prescription_text'] );
	$medications = sanitize_textarea_field( $_POST['medications'] );
	$dosage_instructions = sanitize_textarea_field( $_POST['dosage_instructions'] );
	$doctor_notes = sanitize_textarea_field( $_POST['doctor_notes'] );
	
	// Handle file uploads
	$attachment_ids = array();
	if ( ! empty( $_FILES['attachments'] ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		
		$files = $_FILES['attachments'];
		$file_count = count( $files['name'] );
		
		for ( $i = 0; $i < $file_count; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}
			
			$file = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);
			
			$_FILES = array( 'upload' => $file );
			$attachment_id = media_handle_upload( 'upload', 0 );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment_ids[] = $attachment_id;
			}
		}
	}
	
	global $wpdb;
	$current_user = wp_get_current_user();
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	// Update prescription (don't change prescribed_by and prescribed_at if already set)
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT prescribed_by, prescribed_at, attachment_ids FROM $rochtah_bookings_table WHERE id = %d",
		$booking_id
	) );
	
	$update_data = array(
		'prescription_text' => $prescription_text,
		'medications' => $medications,
		'dosage_instructions' => $dosage_instructions,
		'doctor_notes' => $doctor_notes,
		'status' => 'prescribed'
	);
	
	// Handle attachment IDs - merge with existing if updating
	if ( ! empty( $attachment_ids ) ) {
		$existing_attachments = ! empty( $existing_booking->attachment_ids ) ? json_decode( $existing_booking->attachment_ids, true ) : array();
		if ( ! is_array( $existing_attachments ) ) {
			$existing_attachments = array();
		}
		// Merge new attachments with existing ones
		$all_attachments = array_merge( $existing_attachments, $attachment_ids );
		$update_data['attachment_ids'] = wp_json_encode( $all_attachments );
	}
	
	// Only update prescribed_by and prescribed_at if not already set
	if ( ! $existing_booking->prescribed_by ) {
		$update_data['prescribed_by'] = $current_user->ID;
		$update_data['prescribed_at'] = current_time( 'mysql' );
	}
	
	$format = array( '%s', '%s', '%s', '%s', '%s' );
	if ( ! empty( $attachment_ids ) ) {
		$format[] = '%s'; // attachment_ids
	}
	if ( ! $existing_booking->prescribed_by ) {
		$format[] = '%d'; // prescribed_by
		$format[] = '%s'; // prescribed_at
	}
	
	$updated = $wpdb->update(
		$rochtah_bookings_table,
		$update_data,
		array( 'id' => $booking_id ),
		$format,
		array( '%d' )
	);
	
	if ( $updated !== false ) {
		wp_send_json_success( 'Prescription updated successfully' );
	} else {
		wp_send_json_error( 'Failed to update prescription' );
	}
}
add_action( 'wp_ajax_update_rochtah_prescription', 'snks_update_rochtah_prescription' ); 