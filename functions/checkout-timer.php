<?php
/**
 * Checkout timer
 *
 * @package Jalsah
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set expiration time for the order when it is placed.
 *
 * @param int $order_id The WooCommerce order ID.
 */
function set_order_expiration_time( $order_id ) {
	if ( ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( $order ) {
		$expiration_time = time() + ( CANCELL_AFTER * 60 ); // Set expiration time (CANCELL_AFTER minutes from now).
		$order->update_meta_data( '_order_expiration_time', $expiration_time );
		$order->save();
	}
}
add_action( 'woocommerce_new_order', 'set_order_expiration_time', 10, 1 );

/**
 * Check if the given order has expired.
 *
 * @param WC_Order $order The WooCommerce order object.
 * @return bool True if expired, false otherwise.
 */
function is_order_expired( $order ) {
	$expiration_time = $order->get_meta( '_order_expiration_time', true );
	return ( $expiration_time && time() > $expiration_time );
}

/**
 * Restrict access to expired orders on "View Order" and "Order Pay" pages.
 */
function restrict_expired_orders_and_payment() {
	if ( is_wc_endpoint_url( 'view-order' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		$q_var    = is_wc_endpoint_url( 'view-order' ) ? 'view-order' : 'order-pay';
		$order_id = get_query_var( $q_var, false );
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( $order && is_order_expired( $order ) ) {
			$user_id = $order->get_meta( '_user_id', true );
			wp_safe_redirect( add_query_arg( 'status', 'expired', snks_encrypted_doctor_url( $user_id ) ) );
			exit;
		}
	}
}
add_action( 'template_redirect', 'restrict_expired_orders_and_payment' );

/**
 * Prevent order payment if the order has expired during checkout.
 */
function prevent_expired_orders_payment() {
	$order_id = WC()->session->get( 'order_awaiting_payment' );
	if ( ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( $order && is_order_expired( $order ) ) {
		die; // Prevent further processing.
	}
}
add_action( 'woocommerce_checkout_process', 'prevent_expired_orders_payment' );

/**
 * AJAX handler to check if an order has expired.
 */
function ajax_check_order_expiry() {
	check_ajax_referer( 'check_order_expiry', 'security' );

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	if ( ! $order_id ) {
		wp_send_json_error( 'Invalid order ID' );
	}

	$order = wc_get_order( $order_id );
	if ( $order && is_order_expired( $order ) ) {
		wp_send_json_success( array( 'expired' => true ) );
	} else {
		wp_send_json_success( array( 'expired' => false ) );
	}
}
add_action( 'wp_ajax_check_order_expiry', 'ajax_check_order_expiry' );
/**
 * Enqueue jQuery script for checkout timer.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		$order_id = get_query_var( 'order-pay', false );
		$order    = wc_get_order( $order_id );
		if ( is_order_expired( $order ) ) {
			return;
		}
		$user_id    = $order ? $order->get_meta( '_user_id', true ) : 0;
		$doctor_url = snks_encrypted_doctor_url( $user_id );
		?>
		<div id="countdown-timer">
			<p>يرجى دفع قيمة الحجز قبل انقضاء هذه المدة</p>
			<span id="countdown"><?php echo CANCELL_AFTER;//phpcs:disable ?>:00</span>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			let countdownElement = $("#countdown");
			let orderId = "<?php echo esc_js( $order_id ); ?>";
			let storageKey = `countdown_expiration_${orderId}`;
			let expirationTime = localStorage.getItem(storageKey);
            let cancelAfter = <?php echo CANCELL_AFTER;//phpcs:disable ?>

			if (!expirationTime) {
				expirationTime = Date.now() + ( cancelAfter * 60 * 1000 ); // CANCELL_AFTER minutes in milliseconds
				localStorage.setItem(storageKey, expirationTime);
			} else {
				expirationTime = parseInt(expirationTime);
			}

			function updateTimer() {
				let timeLeft = Math.max(0, Math.floor((expirationTime - Date.now()) / 1000));
				let minutes = Math.floor(timeLeft / 60);
				let seconds = timeLeft % 60;
				countdownElement.text(`${minutes}:${seconds < 10 ? "0" : ""}${seconds}`);

				if (timeLeft <= 0) {
					localStorage.removeItem(storageKey);
					window.location.href = '<?php echo esc_url( $doctor_url ); ?>';
					clearInterval(timerInterval);
				}
			}

			let timerInterval = setInterval(updateTimer, 1000);
			updateTimer();

			// Periodically check order expiry via AJAX using jQuery
			function checkOrderExpiry() {
				$.post(
					"<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					{
						action: "check_order_expiry",
						order_id: orderId,
						security: "<?php echo wp_create_nonce( 'check_order_expiry' ); //phpcs:disable ?>"
					},
					function(response) {
						if (response.success && response.data.expired) {
                            setTimeout(
                                function(){
                                    localStorage.removeItem(storageKey);
                                    window.location.href = '<?php echo esc_url( $doctor_url ); ?>';
                                },
                                2000
                            );

						}
					}
				);
			}

			setInterval(checkOrderExpiry, 3000); // Check every 30 seconds
		});
		</script>
		<?php
	}
);
