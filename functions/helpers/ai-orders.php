<?php
/**
 * AI Orders Helper Class
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * AI Orders Helper Class
 */
class SNKS_AI_Orders {
	
	/**
	 * Create WooCommerce order from existing cart
	 */
    public static function create_order_from_existing_cart( $user_id, $cart_items, $coupon = array() ) {
		if ( empty( $cart_items ) ) {
			throw new Exception( 'No appointments in cart' );
		}
		
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			throw new Exception( 'WooCommerce is not active' );
		}
		
		// Get user data
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			throw new Exception( 'User not found' );
		}
		
		// Create WooCommerce order
		$order = wc_create_order();
		
		// Add appointments as order items
		foreach ( $cart_items as $cart_item ) {
			$product_id = SNKS_AI_Products::get_ai_session_product_id();
			
			if ( ! $product_id ) {
				throw new Exception( 'Failed to get AI session product' );
			}
			
			$session_price = $cart_item['price'] ?? SNKS_AI_Products::get_default_session_price();
			
			// Create a custom order item with the correct price
			$item = new WC_Order_Item_Product();
			$item->set_props( [
				'name' => sprintf(
					'جلسة علاج نفسي - %s - %s %s',
					get_the_title( $cart_item['user_id'] ),
					$cart_item['date_time'],
					$cart_item['starts']
				),
				'quantity' => 1,
				'product_id' => $product_id
			] );
			
			// Set the price directly on the item
			$item->set_total( $session_price );
			$item->set_subtotal( $session_price );
			
			// Add appointment metadata
			$item->add_meta_data( 'therapist_id', $cart_item['user_id'] );
			$item->add_meta_data( 'session_date', $cart_item['date_time'] );
			$item->add_meta_data( 'session_time', $cart_item['starts'] );
			$item->add_meta_data( 'session_duration', SNKS_AI_Products::get_session_duration() );
			$item->add_meta_data( 'is_ai_session', true );
			$item->add_meta_data( 'slot_id', $cart_item['ID'] );
			$item->add_meta_data( '_line_total', $session_price );
			$item->add_meta_data( '_line_subtotal', $session_price );
			
			$order->add_item( $item );
		}
		
        // Apply AI coupon discount if provided
        if ( ! empty( $coupon ) ) {
            $code     = isset( $coupon['code'] ) ? sanitize_text_field( $coupon['code'] ) : '';
            $discount = isset( $coupon['discount'] ) ? floatval( $coupon['discount'] ) : 0;
            if ( $discount > 0 ) {
                // Use a negative fee to represent discount in WooCommerce
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( $code ? sprintf( 'خصم كوبون (%s)', $code ) : 'خصم كوبون' );
                $fee->set_amount( -1 * $discount );
                $fee->set_total( -1 * $discount );
                $order->add_item( $fee );
                // Store coupon meta
                if ( $code ) {
                    $order->update_meta_data( 'ai_coupon_code', $code );
                }
                $order->update_meta_data( 'ai_coupon_discount', $discount );
            }
        }

        // Recalculate order totals
        $order->calculate_totals();
		
		// Set customer data
		$order->set_billing_email( $user->user_email );
		$order->set_billing_first_name( $user->display_name );
		$order->set_billing_phone( get_user_meta( $user_id, 'phone', true ) );
		$order->set_customer_id( $user_id );
		
		// Add AI-specific metadata
		$order->update_meta_data( 'from_jalsah_ai', true );
		$order->update_meta_data( 'ai_user_id', $user_id );
		$order->update_meta_data( 'ai_appointments_count', count( $cart_items ) );
		$order->update_meta_data( 'ai_total_amount', $order->get_total() );
		
		// Store form data for AI pricing table
        $form_data = [
			'_is_ai_booking' => true,
            '_total_price' => $order->get_total(),
			'_session_date' => $cart_items[0]['date_time'] ?? '',
			'_session_time' => $cart_items[0]['starts'] ?? '',
			'_session_duration' => SNKS_AI_Products::get_session_duration(),
            '_coupon_code' => isset( $coupon['code'] ) ? sanitize_text_field( $coupon['code'] ) : ''
		];
		
		// Store form data in transient for pricing table
		set_transient( 'snks_ai_form_data_' . $order->get_id(), $form_data, 3600 ); // 1 hour expiry
		
		// Set payment method (will be selected at checkout)
		$order->set_payment_method( '' ); // Let user select at checkout
		$order->set_payment_method_title( '' );
		
		$order->save();
		
		return $order;
	}
	
	/**
	 * Process AI order payment
	 */
	public static function process_ai_order_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		
		if ( ! $order ) {
			return false;
		}
		
		$is_ai_order = $order->get_meta( 'from_jalsah_ai' );
		
		if ( $is_ai_order !== 'true' && $is_ai_order !== true && $is_ai_order !== '1' && $is_ai_order !== 1 ) {
			return false;
		}
		

		
		// Create appointments from order items
		foreach ( $order->get_items() as $item ) {
			$therapist_id = $item->get_meta( 'therapist_id' );
			$session_date = $item->get_meta( 'session_date' );
			$session_time = $item->get_meta( 'session_time' );
			$slot_id = $item->get_meta( 'slot_id' );
			
			if ( $therapist_id && $session_date && $session_time ) {
				// Update existing slot to booked status
				self::book_appointment_slot( $slot_id, $order_id, $order->get_customer_id() );
				
				// Link appointment to order item
				$item->add_meta_data( 'appointment_id', $slot_id );
				$item->save();
			}
		}
		
		// Clear user's AI cart
		delete_user_meta( $order->get_customer_id(), 'ai_cart' );
		
		// Send notifications
		self::send_ai_order_notifications( $order_id );
		
		return true;
	}
	
	/**
	 * Book appointment slot
	 */
	private static function book_appointment_slot( $slot_id, $order_id, $patient_id ) {
		global $wpdb;
		
		// Get slot details before updating
		$slot = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}snks_provider_timetable WHERE ID = %d",
			$slot_id
		) );
		
		if ( ! $slot ) {
			return false;
		}
		
		$result = $wpdb->update(
			$wpdb->prefix . 'snks_provider_timetable',
			[
				'session_status' => 'open',
				'order_id' => $order_id,
				'settings' => 'ai_booking:completed'
			],
			[ 'ID' => $slot_id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);
		
		if ( $result ) {
			// Trigger appointment creation hook
			$appointment_data = array(
				'is_ai_session' => true,
				'order_id' => $order_id,
				'therapist_id' => $slot->user_id,
				'patient_id' => $patient_id,
				'slot_id' => $slot_id,
				'session_date' => $slot->date_time,
				'session_status' => 'open',
				'settings' => 'ai_booking:completed'
			);
			
			do_action( 'snks_appointment_created', $slot_id, $appointment_data );
		}
		
		return $result;
	}
	
	/**
	 * Send AI order notifications
	 */
	private static function send_ai_order_notifications( $order_id ) {
		$order = wc_get_order( $order_id );
		
		if ( ! $order ) {
			return;
		}
		
		// Send email notification to customer
		$customer_email = $order->get_billing_email();
		$customer_name = $order->get_billing_first_name();
		
		$subject = 'تأكيد حجز الجلسة - منصة جلسة AI';
		$message = sprintf(
			'مرحباً %s،

تم تأكيد حجز جلستك بنجاح!

رقم الطلب: %s
إجمالي المبلغ: %s

سيتم إرسال تفاصيل الجلسة قبل موعدها.

شكراً لك،
فريق منصة جلسة AI',
			$customer_name,
			$order_id,
			$order->get_formatted_order_total()
		);
		
		wp_mail( $customer_email, $subject, $message );
		
		// Send notification to admin
		$admin_email = get_option( 'admin_email' );
		$admin_subject = 'طلب جديد - منصة جلسة AI';
		$admin_message = sprintf(
			'تم استلام طلب جديد:

رقم الطلب: %s
العميل: %s
البريد الإلكتروني: %s
عدد الجلسات: %s
إجمالي المبلغ: %s',
			$order_id,
			$customer_name,
			$customer_email,
			$order->get_meta( 'ai_appointments_count' ),
			$order->get_formatted_order_total()
		);
		
		wp_mail( $admin_email, $admin_subject, $admin_message );
	}
} 