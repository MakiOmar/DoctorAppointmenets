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
