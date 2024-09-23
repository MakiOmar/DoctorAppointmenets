<?php
/**
 * Accounting
 *
 * @package Nafea
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

defined( 'ABSPATH' ) || die();

if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();

define( 'SNKS_DEV_MODE', true );
/**
 * On order complete the system adds the doctor share to his wallet, if the order is created at an hour greater than 9 am and less than 12 am
 * Else it will add it to a temporary wallet. Then the following cron job should run and check if the current hour greater than 9 am and less than 12 am and the this day reports are generated,
 * Then transfere from temp wallet to main wallet.
 *
 * @param [type] $schedules
 * @return void
 */
/**
 * Add a custom schedule for every 15 minutes.
 *
 * @param array $schedules Existing schedules.
 * @return array Modified schedules.
 */
function snks_add_cron_schedule( $schedules ) {
	$schedules['every_15_minutes'] = array(
		'interval' => 15 * 60, // 15 minutes in seconds.
		'display'  => __( 'Every 15 Minutes' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'snks_add_cron_schedule' );

/**
 * Schedule the custom cron job if it's not already scheduled.
 */
function snks_schedule_temp_wallet_cron() {
	if ( ! wp_next_scheduled( 'snks_process_temp_wallet_event' ) ) {
		wp_schedule_event( time(), 'every_15_minutes', 'snks_process_temp_wallet_event' );
	}
}
add_action( 'wp', 'snks_schedule_temp_wallet_cron' );

/**
 * Process users with a temp_wallet balance, credit their wallet, and reset the temp_wallet to 0.
 * This job will only run between 9 AM and 12 AM based on WordPress timezone.
 */
function snks_process_temp_wallet() {
	// Get the current time based on WordPress timezone.
	$current_hour = current_time( 'H' ); // 'H' gives the hour in 24-hour format (00-23).
	$reports      = false; // Check if reports are generated.
	// Only proceed if the time is between 9 AM and 12 AM (midnight).
	if ( ! SNKS_DEV_MODE && ( $current_hour < 9 || $current_hour >= 24 || ! $reports ) ) {
		return; // Exit early if current time is not between 9 AM and 12 AM.
	}

	global $wpdb;

	// Number of users to process per run.
	$limit = 50;

	// Get the cached offset, or default to 0.
	$offset = (int) get_transient( 'snks_temp_wallet_offset' );
    //phpcs:disable.
	// Query users with `temp_wallet` > 0.
	$users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT user_id, meta_value AS temp_wallet
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'temp_wallet' AND meta_value > %d
            LIMIT %d OFFSET %d",
			0,
			$limit,
			$offset
		)
	);
    //phpcs:enable.
	// If no users are found, reset the offset and exit.
	if ( empty( $users ) ) {
		delete_transient( 'snks_temp_wallet_offset' ); // Reset offset if no users are found.
		return;
	}

	foreach ( $users as $user ) {
		$user_id             = $user->user_id;
		$temp_wallet_balance = (float) $user->temp_wallet;

		// Credit the user's wallet.
		snks_wallet_credit( $user_id, $temp_wallet_balance, 'رصيدك بعد السحب' );

		// Reset the temp_wallet to 0.
		update_user_meta( $user_id, 'temp_wallet', 0 );
	}

	// Update the offset for the next run.
	$new_offset = $offset + $limit;
	set_transient( 'snks_temp_wallet_offset', $new_offset, 15 * MINUTE_IN_SECONDS );
}
add_action( 'snks_process_temp_wallet_event', 'snks_process_temp_wallet' );


/**
 * Schedule the custom cron job if it's not already scheduled.
 */
function snks_schedule_withdrawal_cron() {
	if ( ! wp_next_scheduled( 'snks_process_withdrawal_event' ) ) {
		wp_schedule_event( time(), 'every_15_minutes', 'snks_process_withdrawal_event' );
	}
}
add_action( 'wp', 'snks_schedule_withdrawal_cron' );

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
 * Process withdrawals for doctors based on their withdrawal settings.
 * This job will only run between 12 AM and 9 AM, querying 50 users at a time.
 */
