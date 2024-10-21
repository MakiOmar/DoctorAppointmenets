<?php
/**
 * Reports
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! isset( $dv ) ) {
	return;
}

define( 'ANONY_ADMIN_FEES_FACTOR', 0.9 );

//phpcs:disable
/**
 * Get vendor sales orders.
 *
 * @param string $status          The order status (e.g., 'wc-completed').
 * @param int    $current_user_id The current user ID. Defaults to false.
 *
 * @return array The list of orders.
 */
function anony_get_vendor_sales_orders( $status, $current_user_id = false ) {
	global $wpdb;

	if ( ! $current_user_id ) {
		$current_user_id = get_current_user_id();
	}

	$query = "
		SELECT posts.ID
		FROM {$wpdb->posts} AS posts
		INNER JOIN {$wpdb->postmeta} AS meta
			ON posts.ID = meta.post_id
		WHERE posts.post_type = 'shop_order'
			AND posts.post_status = %s
			AND meta.meta_key = 'product_author_ids'
			AND meta.meta_value LIKE %s
	";

	$order_ids = $wpdb->get_col( $wpdb->prepare( $query, $status, '%"' . $current_user_id . '"%' ) );

	$orders = array();
	foreach ( $order_ids as $order_id ) {
		$orders[] = wc_get_order( $order_id );
	}

	return $orders;
}

/**
 * Display sales report shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string The sales report table HTML.
 */
function display_sales_report( $atts ) {
	$user_id = get_current_user_id();

	// Retrieve all products for the current user.
	$products = wc_get_products(
		array(
			'limit'  => -1,
			'status' => 'publish',
			'author' => $user_id,
		)
	);

	$sales_data = array();

	// Loop through each product and get the sales data.
	foreach ( $products as $product ) {
		$product_id        = $product->get_id();
		$product_name      = $product->get_name();
		$product_permalink = $product->get_permalink();

		$quantity_sold = 0;
		$total_sales   = 0;
		$pending_sales = 0; // Initialize pending sales.

		$completed_orders  = anony_get_vendor_sales_orders( 'wc-completed' );
		$processing_orders = anony_get_vendor_sales_orders( 'wc-processing' );

		$orders = array_merge( $completed_orders, $processing_orders );

		// Loop through each order and get the sales data for the product.
		foreach ( $orders as $order ) {
			$items = $order->get_items( 'line_item' );

			foreach ( $items as $item ) {
				$product_ordered = $item->get_product();

				if ( $product_ordered && $product_ordered->get_id() == $product_id ) {
					$quantity_sold += $item->get_quantity();

					// Check if the order is pending.
					if ( 'completed' !== $order->get_status() ) {
						$pending_sales += $item->get_total();
					} else {
						$total_sales += $item->get_total();
					}
				}
			}
		}

		// Subtract 10% from the total sales (apply admin fees).
		$total_sales   *= ANONY_ADMIN_FEES_FACTOR;
		$pending_sales *= ANONY_ADMIN_FEES_FACTOR;

		// Get the current currency symbol.
		$currency_symbol = get_woocommerce_currency_symbol();

		// Add the sales data to the sales_data array.
		$sales_data[] = array(
			'product_name'      => $product_name,
			'product_permalink' => $product_permalink,
			'quantity_sold'     => $quantity_sold,
			'pending_sales'     => $pending_sales,
			'total_sales'       => $total_sales,
			'currency_symbol'   => $currency_symbol,
		);
	}

	// Sort the sales data array by total sales in descending order.
	usort(
		$sales_data,
		function ( $a, $b ) {
			return $b['total_sales'] - $a['total_sales'];
		}
	);

	// Display the sales data as a table.
	$output  = '<table>';
	$output .= '<tr><th>اسم المنتج</th><th>الكمية المباعة</th><th>المبيعات المعلقة</th><th>إجمالي المبيعات</th></tr>';
	foreach ( $sales_data as $product ) {
		$output .= sprintf(
			'<tr><td><a href="%1$s">%2$s</a></td><td>%3$d</td><td>%4$s %5$s</td><td>%6$s %7$s</td></tr>',
			esc_url( $product['product_permalink'] ),
			esc_html( $product['product_name'] ),
			absint( $product['quantity_sold'] ),
			number_format( $product['pending_sales'], 2 ),
			esc_html( $product['currency_symbol'] ),
			number_format( $product['total_sales'], 2 ),
			esc_html( $product['currency_symbol'] )
		);
	}
	$output .= '</table>';

	return $output;
}

