<?php
/**
 * AI Prescription (Roshta) System
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add prescription button to AI session bookings
 */
function snks_add_ai_prescription_button( $session_id, $session_data ) {
	// Only show for completed AI sessions
	if ( $session_data->session_status !== 'completed' ) {
		return '';
	}
	
	// Only show for therapists
	if ( ! snks_is_doctor() ) {
		return '';
	}
	
	// Check if this is an AI session
	$order = wc_get_order( $session_data->order_id );
	if ( ! $order || $order->get_meta( 'from_jalsah_ai' ) !== 'true' ) {
		return '';
	}
	
	// Check if prescription already requested - multiple validation points
	$prescription_requested = get_post_meta( $session_data->order_id, '_ai_prescription_requested', true );
	
	// Also check if there's already a Rochtah booking for this specific session
	global $wpdb;
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE session_id = %d AND status IN ('pending', 'confirmed')",
		$session_data->ID
	) );
	
	if ( $prescription_requested || $existing_booking ) {
		return '<button class="snks-button snks-prescription-requested" disabled>' . __( 'Prescription Requested', 'shrinks' ) . '</button>';
	}
	
	$button_html = '<button class="snks-button snks-prescription-button" data-session-id="' . esc_attr( $session_id ) . '">';
	$button_html .= __( 'Prescription', 'shrinks' );
	$button_html .= '</button>';
	
	return $button_html;
}

/**
 * Handle AI prescription request
 */
function snks_handle_ai_prescription_request() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_prescription_request' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	// Check if user is logged in and is a therapist
	if ( ! is_user_logged_in() || ! snks_is_doctor() ) {
		wp_send_json_error( __( 'Unauthorized access', 'shrinks' ) );
	}
	
	global $wpdb;
	$current_user = wp_get_current_user();
	
	// Get form data
	$session_id = intval( $_POST['session_id'] );
	$needs_medication = sanitize_text_field( $_POST['needs_medication'] );
	$preliminary_diagnosis = sanitize_textarea_field( $_POST['preliminary_diagnosis'] );
	$symptoms = sanitize_textarea_field( $_POST['symptoms'] );
	
	// Validate required fields
	if ( ! $session_id || ! $preliminary_diagnosis || ! $symptoms ) {
		wp_send_json_error( __( 'All fields are required', 'shrinks' ) );
	}
	
	// Get session details
	$session = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE ID = %d AND user_id = %d AND settings LIKE '%ai_booking%'",
		$session_id, $current_user->ID
	) );
	
	if ( ! $session ) {
		wp_send_json_error( __( 'Session not found or access denied', 'shrinks' ) );
	}
	
	// Check if session is completed
	if ( $session->session_status !== 'completed' ) {
		wp_send_json_error( __( 'Session must be completed before requesting prescription', 'shrinks' ) );
	}
	
	// Check if prescription already requested - multiple validation points
	$order = wc_get_order( $session->order_id );
	
	// Check order meta
	if ( $order && $order->get_meta( '_ai_prescription_requested' ) ) {
		wp_send_json_error( __( 'Prescription already requested for this session', 'shrinks' ) );
	}
	
	// Check if there's already a Rochtah booking for this specific session
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE session_id = %d AND status IN ('pending', 'confirmed')",
		$session_id
	) );
	
	if ( $existing_booking ) {
		wp_send_json_error( __( 'Prescription already requested for this session', 'shrinks' ) );
	}
	
	// Create prescription request
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	
	// Get next available Rochtah slot (15-minute intervals)
	$next_slot = snks_get_next_rochtah_slot();
	if ( ! $next_slot ) {
		wp_send_json_error( __( 'No available Rochtah slots at the moment', 'shrinks' ) );
	}
	
	// Create the booking
	$booking_id = $wpdb->insert(
		$rochtah_bookings_table,
		array(
			'patient_id' => $session->client_id,
			'therapist_id' => $current_user->ID,
			'session_id' => $session_id, // Link to specific session
			'diagnosis_id' => 0, // Will be set by Rochtah doctor
			'initial_diagnosis' => $preliminary_diagnosis,
			'symptoms' => $symptoms,
			'booking_date' => $next_slot['date'],
			'booking_time' => $next_slot['time'],
			'status' => 'pending',
			'created_at' => current_time( 'mysql' )
		),
		array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	
	if ( $booking_id ) {
		// Mark prescription as requested
		if ( $order ) {
			$order->update_meta_data( '_ai_prescription_requested', true );
			$order->update_meta_data( '_ai_prescription_booking_id', $booking_id );
			$order->update_meta_data( '_ai_prescription_requested_at', current_time( 'mysql' ) );
			$order->save();
		}
		
		// Send notification to Rochtah doctors
		$rochtah_doctors = get_users( array( 'role' => 'rochtah_doctor' ) );
		foreach ( $rochtah_doctors as $doctor ) {
			snks_create_ai_notification(
				$doctor->ID,
				'ai_prescription_request',
				__( 'New AI Prescription Request', 'shrinks' ),
				sprintf( 
					__( 'Patient %s has requested a prescription consultation for %s at %s', 'shrinks' ),
					get_user_meta( $session->client_id, 'nickname', true ),
					$next_slot['date'],
					$next_slot['time']
				)
			);
		}
		
		// Send WhatsApp and email to patient
		snks_send_ai_prescription_notifications( $session->client_id, $booking_id, $next_slot );
		
		wp_send_json_success( array(
			'message' => __( 'Prescription service request submitted successfully.', 'shrinks' ),
			'booking_id' => $booking_id,
			'rochtah_date' => $next_slot['date'],
			'rochtah_time' => $next_slot['time']
		) );
	} else {
		wp_send_json_error( __( 'Failed to create prescription request. Please try again.', 'shrinks' ) );
	}
}
add_action( 'wp_ajax_ai_prescription_request', 'snks_handle_ai_prescription_request' );

