/**
 * AI Prescription System JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Handle prescription button click
    $(document).on('click', '.snks-prescription-button', function(e) {
        e.preventDefault();
        
        const sessionId = $(this).data('session-id');
        showPrescriptionConfirmation(sessionId);
    });

    // Handle referral reason button click
    $(document).on('click', '.snks-referral-reason-button', function(e) {
        e.preventDefault();
        
        const bookingId = $(this).data('booking-id');
        showReferralReason(bookingId);
    });

    /**
     * Show prescription confirmation dialog
     */
    function showPrescriptionConfirmation(sessionId) {
        const confirmDialog = `
            <div class="snks-modal-overlay">
                <div class="snks-modal">
                    <div class="snks-modal-header">
                        <h3>${snks_ai_prescription.strings.confirm_medication}</h3>
                        <button class="snks-modal-close">&times;</button>
                    </div>
                    <div class="snks-modal-body">
                        <div class="snks-prescription-options">
                            <button class="snks-button snks-button-primary snks-prescription-yes" data-session-id="${sessionId}">
                                ${snks_ai_prescription.strings.yes}
                            </button>
                            <button class="snks-button snks-button-secondary snks-prescription-no">
                                ${snks_ai_prescription.strings.no}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(confirmDialog);
    }

    /**
     * Show prescription form dialog
     */
    function showPrescriptionForm(sessionId) {
        const formDialog = `
            <div class="snks-modal-overlay">
                <div class="snks-modal snks-modal-large">
                    <div class="snks-modal-header">
                        <h3>${snks_ai_prescription.strings.preliminary_diagnosis}</h3>
                        <button class="snks-modal-close">&times;</button>
                    </div>
                    <div class="snks-modal-body">
                        <form class="snks-prescription-form">
                            <div class="snks-form-group">
                                <label for="preliminary_diagnosis">${snks_ai_prescription.strings.preliminary_diagnosis}</label>
                                <textarea id="preliminary_diagnosis" name="preliminary_diagnosis" required rows="4"></textarea>
                            </div>
                            <div class="snks-form-group">
                                <label for="symptoms">${snks_ai_prescription.strings.symptoms}</label>
                                <textarea id="symptoms" name="symptoms" required rows="4"></textarea>
                            </div>
                            <div class="snks-form-actions">
                                <button type="submit" class="snks-button snks-button-primary">
                                    ${snks_ai_prescription.strings.request}
                                </button>
                                <button type="button" class="snks-button snks-button-secondary snks-modal-close">
                                    ${snks_ai_prescription.strings.cancel}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(formDialog);
    }

    /**
     * Show referral reason dialog
     */
    function showReferralReason(bookingId) {
        // Show loading state
        const loadingDialog = `
            <div class="snks-modal-overlay">
                <div class="snks-modal">
                    <div class="snks-modal-header">
                        <h3>Reason for Referral</h3>
                        <button class="snks-modal-close">&times;</button>
                    </div>
                    <div class="snks-modal-body">
                        <div class="snks-loading">Loading...</div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(loadingDialog);

        // Fetch referral reason data
        $.ajax({
            url: snks_ai_prescription.ajax_url,
            type: 'POST',
            data: {
                action: 'get_rochtah_referral_reason',
                booking_id: bookingId,
                nonce: snks_ai_prescription.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const reasonDialog = `
                        <div class="snks-modal-overlay">
                            <div class="snks-modal">
                                <div class="snks-modal-header">
                                    <h3>Reason for Referral</h3>
                                    <button class="snks-modal-close">&times;</button>
                                </div>
                                <div class="snks-modal-body">
                                    <div class="snks-referral-reason">
                                        <div class="snks-form-group">
                                            <label>Preliminary Diagnosis:</label>
                                            <div class="snks-reason-content">${data.preliminary_diagnosis}</div>
                                        </div>
                                        <div class="snks-form-group">
                                            <label>Symptoms:</label>
                                            <div class="snks-reason-content">${data.symptoms}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    $('.snks-modal-overlay').remove();
                    $('body').append(reasonDialog);
                } else {
                    showError('Failed to load referral reason');
                }
            },
            error: function() {
                showError('Failed to load referral reason');
            }
        });
    }

    /**
     * Handle prescription form submission
     */
    $(document).on('submit', '.snks-prescription-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const sessionId = form.closest('.snks-modal').find('.snks-prescription-yes').data('session-id');
        const formData = {
            action: 'ai_prescription_request',
            session_id: sessionId,
            preliminary_diagnosis: form.find('#preliminary_diagnosis').val(),
            symptoms: form.find('#symptoms').val(),
            nonce: snks_ai_prescription.nonce
        };

        // Show loading state
        form.find('button[type="submit"]').prop('disabled', true).text('Submitting...');

        $.ajax({
            url: snks_ai_prescription.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    // Update button to show requested state
                    $(`.snks-prescription-button[data-session-id="${sessionId}"]`)
                        .removeClass('snks-prescription-button')
                        .addClass('snks-prescription-requested')
                        .prop('disabled', true)
                        .text('Prescription Requested');
                } else {
                    showError(response.data || snks_ai_prescription.strings.error_message);
                }
            },
            error: function() {
                showError(snks_ai_prescription.strings.error_message);
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false).text(snks_ai_prescription.strings.request);
            }
        });
    });

    /**
     * Handle modal close
     */
    $(document).on('click', '.snks-modal-close, .snks-modal-overlay', function(e) {
        if (e.target === this) {
            $(this).closest('.snks-modal-overlay').remove();
        }
    });

    /**
     * Handle prescription yes button
     */
    $(document).on('click', '.snks-prescription-yes', function(e) {
        e.preventDefault();
        const sessionId = $(this).data('session-id');
        $('.snks-modal-overlay').remove();
        showPrescriptionForm(sessionId);
    });

    /**
     * Handle prescription no button
     */
    $(document).on('click', '.snks-prescription-no', function(e) {
        e.preventDefault();
        $('.snks-modal-overlay').remove();
    });

    /**
     * Show success message
     */
    function showSuccess(message) {
        const successDialog = `
            <div class="snks-modal-overlay">
                <div class="snks-modal">
                    <div class="snks-modal-header">
                        <h3>Success</h3>
                        <button class="snks-modal-close">&times;</button>
                    </div>
                    <div class="snks-modal-body">
                        <div class="snks-success-message">${message}</div>
                    </div>
                </div>
            </div>
        `;

        $('.snks-modal-overlay').remove();
        $('body').append(successDialog);

        // Auto close after 3 seconds
        setTimeout(function() {
            $('.snks-modal-overlay').remove();
        }, 3000);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const errorDialog = `
            <div class="snks-modal-overlay">
                <div class="snks-modal">
                    <div class="snks-modal-header">
                        <h3>Error</h3>
                        <button class="snks-modal-close">&times;</button>
                    </div>
                    <div class="snks-modal-body">
                        <div class="snks-error-message">${message}</div>
                    </div>
                </div>
            </div>
        `;

        $('.snks-modal-overlay').remove();
        $('body').append(errorDialog);
    }
});
