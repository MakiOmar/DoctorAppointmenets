<?php
/**
 * WhatsApp Notifications for AI Sessions
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Get WhatsApp notification settings
 *
 * @return array
 */
function snks_get_whatsapp_notification_settings() {
	return array(
		'enabled' => get_option( 'snks_ai_notifications_enabled', '1' ),
		'template_new_session' => get_option( 'snks_template_new_session', 'new_session' ),
		'template_doctor_new' => get_option( 'snks_template_doctor_new', 'doctor_new' ),
		'template_rosheta10' => get_option( 'snks_template_rosheta10', 'rosheta10' ),
		'template_rosheta_app' => get_option( 'snks_template_rosheta_app', 'rosheta_app' ),
		'template_patient_rem_24h' => get_option( 'snks_template_patient_rem_24h', 'patient_rem_24h' ),
		'template_patient_rem_1h' => get_option( 'snks_template_patient_rem_1h', 'patient_rem_1h' ),
		'template_patient_rem_now' => get_option( 'snks_template_patient_rem_now', 'patient_rem_now' ),
		'template_doctor_rem' => get_option( 'snks_template_doctor_rem', 'doctor_rem' ),
	);
}

/**
 * Send WhatsApp template message for AI sessions
 *
 * @param string $phone_number Recipient phone number.
 * @param string $template_name Template name.
 * @param array  $parameters Template parameters.
 * @return mixed
 */
function snks_send_whatsapp_template_message( $phone_number, $template_name, $parameters = array() ) {
	// Get WhatsApp API configuration from existing registration settings
	$api_url = get_option( 'snks_whatsapp_api_url', '' );
	$api_token = get_option( 'snks_whatsapp_api_token', '' );
	$phone_number_id = get_option( 'snks_whatsapp_phone_number_id', '' );
	$message_language = get_option( 'snks_whatsapp_message_language', 'ar' );
	
	// Check if WhatsApp API is configured
	if ( empty( $api_url ) || empty( $api_token ) || empty( $phone_number_id ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] API not configured. Please configure in Therapist Registration Settings.' );
		}
		return new WP_Error( 'missing_config', 'WhatsApp API configuration is incomplete' );
	}
	
	// Format phone number (ensure it has proper format without + prefix for API)
	$phone_number = ltrim( $phone_number, '+' );
	
	// Prepare API endpoint
	$api_url = rtrim( $api_url, '/' );
	$endpoint = $api_url . '/' . $phone_number_id . '/messages';
	
	// Determine template language
	$template_language = $message_language === 'en' ? 'en_US' : 'ar';
	
	// Build template components
	$components = array();
	
	if ( ! empty( $parameters ) ) {
		$template_parameters = array();
		foreach ( $parameters as $param_value ) {
			$template_parameters[] = array(
				'type' => 'text',
				'text' => $param_value
			);
		}
		
		$components[] = array(
			'type' => 'body',
			'parameters' => $template_parameters
		);
	}
	
	// Prepare request body
	$body = array(
		'messaging_product' => 'whatsapp',
		'recipient_type' => 'individual',
		'to' => $phone_number,
		'type' => 'template',
		'template' => array(
			'name' => $template_name,
			'category' => 'utility',
			'language' => array(
				'code' => $template_language
			)
		)
	);
	
	if ( ! empty( $components ) ) {
		$body['template']['components'] = $components;
	}
	
	// Prepare headers
	$headers = array(
		'Authorization' => 'Bearer ' . $api_token,
		'Content-Type' => 'application/json',
	);
	
	// Prepare request arguments
	$json_body = wp_json_encode( $body );
	$args = array(
		'headers' => $headers,
		'body' => $json_body,
		'timeout' => 15,
		'blocking' => true,
		'sslverify' => true,
	);
	
	// Debug full request details
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[WhatsApp AI] ========== FULL REQUEST DEBUG ==========' );
		error_log( '[WhatsApp AI] Endpoint URL: ' . $endpoint );
		error_log( '[WhatsApp AI] Method: POST' );
		error_log( '[WhatsApp AI] Headers: ' . json_encode( array(
			'Authorization' => 'Bearer ' . substr( $api_token, 0, 20 ) . '...',
			'Content-Type' => 'application/json'
		) ) );
		error_log( '[WhatsApp AI] Template Name: ' . $template_name );
		error_log( '[WhatsApp AI] Template Language: ' . $template_language );
		error_log( '[WhatsApp AI] Phone Number (to): ' . $phone_number );
		error_log( '[WhatsApp AI] Parameters (values): ' . json_encode( $parameters, JSON_UNESCAPED_UNICODE ) );
		error_log( '[WhatsApp AI] Parameters Count: ' . count( $parameters ) );
		error_log( '[WhatsApp AI] --- Request Body (JSON) ---' );
		error_log( $json_body );
		error_log( '[WhatsApp AI] --- Request Body (Formatted) ---' );
		error_log( json_encode( $body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		error_log( '[WhatsApp AI] ======================================' );
	}
	
	$response = wp_remote_post( $endpoint, $args );
	
	// Check for errors
	if ( is_wp_error( $response ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] Connection Error: ' . $response->get_error_message() );
		}
		return $response;
	}
	
	// Get response body and code
	$response_body = wp_remote_retrieve_body( $response );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_headers = wp_remote_retrieve_headers( $response );
	
	// Debug response
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[WhatsApp AI] ========== RESPONSE DEBUG ==========' );
		error_log( '[WhatsApp AI] Response Code: ' . $response_code );
		error_log( '[WhatsApp AI] Response Headers: ' . json_encode( $response_headers->getAll() ) );
		error_log( '[WhatsApp AI] Response Body: ' . $response_body );
		error_log( '[WhatsApp AI] ======================================' );
	}
	
	// Check response code
	if ( $response_code !== 200 ) {
		$error_data = json_decode( $response_body, true );
		$error_message = 'WhatsApp API request failed';
		
		if ( isset( $error_data['error']['message'] ) ) {
			$error_message = $error_data['error']['message'];
		}
		
		// Debug WhatsApp API errors
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] ========== ERROR ANALYSIS ==========' );
			error_log( '[WhatsApp AI] Error Code: ' . $response_code );
			error_log( '[WhatsApp AI] Error Message: ' . $error_message );
			if ( isset( $error_data['error']['error_data']['details'] ) ) {
				error_log( '[WhatsApp AI] Error Details: ' . $error_data['error']['error_data']['details'] );
			}
			error_log( '[WhatsApp AI] Full Error Response (Formatted): ' );
			error_log( json_encode( $error_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
			error_log( '[WhatsApp AI] ======================================' );
		}
		
		return new WP_Error( 'api_error', $error_message, array( 
			'response_code' => $response_code,
			'response_body' => $response_body,
			'error_data' => $error_data
		) );
	}
	
	// Parse response data
	$response_data = json_decode( $response_body, true );
	
	// Debug success
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		if ( isset( $response_data['messages'][0]['id'] ) ) {
			error_log( '[WhatsApp AI] ✅ Sent - Template: ' . $template_name . ' | Phone: ' . $phone_number . ' | Message ID: ' . $response_data['messages'][0]['id'] );
		}
	}
	
	return $response_data;
}

