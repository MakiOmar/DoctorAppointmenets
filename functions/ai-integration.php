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
		$this->jwt_secret = defined( 'JWT_SECRET' ) ? JWT_SECRET : wp_salt( 'auth' );
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_ai_endpoints' ) );
		add_action( 'wp_ajax_nopriv_ai_auth', array( $this, 'handle_ai_auth' ) );
		add_action( 'wp_ajax_ai_auth', array( $this, 'handle_ai_auth' ) );
		add_action( 'wp_ajax_nopriv_ai_therapists', array( $this, 'handle_ai_therapists' ) );
		add_action( 'wp_ajax_ai_therapists', array( $this, 'handle_ai_therapists' ) );
		add_action( 'wp_ajax_nopriv_ai_appointments', array( $this, 'handle_ai_appointments' ) );
		add_action( 'wp_ajax_ai_appointments', array( $this, 'handle_ai_appointments' ) );
		add_action( 'wp_ajax_nopriv_ai_cart', array( $this, 'handle_ai_cart' ) );
		add_action( 'wp_ajax_ai_cart', array( $this, 'handle_ai_cart' ) );
		add_action( 'wp_ajax_nopriv_ai_diagnoses', array( $this, 'handle_ai_diagnoses' ) );
		add_action( 'wp_ajax_ai_diagnoses', array( $this, 'handle_ai_diagnoses' ) );
	}
	
	/**
	 * Register AI endpoints
	 */
	public function register_ai_endpoints() {
		add_rewrite_rule( '^api/ai/(.*?)/?$', 'index.php?ai_endpoint=$matches[1]', 'top' );
		add_filter( 'query_vars', array( $this, 'add_ai_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_ai_requests' ) );
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
		if ( ! $endpoint ) {
			return;
		}
		
		// Set JSON header
		header( 'Content-Type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
		
		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			http_response_code( 200 );
			exit;
		}
		
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
		
		switch ( $path[0] ) {
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
				$this->get_ai_diagnoses();
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
		
		// Check if user is a patient
		if ( ! in_array( 'customer', $user->roles, true ) ) {
			$this->send_error( 'Access denied', 403 );
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
		$therapists = get_users( array(
			'role' => 'doctor',
			'meta_query' => array(
				array(
					'key' => 'show_on_ai_site',
					'value' => '1',
					'compare' => '='
				)
			)
		) );
		
		$result = array();
		foreach ( $therapists as $therapist ) {
			$result[] = $this->format_ai_therapist( $therapist );
		}
		
		$this->send_success( $result );
	}
	
	/**
	 * Get AI Therapist
	 */
	private function get_ai_therapist( $therapist_id ) {
		$therapist = get_user_by( 'ID', $therapist_id );
		if ( ! $therapist || ! in_array( 'doctor', $therapist->roles, true ) ) {
			$this->send_error( 'Therapist not found', 404 );
		}
		
		$this->send_success( $this->format_ai_therapist( $therapist ) );
	}
	
	/**
	 * Get AI Therapists by Diagnosis
	 */
	private function get_ai_therapists_by_diagnosis( $diagnosis_id ) {
		global $wpdb;
		
		$therapist_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT therapist_id FROM {$wpdb->prefix}snks_therapist_diagnoses WHERE diagnosis_id = %d",
			$diagnosis_id
		) );
		
		if ( empty( $therapist_ids ) ) {
			$this->send_success( array() );
		}
		
		$therapists = get_users( array(
			'include' => $therapist_ids,
			'meta_query' => array(
				array(
					'key' => 'show_on_ai_site',
					'value' => '1',
					'compare' => '='
				)
			)
		) );
		
		$result = array();
		foreach ( $therapists as $therapist ) {
			$result[] = $this->format_ai_therapist( $therapist );
		}
		
		$this->send_success( $result );
	}
	
	/**
	 * Format AI Therapist
	 */
	private function format_ai_therapist( $therapist ) {
		$diagnoses = $this->get_therapist_diagnoses( $therapist->ID );
		
		return array(
			'id' => $therapist->ID,
			'name' => get_user_meta( $therapist->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $therapist->ID, 'billing_last_name', true ),
			'photo' => get_user_meta( $therapist->ID, 'profile-image', true ),
			'bio' => get_user_meta( $therapist->ID, 'ai_bio', true ),
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
		
		$diagnoses = $wpdb->get_results( $wpdb->prepare(
			"SELECT d.*, td.rating, td.suitability_message 
			FROM {$wpdb->prefix}snks_diagnoses d
			JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON d.id = td.diagnosis_id
			WHERE td.therapist_id = %d",
			$therapist_id
		) );
		
		return $diagnoses;
	}
	
	/**
	 * Get Therapist AI Price
	 */
	private function get_therapist_ai_price( $therapist_id ) {
		// Get 45-minute online price
		$pricing = snks_doctor_online_pricings( $therapist_id );
		return isset( $pricing['45_minutes'] ) ? $pricing['45_minutes'] : array();
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
		
		$diagnoses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
		
		$this->send_success( $diagnoses );
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
	}
	
	/**
	 * Send error response
	 */
	private function send_error( $message, $code = 400 ) {
		http_response_code( $code );
		echo json_encode( array( 'success' => false, 'error' => $message ) );
	}
}

// Initialize AI Integration
new SNKS_AI_Integration(); 