/**
 * Get next available Rochtah slot (explicit slots)
 */
function snks_get_next_rochtah_slot() {
	global $wpdb;
	
	// Get Rochtah available days
	$available_days = get_option( 'snks_rochtah_available_days', array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ) );
	
	// Start from tomorrow
	$current_date = current_time( 'Y-m-d' );
	$next_date = date( 'Y-m-d', strtotime( '+1 day', strtotime( $current_date ) ) );
	
	// Look for next available slot in the next 7 days
	for ( $i = 0; $i < 7; $i++ ) {
		$check_date = date( 'Y-m-d', strtotime( "+$i days", strtotime( $next_date ) ) );
		$day_of_week = date( 'l', strtotime( $check_date ) );
		
		if ( in_array( $day_of_week, $available_days ) ) {
			// Get explicit time slots for this day
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$available_slots = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE day_of_week = %s AND status = 'active'
				ORDER BY sort_order ASC, start_time ASC",
				$day_of_week
			) );
			
			foreach ( $available_slots as $slot ) {
				// Check if this specific slot is available
				$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
				$existing_booking = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $rochtah_bookings_table 
					WHERE booking_date = %s 
					AND booking_time = %s 
					AND status IN ('pending', 'confirmed')",
					$check_date,
					$slot->start_time
				) );
				
				if ( ! $existing_booking ) {
					return array(
						'date' => $check_date,
						'time' => $slot->start_time,
						'day_of_week' => $day_of_week
					);
				}
			}
		}
	}
	
	return false;
}

/**
 * Send prescription notifications to patient
 */
