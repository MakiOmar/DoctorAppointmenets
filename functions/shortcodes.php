<?php
/**
 * Shortcodes
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
add_shortcode(
	'snks_timetable_preview',
	function () {
		return snks_generate_preview();
	}
);

add_shortcode(
	'snks_object_title',
	function ( $atts ) {
		global $wp;
		$atts      = shortcode_atts(
			array(
				'font-size' => '20px',
			),
			$atts
		);
		$permalink = '';
		$title     = '';
		if ( isset( $wp->query_vars ) && isset( $wp->query_vars['doctor_id'] ) ) {
			$permalink = '#';
			$title     = '<a href="' . $permalink . '" style="display:block;text-align:center;font-size:' . $atts['font-size'] . '">حجز موعد</a>';
		} elseif ( is_singular() ) {
			global $post;
			$permalink = get_permalink( $post->ID );
			$title     = '<a href="' . $permalink . '" style="display:block;text-align:center;font-size:' . $atts['font-size'] . '">' . $post->post_title . '</a>';
		} elseif ( is_post_type_archive() ) {
			$post_type        = get_post_type();
			$permalink        = get_post_type_archive_link( $post_type );
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object ) {
				$post_type_label = $post_type_object->labels->name; // or use 'singular_name' for the singular label.
				$title           = '<a href="' . $permalink . '" style="display:block;text-align:center;font-size:' . $atts['font-size'] . '">' . $post_type_label . '</a>';
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$term      = get_queried_object();
			$permalink = get_term_link( $term );
			$title     = '<a href="' . $permalink . '" style="display:block;text-align:center;font-size:' . $atts['font-size'] . '">' . $term->name . '</a>';
		} elseif ( is_archive() ) {
			$permalink      = get_post_type_archive_link( get_post_type() );
			$queried_object = get_queried_object();
			if ( $queried_object && isset( $queried_object->label ) ) {
				$archive_label = $queried_object->label;
				$title         = '<a href="' . $permalink . '" style="display:block;text-align:center;font-size:' . $atts['font-size'] . '">' . $archive_label . '</a>';
			}
		}

		return $title;
	}
);

// Shortcode to display a button that copies the encrypted user ID.
add_shortcode(
	'snks_booking_url_button',
	function () {
		// Get the user ID.
		$user_id = get_current_user_id();

		// Encrypt the user ID.
		$url = '';

		ob_start();
		?>
		<div class="anony-flex flex-v-center flex-h-center" style="margin-top: 20px;">
		<input type="hidden" id="booking-url" value="<?php echo esc_url( $url ); ?>"/>
		<button onclick="copyToClipboard()">انسخ رابط الحجز الخاص بك</button>
		</div>
		<script>
		function copyToClipboard() {
			const bookingUrl = document.getElementById('booking-url');
			const el = document.createElement('textarea');
			el.value = bookingUrl.value;
			document.body.appendChild(el);
			el.select();
			document.execCommand('copy');
			document.body.removeChild(el);
			alert('تم النسخ');
		}
		</script>
		<?php
		return ob_get_clean();
	}
);

add_shortcode(
	'snks_bookings',
	function () {
		return snks_generate_bookings();
	}
);

add_shortcode(
	'snks_patient_sessions',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'tense' => 'all',
			),
			$atts
		);
		return snks_render_sessions_listing( $atts['tense'] );
	}
);

add_shortcode(
	'snks_go_back',
	function () {
		return snks_go_back();
	}
);
add_shortcode(
	'snks_appointment_form',
	function () {
		$html    = '';
		$user_id = snks_url_get_doctors_id();
		if ( ! $user_id ) {
			return 'بيانات الطبيب غير صحيحة';
		}
		$html .= snks_form_filter( $user_id );
		$html .= '<div id="consulting-forms-container"></div>';
		return $html;
	}
);

/**
 * Renders phone input
 *
 * @param  string $atts the shortcode attributes.
 * @return string
 */
