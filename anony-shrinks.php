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

// Plugin path.
define( 'SNKS_DIR', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );

// Plugin URI.
define( 'SNKS_URI', plugin_dir_url( __FILE__ ) );

require_once SNKS_DIR . 'functions/helpers.php';

add_action(
	'plugins_loaded',
	function () {
		snks_require_all_files( SNKS_DIR . 'includes' );
		snks_require_all_files( SNKS_DIR . 'functions' );
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
/**
 * Plugin activation hook
 *
 * @return void
 */
function plugin_activation_hook() {
	snks_create_timetable_table();
	snks_create_snks_sessions_actions_table();
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
add_action(
	'wp_footer',
	function () {
		return;
		if ( is_front_page() || ( ! is_page( 682 ) && ! is_page( 1194 ) ) || snks_is_doctor() ) {
			return;
		}
		?>
		<div id="call-customer-care">
			<a class="customer-care-chat" href="#" title="خدمة العملائ">
				<svg width="69" height="69" viewBox="0 0 69 69" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M55.0001 68.6988H14.1952C10.0261 68.6988 6.27586 66.811 3.75828 63.8476C1.72751 61.456 0.497726 58.3653 0.497726 55.0014V14.195C0.497726 10.2778 2.16338 6.73138 4.82248 4.22936C7.2778 1.91839 10.5794 0.497559 14.1966 0.497559H55.0016C59.0716 0.497559 62.7425 2.29624 65.2559 5.13931C67.3956 7.55784 68.699 10.7321 68.699 14.1964V55.0014C68.699 58.4204 67.4296 61.5565 65.3394 63.9622C62.8232 66.8591 59.1169 68.6988 55.0016 68.6988H55.0001Z" fill="#EAF2FA"/>
					<path d="M54.9011 57.2643C54.3605 57.9351 53.5312 58.3738 52.5844 58.3738H16.417C15.282 58.3738 14.3169 57.7441 13.8145 56.8398C13.4904 56.2553 13.3588 55.5562 13.5003 54.8359C15.3754 45.3019 24.6193 41.1838 28.7771 39.7913C30.4243 40.8625 32.3886 41.4838 34.5 41.4838C36.6114 41.4838 38.5757 40.8611 40.2215 39.7913C44.3793 41.1824 53.6246 45.3005 55.4997 54.8359C55.678 55.7388 55.4247 56.6119 54.8997 57.2643H54.9011ZM33.515 33.1173H35.4C36.177 33.1173 36.8732 33.4768 37.3261 34.0428C40.7381 34.0315 43.3193 33.2022 44.6482 32.2767C44.8845 31.4021 45.0105 30.4837 45.0105 29.5341V25.4315C45.0105 22.7243 43.9859 20.2534 42.3032 18.3896C41.3409 17.3254 40.1663 16.4594 38.8403 15.8593C37.5171 15.2593 36.0468 14.9225 34.4986 14.9225C31.3725 14.9225 28.5634 16.2895 26.6373 18.4547C24.9901 20.3114 23.9895 22.754 23.9895 25.4301V29.5327C23.9895 32.0503 24.8754 34.3612 26.3529 36.1698C28.2789 38.5332 31.2126 40.0403 34.4986 40.0403C37.5214 40.0403 40.2456 38.7638 42.1631 36.7232C42.2113 36.6708 42.2608 36.617 42.3089 36.5647C40.8272 36.9269 39.1474 37.1279 37.3247 37.1321C36.8718 37.6968 36.177 38.0577 35.3986 38.0577H33.5136C32.1508 38.0577 31.0427 36.9496 31.0427 35.5868C31.0427 34.224 32.1508 33.1159 33.5136 33.1159L33.515 33.1173ZM22.0012 31.9993C22.5064 31.9993 22.9154 31.5903 22.9154 31.0851V24.0928C23.0937 17.8547 28.2195 12.8351 34.5 12.8351C40.7805 12.8351 45.7563 17.7061 46.0761 23.814V31.0865C46.0761 31.3356 46.1766 31.562 46.3393 31.7276C45.452 33.5291 41.6508 34.9287 37.2539 34.9287H36.8421C36.5902 34.3825 36.0397 34.0032 35.4 34.0032H33.515C32.6376 34.0032 31.9272 34.7136 31.9272 35.591C31.9272 36.4684 32.6376 37.1788 33.515 37.1788H35.4C36.0411 37.1788 36.5916 36.7996 36.8421 36.2533H37.2539C42.6033 36.2533 46.7865 34.4603 47.6795 31.9441C49.8885 31.6116 51.5853 29.7096 51.5853 27.4071V26.3981C51.5853 24.2329 50.0867 22.4229 48.0715 21.9361C46.8941 15.5126 41.2588 10.6274 34.5 10.6274C27.7411 10.6274 22.1074 15.5112 20.9285 21.9346C18.9091 22.4186 17.4062 24.2301 17.4062 26.3981V27.4071C17.4062 29.9445 19.4638 32.0022 22.0012 32.0022V31.9993Z" fill="#78A8B6"/>
				</svg>
			</a>
		</div>
		<?php
	}
);

