<?php
/**
 * Package subscriptions helpers for manual bookings.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Package subscriptions table name.
 *
 * @return string
 */
function snks_package_subscriptions_table() {
	global $wpdb;
	return $wpdb->prefix . 'snks_package_subscriptions';
}

/**
 * Allowed package session counts.
 *
 * @return int[]
 */
function snks_package_allowed_types() {
	return array( 4, 6, 8 );
}

/**
 * Allowed payment methods (Vue manual booking values).
 *
 * @return string[]
 */
function snks_package_allowed_payment_methods() {
	return array( 'InstaPay', 'Wallet', 'Bank transfer' );
}

/**
 * Get active package subscription for patient + therapist.
 *
 * @param int $patient_id   Patient user ID.
 * @param int $therapist_id Therapist user ID.
 * @return object|null
 */
function snks_get_active_package_subscription( $patient_id, $therapist_id ) {
	global $wpdb;
	$patient_id   = absint( $patient_id );
	$therapist_id = absint( $therapist_id );
	if ( ! $patient_id || ! $therapist_id ) {
		return null;
	}
	$table = snks_package_subscriptions_table();
	$row   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE patient_id = %d AND therapist_id = %d AND status = 'active' AND remaining_sessions > 0 ORDER BY id DESC LIMIT 1",
			$patient_id,
			$therapist_id
		)
	);
	return $row ? $row : null;
}

/**
 * Format a subscription row for API responses.
 *
 * @param object $row DB row.
 * @return array
 */
function snks_package_subscription_to_array( $row ) {
	if ( ! $row ) {
		return array();
	}
	$patient_id   = (int) $row->patient_id;
	$therapist_id = (int) $row->therapist_id;
	$patient_first = $patient_id ? (string) get_user_meta( $patient_id, 'billing_first_name', true ) : '';
	$patient_last  = $patient_id ? (string) get_user_meta( $patient_id, 'billing_last_name', true ) : '';
	$patient_name  = trim( $patient_first . ' ' . $patient_last );
	if ( '' === $patient_name && $patient_id ) {
		$user = get_userdata( $patient_id );
		$patient_name = $user ? (string) $user->display_name : '—';
	}
	$patient_phone = '';
	if ( $patient_id ) {
		$patient_phone = (string) get_user_meta( $patient_id, 'whatsapp', true );
		if ( '' === $patient_phone ) {
			$patient_phone = (string) get_user_meta( $patient_id, 'billing_whatsapp', true );
		}
		if ( '' === $patient_phone ) {
			$patient_phone = (string) get_user_meta( $patient_id, 'billing_phone', true );
		}
	}
	$therapist_name = $therapist_id && function_exists( 'snks_get_therapist_name' )
		? (string) snks_get_therapist_name( $therapist_id )
		: '—';

	$package_type = (int) $row->package_type;
	$remaining    = (int) $row->remaining_sessions;
	$used         = max( 0, $package_type - $remaining );

	return array(
		'id'                  => (int) $row->id,
		'patient_id'          => $patient_id,
		'therapist_id'        => $therapist_id,
		'patient_name'        => $patient_name ?: '—',
		'patient_phone'       => $patient_phone,
		'therapist_name'      => $therapist_name,
		'package_type'        => $package_type,
		'package_price'       => (float) $row->package_price,
		'session_price'       => (float) $row->session_price,
		'payment_method'      => (string) $row->payment_method,
		'remaining_sessions'  => $remaining,
		'used_sessions'       => $used,
		'next_session_number' => $used + 1,
		'status'              => (string) $row->status,
		'subscribed_at'       => (string) $row->subscribed_at,
		'cancelled_at'        => $row->cancelled_at ? (string) $row->cancelled_at : null,
		'created_by'          => (int) $row->created_by,
	);
}

/**
 * Create a package subscription.
 *
 * @param array $args Subscription fields.
 * @return array{success:bool,message:string,subscription?:array}
 */
