<?php
/**
 * Jalsah AI: report of manual-booking orders with secretary attribution + WC orders list column.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key: secretary user ID on manual booking orders.
 */
function snks_manual_secretary_meta_user_id_key() {
	return 'snks_manual_secretary_user_id';
}

/**
 * Meta key: secretary display name snapshot.
 */
function snks_manual_secretary_meta_name_key() {
	return 'snks_manual_secretary_name';
}

/**
 * WooCommerce order statuses included in the manual-booking secretary report (table, totals, CSV, dropdown).
 *
 * @return string[]
 */
function snks_manual_booking_report_order_statuses() {
	return apply_filters(
		'snks_manual_booking_report_order_statuses',
		array( 'processing', 'completed' )
	);
}

/**
 * Whether order meta indicates an admin manual booking (aligned with analytics helper).
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function snks_order_is_admin_manual_booking( $order ) {
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return false;
	}
	$v = $order->get_meta( 'admin_manual_booking' );
	return in_array( $v, array( true, 'true', '1', 1, 'yes' ), true );
}

/**
 * Order count and sum of order totals for the report filters (date range + meta_query).
 * Paginates to avoid loading every order at once; applies the same manual-booking guard and statuses as the table.
 *
 * @param string $date_created `wc_get_orders` date_created range (timestamps joined by ...).
 * @param array  $meta_query   Meta query for the report.
 * @return array{ count: int, total: float }
 */
function snks_manual_booking_secretary_report_aggregate_period( $date_created, $meta_query ) {
	$count     = 0;
	$total     = 0.0;
	$page      = 1;
	$per       = 200;
	$max_pages = (int) apply_filters( 'snks_manual_booking_report_aggregate_max_pages', 500 );

	while ( $page <= $max_pages ) {
		$orders = wc_get_orders(
			array(
				'limit'        => $per,
				'page'         => $page,
				'paginate'     => false,
				'return'       => 'objects',
				'orderby'      => 'date',
				'order'        => 'DESC',
				'status'       => snks_manual_booking_report_order_statuses(),
				'meta_query'   => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'date_created' => $date_created,
			)
		);

		if ( empty( $orders ) ) {
			break;
		}

		foreach ( $orders as $order ) {
			if ( ! snks_order_is_admin_manual_booking( $order ) ) {
				continue;
			}
			++$count;
			$total += (float) $order->get_total();
		}

		if ( count( $orders ) < $per ) {
			break;
		}
		++$page;
	}

	return array(
		'count' => $count,
		'total' => $total,
	);
}

/**
 * Distinct secretary user IDs that appear on manual booking orders.
 * Uses wc_get_orders + the same manual-booking check as the report so HPOS/CPT and meta storage match.
 *
 * @return int[]
 */
