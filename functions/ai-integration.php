<?php
/**
 * AI Integration for Jalsah AI Platform.
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
		
		// Handle CORS for admin-ajax requests
		add_action( 'wp_ajax_nopriv_get_ai_nonce', array( $this, 'handle_admin_ajax_cors' ), 1 );
		add_action( 'wp_ajax_nopriv_get_ai_settings', array( $this, 'handle_admin_ajax_cors' ), 1 );
		add_action( 'wp_ajax_nopriv_register_therapist_shortcode', array( $this, 'handle_admin_ajax_cors' ), 1 );
		add_action( 'wp_ajax_nopriv_chat_diagnosis_ajax', array( $this, 'handle_admin_ajax_cors' ), 1 );
		
		
		// Add nonce generation endpoint
		add_action( 'wp_ajax_get_ai_nonce', array( $this, 'get_ai_nonce' ) );
		add_action( 'wp_ajax_nopriv_get_ai_nonce', array( $this, 'get_ai_nonce' ) );
		
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
	add_action( 'wp_ajax_test_diagnosis_limit', array( $this, 'test_diagnosis_limit_ajax' ) );
	add_action( 'wp_ajax_nopriv_test_diagnosis_limit', array( $this, 'test_diagnosis_limit_ajax' ) );
		

		

	}
	
	/**
	 * Register AI endpoints
	 */
	public function register_ai_endpoints() {
		// Add rewrite rules for API endpoints - more specific patterns first
		add_rewrite_rule( '^api/ai/therapists/search/?$', 'index.php?ai_endpoint=therapists/search', 'top' );
		add_rewrite_rule( '^api/ai/therapists/by-diagnosis/(\d+)/?$', 'index.php?ai_endpoint=therapists/by-diagnosis/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/(\d+)/([^/]+)/?$', 'index.php?ai_endpoint=therapists/$matches[1]/$matches[2]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/(\d+)/?$', 'index.php?ai_endpoint=therapists/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/therapists/?$', 'index.php?ai_endpoint=therapists', 'top' );
		add_rewrite_rule( '^api/ai/diagnoses/(\d+)/?$', 'index.php?ai_endpoint=diagnoses/$matches[1]', 'top' );
		add_rewrite_rule( '^api/ai/diagnoses/?$', 'index.php?ai_endpoint=diagnoses', 'top' );
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
		register_rest_route( 'jalsah-ai/v1', '/ai-settings', array(
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
		
		register_rest_route( 'jalsah-ai/v1', '/add-appointment-to-cart-with-confirmation', array(
			'methods' => 'POST',
			'callback' => array( $this, 'add_appointment_to_cart_with_confirmation' ),
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

		// Nonce generation endpoint
		
		// Prescription requests endpoint
		register_rest_route( 'jalsah-ai/v1', '/prescription-requests', array(
			'methods' => 'GET',
			'callback' => 'snks_get_prescription_requests_rest',
			'permission_callback' => '__return_true',
		) );

		// Completed prescriptions endpoint
		register_rest_route( 'jalsah-ai/v1', '/completed-prescriptions', array(
			'methods' => 'GET',
			'callback' => 'snks_get_completed_prescriptions_rest',
			'permission_callback' => '__return_true',
		) );
		
		// User country detection endpoint
		register_rest_route( 'jalsah-ai/v1', '/user-country', array(
			'methods' => 'GET',
			'callback' => 'snks_get_user_country_rest',
			'permission_callback' => '__return_true',
		) );
		
		
		// Rochtah available slots endpoint
		register_rest_route( 'jalsah-ai/v1', '/rochtah-available-slots', array(
			'methods' => 'GET',
			'callback' => 'snks_get_rochtah_available_slots_rest',
			'permission_callback' => '__return_true',
		) );
		
		// Rochtah book appointment endpoint
		register_rest_route( 'jalsah-ai/v1', '/rochtah-book-appointment', array(
			'methods' => 'POST',
			'callback' => 'snks_book_rochtah_appointment_rest',
			'permission_callback' => '__return_true',
		) );
		
		// Rochtah meeting details endpoint
		register_rest_route( 'jalsah-ai/v1', '/rochtah-meeting-details', array(
			'methods' => 'GET',
			'callback' => 'snks_get_rochtah_meeting_details_rest',
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'jalsah-ai/v1', '/nonce', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_ai_nonce_rest' ),
			'permission_callback' => '__return_true',
		) );
		
		// Session management endpoints
		register_rest_route( 'jalsah-ai/v1', '/session/(?P<id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_ai_session' ),
			'permission_callback' => '__return_true',
		) );
		
		register_rest_route( 'jalsah-ai/v1', '/session/(?P<id>\d+)/end', array(
			'methods' => 'POST',
			'callback' => array( $this, 'end_ai_session' ),
			'permission_callback' => '__return_true',
		) );
		
		register_rest_route( 'jalsah-ai/v1', '/session/(?P<id>\d+)/therapist-join', array(
			'methods' => 'POST',
			'callback' => array( $this, 'set_therapist_joined' ),
			'permission_callback' => '__return_true',
		) );
	}
	
	/**
	 * Get allowed frontend origins from settings
	 */
	private function get_allowed_frontend_origins() {
		$frontend_urls = get_option( 'snks_ai_frontend_urls', 'https://jalsah-ai.com' );
		
		// Parse URLs from textarea (one per line)
		$urls = array_filter( array_map( 'trim', explode( "\n", $frontend_urls ) ) );
		
		// Validate and clean URLs
		$valid_origins = array();
		foreach ( $urls as $url ) {
			$url = trim( $url );
			if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
				// Remove trailing slash and ensure proper format
				$url = rtrim( $url, '/' );
				$valid_origins[] = $url;
			}
		}
		
		// If no valid URLs found, use default
		if ( empty( $valid_origins ) ) {
			$valid_origins = array( 'https://jalsah-ai.com' );
		}
		
		return $valid_origins;
	}
	
	/**
	 * Check if request origin is allowed
	 */
	private function is_origin_allowed( $origin ) {
		$allowed_origins = $this->get_allowed_frontend_origins();
		return in_array( $origin, $allowed_origins, true );
	}
	
	/**
	 * Get the first valid frontend URL for redirects
	 */
	private function get_primary_frontend_url() {
		$allowed_origins = $this->get_allowed_frontend_origins();
		return $allowed_origins[0] ?? 'https://jalsah-ai.com';
	}

	/**
	 * Handle CORS headers
	 */
	public function handle_cors() {
		// Handle CORS for API requests and admin-ajax requests
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false || 
			 strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) !== false ) {
			
			// Get the request origin
			$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
			
			// Set CORS headers based on origin validation
			if ( ! empty( $origin ) && $this->is_origin_allowed( $origin ) ) {
				header( 'Access-Control-Allow-Origin: ' . $origin );
			} else {
				// Fallback to wildcard for development or if no origin
				header( 'Access-Control-Allow-Origin: *' );
			}
			
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
		// Check if this is an AI API request or admin-ajax request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false || 
			 strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) !== false ) {
			
			// Get the request origin
			$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
			
			// Set CORS headers based on origin validation
			if ( ! empty( $origin ) && $this->is_origin_allowed( $origin ) ) {
				header( 'Access-Control-Allow-Origin: ' . $origin );
			} else {
				// Fallback to wildcard for development or if no origin
				header( 'Access-Control-Allow-Origin: *' );
			}
			
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
		// Check if this is an AI API request or admin-ajax request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) !== false || 
			 strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) !== false ) {
			
			// Get the request origin
			$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
			
			// Set CORS headers based on origin validation
			if ( ! empty( $origin ) && $this->is_origin_allowed( $origin ) ) {
				header( 'Access-Control-Allow-Origin: ' . $origin );
			} else {
				// Fallback to wildcard for development or if no origin
				header( 'Access-Control-Allow-Origin: *' );
			}
			
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
	 * Handle CORS for admin-ajax requests
	 */
	public function handle_admin_ajax_cors() {
		// Get the request origin
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
		
		// Set CORS headers based on origin validation
		if ( ! empty( $origin ) && $this->is_origin_allowed( $origin ) ) {
			header( 'Access-Control-Allow-Origin: ' . $origin );
		} else {
			// Fallback to wildcard for development or if no origin
			header( 'Access-Control-Allow-Origin: *' );
		}
		
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
		header( 'Access-Control-Allow-Credentials: true' );
		
		// Handle preflight OPTIONS request
		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			http_response_code( 200 );
			exit;
		}
	}

	/**
	 * Get AI nonce for frontend (AJAX)
	 */
	public function get_ai_nonce() {
		$action = sanitize_text_field( $_GET['action'] ?? 'ai_api_nonce' );
		
		// Generate nonce for the requested action
		$nonce = wp_create_nonce( $action );
		
		wp_send_json_success( array(
			'nonce' => $nonce,
			'action' => $action
		) );
	}
	
	/**
	 * Get AI nonce for frontend (REST API)
	 */
	public function get_ai_nonce_rest( $request ) {
		$action = sanitize_text_field( $request->get_param( 'action' ) ?? 'ai_api_nonce' );
		
		// Generate nonce for the requested action
		$nonce = wp_create_nonce( $action );
		
		return array(
			'success' => true,
			'data' => array(
				'nonce' => $nonce,
				'action' => $action
			)
		);
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
	 * Detect language from text
	 */
	private function detect_language( $text ) {
		// Simple Arabic character detection
		$arabic_pattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
		
		if ( preg_match( $arabic_pattern, $text ) ) {
			return 'arabic';
		}
		
		return 'english';
	}
	
	/**
	 * Check if a message is a question
	 */
	private function is_question( $content ) {
		// Remove JSON formatting and get the actual message content
		$clean_content = $content;
		
		// Check for question marks
		if ( strpos( $clean_content, '?' ) !== false ) {
			return true;
		}
		
		// Check for Arabic question words
		$arabic_question_words = array( 'هل', 'متى', 'أين', 'كيف', 'لماذا', 'من', 'ما', 'أي' );
		foreach ( $arabic_question_words as $word ) {
			if ( strpos( $clean_content, $word ) !== false ) {
				return true;
			}
		}
		
		// Check for English question words
		$english_question_words = array( 'what', 'when', 'where', 'how', 'why', 'who', 'which', 'do', 'does', 'did', 'can', 'could', 'would', 'will' );
		foreach ( $english_question_words as $word ) {
			if ( stripos( $clean_content, $word ) !== false ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Generate contextual fallback response based on conversation history
	 */
	private function generate_contextual_fallback( $current_message, $conversation_history, $is_arabic ) {
		// Analyze the current message and conversation context
		$message_lower = strtolower( $current_message );
		
		// Check if we have asked enough questions (minimum requirement)
		$ai_questions_count = 0;
		foreach ( $conversation_history as $msg ) {
			if ( $msg['role'] === 'assistant' && $this->is_question( $msg['content'] ) ) {
				$ai_questions_count++;
			}
		}
		
		$min_questions = get_option( 'snks_ai_chatgpt_min_questions', 5 );
		
		// Only consider diagnosis if we've asked enough questions
		if ( $ai_questions_count >= $min_questions ) {
			$has_sufficient_info = $this->has_sufficient_diagnostic_info( $conversation_history, $current_message );
			
			if ( $has_sufficient_info ) {
				// If we have enough information and questions, provide an actual diagnosis
				$diagnosis_result = $this->generate_fallback_diagnosis( $conversation_history, $current_message, $is_arabic );
				return $diagnosis_result;
			}
		}
		
		// Check for specific keywords in the current message and conversation history
		$asked_questions = array();
		$ai_questions_count = 0;
		foreach ( $conversation_history as $msg ) {
			if ( $msg['role'] === 'assistant' ) {
				$asked_questions[] = strtolower( $msg['content'] );
				if ( $this->is_question( $msg['content'] ) ) {
					$ai_questions_count++;
				}
			}
		}
		
		if ( $is_arabic ) {
			// For early questions (first or second), ask about country/region
			if ( $ai_questions_count <= 1 ) {
				$country_questions = array(
					"أهلاً! أنا هنا لمساعدتك في التقييم النفسي المبدئي. هل يمكنك إخباري من أي بلد أنت؟ هذا سيساعدني في فهم سياقك الثقافي والتحدث معك باللغة المناسبة.",
					"مرحباً! قبل أن نبدأ التقييم النفسي، هل يمكنك إخباري من أي منطقة أو بلد أنت؟ هذا سيساعدني في تقديم إرشادات أكثر ملاءمة لثقافتك.",
					"أهلاً وسهلاً! أنا هنا لمساعدتك في العثور على المعالج المناسب. هل يمكنك إخباري من أي بلد أنت لنتمكن من التواصل بشكل أفضل؟"
				);
				
				foreach ( $country_questions as $question ) {
					if ( ! $this->question_already_asked( $question, $asked_questions ) ) {
						return $question;
					}
				}
			}
			
			// Check if this is a response to country question (second question)
			if ( $ai_questions_count === 1 ) {
				// Detect country and dialect
				$dialect = $this->detect_country_and_dialect( $message );
				
				// If user provided a country response, move to symptoms with appropriate dialect
				$country_keywords = array('مصر', 'السعودية', 'الإمارات', 'الكويت', 'قطر', 'البحرين', 'عمان', 'الأردن', 'لبنان', 'سوريا', 'العراق', 'فلسطين', 'اليمن', 'السودان', 'المغرب', 'الجزائر', 'تونس', 'ليبيا', 'موريتانيا', 'الصومال', 'جيبوتي', 'جزر القمر', 'موريشيوس', 'سيشل', 'جزر المالديف', 'تركيا', 'إيران', 'أفغانستان', 'باكستان', 'الهند', 'بنغلاديش', 'سريلانكا', 'نيبال', 'بوتان', 'ماليزيا', 'إندونيسيا', 'سنغافورة', 'تايلاند', 'فيتنام', 'كمبوديا', 'لاوس', 'ميانمار', 'الفلبين', 'بروناي', 'تيمور الشرقية', 'الصين', 'اليابان', 'كوريا', 'منغوليا', 'كازاخستان', 'أوزبكستان', 'تركمانستان', 'طاجيكستان', 'قيرغيزستان', 'أذربيجان', 'جورجيا', 'أرمينيا', 'روسيا', 'أوكرانيا', 'بيلاروسيا', 'مولدوفا', 'رومانيا', 'بلغاريا', 'صربيا', 'كرواتيا', 'سلوفينيا', 'البوسنة', 'الجبل الأسود', 'ألبانيا', 'مقدونيا', 'اليونان', 'تركيا', 'قبرص', 'مالطا', 'إيطاليا', 'إسبانيا', 'البرتغال', 'فرنسا', 'ألمانيا', 'النمسا', 'سويسرا', 'بلجيكا', 'هولندا', 'لوكسمبورغ', 'الدنمارك', 'السويد', 'النرويج', 'فنلندا', 'آيسلندا', 'إستونيا', 'لاتفيا', 'ليتوانيا', 'بولندا', 'التشيك', 'سلوفاكيا', 'المجر', 'أمريكا', 'كندا', 'المكسيك', 'البرازيل', 'الأرجنتين', 'تشيلي', 'بيرو', 'كولومبيا', 'فنزويلا', 'الإكوادور', 'بوليفيا', 'باراغواي', 'أوروغواي', 'غيانا', 'سورينام', 'غيانا الفرنسية', 'أستراليا', 'نيوزيلندا', 'فيجي', 'بابوا غينيا الجديدة', 'جزر سليمان', 'فانواتو', 'كاليدونيا الجديدة', 'بولينيزيا الفرنسية', 'ساموا', 'تونغا', 'كيريباتي', 'توفالو', 'ناورو', 'بالاو', 'ولايات ميكرونيزيا الموحدة', 'جزر مارشال', 'جنوب أفريقيا', 'نيجيريا', 'كينيا', 'إثيوبيا', 'أوغندا', 'تنزانيا', 'زامبيا', 'زيمبابوي', 'بوتسوانا', 'ناميبيا', 'أنغولا', 'موزمبيق', 'مدغشقر', 'موريشيوس', 'سيشل', 'جزر القمر', 'جيبوتي', 'إريتريا', 'الصومال', 'السودان', 'جنوب السودان', 'جمهورية أفريقيا الوسطى', 'تشاد', 'الكاميرون', 'الغابون', 'جمهورية الكونغو', 'جمهورية الكونغو الديمقراطية', 'رواندا', 'بوروندي', 'مالاوي', 'ليسوتو', 'إسواتيني', 'موريشيوس', 'سيشل', 'جزر القمر', 'جيبوتي', 'إريتريا', 'الصومال', 'السودان', 'جنوب السودان', 'جمهورية أفريقيا الوسطى', 'تشاد', 'الكاميرون', 'الغابون', 'جمهورية الكونغو', 'جمهورية الكونغو الديمقراطية', 'رواندا', 'بوروندي', 'مالاوي', 'ليسوتو', 'إسواتيني');
				
				$is_country_response = false;
				foreach ( $country_keywords as $country ) {
					if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
						$is_country_response = true;
						break;
					}
				}
				
				if ( $is_country_response ) {
					return $this->get_dialect_symptoms_question( $dialect );
				}
				
				// Check for general country response patterns
				$country_patterns = array('من', 'أنا من', 'أقيم في', 'أسكن في', 'موطني', 'بلدي', 'وطني');
				foreach ( $country_patterns as $pattern ) {
					if ( strpos( $message_lower, $pattern ) !== false ) {
						return $this->get_dialect_symptoms_question( $dialect );
					}
				}
				
				// Check if user wants to skip country question or move to symptoms directly
				$skip_patterns = array('لا يهم', 'لا أريد', 'تخطي', 'التالي', 'أريد التحدث عن مشاكلي', 'دعنا نبدأ', 'أريد مساعدة', 'مشاكل', 'أعراض', 'أشعر', 'أعاني');
				foreach ( $skip_patterns as $pattern ) {
					if ( strpos( $message_lower, $pattern ) !== false ) {
						return $this->get_dialect_symptoms_question( $dialect );
					}
				}
			}
			
			// Detect dialect from conversation history
			$dialect = 'egyptian'; // default
			foreach ( $conversation_history as $msg ) {
				if ( $msg['role'] === 'user' ) {
					$detected_dialect = $this->detect_country_and_dialect( $msg['content'] );
					if ( $detected_dialect !== 'egyptian' ) {
						$dialect = $detected_dialect;
						break;
					}
				}
			}
			
			// Arabic keyword detection with repetition avoidance
			if ( strpos( $message_lower, 'أرق' ) !== false || strpos( $message_lower, 'نوم' ) !== false || strpos( $message_lower, 'سهر' ) !== false ) {
				$sleep_question = $this->get_dialect_sleep_question( $dialect );
				if ( ! $this->question_already_asked( $sleep_question, $asked_questions ) ) {
					return $sleep_question;
				}
			}
			
			if ( strpos( $message_lower, 'حزن' ) !== false || strpos( $message_lower, 'اكتئاب' ) !== false || strpos( $message_lower, 'حزين' ) !== false ) {
				$sadness_question = $this->get_dialect_sadness_question( $dialect );
				if ( ! $this->question_already_asked( $sadness_question, $asked_questions ) ) {
					return $sadness_question;
				}
			}
			
			if ( strpos( $message_lower, 'قلق' ) !== false || strpos( $message_lower, 'توتر' ) !== false || strpos( $message_lower, 'خوف' ) !== false ) {
				$anxiety_question = $this->get_dialect_anxiety_question( $dialect );
				if ( ! $this->question_already_asked( $anxiety_question, $asked_questions ) ) {
					return $anxiety_question;
				}
			}
			
			if ( strpos( $message_lower, 'عمل' ) !== false || strpos( $message_lower, 'وظيفة' ) !== false || strpos( $message_lower, 'مهنة' ) !== false ) {
				$work_question = $this->get_dialect_work_question( $dialect );
				if ( ! $this->question_already_asked( $work_question, $asked_questions ) ) {
					return $work_question;
				}
			}
			
			// If user said "no" or "لا", ask about something different
			if ( $message_lower === 'لا' || $message_lower === 'no' ) {
				$different_questions = $this->get_dialect_different_questions( $dialect );
				
				foreach ( $different_questions as $question ) {
					if ( ! $this->question_already_asked( $question, $asked_questions ) ) {
						return $question;
					}
				}
			}
			
			// Default contextual response - avoid repetition
			$default_questions = $this->get_dialect_default_questions( $dialect );
			
			foreach ( $default_questions as $question ) {
				if ( ! $this->question_already_asked( $question, $asked_questions ) ) {
					return $question;
				}
			}
			
			// If all questions have been asked, provide a generic response
			return $this->get_dialect_final_response( $dialect );
		} else {
			// For early questions (first or second), ask about country/region
			if ( $ai_questions_count <= 1 ) {
				$country_questions = array(
					"Hello! I'm here to help you with a preliminary psychological assessment. Could you tell me which country you're from? This will help me understand your cultural context and speak with you in the appropriate language.",
					"Hi there! Before we begin the psychological assessment, could you tell me which region or country you're from? This will help me provide guidance that's more suitable for your culture.",
					"Welcome! I'm here to help you find the right therapist. Could you tell me which country you're from so we can communicate better?"
				);
				
				foreach ( $country_questions as $question ) {
					if ( ! $this->question_already_asked( $question, $asked_questions ) ) {
						return $question;
					}
				}
			}
			
			// Check if this is a response to country question (second question)
			if ( $ai_questions_count === 1 ) {
				// If user provided a country response, move to symptoms
				$country_keywords = array('egypt', 'saudi arabia', 'united arab emirates', 'kuwait', 'qatar', 'bahrain', 'oman', 'jordan', 'lebanon', 'syria', 'iraq', 'palestine', 'yemen', 'sudan', 'morocco', 'algeria', 'tunisia', 'libya', 'mauritania', 'somalia', 'djibouti', 'comoros', 'mauritius', 'seychelles', 'maldives', 'turkey', 'iran', 'afghanistan', 'pakistan', 'india', 'bangladesh', 'sri lanka', 'nepal', 'bhutan', 'malaysia', 'indonesia', 'singapore', 'thailand', 'vietnam', 'cambodia', 'laos', 'myanmar', 'philippines', 'brunei', 'east timor', 'china', 'japan', 'korea', 'mongolia', 'kazakhstan', 'uzbekistan', 'turkmenistan', 'tajikistan', 'kyrgyzstan', 'azerbaijan', 'georgia', 'armenia', 'russia', 'ukraine', 'belarus', 'moldova', 'romania', 'bulgaria', 'serbia', 'croatia', 'slovenia', 'bosnia', 'montenegro', 'albania', 'macedonia', 'greece', 'cyprus', 'malta', 'italy', 'spain', 'portugal', 'france', 'germany', 'austria', 'switzerland', 'belgium', 'netherlands', 'luxembourg', 'denmark', 'sweden', 'norway', 'finland', 'iceland', 'estonia', 'latvia', 'lithuania', 'poland', 'czech republic', 'slovakia', 'hungary', 'usa', 'united states', 'america', 'canada', 'mexico', 'brazil', 'argentina', 'chile', 'peru', 'colombia', 'venezuela', 'ecuador', 'bolivia', 'paraguay', 'uruguay', 'guyana', 'suriname', 'french guiana', 'australia', 'new zealand', 'fiji', 'papua new guinea', 'solomon islands', 'vanuatu', 'new caledonia', 'french polynesia', 'samoa', 'tonga', 'kiribati', 'tuvalu', 'nauru', 'palau', 'micronesia', 'marshall islands', 'south africa', 'nigeria', 'kenya', 'ethiopia', 'uganda', 'tanzania', 'zambia', 'zimbabwe', 'botswana', 'namibia', 'angola', 'mozambique', 'madagascar', 'mauritius', 'seychelles', 'comoros', 'djibouti', 'eritrea', 'somalia', 'south sudan', 'central african republic', 'chad', 'cameroon', 'gabon', 'congo', 'democratic republic of congo', 'rwanda', 'burundi', 'malawi', 'lesotho', 'eswatini');
				
				$is_country_response = false;
				foreach ( $country_keywords as $country ) {
					if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
						$is_country_response = true;
						break;
					}
				}
				
				if ( $is_country_response ) {
					return "Thank you! Now let me help you understand what you're going through. Please tell me about your current situation, symptoms, or concerns. You can be as detailed as you'd like - I'm here to listen and help.";
				}
				
				// Check for general country response patterns
				$country_patterns = array('from', 'i\'m from', 'i am from', 'i live in', 'i reside in', 'my country', 'my homeland', 'my nationality');
				foreach ( $country_patterns as $pattern ) {
					if ( strpos( $message_lower, $pattern ) !== false ) {
						return "Thank you! Now let me help you understand what you're going through. Please tell me about your current situation, symptoms, or concerns. You can be as detailed as you'd like - I'm here to listen and help.";
					}
				}
				
				// Check if user wants to skip country question or move to symptoms directly
				$skip_patterns = array('doesn\'t matter', 'i don\'t want', 'skip', 'next', 'i want to talk about my problems', 'let\'s start', 'i need help', 'problems', 'symptoms', 'i feel', 'i suffer');
				foreach ( $skip_patterns as $pattern ) {
					if ( strpos( $message_lower, $pattern ) !== false ) {
						return "Alright, let me help you understand what you're going through. Please tell me about your current situation, symptoms, or concerns. You can be as detailed as you'd like - I'm here to listen and help.";
					}
				}
			}
			
			// English keyword detection
			if ( strpos( $message_lower, 'sleep' ) !== false || strpos( $message_lower, 'insomnia' ) !== false || strpos( $message_lower, 'awake' ) !== false ) {
				return "I understand you're having sleep issues. Can you tell me more about your sleep pattern? How many hours do you usually sleep? Do you wake up frequently during the night?";
			}
			
			if ( strpos( $message_lower, 'sad' ) !== false || strpos( $message_lower, 'depression' ) !== false || strpos( $message_lower, 'hopeless' ) !== false ) {
				return "I see you're feeling sad. Can you tell me when you started feeling this way? Is there a specific reason for these feelings?";
			}
			
			if ( strpos( $message_lower, 'anxiety' ) !== false || strpos( $message_lower, 'worry' ) !== false || strpos( $message_lower, 'fear' ) !== false ) {
				return "I understand you're feeling anxious. Can you tell me more about what's worrying you? Are there specific situations that increase this anxiety?";
			}
			
			if ( strpos( $message_lower, 'work' ) !== false || strpos( $message_lower, 'job' ) !== false || strpos( $message_lower, 'career' ) !== false ) {
				return "I see that work is affecting your mental health. Can you tell me more about your work environment and the pressures you're facing?";
			}
			
			// Default contextual response
			return "Thank you for sharing that with me. Can you tell me more about how these feelings are affecting your daily life? Are there any other symptoms you're experiencing?";
		}
	}
	
	/**
	 * Check if we have sufficient information for diagnosis
	 */
	private function has_sufficient_diagnostic_info( $conversation_history, $current_message ) {
		// Count user messages with substantial content
		$user_messages = 0;
		$has_symptoms = false;
		$has_duration = false;
		$has_impact = false;
		
		foreach ( $conversation_history as $msg ) {
			if ( $msg['role'] === 'user' ) {
				$content = strtolower( $msg['content'] );
				$user_messages++;
				
				// Check for symptom keywords
				if ( strpos( $content, 'أرق' ) !== false || strpos( $content, 'نوم' ) !== false || 
					 strpos( $content, 'حزن' ) !== false || strpos( $content, 'اكتئاب' ) !== false ||
					 strpos( $content, 'قلق' ) !== false || strpos( $content, 'توتر' ) !== false ||
					 strpos( $content, 'sleep' ) !== false || strpos( $content, 'insomnia' ) !== false ||
					 strpos( $content, 'sad' ) !== false || strpos( $content, 'depression' ) !== false ||
					 strpos( $content, 'anxiety' ) !== false || strpos( $content, 'worry' ) !== false ) {
					$has_symptoms = true;
				}
				
				// Check for duration/time information
				if ( strpos( $content, 'شهر' ) !== false || strpos( $content, 'أسبوع' ) !== false ||
					 strpos( $content, 'يوم' ) !== false || strpos( $content, 'month' ) !== false ||
					 strpos( $content, 'week' ) !== false || strpos( $content, 'day' ) !== false ) {
					$has_duration = true;
				}
				
				// Check for impact information
				if ( strpos( $content, 'عمل' ) !== false || strpos( $content, 'حياة' ) !== false ||
					 strpos( $content, 'يومي' ) !== false || strpos( $content, 'work' ) !== false ||
					 strpos( $content, 'life' ) !== false || strpos( $content, 'daily' ) !== false ) {
					$has_impact = true;
				}
			}
		}
		
		// Also check current message
		$current_lower = strtolower( $current_message );
		if ( strpos( $current_lower, 'أرق' ) !== false || strpos( $current_lower, 'نوم' ) !== false || 
			 strpos( $current_lower, 'حزن' ) !== false || strpos( $current_lower, 'اكتئاب' ) !== false ||
			 strpos( $current_lower, 'قلق' ) !== false || strpos( $current_lower, 'توتر' ) !== false ||
			 strpos( $current_lower, 'sleep' ) !== false || strpos( $current_lower, 'insomnia' ) !== false ||
			 strpos( $current_lower, 'sad' ) !== false || strpos( $current_lower, 'depression' ) !== false ||
			 strpos( $current_lower, 'anxiety' ) !== false || strpos( $current_lower, 'worry' ) !== false ) {
			$has_symptoms = true;
		}
		
		// Consider we have sufficient info if we have symptoms and at least 2 user messages
		return $has_symptoms && $user_messages >= 2;
	}
	
	/**
	 * Detect user's country from message and return appropriate dialect
	 */
	private function detect_country_and_dialect( $message ) {
		$message_lower = strtolower( $message );
		
		// Egyptian dialect
		$egyptian_countries = array('مصر', 'egypt', 'القاهرة', 'cairo', 'الإسكندرية', 'alexandria', 'الجيزة', 'giza');
		foreach ( $egyptian_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'egyptian';
			}
		}
		
		// Saudi dialect
		$saudi_countries = array('السعودية', 'saudi arabia', 'الرياض', 'riyadh', 'جدة', 'jeddah', 'الدمام', 'dammam');
		foreach ( $saudi_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'saudi';
			}
		}
		
		// UAE dialect
		$uae_countries = array('الإمارات', 'united arab emirates', 'دبي', 'dubai', 'أبو ظبي', 'abu dhabi', 'الشارقة', 'sharjah');
		foreach ( $uae_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'uae';
			}
		}
		
		// Kuwait dialect
		$kuwait_countries = array('الكويت', 'kuwait', 'مدينة الكويت', 'kuwait city');
		foreach ( $kuwait_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'kuwait';
			}
		}
		
		// Qatar dialect
		$qatar_countries = array('قطر', 'qatar', 'الدوحة', 'doha');
		foreach ( $qatar_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'qatar';
			}
		}
		
		// Bahrain dialect
		$bahrain_countries = array('البحرين', 'bahrain', 'المنامة', 'manama');
		foreach ( $bahrain_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'bahrain';
			}
		}
		
		// Oman dialect
		$oman_countries = array('عمان', 'oman', 'مسقط', 'muscat');
		foreach ( $oman_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'oman';
			}
		}
		
		// Jordan dialect
		$jordan_countries = array('الأردن', 'jordan', 'عمان', 'amman');
		foreach ( $jordan_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'jordan';
			}
		}
		
		// Lebanon dialect
		$lebanon_countries = array('لبنان', 'lebanon', 'بيروت', 'beirut');
		foreach ( $lebanon_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'lebanon';
			}
		}
		
		// Syria dialect
		$syria_countries = array('سوريا', 'syria', 'دمشق', 'damascus', 'حلب', 'aleppo');
		foreach ( $syria_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'syria';
			}
		}
		
		// Iraq dialect
		$iraq_countries = array('العراق', 'iraq', 'بغداد', 'baghdad');
		foreach ( $iraq_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'iraq';
			}
		}
		
		// Palestine dialect
		$palestine_countries = array('فلسطين', 'palestine', 'القدس', 'jerusalem', 'رام الله', 'ramallah');
		foreach ( $palestine_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'palestine';
			}
		}
		
		// Yemen dialect
		$yemen_countries = array('اليمن', 'yemen', 'صنعاء', 'sanaa');
		foreach ( $yemen_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'yemen';
			}
		}
		
		// Sudan dialect
		$sudan_countries = array('السودان', 'sudan', 'الخرطوم', 'khartoum');
		foreach ( $sudan_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'sudan';
			}
		}
		
		// Morocco dialect
		$morocco_countries = array('المغرب', 'morocco', 'الرباط', 'rabat', 'الدار البيضاء', 'casablanca');
		foreach ( $morocco_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'morocco';
			}
		}
		
		// Algeria dialect
		$algeria_countries = array('الجزائر', 'algeria', 'الجزائر العاصمة', 'algiers');
		foreach ( $algeria_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'algeria';
			}
		}
		
		// Tunisia dialect
		$tunisia_countries = array('تونس', 'tunisia', 'تونس العاصمة', 'tunis');
		foreach ( $tunisia_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'tunisia';
			}
		}
		
		// Libya dialect
		$libya_countries = array('ليبيا', 'libya', 'طرابلس', 'tripoli');
		foreach ( $libya_countries as $country ) {
			if ( strpos( $message_lower, strtolower( $country ) ) !== false ) {
				return 'libya';
			}
		}
		
		// Default to Egyptian if no specific country detected
		return 'egyptian';
	}
	
	/**
	 * Get dialect-specific response for sleep questions
	 */
	private function get_dialect_sleep_question( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "أفهم إنك بتعاني من مشاكل في النوم. ممكن تحكيلي أكتر عن نمط نومك؟ كام ساعة بتنام عادة؟ وهل بتيقظ كتير في الليل؟";
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return "أفهم إنك تعاني من مشاكل في النوم. ممكن تقولي أكتر عن نمط نومك؟ كم ساعة تنام عادة؟ وهل تيقظ كتير في الليل؟";
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return "أفهم إنك تعاني من مشاكل في النوم. ممكن تقولي أكتر عن نمط نومك؟ كم ساعة تنام عادة؟ وهل تيقظ كتير في الليل؟";
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return "أفهم إنك تعاني من مشاكل في النوم. ممكن تقولي ليا أكتر عن نمط نومك؟ كم ساعة تنام عادة؟ وهل تيقظ كتير في الليل؟";
			
			case 'sudan':
			case 'libya':
				return "أفهم إنك بتعاني من مشاكل في النوم. ممكن تحكيلي أكتر عن نمط نومك؟ كام ساعة بتنام عادة؟ وهل بتيقظ كتير في الليل؟";
			
			default:
				return "أفهم أنك تعاني من مشاكل في النوم. هل يمكنك إخباري أكثر عن نمط نومك؟ كم ساعة تنام عادة؟ وهل تستيقظ كثيراً أثناء الليل؟";
		}
	}
	
	/**
	 * Get dialect-specific response for sadness questions
	 */
	private function get_dialect_sadness_question( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "أرى إنك بتحس بالحزن. ممكن تحكيلي متى بدأت تحس بالحزن ده؟ وهل في سبب محدد للمشاعر دي؟";
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return "أرى إنك تحس بالحزن. ممكن تقولي متى بدأت تحس بالحزن هذا؟ وهل في سبب محدد للمشاعر هذه؟";
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return "أرى إنك تحس بالحزن. ممكن تقولي متى بدأت تحس بالحزن هذا؟ وهل في سبب محدد للمشاعر هذه؟";
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return "أرى إنك تحس بالحزن. ممكن تقولي ليا متى بدأت تحس بالحزن هذا؟ وهل في سبب محدد للمشاعر هذه؟";
			
			case 'sudan':
			case 'libya':
				return "أرى إنك بتحس بالحزن. ممكن تحكيلي متى بدأت تحس بالحزن ده؟ وهل في سبب محدد للمشاعر دي؟";
			
			default:
				return "أرى أنك تشعر بالحزن. هل يمكنك إخباري متى بدأت تشعر بهذا الحزن؟ وهل هناك سبب محدد لهذه المشاعر؟";
		}
	}
	
	/**
	 * Get dialect-specific response for anxiety questions
	 */
	private function get_dialect_anxiety_question( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "أفهم إنك بتحس بالقلق. ممكن تحكيلي أكتر عن إيه اللي بيقلقك؟ وهل في مواقف معينة بتبقى القلق فيها أكتر؟";
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return "أفهم إنك تحس بالقلق. ممكن تقولي أكتر عن شو اللي يقلقك؟ وهل في مواقف معينة يصير القلق فيها أكتر؟";
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return "أفهم إنك تحس بالقلق. ممكن تقولي أكتر عن شو اللي يقلقك؟ وهل في مواقف معينة يصير القلق فيها أكتر؟";
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return "أفهم إنك تحس بالقلق. ممكن تقولي ليا أكتر عن شنو لي يقلقك؟ وهل في مواقف معينة يصير القلق فيها أكتر؟";
			
			case 'sudan':
			case 'libya':
				return "أفهم إنك بتحس بالقلق. ممكن تحكيلي أكتر عن إيه اللي بيقلقك؟ وهل في مواقف معينة بتبقى القلق فيها أكتر؟";
			
			default:
				return "أفهم أنك تشعر بالقلق. هل يمكنك إخباري أكثر عن ما يقلقك؟ وهل هناك مواقف معينة تزيد من هذا القلق؟";
		}
	}
	
	/**
	 * Get dialect-specific response for work questions
	 */
	private function get_dialect_work_question( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "أرى إن العمل بيأثر على صحتك النفسية. ممكن تحكيلي أكتر عن طبيعة شغلك والضغوط اللي بتحس بيها؟";
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return "أرى إن العمل يؤثر على صحتك النفسية. ممكن تقولي أكتر عن طبيعة شغلك والضغوط اللي تحس فيها؟";
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return "أرى إن العمل يؤثر على صحتك النفسية. ممكن تقولي أكتر عن طبيعة شغلك والضغوط اللي تحس فيها؟";
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return "أرى إن العمل يؤثر على صحتك النفسية. ممكن تقولي ليا أكتر عن طبيعة شغلك والضغوط اللي تحس فيها؟";
			
			case 'sudan':
			case 'libya':
				return "أرى إن العمل بيأثر على صحتك النفسية. ممكن تحكيلي أكتر عن طبيعة شغلك والضغوط اللي بتحس بيها؟";
			
			default:
				return "أرى أن العمل يؤثر على صحتك النفسية. هل يمكنك إخباري أكثر عن طبيعة عملك والضغوط التي تواجهها؟";
		}
	}
	
	/**
	 * Get dialect-specific response for different questions when user says "no"
	 */
	private function get_dialect_different_questions( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return array(
					"ممكن تحكيلي عن علاقاتك مع العيلة والأصحاب؟ بتحس بالدعم منهم؟",
					"لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"بتلاقي صعوبة في التركيز أو اتخاذ القرارات؟",
					"بتحس بالتوتر أو القلق في مواقف معينة؟",
					"في أنشطة كنت بتحبها قبل كده ومش بتحبها دلوقتي؟"
				);
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return array(
					"ممكن تقولي عن علاقاتك مع العيلة والأصحاب؟ تحس بالدعم منهم؟",
					"لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"تلاقي صعوبة في التركيز أو اتخاذ القرارات؟",
					"تحس بالتوتر أو القلق في مواقف معينة؟",
					"في أنشطة كنت تحبها قبل هذا وما تحبها الحين؟"
				);
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return array(
					"ممكن تقولي عن علاقاتك مع العيلة والأصحاب؟ تحس بالدعم منهم؟",
					"لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"تلاقي صعوبة في التركيز أو اتخاذ القرارات؟",
					"تحس بالتوتر أو القلق في مواقف معينة؟",
					"في أنشطة كنت تحبها قبل هذا وما تحبها الحين؟"
				);
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return array(
					"ممكن تقولي ليا عن علاقاتك مع العيلة والأصحاب؟ تحس بالدعم منهم؟",
					"لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"تلاقي صعوبة في التركيز أو اتخاذ القرارات؟",
					"تحس بالتوتر أو القلق في مواقف معينة؟",
					"في أنشطة كنت تحبها قبل هذا وما تحبها دابا؟"
				);
			
			case 'sudan':
			case 'libya':
				return array(
					"ممكن تحكيلي عن علاقاتك مع العيلة والأصحاب؟ بتحس بالدعم منهم؟",
					"لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"بتلاقي صعوبة في التركيز أو اتخاذ القرارات؟",
					"بتحس بالتوتر أو القلق في مواقف معينة؟",
					"في أنشطة كنت بتحبها قبل كده ومش بتحبها دلوقتي؟"
				);
			
			default:
				return array(
					"هل يمكنك إخباري عن علاقاتك مع العائلة والأصدقاء؟ هل تشعر بالدعم منهم؟",
					"هل لاحظت تغييرات في شهيتك أو وزنك مؤخراً؟",
					"هل تجد صعوبة في التركيز أو اتخاذ القرارات؟",
					"هل تشعر بالتوتر أو القلق في مواقف معينة؟",
					"هل هناك أنشطة كنت تستمتع بها سابقاً ولم تعد تستمتع بها الآن؟"
				);
		}
	}
	
	/**
	 * Get dialect-specific response for default questions
	 */
	private function get_dialect_default_questions( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return array(
					"ممكن تحكيلي أكتر عن تأثير المشاعر دي على حياتك اليومية؟",
					"في أعراض تانية بتعاني منها؟",
					"لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"بتحس إن المشاعر دي بتأثر على علاقاتك مع الناس؟",
					"في مواقف معينة بتبقى المشاعر دي فيها أسوأ؟"
				);
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return array(
					"ممكن تقولي أكتر عن تأثير المشاعر هذه على حياتك اليومية؟",
					"في أعراض ثانية تعاني منها؟",
					"لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"تحس إن المشاعر هذه تؤثر على علاقاتك مع الناس؟",
					"في مواقف معينة تصير المشاعر هذه فيها أسوأ؟"
				);
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return array(
					"ممكن تقولي أكتر عن تأثير المشاعر هذه على حياتك اليومية؟",
					"في أعراض ثانية تعاني منها؟",
					"لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"تحس إن المشاعر هذه تؤثر على علاقاتك مع الناس؟",
					"في مواقف معينة تصير المشاعر هذه فيها أسوأ؟"
				);
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return array(
					"ممكن تقولي ليا أكتر عن تأثير المشاعر هذه على حياتك اليومية؟",
					"في أعراض ثانية تعاني منها؟",
					"لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"تحس إن المشاعر هذه تؤثر على علاقاتك مع الناس؟",
					"في مواقف معينة تصير المشاعر هذه فيها أسوأ؟"
				);
			
			case 'sudan':
			case 'libya':
				return array(
					"ممكن تحكيلي أكتر عن تأثير المشاعر دي على حياتك اليومية؟",
					"في أعراض تانية بتعاني منها؟",
					"لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"بتحس إن المشاعر دي بتأثر على علاقاتك مع الناس؟",
					"في مواقف معينة بتبقى المشاعر دي فيها أسوأ؟"
				);
			
			default:
				return array(
					"هل يمكنك إخباري أكثر عن تأثير هذه المشاعر على حياتك اليومية؟",
					"هل هناك أي أعراض أخرى تعاني منها؟",
					"هل لاحظت أي تغييرات في سلوكك أو عاداتك؟",
					"هل تشعر أن هذه المشاعر تؤثر على علاقاتك مع الآخرين؟",
					"هل هناك مواقف معينة تجعل هذه المشاعر أسوأ؟"
				);
		}
	}
	
	/**
	 * Get dialect-specific final response
	 */
	private function get_dialect_final_response( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "شكراً لك على مشاركة كده معايا. في حاجة تانية عايز تحكيها عن وضعك الحالي؟";
			
			case 'saudi':
			case 'uae':
			case 'kuwait':
			case 'qatar':
			case 'bahrain':
			case 'oman':
				return "شكراً لك على مشاركة هذا معي. في شيء ثاني تريد تقوله عن وضعك الحالي؟";
			
			case 'jordan':
			case 'lebanon':
			case 'syria':
			case 'iraq':
			case 'palestine':
			case 'yemen':
				return "شكراً لك على مشاركة هذا معي. في شيء ثاني تريد تقوله عن وضعك الحالي؟";
			
			case 'morocco':
			case 'algeria':
			case 'tunisia':
				return "شكراً لك على مشاركة هذا معي. في شيء ثاني تريد تقولي ليا عن وضعك الحالي؟";
			
			case 'sudan':
			case 'libya':
				return "شكراً لك على مشاركة كده معايا. في حاجة تانية عايز تحكيها عن وضعك الحالي؟";
			
			default:
				return "شكراً لك على مشاركة ذلك معي. هل هناك أي شيء آخر تود إخباري به عن وضعك الحالي؟";
		}
	}
	
	/**
	 * Get dialect-specific response for symptoms question
	 */
	private function get_dialect_symptoms_question( $dialect ) {
		switch ( $dialect ) {
			case 'egyptian':
				return "شكراً لك! دلوقتي خلينا نفهم إيه اللي مضايقك. ممكن تحكيلي إيه اللي بتحس بيه أو إيه المشاكل اللي بتواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'saudi':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'uae':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'kuwait':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'qatar':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'bahrain':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'oman':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'jordan':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'lebanon':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'syria':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'iraq':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'palestine':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'yemen':
				return "شكراً لك! الحين خلينا نفهم شو اللي مضايقك. ممكن تقولي شو اللي تحس فيه أو شو المشاكل اللي تواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'sudan':
				return "شكراً لك! دلوقتي خلينا نفهم إيه اللي مضايقك. ممكن تحكيلي إيه اللي بتحس بيه أو إيه المشاكل اللي بتواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			case 'morocco':
				return "شكراً لك! دابا خلينا نفهمو شنو لي مضايقك. ممكن تقولي ليا شنو لي كتحس فيه أو شنو المشاكل لي كتواجهها؟ تقدر تكون مفصل كيف ما تحب - أنا هنا نسمعك ونساعدك.";
			
			case 'algeria':
				return "شكراً لك! دابا خلينا نفهمو شنو لي مضايقك. ممكن تقولي ليا شنو لي كتحس فيه أو شنو المشاكل لي كتواجهها؟ تقدر تكون مفصل كيف ما تحب - أنا هنا نسمعك ونساعدك.";
			
			case 'tunisia':
				return "شكراً لك! دابا خلينا نفهمو شنو لي مضايقك. ممكن تقولي ليا شنو لي كتحس فيه أو شنو المشاكل لي كتواجهها؟ تقدر تكون مفصل كيف ما تحب - أنا هنا نسمعك ونساعدك.";
			
			case 'libya':
				return "شكراً لك! دلوقتي خلينا نفهم إيه اللي مضايقك. ممكن تحكيلي إيه اللي بتحس بيه أو إيه المشاكل اللي بتواجهها؟ تقدر تكون مفصل زي ما تحب - أنا هنا أسمعك وأساعدك.";
			
			default:
				return "شكراً لك! الآن دعني أساعدك في فهم ما تمر به. هل يمكنك إخباري عن وضعك الحالي أو الأعراض أو المخاوف التي لديك؟ يمكنك أن تكون مفصلاً كما تريد - أنا هنا للاستماع والمساعدة.";
		}
	}
	
	/**
	 * Check if a question has already been asked
	 */
	private function question_already_asked( $new_question, $asked_questions ) {
		$new_question_lower = strtolower( $new_question );
		
		foreach ( $asked_questions as $asked ) {
			$asked_lower = strtolower( $asked );
			
			// Check for exact match or high similarity
			if ( $new_question_lower === $asked_lower ) {
				return true;
			}
			
			// Check for key phrases that indicate the same question
			$key_phrases = array(
				'تأثير هذه المشاعر على حياتك اليومية',
				'أعراض أخرى تعاني منها',
				'نمط نومك',
				'كم ساعة تنام',
				'متى بدأت تشعر',
				'ما يقلقك',
				'طبيعة عملك',
				'من أي بلد أنت',
				'أي منطقة أو بلد',
				'بلد أنت',
				'impact of these feelings on your daily life',
				'other symptoms you are experiencing',
				'sleep pattern',
				'how many hours do you sleep',
				'when did you start feeling',
				'what worries you',
				'nature of your work',
				'which country you\'re from',
				'which region or country',
				'country you\'re from'
			);
			
			foreach ( $key_phrases as $phrase ) {
				if ( strpos( $new_question_lower, $phrase ) !== false && strpos( $asked_lower, $phrase ) !== false ) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Generate fallback diagnosis when AI response fails
	 */
	private function generate_fallback_diagnosis( $conversation_history, $current_message, $is_arabic ) {
		global $wpdb;
		
		// Get all available diagnoses
		$diagnoses = $wpdb->get_results( "SELECT id, name, name_en, description FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
		
		// Analyze conversation for symptoms
		$symptoms = array();
		$all_text = strtolower( $current_message );
		
		foreach ( $conversation_history as $msg ) {
			if ( $msg['role'] === 'user' ) {
				$all_text .= ' ' . strtolower( $msg['content'] );
			}
		}
		
		// Map symptoms to diagnoses
		$symptom_mapping = array(
			// Sleep disorders
			'أرق' => array( 'Sleep Disorders', 'sleep', 'insomnia' ),
			'نوم' => array( 'Sleep Disorders', 'sleep', 'insomnia' ),
			'sleep' => array( 'Sleep Disorders', 'sleep', 'insomnia' ),
			'insomnia' => array( 'Sleep Disorders', 'sleep', 'insomnia' ),
			
			// Depression
			'حزن' => array( 'Depression', 'depression', 'sadness' ),
			'اكتئاب' => array( 'Depression', 'depression', 'sadness' ),
			'sad' => array( 'Depression', 'depression', 'sadness' ),
			'depression' => array( 'Depression', 'depression', 'sadness' ),
			
			// Anxiety
			'قلق' => array( 'Anxiety Disorders', 'anxiety', 'worry' ),
			'توتر' => array( 'Anxiety Disorders', 'anxiety', 'worry' ),
			'anxiety' => array( 'Anxiety Disorders', 'anxiety', 'worry' ),
			'worry' => array( 'Anxiety Disorders', 'anxiety', 'worry' ),
			
			// Stress
			'ضغط' => array( 'Stress Management', 'stress', 'pressure' ),
			'stress' => array( 'Stress Management', 'stress', 'pressure' ),
			'pressure' => array( 'Stress Management', 'stress', 'pressure' ),
			
			// Work issues
			'عمل' => array( 'Work-Life Balance', 'work', 'job' ),
			'وظيفة' => array( 'Work-Life Balance', 'work', 'job' ),
			'work' => array( 'Work-Life Balance', 'work', 'job' ),
			'job' => array( 'Work-Life Balance', 'work', 'job' ),
		);
		
		// Find matching diagnosis
		$matched_diagnosis = null;
		foreach ( $symptom_mapping as $symptom => $diagnosis_info ) {
			if ( strpos( $all_text, $symptom ) !== false ) {
				$diagnosis_name = $diagnosis_info[0];
				foreach ( $diagnoses as $diagnosis ) {
					if ( stripos( $diagnosis->name, $diagnosis_name ) !== false || 
						 stripos( $diagnosis->name_en, $diagnosis_name ) !== false ) {
						$matched_diagnosis = $diagnosis;
						break 2;
					}
				}
			}
		}
		
		// If no specific match, default to Stress Management
		if ( ! $matched_diagnosis ) {
			foreach ( $diagnoses as $diagnosis ) {
				if ( stripos( $diagnosis->name, 'Stress Management' ) !== false || 
					 stripos( $diagnosis->name_en, 'Stress Management' ) !== false ) {
					$matched_diagnosis = $diagnosis;
					break;
				}
			}
		}
		
		// If still no match, use the first diagnosis
		if ( ! $matched_diagnosis && ! empty( $diagnoses ) ) {
			$matched_diagnosis = $diagnoses[0];
		}
		
		if ( $matched_diagnosis ) {
			if ( $is_arabic ) {
				$message = "بناءً على المعلومات التي قدمتها، أعتقد أنك قد تعاني من **{$matched_diagnosis->name}**.\n\n";
				$message .= "**الوصف:** " . $matched_diagnosis->description . "\n\n";
				$message .= "لقد أكملت التشخيص ويمكنني الآن مساعدتك في العثور على معالجين متخصصين في هذا المجال.";
			} else {
				$message = "Based on the information you've provided, I believe you may be experiencing **{$matched_diagnosis->name}**.\n\n";
				$message .= "**Description:** " . $matched_diagnosis->description . "\n\n";
				$message .= "I've completed the diagnosis and can now help you find therapists who specialize in this area.";
			}
			
			// Return the proper diagnosis completion structure
			return array(
				'message' => $message,
				'diagnosis' => array(
					'completed' => true,
					'id' => $matched_diagnosis->id,
					'title' => $matched_diagnosis->name,
					'description' => $matched_diagnosis->description,
					'confidence' => 'medium',
					'reasoning' => 'Diagnosis generated based on conversation analysis'
				)
			);
		}
		
		// Fallback message if no diagnosis found
		if ( $is_arabic ) {
			$message = "بناءً على المعلومات التي قدمتها، أعتقد أنك قد تحتاج إلى استشارة متخصص في الصحة النفسية. يمكنني مساعدتك في العثور على معالجين متخصصين.";
		} else {
			$message = "Based on the information you've provided, I believe you may need to consult a mental health specialist. I can help you find specialized therapists.";
		}
		
		return array(
			'message' => $message,
			'diagnosis' => array(
				'completed' => true,
				'id' => null,
				'title' => 'General Consultation',
				'description' => 'Recommendation for professional mental health consultation',
				'confidence' => 'low',
				'reasoning' => 'Insufficient information for specific diagnosis'
			)
		);
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
		// Get data from POST (following the same pattern as other AJAX handlers)
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );
		
		// Handle escaped JSON from frontend
		$conversation_history_raw = $_POST['conversation_history'] ?? '[]';
		$conversation_history = json_decode( stripslashes( $conversation_history_raw ), true );
		
		// Ensure we have an array
		if ( ! is_array( $conversation_history ) ) {
			$conversation_history = array();
		}
		
		// Validate required fields
		if ( empty( $message ) ) {
			wp_send_json_error( 'Message is required', 400 );
		}
		
		// Process the chat diagnosis
		$result = $this->process_chat_diagnosis( $message, $conversation_history );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), 400 );
		}
		
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
		// Check if this is an AI API request
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/' ) === false ) {
			return;
		}
		
		$endpoint = get_query_var( 'ai_endpoint' );
		
		// Only log for auth-related requests
		if ( strpos( $_SERVER['REQUEST_URI'], '/api/ai/auth' ) !== false ) {

		}
		
		if ( ! $endpoint ) {
			// If no endpoint is set, try to extract it from the URL
			$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$path_parts = explode( '/', trim( $path, '/' ) );
			
			// Find the 'ai' part and get what comes after it
			$ai_index = array_search( 'ai', $path_parts );
			if ( $ai_index !== false ) {
				// Get all parts after 'ai' to handle nested endpoints like 'auth/register'
				$endpoint_parts = array_slice( $path_parts, $ai_index + 1 );
				$endpoint = implode( '/', $endpoint_parts );
			}
		}
		
		if ( ! $endpoint ) {
			return;
		}
		
		// Prevent WordPress from outputting anything else
		define( 'DOING_AJAX', true );
		
		// Set CORS headers early to prevent redirects
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
		
		// Set CORS headers based on origin validation
		if ( ! empty( $origin ) && $this->is_origin_allowed( $origin ) ) {
			header( 'Access-Control-Allow-Origin: ' . $origin );
		} else {
			// Fallback to wildcard for development or if no origin
			header( 'Access-Control-Allow-Origin: *' );
		}
		
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
			case 'nonce':
				$this->handle_nonce_endpoint( $method, $path );
				break;
			case 'ping':
				$this->send_success( array( 
					'message' => 'Pong!',
					'timestamp' => current_time( 'mysql' ),
					'endpoint' => $endpoint
				) );
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
			case 'therapist-availability':
				$this->handle_therapist_availability_endpoint( $method, $path );
				break;
			case 'therapist-available-dates':
				$this->handle_therapist_available_dates_endpoint( $method, $path );
				break;
			default:
				$this->send_error( 'Endpoint not found', 404 );
		}
	}
	
	/**
	 * Handle nonce endpoints
	 */
	private function handle_nonce_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				$action = sanitize_text_field( $_GET['action'] ?? 'ai_api_nonce' );
				$nonce = wp_create_nonce( $action );
				
				$this->send_success( array(
					'nonce' => $nonce,
					'action' => $action
				) );
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
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
				} elseif ( $path[1] === 'resend-verification' ) {

					$this->ai_resend_verification();
				} else {

					$this->send_error( 'Auth endpoint not found', 404 );
				}
				break;
			default:
	
				$this->send_error( 'Method not allowed', 405 );
		}
	}
	
	/**
	 * Verify nonce for API requests
	 */
	private function verify_api_nonce( $nonce_field = 'nonce', $nonce_action = 'ai_api_nonce' ) {
		// Get nonce from headers or POST data
		$nonce = null;
		
		// Check Authorization header for Bearer token (JWT)
		$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
		if ( strpos( $auth_header, 'Bearer ' ) === 0 ) {
			// If JWT token is present, skip nonce verification for authenticated requests
			return true;
		}
		
		// Check for nonce in POST data
		$input_data = json_decode( file_get_contents( 'php://input' ), true );
		if ( $input_data && isset( $input_data[ $nonce_field ] ) ) {
			$nonce = $input_data[ $nonce_field ];
		}
		
		// Check for nonce in $_POST
		if ( ! $nonce && isset( $_POST[ $nonce_field ] ) ) {
			$nonce = $_POST[ $nonce_field ];
		}
		
		// Check for nonce in query parameters
		if ( ! $nonce && isset( $_GET[ $nonce_field ] ) ) {
			$nonce = $_GET[ $nonce_field ];
		}
		
		// If no nonce provided, allow the request (for backward compatibility)
		// In production, you might want to require nonces for all requests
		if ( ! $nonce ) {
			return true;
		}
		
		// Verify the nonce
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Handle therapists endpoints
	 */
	private function handle_therapists_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					$this->get_ai_therapists();
				} elseif ( $path[1] === 'search' ) {
					$this->get_ai_therapists_search();
				} elseif ( is_numeric( $path[1] ) ) {
					if ( isset( $path[2] ) && $path[2] === 'details' ) {
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
						$this->get_ai_therapist_earliest_slot( $path[1] );
					} elseif ( isset( $path[2] ) && $path[2] === 'available-dates' ) {
						$this->get_ai_therapist_available_dates( $path[1] );
					} elseif ( isset( $path[2] ) && $path[2] === 'time-slots' ) {
						$date = $_GET['date'] ?? '';
						$this->get_ai_therapist_time_slots( $path[1], $date );
					} else {
						$this->get_ai_therapist( $path[1] );
					}
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
				if ( isset( $path[1] ) && $path[1] === 'available' ) {
					$this->get_ai_available_appointments();
				} elseif ( isset( $path[1] ) && $path[1] === 'user' && isset( $path[2] ) && is_numeric( $path[2] ) ) {
					$this->get_ai_user_appointments( $path[2] );
				} elseif ( isset( $path[1] ) && is_numeric( $path[1] ) && count( $path ) === 2 ) {
					// GET /api/ai/appointments/{id} - get single appointment
					$appointment_id = intval( $path[1] );
					$this->get_ai_single_appointment( $appointment_id );
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
			case 'PUT':
				if ( isset( $path[1] ) && is_numeric( $path[1] ) && isset( $path[2] ) ) {
					$appointment_id = intval( $path[1] );
					$action = $path[2];
					
					switch ( $action ) {
						case 'cancel':
							$this->cancel_ai_appointment( $appointment_id );
							break;
						case 'reschedule':
							$this->reschedule_ai_appointment( $appointment_id );
							break;
						default:
							$this->send_error( 'Invalid appointment action', 404 );
					}
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
	 * Handle therapist available dates endpoint
	 */
	private function handle_therapist_available_dates_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					$therapist_id = $_GET['therapist_id'] ?? null;
					if (!$therapist_id) {
						$this->send_error( 'Missing therapist_id', 400 );
						return;
					}
					$this->get_ai_therapist_available_dates( $therapist_id );
				} else {
					$this->send_error( 'Invalid endpoint', 404 );
				}
				break;
			default:
				$this->send_error( 'Method not allowed', 405 );
		}
	}

	/**
	 * Handle therapist availability endpoint
	 */
	private function handle_therapist_availability_endpoint( $method, $path ) {
		switch ( $method ) {
			case 'GET':
				if ( count( $path ) === 1 ) {
					// Create a proper WP_REST_Request object with GET parameters
					$request = new WP_REST_Request( 'GET' );
					$request->set_param( 'therapist_id', $_GET['therapist_id'] ?? null );
					$request->set_param( 'date', $_GET['date'] ?? null );
					$this->get_ai_therapist_availability( $request );
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

		
		// Verify nonce for security
		if ( ! $this->verify_api_nonce( 'nonce', 'ai_login_nonce' ) ) {
	
			$this->send_error( 'Security check failed', 401 );
		}
		
		$data = json_decode( file_get_contents( 'php://input' ), true );

		// Check if password is provided
		if ( ! isset( $data['password'] ) ) {
			$this->send_error( 'Password required', 400 );
		}
		
		// Get therapist registration settings to check email requirement
		$registration_settings = snks_get_therapist_registration_settings();
		error_log('🔍 Login API: Registration settings: ' . print_r($registration_settings, true));
		
		$user = null;
		$login_method = '';
		
		// Determine login method based on settings and provided data
		if ( $registration_settings['require_email'] ) {
			// Email login is required
			error_log('🔍 Login API: Using EMAIL login method');
			if ( ! isset( $data['email'] ) ) {
				$this->send_error( 'Email and password required', 400 );
			}
			$user = get_user_by( 'email', sanitize_email( $data['email'] ) );
			$login_method = 'email';
		} else {
			// WhatsApp login is used
			error_log('🔍 Login API: Using WHATSAPP login method');
			if ( ! isset( $data['whatsapp'] ) ) {
				$this->send_error( 'WhatsApp number and password required', 400 );
			}
			
			// Find user by WhatsApp number
			$users = get_users( array(
				'meta_key' => 'billing_whatsapp',
				'meta_value' => sanitize_text_field( $data['whatsapp'] ),
				'number' => 1
			) );
			
			if ( ! empty( $users ) ) {
				$user = $users[0];
			}
			$login_method = 'whatsapp';
		}
		
		if ( ! $user ) {
			$this->send_error( 'Invalid credentials', 401 );
		}
		
		if ( ! wp_check_password( $data['password'], $user->user_pass ) ) {
	
			$this->send_error( 'Invalid credentials', 401 );
		}
		

		
		// Check if user is a patient (customer) or doctor
		$allowed_roles = array( 'customer', 'doctor', 'clinic_manager' );
		if ( ! array_intersect( $allowed_roles, $user->roles ) ) {
	
			$this->send_error( 'Access denied. Only patients and doctors can access this platform.', 403 );
		}
		

		
		// Check if AI patient needs email verification
		if ( self::is_ai_patient( $user->ID ) ) {
			$is_verified = get_user_meta( $user->ID, 'ai_email_verified', true );
			if ( $is_verified !== '1' ) {
				// Get registration settings to determine verification method
				$otp_method = $registration_settings['otp_method'] ?? 'email';
				$require_email = $registration_settings['require_email'] ?? 1;
				
				// Create context-aware verification message
				if ( $otp_method === 'whatsapp' || !$require_email ) {
					$verification_message = 'Please verify your WhatsApp number before logging in. Check your WhatsApp for verification code.';
				} else {
					$verification_message = 'Please verify your email address before logging in. Check your email for verification code.';
				}
				
				$this->send_error( $verification_message, 401 );
			}
	
		}
		
		$token = $this->generate_jwt_token( $user->ID );

		

		
		// Prepare user data
		$user_data = array(
			'id' => $user->ID,
			'email' => $user->user_email,
			'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ),
			'last_name' => get_user_meta( $user->ID, 'billing_last_name', true ),
			'role' => $user->roles[0], // Primary role
			'roles' => $user->roles,   // All roles
			'login_method' => $login_method
		);
		
		// Add WhatsApp number if available
		$whatsapp = get_user_meta( $user->ID, 'billing_whatsapp', true );
		if ( $whatsapp ) {
			$user_data['whatsapp'] = $whatsapp;
		}
		
		$this->send_success( array(
			'token' => $token,
			'user' => $user_data
		) );
	}
	
	/**
	 * AI Register
	 */
	private function ai_register() {

		
		// Verify nonce for security
		if ( ! $this->verify_api_nonce( 'nonce', 'ai_register_nonce' ) ) {
	
			$this->send_error( 'Security check failed', 401 );
		}
		
		$data = json_decode( file_get_contents( 'php://input' ), true );

		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
	
			$this->send_error( 'Invalid JSON data', 400 );
		}
		
		// Get therapist registration settings to check email requirement
		$registration_settings = snks_get_therapist_registration_settings();
		
		// Base required fields (no phone field, conditional email)
		$required_fields = array( 'first_name', 'last_name', 'age', 'whatsapp', 'password' );
		
		// Add email to required fields if it's required in settings
		if ( $registration_settings['require_email'] ) {
			$required_fields[] = 'email';
		}
		
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
		
				$this->send_error( "Field {$field} is required", 400 );
			}
		}
		

		
		// Check if user exists (by email if provided, otherwise by WhatsApp)
		$existing_user = null;
		$user_identifier = '';
		
		if ( ! empty( $data['email'] ) ) {
			$existing_user = get_user_by( 'email', sanitize_email( $data['email'] ) );
			$user_identifier = sanitize_email( $data['email'] );
		} else {
			// If no email, check by WhatsApp number in user meta
			$users = get_users( array(
				'meta_key' => 'billing_whatsapp',
				'meta_value' => sanitize_text_field( $data['whatsapp'] ),
				'number' => 1
			) );
			if ( ! empty( $users ) ) {
				$existing_user = $users[0];
			}
			$user_identifier = sanitize_text_field( $data['whatsapp'] );
		}
		
		if ( $existing_user ) {
			// Check if user is already verified
			$is_verified = get_user_meta( $existing_user->ID, 'ai_email_verified', true );
			if ( $is_verified === '1' ) {
				$this->send_error( 'User already exists and is verified. Please login instead.', 400 );
			}
			
			// Update existing user fields
			$this->update_ai_user_fields( $existing_user->ID, $data );
			$user = $existing_user;
		} else {
			// Create new user - use email if provided, otherwise create email from WhatsApp
			$username = ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : sanitize_text_field( $data['whatsapp'] );
			
			if ( ! empty( $data['email'] ) ) {
				$email = sanitize_email( $data['email'] );
			} else {
				// Create email from WhatsApp number: +201234567890@jalsah.app
				$clean_whatsapp = preg_replace('/[^0-9+]/', '', $data['whatsapp']);
				$email = $clean_whatsapp . '@jalsah.app';
			}
			
			$user_id = wp_create_user( $username, $data['password'], $email );
			if ( is_wp_error( $user_id ) ) {
				$this->send_error( $user_id->get_error_message(), 400 );
			}
			
			$user = get_user_by( 'ID', $user_id );
			$user->set_role( 'customer' );
			$this->update_ai_user_fields( $user_id, $data );
		}
		
		// Generate verification code - use random numbers only
		$verification_code = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$verification_code .= rand( 0, 9 );
		}
		
		// Debug OTP generation
		error_log( '=== OTP GENERATION DEBUG ===' );
		error_log( 'Generated OTP: ' . $verification_code );
		error_log( 'OTP Length: ' . strlen( $verification_code ) );
		error_log( '============================' );
		

		
		// Store verification code and expiry
		update_user_meta( $user->ID, 'ai_verification_code', $verification_code );
		update_user_meta( $user->ID, 'ai_verification_expires', time() + ( 15 * 60 ) ); // 15 minutes
		update_user_meta( $user->ID, 'ai_email_verified', '0' );
		
		// Send verification code based on OTP method settings
		$otp_success = false;
		$contact_method = '';
		
		if ( $registration_settings['otp_method'] === 'sms' && ! empty( $data['whatsapp'] ) ) {
			$contact_method = $data['whatsapp'];
			$message = snks_get_multilingual_otp_message( $verification_code, $registration_settings['whatsapp_message_language'] ?? 'ar' );
			
			// Use existing WhySMS SMS service
			$sms_result = send_sms_via_whysms( $data['whatsapp'], $message );
			
			if ( ! is_wp_error( $sms_result ) ) {
				$otp_success = true;
			}
		} elseif ( $registration_settings['otp_method'] === 'whatsapp' && ! empty( $data['whatsapp'] ) ) {
			$contact_method = $data['whatsapp'];
			$message = snks_get_multilingual_otp_message( $verification_code, $registration_settings['whatsapp_message_language'] ?? 'ar' );
			
			// Debug patient registration WhatsApp
			error_log( '=== PATIENT REGISTRATION WHATSAPP DEBUG ===' );
			error_log( 'Phone Number Received: ' . $data['whatsapp'] );
			error_log( 'Phone Number Length: ' . strlen( $data['whatsapp'] ) );
			error_log( 'Message: ' . $message );
			error_log( 'API URL: ' . ( $registration_settings['whatsapp_api_url'] ?? 'NOT SET' ) );
			error_log( 'Phone Number ID: ' . ( $registration_settings['whatsapp_phone_number_id'] ?? 'NOT SET' ) );
			error_log( 'Use Template: ' . ( $registration_settings['whatsapp_use_template'] ?? 'NOT SET' ) );
			error_log( 'Template Name: ' . ( $registration_settings['whatsapp_template_name'] ?? 'NOT SET' ) );
			error_log( '===========================================' );
			
			// Use WhatsApp Business API
			$whatsapp_result = snks_send_whatsapp_message( $data['whatsapp'], $message, $registration_settings );
			
			if ( $whatsapp_result && ! is_wp_error( $whatsapp_result ) ) {
				$otp_success = true;
			}
		} elseif ( $registration_settings['otp_method'] === 'email' && ! empty( $data['email'] ) ) {
			$contact_method = $data['email'];
			
			// Send verification email using existing method
			$otp_success = $this->send_verification_email( $user->ID, $verification_code );
		} else {
			// Fallback to email if no method matches or email is available
			if ( ! empty( $data['email'] ) ) {
				$contact_method = $data['email'];
				$otp_success = $this->send_verification_email( $user->ID, $verification_code );
			}
		}
		
		if ( ! $otp_success ) {
			$error_message = '';
			if ( $registration_settings['otp_method'] === 'sms' ) {
				$error_message = 'Failed to send verification code via SMS. Please try again.';
			} elseif ( $registration_settings['otp_method'] === 'whatsapp' ) {
				$error_message = 'Failed to send verification code via WhatsApp. Please try again.';
			} else {
				$error_message = 'Failed to send verification email. Please try again.';
			}
			
			$this->send_error( $error_message, 500 );
		}
		
		// Get locale for response message
		$locale = $this->get_request_locale();
		
		// Dynamic success message based on OTP method
		$success_message = '';
		if ( $registration_settings['otp_method'] === 'sms' ) {
			$success_message = $locale === 'ar' 
				? 'تم إنشاء الحساب بنجاح! تم إرسال رمز التحقق عبر الرسائل القصيرة.'
				: 'Registration successful! Verification code sent via SMS.';
		} elseif ( $registration_settings['otp_method'] === 'whatsapp' ) {
			$success_message = $locale === 'ar' 
				? 'تم إنشاء الحساب بنجاح! تم إرسال رمز التحقق إلى واتساب.'
				: 'Registration successful! Verification code sent via WhatsApp.';
		} else {
			$success_message = $locale === 'ar' 
				? 'تم إنشاء الحساب بنجاح! يرجى التحقق من بريدك الإلكتروني للحصول على رمز التحقق.'
				: 'Registration successful! Please check your email for verification code.';
		}
		
		$this->send_success( array(
			'message' => $success_message,
			'user_id' => $user->ID,
			'email' => $user->user_email,
			'contact_method' => $contact_method,
			'otp_method' => $registration_settings['otp_method'],
			'requires_verification' => true
		) );
	}
	
	/**
	 * Update AI user fields
	 */
	private function update_ai_user_fields( $user_id, $data ) {
		update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $data['first_name'] ) );
		update_user_meta( $user_id, 'billing_last_name', sanitize_text_field( $data['last_name'] ) );
		update_user_meta( $user_id, 'age', intval( $data['age'] ) );
		
		// WhatsApp is always provided, also store as billing_whatsapp for consistency
		update_user_meta( $user_id, 'whatsapp', sanitize_text_field( $data['whatsapp'] ) );
		update_user_meta( $user_id, 'billing_whatsapp', sanitize_text_field( $data['whatsapp'] ) );
		
		// Optional fields
		if ( ! empty( $data['phone'] ) ) {
			update_user_meta( $user_id, 'billing_phone', sanitize_text_field( $data['phone'] ) );
		}
		
		if ( ! empty( $data['country'] ) ) {
			update_user_meta( $user_id, 'billing_country', sanitize_text_field( $data['country'] ) );
		}
		
		if ( ! empty( $data['email'] ) ) {
			update_user_meta( $user_id, 'billing_email', sanitize_email( $data['email'] ) );
		}
		
		// Mark user as AI patient
		update_user_meta( $user_id, 'registered_from_jalsah_ai', '1' );
		update_user_meta( $user_id, 'ai_registration_date', current_time( 'mysql' ) );
	}
	
	/**
	 * Check if user is an AI patient
	 *
	 * @param int $user_id User ID to check
	 * @return bool True if user registered from Jalsah AI
	 */
	public static function is_ai_patient( $user_id ) {
		return get_user_meta( $user_id, 'registered_from_jalsah_ai', true ) === '1';
	}
	
	/**
	 * Get AI registration date
	 *
	 * @param int $user_id User ID
	 * @return string|false Registration date or false if not AI patient
	 */
	public static function get_ai_registration_date( $user_id ) {
		if ( ! self::is_ai_patient( $user_id ) ) {
			return false;
		}
		return get_user_meta( $user_id, 'ai_registration_date', true );
	}
	
	/**
	 * Send verification email
	 */
	private function send_verification_email( $user_id, $verification_code ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}
		
		$first_name = get_user_meta( $user_id, 'billing_first_name', true );
		$site_name = get_bloginfo( 'name' );
		$site_url = get_site_url();
		
		// Get locale from request or user preference
		$locale = $this->get_request_locale();
		
		// Email content based on locale
		if ( $locale === 'ar' ) {
			$subject = sprintf( '[%s] تحقق من عنوان بريدك الإلكتروني', $site_name );
			$message = sprintf(
				'مرحباً %s،

شكراً لك على التسجيل في %s!

رمز التحقق الخاص بك هو: %s

سينتهي هذا الرمز خلال 15 دقيقة.

يرجى إدخال هذا الرمز في نموذج التحقق لإكمال تسجيلك.

إذا لم تقم بالتسجيل للحصول على حساب، يرجى تجاهل هذا البريد الإلكتروني.

مع أطيب التحيات،
فريق %s',
				$first_name,
				$site_name,
				$verification_code,
				$site_name
			);
		} else {
			// Default to English
			$subject = sprintf( '[%s] Verify Your Email Address', $site_name );
			$message = sprintf(
				'Hello %s,

Thank you for registering with %s!

Your verification code is: %s

This code will expire in 15 minutes.

Please enter this code in the verification form to complete your registration.

If you did not register for an account, please ignore this email.

Best regards,
%s Team',
				$first_name,
				$site_name,
				$verification_code,
				$site_name
			);
		}
		
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		
		return wp_mail( $user->user_email, $subject, $message, $headers );
	}
	
	/**
	 * Get locale from request
	 */
	private function get_request_locale() {
		// Check for locale in request headers or query parameters
		$locale = null;
		
		// Check Accept-Language header
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			if ( strpos( $accept_language, 'ar' ) !== false ) {
				$locale = 'ar';
			}
		}
		
		// Check for locale in query parameters
		if ( isset( $_GET['locale'] ) ) {
			$locale = sanitize_text_field( $_GET['locale'] );
		}
		
		// Check for locale in request body
		$input = file_get_contents( 'php://input' );
		$data = json_decode( $input, true );
		if ( $data && isset( $data['locale'] ) ) {
			$locale = sanitize_text_field( $data['locale'] );
		}
		
		// Default to English if no locale detected
		return $locale === 'ar' ? 'ar' : 'en';
	}

	/**
	 * AI Verify Email
	 */
	private function ai_verify_email() {
		// Verify nonce for security
		if ( ! $this->verify_api_nonce( 'nonce', 'ai_verify_nonce' ) ) {
	
			$this->send_error( 'Security check failed', 401 );
		}
		
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		if ( ! isset( $data['code'] ) ) {
			$this->send_error( 'Verification code required', 400 );
		}
		
		// Check if we have email or WhatsApp identifier
		if ( ! isset( $data['email'] ) && ! isset( $data['whatsapp'] ) ) {
			$this->send_error( 'Email or WhatsApp number required', 400 );
		}
		
		$code = sanitize_text_field( $data['code'] );
		$user = null;
		
		// Find user by email or WhatsApp
		if ( isset( $data['email'] ) ) {
			$email = sanitize_email( $data['email'] );
			$user = get_user_by( 'email', $email );
		} else {
			$whatsapp = sanitize_text_field( $data['whatsapp'] );
			$users = get_users( array(
				'meta_key' => 'whatsapp',
				'meta_value' => $whatsapp
			) );
			$user = ! empty( $users ) ? $users[0] : null;
		}
		
		if ( ! $user ) {
			$this->send_error( 'User not found', 404 );
		}
		
		// Check if user is already verified
		$is_verified = get_user_meta( $user->ID, 'ai_email_verified', true );
		if ( $is_verified === '1' ) {
			$this->send_error( 'Email is already verified', 400 );
		}
		
		// Get stored verification code and expiry
		$stored_code = get_user_meta( $user->ID, 'ai_verification_code', true );
		$expires = get_user_meta( $user->ID, 'ai_verification_expires', true );
		
		// Get locale for error messages
		$locale = $this->get_request_locale();
		
		// Check if code is expired
		if ( time() > intval( $expires ) ) {
			$error_message = $locale === 'ar' 
				? 'انتهت صلاحية رمز التحقق. يرجى طلب رمز جديد.'
				: 'Verification code has expired. Please request a new one.';
			$this->send_error( $error_message, 400 );
		}
		
		// Check if code matches
		if ( $code !== $stored_code ) {
			$error_message = $locale === 'ar' 
				? 'رمز التحقق غير صحيح'
				: 'Invalid verification code';
			$this->send_error( $error_message, 400 );
		}
		
		// Mark email as verified
		update_user_meta( $user->ID, 'ai_email_verified', '1' );
		
		// Clear verification code
		delete_user_meta( $user->ID, 'ai_verification_code' );
		delete_user_meta( $user->ID, 'ai_verification_expires' );
		
		// Generate JWT token for auto-login
		$token = $this->generate_jwt_token( $user->ID );
		
		// Get locale for response message
		$locale = $this->get_request_locale();
		$success_message = $locale === 'ar' 
			? 'تم التحقق من البريد الإلكتروني بنجاح! تم تسجيل دخولك الآن.'
			: 'Email verified successfully! You are now logged in.';
		
		$this->send_success( array(
			'message' => $success_message,
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
	 * AI Resend Verification
	 */
	private function ai_resend_verification() {
		// Verify nonce for security
		if ( ! $this->verify_api_nonce( 'nonce', 'ai_resend_verification_nonce' ) ) {
	
			$this->send_error( 'Security check failed', 401 );
		}
		
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
		// Check if we have email or WhatsApp identifier
		if ( ! isset( $data['email'] ) && ! isset( $data['whatsapp'] ) ) {
			$this->send_error( 'Email or WhatsApp number required', 400 );
		}
		
		$user = null;
		
		// Find user by email or WhatsApp
		if ( isset( $data['email'] ) ) {
			$email = sanitize_email( $data['email'] );
			$user = get_user_by( 'email', $email );
		} else {
			$whatsapp = sanitize_text_field( $data['whatsapp'] );
			$users = get_users( array(
				'meta_key' => 'whatsapp',
				'meta_value' => $whatsapp
			) );
			$user = ! empty( $users ) ? $users[0] : null;
		}
		
		if ( ! $user ) {
			$this->send_error( 'User not found', 404 );
		}
		
		// Check if user is already verified
		$is_verified = get_user_meta( $user->ID, 'ai_email_verified', true );
		if ( $is_verified === '1' ) {
			$this->send_error( 'Email is already verified', 400 );
		}
		
		// Generate new verification code
		$verification_code = wp_generate_password( 6, false, false );
		$verification_code = preg_replace( '/[^0-9]/', '', $verification_code ); // Ensure it's only numbers
		if ( strlen( $verification_code ) < 6 ) {
			$verification_code = str_pad( $verification_code, 6, '0', STR_PAD_LEFT );
		}
		
		// Store new verification code and expiry
		update_user_meta( $user->ID, 'ai_verification_code', $verification_code );
		update_user_meta( $user->ID, 'ai_verification_expires', time() + ( 15 * 60 ) ); // 15 minutes
		
		// Send verification email
		$email_sent = $this->send_verification_email( $user->ID, $verification_code );
		
		if ( ! $email_sent ) {
			$this->send_error( 'Failed to send verification email. Please try again.', 500 );
		}
		
		$this->send_success( array(
			'message' => 'Verification code sent successfully! Please check your email.',
			'user_id' => $user->ID,
			'email' => $user->user_email
		) );
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
			ORDER BY id ASC"
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
	 * Get AI Therapists Search
	 */
	private function get_ai_therapists_search() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'therapist_applications';
		
		// Get search query from GET parameters
		$search_query = sanitize_text_field( $_GET['q'] ?? '' );
		$diagnosis_id = intval( $_GET['diagnosis'] ?? 0 );
		
		if ( empty( $search_query ) ) {
			$this->send_error( 'Search query is required', 400 );
		}
		
		// Debug logging
		error_log("🔍 Search query: " . $search_query);
		error_log("🔍 Diagnosis ID: " . $diagnosis_id);
		
		// Build the base query
		$where_conditions = array( 'ta.status = "approved"', 'ta.show_on_ai_site = 1' );
		$query_params = array();
		
		// Add search condition for therapist names
		$search_condition = $wpdb->prepare(
			'(ta.name LIKE %s OR ta.name_en LIKE %s)',
			'%' . $wpdb->esc_like( $search_query ) . '%',
			'%' . $wpdb->esc_like( $search_query ) . '%'
		);
		$where_conditions[] = $search_condition;
		
		// Add diagnosis filter if provided
		if ( $diagnosis_id > 0 ) {
			$where_conditions[] = 'td.diagnosis_id = %d';
			$query_params[] = $diagnosis_id;
		}
		
		// Build the complete query
		$where_clause = implode( ' AND ', $where_conditions );
		
		if ( $diagnosis_id > 0 ) {
			// Join with therapist diagnoses table when filtering by diagnosis
			$query = $wpdb->prepare(
				"SELECT ta.*, td.display_order, td.frontend_order 
				FROM $table_name ta
				JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON ta.user_id = td.therapist_id
				WHERE $where_clause
				ORDER BY td.frontend_order ASC, td.display_order ASC, ta.name ASC",
				...$query_params
			);
		} else {
			// Simple search without diagnosis filter
			$query = "SELECT ta.* FROM $table_name ta WHERE $where_clause ORDER BY ta.name ASC";
		}
		
		// Debug logging
		error_log("🔍 Final query: " . $query);
		
		$applications = $wpdb->get_results( $query );
		
		// Debug logging
		error_log("🔍 Found " . count($applications) . " applications");
		
		$result = array();
		foreach ( $applications as $application ) {
			$result[] = $this->format_ai_therapist_from_application( $application );
		}
		
		error_log("🔍 Returning " . count($result) . " formatted therapists");
		$this->send_success( $result );
	}

	/**
	 * Get AI Therapists by Diagnosis
	 */
	private function get_ai_therapists_by_diagnosis( $diagnosis_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'therapist_applications';
		
		// Debug logging
		error_log("🔍 get_ai_therapists_by_diagnosis called with diagnosis_id: " . $diagnosis_id);
		
		// Check if limit should be applied (when show more button is disabled)
		$limit = null;
		$show_more_enabled = snks_get_show_more_button_enabled();
		$diagnosis_results_limit = snks_get_diagnosis_results_limit();
		
		// If show more button is disabled and limit is set, apply the limit
		if ( ! $show_more_enabled && $diagnosis_results_limit > 0 ) {
			$limit = $diagnosis_results_limit;
		}
		
		$query = $wpdb->prepare(
			"SELECT ta.*, td.display_order, td.frontend_order FROM $table_name ta
			JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON ta.user_id = td.therapist_id
			WHERE td.diagnosis_id = %d AND ta.status = 'approved' AND ta.show_on_ai_site = 1
			ORDER BY td.frontend_order ASC, td.display_order ASC, ta.name ASC",
			$diagnosis_id
		);
		
		// Add LIMIT clause if needed
		if ( $limit !== null ) {
			$query .= $wpdb->prepare( " LIMIT %d", $limit );
		}
		
		$applications = $wpdb->get_results( $query );
		
		// Debug logging
		error_log("🔍 Query executed: " . $query);
		error_log("🔍 Found " . count($applications) . " applications for diagnosis_id: " . $diagnosis_id);
		
		$result = array();
		foreach ( $applications as $application ) {
			$result[] = $this->format_ai_therapist_from_application( $application );
		}
		
		error_log("🔍 Returning " . count($result) . " formatted therapists");
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
		
		// Get the actual earliest slot from timetable
		$earliest_slot_data = $this->get_earliest_slot_from_timetable($application->user_id);
		
		// Get all available dates from timetable
		$available_dates = $this->get_available_dates_from_timetable($application->user_id);
		
		// Get pricing
		$pricing = $this->get_therapist_ai_price( $application->user_id );
		
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
			'doctor_specialty' => $application->doctor_specialty,
			'certifications' => $application->ai_certifications,
			'earliest_slot' => $application->ai_earliest_slot,
			'earliest_slot_data' => $earliest_slot_data,
			'available_dates' => $available_dates,
			'rating' => floatval( $application->rating ),
			'total_ratings' => intval( $application->total_ratings ),
			'price' => $pricing,
			'diagnoses' => $diagnoses,
			'certificates' => $certificates_data,
			'frontend_order' => isset($application->frontend_order) ? intval($application->frontend_order) : intval($application->id),
			'display_order' => isset($application->display_order) ? intval($application->display_order) : intval($application->id),
		);
		
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
			"SELECT d.*, td.rating, td.suitability_message_en, td.suitability_message_ar, td.display_order, td.frontend_order 
			FROM {$wpdb->prefix}snks_diagnoses d
			JOIN {$wpdb->prefix}snks_therapist_diagnoses td ON d.id = td.diagnosis_id
			WHERE td.therapist_id = %d
			ORDER BY td.frontend_order ASC, td.display_order ASC",
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
			$diagnosis->name = $locale === 'ar' ? ($name_ar ?: $name_en) : $name_en;
			$diagnosis->description = $locale === 'ar' ? ($description_ar ?: $description_en) : $description_en;
			
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
			
			// Add frontend_order to the diagnosis object
			$diagnosis->frontend_order = isset( $diagnosis->frontend_order ) ? intval( $diagnosis->frontend_order ) : 0;
			

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
			
			// Check if 45_minutes pricing exists and has a valid 'others' value
			if ( isset( $pricing['45_minutes'] ) && ! empty( $pricing['45_minutes']['others'] ) ) {
				return $pricing['45_minutes'];
			}
			
			// If no 45_minutes pricing, check for 60_minutes as fallback
			if ( isset( $pricing['60_minutes'] ) && ! empty( $pricing['60_minutes']['others'] ) ) {
				return $pricing['60_minutes'];
			}
			
			// If no pricing is set up, check if therapist has any pricing fields set
			$price_45_others = get_user_meta( $therapist_id, '45_minutes_pricing_others', true );
			$price_60_others = get_user_meta( $therapist_id, '60_minutes_pricing_others', true );
			
			if ( ! empty( $price_45_others ) ) {
				return array(
					'countries' => array(),
					'others' => intval( $price_45_others )
				);
			}
			
			// Check for 60_minutes_pricing_others as fallback
			if ( ! empty( $price_60_others ) ) {
				return array(
					'countries' => array(),
					'others' => intval( $price_60_others )
				);
			}
			
			// If still no pricing is found, return empty array (will show "Contact for pricing")
			return array();
		}
	}
	
	/**
	 * Setup default pricing for therapist if none exists
	 */
	private function setup_default_therapist_pricing( $therapist_id ) {
		// Check if pricing is already set up
		$price_45_others = get_user_meta( $therapist_id, '45_minutes_pricing_others', true );
		$price_60_others = get_user_meta( $therapist_id, '60_minutes_pricing_others', true );
		
		if ( ! empty( $price_45_others ) || ! empty( $price_60_others ) ) {
			return; // Pricing already exists
		}
		
		// Set up default pricing structure
		$default_price_45 = 150; // Default 45-minute session price
		$default_price_60 = 200; // Default 60-minute session price
		$default_price_90 = 300; // Default 90-minute session price
		
		// Set up 45-minute pricing
		$pricing_45 = array(
			'countries' => array(),
			'others' => $default_price_45
		);
		update_user_meta( $therapist_id, '45_minutes_pricing', $pricing_45 );
		update_user_meta( $therapist_id, '45_minutes_pricing_others', $default_price_45 );
		
		// Set up 60-minute pricing
		$pricing_60 = array(
			'countries' => array(),
			'others' => $default_price_60
		);
		update_user_meta( $therapist_id, '60_minutes_pricing', $pricing_60 );
		update_user_meta( $therapist_id, '60_minutes_pricing_others', $default_price_60 );
		
		// Set up 90-minute pricing
		$pricing_90 = array(
			'countries' => array(),
			'others' => $default_price_90
		);
		update_user_meta( $therapist_id, '90_minutes_pricing', $pricing_90 );
		update_user_meta( $therapist_id, '90_minutes_pricing_others', $default_price_90 );
		
		// Enable 45-minute sessions by default
		update_user_meta( $therapist_id, '45_minutes', 'on' );
		update_user_meta( $therapist_id, '60_minutes', 'on' );
		update_user_meta( $therapist_id, '90_minutes', 'on' );
	}
	
	/**
	 * Setup default pricing for therapist when activated
	 */
	public static function setup_therapist_default_pricing( $therapist_id ) {
		$instance = new self();
		$instance->setup_default_therapist_pricing( $therapist_id );
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
						'therapist_id' => $appointment->user_id, // Add therapist ID
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
		$user_id = $this->verify_jwt_token();
		
		$data = json_decode( file_get_contents( 'php://input' ), true );
		
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
		
		// Check if slot is already in cart
		foreach ( $cart as $item ) {
			if ( $item['slot_id'] == $slot_id ) {
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
		
		// Add AI session metadata for profit calculation
		if ( ! empty( $session_data ) ) {
			foreach ( $session_data as $session ) {
				$session_metadata = array(
					'session_id' => $session['session_id'] ?? '',
					'therapist_id' => $session['therapist_id'] ?? '',
					'patient_id' => $user_id,
					'session_type' => $session['session_type'] ?? 'first',
					'session_amount' => $session['price'] ?? 0
				);
				snks_add_ai_session_metadata( $order->get_id(), $session_metadata );
			}
		}
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
			$diagnosis->name = $locale === 'ar' ? ($name_ar ?: $name_en) : $name_en;
			$diagnosis->description = $locale === 'ar' ? ($description_ar ?: $description_en) : $description_en;
			
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
		$diagnosis->name = $locale === 'ar' ? ($name_ar ?: $name_en) : $name_en;
		$diagnosis->description = $locale === 'ar' ? ($description_ar ?: $description_en) : $description_en;
		
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
		// Get OpenAI settings
		$api_key = get_option( 'snks_ai_chatgpt_api_key' );
		$model = get_option( 'snks_ai_chatgpt_model', 'gpt-3.5-turbo' );
		$system_prompt = get_option( 'snks_ai_chatgpt_prompt' );
		$max_tokens = get_option( 'snks_ai_chatgpt_max_tokens', 1000 );
		$temperature = get_option( 'snks_ai_chatgpt_temperature', 0.7 );
		$min_questions = get_option( 'snks_ai_chatgpt_min_questions', 5 );
		$max_questions = get_option( 'snks_ai_chatgpt_max_questions', 10 );
		
		if ( ! $api_key ) {
			return new WP_Error( 'no_api_key', 'OpenAI API key not configured' );
		}
		
		// Determine conversation language based on user input and locale
		$locale = sanitize_text_field( $_POST['locale'] ?? 'en' );
		$conversation_language = $this->detect_language( $message );
		$is_arabic = $conversation_language === 'arabic' || $locale === 'ar';
		
		// Get available diagnoses with proper language support
		global $wpdb;
		$diagnoses = $wpdb->get_results( "SELECT id, name, name_en, name_ar, description FROM {$wpdb->prefix}snks_diagnoses ORDER BY name" );
		$diagnosis_list = array();
		foreach ( $diagnoses as $diagnosis ) {
			// Use appropriate name based on language
			if ( $is_arabic ) {
				$diagnosis_name = !empty($diagnosis->name_ar) ? $diagnosis->name_ar : $diagnosis->name;
			} else {
				$diagnosis_name = !empty($diagnosis->name_en) ? $diagnosis->name_en : $diagnosis->name;
			}
			$diagnosis_list[] = $diagnosis_name . ' (ID: ' . $diagnosis->id . ')';
		}
		

		
		// Count questions asked by AI so far
		$ai_questions_count = 0;
		foreach ( $conversation_history as $msg ) {
			if ( $msg['role'] === 'assistant' && $this->is_question( $msg['content'] ) ) {
				$ai_questions_count++;
			}
		}
		
		// Build conversation messages
		$messages = array();
		
		// Add system prompt with forced JSON structure and language/question limits
		$language_instruction = $is_arabic ? 
			"IMPORTANT: Respond ONLY in Arabic language. Use Arabic for all communication, reasoning, and explanations. Never mix languages." : 
			"IMPORTANT: Respond ONLY in English language. Use English for all communication, reasoning, and explanations. Never mix languages.";
		
		$question_limit_instruction = "QUESTION GUIDELINES: You have asked {$ai_questions_count} questions so far. ";
		if ( $ai_questions_count >= $max_questions ) {
			$question_limit_instruction .= "You have reached the maximum questions limit. You must now provide a diagnosis.";
		} elseif ( $ai_questions_count < $min_questions ) {
			$question_limit_instruction .= "You must ask at least {$min_questions} questions total. You need to ask " . ($min_questions - $ai_questions_count) . " more questions before you can provide a diagnosis. DO NOT provide diagnosis yet.";
		} else {
			$question_limit_instruction .= "You have asked enough questions. You can now provide a diagnosis if you have sufficient information.";
		}
		
		$enhanced_system_prompt = "You are a compassionate mental health assistant conducting a diagnostic conversation. " . $language_instruction . "\n\n" . $question_limit_instruction . "\n\nAvailable diagnoses: " . implode( ', ', $diagnosis_list ) . "\n\nCRITICAL CONVERSATION RULES:\n- Read the conversation history carefully and respond contextually\n- Acknowledge what the patient has shared and ask relevant follow-up questions\n- NEVER repeat the same question - always ask a NEW, DIFFERENT question\n- If the patient says 'no' or 'لا', ask about something else\n- You MUST ask at least {$min_questions} questions before providing a diagnosis\n- Be empathetic and supportive in your tone\n- Ask about specific symptoms, duration, severity, and impact on daily life\n- Gather information about sleep, mood, relationships, work, and other relevant areas\n- Ask different types of questions to gather comprehensive information\n- If you've already asked about daily life impact, ask about something else like sleep, relationships, or work\n- IMPORTANT: If this is the first or second question, ask the patient about their country/region to better understand their cultural context and speak in their local language\n\nRESPONSE FORMAT:\nYou must respond with valid JSON in this exact structure:\n{\n  \"diagnosis\": \"diagnosis_name_from_list\",\n  \"confidence\": \"low|medium|high\",\n  \"reasoning\": \"your conversational response to the patient\",\n  \"status\": \"complete|incomplete\",\n  \"question_count\": " . ($ai_questions_count + 1) . "\n}\n\n- Only choose diagnoses from the provided list\n- Use 'incomplete' status when you need more information or haven't asked enough questions\n- Use 'complete' status ONLY when you have asked enough questions AND have sufficient information\n- The 'reasoning' field should contain your actual conversational response to the patient\n- Ask specific, contextual questions based on what they've shared\n- Show empathy and understanding of their situation\n- Do NOT provide diagnosis until you have asked at least {$min_questions} questions\n- NEVER repeat the same question - always ask something new\n- For early questions, ask about their country/region to provide culturally appropriate responses";
		

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
		
		// Call OpenAI API with forced JSON response format
		$data = array(
			'model' => $model,
			'messages' => $messages,
			'max_tokens' => intval( $max_tokens ),
			'temperature' => floatval( $temperature ),
			'response_format' => array( 'type' => 'json_object' )
		);
		
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $data ),
			'timeout' => 30
		) );
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', 'OpenAI API error: ' . $response->get_error_message() );
		}
		
		$body = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );
		
		if ( ! isset( $result['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response from OpenAI API' );
		}
		
		$ai_response = $result['choices'][0]['message']['content'];
		
		// Parse the JSON response
		$response_data = json_decode( $ai_response, true );
		
		if ( ! $response_data || ! isset( $response_data['status'] ) ) {
			// Fallback for invalid JSON - provide a contextual response based on conversation
			$fallback_message = $this->generate_contextual_fallback( $message, $conversation_history, $is_arabic );
			
			return array(
				'message' => $fallback_message,
				'diagnosis' => array(
					'completed' => false
				)
			);
		}
		
		// Validate diagnosis is in our list
		$diagnosis_id = null;
		$diagnosis_name = '';
		$diagnosis_description = '';
		
		if ( $response_data['status'] === 'complete' && ! empty( $response_data['diagnosis'] ) ) {
			foreach ( $diagnoses as $diagnosis ) {
				$arabic_name = !empty($diagnosis->name_ar) ? $diagnosis->name_ar : $diagnosis->name;
				$english_name = !empty($diagnosis->name_en) ? $diagnosis->name_en : $diagnosis->name;
				
				$arabic_match = stripos( $arabic_name, $response_data['diagnosis'] ) !== false;
				$english_match = stripos( $english_name, $response_data['diagnosis'] ) !== false;
				
				if ( $arabic_match || $english_match ) {
					$diagnosis_id = $diagnosis->id;
					// Use appropriate name based on language
					if ( $is_arabic ) {
						$diagnosis_name = !empty($diagnosis->name_ar) ? $diagnosis->name_ar : $diagnosis->name;
					} else {
						$diagnosis_name = !empty($diagnosis->name_en) ? $diagnosis->name_en : $diagnosis->name;
					}
					$diagnosis_description = $diagnosis->description;
					break;
				}
			}
		}
		

		
		// Format response message
		if ( $response_data['status'] === 'complete' && $diagnosis_id ) {
			$confidence_text = '';
			if ( isset( $response_data['confidence'] ) ) {
				if ( $is_arabic ) {
					switch ( $response_data['confidence'] ) {
						case 'high':
							$confidence_text = ' (ثقة عالية)';
							break;
						case 'medium':
							$confidence_text = ' (ثقة متوسطة)';
							break;
						case 'low':
							$confidence_text = ' (ثقة منخفضة)';
							break;
					}
				} else {
					switch ( $response_data['confidence'] ) {
						case 'high':
							$confidence_text = ' (high confidence)';
							break;
						case 'medium':
							$confidence_text = ' (medium confidence)';
							break;
						case 'low':
							$confidence_text = ' (low confidence)';
							break;
					}
				}
			}
			
			if ( $is_arabic ) {
				$message = "بناءً على محادثتنا، أعتقد أنك قد تعاني من **{$diagnosis_name}**{$confidence_text}.\n\n";
				if ( isset( $response_data['reasoning'] ) ) {
					$message .= "**المنطق:** " . $response_data['reasoning'] . "\n\n";
				}
				$message .= "**الوصف:** " . $diagnosis_description . "\n\n";
				$message .= "لقد أكملت التشخيص ويمكنني الآن مساعدتك في العثور على معالجين متخصصين في هذا المجال.";
			} else {
				$message = "Based on our conversation, I believe you may be experiencing **{$diagnosis_name}**{$confidence_text}.\n\n";
				if ( isset( $response_data['reasoning'] ) ) {
					$message .= "**Reasoning:** " . $response_data['reasoning'] . "\n\n";
				}
				$message .= "**Description:** " . $diagnosis_description . "\n\n";
				$message .= "I've completed the diagnosis and can now help you find therapists who specialize in this area.";
			}
			
			return array(
				'message' => $message,
				'diagnosis' => array(
					'completed' => true,
					'id' => $diagnosis_id,
					'title' => $diagnosis_name,
					'description' => $diagnosis_description,
					'confidence' => $response_data['confidence'] ?? 'medium',
					'reasoning' => $response_data['reasoning'] ?? ''
				)
			);
		} else {
			// Continue conversation - use reasoning if available, otherwise provide a contextual response
			$message = '';
			if ( isset( $response_data['reasoning'] ) && ! empty( trim( $response_data['reasoning'] ) ) ) {
				$message = $response_data['reasoning'];
			} else {
				// If reasoning is empty or just whitespace, provide a contextual follow-up question
				$message = $this->generate_contextual_fallback( $message, $conversation_history, $is_arabic );
			}
			
			return array(
				'message' => $message,
				'diagnosis' => array(
					'reasoning' => $response_data['reasoning'] ?? '',
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
		
		// Ensure helper functions are available
		if ( ! function_exists( 'snks_get_appointment_change_terms' ) ) {
			require_once SNKS_DIR . 'functions/helpers.php';
		}
		
		// Fallback function if still not available
		if ( ! function_exists( 'snks_get_appointment_change_terms' ) ) {
			function snks_get_appointment_change_terms( $locale = null ) {
				if ( ! $locale ) {
					$locale = snks_get_current_language();
				}
				
				if ( $locale === 'ar' ) {
					return get_option( 'snks_ai_appointment_change_terms_ar', 'يمكنك تغيير موعدك مرة واحدة فقط قبل الموعد الحالي بـ 24 ساعة فقط، وليس بعد ذلك. تغيير الموعد مجاني.' );
				} else {
					return get_option( 'snks_ai_appointment_change_terms_en', 'You can only change your appointment once before the current appointment by 24 hours only, not after. Change appointment is free.' );
				}
			}
		}
		
		$settings = array(
			'bilingual_enabled' => snks_is_bilingual_enabled(),
			'default_language' => snks_get_default_language(),
			'site_title' => snks_get_site_title( $current_language ),
			'site_description' => snks_get_site_description( $current_language ),
			'ratings_enabled' => get_option( 'snks_ai_ratings_enabled', '1' ) === '1',
			'diagnosis_search_by_name' => get_option( 'snks_ai_diagnosis_search_by_name', '0' ) === '1',
			'diagnosis_results_limit' => snks_get_diagnosis_results_limit(),
			'show_more_button_enabled' => snks_get_show_more_button_enabled(),
			'appointment_change_terms' => snks_get_appointment_change_terms( $current_language ),
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
		
		// Ensure helper functions are available
		if ( ! function_exists( 'snks_get_appointment_change_terms' ) ) {
			require_once SNKS_DIR . 'functions/helpers.php';
		}
		
		// Fallback function if still not available
		if ( ! function_exists( 'snks_get_appointment_change_terms' ) ) {
			function snks_get_appointment_change_terms( $locale = null ) {
				if ( ! $locale ) {
					$locale = snks_get_current_language();
				}
				
				if ( $locale === 'ar' ) {
					return get_option( 'snks_ai_appointment_change_terms_ar', 'يمكنك تغيير موعدك مرة واحدة فقط قبل الموعد الحالي بـ 24 ساعة فقط، وليس بعد ذلك. تغيير الموعد مجاني.' );
				} else {
					return get_option( 'snks_ai_appointment_change_terms_en', 'You can only change your appointment once before the current appointment by 24 hours only, not after. Change appointment is free.' );
				}
			}
		}
		
		$settings = array(
			'bilingual_enabled' => snks_is_bilingual_enabled(),
			'default_language' => snks_get_default_language(),
			'site_title' => snks_get_site_title( $current_language ),
			'site_description' => snks_get_site_description( $current_language ),
			'ratings_enabled' => get_option( 'snks_ai_ratings_enabled', '1' ) === '1',
			'diagnosis_search_by_name' => get_option( 'snks_ai_diagnosis_search_by_name', '0' ) === '1',
			'diagnosis_results_limit' => snks_get_diagnosis_results_limit(),
			'show_more_button_enabled' => snks_get_show_more_button_enabled(),
			'appointment_change_terms' => snks_get_appointment_change_terms( $current_language ),
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
			$this->send_error('Missing therapist_id or date', 400);
		}
		
		global $wpdb;
		

		
		// Query the existing timetable system for available slots
		// Only include slots that are actually available (not booked)
		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d 
			 AND DATE(date_time) = %s 
			 AND session_status = 'waiting' 
			 AND order_id = 0
			 AND attendance_type = 'online'
			 AND (client_id = 0 OR client_id IS NULL)
			 AND (settings NOT LIKE '%ai_booking:booked%' OR settings = '' OR settings IS NULL)
			 AND (settings NOT LIKE '%ai_booking:rescheduled_old_slot%' OR settings = '' OR settings IS NULL)
			 ORDER BY starts ASC",
			$therapist_id, $date
		);
		
		$available_slots = $wpdb->get_results($query);
		
		$formatted_slots = [];
		foreach ($available_slots as $slot) {
			$formatted_slots[] = [
				'time' => $slot->starts,
				'formatted_time' => date('g:i A', strtotime($slot->starts)),
				'slot_id' => $slot->ID,
				'available' => true
			];
		}
		

		
		$this->send_success([
			'available_slots' => $formatted_slots,
			'therapist_id' => $therapist_id,
			'date' => $date
		]);
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
		
		// Check if user has appointments from different therapists in cart
		$existing_cart_query = $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE client_id = %d AND session_status = 'waiting' AND settings LIKE '%ai_booking:in_cart%' 
			 AND user_id != %d LIMIT 1",
			$user_id, $slot->user_id
		);
		
		$different_therapist = $wpdb->get_var($existing_cart_query);
		
		if ($different_therapist) {
			return new WP_REST_Response([
				'success' => false,
				'requires_confirmation' => true,
				'message' => 'different_therapist_confirmation'
			], 200);
		}
		
		// Add to cart by updating the slot with AI identifier and timestamp
		$cart_timestamp = current_time('mysql');
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'client_id' => $user_id,
				'session_status' => 'waiting', // Keep as waiting until checkout
				'settings' => 'ai_booking:in_cart:' . $cart_timestamp // Mark as AI booking with timestamp
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
			'message' => 'Appointment added to cart successfully. The appointment will be automatically removed after half an hour if payment not completed.',
			'slot_id' => $slot_id,
			'expires_at' => date('Y-m-d H:i:s', strtotime($cart_timestamp . ' +30 minutes'))
		], 200);
	}

	/**
	 * Add appointment to cart with confirmation for different therapist
	 */
	public function add_appointment_to_cart_with_confirmation($request) {
		$user_id = $request->get_param('user_id');
		$slot_id = $request->get_param('slot_id');
		$confirm = $request->get_param('confirm');
		
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
		
		// If user confirmed, clear existing cart first
		if ($confirm === 'true') {
			// Clear existing cart items
			$wpdb->update(
				$wpdb->prefix . 'snks_provider_timetable',
				[
					'client_id' => 0,
					'session_status' => 'waiting',
					'settings' => ''
				],
				[
					'client_id' => $user_id,
					'session_status' => 'waiting'
				],
				['%d', '%s', '%s'],
				['%d', '%s']
			);
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
		
		// Add to cart by updating the slot with AI identifier and timestamp
		$cart_timestamp = current_time('mysql');
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'client_id' => $user_id,
				'session_status' => 'waiting', // Keep as waiting until checkout
				'settings' => 'ai_booking:in_cart:' . $cart_timestamp // Mark as AI booking with timestamp
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
			'message' => 'Appointment added to cart successfully. The appointment will be automatically removed after half an hour if payment not completed.',
			'slot_id' => $slot_id,
			'expires_at' => date('Y-m-d H:i:s', strtotime($cart_timestamp . ' +30 minutes'))
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
			 AND t.settings LIKE '%ai_booking:in_cart%'
			 ORDER BY t.date_time ASC",
			$user_id
		);
		
		$cart_items = $wpdb->get_results($cart_query);
		
		// Filter out expired cart items (older than 30 minutes)
		$current_time = current_time('mysql');
		$expired_slot_ids = array();
		$valid_cart_items = array();
		
		foreach ($cart_items as $item) {
			// Extract timestamp from settings field
			if (preg_match('/ai_booking:in_cart:(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $item->settings, $matches)) {
				$cart_timestamp = $matches[1];
				$cart_time = strtotime($cart_timestamp);
				$current_time_stamp = strtotime($current_time);
				
				// Check if item is older than 30 minutes (1800 seconds)
				if (($current_time_stamp - $cart_time) > 1800) {
					$expired_slot_ids[] = $item->ID;
					continue; // Skip this expired item
				}
			}
			
			$valid_cart_items[] = $item;
		}
		
		// Clean up expired items from database
		if (!empty($expired_slot_ids)) {
			$placeholders = implode(',', array_fill(0, count($expired_slot_ids), '%d'));
			$cleanup_query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}snks_provider_timetable 
				 SET client_id = 0, settings = '' 
				 WHERE ID IN ($placeholders) AND settings LIKE '%ai_booking:in_cart%'",
				$expired_slot_ids
			);
			$wpdb->query($cleanup_query);
		}
		
		$total_price = 0;
		foreach ($valid_cart_items as $item) {
			$total_price += 200.00; // Default price
			// Add therapist image URL
			if ($item->profile_image) {
				$item->therapist_image_url = wp_get_attachment_image_url($item->profile_image, 'thumbnail');
			}
		}
		
		return new WP_REST_Response([
			'success' => true,
			'data' => $valid_cart_items,
			'total_price' => $total_price,
			'item_count' => count($valid_cart_items)
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
			"SELECT t.*, t.user_id as therapist_id, ta.name as therapist_name, ta.name_en as therapist_name_en, ta.profile_image
			 FROM {$wpdb->prefix}snks_provider_timetable t
			 LEFT JOIN {$wpdb->prefix}therapist_applications ta ON t.user_id = ta.user_id
			 WHERE t.client_id = %d AND (t.session_status = 'open' OR t.session_status = 'confirmed') 
			 AND t.settings LIKE '%ai_booking%'
			 ORDER BY t.date_time ASC",
			$user_id
		);
		
		$appointments = $wpdb->get_results($query);
		
		foreach ($appointments as $appointment) {
			if ($appointment->profile_image) {
				$appointment->therapist_image_url = wp_get_attachment_image_url($appointment->profile_image, 'thumbnail');
			}
			
			// Map session_status to status for frontend compatibility
			$appointment->status = $appointment->session_status;
			
			// Keep the original date_time field for frontend
			$appointment->date_time = $appointment->date_time;
			
			// Format date and time for frontend - extract only the date part
			$appointment->date = date('Y-m-d', strtotime($appointment->date_time));
			$appointment->time = $appointment->starts;
		}
		
		return new WP_REST_Response(['success' => true, 'data' => $appointments], 200);
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
	 * Cancel AI appointment with 24-hour validation
	 */
	private function cancel_ai_appointment($appointment_id) {
		// Ensure helper functions are available
		if ( ! function_exists( 'snks_can_edit_ai_appointment' ) ) {
			require_once SNKS_DIR . 'functions/helpers.php';
		}
		
		// Fallback function if still not available
		if ( ! function_exists( 'snks_can_edit_ai_appointment' ) ) {
			function snks_can_edit_ai_appointment( $appointment ) {
				if ( ! $appointment || ! isset( $appointment->date_time ) ) {
					return false;
				}
				
				// Check if this is an AI booking
				if ( strpos( $appointment->settings, 'ai_booking' ) === false ) {
					return true; // Not an AI booking, use regular validation
				}
				
				$appointment_time = strtotime( $appointment->date_time );
				$current_time = current_time( 'timestamp' );
				
				// AI appointments can be edited up to 24 hours before (86400 seconds = 24 hours)
				return ( $appointment_time - $current_time ) > 86400;
			}
		}
		
		if ( ! function_exists( 'snks_get_appointment_time_remaining' ) ) {
			function snks_get_appointment_time_remaining( $appointment ) {
				if ( ! $appointment || ! isset( $appointment->date_time ) ) {
					return 0;
				}
				
				$appointment_time = strtotime( $appointment->date_time );
				$current_time = current_time( 'timestamp' );
				
				return $appointment_time - $current_time;
			}
		}
		
		$user_id = $this->verify_jwt_token();
		
		if (!$user_id) {
			$this->send_error('Authentication required', 401);
		}
		
		global $wpdb;
		
		// Get the appointment
		$appointment = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND client_id = %d AND settings LIKE '%ai_booking%'",
			$appointment_id, $user_id
		));
		
		if (!$appointment) {
			$this->send_error('Appointment not found or access denied', 404);
		}
		
		// Check if appointment can be cancelled (24 hours before)
		if (!snks_can_edit_ai_appointment($appointment)) {
			$time_remaining = snks_get_appointment_time_remaining($appointment);
			$hours_remaining = round($time_remaining / 3600, 1);
			
			$this->send_error(
				sprintf(
					'Appointment cannot be cancelled. It is less than 24 hours away (%.1f hours remaining).',
					$hours_remaining
				),
				400
			);
		}
		
		// Cancel the appointment
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			['session_status' => 'cancelled'],
			['ID' => $appointment_id],
			['%s'],
			['%d']
		);
		
		if ($result === false) {
			$this->send_error('Failed to cancel appointment', 500);
		}
		
		$this->send_success([
			'message' => 'Appointment cancelled successfully',
			'appointment_id' => $appointment_id
		]);
	}
	
	/**
	 * Reschedule AI appointment with 24-hour validation
	 */
	private function reschedule_ai_appointment($appointment_id) {
		// Ensure helper functions are available
		if ( ! function_exists( 'snks_can_edit_ai_appointment' ) ) {
			require_once SNKS_DIR . 'functions/helpers.php';
		}
		
		// Fallback function if still not available
		if ( ! function_exists( 'snks_can_edit_ai_appointment' ) ) {
			function snks_can_edit_ai_appointment( $appointment ) {
				if ( ! $appointment || ! isset( $appointment->date_time ) ) {
					return false;
				}
				
				// Check if this is an AI booking
				if ( strpos( $appointment->settings, 'ai_booking' ) === false ) {
					return true; // Not an AI booking, use regular validation
				}
				
				$appointment_time = strtotime( $appointment->date_time );
				$current_time = current_time( 'timestamp' );
				
				// Can modify up to 24 hours before (86400 seconds = 24 hours)
				return ( $appointment_time - $current_time ) > 86400;
			}
		}
		
		if ( ! function_exists( 'snks_get_appointment_time_remaining' ) ) {
			function snks_get_appointment_time_remaining( $appointment ) {
				if ( ! $appointment || ! isset( $appointment->date_time ) ) {
					return 0;
				}
				
				$appointment_time = strtotime( $appointment->date_time );
				$current_time = current_time( 'timestamp' );
				
				return $appointment_time - $current_time;
			}
		}
		
		$user_id = $this->verify_jwt_token();
		
		if (!$user_id) {
			$this->send_error('Authentication required', 401);
		}
		
		// Get request data
		$input = json_decode(file_get_contents('php://input'), true);
		$new_appointment_id = intval($input['new_appointment_id'] ?? 0);
		
		if (!$new_appointment_id) {
			$this->send_error('New appointment ID is required', 400);
		}
		
		global $wpdb;
		
		// Get the current appointment
		$current_appointment = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND client_id = %d AND settings LIKE '%ai_booking%'",
			$appointment_id, $user_id
		));
		
		if (!$current_appointment) {
			$this->send_error('Current appointment not found or access denied', 404);
		}
		
		// Check if appointment can be rescheduled (24 hours before)
		if (!snks_can_edit_ai_appointment($current_appointment)) {
			$time_remaining = snks_get_appointment_time_remaining($current_appointment);
			$hours_remaining = round($time_remaining / 3600, 1);
			
			$this->send_error(
				sprintf(
					'Appointment cannot be rescheduled. It is less than 24 hours away (%.1f hours remaining).',
					$hours_remaining
				),
				400
			);
		}
		
		// Check if this appointment has already been rescheduled (prevent multiple reschedules)
		if (strpos($current_appointment->settings, 'ai_booking:rescheduled') !== false) {
			$this->send_error(
				'This appointment has already been rescheduled once. Multiple reschedules are not allowed.',
				400
			);
		}
		
		// Get the new appointment slot
		$new_appointment = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND session_status = 'waiting'",
			$new_appointment_id
		));
		
		if (!$new_appointment) {
			$this->send_error('New appointment slot not found or not available', 404);
		}
		
		$wpdb->query('START TRANSACTION');
		
		try {
			// Make the old slot available again by clearing client_id and order_id
			$free_old_slot_result = $wpdb->update(
				$wpdb->prefix . 'snks_provider_timetable',
				[
					'client_id' => 0,
					'order_id' => 0,
					'session_status' => 'waiting',
					'settings' => 'ai_booking:rescheduled_old_slot'
				],
				['ID' => $appointment_id],
				['%d', '%d', '%s', '%s'],
				['%d']
			);
			
			if ($free_old_slot_result === false) {
				throw new Exception('Failed to free old appointment slot');
			}
			
			// Book the new appointment with the transferred order data
			$book_result = $wpdb->update(
				$wpdb->prefix . 'snks_provider_timetable',
				[
					'session_status' => 'open',
					'client_id' => $user_id,
					'order_id' => $current_appointment->order_id,
					'settings' => 'ai_booking:rescheduled'
				],
				['ID' => $new_appointment_id],
				['%s', '%d', '%d', '%s'],
				['%d']
			);
			
			if ($book_result === false) {
				throw new Exception('Failed to book new appointment');
			}
			
			$wpdb->query('COMMIT');
			
			$this->send_success([
				'message' => 'Appointment rescheduled successfully',
				'old_appointment_id' => $appointment_id,
				'new_appointment_id' => $new_appointment_id
			]);
			
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			$this->send_error('Failed to reschedule appointment: ' . $e->getMessage(), 500);
		}
	}

	/**
	 * Get single AI appointment
	 */
	private function get_ai_single_appointment($appointment_id) {
		$user_id = $this->verify_jwt_token();
		
		if (!$user_id) {
			$this->send_error('Authentication required', 401);
		}
		
		global $wpdb;
		
		// Get the appointment
		$appointment = $wpdb->get_row($wpdb->prepare(
			"SELECT t.*, t.user_id as therapist_id, ta.name as therapist_name, ta.name_en as therapist_name_en, ta.profile_image
			 FROM {$wpdb->prefix}snks_provider_timetable t
			 LEFT JOIN {$wpdb->prefix}therapist_applications ta ON t.user_id = ta.user_id
			 WHERE t.ID = %d AND t.client_id = %d AND t.settings LIKE '%ai_booking%'",
			$appointment_id, $user_id
		));
		
		if (!$appointment) {
			$this->send_error('Appointment not found or access denied', 404);
		}
		
		// Format the appointment data
		if ($appointment->profile_image) {
			$appointment->therapist_image_url = wp_get_attachment_image_url($appointment->profile_image, 'thumbnail');
		}
		
		// Map session_status to status for frontend compatibility
		$appointment->status = $appointment->session_status;
		
		// Keep the original date_time field for frontend
		$appointment->date_time = $appointment->date_time;
		
		// Format date and time for frontend
		$appointment->date = date('Y-m-d', strtotime($appointment->date_time));
		$appointment->time = $appointment->starts;
		
		$this->send_success(['data' => $appointment]);
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
		
		// Debug logging

		
		// Query for dates that have available slots in the next 30 days
		// Only include dates where there are actually available slots (not booked)
		$query = $wpdb->prepare(
			"SELECT DISTINCT DATE(date_time) as date
			 FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE user_id = %d 
			 AND DATE(date_time) >= CURDATE()
			 AND DATE(date_time) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
			 AND session_status = 'waiting' 
			 AND order_id = 0
			 AND attendance_type = 'online'
			 AND (client_id = 0 OR client_id IS NULL)
			 AND (settings NOT LIKE '%ai_booking:booked%' OR settings = '' OR settings IS NULL)
			 AND (settings NOT LIKE '%ai_booking:rescheduled_old_slot%' OR settings = '' OR settings IS NULL)
			 ORDER BY DATE(date_time) ASC",
			$therapist_id
		);
		

		
		$available_dates = $wpdb->get_results($query);
		

		
		$formatted_dates = [];
		foreach ($available_dates as $date_row) {
			$formatted_dates[] = [
				'date' => $date_row->date,
				'isAvailable' => true,
				'isSelected' => false
			];
		}
		

		
		$this->send_success([
			'available_dates' => $formatted_dates,
			'therapist_id' => $therapist_id
		]);
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
	 * Get AI session details
	 */
	public function get_ai_session($request) {
		$session_id = $request->get_param('id');
		$user_id = $this->verify_jwt_token();
		
		if (!$session_id || !$user_id) {
			return new WP_REST_Response(['error' => 'Missing session ID or user authentication'], 400);
		}
		
		global $wpdb;
		
		// Get session details
		$session = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND (client_id = %d OR user_id = %d) AND settings LIKE '%ai_booking%'",
			$session_id, $user_id, $user_id
		));
		
		if (!$session) {
			return new WP_REST_Response(['error' => 'Session not found or access denied'], 404);
		}
		
		// Get therapist details
		$therapist = get_userdata($session->user_id);
		$therapist_name = get_user_meta($session->user_id, 'ai_display_name', true);
		if (!$therapist_name) {
			$therapist_name = get_user_meta($session->user_id, 'billing_first_name', true) . ' ' . get_user_meta($session->user_id, 'billing_last_name', true);
		}
		
		// Get therapist image
		$therapist_image_id = get_user_meta($session->user_id, 'ai_profile_image', true);
		$therapist_image_url = '';
		if ($therapist_image_id) {
			$therapist_image_url = wp_get_attachment_image_url($therapist_image_id, 'thumbnail');
		}
		
		// Check if therapist has joined
		$therapist_joined = snks_doctor_has_joined($session_id, $session->user_id);
		
		$session_data = array(
			'ID' => $session->ID,
			'therapist_id' => $session->user_id,
			'therapist_name' => $therapist_name,
			'therapist_image_url' => $therapist_image_url,
			'client_id' => $session->client_id,
			'date_time' => $session->date_time,
			'starts' => $session->starts,
			'ends' => $session->ends,
			'period' => $session->period,
			'session_status' => $session->session_status,
			'attendance_type' => $session->attendance_type,
			'therapist_joined' => $therapist_joined,
			'order_id' => $session->order_id,
			'settings' => $session->settings
		);
		
		return new WP_REST_Response([
			'success' => true,
			'data' => $session_data
		]);
	}
	
	/**
	 * Set therapist joined status for AI session
	 */
	public function set_therapist_joined($request) {
		$session_id = $request->get_param('id');
		$user_id = $this->verify_jwt_token();
		
		if (!$session_id || !$user_id) {
			return new WP_REST_Response(['error' => 'Missing session ID or user authentication'], 400);
		}
		
		global $wpdb;
		
		// Get session details
		$session = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND user_id = %d AND settings LIKE '%ai_booking%'",
			$session_id, $user_id
		));
		
		if (!$session) {
			return new WP_REST_Response(['error' => 'Session not found or access denied'], 404);
		}
		
		// Only the therapist can set their joined status
		if ($session->user_id != $user_id) {
			return new WP_REST_Response(['error' => 'Only the therapist can set joined status'], 403);
		}
		
		// Set the transient to mark therapist as joined
		$transient_key = "doctor_has_joined_{$session_id}_{$user_id}";
		$result = set_transient($transient_key, '1', 3600); // Expires in 1 hour
		
		if ($result) {
			return new WP_REST_Response([
				'success' => true,
				'message' => 'Therapist joined status set successfully',
				'session_id' => $session_id,
				'therapist_id' => $user_id
			], 200);
		} else {
			return new WP_REST_Response(['error' => 'Failed to set therapist joined status'], 500);
		}
	}

	/**
	 * End AI session
	 */
	public function end_ai_session($request) {
		$session_id = $request->get_param('id');
		$user_id = $this->verify_jwt_token();
		
		if (!$session_id || !$user_id) {
			return new WP_REST_Response(['error' => 'Missing session ID or user authentication'], 400);
		}
		
		global $wpdb;
		
		// Get session details
		$session = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
			 WHERE ID = %d AND user_id = %d AND settings LIKE '%ai_booking%'",
			$session_id, $user_id
		));
		
		if (!$session) {
			return new WP_REST_Response(['error' => 'Session not found or access denied'], 404);
		}
		
		// Only the therapist can end the session
		if ($session->user_id != $user_id) {
			return new WP_REST_Response(['error' => 'Only the therapist can end this session'], 403);
		}
		
		// Get attendance from request (default to 'yes' since we're not tracking attendance)
		$input = json_decode(file_get_contents('php://input'), true);
		$attendance = $input['attendance'] ?? 'yes'; // Default to 'yes' for simplicity
		
		// Update session status
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'session_status' => 'completed',
				'settings' => 'ai_booking:completed'
			],
			['ID' => $session_id],
			['%s', '%s'],
			['%d']
		);
		
		if ($result === false) {
			return new WP_REST_Response(['error' => 'Failed to end session'], 500);
		}
		
		// Add session action record with the specified attendance
		snks_insert_session_actions($session_id, $session->client_id, $attendance);
		
		// Clear therapist joined transient
		delete_transient("doctor_has_joined_{$session_id}_{$user_id}");
		
		// Check if this is an AI session and trigger profit calculation
		if (snks_is_ai_session($session_id)) {
			$profit_result = snks_execute_ai_profit_transfer($session_id);
			
			if ($profit_result['success']) {
				// Send notification
				snks_ai_session_completion_notification($session_id, $profit_result);
				
				return new WP_REST_Response([
					'success' => true,
					'message' => 'Session ended and profit transferred successfully',
					'transaction_id' => $profit_result['transaction_id'],
					'profit_amount' => $profit_result['profit_amount']
				]);
			} else {
				return new WP_REST_Response([
					'success' => true,
					'message' => 'Session ended but profit transfer failed: ' . $profit_result['message'],
					'profit_error' => $profit_result['message']
				]);
			}
		}
		
		return new WP_REST_Response([
			'success' => true,
			'message' => 'Session ended successfully'
		]);
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
 * Schedule cart cleanup cron job
 */
