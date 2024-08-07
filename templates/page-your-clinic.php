<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();
?>
<div class="anony-grid-row" style="max-width: 960px;margin:auto;margin-top:30px">
	<div class="anony-grid-col">
		<?php echo do_shortcode( '[snks_appointment_form]' ); ?>
	</div>
</div>
<?php
get_footer();
