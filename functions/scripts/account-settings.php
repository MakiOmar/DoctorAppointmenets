<?php
/**
 * Account settings' cripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_footer',
	function () {
		if ( ! is_page( 'account-setting' ) && ! is_page( 'meeting-room' ) ) {
			return;
		}
		?>
		<script>
			function setCookie(name, value, days, sameSite = "None", secure = true) {
				let expires = "";

				if (days) {
					const date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toUTCString();
				} else if (days === 0) {
					// If days is 0, set the expiration to a past date to delete the cookie
					expires = "; expires=Thu, 01 Jan 1970 00:00:00 UTC";
				}

				// Construct the cookie string
				document.cookie = `${name}=${encodeURIComponent(value || "")}${expires}; path=/; SameSite=${sameSite}${
					secure ? "; Secure" : ""
				}`;
			}

			function getCookie(name) {
				let cookieArr = document.cookie.split(";");

				for (let i = 0; i < cookieArr.length; i++) {
					let cookie = cookieArr[i].trim();

					// Check if the cookie's name matches the specified name
					if (cookie.indexOf(name + "=") === 0) {
						return cookie.substring(name.length + 1, cookie.length);
					}
				}

				// Return null if the cookie is not found
				return null;
			}
			setCookie( 'edited_withdrawal_form', '', 0 );
			setCookie('next_popup', '', 0);
			jQuery( document ).ready( function( $ ) {
				
				// Define the URL where you want to prompt the user
				const accountSettingUrlPath = '/account-setting';
				var confirmationMessage = "يرجى التأكد من حفظ الإعدادات، هل أنت متأكد؟";

				var preventNavigation = false; // Flag to control when to prompt
				// Function to set preventNavigation to true when a form is changed
				function setFormChanged( form ) {
					if ( form.getAttribute('data-form-id') ) {
						preventNavigation = true;
						setCookie('edited_form', form.getAttribute('data-form-id'));
					}
					if ( form.getAttribute('id') === 'withdrawal-settings-form' ){
						setCookie('edited_withdrawal_form', 'yes');
					}

					
				}
				function listenToForms( forms ) {
					if (forms.length > 0) {
						forms.forEach(function (form) {
							// Add event listeners to all input, select, and textarea fields in each form
							form.querySelectorAll('input, select, textarea').forEach(function (field) {
								field.addEventListener('input', function(event) {
									setFormChanged(event.target.form);
								});    // Text inputs
								field.addEventListener('change', function(event) {
									setFormChanged(event.target.form);
								});   // Dropdowns, checkboxes, etc.
							});
						});
					}
				}
				function moveToNext() {
					preventNavigation = false;
					setCookie('edited_form', '', 0);
					let nextPopup = getCookie('next_popup');
					if ( nextPopup && nextPopup !== 'snks_account_settings' ) {
						$('.popup-trigger').removeClass('snks-active-popup');
						$('div[data-id="' + nextPopup + '"]').addClass('snks-active-popup');
						$('div[data-id="' + nextPopup + '"]').next('.jet-popup-target').click();
					}

					if ( nextPopup && nextPopup == 'snks_account_settings' ) {
						closeAllPopups();
						$('.popup-trigger').removeClass('snks-active-popup');
						$('#snks_account_settings').css({
							transform: 'translateX(0)', // Moves the element back to its original position
							display: 'block' // Ensure it's visible
						});
					}
					setCookie('next_popup', '', 0);
				}
				function closeAllPopups() {
					var openPopup = $('.jet-popup--show-state');
					if ( openPopup.length > 0 ) {
						// Trigger the close button click on the currently open popup
						var closeButton = openPopup.find('.jet-popup__close-button');
						if (closeButton.length) {
							closeButton.click();
						}
					}
				}
				function repeaterCustomRemove() {
					$('.jet-form-builder-repeater__remove').each(function() {
						if (!$(this).next().hasClass('jet-form-builder-repeater__custom_remove')) {
							// Create a new button element
							const newButton = $('<button>', {
								text: 'x', // Button text
								class: 'jet-form-builder-repeater__custom_remove', // Add your custom class here
								click: function(e) {
									e.preventDefault();
									let rowRemove = $(this).closest('.jet-form-builder-repeater__row-remove');
									let removeButton = rowRemove.find('.jet-form-builder-repeater__remove');
									if ( $(this).closest('form[data-form-id="2199"]').length > 0 ) {
										let baseHour = rowRemove.prev().find('select[data-field-name="appointment_hour"]').val();
										let baseHourId = rowRemove.prev().find('select[data-field-name="appointment_hour"]').attr('id');
										if ( baseHour !== '' && baseHourId !== '' ) {
											$.ajax({
												url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
												type: 'POST',
												data: {
													action: 'check_open_session',
													security: "<?php echo esc_attr( wp_create_nonce( 'snks_nonce' ) ); ?>",
													baseHour: baseHour,
													baseHourId: baseHourId
												},
												success: function(response) {
													if (!response.success) {
														Swal.fire({
															icon: 'error',
															title: 'عفواً',
															text: response.data.message,
															confirmButtonText: 'إغلاق'
														});
													} else {
														removeButton.trigger('click');
													}
												}
											});
										} else {
											removeButton.trigger('click');
										}
									} else {
										removeButton.trigger('click');
									}
								}
							});
							$(this).after(newButton);
						}
					});
				}
				function disableOptions( valuesInput, target, separator ) {
					if ( typeof $(valuesInput).val() === 'undefined' ) {
						return;
					}
					var disabled = $(valuesInput).val().split(separator);
					$('select[data-field-name="' + target + '"]').each(function() {
						var $select = $(this);
						$select.find('option').each(function() {
							var $option = $(this);
							if (disabled.includes($option.val())) {
								if (!$option.is(':selected')) {
									$option.prop('disabled', true);
								}
							} else {
								$option.prop('disabled', false);
							}
						});
					});
				}
				function disableAttendanceOptions() {
					disableOptions( '#disabled-attendance-types', 'appointment_attendance_type', '-' );
					disableOptions( '#disabled-attendance-types', 'app_attendance_type', '-' );
				}
				function disableClinics() {
					disableOptions( '#disabled-clinics', 'appointment_clinic', '|' );
					disableOptions( '#disabled-clinics', 'app_clinic', '|' );
				}
				repeaterCustomRemove();
				setCookie('edited_form', '', 0);
				// Add event listeners for any interaction with form fields
				var confirmSave = document.querySelectorAll('.snks-confirm');
				listenToForms( confirmSave );

				// Select all forms with the class 'jet-form-builder'
				var forms = document.querySelectorAll('.jet-form-builder');
				listenToForms( forms );

				$(window).on('jet-popup/show-event/after-show', function(){
					disableAttendanceOptions();
					disableClinics();
					var forms = document.querySelectorAll('.jet-form-builder');
					listenToForms( forms );
				});

				$( document ).on('click', '.jet-form-builder-repeater__remove', function(){
					$(".item-deleted").trigger('click');
				});

				$( document ).on('change', 'input[name=attendance_type]', function(){
					var attendance;

					if ( 'online' === $(this).val() ) {
						attendance = 'offline-both';
					} else if ( 'offline' === $(this).val() ) {
						attendance = 'online-both';
					} else {
						attendance = '';
					}
					$('#disabled-attendance-types').val( attendance ).trigger('change');
				});

				$( document ).on('click', '.jet-form-builder-repeater__new', function(e){
					e.preventDefault();
					let repeater = $(this).closest('.jet-form-builder__field-wrap');
					let repeaterItems = $('.jet-form-builder-repeater__items', repeater);
					if (repeaterItems.length && repeaterItems.children().length > 0) {
						preventNavigation = true;
						setCookie('edited_form', $(this).closest('form').data('form-id'));
					}
					setTimeout(
						function() {
							repeaterCustomRemove();
							disableAttendanceOptions();
							disableClinics();
						},
						300
					);
				});

				$( document ).on('click', '.item-deleted', function(){
					preventNavigation = true;
					setCookie('edited_form', $(this).closest('form').data('form-id'));
				});
				// Function to auto-click the "Add New" button for repeaters with no items
				
				
				$(window).on('jet-popup/show-event/after-show', function( event, popup, t ){
					repeaterCustomRemove();
				});
				$(window).on('jet-popup/render-content/render-custom-content', function( event, popup, t ){
					let exclude = [ '1961', '1958', '1964' ];
					if ( ! exclude.includes(popup.data.popup_id) ){
						//preventNavigation = false;
						setCookie("next_popup", '', 0);
					}
				});
				$(document).on('click', '#preview_button', function (event) {
					let targetUrl = $(this).attr('href');

					// Check if navigation should be prevented based on cookies
					if (getCookie('edited_form') || getCookie('edited_withdrawal_form')) {
						event.preventDefault(); // Prevent immediate navigation

						Swal.fire({
							title: 'أنت لم تقم بحفظ التعديلات',
							text: "هل ترغب في الحفظ قبل مغادرة الصفحة؟",
							icon: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'حفظ',
							cancelButtonText: 'إلغاء'
						}).then((result) => {
							if (result.isConfirmed) {
								let saveFormId = getCookie('edited_form');
								let targetForm = $('form[data-form-id="' + saveFormId + '"]');

								if (targetForm.length > 0) {
									$('.submit-type-ajax', targetForm).click();
								}
							} else {
								Swal.fire({
									title: 'هل تريد استكمال التعديلات أو إلغاءها؟',
									text: "يمكنك إلغاء التعديلات والرجوع للخيارات السابقة",
									icon: 'warning',
									showCancelButton: true,
									confirmButtonColor: '#3085d6',
									cancelButtonColor: '#d33',
									confirmButtonText: 'إلغاء',
									cancelButtonText: 'إستكمال'
								}).then((secondResult) => {
									if (secondResult.isConfirmed) {
										location.reload();
									} else {
										window.location.href = targetUrl; // Allow navigation after confirmation
									}
								});
							}
						});
					}
				});

								$( document ).on(
					'click',
					'.popup-trigger',
					function() {
						let cookieValue;
						if ( $(this).hasClass('snks-settings-tab') ) {
							cookieValue = 'snks_account_settings';
						} else {
							cookieValue = $(this).data('id');
						}
						if ( getCookie('edited_form') ) {
							setCookie("next_popup", cookieValue, false);
						}
						if (getCookie( 'edited_withdrawal_form' ) ) {
							Swal.fire({
								title: 'انت لم تقم بحفظ التعديلات، هل تريد الغاءها؟',
								text: "يمكنك إلغاء التعديلات والرجوع للخيارات السابقة",
								icon: 'warning',
								showCancelButton: true,
								confirmButtonColor: '#3085d6',
								cancelButtonColor: '#d33',
								confirmButtonText: 'إلغاء',
								cancelButtonText: 'إستكمال'
							}).then((result) => {
								if (result.isConfirmed) {
									location.reload();
								} else {
									$('.popup-trigger').removeClass('snks-active-popup');
								}
							});
							return;
						}
						if (preventNavigation) {
							Swal.fire({
								title: 'أنت لم تقم بحفظ التعديلات',
								text : "هل ترغب في الحفظ؟",
								icon : 'warning',
								showCancelButton: true,
								confirmButtonColor: '#3085d6',
								cancelButtonColor: '#d33',
								confirmButtonText: 'حفظ',
								cancelButtonText: 'إلغاء'
							}).then((result) => {
								if (result.isConfirmed) {
									var saveFormId = getCookie('edited_form');
									let targetForm = $('form[data-form-id="' + saveFormId + '"]');
									if ( targetForm.length > 0 ) {
										$('.submit-type-ajax', targetForm).click();
									}
								} else {
									Swal.fire({
										title: 'هل تريد استكمال التعديلات أو إلغاءها؟',
										text: "يمكنك إلغاء التعديلات والرجوع للخيارات السابقة",
										icon: 'warning',
										showCancelButton: true,
										confirmButtonColor: '#3085d6',
										cancelButtonColor: '#d33',
										confirmButtonText: 'إلغاء',
										cancelButtonText: 'إستكمال'
									}).then((result) => {
										if (result.isConfirmed) {
											location.reload();
										} else {
											$('.popup-trigger').removeClass('snks-active-popup');
										}
									});
								}
							});
						} else if ( !preventNavigation && ! $(this).hasClass('snks-active-popup') ) {
							$('.popup-trigger').removeClass('snks-active-popup');
							$(this).addClass('snks-active-popup');
							if ( cookieValue != 'snks_account_settings' ) {
								$(this).next('.jet-popup-target').click();
							} else {
								closeAllPopups();
								// Slide #snks_account_settings to the right (off screen)
								$('#snks_account_settings').css({
									transform: 'translateX(0)', // Moves the element off-screen to the right
									display: 'block' // Ensure it's still visible (not hidden)
								});
							}
							
						}
						
					}
				);
				$('.jet-popup-target').on(
					'click',
					function() {
						// Get the attached popup ID from the clicked item
						if ( ($(this).closest('#jet-theme-core-footer').length < 1 && preventNavigation) || $(this).closest('#jet-theme-core-footer').length < 1 ) {
							return;
						}
						var attachedPopup = $(this).data('jet-popup');
						// Check if there is an open popup and its ID does not match the attached popup
						var openPopup = $('.jet-popup--show-state');
						if (openPopup.length > 0 && openPopup.attr('id') !== attachedPopup['attached-popup']) {
							// Trigger the close button click on the currently open popup
							var closeButton = openPopup.find('.jet-popup__close-button');
							if (closeButton.length) {
								closeButton.click();
							}
						}

						// Slide #snks_account_settings to the right (off screen)
						$('#snks_account_settings').css({
							transform: 'translateX(100vw)', // Moves the element off-screen to the right
							display: 'block' // Ensure it's still visible (not hidden)
						});
					}
				);
				$(document).on("jet-form-builder/ajax/on-success", function(event, formData, response) {
					let formId = response[0].dataset.formId;
					exclude = [ 1974, 2067, 2069 ];
					if ( exclude.includes( parseInt( formId ) ) ) {
						setCookie('edited_form', '1956');
						$('form[data-form-id="' + formId + '"]').closest('.jet-popup').find('.jet-popup__close-button').click();
						return;
					}

					// if is add sessions form
					if ( formId == 1956 ) {
						Swal.fire({
							title: 'تم الحفظ بنجاح',
							icon: 'success',
							confirmButtonColor: '#3085d6',
							confirmButtonText: 'إغلاق',
						});
					} else if( formId !== 1956 && formId !== 2199 ) {
						Swal.fire({
							title: 'تم الحفظ بنجاح',
							icon: 'success',
							confirmButtonColor: '#3085d6',
							confirmButtonText: 'إغلاق',
						});
					}
					if ( formId == 2199 ) {
						Swal.fire({
							title: 'إنتبه',
							text: "يرجى العلم أنه لن يتم نشر المواعيد قبل الضغط على زر نشر في صفحة ملخص الجلسات.",
							icon: 'warning',
							showCancelButton: false,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'إغلاق',
						}).then((result) => {
							if (result.isConfirmed) {
								moveToNext();
							}
						});
					} else {
						moveToNext();
					}
				});
				$(document).on(
					'click',
					'a',
					function(e) {
						if ( $(this).closest('#jet-theme-core-footer').length < 1 ) {
							return;
						}
						e.preventDefault();
					}
				);
			});
		</script>
		<?php
	},
	1
);