function snks_schedule_cart_cleanup() {
	if ( ! wp_next_scheduled( 'jalsah_ai_cleanup_expired_cart' ) ) {
		wp_schedule_event( time(), 'hourly', 'jalsah_ai_cleanup_expired_cart' );
	}
}

/**
 * Clean up expired cart items (older than 30 minutes)
 */
function snks_cleanup_expired_cart_items() {
	global $wpdb;
	
	$current_time = current_time('mysql');
	$expired_time = date('Y-m-d H:i:s', strtotime($current_time . ' -30 minutes'));
	
	// Find expired cart items
	$expired_query = $wpdb->prepare(
		"SELECT ID FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE settings LIKE %s AND settings LIKE %s",
		'%ai_booking:in_cart%',
		'%' . $expired_time . '%'
	);
	
	$expired_items = $wpdb->get_results($expired_query);
	
	if (!empty($expired_items)) {
		$expired_ids = array_map(function($item) { return $item->ID; }, $expired_items);
		$placeholders = implode(',', array_fill(0, count($expired_ids), '%d'));
		
		// Clean up expired items
		$cleanup_query = $wpdb->prepare(
			"UPDATE {$wpdb->prefix}snks_provider_timetable 
			 SET client_id = 0, settings = '' 
			 WHERE ID IN ($placeholders) AND settings LIKE '%ai_booking:in_cart%'",
			$expired_ids
		);
		
		$result = $wpdb->query($cleanup_query);
		
		// Log cleanup activity
		error_log("Jalsah AI: Cleaned up " . count($expired_ids) . " expired cart items");
	}
}

