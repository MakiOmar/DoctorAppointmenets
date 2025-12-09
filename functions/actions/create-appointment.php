<?php
/**
 * Create appointment
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_action(
	'woocommerce_order_status_changed',
	function ( $order_id, $old_status, $new_status ) {
		// Check if the new status is 'completed' or 'processing'.
		if ( in_array( $new_status, array( 'completed', 'processing' ), true ) ) {
			snks_woocommerce_payment_complete_action( $order_id );
		}
	},
	10,
	3
);

add_action( 'woocommerce_payment_complete', 'snks_woocommerce_payment_complete_action' );

/**
 * Function for `woocommerce_payment_complete` action-hook.
 *
 * @param int $order_id Order's ID.
 * @return void
 * @throws Exception Exception.
 */
function snks_woocommerce_payment_complete_action( $order_id ) {
	$order       = wc_get_order( $order_id );
	
	if ( ! $order ) {
		return;
	}
	
	// Handle AI orders separately
	$is_ai_order = $order->get_meta( 'from_jalsah_ai' );
	
	if ( $is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1 ) {
		// Send booking notifications as soon as the order is processing (no profit yet)
		if ( $order->has_status( 'processing' ) && class_exists( 'SNKS_AI_Orders' ) && ! $order->get_meta( 'ai_booking_notified' ) ) {
			SNKS_AI_Orders::process_ai_order_payment( $order_id );
			$order->update_meta_data( 'ai_booking_notified', 1 );
			$order->save();
		}

		// Process AI order completion (profit only when completed)
		if ( $order->has_status( 'completed' ) ) {
			$result = snks_process_ai_order_completion( $order_id );
		}
		
		// Only redirect if this is not a manual admin completion
		// Check if we're in admin area or if this is a manual status change
		$is_admin_area = is_admin();
		$is_manual_completion = ( isset( $_GET['action'] ) && $_GET['action'] === 'mark_complete' ) ||
							   ( isset( $_POST['order_status'] ) && current_user_can( 'manage_woocommerce' ) ) ||
							   ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/' ) !== false );
		
		if ( ! $is_admin_area && ! $is_manual_completion ) {
			// Redirect AI orders to the frontend appointments page
			$frontend_url = snks_ai_get_primary_frontend_url();
			if ( $frontend_url ) {
				// Use wp_redirect for external URLs (wp_safe_redirect only works for same domain)
				wp_redirect( $frontend_url . '/appointments' );
				exit;
			}
		}
		
		// Return early for AI orders to prevent further processing
		return;
	}
	
	$customer_id = $order->get_customer_id();
	$booking_id  = $order->get_meta( 'booking_id', true );
	if ( ! empty( $booking_id ) ) {
		try {
			$timetable = snks_get_timetable_by( 'ID', absint( $booking_id ) );
			if ( 'waiting' === $timetable->session_status || 'pending' === $timetable->session_status ) {
				$updated = snks_update_timetable(
					$timetable->ID,
					array(
						'client_id'      => $customer_id,
						'session_status' => 'open',
						'order_id'       => $order_id,
						'settings'       => wp_json_encode( snks_timetable_settings( $timetable->user_id ) ),
					)
				);

				if ( $updated ) {
					// Existing logic for processing...
					$current_hour   = current_time( 'H' );
					$doctor_earning = $order->get_meta( '_main_price', true );

					if ( $current_hour >= 0 && $current_hour < 9 ) {
						$current_temp_wallet     = (float) get_user_meta( $timetable->user_id, 'temp_wallet', true );
						$new_temp_wallet_balance = $current_temp_wallet + $doctor_earning;
						update_user_meta( $timetable->user_id, 'temp_wallet', $new_temp_wallet_balance );
					} else {
						snks_wallet_credit( $timetable->user_id, $doctor_earning, 'الدخل مقابل حجز موعد' );
					}

					snks_add_transaction( $timetable->user_id, $timetable->ID, 'add', $doctor_earning );
					snks_log_transaction( $timetable->user_id, $doctor_earning, 'add' );
					snks_insert_session_actions( $timetable->ID, $customer_id, 'no' );
					$order->update_meta_data( 'booking_id', $timetable->ID );
					$order->update_meta_data( 'doctor_id', $timetable->user_id );
					$order->update_meta_data( 'doctor_pricings', snks_doctor_pricings( $timetable->user_id, $timetable->attendance_type ) );
					$order->save();
					// Patient.
					if ( 'online' === $timetable->attendance_type ) {
						$message = sprintf(
							'تم حجز جلسة %1$s يوم %2$s الموافق %3$s الساعه %4$s ويمكنك الدخول للجلسة في موعدها بالضغط هنا :%5$s',
							'offline' === $timetable->attendance_type ? 'أوفلاين' : 'أونلاين',
							localize_date_to_arabic( $timetable->day ),
							gmdate( 'Y-m-d', strtotime( $timetable->date_time ) ),
							snks_localize_time( gmdate( 'h:i a', strtotime( $timetable->date_time ) ) ),
							'www.jalsah.link'
						);
					} else {
						$message = sprintf(
							'تم حجز جلسة %1$s يوم %2$s الموافق %3$s الساعه %4$s ويمكنك معرفة تفاصيل الحجز أو تعديل الموعد بالضغط هنا :%5$s',
							'offline' === $timetable->attendance_type ? 'أوفلاين' : 'أونلاين',
							localize_date_to_arabic( $timetable->day ),
							gmdate( 'Y-m-d', strtotime( $timetable->date_time ) ),
							snks_localize_time( gmdate( 'h:i a', strtotime( $timetable->date_time ) ) ),
							'www.jalsah.link'
						);
					}
					send_sms_via_whysms( $order->get_billing_phone(), $message );
					$patient_user  = get_user_by( 'ID', $customer_id );
					$doctor_user   = get_user_by( 'ID', $timetable->user_id );
					$patient_email = $patient_user->user_email;
					$doctor_email  = $doctor_user->user_email;
					// wp_mail( $patient_email, 'تم حجز جلسة جديدة', $message );

					$doctor_name      = get_user_meta( $timetable->user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $timetable->user_id, 'billing_last_name', true );
					$patient_name     = get_user_meta( $customer_id, 'billing_first_name', true ) . ' ' . get_user_meta( $customer_id, 'billing_last_name', true );
					$session_duration = $timetable->period;
					$session_date     = gmdate( 'Y-m-d', strtotime( $timetable->date_time ) );
					$session_start    = snks_localize_time( gmdate( 'h:i a', strtotime( $timetable->date_time ) ) );
					$session_end      = snks_localize_time( gmdate( 'h:i a', strtotime( "+$session_duration minutes", strtotime( $timetable->date_time ) ) ) );
					$session_price    = $order->get_meta( '_main_price', true );

					// إعداد الرسالة بصيغة HTML و RTL.
					$message = "
<html dir='rtl' lang='ar'>
<head><meta charset='UTF-8'></head>
<body style='font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right;'>
  <h3>تم حجز جلسة جديدة:</h3>
  <ul style='list-style: none; padding: 0;'>
    <li><strong>اسم المعالج:</strong> $doctor_name</li>
    <li><strong>اسم العميل:</strong> $patient_name</li>
    <li><strong>مدة الجلسة:</strong> $session_duration دقيقة</li>
    <li><strong>موعد الجلسة:</strong> $session_date</li>
    <li><strong>وقت الجلسة:</strong> من $session_start إلى $session_end</li>
    <li><strong>سعر الجلسة:</strong> $session_price ج.م</li>
  </ul>
</body>
</html>
";

					wp_mail(
						$doctor_email,
						'تم حجز جلسة جديدة',
						$message,
						array( 'Content-Type: text/html; charset=UTF-8' )
					);

					if ( class_exists( 'FbCloudMessaging\AnonyengineFirebase' ) ) {
						// Use the correct namespace to initialize the class.
						$firebase = new \FbCloudMessaging\AnonyengineFirebase();

						// Ensure that $_req parameters are sanitized before using them.
						$title = 'تم حجز جلسة جديده';
						// Fetch the client's user data.
						$client_id          = $customer_id;
						$billing_first_name = get_user_meta( $client_id, 'billing_first_name', true );
						$billing_last_name  = get_user_meta( $client_id, 'billing_last_name', true );

						// Generate the content with the desired format.
						$content = sprintf(
							'تم حجز جلسة يوم %1$s %2$s الساعه %3$s لمدة %4$s دقيقة باسم %5$s %6$s.',
							localize_date_to_arabic( $timetable->day ), // Localized day name in Arabic.
							gmdate( 'd/m/Y', strtotime( $timetable->date_time ) ), // Format date as DD/MM/YYYY.
							snks_localize_time( gmdate( 'g a', strtotime( $timetable->date_time ) ) ), // Localize time in 12-hour format.
							$timetable->period, // Dynamic session period.
							$billing_first_name, // Client's first name.
							$billing_last_name   // Client's last name.
						);
						$user_id = $timetable->user_id;
						// Call the notifier method.
						$firebase->trigger_notifier( $title, $content, $user_id, '' );
					}
				} else {
					snks_error_log( $order_id . ' :Failed to update timetable.' );
					throw new Exception( 'Failed to update timetable.' );
				}
			}
		} catch ( Exception $e ) {
			// Log the failure for retry.
			$failed_orders = get_option( 'snks_failed_order_actions', array() );
			if ( ! in_array( $order_id, $failed_orders, true ) ) {
				$failed_orders[] = $order_id;
				update_option( 'snks_failed_order_actions', $failed_orders );
			}
		}
	}
}


