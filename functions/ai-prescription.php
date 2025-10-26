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
		wp_send_json_error( 'يجب تحديد الجلسة كمكتملة قبل طلب الروشتة' );
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
			// Get explicit time slots for this specific date
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$available_slots = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE slot_date = %s AND status = 'active'
				ORDER BY start_time ASC",
				$check_date
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
	
	// Get therapist ID from booking
	global $wpdb;
	$booking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT therapist_id FROM {$wpdb->prefix}snks_rochtah_bookings WHERE id = %d",
			$booking_id
		)
	);
	
	// Send rosheta activation notification (rosheta10) via WhatsApp
	if ( $booking && function_exists( 'snks_send_rosheta_activation_notification' ) ) {
		snks_send_rosheta_activation_notification( $patient_id, $booking->therapist_id, $booking_id );
	}
	
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
	// Always show the button for debugging - we can add conditions back later
	$button_html = '<button class="button button-small" onclick="showReferralReason(' . esc_attr( $booking->id ) . ')">';
	$button_html .= __( 'Reason for Referral', 'shrinks' );
	$button_html .= '</button>';
	return $button_html;
}

/**
 * Get referral reason details
 */
function snks_get_rochtah_referral_reason( $booking_id ) {
	global $wpdb;
	
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	error_log( 'DEBUG: Querying table: ' . $rochtah_bookings_table . ' for booking ID: ' . $booking_id );
	
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d",
		$booking_id
	) );
	
	error_log( 'DEBUG: Raw booking data: ' . print_r( $booking, true ) );
	
	if ( $booking ) {
		$result = array(
			'preliminary_diagnosis' => $booking->initial_diagnosis,
			'symptoms' => $booking->symptoms,
			'reason_for_referral' => $booking->reason_for_referral
		);
		error_log( 'DEBUG: Formatted result: ' . print_r( $result, true ) );
		return $result;
	}
	
	error_log( 'DEBUG: No booking found for ID: ' . $booking_id );
	return false;
}

/**
 * AJAX handler for getting referral reason
 */
