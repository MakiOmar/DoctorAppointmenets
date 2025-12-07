<?php
/**
 * Therapist Registration Settings
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Register therapist registration settings page
 */
function snks_add_therapist_registration_settings_page() {
	add_submenu_page(
		'jalsah-ai-management',
		'Therapist Registration Settings',
		'Registration Settings',
		'manage_options',
		'therapist-registration-settings',
		'snks_therapist_registration_settings_page'
	);
}
add_action( 'admin_menu', 'snks_add_therapist_registration_settings_page', 25 );

/**
 * Handle WhatsApp API test request
 */
function snks_test_whatsapp_api_ajax() {
	// Check nonce and permissions
	if ( ! wp_verify_nonce( $_POST['nonce'], 'test_whatsapp_api_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
	}
	
	$test_phone = sanitize_text_field( $_POST['test_phone'] ?? '' );
	if ( empty( $test_phone ) ) {
		wp_send_json_error( array( 'message' => 'Please provide a test phone number' ) );
	}
	
	// Get WhatsApp API settings
	$settings = array(
		'whatsapp_api_url' => get_option( 'snks_whatsapp_api_url', '' ),
		'whatsapp_api_token' => get_option( 'snks_whatsapp_api_token', '' ),
		'whatsapp_phone_number_id' => get_option( 'snks_whatsapp_phone_number_id', '' ),
		'whatsapp_message_language' => get_option( 'snks_whatsapp_message_language', 'ar' ),
		'whatsapp_template_name' => get_option( 'snks_whatsapp_template_name', 'hello_world' ),
		'whatsapp_use_template' => get_option( 'snks_whatsapp_use_template', 1 ),
		// Note: Button URL removed - OTP messages should not have buttons
	);
	
	if ( empty( $settings['whatsapp_api_url'] ) || empty( $settings['whatsapp_api_token'] ) || empty( $settings['whatsapp_phone_number_id'] ) ) {
		wp_send_json_error( array( 'message' => 'WhatsApp API configuration is incomplete. Please fill all required fields.' ) );
	}
	
	// Generate test message
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'shortcodes/therapist-registration-shortcode.php';
	$test_message = snks_get_multilingual_otp_message( '123456', $settings['whatsapp_message_language'] );
	$test_message .= ' (TEST MESSAGE)';
	
	// Debug test message (removed for production)
	
	// Send test message
	$result = snks_send_whatsapp_message( $test_phone, $test_message, $settings );
	
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 
			'message' => 'WhatsApp API test failed: ' . $result->get_error_message(),
			'error_code' => $result->get_error_code()
		) );
	} else {
		wp_send_json_success( array( 
			'message' => 'WhatsApp API test message sent successfully!',
			'response' => $result,
			'debug_info' => array(
				'api_url' => $settings['whatsapp_api_url'],
				'phone_number_id' => $settings['whatsapp_phone_number_id'],
				'test_phone' => $test_phone,
				'language' => $settings['whatsapp_message_language'],
				'use_template' => $settings['whatsapp_use_template'] ? 'Yes' : 'No',
				'template_name' => $settings['whatsapp_template_name']
			)
		) );
	}
}

add_action( 'wp_ajax_test_whatsapp_api', 'snks_test_whatsapp_api_ajax' );

/**
 * Test AI WhatsApp notification template
 */
function snks_test_ai_whatsapp_notification_ajax() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'test_ai_notification_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}
	
	// Check user permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
	}
	
	$template_name = sanitize_text_field( $_POST['template_name'] );
	$test_phone = sanitize_text_field( $_POST['test_phone'] );
	$params = json_decode( stripslashes( $_POST['params'] ), true );
	
	if ( empty( $template_name ) || empty( $test_phone ) ) {
		wp_send_json_error( array( 'message' => 'Template name and phone number are required' ) );
	}
	
	// Get the template name from option (in case it was customized)
	$template_option_name = 'snks_template_' . str_replace( array( '_new', '_app', '10', '_24h', '_1h', '_now', '_rem' ), array( '_new', '_app', '10', '_24h', '_1h', '_now', '_rem' ), $template_name );
	$actual_template = get_option( $template_option_name, $template_name );
	
	// Send test notification using the WhatsApp template function
	if ( function_exists( 'snks_send_whatsapp_template_message' ) ) {
		$result = snks_send_whatsapp_template_message( $test_phone, $actual_template, $params );
		
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$detailed_error = 'WhatsApp API test failed: ' . $result->get_error_message();
			
			// Add more details if available
			if ( isset( $error_data['error_data']['error']['error_data']['details'] ) ) {
				$detailed_error .= ' | Details: ' . $error_data['error_data']['error']['error_data']['details'];
			}
			
			wp_send_json_error( array( 
				'message' => $detailed_error,
				'full_error' => $error_data
			) );
		} else {
			$message_id = isset( $result['messages'][0]['id'] ) ? $result['messages'][0]['id'] : null;
			
			wp_send_json_success( array( 
				'message' => 'تم إرسال رسالة الاختبار بنجاح!',
				'message_id' => $message_id,
				'debug_info' => array(
					'template' => $actual_template,
					'phone' => $test_phone,
					'params_count' => count( $params ),
					'params_sent' => $params
				)
			) );
		}
	} else {
		wp_send_json_error( array( 'message' => 'WhatsApp notification function not available' ) );
	}
}
add_action( 'wp_ajax_test_ai_whatsapp_notification', 'snks_test_ai_whatsapp_notification_ajax' );

