<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

/**
 * Edit session's form's arguments.
 *
 * @param mixed $object_id Object's ID.
 * @return array
 */
function snks_edit_session_form_args( $object_id = false ) {
	$edit_notes = array(
		'id'              => 'user_edit_session_notes',
		'fields_layout'   => 'columns',
		'context'         => 'form',
		'submit_label'    => 'حفظ',
		'used_in'         => array( 2623 ),
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
	if ( $object_id ) {
		$edit_notes['defaults'] = array(
			'object_type' => 'post',
			'object_id'   => $object_id,
		);
	}
	return $edit_notes;
}

if ( class_exists( 'ANONY_Create_Form' ) ) {
	$insert_notes = array(
		'id'              => 'user_insert_session_notes',
		'fields_layout'   => 'columns',
		'context'         => 'form',
		'used_in'         => array( 2623 ),
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

	$init = new ANONY_Create_Form( $insert_notes );
}
