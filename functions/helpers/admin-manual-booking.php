<?php
/**
 * Admin Manual Booking Helper
 *
 * Handles processing of admin-created manual bookings and appointment changes.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Ensure a slot exists for therapist at date+time. Finds existing or creates new.
 * Used when admin selects "create new slot" with custom date and base hour.
 *
 * @param int    $therapist_id Therapist user ID.
 * @param string $date         Date Y-m-d.
 * @param string $time         Start time (HH:MM or HH:MM:SS).
 * @return int|false Slot ID on success, false on failure.
 */
function snks_manual_booking_ensure_slot( $therapist_id, $date, $time ) {
	$therapist_id = absint( $therapist_id );
	$date         = sanitize_text_field( $date );
	$time         = sanitize_text_field( $time );
	if ( ! $therapist_id || ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return false;
	}
	// Normalize time to HH:MM:SS.
	if ( preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $m ) ) {
		$time = sprintf( '%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset( $m[3] ) ? (int) $m[3] : 0 );
	} else {
		return false;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'snks_provider_timetable';

	// Find existing available slot.
	$existing = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT ID FROM {$table}
			 WHERE user_id = %d AND DATE(date_time) = %s AND starts = %s
			 AND session_status = 'waiting' AND order_id = 0
			 AND (client_id = 0 OR client_id IS NULL)
			 AND (settings NOT LIKE %s OR settings = '' OR settings IS NULL)
			 AND (settings NOT LIKE %s OR settings = '' OR settings IS NULL)
			 LIMIT 1",
			$therapist_id,
			$date,
			$time,
			'%ai_booking:booked%',
			'%ai_booking:in_cart%'
		)
	);
	if ( $existing ) {
		return (int) $existing->ID;
	}

	// Create new slot. 45-minute session.
	$date_time = $date . ' ' . $time;
	$ts        = strtotime( $date_time );
	if ( false === $ts || $ts < time() ) {
		return false;
	}
	$day_name = gmdate( 'l', $ts );
	$ends_ts  = strtotime( '+45 minutes', $ts );
	$ends     = gmdate( 'H:i:s', $ends_ts );

	$insert = array(
		'user_id'         => $therapist_id,
		'client_id'       => 0,
		'session_status'  => 'waiting',
		'day'             => $day_name,
		'base_hour'       => $time,
		'period'          => 45,
		'date_time'       => gmdate( 'Y-m-d H:i:s', $ts ),
		'starts'          => $time,
		'ends'            => $ends,
		'clinic'          => '',
		'attendance_type' => 'online',
		'order_id'        => 0,
		'settings'        => '',
	);

	$inserted = $wpdb->insert( $table, $insert );
	if ( ! $inserted || $wpdb->last_error ) {
		return false;
	}
	return (int) $wpdb->insert_id;
}

/**
 * Process admin manual booking (new appointment).
 *
 * @param int    $patient_id     Patient user ID.
 * @param int    $therapist_id   Therapist user ID.
 * @param int    $slot_id        Available timetable slot ID.
 * @param string $country_code   Country code for pricing.
 * @param float  $amount_override Optional manual amount override.
 * @param string $first_name     Patient first name (billing).
 * @param string $last_name      Patient last name (billing).
 * @return array{success:bool, message:string, order_id?:int}
 */
function snks_process_admin_manual_booking( $patient_id, $therapist_id, $slot_id, $country_code = 'EG', $amount_override = null, $first_name = '', $last_name = '' ) {
	global $wpdb;

	$patient_id   = absint( $patient_id );
	$therapist_id = absint( $therapist_id );
	$slot_id      = absint( $slot_id );

	if ( ! $patient_id || ! $therapist_id || ! $slot_id ) {
		return array( 'success' => false, 'message' => __( 'معطيات ناقصة.', 'shrinks' ) );
	}

	$patient = get_userdata( $patient_id );
	if ( ! $patient ) {
		return array( 'success' => false, 'message' => __( 'المريض غير موجود.', 'shrinks' ) );
	}

	$first_name = trim( (string) $first_name );
	$last_name  = trim( (string) $last_name );
	if ( $first_name === '' || $last_name === '' ) {
		return array( 'success' => false, 'message' => __( 'يجب إدخال الاسم الأول واسم العائلة للمريض.', 'shrinks' ) );
	}

	$therapist = get_userdata( $therapist_id );
	if ( ! $therapist ) {
		return array( 'success' => false, 'message' => __( 'المعالج غير موجود.', 'shrinks' ) );
	}

	// Ensure patient billing name is set/updated.
	update_user_meta( $patient_id, 'billing_first_name', $first_name );
	update_user_meta( $patient_id, 'billing_last_name', $last_name );

	$slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE ID = %d AND session_status = 'waiting' AND order_id = 0 
		 AND (client_id = 0 OR client_id IS NULL) 
		 AND user_id = %d",
		$slot_id,
		$therapist_id
	) );

	if ( ! $slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد غير متاح أو تم حجزه.', 'shrinks' ) );
	}

	$period = isset( $slot->period ) && $slot->period > 0 ? (int) $slot->period : 45;

	if ( $amount_override !== null && $amount_override > 0 ) {
		$session_amount = floatval( $amount_override );
	} else {
		$pricing = snks_get_ai_therapist_price( $therapist_id, $country_code, $period );
		$session_amount = isset( $pricing['original_price'] ) ? floatval( $pricing['original_price'] ) : 0;
		if ( $session_amount <= 0 ) {
			$session_amount = SNKS_AI_Products::get_default_session_price();
		}
	}

	$order = SNKS_AI_Orders::create_admin_manual_order( $patient_id, $slot_id, $session_amount, $country_code );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return array( 'success' => false, 'message' => __( 'فشل إنشاء الطلب.', 'shrinks' ) );
	}

	$result = SNKS_AI_Orders::book_slot_for_order( $slot_id, $order->get_id(), $patient_id, 'admin_manual_booking:1' );
	if ( ! $result ) {
		$order->set_status( 'cancelled' );
		$order->save();
		return array( 'success' => false, 'message' => __( 'فشل ربط الموعد بالطلب.', 'shrinks' ) );
	}

	// Create earnings immediately (admin confirmed payment).
	// Session action and order meta (ai_therapist_id, ai_user_id) are set by snks_handle_ai_appointment_creation on snks_appointment_created.
	if ( function_exists( 'snks_execute_ai_profit_transfer' ) ) {
		snks_execute_ai_profit_transfer( $slot_id );
	}

	// Send notifications.
	SNKS_AI_Orders::send_ai_order_notifications( $order->get_id() );

	return array(
		'success'  => true,
		'message'  => __( 'تم الحجز بنجاح.', 'shrinks' ),
		'order_id' => $order->get_id(),
	);
}

