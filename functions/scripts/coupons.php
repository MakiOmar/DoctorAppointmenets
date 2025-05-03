<?php
/**
 * Coupons' scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
add_action( 'wp_footer', 'snks_ajax_coupon_script' );
function snks_ajax_coupon_script() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	?>
	<script>
	jQuery(document).ready(function($) {
		// إنشاء كوبون
		$(document).on('submit', '#snks-coupon-form',function(e) {
			e.preventDefault();

			// اقرأ القيم من النموذج مباشرة
			const code           = $('#snks-generated-code').val();
			const discountType   = $('select[name="discount_type"]').val();
			const discountValue  = $('input[name="discount_value"]').val();
			const expiresAtRaw   = $('input[name="expires_at"]').val();
			const usageLimit     = $('input[name="usage_limit"]').val();

			// تحقق من التاريخ
			if (expiresAtRaw) {
				const selectedDate = new Date(expiresAtRaw + 'T00:00:00');
				const today = new Date();
				today.setHours(0, 0, 0, 0);

				if (selectedDate < today) {
					Swal.fire('⚠️ تاريخ غير صالح', 'لا يمكن اختيار تاريخ انتهى بالفعل.', 'error');
					return;
				}
			}

			// أرسل البيانات إلى الخادم
			$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				action: 'snks_create_coupon',
				security: '<?php echo esc_html( wp_create_nonce( 'snks_coupon_nonce' ) ); ?>',
				code: code,
				discount_type: discountType,
				discount_value: discountValue,
				expires_at: expiresAtRaw,
				usage_limit: usageLimit
			}, function(response) {
				if (response.success) {
					Swal.fire('✅ تم!', response.data.message, 'success');
					const c = response.data.coupon;
					const newRow = `
						<tr id="snks-coupon-row-${c.id}">
							<td data-label="الكود">${c.code}</td>
							<td data-label="الخصم">${c.discount_value}${c.discount_type === 'percent' ? '%' : 'ج.م'}</td>
							<td data-label="الصلاحية">${c.expires_at ?? 'بدون تاريخ صلاحية'}</td>
							<td data-label="المتبقي">${c.usage_limit ?? 'غير محدد'}</td>
							<td data-label="الحالة">فعال</td>
							<td data-label="إجراء"><button class="snks-delete-coupon" data-id="${c.id}">❌</button></td>
						</tr>
					`;

					$('#snks-coupons-table tbody').prepend(newRow);
					$('#snks-coupon-form')[0].reset();
				} else {
					Swal.fire('❌ خطأ', response.data.message, 'error');
				}
			});
		});
		// حذف كوبون
		$(document).on('click', '.snks-delete-coupon',function(e) {
			e.preventDefault();
			let couponId = $(this).data('id');

			Swal.fire({
				title: 'هل أنت متأكد؟',
				text: 'لا يمكن التراجع بعد الحذف.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'نعم، احذف',
				cancelButtonText: 'إلغاء'
			}).then((result) => {
				if (result.isConfirmed) {
					$.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
						action: 'snks_delete_coupon',
						security: '<?php echo wp_create_nonce( 'snks_coupon_delete' ); ?>',
						coupon_id: couponId
					}, function(response) {
						if (response.success) {
							Swal.fire('تم!', response.data.message, 'success');
							$('#snks-coupon-row-' + couponId).fadeOut();
						} else {
							Swal.fire('خطأ!', response.data.message, 'error');
						}
					});
				}
			});
		});
		$(document).on('click', '#snks-generate-code',function() {
			$.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				action: 'snks_generate_coupon_code',
				security: '<?php echo wp_create_nonce( 'snks_generate_code_nonce' ); ?>'
			}, function(response) {
				if (response.success) {
					$('#snks-generated-code').val(response.data.code);
					Swal.fire('🎉 تم التوليد!', 'تم توليد كود بنجاح.', 'success');
				} else {
					Swal.fire('❌ خطأ', response.data.message, 'error');
				}
			});
		});
		$(document).on('click', '.discount-section button',function(e) {
			e.preventDefault();

			const code = $('.discount-section input').val();

			if (!code) {
				Swal.fire('⚠️ أدخل كود الخصم', '', 'warning');
				return;
			}

			$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				action: 'snks_apply_coupon',
				code: code,
				security: '<?php echo esc_html( wp_create_nonce( 'snks_coupon_nonce' ) ); ?>'
			}, function(response) {
				if (response.success) {
					Swal.fire('✅ تم تفعيل الكوبون', response.data.message, 'success').then(() => location.reload());
				} else {
					Swal.fire('❌ خطأ', response.data.message, 'error');
				}
			});
		});
		$(document).on('click', '#snks-remove-coupon',function(e) {
			e.preventDefault();

			Swal.fire({
				title: 'هل أنت متأكد؟',
				text: 'سيتم إزالة الكوبون وتحديث السعر.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'نعم، إزالة',
				cancelButtonText: 'إلغاء'
			}).then((result) => {
				if (result.isConfirmed) {
					$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						action: 'snks_remove_coupon',
						security: '<?php echo esc_html( wp_create_nonce( 'snks_coupon_nonce' ) ); ?>'
					}, function(response) {
						if (response.success) {
							Swal.fire('❌ تم الحذف', response.data.message, 'success').then(() => {
								location.reload();
							});
						} else {
							Swal.fire('خطأ', response.data.message, 'error');
						}
					});
				}
			});
		});
	});

	</script>
	<?php
}