function snks_get_rochtah_referral_reason_ajax() {
	error_log( 'DEBUG: get_rochtah_referral_reason_ajax called' );
	
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_referral_reason' ) ) {
		error_log( 'DEBUG: Nonce verification failed' );
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	error_log( 'DEBUG: Looking for booking ID: ' . $booking_id );
	
	$referral_reason = snks_get_rochtah_referral_reason( $booking_id );
	error_log( 'DEBUG: Referral reason data: ' . print_r( $referral_reason, true ) );
	
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
	
	// Format results to ensure doctor_joined is included
	$formatted_requests = array();
	foreach ( $pending_requests as $request ) {
		$formatted_requests[] = array(
			'id' => $request->id,
			'patient_id' => $request->patient_id,
			'therapist_id' => $request->therapist_id,
			'session_id' => $request->session_id,
			'diagnosis_id' => $request->diagnosis_id,
			'initial_diagnosis' => $request->initial_diagnosis,
			'symptoms' => $request->symptoms,
			'reason_for_referral' => $request->reason_for_referral,
			'booking_date' => $request->booking_date,
			'booking_time' => $request->booking_time,
			'status' => $request->status,
			'prescription_text' => $request->prescription_text,
			'medications' => $request->medications,
			'dosage_instructions' => $request->dosage_instructions,
			'doctor_notes' => $request->doctor_notes,
			'prescribed_by' => $request->prescribed_by,
			'prescribed_at' => $request->prescribed_at,
			'prescription_file' => $request->prescription_file,
			'whatsapp_activation_sent' => $request->whatsapp_activation_sent,
			'whatsapp_appointment_sent' => $request->whatsapp_appointment_sent,
			'appointment_id' => $request->appointment_id,
			'created_at' => $request->created_at,
			'updated_at' => $request->updated_at,
			'therapist_name' => $request->therapist_name,
			'date_time' => $request->date_time,
			'starts' => $request->starts,
			'ends' => $request->ends,
			'doctor_joined' => isset( $request->doctor_joined ) ? (bool) $request->doctor_joined : false,
			'booking_id' => $request->id
		);
	}
	
	return $formatted_requests;
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
	
	// Look for available slots in the next 30 days (increased from 7 days to show more future slots)
	for ( $i = 1; $i <= 30; $i++ ) {
		$check_date = date( 'Y-m-d', strtotime( "+$i days" ) );
		$day_of_week = date( 'l', strtotime( $check_date ) );
		
		if ( in_array( $day_of_week, $available_days ) ) {
			// Get explicit time slots for this specific date
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$day_slots = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE slot_date = %s AND status = 'active'
				ORDER BY start_time ASC",
				$check_date
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
					// Format time in Arabic AM/PM
					$start_time = gmdate( 'h:i a', strtotime( $slot->start_time ) );
					$end_time = gmdate( 'h:i a', strtotime( $slot->end_time ) );
					// Replace AM/PM with Arabic equivalents
					$start_time = str_replace( array( 'am', 'pm' ), array( 'ص', 'م' ), strtolower( $start_time ) );
					$end_time = str_replace( array( 'am', 'pm' ), array( 'ص', 'م' ), strtolower( $end_time ) );
					
					$available_slots[] = array(
						'date' => $check_date,
						'time' => $slot->start_time,
						'end_time' => $slot->end_time,
						'formatted_time' => $start_time . ' - ' . $end_time,
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
	
	// Sort slots by date and time (nearest first)
	usort( $available_slots, function( $a, $b ) {
		$date_a = strtotime( $a['date'] . ' ' . $a['time'] );
		$date_b = strtotime( $b['date'] . ' ' . $b['time'] );
		// Return negative if $a comes before $b (nearest first)
		return $date_a - $date_b;
	});
	
	// Debug: Log sorted slots
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Rochtah Slots] Total slots after sort: ' . count( $available_slots ) );
		foreach ( array_slice( $available_slots, 0, 5 ) as $index => $slot ) {
			error_log( sprintf( '[Rochtah Slots] Slot %d: %s %s', $index, $slot['date'], $slot['time'] ) );
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
 * Get patient's completed prescriptions
 */
function snks_get_patient_completed_prescriptions( $patient_id = null ) {
	if ( ! $patient_id ) {
		$patient_id = get_current_user_id();
	}
	
	global $wpdb;
	
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$completed_prescriptions = $wpdb->get_results( $wpdb->prepare(
		"SELECT rb.*, 
		        t.display_name as therapist_name,
		        prescriber.display_name as prescribed_by_name,
		        s.date_time,
		        s.starts,
		        s.ends
		FROM $rochtah_bookings_table rb
		LEFT JOIN {$wpdb->users} t ON rb.therapist_id = t.ID
		LEFT JOIN {$wpdb->users} prescriber ON rb.prescribed_by = prescriber.ID
		LEFT JOIN {$wpdb->prefix}snks_provider_timetable s ON rb.session_id = s.ID
		WHERE rb.patient_id = %d AND rb.status = 'prescribed'
		ORDER BY rb.prescribed_at DESC",
		$patient_id
	) );
	
	return $completed_prescriptions;
}

/**
 * Generate Jitsi meeting link for Rochtah appointment
 */
function snks_generate_rochtah_meeting_link( $booking_id ) {
	// Generate a unique room name for the Rochtah session
	$room_name = 'rochtah_' . $booking_id . '_' . time();
	
	// Create the Jitsi meeting URL
	$meeting_url = 'https://s.jalsah.app/' . $room_name;
	
	return array(
		'room_name' => $room_name,
		'meeting_url' => $meeting_url,
		'booking_id' => $booking_id
	);
}

/**
 * Get Rochtah meeting details for a booking
 */
function snks_get_rochtah_meeting_details( $booking_id ) {
	global $wpdb;
	
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d",
		$booking_id
	) );
	
	if ( ! $booking ) {
		return false;
	}
	
	// Generate meeting link if not already generated
	$meeting_link = get_post_meta( $booking_id, '_rochtah_meeting_link', true );
	if ( ! $meeting_link ) {
		$meeting_details = snks_generate_rochtah_meeting_link( $booking_id );
		$meeting_link = $meeting_details['meeting_url'];
		update_post_meta( $booking_id, '_rochtah_meeting_link', $meeting_link );
		update_post_meta( $booking_id, '_rochtah_room_name', $meeting_details['room_name'] );
	}
	
	$room_name = get_post_meta( $booking_id, '_rochtah_room_name', true );
	
	return array(
		'booking_id' => $booking_id,
		'room_name' => $room_name,
		'meeting_url' => $meeting_link,
		'booking_date' => $booking->booking_date,
		'booking_time' => $booking->booking_time,
		'status' => $booking->status,
		'patient_id' => $booking->patient_id,
		'therapist_id' => $booking->therapist_id,
		'doctor_joined' => isset( $booking->doctor_joined ) ? (bool) $booking->doctor_joined : false
	);
}

/**
 * AJAX handler for getting Rochtah meeting details
 */
function snks_get_rochtah_meeting_details_ajax() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_meeting' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to access meeting details', 'shrinks' ) );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	$current_user_id = get_current_user_id();
	
	// Get meeting details
	$meeting_details = snks_get_rochtah_meeting_details( $booking_id );
	
	if ( ! $meeting_details ) {
		wp_send_json_error( __( 'Booking not found', 'shrinks' ) );
	}
	
	// Check if user has access to this booking (patient, therapist, or admin)
	$has_access = (
		$meeting_details['patient_id'] == $current_user_id || 
		$meeting_details['therapist_id'] == $current_user_id ||
		current_user_can( 'manage_options' ) ||
		current_user_can( 'manage_rochtah' )
	);
	
	if ( ! $has_access ) {
		wp_send_json_error( __( 'Access denied', 'shrinks' ) );
	}
	
	wp_send_json_success( $meeting_details );
}
add_action( 'wp_ajax_get_rochtah_meeting_details', 'snks_get_rochtah_meeting_details_ajax' );

