<?php
/**
 * Scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


add_action(
	'wp_head',
	function () {
		?>
		<style>
			table {
				border: 1px solid #ccc;
				border-collapse: collapse;
				margin: 0;
				padding: 0;
				width: 100%;
				table-layout: fixed;
			}

			table caption {
				font-size: 1.5em;
				margin: .5em 0 .75em;
			}

			table tr {
				background-color: #3a4091;
				border: 1px solid #3a4091;
				padding: .35em;
				border-radius: 10px;
				color: #fff;
			}

			table th,
			table td {
				padding: .625em;
				text-align: center;
			}

			table th {
				font-size: .85em;
				text-transform: uppercase;
			}

			@media screen and (max-width: 600px) {
			table {
				border: 0;
			}

			table caption {
				font-size: 1.3em;
			}
			
			table thead {
				border: none;
				clip: rect(0 0 0 0);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
			}
			
			table tr {
				border-bottom: 3px solid #ddd;
				display: block;
				margin-bottom: .625em;
			}
			
			table td {
				border-bottom: 1px solid #ddd;
				display: block;
				font-size: .8em;
				text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
			}
			
			table td::before {
				/*
				* aria-label has no advantage, it won't be read inside a table
				content: attr(aria-label);
				*/
				content: attr(data-label);
				float: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				font-weight: bold;
				text-transform: uppercase;
			}
			
			table td:last-child {
				border-bottom: 0;
			}
			}
		</style>
		<?php
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css', false, '4.1.0' );
		wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
		// https://flatpickr.js.org/examples/.
		wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', false, '4.6.13' );
		wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array( 'jquery' ), '4.6.13', true );
		wp_enqueue_script( 'flatpickr-ar', 'https://npmcdn.com/flatpickr@4.6.13/dist/l10n/ar.js', array( 'flatpickr' ), '4.6.13', true );
	}
);

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
				function showErrorpopup() {
					if ( $('.shrinks-error').length > 0 ) {
						$('#error-container').text('يرجى استكمال الإعدادات');
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
				flatpickr.localize(flatpickr.l10ns.ar);
				$('#dateField').flatpickr(
					{
						"disable": [
							function(date) {
								var currentDate = new Date();
								currentDate.setHours(0, 0, 0, 0);
								date.setHours(0, 0, 0, 0);

								// return true to disable
								return ( date.getDay() === 0 || currentDate > date );
							}
						],
						"locale": {
							"firstDayOfWeek": 6, // start week on Monday
							
						}
					}
				);
				
				$('.appointment-settings-submit').on( 'click', function(e){
					if ( ! checkRequiredSettings() ) {
						e.preventDefault();
					}
					showErrorpopup();
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
				function expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper) {
						if ( '' === selectedPeriods || '' === selectedHour ) {
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
								//console.log(response);
								parentWrapper.find('.expected-hourse').html( response.resp );

							}
						});
				}
				$('.delete-slot').on(
					'click',
					function( e ) {
						e.preventDefault();
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
						// Perform nonce check.
						var nonce     = '<?php echo esc_html( wp_create_nonce( 'delete_slot_nonce' ) ); ?>';
						var slotIndex = $(this).data('index');
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slotIndex: slotIndex,
								nonce    : nonce,
								action   : 'delete_slot',
							},
							success: function(response) {
								if ( response.resp ) {
									$( '#timetable-' + slotIndex ).remove();
								}
							}
						});
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
				$('select[data-field-name=appointment_choosen_period]').on(
					'change',
					function () {
						var parentWrapper   = $(this).closest( '.jet-form-builder-repeater__row-fields' );
						var selectedPeriods = $(this).val();
						var selectedHour    = $('select[data-field-name=appointment_hour]').val();
						expectedHoursOutput(selectedPeriods, selectedHour, parentWrapper)
					}
				);
				$('select[data-field-name=appointment_hour]').on(
					'change',
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
				$('.snks-count-down').each(
					function () {
						var countdownElement = $(this);
						if ( isNaN( parseInt(countdownElement.text()) ) ) {
							return;
						}
						var countdownValue = parseInt(countdownElement.text());

						var countdownInterval = setInterval(function() {
							countdownValue--;
							countdownElement.text(countdownValue + ' ثانية');
							if (countdownValue === 0) {
								clearInterval(countdownInterval);
								countdownElement.attr( 'href', countdownElement.data('url') );
								countdownElement.addClass('start');
								countdownElement.text( 'إبدأ الآن' );

							}
						}, 1000); // Update the countdown every second (1000 milliseconds)
					}
				);
				$('body').height( $(window).height() );
				$( '.anony-day-radio' ).on(
					'change',
					function(){
						$( '.anony-day-radio label' ).removeClass( 'active-day' );
						$( this ).closest( '.anony-day-radio' ).find('label').addClass( 'active-day' );
					}
				);
				$( 'body' ).on(
					'change',
					'.active-todo-page input[name=current-week-day]',
					function () {
						var slectedDay = $(this).val();
						var hour;
						$( '.to-do-input' ).each(
							function () {
								hour = $(this).data( 'hour' );
								$(this).attr( 'name', slectedDay + '[' + hour + ']' );
								$( this ).val( '' );
							}
						);
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'fetch_to_do_nonce' ) ); ?>';
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slectedDay: slectedDay,
								action    : 'fetch_to_do',
							},
							success: function(response) {
								if (  response.toDo ) {
									var toDos = JSON.parse( response.toDo );
									var keysArray = Object.keys(toDos);
									var length = keysArray.length;
									for (let i = 0; i < length; i++) {
										var attributeValue = slectedDay + '[' + i + ']';
										var escapedAttributeValue = attributeValue.replace(/([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, "\\$1");
										$( 'input[name=' + escapedAttributeValue + ']' ).val( toDos[i] );
									}
								}

							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});

					}
				);
				$( 'body' ).on(
					'change',
					'.hour-radio',
					function () {
						$( '.available-time' ).removeClass( 'active-hour' );
						if ($(this).is(':checked')) {
							$( this ).closest('.available-time').addClass( 'active-hour' );
						}
					}
				);
				$( '.current-month-day-radio' ).on(
					'change',
					function () {
						$( '.anony-day-radio' ).find('label').removeClass( 'active-day' );
						if ($(this).is(':checked')) {
							$( this ).prev('label').addClass( 'active-day' );
						}
						var slectedDay = $(this).val();
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'fetch_start_times_nonce' ) ); ?>';
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								slectedDay: slectedDay,
								action    : 'fetch_start_times',
							},
							success: function(response) {

								$( '#snks-available-hours' ).html( response.resp );
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});

					}
				);
				$('#todo-pages-container .anony-content-slider-control').on('click','.anony-content-slider-next', function(e) {
					e.preventDefault();
					var activePage = $( '.active-todo-page' );
					if ( activePage.prev( '.todo-page' ).length > 0 ) {
						$( '.todo-page' ).removeClass( 'active-todo-page' );
						activePage.prev( '.todo-page' ).addClass( 'active-todo-page' );
						$('.active-todo-page .current-week-day:first-child').find( '.current-week-day-radio' ).click();
					}
					
					
				});

				$('#todo-pages-container .anony-content-slider-control').on('click','.anony-content-slider-prev', function(e) {
					e.preventDefault();
					var activePage = $( '.active-todo-page' );
					if ( activePage.next( '.todo-page' ).length > 0 ) {
						$( '.todo-page' ).removeClass( 'active-todo-page' );
						activePage.next( '.todo-page' ).addClass( 'active-todo-page' );
						$('.active-todo-page .current-week-day:first-child').find( '.current-week-day-radio' ).click();
					}
					
				});
				
				//$( '.current-month-day-radio' ).val();
				$('.current-day').click();
				function toDoSave( formData ) {
					// Perform nonce check.
					var nonce = '<?php echo esc_html( wp_create_nonce( 'update_to_do_nonce' ) ); ?>';
					// Send AJAX request.
					$.ajax({
						type: 'POST',
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
						data: formData,
						success: function(response) {
							// Handle the response data as needed.
						},
						error: function(xhr, status, error) {
							console.error('Error:', error);
						}
					});
				}
				$('body').on(
					'focusout',
					'.to-do-input',
					function () {
						$('#to-do-form').submit();
					}
				);
				$('#to-do-form').on(
					'submit',
					function ( e ) {
						e.preventDefault();
						const formData = $(this).serialize();
						toDoSave( formData );
					}
				);
				$('.doctor_actions').on(
					'submit',
					function (e) {
						e.preventDefault();
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
						var doctorActions = $(this).serializeArray();
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'doctor_actions_nonce' ) ); ?>';
						doctorActions.push({ name: 'nonce', value: nonce });
						doctorActions.push({ name: 'action', value: 'session_doctor_actions' });
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: doctorActions,
							success: function(response) {
								location.reload();
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
				$( '.snks-cancel-appointment' ).on(
					'click',
					function (e) {
						e.preventDefault();
						if ( confirm("هل أنت متأكد") !== true ) {
							return;
						}
						var clicked   = $(this);
						var bookingID = $(this).data('id');
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'cancel_appointment_nonce' ) ); ?>';
						
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								action    : 'cancel_appointment',
								bookingID : bookingID,
								nonce     : nonce,
							},
							success: function(response) {
								if ( response.resp ) {
									clicked.text('تم');
									window.location.reload();
								}
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
				$( 'body' ).on(
					'focusout',
					'#patient-nickname, #family-nickname',
					function (e) {
						if ( '' !== $(this).next('input').val() ) {
							return;
						}
						var nickName      = $(this).val();
						var nicknameInput = $(this);
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'check_nickname_nonce' ) ); ?>';
						
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: {
								action    : 'check_nickname',
								nickName  : nickName,
								nonce     : nonce,
							},
							success: function(response) {
								if ( response.resp ) {
									nicknameInput.val('');
									nicknameInput.closest('.row').append('<p class="error">عفواً هذا الإسم موجود بالفعل<p>');
								} else {
									nicknameInput.closest('.row').find('.error').remove();
								}
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);
			} );
		</script>
		<?php
	}
);