add_shortcode( 'sales_report', 'display_sales_report' );

add_shortcode( 'Current_user_sales_report', 'display_sales_report' );
add_shortcode(
	'goback',
	function () {
		ob_start(); ?>
	<a id="backButton" href="#">Go Back</a>

	<script>
		const previousPage = document.getElementById('backButton');

		previousPage.setAttribute("href", document.referrer) ;
	</script>
		<?php
		return ob_get_clean();
	}
);

// Display available withdrawal credit on user edit screen.
add_action( 'show_user_profile', 'anony_display_available_withdrawal_credit' );
add_action( 'edit_user_profile', 'anony_display_available_withdrawal_credit' );
/**
 * Display available withdrawal credit for a user.
 *
 * @param WP_User $user The user object.
 */
function anony_display_available_withdrawal_credit( $user ) {
	if ( current_user_can( 'manage_options' ) ) {
		$available_credit = anony_available_withdrawal_credit( $user->ID );
		?>
		<h3><?php esc_html_e( 'Available Withdrawal Credit', 'textdomain' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="available_withdrawal_credit"><?php esc_html_e( 'Credit', 'textdomain' ); ?></label></th>
				<td>
					<input type="text" name="available_withdrawal_credit" id="available_withdrawal_credit" value="<?php echo esc_attr( get_woocommerce_currency_symbol() . $available_credit ); ?>" class="regular-text" readonly>
				</td>
			</tr>
		</table>
		<?php
	}
}

/**
 * Calculate available withdrawal credit for a user.
 *
 * @param int|false $user_id The user ID. Default is false.
 *
 * @return float The available credit.
 */
function anony_available_withdrawal_credit( $user_id = false ) {
	$completed_orders = anony_get_vendor_sales_orders( 'wc-completed', $user_id );
	$credit           = 0;

	if ( ! empty( $completed_orders ) ) {
		foreach ( $completed_orders as $completed_order ) {
			$order_payment_method = $completed_order->get_payment_method();

			if ( 'cod' === $order_payment_method ) {
				$credit += $completed_order->get_total();
			}
		}
	}

	$credit *= ANONY_ADMIN_FEES_FACTOR;

	return $credit;
}

/**
 * Display sales orders for the current user.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string The sales order report in HTML format.
 */
function anony_current_user_sales_orders_vertical( $atts ) {
	$current_user_id = get_current_user_id();

	$withdrawal_amount = anony_available_withdrawal_credit();

	$withdrawal_html = '';
	if ( 0 != $withdrawal_amount ) {
		ob_start();
		?>
		<h3>رصيد السحب المتاح: <span style="color:#3A9A38;font-weight:bold"><?php echo wc_price( $withdrawal_amount ); ?></span></h3>
		<?php echo do_shortcode( '[jet_fb_form form_id="4471" submit_type="reload" required_mark="*" fields_layout="column" enable_progress="" fields_label_tag="div" load_nonce="render" use_csrf=""]' ); ?>
		<?php
		$withdrawal_html = ob_get_clean();
	}

	// Get completed and processing orders.
	$completed_orders  = anony_get_vendor_sales_orders( 'wc-completed' );
	$processing_orders = anony_get_vendor_sales_orders( 'wc-processing' );

	// Generate completed orders table.
	$completed_orders_tables = '<style>
		.anony-sales-order-tab-links {
			list-style: none;
			display: flex;
			width: 100%;
			padding: 0;
			margin: 0;
		}
		.anony-sales-order-tab-links li {
			display: inline-block;
			width: 50%;
		}
		.anony-sales-order-tab-links li a {
			border: 1px solid #ccc;
			padding: 10px;
			display: block;
		}
		.anony-sales-order-tab-pane table td {
			width: 50%;
		}
		.action-buttons {
			display: flex;
			flex-wrap: wrap;
			justify-content: space-between;
			width: 100%!important;
		}
		.set-as-completed {
			padding: 10px;
			border-radius: 50px;
		}
		.anony-sales-order-tab-links li.active a,
		.set-as-completed {
			background-color: green;
			color: #fff;
		}
	</style>';

	$completed_orders_table = '';
	if ( ! empty( $completed_orders ) ) {
		foreach ( $completed_orders as $completed_order ) {
			$order_id                   = $completed_order->get_id();
			$order_date                 = $completed_order->get_date_created()->format( 'M d, Y H:i:s' );
			$order_status               = ucfirst( str_replace( 'wc-', '', $completed_order->get_status() ) );
			$order_payment_method_title = $completed_order->get_payment_method_title();
			$order_item_count           = $completed_order->get_item_count();
			$order_total                = $completed_order->get_total() * ANONY_ADMIN_FEES_FACTOR;
			$order_view_url             = esc_url( $completed_order->get_view_order_url() );

			$completed_orders_table .= '<table class="completed-order">';
			$completed_orders_table .= '<tr><th>طلب #:</th><td>' . esc_html( $order_id ) . '</td></tr>';
			$completed_orders_table .= '<tr><th>تاريخ:</th><td>' . esc_html( $order_date ) . '</td></tr>';
			$completed_orders_table .= '<tr><th>حالة:</th><td>' . esc_html( $order_status ) . '</td></tr>';
			$completed_orders_table .= '<tr><th>طريقة الدفع:</th><td>' . esc_html( $order_payment_method_title ) . '</td></tr>';
			$completed_orders_table .= '<tr><th>أغراض:</th><td>' . esc_html( $order_item_count ) . '</td></tr>';
			$completed_orders_table .= '<tr><th>المجموع:</th><td>' . esc_html( $order_total ) . '</td></tr>';
			$completed_orders_table .= '<tr><th></th><td><a href="' . esc_url( site_url( '/order-details/?order_id=' . $order_id ) ) . '" class="button">' . esc_html__( 'View', 'text-domain' ) . '</a></td></tr>';
			$completed_orders_table .= '</table>';
		}

		$completed_orders_tables .= $completed_orders_table;
	} else {
		$completed_orders_tables .= '<table class="completed-order">';
		$completed_orders_tables .= '<tr><td>' . esc_html__( 'لم يتم الانتهاء من الطلبات بعد ...', 'text-domain' ) . '</td></tr>';
		$completed_orders_tables .= '</table>';
	}

	// Generate processing orders table.
	$processing_orders_table = '';
	if ( ! empty( $processing_orders ) ) {
		foreach ( $processing_orders as $processing_order ) {
			$order_id                   = $processing_order->get_id();
			$order_date                 = $processing_order->get_date_created()->format( 'M d, Y H:i:s' );
			$order_status               = ucfirst( str_replace( 'wc-', '', $processing_order->get_status() ) );
			$order_payment_method_title = $processing_order->get_payment_method_title();
			$order_item_count           = $processing_order->get_item_count();
			$order_total                = $processing_order->get_total() * ANONY_ADMIN_FEES_FACTOR;
			$order_view_url             = esc_url( $processing_order->get_view_order_url() );
			$as_completed               = '';

			if ( 'cod' === $processing_order->get_payment_method() ) {
				$as_completed = ' <a href="#" data-id="' . esc_attr( $order_id ) . '" class="set-as-completed">' . esc_html__( 'Mark as Completed', 'text-domain' ) . '</a>';
			}

			$processing_orders_table .= '<table class="processing-order">';
			$processing_orders_table .= '<tr><th>طلب #:</th><td>' . esc_html( $order_id ) . '</td></tr>';
			$processing_orders_table .= '<tr><th>تاريخ:</th><td>' . esc_html( $order_date ) . '</td></tr>';
			$processing_orders_table .= '<tr><th>حالة:</th><td>' . esc_html( $order_status ) . '</td></tr>';
			$processing_orders_table .= '<tr><th>طريقة الدفع:</th><td>' . esc_html( $order_payment_method_title ) . '</td></tr>';
			$processing_orders_table .= '<tr><th>أغراض:</th><td>' . esc_html( $order_item_count ) . '</td></tr>';
			$processing_orders_table .= '<tr><th>المجموع:</th><td>' . esc_html( $order_total ) . '</td></tr>';
			$processing_orders_table .= '<tr><th></th><td class="action-buttons"><a href="' . esc_url( site_url( '/order-details/?order_id=' . $order_id ) ) . '" class="button">' . esc_html__( 'View', 'text-domain' ) . '</a>' . $as_completed . '</td></tr>';
			$processing_orders_table .= '</table>';
		}
	} else {
		$processing_orders_table .= '<table class="processing-order">';
		$processing_orders_table .= '<tr><td>' . esc_html__( 'No processing orders yet...', 'text-domain' ) . '</td></tr>';
		$processing_orders_table .= '</table>';
	}

	// Generate tabs to separate completed and processing orders.
	$tabs  = '<div class="anony-sales-order-tabs">';
	$tabs .= '<ul class="anony-sales-order-tab-links">';
	$tabs .= '<li class="anony-sales-order-tab-link"><a href="#completed-orders">' . esc_html__( 'Completed Orders', 'text-domain' ) . '</a></li>';
	$tabs .= '<li class="anony-sales-order-tab-link"><a href="#processing-orders">' . esc_html__( 'Processing Orders', 'text-domain' ) . '</a></li>';
	$tabs .= '</ul>';
	$tabs .= '<div class="anony-sales-order-tab-content">';
	$tabs .= '<div id="completed-orders" class="anony-sales-order-tab-pane">' . $completed_orders_tables . '</div>';
	$tabs .= '<div id="processing-orders" class="anony-sales-order-tab-pane">' . $processing_orders_table . '</div>';
	$tabs .= '</div>';
	$tabs .= '</div>';

	// Return the tabs.
	return $withdrawal_html . $tabs;
}

add_shortcode( 'user_sales_orders', 'anony_current_user_sales_orders_vertical' );

add_action( 'wp_ajax_update_order_status', 'update_order_status_callback' );
/**
 * Update attendance
 *
 * @return void
 */
function update_order_status_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
	// Verify the nonce.
	if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'update_order_status_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$order_id    = absint( $_request['orderID'] );
	$orderDetail = new WC_Order( $order_id );
	$updated     = $orderDetail->update_status( 'completed' );
	wp_send_json(
		array(
			'resp' => $updated,
		)
	);
	die;
}

