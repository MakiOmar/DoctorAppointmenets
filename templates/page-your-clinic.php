<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();
$user_id      = snks_url_get_doctors_id();
$user_details = snks_user_details( $user_id );
?>
<div class="snks-booking-page-container">
	<?php
	$org_post = snks_get_user_organization( $user_id );

	if ( $org_post && $org_post instanceof WP_Post ) {
		$org_slug       = $org_post->post_name;
		$org_logo       = get_the_post_thumbnail_url( $org_post->ID, 'medium' );
		$org_main_color = get_post_meta( $org_post->ID, 'main_color', true );
		//phpcs:disable
		$back_url = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : site_url( '/org/' . $org_slug );
		//phpcs:enable
		if ( ! $org_logo ) {
			$org_logo = '/wp-content/uploads/2024/10/default-logo.png';
		}
		?>
			<div style="background-color: <?php echo esc_html( $org_main_color ); ?>; display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; overflow: hidden;max-width: 428px;margin: auto;">
				<a href="<?php echo esc_url( site_url( '/org/' . $org_slug ) ); ?>">
				<img src="<?php echo esc_url( $org_logo ); ?>" alt="شعار المنظمة" style="height: 60px; border-radius: 50%; border: 3px solid #fff;margin: 0;" />
				</a>
				<a href="<?php echo esc_url( $back_url ); ?>" style="display: flex; align-items: center; text-decoration: none;">
					<svg style="width: 30px; height: 30px; fill: white;" viewBox="0 0 24 24">
						<path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
					</svg>
				</a>
				
			</div>
	<?php } ?>

	<div id="snks-booking-page">
		<?php echo do_shortcode( '[snks_go_back]' ); ?>
		<?php
		if ( ! $user_id ) {
			?>
			<div class="anony-flex flex-h-center"><p>هناك شيء خاطيء!</p></div>
			<?php

		} else {
			$profile_image = get_user_meta( $user_id, 'profile-image', true );
			if ( empty( $profile_image ) ) {
				$profile_image = '/wp-content/uploads/2025/02/360_F_346936114_RaxE6OQogebgAWTalE1myseY1Hbb5qPM.jpg';
			} elseif ( is_numeric( $profile_image ) ) {
				$profile_image_src = wp_get_attachment_image_src( absint( $profile_image ), 'full' );
				$profile_image     = $profile_image_src[0];
			}
			?>
		<div class="snks-profile-image-wrapper">
			<div id="head1" class="shap-head">
				<img src="/wp-content/uploads/2025/02/head1.png">
				<div class="shap-head-bg"></div>
			</div>
			
			<div id="head2" class="shap-head">
				<img src="/wp-content/uploads/2025/02/head-3.png">
				<div class="shap-head-bg"></div>
			</div>			
			<div id="head3" class="shap-head">
				<img src="/wp-content/uploads/2025/02/head-2.png">
				<div class="shap-head-bg"></div>
			</div>
			<div class="snks-tear-shap-wrapper">
				<div class="snks-tear-shap">
					<img src="<?php echo esc_url( $profile_image ); ?>"/>
				</div>
				<div class="snks-tear-shap sub anony-box-shadow"></div>
			</div>
		</div>
		<div class="profile-details">
			<h1 class="kacstqurnkacstqurn" style="font-size:28px;"><?php echo esc_html( $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] ); ?></h1>
			<h2 style="font-size:25px;margin:0;margin-bottom:20px;text-align: center;padding: 0 25px;line-height: 35px;"><?php echo wp_kses_post( get_user_meta( $user_id, 'doctor_specialty', true ) ); ?></h2>
			<div class="snks-profile-accordion">
				<?php
				//phpcs:disable
				echo anony_accordion(
					array(
						array(
							'title'   => 'الشهادات والخبرات',
							'content' => snks_get_doctor_experiences( $user_id ),
						),
					)
				);
				//phpcs:enable
				?>
			</div>
		</div>
		<div class="snks-light-bg" style="display: flex;justify-content: center;padding: 10px;position:relative;">
			<h3 style="font-size: 28px;padding-bottom: 3px;">إحجز جلسة</h3>
		</div>

		<div id="snks-booking-form" class="anony-grid-row" style="max-width: 960px;margin:auto;position: relative;">
			<div id="teeth-area"></div>
			<?php echo do_shortcode( '[snks_appointment_form]' ); ?>
		</div>
			<?php echo do_shortcode( '[elementor-template id="2988"]' ); ?>
		<?php } ?>
		
	</div>
</div>
<?php
get_footer();
