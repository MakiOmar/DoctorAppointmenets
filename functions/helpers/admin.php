<?php
/**
 * Admin Helper Functions
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Old therapist_app post type code removed - now using custom database table

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
 * @param string $name The Jalsah AI name
 */
function snks_set_jalsah_ai_name($user_id, $name) {
    update_user_meta($user_id, 'jalsah_ai_name', sanitize_text_field($name));
} 