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
		wp_enqueue_script( 'meeting-script', 'https://s.jalsah.app/external_api.js', array(), time(), false );
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
					meetAPI = new JitsiMeetExternalAPI("s.jalsah.app", options);

					
					meetAPI.executeCommand('displayName', '<?php echo esc_html( $name ); ?>');
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
		if ( ! snks_is_patient() ) {
			//return;
		}
		?>
		<!-- Timer script moved to snks-bookings shortcode -->
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
