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
		frontend_order INT(11) DEFAULT 0,
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
	
	// Create AI profit settings table
	snks_create_ai_profit_settings_table();
	
	// Add AI session type column to sessions_actions table
	snks_add_ai_session_type_column();
	
	// Add AI session metadata columns to booking_transactions table
	snks_add_ai_transaction_metadata_columns();
	
	// Add default profit settings for existing therapists
	snks_add_default_profit_settings();
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
 * Create AI profit settings table
 */
function snks_create_ai_profit_settings_table() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
	$collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id INT(11) NOT NULL AUTO_INCREMENT,
		therapist_id INT(11) NOT NULL,
		first_session_percentage DECIMAL(5,2) DEFAULT 70.00,
		subsequent_session_percentage DECIMAL(5,2) DEFAULT 75.00,
		is_active BOOLEAN DEFAULT TRUE,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY unique_therapist (therapist_id),
		FOREIGN KEY (therapist_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
	) $collate";
	
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Add AI session type column to sessions_actions table
 */
function snks_add_ai_session_type_column() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_sessions_actions';
	
	// Check if column exists
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'ai_session_type',
		$wpdb->dbname
	) );
	
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN ai_session_type ENUM('first', 'subsequent') DEFAULT 'first'" );
	}
	
	// Add therapist_id and patient_id columns if they don't exist
	$therapist_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'therapist_id',
		$wpdb->dbname
	) );
	
	if ( empty( $therapist_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN therapist_id INT(11) DEFAULT NULL" );
	}
	
	$patient_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'patient_id',
		$wpdb->dbname
	) );
	
	if ( empty( $patient_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN patient_id INT(11) DEFAULT NULL" );
	}
}

/**
 * Add AI session metadata columns to booking_transactions table
 */
function snks_add_ai_transaction_metadata_columns() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_booking_transactions';
	
	// Add AI session metadata columns if they don't exist
	$columns_to_add = array(
		'ai_session_id' => 'INT(11) DEFAULT NULL',
		'ai_session_type' => "ENUM('first', 'subsequent') DEFAULT NULL",
		'ai_patient_id' => 'INT(11) DEFAULT NULL',
		'ai_order_id' => 'INT(11) DEFAULT NULL'
	);
	
	foreach ( $columns_to_add as $column_name => $column_definition ) {
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
			$table_name,
			$column_name,
			$wpdb->dbname
		) );
		
		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN $column_name $column_definition" );
		}
	}
}

/**
 * Add default profit settings for existing therapists
 */
function snks_add_default_profit_settings() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
	
	// Get all users with doctor role
	$doctors = get_users( array(
		'role' => 'doctor',
		'fields' => 'ID'
	) );
	
	foreach ( $doctors as $doctor_id ) {
		// Check if profit settings already exist for this therapist
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE therapist_id = %d",
			$doctor_id
		) );
		
		if ( ! $exists ) {
			$wpdb->insert(
				$table_name,
				array(
					'therapist_id' => $doctor_id,
					'first_session_percentage' => 70.00,
					'subsequent_session_percentage' => 75.00,
					'is_active' => 1
				),
				array( '%d', '%f', '%f', '%d' )
			);
		}
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
    
    $table_name = $wpdb->prefix . 'snks_therapist_diagnoses';
    
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
    
    if (!in_array('frontend_order', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN frontend_order INT DEFAULT 0 AFTER display_order");
    }
}

/**
 * Create demo data for user 85 using existing timetable system
 */
