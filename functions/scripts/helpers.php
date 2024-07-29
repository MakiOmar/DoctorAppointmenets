<?php
/**
 * Helpers
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_head',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				
			});
		</script>
		<?php
	}
);
