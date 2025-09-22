<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || die();

// Check if this is an AI order and add JavaScript redirect as fallback
$is_ai_order = $order->get_meta( 'from_jalsah_ai' );
if ( $is_ai_order === 'true' || $is_ai_order === true || $is_ai_order === '1' || $is_ai_order === 1 ) {
	$frontend_url = snks_ai_get_primary_frontend_url();
	?>
	<script>
	// JavaScript fallback redirect for AI orders
	console.log('AI Order detected, redirecting to frontend...');
	setTimeout(function() {
		window.location.href = '<?php echo esc_js( $frontend_url . '/appointments' ); ?>';
	}, 1000);
	</script>
	<?php
}

do_action( 'woocommerce_thankyou', $order->get_id() );
?>
<div class="woocommerce-order">

	<?php
	if ( $order ) :

		if ( $order->has_status( 'failed' ) ) {
			echo '';
		} else {
			echo do_shortcode( '[elementor-template id="3761"]' );
		}
	endif;
	?>

</div>
