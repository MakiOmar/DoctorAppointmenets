<?php
/**
 * Therapist Earnings Dashboard
 * 
 * Displays AI session earnings and transaction history for therapists
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}



/**
 * Add therapist earnings menu to admin
 */
function snks_add_therapist_earnings_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Therapist Earnings', 'anony-turn' ),
		__( 'Therapist Earnings', 'anony-turn' ),
		'manage_options',
		'therapist-earnings',
		'snks_therapist_earnings_page'
	);
}
add_action( 'admin_menu', 'snks_add_therapist_earnings_menu' );

/**
 * Therapist earnings page content
 */
function snks_therapist_earnings_page() {
	// Load AI admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle export requests
	if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
		snks_export_earnings_csv();
	}
	
	// Get filter parameters
	$therapist_id = isset( $_GET['therapist_id'] ) ? intval( $_GET['therapist_id'] ) : 0;
	$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'all';
	$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
	$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
	
	// Get all therapists for filter dropdown
	$therapists = snks_get_all_therapists_for_earnings();
	
	// Get earnings data
	$earnings_data = snks_get_earnings_data( $therapist_id, $period, $date_from, $date_to );
	
	?>
	<div class="wrap">
		<h1><?php echo __( 'Therapist Earnings from AI Sessions', 'anony-turn' ); ?></h1>
		
		<!-- Filters Section -->
		<div class="card">
			<h2><?php echo __( 'Search Filters', 'anony-turn' ); ?></h2>
			<form method="get" action="">
				<input type="hidden" name="page" value="therapist-earnings" />
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="therapist_id"><?php echo __( 'Therapist', 'anony-turn' ); ?></label>
						</th>
						<td>
							<select name="therapist_id" id="therapist_id">
								<option value="0"><?php echo __( 'All Therapists', 'anony-turn' ); ?></option>
								<?php foreach ( $therapists as $therapist ) : ?>
									<option value="<?php echo esc_attr( $therapist['ID'] ); ?>" <?php selected( $therapist_id, $therapist['ID'] ); ?>>
										<?php echo esc_html( $therapist['display_name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="period"><?php echo __( 'Period', 'anony-turn' ); ?></label>
						</th>
						<td>
							<select name="period" id="period">
								<option value="all" <?php selected( $period, 'all' ); ?>><?php echo __( 'All Periods', 'anony-turn' ); ?></option>
								<option value="today" <?php selected( $period, 'today' ); ?>><?php echo __( 'Today', 'anony-turn' ); ?></option>
								<option value="week" <?php selected( $period, 'week' ); ?>><?php echo __( 'This Week', 'anony-turn' ); ?></option>
								<option value="month" <?php selected( $period, 'month' ); ?>><?php echo __( 'This Month', 'anony-turn' ); ?></option>
								<option value="custom" <?php selected( $period, 'custom' ); ?>><?php echo __( 'Custom Period', 'anony-turn' ); ?></option>
							</select>
						</td>
					</tr>
					<tr class="custom-date-fields" style="<?php echo ( $period === 'custom' ) ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="date_from"><?php echo __( 'From Date', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
						</td>
					</tr>
					<tr class="custom-date-fields" style="<?php echo ( $period === 'custom' ) ? '' : 'display: none;'; ?>">
						<th scope="row">
							<label for="date_to"><?php echo __( 'To Date', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php echo __( 'Apply Filters', 'anony-turn' ); ?>" />
					<a href="?page=therapist-earnings&export=csv&therapist_id=<?php echo $therapist_id; ?>&period=<?php echo $period; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="button"><?php echo __( 'Export CSV', 'anony-turn' ); ?></a>
				</p>
			</form>
		</div>
		
		<!-- Summary Statistics -->
		<div class="card">
			<h2><?php echo __( 'Summary Statistics', 'anony-turn' ); ?></h2>
			<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				<div class="stat-box" style="background: #f0f8ff; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo __( 'Total Earnings', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;">
						<?php echo number_format( $earnings_data['summary']['total_profit'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?>
					</p>
				</div>
				<div class="stat-box" style="background: #f0fff0; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #46b450;"><?php echo __( 'Total Sessions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #46b450;">
						<?php echo $earnings_data['summary']['total_sessions']; ?>
					</p>
				</div>
				<div class="stat-box" style="background: #fff8f0; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #ff8c00;"><?php echo __( 'First Sessions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #ff8c00;">
						<?php echo $earnings_data['summary']['first_sessions']; ?>
					</p>
				</div>
				<div class="stat-box" style="background: #f8f0ff; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #9932cc;"><?php echo __( 'Subsequent Sessions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #9932cc;">
						<?php echo $earnings_data['summary']['subsequent_sessions']; ?>
					</p>
				</div>
			</div>
		</div>
		
		<!-- Earnings by Therapist -->
		<div class="card">
			<h2><?php echo __( 'Earnings by Therapist', 'anony-turn' ); ?></h2>
			<?php if ( empty( $earnings_data['by_therapist'] ) ) : ?>
				<p><?php echo __( 'No earnings data for the specified period.', 'anony-turn' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php echo __( 'Therapist', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Email', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Total Earnings', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'First Sessions', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Subsequent Sessions', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Total Sessions', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Average Profit per Session', 'anony-turn' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $earnings_data['by_therapist'] as $therapist_earnings ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $therapist_earnings['therapist_name'] ); ?></strong>
								</td>
								<td><?php echo esc_html( $therapist_earnings['therapist_email'] ); ?></td>
								<td>
									<strong style="color: #0073aa;">
										<?php echo number_format( $therapist_earnings['total_profit'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?>
									</strong>
								</td>
								<td><?php echo $therapist_earnings['first_sessions']; ?></td>
								<td><?php echo $therapist_earnings['subsequent_sessions']; ?></td>
								<td><?php echo $therapist_earnings['total_sessions']; ?></td>
								<td>
									<?php 
									$avg_profit = $therapist_earnings['total_sessions'] > 0 
										? $therapist_earnings['total_profit'] / $therapist_earnings['total_sessions'] 
										: 0;
									echo number_format( $avg_profit, 2 ) . ' ' . __( 'EGP', 'anony-turn' );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<!-- Transaction History -->
		<div class="card">
			<h2><?php echo __( 'Transaction History', 'anony-turn' ); ?></h2>
			<?php if ( empty( $earnings_data['transactions'] ) ) : ?>
				<p><?php echo __( 'No transactions for the specified period.', 'anony-turn' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php echo __( 'Date', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Therapist', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Patient', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Session Type', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Session Amount', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Profit Amount', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Session ID', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Order ID', 'anony-turn' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $earnings_data['transactions'] as $transaction ) : ?>
							<tr>
								<td><?php echo esc_html( date( 'Y-m-d H:i', strtotime( $transaction['transaction_time'] ) ) ); ?></td>
								<td><?php echo esc_html( $transaction['therapist_name'] ); ?></td>
								<td><?php echo esc_html( $transaction['patient_name'] ); ?></td>
								<td>
									<span class="session-type-badge <?php echo $transaction['session_type'] === 'first' ? 'first-session' : 'subsequent-session'; ?>">
										<?php echo $transaction['session_type'] === 'first' ? __( 'First', 'anony-turn' ) : __( 'Subsequent', 'anony-turn' ); ?>
									</span>
								</td>
								<td><?php echo number_format( $transaction['session_amount'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?></td>
								<td>
									<strong style="color: #0073aa;">
										<?php echo number_format( $transaction['profit_amount'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?>
									</strong>
								</td>
								<td><?php echo esc_html( $transaction['session_id'] ); ?></td>
								<td><?php echo esc_html( $transaction['order_id'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<!-- Pagination -->
				<?php if ( $earnings_data['pagination']['total_pages'] > 1 ) : ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<?php
							$current_page = max( 1, get_query_var( 'paged' ) );
							$total_pages = $earnings_data['pagination']['total_pages'];
							
							echo paginate_links( array(
								'base' => add_query_arg( 'paged', '%#%' ),
								'format' => '',
											'prev_text' => '&laquo; ' . __( 'Previous', 'anony-turn' ),
			'next_text' => __( 'Next', 'anony-turn' ) . ' &raquo;',
								'total' => $total_pages,
								'current' => $current_page
							) );
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	
	<style>
	.session-type-badge {
		padding: 4px 8px;
		border-radius: 4px;
		font-size: 12px;
		font-weight: bold;
	}
	.first-session {
		background: #ff8c00;
		color: white;
	}
	.subsequent-session {
		background: #9932cc;
		color: white;
	}
	</style>
	
	<script>
	// Show/hide custom date fields based on period selection
	document.getElementById('period').addEventListener('change', function() {
		const customFields = document.querySelectorAll('.custom-date-fields');
		if (this.value === 'custom') {
			customFields.forEach(field => field.style.display = 'table-row');
		} else {
			customFields.forEach(field => field.style.display = 'none');
		}
	});
	</script>
	<?php
}

/**
 * Get earnings data based on filters
 */
function snks_get_earnings_data( $therapist_id = 0, $period = 'all', $date_from = '', $date_to = '' ) {
	global $wpdb;
	
	$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
	$users_table = $wpdb->users;
	
	// Build date filter
	$date_filter = '';
	switch ( $period ) {
		case 'today':
			$date_filter = $wpdb->prepare( "AND DATE(t.transaction_time) = %s", current_time( 'Y-m-d' ) );
			break;
		case 'week':
			$date_filter = $wpdb->prepare( "AND YEARWEEK(t.transaction_time) = YEARWEEK(%s)", current_time( 'Y-m-d' ) );
			break;
		case 'month':
			$date_filter = $wpdb->prepare( "AND YEAR(t.transaction_time) = YEAR(%s) AND MONTH(t.transaction_time) = MONTH(%s)", current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) );
			break;
		case 'custom':
			if ( $date_from && $date_to ) {
				$date_filter = $wpdb->prepare( "AND DATE(t.transaction_time) BETWEEN %s AND %s", $date_from, $date_to );
			}
			break;
	}
	
	// Build therapist filter
	$therapist_filter = '';
	if ( $therapist_id > 0 ) {
		$therapist_filter = $wpdb->prepare( "AND t.user_id = %d", $therapist_id );
	}
	
	// Get transactions
	$transactions_query = "
		SELECT 
			t.*,
			u.display_name as therapist_name,
			u.user_email as therapist_email,
			p.display_name as patient_name
		FROM $transactions_table t
		LEFT JOIN $users_table u ON t.user_id = u.ID
		LEFT JOIN $users_table p ON t.ai_patient_id = p.ID
		WHERE t.transaction_type = 'add' 
		AND t.ai_session_id IS NOT NULL
		$therapist_filter
		$date_filter
		ORDER BY t.transaction_time DESC
		LIMIT 100
	";
	
	$transactions = $wpdb->get_results( $transactions_query, ARRAY_A );
	
	// Calculate summary statistics
	$summary = array(
		'total_profit' => 0,
		'total_sessions' => 0,
		'first_sessions' => 0,
		'subsequent_sessions' => 0
	);
	
	$by_therapist = array();
	
	foreach ( $transactions as $transaction ) {
		$summary['total_profit'] += $transaction['amount'];
		$summary['total_sessions']++;
		
		if ( $transaction['ai_session_type'] === 'first' ) {
			$summary['first_sessions']++;
		} else {
			$summary['subsequent_sessions']++;
		}
		
		// Group by therapist
		$therapist_id = $transaction['user_id'];
		if ( ! isset( $by_therapist[ $therapist_id ] ) ) {
			$by_therapist[ $therapist_id ] = array(
				'therapist_name' => $transaction['therapist_name'],
				'therapist_email' => $transaction['therapist_email'],
				'total_profit' => 0,
				'total_sessions' => 0,
				'first_sessions' => 0,
				'subsequent_sessions' => 0
			);
		}
		
		$by_therapist[ $therapist_id ]['total_profit'] += $transaction['amount'];
		$by_therapist[ $therapist_id ]['total_sessions']++;
		
		if ( $transaction['ai_session_type'] === 'first' ) {
			$by_therapist[ $therapist_id ]['first_sessions']++;
		} else {
			$by_therapist[ $therapist_id ]['subsequent_sessions']++;
		}
	}
	
	// Add session amount and profit amount to transactions for display
	foreach ( $transactions as &$transaction ) {
		$transaction['session_amount'] = $transaction['amount'] / ( $transaction['ai_session_type'] === 'first' ? 0.7 : 0.75 ) * 100; // Reverse calculate
		$transaction['profit_amount'] = $transaction['amount'];
	}
	
	return array(
		'summary' => $summary,
		'by_therapist' => $by_therapist,
		'transactions' => $transactions,
		'pagination' => array(
			'total_pages' => 1,
			'current_page' => 1
		)
	);
}

/**
 * Get all therapists for earnings filter
 */
function snks_get_all_therapists_for_earnings() {
	global $wpdb;
	
	return $wpdb->get_results( "
		SELECT u.ID, u.display_name, u.user_email
		FROM {$wpdb->users} u
		WHERE u.ID IN (
			SELECT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key = '{$wpdb->prefix}capabilities' 
			AND meta_value LIKE '%doctor%'
		)
		ORDER BY u.display_name ASC
	", ARRAY_A );
}

/**
 * Export earnings data to CSV
 */
function snks_export_earnings_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You are not authorized to access this page', 'anony-turn' ) );
	}
	
	// Get filter parameters
	$therapist_id = isset( $_GET['therapist_id'] ) ? intval( $_GET['therapist_id'] ) : 0;
	$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'all';
	$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
	$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
	
	// Get earnings data
	$earnings_data = snks_get_earnings_data( $therapist_id, $period, $date_from, $date_to );
	
	// Set headers for CSV download
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=earnings-export-' . date( 'Y-m-d' ) . '.csv' );
	
	// Create file pointer connected to the output stream
	$output = fopen( 'php://output', 'w' );
	
	// Add BOM for UTF-8
	fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
	
	// Add headers
	fputcsv( $output, array(
		'التاريخ',
		'المعالج',
		'البريد الإلكتروني للمعالج',
		'المريض',
		'نوع الجلسة',
		'مبلغ الجلسة',
		'مبلغ الربح',
		'معرف الجلسة',
		'معرف الطلب'
	) );
	
	// Add data
	foreach ( $earnings_data['transactions'] as $transaction ) {
		fputcsv( $output, array(
			date( 'Y-m-d H:i', strtotime( $transaction['transaction_time'] ) ),
			$transaction['therapist_name'],
			$transaction['therapist_email'],
			$transaction['patient_name'],
			$transaction['ai_session_type'] === 'first' ? 'أولى' : 'لاحقة',
			number_format( $transaction['session_amount'], 2 ),
			number_format( $transaction['profit_amount'], 2 ),
			$transaction['ai_session_id'],
			$transaction['ai_order_id']
		) );
	}
	
	fclose( $output );
	exit;
}
