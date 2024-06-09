<?php
/**
 * Roles
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

add_action(
	'init',
	function () {
		add_role(
			'doctor',
			'Doctor',
			array(
				'read'         => true,
				'edit_posts'   => true,
				'delete_posts' => true,
			)
		);

		add_role(
			'patient',
			'Patient',
			array(
				'read' => true,
			)
		);
	}
);