add_action(
	'wp_footer',
	function () {
		if ( strpos( $_SERVER['REQUEST_URI'], 'orders-report' ) === false ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function($) {
				$('body').on( 'click', '.set-as-completed', function(e){
					e.preventDefault();
					var orderID = $(this).data('id');
						// Perform nonce check.
					var nonce = '<?php echo esc_html( wp_create_nonce( 'update_order_status_nonce' ) ); ?>';
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: {
							orderID: orderID,
							action    : 'update_order_status',
						},
						success: function(response) {
							if (  response.resp ) {
								location.reload();
							}

						},
						error: function(xhr, status, error) {
							console.error('Error:', error);
						}
					});
				} );
				// Hide all tab content panes except the first one.
				$('.anony-sales-order-tab-pane:not(:first)').hide();
			
				// Add active class to the first tab link.
				$('.anony-sales-order-tab-link:first').addClass('active');
			
				// Switch tabs when a tab link is clicked.
				$('.anony-sales-order-tab-link').click(function(e) {
					e.preventDefault();
			
					// Remove active class from all tab links.
					$('.anony-sales-order-tab-link').removeClass('active');
			
					// Add active class to the clicked tab link.
					$(this).addClass('active');
			
					// Hide all tab content panes.
					$('.anony-sales-order-tab-pane').hide();
			
					// Show the content pane corresponding to the clicked tab link.
					$($(this).children('a').attr('href')).show();
				});
			});
		</script>
		<?php
	}
);