// Hook the functions
add_action( 'init', 'snks_schedule_cart_cleanup' );
add_action( 'jalsah_ai_cleanup_expired_cart', 'snks_cleanup_expired_cart_items' );


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
			$frontend_url = snks_ai_get_primary_frontend_url();
			wp_safe_redirect($frontend_url . '/appointments');
			exit;
		}
	}
}

/**
 * Get the first valid frontend URL for redirects (standalone function)
 */
function snks_ai_get_primary_frontend_url() {
	$frontend_urls = get_option( 'snks_ai_frontend_urls', 'https://jalsah-ai.com' );
	
	// Parse URLs from textarea (one per line)
	$urls = array_filter( array_map( 'trim', explode( "\n", $frontend_urls ) ) );
	
	// Validate and clean URLs
	$valid_origins = array();
	foreach ( $urls as $url ) {
		$url = trim( $url );
		if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$valid_origins[] = $url;
		}
	}
	
	// Return the first valid URL, or fallback
	return $valid_origins[0] ?? 'https://jalsah-ai.com';
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
					$frontend_url = snks_ai_get_primary_frontend_url();
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
	
	$frontend_urls = get_option('snks_ai_frontend_urls');
	if (empty($frontend_urls)) {
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

/**
 * Hook into session completion for automatic profit calculation
 */
function snks_hook_ai_session_completion() {
	// Hook into session status changes
	add_action( 'wp_ajax_end_ai_session', 'snks_handle_ai_session_completion', 10, 1 );
	add_action( 'wp_ajax_nopriv_end_ai_session', 'snks_handle_ai_session_completion', 10, 1 );
	
	// Hook into WooCommerce order status changes for AI orders
	add_action( 'woocommerce_order_status_completed', 'snks_handle_ai_order_completion', 10, 1 );
	add_action( 'woocommerce_order_status_processing', 'snks_handle_ai_order_completion', 10, 1 );
	
	// Hook into session actions table updates
	add_action( 'snks_session_action_updated', 'snks_handle_session_action_update', 10, 2 );
}
add_action( 'init', 'snks_hook_ai_session_completion' );

/**
 * Handle AI session completion from frontend
 */
function snks_handle_ai_session_completion() {
	// This function is called when a session is ended from the frontend
	// The actual profit calculation is handled in the end_ai_session method
	// This is just a hook to ensure we catch all completion events
	
	$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
	
	if ( ! empty( $session_id ) ) {
		// Log the completion event
		error_log( "AI Session Completion Hook: Session ID {$session_id}" );
		
		// Trigger profit calculation if not already processed
		$result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $result['success'] ) {
			error_log( "AI Profit Transfer Success: Session ID {$session_id}, Transaction ID {$result['transaction_id']}" );
		} else {
			error_log( "AI Profit Transfer Failed: Session ID {$session_id}, Reason: {$result['message']}" );
		}
	}
}

