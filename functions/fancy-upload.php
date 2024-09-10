<?php
/**
 * Fancy upload
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


add_shortcode(
	'jetFancyUpload',
	function ( $atts ) {
		$a = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);
		if ( empty( $a['id'] ) ) {
			return 'Jet form media field ID is missing';
		}
		$id = $atts['id'];
		ob_start();  ?>
		<?php

		$current_avatar_url = '';
		add_action(
			'wp_footer',
			function () use ( $id ) {
				?>
			<script>
				document.getElementById('<?php echo esc_html( $id ); ?>').addEventListener('change', function() {
					// This function will be triggered when a file has been selected
					var selectedFile = this.files[0]; // Get the selected file

					if (selectedFile) {
						var reader = new FileReader();
						
						reader.onload = function(e) {
							document.getElementById('<?php echo esc_html( $id ); ?>-preview').style.backgroundImage = 'url(' + e.target.result + ')';
							document.getElementById('<?php echo esc_html( $id ); ?>-preview').classList.add('has-preview');
						};
						
						reader.readAsDataURL(selectedFile);
					}
				});
			</script>
				<?php
			}
		);
		?>
	<div id="<?php echo esc_attr( $a['id'] ); ?>-preview" class="anony-nice-uploader anony-nice-uploader-trigger" data-target="<?php echo esc_attr( $a['id'] ); ?>" data-current-avatar="<?php echo esc_url( $current_avatar_url ); ?>"></div>
		<?php
		return ob_get_clean();
	}
);

add_action(
	'wp_head',
	function () {
		?>
	<style>
		.anony-nice-uploader, .anony-nice-uploader div, .anony-nice-uploader-trigger {
			height: 100%;
			width: 100%;
			cursor: pointer;
			
		}
		.anony-nice-uploader-trigger{
			width:120px;
			height:120px;
			border:2px dashed #16b720;
			background-color:#fff;
			border-radius:10px;
			position: relative;
			margin: auto;
		}
		.anony-nice-uploader-trigger::after{
			content: '+';
			background-color: #fff;
			display: flex;
			position: absolute;
			width: 25px;
			height: 25px;
			z-index: 10;
			border: 1px dashed #16b720;
			border-radius: 50%;
			justify-content: center;
			align-items: center;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			margin: auto;
		}
		.has-preview.anony-nice-uploader-trigger::after, .jet-form-builder-file-upload{
			display:none!important;
		}
		.has-preview{
			position: relative;
			background-repeat   : no-repeat;
			background-position : center;
				background-size : contain;
		}

		.jet-form-builder-file-upload__input{
			position: absolute;
			left: -9999px;
		}
	</style>
		<?php
	}
);


add_action(
	'wp_footer',
	function () {

		if ( is_user_logged_in() ) {
			return;
		}
		?>
		<script>
		jQuery(document).ready(function($){
			$(document).on(
				'click',
				".anony-nice-uploader-trigger",
				function(){
					var clicked = $( this );
					var target = clicked.data('target');
					$('#' + target).trigger('click');	
				}
			);
		});
		</script>
		<?php
	}
);