function snks_create_package_subscription( $args ) {
	global $wpdb;

	$patient_id     = isset( $args['patient_id'] ) ? absint( $args['patient_id'] ) : 0;
	$therapist_id   = isset( $args['therapist_id'] ) ? absint( $args['therapist_id'] ) : 0;
	$package_type   = isset( $args['package_type'] ) ? absint( $args['package_type'] ) : 0;
	$package_price  = isset( $args['package_price'] ) ? floatval( $args['package_price'] ) : 0;
	$session_price  = isset( $args['session_price'] ) ? floatval( $args['session_price'] ) : 0;
	$payment_method = isset( $args['payment_method'] ) ? sanitize_text_field( $args['payment_method'] ) : '';
	$created_by     = isset( $args['created_by'] ) ? absint( $args['created_by'] ) : get_current_user_id();

	if ( ! $patient_id || ! $therapist_id ) {
		return array( 'success' => false, 'message' => __( 'يرجى اختيار المريض والمعالج.', 'shrinks' ) );
	}
	if ( ! in_array( $package_type, snks_package_allowed_types(), true ) ) {
		return array( 'success' => false, 'message' => __( 'نوع الباقة غير صالح. اختر 4 أو 6 أو 8.', 'shrinks' ) );
	}
	if ( $package_price < 0 || $session_price <= 0 ) {
		return array( 'success' => false, 'message' => __( 'يرجى إدخال سعر الباقة وسعر الجلسة بشكل صحيح.', 'shrinks' ) );
	}
	if ( ! in_array( $payment_method, snks_package_allowed_payment_methods(), true ) ) {
		return array( 'success' => false, 'message' => __( 'طريقة الدفع غير صالحة.', 'shrinks' ) );
	}
	if ( ! get_userdata( $patient_id ) ) {
		return array( 'success' => false, 'message' => __( 'المريض غير موجود.', 'shrinks' ) );
	}
	if ( ! get_userdata( $therapist_id ) ) {
		return array( 'success' => false, 'message' => __( 'المعالج غير موجود.', 'shrinks' ) );
	}

	$existing = snks_get_active_package_subscription( $patient_id, $therapist_id );
	if ( $existing ) {
		return array( 'success' => false, 'message' => __( 'يوجد اشتراك باقة نشط بالفعل لهذا المريض مع هذا المعالج.', 'shrinks' ) );
	}

	$now   = current_time( 'mysql' );
	$table = snks_package_subscriptions_table();
	$ok    = $wpdb->insert(
		$table,
		array(
			'patient_id'         => $patient_id,
			'therapist_id'       => $therapist_id,
			'package_type'       => $package_type,
			'package_price'      => $package_price,
			'session_price'      => $session_price,
			'payment_method'     => $payment_method,
			'remaining_sessions' => $package_type,
			'status'             => 'active',
			'subscribed_at'      => $now,
			'created_by'         => $created_by,
		),
		array( '%d', '%d', '%d', '%f', '%f', '%s', '%d', '%s', '%s', '%d' )
	);

	if ( ! $ok ) {
		return array( 'success' => false, 'message' => __( 'فشل إنشاء الاشتراك.', 'shrinks' ) );
	}

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $wpdb->insert_id ) );
	return array(
		'success'      => true,
		'message'      => __( 'تم ربط المريض بالباقة بنجاح.', 'shrinks' ),
		'subscription' => snks_package_subscription_to_array( $row ),
	);
}

/**
 * Cancel an active package subscription.
 *
 * @param int $subscription_id Subscription ID.
 * @return array{success:bool,message:string}
 */
function snks_cancel_package_subscription( $subscription_id ) {
	global $wpdb;
	$subscription_id = absint( $subscription_id );
	if ( ! $subscription_id ) {
		return array( 'success' => false, 'message' => __( 'اشتراك غير صالح.', 'shrinks' ) );
	}
	$table = snks_package_subscriptions_table();
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $subscription_id ) );
	if ( ! $row ) {
		return array( 'success' => false, 'message' => __( 'الاشتراك غير موجود.', 'shrinks' ) );
	}
	if ( 'cancelled' === $row->status ) {
		return array( 'success' => false, 'message' => __( 'الاشتراك ملغى بالفعل.', 'shrinks' ) );
	}
	if ( 'completed' === $row->status ) {
		return array( 'success' => false, 'message' => __( 'لا يمكن إلغاء اشتراك مكتمل.', 'shrinks' ) );
	}

	$updated = $wpdb->update(
		$table,
		array(
			'status'       => 'cancelled',
			'cancelled_at' => current_time( 'mysql' ),
		),
		array( 'id' => $subscription_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		return array( 'success' => false, 'message' => __( 'فشل إلغاء الاشتراك.', 'shrinks' ) );
	}

	return array( 'success' => true, 'message' => __( 'تم إلغاء الاشتراك.', 'shrinks' ) );
}

/**
 * Consume one session from an active package subscription.
 *
 * @param int $subscription_id Subscription ID.
 * @return array{success:bool,message:string,session_number?:int,total_sessions?:int,remaining?:int,subscription?:object}
 */
