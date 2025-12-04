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
	
	// Get global settings first (we'll use these as fallback)
	$global_settings = get_option( 'snks_ai_profit_global_settings', array() );
	
	// Check if individual settings exist (regardless of is_active)
	$individual_settings = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE therapist_id = %d",
		$therapist_id
	), ARRAY_A );
	
	// If individual settings exist and are active, validate they have values
	if ( $individual_settings && ! empty( $individual_settings['is_active'] ) ) {
		$first_percentage = isset( $individual_settings['first_session_percentage'] ) ? floatval( $individual_settings['first_session_percentage'] ) : 0;
		$subsequent_percentage = isset( $individual_settings['subsequent_session_percentage'] ) ? floatval( $individual_settings['subsequent_session_percentage'] ) : 0;
		
		// If individual settings have valid values (greater than 0), use them
		if ( $first_percentage > 0 && $subsequent_percentage > 0 ) {
			error_log( sprintf(
				'AI-PROFIT DEBUG: Using ACTIVE INDIVIDUAL profit settings for therapist_id=%d: first=%0.2f, subsequent=%0.2f, is_active=%d',
				$therapist_id,
				$first_percentage,
				$subsequent_percentage,
				$individual_settings['is_active']
			) );
			return $individual_settings;
		} else {
			// Individual settings are active but missing values, use global settings
			if ( ! empty( $global_settings ) && isset( $global_settings['default_first_percentage'] ) && isset( $global_settings['default_subsequent_percentage'] ) ) {
				$settings = array(
					'first_session_percentage' => floatval( $global_settings['default_first_percentage'] ),
					'subsequent_session_percentage' => floatval( $global_settings['default_subsequent_percentage'] ),
			'is_active' => 1
		);
				error_log( sprintf(
					'AI-PROFIT DEBUG: Using GLOBAL profit settings for therapist_id=%d: first=%0.2f, subsequent=%0.2f (individual settings active but missing values)',
					$therapist_id,
					$settings['first_session_percentage'],
					$settings['subsequent_session_percentage']
				) );
				return $settings;
			}
		}
	}
	
	// If individual settings exist but are inactive, or if they don't exist, use global settings
	if ( ! empty( $global_settings ) && isset( $global_settings['default_first_percentage'] ) && isset( $global_settings['default_subsequent_percentage'] ) ) {
		$settings = array(
			'first_session_percentage' => floatval( $global_settings['default_first_percentage'] ),
			'subsequent_session_percentage' => floatval( $global_settings['default_subsequent_percentage'] ),
			'is_active' => 1
		);
		error_log( sprintf(
			'AI-PROFIT DEBUG: Using GLOBAL profit settings for therapist_id=%d: first=%0.2f, subsequent=%0.2f (individual settings %s)',
			$therapist_id,
			$settings['first_session_percentage'],
			$settings['subsequent_session_percentage'],
			$individual_settings ? 'exist but are inactive' : 'do not exist'
		) );
	return $settings;
	}
	
	// If global settings are not configured, return error/warning (no hardcoded fallback)
	error_log( sprintf(
		'AI-PROFIT ERROR: No profit settings configured for therapist_id=%d. Global settings not found. Please configure global profit settings.',
		$therapist_id
	) );
	
	// Return empty array to indicate no settings available (caller should handle this)
	return array(
		'first_session_percentage' => 0,
		'subsequent_session_percentage' => 0,
		'is_active' => 0,
		'error' => 'No profit settings configured'
	);
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
	
	$session_type = ( $previous_sessions > 0 ) ? 'subsequent' : 'first';
	
	return $session_type;
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
	
	// Validate settings exist and have valid values
	if ( empty( $settings ) || isset( $settings['error'] ) || 
		 empty( $settings['first_session_percentage'] ) || 
		 empty( $settings['subsequent_session_percentage'] ) ) {
		error_log( sprintf(
			'AI-PROFIT ERROR: Cannot calculate profit for therapist_id=%d - profit settings not configured. Please configure global profit settings.',
			$therapist_id
		) );
		return 0; // Return 0 profit if settings are not configured
	}
	
	// Determine if this is first or subsequent session
	$session_type = snks_is_first_session( $therapist_id, $patient_id );
	
	// Get the appropriate percentage
	$percentage = ( $session_type === 'first' ) 
		? $settings['first_session_percentage'] 
		: $settings['subsequent_session_percentage'];
	
	// Calculate profit
	$profit_amount = ( $session_amount * $percentage ) / 100;
	$rounded_profit = round( $profit_amount, 2 );
	
	return $rounded_profit;
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
	$transaction_id = snks_add_transaction( 
		$therapist_id, 
		0, // timetable_id (0 for AI sessions)
		'add', // transaction_type
		$profit_amount 
	);
	
	if ( $transaction_id ) {
		// Calculate admin profit (website share)
		$session_amount = $session_data['session_amount'] ?? 0;
		$admin_profit = $session_amount - $profit_amount;
		
		// Add AI session metadata to the transaction
		$metadata = array(
			'ai_session_id' => $session_data['session_id'] ?? 0,
			'ai_session_type' => $session_data['session_type'] ?? 'first',
			'ai_patient_id' => $session_data['patient_id'] ?? 0,
			'ai_order_id' => $session_data['order_id'] ?? 0,
			'ai_session_amount' => $session_amount,
			'ai_admin_profit' => round( $admin_profit, 2 )
		);
		
		$update_result = $wpdb->update(
			$wpdb->prefix . 'snks_booking_transactions',
			$metadata,
			array( 'id' => $transaction_id ),
			array( '%d', '%s', '%d', '%d', '%f', '%f' ),
			array( '%d' )
		);
		

		$log_result = snks_log_transaction( $therapist_id, $profit_amount, 'ai_session_profit' );
		
	}
	
	return $transaction_id;
}

