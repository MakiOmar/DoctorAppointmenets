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
        // Check if user already exists
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            $user_id = $existing_user->ID;
        } else {
            // Create new user
            if (username_exists($phone)) return;
            $user_id = wp_create_user($phone, $password, $email);
            if (is_wp_error($user_id)) return;
        }
        
        $user = get_user_by('id', $user_id);
        $user->set_role('doctor');
        
        // Set Jalsah AI name using the therapist's name
        $jalsah_ai_name = !empty($name) ? $name : $name_en;
        update_user_meta($user_id, 'jalsah_ai_name', $jalsah_ai_name);
        
        // Mark application as approved and set the author to the created user
        wp_update_post([
            'ID' => $post_id, 
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);
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
            'add_new' => __('Add New Application'),
            'add_new_item' => __('Add New Therapist Application'),
            'edit_item' => __('Edit Therapist Application'),
            'new_item' => __('New Therapist Application'),
            'view_item' => __('View Therapist Application'),
            'search_items' => __('Search Applications'),
            'not_found' => __('No applications found'),
            'not_found_in_trash' => __('No applications found in trash'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'custom-fields', 'author'),
        'menu_icon' => 'dashicons-businessperson',
        'has_archive' => false,
        'map_meta_cap' => true,
    ));
});
// Add meta boxes for therapist applications
// Hide default custom fields metabox for therapist_app
add_action('add_meta_boxes', function() {
    remove_meta_box('postcustom', 'therapist_app', 'normal');
}, 20);

