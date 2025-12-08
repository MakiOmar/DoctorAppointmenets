<?php
/**
 * Edit appointment
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Apply edit booking
 *
 * @param object $booking Booking object.
 * @param object $main_order The main appointment order.
 * @param string $new_booking_id Selected hour.
 * @param bool   $free Is it free edit or not.
 * @return mixed
 */
function snks_apply_booking_edit( $booking, $main_order, $new_booking_id, $free = true ) {
	$_doctor_url     = snks_encrypted_doctor_url( $booking->user_id );
	$booking_changed = false;
	$error_url       = add_query_arg(
		array(
			'edit-booking' => $booking->ID,
			'error'        => 'unknown',
		),
		$_doctor_url
	);
	$prev_date       = gmdate( 'Y-m-d', strtotime( $booking->date_time ) );
	$updated         = snks_update_timetable(
		$booking->ID,
		array(
			'client_id' => 0,
			'order_id'  => 0,
		)
	);
	// If previous appointment is reset.
	if ( $updated ) {
		$status        = 'waiting';
		$new_timetable = snks_get_timetable_by( 'ID', absint( $new_booking_id ) );
		$new_date      = gmdate( 'Y-m-d', strtotime( $new_timetable->date_time ) );
		if ( $new_date === $prev_date ) {
			$prev_date_starts = strtotime( '1970-01-01 ' . $booking->starts );
			$prev_date_ends   = strtotime( '1970-01-01 ' . $booking->ends );
			$new_date_starts  = strtotime( '1970-01-01 ' . $new_timetable->starts );
			if ( $prev_date_starts < $new_date_starts && $prev_date_ends > $new_date_starts ) {
				$status = 'closed';
			}
		}
		if ( 'postponed' !== $booking->session_status ) {
			$old_updated = snks_update_timetable(
				$booking->ID,
				array(
					'session_status' => $status,
				)
			);
			if ( $old_updated ) {
				if ( 'waiting' === $status ) {
					snks_waiting_others( $booking );
				} else {
					snks_close_others( $booking );
				}
			}
		}
		if ( $old_updated || 'postponed' === $booking->session_status ) {
			$updated = snks_update_timetable(
				$new_timetable->ID,
				array(
					'session_status'         => 'open',
					'client_id'              => $booking->client_id,
					'order_id'               => $main_order->get_id(),
					'settings'               => $booking->settings,
					'notification_24hr_sent' => 0,
					'notification_1hr_sent'  => 0,
				)
			);
			if ( $updated ) {
				snks_close_others( $new_timetable );
				if ( snks_is_patient() ) {
					$main_order->update_meta_data( 'booking-edited', '1' );
				}
				$main_order->update_meta_data( 'booking_id', $new_timetable->ID );
				$main_order->save();
				$line_items = $main_order->get_items();
				// Loop through each line item.
				foreach ( $line_items as $item_id => $item ) {
					wc_update_order_item_meta( $item_id, 'booking_id', $new_timetable->ID );
				}
				// Doctor.
				if ( snks_is_patient() && class_exists( 'FbCloudMessaging\AnonyengineFirebase' ) ) {
					// Use the correct namespace to initialize the class.
					$firebase = new \FbCloudMessaging\AnonyengineFirebase();

					// Ensure that $_req parameters are sanitized before using them.
					$title = 'تم تعديل موعد جلسة محجوزه';

					// Fetch the client's user data.
					$client_id          = $booking->client_id;
					$billing_first_name = get_user_meta( $client_id, 'billing_first_name', true );
					$billing_last_name  = get_user_meta( $client_id, 'billing_last_name', true );

					// Generate the old session date and time.
					$old_date_formatted = sprintf(
						'%1$s %2$s الساعه %3$s',
						localize_date_to_arabic( $booking->day ), // Localized old day name in Arabic.
						gmdate( 'd/m/Y', strtotime( $booking->date_time ) ), // Old date formatted as DD/MM/YYYY.
						snks_localize_time( gmdate( 'g a', strtotime( $booking->date_time ) ) ) // Old time localized in 12-hour format.
					);

					// Generate the new session date and time.
					$new_date_formatted = sprintf(
						'%1$s %2$s الساعه %3$s',
						localize_date_to_arabic( $new_timetable->day ), // Localized new day name in Arabic.
						gmdate( 'd/m/Y', strtotime( $new_timetable->date_time ) ), // New date formatted as DD/MM/YYYY.
						snks_localize_time( gmdate( 'g a', strtotime( $new_timetable->date_time ) ) ) // New time localized in 12-hour format.
					);

					// Generate the content with the dynamic period.
					$content = sprintf(
						'تم تعديل موعد جلسة يوم %1$s ومدتها %2$s دقيقة باسم %3$s %4$s الي موعد يوم %5$s.',
						$old_date_formatted,          // Old session date and time.
						$new_timetable->period,       // Dynamic session period.
						$billing_first_name,          // Client's first name.
						$billing_last_name,           // Client's last name.
						$new_date_formatted           // New session date and time.
					);
					$user_id = $new_timetable->user_id;

					// Call the notifier method.
					$firebase->trigger_notifier( $title, $content, $user_id, '' );
				}
				
				// Send WhatsApp notifications for AI sessions (patient + therapist)
				if ( strpos( $booking->settings, 'ai_booking' ) !== false ) {
					// Extract old appointment details
					$old_date = date( 'Y-m-d', strtotime( $booking->date_time ) );
					$old_time = $booking->starts;
					
					// Extract new appointment details
					$new_date = date( 'Y-m-d', strtotime( $new_timetable->date_time ) );
					$new_time = $new_timetable->starts;

					// Patient notification (pass patient_id for fallback when new slot lacks client_id)
					if ( function_exists( 'snks_send_appointment_change_notification' ) ) {
						snks_send_appointment_change_notification( $new_timetable->ID, $old_date, $old_time, $new_date, $new_time, $booking->client_id );
					}

					// Therapist notification (pass patient_id for accurate name)
					if ( function_exists( 'snks_send_therapist_appointment_change_notification' ) ) {
						snks_send_therapist_appointment_change_notification( $booking->ID, $old_date, $old_time, $new_date, $new_time, $booking->client_id );
					}
				}
				
				$booking_changed = true;
				if ( $free ) {
					if ( snks_is_doctor() ) {
						return true;
					}

					wp_safe_redirect(
						add_query_arg(
							array(
								'edit' => 'success',
							),
							site_url( '/my-bookings/' )
						)
					);
					exit;
				}
				$booking_changed = true;

				return true;
			}
		}
	}
	if ( snks_is_patient() && ! $booking_changed ) {
		wp_safe_redirect( $error_url );
		exit;
	}

	return true;
}


