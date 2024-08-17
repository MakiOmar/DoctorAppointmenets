<?php
/**
 * Jitsi
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
							3000
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
						roomName: 'JitsiMeetAPIExample',
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
								APP_NAME: 'Jalsah',
								DEFAULT_BACKGROUND: "#12114F;",
								SHOW_JITSI_WATERMARK: false,
								HIDE_DEEP_LINKING_LOGO: true,
								SHOW_BRAND_WATERMARK: false,
								SHOW_WATERMARK_FOR_GUESTS: false,
								SHOW_POWERED_BY: false,
								DISPLAY_WELCOME_FOOTER: true,
								JITSI_WATERMARK_LINK: 'https://jalsah.app',
								PROVIDER_NAME: 'Jalsah',
								DEFAULT_LOGO_URL: 'https://foreo.makiomar.com/wp-content/uploads/2024/03/Group_2304.svg',
								DEFAULT_WELCOME_PAGE_LOGO_URL: 'https://foreo.makiomar.com/wp-content/uploads/2024/03/Group_2304.svg',
								TOOLBAR_BUTTONS: ['microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'],
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
								console.log( response );
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
}.room-loader-wrapper{width:95vw;height:450px;max-width:450px;background-color:#12114F;margin:auto;}</style>';
		$html .= '<div id="meeting">';
		if ( snks_is_patient() && ! snks_doctor_has_joined( $room_id, $doctor_id ) ) {
			$html .= '<div class="room-loader-wrapper anony-flex flex-v-center anony-flex-column anony-flex-align-center"><div class="room-loader"></div><h5 style="color:#fff">يرجى انتظار الطبيب، شكراً لك</h5></div>';
		}
		$html .= '</div><input type="hidden" id="room_id" value="' . $room_id . '"/>';
		return $html;
	}
);
