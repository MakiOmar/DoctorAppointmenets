<?php
/**
 * Clinics colors
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clinic_Colors_Widget extends Widget_Base {

	public function get_name() {
		return 'clinic_colors_widget';
	}

	public function get_title() {
		return __( 'Clinic Colors Form', 'elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$current_user_id = snks_get_settings_doctor_id();
		$clinic_color    = get_user_meta( $current_user_id, 'clinic_colors', true );
		$image_base_url  = '/wp-content/uploads/2024/09';
		$nonce           = wp_create_nonce( 'clinic_colors_nonce' );
		$images          = range( 1, 20 );

		// Output HTML form and AJAX JavaScript directly in the render method.
		//phpcs:disable
		?>
		<form id="clinic-colors-form" action="" method="post">
			<div class="clinic-colors-grid">
				<?php foreach ( $images as $image ) : ?>
					<div class="clinic-color-item">
						<input type="radio" name="clinic_color" id="color-<?php echo $image; ?>" value="<?php echo $image; ?>" hidden>
						<label for="color-<?php echo $image; ?>">
							<img src="<?php echo esc_url( $image_base_url . "/$image.png" ); ?>" alt="Color <?php echo $image; ?>" class="clinic-color-image<?php echo $image == absint( $clinic_color ) ? ' selected' : ''; ?>">
						</label>
					</div>
				<?php endforeach; ?>
			</div>
			<button class="anony-full-width" type="submit" name="submit_clinic_color">حفظ</button>
			<div id="clinic-colors-response"></div>
		</form>

		<style>
			#clinic-colors-form button{
				margin-top: 20px;
				border-radius: 5px;
			}
			.clinic-colors-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 10px;
			}
			.clinic-color-item {
				text-align: center;
			}
			.clinic-color-image {
				cursor: pointer;
				max-width: 100px;
				max-height: 100px;
				border: 2px solid transparent;
				transition: border-color 0.3s;
			}
			input[type="radio"]:checked + label .clinic-color-image {
				border-color: #0073aa;
			}
			.clinic-color-label input:checked + .clinic-color-image,
			.clinic-color-image.selected {
				border: 5px solid #716c6c!important;
				border-radius: 50%;
			}
			.clinic-color-response {
				text-align: center;
				padding: 10px;
				margin-top: 10px;
				border-radius: 5px;
			}
			.clinic-color-response.success{
				color:green;
				border: 1px solid green;
			}
			.clinic-color-response.error{
				color:red;
				border: 1px solid red;
			}
		</style>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$(document).on(
					'submit',
					'#clinic-colors-form',
					function (e) {
						e.preventDefault();

						const selectedColor = $('input[name="clinic_color"]:checked').val();
						const nonce = '<?php echo $nonce; ?>';

						$.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: 'POST',
							data: {
								action: 'clinic_colors_submit',
								clinic_color: selectedColor,
								nonce: nonce
							},
							success: function (response) {
								if (response.success) {
									$('#clinic-colors-response').html('<p class="clinic-color-response success">' + response.data.message + '</p>');
								} else {
									$('#clinic-colors-response').html('<p class="clinic-color-response error">' + response.data.message + '</p>');
								}
							},
							error: function () {
								$('#clinic-colors-response').html('<p style="color: red;">حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.</p>');
							}
						});
					}
				);
				$(document).on(
					'change',
					'input[name="clinic_color"]',
					function(){
						$('.clinic-color-image').removeClass('selected');
						$('input[name="clinic_color"]:checked').closest('.clinic-color-item').find('img').addClass('selected');
					}
				);
			});
		</script>
		<?php
	}
}
