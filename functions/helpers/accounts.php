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
 * Using user blocker plugin meta key.
 * Blocks a user by setting a meta field to mark the user as blocked.
 *
 * @param int $user_id The ID of the user to block.
 */
function snks_block_user( $user_id ) {
	// Set a user meta field to mark the user as blocked.
	update_user_meta( $user_id, 'is_active', 'n' );

	/**
	 * Fires after a user is blocked.
	 *
	 * @param int $user_id The ID of the blocked user.
	 */
	do_action( 'user_blocked', $user_id );
}

/**
 * Unblocks a user by removing the blocked status from user meta.
 *
 * @param int $user_id The ID of the user to unblock.
 */
function snks_unblock_user( $user_id ) {
	// Remove the blocked status from user meta.
	delete_user_meta( $user_id, 'is_active' );

	/**
	 * Fires after a user is unblocked.
	 *
	 * @param int $user_id The ID of the unblocked user.
	 */
	do_action( 'user_unblocked', $user_id );
}

/**
 * Checks if a user is blocked
 *
 * @param int $user_id The ID of the user to unblock.
 */
function snks_is_blocked( $user_id ) {
	$is_active = get_user_meta( $user_id, 'is_active', true );
	return 'n' === $is_active;
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
 * @param mixed $user_id User's ID.
 * @return bool
 */
function snks_is_doctor( $user_id = false ) {
	$r = false;
	if ( is_user_logged_in() ) {
		$r = false;
	}
	if ( ! $user_id ) {
		$user = wp_get_current_user();
	} else {
		$user = get_user_by( 'ID', $user_id );
	}
	if ( in_array( 'doctor', $user->roles, true ) ) {
		$r = true;
	}
	if ( in_array( 'administrator', $user->roles, true ) ) {
		$r = true;
	}

	return $r;
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
 * Check if is clinic_manager user
 *
 * @return bool
 */
function snks_is_clinic_manager() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	if ( ! in_array( 'clinic_manager', $user->roles, true ) ) {
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

		$user_id = wp_create_user( $user_email, $user_password, $user_email );
		$user    = new WP_User( $user_id );
		$user->set_role( $user_role );
		if ( is_wp_error( $user_id ) ) {
            //phpcs:disable
			wp_die( $user_id->get_error_message() );
            //phpcs:enable
		}
		return $user;
	}
}
add_action( 'jet-form-builder/custom-action/register_patient', 'anony_register_user' );

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
 * Get settings' doctor's id
 *
 * @return int
 */
function snks_get_settings_doctor_id() {
	$id = get_current_user_id();
	if ( snks_is_clinic_manager() ) {
		$linked_doctor = get_user_meta( get_current_user_id(), 'clinic_doctor_id', true );

		if ( $linked_doctor && ! empty( $linked_doctor ) ) {

			if ( $linked_doctor && ! empty( $linked_doctor )
			) {
				// Return the doctor's user ID to impersonate.
				$id = absint( $linked_doctor );
			}
		}
	}
	return $id;
}
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
	//phpcs:disable
	$meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $id ) );
	foreach ( $meta as $mid ) {
		delete_metadata_by_mid( 'user', $mid );
	}

	$wpdb->delete( $wpdb->users, array( 'ID' => $id ) );
	//phpcs:enable
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
		if ( ! ctype_digit( $username ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً رقم الموبايل يجب أن يتكون من أرقام فقط بدو أحرف أو رموز.' );
		}
		if ( strlen( $username ) !== 11 ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً رقم الموبايل يجب أن يتكون من 11 رقماَ' );
		}
		if ( empty( $password ) && ! empty( $clinic_manager_password ) ) {
			$password = sanitize_text_field( $clinic_manager_password );
		}
		if ( empty( $username ) ) {
			throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'رقم التليفون مطلوب' );
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
	//phpcs:disable WordPress.Security.NonceVerification.Missing
	$_req = $_POST;
	if ( isset( $_req['login_with'] ) && 'mobile' === $_req['login_with'] && ! is_numeric( $_req['temp-phone'] ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى ادخال رقم موبايل صحيح.' );
	}
	if ( isset( $_req['login_with'] ) && isset( $_req['doctor_login'] ) && 'mobile' === $_req['login_with'] && is_numeric( $_req['temp-phone'] ) && strlen( $_req['temp-phone'] ) !== 11 ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً رقم الموبايل يجب أن يتكون من 11 رقماَ' );
	}

	if ( isset( $_req['login_with'] ) && 'mobile' !== $_req['login_with'] && ! is_email( $_req['username'] ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'يرجى إدخال بريد إلكتروني صحيح' );
	}

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
	// Check if the user is not a doctor.
	if ( isset( $_req['doctor_login'] ) && ! in_array( 'doctor', $user->roles, true ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفواً! هذا ليس حساب طبيب' );
	}
	if ( snks_is_blocked( $user->ID ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عفوا! هذا الحساب لم يعد متاحاً.' );
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
	if ( isset( $_request['tocheckout'] ) ) {
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	} elseif ( snks_is_patient() ) {
		wp_safe_redirect( add_query_arg( 'id', get_current_user_id(), site_url( 'my-bookings' ) ) );
	} else {
		wp_safe_redirect( add_query_arg( 'id', snks_get_settings_doctor_id(), site_url( 'account-setting' ) ) );
	}
		exit;
}
add_action( 'jet-form-builder/custom-action/log_patient_in', 'custom_log_patient_in', 10, 2 );
add_action( 'jet-form-builder/custom-action/loguserin', 'custom_log_patient_in', 10, 2 );


use Jet_Form_Builder\Exceptions\Action_Exception;

add_action( 'jet-form-builder/custom-action/proccess_form_data', 'custom_process_user_registration', 10, 3 );

/**
 * Processes the custom user registration form.
 *
 * @param array $request  The form data.
 * @throws \Jet_Form_Builder\Exceptions\Action_Exception Exception.
 */
function custom_process_user_registration( $request ) {

	// Sanitize form data.
	$first_name       = sanitize_text_field( wp_unslash( $request['billing_first_name'] ) );
	$last_name        = sanitize_text_field( wp_unslash( $request['billing_last_name'] ) );
	$email            = sanitize_email( wp_unslash( $request['email'] ) );
	$password         = sanitize_text_field( wp_unslash( $request['password'] ) );
	$confirm_password = sanitize_text_field( wp_unslash( $request['confirm_password'] ) );
	$phone            = sanitize_text_field( wp_unslash( $request['uname'] ) );

	// Check if passwords match.
	if ( $password !== $confirm_password ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'كلمات المرور غير متطابقة.' );
	}

	// Check if the email already exists.
	if ( email_exists( $email ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'عنوان البريد الإلكتروني هذا مستخدم بالفعل.' );
	}

	// Check if the username (phone number) already exists.
	if ( username_exists( $phone ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( 'رقم الهاتف هذا موجود بالفعل. يرجى تسجيل الدخول بنفس الرقم أو الاستكمالربرقم آخر.' );
	}

	// Register the user.
	$user_id = wp_create_user( $phone, $password, $email );

	if ( is_wp_error( $user_id ) ) {
		throw new \Jet_Form_Builder\Exceptions\Action_Exception( esc_html( $user_id->get_error_message() ) );
	}

	// Update user meta fields.
	update_user_meta( $user_id, 'billing_first_name', $first_name );
	update_user_meta( $user_id, 'billing_last_name', $last_name );
	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'billing_email', $email );

	// Assign the user role (e.g., 'customer').
	$user = new WP_User( $user_id );
	$user->set_role( 'customer' );

	// Log in the user after registration.
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $phone, $user );

	return true;
}


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

/**
 * Generates a logout link shortcode for logged-in users.
 *
 * This shortcode displays a "Log Out" link if the user is logged in.
 * When clicked, it logs the user out and redirects to the homepage.
 *
 * Usage: [custom_logout]
 *
 * @return string The HTML for the logout link, or an empty string if the user is not logged in.
 */

add_shortcode(
	'custom_logout',
	function () {
		// Check if the user is logged in.
		if ( is_user_logged_in() ) {
			// Create a nonce for logout action.
			$logout_nonce = wp_create_nonce( 'log-out' );

			// Get the logout URL with the nonce.
			if ( snks_is_doctor() ) {
				$to = '/doctor-login';
			} else {
				$to = '/login';
			}
			$logout_url = wp_logout_url( site_url( $to ) );

			// Return the logout link.
			return '<p style="text-align:center;position:relative;z-index:9999"><a href="' . esc_url( $logout_url ) . '">خروج</a></p>';
		} else {
			// If the user is not logged in, return an empty string.
			return '';
		}
	}
);