add_action(
	'wp_footer',
	function () {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$url_params = wp_unslash( $_GET );
		if ( ! snks_is_doctor() || empty( $url_params['room_id'] ) ) {
			return;
		}
		$timetable = snks_get_timetable_by( 'ID', absint( $url_params['room_id'] ) );
		if ( ! $timetable ) {
			return;
		}
		$session_id      = absint( $url_params['room_id'] );
		$current_doctor  = get_current_user_id();
		$session_user_id = $timetable->user_id;
		if ( absint( $session_user_id ) !== $current_doctor ) {
			return;
		}
		$redirect_after_meeting = 'session' === $timetable->purpose ? get_the_permalink( 682 ) : get_the_permalink( 1194 );
		?>
		<script>
			jQuery( document ).ready(
				function ( $ ) {
					document.querySelector("body").addEventListener("sessionEnded", (event) => {
						if ( confirm( 'هل تريد تحديد الجلسة كمكتمل؟' ) === false ) {
							return;
						}
						const sessionID = <?php echo esc_html( $session_id ); ?>;
						const doctorID  = <?php echo esc_html( $current_doctor ); ?>;

						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'end_session_nonce' ) ); ?>';
						const data = {
								action    : 'end_session',
								sessionID : sessionID,
								doctorID  : doctorID,
								nonce     : nonce
						}
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								window.location.href = '<?php echo esc_url( $redirect_after_meeting ); ?>';
							},
							error: function(xhr, status, error) {
								alert('Error:', error);
							}
						});
					});
				}
			);
		</script>
		<?php
	}
);