/**
 * Create AI earnings transaction from timetable session data
 * Called when session status is changed to 'completed' in timetable
 * 
 * @param object $timetable_session The timetable session object
 * @return array Result array with success status and message
 */
function snks_create_ai_earnings_from_timetable( $timetable_session ) {
	global $wpdb;
	
	// Check if it's an AI session
	$is_ai_session = ( strpos( $timetable_session->settings, 'ai_booking' ) !== false );


	
	if ( ! $is_ai_session || ! $timetable_session->order_id ) {

		return array(
			'success' => false,
			'message' => 'Not an AI session or missing order ID'
		);
	}
	
	// Check if transaction already exists for this specific session (by session_id, not just order_id)

	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE ai_session_id = %d AND transaction_type = 'add'",
		$timetable_session->ID
	) );

	
	if ( $existing > 0 ) {

		return array(
			'success' => false,
			'message' => 'Earnings already created for this session'
		);
	}
	
	// Also check by order_id AND session_id as a secondary safeguard (to handle cases where session_id might not be set correctly)

	$existing_by_order_and_session = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE ai_order_id = %d AND ai_session_id = %d AND transaction_type = 'add'",
		$timetable_session->order_id,
		$timetable_session->ID
	) );

	
	if ( $existing_by_order_and_session > 0 ) {

		return array(
			'success' => false,
			'message' => 'Earnings already created for this session'
		);
	}
	
	// Get order details

	$order = wc_get_order( $timetable_session->order_id );
	if ( ! $order ) {

		return array(
			'success' => false,
			'message' => 'Order not found'
		);
	}

	
	// Get therapist and patient IDs
	$therapist_id = $timetable_session->user_id;
	$patient_id = $timetable_session->client_id;

	
	if ( ! $therapist_id || ! $patient_id ) {

		return array(
			'success' => false,
			'message' => 'Missing therapist or patient information'
		);
	}
	
	// Get session amount from order
	$session_amount = $order->get_total();

	
	// Calculate profit

	$profit_amount = snks_calculate_session_profit( $session_amount, $therapist_id, $patient_id );

	
	// Determine session type

	$session_type = snks_is_first_session( $therapist_id, $patient_id );

	
	// Prepare session data for transaction
	$session_data = array(
		'session_id' => $timetable_session->ID, // Use timetable ID as session ID
		'session_type' => $session_type,
		'patient_id' => $patient_id,
		'order_id' => $timetable_session->order_id,
		'session_amount' => $session_amount
	);
	
	// Add transaction


	$transaction_id = snks_add_ai_session_transaction( $therapist_id, $session_data, $profit_amount );

	
	if ( ! $transaction_id ) {


		return array(
			'success' => false,
			'message' => 'Failed to create transaction'
		);
	}

	
	// Create or update session_actions entry with proper AI session metadata
	$actions_table = $wpdb->prefix . 'snks_sessions_actions';
	$existing_action = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$actions_table} WHERE action_session_id = %d AND case_id = %d",
		$timetable_session->ID,
		$timetable_session->order_id
	) );
	
	if ( $existing_action ) {
		// Update existing entry with AI session type
		$wpdb->update(
			$actions_table,
			array( 
				'ai_session_type' => $session_type,
				'session_status' => 'completed'
			),
			array( 'id' => $existing_action->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	} else {
		// Create new session_actions entry for AI session
		$wpdb->insert(
			$actions_table,
			array(
				'action_session_id' => $timetable_session->ID,
				'case_id' => $timetable_session->order_id,
				'therapist_id' => $therapist_id,
				'patient_id' => $patient_id,
				'ai_session_type' => $session_type,
				'session_status' => 'completed',
				'attendance' => 'yes',
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
	}
	
	
	return array(
		'success' => true,
		'transaction_id' => $transaction_id,
		'message' => 'Earnings created successfully'
	);
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
	
	// Check if profit already transferred (ai_session_type should be NULL initially)

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

	
	// Check if it's an AI session (support both meta keys)
	$is_ai_session = $order->get_meta( 'is_ai_session' );
	$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );

	
	if ( ! $is_ai_session && ! $from_jalsah_ai ) {

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

	$update_result = $wpdb->update(
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
 * @param mixed $session_data The session ID (integer) or session object
 * @return bool True if AI session, false otherwise
 */
function snks_is_ai_session( $session_data ) {
	global $wpdb;
	
	// Handle different input types
	if ( is_numeric( $session_data ) ) {
		// Input is session ID - get session object from timetable table first
		$session = $wpdb->get_row( $wpdb->prepare(
			"SELECT settings FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %s",
			$session_data
		) );
		
		if ( $session && strpos( $session->settings, 'ai_booking' ) !== false ) {
			return true;
		}
		
		// Fallback to sessions_actions table for backward compatibility
		$session_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT case_id FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
			$session_data
		) );
		
		if ( ! $session_data ) {
			return false;
		}
		
		$order = wc_get_order( $session_data->case_id );
		
		if ( ! $order ) {
			return false;
		}
		
		// Check for both AI session meta keys
		$is_ai_session = $order->get_meta( 'is_ai_session' );
		$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
		
		return $is_ai_session || $from_jalsah_ai;
		
	} elseif ( is_object( $session_data ) ) {
		// Input is session object - check settings property first
		if ( isset( $session_data->settings ) && strpos( $session_data->settings, 'ai_booking' ) !== false ) {
			return true;
		}
		
		// Fallback to order meta if available
		if ( isset( $session_data->case_id ) && $session_data->case_id > 0 ) {
			$order = wc_get_order( $session_data->case_id );
			
			if ( $order ) {
				$is_ai_session = $order->get_meta( 'is_ai_session' );
				$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
				
				return $is_ai_session || $from_jalsah_ai;
			}
		}
		
		return false;
	}
	
	return false;
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
		// Handle NULL values properly - build query parts separately
		$set_clauses = array();
		$prepare_values = array();
		$prepare_formats = array();
		
		// Handle first_session_percentage
		if ( $settings['first_session_percentage'] !== null ) {
			$set_clauses[] = 'first_session_percentage = %f';
			$prepare_values[] = $settings['first_session_percentage'];
			$prepare_formats[] = '%f';
	} else {
			$set_clauses[] = 'first_session_percentage = NULL';
		}
		
		// Handle subsequent_session_percentage
		if ( $settings['subsequent_session_percentage'] !== null ) {
			$set_clauses[] = 'subsequent_session_percentage = %f';
			$prepare_values[] = $settings['subsequent_session_percentage'];
			$prepare_formats[] = '%f';
		} else {
			$set_clauses[] = 'subsequent_session_percentage = NULL';
		}
		
		// Add is_active and updated_at (always have values)
		$set_clauses[] = 'is_active = %d';
		$prepare_values[] = $settings['is_active'] ?? 1;
		$prepare_formats[] = '%d';
		$set_clauses[] = 'updated_at = %s';
		$prepare_values[] = current_time( 'mysql' );
		$prepare_formats[] = '%s';
		
		// Build the query - replace placeholders manually for NULL fields, use prepare for others
		$sql = "UPDATE $table_name SET " . implode( ', ', $set_clauses ) . " WHERE therapist_id = %d";
		$prepare_values[] = $therapist_id;
		$prepare_formats[] = '%d';
		
		// Use prepare only for the values that need it
		if ( ! empty( $prepare_values ) ) {
			return $wpdb->query( $wpdb->prepare( $sql, $prepare_values ) );
		} else {
			// Fallback if somehow no values (shouldn't happen)
			return $wpdb->query( $sql );
		}
	} else {
		// Insert new settings - only insert if we have non-null values or is_active
		$insert_data = array(
				'therapist_id' => $therapist_id,
				'is_active' => $settings['is_active'] ?? 1
		);
		$insert_format = array( '%d', '%d' );
		
		if ( $settings['first_session_percentage'] !== null ) {
			$insert_data['first_session_percentage'] = $settings['first_session_percentage'];
			$insert_format[] = '%f';
		}
		
		if ( $settings['subsequent_session_percentage'] !== null ) {
			$insert_data['subsequent_session_percentage'] = $settings['subsequent_session_percentage'];
			$insert_format[] = '%f';
		}
		
		return $wpdb->insert(
			$table_name,
			$insert_data,
			$insert_format
		);
	}
}

/**
 * Process AI session completion and execute profit transfer
 */
function snks_process_ai_session_completion( $session_id ) {
	global $wpdb;
	
	// Check if this is an AI session
	if ( ! snks_is_ai_session( $session_id ) ) {
		return array(
			'success' => false,
			'message' => 'Session is not an AI session'
		);
	}
	
	// Check if profit transfer already processed
	$existing_transaction = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE ai_session_id = %s AND transaction_type = 'add'",
		$session_id
	) );
	
	if ( $existing_transaction ) {
		return array(
			'success' => false,
			'message' => 'Profit transfer already processed for this session'
		);
	}
	
	// Execute profit transfer
	$result = snks_execute_ai_profit_transfer( $session_id );
	
	if ( $result['success'] ) {
		// Log successful completion
		snks_ai_session_completion_notification( $session_id, $result );
		
		return $result;
	} else {
		// Log error
		return $result;
	}
}

