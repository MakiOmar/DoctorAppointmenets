<?php
/**
 * Consulting checkout
 *
 * @package Uturn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! function_exists( 'WC' ) ) {
	return;
}

/**
 * Save form data to session
 *
 * @return void
 */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_REQUEST['direct_add_to_cart'] ) || ! empty( $_POST['edit-booking-id'] ) ) {
			return;
		}
		if ( isset( $_POST ) && isset( $_POST['create_appointment_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['create_appointment_nonce'] ) ), 'create_appointment' ) && isset( $_POST['create-appointment'] ) ) {
			return;
		}
		/*var_dump('asss');
		die;*/
		$_req      = wp_unslash( $_POST );
		$timetable = snks_get_timetable_by( 'ID', absint( sanitize_text_field( $_req['selected-hour'] ) ) );
		if ( ! $timetable || empty( $timetable ) ) {
			return;
		}
		

		$user_id = $timetable->user_id;

		if ( absint( $user_id ) !== absint( $_req['user-id'] ) ) {
			WC()->cart->empty_cart();
			// Redirects to the checkout page.
			wp_safe_redirect( site_url( $_req['_wp_http_referer'] ) );
			// Safely closes the function.
			exit();
		}
		$form_data = array(
			'booking_day'  => sanitize_text_field( $_req['current-month-day'] ),
			'booking_hour' => snks_localize_time(
				sprintf(
					'من %s إلى %s',
					esc_html( gmdate( 'h:i a', strtotime( $timetable->starts ) ) ),
					esc_html( gmdate( 'h:i a', strtotime( $timetable->ends ) ) ),
				)
			),
			'booking_id'   => sanitize_text_field( $_req['selected-hour'] ),
			'user_id'      => sanitize_text_field( $_req['user-id'] ),
			'period'       => sanitize_text_field( $_req['period'] ),
		);
		//phpcs:enable.
		WC()->session->set( 'consulting_form_data', $form_data );
		WC()->cart->empty_cart();
		$product_id = 335;

		// This adds the product with the ID; we can also add a second variable which will be the variation ID.
		WC()->cart->add_to_cart( $product_id );

		// Redirects to the checkout page.
		wp_safe_redirect( wc_get_checkout_url() );

		// Safely closes the function.
		exit();
	},
	1
);
/**
 * Add data to cart item
 *
 * @param array $cart_item_data Cart's data.
 * @return mixed
 */
add_filter(
	'woocommerce_add_cart_item_data',
	function ( $cart_item_data ) {
		global $woocommerce;
		if ( ! $woocommerce ) {
			return $cart_item_data;
		}
		$session = $woocommerce->session->get( 'consulting_form_data' );

		if ( ! $session || ! isset( $session['booking_day'] ) ) {
			return $cart_item_data;
		}

		$new_value = array();

		$custom_options = array();

		if ( ! empty( $session ) ) {

			foreach ( $session as $session_key => $args ) {

				$custom_options[ $session_key ] = $args;
			}
		}

		$new_value['_custom_options'] = $custom_options;

		if ( empty( $cart_item_data ) ) {

			$v = $new_value;

		} else {

			$v = array_merge( $cart_item_data, $new_value );
		}

		return $v;
	},
	1
);
// Consulting price.
add_action(
	'woocommerce_before_calculate_totals',
	function ( $cart_object ) {
		global $woocommerce;
		if ( ! $woocommerce ) {
			return;
		}
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$session = $woocommerce->session->get( 'consulting_form_data' );
		if ( ! $session || ! isset( $session['booking_day'] ) ) {
			return;
		}
		$country          = 'EG';
		$user_id          = $session['user_id'];
		$period           = $session['period'];
		$has_discount     = snks_discount_eligible( $user_id );
		$pricings         = snks_doctor_pricings( $user_id );
		$price            = get_price_by_period_and_country( $period, $country, $pricings );
		$discount_percent = get_user_meta( $user_id, 'discount_percent', true );
		if ( $has_discount ) {
			$price = $price - ( $price * ( absint( $discount_percent ) / 100 ) );
		}
		//phpcs:enable
		foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {
			$custom_price = $price; // Set price.
			$value['data']->set_price( $custom_price );
		}
		return $cart_object;
	},
	10
);


/**
 * Function for `woocommerce_get_item_data` filter-hook.
 *
 * @param array $item_data Cart item data. Empty by default.
 * @return array
 */
add_filter(
	'woocommerce_get_item_data',
	function ( $item_data ) {

		global $woocommerce;
		if ( ! $woocommerce ) {
			return $item_data;
		}
		$session = $woocommerce->session->get( 'consulting_form_data' );

		if ( ! $session || ! isset( $session['booking_day'] ) ) {
			return $item_data;
		}
		$item_data[] = array(
			'key'     => 'اليوم',
			'display' => $session['booking_day'],

		);
		$item_data[] = array(
			'key'     => 'الساعة',
			'display' => $session['booking_hour'],

		);
		$item_data[] = array(
			'key'     => 'رقم الحجز',
			'display' => '#' . $session['booking_id'],

		);

		return $item_data;
	}
);


/**
 * Sets order's item meta.
 *
 * @param object $item Item object.
 * @return void
 */
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item ) {
		$session = WC()->session->get( 'consulting_form_data' );

		if ( ! $session || empty( $session ) || ! isset( $session['booking_day'] ) ) {
			return;
		}

		foreach ( $session as $session_key => $value ) {
			$item->update_meta_data( $session_key, $value );
		}
	}
);

/**
 * Set order meta values.
 *
 * @param int $order_id Order's ID.
 * @return void
 */
add_action(
	'woocommerce_new_order',
	function ( $order_id ) {
		$form_data = WC()->session->get( 'consulting_form_data' );
		if ( ! $form_data || empty( $form_data ) || ! isset( $form_data['booking_day'] ) ) {
			return;
		}
		if ( $form_data && is_array( $form_data ) ) {
			foreach ( $form_data as $key => $value ) {
				update_post_meta( $order_id, $key, $value );
			}
			WC()->session->set( 'consulting_form_data', null );
		}
	},
	10,
	1
);

/**
 * Order meta key display.
 *
 * @param string $display_key Display text.
 * @param object $meta Meta object.
 * @return string
 */
add_filter(
	'woocommerce_order_item_display_meta_key',
	function ( $display_key, $meta ) {
		if ( 'booking_day' === $meta->key ) {
			return 'اليوم ';
		}

		if ( 'booking_hour' === $meta->key ) {
			return 'الساعة ';
		}

		if ( 'booking_id' === $meta->key ) {
			return 'رقم الحجز ';
		}
		return $display_key;
	},
	99,
	2
);

add_action(
	'woocommerce_thankyou',
	function ( $order_id ) {
		$booking_day = get_post_meta( $order_id, 'booking_id', true );
		snks_update_timetable(
			absint( $booking_day ),
			array(
				'order_id' => $order_id,
			)
		);
	}
);