function snks_send_ai_prescription_notifications( $patient_id, $booking_id, $slot ) {
	$patient = get_userdata( $patient_id );
	if ( ! $patient ) {
		return;
	}
	
	// Send WhatsApp notification
	$whatsapp_message = sprintf(
		__( 'Your prescription request has been received. Your Rochtah consultation is scheduled for %s at %s. Click here to confirm: %s', 'shrinks' ),
		$slot['date'],
		$slot['time'],
		home_url( '/rochtah-confirmation?booking_id=' . $booking_id )
	);
	
	snks_send_whatsapp_message( $patient->user_meta['phone'] ?? '', $whatsapp_message );
	
	// Send email notification
	$email_subject = __( 'Prescription Request Received', 'shrinks' );
	$email_message = sprintf(
		__( 'Dear %s,

Your prescription request has been received and processed. 

Your Rochtah consultation is scheduled for:
Date: %s
Time: %s

Please click the following link to confirm your appointment:
%s

Best regards,
Jalsah Team', 'shrinks' ),
		$patient->display_name,
		$slot['date'],
		$slot['time'],
		home_url( '/rochtah-confirmation?booking_id=' . $booking_id )
	);
	
	wp_mail( $patient->user_email, $email_subject, $email_message );
}

/**
 * Add prescription button to AI session display
 */
function snks_modify_ai_session_display( $output, $session ) {
	// Only modify AI sessions
	$order = wc_get_order( $session->order_id );
	if ( ! $order || $order->get_meta( 'from_jalsah_ai' ) !== 'true' ) {
		return $output;
	}
	
	// Add prescription button after session completion
	if ( $session->session_status === 'completed' && snks_is_doctor() ) {
		$prescription_button = snks_add_ai_prescription_button( $session->ID, $session );
		if ( $prescription_button ) {
			$output .= '<div class="ai-prescription-section">';
			$output .= '<h4>' . __( 'Prescription Services', 'shrinks' ) . '</h4>';
			$output .= $prescription_button;
			$output .= '</div>';
		}
	}
	
	return $output;
}
add_filter( 'snks_session_display_output', 'snks_modify_ai_session_display', 10, 2 );

/**
 * Enqueue prescription scripts and styles
 */
function snks_enqueue_ai_prescription_assets() {
	if ( is_page() && has_shortcode( get_post()->post_content, 'snks_bookings' ) ) {
		wp_enqueue_script( 'snks-ai-prescription', plugin_dir_url( __FILE__ ) . '../js/ai-prescription.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'snks-ai-prescription', 'snks_ai_prescription', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ai_prescription_request' ),
			'rochtah_nonce' => wp_create_nonce( 'rochtah_booking' ),
			'strings' => array(
				'confirm_medication' => __( 'Do you think the client needs medication and would you like to refer them to a psychiatrist (free of charge) to prescribe medication alongside your sessions?', 'shrinks' ),
				'preliminary_diagnosis' => __( 'Preliminary diagnosis of the client according to your observation', 'shrinks' ),
				'symptoms' => __( 'Symptoms that you believe require medication', 'shrinks' ),
				'request' => __( 'Request', 'shrinks' ),
				'cancel' => __( 'Cancel', 'shrinks' ),
				'yes' => __( 'Yes', 'shrinks' ),
				'no' => __( 'No', 'shrinks' ),
				'close' => __( 'Close', 'shrinks' ),
				'success_message' => __( 'Prescription service request submitted successfully.', 'shrinks' ),
				'error_message' => __( 'An error occurred. Please try again.', 'shrinks' )
			)
		) );
		
		wp_enqueue_style( 'snks-ai-prescription', plugin_dir_url( __FILE__ ) . '../css/ai-prescription.css', array(), '1.0.0' );
	}
}
add_action( 'wp_enqueue_scripts', 'snks_enqueue_ai_prescription_assets' );

/**
 * Add "Reason for Referral" button to Rochtah bookings
 */
function snks_add_rochtah_referral_reason_button( $booking ) {
	if ( $booking->initial_diagnosis || $booking->symptoms ) {
		$button_html = '<button class="snks-button snks-referral-reason-button" data-booking-id="' . esc_attr( $booking->id ) . '">';
		$button_html .= __( 'Reason for Referral', 'shrinks' );
		$button_html .= '</button>';
		return $button_html;
	}
	return '';
}

/**
 * Get referral reason details
 */
function snks_get_rochtah_referral_reason( $booking_id ) {
	global $wpdb;
	
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d",
		$booking_id
	) );
	
	if ( $booking ) {
		return array(
			'preliminary_diagnosis' => $booking->initial_diagnosis,
			'symptoms' => $booking->symptoms
		);
	}
	
	return false;
}

/**
 * AJAX handler for getting referral reason
 */
function snks_get_rochtah_referral_reason_ajax() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_referral_reason' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	$referral_reason = snks_get_rochtah_referral_reason( $booking_id );
	
	if ( $referral_reason ) {
		wp_send_json_success( $referral_reason );
	} else {
		wp_send_json_error( __( 'Referral reason not found', 'shrinks' ) );
	}
}
add_action( 'wp_ajax_get_rochtah_referral_reason', 'snks_get_rochtah_referral_reason_ajax' );

/**
 * Check if patient has pending prescription requests
 */
