<?php
/**
 * Therapist Registration Shortcode
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Therapist Registration Shortcode
 */
function snks_therapist_registration_shortcode( $atts ) {
	// Get settings
	$settings = snks_get_therapist_registration_settings();
	
	// Enqueue necessary scripts and styles
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'wp-util' );
	
	// Get country codes for the dropdown
	$country_codes = snks_get_country_dial_codes();
	$default_country = $settings['default_country'];
	
	ob_start();
	?>
	<div id="therapist-registration-form-container">
		<style>
		.therapist-reg-form {
			max-width: 600px;
			margin: 0 auto;
			padding: 20px;
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		.therapist-reg-form h2 {
			text-align: center;
			color: #333;
			margin-bottom: 30px;
		}
		.form-group {
			margin-bottom: 20px;
		}
		.form-group label {
			display: block;
			margin-bottom: 5px;
			font-weight: 600;
			color: #555;
		}
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 12px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
			box-sizing: border-box;
		}
		.form-group input:focus,
		.form-group select:focus,
		.form-group textarea:focus {
			outline: none;
			border-color: #2271b1;
			box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.1);
		}
		.phone-input-group {
			display: flex;
			gap: 10px;
		}
		.country-code-select {
			flex: 0 0 120px;
		}
		.phone-number-input {
			flex: 1;
		}
		.file-upload-group {
			border: 2px dashed #ddd;
			padding: 20px;
			text-align: center;
			border-radius: 4px;
			background: #fafafa;
		}
		.file-upload-group:hover {
			border-color: #2271b1;
			background: #f0f6ff;
		}
		.file-upload-group input[type="file"] {
			margin: 10px 0;
		}
		.submit-btn {
			background: #2271b1;
			color: #fff;
			border: none;
			padding: 15px 30px;
			border-radius: 4px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			width: 100%;
			transition: background 0.3s;
		}
		.submit-btn:hover {
			background: #1d5f98;
		}
		.submit-btn:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		.checkbox-group {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.checkbox-group input[type="checkbox"] {
			width: auto;
		}
		.alert {
			padding: 12px;
			border-radius: 4px;
			margin: 15px 0;
		}
		.alert-success {
			background: #d4edda;
			border: 1px solid #c3e6cb;
			color: #155724;
		}
		.alert-error {
			background: #f8d7da;
			border: 1px solid #f5c6cb;
			color: #721c24;
		}
		.required {
			color: #dc3545;
		}
		/* RTL Support */
		[dir="rtl"] .phone-input-group {
			direction: ltr;
		}
		[dir="rtl"] .form-group label {
			text-align: right;
		}
		</style>
		
		<form id="therapist-registration-form" class="therapist-reg-form" enctype="multipart/form-data">
			<h2><?php echo __( 'Therapist Registration', 'shrinks' ); ?></h2>
			
			<div id="form-messages"></div>
			
			<div class="form-group">
				<label for="name"><?php echo __( 'Full Name (Arabic)', 'shrinks' ); ?> <span class="required">*</span></label>
				<input type="text" id="name" name="name" required>
			</div>
			
			<div class="form-group">
				<label for="name_en"><?php echo __( 'Full Name (English)', 'shrinks' ); ?> <span class="required">*</span></label>
				<input type="text" id="name_en" name="name_en" required>
			</div>
			
			<?php if ( $settings['require_email'] ) : ?>
			<div class="form-group">
				<label for="email"><?php echo __( 'Email Address', 'shrinks' ); ?> <span class="required">*</span></label>
				<input type="email" id="email" name="email" required>
			</div>
			<?php endif; ?>
			
			<div class="form-group">
				<label for="phone"><?php echo __( 'Phone Number', 'shrinks' ); ?> <span class="required">*</span></label>
				<?php if ( $settings['country_dial_required'] ) : ?>
				<div class="phone-input-group">
					<select class="country-code-select" id="phone_country" name="phone_country">
						<?php foreach ( $country_codes as $code => $country ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $default_country ); ?>>
							<?php echo esc_html( $country['name'] . ' ' . $country['code'] ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<input type="tel" class="phone-number-input" id="phone" name="phone" required placeholder="123456789">
				</div>
				<?php else : ?>
				<input type="tel" id="phone" name="phone" required>
				<?php endif; ?>
			</div>
			
			<div class="form-group">
				<label for="whatsapp"><?php echo __( 'WhatsApp Number', 'shrinks' ); ?> <span class="required">*</span></label>
				<?php if ( $settings['country_dial_required'] ) : ?>
				<div class="phone-input-group">
					<select class="country-code-select" id="whatsapp_country" name="whatsapp_country">
						<?php foreach ( $country_codes as $code => $country ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $default_country ); ?>>
							<?php echo esc_html( $country['name'] . ' ' . $country['code'] ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<input type="tel" class="phone-number-input" id="whatsapp" name="whatsapp" required placeholder="123456789">
				</div>
				<?php else : ?>
				<input type="tel" id="whatsapp" name="whatsapp" required>
				<?php endif; ?>
			</div>
			
			<div class="form-group">
				<label for="doctor_specialty"><?php echo __( 'Specialty / Job Title', 'shrinks' ); ?> <span class="required">*</span></label>
				<input type="text" id="doctor_specialty" name="doctor_specialty" required>
			</div>
			
			<div class="form-group">
				<label for="profile_image"><?php echo __( 'Profile Image', 'shrinks' ); ?></label>
				<div class="file-upload-group">
					<p><?php echo __( 'Upload your professional profile photo', 'shrinks' ); ?></p>
					<input type="file" id="profile_image" name="profile_image" accept="image/*">
				</div>
			</div>
			
			<div class="form-group">
				<label for="identity_front"><?php echo __( 'Identity Document (Front)', 'shrinks' ); ?></label>
				<div class="file-upload-group">
					<p><?php echo __( 'Upload front side of your ID/passport', 'shrinks' ); ?></p>
					<input type="file" id="identity_front" name="identity_front" accept="image/*">
				</div>
			</div>
			
			<div class="form-group">
				<label for="identity_back"><?php echo __( 'Identity Document (Back)', 'shrinks' ); ?></label>
				<div class="file-upload-group">
					<p><?php echo __( 'Upload back side of your ID/passport', 'shrinks' ); ?></p>
					<input type="file" id="identity_back" name="identity_back" accept="image/*">
				</div>
			</div>
			
			<div class="form-group">
				<label for="certificates"><?php echo __( 'Certificates & Qualifications', 'shrinks' ); ?></label>
				<div class="file-upload-group">
					<p><?php echo __( 'Upload your certificates, diplomas, and qualifications (multiple files allowed)', 'shrinks' ); ?></p>
					<input type="file" id="certificates" name="certificates[]" accept=".pdf,image/*" multiple>
				</div>
			</div>
			
			<div class="form-group">
				<div class="checkbox-group">
					<input type="checkbox" id="accept_terms" name="accept_terms" required>
					<label for="accept_terms"><?php echo __( 'I accept the terms and conditions and privacy policy', 'shrinks' ); ?> <span class="required">*</span></label>
				</div>
			</div>
			
			<button type="submit" class="submit-btn" id="submit-btn">
				<?php echo __( 'Submit Application', 'shrinks' ); ?>
			</button>
		</form>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Form submission handler
		$('#therapist-registration-form').on('submit', function(e) {
			e.preventDefault();
			
			const submitBtn = $('#submit-btn');
			const messagesDiv = $('#form-messages');
			
			// Disable submit button and show loading
			submitBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Submitting...', 'shrinks' ) ); ?>');
			messagesDiv.empty();
			
			// Prepare form data
			const formData = new FormData(this);
			formData.append('action', 'register_therapist_shortcode');
			formData.append('nonce', '<?php echo wp_create_nonce( 'therapist_registration_shortcode' ); ?>');
			
			// Add OTP method info
			formData.append('otp_method', '<?php echo esc_js( $settings['otp_method'] ); ?>');
			
			// AJAX submission
			$.ajax({
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						if (response.data.step === 'otp_verification') {
							// Show OTP verification step
							showOtpVerification(response.data);
						} else {
							// Direct success
							messagesDiv.html('<div class="alert alert-success">' + response.data.message + '</div>');
							$('#therapist-registration-form')[0].reset();
						}
					} else {
						messagesDiv.html('<div class="alert alert-error">' + (response.data.message || 'Registration failed. Please try again.') + '</div>');
					}
				},
				error: function() {
					messagesDiv.html('<div class="alert alert-error">An error occurred. Please try again.</div>');
				},
				complete: function() {
					submitBtn.prop('disabled', false).text('<?php echo esc_js( __( 'Submit Application', 'shrinks' ) ); ?>');
				}
			});
		});
		
		// Auto-sync country codes for phone and WhatsApp
		<?php if ( $settings['country_dial_required'] ) : ?>
		$('#phone_country').on('change', function() {
			$('#whatsapp_country').val($(this).val());
		});
		
		$('#whatsapp_country').on('change', function() {
			$('#phone_country').val($(this).val());
		});
		<?php endif; ?>
		
		// Show OTP verification step
		function showOtpVerification(data) {
			const messagesDiv = $('#form-messages');
			messagesDiv.html('<div class="alert alert-success">' + data.message + '</div>');
			
			// Hide main form and show OTP verification form
			$('#therapist-registration-form').hide();
			
			const otpFormHtml = `
				<div id="otp-verification-form" class="therapist-reg-form">
					<h3>تحقق من رمز التأكيد</h3>
					<p class="text-info">تم إرسال رمز التحقق إلى: ${data.contact_method}</p>
					<div class="form-group">
						<label for="otp_code">رمز التحقق (6 أرقام):</label>
						<input type="text" id="otp_code" name="otp_code" maxlength="6" pattern="[0-9]{6}" 
							placeholder="أدخل الرمز المكون من 6 أرقام" class="form-control" style="text-align: center; font-size: 18px; letter-spacing: 2px;" autocomplete="one-time-code">
					</div>
					<button type="button" id="verify-otp-btn" class="submit-btn" style="background: #10b981;">تحقق من الرمز</button>
					<button type="button" id="cancel-otp-btn" class="submit-btn" style="background: #6b7280; margin-top: 10px;">إلغاء والعودة للنموذج</button>
					<input type="hidden" id="session_key" value="${data.session_key}">
				</div>
			`;
			
			$('#therapist-registration-form').after(otpFormHtml);
			
			// OTP input handler (numbers only)
			$('#otp_code').on('input', function() {
				this.value = this.value.replace(/\D/g, '');
			});
			
			// Verify OTP button handler
			$('#verify-otp-btn').on('click', function() {
				const otpCode = $('#otp_code').val();
				const sessionKey = $('#session_key').val();
				
				if (!otpCode || otpCode.length !== 6) {
					messagesDiv.html('<div class="alert alert-error">يرجى إدخال رمز التحقق المكون من 6 أرقام</div>');
					return;
				}
				
				// Disable button and show loading
				$(this).prop('disabled', true).text('جاري التحقق...');
				
				// Send verification request
				$.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					data: {
						action: 'register_therapist_shortcode',
						step: 'verify_otp',
						session_key: sessionKey,
						otp_code: otpCode,
						nonce: '<?php echo wp_create_nonce( 'therapist_registration_shortcode' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							messagesDiv.html('<div class="alert alert-success">' + response.data.message + '</div>');
							$('#otp-verification-form').remove();
							$('#therapist-registration-form')[0].reset();
							$('#therapist-registration-form').show();
						} else {
							messagesDiv.html('<div class="alert alert-error">' + (response.data.message || 'فشل في التحقق من الرمز') + '</div>');
						}
					},
					error: function() {
						messagesDiv.html('<div class="alert alert-error">حدث خطأ أثناء التحقق. حاول مرة أخرى.</div>');
					},
					complete: function() {
						$('#verify-otp-btn').prop('disabled', false).text('تحقق من الرمز');
					}
				});
			});
			
			// Cancel OTP button handler
			$('#cancel-otp-btn').on('click', function() {
				$('#otp-verification-form').remove();
				$('#therapist-registration-form').show();
				messagesDiv.empty();
			});
		}
	});
	</script>
	<?php
	
	return ob_get_clean();
}
add_shortcode( 'therapist_registration_form', 'snks_therapist_registration_shortcode' );

