<?php
/**
 * Plugin Name: A Shrinks
 * Plugin URI: https://makiomar.com/
 * Description: Shrinks Clinics
 * Version: 0.1
 * Author: Makiomar
 * Author URI: https://makiomar.com/
 * License: GPLv2 or later
 * Text Domain: anony-shrinks
 *
 * @package Shrinks
 */

// plugin textdomain.
define( 'SNKS_DOMAIN', 'anony-turn' );

define( 'TIMETABLE_TABLE_NAME', 'snks_provider_timetable' );
define( 'TRNS_TABLE_NAME', 'snks_booking_transactions' );
define( 'WHYSMS_SENDER_ID', 'Jalsah' );
define( 'WHYSMS_TOKEN', '391|s1StiJT5mVm1vlC5El7W7W4AYCIPqu7nyCiow5tBd3009807' );
define( 'CANCELL_AFTER', 15 );

// Ensure the vendor autoload file is required.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	//phpcs:disable
	error_log( 'PHPSpreadsheet library not found. Make sure to run "composer install".' );
	//phpcs:enable
}

// Plugin path.
define( 'SNKS_DIR', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
define( 'SNKS_LOGO', site_url( '/wp-content/uploads/2024/08/logo.jpg' ) );
define( 'SNKS_EMAIL', 'contact@jalsah.app' );
define( 'SNKS_APP_NAME', 'جَلسَة' );
define( 'SNKS_EMAIL_IMG', site_url( '/wp-content/uploads/2024/08/medical-health.png' ) );
define( 'SNKS_EMAIL_ICON', site_url( '/wp-content/uploads/2024/08/41781618489806584.png' ) );
define( 'SNKS_ARROW', site_url( '/wp-content/uploads/2024/08/left-arrow.png' ) );
define( 'SNKS_CAMERA', site_url( '/wp-content/uploads/2024/08/camera.png' ) );
define( 'SNKS_OFFLINE', site_url( '/wp-content/uploads/2024/08/offline2.png' ) );

// Plugin URI.
define( 'SNKS_URI', plugin_dir_url( __FILE__ ) );

define(
	'DAYS_ABBREVIATIONS',
	wp_json_encode(
		array(
			'Mon' => 'الإثنين',
			'Tue' => 'الثلاثاء',
			'Wed' => 'الأربعاء',
			'Thu' => 'الخميس',
			'Fri' => 'الجمعة',
			'Sat' => 'السبت',
			'Sun' => 'الأحد',
		)
	)
);
define(
	'MONTHS_FULL_NAMES',
	wp_json_encode(
		array(
			'January'   => 'يناير',
			'February'  => 'فبراير',
			'March'     => 'مارس',
			'April'     => 'أبريل',
			'May'       => 'مايو',
			'June'      => 'يونيو',
			'July'      => 'يوليو',
			'August'    => 'أغسطس',
			'September' => 'سبتمبر',
			'October'   => 'أكتوبر',
			'November'  => 'نوفمبر',
			'December'  => 'ديسمبر',
		)
	)
);
/**
 * Add a custom schedule for every 15 minutes.
 *
 * @param array $schedules Existing schedules.
 * @return array Modified schedules.
 */
function snks_add_cron_schedule( $schedules ) {
	$schedules['every_15_minutes'] = array(
		'interval' => 15 * 60, // 15 minutes in seconds.
		'display'  => __( 'Every 15 Minutes' ),
	);
	$schedules['every_5_minutes']  = array(
		'interval' => 5 * 60, // 5 minutes in seconds.
		'display'  => __( 'Every 5 Minutes' ),
	);
	$schedules['every_minute']     = array(
		'interval' => 60, // Minute in seconds.
		'display'  => __( 'Every Minute' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'snks_add_cron_schedule' );

require_once SNKS_DIR . 'functions/helpers.php';
require_once SNKS_DIR . '/vendor/autoload.php';

add_action(
	'plugins_loaded',
	function () {
		snks_require_all_files( SNKS_DIR . 'includes' );
		snks_require_all_files( SNKS_DIR . 'functions' );
		snks_require_all_files( SNKS_DIR . 'classes' );
		snks_require_all_files( SNKS_DIR . 'scripts' );
	},
	20
);

add_action(
	'init',
	function () {
		load_plugin_textdomain( SNKS_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

require_once SNKS_DIR . 'includes/timetable-table.php';
require_once SNKS_DIR . 'includes/sessions-actions-table.php';
require_once SNKS_DIR . 'includes/transaction-table.php';
/**
 * Plugin activation hook
 *
 * @return void
 */
function plugin_activation_hook() {
	snks_create_timetable_table();
	snks_create_snks_sessions_actions_table();
	snks_create_transactions_table();
}
register_activation_hook( __FILE__, 'plugin_activation_hook' );

add_filter(
	'gettext',
	function ( $translated_text, $untranslated_text ) {
		if ( 'product' === $untranslated_text ) {
			return 'الخدمة';
		}
		if ( 'Product' === $untranslated_text ) {
			return 'الخدمة';
		}
		return $translated_text;
	},
	10,
	2
);

add_filter(
	'gettext',
	function ( $translated_text, $untranslated_text, $domain ) {
		if ( 'woocommerce' === $domain && 'Have a coupon?' === $untranslated_text ) {
			return 'هل لديك كود خصم؟';
		}
		if ( 'woocommerce' === $domain && 'Click here to enter your code' === $untranslated_text ) {
			return 'إضغط هنا';
		}
		return $translated_text;
	},
	10,
	3
);

add_filter(
	'yith_wcmap_user_name_in_menu',
	function ( $display_name, $current_user ) {
		$nickname = get_user_meta( $current_user->ID, 'nickname', true );
		if ( $nickname && ! empty( $nickname ) ) {
			return $nickname;
		}
		return $display_name;
	},
	10,
	2
);
add_filter(
	'profile_image_current_upload_url',
	function () {
		return get_user_meta( get_current_user_id(), 'profile-image', true );
	}
);
add_filter(
	'anony_phone_input_temp_phone_value',
	function () {
		return get_user_meta( get_current_user_id(), 'clinic_manager_temp_phone', true );
	}
);

add_action(
	'wp_footer',
	function () {
		?>
		<a class="trigger-error" href="#"></a>
		<a class="trigger-sure" href="#"></a>
		<?php
		if ( is_page( 'profile' ) && snks_is_patient() ) {
			?>
			<input type="hidden" id="clinic_manager_temp_phone" value="<?php echo esc_attr( get_user_meta( get_current_user_id(), 'clinic_manager_temp_phone', true ) ); ?>">
			<input type="hidden" id="clinic_manager_country_code" value="<?php echo esc_attr( get_user_meta( get_current_user_id(), 'clinic_manager_country_code', true ) ); ?>">
			<?php
		}
	}
);

define(
	'CLINICS_COLORS',
	wp_json_encode(
		array(
			'color_1'  => array( '#dcf5ff', '#024059', '#012d3e' ),
			'color_2'  => array( '#d9e7ff', '#022259', '#01183e' ),
			'color_3'  => array( '#e2e9f5', '#182843', '#101b2e' ),
			'color_4'  => array( '#eae2f5', '#2b1843', '#1d102e' ),
			'color_5'  => array( '#e9d9fe', '#290259', '#1c013e' ),
			'color_6'  => array( '#d9fdff', '#025459', '#013b3e' ),
			'color_7'  => array( '#e4f3f4', '#1a3e40', '#122b2c' ),
			'color_8'  => array( '#d9fff5', '#025942', '#013e2d' ),
			'color_9'  => array( '#e2f5f0', '#184338', '#102e26' ),
			'color_10' => array( '#f7ffd9', '#475902', '#475902' ),
			'color_11' => array( '#f0f3e4', '#383f1c', '#272c13' ),
			'color_12' => array( '#ffe4d9', '#591b02', '#3e1201' ),
			'color_13' => array( '#f5e7e2', '#432418', '#2e1810' ),
			'color_14' => array( '#fff7d9', '#594602', '#3e3001' ),
			'color_15' => array( '#ffd9ec', '#59012e', '#3e0121' ),
			'color_16' => array( '#f5e2e9', '#f5e2e9', '#f5e2e9' ),
			'color_17' => array( '#ffd9f4', '#590240', '#3e012d' ),
			'color_18' => array( '#f5e2ef', '#431837', '#2e1026' ),
			'color_19' => array( '#f7d9ff', '#460259', '#30013e' ),
			'color_20' => array( '#ececec', '#2d2d2d', '#1f1f1f' ),
		)
	)
);

/**
 * Sets a cookie if the 'doctor_id' query variable is present.
 *
 * This function checks for the 'doctor_id' in query variables and, if present,
 * sets a cookie with a value of '2' for one day.
 *
 * @global WP $wp The global instance of the WP class, containing query variables.
 */
function set_doctor_id_cookie() {
	global $wp;
	if ( isset( $wp->query_vars ) && isset( $wp->query_vars['doctor_id'] ) ) {
		$clinic_color   = get_user_meta( snks_url_get_doctors_id(), 'clinic_colors', true );
		$clinics_colors = json_decode( CLINICS_COLORS );
		$expire_time    = time() + DAY_IN_SECONDS; // 1 day expiration time.
		if ( ! empty( $clinic_color ) ) {
			$clinic_colors = 'color_' . $clinic_color;
			setcookie( 'light_color', esc_attr( $clinics_colors->$clinic_colors[0] ), $expire_time, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			setcookie( 'dark_color', esc_attr( $clinics_colors->$clinic_colors[1] ), $expire_time, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			setcookie( 'darker_color', esc_attr( $clinics_colors->$clinic_colors[2] ), $expire_time, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}
}
add_action( 'wp', 'set_doctor_id_cookie' );

// Stop logging deprecated notices.
add_filter( 'deprecated_file_trigger_error', '__return_false' );
add_filter( 'deprecated_function_trigger_error', '__return_false' );
add_filter( 'deprecated_argument_trigger_error', '__return_false' );
add_filter( 'deprecated_hook_trigger_error', '__return_false' );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action(
		'wp_loaded',
		function () {
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		}
	);
}
add_action(
	'woocommerce_checkout_order_review',
	function () {
		$form_data = get_transient( snks_form_data_transient_key() );
		// Check if the session data exists and contains the expected keys.
		if ( is_array( $form_data ) ) {
			//phpcs:disable
			echo consulting_session_pricing_table_shortcode( $form_data );
			//phpcs:enable

			echo '<h2 style="margin:20px 0;color:#fff;font-size:25px;text-align:center">إختر طريقة الدفع المناسبة</h2>';
		}
	}
);

/**
 * Check the user's cookie if they have it.
 * Create one if they don't.
 */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_COOKIE['booking_trans_key'] ) && ! wp_doing_ajax() ) {
			setcookie( 'booking_trans_key', substr( md5( time() . wp_rand() ), 0, 8 ), time() + 60 * 60, '/' );
		}
	}
);

add_filter(
	'anwv_loading_with',
	function ( $arr ) {
		$arr[] = 'account-setting';
		return $arr;
	}
);
// Register the widget with Elementor.
add_action(
	'elementor/widgets/register',
	function ( $widgets_manager ) {
		require_once plugin_dir_path( __FILE__ ) . 'clinic-colors-widget.php';
		$widgets_manager->register( new \Clinic_Colors_Widget() );
	}
);

/**
 * Custom error handler to suppress specific error messages in WordPress.
 *
 * This handler checks if an error message contains any of the excluded text patterns
 * and prevents it from being logged. The list of patterns can be modified via a filter.
 *
 * @param int    $errno   The level of the error raised.
 * @param string $errstr  The error message.
 * @param string $errfile The filename in which the error occurred.
 * @param int    $errline The line number where the error occurred.
 * @return bool True to suppress the error, false to proceed with default error handling.
 */
function snks_error_handler( $errno, $errstr, $errfile, $errline ) {
	// Define default text patterns to exclude from logging.
	$exclude_patterns = array(
		'_load_textdomain_just_in_time',
	);

	/**
	 * Filter the array of text patterns to exclude from logging.
	 *
	 * @param array $exclude_patterns Array of text patterns to check against error messages.
	 */
	$exclude_patterns = apply_filters( 'snks_error_handler_exclude_patterns', $exclude_patterns );

	// Loop through each pattern and check if it's present in the error message.
	foreach ( $exclude_patterns as $pattern ) {
		if ( strpos( $errstr, $pattern ) !== false ) {
			return true; // Skip logging if pattern is found.
		}
	}

	return false; // Proceed with default error handling if no patterns match.
}

// Set the custom error handler.
set_error_handler( 'snks_error_handler' );

add_action(
	'restrict_manage_users',
	function () {
		?>
	<a id="send-notification-button" class="button button-primary">
		<?php esc_html_e( 'Send Notification' ); ?>
	</a>
		<?php
	}
);

add_action(
	'admin_footer',
	function () {
		$screen = get_current_screen();
		if ( 'users' !== $screen->id ) {
			return;
		}
		?>
	<style>

		#send-notification-modal h2 {
			margin-top: 0;
		}

		#send-notification-modal {
			position: fixed;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background: #fff;
			padding: 20px;
			border: 1px solid #ccc;
			z-index: 1000;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
			width: 400px;
		}

		.modal-content {
			position: relative;
		}

		.close-button {
			position: absolute;
			top: -10px;
			right: 10px;
			background: none;
			border: none;
			font-size: 20px;
			cursor: pointer;
			height: 30px;
			width: 30px;
			background-color: #d51b1b;
			color: #fff;
			border-radius: 50%;
		}

		.form-row {
			display: flex;
			flex-direction: row;
			align-items: center;
			margin-bottom: 15px;
		}

		.form-row label {
			flex: 1;
			font-weight: bold;
		}

		.form-row input,
		.form-row textarea,
		.form-row button {
			flex: 2;
		}

		.form-row textarea {
			resize: vertical;
		}
	</style>
	<div id="send-notification-modal" style="display:none;">
		<div class="modal-content">
			<button type="button" id="close-modal" class="close-button">&times;</button>
			<h2><?php esc_html_e( 'Send Notification' ); ?></h2>
			<form id="send-notification-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
				<input type="hidden" name="action" value="send_notification">
				<div class="form-row">
					<label for="notif-title"><?php esc_html_e( 'Title' ); ?></label>
					<input type="text" id="notif-title" name="title" required>
				</div>
				<div class="form-row">
					<label for="notif-content"><?php esc_html_e( 'Content' ); ?></label>
					<textarea id="notif-content" name="notif_content" required></textarea>
				</div>
				<div class="form-row">
					<label for="notif-link"><?php esc_html_e( 'Link' ); ?></label>
					<input type="url" id="notif-link" name="link">
				</div>
				<input type="hidden" id="notif-user-ids" name="user_ids">
				<div class="form-row">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Send' ); ?></button>
				</div>
			</form>
		</div>
	</div>
		
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const closeModal = document.getElementById('close-modal');
			const modal = document.getElementById('send-notification-modal');
			const button = document.getElementById('send-notification-button');
			const userCheckboxes = document.querySelectorAll('.check-column input[type="checkbox"]');
			const userIdsField = document.getElementById('notif-user-ids');
			const form = document.getElementById('send-notification-form');
			// Close the modal when the close button is clicked.
			closeModal.addEventListener('click', function () {
				modal.style.display = 'none';
			});
			// Show the modal when the button is clicked.
			button.addEventListener('click', function (event) {
				event.preventDefault();
				const selectedUserIds = Array.from(userCheckboxes)
					.filter(checkbox => checkbox.checked)
					.map(checkbox => checkbox.value)
					.join(',');

				if (!selectedUserIds) {
					alert('<?php esc_html_e( 'Please select at least one user.' ); ?>');
					return;
				}

				userIdsField.value = selectedUserIds;
				modal.style.display = 'block';
			});

			// Handle form submission with AJAX.
			form.addEventListener('submit', function (event) {
				event.preventDefault(); // Prevent default form submission.

				const formData = new FormData(form);

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: formData,
				})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							alert(data.data.message);
							modal.style.display = 'none';
							//location.reload(); // Reload the page to update the user table.
						} else {
							alert(data.data.message);
						}
					})
					.catch(error => {
						console.error('Error:', error);
						alert('<?php esc_html_e( 'Failed to send notification. Please try again.' ); ?>');
					});
			});
		});
	</script>
		<?php
	}
);
add_action(
	'wp_ajax_send_notification',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized' ) ) );
		}
		//phpcs:disable
		$_req     = $_POST;
		//phpcs:enable
		$title    = isset( $_req['title'] ) ? sanitize_text_field( $_req['title'] ) : '';
		$content  = isset( $_req['notif_content'] ) ? sanitize_text_field( $_req['notif_content'] ) : '';
		$link     = isset( $_req['link'] ) ? esc_url_raw( $_req['link'] ) : '';
		$user_ids = isset( $_req['user_ids'] ) ? explode( ',', sanitize_text_field( $_req['user_ids'] ) ) : array();

		if ( empty( $title ) || empty( $content ) || empty( $user_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data provided.' ) ) );
		}

		if ( class_exists( 'FbCloudMessaging\AnonyengineFirebase' ) ) {
			$firebase = new \FbCloudMessaging\AnonyengineFirebase();
			$errors   = array();

			foreach ( $user_ids as $user_id ) {
				$response = $firebase->trigger_notifier( $title, $content, absint( $user_id ) );
				if ( $response && false !== strpos( $response, 400 ) ) {
					// Translators: %d is the user ID who failed to receive the notification.
					$errors[] = sprintf( esc_html__( 'Failed to send notification to user ID %d: Unexpected error.', 'text-domain' ), $user_id );
				}
			}

			if ( ! empty( $errors ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Some notifications failed to send.', 'text-domain' ),
					)
				);
				die;
			} else {
				wp_send_json_success( array( 'message' => esc_html__( 'All notifications sent successfully.', 'text-domain' ) ) );
				die;
			}
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Firebase class not found.', 'text-domain' ) ) );
			die;
		}
	}
);
add_action(
	'wp_head',
	function () {
		?>
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="0">
	<script>
			window.addEventListener( "pageshow", function ( event ) {
				var historyTraversal = event.persisted || 
										( typeof window.performance != "undefined" && 
											window.performance.navigation.type === 2 );
				if ( historyTraversal ) {
					// Handle page restore.
					window.location.reload( true );
				}
				});

	</script>
		<?php
	}
);