function snks_consume_package_session( $subscription_id ) {
	global $wpdb;
	$subscription_id = absint( $subscription_id );
	$table           = snks_package_subscriptions_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( 'START TRANSACTION' );

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND status = 'active' FOR UPDATE",
			$subscription_id
		)
	);

	if ( ! $row || (int) $row->remaining_sessions <= 0 ) {
		$wpdb->query( 'ROLLBACK' );
		return array( 'success' => false, 'message' => __( 'لا توجد جلسات متبقية في الباقة.', 'shrinks' ) );
	}

	$package_type   = (int) $row->package_type;
	$remaining      = (int) $row->remaining_sessions;
	$session_number = $package_type - $remaining + 1;
	$new_remaining  = $remaining - 1;
	$new_status     = $new_remaining <= 0 ? 'completed' : 'active';

	$updated = $wpdb->update(
		$table,
		array(
			'remaining_sessions' => $new_remaining,
			'status'             => $new_status,
		),
		array( 'id' => $subscription_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		$wpdb->query( 'ROLLBACK' );
		return array( 'success' => false, 'message' => __( 'فشل خصم جلسة من الباقة.', 'shrinks' ) );
	}

	$wpdb->query( 'COMMIT' );

	$row->remaining_sessions = $new_remaining;
	$row->status             = $new_status;

	return array(
		'success'         => true,
		'message'         => __( 'تم خصم الجلسة من الباقة.', 'shrinks' ),
		'session_number'  => $session_number,
		'total_sessions'  => $package_type,
		'remaining'       => $new_remaining,
		'subscription'    => $row,
	);
}

/**
 * List package subscriptions with optional status filter.
 *
 * @param array $args Query args.
 * @return array{rows:array,total:int}
 */
function snks_list_package_subscriptions( $args = array() ) {
	global $wpdb;
	$table = snks_package_subscriptions_table();

	$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
	$per_page = isset( $args['per_page'] ) ? max( 1, min( 500, absint( $args['per_page'] ) ) ) : 100;
	$status   = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
	$offset   = ( $page - 1 ) * $per_page;

	$where  = '1=1';
	$params = array();
	if ( $status && in_array( $status, array( 'active', 'completed', 'cancelled' ), true ) ) {
		$where   .= ' AND status = %s';
		$params[] = $status;
	}

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
	if ( $params ) {
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
	} else {
		$total = (int) $wpdb->get_var( $count_sql );
	}

	$list_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY subscribed_at DESC LIMIT %d OFFSET %d";
	$list_params = array_merge( $params, array( $per_page, $offset ) );
	$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ) );

	$result = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$result[] = snks_package_subscription_to_array( $row );
		}
	}

	return array( 'rows' => $result, 'total' => $total );
}

/**
 * Resolve patient user IDs by phone (digits / whatsapp / billing phone).
 *
 * @param string $phone Phone search.
 * @return int[]
 */
function snks_package_find_patient_ids_by_phone( $phone ) {
	global $wpdb;
	$phone = preg_replace( '/\D+/', '', (string) $phone );
	if ( strlen( $phone ) < 6 ) {
		return array();
	}
	$like = '%' . $wpdb->esc_like( $phone ) . '%';
	$ids  = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key IN ('billing_phone','billing_whatsapp','whatsapp')
			 AND meta_value LIKE %s
			 LIMIT 50",
			$like
		)
	);
	return array_map( 'absint', is_array( $ids ) ? $ids : array() );
}

/**
 * List package sessions (manual bookings from packages) with phone / date filters.
 *
 * Filter rules:
 * - connect_active + phone: phone AND date range
 * - phone only (connect off): phone, skip date range
 * - no phone: date range
 *
 * @param array $args Filters.
 * @return array{rows:array,total:int}
 */
