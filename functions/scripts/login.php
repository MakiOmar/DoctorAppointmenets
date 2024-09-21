<?php
/**
 * Login
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Add inline script to handle radio button change and clear the username input.
 */

add_action(
	'wp_footer',
	function () {
		?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		function updateLoginBillingPhone( billingPhoneInput ) {
			var phoneInput = $('input[name=temp-phone]');
			var countryCodeInput = $('input[name=country_code]');
			var phone = phoneInput.val().trim();
			var countryCode = countryCodeInput.val().trim();
			billingPhoneInput.val( countryCode + phone ).change();
		}
		$('input[name="login_with"]').on('change', function() {
			var selectedValue = $('input[name="login_with"]:checked').val();

			// If the selected radio value is 'email', clear the username field
			if (selectedValue === 'email') {
				$('input[name="username"]').val('');
			} else {
				updateLoginBillingPhone( $('input[name="username"]') );
			}
		});
	});
	</script>
		<?php
	},
	100
);

