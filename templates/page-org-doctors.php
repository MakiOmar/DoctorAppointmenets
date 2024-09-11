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
$term     = get_term( $term_id );
if ( is_wp_error( $term ) ) {
	return;
}

if ( $org ) {
	$days_labels = json_decode( DAYS_ABBREVIATIONS, true );
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
				top: -21px;
		}
		.snks-doctor-listing > div:nth-child(odd) {
			border-left: 1px dashed #fff;
		}
		.snks-doctor-listing > div{
			border-bottom: 1px dashed #fff;
			padding-bottom: 50px;
			flex-grow: 0;
		}
		.snks-doctor-listing,.snks-org-doctors-container{
			max-width: 428px;
			margin: auto;
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
		.main_color_text,.terms-condstions-text, .terms-condstions-text h2{
			color: <?php echo esc_html( get_post_meta( $org->ID, 'main_color', true ) ); ?>!important
		}
		.snks-white-text{
			color: #fff !important;
		}
	</style>
	<?php echo do_shortcode( '[elementor-template id="3090"]' ); ?>
	<div class="snks-org-doctors-container">
			<div class="anony-flex flex-v center flex-h-center anony-padding-20 secondary_color_bg">
				<a href="/org/<?php echo esc_html( $org_slug ); ?>" style="display:block;height:150px">
					<?php echo get_the_post_thumbnail( $org->ID, 'full' ); ?>
				</a>
			</div>
			<h1 class="main_color_text" style="background-color: #fff;text-align:center;font-size:25px;position: relative;top: -8px;padding: 10px 0 12px 0;"><?php printf( 'حجز جلسات إشراف %s', esc_html( $term->name ) ); ?></h1>
			<?php
			$children_objects = anony_query_related_children( absint( $wp->query_vars['term_id'] ), 24 );
			if ( ! empty( $children_objects ) ) {
				$users  = array_column( $children_objects, 'child_object_id' );
				$orders = array();
				foreach ( $users as $user_id ) {
					$_order             = get_user_meta( $user_id, 'order_position', true );
					$_order             = ! empty( $_order ) ? $_order : 0;
					$orders[ $user_id ] = $_order;
				}

				// Sort the first array based on the values of the second array.
				array_multisort(
					array_map(
						function ( $id ) use ( $orders ) {
							return $orders[ $id ];
						},
						$users
					),
					$users
				);
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
						<div class="anony-grid-col anony-grid-col-6 anony-padding-10">
							<div class="snks-profile-image-wrapper">
								<a href="<?php echo esc_url( $doctor_url ); ?>" target="_blank">
									<div class="snks-tear-shap-wrapper">
										<div class="snks-tear-shap" style="background-image: url('<?php echo esc_url( $profile_image ); ?>');background-position: center;background-repeat: repeat;background-size: cover;">
											<!--<img src="<?php echo esc_url( $profile_image ); ?>"/>-->
										</div>
										<div class="snks-tear-shap sub anony-box-shadow"></div>
									</div>
								</a>
							</div>
		
							<div class="profile-details">
								<h1 class="kacstqurnkacstqurn snks-white-text" style="font-size:18px;text-align:center"><?php echo esc_html( $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] ); ?></h1>
							</div>
							<div class="snks-listing-periods anony-full-width">
								<?php snks_listing_periods( $user_id ); ?>
							</div>
							<div class="snks-listing-closest-date anony-full-width anony-center-text">
								<h5 class="hacen_liner_print-outregular snks-white-text anony-padding-10">أول موعد متاح للحجز</h5>
								<span class="anony-grid-col hacen_liner_print-outregular anony-flex anony-flex-column flex-h-center flex-v-center anony-padding-10 anony-margin-5" style="background-color:#fff;border-radius:25px;font-size: 17px;width: 160px;margin: auto;height: 85px;">
									<?php if ( is_array( $closest_appointment ) && ! empty( $closest_appointment ) ) { ?>
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
										<?php
									} else {
										echo 'غير متاح'; }
									?>
								</span>
							</div>
							<div style="background-color:#fff;width:100%;height:2px;margin:20px 0"></div>
							<a href="<?php echo esc_url( $doctor_url ); ?>" target="_blank" class="main_color_text anony-flex anony-full-width anony-padding-3 flex-h-center" style="background-color:#fff;border-radius:25px;font-size: 18px;">إحجز موعد</a>
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
