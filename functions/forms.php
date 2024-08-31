<?php
/**
 * Ajax
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

if ( class_exists( 'ANONY_Create_Form' ) ) {
	$form = array(
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

	$init = new ANONY_Create_Form( $form );
}
