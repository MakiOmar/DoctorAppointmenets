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
		'is_ai_coupon'   => isset( $_POST['is_ai_coupon'] ) ? 1 : 0,
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
