<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_action(
	'wp_footer',
	function () {
		if ( snks_is_patient() ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Pre-fill radio options and show relevant fields on page load
				var selectedMethod = $('input[name="withdrawal_method"]:checked').val();
				if (selectedMethod) {
					$('input[value="' + selectedMethod + '"]').closest('.withdrawal-radio').find('.withdrawal-accounts-fields').show();
				}

				// Hide all the fields initially, then show the selected one
				$('.withdrawal-accounts-fields').hide();
				$('input[name="withdrawal_method"]:checked').closest('.withdrawal-radio').find('.withdrawal-accounts-fields').show();

				// Select all radio buttons within the withdrawal-options container
				$('body').on(
					'change',
					'.withdrawal-options input[type="radio"]',
					function() {
					if ( $(this).closest('.withdrawal-options').find('.manual-withdrawal-button').length > 0 ) {
						if ( $(this).val() === 'manual_withdrawal' ) {
							$('.manual-withdrawal-button').slideDown();
						} else {
							$('.manual-withdrawal-button').slideUp();
						}
					}

					var parent = $(this).closest('.withdrawal-options');
					
					// Remove 'checked' class from all .anony-custom-radio spans
					$('.anony-custom-radio', parent).removeClass('checked');
					
					// Add 'checked' class to the corresponding span of the selected radio
					$(this).next('label').find('.anony-custom-radio').addClass('checked');
				});
				// Select all radio buttons within the withdrawal-options container
				$('body').on(
					'change',
					'input[name="withdrawal_method"]',
					function() {
					// Hide all field containers first
					$('.withdrawal-accounts-fields').slideUp();
					
					// Show the field container related to the selected radio
					$(this).closest('.withdrawal-radio').find('.withdrawal-accounts-fields').slideDown();
				});


				// Handle sending OTP
				$('body').on(
					'click',
					'#send-otp',
					function(e) {
					e.preventDefault();

					// Disable the OTP button and display a message
					$(this).text('جارٍ إرسال كود التحقق...');
					$(this).attr('disabled', true);
					var formData = $('#withdrawal-settings-form').serialize(); // Serialize form data including the method and specific fields
					// Send AJAX request to generate and send OTP
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						data: formData + '&action=send_email_otp', // Action for backend to handle OTP generation
						success: function(response) {
							if (response.success) {
								Swal.fire({
									icon: 'success', // Success icon to indicate the action was successful
									title: 'تم بنجاح',
									text: 'تم إرسال كود التحقق بنجاح إلى بريدك الإلكتروني/موبايلك.', // The original success message
									confirmButtonText: 'غلق'
								});
								// Show the OTP section and enable submit button
								$('#otp-section').slideDown();
								$('#submit-withdrawal-form').show();
								$('#send-otp').text('إرسال كود التحقق'); // Reset button text
								$('#send-otp').attr('disabled', false);
							} else {
								Swal.fire({
									icon: 'error', // Error icon to indicate the issue
									title: 'خطأ',
									text: response.data.message, // The original error message
									confirmButtonText: 'موافق'
								});
								$('#send-otp').text('إرسال كود التحقق');
								$('#send-otp').attr('disabled', false);
							}
						},
						error: function() {
							Swal.fire({
								icon: 'error',
								title: 'خطأ',
								text: 'حدث خطأ في إرسال كود التحقق.',
								confirmButtonText: 'موافق'
							});
							$('#send-otp').text('إرسال كود التحقق');
							$('#send-otp').attr('disabled', false);
						}
					});
				});

				// Handle form submission with OTP verification
				$('body').on(
					'click',
					'#submit-withdrawal-form',
					function(e) {
					e.preventDefault();

					var formData = $('#withdrawal-settings-form').serialize(); // Serialize the form data including OTP
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						data: formData + '&action=verify_otp_and_save_withdrawal', // Pass the action for OTP verification and withdrawal submission
						success: function(response) {
							if(response.success) {
								Swal.fire({
									icon: 'success', // Error icon to indicate the issue
									title: 'تم',
									text: response.data.message, // The original error message
									confirmButtonText: 'غلق'
								});
							} else {
								Swal.fire({
									icon: 'error', // Error icon to indicate the issue
									title: 'خطأ',
									text: response.data.message, // The original error message
									confirmButtonText: 'غلق'
								});
							}
							setCookie( 'edited_withdrawal_form', '', 0 );
						},
						error: function() {
							Swal.fire({
									icon: 'error', // Error icon to indicate the issue
									title: 'خطأ',
									text: 'حدث خطأ أثناء تقديم النموذج.', // The original error message
									confirmButtonText: 'غلق'
								});
						}
					});
				});
			});
		</script>
		<?php
	}
);


