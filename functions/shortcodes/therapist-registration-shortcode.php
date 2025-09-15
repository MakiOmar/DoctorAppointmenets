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
			position: relative;
			border: 2px dashed #ddd;
			padding: 30px 20px;
			text-align: center;
			border-radius: 8px;
			background: #fafafa;
			transition: all 0.3s ease;
			cursor: pointer;
		}
		.file-upload-group:hover, .file-upload-group.dragover {
			border-color: #2271b1;
			background: #f0f6ff;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(34, 113, 177, 0.1);
		}
		.file-upload-group input[type="file"] {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			opacity: 0;
			cursor: pointer;
		}
		.upload-icon {
			font-size: 48px;
			color: #2271b1;
			margin-bottom: 15px;
			display: block;
		}
		.upload-text {
			font-size: 16px;
			color: #333;
			margin-bottom: 8px;
			font-weight: 600;
		}
		.upload-hint {
			font-size: 14px;
			color: #666;
			margin-bottom: 15px;
		}
		.file-preview {
			display: flex;
			flex-wrap: wrap;
			gap: 15px;
			margin-top: 20px;
			justify-content: center;
		}
		.preview-item {
			position: relative;
			width: 120px;
			height: 120px;
			border-radius: 8px;
			overflow: hidden;
			border: 2px solid #e0e0e0;
			background: #fff;
		}
		.preview-image {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.preview-file {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 10px;
			height: 100%;
			text-align: center;
		}
		.file-icon {
			font-size: 24px;
			color: #2271b1;
			margin-bottom: 8px;
		}
		.file-name {
			font-size: 12px;
			color: #333;
			word-break: break-all;
			line-height: 1.2;
		}
		.remove-file {
			position: absolute;
			top: 5px;
			right: 5px;
			width: 24px;
			height: 24px;
			background: #ff4757;
			color: white;
			border: none;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 14px;
			z-index: 10;
		}
		.remove-file:hover {
			background: #ff3838;
		}
		.upload-progress {
			width: 100%;
			height: 6px;
			background: #e0e0e0;
			border-radius: 3px;
			margin-top: 10px;
			overflow: hidden;
		}
		.progress-bar {
			height: 100%;
			background: linear-gradient(90deg, #2271b1, #4fc3f7);
			transition: width 0.3s ease;
			border-radius: 3px;
		}
		.file-size {
			font-size: 11px;
			color: #888;
			margin-top: 4px;
		}
		.max-files-notice {
			background: #fff3cd;
			color: #856404;
			padding: 12px;
			border-radius: 6px;
			margin-top: 15px;
			border: 1px solid #ffeaa7;
			font-size: 14px;
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
				<div class="file-upload-group" data-field="profile_image">
					<span class="upload-icon">📷</span>
					<div class="upload-text"><?php echo __( 'Upload Profile Photo', 'shrinks' ); ?></div>
					<div class="upload-hint"><?php echo __( 'Click or drag image here (JPG, PNG, max 5MB)', 'shrinks' ); ?></div>
					<input type="file" id="profile_image" name="profile_image" accept="image/*" data-max-size="5242880">
					<div class="file-preview" id="preview_profile_image"></div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="identity_front"><?php echo __( 'Identity Document (Front)', 'shrinks' ); ?></label>
				<div class="file-upload-group" data-field="identity_front">
					<span class="upload-icon">🪪</span>
					<div class="upload-text"><?php echo __( 'Upload ID Front Side', 'shrinks' ); ?></div>
					<div class="upload-hint"><?php echo __( 'Click or drag image here (ID front, max 5MB)', 'shrinks' ); ?></div>
					<input type="file" id="identity_front" name="identity_front" accept="image/*" data-max-size="5242880">
					<div class="file-preview" id="preview_identity_front"></div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="identity_back"><?php echo __( 'Identity Document (Back)', 'shrinks' ); ?></label>
				<div class="file-upload-group" data-field="identity_back">
					<span class="upload-icon">🆔</span>
					<div class="upload-text"><?php echo __( 'Upload ID Back Side', 'shrinks' ); ?></div>
					<div class="upload-hint"><?php echo __( 'Click or drag image here (ID back, max 5MB)', 'shrinks' ); ?></div>
					<input type="file" id="identity_back" name="identity_back" accept="image/*" data-max-size="5242880">
					<div class="file-preview" id="preview_identity_back"></div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="certificates"><?php echo __( 'Certificates & Qualifications', 'shrinks' ); ?></label>
				<div class="file-upload-group" data-field="certificates" data-multiple="true">
					<span class="upload-icon">📜</span>
					<div class="upload-text"><?php echo __( 'Upload Certificates', 'shrinks' ); ?></div>
					<div class="upload-hint"><?php echo __( 'Click or drag files here (PDF, JPG, PNG - multiple files, max 10MB each)', 'shrinks' ); ?></div>
					<input type="file" id="certificates" name="certificates[]" accept=".pdf,image/*" multiple data-max-size="10485760">
					<div class="file-preview" id="preview_certificates"></div>
					<div class="max-files-notice" style="display: none;">
						<?php echo __( 'Maximum 10 files allowed. Please remove some files before adding more.', 'shrinks' ); ?>
					</div>
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
		
		// Fancy File Upload Functionality
		function initFancyUploads() {
			$('.file-upload-group').each(function() {
				const $uploadGroup = $(this);
				const $input = $uploadGroup.find('input[type="file"]');
				const $preview = $uploadGroup.find('.file-preview');
				const fieldName = $uploadGroup.data('field');
				const isMultiple = $uploadGroup.data('multiple') === true;
				const maxSize = parseInt($input.data('max-size')) || 5242880; // Default 5MB
				const maxFiles = 10;
				
				let selectedFiles = [];
				
				// Drag and drop events
				$uploadGroup.on('dragover dragenter', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).addClass('dragover');
				});
				
				$uploadGroup.on('dragleave dragend', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('dragover');
				});
				
				$uploadGroup.on('drop', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('dragover');
					
					const files = e.originalEvent.dataTransfer.files;
					handleFiles(files);
				});
				
				// Click to upload
				$input.on('change', function() {
					handleFiles(this.files);
				});
				
				function handleFiles(files) {
					for (let i = 0; i < files.length; i++) {
						const file = files[i];
						
						// Check file size
						if (file.size > maxSize) {
							const sizeMB = (maxSize / 1024 / 1024).toFixed(1);
							alert('File "' + file.name + '" is too large. Maximum size is ' + sizeMB + 'MB');
							continue;
						}
						
						// Check max files for multiple uploads
						if (isMultiple && selectedFiles.length >= maxFiles) {
							$uploadGroup.find('.max-files-notice').show();
							break;
						}
						
						// For single file uploads, replace existing
						if (!isMultiple) {
							selectedFiles = [];
							$preview.empty();
						}
						
						selectedFiles.push(file);
						addFilePreview(file);
					}
					
					updateFileInput();
				}
				
				function addFilePreview(file) {
					const fileId = 'file_' + Math.random().toString(36).substr(2, 9);
					const isImage = file.type.startsWith('image/');
					const isPDF = file.type === 'application/pdf';
					
					let previewHtml = `
						<div class="preview-item" data-file-id="${fileId}">
							<button type="button" class="remove-file" onclick="removeFile('${fieldName}', '${fileId}')">&times;</button>
					`;
					
					if (isImage) {
						const reader = new FileReader();
						reader.onload = function(e) {
							$(`[data-file-id="${fileId}"] .preview-content`).html(`
								<img src="${e.target.result}" alt="${file.name}" class="preview-image">
							`);
						};
						reader.readAsDataURL(file);
						
						previewHtml += `
							<div class="preview-content">
								<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
									<span>Loading...</span>
								</div>
							</div>
						`;
					} else {
						let icon = '📄';
						if (isPDF) icon = '📋';
						
						previewHtml += `
							<div class="preview-file">
								<div class="file-icon">${icon}</div>
								<div class="file-name">${file.name}</div>
								<div class="file-size">${formatFileSize(file.size)}</div>
							</div>
						`;
					}
					
					previewHtml += '</div>';
					$preview.append(previewHtml);
					
					// Store file reference
					$(`[data-file-id="${fileId}"]`).data('file', file);
				}
				
				function formatFileSize(bytes) {
					if (bytes === 0) return '0 Bytes';
					const k = 1024;
					const sizes = ['Bytes', 'KB', 'MB', 'GB'];
					const i = Math.floor(Math.log(bytes) / Math.log(k));
					return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
				}
				
				// Global function to remove files
				window.removeFile = function(fieldName, fileId) {
					const $uploadGroup = $(`.file-upload-group[data-field="${fieldName}"]`);
					const $preview = $uploadGroup.find('.file-preview');
					const $item = $preview.find(`[data-file-id="${fileId}"]`);
					
					// Remove from selectedFiles array
					const fileIndex = selectedFiles.findIndex(f => 
						$item.data('file') && $item.data('file').name === f.name
					);
					if (fileIndex > -1) {
						selectedFiles.splice(fileIndex, 1);
					}
					
					$item.remove();
					updateFileInput();
					
					// Hide max files notice if under limit
					if (selectedFiles.length < maxFiles) {
						$uploadGroup.find('.max-files-notice').hide();
					}
				};
				
				function updateFileInput() {
					// Create a new DataTransfer object to update the input
					const dt = new DataTransfer();
					selectedFiles.forEach(file => dt.items.add(file));
					$input[0].files = dt.files;
				}
			});
		}
		
		// Initialize fancy uploads
		initFancyUploads();
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
	
	if ( $settings['otp_method'] === 'sms' && ! empty( $whatsapp ) ) {
		$contact_method = $whatsapp;
		$message = snks_get_multilingual_otp_message( $otp_code, $settings['whatsapp_message_language'] ?? 'ar' );
		
		// Use existing WhySMS SMS service
		$sms_result = send_sms_via_whysms( $whatsapp, $message );
		
		if ( ! is_wp_error( $sms_result ) ) {
			$otp_success = true;
		}
	} elseif ( $settings['otp_method'] === 'whatsapp' && ! empty( $whatsapp ) ) {
		$contact_method = $whatsapp;
		$message = snks_get_multilingual_otp_message( $otp_code, $settings['whatsapp_message_language'] ?? 'ar' );
		
		// Use WhatsApp Business API
		$whatsapp_result = snks_send_whatsapp_message( $whatsapp, $message, $settings );
		
		if ( $whatsapp_result && ! is_wp_error( $whatsapp_result ) ) {
			$otp_success = true;
		}
	} elseif ( $settings['otp_method'] === 'email' && ! empty( $_POST['email'] ) ) {
		$contact_method = $_POST['email'];
		$email_content = snks_get_multilingual_email_otp_message( $otp_code, $settings['whatsapp_message_language'] ?? 'ar' );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
		);
		
		if ( wp_mail( $contact_method, $email_content['subject'], $email_content['body'], $headers ) ) {
			$otp_success = true;
		}
	}
	
	if ( $otp_success ) {
		// Store OTP and form data temporarily
		$session_key = md5( $contact_method . time() );
		set_transient( 'therapist_reg_otp_' . $session_key, $otp_code, 10 * MINUTE_IN_SECONDS );
		set_transient( 'therapist_reg_data_' . $session_key, $_POST, 10 * MINUTE_IN_SECONDS );
		set_transient( 'therapist_reg_files_' . $session_key, $_FILES, 10 * MINUTE_IN_SECONDS );
		
		$otp_message = '';
		if ( $settings['otp_method'] === 'sms' ) {
			$otp_message = 'تم إرسال رمز التحقق عبر الرسائل القصيرة.';
		} elseif ( $settings['otp_method'] === 'whatsapp' ) {
			$otp_message = 'تم إرسال رمز التحقق إلى واتساب.';
		} else {
			$otp_message = 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.';
		}
			
		wp_send_json_success( array( 
			'message' => $otp_message . ' يرجى إدخال الرمز للمتابعة.',
			'step' => 'otp_verification',
			'session_key' => $session_key,
			'contact_method' => $contact_method
		) );
	} else {
		$error_message = '';
		if ( $settings['otp_method'] === 'sms' ) {
			$error_message = 'فشل في إرسال رمز التحقق عبر الرسائل القصيرة.';
		} elseif ( $settings['otp_method'] === 'whatsapp' ) {
			$error_message = 'فشل في إرسال رمز التحقق عبر واتساب.';
		} else {
			$error_message = 'فشل في إرسال رمز التحقق عبر البريد الإلكتروني.';
		}
			
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

/**
 * Get multilingual OTP message for therapist registration
 */
function snks_get_multilingual_otp_message( $otp_code, $language = 'ar' ) {
	$messages = array(
		'ar' => 'رمز التحقق: %s',
		'en' => 'Verification code: %s',
		'fr' => 'Code de vérification: %s',
		'es' => 'Código de verificación: %s',
		'de' => 'Bestätigungscode: %s',
		'it' => 'Codice di verifica: %s',
		'tr' => 'Doğrulama kodu: %s',
		'ur' => 'تصدیقی کوڈ: %s'
	);
	
	// Fallback to Arabic if language not found
	$template = isset( $messages[ $language ] ) ? $messages[ $language ] : $messages['ar'];
	
	return sprintf( $template, $otp_code );
}

/**
 * Get multilingual email OTP message for therapist registration
 */
function snks_get_multilingual_email_otp_message( $otp_code, $language = 'ar' ) {
	$messages = array(
		'ar' => array(
			'subject' => 'رمز التحقق - تسجيل المعالج في جلسة',
			'body' => 'رمز التحقق الخاص بك: %s

هذا الرمز صالح لمدة 10 دقائق.'
		),
		'en' => array(
			'subject' => 'Verification Code - Jalsah Therapist Registration',
			'body' => 'Your verification code: %s

This code is valid for 10 minutes.'
		),
		'fr' => array(
			'subject' => 'Code de vérification - Inscription thérapeute Jalsah',
			'body' => 'Votre code de vérification: %s

Ce code est valide pendant 10 minutes.'
		),
		'es' => array(
			'subject' => 'Código de verificación - Registro de terapeuta Jalsah',
			'body' => 'Su código de verificación: %s

Este código es válido por 10 minutos.'
		),
		'de' => array(
			'subject' => 'Bestätigungscode - Jalsah Therapeutenregistrierung',
			'body' => 'Ihr Bestätigungscode: %s

Dieser Code ist 10 Minuten gültig.'
		),
		'it' => array(
			'subject' => 'Codice di verifica - Registrazione terapeuta Jalsah',
			'body' => 'Il tuo codice di verifica: %s

Questo codice è valido per 10 minuti.'
		),
		'tr' => array(
			'subject' => 'Doğrulama Kodu - Jalsah Terapist Kaydı',
			'body' => 'Doğrulama kodunuz: %s

Bu kod 10 dakika geçerlidir.'
		),
		'ur' => array(
			'subject' => 'تصدیقی کوڈ - جلسہ تھراپسٹ رجسٹریشن',
			'body' => 'آپ کا تصدیقی کوڈ: %s

یہ کوڈ 10 منٹ کے لیے درست ہے۔'
		)
	);
	
	// Fallback to Arabic if language not found
	$template = isset( $messages[ $language ] ) ? $messages[ $language ] : $messages['ar'];
	
	return array(
		'subject' => $template['subject'],
		'body' => sprintf( $template['body'], $otp_code )
	);
}

/**
 * Send WhatsApp message using WhatsApp Business API
 */
function snks_send_whatsapp_message( $phone_number, $message, $settings ) {
	// Get WhatsApp API settings
	$api_url = $settings['whatsapp_api_url'];
	$access_token = $settings['whatsapp_api_token'];
	$phone_number_id = $settings['whatsapp_phone_number_id'];
	
	// Check if all required settings are available
	if ( empty( $api_url ) || empty( $access_token ) || empty( $phone_number_id ) ) {
		return new WP_Error( 'missing_config', 'WhatsApp API configuration is incomplete' );
	}
	
	// Format phone number (ensure it has proper format without + prefix for API)
	$phone_number = ltrim( $phone_number, '+' );
	
	// Prepare API endpoint - updated to match Meta's format
	$endpoint = rtrim( $api_url, '/' ) . '/' . $phone_number_id . '/messages';
	
	// Prepare request body - conditional template or text message
	$use_template = isset( $settings['whatsapp_use_template'] ) ? $settings['whatsapp_use_template'] : 1;
	
	if ( $use_template ) {
		// Use template message format for guaranteed delivery
		$template_name = isset( $settings['whatsapp_template_name'] ) ? $settings['whatsapp_template_name'] : 'hello_world';
		$template_language = $settings['whatsapp_message_language'] === 'ar' ? 'ar' : 'en_US';
		
		// Extract verification code from message (assuming it's the first 6-digit number)
		preg_match('/\b\d{6}\b/', $message, $matches);
		$verification_code = isset($matches[0]) ? $matches[0] : '123456';
		
		// Debug template parameters
		error_log( '=== WHATSAPP TEMPLATE DEBUG ===' );
		error_log( 'Template Name: ' . $template_name );
		error_log( 'Template Language: ' . $template_language );
		error_log( 'Original Message: ' . $message );
		error_log( 'Extracted Verification Code: ' . $verification_code );
		error_log( '===============================' );
		
		// Build components array for OTP template
		$components = array();
		
		// Add body component with verification code
		$components[] = array(
			'type' => 'body',
			'parameters' => array(
				array(
					'type' => 'text',
					'text' => $verification_code
				)
			)
		);
		
		// Add OTP button component for auto-fill functionality
		$components[] = array(
			'type' => 'button',
			'sub_type' => 'otp',
			'index' => '0',
			'parameters' => array(
				array(
					'type' => 'otp',
					'otp' => $verification_code
				)
			)
		);
		
		$body = array(
			'messaging_product' => 'whatsapp',
			'to' => $phone_number,
			'type' => 'template',
			'template' => array(
				'name' => $template_name,
				'language' => array(
					'code' => $template_language
				),
				'components' => $components
			)
		);
	} else {
		// Use text message format (requires active conversation)
		$body = array(
			'messaging_product' => 'whatsapp',
			'to' => $phone_number,
			'type' => 'text',
			'text' => array(
				'body' => $message
			)
		);
	}
	
	// Prepare headers - exactly matching Meta's format
	$headers = array(
		'Authorization' => 'Bearer ' . $access_token,
		'Content-Type' => 'application/json',
	);
	
	// Make API request with exact Meta specifications
	$response = wp_remote_post( $endpoint, array(
		'headers' => $headers,
		'body' => wp_json_encode( $body ),
		'timeout' => 30,
		'sslverify' => true, // Ensure SSL verification for Meta API
	) );
	
	// Check for errors
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	// Get response body and code
	$response_body = wp_remote_retrieve_body( $response );
	$response_code = wp_remote_retrieve_response_code( $response );
	
	// Enhanced logging for debugging
	error_log( '=== WHATSAPP API DEBUG ===' );
	error_log( 'Endpoint: ' . $endpoint );
	error_log( 'Original Phone Number: ' . $phone_number );
	error_log( 'Phone Number Length: ' . strlen( $phone_number ) );
	error_log( 'Phone Number ID: ' . $phone_number_id );
	error_log( 'Access Token (first 20 chars): ' . substr( $access_token, 0, 20 ) . '...' );
	error_log( 'Use Template: ' . ( $use_template ? 'Yes' : 'No' ) );
	error_log( 'Template Name: ' . ( $template_name ?? 'N/A' ) );
	error_log( 'Request Body: ' . wp_json_encode( $body ) );
	error_log( 'Message Length: ' . strlen( $message ) );
	error_log( 'Message: ' . $message );
	error_log( 'Response Code: ' . $response_code );
	error_log( 'Response Body: ' . $response_body );
	
	// Check if this is a registration vs test message
	$is_registration = strpos( $message, 'رمز التحقق' ) !== false || strpos( $message, 'verification' ) !== false;
	error_log( 'Message Type: ' . ( $is_registration ? 'REGISTRATION OTP' : 'TEST MESSAGE' ) );
	error_log( '=========================' );
	
	// Check response code - Meta typically returns 200 for success
	if ( $response_code !== 200 ) {
		$error_data = json_decode( $response_body, true );
		$error_message = 'WhatsApp API request failed';
		
		// Extract detailed error message from Meta's response format
		if ( isset( $error_data['error']['message'] ) ) {
			$error_message = $error_data['error']['message'];
		} elseif ( isset( $error_data['error']['error_user_msg'] ) ) {
			$error_message = $error_data['error']['error_user_msg'];
		}
		
		return new WP_Error( 'api_error', $error_message, array( 
			'response_code' => $response_code,
			'response_body' => $response_body 
		) );
	}
	
	// Parse response data
	$response_data = json_decode( $response_body, true );
	
	// Check if the response contains message ID (indicates successful delivery to WhatsApp)
	if ( isset( $response_data['messages'][0]['id'] ) ) {
		error_log( 'WhatsApp API Success - Message ID: ' . $response_data['messages'][0]['id'] );
	} else {
		error_log( 'WhatsApp API Warning - No message ID in response: ' . print_r( $response_data, true ) );
	}
	
	// Return success response
	return $response_data;
}
