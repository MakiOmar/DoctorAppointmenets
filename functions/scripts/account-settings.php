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
		if ( ! is_page( 'account-setting' ) ) {
			return;
		}
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				function setCookie(name, value, days) {
					var expires = "";
					if (days) {
						var date = new Date();
						date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
						expires = "; expires=" + date.toUTCString();
					} else if (days === 0) {
						// If days is 0, set the expiration to a past date to delete the cookie
						expires = "; expires=Thu, 01 Jan 1970 00:00:00 UTC";
					}
					document.cookie = name + "=" + (value || "") + expires + "; path=/";
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
				// Define the URL where you want to prompt the user
				const accountSettingUrlPath = '/account-setting';
				var confirmationMessage = "يرجى التأكد من حفظ الإعدادات، هل أنت متأكد؟";

				var preventNavigation = false; // Flag to control when to prompt

				// Function to set preventNavigation to true when a form is changed
				function setFormChanged( form ) {
					preventNavigation = true;
					setCookie('edited_form', form.getAttribute('data-form-id'));
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
				// Add event listeners for any interaction with form fields
				var confirmSave = document.querySelectorAll('.snks-confirm');
				listenToForms( confirmSave );

				// Select all forms with the class 'jet-form-builder'
				var forms = document.querySelectorAll('.jet-form-builder');
				listenToForms( forms );

				$(window).on('jet-popup/show-event/after-show', function(){
					var forms = document.querySelectorAll('.jet-form-builder');
					listenToForms( forms );
				});

				// Prompt the user when trying to leave the page (refresh, close tab, etc.)
				/*window.addEventListener('beforeunload', function (e) {
					if (preventNavigation) {
						e.returnValue = confirmationMessage; // For most browsers
						return confirmationMessage;          // Some older browsers
					}
				});*/

				$( document ).on('click', '.jet-form-builder-repeater__new', function(){
					preventNavigation = true;
					setCookie('edited_form', $(this).closest('form').data('form-id'));
				});
				$(window).on('jet-popup/show-event/after-show', function(){
					preventNavigation = false;
					setCookie("next_popup", '', 0);
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
						if (preventNavigation) {
							setCookie("next_popup", cookieValue, false);
							Swal.fire({
								title: 'هل انت متأكد؟',
								text: "لم تقم بحفظ خياراتك",
								icon: 'warning',
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
										let ajaxButton = targetForm.find('.submit-type-ajax');
										ajaxButton.click();
									}
								} else {
									Swal.fire({
										title: 'هل تريد التراجع؟',
										text: "يمكنك إلغاء الخيارات والرجوع للخيارات السابقة",
										icon: 'warning',
										showCancelButton: true,
										confirmButtonColor: '#3085d6',
										cancelButtonColor: '#d33',
										confirmButtonText: 'نعم تراجع',
										cancelButtonText: 'أغلق النافذة فقط'
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
					// if is add sessions form
					if ( formId == 2199 ) {
						Swal.fire({
								title: 'مستعد لنشر المواعيد؟',
								text: "يجب عليك الذهاب لملخص المواعيد للنشر",
								icon: 'warning',
								showCancelButton: true,
								confirmButtonColor: '#3085d6',
								cancelButtonColor: '#d33',
								confirmButtonText: 'ملخص الجلسات',
								cancelButtonText: 'إلغاء'
							}).then((result) => {
								if (result.isConfirmed) {
									$('.snks_tab-preview').trigger('click');
								}
							});
					}
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
