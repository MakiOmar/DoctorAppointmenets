<?php
/**
 * Enhanced AI Tables
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create enhanced AI tables
 */
function snks_create_enhanced_ai_tables() {
	global $wpdb;
	
	// Create AI coupons table
	$coupons_table = $wpdb->prefix . 'snks_ai_coupons';
	$coupons_sql = "CREATE TABLE IF NOT EXISTS $coupons_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		code VARCHAR(50) NOT NULL UNIQUE,
		discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
		discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
		usage_limit INT(11) DEFAULT 0,
		current_usage INT(11) DEFAULT 0,
		expiry_date DATE NULL,
		segment VARCHAR(50) DEFAULT '',
		allowed_users TEXT NULL,
		active TINYINT(1) DEFAULT 1,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY unique_code (code)
	) " . $wpdb->get_charset_collate();
	
	// Create Rochtah bookings table
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$rochtah_bookings_sql = "CREATE TABLE IF NOT EXISTS $rochtah_bookings_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		patient_id INT(11) NOT NULL,
		therapist_id INT(11) NOT NULL,
		session_id BIGINT(20) NOT NULL,
		diagnosis_id INT(11) DEFAULT 0,
		initial_diagnosis TEXT,
		symptoms TEXT,
		reason_for_referral TEXT,
		booking_date DATE NOT NULL,
		booking_time TIME NOT NULL,
		status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'prescribed') DEFAULT 'pending',
		prescription_text TEXT,
		medications TEXT,
		dosage_instructions TEXT,
		doctor_notes TEXT,
		prescribed_by INT(11) NULL,
		prescribed_at TIMESTAMP NULL,
		prescription_file VARCHAR(255),
		attachment_ids TEXT NULL,
		whatsapp_activation_sent TINYINT(1) DEFAULT 0,
		whatsapp_appointment_sent TINYINT(1) DEFAULT 0,
		appointment_id BIGINT(20) NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY patient_id (patient_id),
		KEY therapist_id (therapist_id),
		KEY session_id (session_id),
		KEY diagnosis_id (diagnosis_id),
		KEY booking_date (booking_date),
		KEY status (status),
		UNIQUE KEY unique_session_request (session_id)
	) " . $wpdb->get_charset_collate();
	
	// Create Rochtah appointments table
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$rochtah_appointments_sql = "CREATE TABLE IF NOT EXISTS $rochtah_appointments_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		day_of_week VARCHAR(20) NOT NULL,
		slot_date DATE NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		current_bookings INT(11) DEFAULT 0,
		status ENUM('active', 'inactive') DEFAULT 'active',
		is_template TINYINT(1) DEFAULT 0,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY day_of_week (day_of_week),
		KEY slot_date (slot_date),
		KEY start_time (start_time),
		KEY status (status),
		KEY is_template (is_template)
	) " . $wpdb->get_charset_collate();
	
	// Create AI analytics table
	$analytics_table = $wpdb->prefix . 'snks_ai_analytics';
	$analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		event_type VARCHAR(50) NOT NULL,
		user_id INT(11) NULL,
		therapist_id INT(11) NULL,
		diagnosis_id INT(11) NULL,
		session_id INT(11) NULL,
		order_id INT(11) NULL,
		event_data JSON,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY event_type (event_type),
		KEY user_id (user_id),
		KEY therapist_id (therapist_id),
		KEY diagnosis_id (diagnosis_id),
		KEY created_at (created_at)
	) " . $wpdb->get_charset_collate();
	
	// Create AI notifications table
	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	$notifications_sql = "CREATE TABLE IF NOT EXISTS $notifications_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		user_id INT(11) NOT NULL,
		type VARCHAR(50) NOT NULL,
		title VARCHAR(255) NOT NULL,
		message TEXT NOT NULL,
		read_at TIMESTAMP NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY type (type),
		KEY read_at (read_at)
	) " . $wpdb->get_charset_collate();
	
	// Execute SQL
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $coupons_sql );
	dbDelta( $rochtah_bookings_sql );
	dbDelta( $rochtah_appointments_sql );
	dbDelta( $analytics_sql );
	dbDelta( $notifications_sql );
	
	// Add allowed_users column if it doesn't exist (for existing installations)
	// This must be done AFTER table creation
	$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $coupons_table LIKE 'allowed_users'" );
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $coupons_table ADD COLUMN allowed_users TEXT NULL AFTER segment" );
	}
	
	// Add AI meta fields to existing tables
	snks_add_enhanced_ai_meta_fields();
}