function snks_create_demo_booking_data() {
    global $wpdb;
    
    // Check if demo data already exists
    $existing_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 85 AND settings LIKE '%ai_booking%'");
    if ($existing_appointments > 0) {
        error_log('Demo data already exists for doctor 85');
        return; // Demo data already exists
    }
    
    // Create demo available slots for doctor 85 (available for booking)
    $demo_slots = [
        [
            'user_id' => 85, // Doctor ID (doctor 85)
            'client_id' => 0, // No patient assigned yet (available slot)
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
            'settings' => 'ai_booking:available_slot'
        ],
        [
            'user_id' => 85, // Doctor ID (doctor 85)
            'client_id' => 0, // No patient assigned yet (available slot)
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
            'settings' => 'ai_booking:available_slot'
        ],
        [
            'user_id' => 85, // Doctor ID (doctor 85)
            'client_id' => 0, // No patient assigned yet (available slot)
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
            'settings' => 'ai_booking:available_slot'
        ],
        [
            'user_id' => 85, // Doctor ID (doctor 85)
            'client_id' => 0, // No patient assigned yet (available slot)
            'session_status' => 'waiting',
            'day' => 'Fri',
            'base_hour' => '14:00:00',
            'period' => 45,
            'date_time' => date('Y-m-d H:i:s', strtotime('+12 days 14:00:00')),
            'starts' => '14:00:00',
            'ends' => '14:45:00',
            'clinic' => 'Online',
            'attendance_type' => 'online',
            'order_id' => 0,
            'settings' => 'ai_booking:available_slot'
        ]
    ];
    
    foreach ($demo_slots as $slot) {
        $wpdb->insert(
            $wpdb->prefix . 'snks_provider_timetable',
            $slot,
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }
    
    error_log('Demo booking data created for doctor 85 using existing timetable system with AI identifier');
} 

/**
 * Calculate and update frontend_order for all therapists for a specific diagnosis
 * This function sorts therapists by display_order and assigns sequential position numbers (1, 2, 3...)
 * If display_order is 0, it's treated as 999999 to push it to the end
 */
function snks_calculate_frontend_order_for_diagnosis($diagnosis_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'snks_therapist_diagnoses';
    
    // Get all therapists for this diagnosis
    $therapists = $wpdb->get_results($wpdb->prepare(
        "SELECT id, therapist_id, display_order 
         FROM $table_name 
         WHERE diagnosis_id = %d",
        $diagnosis_id
    ));
    
    if (empty($therapists)) {
        return false;
    }
    
    // Create a copy for sorting with modified display_order values
    $therapists_for_sorting = array();
    foreach ($therapists as $therapist) {
        $therapists_for_sorting[] = (object) array(
            'id' => $therapist->id,
            'therapist_id' => $therapist->therapist_id,
            'display_order' => ($therapist->display_order == 0) ? 999999 : $therapist->display_order,
            'original_display_order' => $therapist->display_order
        );
    }
    
    // Sort therapists by display_order (0 values now treated as 999999)
    usort($therapists_for_sorting, function($a, $b) {
        return $a->display_order - $b->display_order;
    });
    
    // Update frontend_order for each therapist based on their sorted position
    $total_therapists = count($therapists_for_sorting);
    foreach ($therapists_for_sorting as $index => $therapist) {
        // If original display_order was 0, assign the highest frontend_order value
        if ($therapist->original_display_order == 0) {
            $frontend_order = 999999; // Use a very high number for display_order = 0
        } else {
            $frontend_order = $index + 1; // Normal sequential ordering for others
        }
        
        $wpdb->update(
            $table_name,
            array('frontend_order' => $frontend_order),
            array('id' => $therapist->id),
            array('%d'),
            array('%d')
        );
    }
    
    return true;
}

/**
 * Calculate and update frontend_order for all diagnoses
 * This function updates frontend_order for all therapist-diagnosis relationships
 */
function snks_calculate_all_frontend_orders() {
    global $wpdb;
    
    $diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
    $therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
    
    // Get all diagnoses
    $diagnoses = $wpdb->get_results("SELECT id FROM $diagnoses_table");
    
    foreach ($diagnoses as $diagnosis) {
        snks_calculate_frontend_order_for_diagnosis($diagnosis->id);
    }
    
    return true;
} 
