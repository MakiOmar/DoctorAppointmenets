<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Generate session form arguments dynamically.
 *
 * @param string $form_id   The unique ID for the form.
 * @param string $form_type The type of form ('insert' or 'edit').
 *
 * @return array The configuration array for the form.
 */
function snks_create_session_form_args( $form_id, $form_type ) {
	$form_args = array(
		'id'              => $form_id,
		'fields_layout'   => 'columns',
		'context'         => 'form',
		'submit_label'    => 'حفظ',
		'used_in'         => array( 1935 ),
		'form_attributes' => array(
			'action'  => '',
			'method'  => 'post',
			'enctype' => 'multipart/form-data',
		),
		'fields'          => array(
			array(
				'title'      => 'اضف ملاحظاتك',
				'id'         => 'content',
				'validate'   => 'html',
				'type'       => 'textarea',
				'direction'  => 'rtl',
				'text-align' => 'right',
			),
			array(
				'id'       => 'session_id',
				'validate' => 'no_html',
				'type'     => 'hidden',
				'default'  => '{session_id}',
			),
			array(
				'title'    => 'إضافة ملفات',
				'id'       => 'files',
				'validate' => 'no_html',
				'type'     => 'gallery',
			),
		),
		'action_list'     => array(
			'Update_post' => array(
				'post_data' => array(
					'post_title'   => 'Session notes #session_id',
					'post_status'  => 'publish',
					'post_type'    => 'session_notes',
					'post_content' => '#content',
					'post_author'  => get_current_user_id(),
				),
				'meta'      => array(
					'session_id' => '#session_id',
					'files'      => '#files',
				),
			),
		),
		'conditions'      => array(
			'logged_in' => true,
			'user_role' => array( 'administrator', 'doctor' ),
		),
	);

	if ( 'edit' === $form_type ) {
		$form_args['defaults'] = array(
			'object_type'    => 'post',
			'object_id_from' => 'shortcode_attr',
		);
	}

	return $form_args;
}

if ( class_exists( 'ANONY_Create_Form' ) ) {
	new ANONY_Create_Form( snks_create_session_form_args( 'user_insert_session_notes', 'insert' ) );
	new ANONY_Create_Form( snks_create_session_form_args( 'user_edit_session_notes', 'edit' ) );
}

add_action(
	'anony_form_submitted',
	function ( $_request, $_id ) {
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			wp_send_json_success();
		}
		if ( in_array( $_id, array( 'user_insert_session_notes', 'user_edit_session_notes' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'id', snks_get_settings_doctor_id(), home_url( '/account-setting' ) ) );
			exit;
		}
	},
	10,
	2
);
