<?php
/**
 * Checkout Order Receipt Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-receipt.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="countdown-timer">
		<p>يرجى دفع قيمة الحجز قبل انقضاء هذه المدة</p>
		<span id="countdown">5:00</span>
	</div>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
		let countdownElement = document.getElementById("countdown");
		let expirationTime = localStorage.getItem("countdown_expiration");
		console.log(expirationTime);
		if (!expirationTime) {
			expirationTime = Date.now() + 300000; // Set expiration time (5 minutes from now)
			localStorage.setItem("countdown_expiration", expirationTime);
		}

		function updateTimer() {
			let timeLeft = Math.max(0, Math.floor((expirationTime - Date.now()) / 1000));

			let minutes = Math.floor(timeLeft / 60);
			let seconds = timeLeft % 60;
			countdownElement.innerHTML = `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;

			if (timeLeft <= 0) {
				localStorage.removeItem("countdown_expiration");
				window.location.href = "<?php echo home_url(); ?>";
			} else {
				setTimeout(updateTimer, 1000);
			}
		}

		updateTimer();
	});
	</script>

<ul class="order_details">
	<li class="order">
		<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
		<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
	</li>
	<li class="date">
		<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
		<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
	</li>
	<li class="total">
		<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
		<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
	</li>
	<?php if ( $order->get_payment_method_title() ) : ?>
	<li class="method">
		<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
		<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
	</li>
	<?php endif; ?>
</ul>

<?php do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order->get_id() ); ?>

<div class="clear"></div>
