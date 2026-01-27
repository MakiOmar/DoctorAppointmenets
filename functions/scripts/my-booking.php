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
		wp_enqueue_script( 'meeting-script', 'https://jitsiserver.jalsah.app/external_api.js', array(), time(), false );
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

		$name = snks_is_doctor() ? snks_get_doctor_name( $timetable->user_id ) : get_user_meta( $timetable->client_id, 'billing_first_name', true ) . ' ' . get_user_meta( $timetable->client_id, 'billing_last_name', true );

		//phpcs:enable
		if ( ! snks_is_timetable_eligible( $room_id ) ) {
			return;
		}
		add_action(
			'wp_footer',
			function () use ( $room_id, $doctor_id, $name ) {
				// Check if this is an AI session and if it's too early to join
				$session = snks_get_timetable_by( 'ID', $room_id );
				$is_ai_session = $session ? snks_is_ai_session( $room_id ) : false;
				$is_too_early = false;
				
				if ( $is_ai_session && $session ) {
					$scheduled_timestamp = strtotime( $session->date_time );
					$current_timestamp = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
					$is_too_early = $current_timestamp < $scheduled_timestamp;
				}
				
				if ( snks_is_patient() && ( ! snks_doctor_has_joined( $room_id, $doctor_id ) || $is_too_early ) ) {
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
						roomName: '<?php echo $room_id;//phpcs:disable ?> جلسة',
						width: '100vw',
						height: (window.innerHeight ) + 'px',
						configOverwrite: {
							prejoinPageEnabled: false,
							startWithAudioMuted: false,
							startWithVideoMuted: false,
							enableWelcomePage: false,
							enableClosePage: true,
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
					meetAPI = new JitsiMeetExternalAPI("jitsiserver.jalsah.app", options);

					
					meetAPI.executeCommand('displayName', '<?php echo esc_html( $name ); ?>');
					
					<?php if ( snks_is_patient() ) { ?>
					// Auto-start the meeting for patients - ensure it joins automatically
					meetAPI.addListener('videoConferenceJoined', function() {
						// Meeting has automatically joined
						console.log('Patient automatically joined the meeting');
					});
					
					// Fallback: Try to auto-click any start/join button if it exists
					// This handles cases where Jitsi might still show a button despite prejoinPageEnabled: false
					var attemptAutoJoin = function() {
						var startButton = document.querySelector('[data-testid="prejoin.joinMeeting"]') || 
										  document.querySelector('.prejoin-button') ||
										  document.querySelector('button[aria-label*="Join"]') ||
										  document.querySelector('button[aria-label*="join"]') ||
										  document.querySelector('button[aria-label*="ابدأ"]') ||
										  document.querySelector('button[aria-label*="Join meeting"]') ||
										  document.querySelector('.videosettingsbutton') ||
										  document.querySelector('[data-tooltip*="Join"]') ||
										  document.querySelector('[id*="join"]') ||
										  document.querySelector('[class*="join-button"]');
						if (startButton && typeof startButton.click === 'function') {
							try {
								startButton.click();
								console.log('Auto-clicked start button for patient');
							} catch(e) {
								console.log('Could not auto-click button:', e);
							}
						}
					};
					
					// Try auto-join after delays to catch any delayed button rendering
					setTimeout(attemptAutoJoin, 500);
					setTimeout(attemptAutoJoin, 1000);
					setTimeout(attemptAutoJoin, 2000);
					<?php } ?>
					
					<?php if ( ! empty( $room_id ) ) { ?>
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
									Swal.fire({
										icon: 'error',
										title: '!عفواً',
										text: 'حدث خطأ ما يرجى الخروج ودخول الجلسة مرة أخرى.', // The original alert message
										confirmButtonText: 'موافق'
									});
								}
							}
						});
					});
					
					// Redirect therapist to account-setting when they leave the meeting
					meetAPI.addListener('videoConferenceLeft', function() {
						// Clean up the meeting API
						if (meetAPI) {
							try {
								meetAPI.dispose();
							} catch(e) {
								console.log('Error disposing meeting:', e);
							}
						}
						<?php if ( ! snks_is_patient() ) { ?>
						// Redirect to account-setting page
						window.location.href = '<?php echo esc_url( site_url( '/account-setting' ) ); ?>';
						<?php } else { ?>
						// Redirect to account-setting page
						window.location.href = '<?php echo esc_url( site_url( '/my-bookings' ) ); ?>';
						<?php } ?>
					});
					
					meetAPI.addListener('readyToClose', function() {
						// Clean up the meeting API
						if (meetAPI) {
							try {
								meetAPI.dispose();
							} catch(e) {
								console.log('Error disposing meeting:', e);
							}
						}
						<?php if ( ! snks_is_patient() ) { ?>
							// Redirect to account-setting page
							window.location.href = '<?php echo esc_url( site_url( '/account-setting' ) ); ?>';
							<?php } else { ?>
							// Redirect to account-setting page
							window.location.href = '<?php echo esc_url( site_url( '/my-bookings' ) ); ?>';
						<?php } ?>
					});
					
					// Also monitor for Jitsi default interface and redirect immediately
					// This handles cases where the interface appears before events fire
					var checkForJitsiInterface = setInterval(function() {
						// Check if we're still in a meeting or if Jitsi default interface appeared
						var iframe = document.querySelector('#meeting iframe');
						if (iframe) {
							try {
								var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
								if (iframeDoc) {
									// Check for Jitsi default interface elements (the welcome/start meeting page)
									var jitsiInterface = iframeDoc.querySelector('.welcome') || 
														 iframeDoc.querySelector('.welcome-page') ||
														 iframeDoc.querySelector('[class*="welcome"]') ||
														 iframeDoc.querySelector('input[placeholder*="meeting"]') ||
														 iframeDoc.querySelector('input[placeholder*="Meeting"]') ||
														 iframeDoc.querySelector('button:contains("Start meeting")') ||
														 iframeDoc.querySelector('button:contains("Start Meeting")');
									
									// Also check for the specific text "Jalsah App" which appears in the default interface
									var pageText = iframeDoc.body ? iframeDoc.body.innerText || iframeDoc.body.textContent : '';
									if (pageText.includes('Jalsah App') && pageText.includes('Start meeting')) {
										clearInterval(checkForJitsiInterface);
										// Redirect immediately
										setTimeout(function() {
											window.location.href = '<?php echo esc_url( site_url( '/account-setting' ) ); ?>';
										}, 500);
									} else if (jitsiInterface) {
										clearInterval(checkForJitsiInterface);
										// Redirect immediately
										setTimeout(function() {
											window.location.href = '<?php echo esc_url( site_url( '/account-setting' ) ); ?>';
										}, 500);
									}
								}
							} catch(e) {
								// CORS - can't access iframe content, which is expected
								// In this case, rely on the event listeners
							}
						}
					}, 1000); // Check every second
					
					// Stop checking after 60 seconds (meeting should have started by then)
					setTimeout(function() {
						clearInterval(checkForJitsiInterface);
					}, 60000);
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
		
		// Check if this is an AI session and if it's too early to join
		$session = snks_get_timetable_by( 'ID', $room_id );
		$is_ai_session = $session ? snks_is_ai_session( $room_id ) : false;
		$is_too_early = false;
		
		if ( $is_ai_session && $session ) {
			$scheduled_timestamp = strtotime( $session->date_time );
			$current_timestamp = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
			$is_too_early = $current_timestamp < $scheduled_timestamp;
		}
		
		if ( snks_is_patient() && ( ! snks_doctor_has_joined( $room_id, $doctor_id ) || $is_too_early ) ) {
			$message = $is_too_early ? 'الجلسة لم تبدأ بعد - يرجى الانتظار حتى وقت الجلسة المحدد' : 'يرجى انتظار المعالج شكراً لك';
			$html .= '<div class="room-loader-wrapper anony-flex flex-v-center anony-flex-column anony-flex-align-center"><div class="room-loader"></div><h5 style="color:#fff">' . $message . '</h5></div>';
		}
		$html .= '</div><input type="hidden" id="room_id" value="' . $room_id . '"/>';
		return $html;
	}
);
add_action(
	'wp_footer',
	function () {
		?>
		<script>
		// Timer script for Jet popup events
		function initializeSnksTimer() {
			jQuery(document).ready(function($){
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
						if ( parent.closest('.past').length > 0 ) {
							return;
						}
						var parentID = parent.attr('id');
						var itemID = parentID.match(/\d+/)[0];
						var dateTime = parent.data('datetime');
						var period = parent.data('period') || 45; // Default 45 minutes if not specified
						
						// Calculate countdown to session start time
						var startDate = new Date(dateTime);
						var countDownDate = startDate.getTime();
						// Calculate session end time for checking if session has passed
						var sessionEndDate = new Date(startDate.getTime() + (period * 60 * 1000)).getTime();
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
									// Session has started - check if it's still within session duration
									// Check if this is an AI session
									var isAiSession = parent.find('.ai-session-flag').length > 0;
									
									if ( now > sessionEndDate ) {
										// Session has ended
										$(".snks-apointment-timer", parent).html('<span>تجاوزت موعد الجلسة</span>');
										// Only add snks-disabled for non-AI sessions
										if ( ! isAiSession ) {
											parent.addClass('snks-disabled');
										}
										clearInterval(x);
									} else {
										// Session is active (started but not ended yet)
										// Remove snks-disabled for all sessions (AI and non-AI) when session starts
										parent.removeClass('snks-disabled');
										$(".snks-apointment-timer", parent).html('<span>حان موعد الجلسة</span>');
										$(".snks-start-meeting", parent).attr('href', '<?php echo esc_url( site_url( 'meeting-room/?room_id=' ) ); ?>' + itemID );
										$(".snks-start-meeting", parent).text('إبدأ الجلسة');
									}
								}
							},
							1000
						);
					}
				);
			});
		}
		
		// Call timer function on Jet popup events
		jQuery(window).on('jet-popup/show-event/after-show', function(){
			initializeSnksTimer();
		});
		
		jQuery(window).on('jet-popup/render-content/render-custom-content', function(){
			initializeSnksTimer();
		});
	</script>
	<?php
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
			jQuery(document).ready(function($) {
				// Attach a click event to the .edit-booking element.
				$(document).on(
					'click',
					'.edit-booking',
					function(event) {
					event.preventDefault(); // Prevent the default action of the link.

					// Get the data attributes from the clicked element.
					var freeChangeBefore = $(this).data('free_change_before');
					var paidChangeBefore = $(this).data('paid_change_before');
					var paidChangeFees = $(this).data('paid_change_fees');
					var noChangePeriod = $(this).data('no_change_period');
					var sessionUrl     = $(this).data('href');

					$('#popup_no_change_period').text(noChangePeriod);
					$('#popup_paid_change_period').text(paidChangeBefore);
					$('#popup_paid_change_fees').text(paidChangeFees);
					$('#popup_free_change_before').text(freeChangeBefore);
					$('a', $('#popup_change_url')).attr( 'href', sessionUrl);
				});
			});
		</script>

		<?php
	}
);