add_action(
	'woocommerce_order_status_cancelled',
	function ( $order_id ) {
		$order      = wc_get_order( $order_id );
		$booking_id = $order->get_meta( 'booking_id', true );
		$booking    = snks_get_timetable_by( 'ID', $booking_id );
		if ( ! $booking || empty( $booking ) || 'open' === $booking->session_status ) {
			return;
		}
		if ( snks_is_past_date( $booking->date_time ) ) {
			$status = 'cancelled';
		} else {
			$status = 'waiting';
		}
		if ( ! empty( $booking_id ) ) {
			$updated = snks_update_timetable(
				absint( $booking_id ),
				array(
					'client_id'      => 0,
					'session_status' => $status,
					'order_id'       => 0,
				)
			);
			if ( $updated ) {
				if ( 'waiting' === $status ) {
					if ( $booking ) {
						snks_waiting_others( $booking );
					}
				}
			}
		}
	}
);

// Add custom order meta to the edit order screen after billing details.
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( $order ) {
		$booking_id = $order->get_meta( 'booking_id', true );
		$html       = '';
		if ( ! empty( $booking_id ) ) {
			ob_start();
			$html .= '<p><strong>Booking ID:</strong> ' . esc_html( $booking_id ) . '</p>';
			ob_end_clean();
		}

		$meta_data = $order->get_meta_data();

		if ( $meta_data ) {
			foreach ( $meta_data as $meta ) {
				if ( 'order_type' === $meta->key && 'edit-fees' === $meta->value ) {
					$html .= 'Connected Order: <a href="/wp-admin/post.php?post=' . $order->get_meta( 'connected_order' ) . '&action=edit">' . $order->get_meta( 'connected_order' ) . '</a><br>';
					$html .= 'Doctor ID: <a href="/wp-admin/user-edit.php?user_id=' . $order->get_meta( '_user_id' ) . '">' . $order->get_meta( '_user_id' ) . '</a><br>';
					$html .= 'Old Booking ID: ' . $order->get_meta( 'booking_id' ) . '<br>';
					$html .= 'New Booking ID: ' . $order->get_meta( 'new_booking_id' ) . '<br>';
					$html .= 'Order Type: ' . $order->get_meta( 'order_type' ) . '<br>';
				}
			}
		}
		echo wp_kses_post( $html );
	}
);