/**
 * Get AI session balance for a therapist
 */
function snks_get_ai_session_balance( $therapist_id ) {
	global $wpdb;
	
	$balance = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(amount) FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE user_id = %d AND ai_session_id IS NOT NULL AND transaction_type = 'add'",
		$therapist_id
	) );
	
	return $balance ?: 0;
}

/**
 * Get AI session withdrawal balance (available for withdrawal)
 */
function snks_get_ai_session_withdrawal_balance( $therapist_id ) {
	global $wpdb;
	
	$withdrawal_balance = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(amount) FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE user_id = %d AND ai_session_id IS NOT NULL AND transaction_type = 'add' 
		 AND processed_for_withdrawal = 0",
		$therapist_id
	) );
	
	return $withdrawal_balance ?: 0;
}

/**
 * Process AI session withdrawal for a therapist
 */
function snks_process_ai_session_withdrawal( $therapist_id, $amount, $withdrawal_method = 'wallet' ) {
	global $wpdb;
	
	// Get available balance
	$available_balance = snks_get_ai_session_withdrawal_balance( $therapist_id );
	
	if ( $amount > $available_balance ) {
		return array(
			'success' => false,
			'message' => 'Insufficient balance for withdrawal'
		);
	}
	
	// Get therapist details
	$therapist = get_user_by( 'ID', $therapist_id );
	if ( ! $therapist ) {
		return array(
			'success' => false,
			'message' => 'Therapist not found'
		);
	}
	
	// Process withdrawal using existing system
	$withdrawal_result = process_user_withdrawal( $therapist_id, $amount, $withdrawal_method );
	
	if ( $withdrawal_result['success'] ) {
		// Mark AI transactions as processed for withdrawal
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}snks_booking_transactions 
			 SET processed_for_withdrawal = 1 
			 WHERE user_id = %d AND ai_session_id IS NOT NULL 
			 AND transaction_type = 'add' AND processed_for_withdrawal = 0
			 LIMIT %d",
			$therapist_id,
			ceil( $amount / 100 ) // Approximate number of transactions to mark
		) );
		
		// Log the withdrawal
		snks_log_transaction( $therapist_id, 'withdrawal', $amount, "AI Session Withdrawal via {$withdrawal_method}" );
		
		return array(
			'success' => true,
			'message' => 'Withdrawal processed successfully',
			'withdrawal_id' => $withdrawal_result['withdrawal_id'] ?? null,
			'amount' => $amount,
			'method' => $withdrawal_method
		);
	} else {
		return array(
			'success' => false,
			'message' => 'Withdrawal failed: ' . ( $withdrawal_result['message'] ?? 'Unknown error' )
		);
	}
}

