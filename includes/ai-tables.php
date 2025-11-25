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
	
	// Create diagnoses table with bilingual support
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	$diagnoses_sql = "CREATE TABLE IF NOT EXISTS $diagnoses_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		name_en VARCHAR(255) NOT NULL,
		name_ar VARCHAR(255) NOT NULL,
		description TEXT,
		description_en TEXT,
		description_ar TEXT,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) " . $wpdb->get_charset_collate();
	
	// Create therapist diagnoses table with bilingual support
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
	
	// Create AI sessions table
	$ai_sessions_table = $wpdb->prefix . 'jalsah_ai_sessions';
	$ai_sessions_sql = "CREATE TABLE IF NOT EXISTS $ai_sessions_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		user_id INT(11) NOT NULL,
		therapist_id INT(11) NOT NULL,
		diagnosis_id INT(11) NULL,
		session_data TEXT,
		status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY therapist_id (therapist_id),
		KEY diagnosis_id (diagnosis_id),
		KEY status (status)
	) " . $wpdb->get_charset_collate();
	
	// Create AI appointments table
	$ai_appointments_table = $wpdb->prefix . 'jalsah_appointments';
	$ai_appointments_sql = "CREATE TABLE IF NOT EXISTS $ai_appointments_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		user_id INT(11) NOT NULL,
		therapist_id INT(11) NOT NULL,
		diagnosis_id INT(11) NULL,
		appointment_date DATE NOT NULL,
		appointment_time TIME NOT NULL,
		duration INT(11) DEFAULT 45,
		status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
		notes TEXT,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY therapist_id (therapist_id),
		KEY diagnosis_id (diagnosis_id),
		KEY appointment_date (appointment_date),
		KEY status (status)
	) " . $wpdb->get_charset_collate();
	
	// Create AI cart table
	$ai_cart_table = $wpdb->prefix . 'jalsah_cart';
	$ai_cart_sql = "CREATE TABLE IF NOT EXISTS $ai_cart_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		user_id INT(11) NOT NULL,
		therapist_id INT(11) NOT NULL,
		diagnosis_id INT(11) NULL,
		appointment_date DATE NOT NULL,
		appointment_time TIME NOT NULL,
		duration INT(11) DEFAULT 45,
		price DECIMAL(10,2) DEFAULT 0.00,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY therapist_id (therapist_id)
	) " . $wpdb->get_charset_collate();
	
	// Create AI orders table
	$ai_orders_table = $wpdb->prefix . 'jalsah_orders';
	$ai_orders_sql = "CREATE TABLE IF NOT EXISTS $ai_orders_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		user_id INT(11) NOT NULL,
		order_number VARCHAR(50) NOT NULL,
		total_amount DECIMAL(10,2) DEFAULT 0.00,
		status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
		payment_method VARCHAR(50),
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY order_number (order_number),
		KEY user_id (user_id),
		KEY status (status)
	) " . $wpdb->get_charset_collate();
	
	// Create AI payments table
	$ai_payments_table = $wpdb->prefix . 'jalsah_payments';
	$ai_payments_sql = "CREATE TABLE IF NOT EXISTS $ai_payments_table (
		id INT(11) NOT NULL AUTO_INCREMENT,
		order_id INT(11) NOT NULL,
		amount DECIMAL(10,2) NOT NULL,
		payment_method VARCHAR(50),
		transaction_id VARCHAR(100),
		status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY order_id (order_id),
		KEY transaction_id (transaction_id),
		KEY status (status)
	) " . $wpdb->get_charset_collate();
	
	// Execute SQL
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $diagnoses_sql );
	dbDelta( $therapist_diagnoses_sql );
	dbDelta( $ai_sessions_sql );
	dbDelta( $ai_appointments_sql );
	dbDelta( $ai_cart_sql );
	dbDelta( $ai_orders_sql );
	dbDelta( $ai_payments_sql );
	
	// Add missing columns to existing tables if they don't exist
	snks_add_missing_ai_columns();
	
	// Add some default diagnoses
	snks_add_default_diagnoses();
	
	// Check and upgrade AI profit system database schema
	snks_upgrade_ai_profit_database_schema();
	
	// Create AI profit settings table
	snks_create_ai_profit_settings_table();
	
	// Add AI session type column to sessions_actions table
	snks_add_ai_session_type_column();
	
	// Add AI session metadata columns to booking_transactions table
	snks_add_ai_transaction_metadata_columns();
	
	// Add default profit settings for existing therapists
	snks_add_default_profit_settings();
	
	// Update the AI profit system version
	update_option( 'snks_ai_profit_system_version', '1.0.0' );
	
	// Update the AI tables version
	update_option( 'snks_ai_tables_version', '2.0.0' );
}

