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
		
		// Create AI Session Product
		$ai_session_product = new WC_Product_Simple();
		$ai_session_product->set_name( 'جلسة علاج نفسي - AI' );
		$ai_session_product->set_description( 'جلسة علاج نفسي عبر منصة جلسة AI - مدة الجلسة 45 دقيقة' );
		$ai_session_product->set_short_description( 'جلسة علاج نفسي عبر الإنترنت' );
		$ai_session_product->set_regular_price( '0' ); // Default price 0, will be overridden dynamically
		$ai_session_product->set_virtual( true ); // Virtual product
		$ai_session_product->set_downloadable( false );
		$ai_session_product->set_status( 'publish' );
		$ai_session_product->set_catalog_visibility( 'hidden' ); // Hidden from catalog
		$ai_session_product->set_sold_individually( false );
		
		$product_id = $ai_session_product->save();
		
		if ( $product_id ) {
			// Store product ID in options
			update_option( 'snks_ai_session_product_id', $product_id );
			
			// Add AI-specific meta
			update_post_meta( $product_id, '_is_ai_session_product', 'yes' );
			update_post_meta( $product_id, '_ai_session_duration', 45 );
		}
		
		return $product_id;
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
} 