/**
 * Add enhanced AI meta fields
 */
function snks_add_enhanced_ai_meta_fields() {
	global $wpdb;
	
	// Add from_jalsah_ai column to orders table if it doesn't exist
	$orders_table = $wpdb->prefix . 'wc_orders';
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$orders_table,
		'from_jalsah_ai',
		$wpdb->dbname
	) );
	
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $orders_table ADD COLUMN from_jalsah_ai TINYINT(1) DEFAULT 0" );
	}
	
	// Add jalsah_ai_sessions column to orders table if it doesn't exist
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$orders_table,
		'jalsah_ai_sessions',
		$wpdb->dbname
	) );
	
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $orders_table ADD COLUMN jalsah_ai_sessions JSON NULL" );
	}
	
	// Add attachment_ids column to rochtah_bookings table if it doesn't exist
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$rochtah_bookings_table,
		'attachment_ids',
		$wpdb->dbname
	) );

	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $rochtah_bookings_table ADD COLUMN attachment_ids TEXT NULL" );
	}

	// Add slot_date and is_template columns to rochtah_appointments table if they don't exist
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	
	// Check for slot_date column
	$slot_date_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$rochtah_appointments_table,
		'slot_date',
		$wpdb->dbname
	) );

	if ( empty( $slot_date_exists ) ) {
		$wpdb->query( "ALTER TABLE $rochtah_appointments_table ADD COLUMN slot_date DATE NULL AFTER day_of_week" );
		$wpdb->query( "ALTER TABLE $rochtah_appointments_table ADD INDEX slot_date (slot_date)" );
	}

	// Check for is_template column
	$is_template_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$rochtah_appointments_table,
		'is_template',
		$wpdb->dbname
	) );

	if ( empty( $is_template_exists ) ) {
		$wpdb->query( "ALTER TABLE $rochtah_appointments_table ADD COLUMN is_template TINYINT(1) DEFAULT 0 AFTER status" );
		$wpdb->query( "ALTER TABLE $rochtah_appointments_table ADD INDEX is_template (is_template)" );
	}
}

/**
 * Add enhanced AI user meta fields
 */
function snks_add_enhanced_ai_user_meta_fields() {
	// These will be added automatically when needed
	$meta_fields = array(
		'show_on_ai_site',
		'ai_display_name',
		'ai_bio',
		'ai_profile_image',
		'secretary_phone',
		'public_short_bio',
		'ai_first_session_percentage',
		'ai_followup_session_percentage',
		'registration_source',
		'ai_cart',
		'ai_certifications',
		'ai_earliest_slot'
	);
	
	// Add default values for existing users if needed
	foreach ( $meta_fields as $field ) {
		// This will be handled by the admin interface
	}
}

/**
 * Create Rochtah doctor role
 */
function snks_create_rochtah_doctor_role() {
	// Add Rochtah doctor role if it doesn't exist
	if ( ! get_role( 'rochtah_doctor' ) ) {
		add_role( 'rochtah_doctor', 'Rochtah Doctor', array(
			'read' => true,
			'edit_posts' => false,
			'delete_posts' => false,
			'manage_rochtah' => true,
			'view_rochtah_appointments' => true,
			'manage_rochtah_prescriptions' => true,
			'view_rochtah_patients' => true,
			'edit_rochtah_prescriptions' => true,
			'delete_rochtah_prescriptions' => true,
			'upload_files' => true,
		) );
	}
	
	// Add capabilities to administrator role
	$admin_role = get_role( 'administrator' );
	if ( $admin_role ) {
		$admin_role->add_cap( 'manage_rochtah' );
		$admin_role->add_cap( 'view_rochtah_appointments' );
		$admin_role->add_cap( 'manage_rochtah_prescriptions' );
		$admin_role->add_cap( 'view_rochtah_patients' );
		$admin_role->add_cap( 'edit_rochtah_prescriptions' );
		$admin_role->add_cap( 'delete_rochtah_prescriptions' );
	}
}

