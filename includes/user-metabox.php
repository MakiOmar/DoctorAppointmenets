<?php
/**
 * User metabox
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Booking url copy
 *
 * @param object $user User's object.
 * @return string
 */
function snks_doctor_booking_url( $user ) {
	if ( ! in_array( 'doctor', $user->roles, true ) ) {
		return;
	}
	ob_start();
	?>
	<div>
		<h2>Doctor's page url</h2>
		<p>
		<input type="hidden" id="booking-url" value="<?php echo esc_url( snks_encrypted_doctor_url( $user ) ); ?>"/>
		<a href="#" class="button button-primary" onclick="copyToClipboard(event)">انسخ رابط الحجز الخاص بك</a>
		</p>
	</div>
	<script>
	function copyToClipboard(e) {
		e.preventDefault();
		const bookingUrl = document.getElementById('booking-url');
		const el = document.createElement('textarea');
		el.value = bookingUrl.value;
		document.body.appendChild(el);
		el.select();
		document.execCommand('copy');
		document.body.removeChild(el);
		Swal.fire({
			icon: 'success', // Error icon to indicate the issue
			title: 'تم',
			text: 'تم النسخ', // The original error message
			confirmButtonText: 'غلق'
		});
	}
	</script>
	<?php
	//phpcs:disable
	echo ob_get_clean();
	//phpcs:enable
}
add_action( 'show_user_profile', 'snks_doctor_booking_url' );
add_action( 'edit_user_profile', 'snks_doctor_booking_url' );
