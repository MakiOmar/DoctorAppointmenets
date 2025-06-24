<?php
/**
 * Template for displaying single organization posts.
 *
 * @package MyPlugin
 */

//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Load custom header from plugin.
$plugin_header_path = SNKS_DIR . 'header-organization.php';

if ( file_exists( $plugin_header_path ) ) {
	require $plugin_header_path;
} else {
	get_header();
}

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$_id             = get_the_ID();
		$main_color      = get_post_meta( $_id, 'main_color', true );
		$secondary_color = get_post_meta( $_id, 'secondary_color', true );
		$header_color    = get_post_meta( $_id, 'header_color', true );
		$footer_color    = get_post_meta( $_id, 'footer_background_color', true );

		$description_section_background              = get_post_meta( $_id, 'description_section_background', true );
		$description_section_text_color              = get_post_meta( $_id, 'description_section_text_color', true );
		$description_section_header_text_color       = get_post_meta( $_id, 'description_section_header_text_color', true );
		$description_section_header_background_color = get_post_meta( $_id, 'description_section_header_background_color', true );

		$contact_section_background              = get_post_meta( $_id, 'contact_section_background', true );
		$contact_section_text_color              = get_post_meta( $_id, 'contact_section_text_color', true );
		$contact_section_header_text_color       = get_post_meta( $_id, 'contact_section_header_text_color', true );
		$contact_section_header_background_color = get_post_meta( $_id, 'contact_section_header_background_color', true );

		$specialties_section_background                   = get_post_meta( $_id, 'specialties_section_background', true );
		$specialties_section_text_color                   = get_post_meta( $_id, 'specialties_section_text_color', true );
		$specialties_section_header_text_color            = get_post_meta( $_id, 'specialties_section_header_text_color', true );
		$specialties_section_header_text_background_color = get_post_meta( $_id, 'specialties_section_header_text_background_color', true );

		$links_section_background                   = get_post_meta( $_id, 'links_section_background', true );
		$links_section_text_color                   = get_post_meta( $_id, 'links_section_text_color', true );
		$links_section_header_text_color            = get_post_meta( $_id, 'links_section_header_text_color', true );
		$links_section_header_text_background_color = get_post_meta( $_id, 'links_section_header_text_background_color', true );


		$slogan                      = get_post_meta( $_id, 'slogan', true );
		$custom_links                = get_post_meta( $_id, 'custom_links', true );
		$enable_description_section  = get_post_meta( $_id, 'enable_description_section', true );
		$enable_contact_section      = get_post_meta( $_id, 'enable_contact_section', true );
		$enable_specialties_section  = get_post_meta( $_id, 'enable_specialties_section', true );
		$enable_custom_links_section = get_post_meta( $_id, 'enable_custom_links_section', true );
		$content                     = get_the_content();
		snks_org_styles( $main_color, $secondary_color, $header_color, $footer_color );
		?>
		<div id="org-container" class="org-home">
			<!-- Top Logo Section -->
			<?php snks_print_org_header( $_id ); ?>
			<?php if ( 'true' === $enable_description_section && ( ! empty( $slogan ) || ! empty( $content ) ) ) { ?>
				<!-- Description -->
				<div class="org-slogan-section" <?php echo snks_get_section_style( $description_section_background, $description_section_text_color, $main_color ); ?>>
					<?php if ( ! empty( $slogan ) ) { ?>
						<h2 <?php echo snks_get_header_style( $description_section_header_background_color, $description_section_header_text_color, $main_color ); ?>>
							<?php echo esc_html( $slogan ); ?>
						</h2>
					<?php } ?>
					<?php
					if ( ! empty( $content ) ) {
						the_content(); }
					?>
				</div>
				<?php
			}
			if ( 'true' === $enable_custom_links_section ) {
				?>
				<!-- Custom links -->
				<div class="org-green-section" <?php echo snks_get_section_style( $links_section_background, $links_section_text_color, $main_color ); ?>>
					<h3 <?php echo snks_get_header_style( $links_section_header_text_background_color, $links_section_header_text_color, $main_color ); ?>>
						<?php esc_html_e( 'احجز جلسة', 'my-plugin' ); ?>
					</h3>


					<?php

					if ( ! empty( $custom_links ) && is_array( $custom_links ) ) :
						echo '<div class="green-links-wrapper" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; justify-items: center; margin-top: 30px;">';

						foreach ( $custom_links as $item ) {
							$icon_url = ! empty( $item['icon'] ) ? $item['icon'] : '#';
							$_title   = ! empty( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
							$url      = ! empty( $item['url'] ) ? $item['url'] : '#';

							if ( $icon_url && $_title && $url ) {
								?>
								<a href="<?php echo esc_url( $url ); ?>" class="green-link-card" target="_self">
									<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $_title ); ?>" style="max-width: 100%; margin-bottom: 10px;">
									<span style="font-weight: bold;"><?php echo esc_html( $_title ); ?></span>
								</a>
								<?php
							}
						}

						echo '</div>';
					endif;
					?>
				</div>

				<?php
			}
			if ( 'true' === $enable_specialties_section ) {
				?>
				<!-- specialties section -->
			<div <?php echo snks_get_section_style( $specialties_section_background, $specialties_section_text_color, $main_color ); ?>>
				<?php
				$box_color  = $main_color;
				$text_color = '#ffffff';
				echo do_shortcode( "[specialization_grid box_color={$box_color} text_color={$text_color} id={$_id}]" );
				?>
			</div>
				<?php
			}
			?>
			<?php
			if ( 'true' === $enable_contact_section ) {
				?>
			<!-- Contact Section -->
			<div class="org-contact-section" <?php echo snks_get_section_style( $contact_section_background, $contact_section_text_color, $main_color ); ?>>
				<h3 <?php echo snks_get_header_style( $contact_section_header_background_color, $contact_section_header_text_color, $main_color ); ?>>
					<?php esc_html_e( 'تواصل معنا', 'my-plugin' ); ?>
				</h3>

				<div class="org-contact-icons">
					<div class="contacts">
						<?php
						$contact_keys = array(
							'phone'      => array(
								'prefix' => 'tel:',
								'icon'   => '/wp-content/uploads/2025/05/phone.png',
							),
							'whatsapp'   => array(
								'prefix' => 'https://wa.me/',
								'icon'   => '/wp-content/uploads/2025/05/whatsapp.png',
							),
							'email'      => array(
								'prefix' => 'mailto:',
								'icon'   => '/wp-content/uploads/2025/05/mails.png',
							),
							'address'    => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/address.png',
							),
							'google_map' => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/google_map.png',
							),
						);

						foreach ( $contact_keys as $key => $data ) {
							$value = get_post_meta( get_the_ID(), $key, true );
							if ( $value && '' !== $value ) {
								$icon_url = $data['icon'];
								if ( 'address' === $key ) {
									// Show swal on click for address
									echo '<a href="#" onclick="showAddressSwal(\'' . esc_js( $value ) . '\'); return false;"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $key ) . '"></a>';
								} else {
									$url = $data['prefix'] . $value;
									echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $key ) . '"></a>';
								}
							}
						}

						?>
					</div>
					<div class="socials" style="background-color: #fff;border-radius: 10px;padding: 8px;">
						<?php
						$socials_keys = array(
							'facebook'  => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/facebook.png',
							),
							'x'         => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/x.png',
							),
							'youtube'   => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/youtube.png',
							),
							'instagram' => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/instagram.png',
							),
							'tiktok'    => array(
								'prefix' => '',
								'icon'   => '/wp-content/uploads/2025/05/tiktok.png',
							),
						);

						foreach ( $socials_keys as $key => $data ) {
							$value = get_post_meta( get_the_ID(), $key, true );
							if ( $value && '' !== $value ) {
								$url      = $data['prefix'] . $value;
								$icon_url = $data['icon'];
								echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $key ) . '"></a>';
							}
						}
						?>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="org-footer">
				<?php echo do_shortcode( '[elementor-template id="3106"]' ); ?>
			</div>
		</div>
		<?php
	endwhile;
endif;
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
	function showAddressSwal(address) {
		Swal.fire({
			icon: 'info',
			title: 'العنوان',
			text: address,
			confirmButtonText: 'حسنًا'
		});
	}
</script>
<?php
get_footer();
