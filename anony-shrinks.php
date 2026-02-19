<?php
/**
 * Plugin Name: A Shrinks
 * Plugin URI: https://makiomar.com/
 * Description: Shrinks Clinics
 * Version: 1.0.303
 * Author: Makiomar
 * Author URI: https://makiomar.com/
 * License: GPLv2 or later
 * Text Domain: anony-shrinks
 *
 * @package Shrinks
 */

// plugin textdomain.
define( 'SNKS_DOMAIN', 'anony-turn' );

// Load text domain for translations
add_action( 'init', function() {
	load_plugin_textdomain( 'shrinks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});

define( 'TIMETABLE_TABLE_NAME', 'snks_provider_timetable' );
define( 'TRNS_TABLE_NAME', 'snks_booking_transactions' );
define( 'WHYSMS_SENDER_ID', 'Jalsah' );
define( 'WHYSMS_TOKEN', '299|dXuaaScFgVVEUu1FcRa8ApgmE5p0uY6dmsUfp6gR2d0970da' );
define( 'CANCELL_AFTER', 15 );
define( 'IL_TO_EG', true );

// JWT Secret for AI Integration
define( 'JWT_SECRET', 'jalsah-ai-secret-key-2024-v1' );

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
define( 'SNKS_PLUGIN_SLUG', plugin_basename( __FILE__ ) );

require SNKS_DIR . '/plugin-update-checker/plugin-update-checker.php';

// Use JSON metadata mode instead of GitHub repository mode.
// The JSON file is hosted on GitHub as a raw file for easy updates.
$my_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://raw.githubusercontent.com/MakiOmar/DoctorAppointmenets/master/plugin-update-checker/examples/plugin.json',
	__FILE__,
	SNKS_PLUGIN_SLUG
);

// Add a plugin row action to clear the update cache manually.
add_filter(
	'plugin_action_links_' . SNKS_PLUGIN_SLUG,
	function ( $links ) {
		if ( current_user_can( 'update_plugins' ) ) {
			$url = wp_nonce_url(
				add_query_arg(
					array(
						'snks_clear_update_cache' => '1',
					),
					admin_url( 'plugins.php' )
				),
				'snks_clear_update_cache'
			);

			$links['snks_clear_update_cache'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Clear update cache', 'anony-shrinks' )
			);
		}

		return $links;
	}
);

// Handle cache clear request.
add_action(
	'admin_init',
	function () use ( $my_update_checker ) {
		if ( ! is_admin() || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( empty( $_GET['snks_clear_update_cache'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'snks_clear_update_cache' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( method_exists( $my_update_checker, 'resetUpdateState' ) ) {
			$my_update_checker->resetUpdateState();
		}

		update_option( 'snks_update_cache_cleared_at', current_time( 'mysql' ) );

		wp_safe_redirect( admin_url( 'plugins.php?snks_update_cache_cleared=1' ) );
		exit;
	}
);

// Show admin notice after cache clear.
add_action(
	'admin_notices',
	function () {
		if ( empty( $_GET['snks_update_cache_cleared'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$time = get_option( 'snks_update_cache_cleared_at' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: date and time */
						__( 'Shrinks update cache cleared successfully at %s. You can now click "Check for updates" again.', 'anony-shrinks' ),
						$time ? $time : current_time( 'mysql' )
					)
				);
				?>
			</p>
		</div>
		<?php
	}
);

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

/**
 * Allow SVG tags in WordPress content sanitization
 * This prevents SVG icons from being stripped when shortcodes are rendered
 */
add_filter(
	'wp_kses_allowed_html',
	function ( $allowed, $context ) {
		// Add SVG support for all contexts where HTML is allowed
		$allowed['svg'] = array(
			'width'          => true,
			'height'         => true,
			'fill'           => true,
			'viewbox'        => true,
			'viewBox'        => true,
			'xmlns'          => true,
			'xmlns:xlink'    => true,
			'version'        => true,
			'id'             => true,
			'class'          => true,
			'style'          => true,
			'xml:space'      => true,
			'stroke'         => true,
			'stroke-width'   => true,
		);
		$allowed['g'] = array(
			'id'    => true,
			'class' => true,
			'style' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
		);
		$allowed['path'] = array(
			'd'          => true,
			'fill'       => true,
			'stroke'     => true,
			'stroke-width' => true,
			'style'      => true,
		);
		$allowed['circle'] = array(
			'cx'     => true,
			'cy'     => true,
			'r'      => true,
			'fill'   => true,
			'stroke' => true,
			'stroke-width' => true,
			'style'  => true,
		);
		$allowed['rect'] = array(
			'width'  => true,
			'height' => true,
			'x'      => true,
			'y'      => true,
			'fill'   => true,
			'style'  => true,
		);
		$allowed['title'] = true;
		
		// Allow input/checkbox elements for booking shortcodes
		$allowed['input'] = array(
			'type'        => true,
			'name'        => true,
			'value'       => true,
			'id'          => true,
			'class'       => true,
			'checked'     => true,
			'readonly'    => true,
			'disabled'    => true,
			'data-date'   => true,
			'data-doctor' => true,
			'data-patient'=> true,
			'aria-label'  => true,
			'style'       => true,
		);

		// Allow other form controls
		$allowed['form'] = array(
			'action'      => true,
			'method'      => true,
			'class'       => true,
			'id'          => true,
			'style'       => true,
			'enctype'     => true,
			'novalidate'  => true,
			'target'      => true,
			'autocomplete'=> true,
		);
		$allowed['textarea'] = array(
			'name'        => true,
			'id'          => true,
			'class'       => true,
			'style'       => true,
			'rows'        => true,
			'cols'        => true,
			'placeholder' => true,
			'required'    => true,
			'readonly'    => true,
			'disabled'    => true,
		);
		$allowed['select'] = array(
			'name'        => true,
			'id'          => true,
			'class'       => true,
			'style'       => true,
			'multiple'    => true,
			'size'        => true,
			'required'    => true,
			'disabled'    => true,
		);
		$allowed['option'] = array(
			'value'     => true,
			'selected'  => true,
			'label'     => true,
		);
		$allowed['optgroup'] = array(
			'label'    => true,
			'disabled' => true,
		);
		$allowed['button'] = array(
			'type'         => true,
			'name'         => true,
			'value'        => true,
			'id'           => true,
			'class'        => true,
			'style'        => true,
			'disabled'     => true,
			'aria-label'   => true,
			'data-coupon'  => true,
			'data-coupon-type' => true,
			'data-date-time' => true,
			'data-attendance-type' => true,
			'data-client-name' => true,
		);
		$allowed['label'] = array(
			'for'    => true,
			'class'  => true,
			'style'  => true,
		);
		$allowed['fieldset'] = array(
			'class' => true,
			'style' => true,
			'disabled' => true,
		);
		$allowed['legend'] = array(
			'class' => true,
			'style' => true,
		);
		$allowed['datalist'] = array(
			'id' => true,
		);
		
		return $allowed;
	},
	10,
	2
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
		
		// Load AI integration
require_once SNKS_DIR . 'functions/ai-integration.php';
require_once SNKS_DIR . 'functions/admin/ai-admin.php';
require_once SNKS_DIR . 'functions/admin/ai-admin-enhanced.php';
require_once SNKS_DIR . 'functions/admin/ai-applications.php';
// Include bilingual migration if available (optional helper)
if ( file_exists( SNKS_DIR . 'functions/admin/bilingual-migration.php' ) ) {
require_once SNKS_DIR . 'functions/admin/bilingual-migration.php';
}
require_once SNKS_DIR . 'functions/admin/add-arabic-diagnoses.php';
require_once SNKS_DIR . 'functions/admin/rochtah-doctor-dashboard.php';
require_once SNKS_DIR . 'functions/admin/rochtah-slots-manager.php';
require_once SNKS_DIR . 'functions/admin/therapist-registration-settings.php';
require_once SNKS_DIR . 'functions/shortcodes/therapist-registration-shortcode.php';
require_once SNKS_DIR . 'functions/admin/test-data-populator.php';
require_once SNKS_DIR . 'functions/admin/demo-doctors-manager.php';
require_once SNKS_DIR . 'functions/admin/cleanup-demo-data.php';
require_once SNKS_DIR . 'functions/admin/bulk-diagnosis-assignment.php';
require_once SNKS_DIR . 'functions/admin/profit-settings.php';
require_once SNKS_DIR . 'functions/admin/therapist-earnings.php';
require_once SNKS_DIR . 'functions/admin/ai-transaction-processing.php';
require_once SNKS_DIR . 'functions/admin/ai-settings-export-import.php';
require_once SNKS_DIR . 'functions/admin/duplicate-users.php';
require_once SNKS_DIR . 'admin-data-migration.php';
require_once SNKS_DIR . 'includes/ai-tables-enhanced.php';
require_once SNKS_DIR . 'functions/ajax/rochtah-ajax.php';
require_once SNKS_DIR . 'functions/ajax/therapist-certificates.php';
require_once SNKS_DIR . 'functions/ajax/therapist-details.php';
require_once SNKS_DIR . 'functions/ajax/session-messages-ajax.php';
require_once SNKS_DIR . 'functions/ai-prescription.php';
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
require_once SNKS_DIR . 'includes/coupons-tables.php';
require_once SNKS_DIR . 'includes/ai-tables.php';
require_once SNKS_DIR . 'includes/session-messages-table.php';

// AI table creation hooks will be registered on init to ensure all functions are loaded

// Check for AI profit system updates on plugin load
add_action( 'init', 'snks_check_ai_profit_system_updates' );

// Ensure database structure is up to date on every page load (for development)
// Remove this in production after initial deployment
add_action( 'init', 'snks_ensure_ai_profit_database_structure' );

// Register AI table creation hooks on init to ensure all functions are loaded
add_action( 'init', 'snks_register_ai_table_hooks' );

add_filter(
	'send_password_change_email',
	function ( $send, $user, $userdata ) {
		if ( ! is_admin() || ! current_user_can( 'edit_users' ) ) {
			return $send;
		}

		global $pagenow;
		$screen_ids = array( 'user-edit.php', 'user-edit-network.php' );
		$action     = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';

		if ( in_array( $pagenow, $screen_ids, true ) && 'update' === $action ) {
			return false;
		}

		return $send;
	},
	10,
	3
);

/**
 * Register AI table creation hooks
 */
function snks_register_ai_table_hooks() {
	// Hook AI tables creation functions (with safety checks)
	if ( function_exists( 'snks_create_ai_tables' ) ) {
		add_action( 'snks_create_ai_tables', 'snks_create_ai_tables' );
	}
	if ( function_exists( 'snks_add_ai_meta_fields' ) ) {
		add_action( 'snks_add_ai_meta_fields', 'snks_add_ai_meta_fields' );
	}
	if ( function_exists( 'snks_create_enhanced_ai_tables' ) ) {
		add_action( 'snks_create_enhanced_ai_tables', 'snks_create_enhanced_ai_tables' );
	}
	if ( function_exists( 'snks_add_enhanced_ai_meta_fields' ) ) {
		add_action( 'snks_add_enhanced_ai_meta_fields', 'snks_add_enhanced_ai_meta_fields' );
	}
	if ( function_exists( 'snks_add_enhanced_ai_user_meta_fields' ) ) {
		add_action( 'snks_add_enhanced_ai_user_meta_fields', 'snks_add_enhanced_ai_user_meta_fields' );
	}
	if ( function_exists( 'snks_create_rochtah_doctor_role' ) ) {
		add_action( 'snks_create_rochtah_doctor_role', 'snks_create_rochtah_doctor_role' );
	}
}

/**
 * Check for AI profit system updates and run upgrades if needed
 */
function snks_check_ai_profit_system_updates() {
	$current_version = get_option( 'snks_ai_profit_system_version', '0.0.0' );
	$plugin_version = '1.0.0'; // Current AI profit system version
	
	if ( version_compare( $current_version, $plugin_version, '<' ) ) {
		// Run the upgrade function
		if ( function_exists( 'snks_upgrade_ai_profit_database_schema' ) ) {
			snks_upgrade_ai_profit_database_schema();
		}
		
		// Update the version
		update_option( 'snks_ai_profit_system_version', $plugin_version );
	
	}
}

/**
 * Ensure AI profit database structure is always up to date
 * This function runs on every page load during development
 * Remove this function call in production after initial deployment
 */
function snks_ensure_ai_profit_database_structure() {
	// Only run this in development environment
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}
	
	// Check if all required functions exist
	if ( ! function_exists( 'snks_create_ai_profit_settings_table' ) ||
		 ! function_exists( 'snks_add_ai_session_type_column' ) ||
		 ! function_exists( 'snks_add_ai_transaction_metadata_columns' ) ||
		 ! function_exists( 'snks_add_default_profit_settings' ) ) {
		return;
	}
	
	global $wpdb;
	
	// Check if AI profit settings table exists
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}snks_ai_profit_settings'" );
	if ( ! $table_exists ) {
		snks_create_ai_profit_settings_table();
	}
	
	// Check if required columns exist in sessions_actions table
	$sessions_table = $wpdb->prefix . 'snks_sessions_actions';
	$required_columns = ['ai_session_type', 'therapist_id', 'patient_id'];
	
	foreach ( $required_columns as $column ) {
		$column_exists = $wpdb->get_var( "SHOW COLUMNS FROM $sessions_table LIKE '$column'" );
		if ( ! $column_exists ) {
			snks_add_ai_session_type_column();
			break; // Function adds all columns, so we only need to call it once
		}
	}
	
	// Check if required columns exist in booking_transactions table
	$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
	$required_columns = ['ai_session_id', 'ai_session_type', 'ai_patient_id', 'ai_order_id'];
	
	foreach ( $required_columns as $column ) {
		$column_exists = $wpdb->get_var( "SHOW COLUMNS FROM $transactions_table LIKE '$column'" );
		if ( ! $column_exists ) {
			snks_add_ai_transaction_metadata_columns();
			break; // Function adds all columns, so we only need to call it once
		}
	}
	
	// Check if we have default profit settings
	$settings_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snks_ai_profit_settings" );
	if ( $settings_count == 0 ) {
		snks_add_default_profit_settings();
	}
}

// Add missing columns to existing installations
if ( function_exists( 'snks_add_missing_therapist_diagnoses_columns' ) ) {
	snks_add_missing_therapist_diagnoses_columns();
}

// Add missing columns to therapist applications table
if ( function_exists( 'snks_add_missing_therapist_applications_columns' ) ) {
	snks_add_missing_therapist_applications_columns();
}

// Note: Demo data creation is now handled manually via create-demo-data.php
// or through the admin interface when needed
/**
 * Plugin activation hook
 *
 * @return void
 */
function plugin_activation_hook() {
	// Load required files for activation (these might not be loaded yet during activation)
	$enhanced_tables_file = plugin_dir_path( __FILE__ ) . 'includes/ai-tables-enhanced.php';
	if ( file_exists( $enhanced_tables_file ) ) {
		require_once $enhanced_tables_file;
	}
	
	snks_create_timetable_table();
	snks_create_snks_sessions_actions_table();
	snks_create_transactions_table();
	snks_create_custom_coupons_table();
	snks_create_coupon_usages_table();
	
	// Add WhatsApp notification columns for AI sessions
	if ( function_exists( 'snks_add_whatsapp_notification_columns' ) ) {
		snks_add_whatsapp_notification_columns();
	}
	
	// Add WhatsApp notification columns for Rochtah bookings
	if ( function_exists( 'snks_add_rochtah_whatsapp_notification_columns' ) ) {
		snks_add_rochtah_whatsapp_notification_columns();
	}
	if ( function_exists( 'snks_add_rochtah_doctor_joined_column' ) ) {
		snks_add_rochtah_doctor_joined_column();
	}
	if ( function_exists( 'snks_add_rochtah_doctor_joined_column' ) ) {
		snks_add_created_updated_columns();
	}
	// Create AI tables
	do_action( 'snks_create_ai_tables' );
	do_action( 'snks_add_ai_meta_fields' );
	do_action( 'snks_create_enhanced_ai_tables' );
	do_action( 'snks_add_enhanced_ai_meta_fields' );
	do_action( 'snks_add_enhanced_ai_user_meta_fields' );
	do_action( 'snks_create_rochtah_doctor_role' );
	
	// Fallback: Direct function calls if action hooks fail
	if ( function_exists( 'snks_create_ai_tables' ) ) {
		snks_create_ai_tables();
	}
	// Ensure enhanced AI tables are created (including coupons table)
	// This is critical - the coupons table must be created on activation
	if ( function_exists( 'snks_create_enhanced_ai_tables' ) ) {
		snks_create_enhanced_ai_tables();
	} else {
		// If function still doesn't exist after require, something is wrong
		// But try one more time with absolute path
		$enhanced_tables_file = plugin_dir_path( __FILE__ ) . 'includes/ai-tables-enhanced.php';
		if ( file_exists( $enhanced_tables_file ) ) {
			require_once $enhanced_tables_file;
			if ( function_exists( 'snks_create_enhanced_ai_tables' ) ) {
				snks_create_enhanced_ai_tables();
			}
		}
	}
	if ( function_exists( 'snks_create_rochtah_tables' ) ) {
		snks_create_rochtah_tables();
	}
	if ( function_exists( 'snks_add_enhanced_ai_meta_fields' ) ) {
		snks_add_enhanced_ai_meta_fields();
	}
	if ( function_exists( 'snks_add_enhanced_ai_user_meta_fields' ) ) {
		snks_add_enhanced_ai_user_meta_fields();
	}
	if ( function_exists( 'snks_create_rochtah_doctor_role' ) ) {
		snks_create_rochtah_doctor_role();
	}
	
	// Ensure AI profit system database structure is complete
	if ( function_exists( 'snks_create_ai_profit_settings_table' ) ) {
		snks_create_ai_profit_settings_table();
	}
	if ( function_exists( 'snks_add_ai_session_type_column' ) ) {
		snks_add_ai_session_type_column();
	}
	if ( function_exists( 'snks_add_ai_transaction_metadata_columns' ) ) {
		snks_add_ai_transaction_metadata_columns();
	}
	if ( function_exists( 'snks_add_default_profit_settings' ) ) {
		snks_add_default_profit_settings();
	}
	
	// Set AI profit system version
	update_option( 'snks_ai_profit_system_version', '1.0.0' );
	
	// Create AI session product for WooCommerce
	// Defer AI product creation to avoid WooCommerce conflicts during activation
	if ( class_exists( 'WooCommerce' ) ) {
		// Include AI helper classes
		require_once SNKS_DIR . 'functions/helpers/ai-products.php';
		// Schedule product creation for next page load to avoid activation conflicts
		add_action( 'init', function() {
			if ( class_exists( 'WooCommerce' ) ) {
				SNKS_AI_Products::create_ai_session_product();
			}
		}, 20 );
	}
}
register_activation_hook( __FILE__, 'plugin_activation_hook' );

// Create therapist applications table on activation
register_activation_hook(
	__FILE__,
	static function() {
		snks_create_therapist_applications_table();
		if ( function_exists( 'snks_add_missing_therapist_applications_columns' ) ) {
			snks_add_missing_therapist_applications_columns();
		}
	}
);

// Hook to set up default pricing when therapist is activated
add_action( 'update_user_meta', 'snks_check_therapist_activation', 10, 4 );

/**
 * Create custom table for therapist applications
 */
function snks_create_therapist_applications_table() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'therapist_applications';
	$charset_collate = $wpdb->get_charset_collate();
	
	           $sql = "CREATE TABLE $table_name (
               id mediumint(9) NOT NULL AUTO_INCREMENT,
               user_id bigint(20) DEFAULT NULL,
               name varchar(255) NOT NULL,
               name_en varchar(255) DEFAULT '',
               email varchar(255) NOT NULL,
               phone varchar(50) NOT NULL,
               whatsapp varchar(50) DEFAULT '',
               doctor_specialty varchar(255) DEFAULT '',
               role varchar(50) DEFAULT '',
               psychiatrist_rank varchar(50) DEFAULT '',
               psych_origin varchar(100) DEFAULT '',
               cp_moh_license varchar(10) DEFAULT '',
               graduate_certificate bigint(20) DEFAULT NULL,
               practice_license bigint(20) DEFAULT NULL,
               syndicate_card bigint(20) DEFAULT NULL,
               rank_certificate bigint(20) DEFAULT NULL,
               cp_graduate_certificate bigint(20) DEFAULT NULL,
               cp_highest_degree bigint(20) DEFAULT NULL,
               cp_moh_license_file bigint(20) DEFAULT NULL,
               experience_years int(11) DEFAULT 0,
               education text,
               bio text,
               bio_en text,
               profile_image bigint(20) DEFAULT NULL,
               identity_front bigint(20) DEFAULT NULL,
               identity_back bigint(20) DEFAULT NULL,
               certificates longtext,
               therapy_courses longtext,
               preferred_groups text,
               diagnoses_children text,
               diagnoses_adult text,
               rating decimal(3,2) DEFAULT 0.00,
               total_ratings int(11) DEFAULT 0,
               ai_bio text,
               ai_bio_en text,
               ai_certifications text,
               ai_earliest_slot int(11) DEFAULT 0,
               show_on_ai_site tinyint(1) DEFAULT 0,
               status varchar(20) DEFAULT 'pending',
               created_at datetime DEFAULT CURRENT_TIMESTAMP,
               updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               KEY user_id (user_id),
               KEY status (status),
               KEY email (email)
           ) $charset_collate;";
	
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

function snks_add_missing_therapist_applications_columns() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	// Check if columns exist and add them if they don't
	$columns_to_add = [
       'role' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN role varchar(50) DEFAULT ""',
       'psychiatrist_rank' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN psychiatrist_rank varchar(50) DEFAULT ""',
       'psych_origin' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN psych_origin varchar(100) DEFAULT ""',
       'cp_moh_license' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN cp_moh_license varchar(10) DEFAULT ""',
       'graduate_certificate' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN graduate_certificate bigint(20) DEFAULT NULL',
       'practice_license' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN practice_license bigint(20) DEFAULT NULL',
       'syndicate_card' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN syndicate_card bigint(20) DEFAULT NULL',
       'rank_certificate' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN rank_certificate bigint(20) DEFAULT NULL',
       'cp_graduate_certificate' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN cp_graduate_certificate bigint(20) DEFAULT NULL',
       'cp_highest_degree' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN cp_highest_degree bigint(20) DEFAULT NULL',
       'cp_moh_license_file' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN cp_moh_license_file bigint(20) DEFAULT NULL',
       'therapy_courses' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN therapy_courses longtext',
       'preferred_groups' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN preferred_groups text',
       'diagnoses_children' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN diagnoses_children text',
       'diagnoses_adult' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN diagnoses_adult text',
       'rating' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN rating decimal(3,2) DEFAULT 0.00',
       'total_ratings' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN total_ratings int(11) DEFAULT 0',
       'ai_bio' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN ai_bio text',
       'ai_bio_en' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN ai_bio_en text',
       'ai_certifications' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN ai_certifications text',
       'ai_earliest_slot' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN ai_earliest_slot int(11) DEFAULT 0',
       'show_on_ai_site' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN show_on_ai_site tinyint(1) DEFAULT 0',
       'otp_method' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN otp_method varchar(20) DEFAULT "email"',
       'submitted_at' => 'ALTER TABLE ' . $table_name . ' ADD COLUMN submitted_at datetime DEFAULT CURRENT_TIMESTAMP'
	];
	
	foreach ( $columns_to_add as $column_name => $sql ) {
		$column_exists = $wpdb->get_results( $wpdb->prepare( 
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
			DB_NAME,
			$table_name,
			$column_name
		) );
		
		if ( empty( $column_exists ) ) {
			$wpdb->query( $sql );
		}
	}
}

/**
 * Check if therapist is being activated and set up default pricing
 */
function snks_check_therapist_activation( $meta_id, $user_id, $meta_key, $meta_value ) {
	// Check if this is the show_on_ai_site meta being set to 1 (activated)
	if ( $meta_key === 'show_on_ai_site' && $meta_value === '1' ) {
		// Check if user is a therapist (has doctor role or is in therapist_applications table)
		$user = get_user_by( 'ID', $user_id );
		if ( $user && in_array( 'doctor', $user->roles ) ) {
			// Set up default pricing for the therapist
			if ( class_exists( 'SNKS_AI_Integration' ) ) {
				SNKS_AI_Integration::setup_therapist_default_pricing( $user_id );
			}
		}
	}
}

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
			'color_10' => array( '#f7ffd9', '#475902', '#323e01' ),
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
define(
	'COUNTRY_CURRENCIES',
	wp_json_encode(
		array(
			'EG' => 'EGP',
			'SA' => 'SAR',
			'AE' => 'AED',
			'KW' => 'KWD',
			'QA' => 'QAR',
			'BH' => 'BHD',
			'OM' => 'OMR',
			'EU' => 'EUR',
			'US' => 'USD',
			'GB' => 'GBP',
			'CA' => 'CAD',
			'AU' => 'AUD',
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
		snks_user_info();
		$form_data = get_transient( snks_form_data_transient_key() );
		// Check if the session data exists and contains the expected keys.
		if ( is_array( $form_data ) ) {
			//phpcs:disable
			echo consulting_session_pricing_table_shortcode( $form_data );
			//phpcs:enable

			echo '<h2 style="margin:20px 0;color:#fff;font-size:25px;text-align:center">إختر طريقة الدفع المناسبة</h2>';
			echo '<p class="hacen_liner_print-outregular" style="color:#fff;font-size:15px;text-align:center">( يرجى العلم أن عملية الدفع ستتم بالجنيه المصري )</p>';
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
					.filter(checkbox => checkbox.checked && checkbox.name === 'users[]')
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


// Extend session to 30 days regardless of "Remember Me"
add_filter('auth_cookie_expiration', function($expiration) {
    return 2592000; // 30 days in seconds
});