jQuery(document).ready(function($) {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "timeOut": "3000"
    };

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Use a single event delegation for all dynamic elements
    const $adminWrap = $('.dynamic-surveys-admin-wrap');

    // Add new option - single row
    $adminWrap.on('click', '#dynamic-surveys-add-option', function(e) {
        e.preventDefault();
        const newOption = `
            <div class="option-row">
                <input type="text" name="options[]" required>
                <button type="button" class="dynamic-surveys-remove-option">${wp.i18n.__('Remove', 'dynamic-surveys')}</button>
            </div>
        `;
        $('#dynamic-surveys-options').append(newOption);
        toastr.success(wp.i18n.__('New option added', 'dynamic-surveys'));
    });

    // Remove option using event delegation
    $adminWrap.on('click', '.dynamic-surveys-remove-option', function(e) {
        e.preventDefault();
        const totalOptions = $('#dynamic-surveys-options .option-row').length;
        if (totalOptions > 2) {
            $(this).closest('.option-row').remove();
            toastr.info(wp.i18n.__('Option removed', 'dynamic-surveys'));
        } else {
            toastr.warning(wp.i18n.__('A survey must have at least two options.', 'dynamic-surveys'));
        }
    });

    // Handle survey creation
    const $createSurveyForm = $('#dynamic-surveys-create-survey');
    let isSubmitting = false;

    // Remove any existing submit handlers first
    $createSurveyForm.off('submit');

    // Add the submit handler
    $createSurveyForm.on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        if (isSubmitting) {
            return false;
        }
        
        // Validate if all options are filled
        let emptyOptions = false;
        $('#dynamic-surveys-options input[type="text"]').each(function() {
            if (!$(this).val().trim()) {
                emptyOptions = true;
                return false;
            }
        });

        if (emptyOptions) {
            toastr.error(wp.i18n.__('Please fill in all option fields', 'dynamic-surveys'));
            return false;
        }
        
        isSubmitting = true;
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        const formData = new FormData(this);
        formData.append('action', 'dynamic_surveys_create_survey');
        formData.append('nonce', dynamic_surveys_admin.nonce);
        
        $.ajax({
            url: dynamic_surveys_admin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $createSurveyForm[0].reset();
                    
                    $('#dynamic-surveys-options').html(`
                        <div class="option-row">
                            <input type="text" name="options[]" required>
                            <button type="button" class="dynamic-surveys-remove-option">${wp.i18n.__('Remove', 'dynamic-surveys')}</button>
                        </div>
                        <div class="option-row">
                            <input type="text" name="options[]" required>
                            <button type="button" class="dynamic-surveys-remove-option">${wp.i18n.__('Remove', 'dynamic-surveys')}</button>
                        </div>
                    `);
                    
                    const survey = response.data.survey;
                    const newRow = `
                        <tr>
                            <td>${escapeHtml(survey.title)}</td>
                            <td>${escapeHtml(survey.question)}</td>
                            <td>${escapeHtml(survey.status)}</td>
                            <td><code title="${wp.i18n.__('Click to copy shortcode', 'dynamic-surveys')}">[dynamic_surveys id="${escapeHtml(survey.id)}"]</code></td>
                            <td>
                                <button class="button dynamic-surveys-delete-survey" data-id="${escapeHtml(survey.id)}">
                                    ${wp.i18n.__('Delete', 'dynamic-surveys')}
                                </button>
                                <button class="button dynamic-surveys-toggle-status" data-id="${escapeHtml(survey.id)}">
                                    ${wp.i18n.__('Close', 'dynamic-surveys')}
                                </button>
                            </td>
                        </tr>
                    `;
                    $('.dynamic-surveys-table tbody').prepend(newRow);
                    
                    toastr.success(response.data.message || wp.i18n.__('Survey created successfully!', 'dynamic-surveys'));
                } else {
                    toastr.error(response.data.message || wp.i18n.__('Error creating survey', 'dynamic-surveys'));
                }
            },
            error: function() {
                toastr.error(wp.i18n.__('Error creating survey. Please try again.', 'dynamic-surveys'));
            },
            complete: function() {
                setTimeout(() => {
                    isSubmitting = false;
                    $submitButton.prop('disabled', false);
                }, 1000);
            }
        });

        return false;
    });

    // Handle survey status toggle
    $(document).on('click', '.dynamic-surveys-toggle-status', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        if ($button.data('processing')) return;
        
        $button.data('processing', true);
        const surveyId = $button.data('id');
        const currentStatus = $button.text().toLowerCase();
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: dynamic_surveys_admin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dynamic_surveys_toggle_survey_status',
                nonce: dynamic_surveys_admin.nonce,
                survey_id: surveyId,
                current_status: currentStatus === 'close' ? 'open' : 'closed'
            },
            success: function(response) {
                if (response.success) {
                    // Update button text
                    const newStatus = response.data.new_status;
                    $button.text(newStatus === 'open' ? wp.i18n.__('Close', 'dynamic-surveys') : wp.i18n.__('Open', 'dynamic-surveys'));
                    
                    // Update status cell
                    $button.closest('tr').find('td:nth-child(3)').text(newStatus);
                    
                    toastr.success(wp.i18n.__('Survey status updated successfully', 'dynamic-surveys'));
                } else {
                    toastr.error(response.data.message || wp.i18n.__('Failed to update survey status', 'dynamic-surveys'));
                }
            },
            error: function() {
                toastr.error(wp.i18n.__('Error updating survey status. Please try again.', 'dynamic-surveys'));
            },
            complete: function() {
                $button.data('processing', false);
                $button.prop('disabled', false);
            }
        });
    });

    // Handle shortcode copying
    $(document).on('click', '.dynamic-surveys-table code', function() {
        const shortcode = $(this).text();
        
        // Create temporary textarea
        const $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(shortcode).select();
        
        try {
            // Execute copy command
            document.execCommand("copy");
            toastr.success(wp.i18n.__('Shortcode copied to clipboard!', 'dynamic-surveys'));
        } catch (err) {
            toastr.error(wp.i18n.__('Failed to copy shortcode', 'dynamic-surveys'));
            console.error('Copy failed:', err);
        }
        
        // Remove temporary textarea
        $temp.remove();
    });

    // Handle survey deletion
    $(document).on('click', '.dynamic-surveys-delete-survey', function(e) {
        e.preventDefault();
        
        if (!confirm(wp.i18n.__('Are you sure you want to delete this survey?', 'dynamic-surveys'))) {
            return;
        }
        
        const $button = $(this);
        if ($button.data('processing')) return;
        
        $button.data('processing', true);
        const surveyId = $button.data('id');
        const $row = $button.closest('tr');
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: dynamic_surveys_admin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dynamic_surveys_delete_survey',
                nonce: dynamic_surveys_admin.nonce,
                survey_id: surveyId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });
                    toastr.success(wp.i18n.__('Survey deleted successfully', 'dynamic-surveys'));
                } else {
                    toastr.error(response.data.message || wp.i18n.__('Failed to delete survey', 'dynamic-surveys'));
                }
            },
            error: function() {
                toastr.error(wp.i18n.__('Error deleting survey. Please try again.', 'dynamic-surveys'));
            },
            complete: function() {
                $button.data('processing', false);
                $button.prop('disabled', false);
            }
        });
    });

    $('.dynamic-surveys-export-csv').on('click', function(e) {
        e.preventDefault();
        const surveyId = $(this).data('survey-id');
        exportSurvey(surveyId);
    });

    function exportSurvey(surveyId) {
        jQuery.ajax({
            url: dynamic_surveys_admin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dynamic_surveys_export_survey',
                nonce: dynamic_surveys_admin.nonce,
                survey_id: surveyId
            },
            success: function(response) {
                if (response.success) {
                    // Handle successful export
                    const csvContent = response.data.data.map(row => row.join(',')).join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    toastr.success(wp.i18n.__('Export completed successfully', 'dynamic-surveys'));
                } else {
                    toastr.error(response.data.message || wp.i18n.__('Export failed', 'dynamic-surveys'));
                }
            },
            error: function() {
                toastr.error(wp.i18n.__('Export failed. Please try again.', 'dynamic-surveys'));
            }
        });
    }
}); 