add_action(
	'wp_footer',
	function () {
		if ( is_page( 'account-setting' ) ) {
			$disabled         = snks_get_inactive_attendance_types();
			$disabled_clinics = snks_disabled_clinics();
			$value            = ! empty( $disabled ) ? implode( '-', $disabled ) : '';
			$_clinics_value   = ! empty( $disabled_clinics ) ? implode( '|', $disabled_clinics ) : '';
			?>
			<input type="text" style="display:none" id="disabled-attendance-types" value="<?php echo esc_attr( $value ); ?>">
			<input type="text" style="display:none" id="disabled-clinics" value="<?php echo esc_attr( $_clinics_value ); ?>">
			<?php
		}
	}
);

add_filter(
	'wc_kashier_payment_icons',
	function ( $list_icons ) {
		$temp = array();
		foreach ( $list_icons as $index => $icon ) {
			if ( 'credit-card' === $index ) {
				$temp[ $index ] = '<div class="kasheir-method"><img class="kashier-visa-icon kashier-icon" alt="visa" src="/wp-content/uploads/2025/02/cards.png"></div>';
			} elseif ( 'meeza-wallet' === $index ) {
				$temp[ $index ] = '<div class="kasheir-method"><img class="kashier-visa-icon kashier-icon" alt="visa" src="/wp-content/uploads/2025/02/wallets.png"></div>';
			} else {
				$temp[ $index ] = '';
			}
		}
		return $temp;
	}
);

add_filter(
	'gettext',
	function ( $translated_text, $text ) {
		if ( stripos( $text, 'There was an error processing your order. Please check for any charges' ) !== false ) {
			$translated_text = 'حدث خطأ أثناء عملية الحجز، ربما لأن الموعد تم حجزه من قبل عميل آخر ويرجى إعادة الحجز مرة أخرى.';
		}
		return $translated_text;
	},
	10,
	2
);
