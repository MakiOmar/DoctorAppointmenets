<?php
/**
 * Accounting
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

define( 'SNKS_CURRENT_TIME', current_time( 'Y-m-d 00:00:00' ) );

define( 'SNKS_DEV_MODE', true );
//phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
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
	return is_null( $available_amount ) ? 0 : $available_amount;
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
	$insert_result = $wpdb->insert(
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
	if ( false === $insert_result ) {
		return false;
	} else {
		// Insertion successful, $insert_result is the number of rows inserted.
		return $wpdb->insert_id; // This is the ID of the inserted row.
	}
}
/**
 * Helper function to process withdrawal for a single user.
 *
 * @param object $user The user object.
 * @param int    $current_day_of_week Current day of the week.
 * @param int    $current_day_of_month Current day of the month.
 * @param string $current_date Current date.
 * @param string $table_name The table name.
 * @param bool   $manual True if manual, Otherwise false.
 */
function process_user_withdrawal( $user, $current_day_of_week, $current_day_of_month, $current_date, $table_name, $manual = false ) {
	global $wpdb;

	$user_id             = $user->ID;
	$withdrawal_settings = get_user_meta( $user_id, 'withdrawal_settings', true );
	if ( empty( $withdrawal_settings ) ) {
		return false;
	}

	$withdrawal_option = $withdrawal_settings['withdrawal_option'];
	$withdrawal_method = $withdrawal_settings['withdrawal_method'];

	// Check if the user is eligible for withdrawal based on the option.
	if ( 'daily_withdrawal' === $withdrawal_option ||
		( 'weekly_withdrawal' === $withdrawal_option && 6 === absint( $current_day_of_week ) ) ||
		( 'monthly_withdrawal' === $withdrawal_option && 1 === absint( $current_day_of_month ) ) || $manual ) {

		// Get the eligible balance for withdrawal.
		$available_balance = get_available_balance( $user_id );
		$withdraw_amount   = $available_balance;

		if ( $withdraw_amount > 5 ) {
			$withdrawal_id = snks_add_transaction( $user_id, 0, 'withdraw', $withdraw_amount );
			if ( ! $withdrawal_id ) {
				return false;
			}

			$output_data = null;
			$output_type = '';

			// Generate withdrawal data and files based on the method.
			if ( 'bank_account' === $withdrawal_method ) {
				$output_data = array( snks_bank_method_xlsx( $user_id, $withdraw_amount, $withdrawal_settings ) );
				$output_type = 'bank';
			} elseif ( 'meza_card' === $withdrawal_method ) {
				$output_data = array( snks_meza_method_xlsx( $user_id, $withdraw_amount, $withdrawal_settings ) );
				$output_type = 'meza';
			} elseif ( 'wallet' === $withdrawal_method ) {
				$output_data = array( snks_wallet_method_xlsx( $user_id, $withdraw_amount, $withdrawal_settings ) );
				$output_type = 'wallet';
			}
			if ( ! empty( $output_data ) ) {
				$generated = snks_generate_xlsx( $output_data, $output_type );
				if ( $generated ) {
					snks_update_processed_withdrawals( $withdrawal_id );
				}
			}
			//phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			// Mark the eligible "add" transactions as processed.
			$result = $wpdb->query(
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
			// Check if the query was successful.
			if ( false !== $result && $result > 0 ) {
				// Log the transaction only if rows were updated.
				snks_log_transaction( $user_id, $withdraw_amount, 'withdraw' );
				return true;
			} else {
				return false;
			}
		}
	}
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
	// Get the current day of the week (1 = Monday, 7 = Sunday) and the day of the month.
	$current_day_of_week  = current_time( 'w' ); // 0 for Sunday through 6 for Saturday.
	$current_day_of_month = current_time( 'j' ); // Day of the month (1-31).

	// Get the transient offset for batch processing (50 users at a time).
	$offset = get_transient( 'withdrawal_offset' );
	if ( false === $offset ) {
		$offset = 0;
	}

	$table_name   = $wpdb->prefix . TRNS_TABLE_NAME;
	$current_date = SNKS_CURRENT_TIME;
	//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// Query 50 users with eligible transactions.
	$users = $wpdb->get_results(
		$wpdb->prepare(
			"
            SELECT user_id 
            FROM $table_name 
            WHERE transaction_type = 'add' 
			AND transaction_time < %s
			AND processed_for_withdrawal = 0
            LIMIT 50 OFFSET %d
            ",
			$current_date,
			$offset
		)
	);

	// Process each user using the helper function.
	foreach ( $users as $user ) {
		process_user_withdrawal( $user, $current_day_of_week, $current_day_of_month, $current_date, $table_name );
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
add_action( 'wp', 'process_withdrawals_batch' );

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
	//phpcs:disable
	// Write log entry to the custom log file.
	error_log( $log_entry, 3, $log_file );
	//phpcs:enable
}

/**
 * Helper function to retrieve withdrawal transactions.
 *
 * @param string $date The date in 'Y-m-d' format to filter transactions (optional).
 * @param int    $limit The number of results to return per page (for pagination).
 * @param int    $offset The offset for pagination (how many records to skip).
 *
 * @return array The result set of filtered transactions.
 */
function get_withdraw_transactions( $date = '', $limit = 0, $offset = 0 ) {
	global $wpdb;

	// Table name.
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;

	// Base SQL query for filtering withdrawals. GEt only withrawals that are not added to xlsx ( processed_for_withdrawal = 0 ).
	$sql = "
        SELECT * 
        FROM $table_name
        WHERE transaction_type = 'withdraw' 
		AND processed_for_withdrawal = 0
    ";

	// Check if a date is provided.
	if ( ! empty( $date ) ) {
		// Ensure the date is in 'Y-m-d' format.
		$formatted_date = gmdate( 'Y-m-d', strtotime( $date ) );
		$sql           .= $wpdb->prepare( ' AND DATE(transaction_time) = %s', $formatted_date );
	}

	// Add pagination if needed.
	if ( $limit > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
	}
	//phpcs:disable
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

/**
 * Create the directory structure under the uploads folder for withdrawals.
 * Structure: /wp-content/uploads/withdrawals/{year}/{month}/
 * This function uses WP_Filesystem methods to secure the folder with an .htaccess file.
 *
 * @return string Path to the directory.
 */
function snks_create_withdrawal_directory() {
	global $wp_filesystem;

	// Get the current year and month.
	$year  = gmdate( 'Y' );
	$month = gmdate( 'm' );

	// Get the WordPress uploads directory.
	$upload_dir = wp_upload_dir();

	// Define the path to the withdrawals folder.
	$withdrawals_dir = $upload_dir['basedir'] . '/withdrawals/' . $year . '/' . $month;

	// Check if the folder already exists, if not, create it.
	if ( ! file_exists( $withdrawals_dir ) ) {
		wp_mkdir_p( $withdrawals_dir ); // Create the directory with nested folders.
		// Use WP_Filesystem to write the .htaccess file.
		if ( ! $wp_filesystem->put_contents( $withdrawals_dir . '/index.html', '', FS_CHMOD_FILE ) ) {
			//phpcs:disable
			error_log( 'Failed to create .htaccess file in withdrawals directory.' );
			//phpcs:enable
		}
	}

	return $withdrawals_dir;
}
/**
 * Bank xlsx generate
 *
 * @param int   $user_id User's ID.
 * @param mixed $balance Balance.
 * @param array $withdrawal_settings withdrawal settings.
 * @return array
 */
function snks_bank_method_xlsx( $user_id, $balance, $withdrawal_settings ) {
	return array(
		'Method'           => 'bank',
		'Recipient Name'   => $withdrawal_settings['account_holder_name'],
		'Recipient Number' => $withdrawal_settings['account_number'],
		'Recipient Bank'   => $withdrawal_settings['bank_code'],
		'Amount'           => (string) $balance,
	);
}
/**
 * Bank xlsx generate
 *
 * @param int   $user_id User's ID.
 * @param mixed $balance Balance.
 * @param array $withdrawal_settings withdrawal settings.
 * @return array
 */
function snks_meza_method_xlsx( $user_id, $balance, $withdrawal_settings ) {
	return array(
		'Method'           => 'card',
		'Recipient Name'   => $withdrawal_settings['card_holder_first_name'],
		'Recipient Number' => $withdrawal_settings['meza_card_number'],
		'Recipient Bank'   => $withdrawal_settings['meza_bank_code'],
		'Amount'           => (string) $balance,
	);
}
/**
 * Bank xlsx generate
 *
 * @param int   $user_id User's ID.
 * @param mixed $balance Balance.
 * @param array $withdrawal_settings Withdrawal settings.
 * @return array
 */
function snks_wallet_method_xlsx( $user_id, $balance, $withdrawal_settings ) {
	return array(
		'Method'           => 'wallet',
		'Recipient Name'   => $withdrawal_settings['wallet_owner_name'],
		'Recipient Number' => $withdrawal_settings['wallet_number'],
		'Amount'           => (string) $balance,
	);
}

/**
 * Generate or append to an xlsx file with the output data.
 *
 * @param array  $data Array of data to be written to the xlsx file.
 * @param string $withdrawal_method Withdrawal method.
 * @return void
 */
function snks_generate_xlsx( $data, $withdrawal_method ) {
	if ( empty( $data ) || ! is_array( $data ) ) {
		return;
	}
	// Create the withdrawals directory for the current year and month.
	$withdrawals_dir = snks_create_withdrawal_directory();

	// Define the filename for the xlsx file.
	$filename = 'withdrawal_data_' . $withdrawal_method . '_' . gmdate( 'Y-m-d' ) . '.xlsx';

	// Full path to the xlsx file.
	$filepath = $withdrawals_dir . '/' . $filename;

	// Set the header row if creating a new file.
	$headers = array_keys( $data[0] );

	if ( class_exists( 'ANONY_PHPOFFICE_HELP' ) ) {
		return ANONY_PHPOFFICE_HELP::array_to_spreadsheet_append( $data, $headers, 'قائمة السحب', $filepath );
	}
	return false;
}

/**
 * Update processed_for_withdrawal for a list of withdrawal IDs.
 *
 * This function updates the processed_for_withdrawal column to 1
 * for all matching withdrawal IDs in the snks_booking_transactions table.
 *
 * @param int $withdrawal_id Withdrawal ID.
 * @return bool True if the rows were updated successfully, false on failure.
 */
function snks_update_processed_withdrawals( $withdrawal_id ) {
	global $wpdb;

	// Prepare the table name with proper prefix.
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME; // Assuming table is prefixed.

	// Construct the SQL query to update processed_for_withdrawal for the specified IDs.
	$sql = "UPDATE {$table_name}
            SET processed_for_withdrawal = 1
            WHERE id = {$withdrawal_id}";
	//phpcs:disable
	// Execute the query using $wpdb.
	$updated = $wpdb->query( $sql );
	//phpcs:enable

	// Return true if rows were updated, false otherwise.
	return ( false !== $updated );
}

/**
 * Get the latest transaction for a specific user from the snks_booking_transactions table.
 *
 * @param int $user_id The ID of the user for whom to retrieve the latest transaction.
 * @return array|false The latest transaction record as an associative array, or false if none found.
 */
function snks_get_latest_transaction( $user_id ) {
	global $wpdb;

	// Sanitize and validate user ID.
	$user_id = absint( $user_id );

	// Bail early if user_id is not valid.
	if ( 0 === $user_id ) {
		return false;
	}

	// Prepare the table name (with proper prefix).
	$table_name = $wpdb->prefix . TRNS_TABLE_NAME;
	//phpcs:disable
	// Prepare the SQL query to get the latest record for the user, ordered by transaction_time.
	$sql = $wpdb->prepare(
		"SELECT * FROM {$table_name} 
         WHERE user_id = %d 
         ORDER BY transaction_time DESC 
         LIMIT 1",
		$user_id
	);

	// Execute the query and get the latest transaction record.
	$latest_transaction = $wpdb->get_row( $sql, ARRAY_A ); // Fetch as an associative array.
	//phpcs:enable
	// Return the result (or false if no record found).
	return $latest_transaction ? $latest_transaction : false;
}

/**
 * Get the latest transaction amount
 *
 * @param int $user_id The ID of the user for whom to retrieve the latest transaction.
 * @return int
 */
function snks_get_latest_transaction_amount( $user_id ) {
	$latest = snks_get_latest_transaction( $user_id );
	if ( is_array( $latest ) ) {
		return $latest['amount'];
	}
	return 0;
}