/**
 * Track AI analytics event
 */
function snks_track_ai_event( $event_type, $data = array() ) {
	global $wpdb;
	
	$analytics_table = $wpdb->prefix . 'snks_ai_analytics';
	
	$wpdb->insert(
		$analytics_table,
		array(
			'event_type' => $event_type,
			'user_id' => $data['user_id'] ?? null,
			'therapist_id' => $data['therapist_id'] ?? null,
			'diagnosis_id' => $data['diagnosis_id'] ?? null,
			'session_id' => $data['session_id'] ?? null,
			'order_id' => $data['order_id'] ?? null,
			'event_data' => json_encode( $data ),
		),
		array( '%s', '%d', '%d', '%d', '%d', '%d', '%s' )
	);
}

/**
 * Create AI notification
 */
function snks_create_ai_notification( $user_id, $type, $title, $message ) {
	global $wpdb;
	
	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	
	$wpdb->insert(
		$notifications_table,
		array(
			'user_id' => $user_id,
			'type' => $type,
			'title' => $title,
			'message' => $message,
		),
		array( '%d', '%s', '%s', '%s' )
	);
}

/**
 * Mark notification as read
 */
function snks_mark_notification_read( $notification_id ) {
	global $wpdb;
	
	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	
	$wpdb->update(
		$notifications_table,
		array( 'read_at' => current_time( 'mysql' ) ),
		array( 'id' => $notification_id ),
		array( '%s' ),
		array( '%d' )
	);
}

/**
 * Get unread notifications for user
 */
function snks_get_unread_notifications( $user_id ) {
	global $wpdb;
	
	$notifications_table = $wpdb->prefix . 'snks_ai_notifications';
	
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $notifications_table WHERE user_id = %d AND read_at IS NULL ORDER BY created_at DESC",
		$user_id
	) );
}

/**
 * Validate AI coupon
 */
function snks_validate_ai_coupon( $code, $user_id = null ) {
	global $wpdb;
	
	$coupons_table = $wpdb->prefix . 'snks_ai_coupons';
	
	$coupon = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $coupons_table WHERE code = %s AND active = 1",
		$code
	) );
	
	if ( ! $coupon ) {
		return array( 'valid' => false, 'message' => 'Invalid coupon code' );
	}
	
	// Check expiry
	if ( $coupon->expiry_date && $coupon->expiry_date < date( 'Y-m-d' ) ) {
		return array( 'valid' => false, 'message' => 'Coupon has expired' );
	}
	
	// Check usage limit
	if ( $coupon->usage_limit > 0 && $coupon->current_usage >= $coupon->usage_limit ) {
		return array( 'valid' => false, 'message' => 'Coupon usage limit reached' );
	}
	
	// Check segment restrictions
	if ( $coupon->segment && $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		$registration_source = get_user_meta( $user_id, 'registration_source', true );
		
		switch ( $coupon->segment ) {
			case 'new_users':
				if ( $registration_source !== 'jalsah_ai' ) {
					return array( 'valid' => false, 'message' => 'Coupon only for new users' );
				}
				break;
			case 'returning_users':
				if ( $registration_source === 'jalsah_ai' ) {
					return array( 'valid' => false, 'message' => 'Coupon only for returning users' );
				}
				break;
			case 'specific_users':
				// Check if user is in allowed_users list
				if ( ! empty( $coupon->allowed_users ) ) {
					$allowed_user_ids = array_map( 'intval', explode( ',', $coupon->allowed_users ) );
					if ( ! in_array( $user_id, $allowed_user_ids, true ) ) {
						return array( 'valid' => false, 'message' => 'Coupon is not available for your account' );
					}
				} else {
					return array( 'valid' => false, 'message' => 'Coupon is not available for your account' );
				}
				break;
		}
	}
	
	return array( 
		'valid' => true, 
		'coupon' => $coupon,
		'message' => 'Coupon is valid'
	);
}

