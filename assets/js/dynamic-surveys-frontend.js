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

    // Handle vote submission
    $('.dynamic-surveys-vote-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const surveyId = $form.data('survey-id');
        const selectedOption = $form.find('input[name="survey_option"]:checked').val();
        const surveyQuestion = $form.closest('.dynamic-surveys-form').find('h3').text();
        
        if (!selectedOption) {
            toastr.warning(wp.i18n.__('Please select an option to vote', 'dynamic-surveys'));
            return;
        }
        
        const $submitButton = $form.find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: dynamic_surveys_frontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dynamic_surveys_submit_vote',
                nonce: dynamic_surveys_frontend.nonce,
                survey_id: surveyId,
                option: selectedOption
            },
            success: function(response) {
                if (response.success) {
                    // Create results HTML structure
                    const resultsHtml = `
                        <div class="dynamic-surveys-results">
                            <h3>${escapeHtml(surveyQuestion)}</h3>
                            <div class="dynamic-surveys-results-wrapper">
                                <canvas id="dynamic-surveys-results-chart-${surveyId}"></canvas>
                            </div>
                            <p class="dynamic-surveys-vote-message">
                                ${wp.i18n.__('Thank you for participating in this survey!', 'dynamic-surveys')}
                            </p>
                        </div>
                    `;

                    // Replace the form container with results
                    $form.closest('.dynamic-surveys-form').replaceWith(resultsHtml);
                    
                    // Prepare chart data
                    const labels = [];
                    const data = [];
                    const backgroundColor = [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ];
                    
                    response.data.results.forEach(function(result) {
                        labels.push(result.option_id);
                        data.push(result.count);
                    });
                    
                    // Create chart
                    const ctx = document.getElementById(`dynamic-surveys-results-chart-${surveyId}`).getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: backgroundColor
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                    
                    toastr.success(response.data.message);
                } else {
                    toastr.error(response.data.message || wp.i18n.__('Error submitting vote', 'dynamic-surveys'));
                    $submitButton.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error(wp.i18n.__('Error submitting vote. Please try again.', 'dynamic-surveys'));
                $submitButton.prop('disabled', false);
            }
        });
    });

    // Initialize existing results charts
    $('.dynamic-surveys-results canvas').each(function() {
        const $canvas = $(this);
        const results = $canvas.data('results');
        
        if (results) {
            const ctx = this.getContext('2d');
            new Chart(ctx, results);
            toastr.info(wp.i18n.__('Survey results loaded', 'dynamic-surveys'));
        }
    });
}); 