/**
 * Handle therapist registration form submission via shortcode
 */
function snks_handle_therapist_registration_shortcode() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'therapist_registration_shortcode' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}
	
	// Get settings
	$settings = snks_get_therapist_registration_settings();
	
	// Check if this is OTP verification step
	if ( isset( $_POST['step'] ) && $_POST['step'] === 'verify_otp' ) {
		snks_handle_therapist_registration_otp_verification();
		return;
	}
	
	// Validate required fields
	$required_fields = array( 'name', 'name_en', 'phone', 'whatsapp', 'doctor_specialty' );
	
	// Add email to required fields if it's required
	if ( $settings['require_email'] ) {
		$required_fields[] = 'email';
	}
	
	foreach ( $required_fields as $field ) {
		if ( empty( $_POST[ $field ] ) ) {
			wp_send_json_error( array( 'message' => sprintf( 'Missing required field: %s', $field ) ) );
		}
	}
	
	// Validate email if provided
	if ( ! empty( $_POST['email'] ) && ! is_email( $_POST['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Invalid email address' ) );
	}
	
	// Process phone numbers with country codes
	$phone = $_POST['phone'];
	$whatsapp = $_POST['whatsapp'];
	
	if ( $settings['country_dial_required'] ) {
		$country_codes = snks_get_country_dial_codes();
		$phone_country = $_POST['phone_country'] ?? $settings['default_country'];
		$whatsapp_country = $_POST['whatsapp_country'] ?? $settings['default_country'];
		
		if ( isset( $country_codes[ $phone_country ] ) ) {
			$phone = $country_codes[ $phone_country ]['code'] . $phone;
		}
		
		if ( isset( $country_codes[ $whatsapp_country ] ) ) {
			$whatsapp = $country_codes[ $whatsapp_country ]['code'] . $whatsapp;
		}
	}
	
	// Generate and send OTP based on method
	$otp_code = rand( 100000, 999999 );
	$otp_success = false;
	$contact_method = '';
	
	if ( $settings['otp_method'] === 'whatsapp' && ! empty( $whatsapp ) ) {
		$contact_method = $whatsapp;
		$message = sprintf( 'رمز التحقق الخاص بك لتسجيل المعالج في جلسة: %s', $otp_code );
		
		// Use existing WhySMS function
		$sms_result = send_sms_via_whysms( $whatsapp, $message );
		
		if ( ! is_wp_error( $sms_result ) ) {
			$otp_success = true;
		}
	} elseif ( $settings['otp_method'] === 'email' && ! empty( $_POST['email'] ) ) {
		$contact_method = $_POST['email'];
		$subject = 'رمز التحقق - تسجيل المعالج في جلسة';
		$message = sprintf( 'رمز التحقق الخاص بك: %s\n\nهذا الرمز صالح لمدة 10 دقائق.', $otp_code );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
		);
		
		if ( wp_mail( $contact_method, $subject, $message, $headers ) ) {
			$otp_success = true;
		}
	}
	
	if ( $otp_success ) {
		// Store OTP and form data temporarily
		$session_key = md5( $contact_method . time() );
		set_transient( 'therapist_reg_otp_' . $session_key, $otp_code, 10 * MINUTE_IN_SECONDS );
		set_transient( 'therapist_reg_data_' . $session_key, $_POST, 10 * MINUTE_IN_SECONDS );
		set_transient( 'therapist_reg_files_' . $session_key, $_FILES, 10 * MINUTE_IN_SECONDS );
		
		$otp_message = $settings['otp_method'] === 'whatsapp' 
			? 'تم إرسال رمز التحقق إلى واتساب.' 
			: 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.';
			
		wp_send_json_success( array( 
			'message' => $otp_message . ' يرجى إدخال الرمز للمتابعة.',
			'step' => 'otp_verification',
			'session_key' => $session_key,
			'contact_method' => $contact_method
		) );
	} else {
		$error_message = $settings['otp_method'] === 'whatsapp' 
			? 'فشل في إرسال رمز التحقق عبر واتساب.' 
			: 'فشل في إرسال رمز التحقق عبر البريد الإلكتروني.';
			
		wp_send_json_error( array( 'message' => $error_message . ' حاول مرة أخرى.' ) );
	}
	
}

