<?php
/**
 * Admin Manual Booking Page
 *
 * Unified UI for new bookings and changing existing appointments.
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Frontend Manual Booking dashboard shortcode.
 * Renders the same UI as the admin page, but on the frontend, restricted to admins and secretaries.
 */
function snks_jalsah_ai_manual_booking_dashboard_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'You must be logged in to access this page.', 'shrinks' ) . '</p>';
	}

	$current_user = wp_get_current_user();
	$roles        = is_object( $current_user ) ? (array) $current_user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		return '<p>' . esc_html__( 'You do not have permission to access this page.', 'shrinks' ) . '</p>';
	}

	ob_start();
	// Reuse the admin page renderer (capability check inside will also pass for allowed roles).
	snks_jalsah_ai_manual_booking_page();
	$content = ob_get_clean();

	return '<div class="snks-ai-frontend-dashboard snks-manual-booking-dashboard">' . $content . '</div>';
}
add_shortcode( 'snks_manual_booking_dashboard', 'snks_jalsah_ai_manual_booking_dashboard_shortcode' );

/**
 * Manual Booking admin page callback.
 */
function snks_jalsah_ai_manual_booking_page() {
	$current_user = wp_get_current_user();
	$roles        = is_object( $current_user ) ? (array) $current_user->roles : array();
	$allowed      = current_user_can( 'manage_options' ) || in_array( 'secretary', $roles, true );

	if ( ! $allowed ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'shrinks' ) );
	}

	snks_load_ai_admin_styles();

	$notice = null;
	$notice_type = 'success';
	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'new';
	$preselect_booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

	// Handle form submission.
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'admin_manual_booking' ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'admin_manual_booking' ) ) {
			$notice = __( 'Security check failed. Please try again.', 'shrinks' );
			$notice_type = 'error';
		} else {
			$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'new';

			if ( 'change' === $mode ) {
				$existing_id = isset( $_POST['existing_booking_id'] ) ? absint( $_POST['existing_booking_id'] ) : 0;
				$new_slot_id = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
				$result = snks_process_admin_change_appointment( $existing_id, $new_slot_id );
			} else {
				$patient_id      = isset( $_POST['patient_id'] ) ? absint( $_POST['patient_id'] ) : 0;
				$therapist_id    = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
				$slot_id         = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
				$country_code    = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : 'EG';
				$amount          = isset( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : null;
				$amount_override = ( $amount !== '' && is_numeric( $amount ) && floatval( $amount ) > 0 ) ? floatval( $amount ) : null;
				$first_name      = isset( $_POST['patient_first_name'] ) ? sanitize_text_field( $_POST['patient_first_name'] ) : '';
				$last_name       = isset( $_POST['patient_last_name'] ) ? sanitize_text_field( $_POST['patient_last_name'] ) : '';
				$payment_method  = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';

				$result = snks_process_admin_manual_booking( $patient_id, $therapist_id, $slot_id, $country_code, $amount_override, $first_name, $last_name );

				// Save payment method on order meta if booking succeeded.
				if ( $result['success'] && isset( $result['order_id'] ) && $payment_method ) {
					$order = wc_get_order( $result['order_id'] );
					if ( $order ) {
						$order->update_meta_data( 'admin_manual_payment_method', $payment_method );
						$order->save();
					}
				}
			}

			$notice = $result['message'];
			$notice_type = $result['success'] ? 'success' : 'error';
			if ( $result['success'] && isset( $result['order_id'] ) ) {
				$notice .= ' ' . sprintf( __( 'رقم الطلب: %s', 'shrinks' ), $result['order_id'] );
			}
		}
	}

	global $wpdb;
	$applications_table = $wpdb->prefix . 'therapist_applications';
	$therapists = $wpdb->get_results(
		"SELECT ta.user_id, ta.name, ta.name_en, ta.phone, ta.whatsapp 
		 FROM {$applications_table} ta 
		 WHERE ta.status = 'approved' AND ta.show_on_ai_site = 1 
		 ORDER BY ta.name ASC"
	);
	// Enrich with billing_phone from usermeta when phone/whatsapp empty
	foreach ( $therapists as $t ) {
		if ( empty( $t->phone ) && empty( $t->whatsapp ) ) {
			$t->phone = get_user_meta( $t->user_id, 'billing_phone', true ) ?: '';
		}
	}

	// Country dropdown is populated per selected therapist via AJAX (see loadCountries).
	// Initial state: no therapist selected.
	$countries_placeholder = array( '' => __( '— Select therapist first —', 'shrinks' ) );

	// Load countries JSON for patient phone (same source as frontend registration).
	$phone_countries = array();
	$countries_json_path = plugin_dir_path( __FILE__ ) . '../../jalsah-ai-frontend/countries-codes-and-flags.json';
	if ( file_exists( $countries_json_path ) ) {
		$json_raw = file_get_contents( $countries_json_path );
		$decoded  = json_decode( $json_raw, true );
		if ( is_array( $decoded ) ) {
			$phone_countries = $decoded;
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manual Booking', 'shrinks' ); ?></h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-manual-booking&tab=new' ) ); ?>" class="nav-tab <?php echo 'new' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'New Booking', 'shrinks' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jalsah-ai-manual-booking&tab=change' ) ); ?>" class="nav-tab <?php echo 'change' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Change Appointment', 'shrinks' ); ?></a>
		</nav>

		<?php if ( 'new' === $active_tab ) : ?>
		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'New Booking', 'shrinks' ); ?></h2>
			<form method="post" id="snks-manual-booking-form">
				<?php wp_nonce_field( 'admin_manual_booking' ); ?>
				<input type="hidden" name="action" value="admin_manual_booking">
				<input type="hidden" name="mode" value="new">

				<table class="form-table">
					<tr>
						<th><label for="patient_search"><?php esc_html_e( 'Patient', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<div style="position: relative;">
								<div style="display:flex; gap:8px; align-items:center;">
									<div id="patient_phone_country_wrapper" style="position:relative;">
										<select id="patient_phone_country" name="patient_phone_country" style="min-width:170px; display:none;">
											<?php
											$default_country_code = 'EG';
											if ( ! empty( $phone_countries ) && is_array( $phone_countries ) ) :
												foreach ( $phone_countries as $country ) :
													$code      = isset( $country['country_code'] ) ? $country['country_code'] : '';
													$name_en   = isset( $country['name_en'] ) ? $country['name_en'] : $code;
													$dial_code = isset( $country['dial_code'] ) ? $country['dial_code'] : '';
													$flag      = isset( $country['flag'] ) ? $country['flag'] : '';
													if ( ! $code || ! $dial_code ) {
														continue;
													}
													?>
													<option value="<?php echo esc_attr( $code ); ?>" data-dial="<?php echo esc_attr( $dial_code ); ?>" data-flag="<?php echo esc_attr( $flag ); ?>" <?php selected( $code, $default_country_code ); ?>>
														<?php echo esc_html( $name_en . ' ' . $dial_code ); ?>
													</option>
													<?php
												endforeach;
											endif;
											?>
										</select>
										<button type="button" id="patient_phone_country_button" class="button" style="min-width:180px; display:flex; align-items:center; justify-content:space-between; font-family: 'Noto Color Emoji','Segoe UI Emoji','Apple Color Emoji',sans-serif;">
											<span id="patient_phone_country_button_label"></span>
											<span class="dashicons dashicons-arrow-down-alt2"></span>
										</button>
										<div id="patient_phone_country_dropdown" style="display:none; position:absolute; top:100%; left:0; right:auto; margin-top:2px; background:#fff; border:1px solid #8c8f94; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,.15); max-height:260px; width:260px; z-index:200;">
											<div style="padding:6px 8px;">
												<input type="text" id="patient_phone_country_search" placeholder="<?php esc_attr_e( 'Search countries...', 'shrinks' ); ?>" style="width:100%; padding:4px 6px; font-size:12px; border:1px solid #ccd0d4; border-radius:3px;">
											</div>
											<div id="patient_phone_country_list" style="max-height:210px; overflow-y:auto;"></div>
										</div>
									</div>
									<input type="text" id="patient_search" class="regular-text" placeholder="<?php esc_attr_e( 'Enter the phone number', 'shrinks' ); ?>" autocomplete="off">
								</div>
								<div id="patient_results" class="snks-patient-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; margin-top:2px; background:#fff; border:1px solid #8c8f94; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; z-index:100;"></div>
							</div>
							<input type="hidden" name="patient_id" id="patient_id" required>
							<span id="patient_display" style="margin-right: 10px; font-weight:500;"></span>
							<button type="button" id="patient_clear_btn" class="button button-small" style="display:none;"><?php esc_html_e( 'Clear', 'shrinks' ); ?></button>
							<span id="patient_search_loading" class="spinner" style="float:none; margin-left:6px; display:none;"></span>
							<p class="description"><?php esc_html_e( 'Enter phone number (with country) to search existing patient or create a new one (password: 12345678).', 'shrinks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="patient_first_name"><?php esc_html_e( 'Patient name', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<input type="text" name="patient_first_name" id="patient_first_name" class="regular-text" placeholder="<?php esc_attr_e( 'First name', 'shrinks' ); ?>" required>
							<input type="text" name="patient_last_name" id="patient_last_name" class="regular-text" placeholder="<?php esc_attr_e( 'Last name', 'shrinks' ); ?>" required style="margin-left:10px;">
							<p class="description"><?php esc_html_e( 'These will be saved as the billing first and last name for this patient.', 'shrinks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="therapist_search"><?php esc_html_e( 'Therapist', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<div style="position: relative;">
								<input type="text" id="therapist_search" class="regular-text" placeholder="<?php esc_attr_e( 'Search by name or phone...', 'shrinks' ); ?>" autocomplete="off">
								<div id="therapist_results" class="snks-patient-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; margin-top:2px; background:#fff; border:1px solid #8c8f94; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; z-index:100;"></div>
							</div>
							<input type="hidden" name="therapist_id" id="therapist_id" required>
							<span id="therapist_display" style="margin-right: 10px; font-weight:500;"></span>
							<button type="button" id="therapist_clear_btn" class="button button-small" style="display:none;"><?php esc_html_e( 'Clear', 'shrinks' ); ?></button>
						</td>
					</tr>
					<tr>
						<th><label for="booking_date"><?php esc_html_e( 'Date', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<select name="booking_date" id="booking_date" required>
								<option value=""><?php esc_html_e( '— Select therapist first —', 'shrinks' ); ?></option>
							</select>
							<span id="dates_loading" style="display:none; margin-left:6px;"><?php esc_html_e( 'Loading...', 'shrinks' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="slot_id"><?php esc_html_e( 'Time Slot', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<select name="slot_id" id="slot_id" required>
								<option value=""><?php esc_html_e( '— Select date and therapist first —', 'shrinks' ); ?></option>
							</select>
							<span id="slots_loading" style="display:none;"><?php esc_html_e( 'Loading...', 'shrinks' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="country_code"><?php esc_html_e( 'Country', 'shrinks' ); ?></label></th>
						<td>
							<select name="country_code" id="country_code">
								<?php foreach ( $countries_placeholder as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="amount"><?php esc_html_e( 'Amount (override)', 'shrinks' ); ?></label></th>
						<td>
							<input type="number" name="amount" id="amount" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Auto from country', 'shrinks' ); ?>">
							<span id="calculated_price"></span>
						</td>
					</tr>
					<tr>
						<th><label for="payment_method"><?php esc_html_e( 'Payment method', 'shrinks' ); ?></label></th>
						<td>
							<select name="payment_method" id="payment_method">
								<option value=""><?php esc_html_e( 'Select payment method', 'shrinks' ); ?></option>
								<option value="instapay"><?php esc_html_e( 'InstaPay', 'shrinks' ); ?></option>
								<option value="wallet"><?php esc_html_e( 'Wallet', 'shrinks' ); ?></option>
								<option value="bank_transfer"><?php esc_html_e( 'Bank transfer', 'shrinks' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Book Appointment', 'shrinks' ) ); ?>
			</form>
		</div>
		<?php else : ?>
		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Change Appointment', 'shrinks' ); ?></h2>
			<form method="post" id="snks-change-appointment-form">
				<?php wp_nonce_field( 'admin_manual_booking' ); ?>
				<input type="hidden" name="action" value="admin_manual_booking">
				<input type="hidden" name="mode" value="change">
				<input type="hidden" name="existing_booking_id" id="existing_booking_id">

				<table class="form-table">
					<tr>
						<th><label for="change_search"><?php esc_html_e( 'Search appointment', 'shrinks' ); ?></label></th>
						<td>
							<input type="text" id="change_search" class="regular-text" placeholder="<?php esc_attr_e( 'Patient email, phone, or booking ID...', 'shrinks' ); ?>">
							<button type="button" id="change_search_btn" class="button"><?php esc_html_e( 'Search', 'shrinks' ); ?></button>
							<span id="change_search_loading" class="spinner" style="float:none; margin:0 0 0 6px; display:none;"></span>
							<div id="change_results" style="margin-top: 10px;"></div>
						</td>
					</tr>
					<tr id="change_slot_row" style="display:none;">
						<th><label for="change_slot_id"><?php esc_html_e( 'New time slot', 'shrinks' ); ?> <span class="required">*</span></label></th>
						<td>
							<input type="date" id="change_date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
							<select name="slot_id" id="change_slot_id">
								<option value=""><?php esc_html_e( '— Select date first —', 'shrinks' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<p id="change_submit_p" style="display:none;"><?php submit_button( __( 'Change Appointment', 'shrinks' ) ); ?></p>
			</form>
		</div>
		<?php endif; ?>
	</div>

	<style>
		/* Override default .spinner visibility so loading indicators show when active */
		#patient_search_loading.is-active,
		#change_search_loading.is-active {
			visibility: visible !important;
			display: inline-block !important;
		}
	</style>
	<script>
	// Ensure ajaxurl is available on frontend as well as in admin.
	if (typeof ajaxurl === 'undefined') {
		var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	}
	jQuery(function($) {
		var therapistId = $('#therapist_id');
		var bookingDate = $('#booking_date');
		var slotSelect = $('#slot_id');
		var countrySelect = $('#country_code');
		var amountInput = $('#amount');
		var phoneCountrySelect = $('#patient_phone_country');
		var phoneCountryButton = $('#patient_phone_country_button');
		var phoneCountryDropdown = $('#patient_phone_country_dropdown');
		var phoneCountrySearch = $('#patient_phone_country_search');
		var phoneCountryList = $('#patient_phone_country_list');

		var therapistsData = <?php echo wp_json_encode( array_map( function( $t ) {
			$name = $t->name ?: $t->name_en ?: '';
			$name_en = $t->name_en ?: '';
			$phone = $t->phone ?: $t->whatsapp ?: '';
			return array( 'id' => (int) $t->user_id, 'name' => $name, 'name_en' => $name_en, 'phone' => $phone );
		}, $therapists ) ); ?>;

		// Countries data for phone validation (same JSON as frontend registration).
		window.snksPhoneCountries = <?php echo wp_json_encode( $phone_countries ); ?> || [];

		function getLocalizedCountries() {
			var list = window.snksPhoneCountries || [];
			var lang = (document.documentElement.lang || '').toLowerCase();
			var isArabic = lang.indexOf('ar') === 0;
			return list.map(function(c) {
				return {
					code: c.country_code,
					name: isArabic ? (c.name_ar || c.name_en || c.country_code) : (c.name_en || c.name_ar || c.country_code),
					dial: c.dial_code,
					flag: c.flag || '🏳️'
				};
			});
		}

		function renderPhoneCountryButton() {
			var currentCode = phoneCountrySelect.val() || 'EG';
			var countries = getLocalizedCountries();
			var selected = countries.find(function(c) { return c.code === currentCode; }) || countries[0];
			if (!selected) return;
			phoneCountryButton.find('#patient_phone_country_button_label').text(selected.flag + ' ' + selected.dial);
		}

		function renderPhoneCountryList(filter) {
			var countries = getLocalizedCountries();
			var q = (filter || '').toLowerCase();
			if (q) {
				countries = countries.filter(function(c) {
					return c.name.toLowerCase().indexOf(q) !== -1 ||
						(c.dial && c.dial.indexOf(q) !== -1) ||
						(c.code && c.code.toLowerCase().indexOf(q) !== -1);
				});
			}
			phoneCountryList.empty();
			if (!countries.length) {
				phoneCountryList.append('<div style="padding:8px 10px; color:#646970;"><?php echo esc_js( __( 'No match', 'shrinks' ) ); ?></div>');
				return;
			}
			countries.forEach(function(c) {
				var row = $('<div class="snks-country-row" data-code="' + c.code + '" style="padding:6px 10px; cursor:pointer; display:flex; align-items:center; border-bottom:1px solid #f0f0f0;"></div>');
				var label = '<span style="margin-right:6px;">' + c.flag + '</span>' +
					'<span style="flex:1;">' + c.name + '</span>' +
					'<span style="color:#777; font-size:11px; margin-left:6px;">' + (c.dial || '') + '</span>';
				row.html(label);
				row.on('mouseenter', function() { $(this).css('background','#f6f7f7'); });
				row.on('mouseleave', function() { $(this).css('background',''); });
				row.on('click', function() {
					var code = $(this).data('code');
					phoneCountrySelect.val(code);
					renderPhoneCountryButton();
					phoneCountryDropdown.hide();
				});
				phoneCountryList.append(row);
			});
		}

		// Initialize phone country UI
		renderPhoneCountryButton();
		renderPhoneCountryList('');

		phoneCountryButton.on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			phoneCountryDropdown.toggle();
			if (phoneCountryDropdown.is(':visible')) {
				phoneCountrySearch.val('').focus();
				renderPhoneCountryList('');
			}
		});

		phoneCountrySearch.on('input', function() {
			renderPhoneCountryList($(this).val());
		});

		function filterTherapists(q) {
			q = (q || '').trim();
			if (q.length < 1) return therapistsData;
			var qLower = q.toLowerCase();
			var qDigits = q.replace(/\D/g, '');
			return therapistsData.filter(function(t) {
				var name = (t.name || '');
				var nameEn = (t.name_en || '');
				var nameMatch = name.toLowerCase().indexOf(qLower) >= 0 || nameEn.toLowerCase().indexOf(qLower) >= 0;
				var phoneMatch = qDigits.length > 0 && (t.phone || '').replace(/\D/g, '').indexOf(qDigits) >= 0;
				return nameMatch || phoneMatch;
			});
		}

		function refreshTherapistResults(searchVal) {
			var q = (typeof searchVal !== 'undefined' ? searchVal : $('#therapist_search').val());
			if (typeof q !== 'string') q = '';
			q = String(q).trim();
			var filtered = filterTherapists(q);
			var $results = $('#therapist_results');
			$results.empty();
			if (filtered.length === 0) {
				$results.append('<div style="padding:10px 12px; color:#646970;"><?php echo esc_js( __( 'No match', 'shrinks' ) ); ?></div>');
			} else {
				filtered.forEach(function(t) {
					var displayText = (t.name || t.name_en || '') + (t.phone ? ' — ' + t.phone : '');
					var row = $('<div class="snks-therapist-row" data-id="' + t.id + '" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0;"></div>');
					row.text(displayText);
					row.on('mouseenter', function() { $(this).css('background', '#f6f7f7'); });
					row.on('mouseleave', function() { $(this).css('background', ''); });
					row.on('click', function() {
						therapistId.val($(this).data('id'));
						$('#therapist_display').text($(this).text());
						$('#therapist_search').val('');
						$results.hide().empty();
						$('#therapist_clear_btn').show();
						loadCountries();
						loadAvailableDates();
						updatePrice();
					});
					$results.append(row);
				});
			}
			$results.show();
		}

		$('#therapist_search').on('input keyup focus', function() {
			var $input = $(this);
			setTimeout(function() {
				refreshTherapistResults( $input.val() );
			}, 0);
		});

		$('#therapist_clear_btn').on('click', function() {
			therapistId.val('');
			$('#therapist_display').text('');
			$('#therapist_search').val('').focus();
			$('#therapist_results').hide().empty();
			$(this).hide();
			resetCountries();
			resetDates();
		});

		function resetCountries() {
			var html = '<option value=""><?php echo esc_js( __( '— Select therapist first —', 'shrinks' ) ); ?></option>';
			countrySelect.html(html);
			$('#calculated_price').text('');
		}

		function resetDates() {
			bookingDate.html('<option value=""><?php echo esc_js( __( '— Select therapist first —', 'shrinks' ) ); ?></option>');
			slotSelect.html('<option value=""><?php echo esc_js( __( '— Select date and therapist first —', 'shrinks' ) ); ?></option>');
		}

		function loadAvailableDates() {
			var tid = therapistId.val();
			if (!tid) {
				resetDates();
				return;
			}
			$('#dates_loading').show();
			bookingDate.html('<option value=""><?php echo esc_js( __( '— Loading dates —', 'shrinks' ) ); ?></option>');
			slotSelect.html('<option value=""><?php echo esc_js( __( '— Select date first —', 'shrinks' ) ); ?></option>');
			$.post(ajaxurl, {
				action: 'snks_manual_booking_get_available_dates',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				therapist_id: tid
			}, function(res) {
				$('#dates_loading').hide();
				var html = '<option value=""><?php echo esc_js( __( '— Select date —', 'shrinks' ) ); ?></option>';
				if (res.success && res.data && res.data.length) {
					res.data.forEach(function(o) {
						html += '<option value="' + (o.date || '').replace(/"/g, '&quot;') + '">' + (o.label || o.date || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
					});
				}
				bookingDate.html(html);
			});
		}

		function loadCountries() {
			var tid = therapistId.val();
			if (!tid) {
				resetCountries();
				return;
			}
			$.post(ajaxurl, {
				action: 'snks_manual_booking_get_therapist_countries',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				therapist_id: tid
			}, function(res) {
				if (res.success && res.data && res.data.length) {
					var html = '<option value=""><?php echo esc_js( __( '— Select —', 'shrinks' ) ); ?></option>';
					res.data.forEach(function(c) {
						var label = (c.name || c.code || '');
						if (c.price != null && c.price !== '') {
							label += ' — ' + c.price + ' ' + (c.currency_symbol || '');
						}
						html += '<option value="' + (c.code || '').replace(/"/g, '&quot;') + '">' + label.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
					});
					countrySelect.html(html);
				} else {
					countrySelect.html('<option value=""><?php echo esc_js( __( 'No countries in therapist pricing', 'shrinks' ) ); ?></option>');
				}
				updatePrice();
			});
		}

		function loadSlots() {
			var tid = therapistId.val();
			var date = bookingDate.val();
			if (!tid || !date) {
				slotSelect.html('<option value=""><?php echo esc_js( __( '— Select date and therapist first —', 'shrinks' ) ); ?></option>');
				return;
			}
			$('#slots_loading').show();
			$.post(ajaxurl, {
				action: 'snks_manual_booking_get_slots',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				therapist_id: tid,
				date: date
			}, function(res) {
				$('#slots_loading').hide();
				if (res.success && res.data && res.data.length) {
					var html = '<option value=""><?php echo esc_js( __( '— Select —', 'shrinks' ) ); ?></option>';
					res.data.forEach(function(s) {
						html += '<option value="' + s.slot_id + '" data-period="' + (s.period || 45) + '">' + s.formatted_time + '</option>';
					});
					slotSelect.html(html);
				} else {
					slotSelect.html('<option value=""><?php echo esc_js( __( 'No slots available', 'shrinks' ) ); ?></option>');
				}
			});
		}

		function updatePrice() {
			var tid = therapistId.val();
			var slotOpt = slotSelect.find('option:selected');
			var slotId = slotOpt.val();
			var country = countrySelect.val();
			if (!tid || !slotId || !country) return;
			var period = slotOpt.data('period') || 45;
			$.post(ajaxurl, {
				action: 'snks_manual_booking_get_price',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				therapist_id: tid,
				country_code: country,
				period: period
			}, function(res) {
				if (res.success && res.data) {
					var countryName = $('#country_code option:selected').text() || '';
					$('#calculated_price').text(countryName + ' ' + res.data.original_price + ' ' + (res.data.currency_symbol || ''));
				}
			});
		}

		therapistId.add(bookingDate).on('change', loadSlots);
		slotSelect.on('change', updatePrice);
		countrySelect.on('change', updatePrice);

		// Phone validation using same rules as frontend registration.
		function validatePhoneNumber(phoneNumber, countryCode) {
			if (!window.snksPhoneCountries || !window.snksPhoneCountries.length) {
				return { isValid: true, error: null };
			}
			var country = window.snksPhoneCountries.find(function(c) { return c.country_code === countryCode; });
			if (!country || !country.validation_pattern) {
				return { isValid: true, error: null };
			}
			var cleanPhone = String(phoneNumber || '').replace(/[\s\-\(\)]/g, '');
			if (!cleanPhone || !/^\d+$/.test(cleanPhone)) {
				return { isValid: false, error: '<?php echo esc_js( __( 'Phone number must contain digits only.', 'shrinks' ) ); ?>' };
			}
			if (cleanPhone.charAt(0) === '0') {
				return { isValid: false, error: '<?php echo esc_js( __( 'Remove leading zero from the phone number.', 'shrinks' ) ); ?>' };
			}
			var fullPhone = (country.dial_code || '') + cleanPhone;
			var pattern = new RegExp(country.validation_pattern);
			if (!pattern.test(fullPhone)) {
				var isArabic = document.documentElement.lang && document.documentElement.lang.toLowerCase().indexOf('ar') === 0;
				var customMessage = isArabic ? country.validation_message_ar : country.validation_message_en;
				var fallback = '<?php echo esc_js( __( 'Please enter a valid phone number for the selected country.', 'shrinks' ) ); ?>';
				return { isValid: false, error: (customMessage && customMessage.trim()) ? customMessage : fallback };
			}
			return { isValid: true, error: null };
		}

		var patientSearchTimer;
		// Restrict patient search (phone) to digits only; keep input type="text".
		$('#patient_search').on('input', function() {
			var $input = $(this);
			var raw = $input.val().replace(/\D/g, '');
			if (raw !== $input.val()) {
				$input.val(raw);
			}
			var q = raw;
			if (q.length < 2) {
				$('#patient_id').val('');
				$('#patient_display').text('');
				$('#patient_first_name').val('');
				$('#patient_last_name').val('');
				$('#patient_results').hide().empty();
				$('#patient_clear_btn').hide();
				$('#patient_search_loading').removeClass('is-active').hide();
				return;
			}
			$('#patient_results').hide().empty();
			clearTimeout(patientSearchTimer);
			$('#patient_search_loading').addClass('is-active').show();
			patientSearchTimer = setTimeout(function() {
				// Build full phone with country dial code for searching/creation.
				var countryCode = phoneCountrySelect.val() || 'EG';
				if (window.snksPhoneCountries && window.snksPhoneCountries.length) {
					var country = window.snksPhoneCountries.find(function(c) { return c.country_code === countryCode; });
					if (country && country.dial_code) {
						q = country.dial_code + raw.replace(/[\s\-\(\)]/g, '');
					}
				}
				$.post(ajaxurl, {
					action: 'snks_manual_booking_search_patient',
					nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
					q: q
				}, function(res) {
					$('#patient_search_loading').removeClass('is-active').hide();
					var $results = $('#patient_results');
					$results.empty();
					if (res.success && res.data && res.data.length) {
						if (res.data.length === 1) {
							var p = res.data[0];
							$('#patient_id').val(p.id);
							$('#patient_display').text(p.name + ' (' + p.email + ')');
							$('#patient_first_name').val(p.first_name || '');
							$('#patient_last_name').val(p.last_name || '');
							$('#patient_search').val('');
							$('#patient_clear_btn').show();
						} else {
							res.data.forEach(function(p) {
								var displayText = (p.name || '') + ' (' + (p.email || '') + ')';
								var row = $('<div class="snks-patient-row" data-id="' + p.id + '" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0;"></div>');
								row.text(displayText);
								row.on('mouseenter', function() { $(this).css('background', '#f6f7f7'); });
								row.on('mouseleave', function() { $(this).css('background', ''); });
								row.on('click', function() {
									$('#patient_id').val($(this).data('id'));
									$('#patient_display').text($(this).text());
									$('#patient_first_name').val(p.first_name || '');
									$('#patient_last_name').val(p.last_name || '');
									$('#patient_search').val('');
									$results.hide().empty();
									$('#patient_clear_btn').show();
								});
								$results.append(row);
							});
							$results.show();
						}
					} else {
						$('#patient_id').val('');
						$('#patient_display').text('');
						var empty = $('<div style="padding:10px 12px; color:#646970;"><?php echo esc_js( __( 'No match', 'shrinks' ) ); ?></div>');
						$results.append(empty);
						$results.show();
					}
				});
			}, 300);
		});

		$('#patient_clear_btn').on('click', function() {
			$('#patient_id').val('');
			$('#patient_display').text('');
			$('#patient_first_name').val('');
			$('#patient_last_name').val('');
			$('#patient_search').val('').focus();
			$('#patient_results').hide().empty();
			$('#patient_search_loading').removeClass('is-active').hide();
			$(this).hide();
		});

		// Validate phone on submit for new patients (when we will auto-create a user).
		$('#snks-manual-booking-form').on('submit', function(e) {
			var patientId = $('#patient_id').val();
			var phoneRaw  = $('#patient_search').val().trim();
			var countryCode = phoneCountrySelect.val() || 'EG';
			// If existing patient is selected, skip phone validation here.
			if (!patientId) {
				if (!phoneRaw) {
					e.preventDefault();
					alert('<?php echo esc_js( __( 'Please enter the patient phone number.', 'shrinks' ) ); ?>');
					return false;
				}
				var result = validatePhoneNumber(phoneRaw, countryCode);
				if (!result.isValid) {
					e.preventDefault();
					alert(result.error);
					return false;
				}
			}
			return true;
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('#patient_search, #patient_results').length) {
				$('#patient_results').hide();
			}
			if (!$(e.target).closest('#therapist_search, #therapist_results').length) {
				$('#therapist_results').hide();
			}
			if (!$(e.target).closest('#patient_phone_country_wrapper').length) {
				phoneCountryDropdown.hide();
			}
		});

		<?php if ( 'change' === $active_tab ) : ?>
		var changeTherapistId = null;
		var preselectId = <?php echo $preselect_booking_id ? (int) $preselect_booking_id : 0; ?>;
		if (preselectId) {
			$('#change_search_loading').addClass('is-active').show();
			$.post(ajaxurl, {
				action: 'snks_manual_booking_search_appointments',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				q: String(preselectId)
			}, function(res) {
				$('#change_search_loading').removeClass('is-active').hide();
				if (res.success && res.data && res.data.length) {
					var a = res.data[0];
					changeTherapistId = a.therapist_id;
					$('#existing_booking_id').val(a.booking_id);
					$('#change_slot_row').show();
					$('#change_submit_p').show();
					$('#change_results').html('<p><?php echo esc_js( __( 'Selected:', 'shrinks' ) ); ?> #' + a.booking_id + ' - ' + a.patient_name + ' / ' + a.therapist_name + ' - ' + a.date_time + '</p>');
				}
			});
		}
		$('#change_search_btn').on('click', function() {
			var q = $('#change_search').val();
			if (!q || q.length < 1) return;
			$('#change_search_loading').addClass('is-active').show();
			$.post(ajaxurl, {
				action: 'snks_manual_booking_search_appointments',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				q: q
			}, function(res) {
				$('#change_search_loading').removeClass('is-active').hide();
				var div = $('#change_results');
				if (res.success && res.data && res.data.length) {
					var html = '<table class="widefat"><thead><tr><th><?php echo esc_js( __( 'Select', 'shrinks' ) ); ?></th><th><?php echo esc_js( __( 'ID', 'shrinks' ) ); ?></th><th><?php echo esc_js( __( 'Patient', 'shrinks' ) ); ?></th><th><?php echo esc_js( __( 'Therapist', 'shrinks' ) ); ?></th><th><?php echo esc_js( __( 'Date/Time', 'shrinks' ) ); ?></th></tr></thead><tbody>';
					res.data.forEach(function(a) {
						html += '<tr><td><button type="button" class="button select-booking" data-id="' + a.booking_id + '" data-therapist="' + a.therapist_id + '"><?php echo esc_js( __( 'Select', 'shrinks' ) ); ?></button></td><td>' + a.booking_id + '</td><td>' + a.patient_name + '</td><td>' + a.therapist_name + '</td><td>' + a.date_time + '</td></tr>';
					});
					html += '</tbody></table>';
					div.html(html);
				} else {
					div.html('<p><?php echo esc_js( __( 'No appointments found.', 'shrinks' ) ); ?></p>');
				}
			});
		});

		$(document).on('click', '.select-booking', function() {
			var id = $(this).data('id');
			changeTherapistId = $(this).data('therapist');
			$('#existing_booking_id').val(id);
			$('#change_slot_row').show();
			$('#change_submit_p').show();
			$('#change_date').val('');
			$('#change_slot_id').html('<option value=""><?php echo esc_js( __( '— Select date first —', 'shrinks' ) ); ?></option>');
		});

		$('#change_date').on('change', function() {
			var date = $(this).val();
			if (!date || !changeTherapistId) return;
			$.post(ajaxurl, {
				action: 'snks_manual_booking_get_slots',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				therapist_id: changeTherapistId,
				date: date
			}, function(res) {
				var sel = $('#change_slot_id');
				if (res.success && res.data && res.data.length) {
					var html = '<option value=""><?php echo esc_js( __( '— Select —', 'shrinks' ) ); ?></option>';
					res.data.forEach(function(s) {
						html += '<option value="' + s.slot_id + '">' + s.formatted_time + '</option>';
					});
					sel.html(html);
				} else {
					sel.html('<option value=""><?php echo esc_js( __( 'No slots available', 'shrinks' ) ); ?></option>');
				}
			});
		});
		<?php endif; ?>
	});
	</script>
	<?php
}

/**
 * Create a patient (customer) user from phone number for manual booking.
 * Username and email are derived from phone; password defaults to 12345678.
 *
 * @param string $phone Phone number (digits with or without +; stored with full dial code e.g. +201026795573).
 * @return int|WP_Error User ID on success, WP_Error on failure.
 */
function snks_manual_booking_create_patient_from_phone( $phone ) {
	$phone = sanitize_text_field( $phone );
	$digits = preg_replace( '/\D/', '', $phone );
	// Require at least 5 digits to treat as phone.
	if ( strlen( $digits ) < 5 ) {
		return new WP_Error( 'invalid_phone', __( 'Please enter a valid phone number (at least 5 digits).', 'shrinks' ) );
	}

	// Store phone/WhatsApp with full dial code (e.g. +201026795573) for consistency.
	$phone_to_store = '+' . $digits;

	$base_login = $digits;
	$base_email = $digits . '@jalsah.app';
	$login = $base_login;
	$email = $base_email;
	$suffix = 0;
	while ( username_exists( $login ) || email_exists( $email ) ) {
		$suffix++;
		$login = $base_login . '_' . $suffix;
		$email = $digits . '_' . $suffix . '@jalsah.app';
	}

	$default_password = '12345678';
	// Use raw phone as display name for auto-created patients to avoid prefixed labels.
	$display_name = $digits;

	$user_id = wp_insert_user( array(
		'user_login'   => $login,
		'user_email'   => $email,
		'user_pass'    => $default_password,
		'display_name' => $display_name,
		'role'         => 'customer',
	) );

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	update_user_meta( $user_id, 'billing_phone', $phone_to_store );
	update_user_meta( $user_id, 'whatsapp', $phone_to_store );

	return $user_id;
}

/**
 * Return therapists list for manual booking dropdown (used by REST and admin page).
 *
 * @return array List of { user_id, name, name_en, phone, whatsapp }.
 */
function snks_manual_booking_data_therapists() {
	global $wpdb;
	$applications_table = $wpdb->prefix . 'therapist_applications';
	$rows = $wpdb->get_results(
		"SELECT ta.user_id, ta.name, ta.name_en, ta.phone, ta.whatsapp 
		 FROM {$applications_table} ta 
		 WHERE ta.status = 'approved' AND ta.show_on_ai_site = 1 
		 ORDER BY ta.name ASC"
	);
	$result = array();
	foreach ( (array) $rows as $t ) {
		$phone = $t->phone ?: ( $t->whatsapp ?: '' );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $t->user_id, 'billing_phone', true ) ?: '';
		}
		$result[] = array(
			'user_id'  => (int) $t->user_id,
			'name'     => $t->name ?: '',
			'name_en'  => $t->name_en ?: '',
			'phone'    => $phone,
			'whatsapp' => $t->whatsapp ?: '',
		);
	}
	return $result;
}

/**
 * Return search-patient data for manual booking (used by AJAX and REST).
 *
 * @param string $q Search query (phone digits or email).
 * @return array List of patient items (id, email, name, first_name, last_name).
 */
function snks_manual_booking_data_search_patient( $q ) {
	$q = sanitize_text_field( $q );
	if ( strlen( $q ) < 2 ) {
		return array();
	}
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $q ) . '%';
	$caps_key = $wpdb->get_blog_prefix() . 'capabilities';
	// Search by email/login (users table) OR by phone/email in meta.
	$users = $wpdb->get_results( $wpdb->prepare(
		"SELECT DISTINCT u.ID as id, u.user_email as email, u.display_name 
		 FROM {$wpdb->users} u
		 INNER JOIN {$wpdb->usermeta} caps ON u.ID = caps.user_id AND caps.meta_key = %s AND caps.meta_value LIKE %s
		 WHERE (
			u.user_email LIKE %s 
			OR u.user_login LIKE %s 
			OR u.ID IN (
				SELECT user_id FROM {$wpdb->usermeta} 
				WHERE meta_key IN ('billing_phone','billing_email','whatsapp') AND meta_value LIKE %s
			)
		 )
		 ORDER BY u.display_name ASC
		 LIMIT 10",
		$caps_key,
		'%customer%',
		$like,
		$like,
		$like
	) );

	$result = array();
	foreach ( $users as $u ) {
		$first = get_user_meta( $u->id, 'billing_first_name', true );
		$last  = get_user_meta( $u->id, 'billing_last_name', true );
		$phone = get_user_meta( $u->id, 'billing_phone', true );
		$name  = trim( $first . ' ' . $last );
		if ( $name === '' ) {
			if ( $phone !== '' ) {
				$name = $phone;
			} else {
				// Backward compatibility: many auto-created patients had display_name like "Patient 2010...".
				// Fall back to digits-only version of display_name when it looks like a phone number, so
				// labels like "Patient 2010..." become just "2010...".
				$display_name = $u->display_name;
				$digits       = preg_replace( '/\D+/', '', (string) $display_name );
				if ( strlen( $digits ) >= 5 ) {
					$name = $digits;
				} else {
					$name = $display_name;
				}
			}
		}

		$result[] = array(
			'id'         => $u->id,
			'email'      => $u->email,
			'name'       => $name,
			'first_name' => $first,
			'last_name'  => $last,
		);
	}

	// If no match and input looks like a phone number, create the user.
	if ( empty( $result ) && strlen( preg_replace( '/\D/', '', $q ) ) >= 5 ) {
		$new_user_id = snks_manual_booking_create_patient_from_phone( $q );
		if ( ! is_wp_error( $new_user_id ) ) {
			$u = get_userdata( $new_user_id );
			$first = $u ? get_user_meta( $u->ID, 'billing_first_name', true ) : '';
			$last  = $u ? get_user_meta( $u->ID, 'billing_last_name', true ) : '';
			$phone = $u ? get_user_meta( $u->ID, 'billing_phone', true ) : '';
			// For auto-created patients, always use the raw phone as name so the frontend can
			// prepend the desired localized label (e.g. "إنشاء مريض جديد - {phone}").
			$name = $phone !== '' ? $phone : preg_replace( '/\D/', '', $q );

			$result[] = array(
				'id'         => (int) $new_user_id,
				'email'      => $u ? $u->user_email : '',
				'name'       => $name,
				'first_name' => $first,
				'last_name'  => $last,
				'is_new'     => true,
			);
		}
	}
	return $result;
}

/**
 * AJAX: Search patient by phone (or email). If no user found and input is a phone number, create the user.
 */
function snks_ajax_manual_booking_search_patient() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
	$result = snks_manual_booking_data_search_patient( $q );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_search_patient', 'snks_ajax_manual_booking_search_patient' );

/**
 * Return available future dates for a therapist (used by AJAX and REST).
 *
 * @param int $therapist_id Therapist user ID.
 * @return array List of { date, label }.
 */
function snks_manual_booking_data_available_dates( $therapist_id ) {
	$therapist_id = absint( $therapist_id );
	if ( ! $therapist_id ) {
		return array();
	}
	global $wpdb;
	$table = $wpdb->prefix . 'snks_provider_timetable';
	$dates = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT DATE(date_time) AS d
		 FROM {$table}
		 WHERE user_id = %d
		 AND session_status = 'waiting'
		 AND order_id = 0
		 AND (client_id = 0 OR client_id IS NULL)
		 AND (settings NOT LIKE '%%ai_booking:booked%%' OR settings = '' OR settings IS NULL)
		 AND (settings NOT LIKE '%%ai_booking:in_cart%%' OR settings = '' OR settings IS NULL)
		 AND date_time > NOW()
		 ORDER BY d ASC
		 LIMIT 60",
		$therapist_id
	) );
	$dates = (array) $dates;
	// Exclude global excluded booking dates (e.g. holidays).
	$global_excluded = function_exists( 'snks_get_global_excluded_booking_dates' ) ? snks_get_global_excluded_booking_dates() : array();
	$dates = array_values( array_diff( $dates, $global_excluded ) );
	$today = current_time( 'Y-m-d' );
	$result = array();
	foreach ( $dates as $d ) {
		$label = ( $d === $today )
			? sprintf( __( 'Today — %s', 'shrinks' ), wp_date( 'j M Y', strtotime( $d ) ) )
			: wp_date( 'D j M Y', strtotime( $d ) );
		$result[] = array( 'date' => $d, 'label' => $label );
	}
	return $result;
}

/**
 * AJAX: Get available future dates for therapist (dates that have at least one available slot).
 */
function snks_ajax_manual_booking_get_available_dates() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$result = snks_manual_booking_data_available_dates( $therapist_id );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_get_available_dates', 'snks_ajax_manual_booking_get_available_dates' );

/**
 * Return available slots for therapist and date (used by AJAX and REST).
 *
 * @param int    $therapist_id Therapist user ID.
 * @param string $date         Date Y-m-d.
 * @return array List of { slot_id, time, formatted_time, period } or empty on invalid params.
 */
function snks_manual_booking_data_slots( $therapist_id, $date ) {
	$therapist_id = absint( $therapist_id );
	$date = sanitize_text_field( $date );
	if ( ! $therapist_id || ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return array();
	}
	global $wpdb;
	$slots = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID as slot_id, starts, period 
		 FROM {$wpdb->prefix}snks_provider_timetable 
		 WHERE user_id = %d AND DATE(date_time) = %s 
		 AND session_status = 'waiting' AND order_id = 0 
		 AND (client_id = 0 OR client_id IS NULL)
		 AND (settings NOT LIKE '%%ai_booking:booked%%' OR settings = '' OR settings IS NULL)
		 AND (settings NOT LIKE '%%ai_booking:in_cart%%' OR settings = '' OR settings IS NULL)
		 ORDER BY starts ASC",
		$therapist_id,
		$date
	) );
	$current_time = current_time( 'H:i:s' );
	$is_today = ( $date === current_time( 'Y-m-d' ) );
	$result = array();
	foreach ( $slots as $s ) {
		if ( $is_today && $s->starts <= $current_time ) {
			continue;
		}
		$parts = explode( ':', $s->starts );
		$h = (int) $parts[0];
		$m = isset( $parts[1] ) ? (int) $parts[1] : 0;
		$period = $h >= 12 ? 'م' : 'ص';
		$display_h = $h > 12 ? $h - 12 : ( $h === 0 ? 12 : $h );
		$formatted = sprintf( '%d:%02d %s', $display_h, $m, $period );
		$result[] = array(
			'slot_id'        => $s->slot_id,
			'time'           => $s->starts,
			'formatted_time' => $formatted,
			'period'         => isset( $s->period ) ? $s->period : 45,
		);
	}
	return $result;
}