add_action( 'wp_footer', 'disable_withdrawal_form_based_on_time' );

/**
 * Disable withdrwal form between 12 am and 9 am
 *
 * @return void
 */
function disable_withdrawal_form_based_on_time() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Function to check the current time and disable/enable the form
			function checkTimeAndDisableForm() {
				var currentDate = new Date();
				var currentHour = currentDate.getHours();

				// Disable the form and add overlay between 12 AM (0) and 9 AM (9)
				if (currentHour >= 0 && currentHour < 9) {
					$('#withdrawal-settings-form').css('pointer-events', 'none'); // Disable form
					$('#withdrawal-settings-form').css('opacity', '0.5'); // Reduce opacity for a disabled effect
					
					// Add an overlay layer
					if (!$('#withdrawal-form-overlay').length) {
						$('#withdrawal-settings-form').prepend('<div id="withdrawal-form-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;"></div>');
					}
				} else {
					// Enable the form and remove the overlay after 9 AM
					$('#withdrawal-settings-form').css('pointer-events', 'auto');
					$('#withdrawal-settings-form').css('opacity', '1');
					$('#withdrawal-form-overlay').remove(); // Remove overlay if exists
				}
			}
			setInterval(function(){
				checkTimeAndDisableForm();
			}, '10000');
			// Run the check when the page loads
			checkTimeAndDisableForm();
		});
	</script>
	<?php
}


add_action(
	'wp_footer',
	function () {
		?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			// Use delegated event handling for dynamically added buttons
			$(document).on('click', '.details-button', function (e) {
				e.preventDefault();
				// Retrieve data attributes
				const dateTime = $(this).data('date-time');
				const attendanceType = $(this).data('attendance-type');
				const clientName = $(this).data('client-name');
				const coupon = $(this).data('coupon');
				const couponType = $(this).data('coupon-type');
				const couponHtml = coupon && coupon !== '' ? `<p><strong>الكوبون:</strong> ${coupon} <span style="color: #666; font-size: 0.9em;">(${couponType === 'AI' ? '🤖 AI فقط' : couponType === 'General' ? '📋 عام' : 'غير معروف'})</span></p>` : '';
				// Display details in Arabic using SweetAlert2
				Swal.fire({
					title: 'تفاصيل الجلسة',
					html: `
						<p><strong>تاريخ ووقت الجلسة:</strong> ${dateTime}</p>
						<p><strong>نوع الحضور:</strong> ${attendanceType}</p>
						<p><strong>اسم العميل:</strong> ${clientName}</p>
						${couponHtml}
					`,
					icon: 'info',
					confirmButtonText: 'أغلق'
				});
			});

			jQuery(document).on('click', '.manual-withdrawal-button .withdrawal-button', function (e) {
				e.preventDefault();

				// Retrieve the nonce from the parent div
				const nonce = jQuery(this).closest('.manual-withdrawal-button').data('nonce');

				// Confirm withdrawal action using SweetAlert2
				Swal.fire({
					title: 'تأكيد السحب',
					text: 'هل أنت متأكد أنك تريد تنفيذ عملية السحب؟',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'نعم، قم بالسحب!',
					cancelButtonText: 'إلغاء',
				}).then((result) => {
					if (result.isConfirmed) {
						// Show a loading indicator
						Swal.fire({
							title: 'جاري التنفيذ...',
							text: 'يرجى الانتظار حتى يتم معالجة السحب.',
							icon: 'info',
							showConfirmButton: false,
							allowOutsideClick: false,
						});

						// Run AJAX to process withdrawal
						jQuery.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // WordPress AJAX handler
							method: 'POST',
							data: {
								action: 'process_manual_withdrawal',
								security: nonce, // Include the nonce in the AJAX request
							},
							success: function (response) {
								if (response.success) {
									Swal.fire({
										title: 'تم بنجاح!',
										text: response.data.msg,
										icon: 'success',
										confirmButtonText: 'إغلاق',
									}).then((result) => {
										location.reload();
									});
								} else {
									Swal.fire({
										title: 'فشل!',
										text: response.data.msg || 'حدث خطأ أثناء التنفيذ.',
										icon: 'error',
										confirmButtonText: 'إغلاق',
									});
								}
							},
							error: function () {
								Swal.fire({
									title: 'فشل!',
									text: 'تعذر تنفيذ الطلب. حاول مرة أخرى لاحقًا.',
									icon: 'error',
									confirmButtonText: 'إغلاق',
								});
							},
						});
					}
				});
			});


		});
	</script>
		<?php
	}
);