/**
 * Create an order for edit appointment
 *
 * @param object $main_order The main appointment order.
 * @param int    $will_pay Payment amount.
 * @param object $booking Booking object.
 * @param int    $new_booking_id New Booking ID.
 * @param array  $extra Extra detals.
 * @return mixed
 */
function snks_create_edit_fees_order( $main_order, $will_pay, $booking, $new_booking_id, $extra = array() ) {
	$fees_order = wc_create_order();
	$fees_order->set_customer_id( get_current_user_id() );
	$product_id = 2363;
	$product    = wc_get_product( $product_id );
	$product->set_price( $will_pay );
	$fees_order->add_product( $product, 1 ); // 1 quantity.
	$fees_order->set_total( $will_pay );
	$fees_order->update_meta_data( 'connected_order', $main_order->get_id() );
	$fees_order->update_meta_data( '_user_id', $booking->user_id );
	$fees_order->update_meta_data( 'booking_id', $booking->ID );
	$fees_order->update_meta_data( 'new_booking_id', $new_booking_id );
	$fees_order->update_meta_data( 'order_type', 'edit-fees' );
	foreach ( $extra as $k => $v ) {
		$fees_order->update_meta_data( $k, $v );
	}
	$fees_order->update_status( 'wc-pending-payment' );

	// Set the billing details for the fees order.
	$fees_order->set_billing_first_name( $main_order->get_billing_first_name() );
	$fees_order->set_billing_last_name( $main_order->get_billing_last_name() );
	$fees_order->set_billing_company( $main_order->get_billing_company() );
	$fees_order->set_billing_address_1( $main_order->get_billing_address_1() );
	$fees_order->set_billing_address_2( $main_order->get_billing_address_2() );
	$fees_order->set_billing_city( $main_order->get_billing_city() );
	$fees_order->set_billing_state( $main_order->get_billing_state() );
	$fees_order->set_billing_postcode( $main_order->get_billing_postcode() );
	$fees_order->set_billing_country( $main_order->get_billing_country() );
	$fees_order->set_billing_email( $main_order->get_billing_email() );
	$fees_order->set_billing_phone( $main_order->get_billing_phone() );
	$fees_order->save();
	return $fees_order;
}
// Hook into WordPress AJAX.
add_action(
	'wp_ajax_doctor_change_appointment',
	function () {
		$_req = wp_unslash( $_POST );

		// Check the nonce for security.
		if ( ! isset( $_req['change_appointment_nonce'] ) || ! wp_verify_nonce( $_req['change_appointment_nonce'], 'change_appointment' ) ) {
			wp_send_json(
				array(
					'message' => 'فشل التحقق الأمني. يرجى إعادة المحاولة.',
					'status'  => 'faild',
				)
			);
			die;
		}

		// Ensure required fields are provided.
		if ( empty( $_req['old_appointment'] ) || empty( $_req['appointment_id'] ) ) {
			wp_send_json(
				array(
					'message' => 'يرجى تحديد الموعد القديم والتاريخ الجديد.',
					'status'  => 'faild',
				)
			);
			die;
		}

		// Check if the user is a doctor.
		if ( ! snks_is_doctor() ) {
			wp_send_json(
				array(
					'message' => 'غير مسموح. يجب أن تكون طبيباً لتغيير الموعد.',
					'status'  => 'faild',
				)
			);
			die;
		}

		// Retrieve the booking by the old appointment ID.
		$booking = snks_get_timetable_by( 'ID', absint( $_req['old_appointment'] ) );

		// Validate the booking and user.
		if ( ! $booking || snks_get_settings_doctor_id() !== absint( $booking->user_id ) ) {
			wp_send_json(
				array(
					'message' => 'غير مسموح لك بتغيير هذا الموعد.',
					'status'  => 'faild',
				)
			);
			die;
		}

		// Process the booking change.
		$main_order     = wc_get_order( $booking->order_id );
		$new_booking_id = $_req['appointment_id'];
		$edited         = snks_apply_booking_edit( $booking, $main_order, $new_booking_id );
		if ( $edited ) {
			// Trigger the custom action for booking edit.
			do_action( 'snks_doctor_edit_booking' );

			// Fetch patient and appointment details.
			$patient_id    = $booking->client_id;
			$patient_user  = get_user_by( 'id', $patient_id );
			$patient_email = $patient_user->user_email;
			$billing_phone = get_user_meta( $patient_id, 'billing_phone', true );

			// Old and new appointment details.
			$old_date      = gmdate( 'd/m/Y', strtotime( $booking->date_time ) );
			$new_booking   = snks_get_timetable_by( 'ID', absint( $new_booking_id ) );
			$new_date_time = $new_booking->date_time; // Assuming this function fetches the new date & time.
			$new_date      = gmdate( 'd/m/Y', strtotime( $new_date_time ) );
			$new_time      = gmdate( 'h:i a', strtotime( $new_date_time ) );

			// Notification title and message.
			$title   = 'تم تعديل موعدك';
			$message = sprintf(
				'تم تغيير موعد جلسة يوم %1$s الي يوم %2$s الساعة %3$s',
				$old_date,
				$new_date,
				$new_time
			);
			if ( empty( $billing_phone ) ) {
				$billing_phone = $patient_user->user_login;
			}
			// Send SMS notification.
			send_sms_via_whysms( $billing_phone, $message );

			// Send email notification.
			$subject = $title . ' - ' . SNKS_APP_NAME;
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . SNKS_APP_NAME . ' <' . SNKS_EMAIL . '>',
			);
			//wp_mail( $patient_email, 'تم تعديل موعدك', $message, $headers );

			// Send a success response.
			wp_send_json(
				array(
					'message' => 'تم تغيير الموعد بنجاح. تم إشعار المريض عبر البريد والرسائل النصية.',
					'status'  => 'success',
				)
			);
			die;
		} else {
			wp_send_json(
				array(
					'message' => 'عفواّ لم يتم تغيير الموعد.',
					'status'  => 'faild',
				)
			);
			die;
		}
	}
);


