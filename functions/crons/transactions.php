<?php
/**
 * Accounting
 *
 * @package Nafea
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

defined( 'ABSPATH' ) || die();
define( 'SNKS_CURRENT_TIME', current_time( 'Y-m-d 23:59:59' ) );
if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();

/**
 * Schedule the withdrawal cron job to run every 15 minutes starting at 12 am.
 */
function schedule_withdrawal_cron_job() {
	if ( ! wp_next_scheduled( 'snks_process_withdrawals_event' ) ) {
		wp_schedule_event( strtotime( '00:00:00' ), 'every_15_minutes', 'snks_process_withdrawals_event' );
	}
}
add_action( 'wp', 'schedule_withdrawal_cron_job' );

/**
 * Clear the withdrawal cron job on deactivation.
 */
function clear_withdrawal_cron_job() {
	wp_clear_scheduled_hook( 'snks_process_withdrawals_event' );
}
register_deactivation_hook( __FILE__, 'clear_withdrawal_cron_job' );

/**
 * Get available balance for withdrawal based on transactions before 12 am.
 *
 * @param int $user_id The user ID.
 *
 * @return float The available balance.
 */
function get_available_balance( $user_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;

	// Get current date for checking transactions before 12 am.
	$current_date = SNKS_CURRENT_TIME;
	//phpcs:disable
	$available_amount = $wpdb->get_var(
		$wpdb->prepare(
			"
            SELECT SUM(amount) 
            FROM $table_name 
            WHERE user_id = %d 
            AND transaction_type = 'add'
            AND transaction_time < %s
			AND processed_for_withdrawal = 0
            ",
			$user_id,
			$current_date
		)
	);

	//phpcs:enable
	return $available_amount;
}

/**
 * Add a transaction (add or withdraw) to the booking transactions table.
 *
 * @param int    $user_id          The user ID.
 * @param int    $timetable_id     The booking ID.
 * @param string $transaction_type The type of transaction ('add' or 'withdraw').
 * @param float  $amount           The amount to be recorded.
 */
function snks_add_transaction( $user_id, $timetable_id, $transaction_type, $amount ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;
	//phpcs:disable
	$wpdb->insert(
		$table_name,
		array(
			'user_id'          => $user_id,
			'timetable_id'     => $timetable_id,
			'transaction_type' => $transaction_type,
			'amount'           => $amount,
			'transaction_time' => current_time( 'mysql' ),
		),
		array(
			'%d',
			'%d',
			'%s',
			'%f',
			'%s',
		)
	);
	//phpcs:enable
}

/**
 * Process withdrawal transactions for a batch of 50 users.
 */
function process_withdrawals_batch() {
	global $wpdb;

	// Ensure the cron job runs only between 12 am and 9 am.
	$current_time = current_time( 'H:i:s' );
	if ( ! SNKS_DEV_MODE && ( $current_time < '00:00:00' || $current_time > '09:00:00' ) ) {
		return;
	}

	// Get the transient offset for batch processing (50 users at a time).
	$offset = get_transient( 'withdrawal_offset' );
	if ( false === $offset ) {
		$offset = 0;
	}

	$table_name   = $wpdb->prefix . TRNS_TABLE_NAME;
	$current_date = SNKS_CURRENT_TIME;
	//phpcs:disable
	// Query 50 users with eligible transactions.
	$users = $wpdb->get_results(
		$wpdb->prepare(
			"
            SELECT user_id 
            FROM $table_name 
            WHERE transaction_type = 'add' 
			AND transaction_time < %s
			AND processed_for_withdrawal = 0
            LIMIT 50
            ",
			$current_date,
			$offset
		)
	);

	//phpcs:enable
	foreach ( $users as $user ) {
		$user_id = $user->user_id;

		// Get the eligible balance for withdrawal.
		$available_balance = get_available_balance( $user_id );

		if ( $available_balance > 0 ) {
			snks_add_transaction( $user_id, 0, 'withdraw', $available_balance );
			//phpcs:disable
			// Mark the eligible "add" transactions as processed.
			$wpdb->query(
				$wpdb->prepare(
					"
                    UPDATE $table_name
                    SET processed_for_withdrawal = 1
                    WHERE user_id = %d 
                    AND transaction_type = 'add' 
                    AND transaction_time < %s
                    AND processed_for_withdrawal = 0
                    ",
					$user_id,
					$current_date
				)
			);
			//phpcs:enable
			snks_log_transaction( $user_id, $available_balance, 'withdraw' );
		}
	}

	// Update the offset for the next batch of users.
	if ( count( $users ) > 0 ) {
		$offset += 50;
		set_transient( 'withdrawal_offset', $offset, 15 * MINUTE_IN_SECONDS );
	} else {
		delete_transient( 'withdrawal_offset' );
	}
}
add_action( 'snks_process_withdrawals_event', 'process_withdrawals_batch' );
add_action( 'wp_footer', 'process_withdrawals_batch' );


