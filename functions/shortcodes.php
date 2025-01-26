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
		$html  = '<div id="preview-timetables">';
		$html .= snks_generate_preview();
		$html .= '</div>';
		return $html;
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
		$user_id = snks_get_settings_doctor_id();

		// Encrypt the user ID.
		$url = snks_encrypted_doctor_url( $user_id );

		ob_start();
		?>
		<div class="anony-flex flex-v-center flex-h-center" style="margin-top: 20px;">
		<button data-url="<?php echo esc_url( $url ); ?>" id="copyToClipboard">نس رابط الحجز</button>
		</div>
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
	function () {
		$past               = snks_render_sessions_listing( 'past' );
		$current_timetables = snks_render_sessions_listing( 'future' );
		return snks_generate_the_bookings( $past, $current_timetables );
	}
);

add_shortcode(
	'snks_go_back',
	function () {
		return snks_go_back();
	}
);
add_shortcode(
	'snks_bookings_popup',
	function () {
		if ( snks_is_patient() ) {
			return '';
		}
		return "<a class='anony-booking-popup' href='#'><i class='fa fa-calendar'></i></a>";
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
	$atts     = shortcode_atts(
		array(
			'name'         => 'phone',
			'with-styles'  => 'yes',
			'target'       => '',
			'hide_target'  => 'yes',
			'height'       => '37px',
			'label_color'  => '#000',
			'country_code' => 'yes',
		),
		$atts,
		'phone_input'
	);
	$response = wp_remote_get( 'https://jalsah.app/wp-content/uploads/2024/09/countires-codes-and-flags.json' );

	if ( is_wp_error( $response ) ) {
		// Handle the error appropriately.
		return;
	}

	$countries = wp_remote_retrieve_body( $response );
	$countries = json_decode( $countries, true );

	if ( ! is_array( $countries ) ) {
		// Handle the error if the JSON decoding fails.
		return;
	}

	$key_values = array_column( $countries, 'name_ar' );
	array_multisort( $key_values, SORT_ASC, $countries );

	$user_country_code = snsk_ip_api_country( false );
	$unique_id         = wp_unique_id( 'anony_' );
	$current_phone     = apply_filters( 'anony_phone_input_' . str_replace( '-', '_', $atts['name'] . '_value' ), '' );
	ob_start();
	?>
	<p style="position:fixed;z-index:-1">
	<style>
		<?php if ( '' !== $atts['target'] && 'yes' === $atts['hide_target'] ) { ?>
			input[name=<?php echo esc_attr( $atts['target'] ); ?>]{
				display: none;
			}
		<?php } ?>
		input[name="<?php echo esc_attr( $atts['name'] ); ?>"]{
			height: <?php echo esc_html( $atts['height'] ); ?>!important;
		}
		#phone_input_main_wrapper_<?php echo esc_attr( $atts['name'] ); ?> .anony-dial-codes-phone-label{
			color: <?php echo esc_attr( $atts['label_color'] ); ?>;
		}
	</style>
	<?php if ( 'yes' === $atts['with-styles'] ) { ?>
		<style>
			.anony-dial-codes img.emoji {
				position: relative;
				top: 3px;
			}
			input.anony_dial_phone{
				margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
				direction: ltr;
				text-align: left;
			}
			.anony-dial-codes {
				position: relative;
				display: flex;
				flex-grow: 1;
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
			button.anony_dial_codes_selected_choice{
				min-width: 80px;
				height: 47px;
				padding: 0 10px;
				color: #000;
				background-color: #ddd;
				margin-right: 3px;
			}
			.anony-dial-codes-phone-label{
				text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;;
				font-size: 20px;
				font-weight: bold;
			}
		</style>
	<?php } ?>
	</p>
	<div id="phone_input_main_wrapper_<?php echo esc_attr( $atts['name'] ); ?>" class="phone_input_main_wrapper">
		<div id="<?php echo esc_attr( $unique_id ); ?>" class="anony-dial-codes">
			<div class="anony-flex flex-v-center anony-full-width">
				<label class="anony-dial-codes-phone-label jet-form-builder-col__start">رقم الموبايل *</label>
				<div class="anony-flex flex-v-center anony-full-width">
				<input type="tel" pattern="[0-9]+" inputmode="numeric" class="anony_dial_phone" name="<?php echo esc_attr( $atts['name'] ); ?>" value="<?php echo esc_attr( str_replace( $user_country_code, '', $current_phone ) ); ?>"/>
				<?php if ( 'yes' === $atts['country_code'] ) { ?>
					<button class="anony_dial_codes_selected_choice"></button>
				<?php } ?>
				</div>
			</div>
			<?php if ( 'yes' === $atts['country_code'] ) { ?>
				<!-- Filter Input Box -->
				<div class="anony-dial-codes-content">
				<input type="text" class="anony-filter-input" placeholder="إبحث عن الدولة...">
				<?php
				foreach ( $countries as $index => $country ) {
					$full_label = $country['flag'] . ' (<span style="direction="ltr"">' . $country['dial_code'] . '</span>) ' . $country['name_ar'];
					$label      = $country['flag'] . ' (' . $country['dial_code'] . ')';
					if ( $user_country_code === $country['country_code'] ) {
						$first_choice   = $label;
						$user_dial_code = $country['dial_code'];
					} elseif ( $index < 1 ) {
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
			<?php } ?>
		</div>
	</div>
	<?php
	add_action(
		'wp_footer',
		function () use( $atts, $unique_id ) {
			?>
			<?php if ( ! empty( $atts['target'] ) ) { ?>
				<script>
					jQuery(document).ready(function($) {
						<?php do_action( 'phone_input_' . $atts['name'] . '_scripts', $atts ); ?>
						<?php do_action( 'phone_input_scripts', $atts ); ?>

						var parent = $('#<?php echo esc_attr( $unique_id ); ?>');
						var phoneInput = $('input[name=<?php echo esc_attr( $atts['name'] ); ?>]', parent);
						var countryCodeInput = $('input[name=country_code]', parent); // Get country code input, may be undefined
						var target = '<?php echo esc_html( $atts['target'] ); ?>';
						// Function to remove any non-numeric characters
						function allowOnlyNumbers(input) {
							var sanitizedValue = input.val().replace(/[^0-9]/g, ''); // Remove non-numeric characters
							input.val(sanitizedValue); // Set the sanitized value back
						}

						// Update billing phone function
						function updateBillingPhone(billingPhoneInput) {
							var phone = phoneInput.val().trim();
							var countryCode = '';
							if (countryCodeInput.length > 0) {
								countryCode = countryCodeInput.val().trim();
							}
							billingPhoneInput.val(countryCode + phone).change();
						}

						// Set initial value on document ready if the target input is empty
						if ($('input[name=' + target + ']').val() === '') {
							updateBillingPhone($('input[name=' + target + ']'));
						}

						// Listen for changes in phone input
						phoneInput.on('input', function() {
							allowOnlyNumbers($(this));
							updateBillingPhone($('input[name=' + target + ']'));
						});

						// Apply the function when pasting
						phoneInput.on('paste', function(e) {
							setTimeout(function() {
								allowOnlyNumbers(phoneInput);
							}, 0); // Wait for the paste action to complete
						});
						// Check if countryCodeInput exists before adding event listener
						if (countryCodeInput.length > 0) {
							countryCodeInput.on('input', function() {
								updateBillingPhone($('input[name=' + target + ']'));
							});
						}
					});

				</script>
			<?php } ?>
			<script>
				jQuery( document ).ready( function( $ ) {
					var parent = $( '#<?php echo esc_attr( $unique_id ); ?>' );
					$('.anony-filter-input', parent).on(
						'keyup',
						function () {
							const filter = $(this).val().toLowerCase();
							const links = $('.anony-dialling-code', parent);

							links.each(function () {
								const link = $(this);
								if (link.text().toLowerCase().includes(filter)) {
									link.parent().show();
								} else {
									link.parent().hide();
								}
							});

							// Show all links when the filter is empty
							if (!filter) {
								links.parent().show();
							}
						}
					);
				} );
				
			</script>
			<?php
		}
	);
	return ob_get_clean();
}

add_shortcode( 'phone_input', 'phone_input_cb' );


/**
 * Withdrawal settings
 *
 * @return string
 */
function custom_withdrawal_form_shortcode() {
	$current_datetime = current_datetime();
	// Get the current user's ID.
	$user_id = get_current_user_id();
	$banks   = get_bank_list();
	// Retrieve saved withdrawal settings from user meta.
	$withdrawal_settings = get_user_meta( $user_id, 'withdrawal_settings', true );

	// Set default values for withdrawal settings.
	$withdrawal_option = isset( $withdrawal_settings['withdrawal_option'] ) ? $withdrawal_settings['withdrawal_option'] : '';
	$withdrawal_method = isset( $withdrawal_settings['withdrawal_method'] ) ? $withdrawal_settings['withdrawal_method'] : '';

	// Define the withdrawal options in an array.
	$withdrawal_options = array(
		array(
			'id'          => 'manual_withdrawal',
			'value'       => 'manual_withdrawal',
			'label'       => 'النظام اليدوي',
			'description' => 'سيتم سحب رصيدك فقط عند الطلب.',
		),
		array(
			'id'          => 'daily_withdrawal',
			'value'       => 'daily_withdrawal',
			'label'       => 'النظام اليومي',
			'description' => 'سيتم سحب رصيدك تلقائيا بشكل يومي.',
		),
		array(
			'id'          => 'weekly_withdrawal',
			'value'       => 'weekly_withdrawal',
			'label'       => 'النظام الأسبوعي',
			'description' => 'سيتم سحب رصيدك تلقائيا كل يوم أربعاء من كل أسبوع.',
		),
		array(
			'id'          => 'monthly_withdrawal',
			'value'       => 'monthly_withdrawal',
			'label'       => 'النظام الشهري',
			'description' => 'سيتم سحب رصيدك تلقائيا في أول يوم عمل من كل شهر.',
		),
	);
	// Define the withdrawal options with associated text fields.
	$withdrawal_details = array(
		array(
			'id'     => 'wallet',
			'value'  => 'wallet',
			'label'  => 'محفظة إلكترونية',
			'fields' => array(
				array(
					'label'      => 'رقم المحفظة',
					'name'       => 'wallet_number',
					'value'      => isset( $withdrawal_settings['wallet_number'] ) ? $withdrawal_settings['wallet_number'] : '',
					'attributes' => array(
						'pattern'     => '\d{11}',
						'maxlength'   => '11',
						'placeholder' => 'أدخل 11 رقماً فقط',
						'title'       => 'يرجى إدخال 11 رقماً فقط بالإنجليزية.',
					),
				),
				array(
					'label'      => 'إسم صاحب المحفظة',
					'name'       => 'wallet_owner_name',
					'value'      => isset( $withdrawal_settings['wallet_owner_name'] ) ? $withdrawal_settings['wallet_owner_name'] : '',
					'attributes' => array(
						'oninput'     => 'this.value=this.value.replace(/[^A-Za-z\s]/g,"");',
						'placeholder' => 'أدخل الأحرف الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأحرف الإنجليزية فقط.',
					),
				),
			),
		),
		array(
			'id'     => 'bank_account',
			'value'  => 'bank_account',
			'label'  => 'حساب بنكي',
			'fields' => array(
				array(
					'label'      => 'اسم صاحب الحساب',
					'name'       => 'account_holder_name',
					'value'      => isset( $withdrawal_settings['account_holder_name'] ) ? $withdrawal_settings['account_holder_name'] : '',
					'attributes' => array(
						'oninput'     => 'this.value=this.value.replace(/[^A-Za-z\s]/g,"");',
						'placeholder' => 'أدخل الأحرف الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأحرف الإنجليزية فقط.',
					),
				),
				array(
					'label'      => 'رقم الحساب',
					'name'       => 'account_number',
					'value'      => isset( $withdrawal_settings['account_number'] ) ? $withdrawal_settings['account_number'] : '',
					'attributes' => array(
						'pattern'     => '\d+',
						'placeholder' => 'أدخل الأرقام الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأرقام الإنجليزية فقط.',
					),
				),
				array(
					'label'      => 'البنك',
					'name'       => 'bank_name',
					'value'      => isset( $withdrawal_settings['bank_name'] ) ? $withdrawal_settings['bank_name'] : '',
					'attributes' => array(
						'oninput'     => 'this.value=this.value.replace(/[^A-Za-z\s]/g,"");',
						'placeholder' => 'أدخل الأحرف الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأحرف الإنجليزية فقط.',
					),
					'options'    => $banks,
				),
			),
		),
		array(
			'id'     => 'meza_card',
			'value'  => 'meza_card',
			'label'  => 'بطاقة ميزة',
			'fields' => array(
				array(
					'label'      => 'الإسم الثلاثي لصاحب البطاقة',
					'name'       => 'card_holder_first_name',
					'value'      => isset( $withdrawal_settings['card_holder_first_name'] ) ? $withdrawal_settings['card_holder_first_name'] : '',
					'attributes' => array(
						'oninput'     => 'this.value=this.value.replace(/[^A-Za-z\s]/g,"");',
						'placeholder' => 'أدخل الأحرف الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأحرف الإنجليزية فقط.',
					),
				),
				array(
					'label'      => 'البنك',
					'name'       => 'meza_bank_code',
					'value'      => isset( $withdrawal_settings['meza_bank_code'] ) ? $withdrawal_settings['meza_bank_code'] : '',
					'options'    => $banks,
					'attributes' => array(
						'oninput'     => 'this.value=this.value.replace(/[^A-Za-z\s]/g,"");',
						'placeholder' => 'أدخل الأحرف الإنجليزية فقط',
						'title'       => 'يرجى إدخال الأحرف الإنجليزية فقط.',
					),
				),

				array(
					'label'      => 'رقم البطاقة',
					'name'       => 'meza_card_number',
					'value'      => isset( $withdrawal_settings['meza_card_number'] ) ? $withdrawal_settings['meza_card_number'] : '',
					'attributes' => array(
						'pattern'     => '\d{16}',
						'maxlength'   => '16',
						'placeholder' => 'أدخل 16 رقماً فقط',
						'title'       => 'يرجى إدخال 16 رقماً بالإنجليزية فقط.',
					),
				),
			),
		),
	);
	ob_start();
	?>
	<form id="withdrawal-settings-form" action="" method="post" class="anony-padding-20 snks-confirm">
		<?php
			$current_hour = (int) $current_datetime->format( 'G' ); // 'G' returns the hour in 24-hour format without leading zeros.

			// Check if the current time is between 12 AM (0) and 9 AM (9).
		if ( $current_hour >= 0 && $current_hour < 9 ) {
			?>
				<div id="withdrawal-form-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;"></div>
			<?php
		}
		?>
		<?php echo str_replace( '{available_amount}', get_available_balance( $user_id ), do_shortcode( '[elementor-template id="3725"]' ) ); //phpcs:disable ?>
		<?php echo str_replace( '{withdrawal_amount}', snks_get_latest_transaction_amount( $user_id ), do_shortcode( '[elementor-template id="3733"]' ) ); ?>
		<?php wp_nonce_field( 'save_withdrawal_settings', 'withdrawal_settings_nonce' ); ?>
		<!-- First Section -->
		<div class="gray-bg anony-padding-20 withdrawal-options withdrawal-section">
			<h1 class="white-bg anony-padding-20 withdrawal-section-title">نظام السحب</h1>
			<?php foreach ( $withdrawal_options as $index => $option ) : ?>
				<div class="withdrawal-radio">
					<input type="radio" id="<?php echo esc_attr( $option['id'] ); ?>" name="withdrawal_option" value="<?php echo esc_attr( $option['value'] ); ?>" <?php checked( $withdrawal_option, $option['value'] ); ?> <?php echo empty( $withdrawal_option ) && 1 > $index ? 'checked' : ''; ?>>
					<label for="<?php echo esc_attr( $option['id'] ); ?>">
						<span class="anony-custom-radio<?php echo $withdrawal_option === $option['value'] ? ' checked' : ''; ?> <?php echo empty( $withdrawal_option ) && 1 > $index ? 'checked' : ''; ?>"></span>
							<?php echo esc_html( $option['label'] ); ?>
					</label>
					<p><?php echo esc_html( $option['description'] ); ?></p>
				</div>
				<?php if ( 1 > $index ) {?>
					<!-- Submit Button -->
					<div class="manual-withdrawal-button" style="display:<?php echo ( 'manual_withdrawal' === $withdrawal_option || empty( $withdrawal_option ) ) ? 'block' : 'none'; ?>"
						data-nonce="<?php echo wp_create_nonce( 'process_withdrawal_nonce' ); ?>">
						<button class="anony-default-padding withdrawal-button">إضغط هنا لطلب السحب</button>
					</div>

				<?php } ?>
			<?php endforeach; ?>

			<div class="financials-white-section anony-default-padding white-bg">
				<p style="color: #939393; text-align: justify;font-size: 23px;">
					في حالة كان يوم السحب يوم عطلة رسمي، يتم السحب في أول يوم عمل تالي.
				</p>
			</div>
		</div>

		<!-- Second Section -->
		<div class="gray-bg anony-padding-20 withdrawal-options withdrawal-section">
			<h1 class="white-bg anony-padding-20 withdrawal-section-title">طريقة السحب</h1>
			<?php foreach ( $withdrawal_details as $index => $option ) :
				?>
				<!-- We will need this when other withdrawal methods are enabeled-->
				
				<div class="withdrawal-radio">
					<input type="radio" id="<?php echo esc_attr( $option['id'] ); ?>" name="withdrawal_method" value="<?php echo esc_attr( $option['value'] ); ?>" <?php checked( $withdrawal_method, $option['value'] , true ); ?>>
					<label for="<?php echo esc_attr( $option['id'] ); ?>">
						<!-- Remove the checked class when other withdrawal methods are enabeled-->
						<span class="anony-custom-radio<?php echo $withdrawal_method === $option['value'] ? ' checked' : ''; ?>"></span>
						<?php echo esc_html( $option['label'] ); ?>
					</label>
					<!-- Hidden Fields Section -->
					<div class="withdrawal-accounts-fields white-bg anony-padding-10" style="display: none;border-radius:10px">
					<?php foreach ( $option['fields'] as $field ) : ?>
						<div class="field-group">
							<label for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
							</label>
							<?php if ( ! empty( $field['options'] ) ) { ?>
								<select name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach( $field['options'] as $k => $v ) { ?>
									<option value="<?php echo esc_attr( $k );?>" <?php selected( $k, $field['value'] ); ?>><?php echo esc_html( $v ); ?></option>
								<?php } ?>
								</select>
							<?php } else { ?>
								<input 
								type="text" 
								id="<?php echo esc_attr( $field['name'] ); ?>" 
								name="<?php echo esc_attr( $field['name'] ); ?>" 
								value="<?php echo esc_attr( $field['value'] ); ?>"
								<?php foreach ( $field['attributes'] as $attr => $val ) : ?>
									<?php echo esc_attr( $attr ) . '="' . esc_attr( $val ) . '" '; ?>
								<?php endforeach; ?>
								>
							<?php } ?>
						</div>
					<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="financials-white-section anony-default-padding white-bg">
				<p style="color: #939393; text-align: justify;font-size: 20px;">
				يرجى العلم أنه لا يمكنك تغيير بيانات السحب الخاصة بك في الفترة من الساعة 12 منتصف الليل وحتي الساعة 9 صباحا، وبحلول منتصف الليل يتم تلقائيا تسجيل بيانات حسابك ( نظام السحب, الرصيد وطريقة السحب ) الموجودة بحسابك لاستخدامها في عملية السحب التالية الخاصة بك.
				</p>
			</div>
		</div>

		<!-- OTP Section -->
		<div id="otp-section" style="display:none;">
			<p>تم إرسال كود التحقق إلى البريد الإلكتروني/الموبايل الخاص بك. يرجى إدخال الكود هنا:</p>
			<input type="text" id="otp_input" name="otp_input" placeholder="ادخل الكود هنا" required>
		</div>

		<!-- Button to send OTP -->
		<p class="anony-center-text">للحفظ يجب إرسال كود التحقق</p>
		<button type="button" id="send-otp" class="anony-default-padding withdrawal-button">إرسال</button>
		<!-- Submit Button (Initially disabled) -->
		<button type="button" id="submit-withdrawal-form" class="anony-default-padding withdrawal-button" style="display:none;">حفظ</button>
	</form>
	<?php echo do_shortcode( '[elementor-template id="3737"]' );?>
	<?php
	return ob_get_clean();
}//phpcs:enable
add_shortcode( 'custom_withdrawal_form', 'custom_withdrawal_form_shortcode' );
add_action(
	'send_headers',
	function () {
		if ( is_page( 'booking-details' ) ) {
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
		}
	}
);


/**
 * Order details
 *
 * @return string
 */
function consulting_session_table_shortcode() {
	// Retrieve the form data from the session.
	// phpcs:disable
	$form_data = get_transient( snks_form_data_transient_key() );
	// phpcs:enable
	// Ensure that necessary data is available.
	if ( empty( $form_data ) ) {
		return;
	}

	return snks_booking_details( $form_data );
}

// Register the shortcode.
add_shortcode( 'consulting_session_table', 'consulting_session_table_shortcode' );
/**
 * Order details
 *
 * @param mixed $form_data Booking form submitted data.
 * @return string
 */
function consulting_session_pricing_table_shortcode( $form_data = false ) {
	if ( ! $form_data ) {
		// phpcs:disable
		$form_data = get_transient( snks_form_data_transient_key() );
	}
	
	// phpcs:enable
	// Ensure that necessary data is available.
	if ( ! $form_data || empty( $form_data ) ) {
		return;
	}
	ob_start();
	?>
	<style>
		#price-break {
			background-color: #0a5468;
			width: 300px;
			margin: auto;
			padding: 20px;
			border: 2px dashed white;
			border-radius: 10px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
		}

		#price-break .discount-section {
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-radius: 5px;
			margin-bottom: 15px;
		}

		#price-break .discount-section button {
			background-color: #024059;
			border: 2px solid #024059;
			color: white;
			padding: 5px 10px;
			border-radius: 5px;
			cursor: pointer;
			height: 45px;
		}

		#price-break .discount-section input {
			padding: 5px;
			border: none;
			border-radius: 5px;
			text-align: center;
		}

		#price-break .amount-section:first-child {
			border-top: 1px solid white;
			padding-top: 10px;
		}
		#price-break .amount-section {
			display: flex;
			justify-content: space-between;
			margin-bottom: 10px;
		}

		#price-break .amount-section p {
			margin: 0;
		}

		#price-break .price {
			background-color: white;
			color: black;
			padding: 5px;
			border-radius: 15px;
			text-align: center;
			width: 100px; /* Set fixed width */
			box-sizing: border-box;
		}

		#price-break .total {
			display: flex;
			justify-content: space-between;
			font-weight: bold;
			padding-top: 10px;
			border-top: 1px solid white;
		}
		#price-break .amount-section > p:first-child, #price-break .total > p{
			color:#fff
		}
		#price-break .total .price{
			background-color: transparent!important;
			color: white!important
		}
	</style>
	
	<div id="price-break" class="container">
		<?php if ( ! is_page( 'booking-details' ) ) { ?>
		<div class="discount-section">
			<input type="text" placeholder="أدخل كود الخصم" style="background-color: #fff;margin-left: 3px !important;">
			<button>تفعيل</button>
		</div>
		<?php } ?>
		<div>
			<div class="amount-section">
				<p>رسوم المعالج</p>
				<p class="price"><?php echo esc_html( $form_data['_main_price'] ); ?> ج.م</p>
			</div>

			<div class="amount-section">
				<p>رسوم موقع جلسة</p>
				<p class="price"><?php echo esc_html( $form_data['_jalsah_commistion'] ); ?> ج.م</p>
			</div>

			<div class="amount-section">
				<p>رسوم  Paymob</p>
				<p class="price"><?php echo esc_html( $form_data['_paymob'] ); ?> ج.م</p>
			</div>

			<!--<div class="amount-section">
				<p>ضريبة القيمة المضافة</p>
				<p class="price">
					<?php
				//phpcs:disable Squiz.PHP.CommentedOutCode.Found
					/*echo esc_html( $form_data['_vat'] );*/
					?>
				ج.م</p>
			</div>-->
		</div>
		<div class="total">
			<p>الإجمالي</p>
			<p class="price"><?php echo esc_html( $form_data['_total_price'] ); ?> ج.م</p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