/**
 * Handle AI order completion from WooCommerce
 */
function snks_handle_ai_order_completion( $order_id ) {
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		return;
	}
	
	// Check if this is an AI order (support both meta keys)
	$is_ai_session = $order->get_meta( 'is_ai_session' );
	$from_jalsah_ai = $order->get_meta( 'from_jalsah_ai' );
	
	if ( ! $is_ai_session && ! $from_jalsah_ai ) {
		return;
	}
	
	// Get session ID from order meta
	$session_id = $order->get_meta( 'ai_session_id' );
	
	if ( ! empty( $session_id ) ) {
		// Trigger profit calculation
		$result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $result['success'] ) {
			// Log successful profit transfer
			error_log( "AI Profit Transfer: Session ID {$session_id}, Transaction ID {$result['transaction_id']}" );
		}
	}
}

/**
 * Handle session action updates
 */
function snks_handle_session_action_update( $session_id, $action_data ) {
	// Check if this is an AI session
	if ( ! snks_is_ai_session( $session_id ) ) {
		return;
	}
	
	// Check if session status changed to completed
	if ( isset( $action_data['session_status'] ) && $action_data['session_status'] === 'completed' ) {
		// Trigger profit calculation
		$result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $result['success'] ) {
			error_log( "AI Profit Transfer: Session ID {$session_id}, Transaction ID {$result['transaction_id']}" );
		}
	}
}

