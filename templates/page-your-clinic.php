<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();
$user_id = snks_url_get_doctors_id();
$user_details = snks_user_details( $user_id );
?>
<style>
	#snks-booking-page{
		overflow: hidden;
	}
	.snks-tear-shap-wrapper{
		width: 200px;
		height: 200px;
		margin: auto;
		margin-top: 50px;
		transform: rotate(45deg);
	}
	.snks-tear-shap{
		width: 100%;
		height: 100%;
		border-radius: 50%;
		border-top-right-radius: 0;
		transform: rotate(-45deg);
		overflow: hidden;
		background-color: #fff;
	}
	.snks-tear-shap.sub{
		position: absolute;
		right: 0px;
		top: 13px;
		z-index: -1;
	}
	.snks-tear-shap img{
		height: 203px;
		width: 203px;
	}
	.snks-profile-image-wrapper{
		position: relative;
		max-width: 350px;
		margin: auto;
	}
	#head1{
		top: -40px;
	left: 90px;
	}
	#head2{
		bottom: -20px;
		left: 40px;
	}
	#head3{
		bottom: 50px;
	right: 20px;
	}
	.shap-head{
		position: absolute;
		height: 45px;
	}
	.profile-details{
		margin-top: 20px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}
	.profile-details h1,.profile-details h2{
		margin: 5px 0;
		color:#024059
	}
</style>
<div id="snks-booking-page">
	<?php
	if ( ! $user_id ) {
		?>
		<div class="anony-flex flex-h-center"><p>هناك شيء خاطيء!</p></div>
		<?php

	} else {
		$profile_image = get_user_meta( $user_id, 'profile-image', true );
		if ( empty( $profile_image ) ) {
			$profile_image = '/wp-content/uploads/2024/08/portrait-3d-male-doctor_23-2151107083.avif';
		}
		?>
	<div class="snks-profile-image-wrapper">
		<img src="/wp-content/uploads/2024/09/head1.png" id="head1" class="shap-head">
		<img src="/wp-content/uploads/2024/09/head1.png" id="head2" class="shap-head">
		<img src="/wp-content/uploads/2024/09/head-2.png" id="head3" class="shap-head">
		<div class="snks-tear-shap-wrapper">
			<div class="snks-tear-shap">
				<img src="<?php echo esc_url( $profile_image ); ?>"/>
			</div>
			<div class="snks-tear-shap sub anony-box-shadow"></div>
		</div>
	</div>
	<div class="profile-details">
		<h1 style="font-size:16px;"><?php echo esc_html( $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] ); ?></h1>
		<h2 style="font-size:20px;font-weight:bold"><?php echo esc_html( get_user_meta( $user_id, 'doctor_specialty', true ) ); ?></h2>
	</div>
	<div id="snks-booking-form" class="anony-grid-row" style="max-width: 960px;margin:auto;margin-top:30px">
		<div class="anony-grid-col">
			<?php echo do_shortcode( '[snks_appointment_form]' ); ?>
		</div>
	</div>
	<?php } ?>
	
</div>
<?php
get_footer();