/**
 * Get AI session transaction history for a therapist
 */
function snks_get_ai_session_transaction_history( $therapist_id, $limit = 50 ) {
	global $wpdb;
	
	$transactions = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.*, 
		        u.display_name as patient_name,
		        u.user_email as patient_email
		 FROM {$wpdb->prefix}snks_booking_transactions t
		 LEFT JOIN {$wpdb->users} u ON t.ai_patient_id = u.ID
		 WHERE t.user_id = %d AND t.ai_session_id IS NOT NULL
		 ORDER BY t.transaction_time DESC
		 LIMIT %d",
		$therapist_id,
		$limit
	), ARRAY_A );
	
	return $transactions;
}

/**
 * Get AI session statistics for a specific period
 */
function snks_get_ai_session_period_statistics( $therapist_id, $start_date, $end_date ) {
	global $wpdb;
	
	$stats = $wpdb->get_row( $wpdb->prepare(
		"SELECT 
			COUNT(*) as total_sessions,
			SUM(CASE WHEN ai_session_type = 'first' THEN 1 ELSE 0 END) as first_sessions,
			SUM(CASE WHEN ai_session_type = 'subsequent' THEN 1 ELSE 0 END) as subsequent_sessions,
			SUM(amount) as total_profit,
			AVG(amount) as average_profit
		 FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE user_id = %d 
		 AND ai_session_id IS NOT NULL 
		 AND transaction_type = 'add'
		 AND DATE(transaction_time) BETWEEN %s AND %s",
		$therapist_id,
		$start_date,
		$end_date
	), ARRAY_A );
	
	return $stats ?: array(
		'total_sessions' => 0,
		'first_sessions' => 0,
		'subsequent_sessions' => 0,
		'total_profit' => 0,
		'average_profit' => 0
	);
}

