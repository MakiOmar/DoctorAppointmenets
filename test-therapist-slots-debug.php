<?php
/**
 * Test script to debug therapist earliest slots
 * 
 * Usage: Navigate to: /test-therapist-slots-debug.php in your browser
 */

// Load WordPress
require_once( __DIR__ . '/../../../wp-load.php' );

// Enable WordPress debug logging
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', true );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
	define( 'WP_DEBUG_DISPLAY', false );
}

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
	<title>Test Therapist Slots Debug</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		.success { color: green; }
		.error { color: red; }
		pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
		h2 { margin-top: 30px; }
	</style>
</head>
<body>
	<h1>Therapist Slots Debug Test</h1>
	
	<?php
	$therapist_id = 211; // محمد عمر
	
	echo "<h2>Testing Therapist ID: {$therapist_id}</h2>";
	
	// Make request to the API
	$url = home_url( '/api/ai/therapists' );
	echo "<p>Calling API: <code>{$url}</code></p>";
	
	$response = wp_remote_get( $url );
	
	if ( is_wp_error( $response ) ) {
		echo '<p class="error">Error: ' . $response->get_error_message() . '</p>';
	} else {
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( isset( $data['success'] ) && $data['success'] ) {
			echo '<p class="success">API call successful!</p>';
			
			// Find therapist 211
			$therapist_211 = null;
			foreach ( $data['data'] as $therapist ) {
				if ( $therapist['id'] == $therapist_id ) {
					$therapist_211 = $therapist;
					break;
				}
			}
			
			if ( $therapist_211 ) {
				echo "<h3>Therapist {$therapist_id} Data:</h3>";
				echo '<pre>';
				echo 'Name: ' . $therapist_211['name'] . "\n";
				echo 'Earliest Slot: ' . $therapist_211['earliest_slot'] . "\n";
				echo 'Earliest Slot Data: ';
				if ( $therapist_211['earliest_slot_data'] ) {
					echo "\n" . print_r( $therapist_211['earliest_slot_data'], true );
				} else {
					echo 'NULL' . "\n";
				}
				echo "\nAvailable Dates Count: " . count( $therapist_211['available_dates'] );
				echo '</pre>';
			} else {
				echo "<p class='error'>Therapist {$therapist_id} not found in response</p>";
			}
			
			// Show all therapists
			echo '<h3>All Therapists Count: ' . count( $data['data'] ) . '</h3>';
			foreach ( $data['data'] as $therapist ) {
				$has_slot = $therapist['earliest_slot_data'] ? 'YES' : 'NO';
				echo "<p>ID: {$therapist['id']} - {$therapist['name']} - Has earliest_slot_data: <strong>{$has_slot}</strong></p>";
			}
			
		} else {
			echo '<p class="error">API returned error or unexpected format</p>';
			echo '<pre>' . print_r( $data, true ) . '</pre>';
		}
	}
	
	// Check debug log
	$debug_log = WP_CONTENT_DIR . '/debug.log';
	if ( file_exists( $debug_log ) ) {
		echo '<h2>Recent Debug Log Entries (last 50 lines):</h2>';
		$log_lines = file( $debug_log );
		$recent_lines = array_slice( $log_lines, -50 );
		echo '<pre style="max-height: 400px;">';
		foreach ( $recent_lines as $line ) {
			if ( strpos( $line, 'Earliest Slot' ) !== false || strpos( $line, 'Therapist ' . $therapist_id ) !== false ) {
				echo '<strong>' . htmlspecialchars( $line ) . '</strong>';
			} else {
				echo htmlspecialchars( $line );
			}
		}
		echo '</pre>';
	} else {
		echo '<p class="error">Debug log file not found at: ' . $debug_log . '</p>';
	}
	
	// Direct database check
	global $wpdb;
	echo '<h2>Direct Database Query:</h2>';
	
	$slots = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID, user_id, date_time, starts, ends, period, clinic, attendance_type, session_status, settings
		FROM {$wpdb->prefix}snks_provider_timetable 
		WHERE user_id = %d 
		ORDER BY date_time ASC 
		LIMIT 10",
		$therapist_id
	) );
	
	if ( $slots ) {
		echo '<p class="success">Found ' . count( $slots ) . ' slots in database for therapist ' . $therapist_id . '</p>';
		echo '<pre>';
		print_r( $slots );
		echo '</pre>';
	} else {
		echo '<p class="error">No slots found in database for therapist ' . $therapist_id . '</p>';
	}
	
	// Check doctor settings
	echo '<h2>Doctor Settings:</h2>';
	$doctor_settings = snks_doctor_settings( $therapist_id );
	echo '<pre>';
	print_r( $doctor_settings );
	echo '</pre>';
	?>
</body>
</html>

