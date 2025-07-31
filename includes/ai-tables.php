<?php
/**
 * AI Tables
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Create AI tables
 */
function snks_create_ai_tables() {
	global $wpdb;
	
	// Create diagnoses table
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	$diagnoses_sql = "CREATE TABLE IF NOT EXISTS $diagnoses_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		description TEXT,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) " . $wpdb->get_charset_collate();
	
	// Create therapist diagnoses table
	$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
	$therapist_diagnoses_sql = "CREATE TABLE IF NOT EXISTS $therapist_diagnoses_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		therapist_id INT(11) NOT NULL,
		diagnosis_id INT(11) NOT NULL,
		rating DECIMAL(3,2) DEFAULT 0.00,
		suitability_message TEXT,
		suitability_message_en TEXT,
		suitability_message_ar TEXT,
		display_order INT(11) DEFAULT 0,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY unique_therapist_diagnosis (therapist_id, diagnosis_id)
	) " . $wpdb->get_charset_collate();
	
	// Execute SQL
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $diagnoses_sql );
	dbDelta( $therapist_diagnoses_sql );
	
	// Add some default diagnoses
	snks_add_default_diagnoses();
}

/**
 * Add default diagnoses
 */
function snks_add_default_diagnoses() {
	global $wpdb;
	
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	
	$default_diagnoses = array(
		'Anxiety Disorders',
		'Depression',
		'Stress Management',
		'Relationship Issues',
		'Trauma and PTSD',
		'Addiction',
		'Eating Disorders',
		'Sleep Disorders',
		'Grief and Loss',
		'Self-Esteem Issues',
		'Work-Life Balance',
		'Family Therapy',
		'Couples Counseling',
		'Child and Adolescent Therapy',
		'Anger Management',
		'OCD (Obsessive-Compulsive Disorder)',
		'Bipolar Disorder',
		'Personality Disorders',
		'Phobias',
		'Panic Disorders'
	);
	
	foreach ( $default_diagnoses as $diagnosis ) {
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $diagnoses_table WHERE name = %s",
			$diagnosis
		) );
		
		if ( ! $exists ) {
			$wpdb->insert(
				$diagnoses_table,
				array(
					'name' => $diagnosis,
					'description' => 'Professional therapy for ' . strtolower( $diagnosis ),
				),
				array( '%s', '%s' )
			);
		}
	}
}

/**
 * Add AI meta fields to existing tables
 */
function snks_add_ai_meta_fields() {
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
}

/**
 * Add AI user meta fields
 */
function snks_add_ai_user_meta_fields() {
	// These will be added automatically when needed
	// show_on_ai_site, ai_bio, ai_certifications, ai_earliest_slot
}

/**
 * Add missing columns to therapist diagnoses table
 */
function snks_add_missing_therapist_diagnoses_columns() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'therapist_diagnoses';
    
    // Check if columns exist
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_column($columns, 'Field');
    
    // Add missing columns
    if (!in_array('suitability_message_ar', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN suitability_message_ar TEXT AFTER suitability_message");
    }
    
    if (!in_array('display_order', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN display_order INT DEFAULT 0 AFTER suitability_message_ar");
    }
    
    if (!in_array('suitability_message_en', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN suitability_message_en TEXT AFTER display_order");
    }
}

/**
 * Create demo data for user 85 using existing timetable system
 */
function snks_create_demo_booking_data() {
    global $wpdb;
    
    // Check if demo data already exists
    $existing_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable WHERE client_id = 85");
    if ($existing_appointments > 0) {
        error_log('Demo data already exists for user 85');
        return; // Demo data already exists
    }
    
    // Create demo appointments for user 85 using the existing timetable system
    $demo_appointments = [
        [
            'user_id' => 1, // Therapist ID
            'client_id' => 85,
            'session_status' => 'open',
            'day' => 'Mon',
            'base_hour' => '10:00:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+3 days 10:00:00')),
            'starts' => '10:00:00',
            'ends' => '10:45:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 1,
            'settings' => 'demo_booking'
        ],
        [
            'user_id' => 1,
            'client_id' => 85,
            'session_status' => 'open',
            'day' => 'Wed',
            'base_hour' => '14:30:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+5 days 14:30:00')),
            'starts' => '14:30:00',
            'ends' => '15:15:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 2,
            'settings' => 'demo_booking'
        ]
    ];
    
    foreach ($demo_appointments as $appointment) {
        $wpdb->insert(
            $wpdb->prefix . 'snks_provider_timetable',
            $appointment,
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }
    
    // Create demo available slots for therapist 1 (for booking)
    $demo_slots = [
        [
            'user_id' => 1,
            'client_id' => 0,
            'session_status' => 'waiting',
            'day' => 'Mon',
            'base_hour' => '09:00:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+7 days 09:00:00')),
            'starts' => '09:00:00',
            'ends' => '09:45:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 0,
            'settings' => 'demo_slot'
        ],
        [
            'user_id' => 1,
            'client_id' => 0,
            'session_status' => 'waiting',
            'day' => 'Tue',
            'base_hour' => '16:00:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+8 days 16:00:00')),
            'starts' => '16:00:00',
            'ends' => '16:45:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 0,
            'settings' => 'demo_slot'
        ],
        [
            'user_id' => 1,
            'client_id' => 0,
            'session_status' => 'waiting',
            'day' => 'Thu',
            'base_hour' => '11:00:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+10 days 11:00:00')),
            'starts' => '11:00:00',
            'ends' => '11:45:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 0,
            'settings' => 'demo_slot'
        ]
    ];
    
    foreach ($demo_slots as $slot) {
        $wpdb->insert(
            $wpdb->prefix . 'snks_provider_timetable',
            $slot,
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }
    
    error_log('Demo booking data created for user 85 using existing timetable system');
} 