/**
 * Enhanced end_ai_session method with profit calculation
 */
function snks_enhanced_end_ai_session( $session_id ) {
	global $wpdb;
	
	// First, update session status
	$update_result = $wpdb->update(
		$wpdb->prefix . 'snks_sessions_actions',
		array( 'session_status' => 'completed' ),
		array( 'action_session_id' => $session_id ),
		array( '%s' ),
		array( '%s' )
	);
	
	if ( $update_result === false ) {
		return array(
			'success' => false,
			'message' => 'Failed to update session status'
		);
	}
	
	// Check if this is an AI session and trigger profit calculation
	if ( snks_is_ai_session( $session_id ) ) {
		$profit_result = snks_execute_ai_profit_transfer( $session_id );
		
		if ( $profit_result['success'] ) {
			return array(
				'success' => true,
				'message' => 'Session ended and profit transferred successfully',
				'transaction_id' => $profit_result['transaction_id'],
				'profit_amount' => $profit_result['profit_amount']
			);
		} else {
			return array(
				'success' => true,
				'message' => 'Session ended but profit transfer failed: ' . $profit_result['message'],
				'profit_error' => $profit_result['message']
			);
		}
	}
	
	return array(
		'success' => true,
		'message' => 'Session ended successfully'
	);
}

/**
 * Add AI session metadata to WooCommerce orders
 */
