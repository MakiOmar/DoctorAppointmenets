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
				function(){
					$(this).closest('.snks-booking-item-wrapper').find('.snks-notes-form').toggleClass('show-notes-form');
				}
			);
			// When the element with class .bulk-action-toggle-tip-close is clicked
			$('.bulk-action-toggle-tip-close').on('click', function() {
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
			function snks_bookings_bulk_action_popup( ele ) {

				$(document).on(
					'click',
					ele,
					function() {
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
							console.log(ele);
							$("#sure-container").append($('<input type="number" id="delay-by" name="delay-by" placeholder="أدخل مدة التأخير بالدقيقة"/>'));
						} else {
							$("#delay-by").remove();
						}
					}
				);

			}

			snks_bookings_bulk_action_popup( '.snks-postpon');
			snks_bookings_bulk_action_popup( '.snks-delay' );
			
			$(document).on(
				'click',
				'#iam-sure',
				function() {
					let parent = $("#" + $(this).data('parent'));
					let values = getCheckedValues(parent, 'bulk-action[]');
					if ( values.length == 0 ) {
						$.fn.justShowErrorpopup('فضلاً قم بتحديد جلسة!');
						return;
					}
					var nonce = '<?php echo esc_html( wp_create_nonce( 'appointment_action_nonce' ) ); ?>';

					var delayBy = false;
					if ( $("#delay-by").length > 0 ) {
						delayBy = $("#delay-by").val();
					}
				
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: {
							action    : 'appointment_action',
							ele       : $(this).data('action'),
							IDs       : values,
							delayBy   : delayBy,
							nonce     : nonce,
						},
						success: function(response) {
							console.log(response);
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
				
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: {
							action    : 'appointment_change_date',
							date      : date,
							time      : time,
							nonce     : nonce,
						},
						success: function(response) {

							console.log("#" + mainDate + "-change-to-list");
							$("#" + mainDate + "-change-to-list").html(response);
						}
					});

				}
			);
			$(document).on(
				'click',
				'.snks-change',
				function() {
					var oldAppointment = $(this).data('id');
					var date = $(this).data('date');
					var time = $(this).data('time');
					$("#old-appointment").val(oldAppointment);
					$("#change-to-date").attr('data-date', date);
					$("#change-to-date").attr('data-time', time);
					var list = $("#change-to-list");
					list.removeAttr('id');
					list.attr('id', date + '-change-to-list');
				}
			);

			
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