add_action(
	'wp_footer',
	function () {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$url_params = wp_unslash( $_GET );
		if ( ( ! snks_is_patient() ) || empty( $url_params['room_id'] ) ) {
			return;
		}
		$dashboard  = get_the_permalink( 682 );
		$session_id = $url_params['room_id'];
		?>
		<script>
			jQuery( document ).ready(
				function ( $ ) {
					const sessionID = <?php echo absint( $session_id ); ?>;
					// Perform nonce check.
					var nonce     = '<?php echo esc_html( wp_create_nonce( 'session_attendance_nonce' ) ); ?>';
					const dashboard = '<?php echo esc_url( $dashboard ); ?>';
					const data = {
							action    : 'session_attendance',
							SessioID  : sessionID,
							nonce     : nonce
					}
					setInterval(
						function() {
							// Send AJAX request.
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
								data: data,
								success: function(response) {
									if( response.resp ) {
										window.location.href = dashboard;
									}
								},
								error: function(xhr, status, error) {
									alert('Error:', error);
								}
							});
						},
						10000
					)
				}
			);
		</script>
		<?php
	}
);
// Content slider.
add_action(
	'wp_footer',
	function () {
		?>
		<script>
			function snksTouchedInside( event, className ) {
				var targetElement     = event.target;
				var isInsideContainer = false;
				while (targetElement) {
					if (targetElement.classList.contains( className )) {
					isInsideContainer = true;
					break;
					}
					targetElement = targetElement.parentElement;
				}

				return isInsideContainer
			};
			jQuery(document).ready(function($) {
				$("#consulting-form").on(
					'submit',
					function(event){
						if ( $('input[name="selected-hour"]:checked').length === 0 || $('input[name="current-month-day"]:checked').length === 0  ) {
							event.preventDefault();
							alert('فضلاً تأكد من أنك قمت بتحديد اليوم والساعة');
						}
					}
				);
				if ( $('.anony-content-slider').length < 1 ) {
					return;
				}
				var slideWidth = $('.anony-content-slide').outerWidth();
				var slider     = $('.anony-content-slider');
				var contentSliderInterval;

				var infiniteLoop = true;
				var totalSlidesCount = $('.anony-content-slide').length;
				var offScreenSlides = 0;
				var margins = 0;
				
				if ( ! infiniteLoop ) {
					if ( totalSlidesCount > 7 ) {
						offScreenSlides = initialOffScreenCount = totalSlidesCount - 7;
					}
					if ( offScreenSlides == 0 ) {
						$('.anony-content-slider-next').hide();
						$('.anony-content-slider-prev').hide();
					}
				}
				$('.anony-content-slide').each( function() {
					margins = margins + parseFloat( $(this).css("marginRight").replace('px', '' ) ) + parseFloat( $(this).css("marginLeft").replace('px', '' ) );
				} );
				var itemsLength = $('.anony-content-slide').length;

				// Adjust the slider width.
				var sliderWidth = slideWidth * itemsLength + margins;
				slider.width(sliderWidth);
				// Set initial position.
				<?php if ( ! is_rtl() ) { ?>
				var initialPosition = -slideWidth;
				<?php } else { ?>
					var initialPosition = slideWidth;
				<?php } ?>
				// Slide to the next slide.
				$('#consulting-form .anony-content-slider-control').on('click','.anony-content-slider-next', function(e) {
					e.preventDefault();
					if ( offScreenSlides >= 0 ) {
						offScreenSlides = offScreenSlides - 1;
					}
					if ( offScreenSlides <= -1 ) {
						offScreenSlides = 0;
						return;
					}
					var $currentSlide = $('.anony-content-slide:first-child');
					var width = $currentSlide.outerWidth();

					slider.animate(
					{ 'margin-<?php echo ! is_rtl() ? 'left' : 'right'; ?>': '-=' + width },
					500
					);
				});

				// Slide to the previous slide.
				$('#consulting-form .anony-content-slider-control').on('click','.anony-content-slider-prev', function(e) {
					e.preventDefault();
					
					
					if ( offScreenSlides < initialOffScreenCount + 1 ) {
						offScreenSlides = offScreenSlides + 1;
					}
					
					if ( offScreenSlides > initialOffScreenCount ) {
						offScreenSlides = initialOffScreenCount;
						return;
					}
					
					var $currentSlide = $('.anony-content-slide:first-child');
					var width = $currentSlide.outerWidth();
					slider.animate(
					{ 'margin-<?php echo ! is_rtl() ? 'left' : 'right'; ?>': '+=' + width },
					500
					);				
				});
				$('.anony-content-slider-container').hover(
					function(){
						$(this).addClass('paused');
					},
					function(){
						$(this).removeClass('paused');
					}
				);

				let xDown = null;
				let yDown = null;

				// We use the touchstart event to capture the initial touch position (xDown and yDown variables).
				function handleTouchStart(event) {
					var element = event.target;
					var container = element.closest('.anony-content-slider-container');
					if (container) {
						$('.paused').removeClass('paused');
						clearInterval(contentSliderInterval);
						xDown = event.touches[0].clientX;
						yDown = event.touches[0].clientY;
					} else {
						xDown = null;
						yDown = null;
					}
				}

				// Calculate the horizontal distance (xDiff) and vertical distance (yDiff) between the initial touch position and the current touch position.
				function handleTouchMove(event) {
					if (!xDown || !yDown) {
						return;
					}

					const xUp = event.touches[0].clientX;
					const yUp = event.touches[0].clientY;

					const xDiff = xDown - xUp;
					const yDiff = yDown - yUp;

					/**
					 * If the horizontal distance (xDiff) is greater than the vertical distance (yDiff),
					 * We determine whether it's a swipe to the left or right based on the sign of xDiff.
					 * A negative xDiff indicates a swipe to the left, while a positive xDiff indicates a swipe to the right.
					 */
					if (Math.abs(xDiff) > Math.abs(yDiff)) {
						if (xDiff > 0) {
						// Swipe to the left
						$('.anony-content-slider-control').find('.anony-content-slider-prev').click();
						} else {
						// Swipe to the right
						$('.anony-content-slider-control').find('.anony-content-slider-next').click();
						}
					}

					// Reset values
					xDown = null;
					yDown = null;
				}

				function handleTouchEnd( event ) {
					if (!xDown || !yDown) {
						return;
					}
					contentSliderInterval = setInterval(
						function(){
							if ( $('.paused').length === 0 ) {
								$('.anony-content-slider-container').find('.anony-content-slider-next').click();
							}
						},
						5000
					);
				}

				document.addEventListener("touchstart", handleTouchStart, false);
				document.addEventListener("touchmove", handleTouchMove, false);
				document.addEventListener("touchend", handleTouchEnd, false);
			});
		</script>
		<?php
	}
);
add_action(
	'admin_footer',
	function () {
		$screen = get_current_screen();
		if ( 'user-edit' !== $screen->base ) {
			return;
		}
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$('#snks-insert_timetable').on(
					'click',
					function ( e ) {
						e.preventDefault();
						if ( '' === $( '#user_timetable_date' ).val() || '' ===  $( '#user_timetable_time' ).val() || '' === $( '#user_id' ).val() ) {
							return;
						}
						// Perform nonce check.
						var nonce = '<?php echo esc_html( wp_create_nonce( 'update_timetable_nonce' ) ); ?>';
						const data = {
								action : 'update_timetable_markup',
								date   : $( '#user_timetable_date' ).val(),
								time   : $( '#user_timetable_time' ).val(),
								userID : $( '#user_id' ).val(),
								purpose : $( '#user_timetable_purpose' ).val(),
								patientID : $( '#user_timetable_patient_id' ).val(),
								sessionTitle : $( '#user_timetable_session_title' ).val(),
								nonce  : nonce
						}
						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								if ( response.resp && '' !== response.html ) {
									$( '#current-user-timetable' ).append( response.html );
									$( '.insert-error' ).hide();
								} else {
									$( '.insert-error' ).show();
									setTimeout(
										function () {
											$( '.insert-error' ).hide();
										},
										2000
									);
								}
								// Handle the response data as needed.
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);

				$('body').on(
					'click',
					'.timetable-action',
					function (e) {
						e.preventDefault();
						if ( confirm( 'هل أنت متأكد؟' ) !== true ) {
							return;
						}
						// Perform nonce check.
						var clicked = $( this );
						var nonce   = '<?php echo esc_html( wp_create_nonce( 'delete_timetable_nonce' ) ); ?>';
						var data    = {
							targrtID : $( this ).data('id'),
							action   : $( this ).data('action'),
							nonce  : nonce
						}

						// Send AJAX request.
						$.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
							data: data,
							success: function(response) {
								if ( response.resp ){
									clicked.closest( 'tr' ).remove();
								}
							},
							error: function(xhr, status, error) {
								console.error('Error:', error);
							}
						});
					}
				);

			} );
		</script>
		<?php
	}
);


