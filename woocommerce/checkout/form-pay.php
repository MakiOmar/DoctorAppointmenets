<?php
/**
 * Pay for order form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-pay.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.2.0
 */

defined( 'ABSPATH' ) || exit;

$totals = $order->get_order_item_totals(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<form id="order_review" method="post">

	<?php
	$order_type = $order->get_meta( 'order_type' );
	if ( 'edit-fees' === $order_type ) {
		$_id             = $order->get_meta( 'connected_order' );
		$connected_order = wc_get_order( $_id );
		$form_data       = array(
			'_period'            => $connected_order->get_meta( '_period', true ),
			'_user_id'           => $connected_order->get_meta( '_user_id', true ),
			'_main_price'        => $order->get_meta( 'session_price', true ),
			'_total_price'       => $order->get_total(),
			'_jalsah_commistion' => $order->get_meta( 'service_fees', true ),
			'_paymob'            => $order->get_meta( 'paymob', true ),
		);
	} else {
		$form_data = array(
			'_period'            => $order->get_meta( '_period', true ),
			'_user_id'           => $order->get_meta( '_user_id', true ),
			'_main_price'        => $order->get_meta( '_main_price', true ),
			'_total_price'       => $order->get_meta( '_total_price', true ),
			'_jalsah_commistion' => $order->get_meta( '_jalsah_commistion', true ),
			'_paymob'            => $order->get_meta( '_paymob', true ),
		);
	}
	snks_user_info();
	//phpcs:disable
	echo consulting_session_pricing_table_shortcode( $form_data );
	if ( 'edit-fees' !== $order_type ) {
		echo snks_doctor_rules( $form_data['_user_id'] );
	}
	//phpcs:enable

	echo '<h2 style="margin:20px 0;color:#fff;font-size:25px;text-align:center">إختر طريقة الدفع المناسبة</h2>';
	echo '<p class="hacen_liner_print-outregular" style="color:#fff;font-size:15px;text-align:center">( يرجى العلم أن عملية الدفع ستتم بالجنيه المصري )</p>';

	?>

	<?php
	/**
	 * Triggered from within the checkout/form-pay.php template, immediately before the payment section.
	 *
	 * @since 8.2.0
	 */
	do_action( 'woocommerce_pay_order_before_payment' );
	?>

	<div id="payment">
		<?php if ( $order->needs_payment() ) : ?>
			<ul class="wc_payment_methods payment_methods methods">
				<?php
				if ( ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				} else {
					echo '<li>';
					wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					echo '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
		<div class="form-row">
			<input type="hidden" name="woocommerce_pay" value="1" />

			<?php wc_get_template( 'checkout/terms.php' ); ?>

			<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

			<?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>

			<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

			<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
		</div>
	</div>
</form>
