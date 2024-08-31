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
		?>
		<script>
			jQuery(document).ready(
				function($){
					$('.snks-count-down').each(
						function(){
							var parent = $(this).closest('.snks-booking-item');
							var dateTime = parent.data('datetime');
							// Set the date we're counting down to.
							var countDownDate = new Date(dateTime).getTime();
							console.log(countDownDate , new Date().getTime() );

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
										}
									} else {
										if ( now - countDownDate > 3600 ) {
											$(".snks-apointment-timer", parent).html('<span>تجاوزت موعد الجلسة</span>');
										} else if( now - countDownDate < 3600 && now - countDownDate > 0 ) {
											$(".snks-apointment-timer", parent).html('<span>حان موعد الجلسة</span>');
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
