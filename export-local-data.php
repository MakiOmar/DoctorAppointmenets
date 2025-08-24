<?php
/**
 * Local Data Export Tool
 * 
 * This script exports all local WordPress data including:
 * - Demo content and settings
 * - Diagnoses and medical data
 * - Custom plugin data
 * - User data and configurations
 * 
 * Usage: Place this file in your WordPress root and access via browser
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You need administrator privileges.');
}

// Set headers for file download
if (isset($_POST['export_data'])) {
    $export_type = $_POST['export_type'] ?? 'all';
    $filename = 'local-data-export-' . date('Y-m-d-H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    echo exportLocalData($export_type);
    exit;
}

function exportLocalData($type = 'all') {
    global $wpdb;
    
    $export_data = [
        'export_info' => [
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'export_type' => $type,
            'version' => '1.0'
        ],
        'data' => []
    ];
    
    switch ($type) {
        case 'all':
            $export_data['data'] = [
                'users' => exportUsers(),
                'posts' => exportPosts(),
                'pages' => exportPages(),
                'options' => exportOptions(),
                'terms' => exportTerms(),
                'diagnoses' => exportDiagnoses(),
                'appointments' => exportAppointments(),
                'therapists' => exportTherapists(),
                'ai_sessions' => exportAISessions(),
                'custom_tables' => exportCustomTables()
            ];
            break;
            
        case 'users':
            $export_data['data']['users'] = exportUsers();
            break;
            
        case 'content':
            $export_data['data'] = [
                'posts' => exportPosts(),
                'pages' => exportPages(),
                'terms' => exportTerms()
            ];
            break;
            
        case 'medical':
            $export_data['data'] = [
                'diagnoses' => exportDiagnoses(),
                'appointments' => exportAppointments(),
                'therapists' => exportTherapists(),
                'ai_sessions' => exportAISessions()
            ];
            break;
            
        case 'settings':
            $export_data['data']['options'] = exportOptions();
            break;
    }
    
    return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function exportUsers() {
    global $wpdb;
    
    $users = $wpdb->get_results("
        SELECT 
            u.ID,
            u.user_login,
            u.user_email,
            u.user_registered,
            u.display_name,
            um.meta_value as role
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
        WHERE u.user_status = 0
        ORDER BY u.ID
    ");
    
    $export_users = [];
    foreach ($users as $user) {
        $user_data = [
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_registered' => $user->user_registered,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'meta' => exportUserMeta($user->ID)
        ];
        $export_users[] = $user_data;
    }
    
    return $export_users;
}

function exportUserMeta($user_id) {
    global $wpdb;
    
    $meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value 
        FROM {$wpdb->usermeta} 
        WHERE user_id = %d 
        AND meta_key NOT LIKE '{$wpdb->prefix}%'
    ", $user_id));
    
    $meta_data = [];
    foreach ($meta as $m) {
        $meta_data[$m->meta_key] = maybe_unserialize($m->meta_value);
    }
    
    return $meta_data;
}

function exportPosts() {
    global $wpdb;
    
    $posts = $wpdb->get_results("
        SELECT 
            ID,
            post_author,
            post_date,
            post_date_gmt,
            post_content,
            post_title,
            post_excerpt,
            post_status,
            post_type,
            post_name,
            post_modified,
            post_modified_gmt,
            guid
        FROM {$wpdb->posts} 
        WHERE post_status != 'trash' 
        AND post_type IN ('post', 'page', 'ai_session', 'diagnosis', 'therapist')
        ORDER BY ID
    ");
    
    $export_posts = [];
    foreach ($posts as $post) {
        $post_data = (array) $post;
        $post_data['meta'] = exportPostMeta($post->ID);
        $export_posts[] = $post_data;
    }
    
    return $export_posts;
}

function exportPostMeta($post_id) {
    global $wpdb;
    
    $meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id = %d
    ", $post_id));
    
    $meta_data = [];
    foreach ($meta as $m) {
        $meta_data[$m->meta_key] = maybe_unserialize($m->meta_value);
    }
    
    return $meta_data;
}

function exportPages() {
    global $wpdb;
    
    $pages = $wpdb->get_results("
        SELECT 
            ID,
            post_author,
            post_date,
            post_date_gmt,
            post_content,
            post_title,
            post_excerpt,
            post_status,
            post_type,
            post_name,
            post_modified,
            post_modified_gmt,
            guid
        FROM {$wpdb->posts} 
        WHERE post_type = 'page' 
        AND post_status != 'trash'
        ORDER BY ID
    ");
    
    $export_pages = [];
    foreach ($pages as $page) {
        $page_data = (array) $page;
        $page_data['meta'] = exportPostMeta($page->ID);
        $export_pages[] = $page_data;
    }
    
    return $export_pages;
}

function exportOptions() {
    global $wpdb;
    
    $options = $wpdb->get_results("
        SELECT option_name, option_value 
        FROM {$wpdb->options} 
        WHERE option_name NOT LIKE '_transient_%'
        AND option_name NOT LIKE '_site_transient_%'
        AND option_name NOT LIKE 'cron'
        ORDER BY option_name
    ");
    
    $export_options = [];
    foreach ($options as $option) {
        $export_options[$option->option_name] = maybe_unserialize($option->option_value);
    }
    
    return $export_options;
}

function exportTerms() {
    global $wpdb;
    
    $terms = $wpdb->get_results("
        SELECT 
            t.term_id,
            t.name,
            t.slug,
            t.term_group,
            tt.taxonomy,
            tt.description,
            tt.parent
        FROM {$wpdb->terms} t
        JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        ORDER BY t.term_id
    ");
    
    $export_terms = [];
    foreach ($terms as $term) {
        $term_data = (array) $term;
        $term_data['meta'] = exportTermMeta($term->term_id);
        $export_terms[] = $term_data;
    }
    
    return $export_terms;
}

function exportTermMeta($term_id) {
    global $wpdb;
    
    $meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value 
        FROM {$wpdb->termmeta} 
        WHERE term_id = %d
    ", $term_id));
    
    $meta_data = [];
    foreach ($meta as $m) {
        $meta_data[$m->meta_key] = maybe_unserialize($m->meta_value);
    }
    
    return $meta_data;
}

function exportDiagnoses() {
    global $wpdb;
    
    $diagnoses = $wpdb->get_results("
        SELECT 
            ID,
            post_author,
            post_date,
            post_content,
            post_title,
            post_status,
            post_type,
            post_name
        FROM {$wpdb->posts} 
        WHERE post_type = 'diagnosis' 
        AND post_status != 'trash'
        ORDER BY ID
    ");
    
    $export_diagnoses = [];
    foreach ($diagnoses as $diagnosis) {
        $diagnosis_data = (array) $diagnosis;
        $diagnosis_data['meta'] = exportPostMeta($diagnosis->ID);
        $export_diagnoses[] = $diagnosis_data;
    }
    
    return $export_diagnoses;
}

function exportAppointments() {
    global $wpdb;
    
    // Check if appointments table exists
    $table_name = $wpdb->prefix . 'jalsah_appointments';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        $appointments = $wpdb->get_results("
            SELECT * FROM $table_name ORDER BY id
        ");
        return $appointments;
    }
    
    return [];
}

function exportTherapists() {
    global $wpdb;
    
    $therapists = $wpdb->get_results("
        SELECT 
            ID,
            post_author,
            post_date,
            post_content,
            post_title,
            post_status,
            post_type,
            post_name
        FROM {$wpdb->posts} 
        WHERE post_type = 'therapist' 
        AND post_status != 'trash'
        ORDER BY ID
    ");
    
    $export_therapists = [];
    foreach ($therapists as $therapist) {
        $therapist_data = (array) $therapist;
        $therapist_data['meta'] = exportPostMeta($therapist->ID);
        $export_therapists[] = $therapist_data;
    }
    
    return $export_therapists;
}

function exportAISessions() {
    global $wpdb;
    
    // Check if AI sessions table exists
    $table_name = $wpdb->prefix . 'jalsah_ai_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        $sessions = $wpdb->get_results("
            SELECT * FROM $table_name ORDER BY id
        ");
        return $sessions;
    }
    
    return [];
}

function exportCustomTables() {
    global $wpdb;
    
    $custom_tables = [];
    $tables = [
        $wpdb->prefix . 'jalsah_appointments',
        $wpdb->prefix . 'jalsah_ai_sessions',
        $wpdb->prefix . 'jalsah_cart',
        $wpdb->prefix . 'jalsah_orders',
        $wpdb->prefix . 'jalsah_payments'
    ];
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $data = $wpdb->get_results("SELECT * FROM $table ORDER BY id");
            $custom_tables[$table] = $data;
        }
    }
    
    return $custom_tables;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Data Export Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .export-option {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .export-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .export-option h3 {
            margin-top: 0;
            color: #fff;
        }
        .export-option p {
            opacity: 0.9;
            margin-bottom: 15px;
        }
        .button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            margin: 5px;
            transition: background 0.3s ease;
        }
        .button:hover {
            background: #45a049;
        }
        .info-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .warning {
            border-left-color: #FF9800;
            background: rgba(255, 152, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📤 Local Data Export Tool</h1>
        
        <div class="info-box">
            <strong>💡 Purpose:</strong><br>
            Export your local WordPress data to import into the online test server.
            This includes demo content, settings, diagnoses, and all custom data.
        </div>

        <div class="info-box warning">
            <strong>⚠️ Important:</strong><br>
            • This will export all your local data<br>
            • The file will be downloaded as JSON<br>
            • Use this data to populate your online test server<br>
            • Make sure you have administrator privileges
        </div>

        <form method="post">
            <div class="export-option">
                <h3>🚀 Export All Data</h3>
                <p>Complete export including users, content, settings, medical data, and custom tables.</p>
                <button type="submit" name="export_data" value="all" class="button">📦 Export Everything</button>
            </div>

            <div class="export-option">
                <h3>👥 Export Users Only</h3>
                <p>Export user accounts, roles, and user meta data.</p>
                <button type="submit" name="export_data" value="users" class="button">👤 Export Users</button>
            </div>

            <div class="export-option">
                <h3>📝 Export Content Only</h3>
                <p>Export posts, pages, and taxonomy terms.</p>
                <button type="submit" name="export_data" value="content" class="button">📄 Export Content</button>
            </div>

            <div class="export-option">
                <h3>🏥 Export Medical Data</h3>
                <p>Export diagnoses, appointments, therapists, and AI sessions.</p>
                <button type="submit" name="export_data" value="medical" class="button">🏥 Export Medical Data</button>
            </div>

            <div class="export-option">
                <h3>⚙️ Export Settings Only</h3>
                <p>Export WordPress options and configurations.</p>
                <button type="submit" name="export_data" value="settings" class="button">⚙️ Export Settings</button>
            </div>
        </form>

        <div class="info-box">
            <strong>📋 What gets exported:</strong><br>
            • Users and user meta data<br>
            • Posts, pages, and custom post types<br>
            • WordPress options and settings<br>
            • Taxonomy terms and meta<br>
            • Diagnoses and medical content<br>
            • Appointments and AI sessions<br>
            • Custom plugin tables<br>
            • All associated meta data
        </div>
    </div>
</body>
</html>
