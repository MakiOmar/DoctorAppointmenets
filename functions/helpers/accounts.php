<?php
/**
 * Debug
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Check if is doctor user
 *
 * @return bool
 */
function snks_is_doctor() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	if ( ! in_array( 'doctor', $user->roles, true ) ) {
		return false;
	}

	return true;
}
/**
 * Check if is family user
 *
 * @return bool
 */
function snks_is_family() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	if ( ! in_array( 'family', $user->roles, true ) ) {
		return false;
	}

	return true;
}

/**
 * Check if is patient user
 *
 * @return bool
 */
function snks_is_patient() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	if ( ! in_array( 'customer', $user->roles, true ) ) {
		return false;
	}

	return true;
}

/**
 * Return doctor settings
 *
 * @return array An array of settings if is a doctor.
 */
function snks_doctor_settings() {
	$settings = array();
	if ( snks_is_doctor() || current_user_can( 'manage_options' ) ) {
		$user_id                               = get_current_user_id();
		$settings['60_minutes']                = get_user_meta( $user_id, '60-minutes', true );
		$settings['45_minutes']                = get_user_meta( $user_id, '45-minutes', true );
		$settings['30_minutes']                = get_user_meta( $user_id, '30-minutes', true );
		$settings['enable_discount']           = get_user_meta( $user_id, 'enable_discount', true );
		$settings['discount_percent']          = get_user_meta( $user_id, 'discount_percent', true );
		$settings['to_be_old_number']          = get_user_meta( $user_id, 'to_be_old_number', true );
		$settings['to_be_old_unit']            = get_user_meta( $user_id, 'to_be_old_unit', true );
		$settings['allow_appointment_change']  = get_user_meta( $user_id, 'allow_appointment_change', true );
		$settings['free_change_before_number'] = get_user_meta( $user_id, 'free_change_before_number', true );
		$settings['free_change_before_unit']   = get_user_meta( $user_id, 'free_change_before_unit', true );
		$settings['block_if_before_number']    = get_user_meta( $user_id, 'block_if_before_number', true );
		$settings['block_if_before_unit']      = get_user_meta( $user_id, 'block_if_before_unit', true );
		$settings['online']                    = get_user_meta( $user_id, 'online', true );
		$settings['offline']                   = get_user_meta( $user_id, 'offline', true );
		$settings['both']                      = get_user_meta( $user_id, 'both', true );
		$settings['clinics_list']              = get_user_meta( $user_id, 'clinics_list', true );
	}

	return $settings;
}

/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['60_minutes'] ) {
		$is_available[] = 60;
	}
	if ( 'on' === $settings['45_minutes'] ) {
		$is_available[] = 45;
	}
	if ( 'on' === $settings['30_minutes'] ) {
		$is_available[] = 30;
	}
	return $is_available;
}

/**
 * Get doctor's available periods
 *
 * @return array
 */
function snks_get_available_periods_options() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['60_minutes'] ) {
		$is_available[] = array(
			'value' => '60',
			'label' => '60 دقيقة',
		);
	}
	if ( 'on' === $settings['45_minutes'] ) {
		$is_available[] = array(
			'value' => '45',
			'label' => '45 دقيقة',
		);
	}
	if ( 'on' === $settings['30_minutes'] ) {
		$is_available[] = array(
			'value' => '30',
			'label' => '30 دقيقة',
		);
	}
	return $is_available;
}
/**
 * Get doctor's available methods
 *
 * @return array
 */
function snks_get_available_attendance_types() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['online'] ) {
		$is_available[] = 'online';
	}
	if ( 'on' === $settings['offline'] ) {
		$is_available[] = 'offline';
	}
	if ( 'on' === $settings['both'] ) {
		$is_available[] = 'both';
	}
	return $is_available;
}

/**
 * Get doctor's available methods
 *
 * @return array
 */
function snks_get_available_attendance_types_options() {
	$settings     = snks_doctor_settings();
	$is_available = array();
	if ( 'on' === $settings['online'] ) {
		$is_available[] = array(
			'value' => 'online',
			'label' => 'أونلاين',
		);
	}
	if ( 'on' === $settings['offline'] ) {
		$is_available[] = array(
			'value' => 'offline',
			'label' => 'عيادة',
		);
	}
	if ( 'on' === $settings['both'] ) {
		$is_available[] = array(
			'value' => 'both',
			'label' => 'أونلاين وعيادة',
		);
	}
	return $is_available;
}

