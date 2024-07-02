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
				function checkHoursOverlap(A, B) {
					const hoursOverlaps = [];
					const timeLastItemOfB   = new Date(`1970-01-01T${B[B.length - 1]}`);
					for (let i = 0; i < A.length; i++) {
						const hourA = A[i];
						var overlapped = false;
						for (let j = 0; j < B.length; j += 2) {
							const startB = B[j];
							const endB = B[j + 1];
							const timeA = new Date(`1970-01-01T${hourA}`);
							const timeStartB = new Date(`1970-01-01T${startB}`);
							const timeEndB = new Date(`1970-01-01T${endB}`);

							if ( timeA < timeLastItemOfB && ( hourA === startB || hourA === endB )  ) {
								overlapped = true;
							}

							if (timeA > timeStartB && timeA < timeEndB) {
								overlapped = true;
							}
						}
						if ( overlapped ) {
							hoursOverlaps.push( hourA );
						}
					}
					if ( hoursOverlaps.length > 0 ) {
						return hoursOverlaps;
					}

					return false;
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
								parentWrapper.find('.expected-hourse').html( response.resp );
								parentWrapper.find('.expected-hours-json').val( JSON.stringify( response.limits ) );
								var totalOverlaps = [];
								$('.expected-hours-json', parentWrapper.closest('.accordion-content')).each(function() {
									const jsonString = $(this).val();
									if ( '' !== $(this).val() ) {
										const parsedValue = JSON.parse(jsonString);

										if ( JSON.stringify( response.limits ) !== jsonString && checkHoursOverlap(response.hours, parsedValue) ) {
											totalOverlaps.push( checkHoursOverlap(response.hours, parsedValue) );
											var array = checkHoursOverlap(response.hours, parsedValue);
											var className;
											array.forEach(function(item, index) {
												className = item.replace(":", "-");
												$( '.' + className, parentWrapper.closest('.accordion-content') ).addClass('shrinks-error');
											});
											setTimeout(() => {
												showErrorpopup('هناك تداخل في المواعيد!');
												$('#jet-popup-2219').removeClass('jet-popup--hide-state').addClass('jet-popup--show-state');
											}, 200);
										} else if ( ! checkHoursOverlap(response.hours, parsedValue)  ) {
											$('.shrinks-error').removeClass('shrinks-error');
										}
									}
								});
								if ( totalOverlaps.length < 1 ) {
									$('select[data-field-name=appointment_hour] option', parentWrapper.closest('.accordion-content')).each( function() {
										if ( ! $(this).is(':selected') && $(this).val() >= response.lowesttHour && $(this).val() < response.largestHour ) {
											$(this).prop('disabled', true)
										}
									} );
								}
							}
						});
				}
				flatpickr.localize(flatpickr.l10ns.ar);
				function flatPickrInput() {
					$('input[data-field-name=off_days]').flatpickr(
						{
							"disable": [
								function(date) {
									var currentDate = new Date();
									currentDate.setHours(0, 0, 0, 0);
									date.setHours(0, 0, 0, 0);

									// return true to disable. To disable sunday date.getDay() === 0
									return ( currentDate > date );
								}
							],
							"locale": {
								"firstDayOfWeek": 6, // start week on Monday
								
							},
							"mode" : 'multiple'
						}
					);
				}
				flatPickrInput();

				
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

				$('.jet-form-builder-repeater__remove').on(
					'click',
					function () {
						var container = $(this).closest('.accordion-content');
						$('select[data-field-name=appointment_hour] option', container).prop('disabled', false);
						setTimeout(
							function() {
								$('select[data-field-name=appointment_choosen_period]', container).trigger('change');
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
		<?php
	}
);