/**
 * AJAX: Get available slots for therapist and date.
 */
function snks_ajax_manual_booking_get_slots() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
	$result = snks_manual_booking_data_slots( $therapist_id, $date );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_get_slots', 'snks_ajax_manual_booking_get_slots' );

/**
 * Return price for therapist, country, period (used by AJAX and REST).
 *
 * @param int    $therapist_id  Therapist user ID.
 * @param string $country_code  Country code.
 * @param int    $period        Session period minutes.
 * @return array Pricing array or empty on invalid therapist.
 */
function snks_manual_booking_data_price( $therapist_id, $country_code = 'EG', $period = 45 ) {
	$therapist_id = absint( $therapist_id );
	if ( ! $therapist_id ) {
		return array();
	}
	$country_code = sanitize_text_field( $country_code ) ?: 'EG';
	$period = absint( $period ) ?: 45;
	return snks_get_ai_therapist_price( $therapist_id, $country_code, $period );
}

/**
 * AJAX: Get price for therapist, country, period.
 */
function snks_ajax_manual_booking_get_price() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : 'EG';
	$period = isset( $_POST['period'] ) ? absint( $_POST['period'] ) : 45;
	$pricing = snks_manual_booking_data_price( $therapist_id, $country_code, $period );
	if ( empty( $pricing ) ) {
		wp_send_json_error( array( 'message' => 'Invalid params' ) );
	}
	wp_send_json_success( $pricing );
}
add_action( 'wp_ajax_snks_manual_booking_get_price', 'snks_ajax_manual_booking_get_price' );