function snks_add_ai_session_metadata( $order_id, $session_data ) {
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		return false;
	}
	
	// Add AI session metadata
	$order->update_meta_data( 'ai_session_id', $session_data['session_id'] ?? '' );
	$order->update_meta_data( 'ai_therapist_id', $session_data['therapist_id'] ?? '' );
	$order->update_meta_data( 'ai_user_id', $session_data['patient_id'] ?? '' );
	$order->update_meta_data( 'ai_session_type', $session_data['session_type'] ?? 'first' );
	$order->update_meta_data( 'ai_session_amount', $session_data['session_amount'] ?? 0 );
	
	$order->save();
	
	return true;
}

/**
 * Get AI session statistics for admin dashboard
 */
function snks_get_ai_session_statistics() {
	global $wpdb;
	
	$sessions_table = $wpdb->prefix . 'snks_sessions_actions';
	$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
	
	// Get total AI sessions
	$total_sessions = $wpdb->get_var( "
		SELECT COUNT(*) FROM $sessions_table 
		WHERE ai_session_type IS NOT NULL
	" );
	
	// Get completed AI sessions
	$completed_sessions = $wpdb->get_var( "
		SELECT COUNT(*) FROM $sessions_table 
		WHERE ai_session_type IS NOT NULL AND session_status = 'completed'
	" );
	
	// Get total profit transferred
	$total_profit = $wpdb->get_var( "
		SELECT SUM(amount) FROM $transactions_table 
		WHERE ai_session_id IS NOT NULL AND transaction_type = 'add'
	" );
	
	// Get today's AI sessions
	$today_sessions = $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(*) FROM $sessions_table 
		WHERE ai_session_type IS NOT NULL AND DATE(created_at) = %s
	", current_time( 'Y-m-d' ) ) );
	
	// Get today's profit
	$today_profit = $wpdb->get_var( $wpdb->prepare( "
		SELECT SUM(amount) FROM $transactions_table 
		WHERE ai_session_id IS NOT NULL AND transaction_type = 'add' 
		AND DATE(transaction_time) = %s
	", current_time( 'Y-m-d' ) ) );
	
	return array(
		'total_sessions' => $total_sessions ?: 0,
		'completed_sessions' => $completed_sessions ?: 0,
		'total_profit' => $total_profit ?: 0,
		'today_sessions' => $today_sessions ?: 0,
		'today_profit' => $today_profit ?: 0,
		'completion_rate' => $total_sessions > 0 ? round( ( $completed_sessions / $total_sessions ) * 100, 2 ) : 0
	);
}

/**
 * Add AI session completion notification
 */
function snks_ai_session_completion_notification( $session_id, $profit_result ) {
	// Get session details
	global $wpdb;
	
	$session_data = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
		$session_id
	), ARRAY_A );
	
	if ( ! $session_data ) {
		return;
	}
	
	// Get therapist and patient details
	$therapist = get_user_by( 'ID', $session_data['therapist_id'] );
	$patient = get_user_by( 'ID', $session_data['patient_id'] );
	
	// Send notification to therapist
	if ( $therapist && $profit_result['success'] ) {
		$notification_message = sprintf(
			'تم إكمال جلسة الذكاء الاصطناعي بنجاح. تم تحويل ربح بقيمة %s ج.م إلى حسابك.',
			number_format( $profit_result['profit_amount'], 2 )
		);
		
		// You can implement your notification system here
		// For example, using WordPress notifications or email
		do_action( 'snks_ai_session_completed_notification', $therapist->ID, $notification_message, $session_id );
	}
	
	// Log the completion
	error_log( sprintf(
		"AI Session Completed: Session ID %s, Therapist %s, Patient %s, Profit %s",
		$session_id,
		$therapist ? $therapist->display_name : 'Unknown',
		$patient ? $patient->display_name : 'Unknown',
		$profit_result['success'] ? number_format( $profit_result['profit_amount'], 2 ) . ' ج.م' : 'Failed'
	) );
}

/**
 * Create session action record for AI appointment
 */
function snks_create_ai_session_action( $appointment_id, $order_id, $therapist_id, $patient_id ) {
	global $wpdb;
	
	// Check if session action already exists
	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}snks_sessions_actions WHERE action_session_id = %s",
		$appointment_id
	) );
	
	if ( $existing ) {
		return $existing;
	}
	
	// Create session action record
	$session_data = array(
		'action_session_id' => $appointment_id,
		'case_id' => $order_id,
		'therapist_id' => $therapist_id,
		'patient_id' => $patient_id,
		'ai_session_type' => NULL, // Will be calculated when profit is transferred
		'session_status' => 'open',
		'attendance' => 'yes', // Use shorter value to avoid VARCHAR(3) limit
		'created_at' => current_time( 'mysql' )
	);
	
	$result = $wpdb->insert(
		$wpdb->prefix . 'snks_sessions_actions',
		$session_data,
		array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
	);
	
	if ( $result ) {
		return $wpdb->insert_id;
	} else {
		return false;
	}
}

