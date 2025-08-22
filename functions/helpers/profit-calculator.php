<?php
/**
 * Profit Calculator Helper Functions
 * 
 * Handles AI session profit calculation and transaction management
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Get therapist profit settings
 * 
 * @param int $therapist_id The therapist user ID
 * @return array|false Array with profit settings or false if not found
 */
function snks_get_therapist_profit_settings( $therapist_id ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
	
	$settings = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE therapist_id = %d AND is_active = 1",
		$therapist_id
	), ARRAY_A );
	
	if ( ! $settings ) {
		// Return default settings if not found
		return array(
			'first_session_percentage' => 70.00,
			'subsequent_session_percentage' => 75.00,
			'is_active' => 1
		);
	}
	
	return $settings;
}

/**
 * Determine if session is first or subsequent for a therapist-patient pair
 * 
 * @param int $therapist_id The therapist user ID
 * @param int $patient_id The patient user ID
 * @return string 'first' or 'subsequent'
 */
function snks_is_first_session( $therapist_id, $patient_id ) {
	global $wpdb;
	
	// Check if there are any previous AI sessions between this therapist and patient
	$previous_sessions = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}snks_sessions_actions 
		 WHERE therapist_id = %d AND patient_id = %d AND ai_session_type IS NOT NULL",
		$therapist_id,
		$patient_id
	) );
	
	return ( $previous_sessions > 0 ) ? 'subsequent' : 'first';
}

/**
 * Calculate profit for an AI session
 * 
 * @param float $session_amount The total session amount
 * @param int $therapist_id The therapist user ID
 * @param int $patient_id The patient user ID
 * @return float The calculated profit amount
 */
function snks_calculate_session_profit( $session_amount, $therapist_id, $patient_id ) {
	// Get therapist profit settings
	$settings = snks_get_therapist_profit_settings( $therapist_id );
	
	// Determine if this is first or subsequent session
	$session_type = snks_is_first_session( $therapist_id, $patient_id );
	
	// Get the appropriate percentage
	$percentage = ( $session_type === 'first' ) 
		? $settings['first_session_percentage'] 
		: $settings['subsequent_session_percentage'];
	
	// Calculate profit
	$profit_amount = ( $session_amount * $percentage ) / 100;
	
	return round( $profit_amount, 2 );
}

/**
 * Add AI session transaction to existing system
 * 
 * @param int $therapist_id The therapist user ID
 * @param array $session_data Session data array
 * @param float $profit_amount The calculated profit amount
 * @return int|false Transaction ID on success, false on failure
 */
function snks_add_ai_session_transaction( $therapist_id, $session_data, $profit_amount ) {
	global $wpdb;
	
	// Use existing transaction system
	$transaction_id = snks_add_transaction( 
		$therapist_id, 
		0, // timetable_id (0 for AI sessions)
		'add', // transaction_type
		$profit_amount 
	);
	
	if ( $transaction_id ) {
		// Add AI session metadata to the transaction
		$wpdb->update(
			$wpdb->prefix . 'snks_booking_transactions',
			array(
				'ai_session_id' => $session_data['session_id'] ?? 0,
				'ai_session_type' => $session_data['session_type'] ?? 'first',
				'ai_patient_id' => $session_data['patient_id'] ?? 0,
				'ai_order_id' => $session_data['order_id'] ?? 0
			),
			array( 'id' => $transaction_id ),
			array( '%d', '%s', '%d', '%d' ),
			array( '%d' )
		);
		
		// Log the transaction
		snks_log_transaction( $transaction_id, 'ai_session_profit', array(
			'therapist_id' => $therapist_id,
			'patient_id' => $session_data['patient_id'] ?? 0,
			'session_amount' => $session_data['session_amount'] ?? 0,
			'profit_amount' => $profit_amount,
			'session_type' => $session_data['session_type'] ?? 'first'
		) );
	}
	
	return $transaction_id;
}

/**
 * Execute profit transfer for AI session
 * 
 * @param string $session_id The session ID
 * @return array Result array with success status and message
 */
