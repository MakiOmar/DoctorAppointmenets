<?php
/**
 * Admin Bookings Dashboard
 *
 * Lists only appointments created via admin manual booking.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Admin Bookings dashboard page callback.
 */
function snks_jalsah_ai_admin_bookings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'shrinks' ) );
	}

	snks_load_ai_admin_styles();

	global $wpdb;
	$timetable_table = $wpdb->prefix . 'snks_provider_timetable';
	$applications_table = $wpdb->prefix . 'therapist_applications';

	$search_date = isset( $_GET['search_date'] ) ? sanitize_text_field( $_GET['search_date'] ) : '';
	$search_patient = isset( $_GET['search_patient'] ) ? sanitize_text_field( $_GET['search_patient'] ) : '';
	$search_therapist = isset( $_GET['search_therapist'] ) ? absint( $_GET['search_therapist'] ) : 0;

	$where = array( "t.session_status = 'open'", "t.client_id > 0", "t.settings LIKE '%admin_manual_booking%'" );
	$params = array();

	if ( $search_date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $search_date ) ) {
		$where[] = 'DATE(t.date_time) = %s';
		$params[] = $search_date;
	}
	if ( $search_patient ) {
		$like = '%' . $wpdb->esc_like( $search_patient ) . '%';
		$where[] = "(t.client_id IN (SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s OR user_login LIKE %s) 
			OR t.client_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'billing_phone' AND meta_value LIKE %s))";
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	if ( $search_therapist > 0 ) {
		$where[] = 't.user_id = %d';
		$params[] = $search_therapist;
	}

	$where_sql = implode( ' AND ', $where );
	$query = "SELECT t.ID as booking_id, t.client_id as patient_id, t.user_id as therapist_id, t.date_time, t.order_id
		FROM {$timetable_table} t
		WHERE {$where_sql}
		ORDER BY t.date_time DESC
		LIMIT 200";

	if ( ! empty( $params ) ) {
		$query = $wpdb->prepare( $query, $params );
	}

	$slots = $wpdb->get_results( $query );

	if ( $slots ) {
		foreach ( $slots as $s ) {
			$first = get_user_meta( $s->patient_id, 'billing_first_name', true );
			$last  = get_user_meta( $s->patient_id, 'billing_last_name', true );
			$s->patient_name = trim( $first . ' ' . $last ) ?: '—';
			$s->therapist_name = $wpdb->get_var( $wpdb->prepare(
				"SELECT name FROM {$applications_table} WHERE user_id = %d LIMIT 1",
				$s->therapist_id
			) ) ?: '—';
			$order = $s->order_id ? wc_get_order( $s->order_id ) : null;
			$s->session_price = $order ? (float) $order->get_total() : null;
			$s->country_code = $order ? $order->get_meta( 'ai_access_country_code', true ) : '';
		}
	}

	$therapists = $wpdb->get_results(
		"SELECT user_id, name FROM {$applications_table} WHERE status = 'approved' AND show_on_ai_site = 1 ORDER BY name"
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Admin Bookings', 'shrinks' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Appointments created via Manual Booking (admin-created only).', 'shrinks' ); ?></p>

		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Filter', 'shrinks' ); ?></h2>
			<form method="get" class="snks-admin-bookings-filter">
				<input type="hidden" name="page" value="jalsah-ai-admin-bookings">
				<p>
					<label for="search_date"><?php esc_html_e( 'Date', 'shrinks' ); ?></label>
					<input type="date" id="search_date" name="search_date" value="<?php echo esc_attr( $search_date ); ?>">
				</p>
				<p>
					<label for="search_patient"><?php esc_html_e( 'Patient (email/phone)', 'shrinks' ); ?></label>
					<input type="text" id="search_patient" name="search_patient" value="<?php echo esc_attr( $search_patient ); ?>" class="regular-text">
				</p>
				<p>
					<label for="search_therapist"><?php esc_html_e( 'Therapist', 'shrinks' ); ?></label>
					<select id="search_therapist" name="search_therapist">
						<option value=""><?php esc_html_e( '— All —', 'shrinks' ); ?></option>
						<?php foreach ( $therapists as $t ) : ?>
							<option value="<?php echo esc_attr( $t->user_id ); ?>" <?php selected( $search_therapist, $t->user_id ); ?>><?php echo esc_html( $t->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'shrinks' ); ?></button>
			</form>
		</div>

		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Admin Bookings', 'shrinks' ); ?></h2>
			<?php if ( ! empty( $slots ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Booking ID', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Patient', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Therapist', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Date & Time', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Country', 'shrinks' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'shrinks' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $slots as $slot ) : ?>
							<tr>
								<td><?php echo esc_html( $slot->booking_id ); ?></td>
								<td><?php echo esc_html( $slot->patient_name ); ?></td>
								<td><?php echo esc_html( $slot->therapist_name ); ?></td>
								<td><?php echo esc_html( $slot->date_time ); ?></td>
								<td>
									<?php
									if ( $slot->session_price !== null ) {
										echo esc_html( number_format_i18n( $slot->session_price, 2 ) );
										echo ' ';
										echo esc_html( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '' );
									} else {
										echo '—';
									}
									?>
								</td>
								<td><?php echo esc_html( $slot->country_code ?: '—' ); ?></td>
								<td>
									<?php if ( $slot->order_id ) : ?>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $slot->order_id . '&action=edit' ) ); ?>" class="button button-small"><?php esc_html_e( 'View Order', 'shrinks' ); ?></a>
									<?php endif; ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-manual-booking&tab=change&booking_id=' . $slot->booking_id ) ); ?>" class="button button-small"><?php esc_html_e( 'Change', 'shrinks' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No admin bookings found.', 'shrinks' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