add_action(
	'wp_footer',
	'snks_get_available_attendance_types'
);
/**
 * Check if programme enrolled
 *
 * @return bool
 */
function snks_is_programme_enrolled() {
	$user = wp_get_current_user();
	if ( $user ) {
		$programme_enrolled = get_user_meta( $user->ID, 'programme_enrolled', true );
		if ( $programme_enrolled && 'yes' === $programme_enrolled ) {
			return true;
		}
	}

	return false;
}
add_shortcode(
	'anony_email_verification',
	function () {
		?>
		<style>
			#verification_code_form{
				text-align: center;
			}
			.verification_code_wrapper{
				display: flex;
				justify-content: center;
				direction: ltr;
			}
			/* Style for the input fields */
			input.verification_code_temp {
				width: 40px;
				height: 40px;
				border-radius: 5px;
				background-color: #F7F5F9;
				border: 1px solid #78A8B6;
				text-align: center;
				margin-right: 5px;
				padding: 3px;
			}
			#verification_code{
				display: none;
			}
			#verification_code_form_submit {
				width: 100%;
				border-radius: 5px;
				background-color: #78A8B6;
				border: 1px solid #78A8B6;
				color: #fff;
				margin-top: 15px;
			}
		</style>
		<form method="post" id="verification_code_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="verify_email">
			<div class="verification_code_wrapper">
			<input type="text" maxlength="1" class="verification_code_temp" id="input1" oninput="moveToNextInput(this)">
			<input type="text" maxlength="1" class="verification_code_temp" id="input2" oninput="moveToNextInput(this)">
			<input type="text" maxlength="1" class="verification_code_temp" id="input3" oninput="moveToNextInput(this)">
			<input type="text" maxlength="1" class="verification_code_temp" id="input4" oninput="moveToNextInput(this)">
			</div>
			<input type="text" name="verification_code" id="verification_code" required maxlength="4" pattern="[0-9]{4}">
			<input id="verification_code_form_submit" type="submit" value="إرسال">
		</form>
		<script>
			// Function to move focus to the next input
			function moveToNextInput(currentInput) {
				const nextInput = currentInput.nextElementSibling;
				if (nextInput) {
					nextInput.focus();
				} else {
					// All inputs are filled, concatenate the values
					const inputValues = Array.from(document.querySelectorAll('.verification_code_temp'))
						.map( input => input.value )
						.join('');
					document.getElementById('verification_code').value = inputValues;
				}
			}
		</script>
		<?php
	}
);

/**
 * Register user
 *
 * @param array $_request Request.
 * @return mixed
 */
function snks_register_user( $_request ) {
	if ( isset( $_request['user_email'] ) && isset( $_request['password'] ) && isset( $_request['repeat_password'] ) ) {
		$user_email       = sanitize_email( wp_unslash( $_request['user_email'] ) );
		$user_password    = sanitize_text_field( $_request['password'] );
		$confirm_password = sanitize_text_field( $_request['repeat_password'] );
		$user_role        = sanitize_text_field( wp_unslash( $_request['user_role'] ) );
		if ( ! is_email( $user_email ) ) {
			wp_die( 'Invalid email address.' );
		}

		if ( email_exists( $user_email ) ) {
			wp_die( 'Email exists.' );
		}

		if ( $user_password !== $confirm_password ) {
			wp_die( 'Passwords do not match.' );
		}
		$verification_code = strval( wp_rand( 1000, 9999 ) );

		$user_id = wp_create_user( $user_email, $user_password, $user_email );
		$user    = new WP_User( $user_id );
		$user->set_role( $user_role );
		if ( is_wp_error( $user_id ) ) {
            //phpcs:disable
			wp_die( $user_id->get_error_message() );
            //phpcs:enable
		}
		ob_start();
		include SNKS_DIR . 'templates/email-template.php';
		$template = ob_get_clean();

		$message = str_replace(
			array(
				'{logo}',
				'{title}',
				'{sub_title}',
				'{content_placeholder}',
				'{text_1}',
				'{text_2}',
				'{text_3}',
				'{button_text}',
				'{button_url}',
			),
			array(
				site_url( 'wp-content/uploads/2023/12/w2.png' ),
				'تأكيد البريد الإلكتروني',
				'لحسابك في شرينكس',
				site_url( '/wp-content/uploads/2024/04/sky-2667455_1280.jpg' ),
				'شكراً لتسجيلك في شرينكس',
				'رمز التحقق الخاص بك هو',
				$verification_code,
				'تأكيد البريد الإلكتروني',
				get_the_permalink( 1315 ),
			),
			$template
		);

		update_user_meta( $user_id, 'verification_code', $verification_code );

		$to      = $user_email;
		$subject = 'تأكيد البريد الإلكتروني لحسابك في شرينكس';
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: شرينكس <customer@shrinks.clinic>',
		);
		$emailed = wp_mail( $to, $subject, $message, $headers );
		if ( ! isset( $_request['account-type'] ) ) {
			wp_safe_redirect( esc_url( home_url( '/verification' ) ) );
			exit;
		} else {
			return $user;
		}
	}
}
add_action( 'jet-form-builder/custom-action/register_patient', 'anony_register_user' );

