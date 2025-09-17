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
	
	// Debug test message
	error_log( '=== WHATSAPP TEST API DEBUG ===' );
	error_log( 'Test Phone: ' . $test_phone );
	error_log( 'Test Message: ' . $test_message );
	error_log( 'Use Template: ' . ( $settings['whatsapp_use_template'] ? 'Yes' : 'No' ) );
	error_log( 'Template Name: ' . $settings['whatsapp_template_name'] );
	error_log( '===============================' );
	
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
			
			<div class="card" id="whatsapp_api_settings" style="display: none;">
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
							<label for="whatsapp_template_name">Template Name</label>
						</th>
						<td>
							<input type="text" name="whatsapp_template_name" id="whatsapp_template_name" value="<?php echo esc_attr( $whatsapp_template_name ); ?>" class="regular-text" placeholder="hello_world">
							<p class="description">Name of your approved WhatsApp message template. Default: "hello_world" (for testing only).</p>
						</td>
					</tr>
					<!-- Button URL field removed - OTP messages should not have buttons -->
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
		'EG' => array( 'name' => 'Egypt', 'code' => '+20' ),
		'SA' => array( 'name' => 'Saudi Arabia', 'code' => '+966' ),
		'AE' => array( 'name' => 'UAE', 'code' => '+971' ),
		'KW' => array( 'name' => 'Kuwait', 'code' => '+965' ),
		'QA' => array( 'name' => 'Qatar', 'code' => '+974' ),
		'BH' => array( 'name' => 'Bahrain', 'code' => '+973' ),
		'OM' => array( 'name' => 'Oman', 'code' => '+968' ),
		'JO' => array( 'name' => 'Jordan', 'code' => '+962' ),
		'LB' => array( 'name' => 'Lebanon', 'code' => '+961' ),
		'SY' => array( 'name' => 'Syria', 'code' => '+963' ),
		'IQ' => array( 'name' => 'Iraq', 'code' => '+964' ),
		'YE' => array( 'name' => 'Yemen', 'code' => '+967' ),
		'PS' => array( 'name' => 'Palestine', 'code' => '+970' ),
		'MA' => array( 'name' => 'Morocco', 'code' => '+212' ),
		'TN' => array( 'name' => 'Tunisia', 'code' => '+216' ),
		'DZ' => array( 'name' => 'Algeria', 'code' => '+213' ),
		'LY' => array( 'name' => 'Libya', 'code' => '+218' ),
		'SD' => array( 'name' => 'Sudan', 'code' => '+249' )
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