function snks_get_patient_prescription_requests( $patient_id = null ) {
	if ( ! $patient_id ) {
		$patient_id = get_current_user_id();
	}
	
	global $wpdb;
	
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$pending_requests = $wpdb->get_results( $wpdb->prepare(
		"SELECT rb.*, 
		        t.display_name as therapist_name,
		        s.date_time,
		        s.starts,
		        s.ends
		FROM $rochtah_bookings_table rb
		LEFT JOIN {$wpdb->users} t ON rb.therapist_id = t.ID
		LEFT JOIN {$wpdb->prefix}snks_provider_timetable s ON rb.session_id = s.ID
		WHERE rb.patient_id = %d AND rb.status IN ('pending', 'confirmed')
		ORDER BY rb.created_at DESC",
		$patient_id
	) );
	
	return $pending_requests;
}

/**
 * Display prescription request section for patients
 */
function snks_display_patient_prescription_requests( $patient_id = null ) {
	if ( ! $patient_id ) {
		$patient_id = get_current_user_id();
	}
	
	$prescription_requests = snks_get_patient_prescription_requests( $patient_id );
	
	if ( empty( $prescription_requests ) ) {
		return '';
	}
	
	$output = '<div class="rochtah-prescription-requests">';
	$output .= '<h3>' . __( 'Prescription Services', 'shrinks' ) . '</h3>';
	
	foreach ( $prescription_requests as $request ) {
		$output .= '<div class="rochtah-request-item">';
		$output .= '<div class="rochtah-request-message">';
		$output .= '<p>' . __( 'A Roshta service request has been submitted by your therapist. You can now book a free 15-minute consultation with a psychiatrist to prescribe suitable medication. Please note that your therapist has already provided the reason for this referral.', 'shrinks' ) . '</p>';
		$output .= '</div>';
		
		$output .= '<div class="rochtah-request-actions">';
		$output .= '<button class="snks-button snks-book-rochtah-button" data-request-id="' . esc_attr( $request->id ) . '">';
		$output .= __( 'Book Free Appointment', 'shrinks' );
		$output .= '</button>';
		$output .= '</div>';
		$output .= '</div>';
	}
	
	$output .= '</div>';
	
	return $output;
}

/**
 * Get available Rochtah time slots for patient booking
 */
function snks_get_rochtah_available_slots_for_patient() {
	global $wpdb;
	
	// Get Rochtah available days
	$available_days = get_option( 'snks_rochtah_available_days', array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ) );
	
	$available_slots = array();
	$slot_count = 0;
	$max_slots = 50; // Limit to 50 slots maximum
	
	// Look for available slots in the next 7 days only
	for ( $i = 1; $i <= 7; $i++ ) {
		$check_date = date( 'Y-m-d', strtotime( "+$i days" ) );
		$day_of_week = date( 'l', strtotime( $check_date ) );
		
		if ( in_array( $day_of_week, $available_days ) ) {
			// Get explicit time slots for this day
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$day_slots = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE day_of_week = %s AND status = 'active'
				ORDER BY sort_order ASC, start_time ASC",
				$day_of_week
			) );
			
			foreach ( $day_slots as $slot ) {
				// Check if this specific slot is available
				$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
				$existing_booking = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $rochtah_bookings_table 
					WHERE booking_date = %s 
					AND booking_time = %s 
					AND status IN ('pending', 'confirmed')",
					$check_date,
					$slot->start_time
				) );
				
				if ( ! $existing_booking && $slot_count < $max_slots ) {
					$available_slots[] = array(
						'date' => $check_date,
						'time' => $slot->start_time,
						'end_time' => $slot->end_time,
						'formatted_time' => date( 'g:i A', strtotime( $slot->start_time ) ) . ' - ' . date( 'g:i A', strtotime( $slot->end_time ) ),
						'day_of_week' => $day_of_week,
						'slot_name' => $slot->slot_name
					);
					$slot_count++;
				}
				
				// Break if we've reached the maximum slots
				if ( $slot_count >= $max_slots ) {
					break 2; // Break out of both loops
				}
			}
		}
	}
	
	return $available_slots;
}

/**
 * AJAX handler for getting available Rochtah slots
 */