function snks_execute_ai_profit_transfer( $session_id ) {
	global $wpdb;
	
	// Get session data
	$session_data = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
		$session_id
	), ARRAY_A );
	
	if ( ! $session_data ) {
		return array(
			'success' => false,
			'message' => 'Session not found'
		);
	}
	
	// Check if profit already transferred
	if ( ! empty( $session_data['ai_session_type'] ) ) {
		return array(
			'success' => false,
			'message' => 'Profit already transferred for this session'
		);
	}
	
	// Get session details from AI order
	$order_id = $session_data['case_id'];
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		return array(
			'success' => false,
			'message' => 'Order not found'
		);
	}
	
	// Check if it's an AI session
	if ( ! $order->get_meta( 'from_jalsah_ai' ) ) {
		return array(
			'success' => false,
			'message' => 'Not an AI session'
		);
	}
	
	// Get session amount
	$session_amount = $order->get_total();
	
	// Get therapist and patient IDs
	$therapist_id = $order->get_meta( 'ai_therapist_id' ) ?: $order->get_meta( 'therapist_id' );
	$patient_id = $order->get_meta( 'ai_user_id' ) ?: $order->get_customer_id();
	
	if ( ! $therapist_id || ! $patient_id ) {
		return array(
			'success' => false,
			'message' => 'Missing therapist or patient information'
		);
	}
	
	// Calculate profit
	$profit_amount = snks_calculate_session_profit( $session_amount, $therapist_id, $patient_id );
	
	// Determine session type
	$session_type = snks_is_first_session( $therapist_id, $patient_id );
	
	// Prepare session data
	$session_data_for_transaction = array(
		'session_id' => $session_id,
		'session_type' => $session_type,
		'patient_id' => $patient_id,
		'order_id' => $order_id,
		'session_amount' => $session_amount
	);
	
	// Add transaction
	$transaction_id = snks_add_ai_session_transaction( $therapist_id, $session_data_for_transaction, $profit_amount );
	
	if ( ! $transaction_id ) {
		return array(
			'success' => false,
			'message' => 'Failed to create transaction'
		);
	}
	
	// Update session actions table
	$wpdb->update(
		$wpdb->prefix . 'snks_sessions_actions',
		array(
			'ai_session_type' => $session_type,
			'therapist_id' => $therapist_id,
			'patient_id' => $patient_id
		),
		array( 'action_session_id' => $session_id ),
		array( '%s', '%d', '%d' ),
		array( '%s' )
	);
	
	return array(
		'success' => true,
		'message' => 'Profit transfer completed successfully',
		'transaction_id' => $transaction_id,
		'profit_amount' => $profit_amount,
		'session_type' => $session_type
	);
}

/**
 * Check if a session is an AI session
 * 
 * @param string $session_id The session ID
 * @return bool True if AI session, false otherwise
 */
function snks_is_ai_session( $session_id ) {
	global $wpdb;
	
	$session_data = $wpdb->get_row( $wpdb->prepare(
		"SELECT case_id FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
		$session_id
	) );
	
	if ( ! $session_data ) {
		return false;
	}
	
	$order = wc_get_order( $session_data->case_id );
	
	return $order && $order->get_meta( 'from_jalsah_ai' );
}

/**
 * Get AI session profit statistics for a therapist
 * 
 * @param int $therapist_id The therapist user ID
 * @param string $period Period to calculate stats for ('all', 'month', 'week', 'day')
 * @return array Statistics array
 */
function snks_get_ai_session_profit_stats( $therapist_id, $period = 'all' ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_booking_transactions';
	
	// Build date filter
	$date_filter = '';
	switch ( $period ) {
		case 'day':
			$date_filter = $wpdb->prepare( "AND DATE(transaction_time) = %s", current_time( 'Y-m-d' ) );
			break;
		case 'week':
			$date_filter = $wpdb->prepare( "AND YEARWEEK(transaction_time) = YEARWEEK(%s)", current_time( 'Y-m-d' ) );
			break;
		case 'month':
			$date_filter = $wpdb->prepare( "AND YEAR(transaction_time) = YEAR(%s) AND MONTH(transaction_time) = MONTH(%s)", current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) );
			break;
	}
	
	// Get total profit
	$total_profit = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(amount) FROM $table_name 
		 WHERE user_id = %d AND transaction_type = 'add' AND ai_session_id IS NOT NULL $date_filter",
		$therapist_id
	) );
	
	// Get session counts
	$first_sessions = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM $table_name 
		 WHERE user_id = %d AND transaction_type = 'add' AND ai_session_type = 'first' $date_filter",
		$therapist_id
	) );
	
	$subsequent_sessions = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM $table_name 
		 WHERE user_id = %d AND transaction_type = 'add' AND ai_session_type = 'subsequent' $date_filter",
		$therapist_id
	) );
	
	return array(
		'total_profit' => $total_profit ?: 0,
		'first_sessions' => $first_sessions ?: 0,
		'subsequent_sessions' => $subsequent_sessions ?: 0,
		'total_sessions' => ( $first_sessions ?: 0 ) + ( $subsequent_sessions ?: 0 ),
		'period' => $period
	);
}

/**
 * Update therapist profit settings
 * 
 * @param int $therapist_id The therapist user ID
 * @param array $settings Settings array
 * @return bool Success status
 */
function snks_update_therapist_profit_settings( $therapist_id, $settings ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
	
	// Check if settings exist
	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $table_name WHERE therapist_id = %d",
		$therapist_id
	) );
	
	if ( $exists ) {
		// Update existing settings
		return $wpdb->update(
			$table_name,
			array(
				'first_session_percentage' => $settings['first_session_percentage'],
				'subsequent_session_percentage' => $settings['subsequent_session_percentage'],
				'is_active' => $settings['is_active'] ?? 1,
				'updated_at' => current_time( 'mysql' )
			),
			array( 'therapist_id' => $therapist_id ),
			array( '%f', '%f', '%d', '%s' ),
			array( '%d' )
		);
	} else {
		// Insert new settings
		return $wpdb->insert(
			$table_name,
			array(
				'therapist_id' => $therapist_id,
				'first_session_percentage' => $settings['first_session_percentage'],
				'subsequent_session_percentage' => $settings['subsequent_session_percentage'],
				'is_active' => $settings['is_active'] ?? 1
			),
			array( '%d', '%f', '%f', '%d' )
		);
	}
}
