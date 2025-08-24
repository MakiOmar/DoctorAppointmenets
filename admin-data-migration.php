<?php
/**
 * Admin Data Migration Page
 * 
 * WordPress admin page for exporting and importing data between environments
 * 
 * @package JalsahAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu page for data migration
 */
function jalsah_ai_add_data_migration_page() {
    add_submenu_page(
        'tools.php', // Parent slug (Tools menu)
        'Data Migration', // Page title
        'Data Migration', // Menu title
        'manage_options', // Capability required
        'jalsah-data-migration', // Menu slug
        'jalsah_ai_data_migration_page' // Callback function
    );
}
add_action('admin_menu', 'jalsah_ai_add_data_migration_page');

/**
 * Handle export and import actions
 */
function jalsah_ai_handle_data_migration_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle export action
    if (isset($_POST['action']) && $_POST['action'] === 'export_data') {
        $export_type = sanitize_text_field($_POST['export_type'] ?? 'all');
        jalsah_ai_export_data($export_type);
    }

    // Handle import action
    if (isset($_POST['action']) && $_POST['action'] === 'import_data' && isset($_FILES['import_file'])) {
        jalsah_ai_import_data($_FILES['import_file']);
    }
}
add_action('admin_init', 'jalsah_ai_handle_data_migration_actions');

/**
 * Export data function
 */
function jalsah_ai_export_data($type = 'all') {
    global $wpdb;
    
    $export_data = [
        'export_info' => [
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'export_type' => $type,
            'version' => '1.0',
            'wordpress_version' => get_bloginfo('version')
        ],
        'data' => []
    ];
    
    switch ($type) {
        case 'all':
            $export_data['data'] = [
                'users' => jalsah_ai_export_users(),
                'posts' => jalsah_ai_export_posts(),
                'pages' => jalsah_ai_export_pages(),
                'options' => jalsah_ai_export_options(),
                'terms' => jalsah_ai_export_terms(),
                'diagnoses' => jalsah_ai_export_diagnoses(),
                'appointments' => jalsah_ai_export_appointments(),
                'therapists' => jalsah_ai_export_therapists(),
                'ai_sessions' => jalsah_ai_export_ai_sessions(),
                'custom_tables' => jalsah_ai_export_custom_tables()
            ];
            break;
            
        case 'users':
            $export_data['data']['users'] = jalsah_ai_export_users();
            break;
            
        case 'content':
            $export_data['data'] = [
                'posts' => jalsah_ai_export_posts(),
                'pages' => jalsah_ai_export_pages(),
                'terms' => jalsah_ai_export_terms()
            ];
            break;
            
        case 'medical':
            $export_data['data'] = [
                'diagnoses' => jalsah_ai_export_diagnoses(),
                'appointments' => jalsah_ai_export_appointments(),
                'therapists' => jalsah_ai_export_therapists(),
                'ai_sessions' => jalsah_ai_export_ai_sessions()
            ];
            break;
            
        case 'settings':
            $export_data['data']['options'] = jalsah_ai_export_options();
            break;
    }
    
    // Set headers for file download
    $filename = 'jalsah-data-export-' . $type . '-' . date('Y-m-d-H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Import data function
 */
function jalsah_ai_import_data($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        add_settings_error(
            'jalsah_data_migration',
            'import_error',
            'File upload failed: ' . $file['error'],
            'error'
        );
        return;
    }
    
    $json_content = file_get_contents($file['tmp_name']);
    $data = json_decode($json_content, true);
    
    if (!$data) {
        add_settings_error(
            'jalsah_data_migration',
            'import_error',
            'Invalid JSON file format',
            'error'
        );
        return;
    }
    
    try {
        $results = jalsah_ai_process_import($data);
        add_settings_error(
            'jalsah_data_migration',
            'import_success',
            'Import completed successfully! ' . implode(', ', $results),
            'success'
        );
    } catch (Exception $e) {
        add_settings_error(
            'jalsah_data_migration',
            'import_error',
            'Import failed: ' . $e->getMessage(),
            'error'
        );
    }
}

/**
 * Process import data
 */
function jalsah_ai_process_import($data) {
    global $wpdb;
    
    $results = [];
    
    // Import users
    if (isset($data['data']['users'])) {
        $results[] = jalsah_ai_import_users($data['data']['users']);
    }
    
    // Import posts
    if (isset($data['data']['posts'])) {
        $results[] = jalsah_ai_import_posts($data['data']['posts']);
    }
    
    // Import pages
    if (isset($data['data']['pages'])) {
        $results[] = jalsah_ai_import_pages($data['data']['pages']);
    }
    
    // Import options
    if (isset($data['data']['options'])) {
        $results[] = jalsah_ai_import_options($data['data']['options']);
    }
    
    // Import terms
    if (isset($data['data']['terms'])) {
        $results[] = jalsah_ai_import_terms($data['data']['terms']);
    }
    
    // Import diagnoses
    if (isset($data['data']['diagnoses'])) {
        $results[] = jalsah_ai_import_diagnoses($data['data']['diagnoses']);
    }
    
    // Import appointments
    if (isset($data['data']['appointments'])) {
        $results[] = jalsah_ai_import_appointments($data['data']['appointments']);
    }
    
    // Import therapists
    if (isset($data['data']['therapists'])) {
        $results[] = jalsah_ai_import_therapists($data['data']['therapists']);
    }
    
    // Import AI sessions
    if (isset($data['data']['ai_sessions'])) {
        $results[] = jalsah_ai_import_ai_sessions($data['data']['ai_sessions']);
    }
    
    // Import custom tables
    if (isset($data['data']['custom_tables'])) {
        $results[] = jalsah_ai_import_custom_tables($data['data']['custom_tables']);
    }
    
    return $results;
}

// Export functions
function jalsah_ai_export_users() {
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
            'meta' => jalsah_ai_export_user_meta($user->ID)
        ];
        $export_users[] = $user_data;
    }
    
    return $export_users;
}