/**
 * Therapist Registration Settings Page
 */
function snks_therapist_registration_settings_page() {
	// Handle form submission
	if ( isset( $_POST['submit_registration_settings'] ) ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'therapist_registration_settings' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Save OTP method setting
		update_option( 'snks_therapist_otp_method', sanitize_text_field( $_POST['otp_method'] ) );
		
		// Save other settings
		update_option( 'snks_therapist_require_email', isset( $_POST['require_email'] ) ? 1 : 0 );
		update_option( 'snks_therapist_country_dial_required', isset( $_POST['country_dial_required'] ) ? 1 : 0 );
		update_option( 'snks_therapist_default_country', sanitize_text_field( $_POST['default_country'] ) );
		
		// WhatsApp API settings
		update_option( 'snks_whatsapp_api_url', sanitize_url( $_POST['whatsapp_api_url'] ?? '' ) );
		update_option( 'snks_whatsapp_api_token', sanitize_text_field( $_POST['whatsapp_api_token'] ?? '' ) );
		update_option( 'snks_whatsapp_phone_number_id', sanitize_text_field( $_POST['whatsapp_phone_number_id'] ?? '' ) );
		update_option( 'snks_whatsapp_message_language', sanitize_text_field( $_POST['whatsapp_message_language'] ?? 'ar' ) );
		update_option( 'snks_whatsapp_template_name', sanitize_text_field( $_POST['whatsapp_template_name'] ?? 'hello_world' ) );
		update_option( 'snks_whatsapp_use_template', isset( $_POST['whatsapp_use_template'] ) ? 1 : 0 );
		
		// AI Notification Template Names
		update_option( 'snks_ai_notifications_enabled', isset( $_POST['ai_notifications_enabled'] ) ? '1' : '0' );
		update_option( 'snks_template_new_session', sanitize_text_field( $_POST['template_new_session'] ?? 'new_session' ) );
		update_option( 'snks_template_doctor_new', sanitize_text_field( $_POST['template_doctor_new'] ?? 'doctor_new' ) );
		update_option( 'snks_template_rosheta10', sanitize_text_field( $_POST['template_rosheta10'] ?? 'rosheta10' ) );
		update_option( 'snks_template_rosheta_app', sanitize_text_field( $_POST['template_rosheta_app'] ?? 'rosheta_app' ) );
		// NEW: Rosheta doctor alert, therapist message, prescription done
		update_option( 'snks_template_rosheta_doctor', sanitize_text_field( $_POST['template_rosheta_doctor'] ?? 'rosheta_doctor' ) );
		update_option( 'snks_template_prescription1', sanitize_text_field( $_POST['template_prescription1'] ?? 'prescription1' ) );
		update_option( 'snks_template_prescription2', sanitize_text_field( $_POST['template_prescription2'] ?? 'prescription2' ) );
		update_option( 'snks_template_patient_rem_24h', sanitize_text_field( $_POST['template_patient_rem_24h'] ?? 'patient_rem_24h' ) );
		update_option( 'snks_template_patient_rem_1h', sanitize_text_field( $_POST['template_patient_rem_1h'] ?? 'patient_rem_1h' ) );
		update_option( 'snks_template_patient_rem_now', sanitize_text_field( $_POST['template_patient_rem_now'] ?? 'patient_rem_now' ) );
		update_option( 'snks_template_doctor_rem', sanitize_text_field( $_POST['template_doctor_rem'] ?? 'doctor_rem' ) );
		update_option( 'snks_template_edit2', sanitize_text_field( $_POST['template_edit2'] ?? 'edit2' ) );
		update_option( 'snks_template_edit', sanitize_text_field( $_POST['template_edit'] ?? 'edit' ) );
		update_option( 'snks_template_password', sanitize_text_field( $_POST['template_password'] ?? 'password' ) );
		// Note: Button URL removed - OTP messages should not have buttons
		
		echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
	}
	
	// Get current settings
	$otp_method = get_option( 'snks_therapist_otp_method', 'email' );
	$require_email = get_option( 'snks_therapist_require_email', 0 );
	$country_dial_required = get_option( 'snks_therapist_country_dial_required', 1 );
	$default_country = get_option( 'snks_therapist_default_country', 'EG' );
	
	// WhatsApp API settings
	$whatsapp_api_url = get_option( 'snks_whatsapp_api_url', '' );
	$whatsapp_api_token = get_option( 'snks_whatsapp_api_token', '' );
	$whatsapp_phone_number_id = get_option( 'snks_whatsapp_phone_number_id', '' );
	$whatsapp_message_language = get_option( 'snks_whatsapp_message_language', 'ar' );
	$whatsapp_template_name = get_option( 'snks_whatsapp_template_name', 'hello_world' );
	$whatsapp_use_template = get_option( 'snks_whatsapp_use_template', 1 );
	// Note: Button URL setting removed - OTP messages should not have buttons
	
	?>
	<div class="wrap">
		<h1>Therapist Registration Settings</h1>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'therapist_registration_settings' ); ?>
			
			<div class="card">
				<h2>OTP Verification Settings</h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="otp_method">OTP Method</label>
						</th>
						<td>
							<select name="otp_method" id="otp_method" onchange="toggleOtpSettings()">
								<option value="email" <?php selected( $otp_method, 'email' ); ?>>Email</option>
								<option value="sms" <?php selected( $otp_method, 'sms' ); ?>>SMS (WhySMS Service)</option>
								<option value="whatsapp" <?php selected( $otp_method, 'whatsapp' ); ?>>WhatsApp API</option>
							</select>
							<p class="description">Choose the method for sending OTP verification codes to new therapist registrants.</p>
						</td>
					</tr>
					<tr id="email_requirement_row">
						<th scope="row">
							<label for="require_email">Require Email Field</label>
						</th>
						<td>
							<input type="checkbox" name="require_email" id="require_email" value="1" <?php checked( $require_email, 1 ); ?> />
							<label for="require_email">Show email field in registration form</label>
							<p class="description">When OTP method is Email, this should be checked. When SMS or WhatsApp is used, you can optionally hide the email field.</p>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="card" id="whatsapp_api_settings" style="display: none;max-width: 100%;width: 100%;">
				<h2>WhatsApp API Settings</h2>
				<p class="description">Configure WhatsApp Business API settings for sending OTP messages via WhatsApp.</p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="whatsapp_api_url">WhatsApp API URL</label>
						</th>
						<td>
							<input type="url" name="whatsapp_api_url" id="whatsapp_api_url" value="<?php echo esc_attr( $whatsapp_api_url ); ?>" class="regular-text" placeholder="https://graph.facebook.com/v22.0">
							<p class="description">Base URL for WhatsApp Business API (e.g., https://graph.facebook.com/v22.0)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="whatsapp_api_token">Access Token</label>
						</th>
						<td>
							<input type="password" name="whatsapp_api_token" id="whatsapp_api_token" value="<?php echo esc_attr( $whatsapp_api_token ); ?>" class="regular-text">
							<p class="description">WhatsApp Business API access token from Facebook Developer Console.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="whatsapp_phone_number_id">Phone Number ID</label>
						</th>
						<td>
							<input type="text" name="whatsapp_phone_number_id" id="whatsapp_phone_number_id" value="<?php echo esc_attr( $whatsapp_phone_number_id ); ?>" class="regular-text" placeholder="701585759714485">
							<p class="description">WhatsApp Business phone number ID from your Meta Developer Dashboard (e.g., 701585759714485).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="whatsapp_message_language">Message Language</label>
						</th>
						<td>
							<select name="whatsapp_message_language" id="whatsapp_message_language">
								<option value="ar" <?php selected( $whatsapp_message_language, 'ar' ); ?>>العربية (Arabic)</option>
								<option value="en" <?php selected( $whatsapp_message_language, 'en' ); ?>>English</option>
								<option value="fr" <?php selected( $whatsapp_message_language, 'fr' ); ?>>Français (French)</option>
								<option value="es" <?php selected( $whatsapp_message_language, 'es' ); ?>>Español (Spanish)</option>
								<option value="de" <?php selected( $whatsapp_message_language, 'de' ); ?>>Deutsch (German)</option>
								<option value="it" <?php selected( $whatsapp_message_language, 'it' ); ?>>Italiano (Italian)</option>
								<option value="tr" <?php selected( $whatsapp_message_language, 'tr' ); ?>>Türkçe (Turkish)</option>
								<option value="ur" <?php selected( $whatsapp_message_language, 'ur' ); ?>>اردو (Urdu)</option>
							</select>
							<p class="description">Choose the language for WhatsApp OTP messages. This affects both SMS and WhatsApp API methods.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="whatsapp_use_template">Use Message Templates</label>
						</th>
						<td>
							<input type="checkbox" name="whatsapp_use_template" id="whatsapp_use_template" value="1" <?php checked( $whatsapp_use_template, 1 ); ?> />
							<label for="whatsapp_use_template">Use pre-approved WhatsApp message templates</label>
							<p class="description"><strong>Recommended:</strong> Templates ensure message delivery. Uncheck only if you have an active conversation window.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="whatsapp_template_name">OTP Template Name</label>
						</th>
						<td>
							<input type="text" name="whatsapp_template_name" id="whatsapp_template_name" value="<?php echo esc_attr( $whatsapp_template_name ); ?>" class="regular-text" placeholder="hello_world">
							<p class="description">Template name for OTP verification messages. Default: "hello_world" (for testing only).</p>
						</td>
					</tr>
				</table>
				
				<h3>AI Notification Templates (Jalsah AI Only)</h3>
				<p class="description">Configure template names for automated WhatsApp notifications for AI sessions. All templates must be pre-approved in WhatsApp Business API.</p>
				
				<div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">
					<p><strong>⚠️ مهم جداً:</strong> عند إنشاء القوالب في WhatsApp Business Manager، استخدم <strong>أرقام</strong> وليس أسماء للمتغيرات:</p>
					<ul style="margin: 10px 0 10px 20px;">
						<li>✅ <strong>صحيح:</strong> <code>المعالج {{1}} يوم {{2}} الموافق {{3}} الساعة {{4}}</code></li>
						<li>❌ <strong>خطأ:</strong> <code>المعالج {{doctor}} يوم {{day}} الموافق {{date}} الساعة {{time}}</code></li>
					</ul>
					<p><small>WhatsApp API يستخدم أرقام ({{1}}, {{2}}, ...) فقط. الأسماء المذكورة أدناه (doctor, day, date, time) هي للتوضيح فقط لمعرفة ترتيب إرسال القيم.</small></p>
				</div>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="ai_notifications_enabled">Enable AI Notifications</label>
						</th>
						<td>
							<input type="checkbox" name="ai_notifications_enabled" id="ai_notifications_enabled" value="1" <?php checked( get_option( 'snks_ai_notifications_enabled', '1' ), '1' ); ?> />
							<label for="ai_notifications_enabled">Enable automated WhatsApp notifications for AI sessions</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_new_session">New Session (Patient)</label>
						</th>
						<td>
							<input type="text" name="template_new_session" id="template_new_session" value="<?php echo esc_attr( get_option( 'snks_template_new_session', 'new_session' ) ); ?>" class="regular-text" placeholder="new_session">
							<button type="button" class="button test-whatsapp-notification" data-template="new_session" data-params='{"doctor": "د. أحمد محمد", "day": "الاثنين", "date": "2025-10-21", "time": "10:00 ص"}' style="margin-right: 10px;">اختبار</button>
							<p class="description">Patient booking | <code>{{doctor}}</code>, <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_doctor_new">New Booking (Doctor)</label>
						</th>
						<td>
							<input type="text" name="template_doctor_new" id="template_doctor_new" value="<?php echo esc_attr( get_option( 'snks_template_doctor_new', 'doctor_new' ) ); ?>" class="regular-text" placeholder="doctor_new">
						<button type="button" class="button test-whatsapp-notification" data-template="doctor_new" data-params='{"patient": "سارة أحمد", "day": "الاثنين", "date": "2025-10-21", "time": "10:00 ص"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Doctor alert | <code>{{patient}}</code>, <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_rosheta10">Rosheta Activation</label>
						</th>
						<td>
							<input type="text" name="template_rosheta10" id="template_rosheta10" value="<?php echo esc_attr( get_option( 'snks_template_rosheta10', 'rosheta10' ) ); ?>" class="regular-text" placeholder="rosheta10">
						<button type="button" class="button test-whatsapp-notification" data-template="rosheta10" data-params='{"patient": "سارة أحمد", "doctor": "د. محمد علي"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Prescription activation | <code>{{patient}}</code>, <code>{{doctor}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_rosheta_app">Rosheta Appointment</label>
						</th>
						<td>
							<input type="text" name="template_rosheta_app" id="template_rosheta_app" value="<?php echo esc_attr( get_option( 'snks_template_rosheta_app', 'rosheta_app' ) ); ?>" class="regular-text" placeholder="rosheta_app">
						<button type="button" class="button test-whatsapp-notification" data-template="rosheta_app" data-params='{"day": "الثلاثاء", "date": "2025-10-22", "time": "02:00 م"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Prescription appointment | <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_rosheta_doctor">Rosheta Doctor Alert</label>
						</th>
						<td>
							<input type="text" name="template_rosheta_doctor" id="template_rosheta_doctor" value="<?php echo esc_attr( get_option( 'snks_template_rosheta_doctor', 'rosheta_doctor' ) ); ?>" class="regular-text" placeholder="rosheta_doctor">
							<button type="button" class="button test-whatsapp-notification" data-template="rosheta_doctor" data-params='{"patient": "محمد علي", "day": "الاثنين", "date": "2025-11-10", "time": "10:00 ص"}' style="margin-right: 10px;">اختبار</button>
							<p class="description">Notify rochtah doctor on booking | <code>{{patient}}</code>, <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_prescription1">Therapist Message → Patient</label>
						</th>
						<td>
							<input type="text" name="template_prescription1" id="template_prescription1" value="<?php echo esc_attr( get_option( 'snks_template_prescription1', 'prescription1' ) ); ?>" class="regular-text" placeholder="prescription1">
							<button type="button" class="button test-whatsapp-notification" data-template="prescription1" data-params='{"patient": "محمد علي"}' style="margin-right: 10px;">اختبار</button>
							<p class="description">When therapist sends a message | <code>{{patient}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_prescription2">Prescription Completed → Patient</label>
						</th>
						<td>
							<input type="text" name="template_prescription2" id="template_prescription2" value="<?php echo esc_attr( get_option( 'snks_template_prescription2', 'prescription2' ) ); ?>" class="regular-text" placeholder="prescription2">
							<button type="button" class="button test-whatsapp-notification" data-template="prescription2" data-params='[]' style="margin-right: 10px;">اختبار</button>
							<p class="description">When rochtah prescription is saved | No parameters</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_patient_rem_24h">24-Hour Reminder</label>
						</th>
						<td>
							<input type="text" name="template_patient_rem_24h" id="template_patient_rem_24h" value="<?php echo esc_attr( get_option( 'snks_template_patient_rem_24h', 'patient_rem_24h' ) ); ?>" class="regular-text" placeholder="patient_rem_24h">
						<button type="button" class="button test-whatsapp-notification" data-template="patient_rem_24h" data-params='{"doctor": "د. خالد حسن", "day": "الأربعاء", "date": "2025-10-23", "time": "03:00 م"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">24h reminder | <code>{{doctor}}</code>, <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_patient_rem_1h">1-Hour Reminder</label>
						</th>
						<td>
							<input type="text" name="template_patient_rem_1h" id="template_patient_rem_1h" value="<?php echo esc_attr( get_option( 'snks_template_patient_rem_1h', 'patient_rem_1h' ) ); ?>" class="regular-text" placeholder="patient_rem_1h">
							<button type="button" class="button test-whatsapp-notification" data-template="patient_rem_1h" data-params='[]' style="margin-right: 10px;">اختبار</button>
							<p class="description">1h reminder | No parameters</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_patient_rem_now">Doctor Joined</label>
						</th>
						<td>
							<input type="text" name="template_patient_rem_now" id="template_patient_rem_now" value="<?php echo esc_attr( get_option( 'snks_template_patient_rem_now', 'patient_rem_now' ) ); ?>" class="regular-text" placeholder="patient_rem_now">
							<button type="button" class="button test-whatsapp-notification" data-template="patient_rem_now" data-params='[]' style="margin-right: 10px;">اختبار</button>
							<p class="description">Doctor joined | No parameters</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_doctor_rem">Doctor Daily Reminder</label>
						</th>
						<td>
							<input type="text" name="template_doctor_rem" id="template_doctor_rem" value="<?php echo esc_attr( get_option( 'snks_template_doctor_rem', 'doctor_rem' ) ); ?>" class="regular-text" placeholder="doctor_rem">
						<button type="button" class="button test-whatsapp-notification" data-template="doctor_rem" data-params='{"day": "الخميس", "date": "2025-10-24"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Daily reminder | <code>{{day}}</code>, <code>{{date}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_edit2">Appointment Change (Patient)</label>
						</th>
						<td>
							<input type="text" name="template_edit2" id="template_edit2" value="<?php echo esc_attr( get_option( 'snks_template_edit2', 'edit2' ) ); ?>" class="regular-text" placeholder="edit2">
						<button type="button" class="button test-whatsapp-notification" data-template="edit2" data-params='{"day": "الثلاثاء", "date": "2025-10-22", "time": "02:00 م", "day2": "الأربعاء", "date2": "2025-10-23", "time2": "03:00 م"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Patient notification | <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code>, <code>{{day2}}</code>, <code>{{date2}}</code>, <code>{{time2}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_edit">Appointment Change (Therapist)</label>
						</th>
						<td>
							<input type="text" name="template_edit" id="template_edit" value="<?php echo esc_attr( get_option( 'snks_template_edit', 'edit' ) ); ?>" class="regular-text" placeholder="edit">
						<button type="button" class="button test-whatsapp-notification" data-template="edit" data-params='{"patient": "سارة أحمد", "day": "الثلاثاء", "date": "2025-10-22", "time": "02:00 م", "day2": "الأربعاء", "date2": "2025-10-23", "time2": "03:00 م"}' style="margin-right: 10px;">اختبار</button>
						<p class="description">Therapist notification | <code>{{patient}}</code>, <code>{{day}}</code>, <code>{{date}}</code>, <code>{{time}}</code>, <code>{{day2}}</code>, <code>{{date2}}</code>, <code>{{time2}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_password">Reset Password</label>
						</th>
						<td>
							<input type="text" name="template_password" id="template_password" value="<?php echo esc_attr( get_option( 'snks_template_password', 'password' ) ); ?>" class="regular-text" placeholder="password">
							<button type="button" class="button test-whatsapp-notification" data-template="password" data-params='{"text": "123456"}' style="margin-right: 10px;">اختبار</button>
							<p class="description">Password reset | <code>{{text}}</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row" colspan="2">
							<hr style="margin: 20px 0;">
							<label for="ai_test_phone">Test Phone Number for AI Notifications</label><br>
							<input type="text" id="ai_test_phone" placeholder="201026795795" class="regular-text" style="margin-top: 10px;">
							<p class="description">Enter a phone number to receive test notifications (without + sign, e.g., 201026795795)</p>
							<div id="ai-test-result" style="margin-top: 10px;"></div>
						</th>
					</tr>
					<tr>
						<th scope="row">
							<label for="test_whatsapp_phone">Test WhatsApp API</label>
						</th>
						<td>
							<input type="text" id="test_whatsapp_phone" placeholder="201026795795" class="regular-text" style="margin-bottom: 10px;">
							<br>
							<button type="button" id="test_whatsapp_button" class="button button-secondary">Send Test Message</button>
							<div id="test_whatsapp_result" style="margin-top: 10px;"></div>
							<p class="description">Enter a phone number (with country code, no +) to test the WhatsApp API configuration. A test OTP message will be sent.</p>
							<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
								<strong>Troubleshooting:</strong>
								<ul style="margin: 5px 0; padding-left: 20px;">
									<li>Ensure the phone number is verified in your Meta Business account</li>
									<li>Check that the phone number is in the correct format (e.g., 201026795795)</li>
									<li>Verify your WhatsApp Business account is properly set up and approved</li>
									<li>Check the error logs for detailed API responses</li>
									<li>Template messages may require pre-approval from Meta</li>
								</ul>
							</div>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2>Phone Number Settings</h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="country_dial_required">Country Dial Code Selector</label>
						</th>
						<td>
							<input type="checkbox" name="country_dial_required" id="country_dial_required" value="1" <?php checked( $country_dial_required, 1 ); ?> />
							<label for="country_dial_required">Show country dial code selector for phone and WhatsApp fields</label>
							<p class="description">Enables a dropdown to select country code before entering phone numbers.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_country">Default Country</label>
						</th>
						<td>
							<select name="default_country" id="default_country">
								<option value="EG" <?php selected( $default_country, 'EG' ); ?>>Egypt (+20)</option>
								<option value="SA" <?php selected( $default_country, 'SA' ); ?>>Saudi Arabia (+966)</option>
								<option value="AE" <?php selected( $default_country, 'AE' ); ?>>UAE (+971)</option>
								<option value="KW" <?php selected( $default_country, 'KW' ); ?>>Kuwait (+965)</option>
								<option value="QA" <?php selected( $default_country, 'QA' ); ?>>Qatar (+974)</option>
								<option value="BH" <?php selected( $default_country, 'BH' ); ?>>Bahrain (+973)</option>
								<option value="OM" <?php selected( $default_country, 'OM' ); ?>>Oman (+968)</option>
								<option value="JO" <?php selected( $default_country, 'JO' ); ?>>Jordan (+962)</option>
								<option value="LB" <?php selected( $default_country, 'LB' ); ?>>Lebanon (+961)</option>
								<option value="SY" <?php selected( $default_country, 'SY' ); ?>>Syria (+963)</option>
								<option value="IQ" <?php selected( $default_country, 'IQ' ); ?>>Iraq (+964)</option>
								<option value="YE" <?php selected( $default_country, 'YE' ); ?>>Yemen (+967)</option>
								<option value="PS" <?php selected( $default_country, 'PS' ); ?>>Palestine (+970)</option>
								<option value="MA" <?php selected( $default_country, 'MA' ); ?>>Morocco (+212)</option>
								<option value="TN" <?php selected( $default_country, 'TN' ); ?>>Tunisia (+216)</option>
								<option value="DZ" <?php selected( $default_country, 'DZ' ); ?>>Algeria (+213)</option>
								<option value="LY" <?php selected( $default_country, 'LY' ); ?>>Libya (+218)</option>
								<option value="SD" <?php selected( $default_country, 'SD' ); ?>>Sudan (+249)</option>
							</select>
							<p class="description">Default country to pre-select in the country dial code dropdown.</p>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2>Registration Form Preview</h2>
				<p><strong>Shortcode for WordPress pages:</strong></p>
				<code>[therapist_registration_form]</code>
				<p class="description">Use this shortcode to embed the therapist registration form on any WordPress page or post.</p>
				
				<div style="margin-top: 20px;">
					<h4>Current Form Configuration:</h4>
					<ul style="margin-left: 20px;">
						<li><strong>OTP Method:</strong> <span id="preview_otp_method"><?php echo ucfirst( $otp_method ); ?></span></li>
						<li><strong>Email Field:</strong> <span id="preview_email_field"><?php echo $require_email ? 'Shown' : 'Hidden'; ?></span></li>
						<li><strong>Country Dial Codes:</strong> <?php echo $country_dial_required ? 'Enabled' : 'Disabled'; ?></li>
						<li><strong>Default Country:</strong> <?php echo $default_country; ?></li>
					</ul>
				</div>
			</div>
			
			<?php submit_button( 'Save Settings', 'primary', 'submit_registration_settings' ); ?>
		</form>
	</div>
	
	<script>
	function toggleOtpSettings() {
		const otpMethod = document.getElementById('otp_method').value;
		const emailRow = document.getElementById('email_requirement_row');
		const emailCheckbox = document.getElementById('require_email');
		const whatsappSettings = document.getElementById('whatsapp_api_settings');
		const previewOtp = document.getElementById('preview_otp_method');
		const previewEmail = document.getElementById('preview_email_field');
		
		// Show/hide WhatsApp API settings
		if (otpMethod === 'whatsapp') {
			whatsappSettings.style.display = 'block';
		} else {
			whatsappSettings.style.display = 'none';
		}
		
		// Handle email field requirements
		if (otpMethod === 'email') {
			emailCheckbox.checked = true;
			emailCheckbox.disabled = true;
			previewEmail.textContent = 'Shown (Required)';
		} else {
			emailCheckbox.disabled = false;
			previewEmail.textContent = emailCheckbox.checked ? 'Shown' : 'Hidden';
		}
		
		// Update preview text
		let methodText = otpMethod.charAt(0).toUpperCase() + otpMethod.slice(1);
		if (otpMethod === 'sms') {
			methodText = 'SMS (WhySMS)';
		} else if (otpMethod === 'whatsapp') {
			methodText = 'WhatsApp API';
		}
		previewOtp.textContent = methodText;
	}
	
	function testWhatsAppAPI() {
		const phoneInput = document.getElementById('test_whatsapp_phone');
		const button = document.getElementById('test_whatsapp_button');
		const resultDiv = document.getElementById('test_whatsapp_result');
		
		const phone = phoneInput.value.trim();
		if (!phone) {
			resultDiv.innerHTML = '<div style="color: red;">Please enter a phone number</div>';
			return;
		}
		
		// Disable button and show loading
		button.disabled = true;
		button.textContent = 'Sending...';
		resultDiv.innerHTML = '<div style="color: blue;">Sending test message...</div>';
		
		// Prepare AJAX data
		const formData = new FormData();
		formData.append('action', 'test_whatsapp_api');
		formData.append('test_phone', phone);
		formData.append('nonce', '<?php echo wp_create_nonce( 'test_whatsapp_api_nonce' ); ?>');
		
		// Send AJAX request
		fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				let debugInfo = '';
				if (data.data.debug_info) {
					debugInfo = '<br><small><strong>Debug Info:</strong><br>' +
						'API URL: ' + data.data.debug_info.api_url + '<br>' +
						'Phone Number ID: ' + data.data.debug_info.phone_number_id + '<br>' +
						'Test Phone: ' + data.data.debug_info.test_phone + '<br>' +
						'Language: ' + data.data.debug_info.language + '<br>' +
						'Use Template: ' + data.data.debug_info.use_template + '<br>' +
						'Template Name: ' + data.data.debug_info.template_name + '</small>';
				}
				
				let responseInfo = '';
				if (data.data.response) {
					responseInfo = '<br><small><strong>API Response:</strong><br>' +
						'<code>' + JSON.stringify(data.data.response, null, 2) + '</code></small>';
				}
				
				resultDiv.innerHTML = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><strong>✓ Success:</strong> ' + data.data.message + debugInfo + responseInfo + '</div>';
			} else {
				resultDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><strong>✗ Error:</strong> ' + data.data.message + '</div>';
			}
		})
		.catch(error => {
			resultDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><strong>✗ Error:</strong> Network error occurred</div>';
			console.error('Error:', error);
		})
		.finally(() => {
			// Re-enable button
			button.disabled = false;
			button.textContent = 'Send Test Message';
		});
	}
	
	// Test AI notification templates  
	jQuery(document).ready(function($) {
		console.log('[WhatsApp AI] Test buttons script loaded');
		
		$(document).on('click', '.test-whatsapp-notification', function(e) {
			e.preventDefault();
			console.log('[WhatsApp AI] Test button clicked');
			
			const button = $(this);
			const templateName = button.data('template');
			const params = button.data('params') || [];
			const resultDiv = $('#ai-test-result');
			const testPhone = $('#ai_test_phone').val();
			
			console.log('[WhatsApp AI] Template:', templateName);
			console.log('[WhatsApp AI] Phone:', testPhone);
			console.log('[WhatsApp AI] Params:', params);
			
			if (!testPhone) {
				resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><strong>✗ خطأ:</strong> يرجى إدخال رقم الهاتف للاختبار</div>');
				return;
			}
			
			// Disable button and show loading
			button.prop('disabled', true);
			button.text('جاري الإرسال...');
			resultDiv.html('<div style="padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;">جاري إرسال رسالة الاختبار...</div>');
			
			console.log('[WhatsApp AI] Sending AJAX request...');
			
			// Send AJAX request using jQuery
			$.ajax({
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				type: 'POST',
				data: {
					action: 'test_ai_whatsapp_notification',
					template_name: templateName,
					test_phone: testPhone,
					params: JSON.stringify(params),
					nonce: '<?php echo wp_create_nonce( 'test_ai_notification_nonce' ); ?>'
				},
				success: function(response) {
					console.log('[WhatsApp AI] Response:', response);
					
					if (response.success) {
						let debugInfo = '';
						if (response.data.debug_info) {
							debugInfo = '<br><small><strong>معلومات الاختبار:</strong><br>' +
								'القالب: ' + response.data.debug_info.template + '<br>' +
								'الهاتف: ' + response.data.debug_info.phone + '<br>' +
								'المتغيرات: ' + response.data.debug_info.params_count + '<br>' +
								'القيم المرسلة: ' + JSON.stringify(response.data.debug_info.params_sent) + '</small>';
						}
						
						let responseInfo = '';
						if (response.data.message_id) {
							responseInfo = '<br><small><strong>Message ID:</strong> ' + response.data.message_id + '</small>';
						}
						
						resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><strong>✓ نجح:</strong> ' + response.data.message + debugInfo + responseInfo + '</div>');
					} else {
						let errorDetails = '';
						if (response.data.full_error) {
							errorDetails = '<br><small><pre style="background: #fff; padding: 10px; overflow: auto;">' + 
								JSON.stringify(response.data.full_error, null, 2) + '</pre></small>';
						}
						
						resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><strong>✗ خطأ:</strong> ' + response.data.message + errorDetails + '<br><small>تحقق من سجل الأخطاء للحصول على تفاصيل كاملة</small></div>');
					}
				},
				error: function(xhr, status, error) {
					console.error('[WhatsApp AI] AJAX Error:', error);
					resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><strong>✗ خطأ:</strong> حدث خطأ في الاتصال</div>');
				},
				complete: function() {
					// Re-enable button
					button.prop('disabled', false);
					button.text('اختبار');
				}
			});
		});
	});
	
	// Initialize on page load
	document.addEventListener('DOMContentLoaded', function() {
		toggleOtpSettings();
		
		// Update preview when email checkbox changes
		document.getElementById('require_email').addEventListener('change', function() {
			const previewEmail = document.getElementById('preview_email_field');
			previewEmail.textContent = this.checked ? 'Shown' : 'Hidden';
		});
		
		// Add test WhatsApp button event listener
		const testButton = document.getElementById('test_whatsapp_button');
		if (testButton) {
			testButton.addEventListener('click', testWhatsAppAPI);
		}
	});
	</script>
	
	<style>
	.card {
		background: #fff;
		border: 1px solid #ccd0d4;
		border-radius: 4px;
		margin: 20px 0;
		padding: 20px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}
	.card h2 {
		margin-top: 0;
		margin-bottom: 15px;
		padding-bottom: 10px;
		border-bottom: 1px solid #eee;
	}
	code {
		background: #f1f1f1;
		padding: 8px 12px;
		border-radius: 3px;
		font-family: Consolas, Monaco, monospace;
		font-size: 14px;
	}
	</style>
	
	<?php
}

