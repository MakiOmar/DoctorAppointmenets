<?php
/**
 * Transactions page
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Content for the Withdraw Transactions admin page.
 */
function withdraw_transactions_admin_page_content() {
	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle pagination and limits.
	$limit  = 50;
	$page   = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;
	$offset = ( $page - 1 ) * $limit;

	// Set default date or use submitted date.
	$date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : gmdate( 'Y-m-d' );

	// Fetch the withdrawal transactions.
	$transactions = get_withdraw_transactions( $date, $limit, $offset );

	// Get total number of transactions for pagination.
	global $wpdb;
	$total_transactions = $wpdb->get_var(
		$wpdb->prepare(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}booking_transactions 
            WHERE transaction_type = 'withdraw' 
            AND DATE(transaction_time) = %s
            ",
			$date . ' 00:00:00'
		)
	);
	$total_pages        = ceil( $total_transactions / $limit );

	?>
		<style>
		/* Form Styling */
		.withdraw-form {
			margin-bottom: 20px;
			text-align: center;
		}

		.withdraw-form input[type="date"] {
			padding: 10px;
			width: 200px;
			font-size: 16px;
			margin-right: 10px;
		}

		.withdraw-form .submit-btn {
			padding: 10px 20px;
			background-color: #28a745; /* Green background */
			color: white;
			border: none;
			cursor: pointer;
			font-size: 16px;
			width: 100%; /* Full-width button */
			max-width: 300px;
		}

		.withdraw-form .submit-btn:hover {
			background-color: #218838; /* Darker green on hover */
		}

		/* Table Styling */
		.withdraw-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}

		.withdraw-table th {
			background-color: #28a745; /* Green header */
			color: white;
			padding: 10px;
		}

		.withdraw-table td {
			padding: 10px;
			border: 1px solid #ddd;
			text-align: center;
		}

		.withdraw-table tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		/* Pagination Styling */
		.pagination {
			text-align: center;
			margin-top: 20px;
		}

		.pagination a {
			display: inline-block;
			padding: 10px 15px;
			margin: 0 5px;
			border: 1px solid #ddd;
			text-decoration: none;
			color: #007bff;
		}

		.pagination a.active {
			background-color: #007bff;
			color: white;
			border-color: #007bff;
		}

		.pagination a:hover {
			background-color: #0056b3;
			color: white;
		}

	</style>
	<div class="wrap">
		<h1><?php esc_html_e( 'Withdraw Transactions', 'your-textdomain' ); ?></h1>
		
		<!-- Date Filter Form -->
		<form method="GET" action=""  class="withdraw-form">
			<input type="hidden" name="page" value="withdraw-transactions">
			<label for="date"><?php esc_html_e( 'Select a Date:', 'your-textdomain' ); ?></label>
			<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>" required>
			<button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Submit', 'your-textdomain' ); ?></button>
		</form>

		<!-- Display Withdraw Transactions Table -->
		<?php if ( ! empty( $transactions ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User ID', 'your-textdomain' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'your-textdomain' ); ?></th>
						<th><?php esc_html_e( 'Transaction Time', 'your-textdomain' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $transactions as $transaction ) : ?>
						<tr>
							<td><?php echo esc_html( $transaction['user_id'] ); ?></td>
							<td><?php echo esc_html( number_format( $transaction['amount'], 2 ) ); ?></td>
							<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $transaction['transaction_time'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No withdrawal transactions found for the selected date.', 'your-textdomain' ); ?></p>
		<?php endif; ?>

		<!-- Pagination Links -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php
					$current_url = add_query_arg( null, null ); // Get current URL for pagination links.
					for ( $i = 1; $i <= $total_pages; $i++ ) :
						?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $i, $current_url ) ); ?>" class="page-numbers <?php echo $i === $page ? 'current' : ''; ?>">
							<?php echo esc_html( $i ); ?>
						</a>
					<?php endfor; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Register a custom admin page for withdrawal transactions.
 */
function register_withdraw_transactions_page() {
	add_menu_page(
		esc_html__( 'Withdraw Transactions', 'your-textdomain' ), // Page title.
		esc_html__( 'Withdraw Transactions', 'your-textdomain' ), // Menu title.
		'manage_options',                                 // Capability required to view this page.
		'withdraw-transactions',                          // Menu slug.
		'withdraw_transactions_admin_page_content',       // Callback function to display content.
		'dashicons-money',                                // Icon for the menu.
		25                                                // Position in the admin menu.
	);
}
add_action( 'admin_menu', 'register_withdraw_transactions_page' );
