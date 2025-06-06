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