/**
 * Get user's WhatsApp phone number
 *
 * @param int $user_id User ID.
 * @return string|false
 */
function snks_get_user_whatsapp( $user_id ) {
	// Try billing_phone first
	$phone = get_user_meta( $user_id, 'billing_phone', true );
	
	// Try user_login as fallback
	if ( empty( $phone ) ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$phone = $user->user_login;
		}
	}
	
	// Try whatsapp meta
	if ( empty( $phone ) ) {
		$phone = get_user_meta( $user_id, 'whatsapp', true );
	}
	
	if ( empty( $phone ) ) {
		return false;
	}
	
	// Ensure phone has country code
	if ( strpos( $phone, '+' ) === false && strpos( $phone, '20' ) !== 0 ) {
		$phone = '+20' . ltrim( $phone, '0' );
	}
	
	return $phone;
}

/**
 * Format Arabic day name
 *
 * @param string $date Date string.
 * @return string
 */
function snks_get_arabic_day_name( $date ) {
	$day_names = array(
		'Sunday' => 'الأحد',
		'Monday' => 'الاثنين',
		'Tuesday' => 'الثلاثاء',
		'Wednesday' => 'الأربعاء',
		'Thursday' => 'الخميس',
		'Friday' => 'الجمعة',
		'Saturday' => 'السبت'
	);
	
	$english_day = gmdate( 'l', strtotime( $date ) );
	return isset( $day_names[ $english_day ] ) ? $day_names[ $english_day ] : $english_day;
}

/**
 * Send new session notification to patient (AI sessions only)
 *
 * @param int $session_id Session ID.
 * @return bool
 */