/**
 * Upgrade AI profit database schema based on version
 */
function snks_upgrade_ai_profit_database_schema() {
	global $wpdb;
	
	$current_version = get_option( 'snks_ai_profit_system_version', '0.0.0' );
	
	// If version is less than 1.0.0, we need to fix the schema
	if ( version_compare( $current_version, '1.0.0', '<' ) ) {
		// Fix AI profit settings table
		$table_name = $wpdb->prefix . 'snks_ai_profit_settings';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		
		if ( $table_exists ) {
			// Check if therapist_id column has correct type
			$column_info = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'therapist_id'" );
			if ( $column_info && strpos( $column_info->Type, 'bigint' ) === false ) {

				$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
			}
		}
		
		// Fix sessions_actions table columns
		$sessions_table = $wpdb->prefix . 'snks_sessions_actions';
		
		// Check and fix therapist_id column type
		$therapist_column = $wpdb->get_row( "SHOW COLUMNS FROM {$sessions_table} LIKE 'therapist_id'" );
		if ( $therapist_column && strpos( $therapist_column->Type, 'bigint' ) === false ) {

			$wpdb->query( "ALTER TABLE {$sessions_table} MODIFY COLUMN therapist_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		}
		
		// Check and fix patient_id column type
		$patient_column = $wpdb->get_row( "SHOW COLUMNS FROM {$sessions_table} LIKE 'patient_id'" );
		if ( $patient_column && strpos( $patient_column->Type, 'bigint' ) === false ) {

			$wpdb->query( "ALTER TABLE {$sessions_table} MODIFY COLUMN patient_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		}
		
		// Fix booking_transactions table columns
		$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
		
		// Check and fix ai_patient_id column type
		$ai_patient_column = $wpdb->get_row( "SHOW COLUMNS FROM {$transactions_table} LIKE 'ai_patient_id'" );
		if ( $ai_patient_column && strpos( $ai_patient_column->Type, 'bigint' ) === false ) {

			$wpdb->query( "ALTER TABLE {$transactions_table} MODIFY COLUMN ai_patient_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		}
		
		// Check and fix ai_order_id column type
		$ai_order_column = $wpdb->get_row( "SHOW COLUMNS FROM {$transactions_table} LIKE 'ai_order_id'" );
		if ( $ai_order_column && strpos( $ai_order_column->Type, 'bigint' ) === false ) {

			$wpdb->query( "ALTER TABLE {$transactions_table} MODIFY COLUMN ai_order_id BIGINT(20) UNSIGNED DEFAULT NULL" );
		}
		

	}
}

/**
 * Add default diagnoses with bilingual support
 */
function snks_add_default_diagnoses() {
	global $wpdb;
	
	$diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
	
	$default_diagnoses = array(
		array(
			'name_en' => 'Anxiety Disorders',
			'name_ar' => 'اضطرابات القلق',
			'description_en' => 'Professional therapy for anxiety disorders including generalized anxiety, panic attacks, and social anxiety.',
			'description_ar' => 'علاج مهني لاضطرابات القلق بما في ذلك القلق العام ونوبات الهلع والقلق الاجتماعي.'
		),
		array(
			'name_en' => 'Depression',
			'name_ar' => 'الاكتئاب',
			'description_en' => 'Professional therapy for depression, mood disorders, and emotional well-being.',
			'description_ar' => 'علاج مهني للاكتئاب واضطرابات المزاج والرفاهية العاطفية.'
		),
		array(
			'name_en' => 'Stress Management',
			'name_ar' => 'إدارة التوتر',
			'description_en' => 'Professional therapy for stress management and coping strategies.',
			'description_ar' => 'علاج مهني لإدارة التوتر واستراتيجيات المواجهة.'
		),
		array(
			'name_en' => 'Relationship Issues',
			'name_ar' => 'مشاكل العلاقات',
			'description_en' => 'Professional therapy for relationship issues, communication, and interpersonal conflicts.',
			'description_ar' => 'علاج مهني لمشاكل العلاقات والتواصل والصراعات الشخصية.'
		),
		array(
			'name_en' => 'Trauma and PTSD',
			'name_ar' => 'الصدمة واضطراب ما بعد الصدمة',
			'description_en' => 'Professional therapy for trauma, PTSD, and post-traumatic stress recovery.',
			'description_ar' => 'علاج مهني للصدمة واضطراب ما بعد الصدمة والتعافي من الإجهاد اللاحق للصدمة.'
		),
		array(
			'name_en' => 'Addiction',
			'name_ar' => 'الإدمان',
			'description_en' => 'Professional therapy for addiction recovery and substance abuse treatment.',
			'description_ar' => 'علاج مهني للتعافي من الإدمان وعلاج إساءة استخدام المواد.'
		),
		array(
			'name_en' => 'Eating Disorders',
			'name_ar' => 'اضطرابات الأكل',
			'description_en' => 'Professional therapy for eating disorders including anorexia, bulimia, and binge eating.',
			'description_ar' => 'علاج مهني لاضطرابات الأكل بما في ذلك فقدان الشهية والشره المرضي والإفراط في الأكل.'
		),
		array(
			'name_en' => 'Sleep Disorders',
			'name_ar' => 'اضطرابات النوم',
			'description_en' => 'Professional therapy for sleep disorders, insomnia, and sleep hygiene.',
			'description_ar' => 'علاج مهني لاضطرابات النوم والأرق ونظافة النوم.'
		),
		array(
			'name_en' => 'Grief and Loss',
			'name_ar' => 'الحزن والخسارة',
			'description_en' => 'Professional therapy for grief, loss, and bereavement support.',
			'description_ar' => 'علاج مهني للحزن والخسارة ودعم الفجيعة.'
		),
		array(
			'name_en' => 'Self-Esteem Issues',
			'name_ar' => 'مشاكل احترام الذات',
			'description_en' => 'Professional therapy for self-esteem issues, confidence building, and self-worth.',
			'description_ar' => 'علاج مهني لمشاكل احترام الذات وبناء الثقة وتقدير الذات.'
		),
		array(
			'name_en' => 'Work-Life Balance',
			'name_ar' => 'توازن العمل والحياة',
			'description_en' => 'Professional therapy for work-life balance, burnout, and career stress.',
			'description_ar' => 'علاج مهني لتوازن العمل والحياة والإرهاق والضغط المهني.'
		),
		array(
			'name_en' => 'Family Therapy',
			'name_ar' => 'العلاج الأسري',
			'description_en' => 'Professional family therapy for family dynamics, communication, and conflict resolution.',
			'description_ar' => 'علاج أسري مهني لديناميكيات الأسرة والتواصل وحل النزاعات.'
		),
		array(
			'name_en' => 'Couples Counseling',
			'name_ar' => 'استشارات الأزواج',
			'description_en' => 'Professional couples counseling for relationship improvement and conflict resolution.',
			'description_ar' => 'استشارات أزواج مهنية لتحسين العلاقات وحل النزاعات.'
		),
		array(
			'name_en' => 'Child and Adolescent Therapy',
			'name_ar' => 'علاج الأطفال والمراهقين',
			'description_en' => 'Professional therapy for children and adolescents with age-appropriate approaches.',
			'description_ar' => 'علاج مهني للأطفال والمراهقين بأساليب مناسبة للعمر.'
		),
		array(
			'name_en' => 'Anger Management',
			'name_ar' => 'إدارة الغضب',
			'description_en' => 'Professional therapy for anger management and emotional regulation.',
			'description_ar' => 'علاج مهني لإدارة الغضب والتنظيم العاطفي.'
		),
		array(
			'name_en' => 'OCD (Obsessive-Compulsive Disorder)',
			'name_ar' => 'اضطراب الوسواس القهري',
			'description_en' => 'Professional therapy for OCD, obsessive thoughts, and compulsive behaviors.',
			'description_ar' => 'علاج مهني لاضطراب الوسواس القهري والأفكار الوسواسية والسلوكيات القهرية.'
		),
		array(
			'name_en' => 'Bipolar Disorder',
			'name_ar' => 'الاضطراب ثنائي القطب',
			'description_en' => 'Professional therapy for bipolar disorder and mood stabilization.',
			'description_ar' => 'علاج مهني للاضطراب ثنائي القطب واستقرار المزاج.'
		),
		array(
			'name_en' => 'Personality Disorders',
			'name_ar' => 'اضطرابات الشخصية',
			'description_en' => 'Professional therapy for personality disorders and behavioral patterns.',
			'description_ar' => 'علاج مهني لاضطرابات الشخصية والأنماط السلوكية.'
		),
		array(
			'name_en' => 'Phobias',
			'name_ar' => 'الرهاب',
			'description_en' => 'Professional therapy for phobias, specific fears, and anxiety disorders.',
			'description_ar' => 'علاج مهني للرهاب والمخاوف المحددة واضطرابات القلق.'
		),
		array(
			'name_en' => 'Panic Disorders',
			'name_ar' => 'اضطرابات الهلع',
			'description_en' => 'Professional therapy for panic disorders and panic attack management.',
			'description_ar' => 'علاج مهني لاضطرابات الهلع وإدارة نوبات الهلع.'
		)
	);
	
	foreach ( $default_diagnoses as $diagnosis ) {
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $diagnoses_table WHERE name_en = %s OR name = %s",
			$diagnosis['name_en'],
			$diagnosis['name_en']
		) );
		
		if ( ! $exists ) {
			$wpdb->insert(
				$diagnoses_table,
				array(
					'name' => $diagnosis['name_en'], // Legacy support
					'name_en' => $diagnosis['name_en'],
					'name_ar' => $diagnosis['name_ar'],
					'description' => $diagnosis['description_en'], // Legacy support
					'description_en' => $diagnosis['description_en'],
					'description_ar' => $diagnosis['description_ar'],
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
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
		therapist_id BIGINT(20) UNSIGNED NOT NULL,
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
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN therapist_id BIGINT(20) UNSIGNED DEFAULT NULL" );
	}
	
	$patient_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'patient_id',
		$wpdb->dbname
	) );
	
	if ( empty( $patient_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN patient_id BIGINT(20) UNSIGNED DEFAULT NULL" );
	}
	
	// Add session_status column if it doesn't exist
	$status_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'session_status',
		$wpdb->dbname
	) );
	
	if ( empty( $status_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN session_status VARCHAR(20) DEFAULT 'open'" );
	}
	
	// Add created_at column if it doesn't exist
	$created_at_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'created_at',
		$wpdb->dbname
	) );
	
	if ( empty( $created_at_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" );
	}
	
	// Add updated_at column if it doesn't exist
	$updated_at_column_exists = $wpdb->get_results( $wpdb->prepare(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
		WHERE TABLE_NAME = %s AND COLUMN_NAME = %s AND TABLE_SCHEMA = %s",
		$table_name,
		'updated_at',
		$wpdb->dbname
	) );
	
	if ( empty( $updated_at_column_exists ) ) {
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
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
		'ai_patient_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL',
		'ai_order_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL',
		'ai_session_amount' => 'DECIMAL(10,2) DEFAULT NULL COMMENT "Total session amount (revenue)"',
		'ai_admin_profit' => 'DECIMAL(10,2) DEFAULT NULL COMMENT "Admin/website profit (revenue - therapist share)"'
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
 * Add missing columns to all AI tables
 */
function snks_add_missing_ai_columns() {
    global $wpdb;
    
    // Add missing columns to diagnoses table
    $diagnoses_table = $wpdb->prefix . 'snks_diagnoses';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $diagnoses_table");
    $column_names = array_column($columns, 'Field');
    
    if (!in_array('name_en', $column_names)) {
        $wpdb->query("ALTER TABLE $diagnoses_table ADD COLUMN name_en VARCHAR(255) AFTER name");
        $wpdb->query("UPDATE $diagnoses_table SET name_en = name WHERE name_en IS NULL OR name_en = ''");
    }
    
    if (!in_array('name_ar', $column_names)) {
        $wpdb->query("ALTER TABLE $diagnoses_table ADD COLUMN name_ar VARCHAR(255) AFTER name_en");
    }
    
    if (!in_array('description_en', $column_names)) {
        $wpdb->query("ALTER TABLE $diagnoses_table ADD COLUMN description_en TEXT AFTER description");
        $wpdb->query("UPDATE $diagnoses_table SET description_en = description WHERE description_en IS NULL OR description_en = ''");
    }
    
    if (!in_array('description_ar', $column_names)) {
        $wpdb->query("ALTER TABLE $diagnoses_table ADD COLUMN description_ar TEXT AFTER description_en");
    }
    
    // Add missing columns to therapist diagnoses table
    $therapist_diagnoses_table = $wpdb->prefix . 'snks_therapist_diagnoses';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $therapist_diagnoses_table");
    $column_names = array_column($columns, 'Field');
    
    if (!in_array('suitability_message_en', $column_names)) {
        $wpdb->query("ALTER TABLE $therapist_diagnoses_table ADD COLUMN suitability_message_en TEXT AFTER suitability_message");
        $wpdb->query("UPDATE $therapist_diagnoses_table SET suitability_message_en = suitability_message WHERE suitability_message_en IS NULL OR suitability_message_en = ''");
    }
    
    if (!in_array('suitability_message_ar', $column_names)) {
        $wpdb->query("ALTER TABLE $therapist_diagnoses_table ADD COLUMN suitability_message_ar TEXT AFTER suitability_message_en");
    }
    
    if (!in_array('display_order', $column_names)) {
        $wpdb->query("ALTER TABLE $therapist_diagnoses_table ADD COLUMN display_order INT DEFAULT 0 AFTER suitability_message_ar");
    }
    
    if (!in_array('frontend_order', $column_names)) {
        $wpdb->query("ALTER TABLE $therapist_diagnoses_table ADD COLUMN frontend_order INT DEFAULT 0 AFTER display_order");
    }
    
    // Add AI meta fields to existing tables
    snks_add_ai_meta_fields();
}

/**
 * Add missing columns to therapist diagnoses table (legacy function for backward compatibility)
 */
function snks_add_missing_therapist_diagnoses_columns() {
    snks_add_missing_ai_columns();
}

/**
 * Create demo data for user 85 using existing timetable system
 */
function snks_create_demo_booking_data() {
    global $wpdb;
    
    // Check if demo data already exists
    $existing_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable WHERE user_id = 85 AND settings LIKE '%ai_booking%'");
    if ($existing_appointments > 0) {

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
