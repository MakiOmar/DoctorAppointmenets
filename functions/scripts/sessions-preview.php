<?php
/**
 * Sessions preview
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$('.delete-slot').on(
					'click',
					function( e ) {
						e.preventDefault();
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
						// Perform nonce check.
						var nonce     = '<?php echo esc_html( wp_create_nonce( 'delete_slot_nonce' ) ); ?>';
						var slotIndex = $(this).data('index');
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slotIndex: slotIndex,
								nonce    : nonce,
								action   : 'delete_slot',
							},
							success: function(response) {
								if ( response.resp ) {
									$( '#timetable-' + slotIndex ).remove();
								}
							}
						});
					}
				);
			});
		</script>
		<?php
	}
);