function jalsah_ai_export_user_meta($user_id) {
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

function jalsah_ai_export_posts() {
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
        $post_data['meta'] = jalsah_ai_export_post_meta($post->ID);
        $export_posts[] = $post_data;
    }
    
    return $export_posts;
}

function jalsah_ai_export_post_meta($post_id) {
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

function jalsah_ai_export_pages() {
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
        $page_data['meta'] = jalsah_ai_export_post_meta($page->ID);
        $export_pages[] = $page_data;
    }
    
    return $export_pages;
}

function jalsah_ai_export_options() {
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

function jalsah_ai_export_terms() {
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
        $term_data['meta'] = jalsah_ai_export_term_meta($term->term_id);
        $export_terms[] = $term_data;
    }
    
    return $export_terms;
}

function jalsah_ai_export_term_meta($term_id) {
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

function jalsah_ai_export_diagnoses() {
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
        $diagnosis_data['meta'] = jalsah_ai_export_post_meta($diagnosis->ID);
        $export_diagnoses[] = $diagnosis_data;
    }
    
    return $export_diagnoses;
}

function jalsah_ai_export_appointments() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_appointments';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id");
    }
    
    return [];
}

function jalsah_ai_export_therapists() {
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
        $therapist_data['meta'] = jalsah_ai_export_post_meta($therapist->ID);
        $export_therapists[] = $therapist_data;
    }
    
    return $export_therapists;
}

function jalsah_ai_export_ai_sessions() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_ai_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id");
    }
    
    return [];
}

function jalsah_ai_export_custom_tables() {
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

// Import functions
function jalsah_ai_import_users($users) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($users as $user_data) {
        $existing_user = get_user_by('email', $user_data['user_email']);
        if ($existing_user) {
            $skipped++;
            continue;
        }
        
        $user_id = wp_create_user(
            $user_data['user_login'],
            wp_generate_password(),
            $user_data['user_email']
        );
        
        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $user_data['display_name'],
                'user_registered' => $user_data['user_registered']
            ]);
            
            if (isset($user_data['meta'])) {
                foreach ($user_data['meta'] as $meta_key => $meta_value) {
                    update_user_meta($user_id, $meta_key, $meta_value);
                }
            }
            
            if (isset($user_data['role'])) {
                $user = new WP_User($user_id);
                $user->set_role('subscriber');
            }
            
            $imported++;
        }
    }
    
    return "Users: $imported imported, $skipped skipped";
}

