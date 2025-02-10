<?php
/**
 * Session actions
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		?>
	<script>
		jQuery(document).ready(function($){
			$(document).on(
				'click',
				'.snks-notes',
				function(e){
					e.preventDefault();
					$(this).closest('.snks-booking-item-wrapper').find('.snks-notes-form').toggleClass('show-notes-form');
				}
			);
			// When the element with class .bulk-action-toggle-tip-close is clicked
			$(document).on(
				'click',
				'.bulk-action-toggle-tip-close',
				function() {
				// Set a cookie named 'hide-delay-tip' with a value '1' and no expiration date (never expires)
				Cookies.set('hide-delay-tip', '1', { expires: 365 * 100 }); // Cookie set for 100 years
			});
			function getCheckedValues(parent, name) {
				// Find all checked checkboxes within the parent element
				var checkedCheckboxes = parent.find('input[name="' + name +'"]:checked');
				// Extract the values of the checked checkboxes
				var values = checkedCheckboxes.map(function() {
					return { 'ID': $(this).val(), 'doctorID' : $(this).data('doctor'), 'patientID' : $(this).data('patient'), 'date' : $(this).data('date') };
				}).get();

				return values;
			}
			$(document).on(
				'click',
				'.snks-booking-bulk-action',
				function(e){
					var ele = $(this).data('action');
					e.preventDefault();
						let parent = $(this).closest('.snks-timetable-accordion-wrapper');
						let values = getCheckedValues(parent, 'bulk-action[]');
						if ( values.length == 0 ) {
							$.fn.justShowErrorpopup('فضلاً قم بتحديد جلسة!');
							return;
						}
						// Output the dates in a <ul>
						var ulElement = $('<ul class="snks-checked-datetimes"></ul>'); // Create a new <ul> element

						// Loop through the checked values and create <li> elements for each date
						values.forEach(function(item) {
							var liElement = $('<li></li>').text(item.date); // Create a new <li> element with the date text
							ulElement.append(liElement); // Append the <li> element to the <ul>
						});
						$("#iam-sure").attr('data-action', ele);
						$("#iam-sure").attr('data-parent', parent.attr('data-id'));
						$.fn.justShowSurepopup(ulElement, $(this).data('title') + ' هذه المواعيد');
						
						if ( ele === '.snks-delay' ){
							$("#sure-container").append($('<input type="number" id="delay-by" name="delay-by" placeholder="أدخل مدة التأخير بالدقيقة"/>'));
						} else {
							$("#delay-by").remove();
						}
				}
			);
			$(document.body).on(
				'click',
				'#iam-sure',
				function() {
					var data;
					let container = $(this).closest('.jet-popup__container');
					let values = getCheckedValues($("#" + $(this).attr('data-parent')), 'bulk-action[]');
					if ( values.length == 0 ) {
						$.fn.justShowErrorpopup('فضلاً قم بتحديد جلسة!');
						return;
					}
					var nonce = '<?php echo esc_html( wp_create_nonce( 'appointment_action_nonce' ) ); ?>';

					var delayBy = false;
					var swalTitle = 'نجحت العملية';
					var swalText = 'تم تأجيل المواعيد بنجاح';
					var act     = $("#iam-sure").attr('data-action');
					if ( $("#delay-by").length > 0 ) {
						delayBy = $("#delay-by").val();
						swalText = "تم إرسال إشعار  بتأخير  الموعد";
					}
					data = {
							action    : 'appointment_action',
							ele       : act,
							IDs       : values,
							delayBy   : delayBy,
							nonce     : nonce,
						}
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: data,
						beforeSend : function() {

						},
						success: function(response) {
							$('.jet-popup__close-button', container).trigger('click');
							Swal.fire({
								title: swalTitle,
								text: swalText,
								icon: "success",
								confirmButtonText: 'غلق',
							}).then((result) => {
								if ( '.snks-postpon' === act ) {
									location.reload();
								}
							});
						}
					});

				}
			);

			$(document).on(
				'change',
				'#change-to-date',
				function() {
					var nonce = '<?php echo esc_html( wp_create_nonce( 'appointment_change_date_nonce' ) ); ?>';

					var date = $(this).val();
					var mainDate = $(this).data('date');
					var mainTime = $(this).data('time');
					console.log($('#old-appointment').val());
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: {
							action    : 'appointment_change_date',
							oldAppointment : $('#old-appointment').val(),
							date      : date,
							nonce     : nonce,
						},
						success: function(response) {
							$("#" + mainDate + "-change-to-list").html(response);
						}
					});

				}
			);
			$(document).on(
				'click',
				'.snks-change',
				function(e) {
					e.preventDefault();
					var oldAppointment = $(this).data('id');
					var date = $(this).data('date');
					var time = $(this).data('time');
					$("#old-appointment").val(oldAppointment);
					$("#change-to-date").attr('data-date', date);
					$("#change-to-date").attr('data-time', time);
					$("#change-to-date").val('0');
					var list = $("#change-to-list");
					$(".change-to-list").html('');
					list.removeAttr('id');
					list.attr('id', date + '-change-to-list');
					$('#snks-change-trigger').trigger('click');
				}
			);
			$(document).on('click', '#doctor-change-appointment #doctor-change-appointment-submit',function(e) {
				e.preventDefault(); // Prevent the form from submitting normally
				let popup = $(this).closest('jet-popup');
				// Collect form data
				var formData = {
					action: 'doctor_change_appointment', // The action for AJAX
					date: $('#change-to-date').val(), // Selected date
					appointment_id: $('input[name="change-to-this-date"]:checked').val(), // Selected appointment
					old_appointment: $('#old-appointment').val(), // Old appointment
					change_appointment_nonce: $('#change_appointment_nonce').val() // Nonce field for security
				};
				// Send the AJAX request
				$.ajax({
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // WordPress AJAX URL
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.status && response.status === 'success') {
							// Show success message with SweetAlert
							Swal.fire({
								icon: 'success',
								title: 'تم',
								text: response.message,
								confirmButtonText: 'موافق'
							}).then((result) => {
								if (result.isConfirmed) {
									location.reload();
								}
							});
						} else if (response.status && response.status === 'faild') {
							// Show error message with SweetAlert
							Swal.fire({
								icon: 'error',
								title: 'خطأ',
								text: response.message,
								confirmButtonText: 'موافق'
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: 'خطأ',
								text: 'حدث خطأ.',
								confirmButtonText: 'موافق'
							});
						}
					},
					error: function(error) {
						// Show error message with SweetAlert if the AJAX request fails
						Swal.fire({
							icon: 'error',
							title: 'خطأ',
							text: 'حدث خطأ أثناء الاتصال بالخادم.',
							confirmButtonText: 'موافق'
						});
					}
				});
			});

			$(document).on(
				'click',
				'.bulk-action-toggle svg',
				function() {
					let parent = $(this).closest('.snks-timetable-accordion-wrapper');
					$('.snks-timetable-accordion-actions', parent).toggleClass('snks-timetable-active-accordion');
					$('.bulk-action-checkbox', parent).toggle();
				}
			);
			$(document).on(
				'click',
				'.bulk-action-toggle-tip-close',
				function() {
					let parent = $(this).closest('.snks-timetable-accordion-wrapper');
					$('.bulk-action-toggle-tip', parent).hide();
				}
			);
		});
			
	</script>
		<?php
	}
);