/**
 * Function for `woocommerce_payment_complete` action-hook.
 *
 * @param int $order_id Order's ID.
 * @return void
 */
add_action(
	'woocommerce_thankyou',
	function ( $order_id ) {
		$order      = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		
		// Skip processing if this is an AI order (handled separately)
		$is_ai_order = $order->get_meta( 'from_jalsah_ai' );
		if ( $is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1 ) {
			// Redirect AI orders to the frontend appointments page
            $frontend_url = snks_ai_get_primary_frontend_url();
            if ( $frontend_url ) {
				// Use wp_redirect for external URLs (wp_safe_redirect only works for same domain)
				wp_redirect( $frontend_url . '/appointments' );
				exit;
			}
		}
		
		$order_type = $order->get_meta( 'order_type' );
		if ( 'edit-fees' === $order_type && ( $order->has_status( 'completed' ) || $order->has_status( 'processing' ) ) ) {
			$connected_order = $order->get_meta( 'connected_order' );
			if ( ! $connected_order ) {
				return;
			}
			$old_booking_id = $order->get_meta( 'booking_id' );
			$new_booking_id = $order->get_meta( 'new_booking_id' );
			$booking        = snks_get_timetable_by( 'ID', absint( $old_booking_id ) );
			$main_order     = wc_get_order( absint( $connected_order ) );
			if ( ! $main_order ) {
				return;
			}
			snks_apply_booking_edit( $booking, $main_order, $new_booking_id, false );
			do_action( 'snks_patient_edit_booking' );
			wp_safe_redirect( home_url( '/my-bookings' ) );
			exit;
		}
	},
	5  // Higher priority to run before other hooks
);