/**
 * Hook into appointment creation for AI sessions
 */
function snks_handle_ai_appointment_creation( $appointment_id, $appointment_data ) {
	// Check if this is an AI appointment
	if ( empty( $appointment_data['is_ai_session'] ) || ! $appointment_data['is_ai_session'] ) {
		return;
	}
	
	// Get order ID from appointment
	$order_id = $appointment_data['order_id'] ?? 0;
	$therapist_id = $appointment_data['therapist_id'] ?? 0;
	$patient_id = $appointment_data['patient_id'] ?? 0;
	
	if ( $order_id && $therapist_id && $patient_id ) {
		// Create session action record
		$session_action_id = snks_create_ai_session_action( $appointment_id, $order_id, $therapist_id, $patient_id );
		
		if ( $session_action_id ) {
			// Update order meta with session ID
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_meta_data( 'ai_session_id', $appointment_id );
				$order->update_meta_data( 'ai_therapist_id', $therapist_id );
				$order->update_meta_data( 'ai_user_id', $patient_id );
				$order->save();
			}
		}
	}
}

// Hook into appointment creation
add_action( 'snks_appointment_created', 'snks_handle_ai_appointment_creation', 10, 2 );

/**
 * Global helper function to check if user is an AI patient
 *
 * @param int $user_id User ID to check
 * @return bool True if user registered from Jalsah AI
 */