/**
 * Get country codes defined in a therapist's pricing (45, 60, 90 minutes).
 * Returns array of [ 'code' => country_code, 'name' => display_name ].
 *
 * @param int $therapist_id Therapist user ID.
 * @return array
 */
function snks_manual_booking_therapist_pricing_countries( $therapist_id ) {
	$codes = array();
	foreach ( array( 45, 60, 90 ) as $period ) {
		$meta = get_user_meta( $therapist_id, $period . '_minutes_pricing', true );
		if ( ! is_array( $meta ) ) {
			continue;
		}
		foreach ( $meta as $item ) {
			if ( is_array( $item ) && ! empty( $item['country_code'] ) ) {
				$codes[ $item['country_code'] ] = true;
			}
		}
	}
	$codes = array_keys( $codes );
	if ( empty( $codes ) ) {
		return array();
	}
	$name_map = array();
	if ( function_exists( 'snks_get_country_dial_codes' ) ) {
		$dial_codes = snks_get_country_dial_codes();
		foreach ( $dial_codes as $code => $data ) {
			$name_map[ $code ] = isset( $data['name'] ) ? $data['name'] : $code;
		}
	}
	$extra = array( 'EU' => __( 'Europe', 'shrinks' ), 'US' => __( 'United States', 'shrinks' ), 'GB' => __( 'United Kingdom', 'shrinks' ), 'CA' => __( 'Canada', 'shrinks' ), 'AU' => __( 'Australia', 'shrinks' ) );
	$name_map = array_merge( $name_map, $extra );
	$result = array();
	foreach ( $codes as $code ) {
		$result[] = array(
			'code' => $code,
			'name' => isset( $name_map[ $code ] ) ? $name_map[ $code ] : $code,
		);
	}
	// Sort by name for consistent dropdown order.
	usort( $result, function( $a, $b ) {
		return strcasecmp( $a['name'], $b['name'] );
	} );
	return $result;
}

