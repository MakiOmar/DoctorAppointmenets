<?php
/**
 * Meeting Room Shortlink Helper
 *
 * Provides token-based shortlinks for Jitsi meeting rooms.
 * URL format: /meeting/{token} (frontend AI) or /j/{token} (redirects to /meeting/). No login required.
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
 * Handle meeting shortlink requests - redirect to frontend AI meeting route.
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

	if ( snks_is_google_meet_active() && function_exists( 'snks_get_session_meeting_for_timetable' ) ) {
		$meeting = snks_get_session_meeting_for_timetable( $timetable_id );
		if ( ! empty( $meeting['join_url'] ) ) {
			snks_render_guest_google_meet_room( $meeting['join_url'] );
			exit;
		}
		wp_die(
			esc_html__( 'لم يتم تعيين رابط Google Meet لهذه الجلسة بعد. يرجى التواصل مع الإدارة.', 'shrinks' ),
			esc_html__( 'خطأ', 'shrinks' ),
			array( 'response' => 503 )
		);
	}

	// Redirect to frontend AI meeting route so the Jitsi room opens inside the app.
	wp_safe_redirect( home_url( '/meeting/' . sanitize_text_field( $token ) ) );
	exit;
}
add_action( 'template_redirect', 'snks_handle_meeting_shortlink_redirect' );

/**
 * Register REST route for resolving meeting token (used by frontend meeting page).
 */
function snks_register_meeting_by_token_rest_route() {
	register_rest_route(
		'jalsah-ai/v1',
		'/meeting-by-token',
		array(
			'methods'             => 'GET',
			'callback'            => 'snks_rest_meeting_by_token',
			'permission_callback' => '__return_true',
			'args'                => array(
				'token' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'snks_register_meeting_by_token_rest_route' );

/**
 * REST callback: resolve token and return room details for frontend Jitsi.
 *
 * @param WP_REST_Request $request Request with token param.
 * @return WP_REST_Response|WP_Error
 */
function snks_rest_meeting_by_token( $request ) {
	$token = $request->get_param( 'token' );
	$timetable_id = snks_resolve_meeting_token( $token );
	if ( ! $timetable_id ) {
		return new WP_Error( 'invalid_token', __( 'رابط الجلسة غير صالح أو منتهي الصلاحية.', 'shrinks' ), array( 'status' => 404 ) );
	}

	$timetable = snks_get_timetable_by( 'ID', $timetable_id );
	if ( ! $timetable ) {
		return new WP_Error( 'session_not_found', __( 'الجلسة غير موجودة.', 'shrinks' ), array( 'status' => 404 ) );
	}

	if ( $timetable->session_status === 'cancelled' ) {
		return new WP_Error( 'session_cancelled', __( 'تم إلغاء هذه الجلسة.', 'shrinks' ), array( 'status' => 410 ) );
	}
	if ( empty( $timetable->attendance_type ) || 'online' !== $timetable->attendance_type ) {
		return new WP_Error( 'session_not_online', __( 'هذه الجلسة غير مؤهلة للدخول.', 'shrinks' ), array( 'status' => 403 ) );
	}

	$display_name = __( 'مشارك', 'shrinks' );
	if ( ! empty( $timetable->client_id ) ) {
		$first = get_user_meta( $timetable->client_id, 'billing_first_name', true );
		$last  = get_user_meta( $timetable->client_id, 'billing_last_name', true );
		$display_name = trim( $first . ' ' . $last ) ?: $display_name;
	}

	$meeting = function_exists( 'snks_get_session_meeting_for_timetable' )
		? snks_get_session_meeting_for_timetable( $timetable_id )
		: array();

	$payload = array_merge(
		array(
			'timetable_id'  => (int) $timetable_id,
			'display_name'  => $display_name,
			'provider'      => snks_is_google_meet_active() ? 'google_meet' : 'jitsi',
			'room_name'     => (int) $timetable_id . ' جلسة',
			'join_url'      => '',
			'google_meet_join_url' => '',
			'use_meeting_timers'   => snks_should_use_jitsi_meeting_timers(),
			'live_stream_provider' => snks_get_live_stream_provider(),
		),
		$meeting
	);

	if ( empty( $payload['display_name'] ) ) {
		$payload['display_name'] = $display_name;
	}

	return rest_ensure_response( $payload );
}

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
 * Get base URL for meeting links (WhatsApp, SMS, etc.). Uses first Frontend URL from settings when available.
 *
 * @return string Base URL without trailing slash.
 */
function snks_get_meeting_link_base() {
	if ( function_exists( 'snks_ai_get_primary_frontend_url' ) ) {
		$base = snks_ai_get_primary_frontend_url();
		if ( ! empty( $base ) ) {
			return untrailingslashit( $base );
		}
	}
	return untrailingslashit( home_url() );
}

/**
 * Generate or retrieve meeting shortlink for a timetable.
 * Uses the first Frontend URL from general settings as base when set (for WhatsApp and other notifications).
 *
 * @param int $timetable_id Timetable (slot) ID.
 * @return string Full shortlink URL (frontend /meeting/{token} when Frontend URLs is set).
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
		return snks_get_meeting_link_base() . '/meeting/' . $token;
	}

	// Generate new token.
	$token = snks_generate_meeting_token();
	$tokens[ $token ] = $timetable_id;
	update_option( 'snks_meeting_tokens', $tokens );

	// Return frontend AI meeting URL so the link opens inside the app.
	return snks_get_meeting_link_base() . '/meeting/' . $token;
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
 * Render guest page that opens Google Meet in a new tab.
 *
 * @param string $join_url Meet URL.
 * @return void
 */
function snks_render_guest_google_meet_room( $join_url ) {
	$join_url = esc_url( $join_url );
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?> dir="rtl">
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php esc_html_e( 'جلسة - Google Meet', 'shrinks' ); ?></title>
		<style>
			body { font-family: sans-serif; margin: 0; padding: 2rem; background: #f5f5f5; text-align: center; }
			.box { max-width: 480px; margin: 2rem auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
			.btn { display: inline-block; margin-top: 1rem; padding: 12px 24px; background: #024059; color: #fff; text-decoration: none; border-radius: 6px; }
		</style>
	</head>
	<body>
		<div class="box">
			<h1><?php esc_html_e( 'فتح جلسة Google Meet', 'shrinks' ); ?></h1>
			<p><?php esc_html_e( 'سيتم فتح الجلسة في نافذة جديدة. إذا لم تفتح تلقائياً، اضغط الزر أدناه.', 'shrinks' ); ?></p>
			<a class="btn" href="<?php echo esc_url( $join_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'فتح الجلسة', 'shrinks' ); ?></a>
		</div>
		<script>
		(function() {
			var url = <?php echo wp_json_encode( $join_url ); ?>;
			if (url) { window.open(url, '_blank', 'noopener,noreferrer'); }
		})();
		</script>
	</body>
	</html>
	<?php
}

/**
 * Render guest meeting room page (no login required).
 *
 * @param int    $timetable_id Timetable ID.
 * @param object $timetable   Timetable object.
 */
function snks_render_guest_meeting_room( $timetable_id, $timetable ) {
	if ( snks_is_google_meet_active() ) {
		$meeting = snks_get_session_meeting_for_timetable( $timetable_id );
		if ( ! empty( $meeting['join_url'] ) ) {
			snks_render_guest_google_meet_room( $meeting['join_url'] );
			return;
		}
	}

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
					JITSI_WATERMARK_LINK: 'https://jalsah.app',
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