function snks_process_withdrawal() {
	// Get the current time based on WordPress timezone.
	$current_hour = current_time( 'H' ); // 'H' gives the hour in 24-hour format (00-23).

	// Only proceed if the time is between 12 AM and 9 AM.
	if ( ! SNKS_DEV_MODE && ( $current_hour < 0 || $current_hour > 9 ) ) {
		return; // Exit if current time is not between 12 AM and 9 AM.
	}

	// Get the current day of the week (1 = Monday, 7 = Sunday) and the day of the month.
	$current_day_of_week  = current_time( 'w' ); // 0 for Sunday through 6 for Saturday.
	$current_day_of_month = current_time( 'j' ); // Day of the month (1-31).

	global $wpdb;

	// Number of users to process per run.
	$limit = 50;

	// Get the cached offset, or default to 0.
	$offset = (int) get_transient( 'snks_withdrawal_offset' );

	// Query users with the 'doctor' role.
	$users = get_users(
		array(
			'role'   => 'doctor',
			'number' => $limit,
			'offset' => $offset,
			'fields' => array( 'ID' ),
		)
	);

	// If no users are found, reset the offset and exit.
	if ( empty( $users ) ) {
		delete_transient( 'snks_withdrawal_offset' ); // Reset offset if no users are found.
		return;
	}

	$output = array(); // Output array to store eligible users for withdrawal.

	foreach ( $users as $user ) {
		$user_id = $user->ID;

		// Get the user's wallet balance.
		$wallet_balance = snks_get_wallet_balance( $user_id );
		// Skip this user if their wallet balance is 0.
		if ( $wallet_balance <= 0 ) {
			continue;
		}
		$withdrawal_settings = get_user_meta( $user_id, 'withdrawal_settings', true );
		if ( empty( $withdrawal_settings ) ) {
			continue;
		}
		$withdrawal_option = $withdrawal_settings['withdrawal_option'];
		// Check the withdrawal conditions based on the user's withdrawal option.
		if ( 'daily_withdrawal' === $withdrawal_option ||
			( 'weekly_withdrawal' === $withdrawal_option && 3 === absint( $current_day_of_week ) ) || // 3 = Wednesday.
			( 'monthly_withdrawal' === $withdrawal_option && 1 === absint( $current_day_of_month ) )
		) {
			// Get the withdrawal method and its corresponding fields.
			$withdrawal_method = $withdrawal_settings['withdrawal_method'];
			$fields            = array();

			// Fetch corresponding fields for the method (depending on the method type).
			if ( 'bank_account' === $withdrawal_method ) {
				$fields = array(
					'account_holder_name' => $withdrawal_settings['account_holder_name'],
					'bank_name'           => $withdrawal_settings['bank_name'],
					'branch'              => $withdrawal_settings['branch'],
					'account_number'      => $withdrawal_settings['account_number'],
					'iban_number'         => $withdrawal_settings['iban_number'],
				);
			} elseif ( 'meza_card' === $withdrawal_method ) {
				$fields = array(
					'card_holder_name' => $withdrawal_settings['card_holder_name'],
					'meza_bank_name'   => $withdrawal_settings['meza_bank_name'],
					'meza_card_number' => $withdrawal_settings['meza_card_number'],
				);
			} elseif ( 'wallet' === $withdrawal_method ) {
				$fields = array(
					'wallet_holder_name' => $withdrawal_settings['wallet_holder_name'],
					'wallet_number'      => $withdrawal_settings['wallet_number'],
				);
			}

			// Add the user's data to the output array.
			$output[] = array(
				'user_id'           => $user_id,
				'withdrawal_option' => $withdrawal_option,
				'withdrawal_method' => $withdrawal_method,
				'wallet_balance'    => $wallet_balance,
				'fields'            => $fields,
			);
		}
	}

	// Update the offset for the next run.
	$new_offset = $offset + $limit;
	set_transient( 'snks_withdrawal_offset', $new_offset, 15 * MINUTE_IN_SECONDS );
	// Create the xlsx file and write the data to it.
	if ( ! empty( $output ) ) {
		snks_generate_xlsx( $output );
	}
}
add_action( 'snks_process_withdrawal_event', 'snks_process_withdrawal' );
add_action( 'template_redirect', 'snks_process_withdrawal' );

/**
 * Generate or append to an xlsx file with the output data.
 *
 * @param array $data Array of data to be written to the xlsx file.
 * @return string
 */
function snks_generate_xlsx( $data ) {
	// Create the withdrawals directory for the current year and month.
	$withdrawals_dir = snks_create_withdrawal_directory();

	// Define the filename for the xlsx file.
	$filename = 'withdrawal_data_' . current_time( 'Y-m-d_H-i-s' ) . '.xlsx';

	// Full path to the xlsx file.
	$filepath = $withdrawals_dir . '/' . $filename;

	// Check if the file exists, and load it if it does, otherwise create a new one.
	if ( file_exists( $filepath ) ) {
		// Load the existing spreadsheet.
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $filepath );
		$sheet       = $spreadsheet->getActiveSheet();

		// Find the last row to append new data.
		$last_row = $sheet->getHighestRow(); // Get the last row number.
	} else {
		// Create a new Spreadsheet object if the file doesn't exist.
		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();

		// Set the header row if creating a new file.
		$headers = array( 'User ID', 'Withdrawal Option', 'Withdrawal Method', 'Wallet Balance', 'Fields' );
		$sheet->fromArray( $headers, null, 'A1' );
		$last_row = 1; // Start after the header row for new file.
	}

	// Process and clean the data before writing to the spreadsheet.
	$cleaned_data = array();

	foreach ( $data as $entry ) {
		// If 'withdrawal_method' contains an array, convert it to a string.
		if ( is_array( $entry['withdrawal_method'] ) ) {
			$entry['withdrawal_method'] = implode( ', ', $entry['withdrawal_method'] );
		}

		// Handle the fields (which can also be an array).
		if ( isset( $entry['fields'] ) && is_array( $entry['fields'] ) ) {
			$entry['fields'] = implode( ', ', $entry['fields'] );
		}

		// Add the cleaned entry to the cleaned data array.
		$cleaned_data[] = array(
			$entry['user_id'],
			$entry['withdrawal_option'],
			$entry['withdrawal_method'], // Now a string.
			$entry['wallet_balance'],
			isset( $entry['fields'] ) ? $entry['fields'] : '',
		);
	}
	// Append new cleaned data starting from the next row after the last row.
	$sheet->fromArray( $cleaned_data, null, 'A' . ( $last_row + 1 ) );

	// Create a new Xlsx writer and save the updated file.
	$writer = new Xlsx( $spreadsheet );
	$writer->save( $filepath );
	// Output the file path for further use (if needed).
	return $filepath;
}
