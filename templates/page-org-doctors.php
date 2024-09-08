<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();
global $wp;
$org_slug = sanitize_text_field( $wp->query_vars['org'] );
$org      = get_page_by_path( $org, OBJECT, 'organization' );
if ( $org ) {
	$days_labels = json_decode( DAYS_ABBREVIATIONS, true );
	?>
	<style>
		header, footer{
			display: none;
		}
		.secondary_color_bg{
			background-color: <?php echo esc_html( get_post_meta( $org->ID, 'secondary_color', true ) ); ?>!important
		}
		.secondary_color_text{
			color: <?php echo esc_html( get_post_meta( $org->ID, 'secondary_color', true ) ); ?>!important
		}
		.main_color_bg{
			background-color: <?php echo esc_html( get_post_meta( $org->ID, 'main_color', true ) ); ?>!important
		}
		.main_color_text{
			color: <?php echo esc_html( get_post_meta( $org->ID, 'main_color', true ) ); ?>!important
		}
		.snks-white-text{
			color: #fff !important;
		}
	</style>
	<?php echo do_shortcode( '[elementor-template id="3090"]' ); ?>
	<div class="snks-org-doctors-container">
			<div class="anony-flex flex-v center flex-h-center anony-padding-20 secondary_color_bg">
				<?php echo get_the_post_thumbnail( $org->ID, 'thumbnail' ); ?>
			</div>
			<?php
			$children_objects = anony_query_related_children( absint( $wp->query_vars['term_id'] ), 24 );
			if ( ! empty( $children_objects ) ) {
				$users = array_column( $children_objects, 'child_object_id' );
				?>
				<div class="snks-doctor-listing anony-grid-row main_color_bg anony-padding-10">
					<?php
					foreach ( $users as $user_id ) {
						$user_details        = snks_user_details( $user_id );
						$doctor_url          = snks_encrypted_doctor_url( $user_id );
						$closest_appointment = snks_get_closest_timetable( $user_id );
						$profile_image       = get_user_meta( $user_id, 'profile-image', true );
						if ( empty( $profile_image ) ) {
							$profile_image = '/wp-content/uploads/2024/08/portrait-3d-male-doctor_23-2151107083.avif';
						} elseif ( is_numeric( $profile_image ) ) {
							$profile_image_src = wp_get_attachment_image_src( absint( $profile_image ), 'full' );
							$profile_image     = $profile_image_src[0];
						}
						?>
						<div class="anony-grid-col anony-grid-col-4 anony-grid-col-av-6 anony-grid-col-lg-12 anony-padding-10">
							<div class="snks-profile-image-wrapper">
								<a href="<?php echo esc_url( $doctor_url ); ?>">
									<div class="snks-tear-shap-wrapper">
										<div class="snks-tear-shap">
											<img src="<?php echo esc_url( $profile_image ); ?>"/>
										</div>
										<div class="snks-tear-shap sub anony-box-shadow"></div>
									</div>
								</a>
							</div>
		
							<div class="profile-details">
								<h1 class="kacstqurnkacstqurn snks-white-text" style="font-size:28px;"><?php echo esc_html( $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] ); ?></h1>
							</div>
							<div class="snks-listing-periods anony-full-width">
								<?php snks_listing_periods( $user_id ); ?>
							</div>
							<?php if ( is_array( $closest_appointment ) && ! empty( $closest_appointment ) ) { ?>
							<div class="snks-listing-closest-date anony-full-width anony-center-text">
								<h5 class="hacen_liner_print-outregular snks-white-text anony-padding-10">أول موعد متاح للحجز</h5>
								<span class="anony-grid-col hacen_liner_print-outregular anony-flex anony-flex-column flex-h-center flex-v-center anony-padding-10 anony-margin-5" style="background-color:#fff;border-radius:25px;font-size: 17px;width: 160px;margin: auto;">
									<span>
									<?php
									printf(
										'%1$s | %2$s',
										esc_html( $days_labels[ $closest_appointment[0]->day ] ),
										esc_html( gmdate( 'Y-m-d', strtotime( $closest_appointment[0]->date_time ) ) )
									);
									?>
									</span>
									<div class="main_color_bg anony-margin-5" style="width:100%;height:2px;"></div>
									<span>
									<?php
									printf(
										'%1$s %2$s',
										'الساعة',
										esc_html( snks_localized_time( $closest_appointment[0]->starts ) ),
									);
									?>
									</span>
								</span>
							</div>
							<?php } ?>
							<div style="background-color:#fff;width:100%;height:2px;margin:20px 0"></div>
							<a href="<?php echo esc_url( $doctor_url ); ?>" class="main_color_text anony-flex anony-full-width anony-padding-3 flex-h-center" style="background-color:#fff;border-radius:25px;font-size: 18px;">إحجز موعد</a>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			echo do_shortcode( '[elementor-template id="3106"]' );
			?>
	</div>
	<?php
}
get_footer();
