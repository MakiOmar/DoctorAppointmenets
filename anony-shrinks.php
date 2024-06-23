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
		?>
		<a class="trigger-error" href="#"></a>
		<?php
	}
);

