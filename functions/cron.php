<?php
/**
 * Cron job
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();
/**
 * Start cron action
 *
 * @param object $result Timetable object.
 * @param string $diff Remaining time.
 * @param string $type Time type.
 * @return void
 */
function snks_cron_action( $result, $diff, $type ) {
	$clients_ids = explode( ',', $result->client_id );
	$purpose     = 'consulting' === $result->purpose ? 'الاستشارة' : 'الجلسة';
	$type        = 'H' === $type ? 'ساعة' : 'دقيقة';
	$msg         = 'باقي على موعد';
	$title       = 'تذكير بالموعد';
	$body        = sprintf(
		'%1$s %2$s (%3$s) %4$s %5$s %6$s',
		$msg,
		$purpose,
		$result->date_time,
		'أقل من',
		$diff,
		$type
	);
	foreach ( $clients_ids as $user_id ) {
		// Check if the notification has already been sent for this case.
		$_sent_time_key = 'notification_sent_' . $diff . '_time_' . $result->ID . '_' . $user_id;
		$_sent_time     = get_transient( $_sent_time_key );
		$data           = array();
		if ( ! $_sent_time ) {
			snks_insert_notification(
				$body,
				'',
				$user_id
			);
			if ( class_exists( 'Anonyengine_App_Notifications' ) && defined( 'ANOTF_DIR' ) ) {
				require_once ANOTF_DIR . 'public/class-anonyengine-firebase.php';
				$nickname  = get_user_meta( $user_id, 'nickname', true );
				$fulltitle = sprintf(
					'%1$s %2$s %3$s %4$s',
					'مرحبا بك',
					$nickname,
					'هذا',
					$title,
				);
				$firebase  = new Anonyengine_Firebase();
				$firebase->trigger_notifier( $fulltitle, $body, $data, $user_id );
			}
			// Set a transient to mark that the notification has been sent for this case.
			set_transient( $_sent_time_key, true, 24 * 60 * 60 ); // Expire after 24 hours.
		}
	}
	// Check if the notification has already been sent for this case.
	$_sent_time_key = 'notification_sent_' . $diff . '_time_' . $result->ID . '_' . $result->user_id;
	$_sent_time     = get_transient( $_sent_time_key );
	$data           = array();
	if ( ! $_sent_time ) {
		snks_insert_notification(
			$body,
			'',
			$result->user_id
		);
		if ( class_exists( 'Anonyengine_App_Notifications' ) && defined( 'ANOTF_DIR' ) ) {
			require_once ANOTF_DIR . 'public/class-anonyengine-firebase.php';
			$first_name = get_user_meta( $result->user_id, 'first_name', true );
			$last_name  = get_user_meta( $result->user_id, 'last_name', true );

			$fulltitle = sprintf(
				'%1$s %2$s %3$s %4$s',
				'مرحبا بك',
				$first_name . ' ' . $last_name,
				'هذا',
				$title,
			);
			$firebase  = new Anonyengine_Firebase();
			$firebase->trigger_notifier( $fulltitle, $body, $data, $result->user_id );
		}
		// Set a transient to mark that the notification has been sent for this case.
		set_transient( $_sent_time_key, true, 24 * 60 * 60 ); // Expire after 24 hours.
	}
}
/**
 * Run cron on time difference.
 *
 * @param object $result Timetable object.
 * @param string $diff Remaining time.
 * @param string $type H|M.
 * @param string $state add|sub.
 * @return void
 */
function snks_cron_action_if_diff( $result, $diff, $type, $state = 'add' ) {
	$date_interval = 'PT' . $diff . $type;
	$current_time  = date_i18n( 'Y-m-d H:i:s' );
	anony_error_log(
		array(
			'Current datetime' => $diff . ':' . $current_time,
		)
	);
	$date_time = $result->date_time;
	// Convert date_time to DateTime objects.
	$date_time_obj    = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_time );
	$current_time_obj = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $current_time );
	$interval_time    = new DateInterval( $date_interval );
	if ( 'add' === $state ) {
		$target_time = $current_time_obj->add( $interval_time );
	} elseif ( 'sub' === $state ) {
		$target_time = $date_time_obj->sub( $interval_time );
	} else {
		return;
	}

	if ( ( 'add' === $state && $date_time_obj <= $target_time ) || ( 'sub' === $state && $current_time_obj > $target_time ) ) {
		snks_cron_action( $result, $diff, $type );
	}
}
/**
 * 24 Cron
 *
 * @return void
 */