function snks_manual_booking_distinct_secretary_ids() {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$per_page  = 200;
	$max_pages = (int) apply_filters( 'snks_manual_booking_secretary_ids_max_pages', 250 );
	$ids       = array();
	$page      = 1;

	$manual_meta = array(
		'key'     => 'admin_manual_booking',
		'value'   => array( '1', 'true', 'yes' ),
		'compare' => 'IN',
	);

	while ( $page <= $max_pages ) {
		$orders = wc_get_orders(
			array(
				'limit'      => $per_page,
				'page'       => $page,
				'paginate'   => false,
				'return'     => 'objects',
				'status'     => snks_manual_booking_report_order_statuses(),
				'orderby'    => 'date',
				'order'      => 'DESC',
				'meta_query' => array( $manual_meta ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		if ( empty( $orders ) ) {
			break;
		}

		foreach ( $orders as $order ) {
			if ( ! snks_order_is_admin_manual_booking( $order ) ) {
				continue;
			}
			$sid = absint( $order->get_meta( snks_manual_secretary_meta_user_id_key() ) );
			if ( $sid > 0 ) {
				$ids[ $sid ] = true;
			}
		}

		if ( count( $orders ) < $per_page ) {
			break;
		}
		++$page;
	}

	return array_map( 'absint', array_keys( $ids ) );
}

/**
 * Admin report page: manual orders by secretary.
 */
function snks_manual_booking_secretary_report_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'shrinks' ) );
	}

	if ( ! function_exists( 'wc_get_orders' ) ) {
		echo '<div class="wrap"><p>' . esc_html__( 'WooCommerce is required.', 'shrinks' ) . '</p></div>';
		return;
	}

	if ( isset( $_GET['snks_export'] ) && 'csv' === $_GET['snks_export'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'snks_secretary_report_csv' ) ) {
		snks_manual_booking_secretary_report_send_csv();
		return;
	}

	$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
	$default_to = ( new DateTimeImmutable( 'now', $timezone ) )->format( 'Y-m-d' );
	$default_from = ( new DateTimeImmutable( 'now', $timezone ) )->modify( '-30 days' )->format( 'Y-m-d' );

	$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : $default_from; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : $default_to; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sec_filter = isset( $_GET['secretary'] ) ? absint( $_GET['secretary'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$paged      = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page   = 50;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
		$date_from = $default_from;
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
		$date_to = $default_to;
	}

	$d1 = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_from . ' 00:00:00', $timezone );
	$d2 = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_to . ' 23:59:59', $timezone );
	if ( ! $d1 || ! $d2 ) {
		$d1 = new DateTimeImmutable( $default_from . ' 00:00:00', $timezone );
		$d2 = new DateTimeImmutable( $default_to . ' 23:59:59', $timezone );
	}

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => 'admin_manual_booking',
			'value'   => array( '1', 'true', 'yes' ),
			'compare' => 'IN',
		),
	);
	if ( $sec_filter > 0 ) {
		$meta_query[] = array(
			'key'     => snks_manual_secretary_meta_user_id_key(),
			'value'   => $sec_filter,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	}

	$args = array(
		'limit'      => $per_page,
		'page'       => $paged,
		'paginate'   => true,
		'orderby'    => 'date',
		'order'      => 'DESC',
		'return'     => 'objects',
		'status'     => snks_manual_booking_report_order_statuses(),
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	);

	$args['date_created'] = $d1->getTimestamp() . '...' . $d2->getTimestamp();

	$result = wc_get_orders( $args );

	$period_stats = snks_manual_booking_secretary_report_aggregate_period( $args['date_created'], $meta_query );

	$secretary_ids = snks_manual_booking_distinct_secretary_ids();
	sort( $secretary_ids );

	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}

	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'          => 'jalsah-ai-manual-booking-secretary-report',
				'date_from'     => $date_from,
				'date_to'       => $date_to,
				'secretary'     => $sec_filter,
				'snks_export'   => 'csv',
			),
			admin_url( 'admin.php' )
		),
		'snks_secretary_report_csv'
	);

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manual bookings by secretary', 'shrinks' ); ?></h1>
		<p class="description"><?php esc_html_e( 'WooCommerce orders created via Manual Booking with status Processing or Completed, filtered by date and optional secretary.', 'shrinks' ); ?></p>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="snks-secretary-report-filters" style="margin: 1em 0;">
			<input type="hidden" name="page" value="jalsah-ai-manual-booking-secretary-report" />
			<label>
				<?php esc_html_e( 'From', 'shrinks' ); ?>
				<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
			</label>
			<label style="margin-left:1em;">
				<?php esc_html_e( 'To', 'shrinks' ); ?>
				<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
			</label>
			<label style="margin-left:1em;">
				<?php esc_html_e( 'Secretary', 'shrinks' ); ?>
				<select name="secretary">
					<option value="0"><?php esc_html_e( 'All', 'shrinks' ); ?></option>
					<?php foreach ( $secretary_ids as $uid ) : ?>
						<?php $u = get_userdata( $uid ); ?>
						<option value="<?php echo esc_attr( (string) $uid ); ?>" <?php selected( $sec_filter, $uid ); ?>>
							<?php echo esc_html( $u ? $u->display_name . ' (#' . $uid . ')' : '#' . $uid ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="submit" class="button button-primary" style="margin-left:1em;"><?php esc_html_e( 'Filter', 'shrinks' ); ?></button>
			<a class="button" style="margin-left:0.5em;" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'shrinks' ); ?></a>
		</form>

		<div class="snks-secretary-report-period-summary" style="margin: 1em 0; padding: 12px 16px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; max-width: 640px;">
			<p style="margin: 0 0 8px; font-weight: 600;">
				<?php
				printf(
					/* translators: 1: start date (Y-m-d), 2: end date (Y-m-d) */
					esc_html__( 'Period summary (%1$s – %2$s)', 'shrinks' ),
					esc_html( $date_from ),
					esc_html( $date_to )
				);
				?>
			</p>
			<p style="margin: 0 0 4px;">
				<?php
				printf(
					/* translators: %d: number of orders */
					esc_html__( 'Total orders: %d', 'shrinks' ),
					(int) $period_stats['count']
				);
				?>
			</p>
			<p style="margin: 0;">
				<?php esc_html_e( 'Orders total (sum):', 'shrinks' ); ?>
				<?php echo wp_kses_post( wc_price( $period_stats['total'] ) ); ?>
			</p>
		</div>

		<?php if ( empty( $result->orders ) ) : ?>
			<p><?php esc_html_e( 'No orders match these filters.', 'shrinks' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Order', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date created', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Secretary', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Secretary ID', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Patient / customer', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Total', 'shrinks' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'shrinks' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result->orders as $order ) : ?>
						<?php
						if ( ! snks_order_is_admin_manual_booking( $order ) ) {
							continue;
						}
						$sid = (int) $order->get_meta( snks_manual_secretary_meta_user_id_key() );
						$sname = (string) $order->get_meta( snks_manual_secretary_meta_name_key() );
						$edit  = $order->get_edit_order_url();
						$cid   = $order->get_customer_id();
						$cname = $order->get_formatted_billing_full_name();
						if ( $cid && ( trim( $cname ) === '' ) ) {
							$cu = get_userdata( $cid );
							$cname = $cu ? $cu->display_name : '';
						}
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $edit ); ?>">#<?php echo esc_html( (string) $order->get_order_number() ); ?></a>
							</td>
							<td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—' ); ?></td>
							<td><?php echo $sname !== '' ? esc_html( $sname ) : '—'; ?></td>
							<td><?php echo $sid > 0 ? esc_html( (string) $sid ) : '—'; ?></td>
							<td><?php echo esc_html( $cname !== '' ? $cname : '—' ); ?> <?php echo $cid ? '<small>(#' . esc_html( (string) $cid ) . ')</small>' : ''; ?></td>
							<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
							<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( (int) $result->max_num_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						$paginate_base = add_query_arg(
							array(
								'page'      => 'jalsah-ai-manual-booking-secretary-report',
								'date_from' => $date_from,
								'date_to'   => $date_to,
								'secretary' => $sec_filter ? (string) $sec_filter : '0',
								'paged'     => '%#%',
							),
							admin_url( 'admin.php' )
						);
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => $paginate_base,
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => (int) $result->max_num_pages,
									'current'   => $paged,
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Stream CSV export for current filter (no pagination — all matching orders).
 */
function snks_manual_booking_secretary_report_send_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'shrinks' ) );
	}

	$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
	$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sec_filter = isset( $_GET['secretary'] ) ? absint( $_GET['secretary'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
		wp_die( esc_html__( 'Invalid date range.', 'shrinks' ) );
	}

	$d1 = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_from . ' 00:00:00', $timezone );
	$d2 = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date_to . ' 23:59:59', $timezone );
	if ( ! $d1 || ! $d2 ) {
		wp_die( esc_html__( 'Invalid date range.', 'shrinks' ) );
	}

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => 'admin_manual_booking',
			'value'   => array( '1', 'true', 'yes' ),
			'compare' => 'IN',
		),
	);
	if ( $sec_filter > 0 ) {
		$meta_query[] = array(
			'key'     => snks_manual_secretary_meta_user_id_key(),
			'value'   => $sec_filter,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	}

	$orders = wc_get_orders(
		array(
			'limit'        => -1,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'return'       => 'objects',
			'status'       => snks_manual_booking_report_order_statuses(),
			'meta_query'   => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'date_created' => $d1->getTimestamp() . '...' . $d2->getTimestamp(),
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=manual-bookings-secretary-' . $date_from . '-to-' . $date_to . '.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) {
		exit;
	}

	fputcsv( $out, array( 'order_id', 'order_number', 'date_created', 'secretary_id', 'secretary_name', 'customer_id', 'customer_name', 'total', 'status' ) );

	foreach ( $orders as $order ) {
		if ( ! snks_order_is_admin_manual_booking( $order ) ) {
			continue;
		}
		$sid   = (int) $order->get_meta( snks_manual_secretary_meta_user_id_key() );
		$sname = (string) $order->get_meta( snks_manual_secretary_meta_name_key() );
		$cid   = $order->get_customer_id();
		$cname = $order->get_formatted_billing_full_name();
		fputcsv(
			$out,
			array(
				$order->get_id(),
				$order->get_order_number(),
				$order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
				$sid,
				$sname,
				$cid,
				$cname,
				$order->get_total(),
				$order->get_status(),
			)
		);
	}
	fclose( $out );
	exit;
}

/**
 * HPOS + CPT: add Secretary column to WooCommerce orders list.
 *
 * @param array $columns Columns.
 * @return array
 */
function snks_wc_orders_list_table_add_secretary_column( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$new['snks_secretary'] = __( 'Secretary (manual)', 'shrinks' );
		}
	}
	if ( ! isset( $new['snks_secretary'] ) ) {
		$new['snks_secretary'] = __( 'Secretary (manual)', 'shrinks' );
	}
	return $new;
}

