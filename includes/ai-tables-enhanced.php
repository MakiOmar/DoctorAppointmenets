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
		diagnosis_id INT(11) NOT NULL,
		initial_diagnosis TEXT,
		symptoms TEXT,
		booking_date DATE NOT NULL,
		booking_time TIME NOT NULL,
		status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
		prescription_text TEXT,
		prescription_file VARCHAR(255),
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY patient_id (patient_id),
		KEY therapist_id (therapist_id),
		KEY diagnosis_id (diagnosis_id),
		KEY booking_date (booking_date),
		KEY status (status)
	) " . $wpdb->get_charset_collate();
	
	// Create Rochtah appointments table
	$rochtah_appointments_table = $wpdb->prefix . 'snks_rochtah_appointments';
	$rochtah_appointments_sql = "CREATE TABLE IF NOT EXISTS $rochtah_appointments_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		day_of_week VARCHAR(20) NOT NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		current_bookings INT(11) DEFAULT 0,
		status ENUM('active', 'inactive') DEFAULT 'active',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY day_of_week (day_of_week),
		KEY start_time (start_time),
		KEY status (status)
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
		) );
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
 */
function snks_apply_ai_coupon( $code, $total_amount ) {
	$validation = snks_validate_ai_coupon( $code );
	
	if ( ! $validation['valid'] ) {
		return $validation;
	}
	
	$coupon = $validation['coupon'];
	$discount_amount = 0;
	
	if ( $coupon->discount_type === 'percentage' ) {
		$discount_amount = ( $total_amount * $coupon->discount_value ) / 100;
	} else {
		$discount_amount = $coupon->discount_value;
	}
	
	// Ensure discount doesn't exceed total
	$discount_amount = min( $discount_amount, $total_amount );
	
	return array(
		'valid' => true,
		'discount_amount' => $discount_amount,
		'final_amount' => $total_amount - $discount_amount,
		'coupon' => $coupon
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