/**
 * Validate AI session data before processing
 */
function snks_validate_ai_session_data( $session_id ) {
	global $wpdb;
	
	// Get session data
	$session_data = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
		$session_id
	), ARRAY_A );
	
	if ( ! $session_data ) {
		return array(
			'valid' => false,
			'message' => 'Session not found'
		);
	}
	
	// Check if session is completed
	if ( $session_data['session_status'] !== 'completed' ) {
		return array(
			'valid' => false,
			'message' => 'Session is not completed'
		);
	}
	
	// Check if therapist and patient IDs are set
	if ( empty( $session_data['therapist_id'] ) || empty( $session_data['patient_id'] ) ) {
		return array(
			'valid' => false,
			'message' => 'Missing therapist or patient ID'
		);
	}
	
	// Check if session type is set
	if ( empty( $session_data['ai_session_type'] ) ) {
		return array(
			'valid' => false,
			'message' => 'Missing session type'
		);
	}
	
	// Validate therapist exists
	$therapist = get_user_by( 'ID', $session_data['therapist_id'] );
	if ( ! $therapist ) {
		return array(
			'valid' => false,
			'message' => 'Therapist not found'
		);
	}
	
	// Validate patient exists
	$patient = get_user_by( 'ID', $session_data['patient_id'] );
	if ( ! $patient ) {
		return array(
			'valid' => false,
			'message' => 'Patient not found'
		);
	}
	
	return array(
		'valid' => true,
		'session_data' => $session_data,
		'therapist' => $therapist,
		'patient' => $patient
	);
}

/**
 * Get AI session completion rate for a therapist
 */