/**
 * Get order details.
 *
 * @param int $order_id The order ID.
 *
 * @return array|false The order details or false if the order doesn't exist.
 */
function anony_get_order_details( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}

	$items = array();
	/**
	 * @var WC_Order_Item_Product $item
	 */
	foreach ( $order->get_items() as $item_id => $item ) {
		$product = $item->get_product();
		$items[] = array(
			'name'        => $item->get_name(),
			'quantity'    => $item->get_quantity(),
			'price'       => $item->get_total(),
			'product_id'  => $product ? $product->get_id() : '',
			'product_sku' => $product ? $product->get_sku() : '',
		);
	}

	$billing = array();
	if ( $order->get_billing_first_name() ) {
		$billing['first_name'] = $order->get_billing_first_name();
	}
	if ( $order->get_billing_last_name() ) {
		$billing['last_name'] = $order->get_billing_last_name();
	}
	if ( $order->get_billing_email() ) {
		$billing['email'] = $order->get_billing_email();
	}
	if ( $order->get_billing_phone() ) {
		$billing['phone'] = $order->get_billing_phone();
	}

	$shipping = array();
	if ( $order->get_shipping_first_name() ) {
		$shipping['first_name'] = $order->get_shipping_first_name();
	}
	if ( $order->get_shipping_last_name() ) {
		$shipping['last_name'] = $order->get_shipping_last_name();
	}
	if ( $order->get_shipping_address_1() ) {
		$shipping['address_1'] = $order->get_shipping_address_1();
	}
	if ( $order->get_shipping_address_2() ) {
		$shipping['address_2'] = $order->get_shipping_address_2();
	}
	if ( $order->get_shipping_city() ) {
		$shipping['city'] = $order->get_shipping_city();
	}
	if ( $order->get_shipping_state() ) {
		$shipping['state'] = $order->get_shipping_state();
	}
	if ( $order->get_shipping_postcode() ) {
		$shipping['postcode'] = $order->get_shipping_postcode();
	}
	if ( $order->get_shipping_country() ) {
		$shipping['country'] = $order->get_shipping_country();
	}

	$order_data = array(
		'order_id'             => $order->get_id(),
		'order_number'         => $order->get_order_number(),
		'status'               => $order->get_status(),
		'date_created'         => $order->get_date_created(),
		'billing'              => $billing,
		'shipping'             => $shipping,
		'items'                => $items,
		'total'                => $order->get_total(),
		'payment_method_title' => $order->get_payment_method_title(),
	);

	return $order_data;
}


