<?php
/**
 * Elementor-friendly therapist conversations hub shortcode.
 *
 * Usage: [snks_therapist_conversations_hub]
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'snks_therapist_conversations_hub', 'snks_therapist_conversations_hub_shortcode' );

/**
 * Shortcode callback.
 *
 * @return string
 */
function snks_therapist_conversations_hub_shortcode() {
	if ( ! is_user_logged_in() || ! snks_is_doctor() ) {
		return '<p class="snks-dc-hub-login-hint">' . esc_html__( 'Please log in as a therapist to view conversations.', 'anony-shrinks' ) . '</p>';
	}

	wp_enqueue_style(
		'snks-therapist-conv-hub',
		SNKS_URI . 'assets/css/snks-therapist-conversations-hub.css',
		array(),
		'1.0.0'
	);
	wp_enqueue_script(
		'snks-therapist-conv-hub',
		SNKS_URI . 'assets/js/snks-therapist-conversations-hub.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);
	wp_localize_script(
		'snks-therapist-conv-hub',
		'snksDirectConvHub',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'snks_direct_conv_nonce' ),
			'currentUserId' => get_current_user_id(),
			'i18n'          => array(
				'title'       => __( 'Messages', 'anony-shrinks' ),
				'viewAll'     => __( 'View all conversations', 'anony-shrinks' ),
				'placeholder' => __( 'Type a message…', 'anony-shrinks' ),
				'send'        => __( 'Send', 'anony-shrinks' ),
				'attach'      => __( 'Attach file', 'anony-shrinks' ),
				'noUnread'    => __( 'No unread messages', 'anony-shrinks' ),
				'unread'      => __( 'Unread messages', 'anony-shrinks' ),
			),
		)
	);

	return '<div id="snks-therapist-conversations-hub" class="snks-dc-hub" data-nonce="' . esc_attr( wp_create_nonce( 'snks_direct_conv_nonce' ) ) . '"></div>';
}
