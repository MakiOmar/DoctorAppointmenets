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
	
	$therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
	
	// Check if suitability_message_ar column exists
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$therapist_diagnoses_table,
		'suitability_message_ar',
		$wpdb->dbname
	) );
	
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $therapist_diagnoses_table ADD COLUMN suitability_message_ar TEXT AFTER suitability_message" );
	}
	
	// Check if display_order column exists
	$column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$therapist_diagnoses_table,
		'display_order',
		$wpdb->dbname
	) );
	
	if ( empty( $column_exists ) ) {
		$wpdb->query( "ALTER TABLE $therapist_diagnoses_table ADD COLUMN display_order INT(11) DEFAULT 0 AFTER suitability_message_ar" );
	}
}

// Hook to create tables on plugin activation
add_action( 'snks_create_ai_tables', 'snks_create_ai_tables' );
add_action( 'snks_add_ai_meta_fields', 'snks_add_ai_meta_fields' ); 