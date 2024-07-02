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
			.timetable-preview-item{
				display: none;
			}
			.timetable-preview-tab{
				cursor: pointer;
			}
			.ui-datepicker{
				width:350px;
				right: 0;
				margin: auto;
			}
			.preview-timetable{
				margin-bottom: 30px;
			}
			/*Flat picker*/
			.flatpickr-day.disabled{
				position:relative;
				opacity: 0.5;
			}
			.flatpickr-day.disabled:after{
				content: '/';
			position: absolute;
			right: 13px;
			font-size: 25px;
			opacity: 0.5;
				color:red
			}
			.nextMonthDay{
				color:#34499c!important
			}
			.select2-container--default .select2-selection--single {
				background-color: #f5f5f5;
				border: navajowhite;
				border-radius: 4px;
			}
			.select2-container .select2-selection--single {
				height: 40px;
			}
			.select2-container .select2-selection--single .select2-selection__rendered {
				position: relative;
				top: 8px;
			}
			.select2-container--default .select2-selection--single .select2-selection__arrow {
				height: 40px;
			}
			.trigger-error{
				display:none
			}
			.accordion-content{
				display:none
			}
			.accordion-heading{
				padding:10px;
				background-color:#fff;
				border-radius:10px;
				cursor:pointer;
			}
			.accordion-heading.active-accordion{
				background-color:#12114F;
				color:#fff!important
			}
			.shrinks-error{
				border:1px solid red;
				padding:3px
			}
			.jet-form-builder__conditional.clinics-list .jet-form-builder-repeater__actions button.jet-form-builder-repeater__new{
				color:#fff;
				background-color:#20C319;
				font-size:14px;
				font-weight:bold;
				border-radius:50px
			}
			.change-fees .jet-form-builder-row.field-type-switcher {
				border:none
			}
			.default-border{
				border: 1px solid #000;
			}
			.default-border-radius{
				border-radius: 10px;
			}
			.default-padding{
				padding: 10px
			}
			.jet-form-builder-row.field-type-switcher {
				position: relative;
			}
			.jet-form-builder-repeater__remove{
				height: 25px!important;
			width: 25px!important;
			display: inline-flex!important;
			justify-content: center!important;
			align-items: center!important;
			line-height: 0;
			padding: 3px!important;
				background-color:red
			}
			.jet-form-builder-repeater__row {
				padding: 5px 0;
			}
			table.ui-datepicker-calendar, .ui-datepicker{
				background-color: #3a4091;
			}
			td:not(.ui-datepicker-unselectable) a{
				color:#fff
			}
			table:not(.ui-datepicker-calendar) {
				border: 1px solid #ccc;
				border-collapse: collapse;
				margin: 0;
				padding: 0;
				width: 100%;
				table-layout: fixed;
			}

			table:not(.ui-datepicker-calendar) caption {
				font-size: 1.5em;
				margin: .5em 0 .75em;
			}

			table:not(.ui-datepicker-calendar) tr {
				background-color: #3a4091;
				border: 1px solid #3a4091;
				padding: .35em;
				border-radius: 10px;
				color: #fff;
			}

			table:not(.ui-datepicker-calendar) th,
			table td {
				padding: .625em;
				text-align: center;
			}

			table:not(.ui-datepicker-calendar) th {
				font-size: .85em;
				text-transform: uppercase;
			}

			@media screen and (max-width: 600px) {
				table:not(.ui-datepicker-calendar) {
				border: 0;
			}

			table:not(.ui-datepicker-calendar) caption {
				font-size: 1.3em;
			}
			
			table:not(.ui-datepicker-calendar) thead {
				border: none;
				clip: rect(0 0 0 0);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
			}
			
			table:not(.ui-datepicker-calendar) tr {
				border-bottom: 3px solid #ddd;
				display: block;
				margin-bottom: .625em;
			}
			
			table:not(.ui-datepicker-calendar) td {
				border-bottom: 1px solid #ddd;
				display: block;
				font-size: .8em;
				text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
			}
			
			table:not(.ui-datepicker-calendar) td::before {
				/*
				* aria-label has no advantage, it won't be read inside a table
				content: attr(aria-label);
				*/
				content: attr(data-label);
				float: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				font-weight: bold;
				text-transform: uppercase;
			}
			
			table:not(.ui-datepicker-calendar) td:last-child {
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