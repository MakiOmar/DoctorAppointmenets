<?php
/**
 * Coupons
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Insert a new coupon.
 *
 * @param array $args {
 *     @type string  $code           Coupon code.
 *     @type string  $discount_type  Type of discount (fixed|percent).
 *     @type float   $discount_value Value of the discount.
 *     @type string  $expires_at     Expiry datetime (Y-m-d H:i:s).
 *     @type int     $usage_limit    Maximum number of uses.
 *     @type int     $doctor_id      ID of the doctor who created the coupon.
 *     @type int     $is_ai_coupon   Whether this coupon is for AI sessions only (0|1).
 * }
 * @return int|false Inserted coupon ID on success, false on failure.
 */
function snks_insert_coupon( $args ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupons';

	$inserted = $wpdb->insert(
		$table,
		array(
			'code'           => sanitize_text_field( $args['code'] ),
			'discount_type'  => $args['discount_type'],
			'discount_value' => $args['discount_value'],
			'expires_at'     => $args['expires_at'],
			'usage_limit'    => $args['usage_limit'],
			'doctor_id'      => $args['doctor_id'],
			'is_ai_coupon'   => isset( $args['is_ai_coupon'] ) ? intval( $args['is_ai_coupon'] ) : 0,
		),
		array( '%s', '%s', '%f', '%s', '%d', '%d', '%d' )
	);
	return $inserted ? $wpdb->insert_id : false;
}




/**
 * Retrieve a coupon by code.
 *
 * @param string $code Coupon code.
 * @return object|null Coupon object if found, null otherwise.
 */
function snks_get_coupon_by_code( $code ) {
	$code = sanitize_text_field( trim( $code ) );
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupons';
	return $wpdb->get_row(
		"SELECT * FROM $table WHERE code = '$code'",
	);
}

/**
 * Get coupon by ID.
 *
 * @param int $coupon_id Coupon ID.
 * @return object|null Coupon object or null.
 */
function snks_get_coupon_by_code_id( $coupon_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'snks_custom_coupons';

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d",
			$coupon_id
		)
	);
}


/**
 * Retrieve all coupons created by a specific doctor.
 *
 * @param int $doctor_id ID of the doctor.
 * @return array List of coupon objects.
 */
function snks_get_coupons_by_doctor( $doctor_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupons';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE doctor_id = %d ORDER BY expires_at DESC",
			$doctor_id
		)
	);
}


/**
 * Validate a coupon.
 *
 * @param string $code Coupon code.
 * @return object|false Coupon object if valid, false if invalid.
 */
function snks_is_coupon_valid( $code ) {
	$coupon = snks_get_coupon_by_code( $code );

	if ( null === $coupon ) {
		return false;
	}

	$now = current_time( 'mysql' );

	if ( ! empty( $coupon->expires_at ) && $coupon->expires_at < $now ) {
		return false;
	}

	$usage_count = snks_get_coupon_usage_count( $coupon->id );

	if ( ! empty( $coupon->usage_limit ) && $usage_count >= $coupon->usage_limit ) {
		return false;
	}

	return $coupon;
}


/**
 * Log the usage of a coupon in the tracking table.
 *
 * @param int      $coupon_id    The ID of the coupon used.
 * @param int      $user_id      The ID of the user who used the coupon.
 * @param int      $timetable_id The ID of the timetable/session the coupon was used for.
 * @param int|null $order_id     The ID of the WooCommerce order (optional).
 *
 * @return bool True on successful insert, false on failure.
 */
function snks_log_coupon_usage( $coupon_id, $user_id, $timetable_id, $order_id = null ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'snks_custom_coupon_usages';

	$inserted = $wpdb->insert(
		$table_name,
		array(
			'coupon_id'    => absint( $coupon_id ),
			'user_id'      => absint( $user_id ),
			'timetable_id' => absint( $timetable_id ),
			'order_id'     => ( null !== $order_id ) ? absint( $order_id ) : null,
		),
		array( '%d', '%d', '%d', '%d' )
	);

	return ( false !== $inserted );
}



/**
 * Get how many times a coupon has been used.
 *
 * @param int $coupon_id ID of the coupon.
 * @return int Number of usages.
 */
function snks_get_coupon_usage_count( $coupon_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupon_usages';

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE coupon_id = %d",
			$coupon_id
		)
	);
}


/**
 * Check if a specific user has used a specific coupon before.
 *
 * @param int $coupon_id    ID of the coupon.
 * @param int $user_id      ID of the user.
 * @return bool True if user has used the coupon, false otherwise.
 */
function snks_user_has_used_coupon( $coupon_id, $user_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupon_usages';

	$usage_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE coupon_id = %d AND user_id = %d",
			$coupon_id,
			$user_id
		)
	);

	return ( 0 < $usage_count );
}


/**
 * Delete a coupon.
 *
 * @param int $coupon_id ID of the coupon.
 * @return bool True on success, false on failure.
 */
function snks_delete_coupon( $coupon_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupons';

	return ( false !== $wpdb->delete( $table, array( 'id' => $coupon_id ), array( '%d' ) ) );
}


/**
 * Retrieve all usage records for a specific coupon.
 *
 * @param int $coupon_id ID of the coupon.
 * @return array Array of usage entries.
 */
function snks_get_coupon_usages( $coupon_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupon_usages';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE coupon_id = %d ORDER BY used_at DESC",
			$coupon_id
		)
	);
}

/**
 * Apply a valid coupon to a given amount.
 *
 * @param string $code   Coupon code.
 * @param float  $amount Original amount before discount.
 * @return array {
 *     @type bool   $valid     Whether the coupon is valid.
 *     @type float  $final     Final amount after discount.
 *     @type float  $discount  Discount value applied.
 *     @type object $coupon    Coupon object (if valid).
 *     @type string $message   Message for user interface (if invalid).
 * }
 */
function snks_apply_coupon_to_amount( $code, $amount ) {
	$amount = floatval( $amount );
	$coupon = snks_is_coupon_valid( $code );

	if ( false === $coupon ) {
		return array(
			'valid'    => false,
			'final'    => $amount,
			'discount' => 0,
			'coupon'   => null,
			'message'  => 'الكوبون غير صالح أو انتهى.',
		);
	}

	if ( 'fixed' === $coupon->discount_type ) {
		$discount = floatval( $coupon->discount_value );
	} elseif ( 'percent' === $coupon->discount_type ) {
		$discount = ( $amount * floatval( $coupon->discount_value ) ) / 100;
	} else {
		return array(
			'valid'    => false,
			'final'    => $amount,
			'discount' => 0,
			'coupon'   => $coupon,
			'message'  => 'نوع الخصم غير معروف.',
		);
	}

	$discount = min( $discount, $amount );
	$final    = $amount - $discount;

	return array(
		'valid'    => true,
		'final'    => round( $final, 2 ),
		'discount' => round( $discount, 2 ),
		'coupon'   => $coupon,
		'message'  => 'تم تطبيق الكوبون بنجاح.',
	);
}

/**
 * Check if the user has already used the coupon on the same session.
 *
 * @param int $coupon_id     Coupon ID.
 * @param int $user_id       User ID.
 * @param int $timetable_id  Timetable ID.
 * @return bool True if already used, false otherwise.
 */
function snks_user_has_used_coupon_on_timetable( $coupon_id, $user_id, $timetable_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'snks_custom_coupon_usages';

	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE coupon_id = %d AND user_id = %d AND timetable_id = %d",
			$coupon_id,
			$user_id,
			$timetable_id
		)
	);

	return ( 0 < $count );
}