/**
 * Rotate the log file if it exceeds the maximum size using WP_Filesystem.
 *
 * @param string $log_file The log file path.
 * @param int    $max_size Maximum file size in bytes (default is 1 MB).
 */
function rotate_log_file( $log_file, $max_size = 1048576 ) {
	// Access the global filesystem object.
	global $wp_filesystem;

	// Ensure the WP_Filesystem is initialized.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	// Initialize WP_Filesystem.
	WP_Filesystem();

	// Check if the log file exists and its size exceeds the maximum limit.
	if ( $wp_filesystem->exists( $log_file ) && $wp_filesystem->size( $log_file ) > $max_size ) {
		$archive_file = WP_CONTENT_DIR . '/uploads/transaction_logs_' . time() . '.txt';

		// Move the current log file to an archive file.
		$wp_filesystem->move( $log_file, $archive_file );
	}
}


/**
 * Log the withdrawal transactions to a custom log file.
 *
 * @param int   $user_id  The user ID.
 * @param float $amount   The withdrawn amount.
 * @param float $type     The transaction type.
 */
function snks_log_transaction( $user_id, $amount, $type ) {
	$log_file = WP_CONTENT_DIR . '/uploads/transaction_logs.txt'; // Path to log file.
	rotate_log_file( $log_file ); // Rotate the log if necessary.
	$transaction_time = current_time( 'Y-m-d H:i:s' ); // Get the current transaction time.
	$log_entry        = sprintf(
		"Transaction Type: %s | User ID: %d | Amount: %.2f | Transaction Time: %s\n",
		$type,
		$user_id,
		$amount,
		$transaction_time
	);

	// Write log entry to the custom log file.
	error_log( $log_entry, 3, $log_file );
}

/**
 * Helper function to retrieve withdrawal transactions.
 *
 * @param string $date The date in 'Y-m-d' format to filter transactions (on the date).
 * @param int    $limit The number of results to return per page (for pagination).
 * @param int    $offset The offset for pagination (how many records to skip).
 *
 * @return array The result set of filtered transactions.
 */
function get_withdraw_transactions( $date, $limit = 0, $offset = 0 ) {
	global $wpdb;
	//phpcs:disable
	// Ensure the date is in 'Y-m-d' format.
	$formatted_date = gmdate( 'Y-m-d', strtotime( $date ) ) . ' 00:00:00';

	// Table name.
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;

	// Base SQL query for filtering withdrawals on or after the provided date.
	$sql = $wpdb->prepare(
		"
        SELECT * 
        FROM $table_name
        WHERE transaction_type = 'withdraw' 
        AND DATE(transaction_time) = %s
        ",
		$formatted_date
	);

	// Add pagination if needed.
	if ( $limit > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
	}

	// Execute the query and return the results.
	$results = $wpdb->get_results( $sql, ARRAY_A );
	//phpcs:enable
	return $results;
}

/**
 * Reset the withdrawal offset at midnight.
 */
function reset_withdrawal_offset() {
	if ( current_time( 'H:i:s' ) === '00:00:00' ) {
		delete_transient( 'withdrawal_offset' );
	}
}
add_action( 'wp', 'reset_withdrawal_offset' );
