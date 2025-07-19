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
	$preferred_date = sanitize_text_field( $_POST['preferred_date'] );
	$preferred_time = sanitize_text_field( $_POST['preferred_time'] );
	
	// Validate required fields
	if ( ! $therapist_id || ! $diagnosis_id || ! $initial_diagnosis || ! $symptoms ) {
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
			'booking_date' => $preferred_date,
			'booking_time' => $preferred_time,
			'status' => 'pending'
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
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
	
	global $wpdb;
	$current_user = wp_get_current_user();
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$updated = $wpdb->update(
		$rochtah_bookings_table,
		array(
			'prescription_text' => $prescription_text,
			'medications' => $medications,
			'dosage_instructions' => $dosage_instructions,
			'doctor_notes' => $doctor_notes,
			'prescribed_by' => $current_user->ID,
			'prescribed_at' => current_time( 'mysql' ),
			'status' => 'prescribed'
		),
		array( 'id' => $booking_id ),
		array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
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