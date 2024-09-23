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

?>
<div class="woocommerce-order">

	<?php
	if ( $order ) :

		if ( $order->has_status( 'failed' ) ) {
			echo '';
		} else {
			echo do_shortcode( '[elementor-template id="3761"]' );
		}
		do_action( 'woocommerce_thankyou', $order->get_id() );
	endif;
	?>

</div>