function snks_get_rochtah_available_slots_ajax() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_booking' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	$available_slots = snks_get_rochtah_available_slots_for_patient();
	
	if ( $available_slots ) {
		wp_send_json_success( $available_slots );
	} else {
		wp_send_json_error( __( 'No available slots at the moment', 'shrinks' ) );
	}
}
add_action( 'wp_ajax_get_rochtah_available_slots', 'snks_get_rochtah_available_slots_ajax' );

/**
 * AJAX handler for booking Rochtah appointment
 */
function snks_book_rochtah_appointment() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_booking' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to book an appointment', 'shrinks' ) );
	}
	
	$request_id = intval( $_POST['request_id'] );
	$selected_date = sanitize_text_field( $_POST['selected_date'] );
	$selected_time = sanitize_text_field( $_POST['selected_time'] );
	
	if ( ! $request_id || ! $selected_date || ! $selected_time ) {
		wp_send_json_error( __( 'Missing required information', 'shrinks' ) );
	}
	
	global $wpdb;
	$current_user = wp_get_current_user();
	
	// Verify the request belongs to the current user
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$request = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d AND patient_id = %d AND status = 'pending'",
		$request_id, $current_user->ID
	) );
	
	if ( ! $request ) {
		wp_send_json_error( __( 'Prescription request not found or already booked', 'shrinks' ) );
	}
	
	// Check if slot is still available
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE booking_date = %s 
		AND booking_time = %s 
		AND status IN ('pending', 'confirmed')
		AND id != %d",
		$selected_date,
		$selected_time,
		$request_id
	) );
	
	if ( $existing_booking ) {
		wp_send_json_error( __( 'This time slot is no longer available', 'shrinks' ) );
	}
	
	// Update the booking with the selected date and time
	$result = $wpdb->update(
		$rochtah_bookings_table,
		array(
			'booking_date' => $selected_date,
			'booking_time' => $selected_time,
			'status' => 'confirmed'
		),
		array( 'id' => $request_id ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);
	
	if ( $result !== false ) {
		// Send notification to Rochtah doctors
		$rochtah_doctors = get_users( array( 'role' => 'rochtah_doctor' ) );
		foreach ( $rochtah_doctors as $doctor ) {
			snks_create_ai_notification(
				$doctor->ID,
				'rochtah_appointment_booked',
				__( 'Rochtah Appointment Booked', 'shrinks' ),
				sprintf( 
					__( 'Patient %s has booked a Rochtah consultation for %s at %s', 'shrinks' ),
					$current_user->display_name,
					$selected_date,
					$selected_time
				)
			);
		}
		
		wp_send_json_success( array(
			'message' => __( 'Appointment booked successfully.', 'shrinks' ),
			'booking_id' => $request_id,
			'date' => $selected_date,
			'time' => $selected_time
		) );
	} else {
		wp_send_json_error( __( 'Failed to book appointment. Please try again.', 'shrinks' ) );
	}
}
add_action( 'wp_ajax_book_rochtah_appointment', 'snks_book_rochtah_appointment' );

/**
 * Get prescription requests for current user via AJAX
 */
function snks_get_prescription_requests_ajax() {
	// Debug logging
	error_log( 'AJAX handler called: get_prescription_requests' );
	
	if ( ! is_user_logged_in() ) {
		error_log( 'User not logged in' );
		wp_send_json_error( __( 'You must be logged in to view prescription requests', 'shrinks' ) );
	}
	
	$current_user = wp_get_current_user();
	error_log( 'Current user ID: ' . $current_user->ID );
	
	$prescription_requests = snks_get_patient_prescription_requests( $current_user->ID );
	error_log( 'Prescription requests found: ' . count( $prescription_requests ) );
	
	wp_send_json_success( $prescription_requests );
}
add_action( 'wp_ajax_get_prescription_requests', 'snks_get_prescription_requests_ajax' );

/**
 * Debug AJAX handler to test if AJAX is working
 */
function snks_debug_ajax_test() {
	error_log( 'Debug AJAX test called' );
	wp_send_json_success( array( 'message' => 'AJAX is working' ) );
}
add_action( 'wp_ajax_debug_test', 'snks_debug_ajax_test' );

/**
 * Create Rochtah database tables
 */