/**
 * Get country dial codes
 */
function snks_get_country_dial_codes() {
	return array(
		'EG' => array( 'name' => 'Egypt', 'code' => '+20', 'validation_pattern' => '^\\+20(10|11|12|15|16)[0-9]{8}$' ),
		'SA' => array( 'name' => 'Saudi Arabia', 'code' => '+966', 'validation_pattern' => '^\\+966(5|50|51|52|53|54|55|56|57|58|59)[0-9]{7}$' ),
		'AE' => array( 'name' => 'UAE', 'code' => '+971', 'validation_pattern' => '^\\+971(5|50|52|54|55|56|58|59)[0-9]{7}$' ),
		'KW' => array( 'name' => 'Kuwait', 'code' => '+965', 'validation_pattern' => '^\\+965[569][0-9]{7}$' ),
		'QA' => array( 'name' => 'Qatar', 'code' => '+974', 'validation_pattern' => '^\\+974[3-7][0-9]{7}$' ),
		'BH' => array( 'name' => 'Bahrain', 'code' => '+973', 'validation_pattern' => '^\\+973[3-9][0-9]{7}$' ),
		'OM' => array( 'name' => 'Oman', 'code' => '+968', 'validation_pattern' => '^\\+968[79][0-9]{7}$' ),
		'JO' => array( 'name' => 'Jordan', 'code' => '+962', 'validation_pattern' => '^\\+962[7][789][0-9]{7}$' ),
		'LB' => array( 'name' => 'Lebanon', 'code' => '+961', 'validation_pattern' => '^\\+961[3-9][0-9]{7}$' ),
		'SY' => array( 'name' => 'Syria', 'code' => '+963', 'validation_pattern' => '^\\+963[9][0-9]{8}$' ),
		'IQ' => array( 'name' => 'Iraq', 'code' => '+964', 'validation_pattern' => '^\\+964[7][3-9][0-9]{8}$' ),
		'YE' => array( 'name' => 'Yemen', 'code' => '+967', 'validation_pattern' => '^\\+967[7][0-9]{8}$' ),
		'PS' => array( 'name' => 'Palestine', 'code' => '+970', 'validation_pattern' => '^\\+970[5][0-9]{8}$' ),
		'MA' => array( 'name' => 'Morocco', 'code' => '+212', 'validation_pattern' => '^\\+212[6-7][0-9]{8}$' ),
		'TN' => array( 'name' => 'Tunisia', 'code' => '+216', 'validation_pattern' => '^\\+216[2-5][0-9]{7}$' ),
		'DZ' => array( 'name' => 'Algeria', 'code' => '+213', 'validation_pattern' => '^\\+213[5-7][0-9]{8}$' ),
		'LY' => array( 'name' => 'Libya', 'code' => '+218', 'validation_pattern' => '^\\+218[9][0-9]{8}$' ),
		'SD' => array( 'name' => 'Sudan', 'code' => '+249', 'validation_pattern' => '^\\+249[9][0-9]{8}$' )
	);
}