/**
 * Return therapist countries with price (used by AJAX and REST).
 *
 * @param int $therapist_id Therapist user ID.
 * @return array List of { code, name, price, currency_symbol }.
 */
function snks_manual_booking_data_therapist_countries( $therapist_id ) {
	$therapist_id = absint( $therapist_id );
	if ( ! $therapist_id ) {
		return array();
	}
	$list = snks_manual_booking_therapist_pricing_countries( $therapist_id );
	$has_others = get_user_meta( $therapist_id, '45_minutes_pricing_others', true ) !== ''
		|| get_user_meta( $therapist_id, '60_minutes_pricing_others', true ) !== ''
		|| get_user_meta( $therapist_id, '90_minutes_pricing_others', true ) !== '';
	if ( empty( $list ) && $has_others ) {
		$list = array( array( 'code' => 'OTHERS', 'name' => __( 'Other countries', 'shrinks' ) ) );
	} elseif ( $has_others ) {
		$list[] = array( 'code' => 'OTHERS', 'name' => __( 'Other countries', 'shrinks' ) );
	}
	$period = 45;
	foreach ( $list as &$item ) {
		$pricing = snks_get_ai_therapist_price( $therapist_id, $item['code'], $period );
		$item['price']           = isset( $pricing['original_price'] ) ? $pricing['original_price'] : 0;
		$item['currency_symbol'] = isset( $pricing['currency_symbol'] ) ? $pricing['currency_symbol'] : ( isset( $pricing['currency'] ) ? $pricing['currency'] : 'EGP' );
	}
	unset( $item );
	return $list;
}

