<?php
/**
 * AI Transaction Processing Admin Page
 * 
 * Manages AI session transaction processing and withdrawal management
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}



/**
 * Add AI transaction processing menu to admin
 */
function snks_add_ai_transaction_processing_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Transaction Processing', 'anony-turn' ),
		__( 'Transaction Processing', 'anony-turn' ),
		'manage_options',
		'ai-transaction-processing',
		'snks_ai_transaction_processing_page'
	);
}
add_action( 'admin_menu', 'snks_add_ai_transaction_processing_menu' );

/**
 * AI transaction processing page content
 */
function snks_ai_transaction_processing_page() {
	// Load AI admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle form submissions
	if ( isset( $_POST['process_session'] ) ) {
		snks_handle_session_processing();
	}
	
	if ( isset( $_POST['process_withdrawal'] ) ) {
		snks_handle_withdrawal_processing();
	}
	
	// Get processing statistics
	$stats = snks_get_ai_processing_statistics();
	
	?>
	<div class="wrap">
		<h1><?php echo __( 'AI Session Transaction Processing', 'anony-turn' ); ?></h1>
		
		<!-- Processing Statistics -->
		<div class="card">
			<h2><?php echo __( 'Processing Statistics', 'anony-turn' ); ?></h2>
			<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				<div class="stat-box" style="background: #f0f8ff; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo __( 'Completed Sessions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;">
						<?php echo number_format( $stats['completed_sessions'] ); ?>
					</p>
				</div>
				<div class="stat-box" style="background: #f0fff0; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #46b450;"><?php echo __( 'Processed Transactions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #46b450;">
						<?php echo number_format( $stats['processed_transactions'] ); ?>
					</p>
				</div>
				<div class="stat-box" style="background: #fff8f0; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #ff8c00;"><?php echo __( 'Total Earnings', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #ff8c00;">
						<?php echo number_format( $stats['total_profit'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?>
					</p>
				</div>
				<div class="stat-box" style="background: #f8f0ff; padding: 20px; border-radius: 8px; text-align: center;">
					<h3 style="margin: 0 0 10px 0; color: #9932cc;"><?php echo __( 'Pending Sessions', 'anony-turn' ); ?></h3>
					<p style="font-size: 24px; font-weight: bold; margin: 0; color: #9932cc;">
						<?php echo number_format( $stats['pending_sessions'] ); ?>
					</p>
				</div>
			</div>
		</div>
		
		<!-- Manual Session Processing -->
		<div class="card">
			<h2><?php echo __( 'Manual Session Processing', 'anony-turn' ); ?></h2>
			<form method="post" action="">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="session_id"><?php echo __( 'Session ID', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="text" 
								   id="session_id" 
								   name="session_id" 
								   class="regular-text" 
								   placeholder="<?php echo __( 'Enter session ID', 'anony-turn' ); ?>" />
							<p class="description"><?php echo __( 'Enter the session ID to process it manually', 'anony-turn' ); ?></p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="process_session" class="button-primary" value="<?php echo __( 'Process Session', 'anony-turn' ); ?>" />
				</p>
			</form>
		</div>
		
		<!-- Pending Sessions -->
		<div class="card">
			<h2><?php echo __( 'Pending Sessions for Processing', 'anony-turn' ); ?></h2>
			<?php
			$pending_sessions = snks_get_pending_ai_sessions();
			if ( empty( $pending_sessions ) ) : ?>
				<p><?php echo __( 'No pending sessions for processing.', 'anony-turn' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
													<th scope="col"><?php echo __( 'Session ID', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Therapist', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Patient', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Session Type', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Session Date', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Status', 'anony-turn' ); ?></th>
						<th scope="col"><?php echo __( 'Actions', 'anony-turn' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending_sessions as $session ) : ?>
							<tr>
								<td><?php echo esc_html( $session['action_session_id'] ); ?></td>
								<td><?php echo esc_html( $session['therapist_name'] ); ?></td>
								<td><?php echo esc_html( $session['patient_name'] ); ?></td>
								<td>
									<span class="session-type-badge <?php echo $session['ai_session_type'] === 'first' ? 'first-session' : 'subsequent-session'; ?>">
										<?php echo $session['ai_session_type'] === 'first' ? 'أولى' : 'لاحقة'; ?>
									</span>
								</td>
								<td><?php echo esc_html( date( 'Y-m-d H:i', strtotime( $session['created_at'] ) ) ); ?></td>
								<td><?php echo esc_html( $session['session_status'] ); ?></td>
								<td>
									<form method="post" action="" style="display: inline;">
										<input type="hidden" name="session_id" value="<?php echo esc_attr( $session['action_session_id'] ); ?>" />
										<input type="submit" name="process_session" class="button button-small" value="معالجة" />
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<!-- Withdrawal Management -->
		<div class="card">
			<h2>إدارة السحوبات</h2>
			<form method="post" action="">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="therapist_id">المعالج</label>
						</th>
						<td>
							<select name="therapist_id" id="therapist_id">
								<option value=""><?php echo __( 'Choose Therapist', 'anony-turn' ); ?></option>
								<?php
								$therapists = get_users( array( 'role' => 'doctor' ) );
								foreach ( $therapists as $therapist ) {
									$balance = snks_get_ai_session_withdrawal_balance( $therapist->ID );
									if ( $balance > 0 ) {
										echo '<option value="' . esc_attr( $therapist->ID ) . '">' . 
											 esc_html( $therapist->display_name ) . 
											 							' (' . __( 'Balance:', 'anony-turn' ) . ' ' . number_format( $balance, 2 ) . ' ' . __( 'EGP', 'anony-turn' ) . ')' . 
											 '</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="withdrawal_amount">مبلغ السحب</label>
						</th>
						<td>
							<input type="number" 
								   id="withdrawal_amount" 
								   name="withdrawal_amount" 
								   step="0.01" 
								   min="0" 
								   class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="withdrawal_method">طريقة السحب</label>
						</th>
						<td>
							<select name="withdrawal_method" id="withdrawal_method">
								<option value="wallet">محفظة</option>
								<option value="bank">تحويل بنكي</option>
								<option value="meza">ميزة</option>
							</select>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="process_withdrawal" class="button-primary" value="معالجة السحب" />
				</p>
			</form>
		</div>
		
		<!-- Recent Transactions -->
		<div class="card">
			<h2>آخر المعاملات</h2>
			<?php
			$recent_transactions = snks_get_recent_ai_transactions( 20 );
			if ( empty( $recent_transactions ) ) : ?>
				<p>لا توجد معاملات حديثة.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col">التاريخ</th>
							<th scope="col">المعالج</th>
							<th scope="col"><?php echo __( 'Patient', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Session Type', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Amount', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Session ID', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Status', 'anony-turn' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_transactions as $transaction ) : ?>
							<tr>
								<td><?php echo esc_html( date( 'Y-m-d H:i', strtotime( $transaction['transaction_time'] ) ) ); ?></td>
								<td><?php echo esc_html( $transaction['therapist_name'] ); ?></td>
								<td><?php echo esc_html( $transaction['patient_name'] ); ?></td>
								<td>
									<span class="session-type-badge <?php echo $transaction['ai_session_type'] === 'first' ? 'first-session' : 'subsequent-session'; ?>">
										<?php echo $transaction['ai_session_type'] === 'first' ? 'أولى' : 'لاحقة'; ?>
									</span>
								</td>
								<td>
									<strong style="color: #0073aa;">
										<?php echo number_format( $transaction['amount'], 2 ); ?> <?php echo __( 'EGP', 'anony-turn' ); ?>
									</strong>
								</td>
								<td><?php echo esc_html( $transaction['ai_session_id'] ); ?></td>
								<td>
									<?php if ( $transaction['processed_for_withdrawal'] ) : ?>
										<span style="color: #46b450;">✓ معالج</span>
									<?php else : ?>
										<span style="color: #ff8c00;">⏳ معلق</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
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
	<?php
}

/**
 * Handle session processing form submission
 */
function snks_handle_session_processing() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'غير مصرح لك بالوصول إلى هذه الصفحة' );
	}
	
	$session_id = sanitize_text_field( $_POST['session_id'] );
	
	if ( empty( $session_id ) ) {
		echo '<div class="notice notice-error"><p>' . __( 'Please enter a session ID.', 'anony-turn' ) . '</p></div>';
		return;
	}
	
	// Validate session data
	$validation = snks_validate_ai_session_data( $session_id );
	
	if ( ! $validation['valid'] ) {
		echo '<div class="notice notice-error"><p>خطأ في البيانات: ' . esc_html( $validation['message'] ) . '</p></div>';
		return;
	}
	
	// Process the session
	$result = snks_process_ai_session_completion( $session_id );
	
	if ( $result['success'] ) {
		echo '<div class="notice notice-success"><p>' . __( 'Session processed successfully. Transaction ID:', 'anony-turn' ) . ' ' . esc_html( $result['transaction_id'] ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error"><p>' . __( 'Session processing failed:', 'anony-turn' ) . ' ' . esc_html( $result['message'] ) . '</p></div>';
	}
}

/**
 * Handle withdrawal processing form submission
 */
function snks_handle_withdrawal_processing() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'غير مصرح لك بالوصول إلى هذه الصفحة' );
	}
	
	$therapist_id = intval( $_POST['therapist_id'] );
	$amount = floatval( $_POST['withdrawal_amount'] );
	$withdrawal_method = sanitize_text_field( $_POST['withdrawal_method'] );
	
	if ( empty( $therapist_id ) || empty( $amount ) ) {
		echo '<div class="notice notice-error"><p>' . __( 'Please enter all required data.', 'anony-turn' ) . '</p></div>';
		return;
	}
	
	// Process withdrawal
	$result = snks_process_ai_session_withdrawal( $therapist_id, $amount, $withdrawal_method );
	
	if ( $result['success'] ) {
		echo '<div class="notice notice-success"><p>' . __( 'Withdrawal processed successfully. Amount:', 'anony-turn' ) . ' ' . number_format( $amount, 2 ) . ' ' . __( 'EGP', 'anony-turn' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error"><p>' . __( 'Withdrawal processing failed:', 'anony-turn' ) . ' ' . esc_html( $result['message'] ) . '</p></div>';
	}
}

/**
 * Get AI processing statistics
 */
function snks_get_ai_processing_statistics() {
	global $wpdb;
	
	$sessions_table = $wpdb->prefix . 'snks_sessions_actions';
	$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
	
	$completed_sessions = $wpdb->get_var( "
		SELECT COUNT(*) FROM $sessions_table 
		WHERE ai_session_type IS NOT NULL AND session_status = 'completed'
	" );
	
	$processed_transactions = $wpdb->get_var( "
		SELECT COUNT(*) FROM $transactions_table 
		WHERE ai_session_id IS NOT NULL AND transaction_type = 'add'
	" );
	
	$total_profit = $wpdb->get_var( "
		SELECT SUM(amount) FROM $transactions_table 
		WHERE ai_session_id IS NOT NULL AND transaction_type = 'add'
	" );
	
	$pending_sessions = $wpdb->get_var( "
		SELECT COUNT(*) FROM $sessions_table 
		WHERE ai_session_type IS NOT NULL AND session_status = 'open'
	" );
	
	return array(
		'completed_sessions' => $completed_sessions ?: 0,
		'processed_transactions' => $processed_transactions ?: 0,
		'total_profit' => $total_profit ?: 0,
		'pending_sessions' => $pending_sessions ?: 0
	);
}

/**
 * Get pending AI sessions
 */
function snks_get_pending_ai_sessions( $limit = 50 ) {
	global $wpdb;
	
	$sessions = $wpdb->get_results( "
		SELECT sa.*, 
		       t.display_name as therapist_name,
		       p.display_name as patient_name
		FROM {$wpdb->prefix}snks_sessions_actions sa
		LEFT JOIN {$wpdb->users} t ON sa.therapist_id = t.ID
		LEFT JOIN {$wpdb->users} p ON sa.patient_id = p.ID
		WHERE sa.ai_session_type IS NOT NULL 
		AND sa.session_status = 'open'
		ORDER BY sa.created_at DESC
		LIMIT $limit
	", ARRAY_A );
	
	return $sessions;
}

/**
 * Get recent AI transactions
 */
function snks_get_recent_ai_transactions( $limit = 50 ) {
	global $wpdb;
	
	$transactions = $wpdb->get_results( "
		SELECT t.*, 
		       u.display_name as therapist_name,
		       p.display_name as patient_name
		FROM {$wpdb->prefix}snks_booking_transactions t
		LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
		LEFT JOIN {$wpdb->users} p ON t.ai_patient_id = p.ID
		WHERE t.ai_session_id IS NOT NULL
		ORDER BY t.transaction_time DESC
		LIMIT $limit
	", ARRAY_A );
	
	return $transactions;
}
