<?php
// Admin columns for therapist_application
add_filter('manage_therapist_app_posts_columns', function($columns) {
    $columns['name'] = __('Name');
    $columns['email'] = __('Email');
    $columns['phone'] = __('Phone');
    $columns['status'] = __('Status');
    $columns['actions'] = __('Actions');
    return $columns;
});
add_action('manage_therapist_app_posts_custom_column', function($column, $post_id) {
    $meta = get_post_meta($post_id);
    switch ($column) {
        case 'name':
            echo esc_html($meta['name'][0] ?? '');
            break;
        case 'email':
            echo esc_html($meta['email'][0] ?? '');
            break;
        case 'phone':
            echo esc_html($meta['phone'][0] ?? '');
            break;
        case 'status':
            echo esc_html(get_post_status($post_id));
            break;
        case 'actions':
            if (get_post_status($post_id) === 'pending') {
                $url = wp_nonce_url(admin_url('edit.php?post_type=therapist_app&approve_therapist=' . $post_id), 'approve_therapist_' . $post_id);
                echo '<a href="' . esc_url($url) . '" class="button">Approve</a>';
            } else {
                echo __('Approved');
            }
            break;
    }
}, 10, 2);
// Approve therapist application
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;
    if (isset($_GET['approve_therapist']) && isset($_GET['_wpnonce'])) {
        $post_id = intval($_GET['approve_therapist']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_therapist_' . $post_id)) return;
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'therapist_app' || $post->post_status !== 'pending') return;
        $meta = get_post_meta($post_id);
        $email = $meta['email'][0] ?? '';
        $phone = $meta['phone'][0] ?? '';
        $name = $meta['name'][0] ?? '';
        $name_en = $meta['name_en'][0] ?? '';
        $whatsapp = $meta['whatsapp'][0] ?? '';
        $specialty = $meta['doctor_specialty'][0] ?? '';
        $profile_image = $meta['profile_image'][0] ?? '';
        $identity_front = $meta['identity_front'][0] ?? '';
        $identity_back = $meta['identity_back'][0] ?? '';
        $certificates = isset($meta['certificates']) ? maybe_unserialize($meta['certificates'][0]) : [];
        $mode = $meta['password_mode'][0] ?? 'auto';
        $password = $meta['password'][0] ?? '';
        if ($mode === 'auto' || empty($password)) {
            $password = wp_generate_password(8, false);
        }
        // Create user
        if (email_exists($email) || username_exists($phone)) return;
        $user_id = wp_create_user($phone, $password, $email);
        if (is_wp_error($user_id)) return;
        $user = get_user_by('id', $user_id);
        $user->set_role('doctor');
        update_user_meta($user_id, 'first_name', $name);
        update_user_meta($user_id, 'last_name', $name_en);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'whatsapp', $whatsapp);
        update_user_meta($user_id, 'doctor_specialty', $specialty);
        update_user_meta($user_id, 'profile_image', $profile_image);
        update_user_meta($user_id, 'identity_front', $identity_front);
        update_user_meta($user_id, 'identity_back', $identity_back);
        update_user_meta($user_id, 'certificates', $certificates);
        
        // Set Jalsah AI name using the therapist's name
        $jalsah_ai_name = !empty($name) ? $name : $name_en;
        update_user_meta($user_id, 'jalsah_ai_name', $jalsah_ai_name);
        // Mark application as approved
        wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
        // Notify user
        $email_subject = __('Your therapist application is approved');
        $email_message = sprintf(
            __('Your account has been created successfully!') . "\n\n" .
            __('Username: %s') . "\n" .
            __('Password: %s') . "\n" .
            __('Jalsah AI Name: %s') . "\n\n" .
            __('You can now log in to your account and start using the platform.'),
            $phone,
            $password,
            $jalsah_ai_name
        );
        wp_mail($email, $email_subject, $email_message);
        wp_redirect(admin_url('edit.php?post_type=therapist_app&approved=1'));
        exit;
    }
});
add_action('init', function() {
    register_post_type('therapist_app', array(
        'labels' => array(
            'name' => __('Therapist Applications'),
            'singular_name' => __('Therapist Application'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'custom-fields'),
        'menu_icon' => 'dashicons-businessperson',
        'has_archive' => false,
    ));
});
add_action('add_meta_boxes', function() {
    add_meta_box(
        'therapist_app_details',
        __('Application Details'),
        function($post) {
            if ($post->post_type !== 'therapist_app') return;
            $meta = get_post_meta($post->ID);
            echo '<table class="form-table">';
            $fields = [
                'name' => __('Name'),
                'name_en' => __('Name (English)'),
                'email' => __('Email'),
                'phone' => __('Phone'),
                'whatsapp' => __('WhatsApp'),
                'doctor_specialty' => __('Specialty'),
                'password_mode' => __('Password Mode'),
            ];
            
            // Show what the Jalsah AI name will be
            $jalsah_ai_name = !empty($meta['name'][0]) ? $meta['name'][0] : $meta['name_en'][0];
            echo "<tr><th>" . __('Jalsah AI Name') . "</th><td><strong>" . esc_html($jalsah_ai_name) . "</strong></td></tr>";
            foreach ($fields as $key => $label) {
                $val = esc_html($meta[$key][0] ?? '');
                echo "<tr><th>{$label}</th><td>{$val}</td></tr>";
            }
            // Images
            $img_fields = [
                'profile_image' => __('Profile Image'),
                'identity_front' => __('Identity Front'),
                'identity_back' => __('Identity Back'),
            ];
            foreach ($img_fields as $key => $label) {
                $id = $meta[$key][0] ?? '';
                if ($id && wp_attachment_is_image($id)) {
                    $url = wp_get_attachment_url($id);
                    echo "<tr><th>{$label}</th><td><a href='" . esc_url($url) . "' class='snks-lightbox-link'><img src='" . esc_url($url) . "' style='max-width:120px;max-height:120px;border:1px solid #ccc;cursor:pointer;' /></a></td></tr>";
                } elseif ($id) {
                    $url = wp_get_attachment_url($id);
                    echo "<tr><th>{$label}</th><td><a href='" . esc_url($url) . "' target='_blank'>" . basename($url) . "</a></td></tr>";
                }
            }
            // Certificates
            $certs = isset($meta['certificates']) ? maybe_unserialize($meta['certificates'][0]) : [];
            if ($certs && is_array($certs)) {
                echo "<tr><th>" . __('Certificates') . "</th><td>";
                foreach ($certs as $cid) {
                    $url = wp_get_attachment_url($cid);
                    if (wp_attachment_is_image($cid)) {
                        echo "<a href='" . esc_url($url) . "' class='snks-lightbox-link'><img src='" . esc_url($url) . "' style='max-width:80px;max-height:80px;margin:2px;border:1px solid #ccc;cursor:pointer;' /></a> ";
                    } else {
                        echo "<a href='" . esc_url($url) . "' target='_blank'>" . basename($url) . "</a> ";
                    }
                }
                echo "</td></tr>";
            }
            echo '</table>';
            // Lightbox overlay markup
            echo "<div id='snks-lightbox-overlay' style='display:none;position:fixed;z-index:99999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);align-items:center;justify-content:center;'><img id='snks-lightbox-img' src='' style='max-width:90vw;max-height:90vh;border:4px solid #fff;border-radius:8px;box-shadow:0 0 24px #000;'/></div>";
            // Lightbox JS
            echo "<script>document.addEventListener('DOMContentLoaded',function(){var links=document.querySelectorAll('.snks-lightbox-link');var overlay=document.getElementById('snks-lightbox-overlay');var img=document.getElementById('snks-lightbox-img');links.forEach(function(link){link.addEventListener('click',function(e){e.preventDefault();img.src=link.href;overlay.style.display='flex';});});overlay.addEventListener('click',function(){overlay.style.display='none';img.src='';});});</script>";
        },
        'therapist_app',
        'normal',
        'high'
    );
});

/**
 * Get Jalsah AI name for a therapist
 * 
 * @param int $user_id The user ID
 * @return string The Jalsah AI name
 */
function snks_get_jalsah_ai_name($user_id) {
    $jalsah_ai_name = get_user_meta($user_id, 'jalsah_ai_name', true);
    
    // If Jalsah AI name is not set, fall back to first_name or display_name
    if (empty($jalsah_ai_name)) {
        $user = get_user_by('id', $user_id);
        if ($user) {
            $jalsah_ai_name = get_user_meta($user_id, 'first_name', true);
            if (empty($jalsah_ai_name)) {
                $jalsah_ai_name = $user->display_name;
            }
        }
    }
    
    return $jalsah_ai_name;
}

/**
 * Set Jalsah AI name for a therapist
 * 
 * @param int $user_id The user ID
 * @param string $name The name to set
 * @return bool Success status
 */
function snks_set_jalsah_ai_name($user_id, $name) {
    return update_user_meta($user_id, 'jalsah_ai_name', sanitize_text_field($name));
} 