<?php
/**
 * Template Name: Your Clinic
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die;

get_header();
$user_id = snks_url_get_doctors_id();
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
	
</style>
<div id="snks-booking-page">
	<?php
	if ( ! $user_id ) {
		?>
		<div class="anony-flex flex-h-center"><p>هناك شيء خاطيء!</p></div>
		<?php

	} elseif ( isset( $record ) ) {
		$profile_image = get_user_meta( $record->user_id, 'profile-image', true );
		if ( empty( $profile_image ) ) {
			$profile_image = '/wp-content/uploads/2024/08/portrait-3d-male-doctor_23-2151107083.avif';
		}
		?>
	<div class="snks-tear-shap-wrapper">
		<div class="snks-tear-shap">
			<img src="<?php echo esc_url( $profile_image ); ?>"/>
		</div>
		<div class="snks-tear-shap sub anony-box-shadow"></div>
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
