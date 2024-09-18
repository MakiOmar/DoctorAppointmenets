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
 * Get patient details
 *
 * @param int $current_user_id User ID.
 * @return array
 */
function snks_user_details( $current_user_id ) {
	global $wpdb;

	$sql = $wpdb->prepare(
		"
		SELECT meta_key, meta_value
		FROM {$wpdb->prefix}usermeta
		WHERE user_id = %d
		AND meta_key IN ('billing_first_name', 'billing_last_name', 'billing_phone', 'whatsapp', 'about-me', 'certificates', 'profile-image')
	",
		$current_user_id
	);
	//phpcs:disable
	$results = $wpdb->get_results( $sql );
	//phpcs:enable

	$meta_values = array();

	foreach ( $results as $row ) {
		$meta_values[ $row->meta_key ] = $row->meta_value;
	}

	return( $meta_values );
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
				border: 1px solid #024059;
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
				background-color: #024059;
				border: 1px solid #024059;
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
/**
 * Delete user.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $id       User ID.
 * @return bool True when finished.
 */
function snks_wp_delete_user( $id ) {
	global $wpdb;

	if ( ! is_numeric( $id ) ) {
		return false;
	}

	$id   = (int) $id;
	$user = new WP_User( $id );

	if ( ! $user->exists() ) {
		return false;
	}

	$meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $id ) );
	foreach ( $meta as $mid ) {
		delete_metadata_by_mid( 'user', $mid );
	}

	$wpdb->delete( $wpdb->users, array( 'ID' => $id ) );

	clean_user_cache( $user );

	return true;
}
add_action(
	'jet-form-builder/custom-action/add_clinic_manager',
	function ( $_request ) {
		$current_user_id = get_current_user_id();
		// Sanitize input data.
		$email                   = sanitize_email( $_request['email'] );
		$username                = sanitize_text_field( $_request['phone'] ); // Use phone as username.
		$password                = sanitize_text_field( $_request['password'] );
		$clinic_manager_email    = get_user_meta( $current_user_id, 'clinic_manager_email', true );
		$clinic_manager_password = get_user_meta( $current_user_id, 'clinic_manager_password', true );
		$clinic_manager_phone    = get_user_meta( $current_user_id, 'clinic_manager_phone', true );
		if ( empty( $password ) && ! empty( $clinic_manager_password ) ) {
			$password = sanitize_text_field( $clinic_manager_password );
		}
		if ( empty( $email ) && ! empty( $clinic_manager_email ) && is_email( $clinic_manager_email ) ) {
			$clinic_manager = get_user_by( 'email', $email );
			if ( $clinic_manager ) {
				snks_wp_delete_user( $clinic_manager->ID );
				delete_user_meta( $current_user_id, 'clinic_manager_email' );
				delete_user_meta( $current_user_id, 'clinic_manager_phone' );
				delete_user_meta( $current_user_id, 'clinic_manager_temp_phone' ); //phpcs:disable
				delete_user_meta( $current_user_id, 'clinic_manager_country_code' );
				delete_user_meta( $current_user_id, 'clinic_manager_password' );
				delete_user_meta( $current_user_id, 'clinic_manager_id' );//phpcs:enable
			}
		}
		// Validate email.
		if ( ! is_email( $email ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'البريد الإلكتروني غير صحيح' );
		}

		// Check if username is unique.
		if ( $clinic_manager_phone !== $username && username_exists( $username ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'رقم التليفون موجود بالفعل' );
		}

		// Check if email is unique.
		if ( $clinic_manager_email !== $email && email_exists( $email ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'البريد الإلكتروني موجود بالفعل' );
		}

		if ( empty( $clinic_manager_email ) ) {
			// Create a new user if the email is unique.
			$user_id = wp_create_user( $username, $password, $email );

			// Check for errors in user creation.
			if ( is_wp_error( $user_id ) ) {
				throw new \Jet_Form_Builder\Exceptions\Action_Exception( wp_kses_post( $user_id->get_error_message() ) );
			}
			// Set the user role to 'clinic_manager'.
			$clinic_manager = new WP_User( $user_id );
			$clinic_manager->set_role( 'clinic_manager' );
		} else {
			$clinic_manager = get_user_by( 'email', $clinic_manager_email );
			if ( $clinic_manager ) {
				snks_wp_delete_user( $clinic_manager->ID );
				// Create a new user if the email is unique.
				$user_id = wp_create_user( $username, $password, $email );

				// Check for errors in user creation.
				if ( is_wp_error( $user_id ) ) {
					throw new \Jet_Form_Builder\Exceptions\Action_Exception( wp_kses_post( $user_id->get_error_message() ) );
				}
				// Set the user role to 'clinic_manager'.
				$clinic_manager = new WP_User( $user_id );
				$clinic_manager->set_role( 'clinic_manager' );
			}
		}
		if ( $clinic_manager ) {
			// Set additional user meta.
			update_user_meta( $user_id, 'billing_phone', $username ); // Save phone number as meta.

			// Associate the new clinic manager with the current doctor.
			update_user_meta( $current_user_id, 'clinic_manager_email', $email );
			update_user_meta( $current_user_id, 'clinic_manager_phone', $username );
			update_user_meta( $current_user_id, 'clinic_manager_temp_phone', $_POST['temp-phone'] ); //phpcs:disable
			update_user_meta( $current_user_id, 'clinic_manager_country_code', $_POST['country_code'] );
			update_user_meta( $current_user_id, 'clinic_manager_password', $password );
			update_user_meta( $current_user_id, 'clinic_manager_id', $user_id );//phpcs:enable
			update_user_meta( $user_id, 'clinic_doctor_id', $current_user_id );
		}
	}
);

/**
 * Custom log in action for JetFormBuilder.
 *
 * @param array $_request   The submitted form data.
 * @return void
 * @throws \Jet_Form_Builder\Exceptions\Action_Exception If missing or not correct user credintials.
 */
function custom_log_patient_in( $_request ) {
	// Sanitize and retrieve username and password from the request.
	$username = isset( $_request['username'] ) ? sanitize_text_field( $_request['username'] ) : '';
	$password = isset( $_request['password'] ) ? sanitize_text_field( $_request['password'] ) : '';

	// Check if the username and password are provided.
	if ( empty( $username ) || empty( $password ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى تعبئة البيانات كاملة' );
	}

	// Determine if the username is an email.
	if ( is_email( $username ) ) {
		// Get the user by email.
		$user = get_user_by( 'email', $username );
	} else {
		// Get the user by username.
		$user = get_user_by( 'login', $username );
	}

	// Check if the user exists.
	if ( ! $user ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفوا! بيانات الدخول غير صحيحة' );
	}

	// Validate the password.
	if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفوا! كلمة المرور غير صحيحة' );
	}

	// Log the user in.
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true ); // The second parameter is true to remember the user.
	/**
	 * Fires after the user has successfully logged in.
	 *
	 * @since 1.5.0
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       WP_User object of the logged-in user.
	 */
	do_action( 'wp_login', $user->user_login, $user );

	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}
add_action( 'jet-form-builder/custom-action/log_patient_in', 'custom_log_patient_in', 10, 2 );

/**
 * Allow '+' character in WordPress usernames
 */
add_filter(
	'sanitize_user',
	function ( $username, $raw_username ) {
		// Allow '+' character by defining allowed characters.
		$username = preg_replace( '/[^a-zA-Z0-9._\-+]/', '', $raw_username );

		return $username;
	},
	999,
	2
);