/**
 * AJAX: Get countries from selected therapist's pricing (for country dropdown).
 */
function snks_ajax_manual_booking_get_therapist_countries() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$list = snks_manual_booking_data_therapist_countries( $therapist_id );
	wp_send_json_success( $list );
}
add_action( 'wp_ajax_snks_manual_booking_get_therapist_countries', 'snks_ajax_manual_booking_get_therapist_countries' );

/**
 * Return search-appointments data for change-appointment mode (used by AJAX and REST).
 *
 * @param string $q Search query (booking ID, phone, email).
 * @return array List of { booking_id, patient_id, therapist_id, patient_name, therapist_name, date_time }.
 */
function snks_manual_booking_data_search_appointments( $q ) {
	$q = sanitize_text_field( $q );
	if ( strlen( $q ) < 1 ) {
		return array();
	}
	global $wpdb;
	$timetable_table = $wpdb->prefix . 'snks_provider_timetable';
	$applications_table = $wpdb->prefix . 'therapist_applications';

	$like = '%' . $wpdb->esc_like( $q ) . '%';
	$numeric = absint( $q );

	$rows = array();
	if ( $numeric > 0 ) {
		$by_booking = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.ID as booking_id, t.client_id as patient_id, t.user_id as therapist_id, t.date_time
			 FROM {$timetable_table} t
			 WHERE t.ID = %d AND t.session_status = 'open' AND t.client_id > 0 AND t.order_id > 0",
			$numeric
		) );
		if ( $by_booking ) {
			$rows = $by_booking;
		}
	}
	if ( empty( $rows ) ) {
		$patient_ids = array();
		if ( $numeric > 0 ) {
			$patient_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'billing_phone' AND meta_value LIKE %s",
				$like
			) );
		} else {
			$by_email = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s OR user_login LIKE %s",
				$like,
				$like
			) );
			$by_meta = $wpdb->get_col( $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('billing_phone','billing_email','whatsapp') AND meta_value LIKE %s",
				$like
			) );
			$patient_ids = array_unique( array_merge( (array) $by_email, (array) $by_meta ) );
		}
		$patient_ids = array_filter( array_map( 'absint', $patient_ids ) );
		if ( ! empty( $patient_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $patient_ids ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT t.ID as booking_id, t.client_id as patient_id, t.user_id as therapist_id, t.date_time
				 FROM {$timetable_table} t
				 WHERE t.client_id IN ($placeholders) AND t.session_status = 'open'
				 AND t.client_id > 0 AND t.order_id > 0
				 ORDER BY t.date_time DESC LIMIT 20",
				$patient_ids
			) );
		}
	}

	foreach ( $rows as $r ) {
		$r->first_name = get_user_meta( $r->patient_id, 'billing_first_name', true );
		$r->last_name  = get_user_meta( $r->patient_id, 'billing_last_name', true );
		$r->therapist_name = $wpdb->get_var( $wpdb->prepare(
			"SELECT name FROM {$applications_table} WHERE user_id = %d LIMIT 1",
			$r->therapist_id
		) );
	}
	$result = array();
	foreach ( $rows as $r ) {
		$patient_name = trim( ( $r->first_name ?? '' ) . ' ' . ( $r->last_name ?? '' ) ) ?: '—';
		$result[] = array(
			'booking_id'    => $r->booking_id,
			'patient_id'    => $r->patient_id,
			'therapist_id'  => $r->therapist_id,
			'patient_name'  => $patient_name,
			'therapist_name' => $r->therapist_name ?: '—',
			'date_time'     => $r->date_time,
		);
	}
	return $result;
}