/**
 * Apply AI coupon
 * For AI coupons, discount applies only to Jalsah AI fee (40% of session price)
 */
function snks_apply_ai_coupon( $code, $total_amount ) {
	$validation = snks_validate_ai_coupon( $code );
	
	if ( ! $validation['valid'] ) {
		return $validation;
	}
	
	$coupon = $validation['coupon'];
	
	// Calculate Jalsah AI fee (40% of the session price)
	$jalsah_fee = $total_amount * 0.4;
	
	// Apply discount only to the Jalsah fee portion
	$discount_amount = 0;
	if ( $coupon->discount_type === 'percentage' ) {
		$discount_amount = ( $jalsah_fee * $coupon->discount_value ) / 100;
	} else {
		// For fixed discount, apply to Jalsah fee but don't exceed it
		$discount_amount = min( $coupon->discount_value, $jalsah_fee );
	}
	
	// Calculate final amount: total - discount (discount only applies to Jalsah fee)
	$final_amount = $total_amount - $discount_amount;
	
	return array(
		'valid' => true,
		'discount_amount' => round( $discount_amount, 2 ),
		'final_amount' => round( $final_amount, 2 ),
		'coupon' => $coupon,
		'jalsah_fee' => round( $jalsah_fee, 2 ),
		'discount_applied_to_fee' => true
	);
}

/**
 * Increment coupon usage
 */
function snks_increment_coupon_usage( $code ) {
	global $wpdb;
	
	$coupons_table = $wpdb->prefix . 'snks_ai_coupons';
	
	$wpdb->query( $wpdb->prepare(
		"UPDATE $coupons_table SET current_usage = current_usage + 1 WHERE code = %s",
		$code
	) );
}

// Hook to create tables on plugin activation
add_action( 'snks_create_enhanced_ai_tables', 'snks_create_enhanced_ai_tables' );
add_action( 'snks_add_enhanced_ai_meta_fields', 'snks_add_enhanced_ai_meta_fields' );
add_action( 'snks_add_enhanced_ai_user_meta_fields', 'snks_add_enhanced_ai_user_meta_fields' );
add_action( 'snks_create_rochtah_doctor_role', 'snks_create_rochtah_doctor_role' );

/**
 * Add WhatsApp notification columns to rochtah_bookings table
 */
function snks_add_rochtah_whatsapp_notification_columns() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$columns_to_add = array(
		'whatsapp_activation_sent' => 'TINYINT(1) DEFAULT 0',
		'whatsapp_appointment_sent' => 'TINYINT(1) DEFAULT 0',
		'appointment_id' => 'BIGINT(20) NULL',
	);
	
	//phpcs:disable
	foreach ( $columns_to_add as $column_name => $column_definition ) {
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s',
				$table_name,
				$column_name,
				$wpdb->dbname
			)
		);
		
		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $table_name ADD COLUMN $column_name $column_definition"
			);
		}
	}
	//phpcs:enable
}
add_action( 'admin_init', 'snks_add_rochtah_whatsapp_notification_columns' );

/**
 * Add doctor_joined column to rochtah_bookings table
 */
function snks_add_rochtah_doctor_joined_column() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'snks_rochtah_bookings';
	
	$column_exists = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s',
			$table_name,
			'doctor_joined',
			$wpdb->dbname
		)
	);
	
	if ( empty( $column_exists ) ) {
		$wpdb->query(
			"ALTER TABLE $table_name ADD COLUMN doctor_joined TINYINT(1) DEFAULT 0"
		);
	}
}
add_action( 'admin_init', 'snks_add_rochtah_doctor_joined_column' ); 