add_action(
	'wp_footer',
	function () {
		// If not dashboard , not consulting list.
		if ( ! is_page( 682 ) && ! is_page( 1194 ) && ! is_front_page() && ! snks_is_patient() ) {
			return;
		}
		?>

		<!--Start of Tawk.to Script-->
		<script type="text/javascript">
			var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
			(function(){
			var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
			s1.async=true;
			s1.src='https://embed.tawk.to/65fd9600a0c6737bd123af11/1hpj93t27';
			s1.charset='UTF-8';
			s1.setAttribute('crossorigin','*');
			s0.parentNode.insertBefore(s1,s0);
			})();
		</script>
		<script type="text/javascript">
			var Tawk_API = Tawk_API || {};

			Tawk_API.customStyle = {
				visibility : {
					desktop : {
						position : 'bl',
						xOffset : '10px',
						yOffset : 80
					},
					mobile : {
						position : 'bl',
						xOffset : '10px',
						yOffset : 80
					},
					bubble : {
						rotate : '0deg',
						xOffset : -20,
						yOffset : 0
					}
				}
			};
			Tawk_API.disableWidgetFont = true;
			window.Tawk_API.onLoad = function(){
				window.Tawk_API.hideWidget();
			};
				
			
			jQuery( document ).ready(
				function ( $ ) {
					$( 'body' ).on(
						'click',
						'#customer-care-chat, .customer-care-chat',
						function ( e ) {
							e.preventDefault();
							console.log('true');
							Tawk_API.toggle()
						}
					);
				}
			);
		</script>
		<!--End of Tawk.to Script-->
		<?php
	}
);