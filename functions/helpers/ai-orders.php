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
			
			// Use original_price for checkout/orders (not converted price for display)
			// Fallback to price if original_price not available (backward compatibility)
			if ( isset( $cart_item['original_price'] ) && is_numeric( $cart_item['original_price'] ) ) {
				$session_price = floatval( $cart_item['original_price'] );
			} elseif ( isset( $cart_item['pricing_info']['original_price'] ) ) {
				$session_price = floatval( $cart_item['pricing_info']['original_price'] );
			} elseif ( isset( $cart_item['price'] ) && is_numeric( $cart_item['price'] ) ) {
				// Fallback to price if original_price not set (old format)
				$session_price = floatval( $cart_item['price'] );
			} else {
				$session_price = SNKS_AI_Products::get_default_session_price();
			}
			
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
                $fee->set_tax_status( 'none' );
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
        
        // If coupon is applied, calculate correct final total from original amount
        if ( ! empty( $coupon ) ) {
            $discount = isset( $coupon['discount'] ) ? floatval( $coupon['discount'] ) : 0;
            if ( $discount > 0 ) {
                // Calculate items total BEFORE the negative fee (sum of all product line items only)
                $items_total = 0;
                foreach ( $order->get_items() as $item ) {
                    if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
                        $items_total += (float) $item->get_total();
                    }
                }
                
                // items_total is the original amount (200)
                // Final total = items_total - discount
                $final_total = max( 0, $items_total - $discount );
                
                // Set the final total directly
                $order->set_total( $final_total );
                $order->save(); // Save immediately to persist the total
            }
        }
		
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

		$patient_id = absint( $order->get_customer_id() );
		$patient    = $patient_id ? get_user_by( 'ID', $patient_id ) : null;
		
		// Send email notification to customer
		$customer_email = $order->get_billing_email();
		$customer_name = $order->get_billing_first_name();

		// Prefer the real patient name from billing meta when available
		if ( $patient_id ) {
			$patient_first_name = get_user_meta( $patient_id, 'billing_first_name', true );
			$patient_last_name  = get_user_meta( $patient_id, 'billing_last_name', true );
			$billing_full_name  = trim( $patient_first_name . ' ' . $patient_last_name );
			if ( ! empty( $billing_full_name ) ) {
				$customer_name = $billing_full_name;
			}
		}

		// Collect extended AI booking info (used for admin notification + order screen).
		$patient_username = $patient ? $patient->user_login : '';
		$patient_whatsapp = '';
		if ( $patient_id && function_exists( 'snks_get_user_whatsapp' ) ) {
			$patient_whatsapp = (string) snks_get_user_whatsapp( $patient_id );
		}
		if ( empty( $patient_whatsapp ) && $patient_id ) {
			$patient_whatsapp = (string) get_user_meta( $patient_id, 'billing_whatsapp', true );
		}
		if ( empty( $patient_whatsapp ) ) {
			$patient_whatsapp = (string) $order->get_billing_phone();
		}

		$country_data = self::get_order_access_country_data( $order );

		// Persist country (best-effort) so it’s visible in admin even if GeoIP changes later.
		if ( ! empty( $country_data['code'] ) ) {
			if ( ! $order->get_meta( 'ai_access_country_code', true ) ) {
				$order->update_meta_data( 'ai_access_country_code', $country_data['code'] );
			}
			if ( ! $order->get_meta( 'ai_access_country_name', true ) ) {
				$order->update_meta_data( 'ai_access_country_name', $country_data['name'] );
			}
			if ( ! empty( $country_data['ip'] ) && ! $order->get_meta( 'ai_access_ip', true ) ) {
				$order->update_meta_data( 'ai_access_ip', $country_data['ip'] );
			}
			$order->save();
		}

		$appointments_lines = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			$therapist_id = absint( $item->get_meta( 'therapist_id', true ) );
			$doctor       = $therapist_id ? get_user_by( 'ID', $therapist_id ) : null;

			$appointment_id = absint( $item->get_meta( 'appointment_id', true ) );
			if ( ! $appointment_id ) {
				$appointment_id = absint( $item->get_meta( 'slot_id', true ) );
			}

			$doctor_username = $doctor ? $doctor->user_login : '';
			$doctor_name     = $doctor ? $doctor->display_name : '';
			if ( $therapist_id && function_exists( 'snks_get_therapist_name' ) ) {
				$doctor_name = (string) snks_get_therapist_name( $therapist_id );
			}

			$session_date  = (string) $item->get_meta( 'session_date', true );
			$session_time  = (string) $item->get_meta( 'session_time', true );
			$session_price = (float) $item->get_total();

			$patient_appointment_number_with_doctor = self::count_patient_appointments_with_doctor( $patient_id, $therapist_id );

			$appointments_lines[] = sprintf(
				'- Appointment #%1$s | Doctor: %2$s (@%3$s) | Date: %4$s %5$s | Price: %6$s | Patient # with doctor: %7$s',
				$appointment_id ? $appointment_id : '-',
				$doctor_name ? $doctor_name : '-',
				$doctor_username ? $doctor_username : '-',
				$session_date ? $session_date : '-',
				$session_time ? $session_time : '-',
				self::format_price_plain( $session_price ),
				$patient_appointment_number_with_doctor ? $patient_appointment_number_with_doctor : 0
			);
		}
		
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
			'تم استلام طلب جديد (AI):

