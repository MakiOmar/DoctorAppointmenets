<?php
/**
 * Consulting form ajax
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

add_action( 'wp_ajax_fetch_start_times', 'fetch_start_times_callback' );
add_action( 'wp_ajax_nopriv_fetch_start_times', 'fetch_start_times_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function fetch_start_times_callback() {
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();

	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'fetch_start_times_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}

	$attendance_type = sanitize_text_field( $_request['attendanceType'] );
	$date            = sanitize_text_field( $_request['slectedDay'] );
	$user_id         = sanitize_text_field( $_request['userID'] );
	$period          = sanitize_text_field( $_request['period'] );
	$_order          = ! empty( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';

	global $wpdb;

	// Start building the SQL query.
    $sql = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
        WHERE user_id = %d 
        AND period = %d 
        AND order_id = 0 
        AND session_status = 'waiting' 
        AND DATE(date_time) = %s
        AND settings NOT LIKE %s"; // <-- exclude rows containing ai_booking

    // Prepare the parameters for the query.
    $query_params = array(
        $user_id,
        absint( $period ),
        $date,
        '%ai_booking%', // parameter for NOT LIKE
    );

	// Apply clinic conditions only if the attendance type is NOT online.
	if ( $attendance_type !== 'online' ) {
		// Fetch disabled clinics.
		$disabled_clinics = snks_disabled_clinics( $user_id );
		if ( ! empty( $disabled_clinics ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $disabled_clinics ), '%s' ) );
			$sql         .= " AND clinic NOT IN ($placeholders)";
			$query_params = array_merge( $query_params, $disabled_clinics );
		}

		// Fetch enabled clinics.
		$enabled_clinics = snks_enabled_clinics( $user_id );
		if ( ! empty( $enabled_clinics ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $enabled_clinics ), '%s' ) );
			$sql         .= " AND clinic IN ($placeholders)";
			$query_params = array_merge( $query_params, $enabled_clinics );
		}
	}

	// Add the attendance type condition if present.
	if ( ! empty( $attendance_type ) ) {
		$sql           .= ' AND attendance_type = %s';
		$query_params[] = sanitize_text_field( $attendance_type );
	}

	// Add the order by clause.
	$sql .= ' ORDER BY date_time ' . esc_sql( $_order );

	// phpcs:disable
	// Execute the query.
	$results = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ) );
	// phpcs:enable

	// Render the results.
	$availables = $results;
	$html       = snks_render_consulting_hours( $availables, $attendance_type, $user_id );

	wp_send_json(
		array(
			'resp' => $html,
		)
	);
	die;
}


add_action( 'wp_ajax_get_booking_form', 'get_booking_form_callback' );
add_action( 'wp_ajax_nopriv_get_booking_form', 'get_booking_form_callback' );

add_filter(
	'query_builder_query_query',
	function ( $query ) {
		return $query;
	}
);

/**
 * Get booking form
 *
 * @return void
 */
function get_booking_form_callback() {
	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'get_booking_form_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$settings               = snks_doctor_settings( absint( $_req['doctor_id'] ) );
	$doctor_attendance_type = $settings['attendance_type'];
	if ( 'online' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'online', 'both' ), true ) ) {
		echo '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
		die;
	}

	if ( 'offline' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'offline', 'both' ), true ) ) {
		echo '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
		die;
	}
	//phpcs:disable
	echo snks_generate_consulting_form( $_req['doctor_id'], $_req['period'], $_req['price'], $_req['attendanceType'], $_req['editBookingId'] );
	//phpcs:enable
	die();
}

add_action( 'wp_ajax_get_periods', 'get_periods_callback' );
add_action( 'wp_ajax_nopriv_get_periods', 'get_periods_callback' );

/**
 * Get booking form
 *
 * @return void
 */
function get_periods_callback() {

	$_req = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_req['nonce'] ) && isset( $_req['doctor_id'] ) && ! wp_verify_nonce( sanitize_text_field( $_req['nonce'] ), 'get_periods_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$settings               = snks_doctor_settings( absint( $_req['doctor_id'] ) );
	$doctor_attendance_type = $settings['attendance_type'];
	if ( 'online' === $_req['attendanceType'] && ! in_array( $doctor_attendance_type, array( 'online', 'both' ), true ) ) {
		echo '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
		die;
	}
	$enabled_clinics = snks_enabled_clinics( $_req['doctor_id'] );
	if ( 'offline' === $_req['attendanceType'] && 'online' === $doctor_attendance_type ) {
		//phpcs:disable
		echo snks_render_doctor_clinics( $_req['doctor_id'] );
		//phpcs:enable
		die;
	}

	if ( 'offline' === $_req['attendanceType'] && empty( $enabled_clinics ) ) {
		echo '<p style="text-align:center;padding:16px 0 5px 0">عفواً! لا توجد بيانات متاحة.</p>';
		die;
	}
	//phpcs:disable
	snks_periods_filter( $_req['doctor_id'] , $_req['attendanceType'], $_req['editBookingId']);
	//phpcs:enable
	die();
}
