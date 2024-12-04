<?php
/**
 * SMS Notifications
 *
 * @package Nafea
 */

defined( 'ABSPATH' ) || die();

if ( ! wp_next_scheduled( 'flush_rewrite_cron_job' ) ) {
	wp_schedule_event( time(), 'hourly', 'snks_flush_rewrite_cron_job' );
}
add_action(
	'snks_flush_rewrite_cron_job',
	function () {
		flush_rewrite_rules();
	}
);
