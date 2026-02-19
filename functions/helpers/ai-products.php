<?php
/**
 * AI Products Helper Class
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * AI Products Helper Class
 */
class SNKS_AI_Products {
	
	/**
	 * Get AI session product ID
	 */
	public static function get_ai_session_product_id() {
		$product_id = get_option( 'snks_ai_session_product_id' );
		
		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			// Recreate product if it doesn't exist
			$product_id = self::create_ai_session_product();
		}
		
		return $product_id;
	}
	
	/**
	 * Create AI session product
	 */
	public static function create_ai_session_product() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		
		try {
			// Check if product already exists
			$existing_product_id = get_option( 'snks_ai_session_product_id' );
			if ( $existing_product_id && wc_get_product( $existing_product_id ) ) {
				return $existing_product_id;
			}
			
			// Create AI Session Product using wp_insert_post to avoid WooCommerce hooks during activation
			$product_data = array(
				'post_title'    => 'جلسة علاج نفسي - AI',
				'post_content'  => 'جلسة علاج نفسي عبر منصة جلسة AI - مدة الجلسة 45 دقيقة',
				'post_excerpt'  => 'جلسة علاج نفسي عبر الإنترنت',
				'post_status'   => 'publish',
				'post_type'     => 'product',
				'post_author'   => 1,
				'comment_status' => 'closed'
			);
			
			$product_id = wp_insert_post( $product_data );
			
			if ( $product_id && ! is_wp_error( $product_id ) ) {
				// Store product ID in options
				update_option( 'snks_ai_session_product_id', $product_id );
				
				// Add WooCommerce product meta
				update_post_meta( $product_id, '_regular_price', SNKS_AI_Products::get_default_session_price() );
				update_post_meta( $product_id, '_price', SNKS_AI_Products::get_default_session_price() );
				update_post_meta( $product_id, '_virtual', 'yes' );
				update_post_meta( $product_id, '_downloadable', 'no' );
				update_post_meta( $product_id, '_visibility', 'hidden' );
				update_post_meta( $product_id, '_sold_individually', 'no' );
				update_post_meta( $product_id, '_manage_stock', 'no' );
				update_post_meta( $product_id, '_stock_status', 'instock' );
				
				// Add AI-specific meta
				update_post_meta( $product_id, '_is_ai_session_product', 'yes' );
				update_post_meta( $product_id, '_ai_session_duration', 45 );
				
				// Set product type
				wp_set_object_terms( $product_id, 'simple', 'product_type' );
				
				return $product_id;
			}
		} catch ( Exception $e ) {
			error_log( 'AI Product creation error: ' . $e->getMessage() );
		}
		
		return false;
	}
	
	/**
	 * Get default AI session price
	 */
	public static function get_default_session_price() {
		return get_option( 'snks_ai_default_session_price', 200.00 );
	}
	
	/**
	 * Get AI session duration
	 */
	public static function get_session_duration() {
		return get_option( 'snks_ai_session_duration', 45 );
	}

	/**
	 * Get Rochtah consultation product ID. Creates product if missing; syncs price from options.
	 *
	 * @return int|false Product ID or false if WooCommerce inactive or creation failed.
	 */
	public static function get_rochtah_product_id() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		$product_id = get_option( 'snks_rochtah_product_id' );
		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			$product_id = self::create_rochtah_product();
		}
		if ( $product_id ) {
			self::sync_rochtah_product_price( $product_id );
		}
		return $product_id;
	}

	/**
	 * Create Rochtah consultation product (virtual, hidden).
	 *
	 * @return int|false Product ID or false on failure.
	 */
	public static function create_rochtah_product() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		try {
			$existing_id = get_option( 'snks_rochtah_product_id' );
			if ( $existing_id && wc_get_product( $existing_id ) ) {
				return $existing_id;
			}
			$price = self::get_rochtah_price();
			$product_data = array(
				'post_title'     => __( 'استشارة روشتا - وصف دواء', 'shrinks' ),
				'post_content'  => __( 'استشارة روشتا مع طبيب نفسي لوصف الدواء - 15 دقيقة', 'shrinks' ),
				'post_excerpt'   => __( 'استشارة روشتا وصف دواء', 'shrinks' ),
				'post_status'   => 'publish',
				'post_type'     => 'product',
				'post_author'   => 1,
				'comment_status' => 'closed',
			);
			$product_id = wp_insert_post( $product_data );
			if ( ! $product_id || is_wp_error( $product_id ) ) {
				return false;
			}
			update_option( 'snks_rochtah_product_id', $product_id );
			update_post_meta( $product_id, '_regular_price', $price );
			update_post_meta( $product_id, '_price', $price );
			update_post_meta( $product_id, '_virtual', 'yes' );
			update_post_meta( $product_id, '_downloadable', 'no' );
			update_post_meta( $product_id, '_visibility', 'hidden' );
			update_post_meta( $product_id, '_sold_individually', 'yes' );
			update_post_meta( $product_id, '_manage_stock', 'no' );
			update_post_meta( $product_id, '_stock_status', 'instock' );
			update_post_meta( $product_id, '_is_rochtah_product', 'yes' );
			wp_set_object_terms( $product_id, 'simple', 'product_type' );
			return $product_id;
		} catch ( Exception $e ) {
			error_log( 'Rochtah product creation error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Sync Rochtah product price from option (so new orders use current price).
	 *
	 * @param int $product_id WooCommerce product ID.
	 */
	public static function sync_rochtah_product_price( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}
		$price = self::get_rochtah_price();
		$product->set_regular_price( $price );
		$product->set_price( $price );
		$product->save();
	}

	/**
	 * Get Rochtah consultation price from options.
	 *
	 * @return float
	 */
	public static function get_rochtah_price() {
		$price = get_option( 'snks_rochtah_price', 0 );
		return is_numeric( $price ) ? floatval( $price ) : 0;
	}
} 