function snks_send_new_session_notification( $session_id ) {
	global $wpdb;
	
	$session = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d",
			$session_id
		)
	);
	
	if ( ! $session || ! snks_is_ai_session( $session_id ) ) {
		return false;
	}
	
	// Check if notification already sent
	if ( $session->whatsapp_new_session_sent ) {
		return false;
	}
	
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return false;
	}
	
	// Get patient phone
	$patient_phone = snks_get_user_whatsapp( $session->client_id );
	if ( ! $patient_phone ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] No phone for patient ID: ' . $session->client_id );
		}
		return false;
	}
	
	// Get doctor name
	$doctor = get_user_by( 'id', $session->user_id );
	$doctor_name = $doctor ? $doctor->display_name : 'المعالج';
	
	// Format date and time
	$day_name = snks_get_arabic_day_name( $session->date_time );
	$date = gmdate( 'Y-m-d', strtotime( $session->date_time ) );
	$time = gmdate( 'h:i a', strtotime( $session->date_time ) );
	
	// Send WhatsApp template
	$result = snks_send_whatsapp_template_message(
		$patient_phone,
		$settings['template_new_session'],
		array( $doctor_name, $day_name, $date, $time )
	);
	
	// Mark as sent
	if ( ! is_wp_error( $result ) ) {
		$wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			array( 'whatsapp_new_session_sent' => 1 ),
			array( 'ID' => $session_id ),
			array( '%d' ),
			array( '%d' )
		);
		return true;
	}
	
	return false;
}

/**
 * Send new booking notification to doctor (AI sessions only)
 *
 * @param int $session_id Session ID.
 * @return bool
 */
function snks_send_doctor_new_booking_notification( $session_id ) {
	global $wpdb;
	
	$session = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d",
			$session_id
		)
	);
	
	if ( ! $session || ! snks_is_ai_session( $session_id ) ) {
		return false;
	}
	
	// Check if notification already sent
	if ( $session->whatsapp_doctor_notified ) {
		return false;
	}
	
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return false;
	}
	
	// Get doctor phone
	$doctor_phone = snks_get_user_whatsapp( $session->user_id );
	if ( ! $doctor_phone ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] No phone for doctor ID: ' . $session->user_id );
		}
		return false;
	}
	
	// Get patient name
	$patient = get_user_by( 'id', $session->client_id );
	$patient_name = $patient ? $patient->display_name : 'المريض';
	
	// Format date and time
	$day_name = snks_get_arabic_day_name( $session->date_time );
	$date = gmdate( 'Y-m-d', strtotime( $session->date_time ) );
	$time = gmdate( 'h:i a', strtotime( $session->date_time ) );
	
	// Send WhatsApp template
	$result = snks_send_whatsapp_template_message(
		$doctor_phone,
		$settings['template_doctor_new'],
		array( $patient_name, $day_name, $date, $time )
	);
	
	// Mark as sent
	if ( ! is_wp_error( $result ) ) {
		$wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			array( 'whatsapp_doctor_notified' => 1 ),
			array( 'ID' => $session_id ),
			array( '%d' ),
			array( '%d' )
		);
		return true;
	}
	
	return false;
}

/**
 * Send rosheta activation notification to patient
 *
 * @param int $patient_id Patient ID.
 * @param int $doctor_id Doctor ID.
 * @param int $booking_id Optional booking ID to mark as sent.
 * @return bool
 */
function snks_send_rosheta_activation_notification( $patient_id, $doctor_id, $booking_id = 0 ) {
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return false;
	}
	
	// Get patient phone
	$patient_phone = snks_get_user_whatsapp( $patient_id );
	if ( ! $patient_phone ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] No phone for patient ID: ' . $patient_id );
		}
		return false;
	}
	
	// Get patient and doctor names
	$patient = get_user_by( 'id', $patient_id );
	$patient_name = $patient ? $patient->display_name : 'المريض';
	
	$doctor = get_user_by( 'id', $doctor_id );
	$doctor_name = $doctor ? $doctor->display_name : 'المعالج';
	
	// Send WhatsApp template
	$result = snks_send_whatsapp_template_message(
		$patient_phone,
		$settings['template_rosheta10'],
		array( $patient_name, $doctor_name )
	);
	
	// Mark as sent if booking_id is provided
	if ( ! is_wp_error( $result ) && $booking_id > 0 ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'snks_rochtah_bookings',
			array( 'whatsapp_activation_sent' => 1 ),
			array( 'id' => $booking_id ),
			array( '%d' ),
			array( '%d' )
		);
	}
	
	return ! is_wp_error( $result );
}

/**
 * Send rosheta appointment confirmation notification
 *
 * @param int $booking_id Rosheta booking ID.
 * @return bool
 */
