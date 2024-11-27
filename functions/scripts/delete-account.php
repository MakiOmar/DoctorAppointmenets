<?php
/**
 * Delete account
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		if ( ! is_page( 'account-setting' ) ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function($) {
				// Send verification code
				$(document).on(
					'click',
					'#send-verification-code',
					function(e) {
						e.preventDefault();
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'POST',
							data: {
								action: 'send_verification_code',
								nonce: '<?php echo esc_html( wp_create_nonce( 'delete_account_nonce' ) ); ?>',
							},
							success: function(response) {
								if (response.success) {
									Swal.fire({
										icon: 'success',
										title: 'تم إرسال الرمز',
										text: response.data.message,
										confirmButtonText: 'حسنًا'
									});
									$('#verification-code-section').show();
								} else {
									Swal.fire({
										icon: 'error',
										title: 'خطأ',
										text: response.data.message,
										confirmButtonText: 'حسنًا'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: 'خطأ غير متوقع',
									text: 'حدث خطأ غير متوقع. حاول مرة أخرى.',
									confirmButtonText: 'حسنًا'
								});
							}
						});
					}
				);

				// Verify code and delete account
				$(document).on('click','#verify-and-delete' ,function(e) {
					e.preventDefault();
					const verificationCode = $('#verification-code').val();

					$.ajax({
						url: deleteAccount.ajax_url,
						type: 'POST',
						data: {
							action: 'verify_and_delete_account',
							verification_code: verificationCode,
							nonce: deleteAccount.nonce,
						},
						success: function(response) {
							if (response.success) {
								Swal.fire({
									icon: 'success',
									title: 'تم الحذف',
									text: response.data.message,
									confirmButtonText: 'حسنًا'
								}).then(() => {
									$('#delete-account-section').hide(); // Hide the section after deletion
									location.reload();
								});
							} else {
								Swal.fire({
									icon: 'error',
									title: 'خطأ',
									text: response.data.message,
									confirmButtonText: 'حسنًا'
								});
							}
						},
						error: function() {
							Swal.fire({
								icon: 'error',
								title: 'خطأ غير متوقع',
								text: 'حدث خطأ غير متوقع. حاول مرة أخرى.',
								confirmButtonText: 'حسنًا'
							});
						}
					});
				});
			});

		</script>
		<?php
	}
);