/**
 * Save form data to session
 *
 * @return void
 */
add_action(
	'template_redirect',
	function () {
		if ( empty( $_POST['edit-booking-id'] ) ) {
			return;
		}
		if ( isset( $_POST ) && isset( $_POST['create_appointment_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['create_appointment_nonce'] ) ), 'create_appointment' ) && isset( $_POST['create-appointment'] ) ) {
			$_request      = wp_unslash( $_POST );
			$needs_payment = false;
			$_doctor_url   = snks_encrypted_doctor_url( $_request['user-id'] );
			$booking       = snks_get_timetable_by( 'ID', absint( $_request['edit-booking-id'] ) );
			if ( ! $booking || get_current_user_id() !== absint( $booking->client_id ) ) {
				wp_safe_redirect( add_query_arg( 'error', 'unknown', $_doctor_url ) );
				exit;
			}
			$new_booking     = snks_get_timetable_by( 'ID', absint( $_request['selected-hour'] ) );
			$doctor_settings = json_decode( $booking->settings, true );
			if ( ! $doctor_settings || empty( $doctor_settings ) ) {
				$doctor_settings = snks_doctor_settings( $booking->user_id );
			}
			$diff_seconds     = snks_diff_seconds( $booking );
			$order_id         = $booking->order_id;
			$main_order       = wc_get_order( $order_id );
			$country          = $doctor_settings['country'];
			$price            = snks_calculated_price( $new_booking->user_id, $country, $new_booking->period, $new_booking->attendance_type );
			$change_fees      = ! empty( $doctor_settings['appointment_change_fee'] ) ? $doctor_settings['appointment_change_fee'] : 0;
			$will_pay         = ( $change_fees / 100 ) * $price;
			$calculated_price = snks_session_total_price( $will_pay, $new_booking->attendance_type, 'edit' );
			$will_pay         = $calculated_price['total_price'];
			$order            = wc_get_order( $order_id );
			$edited_before    = $order->get_meta( 'booking-edited', true );
			
			// Check 24-hour restriction for AI bookings using dedicated function
			if ( ! function_exists( 'snks_can_edit_ai_appointment' ) ) {
				require_once SNKS_DIR . 'functions/helpers.php';
			}
			
			if ( ! snks_can_edit_ai_appointment( $booking ) ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'edit-booking' => $_request['edit-booking-id'],
							'error'        => 'ai-24-hour-limit',
						),
						$_doctor_url
					)
				);
				exit;
			}
			
			// If not postponed then check for edit time.
			if ( 'postponed' !== $booking->session_status && ( ( $edited_before && ! empty( $edited_before ) ) || $diff_seconds < snks_get_edit_before_seconds( $doctor_settings ) ) ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'edit-booking' => $_request['edit-booking-id'],
							'error'        => 'edit-limit',
						),
						$_doctor_url
					)
				);
				exit;
			}
			$updated = snks_update_timetable(
				$new_booking->ID,
				array(
					'session_status' => 'pending',
				)
			);
			if ( $updated ) {
				snks_close_others( $new_booking );
			}
			// Compare the input date and time with the modified current date and time.
			if ( ! snks_is_doctor() && 'postponed' !== $booking->session_status && ( ! $edited_before || empty( $edited_before ) ) && $diff_seconds < snks_get_free_edit_before_seconds( $doctor_settings ) ) {
				$needs_payment = true;
				$fees_order    = snks_create_edit_fees_order(
					$main_order,
					$will_pay,
					$booking,
					$_request['selected-hour'],
					$calculated_price
				);
				if ( is_a( $fees_order, 'WC_Order' ) ) {
					wp_safe_redirect( $fees_order->get_checkout_payment_url() );
					exit;
				}
			}

			if ( ! $needs_payment ) {
				snks_apply_booking_edit( $booking, $main_order, $_request['selected-hour'], true );
				do_action( 'snks_patient_edit_booking' );
			}
		}
	}
);