/**
 * Return list of manual bookings for manage tab (order_id, session_id, therapist_name, session_price, meeting_link, payment_method).
 * Includes both past (completed) and future (open) bookings with pagination.
 *
 * @param int $page    Page number (1-based).
 * @param int $per_page Number of results per page (default 100).
 * @return array{rows: array, total: int} rows = list of booking rows, total = total count.
 */
function snks_manual_booking_data_list_bookings( $page = 1, $per_page = 100 ) {
	global $wpdb;
	$timetable_table = $wpdb->prefix . 'snks_provider_timetable';
	$applications_table = $wpdb->prefix . 'therapist_applications';

	$page = max( 1, absint( $page ) );
	$per_page = max( 1, min( 500, absint( $per_page ) ) );
	$offset = ( $page - 1 ) * $per_page;

	$where = "t.session_status IN ('open', 'completed') AND t.settings LIKE '%admin_manual_booking%' AND t.client_id > 0";
	$count_query = "SELECT COUNT(*) FROM {$timetable_table} t WHERE {$where}";
	$total = (int) $wpdb->get_var( $count_query );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.ID AS booking_id, t.order_id, t.client_id AS patient_id, t.user_id AS therapist_id, t.date_time
		 FROM {$timetable_table} t
		 WHERE {$where}
		 ORDER BY t.date_time DESC
		 LIMIT %d OFFSET %d",
		$per_page,
		$offset
	) );
	if ( ! is_array( $rows ) ) {
		return array( 'rows' => array(), 'total' => 0 );
	}

	$result = array();
	foreach ( $rows as $r ) {
		$order_id = (int) $r->order_id;
		$order = $order_id ? wc_get_order( $order_id ) : null;
		$session_price = $order ? (float) $order->get_total() : 0;
		$payment_method = $order ? (string) $order->get_meta( 'admin_manual_payment_method' ) : '';
		$therapist_row = $wpdb->get_row( $wpdb->prepare(
			"SELECT name, phone, whatsapp FROM {$applications_table} WHERE user_id = %d LIMIT 1",
			(int) $r->therapist_id
		), ARRAY_A );
		$therapist_name  = ( $therapist_row && ! empty( $therapist_row['name'] ) ) ? $therapist_row['name'] : '—';
		$therapist_phone = '';
		if ( $therapist_row && ( ! empty( $therapist_row['phone'] ) || ! empty( $therapist_row['whatsapp'] ) ) ) {
			$therapist_phone = ! empty( $therapist_row['phone'] ) ? $therapist_row['phone'] : $therapist_row['whatsapp'];
		}
		if ( '' === $therapist_phone && (int) $r->therapist_id ) {
			$therapist_phone = get_user_meta( $r->therapist_id, 'billing_phone', true ) ?: '';
		}
		$meeting_link = function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( (int) $r->booking_id ) : '';
		$patient_first = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_first_name', true ) : '';
		$patient_last  = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_last_name', true ) : '';
		$patient_whatsapp = '';
		if ( (int) $r->patient_id ) {
			$patient_whatsapp = get_user_meta( $r->patient_id, 'whatsapp', true );
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_whatsapp', true );
			}
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_phone', true );
			}
		}
		$patient_name  = trim( $patient_first . ' ' . $patient_last ) ?: '—';

		$result[] = array(
			'order_id'        => $order_id,
			'session_id'      => (int) $r->booking_id,
			'therapist_name'  => $therapist_name,
			'therapist_phone' => $therapist_phone,
			'session_price'   => $session_price,
			'meeting_link'    => $meeting_link,
			'payment_method'  => $payment_method ?: '—',
			'patient_id'      => (int) $r->patient_id,
			'patient_name'    => $patient_name,
			'patient_whatsapp' => $patient_whatsapp,
			'therapist_id'    => (int) $r->therapist_id,
			'date_time'       => $r->date_time,
		);
	}
	return array( 'rows' => $result, 'total' => $total );
}