/**
 * AJAX handler for getting Rochtah meeting details for doctors
 */
function snks_get_rochtah_meeting_details_doctor_ajax() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'rochtah_meeting_doctor' ) ) {
		wp_send_json_error( __( 'Security check failed', 'shrinks' ) );
	}
	
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to access meeting details', 'shrinks' ) );
	}
	
	// Check if user has permission to access Rochtah doctor features
	if ( ! current_user_can( 'manage_rochtah' ) && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'You do not have permission to access this feature', 'shrinks' ) );
	}
	
	$booking_id = intval( $_POST['booking_id'] );
	
	// Get meeting details
	$meeting_details = snks_get_rochtah_meeting_details( $booking_id );
	
	if ( ! $meeting_details ) {
		wp_send_json_error( __( 'Booking not found', 'shrinks' ) );
	}
	
	// Get additional patient information for doctor dashboard
	global $wpdb;
	$booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT rb.*, u.display_name as patient_name, u.user_email as patient_email
		FROM {$wpdb->prefix}snks_rochtah_bookings rb
		LEFT JOIN {$wpdb->users} u ON rb.patient_id = u.ID
		WHERE rb.id = %d",
		$booking_id
	) );
	
	if ( $booking ) {
		$meeting_details['patient_name'] = $booking->patient_name;
		$meeting_details['patient_email'] = $booking->patient_email;
	}
	
	wp_send_json_success( $meeting_details );
}
add_action( 'wp_ajax_get_rochtah_meeting_details_doctor', 'snks_get_rochtah_meeting_details_doctor_ajax' );

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
	
	// Rochtah appointments table - now stores explicit slots with actual dates
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$sql_appointments = "CREATE TABLE $rochtah_appointments_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		day_of_week varchar(20) NOT NULL,
		slot_date date NOT NULL,
		start_time time NOT NULL,
		end_time time NOT NULL,
		slot_name varchar(100) DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT 'active',
		sort_order int(11) DEFAULT 0,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY day_of_week (day_of_week),
		KEY slot_date (slot_date),
		KEY status (status),
		KEY sort_order (sort_order),
		UNIQUE KEY unique_slot (slot_date, start_time)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_appointments );
	
	// Note: snks_rochtah_bookings table is created in snks_create_enhanced_ai_tables()
	
	// Note: Rochtah slots are now managed manually through the admin interface
	// No automatic slot creation - admins must add slots manually
}

/**
 * Add Rochtah slots management to admin menu
 * Note: Menu registration moved to ai-admin-enhanced.php to avoid conflicts
 */