add_shortcode(
	'singleorder',
	function () {
		ob_start();

		// Sanitize the order ID from the URL.
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

		// Get the order details.
		$order_data = anony_get_order_details( $order_id );

		if ( $order_data ) {
			?>
			<div class="table-responsive">
				<table class="table">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'رقم الطلب', 'textdomain' ); ?></th>
							<td><?php echo esc_html( '#' . $order_data['order_number'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'الحالة', 'textdomain' ); ?></th>
							<td><?php echo esc_html( $order_data['status'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'طريقة الدفع', 'textdomain' ); ?></th>
							<td><?php echo esc_html( $order_data['payment_method_title'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'تاريخ انشاء الطلب', 'textdomain' ); ?></th>
							<td><?php echo esc_html( $order_data['date_created']->format( 'Y-m-d H:i:s' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'بيانات الفاتورة', 'textdomain' ); ?></th>
							<td>
								<?php echo esc_html( $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] ); ?><br>
								<?php echo esc_html( $order_data['billing']['email'] ); ?><br>
								<?php echo esc_html( $order_data['billing']['phone'] ); ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'بيانات الشحن', 'textdomain' ); ?></th>
							<td>
								<?php echo esc_html( $order_data['shipping']['first_name'] . ' ' . $order_data['shipping']['last_name'] ); ?><br>
								<?php echo esc_html( $order_data['shipping']['address_1'] ); ?><br>
								<?php echo esc_html( $order_data['shipping']['address_2'] ); ?><br>
								<?php echo esc_html( $order_data['shipping']['city'] . ', ' . $order_data['shipping']['state'] . ' ' . $order_data['shipping']['postcode'] ); ?><br>
								<?php echo esc_html( $order_data['shipping']['country'] ); ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'المنتجات', 'textdomain' ); ?></th>
							<td>
								<table class="table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'اسم المنتج', 'textdomain' ); ?></th>
											<th><?php esc_html_e( 'الكمية', 'textdomain' ); ?></th>
											<th><?php esc_html_e( 'السعر', 'textdomain' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $order_data['items'] as $item ) { ?>
											<tr>
												<td><a href="<?php echo esc_url( get_permalink( $item['product_id'] ) ); ?>"><?php echo esc_html( $item['name'] ); ?></a></td>
												<td><?php echo esc_html( $item['quantity'] ); ?></td>
												<td><?php echo wc_price( $item['price'] ); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'الاجمالي', 'textdomain' ); ?></th>
							<td><?php echo wc_price( $order_data['total'] ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
		}

		return ob_get_clean();
	}
);


add_action( 'woocommerce_thankyou', 'save_order_product_author_ids', 10, 1 );
/**
 * Save the product author IDs to the order meta.
 *
 * @param int $order_id The order ID.
 */
function save_order_product_author_ids( $order_id ) {
	$order              = wc_get_order( $order_id );
	$product_author_ids = array();
	$items              = $order->get_items();
	/**
	 * @var WC_Order_Item_Product $item
	 */
	foreach ( $items as $item ) {
		$product = $item->get_product();

		if ( $product ) {
			$product_id        = $product->get_id();
			$product_author_id = get_post_field( 'post_author', $product_id );

			// Add the author ID if it's not already in the array.
			if ( ! in_array( $product_author_id, $product_author_ids, true ) ) {
				$product_author_ids[] = $product_author_id;
			}
		}
	}

	// Update the order meta with the product author IDs.
	if ( ! empty( $product_author_ids ) ) {
		update_post_meta( $order_id, 'product_author_ids', $product_author_ids );
	}
}


add_filter( 'woocommerce_add_to_cart_validation', 'anony_validate_cart_author', 10, 3 );

/**
 * Get the total savings on an order.
 *
 * @param WC_Order $order The order object.
 *
 * @return float The total savings.
 */
function anony_get_order_total_savings( $order ) {
	$total_savings = 0;
	/**
	 * @var WC_Order_Item_Product $item
	 */
	foreach ( $order->get_items() as $item_id => $item ) {
		$product = $item->get_product();

		if ( $product && $product->is_on_sale() ) {
			$regular_price  = $product->get_regular_price( 'edit' );
			$sale_price     = $product->get_sale_price( 'edit' );
			$quantity       = $item->get_quantity();
			$item_savings   = ( $regular_price - $sale_price ) * $quantity;
			$total_savings += $item_savings;
		}
	}

	return $total_savings;
}

/**
 * Function for `woocommerce_checkout_order_processed` action-hook.
 *
 * @param  $order_id
 * @param  $posted_data
 * @param  $order
 *
 * @return void
 */
add_action(
	'woocommerce_checkout_order_processed',
	function ( $order_id, $posted_data, $order ) {
		$order_savings = anony_get_order_total_savings( $order );

		update_post_meta( $order_id, 'anony_order_total_savings', $order_savings );
	},
	10,
	3
);

/**
 * Display custom data after billing details.
 *
 * @param WC_Order $order The order object.
 */
function anony_display_custom_data_after_billing( $order ) {
	$custom_data = $order->get_meta( 'anony_order_total_savings', true );
	$savings     = ! empty( $custom_data ) ? esc_html( $custom_data ) : 0;

	echo '<div class="order_data_column">';
	echo '<p><strong>' . esc_html__( 'Total savings:', 'woocommerce' ) . '</strong> ' . esc_html( $savings ) . '</p>';
	echo '</div>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'anony_display_custom_data_after_billing', 10, 1 );

/**
 * Display total savings after order table.
 *
 * @param WC_Order $order The order object.
 */
function anony_display_order_total_savings( $order ) {
	$total_savings = anony_get_order_total_savings( $order );

	if ( $total_savings > 0 ) {
		echo '<p><strong>' . esc_html__( 'Total Savings:', 'woocommerce' ) . '</strong> ' . wc_price( $total_savings ) . '</p>';
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'anony_display_order_total_savings', 10, 1 );

/**
 * Display total savings form on the account orders endpoint.
 */
function anony_display_total_savings_form() {
	if ( is_wc_endpoint_url( 'orders' ) ) {
		$selected_savings_month = isset( $_POST['savings_month'] ) ? sanitize_text_field( wp_unslash( $_POST['savings_month'] ) ) : false;
		$selected_savings_year  = isset( $_POST['savings_year'] ) ? sanitize_text_field( wp_unslash( $_POST['savings_year'] ) ) : false;
		?>
		<form method="post" action="">
			<label for="savings_month"><?php esc_html_e( 'اختر الشهر', 'textdomain' ); ?></label>
			<select name="savings_month" id="savings_month">
				<?php
				for ( $i = 1; $i <= 12; $i++ ) {
					$savings_month = gmdate( 'F', mktime( 0, 0, 0, $i, 1 ) );
					echo '<option value="' . esc_attr( $savings_month ) . '" ' . selected( $selected_savings_month, $savings_month, false ) . '>' . esc_html( $savings_month ) . '</option>';
				}
				?>
			</select>
			<label for="savings_year"><?php esc_html_e( 'اختر السنة', 'textdomain' ); ?></label>
			<select name="savings_year" id="savings_year">
				<?php
				$current_savings_year = gmdate( 'Y' );
				for ( $i = $current_savings_year; $i >= 2020; $i-- ) {
					echo '<option value="' . esc_attr( $i ) . '" ' . selected( $selected_savings_year, $i, false ) . '>' . esc_html( $i ) . '</option>';
				}
				?>
			</select>
			<input type="submit" name="submit" value="<?php esc_attr_e( 'عرض جميع التوفيرات', 'textdomain' ); ?>">
		</form>
		<?php
	}
}
add_action( 'woocommerce_account_orders_endpoint', 'anony_display_total_savings_form', 10 );

add_action( 'woocommerce_account_orders_endpoint', 'anony_display_total_savings_for_customer_orders', 11 );
/**
 * Display total savings for customer orders.
 *
 * @param int|false $user_id The user ID (optional).
 *
 * @return array|null Response data if called via the REST API, otherwise null.
 */
function anony_display_total_savings_for_customer_orders( $user_id = false ) {

	if ( is_wc_endpoint_url( 'orders' ) || strpos( $_SERVER['REQUEST_URI'], 'orders/total-savings' ) !== false ) {

		$customer_id            = $user_id ? intval( $user_id ) : get_current_user_id();
		$total_savings          = 0;
		$selected_savings_month = isset( $_POST['savings_month'] ) ? sanitize_text_field( wp_unslash( $_POST['savings_month'] ) ) : false;
		$selected_savings_year  = isset( $_POST['savings_year'] ) ? sanitize_text_field( wp_unslash( $_POST['savings_year'] ) ) : false;

		$args = array(
			'customer' => $customer_id,
			'status'   => array( 'completed', 'delivered' ),
		);

		$result_msg = esc_html__( 'Total Savings for all orders:', 'woocommerce' );

		if ( $selected_savings_month && $selected_savings_year ) {
			$first_day_of_savings_month = gmdate( 'Y-m-01', strtotime( $selected_savings_month . ' ' . $selected_savings_year ) );
			$last_day_of_savings_month  = gmdate( 'Y-m-t', strtotime( $first_day_of_savings_month ) );

			$args['date_created'] = $first_day_of_savings_month . '...' . $last_day_of_savings_month;
			// Translators: month, year.
			$result_msg = sprintf( esc_html__( 'Total Savings for all orders of %1$s %2$s is:', 'woocommerce' ), esc_html( $selected_savings_month ), esc_html( $selected_savings_year ) );
		}

		$orders = wc_get_orders( $args );

		if ( strpos( $_SERVER['REQUEST_URI'], 'orders/total-savings' ) !== false && ( ! $orders || empty( $orders ) ) ) {
			$headers  = getallheaders();
			$app_lang = ! empty( $headers['app_lang'] ) ? $headers['app_lang'] : 'ar';
			$response = array(
				'http_code' => '400',
				'status'    => 'error',
				'message'   => '',
				'language'  => $app_lang,
				'content'   => array(
					'total_savings' => 0,
					'msg'           => esc_html__( 'No completed or delivered orders found!', 'woocommerce' ),
				),
			);

			return $response;

		} elseif ( strpos( $_SERVER['REQUEST_URI'], 'orders/total-savings' ) === false && ( ! $orders || empty( $orders ) ) ) {
			echo esc_html__( 'No completed or delivered orders found!', 'woocommerce' );

			return;
		}

		foreach ( $orders as $order ) {
			$total_savings += anony_get_order_total_savings( $order );
		}

		if ( $total_savings > 0 && strpos( $_SERVER['REQUEST_URI'], 'orders/total-savings' ) === false ) {
			echo '<p><strong>' . esc_html( $result_msg ) . '</strong> ' . wc_price( $total_savings ) . '</p>';
		} else {
			$headers  = getallheaders();
			$app_lang = ! empty( $headers['app_lang'] ) ? $headers['app_lang'] : 'ar';
			$response = array(
				'http_code' => '201',
				'status'    => 'success',
				'message'   => '',
				'language'  => $app_lang,
				'content'   => array(
					'total_savings' => $total_savings,
					'msg'           => esc_html( $result_msg ),
				),
			);

			return $response;
		}
	}

	return null;
}

add_action( 'rest_api_init', 'anony_register_total_savings_endpoint' );

/**
 * Register the total savings REST API endpoint.
 */
function anony_register_total_savings_endpoint() {
	register_rest_route(
		'api/orders',
		'/total-savings/(?P<customer_id>\d+)',
		array(
			'methods'             => 'POST',
			'callback'            => 'anony_send_total_savings_for_customer_orders',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Send total savings for customer orders via the REST API.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return array|null The response data.
 */
function anony_send_total_savings_for_customer_orders( $request ) {
	$customer_id = $request->get_param( 'customer_id' );

	return anony_display_total_savings_for_customer_orders( $customer_id );
}


add_action( 'woocommerce_new_order_item', 'add_values_to_order_item_meta', 1, 2 );
/**
 * Add values to order item meta.
 *
 * @param int   $item_id The item ID.
 * @param object $item    The item object.
 */
function add_values_to_order_item_meta( $item_id, $item ) {
	// Get product ID.
	$product_id = $item->get_product_id();
	$_product   = wc_get_product( $product_id );

	if ( ! $_product || is_wp_error( $_product ) ) {
		return;
	}

	// Get regular price.
	$regular_price = $_product->get_regular_price();

	// Get sale price.
	$sale_price = $_product->get_sale_price();

	// Save regular price as item meta.
	if ( ! empty( $regular_price ) ) {
		wc_add_order_item_meta( $item->get_id(), '_regular_price', $regular_price );
	}

	// Save sale price as item meta.
	if ( ! empty( $sale_price ) ) {
		wc_add_order_item_meta( $item->get_id(), '_sale_price', $sale_price );
	}
}

/**
 * Validate that products from the same author are in the cart.
 *
 * @param bool $passed     Whether the product can be added to the cart.
 * @param int  $product_id The product ID.
 * @param int  $quantity   The quantity of the product being added.
 *
 * @return bool Whether the product can be added to the cart.
 */
function anony_validate_cart_author( $passed, $product_id, $quantity ) {
	// Get the author ID of the product being added.
	$product_author_id = get_post_field( 'post_author', $product_id );

	// Loop through the cart items to check for products from a different author.
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		// Get the product ID and author ID of the cart item.
		$cart_item_product_id = $cart_item['product_id'];
		$cart_item_author_id  = get_post_field( 'post_author', $cart_item_product_id );

		// Check if the cart item has a different author than the product being added.
		if ( $cart_item_author_id !== $product_author_id ) {
			// Set an error message.
			wc_add_notice( __( 'لا يمكنك إضافة منتجات من بائع مختلف إلى سلة التسوق.', 'your-text-domain' ), 'error' );

			// Prevent the product from being added to the cart.
			return false;
		}
	}

	// If no products from a different author were found, allow the product to be added to the cart.
	return $passed;
}
