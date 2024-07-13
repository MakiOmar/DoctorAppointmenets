<?php
/**
 * Tawkto scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
add_action(
	'wp_footer',
	function () {
		// If not dashboard , not consulting list.
		if ( ! is_page( 682 ) && ! is_page( 1194 ) && ! is_front_page() && ! snks_is_patient() ) {
			return;
		}
		?>

		<!--Start of Tawk.to Script-->
		<script type="text/javascript">
			var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
			(function(){
			var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
			s1.async=true;
			s1.src='https://embed.tawk.to/65fd9600a0c6737bd123af11/1hpj93t27';
			s1.charset='UTF-8';
			s1.setAttribute('crossorigin','*');
			s0.parentNode.insertBefore(s1,s0);
			})();
		</script>
		<script type="text/javascript">
			var Tawk_API = Tawk_API || {};

			Tawk_API.customStyle = {
				visibility : {
					desktop : {
						position : 'bl',
						xOffset : '10px',
						yOffset : 80
					},
					mobile : {
						position : 'bl',
						xOffset : '10px',
						yOffset : 80
					},
					bubble : {
						rotate : '0deg',
						xOffset : -20,
						yOffset : 0
					}
				}
			};
			Tawk_API.disableWidgetFont = true;
			window.Tawk_API.onLoad = function(){
				window.Tawk_API.hideWidget();
			};
				
			
			jQuery( document ).ready(
				function ( $ ) {
					$( 'body' ).on(
						'click',
						'#customer-care-chat, .customer-care-chat',
						function ( e ) {
							e.preventDefault();
							console.log('true');
							Tawk_API.toggle()
						}
					);
				}
			);
		</script>
		<!--End of Tawk.to Script-->
		<?php
	}
);
