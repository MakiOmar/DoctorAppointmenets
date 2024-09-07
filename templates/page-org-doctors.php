<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();

?>
<div class="snks-org-doctors-container">
	<?php
	global $wp;
	$children_objects = anony_query_related_children( absint( $wp->query_vars['term_id'] ), 24 );
	if ( ! empty( $children_objects ) ) {
		$users = array_column( $children_objects, 'child_object_id' );
		snks_print_r( $users );

	}
	?>
</div>
<?php
get_footer();
