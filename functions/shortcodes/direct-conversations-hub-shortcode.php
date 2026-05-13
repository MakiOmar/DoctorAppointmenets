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
add_action( 'wp_enqueue_scripts', 'snks_therapist_conversations_hub_maybe_enqueue_assets', 30 );

/**
 * Enqueue hub assets globally for logged-in therapists.
 * Needed when shortcode is rendered later inside JetPopup AJAX content.
 *
 * @return void
 */
function snks_therapist_conversations_hub_maybe_enqueue_assets() {
	if ( is_admin() || ! is_user_logged_in() || ! snks_is_doctor() ) {
		return;
	}

	wp_enqueue_style(
		'snks-therapist-conv-hub',
		SNKS_URI . 'assets/css/snks-therapist-conversations-hub.css',
		array(),
		time()
	);
	wp_enqueue_script(
		'snks-therapist-conv-hub',
		SNKS_URI . 'assets/js/snks-therapist-conversations-hub.js',
		array( 'jquery' ),
		time(),
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
				'title'              => __( 'الرسائل', 'anony-shrinks' ),
				'viewAll'            => __( 'عرض كل المحادثات', 'anony-shrinks' ),
				'placeholder'        => __( 'اكتب رسالة...', 'anony-shrinks' ),
				'send'               => __( 'إرسال', 'anony-shrinks' ),
				'attach'             => __( 'إرفاق ملف', 'anony-shrinks' ),
				'noUnread'           => __( 'لا توجد رسائل غير مقروءة', 'anony-shrinks' ),
				'noBookedPatients'   => __( 'لا يوجد مرضى لديهم حجز حديث', 'anony-shrinks' ),
				'bookedPatientsTab'  => __( 'قائمة المرضى', 'anony-shrinks' ),
				'unreadTab'          => __( 'غير المقروءة', 'anony-shrinks' ),
				'newConversation'    => __( 'محادثة جديدة', 'anony-shrinks' ),
				'patientFallback'    => __( 'مريض', 'anony-shrinks' ),
				'uploading'          => __( 'جاري الرفع', 'anony-shrinks' ),
				'sendingMessage'     => __( 'جاري الإرسال…', 'anony-shrinks' ),
			),
		)
	);
}

/**
 * Shortcode callback.
 *
 * @return string
 */
function snks_therapist_conversations_hub_shortcode() {
	if ( ! is_user_logged_in() || ! snks_is_doctor() ) {
		return '<p class="snks-dc-hub-login-hint">' . esc_html__( 'يرجى تسجيل الدخول كمعالج لعرض المحادثات.', 'anony-shrinks' ) . '</p>';
	}

	snks_therapist_conversations_hub_maybe_enqueue_assets();

	return '<div class="snks-dc-hub" data-nonce="' . esc_attr( wp_create_nonce( 'snks_direct_conv_nonce' ) ) . '"></div>';
}