function snks_add_rochtah_admin_menu() {
	// Menu registration moved to ai-admin-enhanced.php to avoid conflicts
	// add_submenu_page(
	// 	'jalsah-ai-management',
	// 	__( 'Rochtah Slots', 'shrinks' ),
	// 	__( 'Rochtah Slots', 'shrinks' ),
	// 	'manage_options',
	// 	'jalsah-ai-rochtah-slots',
	// 	'snks_rochtah_slots_admin_page'
	// );
}
// add_action( 'admin_menu', 'snks_add_rochtah_admin_menu', 25 ); // Commented out to avoid conflicts

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
			
			// Validate that the time difference is exactly 15 minutes
			$start_timestamp = strtotime( $start_time );
			$end_timestamp = strtotime( $end_time );
			$time_diff_minutes = ( $end_timestamp - $start_timestamp ) / 60;
			
			if ( $time_diff_minutes !== 15 ) {
				echo '<div class="notice notice-error"><p>' . __( 'Error: The difference between start time and end time must be exactly 15 minutes!', 'shrinks' ) . '</p></div>';
			} else {
			
			// Check if slot already exists for this day and start time
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$existing_slot = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE day_of_week = %s AND start_time = %s AND status = 'active'",
				$day_of_week, $start_time
			) );
			
			// Generate slots for the next 30 occurrences of this day
			$slots_created = 0;
			$current_date = current_time( 'Y-m-d' );
			
			for ( $i = 0; $i < 30; $i++ ) {
				// Find the next occurrence of this day
				$target_date = date( 'Y-m-d', strtotime( "+$i days", strtotime( $current_date ) ) );
				$target_day = date( 'l', strtotime( $target_date ) );
				
				// If this is the target day, create the slot
				if ( $target_day === $day_of_week ) {
					// Check if slot already exists for this specific date and time
					$existing_slot = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM $rochtah_appointments_table 
						WHERE slot_date = %s AND start_time = %s AND status = 'active'",
						$target_date, $start_time
					) );
					
					if ( ! $existing_slot ) {
						$wpdb->insert(
							$rochtah_appointments_table,
							array(
								'day_of_week' => $day_of_week,
								'slot_date' => $target_date,
								'start_time' => $start_time,
								'end_time' => $end_time,
								'slot_name' => $slot_name,
								'status' => 'active',
								'sort_order' => 0
							),
							array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
						);
						$slots_created++;
					}
				}
			}
			
			if ( $slots_created > 0 ) {
				echo '<div class="notice notice-success"><p>' . sprintf( __( '%d slots created successfully for the next 30 occurrences of %s!', 'shrinks' ), $slots_created, $day_of_week ) . '</p></div>';
			} else {
				echo '<div class="notice notice-warning"><p>' . __( 'No new slots were created. All slots for this day and time already exist.', 'shrinks' ) . '</p></div>';
			}
			}
		} elseif ( $_POST['action'] === 'delete_slot' ) {
			$slot_id = intval( $_POST['slot_id'] );
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$wpdb->delete( $rochtah_appointments_table, array( 'id' => $slot_id ), array( '%d' ) );
			
			echo '<div class="notice notice-success"><p>' . __( 'Slot deleted successfully!', 'shrinks' ) . '</p></div>';
		}
	}
	
	// Get existing slots
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$slots = $wpdb->get_results( "SELECT * FROM $rochtah_appointments_table ORDER BY slot_date ASC, start_time ASC" );
	
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
							<select name="start_time" required>
								<option value=""><?php _e( 'Select Start Time', 'shrinks' ); ?></option>
								<?php
								// Generate 15-minute intervals from 00:00 to 23:45
								for ( $hour = 0; $hour < 24; $hour++ ) {
									for ( $minute = 0; $minute < 60; $minute += 15 ) {
										$time = sprintf( '%02d:%02d:00', $hour, $minute );
										$display_time = date( 'g:i A', strtotime( $time ) );
										echo '<option value="' . esc_attr( $time ) . '">' . esc_html( $display_time ) . '</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'End Time', 'shrinks' ); ?></th>
						<td>
							<select name="end_time" required>
								<option value=""><?php _e( 'Select End Time', 'shrinks' ); ?></option>
								<?php
								// Generate 15-minute intervals from 00:00 to 23:45
								for ( $hour = 0; $hour < 24; $hour++ ) {
									for ( $minute = 0; $minute < 60; $minute += 15 ) {
										$time = sprintf( '%02d:%02d:00', $hour, $minute );
										$display_time = date( 'g:i A', strtotime( $time ) );
										echo '<option value="' . esc_attr( $time ) . '">' . esc_html( $display_time ) . '</option>';
									}
								}
								?>
							</select>
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
			
			<script>
			jQuery(document).ready(function($) {
				// Validate time difference on form submission
				$('form').on('submit', function(e) {
					var startTime = $('select[name="start_time"]').val();
					var endTime = $('select[name="end_time"]').val();
					
					if (startTime && endTime) {
						var startTimestamp = new Date('2000-01-01 ' + startTime);
						var endTimestamp = new Date('2000-01-01 ' + endTime);
						var timeDiffMinutes = (endTimestamp - startTimestamp) / (1000 * 60);
						
						if (timeDiffMinutes !== 15) {
							e.preventDefault();
							alert('<?php _e( 'Error: The difference between start time and end time must be exactly 15 minutes!', 'shrinks' ); ?>');
							return false;
						}
					}
				});
				
				// Auto-select end time when start time is selected
				$('select[name="start_time"]').on('change', function() {
					var startTime = $(this).val();
					if (startTime) {
						var startTimestamp = new Date('2000-01-01 ' + startTime);
						var endTimestamp = new Date(startTimestamp.getTime() + (15 * 60 * 1000)); // Add 15 minutes
						var endTime = endTimestamp.toTimeString().slice(0, 8);
						
						$('select[name="end_time"]').val(endTime);
					}
				});
			});
			</script>
		</div>
		
		<div class="card">
			<h2><?php _e( 'Existing Slots', 'shrinks' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Date', 'shrinks' ); ?></th>
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
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $slot->slot_date ) ) ); ?></td>
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
							<td colspan="7"><?php _e( 'No slots found. Add some slots above.', 'shrinks' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