/**
 * Verify email
 *
 * @return void
 */
function verify_email() {
    //phpcs:disable
    $_request = $_POST;
    //phpcs:enable
	if ( isset( $_request['verification_code'] ) ) {
		$verification_code = sanitize_text_field( wp_unslash( $_request['verification_code'] ) );
		//phpcs:disable
		// Query for the user based on the meta key and value.
		$args = array(
			'meta_key'   => 'verification_code',
			'meta_value' => $verification_code,
		);
		//phpcs:enable

		// Get the user(s) matching the criteria.
		$users = get_users( $args );

		if ( ! empty( $users ) ) {
			$user        = reset( $users );
			$stored_code = get_user_meta( $user->ID, 'verification_code', true );

			if ( $stored_code && $verification_code === $stored_code ) {
				delete_user_meta( $user->ID, 'verification_code' );
				update_user_meta( $user->ID, 'is_verified', true );
				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->user_login, $user );
				wp_safe_redirect( esc_url( home_url( '/consulting-form' ) ) );
				exit;
			} else {
				wp_die( 'Invalid verification code.' );
			}
		}
	}
}
add_action( 'init', 'verify_email' );

/**
 * Verify user status
 *
 * @param object $user_login User login.
 * @param object $user User object.
 * @return void
 */
function verify_user_status( $user_login, $user ) {
	if ( ! snks_is_patient() ) {
		return;
	}
	if ( $user && is_a( $user, 'WP_User' ) ) {
		$is_verified = get_user_meta( $user->ID, 'is_verified', true );

		if ( ! $is_verified || empty( $is_verified ) ) {
			wp_destroy_current_session();
			wp_clear_auth_cookie();
			wp_set_current_user( 0 );
			wp_safe_redirect( esc_url( home_url( '/verification' ) ) );
			exit;
		}
	}
}
add_action( 'wp_login', 'verify_user_status', 10, 2 );

add_action(
	'template_redirect',
	function () {
		if ( is_page( 'therapeutic-programme-booking' ) ) {
			if ( ! is_user_logged_in() ) {
				// Redirect to login.
				wp_safe_redirect( esc_url( get_the_permalink( 303 ) ) );
				exit;
			}
		}
	}
);

add_action(
	'wp_logout',
	function () {
		// // Redirect to login.
		wp_safe_redirect( esc_url( site_url( '/' ) ) );
		exit;
	}
);

/**
 * Check if nickname exists
 *
 * @param string $nickname Nickname.
 * @return bool
 */
function snks_nickname_exists( $nickname ) {
	//phpcs:disable
	$args = array(
		'meta_query' => array(
			array(
				'key'     => 'nickname',
				'value'   => $nickname,
				'compare' => '=',
			),
		),
	);
	//phpcs:enable

	$user_query = new WP_User_Query( $args );
	if ( ! empty( $user_query->get_results() ) ) {
		return true;
	} else {
		return false;
	}
}

add_action(
	'wp_ajax_check_nickname',
	function () {
		if ( ! snks_is_patient() ) {
			return;
		}
		$_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
		// Verify the nonce.
		if ( isset( $_request['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_request['nonce'] ), 'check_nickname_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}
		wp_send_json(
			array(
				'resp' => snks_nickname_exists( $_request['nickName'] ),
			)
		);
		die;
	}
);
