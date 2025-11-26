<?php
/**
 * Local Data Import Tool
 * 
 * This script imports exported local WordPress data into the online test server.
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

$message = '';
$error = '';

// Handle file upload
if (isset($_POST['import_data']) && isset($_FILES['json_file'])) {
    $file = $_FILES['json_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $json_content = file_get_contents($file['tmp_name']);
        $data = json_decode($json_content, true);
        
        if ($data) {
            try {
                $result = importLocalData($data);
                $message = "✅ Import completed successfully!<br><br>" . $result;
            } catch (Exception $e) {
                $error = "❌ Import failed: " . $e->getMessage();
            }
        } else {
            $error = "❌ Invalid JSON file format";
        }
    } else {
        $error = "❌ File upload error: " . $file['error'];
    }
}

function importLocalData($data) {
    global $wpdb;
    
    $results = [];
    
    // Import users
    if (isset($data['data']['users'])) {
        $results[] = importUsers($data['data']['users']);
    }
    
    // Import posts
    if (isset($data['data']['posts'])) {
        $results[] = importPosts($data['data']['posts']);
    }
    
    // Import pages
    if (isset($data['data']['pages'])) {
        $results[] = importPages($data['data']['pages']);
    }
    
    // Import options
    if (isset($data['data']['options'])) {
        $results[] = importOptions($data['data']['options']);
    }
    
    // Import terms
    if (isset($data['data']['terms'])) {
        $results[] = importTerms($data['data']['terms']);
    }
    
    // Import diagnoses
    if (isset($data['data']['diagnoses'])) {
        $results[] = importDiagnoses($data['data']['diagnoses']);
    }
    
    // Import appointments
    if (isset($data['data']['appointments'])) {
        $results[] = importAppointments($data['data']['appointments']);
    }
    
    // Import therapists
    if (isset($data['data']['therapists'])) {
        $results[] = importTherapists($data['data']['therapists']);
    }
    
    // Import AI sessions
    if (isset($data['data']['ai_sessions'])) {
        $results[] = importAISessions($data['data']['ai_sessions']);
    }
    
    // Import custom tables
    if (isset($data['data']['custom_tables'])) {
        $results[] = importCustomTables($data['data']['custom_tables']);
    }
    
    return implode('<br>', $results);
}

function importUsers($users) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($users as $user_data) {
        // Check if user already exists
        $existing_user = get_user_by('email', $user_data['user_email']);
        if ($existing_user) {
            $skipped++;
            continue;
        }
        
        // Create user
        $user_id = wp_create_user(
            $user_data['user_login'],
            wp_generate_password(), // Generate random password
            $user_data['user_email']
        );
        
        if (!is_wp_error($user_id)) {
            // Update user data
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $user_data['display_name'],
                'user_registered' => $user_data['user_registered']
            ]);
            
            // Import user meta
            if (isset($user_data['meta'])) {
                foreach ($user_data['meta'] as $meta_key => $meta_value) {
                    update_user_meta($user_id, $meta_key, $meta_value);
                }
            }
            
            // Set user role
            if (isset($user_data['role'])) {
                $user = new WP_User($user_id);
                $user->set_role('subscriber'); // Default role, adjust as needed
            }
            
            $imported++;
        }
    }
    
    return "👥 Users: $imported imported, $skipped skipped";
}

function importPosts($posts) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($posts as $post_data) {
        // Check if post already exists
        $existing_post = get_page_by_title($post_data['post_title'], OBJECT, $post_data['post_type']);
        if ($existing_post) {
            $skipped++;
            continue;
        }
        
        // Create post
        $post_id = wp_insert_post([
            'post_title' => $post_data['post_title'],
            'post_content' => $post_data['post_content'],
            'post_excerpt' => $post_data['post_excerpt'],
            'post_status' => $post_data['post_status'],
            'post_type' => $post_data['post_type'],
            'post_name' => $post_data['post_name'],
            'post_date' => $post_data['post_date'],
            'post_date_gmt' => $post_data['post_date_gmt'],
            'post_modified' => $post_data['post_modified'],
            'post_modified_gmt' => $post_data['post_modified_gmt']
        ]);
        
        if (!is_wp_error($post_id)) {
            // Import post meta
            if (isset($post_data['meta'])) {
                foreach ($post_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
            }
            
            $imported++;
        }
    }
    
    return "📝 Posts: $imported imported, $skipped skipped";
}

function importPages($pages) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($pages as $page_data) {
        // Check if page already exists
        $existing_page = get_page_by_title($page_data['post_title'], OBJECT, 'page');
        if ($existing_page) {
            $skipped++;
            continue;
        }
        
        // Create page
        $page_id = wp_insert_post([
            'post_title' => $page_data['post_title'],
            'post_content' => $page_data['post_content'],
            'post_excerpt' => $page_data['post_excerpt'],
            'post_status' => $page_data['post_status'],
            'post_type' => 'page',
            'post_name' => $page_data['post_name'],
            'post_date' => $page_data['post_date'],
            'post_date_gmt' => $page_data['post_date_gmt'],
            'post_modified' => $page_data['post_modified'],
            'post_modified_gmt' => $page_data['post_modified_gmt']
        ]);
        
        if (!is_wp_error($page_id)) {
            // Import page meta
            if (isset($page_data['meta'])) {
                foreach ($page_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($page_id, $meta_key, $meta_value);
                }
            }
            
            $imported++;
        }
    }
    
    return "📄 Pages: $imported imported, $skipped skipped";
}

function importOptions($options) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($options as $option_name => $option_value) {
        // Skip certain options that shouldn't be imported
        if (in_array($option_name, ['siteurl', 'home', 'blogname', 'blogdescription'])) {
            $skipped++;
            continue;
        }
        
        update_option($option_name, $option_value);
        $imported++;
    }
    
    return "⚙️ Options: $imported imported, $skipped skipped";
}

function importTerms($terms) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($terms as $term_data) {
        // Check if term already exists
        $existing_term = get_term_by('slug', $term_data['slug'], $term_data['taxonomy']);
        if ($existing_term) {
            $skipped++;
            continue;
        }
        
        // Create term
        $term_id = wp_insert_term($term_data['name'], $term_data['taxonomy'], [
            'slug' => $term_data['slug'],
            'description' => $term_data['description'],
            'parent' => $term_data['parent']
        ]);
        
        if (!is_wp_error($term_id)) {
            // Import term meta
            if (isset($term_data['meta'])) {
                foreach ($term_data['meta'] as $meta_key => $meta_value) {
                    update_term_meta($term_id['term_id'], $meta_key, $meta_value);
                }
            }
            
            $imported++;
        }
    }
    
    return "🏷️ Terms: $imported imported, $skipped skipped";
}

function importDiagnoses($diagnoses) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($diagnoses as $diagnosis_data) {
        // Check if diagnosis already exists
        $existing_diagnosis = get_page_by_title($diagnosis_data['post_title'], OBJECT, 'diagnosis');
        if ($existing_diagnosis) {
            $skipped++;
            continue;
        }
        
        // Create diagnosis
        $diagnosis_id = wp_insert_post([
            'post_title' => $diagnosis_data['post_title'],
            'post_content' => $diagnosis_data['post_content'],
            'post_status' => $diagnosis_data['post_status'],
            'post_type' => 'diagnosis',
            'post_name' => $diagnosis_data['post_name']
        ]);
        
        if (!is_wp_error($diagnosis_id)) {
            // Import diagnosis meta
            if (isset($diagnosis_data['meta'])) {
                foreach ($diagnosis_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($diagnosis_id, $meta_key, $meta_value);
                }
            }
            
            $imported++;
        }
    }
    
    return "🏥 Diagnoses: $imported imported, $skipped skipped";
}

function importAppointments($appointments) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_appointments';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        return "📅 Appointments: Table does not exist";
    }
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($appointments as $appointment) {
        // Check if appointment already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $appointment->id
        ));
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Insert appointment
        $result = $wpdb->insert($table_name, (array) $appointment);
        if ($result !== false) {
            $imported++;
        }
    }
    
    return "📅 Appointments: $imported imported, $skipped skipped";
}

function importTherapists($therapists) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($therapists as $therapist_data) {
        // Check if therapist already exists
        $existing_therapist = get_page_by_title($therapist_data['post_title'], OBJECT, 'therapist');
        if ($existing_therapist) {
            $skipped++;
            continue;
        }
        
        // Create therapist
        $therapist_id = wp_insert_post([
            'post_title' => $therapist_data['post_title'],
            'post_content' => $therapist_data['post_content'],
            'post_status' => $therapist_data['post_status'],
            'post_type' => 'therapist',
            'post_name' => $therapist_data['post_name']
        ]);
        
        if (!is_wp_error($therapist_id)) {
            // Import therapist meta
            if (isset($therapist_data['meta'])) {
                foreach ($therapist_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($therapist_id, $meta_key, $meta_value);
                }
            }
            
            $imported++;
        }
    }
    
    return "👨‍⚕️ Therapists: $imported imported, $skipped skipped";
}

function importAISessions($sessions) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_ai_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        return "🤖 AI Sessions: Table does not exist";
    }
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($sessions as $session) {
        // Check if session already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $session->id
        ));
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Insert session
        $result = $wpdb->insert($table_name, (array) $session);
        if ($result !== false) {
            $imported++;
        }
    }
    
    return "🤖 AI Sessions: $imported imported, $skipped skipped";
}

function importCustomTables($custom_tables) {
    global $wpdb;
    
    $results = [];
    
    foreach ($custom_tables as $table_name => $table_data) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $results[] = "📊 $table_name: Table does not exist";
            continue;
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($table_data as $row) {
            // Check if row already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE id = %d",
                $row->id
            ));
            
            if ($existing) {
                $skipped++;
                continue;
            }
            
            // Insert row
            $result = $wpdb->insert($table_name, (array) $row);
            if ($result !== false) {
                $imported++;
            }
        }
        
        $results[] = "📊 $table_name: $imported imported, $skipped skipped";
    }
    
    return implode('<br>', $results);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Data Import Tool</title>
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
        .upload-area {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            text-align: center;
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
        .success {
            border-left-color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }
        .error {
            border-left-color: #f44336;
            background: rgba(244, 67, 54, 0.1);
        }
        .file-input {
            margin: 20px 0;
        }
        .file-input input[type="file"] {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            padding: 10px;
            border-radius: 5px;
            color: white;
            width: 100%;
        }
        .file-input input[type="file"]::file-selector-button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Local Data Import Tool</h1>
        
        <div class="info-box">
            <strong>💡 Purpose:</strong><br>
            Import exported local WordPress data into this online test server.
            This will populate the server with your demo content, settings, and data.
        </div>

        <div class="info-box warning">
            <strong>⚠️ Important:</strong><br>
            • This will import data into the current database<br>
            • Existing data with the same titles/IDs will be skipped<br>
            • Make sure you have administrator privileges<br>
            • Backup your current data before importing
        </div>

        <?php if ($message): ?>
            <div class="info-box success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="info-box error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="upload-area">
                <h3>📁 Select Export File</h3>
                <p>Choose the JSON file exported from your local WordPress installation</p>
                
                <div class="file-input">
                    <input type="file" name="json_file" accept=".json" required>
                </div>
                
                <button type="submit" name="import_data" class="button">📥 Import Data</button>
            </div>
        </form>

        <div class="info-box">
            <strong>📋 What will be imported:</strong><br>
            • Users and user meta data<br>
            • Posts, pages, and custom post types<br>
            • WordPress options and settings<br>
            • Taxonomy terms and meta<br>
            • Diagnoses and medical content<br>
            • Appointments and AI sessions<br>
            • Custom plugin tables<br>
            • All associated meta data
        </div>

        <div class="info-box warning">
            <strong>🔄 After Import:</strong><br>
            • Clear any caches<br>
            • Test the functionality<br>
            • Verify all data is imported correctly<br>
            • Update any site-specific settings if needed
        </div>
    </div>
</body>
</html>
