<?php
/**
 * Sessions preview
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				if ( $('.conflict-error').length > 0 ) {
					$([document.documentElement, document.body]).animate(
						{
							scrollTop: $(".conflict-error").offset().top - 100
						},
						2000
					);
				}
				$(document).on(
					'click',
					'.timetable-preview-tab',
					function() {
						var target = $( this ).data('target');
						if ( $('.' + target).hasClass('timetable-show') ) {
							$('.' + target).slideUp();
							$('.' + target).removeClass('timetable-show');
						} else {
							$('.' + target).slideDown();
							$('.' + target).addClass('timetable-show');
						}
					}
				);

				function initializeFlatpickr() {
					$('input[name=date], input[data-field-name=date]').each(function() {
						var enabledDay = parseInt($(this).data('day')); // The enabled day for this instance
						var offDays = $('#doctor-off-days').val().split(','); // Days to be disabled
						
						// Initialize Flatpickr
						flatpickr(this, {
							dateFormat: 'Y-m-d',
							disable: [
								function(date) {
									// Disable specific dates from offDays variable
									var formattedDate = flatpickr.formatDate(date, 'Y-m-d');
									return offDays.includes(formattedDate);
								}
							],
							enable: [
								function(date) {
									// Enable only the specific day of the week
									return date.getDay() === enabledDay;
								}
							],
							minDate: "today",  // Disable past dates
							onOpen: function(selectedDates, dateStr, instance) {
								// Set a custom ID or class for the Flatpickr container
								instance.calendarContainer.id = 'flatpickr-' + $(this.element).attr('id');
							}
						});
					});
				}

				// Call initializeDatepicker() on page load for existing inputs
				initializeFlatpickr();

				// Observe the parent container for injected elements
				var targetNode = document.getElementById('jet-popup-4085');  // Change this to the container ID

				var config = { childList: true, subtree: true }; // Observe child nodes inside the parent

				var callback = function(mutationsList, observer) {
					mutationsList.forEach(function(mutation) {
						mutation.addedNodes.forEach(function(node) {
							if ($(node).find('input[name=date], input[data-field-name=date]').length) {
								initializeFlatpickr();  // Re-initialize datepicker for new inputs
							}
						});
					});
				};

				// Create an observer instance linked to the callback function
				var observer = new MutationObserver(callback);

				// Start observing the target node for configured mutations
				observer.observe(targetNode, config);
				

				$(document).on(
					'click',
					'.delete-slot',
					function( e ) {
						e.preventDefault();
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
						// Perform nonce check.
						var nonce     = '<?php echo esc_html( wp_create_nonce( 'delete_slot_nonce' ) ); ?>';
						var slotIndex = $(this).data('index');
						var slotDay   = $(this).data('day');
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slotIndex: slotIndex,
								slotDay  : slotDay,
								nonce    : nonce,
								action   : 'delete_slot',
							},
							success: function(response) {
								if ( response.resp ) {
									$( '#timetable-' + slotDay + '-' + slotIndex ).remove();
								}
							}
						});
					}
				);				

				$(document).on(
					'change',
					'#app_attendance_type',
					function( ) {
						if ( $(this).val() === 'offline' ) {
							$('#app_clinic', $(this).closest('.day-specific-form')).show();
						} else {
							$('#app_clinic', $(this).closest('.day-specific-form')).hide();
						}
					}
				);
			});
		</script>
		<?php
	}
);