/**
 * Return open (booked) Jalsah AI slots for a given date. Same row shape as list_bookings for consistent table display.
 *
 * @param string $date     Date in Y-m-d format.
 * @param int    $page     Page number (1-based).
 * @param int    $per_page Results per page (default 100).
 * @return array{rows: array, total: int}
 */
function snks_manual_booking_data_open_slots( $date, $page = 1, $per_page = 100 ) {
	if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return array( 'rows' => array(), 'total' => 0 );
	}

	global $wpdb;
	$timetable_table   = $wpdb->prefix . 'snks_provider_timetable';
	$applications_table = $wpdb->prefix . 'therapist_applications';

	$page = max( 1, absint( $page ) );
	$per_page = max( 1, min( 500, absint( $per_page ) ) );
	$offset = ( $page - 1 ) * $per_page;

	$where = "t.session_status = 'open' AND t.client_id > 0 AND t.settings LIKE '%ai_booking%' AND DATE(t.date_time) = %s";
	$count_query = "SELECT COUNT(*) FROM {$timetable_table} t WHERE {$where}";
	$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $date ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.ID AS booking_id, t.order_id, t.client_id AS patient_id, t.user_id AS therapist_id, t.date_time
		 FROM {$timetable_table} t
		 WHERE {$where}
		 ORDER BY t.date_time ASC
		 LIMIT %d OFFSET %d",
		$date,
		$per_page,
		$offset
	) );
	if ( ! is_array( $rows ) ) {
		return array( 'rows' => array(), 'total' => 0 );
	}

	$result = array();
	foreach ( $rows as $r ) {
		$order_id = (int) $r->order_id;
		$order = $order_id ? wc_get_order( $order_id ) : null;
		$session_price = $order ? (float) $order->get_total() : 0;
		$payment_method = $order ? (string) $order->get_meta( 'admin_manual_payment_method' ) : '';
		$therapist_row = $wpdb->get_row( $wpdb->prepare(
			"SELECT name, phone, whatsapp FROM {$applications_table} WHERE user_id = %d LIMIT 1",
			(int) $r->therapist_id
		), ARRAY_A );
		$therapist_name  = ( $therapist_row && ! empty( $therapist_row['name'] ) ) ? $therapist_row['name'] : '—';
		$therapist_phone = '';
		if ( $therapist_row && ( ! empty( $therapist_row['phone'] ) || ! empty( $therapist_row['whatsapp'] ) ) ) {
			$therapist_phone = ! empty( $therapist_row['phone'] ) ? $therapist_row['phone'] : $therapist_row['whatsapp'];
		}
		if ( '' === $therapist_phone && (int) $r->therapist_id ) {
			$therapist_phone = get_user_meta( $r->therapist_id, 'billing_phone', true ) ?: '';
		}
		$meeting_link = function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( (int) $r->booking_id ) : '';
		$patient_first = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_first_name', true ) : '';
		$patient_last  = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_last_name', true ) : '';
		$patient_whatsapp = '';
		if ( (int) $r->patient_id ) {
			$patient_whatsapp = get_user_meta( $r->patient_id, 'whatsapp', true );
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_whatsapp', true );
			}
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_phone', true );
			}
		}
		$patient_name = trim( $patient_first . ' ' . $patient_last ) ?: '—';

		// Total Jalsah AI orders for this patient (same query as dashboard Open Slots page).
		$total_patient_orders = 0;
		if ( (int) $r->patient_id && function_exists( 'wc_get_orders' ) ) {
			$countable_statuses = array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending' );
			$ai_orders = wc_get_orders( array(
				'customer_id' => (int) $r->patient_id,
				'status'      => $countable_statuses,
				'limit'       => -1,
				'return'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'from_jalsah_ai',
						'value'   => array( '1', 'true', 'yes', true, 1 ),
						'compare' => 'IN',
					),
				),
			) );
			$total_patient_orders = is_array( $ai_orders ) ? count( $ai_orders ) : 0;
		}

		$result[] = array(
			'order_id'              => $order_id,
			'session_id'            => (int) $r->booking_id,
			'therapist_name'        => $therapist_name,
			'therapist_phone'       => $therapist_phone,
			'session_price'         => $session_price,
			'meeting_link'          => $meeting_link,
			'payment_method'        => $payment_method ?: '—',
			'patient_id'            => (int) $r->patient_id,
			'patient_name'          => $patient_name,
			'patient_whatsapp'      => $patient_whatsapp,
			'therapist_id'          => (int) $r->therapist_id,
			'date_time'             => $r->date_time,
			'total_patient_orders'  => $total_patient_orders,
		);
	}
	return array( 'rows' => $result, 'total' => $total );
}

