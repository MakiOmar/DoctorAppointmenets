<?php
/**
 * AI Integration for Jalsah AI Platform
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Include JWT library
require_once SNKS_DIR . 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * AI Integration Class
 */
class SNKS_AI_Integration {
	
	private $jwt_secret;
	private $jwt_algorithm = 'HS256';
	
	public function __construct() {
		$this->jwt_secret = defined( 'JWT_SECRET' ) ? JWT_SECRET : 'your-secret-key';
		$this->jwt_algorithm = 'HS256';
		
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Register AI endpoints
		add_action( 'init', array( $this, 'register_ai_endpoints' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		
		// Handle CORS
		add_action( 'init', array( $this, 'handle_cors' ) );
		add_action( 'send_headers', array( $this, 'handle_very_early_cors' ) );
		add_action( 'parse_request', array( $this, 'handle_early_api_requests' ) );
		
		// Flush rewrite rules on activation
		register_activation_hook( __FILE__, array( $this, 'flush_rewrite_rules' ) );
		
		// Add admin action for flushing rewrite rules
		add_action( 'admin_post_flush_ai_rewrite_rules', array( $this, 'flush_rewrite_rules' ) );
		
		// Add AJAX endpoints for testing
		add_action( 'wp_ajax_test_ai_endpoint', array( $this, 'test_ai_endpoint' ) );
		add_action( 'wp_ajax_nopriv_test_ai_endpoint', array( $this, 'test_ai_endpoint' ) );
		add_action( 'wp_ajax_test_diagnosis_ajax', array( $this, 'test_diagnosis_ajax' ) );
		add_action( 'wp_ajax_nopriv_test_diagnosis_ajax', array( $this, 'test_diagnosis_ajax' ) );
		add_action( 'wp_ajax_simple_test_ajax', array( $this, 'simple_test_ajax' ) );
		add_action( 'wp_ajax_nopriv_simple_test_ajax', array( $this, 'simple_test_ajax' ) );
	}
	
	/**
	 * Register AI endpoints
	 */
	public function register_ai_endpoints() {
		// Add rewrite rules for API endpoints
		add_rewrite_rule( '^api/ai/(.*?)/?$', 'index.php?ai_endpoint=$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/?$', 'index.php?ai_endpoint=ping', 'top' );
		// Add rewrite rule for v2 endpoints
		add_rewrite_rule( '^api/ai/v2/(.*?)/?$', 'index.php?ai_endpoint=v2/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/v2/?$', 'index.php?ai_endpoint=v2/ping', 'top' );
		
		add_filter( 'query_vars', array( $this, 'add_ai_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_ai_requests' ) );
		
		// Also add a simple test endpoint
		add_action( 'wp_ajax_test_ai_endpoint', array( $this, 'test_ai_endpoint' ) );
		add_action( 'wp_ajax_nopriv_test_ai_endpoint', array( $this, 'test_ai_endpoint' ) );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'jalsah-ai/v1', '/settings', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_ai_settings_rest' ),
			'permission_callback' => '__return_true',
		) );
		
		register_rest_route( 'jalsah-ai/v1', '/ping', array(
			'methods' => 'GET',
			'callback' => array( $this, 'ping_rest' ),
			'permission_callback' => '__return_true',
		) );
	}
	
	/**
	 * Handle CORS headers
	 */
	public function handle_cors() {
		// Only handle CORS for API requests
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false ) {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
			header( 'Access-Control-Allow-Credentials: true' );
			
			// Handle preflight OPTIONS request
			if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
				http_response_code( 200 );
				exit;
			}
			