/**
 * Get therapist registration settings for frontend
 */
function snks_get_therapist_registration_settings() {
	$settings = array(
		'otp_method' => get_option( 'snks_therapist_otp_method', 'email' ),
		'require_email' => get_option( 'snks_therapist_require_email', 0 ),
		'country_dial_required' => get_option( 'snks_therapist_country_dial_required', 1 ),
		'whatsapp_api_url' => get_option( 'snks_whatsapp_api_url', '' ),
		'whatsapp_api_token' => get_option( 'snks_whatsapp_api_token', '' ),
		'whatsapp_phone_number_id' => get_option( 'snks_whatsapp_phone_number_id', '' ),
		'whatsapp_message_language' => get_option( 'snks_whatsapp_message_language', 'ar' ),
		'whatsapp_template_name' => get_option( 'snks_whatsapp_template_name', 'hello_world' ),
		'whatsapp_use_template' => get_option( 'snks_whatsapp_use_template', 1 ),
		'default_country' => get_option( 'snks_therapist_default_country', 'EG' ),
		'country_codes' => snks_get_country_dial_codes()
	);
	
	return $settings;
}

/**
 * REST API endpoint for therapist registration settings
 */
function snks_get_therapist_registration_settings_rest( $request ) {
	$settings = snks_get_therapist_registration_settings();
	
	return array(
		'success' => true,
		'data' => $settings
	);
}

// Register REST endpoint
add_action( 'rest_api_init', function() {
	register_rest_route( 'jalsah-ai/v1', '/therapist-registration-settings', array(
		'methods' => 'GET',
		'callback' => 'snks_get_therapist_registration_settings_rest',
		'permission_callback' => '__return_true',
	) );
} );