// Register the shortcode.
add_shortcode( 'consulting_session_pricing_table', 'consulting_session_pricing_table_shortcode' );

/**
 * Shortcode to display doctor validation messages in Arabic with a red cross.
 *
 * Retrieves the transient for the current user, displays the messages with a red cross,
 * and then deletes the transient.
 *
 * @return string The messages to display, or an empty string if no messages exist.
 */
add_shortcode(
	'snks_doctor_message',
	function () {
		$user_id = snks_get_settings_doctor_id();

		// Get the transient message array for the current user.
		$messages = get_transient( 'snks_doctor_message_' . $user_id );

		// If there are messages, display them.
		if ( ! empty( $messages ) ) {
			// Start building the output.
			$output = '<div class="snks-doctor-message">';
			foreach ( $messages as $message ) {
				// Add a red cross before each message.
				$output .= '<p>&#10060; ' . esc_html( $message ) . '</p>';
			}
			$output .= '</div>';

			// Delete the transient after rendering.
			delete_transient( 'snks_doctor_message_' . $user_id );

			// Return the output.
			return $output;
		}

		// Return an empty string if no messages exist.
		return '';
	}
);

/**
 * Shortcode to display "Delete My Account" button with AJAX verification.
 *
 * @return string
 */
function delete_account_shortcode() {
	if ( ! is_user_logged_in() ) {
		return 'يجب أن تكون مسجل الدخول لحذف حسابك.';
	}
	ob_start();
	?>
	<div id="delete-account-section">
		<button id="send-verification-code" type="button">حذف حسابي</button>
		<div id="verification-code-section" style="display: none;">
			<input type="text" id="verification-code" placeholder="أدخل رمز التحقق" required>
			<button id="verify-and-delete" type="button">تحقق واحذف الحساب</button>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'delete_account', 'delete_account_shortcode' );