function snks_get_ai_session_completion_rate( $therapist_id, $period_days = 30 ) {
	global $wpdb;
	
	$start_date = date( 'Y-m-d', strtotime( "-{$period_days} days" ) );
	
	$stats = $wpdb->get_row( $wpdb->prepare(
		"SELECT 
			COUNT(*) as total_sessions,
			SUM(CASE WHEN session_status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
		 FROM {$wpdb->prefix}snks_sessions_actions 
		 WHERE therapist_id = %d 
		 AND ai_session_type IS NOT NULL
		 AND DATE(created_at) >= %s",
		$therapist_id,
		$start_date
	), ARRAY_A );
	
	if ( ! $stats || $stats['total_sessions'] == 0 ) {
		return 0;
	}
	
	return round( ( $stats['completed_sessions'] / $stats['total_sessions'] ) * 100, 2 );
}

/**
 * Get AI session profit trends for a therapist
 */
function snks_get_ai_session_profit_trends( $therapist_id, $days = 30 ) {
	global $wpdb;
	
	$trends = $wpdb->get_results( $wpdb->prepare(
		"SELECT 
			DATE(transaction_time) as date,
			COUNT(*) as sessions,
			SUM(amount) as daily_profit,
			AVG(amount) as average_profit
		 FROM {$wpdb->prefix}snks_booking_transactions 
		 WHERE user_id = %d 
		 AND ai_session_id IS NOT NULL 
		 AND transaction_type = 'add'
		 AND DATE(transaction_time) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
		 GROUP BY DATE(transaction_time)
		 ORDER BY date DESC",
		$therapist_id,
		$days
	), ARRAY_A );
	
	return $trends;
}

/**
 * Get therapist earnings (alias for compatibility)
 * 
 * @param int $therapist_id The therapist user ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array Earnings data
 */
function snks_get_therapist_earnings( $therapist_id, $start_date = null, $end_date = null ) {
	global $wpdb;
	
	$date_filter = '';
	if ( $start_date && $end_date ) {
		$date_filter = $wpdb->prepare( "AND DATE(transaction_time) BETWEEN %s AND %s", $start_date, $end_date );
	}
	
	$earnings = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.*, 
		        u.display_name as patient_name,
		        u.user_email as patient_email,
		        s.ai_session_type,
		        s.attendance
		 FROM {$wpdb->prefix}snks_booking_transactions t
		 LEFT JOIN {$wpdb->users} u ON t.ai_patient_id = u.ID
		 LEFT JOIN {$wpdb->prefix}snks_sessions_actions s ON t.ai_session_id = s.action_session_id
		 WHERE t.user_id = %d 
		 AND t.ai_session_id IS NOT NULL 
		 AND t.transaction_type = 'add'
		 $date_filter
		 ORDER BY t.transaction_time DESC",
		$therapist_id
	), ARRAY_A );
	
	// Calculate totals
	$total_earnings = 0;
	$first_sessions = 0;
	$subsequent_sessions = 0;
	
	foreach ( $earnings as $earning ) {
		$total_earnings += $earning['amount'];
		if ( $earning['ai_session_type'] === 'first' ) {
			$first_sessions++;
		} else {
			$subsequent_sessions++;
		}
	}
	
	return array(
		'earnings' => $earnings,
		'total_earnings' => $total_earnings,
		'first_sessions' => $first_sessions,
		'subsequent_sessions' => $subsequent_sessions,
		'total_sessions' => count( $earnings )
	);
}

/**
 * Process AI session transaction (alias for compatibility)
 * 
 * @param string $session_id The session ID
 * @return array Result array
 */
function snks_process_ai_session_transaction( $session_id ) {
	return snks_process_ai_session_completion( $session_id );
}

/**
 * Process therapist withdrawal (alias for compatibility)
 * 
 * @param int $therapist_id The therapist user ID
 * @param float $amount The withdrawal amount
 * @param string $method The withdrawal method
 * @return array Result array
 */
function snks_process_therapist_withdrawal( $therapist_id, $amount, $method = 'wallet' ) {
	return snks_process_ai_session_withdrawal( $therapist_id, $amount, $method );
}

/**
 * Get recent AI transactions (alias for compatibility)
 * 
 * @param int $limit Number of transactions to return
 * @return array Recent transactions
 */
function snks_get_recent_ai_transactions( $limit = 20 ) {
	global $wpdb;
	
	$transactions = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.*, 
		        u.display_name as therapist_name,
		        p.display_name as patient_name
		 FROM {$wpdb->prefix}snks_booking_transactions t
		 LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
		 LEFT JOIN {$wpdb->users} p ON t.ai_patient_id = p.ID
		 WHERE t.ai_session_id IS NOT NULL 
		 AND t.transaction_type = 'add'
		 ORDER BY t.transaction_time DESC
		 LIMIT %d",
		$limit
	), ARRAY_A );
	
	return $transactions;
}