/**
 * @param string    $column_name Column.
 * @param WC_Order  $order       Order.
 */
function snks_wc_orders_list_table_render_secretary_column( $column_name, $order ) {
	if ( 'snks_secretary' !== $column_name || ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}
	if ( ! snks_order_is_admin_manual_booking( $order ) ) {
		echo '—';
		return;
	}
	$sname = (string) $order->get_meta( snks_manual_secretary_meta_name_key() );
	$sid   = (int) $order->get_meta( snks_manual_secretary_meta_user_id_key() );
	if ( $sname !== '' ) {
		echo esc_html( $sname );
		if ( $sid > 0 ) {
			echo ' <small>#' . esc_html( (string) $sid ) . '</small>';
		}
	} elseif ( $sid > 0 ) {
		echo esc_html( '#' . $sid );
	} else {
		echo '—';
	}
}

/**
 * Classic posts-based orders screen column header.
 *
 * @param array $columns Columns.
 * @return array
 */
function snks_wc_shop_order_posts_add_secretary_column( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$new['snks_secretary'] = __( 'Secretary (manual)', 'shrinks' );
		}
	}
	if ( ! isset( $new['snks_secretary'] ) ) {
		$new['snks_secretary'] = __( 'Secretary (manual)', 'shrinks' );
	}
	return $new;
}