function snks_create_rochtah_tables() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	
	// Rochtah appointments table - now stores explicit slots
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$sql_appointments = "CREATE TABLE $rochtah_appointments_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		day_of_week varchar(20) NOT NULL,
		start_time time NOT NULL,
		end_time time NOT NULL,
		slot_name varchar(100) DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT 'active',
		sort_order int(11) DEFAULT 0,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY day_of_week (day_of_week),
		KEY status (status),
		KEY sort_order (sort_order)
	) $charset_collate;";
	
	// Rochtah bookings table (if it doesn't exist)
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$sql_bookings = "CREATE TABLE $rochtah_bookings_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		patient_id bigint(20) NOT NULL,
		therapist_id bigint(20) NOT NULL,
		session_id bigint(20) NOT NULL,
		diagnosis_id bigint(20) DEFAULT 0,
		initial_diagnosis text,
		symptoms text,
		booking_date date NOT NULL,
		booking_time time NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY patient_id (patient_id),
		KEY therapist_id (therapist_id),
		KEY session_id (session_id),
		KEY status (status),
		UNIQUE KEY unique_session_request (session_id)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_appointments );
	dbDelta( $sql_bookings );
	
	// Insert default Rochtah schedule if table is empty - now creates explicit 15-minute slots
	$existing_slots = $wpdb->get_var( "SELECT COUNT(*) FROM $rochtah_appointments_table" );
	
	if ( $existing_slots == 0 ) {
		$default_days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' );
		$sort_order = 0;
		
		foreach ( $default_days as $day ) {
			// Create 15-minute slots from 9:00 AM to 5:00 PM
			$start_hour = 9;
			$end_hour = 17;
			
			for ( $hour = $start_hour; $hour < $end_hour; $hour++ ) {
				for ( $minute = 0; $minute < 60; $minute += 15 ) {
					$start_time = sprintf( '%02d:%02d:00', $hour, $minute );
					$end_minute = $minute + 15;
					$end_hour_slot = $hour;
					
					if ( $end_minute >= 60 ) {
						$end_minute = 0;
						$end_hour_slot = $hour + 1;
					}
					
					$end_time = sprintf( '%02d:%02d:00', $end_hour_slot, $end_minute );
					
					// Skip lunch break (12:00-13:00)
					if ( $hour == 12 ) {
						continue;
					}
					
					$slot_name = sprintf( '%s %s-%s', $day, date( 'g:i A', strtotime( $start_time ) ), date( 'g:i A', strtotime( $end_time ) ) );
					
					$wpdb->insert(
						$rochtah_appointments_table,
						array(
							'day_of_week' => $day,
							'start_time' => $start_time,
							'end_time' => $end_time,
							'slot_name' => $slot_name,
							'status' => 'active',
							'sort_order' => $sort_order++
						),
						array( '%s', '%s', '%s', '%s', '%s', '%d' )
					);
				}
			}
		}
	}
}

// Create tables on plugin activation
register_activation_hook( __FILE__, 'snks_create_rochtah_tables' );

// Also create tables if they don't exist (for existing installations)
add_action( 'init', 'snks_create_rochtah_tables' );

/**
 * Add Rochtah slots management to admin menu
 */
function snks_add_rochtah_admin_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Rochtah Slots', 'shrinks' ),
		__( 'Rochtah Slots', 'shrinks' ),
		'manage_options',
		'jalsah-ai-rochtah-slots',
		'snks_rochtah_slots_admin_page'
	);
}
add_action( 'admin_menu', 'snks_add_rochtah_admin_menu' );

/**
 * Admin page for managing Rochtah slots
 */
