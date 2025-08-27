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
	
	// Check if prescription already requested
	$prescription_requested = get_post_meta( $session_data->order_id, '_ai_prescription_requested', true );
	if ( $prescription_requested ) {
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
	
	// Check if prescription already requested
	$order = wc_get_order( $session->order_id );
	if ( $order && $order->get_meta( '_ai_prescription_requested' ) ) {
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
			'diagnosis_id' => 0, // Will be set by Rochtah doctor
			'initial_diagnosis' => $preliminary_diagnosis,
			'symptoms' => $symptoms,
			'booking_date' => $next_slot['date'],
			'booking_time' => $next_slot['time'],
			'status' => 'pending',
			'created_at' => current_time( 'mysql' )
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
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
 * Get next available Rochtah slot (15-minute intervals)
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
			// Get available time slots for this day
			$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
			$available_slots = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $rochtah_appointments_table 
				WHERE day_of_week = %s AND status = 'active'
				ORDER BY start_time ASC",
				$day_of_week
			) );
			
			foreach ( $available_slots as $slot ) {
				// Check if this slot is available (15-minute intervals)
				$start_time = $slot->start_time;
				$end_time = $slot->end_time;
				
				// Generate 15-minute intervals
				$current_time = strtotime( $start_time );
				$end_timestamp = strtotime( $end_time );
				
				while ( $current_time < $end_timestamp ) {
					$interval_time = date( 'H:i:s', $current_time );
					
					// Check if this specific time is available
					$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
					$existing_booking = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM $rochtah_bookings_table 
						WHERE booking_date = %s 
						AND booking_time = %s 
						AND status IN ('pending', 'confirmed')",
						$check_date,
						$interval_time
					) );
					
					if ( ! $existing_booking ) {
						return array(
							'date' => $check_date,
							'time' => $interval_time,
							'day_of_week' => $day_of_week
						);
					}
					
					$current_time = strtotime( '+15 minutes', $current_time );
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