/**
 * Classic posts-based orders screen cell.
 *
 * @param string $column Column id.
 * @param int    $post_id Post ID.
 */
function snks_wc_shop_order_posts_render_secretary_column( $column, $post_id ) {
	if ( 'snks_secretary' !== $column ) {
		return;
	}
	$order = wc_get_order( $post_id );
	if ( ! $order ) {
		echo '—';
		return;
	}
	snks_wc_orders_list_table_render_secretary_column( 'snks_secretary', $order );
}

/**
 * Register WooCommerce order list columns when WooCommerce is active.
 */
function snks_manual_booking_secretary_register_wc_list_columns() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	add_filter( 'woocommerce_shop_order_list_table_columns', 'snks_wc_orders_list_table_add_secretary_column', 20 );
	add_action( 'woocommerce_shop_order_list_table_custom_column', 'snks_wc_orders_list_table_render_secretary_column', 10, 2 );

	add_filter( 'manage_edit-shop_order_columns', 'snks_wc_shop_order_posts_add_secretary_column', 20 );
	add_action( 'manage_shop_order_posts_custom_column', 'snks_wc_shop_order_posts_render_secretary_column', 10, 2 );
}
add_action( 'plugins_loaded', 'snks_manual_booking_secretary_register_wc_list_columns', 30 );
