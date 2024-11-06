<?php
/**
 * Account settings' cripts
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
			jQuery(document).ready(
				function($) {
					$(document).on('click', '#copyToClipboard', function() {
						const textToCopy = $(this).data('url');
						navigator.clipboard.writeText(textToCopy).then(function() {
							Swal.fire({
								icon: 'success',
								title: 'تم',
								text: 'تم النسخ',
								confirmButtonText: 'غلق'
							});
						}).catch(function(error) {
							console.error('Failed to copy text: ', error);
						});
					});

					$(document).on(
						'click',
						'.anony_dial_codes_selected_choice',
						function(event){
							event.preventDefault();
							$(this).closest('.anony-dial-codes').find('.anony-dial-codes-content').toggle();
						}
					);
					$(document).on(
						'click',
						'.anony-dialling-code',
						function(event){
							event.preventDefault();
							var parent = $(this).closest('.anony-dial-codes');
							
							$('.anony_dial_codes_selected_choice', parent).html( $(this).next('.anony_selected_dial_code', parent).html() );
							$('.anony_dial_codes_first_choice', parent).html( $(this).next('.anony_selected_dial_code', parent).html() );
							$('.anony_dial_code', parent).val( $(this).data('dial-code' ) ).change();
							$(this).closest('.anony-dial-codes').find('.anony-dial-codes-content').toggle();
							
						}
					);
					if ( $( '.anony_dial_codes_first_choice' ).length > 0 ) {
						$(".anony-dial-codes").each(
							function () {
								var thisDialCodes = $(this);
								$('.anony_dial_codes_selected_choice', thisDialCodes).html($('.anony_dial_codes_first_choice', thisDialCodes).html(  )  );
							}
						);
					}
					$(window).on('jet-popup/show-event/after-show', function(){
						$(".anony-dial-codes").each(
							function () {
								var thisDialCodes = $(this);
								$('.anony_dial_codes_selected_choice', thisDialCodes).html($('.anony_dial_codes_first_choice', thisDialCodes).html(  )  );
							}
						);
					});
				}
			);
		</script>
		<?php
	}
);