function phone_input_cb( $atts ) {
	$atts       = shortcode_atts(
		array(
			'name'        => 'phone',
			'with-styles' => 'yes',
		),
		$atts,
		'phone_input'
	);
	$countries  = file_get_contents( 'https://jalsah.app/wp-content/uploads/2024/09/countires-codes-and-flags.json' );
	$countries  = json_decode( $countries, true );
	$key_values = array_column( $countries, 'name_ar' );
	array_multisort( $key_values, SORT_ASC, $countries );
	$user_country_code = snsk_ip_api_country();

	ob_start();
	?>
	<?php if ( 'yes' === $atts['with-styles'] ) { ?>
	<style>
		.anony-dial-codes img.emoji {
			position: relative;
			top: 3px;
		}
		input.anony_dial_phone{
			margin-<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
		}
		.anony-dial-codes {
			position: relative;
			display: flex;
			direction: ltr;
			text-align: left;
		}
		.anony-dial-codes-content {
			display: none;
			position: absolute;
			background-color: #f1f1f1;
			min-width: 160px;
			max-height: 200px;
			overflow-y: auto;
			box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
			z-index: 1;
			max-width: 250px;
				top: 55px;
		}
		.anony-dial-codes-content a {
			color: black;
			padding: 12px 16px;
			text-decoration: none;
			display: block;
		}
		.anony-dial-codes-content a:hover {
			background-color: #ddd;
		}
		.anony-filter-input {
			width: 100%;
			max-width: 220px;
			text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
			direction: <?php echo is_rtl() ? 'rtl' : 'ltr'; ?>;
			padding: 10px;
			box-sizing: border-box;
			margin-bottom: 5px;
		}
	</style>
	<?php } ?>
	<div class="anony-dial-codes">
		<div class="anony-flex flex-v-center anony-full-width">
			<button style="min-width:100px;height:47px;padding:0 10px" class="anony_dial_codes_selected_choice"></button>
			<input type="tel" class="anony_dial_phone" name="<?php echo esc_attr( $atts['name'] ); ?>" value="<?php echo esc_attr( str_replace( $user_country_code, '', apply_filters( str_replace( '-', '_', $atts['name'] . '_value' ), '' ) ) ); ?>"/>
		</div>
		<!-- Filter Input Box -->
		<div class="anony-dial-codes-content">
		<input type="text" class="anony-filter-input hacen_liner_print-outregular" placeholder="إبحث عن الدولة..." onkeyup="filterDiallingCodes()">
		<?php
		foreach ( $countries as $index => $country ) {
			$full_label = $country['flag'] . ' (<span style="direction="ltr"">' . $country['dial_code'] . '</span>) ' . $country['name_ar'];
			$label      = $country['flag'] . ' (' . $country['dial_code'] . ')';
			if ( $user_country_code === $country['country_code'] ) {
				$first_choice   = $label;
				$user_dial_code = $country['dial_code'];
			}
			echo '<span>';
			echo '<a class="anony-dialling-code" href="#" data-dial-code="' . esc_attr( $country['dial_code'] ) . '">' . wp_kses_post( $full_label ) . '</a>';
			echo '<a style="display:none" class="anony_selected_dial_code">' . wp_kses_post( $label ) . '</a>';
			echo '</span>';
		}
		?>
		</div>
		<input type="text" style="display:none" name="country_code" class="anony_dial_code" id="<?php echo esc_attr( $atts['name'] ); ?>_country_code" value="<?php echo isset( $user_dial_code ) ? esc_attr( $user_dial_code ) : ''; ?>"/>
		<div style="display:none" class="anony_dial_codes_first_choice"><?php echo isset( $first_choice ) ? wp_kses_post( $first_choice ) : ''; ?></div>
	</div>
	<script>
		function filterDiallingCodes() {
			const input = document.querySelector('.anony-filter-input');
			const filter = input.value.toLowerCase();
			const links = document.querySelectorAll('.anony-dialling-code');

			links.forEach(link => {
				if (link.textContent.toLowerCase().includes(filter)) {
					link.parentElement.style.display = '';
				} else {
					link.parentElement.style.display = 'none';
				}
			});

			// Show all links when the filter is empty
			if (!filter) {
				links.forEach(link => link.parentElement.style.display = '');
			}
		}
	</script>
	<?php
	return ob_get_clean();
}

add_shortcode( 'phone_input', 'phone_input_cb' );


add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery(document).ready(
				function($) {
					$(document).on(
						'click',
						'.anony_dial_codes_selected_choice',
						function(event){
							event.preventDefault();
							$(this).closest('.anony-dial-codes').find('.anony-dial-codes-content').toggle();
						}
					);
					$(document).on(
						'click',
						'.anony-dialling-code',
						function(event){
							event.preventDefault();
							var parent = $(this).closest('.anony-dial-codes');
							
							$('.anony_dial_codes_selected_choice', parent).html( $(this).next('.anony_selected_dial_code', parent).html() );
							$('.anony_dial_codes_first_choice', parent).html( $(this).next('.anony_selected_dial_code', parent).html() );
							$('.anony_dial_code', parent).val( $(this).data('dial-code' ) ).change();
							$(this).closest('.anony-dial-codes').find('.anony-dial-codes-content').toggle();
							
						}
					);
					$(".anony-dial-codes").each(
						function () {
							var thisDialCodes = $(this);
							$('.anony_dial_codes_selected_choice', thisDialCodes).html($('.anony_dial_codes_first_choice', thisDialCodes).html(  )  );
						}
					);
				}
			);
		</script>
		<?php
	}
);