function snks_send_rosheta_appointment_notification( $booking_id ) {
	global $wpdb;
	
	$booking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_rochtah_bookings WHERE id = %d",
			$booking_id
		)
	);
	
	if ( ! $booking ) {
		return false;
	}
	
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return false;
	}
	
	// Get patient phone
	$patient_phone = snks_get_user_whatsapp( $booking->patient_id );
	if ( ! $patient_phone ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] No phone for patient ID: ' . $booking->patient_id );
		}
		return false;
	}
	
	// Format date and time
	$day_name = snks_get_arabic_day_name( $booking->booking_date );
	$date = $booking->booking_date;
	$time = gmdate( 'h:i a', strtotime( $booking->booking_time ) );
	
	// Send WhatsApp template
	$result = snks_send_whatsapp_template_message(
		$patient_phone,
		$settings['template_rosheta_app'],
		array( $day_name, $date, $time )
	);
	
	// Mark as sent
	if ( ! is_wp_error( $result ) ) {
		$wpdb->update(
			$wpdb->prefix . 'snks_rochtah_bookings',
			array( 'whatsapp_appointment_sent' => 1 ),
			array( 'id' => $booking_id ),
			array( '%d' ),
			array( '%d' )
		);
		return true;
	}
	
	return false;
}

/**
 * Hook: Send notifications when AI appointment is created
 */
add_action( 'snks_appointment_created', 'snks_handle_ai_appointment_notifications', 10, 2 );

function snks_handle_ai_appointment_notifications( $slot_id, $appointment_data ) {
	// Only process AI sessions
	if ( empty( $appointment_data['is_ai_session'] ) ) {
		return;
	}
	
	// Send patient notification
	snks_send_new_session_notification( $slot_id );
	
	// Send doctor notification
	snks_send_doctor_new_booking_notification( $slot_id );
}

/**
 * Send notification to patient when doctor joins session (AI sessions only)
 *
 * @param int $session_id Session ID.
 * @return bool
 */
function snks_send_doctor_joined_notification( $session_id ) {
	global $wpdb;
	
	$session = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d",
			$session_id
		)
	);
	
	if ( ! $session || ! snks_is_ai_session( $session_id ) ) {
		return false;
	}
	
	// Check if notification already sent
	if ( $session->whatsapp_patient_now_sent ) {
		return false;
	}
	
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return false;
	}
	
	// Get patient phone
	$patient_phone = snks_get_user_whatsapp( $session->client_id );
	if ( ! $patient_phone ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WhatsApp AI] No phone for patient ID: ' . $session->client_id );
		}
		return false;
	}
	
	// Send WhatsApp template (no parameters)
	$result = snks_send_whatsapp_template_message(
		$patient_phone,
		$settings['template_patient_rem_now'],
		array()
	);
	
	// Mark as sent
	if ( ! is_wp_error( $result ) ) {
		$wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			array( 'whatsapp_patient_now_sent' => 1 ),
			array( 'ID' => $session_id ),
			array( '%d' ),
			array( '%d' )
		);
		return true;
	}
	
	return false;
}

/**
 * Send midnight reminder to doctors with AI sessions tomorrow
 */
function snks_send_doctor_midnight_reminders() {
	global $wpdb;
	
	$settings = snks_get_whatsapp_notification_settings();
	if ( $settings['enabled'] != '1' ) {
		return;
	}
	
	// Get tomorrow's date
	$tomorrow_date = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
	
	// Get all doctors with AI sessions tomorrow
	$doctors_with_sessions = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT user_id 
			FROM {$wpdb->prefix}snks_provider_timetable
			WHERE DATE(date_time) = %s
			AND session_status = 'open'
			AND settings LIKE %s
			AND whatsapp_doctor_reminded = 0",
			$tomorrow_date,
			'%ai_booking%'
		)
	);
	
	foreach ( $doctors_with_sessions as $doctor_row ) {
		$doctor_id = $doctor_row->user_id;
		
		// Get doctor phone
		$doctor_phone = snks_get_user_whatsapp( $doctor_id );
		if ( ! $doctor_phone ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[WhatsApp AI] No phone for doctor ID: ' . $doctor_id );
			}
			continue;
		}
		
		// Format date
		$day_name = snks_get_arabic_day_name( $tomorrow_date );
		$date = $tomorrow_date;
		
		// Send WhatsApp template
		$result = snks_send_whatsapp_template_message(
			$doctor_phone,
			$settings['template_doctor_rem'],
			array( $day_name, $date )
		);
		
		// Mark all doctor's tomorrow sessions as reminded
		if ( ! is_wp_error( $result ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}snks_provider_timetable
					SET whatsapp_doctor_reminded = 1
					WHERE user_id = %d
					AND DATE(date_time) = %s
					AND settings LIKE %s",
					$doctor_id,
					$tomorrow_date,
					'%ai_booking%'
				)
			);
		}
	}
}

/**
 * Schedule midnight doctor reminder cron
 */
if ( ! wp_next_scheduled( 'snks_send_doctor_midnight_reminders' ) ) {
	// Schedule to run daily at midnight
	wp_schedule_event( strtotime( 'midnight' ), 'daily', 'snks_send_doctor_midnight_reminders' );
}
add_action( 'snks_send_doctor_midnight_reminders', 'snks_send_doctor_midnight_reminders' );

