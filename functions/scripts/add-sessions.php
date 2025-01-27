<?php
/**
 * Add sessions scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				var disabledOptions = [];
				var weekDays = ['sun','mon','tue','wed','thu','fri','sat'];
				function showErrorpopup( msg ) {
					if ( $('.shrinks-error').length > 0 ) {
						$('#error-container').text(msg);
						$('html').animate(
							{
							scrollTop: $('.shrinks-error').offset().top - 150,
							},
							800 //speed
						);
						$('.trigger-error').trigger('click');
					}
				}

				function applySelect2() {
					$('select[data-field-name=country_code]').not('.select2-hidden-accessible').select2({width: '100%'});
				}
				function checkHoursOverlap(tos, selectedHour, parentWrapper) {
					const timeSelectedHour = new Date(`1970-01-01T${selectedHour}`); // DateTime for selectedHour.
					let overlappedHours = [];
					let startingHours   = [];
					$('select[data-field-name="appointment_hour"]', parentWrapper.closest('.accordion-content')).each(
						function() {
							if ( '' !== $(this).val() ){
								startingHours.push( $(this).val() );
							}
						}
					);
					startingHours.forEach( function( startingHour ) {
						var timeStartingHour =  new Date(`1970-01-01T${startingHour}`);
						if ( timeSelectedHour.getTime() < timeStartingHour.getTime() ) {
							tos.forEach( function( to ) {
								timeTo = new Date(`1970-01-01T${to}`);
								if ( timeTo.getTime() > timeStartingHour.getTime() && !overlappedHours.includes(to) ) {
									overlappedHours.push( to );
								}
							} );
						}
					} );

					return overlappedHours;
				}
				function expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper, repeaterName) {
						if ( '' === selectedPeriods || '' === selectedHour ) {
							parentWrapper.find('.expected-hourse').html('');
							return;
						}
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'expected_hours_output_nonce' ) ); ?>';
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								selectedPeriods: selectedPeriods,
								selectedHour: selectedHour,
								nonce:nonce,
								action    : 'expected_hours_output',
							},
							success: function(response) {
								var repeaterName = parentWrapper.closest('.accordion-content').data('field-name');
								$('select[data-field-name=appointment_hour] option', parentWrapper.closest('.accordion-content')).each( function() {
									if ( ! $(this).is(':selected') && $(this).val() >= response.lowesttHour && $(this).val() < response.largestHour && disabledOptions[repeaterName] && ! disabledOptions[repeaterName].includes( $(this).val() ) ) {
										$(this).prop('disabled', false);
									}
								} );
								parentWrapper.find('.expected-hourse').html( response.resp );
								const hoursOverlaps = checkHoursOverlap(response.tos, selectedHour, parentWrapper);
								if ( hoursOverlaps.length > 0 ) {
									var className;
									hoursOverlaps.forEach(function(item, index) {
										className = item.replace(":", "-");
										if ( ! $( '.' + className, parentWrapper.closest('.accordion-content') ).hasClass('shrinks-error') ) {
											$( '.' + className, parentWrapper.closest('.accordion-content') ).addClass('shrinks-error');
										}
									});
									setTimeout(() => {
										showErrorpopup('هناك تداخل في المواعيد!');
										$('#jet-popup-2219').removeClass('jet-popup--hide-state').addClass('jet-popup--show-state');
									}, 200);
								} else {
									var hourDisable = [];
									$('.shrinks-error').removeClass('shrinks-error');
									$('select[data-field-name=appointment_hour] option', parentWrapper.closest('.accordion-content')).each(function () {
										const optionValue = $(this).val();
										const lowestHour = response.lowesttHour; // e.g., "23:45"
										const largestHour = response.largestHour; // e.g., "00:30"

										// Base dates for the same day and next day
										const baseDate = "2025-01-01";
										const nextDayBaseDate = "2025-01-02";

										// Parse option time
										const optionDate = new Date(`${baseDate}T${optionValue}`);
										const lowestDate = new Date(`${baseDate}T${lowestHour}`);
										const largestDate =
											largestHour < lowestHour
												? new Date(`${nextDayBaseDate}T${largestHour}`) // largestHour falls on the next day
												: new Date(`${baseDate}T${largestHour}`);

										// Adjust option date for next day if it loops over midnight
										if (optionValue < lowestHour) {
											optionDate.setDate(optionDate.getDate() + 1); // Move optionDate to the next day
										}

										// Disable options between lowestHour (inclusive) and largestHour (exclusive)
										if (!$(this).is(':selected') && optionDate >= lowestDate && optionDate < largestDate) {
											$(this).prop('disabled', true);
											if (!hourDisable.includes(optionValue)) {
												hourDisable.push(optionValue);
											}
										}
									});



									if (hourDisable && hourDisable.length > 0) {
										disabledOptions[repeaterName] = [];
										disabledOptions[repeaterName][selectedHour] = [];
										disabledOptions[repeaterName][selectedHour] = hourDisable;
									}
								}
							}
						});
				}
				flatpickr.localize(flatpickr.l10ns.ar);
				function flatPickrInput( disabledDays = false ) {
					$('input[data-field-name=off_days]').each(
						function() {
							var existingValue = $(this).val();
							var datesArray = existingValue.split(',');
							flatpickr(
								this,
								{
									"defaultDate": datesArray,
									"disable"    : [
										function(date) {
											var currentDate = new Date();
											currentDate.setHours(0, 0, 0, 0);
											date.setHours(0, 0, 0, 0);
											// To disable sunday date.getDay() === 0
											if ( disabledDays ) {
												return ( disabledDays.includes( date.getDay() ) || currentDate > date );
											} else {
												// return true to disable.
												return ( currentDate > date );
											}
										}
									],
									"locale"     : {
										"firstDayOfWeek": 6, // start week on Monday
										
									},
									"mode"       : 'multiple'
								}
							);
						}
						
					);
				}
				function disableEmptyDays() {
					var disabledDays = [];
					$( '.jet-form-builder-repeater__items' ).each(
						function(){
							if ( '' === $(this).html() ) {
								var closestRepeater = $( this ).closest( '.jet-form-builder-repeater' );
								var repeaterName    = closestRepeater.attr('name');
								var weekDay         = repeaterName.replace('_timetable','');
								var dayIndex        = weekDays.indexOf( weekDay );
								disabledDays.push(dayIndex);
							}
						}
					);
					flatPickrInput( disabledDays );
				}
				disableEmptyDays();

				// Apply Select2 to existing selects
				applySelect2();

				// Mutation Observer to handle dynamically injected selects
				const observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(mutation) {
						mutation.addedNodes.forEach(function(node) {
							if ($(node).is('select') || $(node).find('select').length) {
								applySelect2();
							}
						});
					});
				});

				// Configure and start the observer
				observer.observe(document.body, {
					childList: true,
					subtree: true
			    });
			function initializeFlatpickr(enabledDay) {
					$('input[name=date], input[data-field-name=date]').each(function() {
						var offDays = $('#doctor-off-days').val().split(','); // Days to be disabled
						
						// Initialize Flatpickr
						flatpickr(this, {
							dateFormat: 'Y-m-d',
							disable: [
								function(date) {
									// Disable specific dates from offDays variable
									var formattedDate = flatpickr.formatDate(date, 'Y-m-d');
									return offDays.includes(formattedDate);
								}
							],
							enable: [
								function(date) {
									// Enable only the specific day of the week
									return date.getDay() === enabledDay;
								}
							],
							minDate: "today",  // Disable past dates
							onOpen: function(selectedDates, dateStr, instance) {
								// Set a custom ID or class for the Flatpickr container
								instance.calendarContainer.id = 'flatpickr-' + $(this.element).attr('id');
							}
						});
					});
				}
			$(document).on('click', '.custom-timetabl-trigger', function(e) {
				e.preventDefault();
				$('#day-label').text( $(this).data('day-label') );
				$('input[name=day]', $('.day-specific-form')).val( $(this).data('day') );
				initializeFlatpickr($(this).data('day-index'));
				$('#custom-timetabl-trigger').trigger( 'click' );
			});
			$(document).on('click', '.custom-timetable-submit', function(e) {
				e.preventDefault(); // Prevent form default submission

				// Collect form data
				var appHour = $('#app_hour').val();
				var appChosenPeriod = $('#app_choosen_period').val();
				var date = $('#date').val();
				var appClinic = $('#app_clinic').val();
				var appAttendanceType = $('#app_attendance_type').val();
				var day = $('input[name="day"]').val();

				// Validate form fields
				if (!appHour || !appChosenPeriod || !date || !appAttendanceType || !day ) {
					// Show error alert using SweetAlert
					Swal.fire({
						icon: 'error',
						title: 'خطأ',
						text: 'يرجى تعبئة جميع الحقول المطلوبة.',
						confirmButtonText: 'غلق'
					});
					return; // Exit the function to prevent AJAX submission
				}
				// Validate form fields
				if ( ( appAttendanceType === 'offline' || appAttendanceType === 'both' ) && !appClinic ) {
					// Show error alert using SweetAlert
					Swal.fire({
						icon: 'error',
						title: 'خطأ',
						text: 'يرجى  تحديد العيادة.',
						confirmButtonText: 'غلق'
					});
					return; // Exit the function to prevent AJAX submission
				}

				// All fields are valid, proceed with the AJAX request
				var formData = new FormData();
				formData.append('action', 'create_custom_timetable'); // Action name
				formData.append('app_hour', appHour); // Selected hour
				formData.append('app_choosen_period', appChosenPeriod); // Chosen period
				formData.append('date', date); // Selected date
				formData.append('app_clinic', appClinic); // Clinic
				formData.append('app_attendance_type', appAttendanceType); // Attendance type
				formData.append('day', day); // Day field

				// Send the AJAX request
				$.ajax({
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // URL for AJAX requests in WordPress
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						// Check if there is a conflict in the response
						if (response.success) {
							// Show success alert using SweetAlert
							Swal.fire({
								icon: 'success',
								title: 'تم الإدخال بنجاح',
								text: 'تم إدخال الموعد بنجاح!',
								confirmButtonText: 'غلق'
							}).then((result) => {
								if (result.isConfirmed) {
									$.ajax({
										url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // WordPress AJAX URL
										type: 'POST',
										data: {
											action: 'get_preview_tables',
										},
										success: function(data) {
											$('#preview-timetables').html(data);
										}
									});
								}
							});
						} else {
							// Show error alert using SweetAlert
							Swal.fire({
								icon: 'error',
								title: 'خطأ',
								text: response.data.message, // Error message from server
								confirmButtonText: 'غلق'
							});
						}
					},
					error: function(error) {
						// Show a generic error alert in case the AJAX request fails
						Swal.fire({
							icon: 'error',
							title: 'حدث خطأ',
							text: 'يرجى المحاولة مرة أخرى.',
							confirmButtonText: 'غلق'
						});
					},
					complete: function() {
						// Reset form values on completion of AJAX
						$('#app_hour').val('');
						$('#app_choosen_period').val('');
						$('#date').val('');
						$('#app_clinic').val('');
						$('#app_attendance_type').val('');
						$('input[name="day"]').val('');
					}

				});
			});


				$('body').on(
					'click',
					'.accordion-heading',
					function () {
						$(this).toggleClass('active-accordion');
						$(this).parent('.field-type-heading-field').next('.field-type-repeater-field').find('.accordion-content').toggleClass('accordion-content-active');
						$('.accordion-content').each(
							function () {
								if ( $( this ).hasClass('accordion-content-active') ) {
									$( this ).slideDown();
								} else {
									$( this ).slideUp();
								}
							}
						);
					}
				);

				$(document).on(
					'change',
					'select[data-field-name=appointment_hour]',
					function() {
						let parentWrapper   = $(this).closest( '.jet-form-builder-repeater__row-fields' );
						let periods   = $('select[data-field-name=appointment_choosen_period]', parentWrapper);
						if ( $(this).val() == '23:15' ) {
							periods.find("option").each(function () {
								if ($(this).val().includes("60")) {
									$(this).prop("disabled", true); // Disable the option
								} else {
									$(this).prop("disabled", false);
								}
							});
						}
						if ( $(this).val() == '23:30' ) {
							periods.find("option").each(function () {
								let value = $(this).val();
								if (value.includes("60") || value.includes("45")) {
									$(this).prop("disabled", true); // Disable the option
								} else {
									$(this).prop("disabled", false);
								}
							});
						}
						if ( $(this).val() == '23:00' ) {
							periods.find("option").each(function () {
								$(this).prop("disabled", false); // Disable the option
							});
						}
						
						setTimeout(function(){
							$('select[data-field-name=appointment_choosen_period]', parentWrapper).trigger('change');
						},200)
					}
				);
				$(document).on(
					'click',
					'.jet-form-builder-repeater__new',
					function(){
						disableEmptyDays();
						var container = $(this).closest('.accordion-content');

						setTimeout(
							function() {
								$('select[data-field-name=appointment_choosen_period]', container).trigger('change');
								$('select[data-field-name=appointment_hour] option', container).each( function() {
									if ( ! $(this).is(':selected') && disabledOptions[container.data('field-name')] && disabledOptions[container.data('field-name')].includes( $(this).val() ) ) {
										$(this).prop('disabled', true)
									} else {
										$(this).prop('disabled', false)
									}
								} );
							},
							200
						)
					}
				);

				$(document).on(
					'click',
					'#insert-timetable',
					function( e ) {
						e.preventDefault();
						$("#insert-timetable-msg").text('');
						Swal.fire({
							title: 'هل أنت متأكد؟',
							text: "لا يمكنك التراجع بعد ذلك!",
							icon: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'نعم، أنا متأكد',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (!result.isConfirmed) {
								return;
							}
							// Perform nonce check.
							var nonce     = '<?php echo esc_html( wp_create_nonce( 'insert_timetable_nonce' ) ); ?>';
							// Send AJAX request.
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
								data: {
									nonce    : nonce,
									action   : 'insert_timetable',
								},
								success: function(response) {
									if ( response.resp ) {
										Swal.fire({
											title: 'تم الحفظ بنجاح!',
											icon: 'success',
											confirmButtonText: 'موافق'
										});
									}
								}
							});
						});
					}
				);
				$(document).on(
					'change',
					'select[data-field-name=appointment_choosen_period]',
					function () {
						var repeaterName    =  $(this).closest( '.accordion-content' ).data('field-name');
						var parentWrapper   = $(this).closest( '.jet-form-builder-repeater__row-fields' );
						var selectedPeriods = $(this).val();
						var selectedHour    = $('select[data-field-name=appointment_hour]', parentWrapper).val();
						expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper, repeaterName)
					}
				);

				$(document).on(
					'click',
					'.jet-form-builder-repeater__remove',
					function () {
						var container = $(this).closest('.accordion-content');
						$('select[data-field-name=appointment_hour] option', container).prop('disabled', false);
						setTimeout(
							function() {
								$('select[data-field-name=appointment_choosen_period]', container).trigger('change');
								disableEmptyDays();
							},
							200
						)
					}
				);

				$( '.jet-form-builder-repeater__actions', $('div[name=change_fees_list]') ).each(
					function() {
						var $items = $(this).prev();
						if ( $('#appointment_change_fee').is(':checked') && $items.html() === '' ) {
							$items.html($('.jet-form-builder-repeater__initial', $('div[name=change_fees_list]') ).html().replace(/__i__/g, '0'));
						}
					}
				);
				<?php
				//phpcs:disable
				if ( isset( $_SERVER['REQUEST_URI'] ) && ( false !== strpos( $_SERVER['REQUEST_URI'], 'account-setting' ) || false !== strpos( $_SERVER['REQUEST_URI'], 'meeting-room' ) ) ) {
				//phpcs:enable
					?>
				$(document).on(
					'change',
					'input[name=attendance_type]',
					function(){
						if ( $(this).is(':checked') ) {
							$('.shrinks-usage-method').removeClass('shrinks-error');
						}
					}
				);
				$(document).on(
					'change',
					'.jet-form-builder__field[type="checkbox"]',
					function() {
					const checkedFieldId = $(this).attr('id');
					if ($(this).is(':checked')) {
						
						var sessionPeriodsContainer = $(this).closest(".session-periods-container");
						if ( sessionPeriodsContainer.length > 0 ){
							$('.session-periods-container').removeClass('shrinks-error');
						}
						const elementToClickId = checkedFieldId + '-settings-trigger';
						$('#' + elementToClickId).click();
					}
				});
				<?php } ?>
			} );
		</script>
		<?php
	}
);

add_action(
	'wp_head',
	function () {
		?>
		<script>
			jQuery(document).ready(function($) {
				// Function to safely extract serializable data from formData and response
				function extractSerializableData(data) {
					let serializableData = {};
					for (let key in data) {
						if (data.hasOwnProperty(key) && typeof data[key] !== 'object') {
							serializableData[key] = data[key];
						}
					}
					return serializableData;
				}				
				// On the form success event, set a key in localStorage
				/*jQuery(document).on("jet-form-builder/after-init", function(event, formContainer, jetFormInstance) {
					// Access the form element
					const formElement = formContainer[0].querySelector("form.jet-form-builder");
					
					// Retrieve the form ID from the dataset of the form element
					const formId = formElement ? formElement.dataset.formId : null;

					if (formId && formId == 2199) {
						localStorage.setItem('ajaxForm', 'true');
					}
					if (formId && formId != 2199) {
						localStorage.removeItem('ajaxForm');
					}
				});
				*/
				// On the form success event, set a key in localStorage
				$(document).on("jet-form-builder/ajax/on-success", function(event, formData, response) {
					// Check if personal details form
					if ( response[0].dataset.formId == '3362' && ( $('input[name=username_copy]').val() !== $('input[name=username]').val() || $('input[name=new-password]').val() !== '' )  ) {
						location.reload();
					}
					if (response[0].dataset.formId == '2199' /*&& localStorage.getItem('ajaxForm') === 'true'*/) {
						// Store a flag in localStorage
						localStorage.setItem('ajaxInProgress', 'true');

						// Store serializable formData and response (avoid circular structure)
						localStorage.setItem('formData', JSON.stringify(extractSerializableData(formData)));
						localStorage.setItem('response', JSON.stringify(extractSerializableData(response)));
					}
				});
				// Use setInterval to periodically check for the flag in localStorage
				let checkInterval = setInterval(function() {
					if (localStorage.getItem('ajaxInProgress') === 'true') {
						// If the flag is present, run the AJAX call
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // WordPress AJAX URL
							type: 'POST',
							data: {
								action: 'get_preview_tables',
								form_data: JSON.parse(localStorage.getItem('formData')), // Get form data from localStorage
								response_data: JSON.parse(localStorage.getItem('response')) // Get response data from localStorage
							},
							success: function(data) {
								$('#preview-timetables').html( data );
								// Handle the successful response here, such as updating the DOM
								// Remove the localStorage key after success
								localStorage.removeItem('ajaxInProgress');
								//localStorage.removeItem('ajaxForm');
								localStorage.removeItem('formData');
								localStorage.removeItem('response');
							},
							error: function(error) {
								// Handle errors here, maybe reset the localStorage key
								localStorage.removeItem('ajaxInProgress');
								//localStorage.removeItem('ajaxForm');
								localStorage.removeItem('formData');
								localStorage.removeItem('response');
							}
						});
					}
				}, 1000);

			});

			function showTab(tabId) {
				document.querySelectorAll('.snks_tab-content').forEach((content) => {
					content.classList.remove('snks_active');
				});
				document.querySelectorAll('.tab-contents').forEach((content) => {
					content.classList.remove('snks_active');
					content.style.display = 'none';
				});

				document.getElementById(tabId).classList.add('snks_active');

				document.querySelectorAll('.snks_tab').forEach((tab) => {
					tab.classList.remove('snks-bg');
				});

				if (tabId === 'add-appointments') {
					document.querySelector('.snks_tab-add').classList.add('snks-bg');
				} else {
					document.querySelector('.snks_tab-preview').classList.add('snks-bg');
				}
			}
			document.addEventListener('DOMContentLoaded', function() {
					if (document.querySelector('#add-appointments')) {
						showTab('add-appointments');
					}
			});
		</script>
		<?php
	}
);