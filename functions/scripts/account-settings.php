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
				// Define the URL where you want to prompt the user
				const accountSettingUrlPath = '/account-setting';
				var confirmationMessage = "يرجى التأكد من حفظ الإعدادات، هل أنت متأكد؟";

				var preventNavigation = false; // Flag to control when to prompt

				// Function to set preventNavigation to true when a form is changed
				function setFormChanged() {
					preventNavigation = true;
				}

				// Add event listeners for any interaction with form fields
				const confirmSave = document.querySelector('.snks-confirm');
				// Select all forms with the class 'jet-form-builder'
				var forms = document.querySelectorAll('.jet-form-builder');

				if (forms.length > 0) {
					forms.forEach(function (form) {
						// Add event listeners to all input, select, and textarea fields in each form
						form.querySelectorAll('input, select, textarea').forEach(function (field) {
							console.log('changed');
							field.addEventListener('input', setFormChanged);    // Text inputs
							field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
						});
					});
				}

				$(window).on('jet-popup/show-event/after-show', function(){
					var forms = document.querySelectorAll('.jet-form-builder');

					if (forms.length > 0) {
						forms.forEach(function (form) {
							// Add event listeners to all input, select, and textarea fields in each form
							form.querySelectorAll('input, select, textarea').forEach(function (field) {
								console.log('changed');
								field.addEventListener('input', setFormChanged);    // Text inputs
								field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
							});
						});
					}
				});

				if (confirmSave) {
					confirmSave.querySelectorAll('input, select, textarea').forEach(function (field) {
						field.addEventListener('input', setFormChanged);    // Text inputs
						field.addEventListener('change', setFormChanged);   // Dropdowns, checkboxes, etc.
					});
				}

				// Prompt the user when trying to leave the page (refresh, close tab, etc.)
				window.addEventListener('beforeunload', function (e) {
					if (preventNavigation) {
						e.returnValue = confirmationMessage; // For most browsers
						return confirmationMessage;          // Some older browsers
					}
				});
				
				// Handle the case when the user clicks on a link within the page
				document.querySelectorAll('.popup-trigger').forEach(function (link) {
					link.addEventListener('click', function (e) {
						
					});
				});

				$( document ).on('click', '.jet-form-builder-repeater__new', function(){
					preventNavigation = true;
				});
				$(window).on('jet-popup/show-event/after-show', function(){
					preventNavigation = false;
				});
				$( document ).on(
					'click',
					'.popup-trigger',
					function() {
						
						if (preventNavigation) {
							var confirmation = confirm(confirmationMessage);
							if (confirmation) {
								preventNavigation = false; // Allow navigation and turn off the beforeunload prompt
								$('.popup-trigger').removeClass('snks-active-popup');
								$(this).addClass('snks-active-popup');
								$(this).next('.trigger-popup').click();
							} else {
								$('.popup-trigger').removeClass('snks-active-popup');
							}
						} else if ( !preventNavigation && ! $(this).hasClass('snks-active-popup') ) {
							$('.popup-trigger').removeClass('snks-active-popup');
							$(this).addClass('snks-active-popup');
							$(this).next('.trigger-popup').click();
						}
					}
				);
				$(document).on("jet-form-builder/ajax/on-success", function(event, formData, response) {
					preventNavigation = false;
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
				$('.jet-popup-target').on(
					'click',
					function() {
						if ( ($(this).closest('#jet-theme-core-footer').length < 1 && preventNavigation) || $(this).closest('#jet-theme-core-footer').length < 1 ) {
							return;
						}

						// Get the attached popup ID from the clicked item
						var attachedPopup = $(this).data('jet-popup');

						// Check if there is an open popup and its ID does not match the attached popup
						var openPopup = $('.jet-popup--show-state');
						if (openPopup.length && openPopup.attr('id') !== attachedPopup) {
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

				$('.snks-settings-tab a').on('click', function(event) {
					event.preventDefault();
				});

				$(document).on(
					'click',
					'.snks-settings-tab',
					function(){
						if (preventNavigation) {
							var confirmation = confirm(confirmationMessage);
							if ( confirmation) {
								var openPopup = $('.jet-popup--show-state');
								if (openPopup.length ) {
									// Trigger the close button click on the currently open popup
									var closeButton = openPopup.find('.jet-popup__close-button');
									if (closeButton.length) {
										closeButton.click();
									}
								}
								$('#snks_account_settings').css({
									transform: 'translateX(0)', // Moves the element back to its original position
									display: 'block' // Ensure it's visible
								});
								$('.popup-trigger').removeClass('snks-active-popup');
								preventNavigation = false;
							}
						} else {
							var openPopup = $('.jet-popup--show-state');
							if (openPopup.length ) {
								// Trigger the close button click on the currently open popup
								var closeButton = openPopup.find('.jet-popup__close-button');
								if (closeButton.length) {
									closeButton.click();
								}
							}
							$('#snks_account_settings').css({
								transform: 'translateX(0)', // Moves the element back to its original position
								display: 'block' // Ensure it's visible
							});
							$('.popup-trigger').removeClass('snks-active-popup');
						}
						

					}
				);
			});
		</script>
		<?php
	},
	1
);