			// Prevent WordPress from redirecting API requests
			remove_action( 'template_redirect', 'redirect_canonical' );
		}
	}

	/**
	 * Handle very early CORS
	 */
	public function handle_very_early_cors() {
		// Check if this is an AI API request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false ) {
			// Set CORS headers immediately
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
			header( 'Access-Control-Allow-Credentials: true' );
			
			// Handle preflight OPTIONS request
			if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
				http_response_code( 200 );
				exit;
			}
		}
	}

	/**
	 * Handle early API requests
	 */
	public function handle_early_api_requests( $wp ) {
		// Check if this is an AI API request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false ) {
			// Set CORS headers immediately
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
			header( 'Access-Control-Allow-Credentials: true' );
			
			// Handle preflight OPTIONS request
			if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
				http_response_code( 200 );
				exit;
			}
			
			// Don't prevent WordPress from processing - let it continue
			// but ensure our handler gets called
		}
	}

	/**
	 * Test AI endpoint
	 */
	public function test_ai_endpoint() {
		wp_send_json_success( array( 
			'message' => 'AI endpoint is working!', 
			'timestamp' => current_time( 'mysql' ),
			'endpoint' => 'test'
		) );
	}
	
	/**
	 * Simple test AJAX endpoint
	 */
	public function simple_test_ajax() {
		wp_send_json_success( array(
			'message' => 'Simple AJAX test successful!',
			'timestamp' => current_time( 'mysql' ),
			'post_data' => $_POST
		) );
	}
	
	/**
	 * Parse JSON field that may have escaped quotes
	 */
	private function parse_json_field( $json_string ) {
		// First try to decode as is
		$decoded = json_decode( $json_string, true );
		
		if ( $decoded !== null ) {
			return $decoded;
		}
		
		// If that fails, try to fix escaped quotes
		$fixed_json = str_replace( '\\"', '"', $json_string );
		$decoded = json_decode( $fixed_json, true );
		
		if ( $decoded !== null ) {
			return $decoded;
		}
		
		// If still fails, try to strip all backslashes
		$fixed_json = stripslashes( $json_string );
		$decoded = json_decode( $fixed_json, true );
		
		if ( $decoded !== null ) {
			return $decoded;
		}
		
		// Final fallback - return empty array
		return array();
	}
	
	/**
	 * Test diagnosis endpoint via AJAX
	 */
	public function test_diagnosis_ajax() {
		// Check if this is a POST request
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			wp_send_json_error( 'Method not allowed', 405 );
		}
		
		// Get data from POST (WordPress AJAX sends data via $_POST)
		$data = array(
			'mood' => $_POST['mood'] ?? '',
			'duration' => $_POST['duration'] ?? '',
			'selectedSymptoms' => $this->parse_json_field( $_POST['selectedSymptoms'] ?? '[]' ),
			'impact' => $_POST['impact'] ?? '',
			'affectedAreas' => $this->parse_json_field( $_POST['affectedAreas'] ?? '[]' ),
			'goals' => $_POST['goals'] ?? '',
			'preferredApproach' => $_POST['preferredApproach'] ?? ''
		);
		
		// Validate required fields
		if ( empty( $data['mood'] ) || empty( $data['selectedSymptoms'] ) ) {
			wp_send_json_error( 'Mood and symptoms are required', 400 );
		}
		
		// Process the diagnosis
		$diagnosis_id = $this->simulate_ai_diagnosis( $data );
		
		// If no diagnosis found, return error
		if ( $diagnosis_id === null ) {
			wp_send_json_error( 'No suitable diagnosis found. Please try again with different symptoms.', 400 );
		}
		
		$response_data = array( 
			'diagnosis_id' => $diagnosis_id,
			'message' => 'Diagnosis processed successfully'
		);
		
		wp_send_json_success( $response_data );
	}
	
	/**
	 * Flush rewrite rules
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
	
	/**
	 * Debug endpoint (for testing)
	 */
	public function debug_ai_endpoint() {
		if ( isset( $_GET['debug_ai'] ) ) {
			header( 'Content-Type: application/json' );
			header( 'Access-Control-Allow-Origin: *' );
			echo json_encode( array(
				'success' => true,
				'message' => 'AI endpoint is working!',
				'endpoint' => get_query_var( 'ai_endpoint' ),
				'request_uri' => $_SERVER['REQUEST_URI'],
				'query_vars' => $GLOBALS['wp_query']->query_vars,
			) );
			exit;
		}
	}
	
	/**
	 * Add AI query vars
	 */
	public function add_ai_query_vars( $vars ) {
		$vars[] = 'ai_endpoint';
		return $vars;
	}
	
	/**
	 * Handle AI requests
	 */
	public function handle_ai_requests() {
		$endpoint = get_query_var( 'ai_endpoint' );
		
		// Check if this is an AI API request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) === false ) {
			return;
		}
		
		if ( ! $endpoint ) {
			// If no endpoint is set, try to extract it from the URL
			$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$path_parts = explode( '/', trim( $path, '/' ) );
			
			// Find the 'ai' part and get what comes after it
			$ai_index = array_search( 'ai', $path_parts );
			if ( $ai_index !== false && isset( $path_parts[ $ai_index + 1 ] ) ) {
				$endpoint = $path_parts[ $ai_index + 1 ];
			}
		}
		
		if ( ! $endpoint ) {
			return;
		}
		
		// Prevent WordPress from outputting anything else
		define( 'DOING_AJAX', true );
		
		// Set CORS headers early to prevent redirects
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
		header( 'Access-Control-Allow-Credentials: true' );
		
		// Handle preflight OPTIONS request
		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			http_response_code( 200 );
			exit;
		}
		
		// Set JSON header
		header( 'Content-Type: application/json' );
		
		// Route the request
		$this->route_ai_request( $endpoint );
		exit;
	}
	
	/**
	 * Route AI request
	 */
	private function route_ai_request( $endpoint ) {
		$method = $_SERVER['REQUEST_METHOD'];
		$path = explode( '/', $endpoint );
		
		// Check for v2 endpoints
		if ($path[0] === 'v2') {
			switch ($path[1]) {
				case 'therapists':
					$this->handle_therapists_endpoint_v2($method, array_slice($path, 1));
					break;
				// Add more v2 endpoints as needed
				case 'ping':
					$this->send_success(['message' => 'Pong! (v2)', 'timestamp' => current_time('mysql'), 'endpoint' => $endpoint]);
					break;
				default:
					$this->send_error('V2 Endpoint not found', 404);
			}
			return;
		}
		
		switch ( $path[0] ) {
			case 'test':
				$this->send_success( array( 'message' => 'AI endpoint is working!', 'endpoint' => $endpoint ) );
				break;
			case 'debug':
				$this->send_success( array( 
					'message' => 'Debug endpoint',
					'endpoint' => $endpoint,
					'method' => $method,
					'path' => $path,
					'request_uri' => $_SERVER['REQUEST_URI'],
					'query_vars' => $GLOBALS['wp_query']->query_vars,
					'headers' => getallheaders()
				) );
				break;
			case 'ping':
				$this->send_success( array( 
					'message' => 'Pong!',
					'timestamp' => current_time( 'mysql' ),
					'endpoint' => $endpoint
				) );
				break;
			case 'auth':
				$this->handle_auth_endpoint( $method, $path );
				break;
			case 'therapists':
				$this->handle_therapists_endpoint( $method, $path );
				break;
			case 'appointments':
				$this->handle_appointments_endpoint( $method, $path );
				break;
			case 'cart':
				$this->handle_cart_endpoint( $method, $path );
				break;
			case 'diagnoses':
				$this->handle_diagnoses_endpoint( $method, $path );
				break;
			case 'diagnosis':
				$this->handle_diagnosis_endpoint( $method, $path );
				break;
			default:
				$this->send_error( 'Endpoint not found', 404 );
		}
	}
	
	/**
	 * Handle auth endpoints
	 */
	private function handle_auth_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'POST':
				if ( count( $path ) === 1 ) {
					$this->ai_login();
				} elseif ( $path[1] === 'register' ) {
					$this->ai_register();
				} elseif ( $path[1] === 'verify' ) {
					$this->ai_verify_email();
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Handle therapists endpoints
	 */
	private function handle_therapists_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					$this->get_ai_therapists();
				} elseif ( is_numeric( $path[1] ) ) {
					$this->get_ai_therapist( $path[1] );
				} elseif ( $path[1] === 'by-diagnosis' && is_numeric( $path[2] ) ) {
					$this->get_ai_therapists_by_diagnosis( $path[2] );
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Handle appointments endpoints
	 */
	private function handle_appointments_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( $path[1] === 'available' ) {
					$this->get_ai_available_appointments();
				} elseif ( $path[1] === 'user' && is_numeric( $path[2] ) ) {
					$this->get_ai_user_appointments( $path[2] );
				}
				break;
			case 'POST':
				if ( $path[1] === 'book' ) {
					$this->book_ai_appointment();
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Handle cart endpoints
	 */
	private function handle_cart_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( is_numeric( $path[1] ) ) {
					$this->get_ai_cart( $path[1] );
				}
				break;
			case 'POST':
				if ( $path[1] === 'add' ) {
					$this->add_to_ai_cart();
				} elseif ( $path[1] === 'checkout' ) {
					$this->checkout_ai_cart();
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Handle diagnoses endpoints
	 */
	private function handle_diagnoses_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					$this->get_ai_diagnoses();
				} elseif ( is_numeric( $path[1] ) ) {
					$this->get_ai_diagnosis( $path[1] );
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Handle diagnosis endpoint
	 */
	private function handle_diagnosis_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'POST':
				if ( count( $path ) === 1 ) {
					$this->process_diagnosis_data();
				} else {
					$this->send_error( 'Invalid endpoint', 404 );
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * AI Login
	 */
	private function ai_login() {
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		if ( ! isset( $data['email'] ) || ! isset( $data['password'] ) ) {
			$this->send_error( 'Email and password required', 400 );
		}
		
		$user = get_user_by( 'email', sanitize_email( $data['email'] ) );
		if ( ! $user || ! wp_check_password( $data['password'], $user->user_pass ) ) {
			$this->send_error( 'Invalid credentials', 401 );
		}
		
		// Debug: Log user roles
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AI Login Debug - User ID: ' . $user->ID );
			error_log( 'AI Login Debug - User Roles: ' . print_r( $user->roles, true ) );
		}
		
		// Check if user is a patient (customer) or doctor
		$allowed_roles = array( 'customer', 'doctor', 'clinic_manager' );
		if ( ! array_intersect( $allowed_roles, $user->roles ) ) {
			$this->send_error( 'Access denied. Only patients and doctors can access this platform.', 403 );
		}
		
		$token = $this->generate_jwt_token( $user->ID );
		
		$this->send_success( array(
			'token' => $token,
			'user' => array(
				'id' => $user->ID,
				'email' => $user->user_email,
				'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ),
				'last_name' => get_user_meta( $user->ID, 'billing_last_name', true ),
				'role' => $user->roles[0], // Primary role
				'roles' => $user->roles,   // All roles
			)
		) );
	}
	
	/**
	 * AI Register
	 */
	private function ai_register() {
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		$required_fields = array( 'first_name', 'last_name', 'age', 'email', 'phone', 'whatsapp', 'country', 'password' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
				$this->send_error( "Field {$field} is required", 400 );
			}
		}
		
		// Check if user exists
		$existing_user = get_user_by( 'email', sanitize_email( $data['email'] ) );
		if ( $existing_user ) {
			// Update missing fields
			$this->update_ai_user_fields( $existing_user->ID, $data );
			$user = $existing_user;
		} else {
			// Create new user
			$user_id = wp_create_user( $data['email'], $data['password'], $data['email'] );
			if ( is_wp_error( $user_id ) ) {
				$this->send_error( $user_id->get_error_message(), 400 );
			}
			
			$user = get_user_by( 'ID', $user_id );
			$user->set_role( 'customer' );
			$this->update_ai_user_fields( $user_id, $data );
		}
		
		$token = $this->generate_jwt_token( $user->ID );
		
		$this->send_success( array(
			'token' => $token,
			'user' => array(
				'id' => $user->ID,
				'email' => $user->user_email,
				'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ),
				'last_name' => get_user_meta( $user->ID, 'billing_last_name', true ),
			)
		) );
	}
	
	/**
	 * Update AI user fields
	 */
	private function update_ai_user_fields( $user_id, $data ) {
		update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $data['first_name'] ) );
		update_user_meta( $user_id, 'billing_last_name', sanitize_text_field( $data['last_name'] ) );
		update_user_meta( $user_id, 'age', intval( $data['age'] ) );
		update_user_meta( $user_id, 'billing_phone', sanitize_text_field( $data['phone'] ) );
		update_user_meta( $user_id, 'whatsapp', sanitize_text_field( $data['whatsapp'] ) );
		update_user_meta( $user_id, 'billing_country', sanitize_text_field( $data['country'] ) );
	}
	
	/**
	 * AI Verify Email
	 */
	private function ai_verify_email() {
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		if ( ! isset( $data['email'] ) || ! isset( $data['code'] ) ) {
			$this->send_error( 'Email and verification code required', 400 );
		}
		
		// Implement email verification logic here
		// For now, just return success
		$this->send_success( array( 'message' => 'Email verified successfully' ) );
	}
	
	/**
	 * Get AI Therapists
	 */
	private function get_ai_therapists() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'therapist_applications';
		
		// Get all approved therapists who are enabled for AI platform
		$applications = $wpdb->get_results(
			"SELECT * FROM $table_name 
			WHERE status = 'approved' AND show_on_ai_site = 1 
			ORDER BY name ASC"
		);
		
		$result = array();
		foreach ( $applications as $application ) {
			$result[] = $this->format_ai_therapist_from_application( $application );
		}
		
		$this->send_success( $result );
	}
	
	/**
	 * Get AI Therapist
	 */
	private function get_ai_therapist( $therapist_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'therapist_applications';
		
		$application = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND status = 'approved' AND show_on_ai_site = 1",
			$therapist_id
		) );
		
		if ( ! $application ) {
			$this->send_error( 'Therapist not found or not available on AI platform', 404 );
		}
		
		$this->send_success( $this->format_ai_therapist_from_application( $application ) );
	}
	
	/**
	 * Get AI Therapists by Diagnosis
	 */
	private function get_ai_therapists_by_diagnosis( $diagnosis_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'therapist_applications';
		
		$applications = $wpdb->get_results( $wpdb->prepare(
			"SELECT ta.* FROM $table_name ta
			JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON ta.user_id = td.therapist_id
			WHERE td.diagnosis_id = %d AND ta.status = 'approved' AND ta.show_on_ai_site = 1
			ORDER BY ta.name ASC",
			$diagnosis_id
		) );
		
		$result = array();
		foreach ( $applications as $application ) {
			$result[] = $this->format_ai_therapist_from_application( $application );
		}
		
		$this->send_success( $result );
	}
	
	/**
	 * Format AI Therapist from Application
	 */
	private function format_ai_therapist_from_application( $application ) {
		$diagnoses = $this->get_therapist_diagnoses( $application->user_id );
		
		// Get current locale using helper function
		$locale = snks_get_current_language();
		
		// Select appropriate language based on locale
		$name = $locale === 'ar' ? $application->name : $application->name_en;
		if ( empty( $name ) ) {
			$name = $application->name; // Fallback to Arabic name
		}
		
		$bio = $locale === 'ar' ? $application->bio : $application->bio_en;
		if ( empty( $bio ) ) {
			$bio = $application->bio; // Fallback to Arabic bio
		}
		
		$ai_bio = $locale === 'ar' ? $application->ai_bio : $application->ai_bio_en;
		if ( empty( $ai_bio ) ) {
			$ai_bio = $bio; // Fallback to regular bio
		}
		
		// Get profile image URL
		$profile_image_url = '';
		if ( $application->profile_image ) {
			$profile_image_url = wp_get_attachment_image_url( $application->profile_image, 'medium' );
			if ( ! $profile_image_url ) {
				$profile_image_url = wp_get_attachment_url( $application->profile_image );
			}
		}
		
		$result = array(
			'id' => $application->user_id,
			'name' => $name,
			'name_en' => $application->name_en,
			'name_ar' => $application->name,
			'photo' => $profile_image_url,
			'bio' => $ai_bio,
			'bio_en' => $application->ai_bio_en,
			'bio_ar' => $application->ai_bio,
			'public_bio' => $ai_bio,
			'public_bio_en' => $application->ai_bio_en,
			'public_bio_ar' => $application->ai_bio,
			'certifications' => $application->ai_certifications,
			'earliest_slot' => $application->ai_earliest_slot,
			'rating' => floatval( $application->rating ),
			'total_ratings' => intval( $application->total_ratings ),
			'price' => $this->get_therapist_ai_price( $application->user_id ),
			'diagnoses' => $diagnoses,
		);
		
		// Debug logging
		error_log( 'Therapist API Response for ID ' . $application->user_id . ': ' . print_r( $result, true ) );
		
		return $result;
	}
	
	/**
	 * Format AI Therapist (Legacy function for backward compatibility)
	 */
	private function format_ai_therapist( $therapist ) {
		$diagnoses = $this->get_therapist_diagnoses( $therapist->ID );
		
		// Get current locale using helper function
		$locale = snks_get_current_language();
		
		// Get bilingual data
		$display_name_en = get_user_meta( $therapist->ID, 'ai_display_name_en', true );
		$display_name_ar = get_user_meta( $therapist->ID, 'ai_display_name_ar', true );
		$bio_en = get_user_meta( $therapist->ID, 'ai_bio_en', true );
		$bio_ar = get_user_meta( $therapist->ID, 'ai_bio_ar', true );
		$public_bio_en = get_user_meta( $therapist->ID, 'public_short_bio_en', true );
		$public_bio_ar = get_user_meta( $therapist->ID, 'public_short_bio_ar', true );
		
		// Select appropriate language based on locale
		$name = $locale === 'ar' ? $display_name_ar : $display_name_en;
		if ( empty( $name ) ) {
			$name = get_user_meta( $therapist->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $therapist->ID, 'billing_last_name', true );
		}
		
		$bio = $locale === 'ar' ? $bio_ar : $bio_en;
		if ( empty( $bio ) ) {
			$bio = get_user_meta( $therapist->ID, 'ai_bio', true ); // Fallback to old field
		}
		
		$public_bio = $locale === 'ar' ? $public_bio_ar : $public_bio_en;
		
		return array(
			'id' => $therapist->ID,
			'name' => $name,
			'name_en' => $display_name_en,
			'name_ar' => $display_name_ar,
			'photo' => get_user_meta( $therapist->ID, 'profile-image', true ),
			'bio' => $bio,
			'bio_en' => $bio_en,
			'bio_ar' => $bio_ar,
			'public_bio' => $public_bio,
			'public_bio_en' => $public_bio_en,
			'public_bio_ar' => $public_bio_ar,
			'certifications' => get_user_meta( $therapist->ID, 'ai_certifications', true ),
			'earliest_slot' => get_user_meta( $therapist->ID, 'ai_earliest_slot', true ),
			'price' => $this->get_therapist_ai_price( $therapist->ID ),
			'diagnoses' => $diagnoses,
		);
	}
	
	/**
	 * Get Therapist Diagnoses
	 */
	private function get_therapist_diagnoses( $therapist_id ) {
		global $wpdb;
		
		// Get current locale using helper function
		$locale = snks_get_current_language();
		
		$diagnoses = $wpdb->get_results( $wpdb->prepare(
			"SELECT d.*, td.rating, td.suitability_message_en, td.suitability_message_ar 
			FROM {$wpdb->prefix}snks_diagnoses d
			JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON d.id = td.diagnosis_id
			WHERE td.therapist_id = %d",
			$therapist_id
		) );
		

		
		// Process each diagnosis to include bilingual data
		foreach ( $diagnoses as $diagnosis ) {
			// Ensure rating is a number
			$diagnosis->rating = floatval( $diagnosis->rating );
			
			// Get bilingual diagnosis names
			$name_en = $diagnosis->name_en ?: $diagnosis->name;
			$name_ar = $diagnosis->name_ar ?: '';
			$description_en = $diagnosis->description_en ?: $diagnosis->description;
			$description_ar = $diagnosis->description_ar ?: '';
			
			// Select appropriate language based on locale
			$diagnosis->name = $locale === 'ar' ? $name_ar : $name_en;
			$diagnosis->description = $locale === 'ar' ? $description_ar : $description_en;
			
			// Add bilingual fields
			$diagnosis->name_en = $name_en;
			$diagnosis->name_ar = $name_ar;
			$diagnosis->description_en = $description_en;
			$diagnosis->description_ar = $description_ar;
			
			// Handle suitability messages
			$suitability_message_en = isset( $diagnosis->suitability_message_en ) ? $diagnosis->suitability_message_en : '';
			$suitability_message_ar = isset( $diagnosis->suitability_message_ar ) ? $diagnosis->suitability_message_ar : '';
			$suitability_message = isset( $diagnosis->suitability_message ) ? $diagnosis->suitability_message : '';
			
			$suitability_message_en = $suitability_message_en ?: $suitability_message;
			
			$diagnosis->suitability_message = $locale === 'ar' ? $suitability_message_ar : $suitability_message_en;
			$diagnosis->suitability_message_en = $suitability_message_en;
			$diagnosis->suitability_message_ar = $suitability_message_ar;
		}
		

		
		return $diagnoses;
	}
	
	/**
	 * Get Therapist AI Price
	 */
	private function get_therapist_ai_price( $therapist_id ) {
		// Check if this is a demo therapist
		$is_demo_doctor = get_user_meta( $therapist_id, 'is_demo_doctor', true );
		
		if ( $is_demo_doctor ) {
			// For demo therapists, use the simple pricing fields
			$price_45_min = get_user_meta( $therapist_id, 'price_45_min', true );
			$price_60_min = get_user_meta( $therapist_id, 'price_60_min', true );
			$price_90_min = get_user_meta( $therapist_id, 'price_90_min', true );
			
			// Return pricing in the format expected by the frontend (45_minutes structure)
			return array(
				'countries' => array(),
				'others' => intval( $price_45_min ) ?: 150 // Default to 150 if not set
			);
		} else {
			// For regular therapists, use the main pricing system
			$pricing = snks_doctor_online_pricings( $therapist_id );
			return isset( $pricing['45_minutes'] ) ? $pricing['45_minutes'] : array();
		}
	}
	
	/**
	 * Get AI Available Appointments
	 */
	private function get_ai_available_appointments() {
		$therapist_id = isset( $_GET['therapist_id'] ) ? intval( $_GET['therapist_id'] ) : 0;
		$date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
		
		if ( ! $therapist_id || ! $date ) {
			$this->send_error( 'Therapist ID and date required', 400 );
		}
		
		// Get available 45-minute online slots
		$slots = get_bookable_dates( $therapist_id, 45, '+1 month', 'online' );
		
		$available_slots = array();
		foreach ( $slots as $slot ) {
			if ( date( 'Y-m-d', strtotime( $slot->date_time ) ) === $date ) {
				$available_slots[] = array(
					'id' => $slot->ID,
					'time' => date( 'H:i', strtotime( $slot->starts ) ),
					'date' => $slot->date_time,
				);
			}
		}
		
		$this->send_success( $available_slots );
	}
	
	/**
	 * Get AI User Appointments
	 */
	private function get_ai_user_appointments( $user_id ) {
		// Verify JWT token
		$token_user_id = $this->verify_jwt_token();
		if ( $token_user_id != $user_id ) {
			$this->send_error( 'Unauthorized', 401 );
		}
		
		$appointments = snks_get_patient_sessions( 'all' );
		$ai_appointments = array();
		
		foreach ( $appointments as $appointment ) {
			$order = wc_get_order( $appointment->order_id );
			if ( $order && $order->get_meta( 'from_jalsah_ai' ) ) {
				$ai_appointments[] = array(
					'id' => $appointment->ID,
					'date' => $appointment->date_time,
					'status' => $appointment->session_status,
					'therapist_name' => get_user_meta( $appointment->user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $appointment->user_id, 'billing_last_name', true ),
				);
			}
		}
		
		$this->send_success( $ai_appointments );
	}
	
	/**
	 * Book AI Appointment
	 */
	private function book_ai_appointment() {
		$user_id = $this->verify_jwt_token();
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		if ( ! isset( $data['slot_id'] ) ) {
			$this->send_error( 'Slot ID required', 400 );
		}
		
		$slot = snks_get_timetable_by( 'ID', intval( $data['slot_id'] ) );
		if ( ! $slot || $slot->session_status !== 'waiting' ) {
			$this->send_error( 'Slot not available', 400 );
		}
		
		// Add to AI cart
		$cart = get_user_meta( $user_id, 'ai_cart', true );
		if ( ! is_array( $cart ) ) {
			$cart = array();
		}
		
		$cart[] = array(
			'slot_id' => $slot->ID,
			'therapist_id' => $slot->user_id,
			'date_time' => $slot->date_time,
			'price' => $this->get_therapist_ai_price( $slot->user_id ),
		);
		
		update_user_meta( $user_id, 'ai_cart', $cart );
		
		$this->send_success( array( 'message' => 'Added to cart' ) );
	}
	
	/**
	 * Get AI Cart
	 */
	private function get_ai_cart( $user_id ) {
		$token_user_id = $this->verify_jwt_token();
		if ( $token_user_id != $user_id ) {
			$this->send_error( 'Unauthorized', 401 );
		}
		
		$cart = get_user_meta( $user_id, 'ai_cart', true );
		if ( ! is_array( $cart ) ) {
			$cart = array();
		}
		
		$this->send_success( $cart );
	}
	
	/**
	 * Add to AI Cart
	 */
	private function add_to_ai_cart() {
		$user_id = $this->verify_jwt_token();
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		if ( ! isset( $data['slot_id'] ) ) {
			$this->send_error( 'Slot ID required', 400 );
		}
		
		$slot = snks_get_timetable_by( 'ID', intval( $data['slot_id'] ) );
		if ( ! $slot || $slot->session_status !== 'waiting' ) {
			$this->send_error( 'Slot not available', 400 );
		}
		
		$cart = get_user_meta( $user_id, 'ai_cart', true );
		if ( ! is_array( $cart ) ) {
			$cart = array();
		}
		
		$cart[] = array(
			'slot_id' => $slot->ID,
			'therapist_id' => $slot->user_id,
			'date_time' => $slot->date_time,
			'price' => $this->get_therapist_ai_price( $slot->user_id ),
		);
		
		update_user_meta( $user_id, 'ai_cart', $cart );
		
		$this->send_success( array( 'message' => 'Added to cart' ) );
	}
	
	/**
	 * Checkout AI Cart
	 */
	private function checkout_ai_cart() {
		$user_id = $this->verify_jwt_token();
		$cart = get_user_meta( $user_id, 'ai_cart', true );
		
		if ( ! is_array( $cart ) || empty( $cart ) ) {
			$this->send_error( 'Cart is empty', 400 );
		}
		
		// Create WooCommerce order
		$order = wc_create_order();
		$order->set_customer_id( $user_id );
		
		$total = 0;
		$session_data = array();
		
		foreach ( $cart as $item ) {
			$slot = snks_get_timetable_by( 'ID', $item['slot_id'] );
			if ( $slot && $slot->session_status === 'waiting' ) {
				$price = $item['price']['others'] ?? 0;
				$total += $price;
				
				$session_data[] = array(
					'slot_id' => $slot->ID,
					'therapist_id' => $slot->user_id,
					'date_time' => $slot->date_time,
					'price' => $price,
				);
			}
		}
		
		if ( empty( $session_data ) ) {
			$this->send_error( 'No valid sessions in cart', 400 );
		}
		
		// Add product to order
		$product_id = 335; // Default product ID
		$product = wc_get_product( $product_id );
		$order->add_product( $product, 1 );
		
		$order->set_total( $total );
		$order->update_meta_data( 'from_jalsah_ai', true );
		$order->update_meta_data( 'ai_sessions', json_encode( $session_data ) );
		$order->set_status( 'pending' );
		$order->save();
		
		// Clear cart
		delete_user_meta( $user_id, 'ai_cart' );
		
		$this->send_success( array(
			'order_id' => $order->get_id(),
			'checkout_url' => $order->get_checkout_payment_url(),
			'total' => $total,
		) );
	}
	
	/**
	 * Get AI Diagnoses
	 */
	private function get_ai_diagnoses() {
		global $wpdb;
		
		// Get current locale using helper function
		$locale = snks_get_current_language();
		
		$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name_en, name" );
		
		// Process each diagnosis to include bilingual data
		foreach ( $diagnoses as $diagnosis ) {
			// Get bilingual diagnosis names
			$name_en = $diagnosis->name_en ?: $diagnosis->name;
			$name_ar = $diagnosis->name_ar ?: '';
			$description_en = $diagnosis->description_en ?: $diagnosis->description;
			$description_ar = $diagnosis->description_ar ?: '';
			
			// Select appropriate language based on locale
			$diagnosis->name = $locale === 'ar' ? $name_ar : $name_en;
			$diagnosis->description = $locale === 'ar' ? $description_ar : $description_en;
			
			// Add bilingual fields
			$diagnosis->name_en = $name_en;
			$diagnosis->name_ar = $name_ar;
			$diagnosis->description_en = $description_en;
			$diagnosis->description_ar = $description_ar;
		}
		
		$this->send_success( $diagnoses );
	}

	/**
	 * Get AI Diagnosis by ID
	 */
	private function get_ai_diagnosis( $diagnosis_id ) {
		global $wpdb;

		// Get current locale using helper function
		$locale = snks_get_current_language();

		$diagnosis = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_diagnoses WHERE id = %d",
			$diagnosis_id
		) );

		if ( ! $diagnosis ) {
			$this->send_error( 'Diagnosis not found', 404 );
		}

		// Get bilingual diagnosis names
		$name_en = $diagnosis->name_en ?: $diagnosis->name;
		$name_ar = $diagnosis->name_ar ?: '';
		$description_en = $diagnosis->description_en ?: $diagnosis->description;
		$description_ar = $diagnosis->description_ar ?: '';
		
		// Select appropriate language based on locale
		$diagnosis->name = $locale === 'ar' ? $name_ar : $name_en;
		$diagnosis->description = $locale === 'ar' ? $description_ar : $description_en;
		
		// Add bilingual fields
		$diagnosis->name_en = $name_en;
		$diagnosis->name_ar = $name_ar;
		$diagnosis->description_en = $description_en;
		$diagnosis->description_ar = $description_ar;

		$this->send_success( $diagnosis );
	}
	
	/**
	 * Process diagnosis data
	 */
	private function process_diagnosis_data() {
		// Get the raw input
		$raw_input = file_get_contents( 'php://input' );
		$data = json_decode( $raw_input, true );
		
		// Validate required fields
		if ( ! isset( $data['mood'] ) || ! isset( $data['selectedSymptoms'] ) ) {
			$this->send_error( 'Mood and symptoms are required', 400 );
		}
		
		// Process the diagnosis
		$diagnosis_id = $this->simulate_ai_diagnosis( $data );
		
		// If no diagnosis found, return error
		if ( $diagnosis_id === null ) {
			$this->send_error( 'No suitable diagnosis found. Please try again with different symptoms.', 400 );
		}
		
		$response_data = array( 
			'diagnosis_id' => $diagnosis_id,
			'message' => 'Diagnosis processed successfully'
		);
		
		$this->send_success( $response_data );
	}
	
	/**
	 * Simulate AI diagnosis based on form data
	 */
	private function simulate_ai_diagnosis( $data ) {
		global $wpdb;
		
		$mood = $data['mood'];
		$symptoms = $data['selectedSymptoms'];
		$impact = $data['impact'] ?? 'moderate';
		
		// Get all available diagnoses from database
		$all_diagnoses = $wpdb->get_results( "SELECT id, name, name_en FROM {$wpdb->prefix}snks_diagnoses ORDER BY id ASC" );
		
		if ( empty( $all_diagnoses ) ) {
			// If no diagnoses exist, return null
			return null;
		}
		
		// Map symptoms to diagnosis keywords
		$symptom_mapping = array(
			// Anxiety-related symptoms
			'anxiety' => array( 'anxiety', 'panic', 'worry', 'fear', 'nervous' ),
			'panic' => array( 'anxiety', 'panic', 'worry', 'fear', 'nervous' ),
			'worry' => array( 'anxiety', 'panic', 'worry', 'fear', 'nervous' ),
			'fear' => array( 'anxiety', 'panic', 'worry', 'fear', 'nervous' ),
			'nervous' => array( 'anxiety', 'panic', 'worry', 'fear', 'nervous' ),
			
			// Depression-related symptoms
			'depression' => array( 'depression', 'mood', 'sadness', 'hopelessness', 'worthless' ),
			'hopelessness' => array( 'depression', 'mood', 'sadness', 'hopelessness', 'worthless' ),
			'sadness' => array( 'depression', 'mood', 'sadness', 'hopelessness', 'worthless' ),
			'worthless' => array( 'depression', 'mood', 'sadness', 'hopelessness', 'worthless' ),
			
			// Stress-related symptoms
			'stress' => array( 'stress', 'burnout', 'overwhelmed', 'pressure' ),
			'overwhelmed' => array( 'stress', 'burnout', 'overwhelmed', 'pressure' ),
			'pressure' => array( 'stress', 'burnout', 'overwhelmed', 'pressure' ),
			
			// Trauma-related symptoms
			'trauma' => array( 'trauma', 'ptsd', 'flashback', 'nightmare' ),
			'flashback' => array( 'trauma', 'ptsd', 'flashback', 'nightmare' ),
			'nightmare' => array( 'trauma', 'ptsd', 'flashback', 'nightmare' ),
			
			// Sleep-related symptoms
			'insomnia' => array( 'sleep', 'insomnia', 'restless' ),
			'sleep' => array( 'sleep', 'insomnia', 'restless' ),
			'restless' => array( 'sleep', 'insomnia', 'restless' ),
			
			// Relationship issues
			'relationship' => array( 'relationship', 'couple', 'family', 'marriage' ),
			'couple' => array( 'relationship', 'couple', 'family', 'marriage' ),
			'family' => array( 'relationship', 'couple', 'family', 'marriage' ),
			
			// Eating disorders
			'eating' => array( 'eating', 'food', 'anorexia', 'bulimia' ),
			'food' => array( 'eating', 'food', 'anorexia', 'bulimia' ),
			
			// Addiction
			'addiction' => array( 'addiction', 'substance', 'alcohol', 'drug' ),
			'substance' => array( 'addiction', 'substance', 'alcohol', 'drug' ),
			
			// OCD
			'obsession' => array( 'ocd', 'obsession', 'compulsion', 'ritual' ),
			'compulsion' => array( 'ocd', 'obsession', 'compulsion', 'ritual' ),
			'ritual' => array( 'ocd', 'obsession', 'compulsion', 'ritual' ),
			
			// Anger
			'anger' => array( 'anger', 'rage', 'irritable', 'aggressive' ),
			'rage' => array( 'anger', 'rage', 'irritable', 'aggressive' ),
			'irritable' => array( 'anger', 'rage', 'irritable', 'aggressive' ),
			
			// Grief
			'grief' => array( 'grief', 'loss', 'bereavement', 'mourning' ),
			'loss' => array( 'grief', 'loss', 'bereavement', 'mourning' ),
			
			// Self-esteem
			'confidence' => array( 'self-esteem', 'confidence', 'worth', 'value' ),
			'worth' => array( 'self-esteem', 'confidence', 'worth', 'value' ),
			'value' => array( 'self-esteem', 'confidence', 'worth', 'value' ),
			
			// Work-life balance
			'work' => array( 'work', 'balance', 'career', 'professional' ),
			'balance' => array( 'work', 'balance', 'career', 'professional' ),
			'career' => array( 'work', 'balance', 'career', 'professional' ),
			
			// Bipolar
			'manic' => array( 'bipolar', 'manic', 'mania', 'mood swing' ),
			'mania' => array( 'bipolar', 'manic', 'mania', 'mood swing' ),
			'mood swing' => array( 'bipolar', 'manic', 'mania', 'mood swing' ),
			
			// Phobias
			'phobia' => array( 'phobia', 'fear', 'avoidance', 'panic' ),
			'avoidance' => array( 'phobia', 'fear', 'avoidance', 'panic' ),
			
			// Personality disorders
			'personality' => array( 'personality', 'borderline', 'narcissistic', 'antisocial' ),
			'borderline' => array( 'personality', 'borderline', 'narcissistic', 'antisocial' ),
			'narcissistic' => array( 'personality', 'borderline', 'narcissistic', 'antisocial' ),
			
			// Child and adolescent
			'child' => array( 'child', 'adolescent', 'teen', 'youth' ),
			'adolescent' => array( 'child', 'adolescent', 'teen', 'youth' ),
			'teen' => array( 'child', 'adolescent', 'teen', 'youth' ),
			'youth' => array( 'child', 'adolescent', 'teen', 'youth' )
		);
		
		// Find matching diagnoses based on symptoms
		$matched_diagnoses = array();
		
		foreach ( $symptoms as $symptom ) {
			if ( isset( $symptom_mapping[$symptom] ) ) {
				$keywords = $symptom_mapping[$symptom];
				
				foreach ( $all_diagnoses as $diagnosis ) {
					$diagnosis_name = strtolower( $diagnosis->name_en ?: $diagnosis->name );
					
					foreach ( $keywords as $keyword ) {
						if ( strpos( $diagnosis_name, $keyword ) !== false ) {
							$matched_diagnoses[$diagnosis->id] = $diagnosis;
							break 2; // Found a match for this diagnosis, move to next symptom
						}
					}
				}
			}
		}
		
		// If we found matches, return the first one
		if ( ! empty( $matched_diagnoses ) ) {
			$first_match = reset( $matched_diagnoses );
			return intval( $first_match->id );
		}
		
		// If no specific matches found, return a general diagnosis based on mood
		$mood_mapping = array(
			'very_bad' => array( 'depression', 'anxiety', 'stress' ),
			'bad' => array( 'stress', 'anxiety', 'depression' ),
			'neutral' => array( 'stress', 'work', 'relationship' ),
			'good' => array( 'work', 'relationship', 'self-esteem' ),
			'very_good' => array( 'work', 'relationship', 'self-esteem' )
		);
		
		if ( isset( $mood_mapping[$mood] ) ) {
			$mood_keywords = $mood_mapping[$mood];
			
			foreach ( $mood_keywords as $keyword ) {
				foreach ( $all_diagnoses as $diagnosis ) {
					$diagnosis_name = strtolower( $diagnosis->name_en ?: $diagnosis->name );
					if ( strpos( $diagnosis_name, $keyword ) !== false ) {
						return intval( $diagnosis->id );
					}
				}
			}
		}
		
		// Final fallback: return the first available diagnosis
		return intval( $all_diagnoses[0]->id );
	}
	
	/**
	 * Generate JWT Token
	 */
	private function generate_jwt_token( $user_id ) {
		$payload = array(
			'user_id' => $user_id,
			'iat' => time(),
			'exp' => time() + ( 24 * 60 * 60 ), // 24 hours
		);
		
		return JWT::encode( $payload, $this->jwt_secret, $this->jwt_algorithm );
	}
	
	/**
	 * Verify JWT Token
	 */
	private function verify_jwt_token() {
		$headers = getallheaders();
		$auth_header = isset( $headers['Authorization'] ) ? $headers['Authorization'] : '';
		
		// Also check for HTTP_AUTHORIZATION (some servers use this)
		if ( empty( $auth_header ) && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = $_SERVER['HTTP_AUTHORIZATION'];
		}
		
		if ( ! preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$this->send_error( 'No token provided', 401 );
		}
		
		$token = $matches[1];
		
		try {
			$decoded = JWT::decode( $token, new Key( $this->jwt_secret, $this->jwt_algorithm ) );
			return $decoded->user_id;
		} catch ( Exception $e ) {
			$this->send_error( 'Invalid token', 401 );
		}
	}
	
	/**
	 * Send success response
	 */
	private function send_success( $data ) {
		http_response_code( 200 );
		echo json_encode( array( 'success' => true, 'data' => $data ) );
		exit;
	}
	
	/**
	 * Send error response
	 */
	private function send_error( $message, $code = 400 ) {
		http_response_code( $code );
		echo json_encode( array( 'success' => false, 'error' => $message ) );
		exit;
	}
	
	/**
	 * Get AI Settings AJAX Handler
	 */
	public function get_ai_settings_ajax() {
		$current_language = snks_get_current_language();
		
		$settings = array(
			'bilingual_enabled' => snks_is_bilingual_enabled(),
			'default_language' => snks_get_default_language(),
			'site_title' => snks_get_site_title( $current_language ),
			'site_description' => snks_get_site_description( $current_language ),
		);
		
		wp_send_json_success( $settings );
	}
	
	/**
	 * Test Connection AJAX Handler
	 */
	public function test_connection_ajax() {
		wp_send_json_success( array(
			'message' => 'Connection successful',
			'timestamp' => current_time( 'mysql' ),
			'wordpress_version' => get_bloginfo( 'version' ),
			'plugin_active' => true
		) );
	}
	
	/**
	 * Get AI Settings REST API Handler
	 */
	public function get_ai_settings_rest( $request ) {
		$current_language = snks_get_current_language();
		
		$settings = array(
			'bilingual_enabled' => snks_is_bilingual_enabled(),
			'default_language' => snks_get_default_language(),
			'site_title' => snks_get_site_title( $current_language ),
			'site_description' => snks_get_site_description( $current_language ),
		);
		
		return new WP_REST_Response( array(
			'success' => true,
			'data' => $settings
		), 200 );
	}
	
	/**
	 * Ping REST API Handler
	 */
	public function ping_rest( $request ) {
		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Jalsah AI API is working',
			'timestamp' => current_time( 'mysql' )
		), 200 );
	}

	// Placeholder for v2 therapists endpoint handler
	private function handle_therapists_endpoint_v2($method, $path) {
		// TODO: Implement country-based pricing and currency logic here
		if ($method === 'GET' && count($path) === 1) {
			$this->send_success(['message' => 'v2 therapists endpoint placeholder']);
		} else {
			$this->send_error('Method not allowed or not implemented (v2)', 405);
		}
	}
}

// Initialize AI Integration
new SNKS_AI_Integration(); 