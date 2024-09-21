<?php
/**
 * Notifications
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();
/**
 * Insert notification
 *
 * @param string  $notification_message Message.
 * @param string  $link Link.
 * @param integer $user_id User ID.
 * @return mixed
 */
function snks_insert_notification( $notification_message, $link = '', $user_id = 0 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'anony_notifications';
	// Prepare the data to be inserted.
	$data = array(
		'user_id'    => $user_id,
		'message'    => $notification_message,
		'created_at' => current_time( 'mysql' ),
	);
	// Insert the notification into the database.
	//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	return $wpdb->insert( $table_name, $data );
}
add_action(
	'admin_init',
	function () {
        //phpcs:disable WordPress.Security.NonceVerification.Missing
		$_req = $_POST;
		if ( isset( $_req['notification-switch'] ) && 'yes' === $_req['notification-switch'] && isset( $_req['notif_content'] ) && ! empty( $_req['notif_content'] ) ) {
			if ( ! isset( $_req['notif_user'] ) ) {
				$_req['notif_user'] = 0;
			}
			if ( empty( $_req['title'] ) ) {
				$_req['title'] = 'جلسة';
			}
			$link = ! empty( $_req['link'] ) ? $_req['link'] : '';
			snks_insert_notification( wp_strip_all_tags( $_req['notif_content'] ), $link, $_req['notif_user'] );
			if ( class_exists( 'Anonyengine_App_Notifications' ) && defined( 'ANOTF_DIR' ) ) {
				require_once ANOTF_DIR . 'public/class-anonyengine-firebase.php';
				$firebase = new Anonyengine_Firebase();
				$firebase->trigger_notifier( $_req['title'], $_req['notif_content'], absint( $_req['notif_user'] ) );
			}
		}

		if ( isset( $_req['programme_enrolled'] ) && 'true' === $_req['programme_enrolled'] ) {
			update_user_meta( $user_id, 'programme_enrolled', 'yes' );
			update_user_meta( $user_id, 'programme_enrolment_date', date_i18n( 'Y-m-d' ) );
		}
		if ( ! empty( $_req['payment-url'] ) ) {
			ob_start();
			include SNKS_DIR . 'templates/email-template.php';
			$template = ob_get_clean();

			$message = str_replace(
				array(
					'{logo}',
					'{title}',
					'{sub_title}',
					'{content_placeholder}',
					'{text_1}',
					'{text_2}',
					'{text_3}',
					'{button_text}',
					'{button_url}',
				),
				array(
					site_url( 'wp-content/uploads/2023/12/w2.png' ),
					'الدفع للإنضمام للبرنامج العلاجي',
					'في جلسة',
					site_url( '/wp-content/uploads/2024/04/sky-2667455_1280.jpg' ),
					'شكراً لثقتك في جلسة',
					'يرجى استكمال الدفع',
					'أنقر على الزر التالي',
					'إدفع الآن',
					$_req['payment-url'],
				),
				$template
			);
			$to      = $_req['email'];
			$subject = 'الإشتراك في البرنامج العلاجي من جلسة';
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: جلسة <customer@shrinks.clinic>',
			);
			wp_mail( $to, $subject, $message, $headers );
		}
	}
);
