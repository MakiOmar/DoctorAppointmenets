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

				$('input[name=date], input[data-field-name=date]').datepicker({
					dateFormat: 'yy-mm-dd',
					beforeShowDay: function(date) {
						var enabledDay = parseInt($(this).data('day'));
						var day = date.getDay();
						var currentDate = new Date();
						currentDate.setHours(0, 0, 0, 0);
						date.setHours(0, 0, 0, 0);
						var offDays = $('#doctor-off-days').val();

						// Disable specific dates from offDays variable
						var disabledDates = offDays.split(',');
						var disabled = false;
						var formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
						if (disabledDates.indexOf(formattedDate) !== -1) {
							disabled = true;
						}
						return [(day === enabledDay) && currentDate <= date && ! disabled ];
					}
				});

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
			});
		</script>
		<?php
	}
);
