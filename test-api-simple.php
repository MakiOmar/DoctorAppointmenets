<?php
/**
 * Simple API Test
 * 
 * Test if API endpoints are accessible and working
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the endpoint from URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Find the 'ai' part and get what comes after it
$ai_index = array_search('ai', $path_parts);
if ($ai_index === false) {
    http_response_code(404);
    echo json_encode(['error' => 'AI endpoint not found']);
    exit;
}

$endpoint = isset($path_parts[$ai_index + 1]) ? $path_parts[$ai_index + 1] : 'ping';

// Simple endpoint routing
switch ($endpoint) {
    case 'ping':
        echo json_encode([
            'success' => true,
            'message' => 'Pong!',
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint
        ]);
        break;
        
    case 'debug':
        echo json_encode([
            'success' => true,
            'message' => 'Debug endpoint working',
            'request_uri' => $request_uri,
            'method' => $_SERVER['REQUEST_METHOD'],
            'headers' => getallheaders(),
            'timestamp' => current_time('mysql')
        ]);
        break;
        
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'Test endpoint working',
            'data' => [
                'wordpress_version' => get_bloginfo('version'),
                'site_url' => get_site_url(),
                'plugin_active' => is_plugin_active('DoctorAppointmenets/anony-shrinks.php'),
                'php_version' => PHP_VERSION
            ]
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found',
            'requested_endpoint' => $endpoint
        ]);
        break;
}
?>
