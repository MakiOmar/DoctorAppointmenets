<?php
/**
 * Template for displaying specialties.
 *
 * @package Jalsah
 */

defined( 'ABSPATH' ) || die;

global $wp;
$org_slug = sanitize_text_field( $wp->query_vars['org'] );
$org      = get_page_by_path( $org, OBJECT, 'organization' );
if ( ! $org ) {
	return;
}
// Load custom header from plugin.
$plugin_header_path = SNKS_DIR . 'header-organization.php';

if ( file_exists( $plugin_header_path ) ) {
	require $plugin_header_path;
} else {
	get_header();
}

$main_color      = get_post_meta( $org->ID, 'main_color', true );
$secondary_color = get_post_meta( $org->ID, 'secondary_color', true );
snks_org_styles( $main_color, $secondary_color );
?>
<div id="org-container" style="background-color: #fff;">
	<?php snks_print_org_header( $org->ID ); ?>
	<?php
		echo do_shortcode( "[specialization_grid box_color={$main_color} text_color=#fff id={$org->ID}]" );
	?>
	<?php echo do_shortcode( '[elementor-template id="3106"]' ); ?>
</div>
<?php
get_footer();