function snks_list_package_sessions( $args = array() ) {
	global $wpdb;

	$phone          = isset( $args['phone'] ) ? (string) $args['phone'] : '';
	$date_from      = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
	$date_to        = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';
	$connect_active = ! empty( $args['connect_active'] );
	$page           = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
	$per_page       = isset( $args['per_page'] ) ? max( 1, min( 500, absint( $args['per_page'] ) ) ) : 100;
	$offset         = ( $page - 1 ) * $per_page;

	$phone_digits = preg_replace( '/\D+/', '', $phone );
	$has_phone    = strlen( $phone_digits ) >= 6;

	if ( ! $date_from || ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $date_from ) ) {
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month', current_time( 'timestamp' ) ) );
	}
	if ( ! $date_to || ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $date_to ) ) {
		$date_to = current_time( 'mysql' );
	}
	// Normalize date-only to full day bounds.
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
		$date_from .= ' 00:00:00';
	}
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
		$date_to .= ' 23:59:59';
	}

	$timetable = $wpdb->prefix . 'snks_provider_timetable';
	$where     = "t.session_status IN ('open','completed') AND t.client_id > 0 AND t.settings LIKE '%package_booking:1%'";
	$params    = array();

	if ( $has_phone ) {
		$patient_ids = snks_package_find_patient_ids_by_phone( $phone_digits );
		if ( empty( $patient_ids ) ) {
			return array( 'rows' => array(), 'total' => 0 );
		}
		$placeholders = implode( ',', array_fill( 0, count( $patient_ids ), '%d' ) );
		$where       .= " AND t.client_id IN ({$placeholders})";
		$params       = array_merge( $params, $patient_ids );
		if ( $connect_active ) {
			$where   .= ' AND t.date_time BETWEEN %s AND %s';
			$params[] = $date_from;
			$params[] = $date_to;
		}
	} else {
		$where   .= ' AND t.date_time BETWEEN %s AND %s';
		$params[] = $date_from;
		$params[] = $date_to;
	}

	$count_sql = "SELECT COUNT(*) FROM {$timetable} t WHERE {$where}";
	$total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

	$list_params = array_merge( $params, array( $per_page, $offset ) );
	$rows        = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT t.ID AS session_id, t.order_id, t.client_id AS patient_id, t.user_id AS therapist_id, t.date_time, t.settings
			 FROM {$timetable} t
			 WHERE {$where}
			 ORDER BY t.date_time DESC
			 LIMIT %d OFFSET %d",
			$list_params
		)
	);

	$result = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $r ) {
			$order_id = (int) $r->order_id;
			$order    = $order_id ? wc_get_order( $order_id ) : null;
			$session_num  = $order ? (int) $order->get_meta( 'package_session_number' ) : 0;
			$session_tot  = $order ? (int) $order->get_meta( 'package_total_sessions' ) : 0;
			$session_price = $order ? (float) $order->get_meta( '_main_price' ) : 0;
			if ( $session_price <= 0 && $order ) {
				$extra = (float) $order->get_meta( 'admin_manual_extra_fees' );
				$session_price = max( 0, (float) $order->get_total() - $extra );
			}
			$payment_method = $order ? (string) $order->get_meta( 'admin_manual_payment_method' ) : '';
			$patient_first  = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_first_name', true ) : '';
			$patient_last   = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_last_name', true ) : '';
			$patient_phone  = '';
			if ( (int) $r->patient_id ) {
				$patient_phone = get_user_meta( $r->patient_id, 'whatsapp', true );
				if ( '' === $patient_phone ) {
					$patient_phone = get_user_meta( $r->patient_id, 'billing_whatsapp', true );
				}
				if ( '' === $patient_phone ) {
					$patient_phone = get_user_meta( $r->patient_id, 'billing_phone', true );
				}
			}
			$therapist_name = function_exists( 'snks_get_therapist_name' )
				? snks_get_therapist_name( (int) $r->therapist_id )
				: '—';

			$result[] = array(
				'session_id'              => (int) $r->session_id,
				'order_id'                => $order_id,
				'date_time'               => (string) $r->date_time,
				'patient_id'              => (int) $r->patient_id,
				'patient_name'            => trim( $patient_first . ' ' . $patient_last ) ?: '—',
				'patient_phone'           => $patient_phone,
				'therapist_id'            => (int) $r->therapist_id,
				'therapist_name'          => $therapist_name,
				'session_price'           => $session_price,
				'payment_method'          => $payment_method ?: '—',
				'package_session_number'  => $session_num,
				'package_total_sessions'  => $session_tot,
				'package_counter'         => ( $session_num && $session_tot ) ? ( $session_num . '/' . $session_tot ) : '',
			);
		}
	}

	return array( 'rows' => $result, 'total' => $total );
}

/**
 * Owe report: active subscriptions with remaining sessions.
 *
 * @return array{rows:array}
 */
function snks_package_owe_report() {
	global $wpdb;
	$table = snks_package_subscriptions_table();
	$rows  = $wpdb->get_results(
		"SELECT * FROM {$table} WHERE status = 'active' AND remaining_sessions > 0 ORDER BY subscribed_at DESC"
	);
	$result = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$item = snks_package_subscription_to_array( $row );
			$item['amount_paid']     = (float) $row->package_price;
			$item['amount_remaining'] = (float) $row->remaining_sessions * (float) $row->session_price;
			$result[] = $item;
		}
	}
	return array( 'rows' => $result );
}