/**
 * Shortcode to display current user's transactions with colored arrows in Arabic.
 *
 * @return string HTML output of the transaction table.
 */

add_shortcode(
	'user_transactions',
	function () {
		// Check if the user is logged in.
		if ( ! is_user_logged_in() ) {
				return '<p>يجب تسجيل الدخول لعرض المعاملات.</p>';
		}

		// Get the current user ID.
		$user_id = get_current_user_id();

		global $wpdb;
		$transactions_table = $wpdb->prefix . 'snks_booking_transactions';
		$timetable_table    = $wpdb->prefix . 'snks_provider_timetable';

		//phpcs:disable
		// Query to fetch transactions for the current user with timetable date_time.
		$transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, s.*
				FROM $transactions_table t
				LEFT JOIN $timetable_table s ON t.timetable_id = s.id
				WHERE t.user_id = %d
				ORDER BY t.transaction_time DESC",
				$user_id
			)
		);
		//phpcs:enable
		// Check if there are any transactions.
		if ( empty( $transactions ) ) {
			return '<p>لم يتم العثور على معاملات.</p>';
		}

		// Start output buffering.
		ob_start();

		// Display the transactions table.
		echo '<table class="user-transactions" style="text-align: right; direction: rtl;">';
		echo '<tr><th>النوع</th><th>المبلغ</th><th>تاريخ المعاملة</th><th>تفاصيل الجلسة</th></tr>';
		foreach ( $transactions as $transaction ) {
			// Determine the arrow color based on the transaction type.
			$arrow_color           = ( 'add' === $transaction->transaction_type ) ? 'green' : 'red';
			$arrow_icon            = ( 'add' === $transaction->transaction_type ) ? '↑' : '↓';
			$transaction_type_text = ( 'add' === $transaction->transaction_type ) ? 'إضافة' : 'سحب';

			if ( $transaction->date_time ) {
				$clinic = snks_get_clinic( $transaction->clinic );
				if ( 'offline' === $transaction->attendance_type ) {
					if ( $clinic ) {
						$attendance = $clinic['clinic_title'];
					} else {
						$attendance = 'عيادة';
					}
				} else {
					$attendance = 'أونلاين';
				}
				$user_details = snks_user_details( $transaction->client_id );
				$first_name   = ! empty( $user_details['billing_first_name'] ) ? $user_details['billing_first_name'] : '';
				$last_name    = ! empty( $user_details['billing_last_name'] ) ? $user_details['billing_last_name'] : '';
				$details      = '<button class="details-button" data-date-time="' . esc_attr( snks_localized_datetime( $transaction->date_time ) ) . '" data-attendance-type="' . esc_attr( $attendance ) . '" data-client-name="' . esc_attr( $first_name . ' ' . $last_name ) . '">عرض التفاصيل</button>';
			} else {
				$details = 'غير متاح';
			}

			// Display transaction data with timetable date_time if available.
			echo '<tr id="' . esc_attr( $transaction->id ) . '" style="font-size:13px !important">';
			echo '<td style="vertical-align: middle;"><span style="font-size:25px;color:' . esc_attr( $arrow_color ) . ';">' . esc_html( $arrow_icon ) . '</span> ' . esc_html( $transaction_type_text ) . '</td>';
			echo '<td style="vertical-align: middle;">' . esc_html( number_format( $transaction->amount, 2 ) ) . '</td>';
			echo '<td style="vertical-align: middle;">' . esc_html( snks_localized_datetime( $transaction->transaction_time ) ) . '</td>';
			echo '<td style="vertical-align: middle;">' . wp_kses_post( $details ) . '</td>';
			echo '</tr>';
		}

		echo '</table>';

		return ob_get_clean();
	}
);

