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

	$date    = sanitize_text_field( $_request['slectedDay'] );
	$user_id = sanitize_text_field( $_request['userID'] );
	$period  = sanitize_text_field( $_request['period'] );

	// Cache key generation.
	$cache_key = 'dates-appointments-' . $user_id . '-' . $date . '-' . $period . '-' . $attendance_type;
    $results   = wp_cache_get( $cache_key ); // phpcs:disable
    $_order    = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

    if ( ! $results ) {
        // Initialize query builder.
        $builder = wp_query_builder()
            ->select( '*' )
            ->from( 'snks_provider_timetable' )
            ->where([
                'user_id'  => $user_id,
                'period'   => absint( $period ),
                'order_id' => 0,
            ]);

        // Add a DATE condition using a raw condition within the array.
        $builder->where([ 'DATE(date_time)' => $date ]);

        // Add attendance_type condition if provided.
        if ( $attendance_type !== null ) {
            $builder->where([ 'attendance_type' => sanitize_text_field( $attendance_type ) ]);
        }

        // Add order by clause
        $builder->order_by( 'date_time', $_order );

        // Execute the query
        $results = $builder->get();
        
        // Cache the results
        wp_cache_set( $cache_key, $results );

		$availables = $results;
		$html       = snks_render_consulting_hours( $availables, $attendance_type, $user_id );
    } else {
		$html = '<p class="anony-center-text">هناك شيء خاطيء</p>';
	}

	
	wp_send_json(
		array(
			'resp' => $html,
		)
	);
	die;
}

add_action( 'wp_ajax_get_booking_form', 'get_booking_form_callback' );
add_action( 'wp_ajax_nopriv_get_booking_form', 'get_booking_form_callback' );

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

	if ( 'offline' === $_req['attendanceType'] && 'online' === $doctor_attendance_type ) {
		//phpcs:disable
		echo snks_render_doctor_clinics( $_req['doctor_id'] );
		//phpcs:enable
		die;
	}

	//phpcs:disable
	snks_periods_filter( $_req['doctor_id'] , $_req['attendanceType'], $_req['editBookingId']);
	//phpcs:enable
	die();
}