/**
 * List sessions that have extra fees > 0 (HPOS-safe: reads order meta via WC_Order).
 * Extra fees are Jalsah revenue, not therapist earnings.
 *
 * @param array $args date_from, date_to, page, per_page.
 * @return array{rows:array,total:int,total_extra_fees:float}
 */
function snks_list_extra_fees_sessions( $args = array() ) {
	global $wpdb;

	$date_from = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
	$date_to   = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';
	$page      = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
	$per_page  = isset( $args['per_page'] ) ? max( 1, min( 500, absint( $args['per_page'] ) ) ) : 100;
	$offset    = ( $page - 1 ) * $per_page;

	if ( ! $date_from || ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $date_from ) ) {
		$date_from = gmdate( 'Y-m-d', strtotime( '-1 month', current_time( 'timestamp' ) ) );
	}
	if ( ! $date_to || ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $date_to ) ) {
		$date_to = current_time( 'Y-m-d' );
	}
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
		$date_from .= ' 00:00:00';
	}
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
		$date_to .= ' 23:59:59';
	}

	$timetable = $wpdb->prefix . 'snks_provider_timetable';

	// Manual-booking sessions in range; filter extra fees via WC_Order meta (HPOS-safe).
	$sql = "
		SELECT t.ID AS session_id, t.order_id, t.date_time, t.user_id AS therapist_id
		FROM {$timetable} t
		WHERE t.session_status IN ('open','completed')
			AND t.client_id > 0
			AND t.order_id > 0
			AND t.settings LIKE '%admin_manual_booking%'
			AND t.date_time BETWEEN %s AND %s
		ORDER BY t.date_time DESC
	";

	$all = $wpdb->get_results( $wpdb->prepare( $sql, $date_from, $date_to ) );
	if ( ! is_array( $all ) ) {
		$all = array();
	}

	$total_extra = 0.0;
	$rows_out    = array();
	foreach ( $all as $r ) {
		$order_id = (int) $r->order_id;
		if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
			continue;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			continue;
		}
		$extra = (float) $order->get_meta( 'admin_manual_extra_fees' );
		if ( $extra <= 0 ) {
			continue;
		}
		$main = (float) $order->get_meta( '_main_price' );
		if ( $main <= 0 ) {
			$main = max( 0, (float) $order->get_total() - $extra );
		}
		$total_extra += $extra;
		$rows_out[]   = array(
			'session_id'      => (int) $r->session_id,
			'order_id'        => $order_id,
			'date_time'       => (string) $r->date_time,
			'therapist_price' => $main,
			'extra_fees'      => $extra,
		);
	}

	$total = count( $rows_out );
	$slice = array_slice( $rows_out, $offset, $per_page );

	return array(
		'rows'             => $slice,
		'total'            => $total,
		'total_extra_fees' => round( $total_extra, 2 ),
	);
}

/**
 * Stamp package metas on order after successful booking.
 *
 * @param WC_Order $order           Order.
 * @param int      $subscription_id Subscription ID.
 * @param int      $session_number  1-based session index.
 * @param int      $total_sessions  Package size.
 * @return void
 */
function snks_stamp_package_order_metas( $order, $subscription_id, $session_number, $total_sessions ) {
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}
	$order->update_meta_data( 'package_subscription_id', absint( $subscription_id ) );
	$order->update_meta_data( 'is_package_session', 1 );
	$order->update_meta_data( 'package_session_number', absint( $session_number ) );
	$order->update_meta_data( 'package_total_sessions', absint( $total_sessions ) );
	$order->save();
}

/**
 * Get package counter string for an order or timetable settings.
 *
 * @param WC_Order|null $order    Order.
 * @param object|null   $timetable Timetable row optional.
 * @return string Empty or "n/x".
 */
function snks_get_package_session_counter( $order = null, $timetable = null ) {
	$n = 0;
	$x = 0;
	if ( $order && is_a( $order, 'WC_Order' ) ) {
		$n = (int) $order->get_meta( 'package_session_number' );
		$x = (int) $order->get_meta( 'package_total_sessions' );
	}
	if ( ( ! $n || ! $x ) && $timetable && ! empty( $timetable->order_id ) ) {
		$o = wc_get_order( (int) $timetable->order_id );
		if ( $o ) {
			$n = (int) $o->get_meta( 'package_session_number' );
			$x = (int) $o->get_meta( 'package_total_sessions' );
		}
	}
	if ( $n > 0 && $x > 0 ) {
		return $n . '/' . $x;
	}
	return '';
}
