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

// Include AI helper classes
require_once SNKS_DIR . 'functions/helpers/ai-products.php';
require_once SNKS_DIR . 'functions/helpers/ai-orders.php';

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
		add_action( 'wp_ajax_chat_diagnosis_ajax', array( $this, 'chat_diagnosis_ajax' ) );
		add_action( 'wp_ajax_nopriv_chat_diagnosis_ajax', array( $this, 'chat_diagnosis_ajax' ) );
		add_action( 'wp_ajax_simple_test_ajax', array( $this, 'simple_test_ajax' ) );
		add_action( 'wp_ajax_nopriv_simple_test_ajax', array( $this, 'simple_test_ajax' ) );
		
		// Add AJAX endpoints for settings
		add_action( 'wp_ajax_get_ai_settings', array( $this, 'get_ai_settings_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_ai_settings', array( $this, 'get_ai_settings_ajax' ) );
		

	}
	
	/**
	 * Register AI endpoints
	 */
	public function register_ai_endpoints() {
		// Add rewrite rules for API endpoints - more specific patterns first
		add_rewrite_rule( '^api/ai/therapists/by-diagnosis/(\d+)/?$', 'index.php?ai_endpoint=therapists/by-diagnosis/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/(\d+)/([^/]+)/?$', 'index.php?ai_endpoint=therapists/$matches[1]/$matches[2]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/(\d+)/?$', 'index.php?ai_endpoint=therapists/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/?$', 'index.php?ai_endpoint=therapists', 'top' );
		add_rewrite_rule( '^api/ai/([^/]+)/?$', 'index.php?ai_endpoint=$matches[1]', 'top' );
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

		// New REST routes for existing timetable system
		register_rest_route( 'jalsah-ai/v1', '/therapist-availability', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_ai_therapist_availability' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'jalsah-ai/v1', '/add-appointment-to-cart', array(
			'methods' => 'POST',
			'callback' => array( $this, 'add_appointment_to_cart' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'jalsah-ai/v1', '/get-user-cart', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_user_cart' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'jalsah-ai/v1', '/remove-from-cart', array(
			'methods' => 'POST',
			'callback' => array( $this, 'remove_from_cart' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'jalsah-ai/v1', '/book-appointments-from-cart', array(
			'methods' => 'POST',
			'callback' => array( $this, 'book_appointments_from_cart' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'jalsah-ai/v1', '/get-user-appointments', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_user_appointments' ),
			'permission_callback' => '__return_true',
		) );

		// WooCommerce order creation endpoint
		register_rest_route( 'jalsah-ai/v1', '/create-woocommerce-order', array(
			'methods' => 'POST',
			'callback' => array( $this, 'create_woocommerce_order_from_cart' ),
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
	 * Chat diagnosis endpoint via AJAX
	 */
	public function chat_diagnosis_ajax() {
		// Debug logging
		error_log( 'Chat diagnosis AJAX called' );
		error_log( 'POST data: ' . print_r( $_POST, true ) );
		
		// Get data from POST (following the same pattern as other AJAX handlers)
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );
		
		// Handle escaped JSON from frontend
		$conversation_history_raw = $_POST['conversation_history'] ?? '[]';
		$conversation_history = json_decode( stripslashes( $conversation_history_raw ), true );
		
		// Ensure we have an array
		if ( ! is_array( $conversation_history ) ) {
			$conversation_history = array();
		}
		
		error_log( 'Message: ' . $message );
		error_log( 'Conversation history: ' . print_r( $conversation_history, true ) );
		
		// Validate required fields
		if ( empty( $message ) ) {
			error_log( 'Message is empty' );
			wp_send_json_error( 'Message is required', 400 );
		}
		
		// Process the chat diagnosis
		$result = $this->process_chat_diagnosis( $message, $conversation_history );
		
		if ( is_wp_error( $result ) ) {
			error_log( 'Process chat diagnosis error: ' . $result->get_error_message() );
			wp_send_json_error( $result->get_error_message(), 400 );
		}
		
		error_log( 'Chat diagnosis result: ' . print_r( $result, true ) );
		wp_send_json_success( $result );
	}
	
	/**
	 * Flush rewrite rules
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
		// Rewrite rules flushed
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
		
		// Route the request
		
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
			case 'earliest-slot-test':
				$this->send_success( array( 
					'message' => 'Earliest slot test endpoint',
					'endpoint' => $endpoint,
					'method' => $method,
					'path' => $path,
					'request_uri' => $_SERVER['REQUEST_URI'],
					'full_path' => $path
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
		// Debug logging
		error_log("Therapists Endpoint Debug: Method: " . $method . ", Path: " . print_r($path, true));
		
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					error_log("Therapists Endpoint Debug: Calling get_ai_therapists()");
					$this->get_ai_therapists();
				} elseif ( is_numeric( $path[1] ) ) {
					error_log("Therapists Endpoint Debug: Therapist ID: " . $path[1] . ", Path[2]: " . ($path[2] ?? 'not set'));
					
					if ( isset( $path[2] ) && $path[2] === 'details' ) {
						error_log("Therapists Endpoint Debug: Calling therapist details");
						// Call the therapist details REST API function
						$request = new WP_REST_Request('GET', '/jalsah-ai/v1/therapists/' . $path[1] . '/details');
						$request->set_param('id', $path[1]);
						$response = snks_get_therapist_details_rest($request);
						
						if (is_wp_error($response)) {
							$this->send_error($response->get_error_message(), $response->get_error_data()['status'] ?? 400);
						} else {
							// Return the data directly without double-wrapping
							$this->send_success($response['data']);
						}
					} elseif ( isset( $path[2] ) && $path[2] === 'earliest-slot' ) {
						error_log("Therapists Endpoint Debug: Calling earliest slot for therapist ID: " . $path[1]);
						$this->get_ai_therapist_earliest_slot( $path[1] );
					} elseif ( isset( $path[2] ) && $path[2] === 'available-dates' ) {
						error_log("Therapists Endpoint Debug: Calling available dates for therapist ID: " . $path[1]);
						$this->get_ai_therapist_available_dates( $path[1] );
					} elseif ( isset( $path[2] ) && $path[2] === 'time-slots' ) {
						$date = $_GET['date'] ?? '';
						error_log("Therapists Endpoint Debug: Calling time slots for therapist ID: " . $path[1] . ", date: " . $date);
						$this->get_ai_therapist_time_slots( $path[1], $date );
					} else {
						error_log("Therapists Endpoint Debug: Calling get_ai_therapist for therapist ID: " . $path[1]);
						$this->get_ai_therapist( $path[1] );
					}
				} elseif ( $path[1] === 'by-diagnosis' && is_numeric( $path[2] ) ) {
					error_log("Therapists Endpoint Debug: Calling therapists by diagnosis: " . $path[2]);
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
				if ( isset( $path[1] ) && $path[1] === 'available' ) {
					$this->get_ai_available_appointments();
				} elseif ( isset( $path[1] ) && $path[1] === 'user' && isset( $path[2] ) && is_numeric( $path[2] ) ) {
					$this->get_ai_user_appointments( $path[2] );
				} elseif ( count( $path ) === 1 ) {
					// GET /api/ai/appointments - get current user's appointments
					$user_id = $this->verify_jwt_token();
					$this->get_ai_user_appointments( $user_id );
				} else {
					$this->send_error( 'Invalid appointments endpoint', 404 );
				}
				break;
			case 'POST':
				if ( isset( $path[1] ) && $path[1] === 'book' ) {
					$this->book_ai_appointment();
				} else {
					$this->send_error( 'Invalid appointments endpoint', 404 );
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
				if ( isset( $path[1] ) && is_numeric( $path[1] ) ) {
					$this->get_ai_cart( $path[1] );
				} else {
					$this->send_error( 'Invalid cart endpoint', 404 );
				}
				break;
			case 'POST':
				if ( isset( $path[1] ) && $path[1] === 'add' ) {
					$this->add_to_ai_cart();
				} elseif ( isset( $path[1] ) && $path[1] === 'checkout' ) {
					$this->checkout_ai_cart();
				} else {
					$this->send_error( 'Invalid cart endpoint', 404 );
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
				} elseif ( isset( $path[1] ) && is_numeric( $path[1] ) ) {
					$this->get_ai_diagnosis( $path[1] );
				} else {
					$this->send_error( 'Invalid diagnoses endpoint', 404 );
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
		
		// Get certificates
		$certificates = !empty($application->certificates) ? json_decode($application->certificates, true) : [];
		
		// Debug logging
		error_log("AI Integration Debug: Raw certificates from application: " . print_r($application->certificates, true));
		error_log("AI Integration Debug: After JSON decode: " . print_r($certificates, true));
		
		$certificates_data = [];
		
		foreach ($certificates as $cert_id) {
			$attachment = get_post($cert_id);
			if (!$attachment) continue;
			
			$file_url = wp_get_attachment_url($cert_id);
			$file_type = get_post_mime_type($cert_id);
			
			// Get file extension
			$file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
			
			// Determine if it's an image or document
			$is_image = wp_attachment_is_image($cert_id);
			
			// Get file size
			$file_size = filesize(get_attached_file($cert_id));
			$file_size_formatted = size_format($file_size, 2);
			
			// Get upload date
			$upload_date = get_the_date('Y-m-d', $cert_id);
			
			$certificates_data[] = [
				'id' => $cert_id,
				'name' => $attachment->post_title ?: basename($file_url),
				'description' => $attachment->post_content ?: '',
				'url' => $file_url,
				'thumbnail_url' => $is_image ? wp_get_attachment_image_url($cert_id, 'thumbnail') : '',
				'is_image' => $is_image,
				'file_type' => $file_type,
				'file_extension' => strtoupper($file_extension),
				'file_size' => $file_size_formatted,
				'upload_date' => $upload_date,
				'alt_text' => get_post_meta($cert_id, '_wp_attachment_image_alt', true) ?: ''
			];
		}
		
		// Sort certificates by upload date (newest first)
		usort($certificates_data, function($a, $b) {
			return strtotime($b['upload_date']) - strtotime($a['upload_date']);
		});
		
		error_log("AI Integration Debug: Final certificates data: " . print_r($certificates_data, true));
		
		// Get the actual earliest slot from timetable
		$earliest_slot_data = $this->get_earliest_slot_from_timetable($application->user_id);
		
		// Get all available dates from timetable
		$available_dates = $this->get_available_dates_from_timetable($application->user_id);
		
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
			'earliest_slot_data' => $earliest_slot_data,
			'available_dates' => $available_dates,
			'rating' => floatval( $application->rating ),
			'total_ratings' => intval( $application->total_ratings ),
			'price' => $this->get_therapist_ai_price( $application->user_id ),
			'diagnoses' => $diagnoses,
			'certificates' => $certificates_data,
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
		
		global $wpdb;
		
		// Get AI appointments for the specific user from the timetable
		$query = $wpdb->prepare(
			"SELECT t.*, u.display_name as therapist_name 
			FROM {$wpdb->prefix}snks_provider_timetable t
			LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
			WHERE t.client_id = %d 
			AND t.order_id IS NOT NULL 
			AND t.order_id != 0
			ORDER BY t.date_time DESC",
			$user_id
		);
		
		$appointments = $wpdb->get_results($query);
		$ai_appointments = array();
		
		foreach ( $appointments as $appointment ) {
			$order = wc_get_order( $appointment->order_id );
			if ( $order ) {
				$is_ai_order = $order->get_meta( 'from_jalsah_ai' );
				
				if ( $is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1 ) {
					// Map database status to frontend status
					$status_mapping = array(
						'open' => 'confirmed',
						'waiting' => 'pending',
						'completed' => 'completed',
						'cancelled' => 'cancelled'
					);
					$frontend_status = isset($status_mapping[$appointment->session_status]) ? $status_mapping[$appointment->session_status] : $appointment->session_status;
					
					$ai_appointments[] = array(
						'id' => $appointment->ID,
						'date' => $appointment->date_time,
						'time' => $appointment->starts,
						'status' => $frontend_status,
						'session_type' => $appointment->period ?: 60,
						'therapist' => array(
							'name' => $appointment->therapist_name ?: 'Unknown Therapist',
							'photo' => get_user_meta( $appointment->user_id, 'profile_image', true )
						),
						'notes' => '', // No notes column in the database
						'session_link' => null // No session_link column in the database
					);
				}
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
		error_log("=== OLD ADD TO AI CART DEBUG ===");
		$user_id = $this->verify_jwt_token();
		error_log("User ID from JWT: " . $user_id);
		
		$data = json_decode( file_get_contents( 'php://input' ), true );
		error_log("Request data: " . print_r($data, true));
		
		// Support both old format (slot_id) and new format (therapist_id, date, time)
		if ( isset( $data['slot_id'] ) ) {
			// Old format
			$slot = snks_get_timetable_by( 'ID', intval( $data['slot_id'] ) );
			if ( ! $slot || $slot->session_status !== 'waiting' ) {
				$this->send_error( 'Slot not available', 400 );
			}
			
			$slot_id = $slot->ID;
			$therapist_id = $slot->user_id;
			$date_time = $slot->date_time;
		} else {
			// New format from TherapistCard
			if ( ! isset( $data['therapist_id'] ) || ! isset( $data['date'] ) || ! isset( $data['time'] ) ) {
				$this->send_error( 'Therapist ID, date, and time required', 400 );
			}
			
			global $wpdb;
			
			// Find the slot by therapist_id, date, and time
			$slot = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
				 WHERE user_id = %d AND DATE(date_time) = %s AND starts = %s 
				 AND session_status = 'waiting' AND settings LIKE '%ai_booking%'",
				$data['therapist_id'],
				$data['date'],
				$data['time']
			));
			
			if ( ! $slot ) {
				$this->send_error( 'Slot not available', 400 );
			}
			
			$slot_id = $slot->ID;
			$therapist_id = $slot->user_id;
			$date_time = $slot->date_time;
		}
		
		$cart = get_user_meta( $user_id, 'ai_cart', true );
		if ( ! is_array( $cart ) ) {
			$cart = array();
		}
		
		error_log("Current cart: " . print_r($cart, true));
		error_log("Slot ID to add: " . $slot_id);
		
		// Check if slot is already in cart
		foreach ( $cart as $item ) {
			if ( $item['slot_id'] == $slot_id ) {
				error_log("ERROR: Slot already in cart (old system)");
				$this->send_error( 'Slot already in cart', 400 );
			}
		}
		
		$cart[] = array(
			'slot_id' => $slot_id,
			'therapist_id' => $therapist_id,
			'date_time' => $date_time,
			'price' => $this->get_therapist_ai_price( $therapist_id ),
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
	 * Process chat diagnosis using OpenAI
	 */
	private function process_chat_diagnosis( $message, $conversation_history ) {
		error_log( 'Process chat diagnosis started' );
		
		// Get OpenAI settings
		$api_key = get_option( 'snks_ai_chatgpt_api_key' );
		$model = get_option( 'snks_ai_chatgpt_model', 'gpt-3.5-turbo' );
		$system_prompt = get_option( 'snks_ai_chatgpt_prompt' );
		$max_tokens = get_option( 'snks_ai_chatgpt_max_tokens', 500 );
		$temperature = get_option( 'snks_ai_chatgpt_temperature', 0.7 );
		
		error_log( 'API Key: ' . ( $api_key ? 'Set' : 'Not set' ) );
		error_log( 'Model: ' . $model );
		error_log( 'Max tokens: ' . $max_tokens );
		error_log( 'Temperature: ' . $temperature );
		
		if ( ! $api_key ) {
			error_log( 'No API key configured' );
			return new WP_Error( 'no_api_key', 'OpenAI API key not configured' );
		}
		
		// Get available diagnoses
		global $wpdb;
		$diagnoses = $wpdb->get_results( "SELECT id, name, name_en, description FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
		$diagnosis_list = array();
		foreach ( $diagnoses as $diagnosis ) {
			$diagnosis_list[] = $diagnosis->name . ' (ID: ' . $diagnosis->id . ')';
		}
		
		// Build conversation messages
		$messages = array();
		
		// Add system prompt
		$enhanced_system_prompt = $system_prompt . "\n\nAvailable diagnoses: " . implode( ', ', $diagnosis_list ) . "\n\nIMPORTANT: After analyzing the conversation, if you can confidently suggest a diagnosis, respond with 'DIAGNOSIS_COMPLETE:' followed by the diagnosis name and a brief explanation. Otherwise, continue the conversation to gather more information.";
		$messages[] = array(
			'role' => 'system',
			'content' => $enhanced_system_prompt
		);
		
		// Add conversation history (limit to last 10 messages to avoid token limits)
		$recent_history = array_slice( $conversation_history, -10 );
		foreach ( $recent_history as $msg ) {
			if ( isset( $msg['role'] ) && isset( $msg['content'] ) ) {
				$messages[] = array(
					'role' => $msg['role'],
					'content' => $msg['content']
				);
			}
		}
		
		// Add current message
		$messages[] = array(
			'role' => 'user',
			'content' => $message
		);
		
		// Call OpenAI API
		$data = array(
			'model' => $model,
			'messages' => $messages,
			'max_tokens' => $max_tokens,
			'temperature' => $temperature
		);
		
		error_log( 'OpenAI API request data: ' . json_encode( $data ) );
		
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $data ),
			'timeout' => 30
		) );
		
		if ( is_wp_error( $response ) ) {
			error_log( 'OpenAI API error: ' . $response->get_error_message() );
			return new WP_Error( 'api_error', 'OpenAI API error: ' . $response->get_error_message() );
		}
		
		$body = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );
		
		error_log( 'OpenAI API response: ' . $body );
		
		if ( ! isset( $result['choices'][0]['message']['content'] ) ) {
			error_log( 'Invalid OpenAI response structure' );
			return new WP_Error( 'invalid_response', 'Invalid response from OpenAI API' );
		}
		
		$ai_response = $result['choices'][0]['message']['content'];
		
		// Check if diagnosis is complete
		if ( strpos( $ai_response, 'DIAGNOSIS_COMPLETE:' ) === 0 ) {
			// Extract diagnosis information
			$diagnosis_text = substr( $ai_response, 19 ); // Remove 'DIAGNOSIS_COMPLETE:'
			$parts = explode( ':', $diagnosis_text, 2 );
			$diagnosis_name = trim( $parts[0] );
			$explanation = isset( $parts[1] ) ? trim( $parts[1] ) : '';
			
			// Find matching diagnosis
			$diagnosis_id = null;
			foreach ( $diagnoses as $diagnosis ) {
				if ( stripos( $diagnosis->name, $diagnosis_name ) !== false || 
					 stripos( $diagnosis->name_en, $diagnosis_name ) !== false ) {
					$diagnosis_id = $diagnosis->id;
					$diagnosis_name = $diagnosis->name;
					$explanation = $diagnosis->description;
					break;
				}
			}
			
			if ( $diagnosis_id ) {
				return array(
					'message' => "Based on our conversation, I believe you may be experiencing **{$diagnosis_name}**. {$explanation}\n\nI've completed the diagnosis and can now help you find therapists who specialize in this area.",
					'diagnosis' => array(
						'completed' => true,
						'id' => $diagnosis_id,
						'title' => $diagnosis_name,
						'description' => $explanation
					)
				);
			} else {
				// Fallback: use the first diagnosis
				$diagnosis = $diagnoses[0];
				return array(
					'message' => "Based on our conversation, I believe you may be experiencing **{$diagnosis->name}**. {$diagnosis->description}\n\nI've completed the diagnosis and can now help you find therapists who specialize in this area.",
					'diagnosis' => array(
						'completed' => true,
						'id' => $diagnosis->id,
						'title' => $diagnosis->name,
						'description' => $diagnosis->description
					)
				);
			}
		} else {
			// Continue conversation
			return array(
				'message' => $ai_response,
				'diagnosis' => array(
					'completed' => false
				)
			);
		}
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
		// Debug logging
		error_log( 'JWT Debug - Request URI: ' . $_SERVER['REQUEST_URI'] );
		error_log( 'JWT Debug - Request Method: ' . $_SERVER['REQUEST_METHOD'] );
		error_log( 'JWT Debug - All Headers: ' . print_r( getallheaders(), true ) );
		
		$headers = getallheaders();
		$auth_header = isset( $headers['Authorization'] ) ? $headers['Authorization'] : '';
		
		// Also check for HTTP_AUTHORIZATION (some servers use this)
		if ( empty( $auth_header ) && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = $_SERVER['HTTP_AUTHORIZATION'];
		}
		
		error_log( 'JWT Debug - Auth Header: ' . $auth_header );
		
		if ( ! preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			error_log( 'JWT Debug - No token found in header' );
			$this->send_error( 'No token provided', 401 );
		}
		
		$token = $matches[1];
		error_log( 'JWT Debug - Token: ' . substr( $token, 0, 20 ) . '...' );
		
		try {
			$decoded = JWT::decode( $token, new Key( $this->jwt_secret, $this->jwt_algorithm ) );
			error_log( 'JWT Debug - Token decoded successfully, user_id: ' . $decoded->user_id );
			return $decoded->user_id;
		} catch ( Exception $e ) {
			error_log( 'JWT Debug - Token decode error: ' . $e->getMessage() );
			
			// If it's a signature verification failure, suggest re-login
			if ( strpos( $e->getMessage(), 'Signature verification failed' ) !== false ) {
				$this->send_error( 'Token expired. Please login again.', 401 );
			} else {
				$this->send_error( 'Invalid token: ' . $e->getMessage(), 401 );
			}
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
			'ratings_enabled' => get_option( 'snks_ai_ratings_enabled', '1' ) === '1',
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
			'ratings_enabled' => get_option( 'snks_ai_ratings_enabled', '1' ) === '1',
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

	/**
	 * Get therapist availability for a specific date
	 */
	public function get_ai_therapist_availability($request) {
		$therapist_id = $request->get_param('therapist_id');
		$date = $request->get_param('date');
		
		if (!$therapist_id || !$date) {
			return new WP_REST_Response(['error' => 'Missing therapist_id or date'], 400);
		}
		
		global $wpdb;
		
		// Query the existing timetable system for available slots
		$available_slots = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d 
			 AND DATE(date_time) = %s 
			 AND session_status = 'waiting' 
			 AND order_id = 0
			 AND attendance_type = 'online'
			 ORDER BY starts ASC",
			$therapist_id, $date
		));
		
		$formatted_slots = [];
		foreach ($available_slots as $slot) {
			$formatted_slots[] = [
				'time' => $slot->starts,
				'formatted_time' => date('g:i A', strtotime($slot->starts)),
				'slot_id' => $slot->ID,
				'available' => true
			];
		}
		
		return new WP_REST_Response([
			'available_slots' => $formatted_slots,
			'therapist_id' => $therapist_id,
			'date' => $date
		], 200);
	}

	/**
	 * Add appointment to cart using existing timetable system
	 */
	public function add_appointment_to_cart($request) {
		$user_id = $request->get_param('user_id');
		$slot_id = $request->get_param('slot_id');
		

		
		if (!$user_id || !$slot_id) {
			return new WP_REST_Response(['error' => 'Missing user_id or slot_id'], 400);
		}
		
		global $wpdb;
		
		// Check if slot is still available
		$slot = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND session_status = 'waiting' AND order_id = 0",
			$slot_id
		));
		
		if (!$slot) {
			return new WP_REST_Response(['error' => 'Time slot is no longer available'], 400);
		}
		
		// Check if slot is already in user's cart
		$cart_check_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND client_id = %d AND session_status = 'waiting' AND settings LIKE '%ai_booking:in_cart%'",
			$slot_id, $user_id
		);
		
		$in_cart = $wpdb->get_var($cart_check_query);
		
		if ($in_cart) {
			return new WP_REST_Response(['error' => 'Appointment already in cart'], 400);
		}
		
		// Add to cart by updating the slot with AI identifier
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'client_id' => $user_id,
				'session_status' => 'waiting', // Keep as waiting until checkout
				'settings' => 'ai_booking:in_cart' // Mark as AI booking
			],
			['ID' => $slot_id],
			['%d', '%s', '%s'],
			['%d']
		);
		
		if ($result === false) {
			return new WP_REST_Response(['error' => 'Failed to add to cart'], 500);
		}
		
		return new WP_REST_Response([
			'success' => true,
			'message' => 'Appointment added to cart',
			'slot_id' => $slot_id
		], 200);
	}

	/**
	 * Get user's cart using existing timetable system
	 */
	public function get_user_cart($request) {
		$user_id = $request->get_param('user_id');
		

		
		if (!$user_id) {
			return new WP_REST_Response(['error' => 'Missing user_id'], 400);
		}
		
		global $wpdb;
		
		$cart_query = $wpdb->prepare(
			"SELECT t.*, ta.name as therapist_name, ta.name_en as therapist_name_en, ta.profile_image
			 FROM {$wpdb->prefix}snks_provider_timetable t
			 LEFT JOIN {$wpdb->prefix}therapist_applications ta ON t.user_id = ta.user_id
			 WHERE t.client_id = %d AND t.session_status = 'waiting' AND t.order_id = 0 
			 AND t.settings LIKE '%ai_booking%'
			 ORDER BY t.date_time ASC",
			$user_id
		);
		
		$cart_items = $wpdb->get_results($cart_query);
		
		$total_price = 0;
		foreach ($cart_items as $item) {
			$total_price += 200.00; // Default price
			// Add therapist image URL
			if ($item->profile_image) {
				$item->therapist_image_url = wp_get_attachment_image_url($item->profile_image, 'thumbnail');
			}
		}
		
		return new WP_REST_Response([
			'success' => true,
			'data' => $cart_items,
			'total_price' => $total_price,
			'item_count' => count($cart_items)
		], 200);
	}

	/**
	 * Remove item from cart using existing timetable system
	 */
	public function remove_from_cart($request) {
		$slot_id = $request->get_param('slot_id');
		$user_id = $request->get_param('user_id');
		
		if (!$slot_id || !$user_id) {
			return new WP_REST_Response(['error' => 'Missing slot_id or user_id'], 400);
		}
		
		global $wpdb;
		
		// Remove from cart by resetting the slot
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'client_id' => 0,
				'session_status' => 'waiting',
				'settings' => '' // Clear the AI booking marker
			],
			['ID' => $slot_id, 'client_id' => $user_id],
			['%d', '%s', '%s'],
			['%d', '%d']
		);
		
		if ($result === false) {
			return new WP_REST_Response(['error' => 'Failed to remove from cart'], 500);
		}
		
		return new WP_REST_Response([
			'success' => true,
			'message' => 'Item removed from cart'
		], 200);
	}

	/**
	 * Book appointments from cart using existing timetable system
	 */
	public function book_appointments_from_cart($request) {
		$user_id = $request->get_param('user_id');
		
		if (!$user_id) {
			return new WP_REST_Response(['error' => 'Missing user_id'], 400);
		}
		
		global $wpdb;
		
		// Get cart items
		$cart_items = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE client_id = %d AND session_status = 'waiting' AND order_id = 0",
			$user_id
		));
		
		if (empty($cart_items)) {
			return new WP_REST_Response(['error' => 'Cart is empty'], 400);
		}
		
		$wpdb->query('START TRANSACTION');
		
		try {
			$booked_appointments = [];
			
			foreach ($cart_items as $item) {
				// Check if slot is still available
				$is_booked = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}snks_provider_timetable 
					 WHERE ID = %d AND session_status != 'waiting'",
					$item->ID
				));
				
				if ($is_booked) {
					throw new Exception("Time slot {$item->date_time} is no longer available");
				}
				
				// Book the appointment
				$appointment_result = $wpdb->update(
					$wpdb->prefix . 'snks_provider_timetable',
					[
						'session_status' => 'open',
						'order_id' => 1, // Demo order ID
						'settings' => 'ai_booking:confirmed' // Mark as confirmed AI booking
					],
					['ID' => $item->ID],
					['%s', '%d', '%s'],
					['%d']
				);
				
				if ($appointment_result === false) {
					throw new Exception('Failed to book appointment');
				}
				
				$booked_appointments[] = $item->ID;
			}
			
			$wpdb->query('COMMIT');
			
			return new WP_REST_Response([
				'success' => true,
				'message' => 'Appointments booked successfully',
				'appointment_ids' => $booked_appointments
			], 200);
			
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			return new WP_REST_Response(['error' => $e->getMessage()], 500);
		}
	}

	/**
	 * Get user's appointments using existing timetable system
	 */
	public function get_user_appointments($request) {
		$user_id = $request->get_param('user_id');
		

		
		if (!$user_id) {
			return new WP_REST_Response(['error' => 'Missing user_id'], 400);
		}
		
		global $wpdb;
		
		$query = $wpdb->prepare(
			"SELECT t.*, ta.name as therapist_name, ta.name_en as therapist_name_en, ta.profile_image
			 FROM {$wpdb->prefix}snks_provider_timetable t
			 LEFT JOIN {$wpdb->prefix}therapist_applications ta ON t.user_id = ta.user_id
			 WHERE t.client_id = %d AND t.session_status = 'open' 
			 AND t.settings LIKE '%ai_booking%'
			 ORDER BY t.date_time ASC",
			$user_id
		);
		
		$appointments = $wpdb->get_results($query);
		
		foreach ($appointments as $appointment) {
			if ($appointment->profile_image) {
				$appointment->therapist_image_url = wp_get_attachment_image_url($appointment->profile_image, 'thumbnail');
			}
		}
		
		return new WP_REST_Response(['appointments' => $appointments], 200);
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

	/**
	 * Get earliest slot from timetable for a therapist
	 */
	private function get_earliest_slot_from_timetable($therapist_id) {
		global $wpdb;
		
		// Get the earliest slot regardless of settings - prioritize by date/time
		$earliest_slot = $wpdb->get_row($wpdb->prepare(
			"SELECT ID, date_time, starts, ends, period, clinic, attendance_type, session_status, settings
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d AND session_status = 'waiting' 
			 AND date_time >= NOW()
			 ORDER BY date_time ASC 
			 LIMIT 1",
			$therapist_id
		));
		
		if ($earliest_slot) {
			$date = new DateTime($earliest_slot->date_time);
			return [
				'id' => $earliest_slot->ID,
				'date' => $date->format('Y-m-d'),
				'time' => $earliest_slot->starts,
				'end_time' => $earliest_slot->ends,
				'period' => $earliest_slot->period,
				'clinic' => $earliest_slot->clinic,
				'attendance_type' => $earliest_slot->attendance_type
			];
		}
		
		return null;
	}

	/**
	 * Get available dates from timetable for a therapist
	 */
	private function get_available_dates_from_timetable($therapist_id) {
		global $wpdb;
		
		// Get all available slots from the timetable
		$available_slots = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, date_time, starts, ends, period, clinic, attendance_type
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d AND session_status = 'waiting' 
			 AND date_time >= NOW()
			 AND (settings LIKE '%ai_booking%' OR settings = '')
			 ORDER BY date_time ASC",
			$therapist_id
		));
		
		$available_dates = [];
		foreach ($available_slots as $slot) {
			$date = new DateTime($slot->date_time);
			$available_dates[] = [
				'date' => $date->format('Y-m-d'),
				'day' => $date->format('D'), // Short day name
				'slot_id' => $slot->ID,
				'time' => $slot->starts,
				'end_time' => $slot->ends,
				'period' => $slot->period,
				'clinic' => $slot->clinic,
				'attendance_type' => $slot->attendance_type
			];
		}
		
		return $available_dates;
	}

	/**
	 * Get therapist's earliest available slot
	 */
	private function get_ai_therapist_earliest_slot($therapist_id) {
		global $wpdb;
		
		// Debug logging
		error_log("Earliest Slot Debug: Requested therapist ID: " . $therapist_id);
		
		// First, let's check what session statuses exist for this therapist
		$statuses = $wpdb->get_results($wpdb->prepare(
			"SELECT DISTINCT session_status FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d",
			$therapist_id
		));
		error_log("Earliest Slot Debug: Available session statuses: " . print_r($statuses, true));
		
		// Get the earliest slot regardless of settings - prioritize by date/time
		$earliest_slot = $wpdb->get_row($wpdb->prepare(
			"SELECT ID, date_time, starts, ends, period, clinic, attendance_type, session_status, settings
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d AND session_status = 'waiting' 
			 AND date_time >= NOW()
			 ORDER BY date_time ASC 
			 LIMIT 1",
			$therapist_id
		));
		
		error_log("Earliest Slot Debug: Query result for earliest slot: " . print_r($earliest_slot, true));
		
		// If no 'waiting' slots, try 'open' status
		if (!$earliest_slot) {
			$earliest_slot = $wpdb->get_row($wpdb->prepare(
				"SELECT ID, date_time, starts, ends, period, clinic, attendance_type, session_status, settings
				 FROM {$wpdb->prefix}snks_provider_timetable 
				 WHERE user_id = %d AND session_status = 'open' 
				 AND date_time >= NOW()
				 AND (settings LIKE '%ai_booking%' OR settings = '')
				 ORDER BY date_time ASC 
				 LIMIT 1",
				$therapist_id
			));
		}
		
		if ($earliest_slot) {
			$date = new DateTime($earliest_slot->date_time);
			$this->send_success([
				'id' => $earliest_slot->ID,
				'date' => $date->format('Y-m-d'),
				'time' => $earliest_slot->starts,
				'end_time' => $earliest_slot->ends,
				'period' => $earliest_slot->period,
				'clinic' => $earliest_slot->clinic,
				'attendance_type' => $earliest_slot->attendance_type
			]);
		} else {
			$this->send_success(null);
		}
	}

	/**
	 * Get therapist's available dates
	 */
	private function get_ai_therapist_available_dates($therapist_id) {
		global $wpdb;
		
		$available_dates = $wpdb->get_results($wpdb->prepare(
			"SELECT DISTINCT DATE(date_time) as date
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d AND session_status = 'waiting' 
			 AND date_time >= CURDATE()
			 AND (settings LIKE '%ai_booking%' OR settings = '')
			 ORDER BY date ASC",
			$therapist_id
		));
		
		$dates = [];
		foreach ($available_dates as $date) {
			$dates[] = [
				'date' => $date->date,
				'day' => date('D', strtotime($date->date)),
				'formatted' => date('M j', strtotime($date->date))
			];
		}
		
		$this->send_success($dates);
	}

	/**
	 * Get therapist's time slots for a specific date
	 */
	private function get_ai_therapist_time_slots($therapist_id, $date) {
		global $wpdb;
		
		if (empty($date)) {
			$this->send_error('Date parameter is required', 400);
			return;
		}
		
		$time_slots = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, date_time, starts, ends, period, clinic, attendance_type
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d AND session_status = 'waiting' 
			 AND DATE(date_time) = %s
			 AND (settings LIKE '%ai_booking%' OR settings = '')
			 ORDER BY starts ASC",
			$therapist_id,
			$date
		));
		
		$slots = [];
		foreach ($time_slots as $slot) {
			$slots[] = [
				'id' => $slot->ID,
				'value' => $slot->starts,
				'time' => $slot->starts,
				'end_time' => $slot->ends,
				'period' => $slot->period,
				'clinic' => $slot->clinic,
				'attendance_type' => $slot->attendance_type,
				'date_time' => $slot->date_time
			];
		}
		
		$this->send_success($slots);
	}

	/**
	 * Create WooCommerce order from existing cart
	 */
	public function create_woocommerce_order_from_cart($request) {
		$user_id = $request->get_param('user_id');
		$cart_items = $request->get_param('cart_items');
		

		
		if (!$user_id || !$cart_items) {
			return new WP_REST_Response(['error' => 'Missing user_id or cart_items'], 400);
		}
		
		try {
			// Create WooCommerce order from existing cart
			$order = SNKS_AI_Orders::create_order_from_existing_cart($user_id, $cart_items);
			
			// Generate auto-login URL for main website
			$auto_login_url = self::generate_auto_login_url($user_id, $order->get_id());
			
			return new WP_REST_Response([
				'success' => true,
				'order_id' => $order->get_id(),
				'checkout_url' => $order->get_checkout_payment_url(),
				'auto_login_url' => $auto_login_url,
				'total' => $order->get_total(),
				'appointments_count' => count($cart_items)
			]);
			
		} catch (Exception $e) {
			return new WP_REST_Response(['error' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Generate auto-login URL for main website
	 */
	private function generate_auto_login_url($user_id, $order_id) {
		$user = get_userdata($user_id);
		if (!$user) {
			return '';
		}
		
		// Create a secure token for auto-login
		$token = wp_create_nonce('ai_auto_login_' . $user_id . '_' . $order_id);
		$expires = time() + (15 * 60); // 15 minutes expiry
		
		// Store the auto-login token
		update_user_meta($user_id, 'ai_auto_login_token', $token);
		update_user_meta($user_id, 'ai_auto_login_expires', $expires);
		update_user_meta($user_id, 'ai_auto_login_order_id', $order_id);
		
		// Generate the auto-login URL for main website
		$main_site_url = home_url('/wp-admin/admin-ajax.php');
		$auto_login_url = add_query_arg([
			'action' => 'ai_auto_login',
			'user_id' => $user_id,
			'token' => $token,
			'order_id' => $order_id,
			'redirect' => urlencode(wc_get_order($order_id)->get_checkout_payment_url())
		], $main_site_url);
		
		return $auto_login_url;
	}
}

// Initialize AI Integration
$ai_integration = new SNKS_AI_Integration();

// WooCommerce Hooks Integration
add_action('woocommerce_payment_complete', 'snks_process_ai_order_payment');
add_action('woocommerce_order_status_changed', 'snks_process_ai_order_status_change', 10, 3);



/**
 * Process AI orders on payment completion
 */
function snks_process_ai_order_payment($order_id) {
	$order = wc_get_order($order_id);
	
	if ($order) {
		$is_ai_order = $order->get_meta('from_jalsah_ai');
		
		if ($is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1) {
			SNKS_AI_Orders::process_ai_order_payment($order_id);
		}
	}
}

/**
 * Process AI orders on status change
 */
function snks_process_ai_order_status_change($order_id, $old_status, $new_status) {
	if (in_array($new_status, ['completed', 'processing'])) {
		$order = wc_get_order($order_id);
		
		if ($order) {
			$is_ai_order = $order->get_meta('from_jalsah_ai');
			
			if ($is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1) {
				SNKS_AI_Orders::process_ai_order_payment($order_id);
			}
		}
	}
}

/**
 * Customize WooCommerce checkout for AI orders
 */
add_filter('woocommerce_checkout_fields', 'snks_customize_ai_checkout_fields');
add_action('woocommerce_checkout_order_processed', 'snks_handle_ai_checkout_order', 10, 3);

/**
 * Redirect AI orders to appointments page after payment completion
 */
add_action('woocommerce_thankyou', 'snks_ai_order_thankyou_redirect', 5, 1);
add_action('template_redirect', 'snks_ai_order_template_redirect', 5);

function snks_ai_order_thankyou_redirect($order_id) {
	$order = wc_get_order($order_id);
	
	if ($order) {
		$is_ai_order = $order->get_meta('from_jalsah_ai');
		if ($is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1) {
			// Redirect AI orders to the frontend appointments page
			$frontend_url = get_option('snks_ai_frontend_url', 'https://jalsah-ai.com');
			wp_safe_redirect($frontend_url . '/appointments');
			exit;
		}
	}
}

function snks_ai_order_template_redirect() {
	// Check if we're on the order received page
	if (is_wc_endpoint_url('order-received')) {
		// Try multiple ways to get the order ID
		$order_id = get_query_var('order-received');
		
		// If not found in query var, try to extract from URL
		if (!$order_id && isset($_SERVER['REQUEST_URI'])) {
			if (preg_match('/order-received\/(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
				$order_id = $matches[1];
			}
		}
		
		if ($order_id) {
			$order = wc_get_order($order_id);
			
			if ($order) {
				$is_ai_order = $order->get_meta('from_jalsah_ai');
				
				if ($is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1) {
					// Redirect AI orders to the frontend appointments page
					$frontend_url = get_option('snks_ai_frontend_url', 'https://jalsah-ai.com');
					wp_safe_redirect($frontend_url . '/appointments');
					exit;
				}
			}
		}
	}
}

/**
 * Add admin notice for missing frontend URL
 */
add_action('admin_notices', 'snks_ai_frontend_url_notice');

function snks_ai_frontend_url_notice() {
	// Only show on AI admin pages
	if (!isset($_GET['page']) || strpos($_GET['page'], 'jalsah-ai') === false) {
		return;
	}
	
	$frontend_url = get_option('snks_ai_frontend_url');
	if (empty($frontend_url)) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong>Jalsah AI Configuration Required:</strong> 
				Please set the Frontend URL in <a href="<?php echo admin_url('admin.php?page=jalsah-ai-settings'); ?>">Jalsah AI Settings</a> 
				to enable proper payment redirects for AI orders.
			</p>
		</div>
		<?php
	}
}

function snks_customize_ai_checkout_fields($fields) {
	// Check if current order is from Jalsah AI
	$order_id = get_query_var('order-pay');
	if ($order_id) {
		$order = wc_get_order($order_id);
		if ($order && $order->get_meta('from_jalsah_ai') === 'true') {
			// Customize fields for AI orders
			$fields['billing']['billing_email']['required'] = true;
			$fields['billing']['billing_phone']['required'] = true;
			
			// Add AI-specific fields if needed
			$fields['billing']['ai_user_id'] = [
				'type' => 'hidden',
				'default' => get_current_user_id()
			];
		}
	}
	
	return $fields;
}

function snks_handle_ai_checkout_order($order_id, $posted_data, $order) {
	// Check if order contains AI sessions
	$has_ai_sessions = false;
	foreach ($order->get_items() as $item) {
		if ($item->get_meta('is_ai_session') === 'true') {
			$has_ai_sessions = true;
			break;
		}
	}
	
	if ($has_ai_sessions) {
		// Mark order as AI order
		$order->update_meta_data('from_jalsah_ai', true);
		$order->update_meta_data('ai_user_id', get_current_user_id());
		$order->save();
	}
}

/**
 * Filter to ensure AI session prices are used correctly
 */
add_filter('woocommerce_order_item_get_total', 'snks_ai_order_item_total', 10, 2);
add_filter('woocommerce_order_item_get_subtotal', 'snks_ai_order_item_subtotal', 10, 2);

/**
 * Filter to modify form data transient key for AI orders
 */
add_filter('snks_form_data_transient_key', 'snks_ai_form_data_transient_key', 10, 1);

function snks_ai_form_data_transient_key($key) {
	// Check if we're on a WooCommerce order page
	if (isset($_GET['order-pay']) && !empty($_GET['order-pay'])) {
		$order_id = intval($_GET['order-pay']);
		$order = wc_get_order($order_id);
		
		if ($order && $order->get_meta('from_jalsah_ai') === 'true') {
			// Return AI-specific transient key
			return 'snks_ai_form_data_' . $order_id;
		}
	}
	
	return $key;
}

function snks_ai_order_item_total($total, $item) {
	if ($item->get_meta('is_ai_session') === 'true') {
		$line_total = $item->get_meta('_line_total');
		if ($line_total) {
			return floatval($line_total);
		}
	}
	return $total;
}

function snks_ai_order_item_subtotal($subtotal, $item) {
	if ($item->get_meta('is_ai_session') === 'true') {
		$line_subtotal = $item->get_meta('_line_subtotal');
		if ($line_subtotal) {
			return floatval($line_subtotal);
		}
	}
	return $subtotal;
}

/**
 * AJAX handler for AI auto-login
 */
add_action('wp_ajax_ai_auto_login', 'snks_ai_auto_login_handler');
add_action('wp_ajax_nopriv_ai_auto_login', 'snks_ai_auto_login_handler');

function snks_ai_auto_login_handler() {
	$user_id = intval($_GET['user_id']);
	$token = sanitize_text_field($_GET['token']);
	$order_id = intval($_GET['order_id']);
	$redirect_url = urldecode($_GET['redirect']);
	
	// Verify token
	$stored_token = get_user_meta($user_id, 'ai_auto_login_token', true);
	$stored_expires = get_user_meta($user_id, 'ai_auto_login_expires', true);
	$stored_order_id = get_user_meta($user_id, 'ai_auto_login_order_id', true);
	
	// Check if token is valid and not expired
	if (!$stored_token || $token !== $stored_token || time() > $stored_expires || $order_id != $stored_order_id) {
		wp_die('Invalid or expired auto-login token');
	}
	
	// Force logout any existing session
	wp_logout();
	
	// Auto-login the user
	$user = get_userdata($user_id);
	if ($user) {
		wp_set_current_user($user_id, $user->user_login);
		wp_set_auth_cookie($user_id, true);
		
		// Clear the auto-login token
		delete_user_meta($user_id, 'ai_auto_login_token');
		delete_user_meta($user_id, 'ai_auto_login_expires');
		delete_user_meta($user_id, 'ai_auto_login_order_id');
		
		// Redirect to checkout
		wp_redirect($redirect_url);
		exit;
	} else {
		wp_die('User not found');
	}
} 