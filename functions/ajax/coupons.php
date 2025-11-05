<?php
/**
 * Coupons' ajax
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action( 'wp_ajax_snks_create_coupon', 'snks_create_coupon_ajax_handler' );

/**
 * Handle Ajax coupon creation request.
 *
 * @return void
 */
function snks_create_coupon_ajax_handler() {
	check_ajax_referer( 'snks_coupon_nonce', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول.' ) );
	}

	$current_user = get_current_user_id();

	$args = array(
		'code'           => sanitize_text_field( $_POST['code'] ?? '' ),
		'discount_type'  => sanitize_text_field( $_POST['discount_type'] ?? 'fixed' ),
		'discount_value' => floatval( $_POST['discount_value'] ?? 0 ),
		'expires_at'     => ! empty( $_POST['expires_at'] ) ? date( 'Y-m-d 00:00:00', strtotime( $_POST['expires_at'] ) ) : null,
		'usage_limit'    => ! empty( $_POST['usage_limit'] ) ? intval( $_POST['usage_limit'] ) : null,
		'doctor_id'      => $current_user,
		// Respect explicit 0/1 sent from client; default to 0 when absent
		'is_ai_coupon'   => array_key_exists( 'is_ai_coupon', $_POST ) ? intval( $_POST['is_ai_coupon'] ) : 0,
	);

	if ( empty( $args['code'] ) || 0 >= $args['discount_value'] ) {
		wp_send_json_error( array( 'message' => 'يرجى ملء الحقول المطلوبة.' ) );
	}

	$exists = snks_get_coupon_by_code( $args['code'] );
	if ( null !== $exists ) {
		wp_send_json_error( array( 'message' => 'الكود مستخدم من قبل.' ) );
	}

	$inserted = snks_insert_coupon( $args );

	if ( false === $inserted ) {
		wp_send_json_error( array( 'message' => 'حدث خطأ أثناء إنشاء الكوبون.' ) );
	}

	wp_send_json_success(
		array(
			'message' => 'تم إنشاء الكوبون بنجاح.',
			'coupon'  => array(
				'id'             => $inserted,
				'code'           => $args['code'],
				'discount_type'  => $args['discount_type'],
				'discount_value' => $args['discount_value'],
				'expires_at'     => $args['expires_at'],
				'usage_limit'    => $args['usage_limit'],
				'is_ai_coupon'   => isset( $args['is_ai_coupon'] ) ? (int) $args['is_ai_coupon'] : 0,
			),
		)
	);
}

add_action( 'wp_ajax_snks_generate_coupon_code', 'snks_generate_coupon_code_ajax' );

/**
 * Generate a unique coupon code via Ajax.
 *
 * @return void
 */
function snks_generate_coupon_code_ajax() {
	check_ajax_referer( 'snks_generate_code_nonce', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول.' ) );
	}

	$code      = '';
	$tries     = 0;
	$max_tries = 10;

	do {
		$code   = 'DR-' . strtoupper( wp_generate_password( 5, false, false ) );
		$exists = snks_get_coupon_by_code( $code );
		++$tries;
	} while ( null !== $exists && $tries < $max_tries );

	if ( null !== $exists ) {
		wp_send_json_error( array( 'message' => 'تعذر توليد كود فريد. حاول مجددًا.' ) );
	}

	wp_send_json_success( array( 'code' => $code ) );
}

add_action( 'wp_ajax_snks_delete_coupon', 'snks_delete_coupon_ajax_handler' );

/**
 * Handle Ajax coupon deletion request.
 *
 * @return void
 */
function snks_delete_coupon_ajax_handler() {
	check_ajax_referer( 'snks_coupon_delete', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'أنت غير مسجل دخول.' ) );
	}

	$coupon_id    = intval( $_POST['coupon_id'] ?? 0 );
	$current_user = get_current_user_id();

	$coupon = snks_get_coupon_by_code_id( $coupon_id );

	if ( null === $coupon || (int) $coupon->doctor_id !== (int) $current_user ) {
		wp_send_json_error( array( 'message' => 'ليس لديك صلاحية حذف هذا الكوبون.' ) );
	}

	if ( false === snks_delete_coupon( $coupon_id ) ) {
		wp_send_json_error( array( 'message' => 'حدث خطأ أثناء الحذف.' ) );
	}

	wp_send_json_success( array( 'message' => 'تم حذف الكوبون بنجاح.' ) );
}

/**
 * Handle Ajax coupon application and recalculate session data.
 *
 * @return void
 */
