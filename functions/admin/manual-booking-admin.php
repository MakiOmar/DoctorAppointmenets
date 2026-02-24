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
 * Manual Booking admin page callback.
 */
function snks_jalsah_ai_manual_booking_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
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
				$patient_id   = isset( $_POST['patient_id'] ) ? absint( $_POST['patient_id'] ) : 0;
				$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
				$slot_id      = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
				$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : 'EG';
				$amount       = isset( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : null;
				$amount_override = ( $amount !== '' && is_numeric( $amount ) && floatval( $amount ) > 0 ) ? floatval( $amount ) : null;
				$first_name   = isset( $_POST['patient_first_name'] ) ? sanitize_text_field( $_POST['patient_first_name'] ) : '';
				$last_name    = isset( $_POST['patient_last_name'] ) ? sanitize_text_field( $_POST['patient_last_name'] ) : '';
				$result = snks_process_admin_manual_booking( $patient_id, $therapist_id, $slot_id, $country_code, $amount_override, $first_name, $last_name );
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
								<input type="text" id="patient_search" class="regular-text" placeholder="<?php esc_attr_e( 'Enter the phone number', 'shrinks' ); ?>" autocomplete="off">
								<div id="patient_results" class="snks-patient-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; margin-top:2px; background:#fff; border:1px solid #8c8f94; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; z-index:100;"></div>
							</div>
							<input type="hidden" name="patient_id" id="patient_id" required>
							<span id="patient_display" style="margin-right: 10px; font-weight:500;"></span>
							<button type="button" id="patient_clear_btn" class="button button-small" style="display:none;"><?php esc_html_e( 'Clear', 'shrinks' ); ?></button>
							<p class="description"><?php esc_html_e( 'Enter phone number to search existing patient or create a new one (password: 123456).', 'shrinks' ); ?></p>
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
							<input type="date" name="booking_date" id="booking_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
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

	<script>
	jQuery(function($) {
		var therapistId = $('#therapist_id');
		var bookingDate = $('#booking_date');
		var slotSelect = $('#slot_id');
		var countrySelect = $('#country_code');
		var amountInput = $('#amount');

		var therapistsData = <?php echo wp_json_encode( array_map( function( $t ) {
			$name = $t->name ?: $t->name_en ?: '';
			$name_en = $t->name_en ?: '';
			$phone = $t->phone ?: $t->whatsapp ?: '';
			return array( 'id' => (int) $t->user_id, 'name' => $name, 'name_en' => $name_en, 'phone' => $phone );
		}, $therapists ) ); ?>;

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
						loadSlots();
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
			loadSlots();
		});

		function resetCountries() {
			var html = '<option value=""><?php echo esc_js( __( '— Select therapist first —', 'shrinks' ) ); ?></option>';
			countrySelect.html(html);
			$('#calculated_price').text('');
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
						html += '<option value="' + (c.code || '').replace(/"/g, '&quot;') + '">' + (c.name || c.code || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
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
					$('#calculated_price').text('<?php echo esc_js( __( 'Price:', 'shrinks' ) ); ?> ' + res.data.original_price + ' ' + (res.data.currency_symbol || ''));
				}
			});
		}

		therapistId.add(bookingDate).on('change', loadSlots);
		slotSelect.on('change', updatePrice);
		countrySelect.on('change', updatePrice);

		var patientSearchTimer;
		$('#patient_search').on('input', function() {
			var q = $(this).val().trim();
			if (q.length < 2) {
				$('#patient_id').val('');
				$('#patient_display').text('');
				$('#patient_first_name').val('');
				$('#patient_last_name').val('');
				$('#patient_results').hide().empty();
				$('#patient_clear_btn').hide();
				return;
			}
			$('#patient_results').hide().empty();
			clearTimeout(patientSearchTimer);
			patientSearchTimer = setTimeout(function() {
				$.post(ajaxurl, {
					action: 'snks_manual_booking_search_patient',
					nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
					q: q
				}, function(res) {
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
			$(this).hide();
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('#patient_search, #patient_results').length) {
				$('#patient_results').hide();
			}
			if (!$(e.target).closest('#therapist_search, #therapist_results').length) {
				$('#therapist_results').hide();
			}
		});

		<?php if ( 'change' === $active_tab ) : ?>
		var changeTherapistId = null;
		var preselectId = <?php echo $preselect_booking_id ? (int) $preselect_booking_id : 0; ?>;
		if (preselectId) {
			$.post(ajaxurl, {
				action: 'snks_manual_booking_search_appointments',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				q: String(preselectId)
			}, function(res) {
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
			$.post(ajaxurl, {
				action: 'snks_manual_booking_search_appointments',
				nonce: '<?php echo esc_js( wp_create_nonce( 'manual_booking' ) ); ?>',
				q: q
			}, function(res) {
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
 * Username and email are derived from phone; password defaults to 123456.
 *
 * @param string $phone Phone number (stored as-is in billing_phone).
 * @return int|WP_Error User ID on success, WP_Error on failure.
 */
function snks_manual_booking_create_patient_from_phone( $phone ) {
	$phone = sanitize_text_field( $phone );
	$digits = preg_replace( '/\D/', '', $phone );
	// Require at least 5 digits to treat as phone.
	if ( strlen( $digits ) < 5 ) {
		return new WP_Error( 'invalid_phone', __( 'Please enter a valid phone number (at least 5 digits).', 'shrinks' ) );
	}

	$base_login = 'phone_' . $digits;
	$base_email = $digits . '@jalsah.app';
	$login = $base_login;
	$email = $base_email;
	$suffix = 0;
	while ( username_exists( $login ) || email_exists( $email ) ) {
		$suffix++;
		$login = $base_login . '_' . $suffix;
		$email = $digits . '_' . $suffix . '@jalsah.app';
	}

	$default_password = '123456';
	$display_name = sprintf( /* translators: %s: phone number */ __( 'Patient %s', 'shrinks' ), $phone );

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

	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'whatsapp', $phone );

	return $user_id;
}

/**
 * AJAX: Search patient by phone (or email). If no user found and input is a phone number, create the user.
 */
function snks_ajax_manual_booking_search_patient() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
	if ( strlen( $q ) < 2 ) {
		wp_send_json_success( array() );
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
		$name  = trim( $first . ' ' . $last ) ?: $u->display_name;
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
			$name = $u ? ( trim( $first . ' ' . $last ) ?: $u->display_name ) : sprintf( __( 'Patient %s', 'shrinks' ), $q );
			$result[] = array(
				'id'         => (int) $new_user_id,
				'email'      => $u ? $u->user_email : '',
				'name'       => $name,
				'first_name' => $first,
				'last_name'  => $last,
			);
		}
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_search_patient', 'snks_ajax_manual_booking_search_patient' );

/**
 * AJAX: Get available slots for therapist and date.
 */
function snks_ajax_manual_booking_get_slots() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$date         = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
	if ( ! $therapist_id || ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wp_send_json_error( array( 'message' => 'Invalid params' ) );
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
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_get_slots', 'snks_ajax_manual_booking_get_slots' );

/**
 * AJAX: Get price for therapist, country, period.
 */
function snks_ajax_manual_booking_get_price() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : 'EG';
	$period       = isset( $_POST['period'] ) ? absint( $_POST['period'] ) : 45;
	if ( ! $therapist_id ) {
		wp_send_json_error( array( 'message' => 'Invalid params' ) );
	}

	$pricing = snks_get_ai_therapist_price( $therapist_id, $country_code, $period );
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
 * AJAX: Get countries from selected therapist's pricing (for country dropdown).
 */
function snks_ajax_manual_booking_get_therapist_countries() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}
	$therapist_id = isset( $_POST['therapist_id'] ) ? absint( $_POST['therapist_id'] ) : 0;
	if ( ! $therapist_id ) {
		wp_send_json_success( array() );
		return;
	}
	$list = snks_manual_booking_therapist_pricing_countries( $therapist_id );
	// If therapist has "others" price but no specific countries, show "Others" only.
	$has_others = get_user_meta( $therapist_id, '45_minutes_pricing_others', true ) !== ''
		|| get_user_meta( $therapist_id, '60_minutes_pricing_others', true ) !== ''
		|| get_user_meta( $therapist_id, '90_minutes_pricing_others', true ) !== '';
	if ( empty( $list ) && $has_others ) {
		$list = array( array( 'code' => 'OTHERS', 'name' => __( 'Other countries', 'shrinks' ) ) );
	} elseif ( $has_others ) {
		$list[] = array( 'code' => 'OTHERS', 'name' => __( 'Other countries', 'shrinks' ) );
	}
	wp_send_json_success( $list );
}
add_action( 'wp_ajax_snks_manual_booking_get_therapist_countries', 'snks_ajax_manual_booking_get_therapist_countries' );

/**
 * AJAX: Search appointments for change mode.
 */
function snks_ajax_manual_booking_search_appointments() {
	check_ajax_referer( 'manual_booking', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
	if ( strlen( $q ) < 1 ) {
		wp_send_json_success( array() );
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
			 WHERE t.ID = %d AND t.session_status = 'open' AND t.settings LIKE %s",
			$numeric,
			'%admin_manual_booking%'
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
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ('billing_phone','whatsapp') AND meta_value LIKE %s",
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
				 AND t.settings LIKE %s ORDER BY t.date_time DESC LIMIT 20",
				array_merge( $patient_ids, array( '%admin_manual_booking%' ) )
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
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_snks_manual_booking_search_appointments', 'snks_ajax_manual_booking_search_appointments' );
