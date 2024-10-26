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
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}
);

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				if ( $('.conflict-error').length > 0 ) {
					$([document.documentElement, document.body]).animate(
						{
							scrollTop: $(".conflict-error").offset().top - 100
						},
						2000
					);
				}
				$(document).on(
					'click',
					'.timetable-preview-tab',
					function() {
						var target = $( this ).data('target');
						if ( $('.' + target).hasClass('timetable-show') ) {
							$('.' + target).slideUp();
							$('.' + target).removeClass('timetable-show');
						} else {
							$('.' + target).slideDown();
							$('.' + target).addClass('timetable-show');
						}
					}
				);

				$(document).on(
					'click',
					'.delete-slot',
					function( e ) {
						e.preventDefault();
						Swal.fire({
							title: 'هل أنت متأكد؟',
							text: "لا يمكنك التراجع بعد ذلك!",
							icon: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'نعم، أنا متأكد',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (!result.isConfirmed) {
								return;
							}
						});

						// Perform nonce check.
						var nonce     = '<?php echo esc_html( wp_create_nonce( 'delete_slot_nonce' ) ); ?>';
						var slotIndex = $(this).data('index');
						var slotDay   = $(this).data('day');
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slotIndex: slotIndex,
								slotDay  : slotDay,
								nonce    : nonce,
								action   : 'delete_slot',
							},
							success: function(response) {
								if ( response.resp ) {
									$( '#timetable-' + slotDay + '-' + slotIndex ).remove();
								}
							}
						});
					}
				);				

				$(document).on(
					'change',
					'#app_attendance_type',
					function( ) {
						if ( $(this).val() === 'offline' ) {
							$('#app_clinic', $(this).closest('.day-specific-form')).show();
						} else {
							$('#app_clinic', $(this).closest('.day-specific-form')).hide();
						}
					}
				);
			});
		</script>
		<?php
	}
);
