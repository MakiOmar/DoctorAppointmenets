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
				function checkRequiredSettings() {
					var pass = true;
					if ( ! $('input[name=attendance_type]').is(':checked') ) {
						$('.shrinks-usage-method').addClass('shrinks-error');
						pass = false;
					}
					if ( ! $('#60-minutes').is(':checked') && ! $('#45-minutes').is(':checked') && ! $('#30-minutes').is(':checked') ) {
						$('.session-periods-container').addClass('shrinks-error');
						pass = false;
					}
					return pass;
				}//shrinks-usage-method
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

						if ( timeSelectedHour < timeStartingHour ) {
							tos.forEach( function( to ) {
								timeTo = new Date(`1970-01-01T${to}`);
								if ( timeTo > timeStartingHour ) {
									overlappedHours.push( to );
								}
							} );
						}
					} );

					return overlappedHours;
				}
				function expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper) {
						if ( '' === selectedPeriods || '' === selectedHour ) {
							parentWrapper.find('.expected-hourse').html('');
							console.log('No expected hours');
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
								$('select[data-field-name=appointment_hour] option', parentWrapper.closest('.accordion-content')).each( function() {
									if ( ! $(this).is(':selected') && $(this).val() >= response.lowesttHour && $(this).val() < response.largestHour && ! disabledOptions.includes( $(this).val() ) ) {
										$(this).prop('disabled', false)
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
									$('.shrinks-error').removeClass('shrinks-error');
									$('select[data-field-name=appointment_hour] option', parentWrapper.closest('.accordion-content')).each( function() {
										if ( ! $(this).is(':selected') && $(this).val() >= response.lowesttHour && $(this).val() < response.largestHour ) {
											$(this).prop('disabled', true);
											if ( ! disabledOptions.includes( $(this).val() ) ) {
												disabledOptions.push( $(this).val() );
											}
										}
									} );
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
				$('.appointment-settings-submit').on( 'click', function(e){
					if ( ! checkRequiredSettings() ) {
						e.preventDefault();
					}
					showErrorpopup( 'يرجى استكمال الإعدادات' );
				} );

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

				$('select[data-field-name=appointment_hour]').change(function() {
					var selectedValue = $(this).val();
					var container     = $(this).closest('.accordion-content');
					$('select[data-field-name=appointment_hour] option', container).prop('disabled', false);		
					$('select[data-field-name=appointment_hour] option[value="' + selectedValue + '"]', container).not(this).prop('disabled', true);
					$(this).find('option[value="' + selectedValue + '"]').prop('disabled', false);
				});
				$('.accordion-content').on(
					'click',
					'.jet-form-builder-repeater__new',
					function(){
						disableEmptyDays();
						var container = $(this).closest('.accordion-content');
						setTimeout(
							function() {
								$('select[data-field-name=appointment_choosen_period]', container).trigger('change');
							},
							200
						)
					}
				);
				$('#insert-timetable').on(
					'click',
					function( e ) {
						e.preventDefault();
						$("#insert-timetable-msg").text('');
						if ( confirm("هل أنت متأكد") !== true ) {
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
									$("#insert-timetable-msg").text('تم الحفظ بنجاح');
								}
								console.log( response.errors );
							}
						});
					}
				);
				$('body').on(
					'change',
					'select[data-field-name=appointment_choosen_period]',
					function () {
						var parentWrapper   = $(this).closest( '.jet-form-builder-repeater__row-fields' );
						var selectedPeriods = $(this).val();
						var selectedHour    = $('select[data-field-name=appointment_hour]', parentWrapper).val();
						expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper)
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
				$('body').on(
					'change',
					'select[data-field-name=appointment_hour]',
					function () {
						var parentWrapper   = $(this).closest( '.jet-form-builder-repeater__row-fields' );
						setTimeout(function(){
							$('select[data-field-name=appointment_choosen_period]', parentWrapper).trigger('change');
						},200)
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
				if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'appointments-settings' ) ) {
				//phpcs:enable
					?>
				$('input[name=attendance_type]').on(
					'change',
					function(){
						if ( $(this).is(':checked') ) {
							$('.shrinks-usage-method').removeClass('shrinks-error');
						}
					}
				);
				$('.jet-form-builder__field[type="checkbox"]').on('change', function() {

					const checkedFieldId = $(this).attr('id');
					if ($(this).is(':checked')) {
			 
						var sessionPeriodsContainer = $(this).closest(".session-periods-container");
						if ( sessionPeriodsContainer.length > 0 ){
							$('.session-periods-container').removeClass('shrinks-error');
						}
						const elementToClickId = checkedFieldId + '-settings-trigger';
						$('#' + elementToClickId).click();
						$('.jet-form-builder-repeater__actions').each(
							function() {
								var $items = $(this).prev();
								if ( $items.html() === '' ) {
									$(this).find('.jet-form-builder-repeater__new').click();
								}
							}
						);
					}
				});
				<?php } ?>
			} );
		</script>
		<script>
			function showTab(tabId) {
				document.querySelectorAll('.snks_tab-content').forEach((content) => {
					content.classList.remove('snks_active');
				});
				document.querySelectorAll('.snks_active').forEach((content) => {
					content.classList.remove('snks_active');
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
			
			showTab('add-appointments');

		</script>
		<?php
	}
);
