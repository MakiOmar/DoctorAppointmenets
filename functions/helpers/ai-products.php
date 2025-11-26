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
} 