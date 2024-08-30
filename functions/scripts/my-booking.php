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

							// Update the count down every 1 second.
							var x = setInterval(
								function() {

									// Get today's date and time.
									var now = new Date().getTime();

									// Find the distance between now and the count down date.
									var distance = countDownDate - now;

									// Time calculations for days, hours, minutes and seconds.
									var days = Math.floor(distance / (1000 * 60 * 60 * 24));
									var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
									var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
									var seconds = Math.floor((distance % (1000 * 60)) / 1000);


									$(".snks-apointment-timer", parent).html("<span>"+days + " يوم </span>" +"<span>"+ hours + " ساعة </span>"
									+"<span>"+ minutes + " دقيقة </span>" + "<span>"+seconds + " ثانية </span>");

									// If the count down is finished, write some text.
									if (distance < 0) {
										clearInterval(x);
										$(".snks-apointment-timer", parent).html('<span>إبدأ الجلسة</span>');
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