/**
 * Handle OTP verification for therapist registration
 */
function snks_handle_therapist_registration_otp_verification() {
	$session_key = sanitize_text_field( $_POST['session_key'] ?? '' );
	$entered_otp = sanitize_text_field( $_POST['otp_code'] ?? '' );
	
	if ( empty( $session_key ) || empty( $entered_otp ) ) {
		wp_send_json_error( array( 'message' => 'Missing verification data' ) );
	}
	
	// Retrieve stored OTP and form data
	$stored_otp = get_transient( 'therapist_reg_otp_' . $session_key );
	$form_data = get_transient( 'therapist_reg_data_' . $session_key );
	$files_data = get_transient( 'therapist_reg_files_' . $session_key );
	
	if ( ! $stored_otp || ! $form_data ) {
		wp_send_json_error( array( 'message' => 'Verification code expired. Please try again.' ) );
	}
	
	if ( $entered_otp !== $stored_otp ) {
		wp_send_json_error( array( 'message' => 'Invalid verification code. Please try again.' ) );
	}
	
	// OTP verified, process the registration
	$settings = snks_get_therapist_registration_settings();
	
	// Process phone numbers with country codes (recreate from stored data)
	$phone = $form_data['phone'];
	$whatsapp = $form_data['whatsapp'];
	
	if ( $settings['country_dial_required'] ) {
		$country_codes = snks_get_country_dial_codes();
		$phone_country = $form_data['phone_country'] ?? $settings['default_country'];
		$whatsapp_country = $form_data['whatsapp_country'] ?? $settings['default_country'];
		
		if ( isset( $country_codes[ $phone_country ] ) ) {
			$phone = $country_codes[ $phone_country ]['code'] . $phone;
		}
		
		if ( isset( $country_codes[ $whatsapp_country ] ) ) {
			$whatsapp = $country_codes[ $whatsapp_country ]['code'] . $whatsapp;
		}
	}
	
	// Handle file uploads using the stored $_FILES data
	$uploaded_files = array();
	$file_fields = array( 'profile_image', 'identity_front', 'identity_back' );
	
	// Restore $_FILES from stored data for processing
	if ( $files_data ) {
		foreach ( $file_fields as $field ) {
			if ( ! empty( $files_data[ $field ]['name'] ) ) {
				// Create a temporary file upload array
				$file_array = array(
					'name'     => $files_data[ $field ]['name'],
					'type'     => $files_data[ $field ]['type'],
					'tmp_name' => $files_data[ $field ]['tmp_name'],
					'error'    => $files_data[ $field ]['error'],
					'size'     => $files_data[ $field ]['size']
				);
				
				// Only process if the temporary file still exists
				if ( file_exists( $file_array['tmp_name'] ) ) {
					$_FILES[ $field ] = $file_array;
					$attachment_id = media_handle_upload( $field, 0 );
					if ( ! is_wp_error( $attachment_id ) ) {
						$uploaded_files[ $field ] = $attachment_id;
					}
				}
			}
		}
		
		// Handle certificates (multiple files)
		$certificates = array();
		if ( ! empty( $files_data['certificates'] ) ) {
			$files = $files_data['certificates'];
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				if ( ! empty( $files['name'][ $i ] ) && file_exists( $files['tmp_name'][ $i ] ) ) {
					$_FILES['certificate_' . $i] = array(
						'name' => $files['name'][ $i ],
						'type' => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error' => $files['error'][ $i ],
						'size' => $files['size'][ $i ]
					);
					
					$attachment_id = media_handle_upload( 'certificate_' . $i, 0 );
					if ( ! is_wp_error( $attachment_id ) ) {
						$certificates[] = $attachment_id;
					}
				}
			}
		}
	}
	
	// Insert into database
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$result = $wpdb->insert(
		$table_name,
		array(
			'name' => sanitize_text_field( $form_data['name'] ),
			'name_en' => sanitize_text_field( $form_data['name_en'] ),
			'email' => sanitize_email( $form_data['email'] ?? '' ),
			'phone' => sanitize_text_field( $phone ),
			'whatsapp' => sanitize_text_field( $whatsapp ),
			'doctor_specialty' => sanitize_text_field( $form_data['doctor_specialty'] ),
			'profile_image' => $uploaded_files['profile_image'] ?? null,
			'identity_front' => $uploaded_files['identity_front'] ?? null,
			'identity_back' => $uploaded_files['identity_back'] ?? null,
			'certificates' => ! empty( $certificates ) ? json_encode( $certificates ) : null,
			'status' => 'pending',
			'otp_method' => $settings['otp_method'],
			'submitted_at' => current_time( 'mysql' )
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
	);
	
	if ( $result ) {
		// Clean up transients
		delete_transient( 'therapist_reg_otp_' . $session_key );
		delete_transient( 'therapist_reg_data_' . $session_key );
		delete_transient( 'therapist_reg_files_' . $session_key );
		
		// Send notification email to admin
		$admin_email = get_option( 'admin_email' );
		$subject = 'New Therapist Registration Application';
		$message = sprintf(
			"A new therapist has submitted a registration application.\n\nName: %s\nEmail: %s\nPhone: %s\nSpecialty: %s\n\nPlease review the application in the admin dashboard.",
			$form_data['name'],
			$form_data['email'] ?? 'Not provided',
			$phone,
			$form_data['doctor_specialty']
		);
		
		wp_mail( $admin_email, $subject, $message );
		
		wp_send_json_success( array( 'message' => 'تم التحقق بنجاح! تم إرسال طلبك وسيتم مراجعته قريباً.' ) );
	} else {
		wp_send_json_error( array( 'message' => 'حدث خطأ أثناء حفظ الطلب. حاول مرة أخرى.' ) );
	}
}

add_action( 'wp_ajax_register_therapist_shortcode', 'snks_handle_therapist_registration_shortcode' );
add_action( 'wp_ajax_nopriv_register_therapist_shortcode', 'snks_handle_therapist_registration_shortcode' );
