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
		if ( ! snks_is_doctor() ) {
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
				$('.withdrawal-options input[type="radio"]').on('change', function() {
					if ( $(this).val() === 'manual_withdrawal' ) {
						$('.manual-withdrawal-button').slideDown();
					} else {
						$('.manual-withdrawal-button').slideUp();
					}
					var parent = $(this).closest('.withdrawal-options');
					
					// Remove 'checked' class from all .anony-custom-radio spans
					$('.anony-custom-radio', parent).removeClass('checked');
					
					// Add 'checked' class to the corresponding span of the selected radio
					$(this).next('label').find('.anony-custom-radio').addClass('checked');
				});
				// Select all radio buttons within the withdrawal-options container
				$('input[name="withdrawal_method"]').on('change', function() {
					// Hide all field containers first
					$('.withdrawal-accounts-fields').slideUp();
					
					// Show the field container related to the selected radio
					$(this).closest('.withdrawal-radio').find('.withdrawal-accounts-fields').slideDown();
				});


				// Handle sending OTP
				$('#send-otp').on('click', function(e) {
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
								alert('تم إرسال كود التحقق بنجاح إلى بريدك الإلكتروني/موبايلك.');
								// Show the OTP section and enable submit button
								$('#otp-section').slideDown();
								$('#submit-withdrawal-form').show();
								$('#send-otp').text('إرسال كود التحقق'); // Reset button text
								$('#send-otp').attr('disabled', false);
							} else {
								alert(response.data.message); // Show error message
								$('#send-otp').text('إرسال كود التحقق');
								$('#send-otp').attr('disabled', false);
							}
						},
						error: function() {
							alert('حدث خطأ في إرسال كود التحقق.');
							$('#send-otp').text('إرسال كود التحقق');
							$('#send-otp').attr('disabled', false);
						}
					});
				});

				// Handle form submission with OTP verification
				$('#submit-withdrawal-form').on('click', function(e) {
					e.preventDefault();

					var formData = $('#withdrawal-settings-form').serialize(); // Serialize the form data including OTP
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						data: formData + '&action=verify_otp_and_save_withdrawal', // Pass the action for OTP verification and withdrawal submission
						success: function(response) {
							if(response.success) {
								alert(response.data.message); // Success message
							} else {
								alert(response.data.message); // OTP or form submission error
							}
						},
						error: function() {
							alert('حدث خطأ أثناء تقديم النموذج.');
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

			// Run the check when the page loads
			checkTimeAndDisableForm();
		});
	</script>
	<?php
}
