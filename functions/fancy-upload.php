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
	'jet_fancy_upload',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'          => '',
				'is-gallery'  => 'no',
				'with-styles' => 'yes',
				'width'       => '70px',
				'height'      => '70px',
				'style'       => 'default',
			),
			$atts
		);
		if ( empty( $atts['id'] ) ) {
			return 'Jet form media field ID is missing';
		}
		$id = $atts['id'];
		ob_start();  ?>
		<style>
			#<?php echo esc_attr( $atts['id'] ); ?>-preview.anony-nice-uploader, .snks-tear-shap-wrapper{
				width:<?php echo esc_attr( $atts['width'] ); ?>;
				height:<?php echo esc_attr( $atts['height'] ); ?>;
			}
			<?php if ( 'one' === $atts['style'] ) { ?>
				.anony-nice-uploader {
					border: none!important;
				}
				.snks-profile-image-wrapper .has-preview.anony-nice-uploader::after {
					bottom: auto;
					left: auto;
					margin: initial;
					top: 5px;
					right: 5px;
				}
			<?php } ?>
		</style>
		<?php if ( 'yes' === $atts['with-styles'] ) { ?>
			<style>
				.jet-gallery-remove {
					position: absolute;
					left: 0;
					right: 0;
					top: 50%;
					bottom: 0;
					cursor: pointer;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: opacity 200ms linear;
					opacity: 0;
					background: rgba(0,0,0,0.4);
				}
				.jet-gallery-remove svg path {
					fill: #fff;
				}
				.has-preview:hover .jet-gallery-remove{
					opacity:1
				}
				.jet-gallery-upload .anony-nice-uploader{
					margin-bottom: 10px;
				}
				.anony-nice-uploader, .anony-nice-uploader div, .anony-nice-uploader {
					cursor: pointer;
					
				}
				.anony-nice-uploader{
					width:120px;
					height:120px;
					border:2px dashed #16b720;
					background-color:#fff;
					border-radius:10px;
					position: relative;
					margin: auto;
				}
				.jet-gallery-upload .anony-nice-uploader{
					margin: 10px!important;
				}
				.anony-nice-uploader::after{
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
				.jet-form-builder-file-upload{
					display:none!important;
				}
				.has-preview.anony-nice-uploader::after{
					content:'';
					background-image: url(https://jalsah.app/wp-content/uploads/2024/09/edit.png);
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center;
					width: 35px;
					height: 35px;
					bottom: 3px;
					left: 3px;
					margin: initial;
					top: auto;
					right: auto;
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
		<?php } ?>
		<?php
		$current_avatar_url = apply_filters( str_replace( '-', '_', $atts['id'] ) . '_current_upload_url', '' );
		if ( ! empty( $current_avatar_url ) ) {
			$style = ' style="background-image:url(\'' . $current_avatar_url . '\')"';
			$class = ' has-preview';
		} else {
			$style = '';
			$class = '';
		}
		if ( 'no' === $atts['is-gallery'] ) {
			//phpcs:disable
			?>
			<div class="snks-profile-image-wrapper <?php echo $atts['style']; ?>">
				<?php if ( 'default' === $atts['style'] ) { ?>
					<div id="<?php echo esc_attr( $atts['id'] ); ?>-preview" class="anony-nice-uploader anony-nice-uploader-trigger<?php echo $class; ?>"<?php echo $style; ?> data-target="<?php echo esc_attr( $atts['id'] ); ?>" data-current-avatar="<?php echo esc_url( $current_avatar_url ); ?>"></div>
				<?php } elseif ( 'one' === $atts['style'] ) { ?>
					<div class="snks-tear-shap-wrapper">
						<div class="snks-tear-shap">
							<div id="<?php echo esc_attr( $atts['id'] ); ?>-preview" class="anony-nice-uploader anony-nice-uploader-trigger<?php echo $class; ?>"<?php echo $style; ?> data-target="<?php echo esc_attr( $atts['id'] ); ?>" data-current-avatar="<?php echo esc_url( $current_avatar_url ); ?>"></div>
						</div>
						<div class="snks-tear-shap sub anony-box-shadow"></div>
					</div>
				<?php } ?>
			</div>
			<?php
			//phpcs:enable
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
		} else {
			?>
			<div class="jet-gallery-upload" id="<?php echo esc_attr( $atts['id'] ); ?>-preview" style="display:flex; flex-wrap:wrap">
				<div class="anony-nice-uploader anony-nice-uploader-trigger" data-target="<?php echo esc_attr( $atts['id'] ); ?>"></div>
			</div>
			<?php
			add_action(
				'wp_footer',
				function () use ( $id ) {
					?>
				<script>
					function removeFile(index, inputId) {
						var filesInput = document.getElementById(inputId);
						var files = filesInput.files;
						var fileBuffer = new DataTransfer();

						for (let i = 0; i < files.length; i++) {
							if (index !== i) {
								fileBuffer.items.add(files[i]);
							}
						}

						filesInput.files = fileBuffer.files;
					}
					document.getElementById('<?php echo esc_html( $id ); ?>').addEventListener('change', function() {
						var selectedFiles = this.files;
						for (let i = 0; i < selectedFiles.length; i++) {
							var selectedFile = selectedFiles[i];

							if (selectedFile) {
								var reader = new FileReader();

								reader.onload = function(e) {
									var preview = document.createElement('div');
									preview.classList.add('has-preview', 'anony-nice-uploader');
									preview.style.backgroundImage = 'url(' + e.target.result + ')';

									// Create the file remove div
									var fileRemoveDiv = document.createElement('div');
									fileRemoveDiv.classList.add('jet-gallery-remove');
									fileRemoveDiv.setAttribute('data-filename', selectedFile.name);

									// Add the SVG content
									fileRemoveDiv.innerHTML = `
										<svg width="22" height="22" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M4.375 7H6.125V12.25H4.375V7ZM7.875 7H9.625V12.25H7.875V7ZM10.5 1.75C10.5 1.51302 10.4134 1.30794 10.2402 1.13477C10.0762 0.961589 9.87109 0.875 9.625 0.875H4.375C4.12891 0.875 3.91927 0.961589 3.74609 1.13477C3.58203 1.30794 3.5 1.51302 3.5 1.75V3.5H0V5.25H0.875V14C0.875 14.237 0.957031 14.4421 1.12109 14.6152C1.29427 14.7884 1.50391 14.875 1.75 14.875H12.25C12.4961 14.875 12.7012 14.7884 12.8652 14.6152C13.0384 14.4421 13.125 14.237 13.125 14V5.25H14V3.5H10.5V1.75ZM5.25 2.625H8.75V3.5H5.25V2.625ZM11.375 5.25V13.125H2.625V5.25H11.375Z"></path>
										</svg>
									`;
									fileRemoveDiv.addEventListener('click', function() {
									console.log(  );
										removeFile(i, '<?php echo esc_html( $id ); ?>'); // Call the removeFile function with the index to remove the file
									});
									// Append the file remove div to the preview element
									preview.appendChild(fileRemoveDiv);

									document.getElementById('<?php echo esc_html( $id ); ?>-preview').appendChild(preview);
								};

								reader.readAsDataURL(selectedFile);
							}
						}
					});
				</script>
					<?php
				}
			);
		}
		return ob_get_clean();
	}
);

add_action(
	'wp_head',
	function () {
		?>

		<?php
	}
);


add_action(
	'wp_footer',
	function () {
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
			$(document).on(
				'click',
				".jet-gallery-remove",
				function(){
					var fileName = $(this).data('file-name');
					// Trigger the click event on the document
					$(document).trigger('customClick', fileName);
					//$(this).closest('.anony-nice-uploader').remove();
				}
			);
			// Handle the custom click event on the document
			$(document).on('customClick', function(event, fileName) {
				// Find the dynamically injected div with the matching data-filename attribute and trigger a click on it
				$('div[data-filename="' + fileName + '"]').click();
			});
		});
		</script>
		<?php
	}
);