// Add meta boxes for therapist applications
add_action('add_meta_boxes', function() {
    // Add form meta box for editing applications
    add_meta_box(
        'therapist_app_form',
        __('Edit Application'),
        function($post) {
            if ($post->post_type !== 'therapist_app') return;
            
            // Add nonce for security
            wp_nonce_field('therapist_app_form', 'therapist_app_nonce');
            
            $meta = get_post_meta($post->ID);
            
            // Enqueue media uploader
            wp_enqueue_media();
            ?>
            <style>
                .snks-upload-field {
                    margin-bottom: 20px;
                }
                .snks-upload-preview {
                    margin-top: 10px;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    background: #f9f9f9;
                }
                .snks-upload-preview img {
                    max-width: 150px;
                    max-height: 150px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                }
                .snks-upload-preview .file-info {
                    margin-top: 10px;
                    font-size: 12px;
                    color: #666;
                }
                .snks-upload-preview .remove-file {
                    color: #dc3232;
                    text-decoration: none;
                    margin-left: 10px;
                }
                .snks-upload-preview .remove-file:hover {
                    color: #a00;
                }
                .snks-certificates-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                    gap: 10px;
                    margin-top: 10px;
                }
                .snks-certificate-item {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 8px;
                    text-align: center;
                    background: white;
                }
                .snks-certificate-item img {
                    max-width: 100px;
                    max-height: 100px;
                    border-radius: 4px;
                }
                .snks-certificate-item .file-name {
                    font-size: 11px;
                    margin-top: 5px;
                    word-break: break-word;
                }
            </style>
            
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Name (Arabic)'); ?></label></th>
                    <td><input type="text" id="name" name="name" value="<?php echo esc_attr($meta['name'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="name_en"><?php _e('Name (English)'); ?></label></th>
                    <td><input type="text" id="name_en" name="name_en" value="<?php echo esc_attr($meta['name_en'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="email"><?php _e('Email'); ?></label></th>
                    <td><input type="email" id="email" name="email" value="<?php echo esc_attr($meta['email'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="phone"><?php _e('Phone'); ?></label></th>
                    <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($meta['phone'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="whatsapp"><?php _e('WhatsApp'); ?></label></th>
                    <td><input type="text" id="whatsapp" name="whatsapp" value="<?php echo esc_attr($meta['whatsapp'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="doctor_specialty"><?php _e('Specialty'); ?></label></th>
                    <td><input type="text" id="doctor_specialty" name="doctor_specialty" value="<?php echo esc_attr($meta['doctor_specialty'][0] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="password_mode"><?php _e('Password Mode'); ?></label></th>
                    <td>
                        <select id="password_mode" name="password_mode">
                            <option value="auto" <?php selected($meta['password_mode'][0] ?? '', 'auto'); ?>><?php _e('Auto Generate'); ?></option>
                            <option value="manual" <?php selected($meta['password_mode'][0] ?? '', 'manual'); ?>><?php _e('Manual Entry'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <h3><?php _e('File Uploads'); ?></h3>
            
            <!-- Profile Image Upload -->
            <div class="snks-upload-field">
                <label for="profile_image"><?php _e('Profile Image'); ?></label>
                <input type="hidden" id="profile_image" name="profile_image" value="<?php echo esc_attr($meta['profile_image'][0] ?? ''); ?>" />
                <button type="button" class="button" onclick="snksOpenMediaUploader('profile_image')"><?php _e('Choose Image'); ?></button>
                <button type="button" class="button" onclick="snksRemoveFile('profile_image')" style="display: none;"><?php _e('Remove'); ?></button>
                <div id="profile_image_preview" class="snks-upload-preview">
                    <?php
                    $profile_id = $meta['profile_image'][0] ?? '';
                    if ($profile_id && wp_attachment_is_image($profile_id)) {
                        $url = wp_get_attachment_url($profile_id);
                        $file_info = wp_get_attachment_metadata($profile_id);
                        echo '<img src="' . esc_url($url) . '" alt="Profile Image" />';
                        echo '<div class="file-info">';
                        echo '<strong>' . basename($url) . '</strong><br>';
                        echo 'Size: ' . size_format(filesize(get_attached_file($profile_id))) . '<br>';
                        echo 'Dimensions: ' . $file_info['width'] . ' × ' . $file_info['height'];
                        echo '<a href="#" class="remove-file" onclick="snksRemoveFile(\'profile_image\')">Remove</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Identity Front Upload -->
            <div class="snks-upload-field">
                <label for="identity_front"><?php _e('Identity Front'); ?></label>
                <input type="hidden" id="identity_front" name="identity_front" value="<?php echo esc_attr($meta['identity_front'][0] ?? ''); ?>" />
                <button type="button" class="button" onclick="snksOpenMediaUploader('identity_front')"><?php _e('Choose File'); ?></button>
                <button type="button" class="button" onclick="snksRemoveFile('identity_front')" style="display: none;"><?php _e('Remove'); ?></button>
                <div id="identity_front_preview" class="snks-upload-preview">
                    <?php
                    $front_id = $meta['identity_front'][0] ?? '';
                    if ($front_id) {
                        $url = wp_get_attachment_url($front_id);
                        $file_info = wp_get_attachment_metadata($front_id);
                        if (wp_attachment_is_image($front_id)) {
                            echo '<img src="' . esc_url($url) . '" alt="Identity Front" />';
                        } else {
                            echo '<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">';
                            echo '<strong>Document File</strong>';
                            echo '</div>';
                        }
                        echo '<div class="file-info">';
                        echo '<strong>' . basename($url) . '</strong><br>';
                        echo 'Size: ' . size_format(filesize(get_attached_file($front_id)));
                        echo '<a href="#" class="remove-file" onclick="snksRemoveFile(\'identity_front\')">Remove</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Identity Back Upload -->
            <div class="snks-upload-field">
                <label for="identity_back"><?php _e('Identity Back'); ?></label>
                <input type="hidden" id="identity_back" name="identity_back" value="<?php echo esc_attr($meta['identity_back'][0] ?? ''); ?>" />
                <button type="button" class="button" onclick="snksOpenMediaUploader('identity_back')"><?php _e('Choose File'); ?></button>
                <button type="button" class="button" onclick="snksRemoveFile('identity_back')" style="display: none;"><?php _e('Remove'); ?></button>
                <div id="identity_back_preview" class="snks-upload-preview">
                    <?php
                    $back_id = $meta['identity_back'][0] ?? '';
                    if ($back_id) {
                        $url = wp_get_attachment_url($back_id);
                        $file_info = wp_get_attachment_metadata($back_id);
                        if (wp_attachment_is_image($back_id)) {
                            echo '<img src="' . esc_url($url) . '" alt="Identity Back" />';
                        } else {
                            echo '<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">';
                            echo '<strong>Document File</strong>';
                            echo '</div>';
                        }
                        echo '<div class="file-info">';
                        echo '<strong>' . basename($url) . '</strong><br>';
                        echo 'Size: ' . size_format(filesize(get_attached_file($back_id)));
                        echo '<a href="#" class="remove-file" onclick="snksRemoveFile(\'identity_back\')">Remove</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Certificates Upload -->
            <div class="snks-upload-field">
                <label for="certificates"><?php _e('Certificates'); ?></label>
                <input type="hidden" id="certificates" name="certificates" value="<?php 
                    $certs = isset($meta['certificates']) ? maybe_unserialize($meta['certificates'][0]) : [];
                    echo esc_attr(is_array($certs) ? implode(',', $certs) : ''); 
                ?>" />
                <button type="button" class="button" onclick="snksOpenMediaUploader('certificates', true)"><?php _e('Add Certificates'); ?></button>
                <button type="button" class="button" onclick="snksRemoveAllCertificates()" style="display: none;"><?php _e('Remove All'); ?></button>
                <div id="certificates_preview" class="snks-upload-preview">
                    <?php
                    if ($certs && is_array($certs)) {
                        echo '<div class="snks-certificates-grid">';
                        foreach ($certs as $cert_id) {
                            $url = wp_get_attachment_url($cert_id);
                            $file_name = basename($url);
                            if (wp_attachment_is_image($cert_id)) {
                                echo '<div class="snks-certificate-item">';
                                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($file_name) . '" />';
                                echo '<div class="file-name">' . esc_html($file_name) . '</div>';
                                echo '<a href="#" class="remove-file" onclick="snksRemoveCertificate(' . $cert_id . ')">Remove</a>';
                                echo '</div>';
                            } else {
                                echo '<div class="snks-certificate-item">';
                                echo '<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">';
                                echo '<strong>Document</strong>';
                                echo '</div>';
                                echo '<div class="file-name">' . esc_html($file_name) . '</div>';
                                echo '<a href="#" class="remove-file" onclick="snksRemoveCertificate(' . $cert_id . ')">Remove</a>';
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <script>
            function snksOpenMediaUploader(fieldId, multiple = false) {
                var frame = wp.media({
                    title: 'Select File',
                    multiple: multiple,
                    library: {
                        type: multiple ? null : 'image'
                    }
                });

                frame.on('select', function() {
                    var attachments = frame.state().get('selection').map(function(attachment) {
                        attachment = attachment.toJSON();
                        return attachment;
                    });

                    if (multiple) {
                        // Handle multiple files (certificates)
                        var currentIds = document.getElementById(fieldId).value;
                        var currentArray = currentIds ? currentIds.split(',').map(function(id) { return id.trim(); }) : [];
                        
                        attachments.forEach(function(attachment) {
                            if (currentArray.indexOf(attachment.id.toString()) === -1) {
                                currentArray.push(attachment.id);
                            }
                        });
                        
                        document.getElementById(fieldId).value = currentArray.join(',');
                        snksUpdateCertificatesPreview(currentArray);
                    } else {
                        // Handle single file
                        var attachment = attachments[0];
                        document.getElementById(fieldId).value = attachment.id;
                        snksUpdateFilePreview(fieldId, attachment);
                    }
                });

                frame.open();
            }

            function snksUpdateFilePreview(fieldId, attachment) {
                var preview = document.getElementById(fieldId + '_preview');
                var removeBtn = preview.previousElementSibling;
                
                if (attachment.type === 'image') {
                    preview.innerHTML = '<img src="' + attachment.url + '" alt="' + attachment.filename + '" />' +
                        '<div class="file-info">' +
                        '<strong>' + attachment.filename + '</strong><br>' +
                        'Size: ' + (attachment.filesizeHumanReadable || 'Unknown') + '<br>' +
                        'Dimensions: ' + (attachment.width || 'Unknown') + ' × ' + (attachment.height || 'Unknown') +
                        '<a href="#" class="remove-file" onclick="snksRemoveFile(\'' + fieldId + '\')">Remove</a>' +
                        '</div>';
                } else {
                    preview.innerHTML = '<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">' +
                        '<strong>Document File</strong>' +
                        '</div>' +
                        '<div class="file-info">' +
                        '<strong>' + attachment.filename + '</strong><br>' +
                        'Size: ' + (attachment.filesizeHumanReadable || 'Unknown') +
                        '<a href="#" class="remove-file" onclick="snksRemoveFile(\'' + fieldId + '\')">Remove</a>' +
                        '</div>';
                }
                
                preview.style.display = 'block';
                removeBtn.style.display = 'inline-block';
            }

            function snksUpdateCertificatesPreview(certificateIds) {
                var preview = document.getElementById('certificates_preview');
                var removeBtn = preview.previousElementSibling;
                
                if (certificateIds && certificateIds.length > 0) {
                    // Fetch certificate details via AJAX
                    jQuery.post(ajaxurl, {
                        action: 'snks_get_certificates_preview',
                        certificate_ids: certificateIds,
                        nonce: '<?php echo wp_create_nonce('snks_certificates_preview'); ?>'
                    }, function(response) {
                        if (response.success) {
                            preview.innerHTML = response.data.html;
                            preview.style.display = 'block';
                            removeBtn.style.display = 'inline-block';
                        }
                    });
                } else {
                    preview.innerHTML = '';
                    preview.style.display = 'none';
                    removeBtn.style.display = 'none';
                }
            }

            function snksRemoveFile(fieldId) {
                document.getElementById(fieldId).value = '';
                var preview = document.getElementById(fieldId + '_preview');
                var removeBtn = preview.previousElementSibling;
                preview.innerHTML = '';
                preview.style.display = 'none';
                removeBtn.style.display = 'none';
            }

            function snksRemoveCertificate(certificateId) {
                var field = document.getElementById('certificates');
                var currentIds = field.value.split(',').map(function(id) { return id.trim(); });
                var index = currentIds.indexOf(certificateId.toString());
                if (index > -1) {
                    currentIds.splice(index, 1);
                }
                field.value = currentIds.join(',');
                snksUpdateCertificatesPreview(currentIds);
            }

            function snksRemoveAllCertificates() {
                document.getElementById('certificates').value = '';
                var preview = document.getElementById('certificates_preview');
                var removeBtn = preview.previousElementSibling;
                preview.innerHTML = '';
                preview.style.display = 'none';
                removeBtn.style.display = 'none';
            }

            // Show/hide remove buttons and previews on page load
            document.addEventListener('DOMContentLoaded', function() {
                ['profile_image', 'identity_front', 'identity_back'].forEach(function(fieldId) {
                    var field = document.getElementById(fieldId);
                    var removeBtn = field.nextElementSibling.nextElementSibling;
                    var preview = document.getElementById(fieldId + '_preview');
                    
                    console.log('Field:', fieldId, 'Value:', field.value, 'Preview content:', preview.innerHTML.trim());
                    
                    if (field.value && preview.innerHTML.trim() !== '') {
                        removeBtn.style.display = 'inline-block';
                        preview.style.display = 'block';
                        console.log('Showing preview for:', fieldId);
                    } else {
                        removeBtn.style.display = 'none';
                        preview.style.display = 'none';
                        console.log('Hiding preview for:', fieldId);
                    }
                });
                
                var certificatesField = document.getElementById('certificates');
                var certificatesRemoveBtn = certificatesField.nextElementSibling.nextElementSibling;
                var certificatesPreview = document.getElementById('certificates_preview');
                
                console.log('Certificates field value:', certificatesField.value, 'Preview content:', certificatesPreview.innerHTML.trim());
                
                if (certificatesField.value && certificatesPreview.innerHTML.trim() !== '') {
                    certificatesRemoveBtn.style.display = 'inline-block';
                    certificatesPreview.style.display = 'block';
                    console.log('Showing certificates preview');
                } else {
                    certificatesRemoveBtn.style.display = 'none';
                    certificatesPreview.style.display = 'none';
                    console.log('Hiding certificates preview');
                }
            });
            </script>
            <?php
        },
        'therapist_app',
        'normal',
        'default'
    );
});

// Save therapist application form data
add_action('save_post', function($post_id, $post, $update) {
    // Only handle therapist_app post type
    if ($post->post_type !== 'therapist_app') return;
    
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // Check nonce
    if (!isset($_POST['therapist_app_nonce']) || !wp_verify_nonce($_POST['therapist_app_nonce'], 'therapist_app_form')) return;
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Save form fields
    $fields = [
        'name', 'name_en', 'email', 'phone', 'whatsapp', 
        'doctor_specialty', 'password_mode', 'profile_image', 
        'identity_front', 'identity_back'
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Handle certificates (convert comma-separated string to array)
    if (isset($_POST['certificates'])) {
        $certificates = array_filter(array_map('trim', explode(',', $_POST['certificates'])));
        $certificates = array_map('intval', $certificates);
        update_post_meta($post_id, 'certificates', $certificates);
    }
    
}, 10, 3);

// AJAX handler for certificates preview
add_action('wp_ajax_snks_get_certificates_preview', function() {
    check_ajax_referer('snks_certificates_preview', 'nonce');
    
    $certificate_ids = $_POST['certificate_ids'] ?? [];
    $html = '';
    
    if (!empty($certificate_ids)) {
        $html .= '<div class="snks-certificates-grid">';
        foreach ($certificate_ids as $cert_id) {
            $url = wp_get_attachment_url($cert_id);
            $file_name = basename($url);
            
            if (wp_attachment_is_image($cert_id)) {
                $html .= '<div class="snks-certificate-item">';
                $html .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($file_name) . '" />';
                $html .= '<div class="file-name">' . esc_html($file_name) . '</div>';
                $html .= '<a href="#" class="remove-file" onclick="snksRemoveCertificate(' . $cert_id . ')">Remove</a>';
                $html .= '</div>';
            } else {
                $html .= '<div class="snks-certificate-item">';
                $html .= '<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">';
                $html .= '<strong>Document</strong>';
                $html .= '</div>';
                $html .= '<div class="file-name">' . esc_html($file_name) . '</div>';
                $html .= '<a href="#" class="remove-file" onclick="snksRemoveCertificate(' . $cert_id . ')">Remove</a>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    }
    
    wp_send_json_success(['html' => $html]);
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