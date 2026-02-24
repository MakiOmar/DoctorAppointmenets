<?php
/**
 * Meeting Room Shortlink Helper
 *
 * Provides token-based shortlinks for Jitsi meeting rooms.
 * URL format: /j/{token} - accessible without login.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Register meeting shortlink rewrite rule and query var.
 */
function snks_register_meeting_shortlink_rewrite() {
	add_rewrite_rule( '^j/([a-zA-Z0-9_-]+)/?$', 'index.php?snks_meeting_token=$matches[1]', 'top' );
}
add_action( 'init', 'snks_register_meeting_shortlink_rewrite', 5 );

/**
 * Add query var for meeting token.
 *
 * @param array $vars Query vars.
 * @return array
 */
function snks_add_meeting_token_query_var( $vars ) {
	$vars[] = 'snks_meeting_token';
	return $vars;
}
add_filter( 'query_vars', 'snks_add_meeting_token_query_var' );

/**
 * Handle meeting shortlink requests - render Jitsi room without login.
 */
function snks_handle_meeting_shortlink_redirect() {
	$token = get_query_var( 'snks_meeting_token' );
	if ( empty( $token ) ) {
		return;
	}

	$timetable_id = snks_resolve_meeting_token( $token );
	if ( ! $timetable_id ) {
		wp_die( esc_html__( 'رابط الجلسة غير صالح أو منتهي الصلاحية.', 'shrinks' ), esc_html__( 'خطأ', 'shrinks' ), array( 'response' => 404 ) );
	}

	$timetable = snks_get_timetable_by( 'ID', $timetable_id );
	if ( ! $timetable ) {
		wp_die( esc_html__( 'الجلسة غير موجودة.', 'shrinks' ), esc_html__( 'خطأ', 'shrinks' ), array( 'response' => 404 ) );
	}

	// Verify session is valid: not cancelled and online (AI or regular).
	if ( $timetable->session_status === 'cancelled' ) {
		wp_die( esc_html__( 'تم إلغاء هذه الجلسة.', 'shrinks' ), esc_html__( 'خطأ', 'shrinks' ), array( 'response' => 410 ) );
	}
	if ( empty( $timetable->attendance_type ) || 'online' !== $timetable->attendance_type ) {
		wp_die( esc_html__( 'هذه الجلسة غير مؤهلة للدخول.', 'shrinks' ), esc_html__( 'خطأ', 'shrinks' ), array( 'response' => 403 ) );
	}

	// Render the meeting room page (no login required).
	snks_render_guest_meeting_room( $timetable_id, $timetable );
	exit;
}
add_action( 'template_redirect', 'snks_handle_meeting_shortlink_redirect' );

/**
 * Resolve token to timetable ID.
 *
 * @param string $token Meeting token.
 * @return int|false Timetable ID or false.
 */
function snks_resolve_meeting_token( $token ) {
	$tokens = get_option( 'snks_meeting_tokens', array() );
	if ( ! is_array( $tokens ) ) {
		return false;
	}
	return isset( $tokens[ $token ] ) ? absint( $tokens[ $token ] ) : false;
}

/**
 * Generate or retrieve meeting shortlink for a timetable.
 *
 * @param int $timetable_id Timetable (slot) ID.
 * @return string Full shortlink URL.
 */
function snks_get_meeting_shortlink( $timetable_id ) {
	$timetable_id = absint( $timetable_id );
	if ( ! $timetable_id ) {
		return '';
	}

	$tokens = get_option( 'snks_meeting_tokens', array() );
	if ( ! is_array( $tokens ) ) {
		$tokens = array();
	}

	// Find existing token for this timetable.
	$token = array_search( $timetable_id, $tokens, true );
	if ( false !== $token ) {
		return home_url( '/j/' . $token );
	}

	// Generate new token.
	$token = snks_generate_meeting_token();
	$tokens[ $token ] = $timetable_id;
	update_option( 'snks_meeting_tokens', $tokens );

	return home_url( '/j/' . $token );
}

/**
 * Generate a random unguessable token.
 *
 * @return string
 */
function snks_generate_meeting_token() {
	return bin2hex( random_bytes( 8 ) );
}

/**
 * Render guest meeting room page (no login required).
 *
 * @param int   $timetable_id Timetable ID.
 * @param object $timetable   Timetable object.
 */
function snks_render_guest_meeting_room( $timetable_id, $timetable ) {
	$doctor_id = $timetable->user_id;
	$client_id = $timetable->client_id;

	// Display name: use patient name if available, else generic.
	$name = __( 'مشارك', 'shrinks' );
	if ( $client_id && function_exists( 'snks_get_therapist_name' ) ) {
		$first = get_user_meta( $client_id, 'billing_first_name', true );
		$last  = get_user_meta( $client_id, 'billing_last_name', true );
		$name  = trim( $first . ' ' . $last ) ?: $name;
	}

	wp_enqueue_script( 'meeting-script', 'https://jitsiserver.jalsah.app/external_api.js', array(), time(), false );

	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?> dir="rtl">
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php esc_html_e( 'جلسة - Jalsah', 'shrinks' ); ?></title>
		<style>
			body { margin: 0; padding: 0; background: #024059; }
			#meeting { width: 100vw; height: 100vh; }
		</style>
	</head>
	<body>
		<div id="meeting"></div>
		<script>
		(function() {
			var roomID = <?php echo (int) $timetable_id; ?>;
			var roomName = roomID + ' جلسة';
			var displayName = <?php echo wp_json_encode( $name ); ?>;
			var options = {
				parentNode: document.querySelector('#meeting'),
				roomName: roomName,
				width: '100vw',
				height: (window.innerHeight) + 'px',
				userInfo: { displayName: displayName },
				configOverwrite: {
					prejoinPageEnabled: false,
					startWithAudioMuted: false,
					startWithVideoMuted: false,
					enableWelcomePage: false,
					enableClosePage: true,
					participantsPane: { enabled: true }
				},
				interfaceConfigOverwrite: {
					prejoinPageEnabled: false,
					APP_NAME: 'Jalsah',
					DEFAULT_BACKGROUND: '#024059',
					SHOW_JITSI_WATERMARK: false,
					HIDE_DEEP_LINKING_LOGO: true,
					SHOW_BRAND_WATERMARK: true,
					SHOW_POWERED_BY: false,
					DISPLAY_WELCOME_FOOTER: false,
					JITSI_WATERMARK_LINK: 'http://localhost/shrinks',
					PROVIDER_NAME: 'Jalsah'
				}
			};
			var meetAPI = new JitsiMeetExternalAPI('jitsiserver.jalsah.app', options);
			meetAPI.executeCommand('displayName', displayName);
			meetAPI.addListener('videoConferenceJoined', function() {
				<?php if ( function_exists( 'wp_create_nonce' ) ) : ?>
				var nonce = '<?php echo esc_js( wp_create_nonce( 'doctor_presence_nonce' ) ); ?>';
				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'action=doctor_presence&nonce=' + nonce + '&roomID=' + roomID + '&doctorID=<?php echo (int) ( is_user_logged_in() ? get_current_user_id() : $doctor_id ); ?>'
				});
				<?php endif; ?>
			});
		})();
		</script>
	</body>
	</html>
	<?php
}