/**
 * Process admin change appointment (move to new slot).
 *
 * @param int $existing_booking_id Current timetable slot ID (booked).
 * @param int $new_slot_id         New available timetable slot ID.
 * @return array{success:bool, message:string}
 */
function snks_process_admin_change_appointment( $existing_booking_id, $new_slot_id ) {
	global $wpdb;

	$existing_booking_id = absint( $existing_booking_id );
	$new_slot_id         = absint( $new_slot_id );

	if ( ! $existing_booking_id || ! $new_slot_id ) {
		return array( 'success' => false, 'message' => __( 'معطيات ناقصة.', 'shrinks' ) );
	}

	$old_slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d AND session_status = 'open' AND client_id > 0 AND order_id > 0",
		$existing_booking_id
	) );

	if ( ! $old_slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد الحالي غير موجود أو غير محجوز.', 'shrinks' ) );
	}

	$patient_id   = (int) $old_slot->client_id;
	$therapist_id = (int) $old_slot->user_id;
	$order_id    = (int) $old_slot->order_id;

	$new_slot = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE ID = %d AND session_status = 'waiting' AND order_id = 0 
		 AND (client_id = 0 OR client_id IS NULL) AND user_id = %d",
		$new_slot_id,
		$therapist_id
	) );

	if ( ! $new_slot ) {
		return array( 'success' => false, 'message' => __( 'الموعد الجديد غير متاح.', 'shrinks' ) );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return array( 'success' => false, 'message' => __( 'الطلب غير موجود.', 'shrinks' ) );
	}

	$wpdb->query( 'START TRANSACTION' );

	try {
		// Release old slot and reset all notification/state columns to initial state.
		$wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			array(
				'session_status'                      => 'waiting',
				'client_id'                           => 0,
				'order_id'                            => 0,
				'settings'                            => str_replace( 'admin_manual_booking:1', '', $old_slot->settings ),
				'notification_24hr_sent'              => 0,
				'notification_1hr_sent'               => 0,
				'whatsapp_new_session_sent'           => 0,
				'whatsapp_doctor_notified'             => 0,
				'whatsapp_rosheta_activated'          => 0,
				'whatsapp_rosheta_booked'             => 0,
				'whatsapp_doctor_reminded'            => 0,
				'whatsapp_patient_now_sent'           => 0,
				'whatsapp_appointment_changed'        => 0,
				'whatsapp_therapist_appointment_changed' => 0,
			),
			array( 'ID' => $existing_booking_id ),
			array( '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' ),
			array( '%d' )
		);

		// Book new slot with same order.
		$settings = 'ai_booking:completed admin_manual_booking:1';
		$booked = SNKS_AI_Orders::book_slot_for_order( $new_slot_id, $order_id, $patient_id, 'admin_manual_booking:1' );
		if ( ! $booked ) {
			throw new Exception( __( 'فشل حجز الموعد الجديد.', 'shrinks' ) );
		}

		// Update order item slot_id (find the item for this appointment).
		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$item_slot = (int) $item->get_meta( 'slot_id', true );
			if ( $item_slot === $existing_booking_id ) {
				$item->update_meta_data( 'slot_id', $new_slot_id );
				$item->update_meta_data( 'appointment_id', $new_slot_id );
				$item->update_meta_data( 'session_date', $new_slot->date_time );
				$item->update_meta_data( 'session_time', $new_slot->starts );
				$item->save();
				break;
			}
		}

		$wpdb->query( 'COMMIT' );

		// Send notification with new meeting link.
		SNKS_AI_Orders::send_ai_order_notifications( $order_id );

		// Send WhatsApp appointment-change notifications and set timetable flags on the new slot.
		$old_date = date( 'Y-m-d', strtotime( $old_slot->date_time ) );
		$old_time = $old_slot->starts;
		$new_date = date( 'Y-m-d', strtotime( $new_slot->date_time ) );
		$new_time = $new_slot->starts;
		if ( function_exists( 'snks_send_appointment_change_notification' ) ) {
			snks_send_appointment_change_notification( $new_slot_id, $old_date, $old_time, $new_date, $new_time, $patient_id );
		}
		if ( function_exists( 'snks_send_therapist_appointment_change_notification' ) ) {
			snks_send_therapist_appointment_change_notification( $new_slot_id, $old_date, $old_time, $new_date, $new_time, $patient_id );
		}

		return array( 'success' => true, 'message' => __( 'تم تغيير الموعد بنجاح.', 'shrinks' ) );
	} catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		return array( 'success' => false, 'message' => $e->getMessage() );
	}
}