/**
 * Generates a logout link shortcode for logged-in users.
 *
 * This shortcode displays a "Log Out" link if the user is logged in.
 * When clicked, it logs the user out and redirects to the homepage.
 *
 * Usage: [custom_logout]
 *
 * @return string The HTML for the logout link, or an empty string if the user is not logged in.
 */

add_shortcode(
	'custom_logout',
	function () {
		// Check if the user is logged in.
		if ( is_user_logged_in() ) {
			return '<p style="text-align:center;position:relative;z-index:9999"><a id="snks-logout" href="#">خروج</a></p>';
		} else {
			// If the user is not logged in, return an empty string.
			return '';
		}
	}
);

/**
 * Registers a shortcode to display a linked image with the current user's nickname in the URL.
 *
 * @return string The HTML output of the linked image.
 */
function render_user_linked_image() {
	// Get the current user data.
	$current_user = wp_get_current_user();

	// Define the base URL and image source.
	$base_url  = home_url( '/' );
	$image_src = '/wp-content/uploads/2024/08/preview.png';
	$user_link = esc_url( $base_url . '7jz/' . $current_user->nickname );

	// Return the linked image HTML.
	return sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer">
            <img src="%2$s" alt="%3$s">
        </a>',
		$user_link,
		esc_url( $image_src ),
		esc_attr__( 'Preview booking form', 'text-domain' )
	);
}
add_shortcode( 'user_linked_image', 'render_user_linked_image' );