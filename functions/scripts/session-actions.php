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
			function getCheckedValues(parent, name) {
				// Find all checked checkboxes within the parent element
				var checkedCheckboxes = parent.find('input[name="' + name +'"]:checked');
				// Extract the values of the checked checkboxes
				var values = checkedCheckboxes.map(function() {
					return { 'ID': $(this).val(), 'doctorID' : $(this).data('doctor'), 'patientID' : $(this).data('patient'), 'date' : $(this).data('date') };
				}).get();

				return values;
			}
			function snks_bookings_bulk_action( ele, actionName ) {

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

						var nonce = '<?php echo esc_html( wp_create_nonce( 'appointment_action_nonce' ) ); ?>';
					
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								action    : actionName,
								ele       : ele,
								IDs       : values,
								nonce     : nonce,
							},
							success: function(response) {
								console.log(response);
							}
						});

					}
				);

			}
			snks_bookings_bulk_action( '.snks-postpon', 'appointment_action' );
			snks_bookings_bulk_action( '.snks-delay', 'appointment_action' );
			
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