function jalsah_ai_import_posts($posts) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($posts as $post_data) {
        $existing_post = get_page_by_title($post_data['post_title'], OBJECT, $post_data['post_type']);
        if ($existing_post) {
            $skipped++;
            continue;
        }
        
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
            if (isset($post_data['meta'])) {
                foreach ($post_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
            }
            $imported++;
        }
    }
    
    return "Posts: $imported imported, $skipped skipped";
}

function jalsah_ai_import_pages($pages) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($pages as $page_data) {
        $existing_page = get_page_by_title($page_data['post_title'], OBJECT, 'page');
        if ($existing_page) {
            $skipped++;
            continue;
        }
        
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
            if (isset($page_data['meta'])) {
                foreach ($page_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($page_id, $meta_key, $meta_value);
                }
            }
            $imported++;
        }
    }
    
    return "Pages: $imported imported, $skipped skipped";
}

function jalsah_ai_import_options($options) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($options as $option_name => $option_value) {
        if (in_array($option_name, ['siteurl', 'home', 'blogname', 'blogdescription'])) {
            $skipped++;
            continue;
        }
        
        update_option($option_name, $option_value);
        $imported++;
    }
    
    return "Options: $imported imported, $skipped skipped";
}

function jalsah_ai_import_terms($terms) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($terms as $term_data) {
        $existing_term = get_term_by('slug', $term_data['slug'], $term_data['taxonomy']);
        if ($existing_term) {
            $skipped++;
            continue;
        }
        
        $term_id = wp_insert_term($term_data['name'], $term_data['taxonomy'], [
            'slug' => $term_data['slug'],
            'description' => $term_data['description'],
            'parent' => $term_data['parent']
        ]);
        
        if (!is_wp_error($term_id)) {
            if (isset($term_data['meta'])) {
                foreach ($term_data['meta'] as $meta_key => $meta_value) {
                    update_term_meta($term_id['term_id'], $meta_key, $meta_value);
                }
            }
            $imported++;
        }
    }
    
    return "Terms: $imported imported, $skipped skipped";
}

function jalsah_ai_import_diagnoses($diagnoses) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($diagnoses as $diagnosis_data) {
        $existing_diagnosis = get_page_by_title($diagnosis_data['post_title'], OBJECT, 'diagnosis');
        if ($existing_diagnosis) {
            $skipped++;
            continue;
        }
        
        $diagnosis_id = wp_insert_post([
            'post_title' => $diagnosis_data['post_title'],
            'post_content' => $diagnosis_data['post_content'],
            'post_status' => $diagnosis_data['post_status'],
            'post_type' => 'diagnosis',
            'post_name' => $diagnosis_data['post_name']
        ]);
        
        if (!is_wp_error($diagnosis_id)) {
            if (isset($diagnosis_data['meta'])) {
                foreach ($diagnosis_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($diagnosis_id, $meta_key, $meta_value);
                }
            }
            $imported++;
        }
    }
    
    return "Diagnoses: $imported imported, $skipped skipped";
}

function jalsah_ai_import_appointments($appointments) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_appointments';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        return "Appointments: Table does not exist";
    }
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($appointments as $appointment) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $appointment->id
        ));
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        $result = $wpdb->insert($table_name, (array) $appointment);
        if ($result !== false) {
            $imported++;
        }
    }
    
    return "Appointments: $imported imported, $skipped skipped";
}

function jalsah_ai_import_therapists($therapists) {
    global $wpdb;
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($therapists as $therapist_data) {
        $existing_therapist = get_page_by_title($therapist_data['post_title'], OBJECT, 'therapist');
        if ($existing_therapist) {
            $skipped++;
            continue;
        }
        
        $therapist_id = wp_insert_post([
            'post_title' => $therapist_data['post_title'],
            'post_content' => $therapist_data['post_content'],
            'post_status' => $therapist_data['post_status'],
            'post_type' => 'therapist',
            'post_name' => $therapist_data['post_name']
        ]);
        
        if (!is_wp_error($therapist_id)) {
            if (isset($therapist_data['meta'])) {
                foreach ($therapist_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($therapist_id, $meta_key, $meta_value);
                }
            }
            $imported++;
        }
    }
    
    return "Therapists: $imported imported, $skipped skipped";
}

function jalsah_ai_import_ai_sessions($sessions) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jalsah_ai_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        return "AI Sessions: Table does not exist";
    }
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($sessions as $session) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $session->id
        ));
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        $result = $wpdb->insert($table_name, (array) $session);
        if ($result !== false) {
            $imported++;
        }
    }
    
    return "AI Sessions: $imported imported, $skipped skipped";
}

