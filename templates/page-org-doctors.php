<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

// Load custom header from plugin.
$plugin_header_path = SNKS_DIR . 'header-organization.php';

if ( file_exists( $plugin_header_path ) ) {
	require $plugin_header_path;
} else {
	get_header();
}
global $wp;
$org_slug = sanitize_text_field( $wp->query_vars['org'] );
$org      = get_page_by_path( $org, OBJECT, 'organization' );
$_term    = get_term( $_term_id );
if ( is_wp_error( $_term ) ) {
	return;
}

if ( $org ) {
	$slogan           = get_post_meta( $org->ID, 'slogan', true );
	$description      = get_post_field( 'post_content', $org->ID );
	$main_color       = get_post_meta( $org->ID, 'main_color', true );
	$secondary_color  = get_post_meta( $org->ID, 'secondary_color', true );
	$header_color     = get_post_meta( $org->ID, 'header_color', true );
	$children_objects = get_doctors_by_org_and_specialty( $org->ID, $wp->query_vars['_term_id'] );
	?>
	<style>
		header, footer{
			display: none;
		}
		.snks-org-doctors-container{
			border-right: 2px solid #fff;
			border-left: 2px solid #fff;
			overflow: hidden;
		}
		.snks-doctor-listing{
			padding: 0!important;
			position: relative;
			background-color: #fff;
			padding-top: 20px!important;
			padding-bottom: 20px !important;
		}

		.snks-doctor-listing > div{
			flex-grow: 0;
		}
		.snks-doctor-listing,.snks-org-doctors-container{
			max-width: 428px;
			margin: auto;
		}
		.secondary_color_bg{
			background-color: <?php echo esc_html( $secondary_color ); ?>!important
		}
		.secondary_color_text{
			color: <?php echo esc_html( $secondary_color ); ?>!important
		}
		.main_color_bg{
			background-color: <?php echo esc_html( $main_color ); ?>!important
		}
		.main_color_text,.terms-condstions-text, .terms-condstions-text h2{
			color: <?php echo esc_html( $main_color ); ?>!important
		}
		.snks-white-text{
			color: #fff !important;
		}
		.snks-doctor-listing-item{
			padding: 10px;
		}
		.snks-doctor-listing-item .item-content{
			background-color: <?php echo esc_html( $main_color ); ?>;
			border-radius: 10px;
			padding-bottom: 10px;
			text-align: center;
		}
	</style>
	<?php
	snks_org_styles( $main_color, $secondary_color, $header_color );
	?>
	<?php if ( ! isset( $wp->query_vars['_term_id'] ) && ! isset( $wp->query_vars['is_specialties'] ) ) { ?>
		<div class="clinic-description">
			<h2><?php echo wp_kses_post( $slogan ); ?></h2>
			<hp><?php echo wp_kses_post( $description ); ?></p>
		</div>
	<?php } ?>
	<div class="snks-org-doctors-container">
			<div class="anony-flex flex-v center flex-h-center anony-padding-20">
				<a href="/org/<?php echo esc_html( $org_slug ); ?>" style="display:block;height:150px">
					<?php echo get_the_post_thumbnail( $org->ID, 'full' ); ?>
				</a>
			</div>
			<div style="background-color: #fff;padding-top: 20px;">
				<?php
				if ( ! empty( $children_objects ) ) {
					$_title       = sprintf( 'حجز جلسات إشراف %s',  $_term->name );
					$custom_title = get_term_meta( $_term->term_id, 'custom_title', true );
					if ( $custom_title && ! empty( $custom_title ) ) {
						$_title = $custom_title;
					}
					?>
				<h1 style="border-radius: 10px; margin: auto; background-color: <?php echo esc_html( $main_color ); ?>;text-align:center;color:#fff;max-width: 80%; font-size:25px;position: relative;padding: 10px 0 12px 0;"><?php echo esc_html( $_title ) ?></h1>
					<?php
					$orders = array();

					foreach ( $children_objects as $user ) {
						$user_id            = $user->ID;
						$order_position     = get_user_meta( $user_id, 'order_position', true );
						$orders[ $user_id ] = ! empty( $order_position ) ? $order_position : 0;
					}

					// Sort by order_position.
					usort(
						$children_objects,
						function ( $a, $b ) use ( $orders ) {
							return $orders[ $a->ID ] <=> $orders[ $b->ID ];
						}
					);

					snks_render_doctor_listing( $children_objects );
				}
				?>
			</div>
			<div style="height: 7px;"></div>
			<?php
			echo do_shortcode( '[elementor-template id="3106"]' );
			?>
	</div>
	<?php
}
get_footer();
