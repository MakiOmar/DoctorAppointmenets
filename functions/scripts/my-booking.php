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
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'meeting-script', 'https://meet.jalsah.app/external_api.js', array(), time(), false );
	}
);
add_shortcode(
	'meeting_room',
	function () {
		//phpcs:disable
		if ( empty( $_GET['room_id'] ) ) {
			return;
		}
		$room_id = absint( $_GET['room_id'] );

		$timetable = snks_get_timetable_by( 'ID', $room_id );

		if ( ! $timetable ) {
			return;
		}
		$doctor_id = $timetable->user_id;

		//phpcs:enable
		if ( ! snks_is_timetable_eligible( $room_id ) ) {
			return;
		}
		add_action(
			'wp_footer',
			function () use ( $room_id, $doctor_id ) {
				if ( snks_is_patient() && ! snks_doctor_has_joined( $room_id, $doctor_id ) ) {
					?>
					<script>
					jQuery(document).ready(function($){
						const roomID = <?php echo $room_id;//phpcs:disable ?>;
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'doctor_has_joind_nonce' ) ); ?>';
						
						setInterval(
							function(){
								// Send AJAX request.
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
									data: {
										nonce    : nonce,
										roomID   : roomID,
										doctorID : <?php echo $doctor_id; ?>,
										action   : 'doctor_has_joind',
									},
									success: function(response) {
										if ( response.resp ) {
											location.reload();
										}
									}
								});
							},
							5000
						);
						
					});
					</script>
					<?php
					return;
				}
				?>
				<script>
					jQuery(document).ready(function($){
						const roomID = <?php echo $room_id;//phpcs:disable ?>;
						const options = {
						parentNode: document.querySelector('#meeting'),
						roomName: 'عيادة الدكتور',
						width: 700,
						height: 700,
						configOverwrite: {
							prejoinPageEnabled: false,
							participantsPane: {
								enabled: true,
								hideModeratorSettingsTab: false,
								hideMoreActionsButton: false,
								hideMuteAllButton: false
							}
						},
						interfaceConfigOverwrite: {
								prejoinPageEnabled: false,
								APP_NAME: 'Jalsah',
								DEFAULT_BACKGROUND: "#024059;",
								SHOW_JITSI_WATERMARK: false,
								HIDE_DEEP_LINKING_LOGO: true,
								SHOW_BRAND_WATERMARK: true,
								SHOW_WATERMARK_FOR_GUESTS: true,
								SHOW_POWERED_BY: false,
								DISPLAY_WELCOME_FOOTER: false,
								JITSI_WATERMARK_LINK: 'https://jalsah.app',
								PROVIDER_NAME: 'Jalsah',
								DEFAULT_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
								DEFAULT_WELCOME_PAGE_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
								//TOOLBAR_BUTTONS: ['microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'],
							}
					}
					meetAPI = new JitsiMeetExternalAPI("meet.jalsah.app", options);

					
					meetAPI.executeCommand('displayName', 'YourDisplayNameHere');
					<?php if ( ! snks_is_patient() && ! empty( $room_id ) ) { ?>
					//videoConferenceJoined
					meetAPI.addListener('videoConferenceJoined', function(room){
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'doctor_presence_nonce' ) ); ?>';
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								nonce    : nonce,
								roomID   : roomID,
								doctorID : <?php echo get_current_user_id(); ?>,
								action   : 'doctor_presence',
							},
							success: function(response) {
								if ( ! response.resp ) {
									alert('عفوا! حدث خطأ ما يرجى الخروج ودخول الجلسة ملة أخرى..')
								}
							}
						});
					});
					<?php } ?>
					});

				</script>
				<?php
			}
		);
		$html  = '<style>
				.room-loader {
				width: 70px;
				aspect-ratio: 1;
				background:
					radial-gradient(farthest-side,#000 90%,#0000) 0 0/8px 8px no-repeat,
					conic-gradient(from -90deg at 30px 30px,#0000 90deg,#fff 0) 0 0/40px 40px ,
					conic-gradient(from  90deg at 10px 10px,#0000 90deg,#fff 0) 0 0/40px 40px no-repeat,
					conic-gradient(from -90deg at 30px 30px,#0000 90deg,#fff 0) 100% 100%/40px 40px no-repeat;
				animation: l5 2s infinite;
				}
				@keyframes l5 {
				0%     {background-position:left 1px top 1px,0 0,0 0,100% 100%}
				16.67% {background-position:left 50% top 1px,0 0,0 0,100% 100%}
				33.33% {background-position:left 50% bottom 1px,0 0,0 0,100% 100%}
				50%    {background-position:right 1px bottom 1px,0 0,0 0,100% 100%}
				66.67% {background-position:right 1px bottom 50%,0 0,0 0,100% 100%}
				83.33% {background-position:left 1px bottom 50%,0 0,0 0,100% 100%}
				100%   {background-position:left 1px top 1px,0 0,0 0,100% 100%}
				}.room-loader-wrapper{width:95vw;height:450px;max-width:450px;background-color:#024059;margin:auto;}</style>';
		$html .= '<div id="meeting">';
		if ( snks_is_patient() && ! snks_doctor_has_joined( $room_id, $doctor_id ) ) {
			$html .= '<div class="room-loader-wrapper anony-flex flex-v-center anony-flex-column anony-flex-align-center"><div class="room-loader"></div><h5 style="color:#fff">يرجى انتظار الطبيب، شكراً لك</h5></div>';
		}
		$html .= '</div><input type="hidden" id="room_id" value="' . $room_id . '"/>';
		return $html;
	}
);


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