add_action( 'wp_ajax_snks_apply_coupon', 'snks_apply_coupon_ajax_handler' );
add_action( 'wp_ajax_nopriv_snks_apply_coupon', 'snks_apply_coupon_ajax_handler' );

/**
 * Handle Ajax coupon application and update session data.
 *
 * @return void
 */
function snks_apply_coupon_ajax_handler() {
	check_ajax_referer( 'snks_coupon_nonce', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول لتفعيل الكوبون.' ) );
	}

	$code      = sanitize_text_field( $_POST['code'] ?? '' );
	$form_data = get_transient( snks_form_data_transient_key() );

	if ( ! $form_data || empty( $form_data['_main_price'] ) ) {
		wp_send_json_error( array( 'message' => 'لم يتم العثور على بيانات الحجز. حاول من جديد.' ) );
	}
	if ( ! empty( $form_data['_coupon_code'] ) ) {
		wp_send_json_error( array( 'message' => 'عفوا! هناك كوبون مستخدم بالفعل' ) );
	}
	$coupon = snks_get_coupon_by_code( $code );
	if ( null === $coupon ) {
		wp_send_json_error( array( 'message' => 'كوبون غير صالح!' ) );
	}
	$doctor_id = $coupon->doctor_id;
	if ( $doctor_id !== $form_data['_user_id'] ) {
		wp_send_json_error( array( 'message' => 'كوبون غير صالح' ) );
	}

	// Enforce AI-only vs general coupon usage based on current booking context
	$is_ai_context = ! empty( $form_data['_is_ai_booking'] ) || ! empty( $form_data['_from_jalsah_ai'] );
	$is_ai_coupon  = ! empty( $coupon->is_ai_coupon );
	if ( $is_ai_context && ! $is_ai_coupon ) {
		wp_send_json_error( array( 'message' => 'هذا الكوبون غير مخصص لجلسات الذكاء الاصطناعي.' ) );
	}
	if ( ! $is_ai_context && $is_ai_coupon ) {
		wp_send_json_error( array( 'message' => 'هذا الكوبون مخصص لجلسات الذكاء الاصطناعي فقط.' ) );
	}

	$user_id      = get_current_user_id();
	$timetable_id = absint( $form_data['booking_id'] ?? 0 );

	// تطبيق الكوبون.
	$result = snks_apply_coupon_to_amount( $code, $form_data['_main_price'] );

	if ( false === $result['valid'] ) {
		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	$coupon = $result['coupon'];

	// منع إعادة استخدام الكوبون على نفس الجلسة.
	if ( snks_user_has_used_coupon_on_timetable( $coupon->id, $user_id, $timetable_id ) ) {
		wp_send_json_error( array( 'message' => 'لا يمكنك استخدام هذا الكوبون مرة أخرى على نفس الجلسة.' ) );
	}

	// إعادة حساب السعر بناءً على الخصم.
	$new_main_price = $result['final'];
	$recalculated   = snks_session_total_price( $new_main_price, $form_data['attendance_type'] ?? 'online' );

	// تحديث بيانات الجلسة.
	$form_data['_main_price']        = $new_main_price;
	$form_data['_total_price']       = $recalculated['total_price'];
	$form_data['_jalsah_commistion'] = $recalculated['service_fees'];
	$form_data['_paymob']            = $recalculated['paymob'];
	$form_data['_coupon_code']       = $code;
	$form_data['_coupon_id']         = $coupon->id;

	set_transient( snks_form_data_transient_key(), $form_data, 3600 );

	wp_send_json_success(
		array(
			'message'     => 'تم تطبيق الكوبون بنجاح.',
			'final_price' => $new_main_price,
		)
	);
}

add_action( 'wp_ajax_snks_remove_coupon', 'snks_remove_coupon_ajax_handler' );

/**
 * Ajax: Remove coupon from transient session.
 *
 * @return void
 */
function snks_remove_coupon_ajax_handler() {
	check_ajax_referer( 'snks_coupon_nonce', 'security' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول.' ) );
	}

	$form_data = get_transient( snks_form_data_transient_key() );

	if ( ! $form_data || empty( $form_data['_coupon_code'] ) ) {
		wp_send_json_error( array( 'message' => 'لا يوجد كوبون مفعل.' ) );
	}

	// إزالة بيانات الكوبون فقط من الجلسة.
	unset( $form_data['_coupon_code'], $form_data['_coupon_id'] );

	// إعادة السعر الأساسي.
	$price = snks_calculated_price(
		absint( $form_data['_user_id'] ),
		snsk_ip_api_country(),
		$form_data['_period'],
		$form_data['attendance_type'] ?? 'online'
	);

	$pricing_data = snks_session_total_price( $price, $form_data['attendance_type'] ?? 'online' );

	$form_data['_main_price']        = $price;
	$form_data['_total_price']       = $pricing_data['total_price'];
	$form_data['_jalsah_commistion'] = $pricing_data['service_fees'];
	$form_data['_paymob']            = $pricing_data['paymob'];

	set_transient( snks_form_data_transient_key(), $form_data, 3600 );

	wp_send_json_success( array( 'message' => 'تمت إزالة الكوبون وإعادة السعر الأصلي.' ) );
}

/**
 * Ajax: Apply coupon for AI cart context without relying on session transient.
 * Expects: code, amount, security (nonce for 'snks_coupon_nonce').
 * Returns: success, final_price, discount.
 */
add_action( 'wp_ajax_snks_apply_ai_coupon', 'snks_apply_ai_coupon_ajax_handler' );
add_action( 'wp_ajax_nopriv_snks_apply_ai_coupon', 'snks_apply_ai_coupon_ajax_handler' );

function snks_apply_ai_coupon_ajax_handler() {
    // Debug: log incoming request (safe/gated)
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AI Coupon] === Handler start ===' );
        error_log( '[AI Coupon] Raw POST: ' . json_encode( array(
            'code' => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null,
            'amount' => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : null,
            'security_len' => isset( $_POST['security'] ) ? strlen( sanitize_text_field( wp_unslash( $_POST['security'] ) ) ) : 0,
        ) ) );
    }

    // Preflight nonce check to allow logging (instead of immediate die)
    $raw_nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
    if ( ! wp_verify_nonce( $raw_nonce, 'snks_coupon_nonce' ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[AI Coupon] Nonce invalid or expired' );
        }
        wp_send_json_error( array( 'message' => 'انتهت صلاحية الجلسة. حدِّث الصفحة وحاول مرة أخرى.' ) );
    }
    // Secondary WordPress nonce enforcement
    check_ajax_referer( 'snks_coupon_nonce', 'security' );

    if ( ! is_user_logged_in() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[AI Coupon] Rejected: not logged in' );
        }
        wp_send_json_error( array( 'message' => 'يجب تسجيل الدخول لتفعيل الكوبون.' ) );
    }

    $code   = sanitize_text_field( $_POST['code'] ?? '' );
    $amount = floatval( $_POST['amount'] ?? 0 );

    if ( '' === $code || $amount <= 0 ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[AI Coupon] Invalid input: code or amount. code=' . $code . ' amount=' . $amount );
        }
        wp_send_json_error( array( 'message' => 'بيانات غير صالحة لتطبيق الكوبون.' ) );
    }

    // Validate coupon and compute discount against provided amount
    $result = snks_apply_coupon_to_amount( $code, $amount );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AI Coupon] Apply result: ' . json_encode( array(
            'valid' => isset( $result['valid'] ) ? (bool) $result['valid'] : null,
            'message' => isset( $result['message'] ) ? $result['message'] : null,
            'final' => isset( $result['final'] ) ? $result['final'] : null,
            'discount' => isset( $result['discount'] ) ? $result['discount'] : null,
        ) ) );
    }

    if ( false === $result['valid'] ) {
        wp_send_json_error( array( 'message' => $result['message'] ) );
    }

    // Enforce AI-only coupon usage for this AI cart endpoint
    $coupon      = $result['coupon'];
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AI Coupon] Coupon fetched: ' . json_encode( array(
            'code' => isset( $coupon->code ) ? $coupon->code : null,
            'is_ai_coupon' => isset( $coupon->is_ai_coupon ) ? (int) $coupon->is_ai_coupon : null,
            'discount_type' => isset( $coupon->discount_type ) ? $coupon->discount_type : null,
            'discount_value' => isset( $coupon->discount_value ) ? $coupon->discount_value : null,
        ) ) );
    }
    $is_ai_coupon = ! empty( $coupon->is_ai_coupon );
    if ( ! $is_ai_coupon ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[AI Coupon] Rejected: coupon is not AI-only' );
        }
        wp_send_json_error( array( 'message' => 'هذا الكوبون غير مخصص لجلسات الذكاء الاصطناعي.' ) );
    }

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AI Coupon] Success: final=' . $result['final'] . ' discount=' . $result['discount'] );
    }
    wp_send_json_success(
        array(
            'message'     => 'تم تطبيق الكوبون بنجاح.',
            'final_price' => $result['final'],
            'discount'    => $result['discount'],
            'coupon_type' => 'AI',
        )
    );
}