function snks_is_ai_patient( $user_id ) {
	return SNKS_AI_Integration::is_ai_patient( $user_id );
}

/**
 * Validate Jalsah token and return user ID
 *
 * @param string $token The Jalsah token
 * @return int|false User ID if valid, false otherwise
 */
function snks_validate_jalsah_token( $token ) {
	error_log( '=== TOKEN VALIDATION DEBUG ===' );
	error_log( "Token received: " . ( $token ? substr( $token, 0, 10 ) . '...' : 'NULL' ) );
	
	if ( ! $token ) {
		error_log( 'Token is empty or null' );
		return false;
	}
	
	// Check if this is a JWT token (starts with eyJ)
	if ( strpos( $token, 'eyJ' ) === 0 ) {
		error_log( 'Detected JWT token, attempting to decode...' );
		
		// Decode JWT token (without verification for now)
		$token_parts = explode( '.', $token );
		if ( count( $token_parts ) === 3 ) {
			$payload = $token_parts[1];
			// Add padding if needed
			$payload = str_pad( $payload, strlen( $payload ) % 4, '=', STR_PAD_RIGHT );
			$decoded_payload = base64_decode( strtr( $payload, '-_', '+/' ) );
			
			if ( $decoded_payload ) {
				$payload_data = json_decode( $decoded_payload, true );
				error_log( "JWT payload: " . print_r( $payload_data, true ) );
				
				if ( $payload_data && isset( $payload_data['user_id'] ) ) {
					$user_id = intval( $payload_data['user_id'] );
					error_log( "Extracted user_id from JWT: $user_id" );
					
					// Check if user exists and is active
					$user = get_userdata( $user_id );
					error_log( "User data check - User exists: " . ( $user ? 'YES' : 'NO' ) );
					
					if ( $user ) {
						error_log( "User status: " . $user->user_status );
						if ( $user->user_status == 0 ) {
							error_log( "JWT token validation successful for user ID: $user_id" );
							return $user_id;
						} else {
							error_log( "User is not active (status: " . $user->user_status . ")" );
						}
					} else {
						error_log( "User with ID $user_id does not exist" );
					}
				} else {
					error_log( "JWT payload does not contain user_id" );
				}
			} else {
				error_log( "Failed to decode JWT payload" );
			}
		} else {
			error_log( "Invalid JWT format (should have 3 parts)" );
		}
	} else {
		error_log( 'Not a JWT token, trying legacy token validation...' );
		// Legacy token validation (for backward compatibility)
		global $wpdb;
		
		$user_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key = 'jalsah_token' AND meta_value = %s",
			$token
		) );
		
		error_log( "Legacy database query result - User ID: " . ( $user_id ? $user_id : 'NOT FOUND' ) );
		
		if ( $user_id ) {
			// Check if user exists and is active
			$user = get_userdata( $user_id );
			error_log( "User data check - User exists: " . ( $user ? 'YES' : 'NO' ) );
			
			if ( $user ) {
				error_log( "User status: " . $user->user_status );
				if ( $user->user_status == 0 ) {
					error_log( "Legacy token validation successful for user ID: $user_id" );
					return intval( $user_id );
				} else {
					error_log( "User is not active (status: " . $user->user_status . ")" );
				}
			}
		} else {
			error_log( 'No user found with this legacy token' );
		}
	}
	
	error_log( 'Token validation failed' );
	return false;
}

/**
 * Global helper function to get AI registration date
 *
 * @param int $user_id User ID
 * @return string|false Registration date or false if not AI patient
 */
function snks_get_ai_registration_date( $user_id ) {
	return SNKS_AI_Integration::get_ai_registration_date( $user_id );
}

/**
 * Get prescription requests via REST API
 */
function snks_get_prescription_requests_rest( $request ) {
	$user_id = $request->get_param( 'user_id' );
	$locale = $request->get_param( 'locale' ) ?: 'en';
	
	if ( ! $user_id ) {
		return new WP_Error( 'missing_user_id', 'User ID is required', array( 'status' => 400 ) );
	}
	
	// Check if function exists (it should be in ai-prescription.php)
	if ( ! function_exists( 'snks_get_patient_prescription_requests' ) ) {
		return new WP_Error( 'function_not_found', 'Prescription function not available', array( 'status' => 500 ) );
	}
	
	$prescription_requests = snks_get_patient_prescription_requests( $user_id );
	
	// Use the same response format as other endpoints
	return array(
		'success' => true,
		'data' => $prescription_requests
	);
}

/**
 * Get completed prescriptions via REST API
 */
function snks_get_completed_prescriptions_rest( $request ) {
	$user_id = $request->get_param( 'user_id' );
	$locale = $request->get_param( 'locale' ) ?: 'en';
	
	if ( ! $user_id ) {
		return new WP_Error( 'missing_user_id', 'User ID is required', array( 'status' => 400 ) );
	}
	
	// Check if function exists (it should be in ai-prescription.php)
	if ( ! function_exists( 'snks_get_patient_completed_prescriptions' ) ) {
		return new WP_Error( 'function_not_found', 'Prescription function not available', array( 'status' => 500 ) );
	}
	
	$completed_prescriptions = snks_get_patient_completed_prescriptions( $user_id );
	
	// Format the prescription data for frontend consumption
	$formatted_prescriptions = array();
	foreach ( $completed_prescriptions as $prescription ) {
		$formatted_prescriptions[] = array(
			'id' => $prescription->id,
			'booking_date' => $prescription->booking_date,
			'booking_time' => $prescription->booking_time,
			'prescribed_at' => $prescription->prescribed_at,
			'prescribed_by_name' => $prescription->prescribed_by_name,
			'therapist_name' => $prescription->therapist_name,
			'prescription_text' => $prescription->prescription_text,
			'medications' => $prescription->medications,
			'dosage_instructions' => $prescription->dosage_instructions,
			'doctor_notes' => $prescription->doctor_notes,
			'initial_diagnosis' => $prescription->initial_diagnosis,
			'symptoms' => $prescription->symptoms,
			'reason_for_referral' => $prescription->reason_for_referral,
			'status' => $prescription->status
		);
	}
	
	return array(
		'success' => true,
		'data' => $formatted_prescriptions
	);
}

/**
 * Get user country based on IP address via REST API
 */
function snks_get_user_country_rest( $request ) {
	// Get IP from request parameter or use REMOTE_ADDR
	$custom_ip = $request->get_param( 'ip' );
	$detected_ip = $custom_ip ?: ( $_SERVER['REMOTE_ADDR'] ?? 'Not Set' );
	
	// Call the helper function with custom IP (force fresh detection by not using cookie cache)
	$country_code = snks_get_country_code( false, $custom_ip );
	
	$final_country_code = $country_code !== 'Unknown' ? $country_code : 'EG';
	
	// Return the country code
	return rest_ensure_response( array(
		'success' => true,
		'country_code' => $final_country_code,
		'data' => array(
			'country_code' => $final_country_code
		)
	) );
}


/**
 * Get Rochtah available slots via REST API
 */
function snks_get_rochtah_available_slots_rest( $request ) {
	$request_id = $request->get_param( 'request_id' );
	
	if ( ! $request_id ) {
		return new WP_Error( 'missing_request_id', 'Request ID is required', array( 'status' => 400 ) );
	}
	
	// Check if function exists (it should be in ai-prescription.php)
	if ( ! function_exists( 'snks_get_rochtah_available_slots_for_patient' ) ) {
		return new WP_Error( 'function_not_found', 'Rochtah function not available', array( 'status' => 500 ) );
	}
	
	$available_slots = snks_get_rochtah_available_slots_for_patient();
	
	return array(
		'success' => true,
		'data' => $available_slots
	);
}

/**
 * Book Rochtah appointment via REST API
 */
function snks_book_rochtah_appointment_rest( $request ) {
	// Debug logging
	error_log( '=== ROCHTAH BOOKING DEBUG START ===' );
	error_log( 'Request parameters: ' . print_r( $request->get_params(), true ) );
	
	$request_id = $request->get_param( 'request_id' );
	$selected_date = $request->get_param( 'selected_date' );
	$selected_time = $request->get_param( 'selected_time' );
	
	error_log( "Request ID: $request_id, Date: $selected_date, Time: $selected_time" );
	
	if ( ! $request_id || ! $selected_date || ! $selected_time ) {
		error_log( 'Missing parameters detected' );
		return new WP_Error( 'missing_parameters', 'Request ID, date, and time are required', array( 'status' => 400 ) );
	}
	
	// Check authentication - try both WordPress session and Bearer token
	$user_id = null;
	
	error_log( 'Checking WordPress session authentication...' );
	// First try WordPress session authentication
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		error_log( "WordPress session auth successful. User ID: $user_id" );
	} else {
		error_log( 'WordPress session auth failed, trying Bearer token...' );
		// Try Bearer token authentication
		$auth_header = $request->get_header( 'Authorization' );
		error_log( "Authorization header: " . ( $auth_header ? $auth_header : 'NOT SET' ) );
		
		if ( $auth_header && strpos( $auth_header, 'Bearer ' ) === 0 ) {
			$token = substr( $auth_header, 7 ); // Remove 'Bearer ' prefix
			error_log( "Extracted token: " . substr( $token, 0, 10 ) . '...' );
			
			// Validate the token and get user ID
			$user_id = snks_validate_jalsah_token( $token );
			error_log( "Token validation result - User ID: " . ( $user_id ? $user_id : 'FAILED' ) );
		} else {
			error_log( 'No valid Authorization header found' );
		}
	}
	
	if ( ! $user_id ) {
		error_log( 'Authentication failed - no valid user ID found' );
		return new WP_Error( 'not_logged_in', 'You must be logged in to book an appointment', array( 'status' => 401 ) );
	}
	
	error_log( "Authentication successful. Proceeding with user ID: $user_id" );
	
	global $wpdb;
	$current_user = get_userdata( $user_id );
	
	// Verify the request belongs to the current user
	$rochtah_bookings_table = $wpdb->prefix . 'snks_rochtah_bookings';
	$request = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table WHERE id = %d AND patient_id = %d AND status = 'pending'",
		$request_id, $user_id
	) );
	
	if ( ! $request ) {
		return new WP_Error( 'request_not_found', 'Prescription request not found or already booked', array( 'status' => 404 ) );
	}
	
	// Check if slot is still available
	$existing_booking = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $rochtah_bookings_table 
		WHERE booking_date = %s 
		AND booking_time = %s 
		AND status IN ('pending', 'confirmed')
		AND id != %d",
		$selected_date,
		$selected_time,
		$request_id
	) );
	
	if ( $existing_booking ) {
		return new WP_Error( 'slot_unavailable', 'This time slot is no longer available', array( 'status' => 409 ) );
	}
	
	// Update the booking with the selected date and time
	$result = $wpdb->update(
		$rochtah_bookings_table,
		array(
			'booking_date' => $selected_date,
			'booking_time' => $selected_time,
			'status' => 'confirmed'
		),
		array( 'id' => $request_id ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);
	
	if ( $result !== false ) {
		// Send notification to Rochtah doctors
		$rochtah_doctors = get_users( array( 'role' => 'rochtah_doctor' ) );
		foreach ( $rochtah_doctors as $doctor ) {
			snks_create_ai_notification(
				$doctor->ID,
				'rochtah_appointment_booked',
				__( 'Rochtah Appointment Booked', 'shrinks' ),
				sprintf( 
					__( 'Patient %s has booked a Rochtah consultation for %s at %s', 'shrinks' ),
					$current_user->display_name,
					$selected_date,
					$selected_time
				)
			);
		}
		
		error_log( 'Booking update successful' );
		return array(
			'success' => true,
			'data' => array(
				'message' => __( 'Appointment booked successfully.', 'shrinks' ),
				'booking_id' => $request_id,
				'date' => $selected_date,
				'time' => $selected_time
			)
		);
	} else {
		error_log( 'Booking update failed' );
		return new WP_Error( 'booking_failed', 'Failed to book appointment. Please try again.', array( 'status' => 500 ) );
	}
	
	error_log( '=== ROCHTAH BOOKING DEBUG END ===' );
}

/**
 * Get Rochtah meeting details via REST API
 */
function snks_get_rochtah_meeting_details_rest( $request ) {
	$booking_id = $request->get_param( 'booking_id' );
	
	if ( ! $booking_id ) {
		return new WP_Error( 'missing_booking_id', 'Booking ID is required', array( 'status' => 400 ) );
	}
	
	// Check authentication - try both WordPress session and Bearer token
	$user_id = null;
	
	// First try WordPress session authentication
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		// Try Bearer token authentication
		$auth_header = $request->get_header( 'Authorization' );
		if ( $auth_header && strpos( $auth_header, 'Bearer ' ) === 0 ) {
			$token = substr( $auth_header, 7 ); // Remove 'Bearer ' prefix
			$user_id = snks_validate_jalsah_token( $token );
		}
	}
	
	if ( ! $user_id ) {
		return new WP_Error( 'not_logged_in', 'You must be logged in to access meeting details', array( 'status' => 401 ) );
	}
	
	// Check if function exists (it should be in ai-prescription.php)
	if ( ! function_exists( 'snks_get_rochtah_meeting_details' ) ) {
		return new WP_Error( 'function_not_found', 'Rochtah meeting function not available', array( 'status' => 500 ) );
	}
	
	// Get meeting details
	$meeting_details = snks_get_rochtah_meeting_details( $booking_id );
	
	if ( ! $meeting_details ) {
		return new WP_Error( 'booking_not_found', 'Booking not found', array( 'status' => 404 ) );
	}
	
	// Check if user has access to this booking (patient, therapist, or admin)
	$has_access = (
		$meeting_details['patient_id'] == $user_id || 
		$meeting_details['therapist_id'] == $user_id ||
		user_can( $user_id, 'manage_options' ) ||
		user_can( $user_id, 'manage_rochtah' )
	);
	
	if ( ! $has_access ) {
		return new WP_Error( 'access_denied', 'Access denied', array( 'status' => 403 ) );
	}
	
	return array(
		'success' => true,
		'data' => $meeting_details
	);
}