/**
 * Return bookings for a given phone: if phone belongs to a therapist, return that therapist's bookings;
 * if it belongs to a patient, return that patient's bookings. Applies to any booking type (manual or AI).
 * Includes both past (completed) and future (open) bookings with pagination.
 *
 * @param string $phone    Phone number (digits, optional country code).
 * @param int    $page     Page number (1-based).
 * @param int    $per_page Results per page (default 100).
 * @return array{role: string, bookings: array, total: int, therapist_settings?: array, patient_name?: string}
 */
function snks_manual_booking_data_bookings_by_phone( $phone, $page = 1, $per_page = 100 ) {
	$phone = preg_replace( '/\D/', '', sanitize_text_field( $phone ) );
	if ( strlen( $phone ) < 5 ) {
		return array( 'role' => '', 'bookings' => array(), 'total' => 0 );
	}

	$page = max( 1, absint( $page ) );
	$per_page = max( 1, min( 500, absint( $per_page ) ) );
	$offset = ( $page - 1 ) * $per_page;

	global $wpdb;
	$timetable_table   = $wpdb->prefix . 'snks_provider_timetable';
	$applications_table = $wpdb->prefix . 'therapist_applications';
	$like = '%' . $wpdb->esc_like( $phone ) . '%';

	// Prefer therapist: match therapist_applications.phone or .whatsapp, or user billing_phone/whatsapp for that user.
	$therapist_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ta.user_id FROM {$applications_table} ta
		 WHERE ta.status = 'approved'
		 AND ( ta.phone LIKE %s OR ta.whatsapp LIKE %s )
		 LIMIT 1",
		$like,
		$like
	) );
	if ( ! $therapist_id ) {
		$therapist_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT u.ID FROM {$wpdb->users} u
			 INNER JOIN {$applications_table} ta ON ta.user_id = u.ID AND ta.status = 'approved'
			 INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID AND m.meta_key IN ('billing_phone','billing_whatsapp','whatsapp') AND m.meta_value LIKE %s
			 LIMIT 1",
			$like
		) );
	}

	$therapist_settings = array();
	$patient_ids = array();
	$role = '';

	if ( $therapist_id ) {
		if ( function_exists( 'snks_doctor_settings' ) ) {
			$doctor_settings    = snks_doctor_settings( $therapist_id );
			$therapist_settings = array(
				'block_if_before_number' => isset( $doctor_settings['block_if_before_number'] ) ? $doctor_settings['block_if_before_number'] : '',
				'block_if_before_unit'   => isset( $doctor_settings['block_if_before_unit'] ) ? $doctor_settings['block_if_before_unit'] : '',
				'form_days_count'        => isset( $doctor_settings['form_days_count'] ) ? $doctor_settings['form_days_count'] : '',
			);
		}
		$where = "t.session_status IN ('open', 'completed') AND t.client_id > 0 AND t.user_id = %d";
		$count_query = "SELECT COUNT(*) FROM {$timetable_table} t WHERE {$where}";
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $therapist_id ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.ID AS booking_id, t.order_id, t.client_id AS patient_id, t.user_id AS therapist_id, t.date_time, t.settings
			 FROM {$timetable_table} t
			 WHERE {$where}
			 ORDER BY t.date_time DESC
			 LIMIT %d OFFSET %d",
			$therapist_id,
			$per_page,
			$offset
		) );
		$role = 'therapist';
	} else {
		$patient_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key IN ('billing_phone','billing_whatsapp','whatsapp') AND meta_value LIKE %s
			 LIMIT 5",
			$like
		) );
		$patient_ids = array_filter( array_map( 'absint', $patient_ids ) );
		if ( empty( $patient_ids ) ) {
			return array( 'role' => '', 'bookings' => array(), 'total' => 0 );
		}
		$placeholders = implode( ',', array_fill( 0, count( $patient_ids ), '%d' ) );
		$where = "t.session_status IN ('open', 'completed') AND t.client_id > 0 AND t.client_id IN ($placeholders)";
		$count_query = "SELECT COUNT(*) FROM {$timetable_table} t WHERE {$where}";
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $patient_ids ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.ID AS booking_id, t.order_id, t.client_id AS patient_id, t.user_id AS therapist_id, t.date_time, t.settings
			 FROM {$timetable_table} t
			 WHERE {$where}
			 ORDER BY t.date_time DESC
			 LIMIT %d OFFSET %d",
			array_merge( $patient_ids, array( $per_page, $offset ) )
		) );
		$role = 'patient';
	}

	if ( ! is_array( $rows ) ) {
		$out = array(
			'role'               => $role,
			'bookings'           => array(),
			'total'               => 0,
			'therapist_settings' => $therapist_settings,
		);
		if ( $role === 'patient' && ! empty( $patient_ids ) ) {
			$first_patient_id = (int) $patient_ids[0];
			$first = get_user_meta( $first_patient_id, 'billing_first_name', true );
			$last  = get_user_meta( $first_patient_id, 'billing_last_name', true );
			$out['patient_name'] = trim( $first . ' ' . $last ) ?: '—';
		}
		return $out;
	}

	$result = array();
	foreach ( $rows as $r ) {
		$order_id = (int) $r->order_id;
		$order = $order_id ? wc_get_order( $order_id ) : null;
		$session_price = $order ? (float) $order->get_total() : 0;
		$payment_method = $order ? (string) $order->get_meta( 'admin_manual_payment_method' ) : '';
		$therapist_row = $wpdb->get_row( $wpdb->prepare(
			"SELECT name, phone, whatsapp FROM {$applications_table} WHERE user_id = %d LIMIT 1",
			(int) $r->therapist_id
		), ARRAY_A );
		$therapist_name  = ( $therapist_row && ! empty( $therapist_row['name'] ) ) ? $therapist_row['name'] : '—';
		$therapist_phone = '';
		if ( $therapist_row && ( ! empty( $therapist_row['phone'] ) || ! empty( $therapist_row['whatsapp'] ) ) ) {
			$therapist_phone = ! empty( $therapist_row['phone'] ) ? $therapist_row['phone'] : $therapist_row['whatsapp'];
		}
		if ( '' === $therapist_phone && (int) $r->therapist_id ) {
			$therapist_phone = get_user_meta( $r->therapist_id, 'billing_phone', true ) ?: '';
		}
		$meeting_link = function_exists( 'snks_get_meeting_shortlink' ) ? snks_get_meeting_shortlink( (int) $r->booking_id ) : '';
		$patient_first = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_first_name', true ) : '';
		$patient_last  = (int) $r->patient_id ? get_user_meta( $r->patient_id, 'billing_last_name', true ) : '';
		$patient_whatsapp = '';
		if ( (int) $r->patient_id ) {
			$patient_whatsapp = get_user_meta( $r->patient_id, 'whatsapp', true );
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_whatsapp', true );
			}
			if ( '' === $patient_whatsapp ) {
				$patient_whatsapp = get_user_meta( $r->patient_id, 'billing_phone', true );
			}
		}
		$patient_name  = trim( $patient_first . ' ' . $patient_last ) ?: '—';
		$booking_type  = ( strpos( $r->settings, 'admin_manual_booking' ) !== false ) ? 'manual' : 'ai';

		$result[] = array(
			'order_id'         => $order_id,
			'session_id'       => (int) $r->booking_id,
			'therapist_name'   => $therapist_name,
			'therapist_phone'  => $therapist_phone,
			'session_price'   => $session_price,
			'meeting_link'    => $meeting_link,
			'payment_method'  => $payment_method ?: '—',
			'patient_id'      => (int) $r->patient_id,
			'patient_name'    => $patient_name,
			'patient_whatsapp' => $patient_whatsapp,
			'therapist_id'    => (int) $r->therapist_id,
			'date_time'       => $r->date_time,
			'booking_type'    => $booking_type,
		);
	}
	$out = array(
		'role'               => $role,
		'bookings'           => $result,
		'total'              => $total,
		'therapist_settings' => $therapist_settings,
	);
	if ( $role === 'patient' && ! empty( $patient_ids ) ) {
		$first_patient_id = (int) $patient_ids[0];
		$first = get_user_meta( $first_patient_id, 'billing_first_name', true );
		$last  = get_user_meta( $first_patient_id, 'billing_last_name', true );
		$out['patient_name'] = trim( $first . ' ' . $last ) ?: '—';
	}
	return $out;
}

/**
 * AJAX: Search appointments for change mode.
 */
function snks_ajax_manual_booking_search_appointments() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	$user = wp_get_current_user();
	$roles = is_object( $user ) ? (array) $user->roles : array();
	if ( ! current_user_can( 'manage_options' ) && ! in_array( 'secretary', $roles, true ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
	$result = snks_manual_booking_data_search_appointments( $q );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_search_appointments', 'snks_ajax_manual_booking_search_appointments' );
