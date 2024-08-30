<?php
/**
 * Meeting scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
        return;
		?>
		<button id="pip-button">Enter Picture-in-Picture</button>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const video = document.getElementById('largeVideo');
				const pipButton = document.getElementById('pip-button');

				pipButton.addEventListener('click', async () => {
					if (document.pictureInPictureElement) {
						document.exitPictureInPicture();
					} else {
						try {
							await video.requestPictureInPicture();
						} catch (error) {
							console.error('Picture-in-Picture mode is not supported');
						}
					}
				});

				video.addEventListener('leavepictureinpicture', () => {
					console.log('Exited Picture-in-Picture mode');
				});
			});
		</script>
		<?php
	}
);
