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

/**
 * Ensure rochtah_doctor role exists
 * This ensures the role appears in the roles dropdown on user edit pages
 */
function snks_ensure_rochtah_doctor_role_exists() {
	// Ensure the role exists - this makes it visible in user edit pages
	if ( ! get_role( 'rochtah_doctor' ) ) {
		add_role( 'rochtah_doctor', 'Rochtah Doctor', array(
			'read' => true,
			'edit_posts' => false,
			'delete_posts' => false,
			'manage_rochtah' => true,
			'view_rochtah_appointments' => true,
			'manage_rochtah_prescriptions' => true,
			'view_rochtah_patients' => true,
			'edit_rochtah_prescriptions' => true,
			'delete_rochtah_prescriptions' => true,
			'upload_files' => true,
		) );
	}
}
// Hook on init to ensure role exists early
add_action( 'init', 'snks_ensure_rochtah_doctor_role_exists', 1 );
// Also hook on admin_init as backup
add_action( 'admin_init', 'snks_ensure_rochtah_doctor_role_exists', 1 );

/**
 * Add WhatsApp number field for Rochtah doctors
 *
 * @param object $user User's object.
 * @return void
 */
function snks_rochtah_doctor_whatsapp_field( $user ) {
	// Only show for users with rochtah_doctor role
	if ( ! in_array( 'rochtah_doctor', $user->roles, true ) ) {
		return;
	}
	
	$whatsapp = get_user_meta( $user->ID, 'rochtah_whatsapp', true );
	?>
	<h2><?php _e( 'Rochtah Doctor Information', 'shrinks' ); ?></h2>
	<table class="form-table">
		<tr>
			<th>
				<label for="rochtah_whatsapp"><?php _e( 'WhatsApp Number', 'shrinks' ); ?></label>
			</th>
			<td>
				<input 
					type="text" 
					name="rochtah_whatsapp" 
					id="rochtah_whatsapp" 
					value="<?php echo esc_attr( $whatsapp ); ?>" 
					class="regular-text" 
					placeholder="+201234567890"
				/>
				<p class="description">
					<?php _e( 'WhatsApp number for notifications. Include country code (e.g., +201234567890)', 'shrinks' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'snks_rochtah_doctor_whatsapp_field' );
add_action( 'edit_user_profile', 'snks_rochtah_doctor_whatsapp_field' );

/**
 * Save WhatsApp number for Rochtah doctors
 *
 * @param int $user_id User ID.
 * @return void
 */
function snks_save_rochtah_doctor_whatsapp( $user_id ) {
	// Check if current user can edit this user
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	
	// Only save if user has rochtah_doctor role or is being assigned the role
	$user = get_userdata( $user_id );
	$assigning_role = isset( $_POST['role'] ) && $_POST['role'] === 'rochtah_doctor';
	
	if ( in_array( 'rochtah_doctor', $user->roles, true ) || $assigning_role ) {
		if ( isset( $_POST['rochtah_whatsapp'] ) ) {
			$whatsapp = sanitize_text_field( $_POST['rochtah_whatsapp'] );
			update_user_meta( $user_id, 'rochtah_whatsapp', $whatsapp );
		}
	}
}
add_action( 'personal_options_update', 'snks_save_rochtah_doctor_whatsapp' );
add_action( 'edit_user_profile_update', 'snks_save_rochtah_doctor_whatsapp' );

/**
 * Ensure rochtah_doctor role is assigned when role is set via user edit page
 * Hook into set_user_role to automatically assign role if doctor is set as rochtah
 */
function snks_ensure_rochtah_doctor_role( $user_id, $role, $old_roles ) {
	// If rochtah_doctor role is being assigned, ensure it's properly set
	if ( $role === 'rochtah_doctor' ) {
		$user = get_userdata( $user_id );
		if ( $user && ! in_array( 'rochtah_doctor', $user->roles, true ) ) {
			$user->add_role( 'rochtah_doctor' );
		}
	}
}
add_action( 'set_user_role', 'snks_ensure_rochtah_doctor_role', 10, 3 );
