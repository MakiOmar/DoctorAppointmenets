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
		if ( current_user_can( 'manage_options' ) ) {
			//$html .= '<p>Please note that we have used EG as default country temporarily</p>';
		}
		return $html;
	}
);