رقم الطلب: %s
اسم المريض: %s
واتساب المريض: %s
بلد الوصول (من IP): %s
IP: %s
اسم مستخدم المريض: %s
البريد الإلكتروني: %s
عدد الجلسات: %s
إجمالي المبلغ: %s

تفاصيل المواعيد:
%s',
			$order_id,
			$customer_name,
			$patient_whatsapp ? $patient_whatsapp : '-',
			! empty( $country_data['name'] ) ? $country_data['name'] : ( ! empty( $country_data['code'] ) ? $country_data['code'] : '-' ),
			! empty( $country_data['ip'] ) ? $country_data['ip'] : '-',
			$patient_username ? $patient_username : '-',
			$customer_email,
			$order->get_meta( 'ai_appointments_count' ),
			$order->get_formatted_order_total(),
			! empty( $appointments_lines ) ? implode( "\n", $appointments_lines ) : '-'
		);
		
		wp_mail( $admin_email, $admin_subject, $admin_message );
	}

	/**
	 * Get customer access country from order IP (best-effort).
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array{ip:string,code:string,name:string}
	 */
	private static function get_order_access_country_data( $order ) {
		$ip = (string) $order->get_customer_ip_address();
		if ( empty( $ip ) ) {
			$ip = (string) $order->get_meta( '_customer_ip_address', true );
		}

		$country_code = '';
		if ( ! empty( $ip ) && class_exists( 'WC_Geolocation' ) ) {
			$geo = WC_Geolocation::geolocate_ip( $ip, true, false );
			if ( is_array( $geo ) && ! empty( $geo['country'] ) ) {
				$country_code = strtoupper( (string) $geo['country'] );
			}
		}

		$country_name = '';
		if ( ! empty( $country_code ) && function_exists( 'WC' ) && WC()->countries && is_array( WC()->countries->countries ) ) {
			$country_name = (string) ( WC()->countries->countries[ $country_code ] ?? '' );
		}

		return array(
			'ip'   => $ip,
			'code' => $country_code,
			'name' => $country_name,
		);
	}

	/**
	 * Count patient's appointments with a specific doctor.
	 *
	 * Note: Uses the sessions actions table, which is populated for AI sessions at creation time.
	 *
	 * @param int $patient_id Patient user ID.
	 * @param int $doctor_id Doctor user ID.
	 * @return int
	 */
	private static function count_patient_appointments_with_doctor( $patient_id, $doctor_id ) {
		global $wpdb;

		$patient_id = absint( $patient_id );
		$doctor_id  = absint( $doctor_id );

		if ( ! $patient_id || ! $doctor_id ) {
			return 0;
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}snks_sessions_actions WHERE therapist_id = %d AND patient_id = %d",
				$doctor_id,
				$patient_id
			)
		);

		return absint( $count );
	}

	/**
	 * Format WooCommerce price as plain text (no HTML).
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	private static function format_price_plain( $amount ) {
		$amount = (float) $amount;
		if ( ! function_exists( 'wc_price' ) ) {
			return (string) $amount;
		}

		$formatted = wc_price( $amount ); // Returns HTML by default.
		$formatted = wp_strip_all_tags( (string) $formatted );

		return html_entity_decode( $formatted, ENT_QUOTES, 'UTF-8' );
	}
}