function jalsah_ai_import_custom_tables($custom_tables) {
    global $wpdb;
    
    $results = [];
    
    foreach ($custom_tables as $table_name => $table_data) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $results[] = "$table_name: Table does not exist";
            continue;
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($table_data as $row) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE id = %d",
                $row->id
            ));
            
            if ($existing) {
                $skipped++;
                continue;
            }
            
            $result = $wpdb->insert($table_name, (array) $row);
            if ($result !== false) {
                $imported++;
            }
        }
        
        $results[] = "$table_name: $imported imported, $skipped skipped";
    }
    
    return implode(', ', $results);
}

/**
 * Admin page HTML
 */
function jalsah_ai_data_migration_page() {
    ?>
    <div class="wrap">
        <h1>📊 Data Migration</h1>
        
        <?php settings_errors('jalsah_data_migration'); ?>
        
        <div class="notice notice-info">
            <p><strong>💡 Purpose:</strong> Export your WordPress data to import into another environment (local to staging, staging to production, etc.)</p>
        </div>
        
        <div class="notice notice-warning">
            <p><strong>⚠️ Important:</strong> This tool exports/imports all your data including users, content, settings, and custom tables. Use with caution!</p>
        </div>
        
        <div class="card">
            <h2>📤 Export Data</h2>
            <p>Export your current WordPress data to a JSON file that can be imported into another environment.</p>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="export_data">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Export Type</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="export_type" value="all" checked>
                                    <strong>All Data</strong> - Complete export including users, content, settings, medical data, and custom tables
                                </label><br>
                                <label>
                                    <input type="radio" name="export_type" value="users">
                                    <strong>Users Only</strong> - Export user accounts, roles, and user meta data
                                </label><br>
                                <label>
                                    <input type="radio" name="export_type" value="content">
                                    <strong>Content Only</strong> - Export posts, pages, and taxonomy terms
                                </label><br>
                                <label>
                                    <input type="radio" name="export_type" value="medical">
                                    <strong>Medical Data</strong> - Export diagnoses, appointments, therapists, and AI sessions
                                </label><br>
                                <label>
                                    <input type="radio" name="export_type" value="settings">
                                    <strong>Settings Only</strong> - Export WordPress options and configurations
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('📤 Export Data', 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>📥 Import Data</h2>
            <p>Import data from a previously exported JSON file. Existing data with the same titles/IDs will be skipped.</p>
            
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_data">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Import File</th>
                        <td>
                            <input type="file" name="import_file" accept=".json" required>
                            <p class="description">Select the JSON file exported from another WordPress installation</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('📥 Import Data', 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 What Gets Exported/Imported</h2>
            <ul>
                <li><strong>Users:</strong> User accounts, roles, capabilities, and meta data</li>
                <li><strong>Content:</strong> Posts, pages, custom post types, and their meta data</li>
                <li><strong>Settings:</strong> WordPress options, plugin settings, theme customizations</li>
                <li><strong>Medical Data:</strong> Diagnoses, appointments, therapists, AI sessions</li>
                <li><strong>Custom Tables:</strong> Jalsah plugin tables (appointments, AI sessions, cart, orders, payments)</li>
                <li><strong>Taxonomies:</strong> Categories, tags, custom taxonomies, and term meta</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>🔒 Security & Safety</h2>
            <ul>
                <li><strong>Admin Only:</strong> This tool requires administrator privileges</li>
                <li><strong>Duplicate Protection:</strong> Existing data will not be overwritten</li>
                <li><strong>Settings Protection:</strong> Site URL and home URL are not imported to prevent breaking the target site</li>
                <li><strong>Safe Import:</strong> All data is validated before import</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>🔄 After Import</h2>
            <ol>
                <li>Clear all caches (WordPress, plugin, server)</li>
                <li>Test functionality thoroughly</li>
                <li>Update passwords for imported users</li>
                <li>Verify data integrity</li>
                <li>Update any site-specific settings if needed</li>
            </ol>
        </div>
    </div>
    
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
            color: #23282d;
        }
        .form-table th {
            width: 200px;
        }
        .notice {
            margin: 20px 0;
        }
    </style>
    <?php
}
