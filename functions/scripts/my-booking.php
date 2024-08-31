<?php
/**
 * My booking scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		if ( ! snks_is_patient() ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(
				function($){
					$(document).on(
						'click',
						'.snks-disabled .snks-start-meeting',
						function(event) {
							event.preventDefault();
						}
					);
					$('.snks-count-down').each(
						function(){
							var parent = $(this).closest('.snks-booking-item');
							var parentID = parent.attr('id');
							var itemID = parentID.match(/\d+/)[0];
							var dateTime = parent.data('datetime');
							// Set the date we're counting down to.
							var countDownDate = new Date(dateTime).getTime();
							
							// Update the count down every 1 second.
							var x = setInterval(
								function() {

									// Get today's date and time.
									var now = new Date().getTime();
									if ( countDownDate > now ) {
										// Find the distance between now and the count down date.
										var distance = countDownDate - now;

										// Time calculations for days, hours, minutes and seconds.
										var days = Math.floor(distance / (1000 * 60 * 60 * 24));
										var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
										var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
										var seconds = Math.floor((distance % (1000 * 60)) / 1000);

										// Update the HTML content.
										$(".snks-apointment-timer", parent).html("<span>"+ days + " يوم </span>"
											+ "<span>"+ hours + " ساعة </span>"
											+ "<span>"+ minutes + " دقيقة </span>"
											+ "<span>"+ seconds + " ثانية </span>");

										// Check if days is 0 and add a class to its container span.
										if (days <= 0) {
											$(".snks-apointment-timer span:contains('0 يوم')", parent).hide();
										}

										// Check if hours is 0 and add a class to its container span.
										if ( hours <= 0 && days <= 0 ) {
											$(".snks-apointment-timer span:contains('0 ساعة')", parent).hide();
										}

										// Check if hours is 0 and add a class to its container span.
										if (minutes <= 0 && hours <= 0 && days <= 0 ) {
											$(".snks-apointment-timer span:contains('0 دقيقة')", parent).hide();
										}

										// If the count down is finished, write some text.
										if (distance < 0) {
											clearInterval(x);
											$(".snks-apointment-timer", parent).html('<span>حان موعد الجلسة</span>');
											$(".snks-start-meeting", parent).attr('href', '<?php echo esc_url( site_url( 'meeting-room/?room_id=' ) ); ?>' + itemID );
										}
									} else {
										if ( now - countDownDate > 3600000 ) {
											$(".snks-apointment-timer", parent).html('<span>تجاوزت موعد الجلسة</span>');
											parent.addClass('snks-disabled');
											$(".snks-start-meeting", parent).attr('href', '#');
											clearInterval(x);
										} else if( now - countDownDate < 3600000 && now - countDownDate > 0 ) {
											parent.removeClass('snks-disabled');
											$(".snks-apointment-timer", parent).html('<span>حان موعد الجلسة</span>');
											$(".snks-start-meeting", parent).attr('href', '<?php echo esc_url( site_url( 'meeting-room/?room_id=' ) ); ?>' + itemID );
										}
									}
								},
								1000
							);
						}
					);
				}
			);
		</script>
		<?php
	}
);