function snks_rochtah_slots_admin_page() {
	global $wpdb;
	
	// Handle form submissions
	if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['rochtah_slots_nonce'], 'rochtah_slots_action' ) ) {
		if ( $_POST['action'] === 'add_slot' ) {
			$day_of_week = sanitize_text_field( $_POST['day_of_week'] );
			$start_time = sanitize_text_field( $_POST['start_time'] );
			$end_time = sanitize_text_field( $_POST['end_time'] );
			$slot_name = sanitize_text_field( $_POST['slot_name'] );
			
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$wpdb->insert(
				$rochtah_appointments_table,
				array(
					'day_of_week' => $day_of_week,
					'start_time' => $start_time,
					'end_time' => $end_time,
					'slot_name' => $slot_name,
					'status' => 'active',
					'sort_order' => 0
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d' )
			);
			
			echo '<div class="notice notice-success"><p>' . __( 'Slot added successfully!', 'shrinks' ) . '</p></div>';
		} elseif ( $_POST['action'] === 'delete_slot' ) {
			$slot_id = intval( $_POST['slot_id'] );
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$wpdb->delete( $rochtah_appointments_table, array( 'id' => $slot_id ), array( '%d' ) );
			
			echo '<div class="notice notice-success"><p>' . __( 'Slot deleted successfully!', 'shrinks' ) . '</p></div>';
		}
	}
	
	// Get existing slots
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$slots = $wpdb->get_results( "SELECT * FROM $rochtah_appointments_table ORDER BY day_of_week, sort_order, start_time" );
	
	?>
	<div class="wrap">
		<h1><?php _e( 'Rochtah Slots Management', 'shrinks' ); ?></h1>
		
		<div class="card">
			<h2><?php _e( 'Add New Slot', 'shrinks' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'rochtah_slots_action', 'rochtah_slots_nonce' ); ?>
				<input type="hidden" name="action" value="add_slot">
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Day of Week', 'shrinks' ); ?></th>
						<td>
							<select name="day_of_week" required>
								<option value=""><?php _e( 'Select Day', 'shrinks' ); ?></option>
								<option value="Monday"><?php _e( 'Monday', 'shrinks' ); ?></option>
								<option value="Tuesday"><?php _e( 'Tuesday', 'shrinks' ); ?></option>
								<option value="Wednesday"><?php _e( 'Wednesday', 'shrinks' ); ?></option>
								<option value="Thursday"><?php _e( 'Thursday', 'shrinks' ); ?></option>
								<option value="Friday"><?php _e( 'Friday', 'shrinks' ); ?></option>
								<option value="Saturday"><?php _e( 'Saturday', 'shrinks' ); ?></option>
								<option value="Sunday"><?php _e( 'Sunday', 'shrinks' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Start Time', 'shrinks' ); ?></th>
						<td>
							<input type="time" name="start_time" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'End Time', 'shrinks' ); ?></th>
						<td>
							<input type="time" name="end_time" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Slot Name (Optional)', 'shrinks' ); ?></th>
						<td>
							<input type="text" name="slot_name" placeholder="<?php _e( 'e.g., Morning Session, Afternoon Break', 'shrinks' ); ?>">
						</td>
					</tr>
				</table>
				
				<?php submit_button( __( 'Add Slot', 'shrinks' ) ); ?>
			</form>
		</div>
		
		<div class="card">
			<h2><?php _e( 'Existing Slots', 'shrinks' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Day', 'shrinks' ); ?></th>
						<th><?php _e( 'Start Time', 'shrinks' ); ?></th>
						<th><?php _e( 'End Time', 'shrinks' ); ?></th>
						<th><?php _e( 'Slot Name', 'shrinks' ); ?></th>
						<th><?php _e( 'Status', 'shrinks' ); ?></th>
						<th><?php _e( 'Actions', 'shrinks' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $slots ): ?>
						<?php foreach ( $slots as $slot ): ?>
							<tr>
								<td><?php echo esc_html( $slot->day_of_week ); ?></td>
								<td><?php echo esc_html( date( 'g:i A', strtotime( $slot->start_time ) ) ); ?></td>
								<td><?php echo esc_html( date( 'g:i A', strtotime( $slot->end_time ) ) ); ?></td>
								<td><?php echo esc_html( $slot->slot_name ?: '-' ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $slot->status ); ?>">
										<?php echo esc_html( ucfirst( $slot->status ) ); ?>
									</span>
								</td>
								<td>
									<form method="post" action="" style="display: inline;">
										<?php wp_nonce_field( 'rochtah_slots_action', 'rochtah_slots_nonce' ); ?>
										<input type="hidden" name="action" value="delete_slot">
										<input type="hidden" name="slot_id" value="<?php echo esc_attr( $slot->id ); ?>">
										<button type="submit" class="button button-small" onclick="return confirm('<?php _e( 'Are you sure you want to delete this slot?', 'shrinks' ); ?>')">
											<?php _e( 'Delete', 'shrinks' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="6"><?php _e( 'No slots found. Add some slots above.', 'shrinks' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
