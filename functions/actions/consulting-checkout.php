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
 * Save form data to session or redirect non-logged-in users to login.
 *
 * @return void
 */
add_action(
	'template_redirect',
	function () {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// Start session if not already started.
		// Check for the necessary request parameters.
		if ( ! isset( $_REQUEST['appointment_add_to_cart'] ) || ! empty( $_POST['edit-booking-id'] ) ) {
			return;
		}
		// Verify nonce and handle form submission.
		if ( isset( $_POST ) && isset( $_POST['create_appointment_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['create_appointment_nonce'] ) ), 'create_appointment' ) && isset( $_POST['create-appointment'] ) ) {
			return;
		}
		$_req       = wp_unslash( $_POST ); // Unslashing input data.
		$doctor_url = snks_encrypted_doctor_url( sanitize_text_field( $_req['user-id'] ) );

		// Check terms and conditions.
		if ( empty( $_req['terms-conditions'] ) || 'yes' !== $_req['terms-conditions'] ) {
			wp_safe_redirect( add_query_arg( 'error', 'accept-terms', $doctor_url ) );
			exit; // Safely exit to prevent further execution.
		}

		$timetable = snks_get_timetable_by( 'ID', absint( sanitize_text_field( $_req['selected-hour'] ) ) );
		if ( ! $timetable || empty( $timetable ) ) {
			return;
		}

		$user_id = $timetable->user_id;

		// Validate user ID against timetable data.
		if ( absint( $user_id ) !== absint( $_req['user-id'] ) ) {
			WC()->cart->empty_cart();
			wp_safe_redirect( site_url( $doctor_url ) );
			exit;
		}
		$country      = snsk_ip_api_country();
		$price        = snks_calculated_price( $user_id, $country, sanitize_text_field( $_req['period'] ) );
		$pricing_data = snks_session_total_price( $price, $timetable->attendance_type );
		$total_price  = $pricing_data['total_price'];
		// Prepare form data to store in session.
		$form_data = array(
			'booking_day'        => sanitize_text_field( $_req['current-month-day'] ),
			'booking_hour'       => snks_localize_time(
				sprintf(
					/* translators: 1: start time, 2: end time */
					esc_html__( 'من %1$s إلى %2$s', 'text-domain' ),
					esc_html( gmdate( 'h:i a', strtotime( $timetable->starts ) ) ),
					esc_html( gmdate( 'h:i a', strtotime( $timetable->ends ) ) )
				)
			),
			'booking_id'         => sanitize_text_field( $_req['selected-hour'] ),
			'_user_id'           => sanitize_text_field( $_req['user-id'] ),
			'_period'            => sanitize_text_field( $_req['period'] ),
			'_main_price'        => $price,
			'_total_price'       => $total_price,
			'_jalsah_commistion' => $pricing_data['service_fees'],
			'_paymob'            => $pricing_data['paymob'],
		);

		set_transient( snks_form_data_transient_key(), $form_data, 3600 );
		// Check if the user is logged in; otherwise, redirect to login.
		if ( is_user_logged_in() ) {
			// Process form data for logged-in users.
			process_form_data( $form_data );
		} else {
			// Redirect to login page with a redirect back to the checkout page.
			wp_safe_redirect( site_url( 'booking-details' ) );
			exit;
		}
	},
	1
);

/**
 * Process the form data to add to cart and redirect to checkout.
 *
 * @param array $form_data The form data array.
 */
function process_form_data( $form_data ) {
	if ( $form_data && ! empty( $form_data ) ) {
		// Empty the cart before adding new items.
		WC()->cart->empty_cart();
		$product_id = 335;

		// Add the product to the cart.
		WC()->cart->add_to_cart( $product_id );

		// Redirect to the checkout page.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
}
/**
 * Handle post-login redirection to checkout and process stored form data.
 */
function snks_logged_in_proccess_form_data() {
	if ( snks_is_patient() ) {
		$form_data = get_transient( snks_form_data_transient_key() );
		// Process the stored form data after login.
		process_form_data( $form_data );
	}
}

add_action( 'wp_login', 'snks_logged_in_proccess_form_data' );

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
		$session = get_transient( snks_form_data_transient_key() );

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
		$session = get_transient( snks_form_data_transient_key() );

		if ( ! $session || ! isset( $session['booking_day'] ) ) {
			return;
		}

		//phpcs:enable
		foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {
			$value['data']->set_price( $session['_total_price'] );
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
		$session = get_transient( snks_form_data_transient_key() );

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
		$session = get_transient( snks_form_data_transient_key() );

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
		$form_data = get_transient( snks_form_data_transient_key() );
		if ( ! $form_data || empty( $form_data ) || ! isset( $form_data['booking_day'] ) ) {
			return;
		}
		if ( $form_data && is_array( $form_data ) ) {
			foreach ( $form_data as $key => $value ) {
				update_post_meta( $order_id, $key, $value );
			}
			delete_transient( snks_form_data_transient_key() );
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
		$timetable = snks_get_timetable_by( 'ID', absint( $booking_day ) );
		if ( $timetable ) {
			snks_close_others( $timetable );
		}
	}
);
/**
 * Preset the default country to Egypt (EG) in WooCommerce checkout.
 *
 * @param array $fields Checkout fields.
 * @return array Modified checkout fields with default country set to Egypt.
 */
function set_woocommerce_default_country( $fields ) {
	// Set the default billing country to Egypt.
	if ( isset( $fields['billing']['billing_country'] ) ) {
		$fields['billing']['billing_country']['default'] = snsk_ip_api_country( false );
	}

	// Set the default shipping country to Egypt, if shipping fields are enabled.
	if ( isset( $fields['shipping']['shipping_country'] ) ) {
		$fields['shipping']['shipping_country']['default'] = snsk_ip_api_country( false );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'set_woocommerce_default_country', 99 );

/**
 * Pre-populate WooCommerce checkout fields with current user data.
 *
 * @param mixed  $input The field value.
 * @param string $key   The field key.
 * @return mixed The pre-populated field value.
 */
add_filter(
	'woocommerce_checkout_get_value',
	function ( $input, $key ) {

		// Check which field is being populated and set the value accordingly.
		switch ( $key ) {

			case 'billing_country':
				return snsk_ip_api_country( false );
			default:
				return $input;
		}
	},
	10,
	2
);