function snks_24_cron_handler() {
	global $wpdb;

	$current_time = date_i18n( 'Y-m-d H:i:s' );
	$start_time   = $current_time;
	$target_time  = date_i18n( 'Y-m-d H:i:s', strtotime( '+24 hours', strtotime( $current_time ) ) );
	//phpcs:disable
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE date_time BETWEEN %s AND %s AND session_status = %s",
			$start_time,
			$target_time,
			'open'
		)
	);
	//phpcs:enable
	if ( ! $results || empty( $results ) ) {
		return;
	}
	// Process the query results.
	foreach ( $results as $result ) {

		// Case 1: date_time is less than or equal to 24 hours from now.
		snks_cron_action_if_diff( $result, '24', 'H' );
	}
}
/**
 * 15 Minutes
 *
 * @return void
 */
function snks_15_cron_handler() {
	global $wpdb;

	$current_time = date_i18n( 'Y-m-d H:i:s' );
	$start_time   = $current_time;
	$target_time  = date_i18n( 'Y-m-d H:i:s', strtotime( '+24 hours', strtotime( $current_time ) ) );
	//phpcs:disable
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE date_time BETWEEN %s AND %s AND session_status = %s",
			$start_time,
			$target_time,
			'open'
		)
	);
	//phpcs:enable
	if ( ! $results || empty( $results ) ) {
		return;
	}
	// Process the query results.
	foreach ( $results as $result ) {
		snks_cron_action_if_diff( $result, '15', 'M' );
	}
}


/**
 * Adds a custom cron schedule for every 5 minutes.
 *
 * @param array $schedules An array of non-default cron schedules.
 * @return array Filtered array of non-default cron schedules.
 */


add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'shrinks_24_notifications' ) ) {
			wp_schedule_event( time(), 'daily', 'shrinks_24_notifications' );
		}

		if ( ! wp_next_scheduled( 'shrinks_15_notifications' ) ) {
			wp_schedule_event( time(), 'every_15_minutes', 'shrinks_15_notifications' );
		}
	}
);

add_action( 'shrinks_24_notifications', 'snks_24_cron_handler' );
add_action( 'shrinks_15_notifications', 'snks_15_cron_handler' );
add_action( 'wp_footer', 'snks_15_cron_handler' );

/**
 * Session started notification
 *
 * @return void
 */
function snks_session_started() {
	if ( ! snks_is_doctor() ) {
		return;
	}
	//phpcs:disable
	$_req = $_GET;
	//phpcs:enable
	if ( ! is_page( 'zego' ) || empty( $_req['room_id'] ) ) {
		return;
	}
	$booking = snks_get_timetable_by( 'ID', absint( $_req['room_id'] ) );
	if ( $booking ) {
		$click_action = add_query_arg( 'room_id', $_req['room_id'], site_url( '/zego' ) );
		$clients_ids  = explode( ',', $booking->client_id );
		$data         = array();
		foreach ( $clients_ids as $user_id ) {
			// Check if the notification has already been sent for this case.
			$_sent_time_key = 'notification_sent_' . $booking->purpose . '_' . $_req['room_id'] . '_' . $booking->client_id;
			$_sent_time     = get_transient( $_sent_time_key );
			if ( ! $_sent_time ) {
				snks_insert_notification(
					'طبيب يوتين بانتظارك الآن',
					$click_action,
					$user_id
				);
				if ( class_exists( 'Anonyengine_App_Notifications' ) && defined( 'ANOTF_DIR' ) ) {
					require_once ANOTF_DIR . 'public/class-anonyengine-firebase.php';
					$firebase = new Anonyengine_Firebase();
					$firebase->trigger_notifier(
						'شرينكس',
						'طبيب يوتين بانتظارك الآن',
						$data,
						$user_id,
						$click_action
					);
				}
				// Set a transient to mark that the notification has been sent for this case.
				set_transient( $_sent_time_key, true, 24 * 60 * 60 ); // Expire after 24 hours.
			}
		}
	}
}
add_action( 'template_redirect', 'snks_session_started' );
