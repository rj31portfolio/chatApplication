/**
 * Admin dashboard functionality
 */

// Initialize charts and statistics when the page is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if their containers exist
    if (document.getElementById('conversationsChart')) {
        initConversationsChart();
    }
    
    if (document.getElementById('messagesChart')) {
        initMessagesChart();
    }
    
    if (document.getElementById('responseTimesChart')) {
        initResponseTimesChart();
    }
    
    // Initialize datetime pickers for date ranges
    initDateRangePickers();
    
    // Initialize response editor if it exists
    if (document.getElementById('responseEditor')) {
        initResponseEditor();
    }
    
    // Initialize training interface if it exists
    if (document.getElementById('trainingInterface')) {
        initTrainingInterface();
    }
    
    // Initialize copy-to-clipboard functionality
    initCopyToClipboard();
});

/**
 * Initialize conversations chart
 */
function initConversationsChart() {
    // Get the canvas element
    const ctx = document.getElementById('conversationsChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: getLastNDays(7),
        datasets: [{
            label: 'Conversations',
            data: [12, 19, 15, 17, 14, 21, 25],
            backgroundColor: 'rgba(13, 110, 253, 0.2)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1,
            tension: 0.4
        }]
    };
    
    // Create the chart
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize messages chart
 */
function initMessagesChart() {
    // Get the canvas element
    const ctx = document.getElementById('messagesChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: getLastNDays(7),
        datasets: [
            {
                label: 'User Messages',
                data: [25, 32, 28, 35, 40, 38, 45],
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            },
            {
                label: 'Bot Messages',
                data: [30, 38, 35, 40, 45, 42, 50],
                backgroundColor: 'rgba(25, 135, 84, 0.2)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1
            }
        ]
    };
    
    // Create the chart
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize response times chart
 */
function initResponseTimesChart() {
    // Get the canvas element
    const ctx = document.getElementById('responseTimesChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: ['< 1s', '1-2s', '2-3s', '3-5s', '> 5s'],
        datasets: [{
            label: 'Response Times',
            data: [15, 30, 25, 20, 10],
            backgroundColor: [
                'rgba(25, 135, 84, 0.2)',
                'rgba(13, 110, 253, 0.2)',
                'rgba(255, 193, 7, 0.2)',
                'rgba(220, 53, 69, 0.2)',
                'rgba(108, 117, 125, 0.2)'
            ],
            borderColor: [
                'rgba(25, 135, 84, 1)',
                'rgba(13, 110, 253, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(108, 117, 125, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    // Create the chart
    new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

/**
 * Initialize date range pickers
 */
function initDateRangePickers() {
    // This would typically use a date picker library
    // For simplicity, we're just using basic date inputs
    const dateInputs = document.querySelectorAll('.date-range-picker');
    
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            // This would trigger reloading of chart data based on selected dates
            console.log('Date range changed:', this.value);
            // updateChartData(this.dataset.chart, this.value);
        });
    });
}

/**
 * Get array of the last N days (for chart labels)
 * @param {number} days - Number of days
 * @return {Array} Array of date strings
 */
function getLastNDays(days) {
    const result = [];
    for (let i = days - 1; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        result.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    }
    return result;
}

/**
 * Initialize response editor
 */
function initResponseEditor() {
    const editor = document.getElementById('responseEditor');
    
    // Handle form submission for adding/editing responses
    editor.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Send form data to server via AJAX
        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('Response saved successfully!', 'success');
                
                // Reset form or redirect as needed
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    form.reset();
                }
            } else {
                // Show error message
                showAlert(data.message || 'Error saving response', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while saving the response', 'danger');
        });
    });
    
    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-response');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this response?')) {
                const url = this.getAttribute('href');
                
                fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the response element from the DOM
                        const responseItem = this.closest('.response-item');
                        if (responseItem) {
                            responseItem.remove();
                        }
                        
                        // Show success message
                        showAlert('Response deleted successfully!', 'success');
                    } else {
                        // Show error message
                        showAlert(data.message || 'Error deleting response', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting the response', 'danger');
                });
            }
        });
    });
}

/**
 * Initialize training interface
 */
function initTrainingInterface() {
    const trainingForm = document.getElementById('trainingForm');
    
    // Handle form submission for training
    trainingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Training...';
        
        // Send form data to server via AJAX
        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('Chatbot trained successfully!', 'success');
                
                // Reset form if needed
                if (!data.keepForm) {
                    form.reset();
                }
            } else {
                // Show error message
                showAlert(data.message || 'Error training chatbot', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred during training', 'danger');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
    
    // Handle add example button
    const addExampleButton = document.getElementById('addExampleButton');
    if (addExampleButton) {
        addExampleButton.addEventListener('click', function() {
            const examplesContainer = document.getElementById('examplesContainer');
            const index = document.querySelectorAll('.training-pair').length;
            
            const newExample = document.createElement('div');
            newExample.className = 'training-pair';
            newExample.innerHTML = `
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="userMessage${index}" class="form-label">User Message</label>
                        <textarea class="form-control" id="userMessage${index}" name="user_messages[]" rows="2" required></textarea>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="botResponse${index}" class="form-label">Bot Response</label>
                        <textarea class="form-control" id="botResponse${index}" name="bot_responses[]" rows="2" required></textarea>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mb-3">
                        <button type="button" class="btn btn-danger remove-example">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            
            examplesContainer.appendChild(newExample);
            
            // Add event listener to the new remove button
            const removeButton = newExample.querySelector('.remove-example');
            removeButton.addEventListener('click', function() {
                newExample.remove();
            });
        });
    }
    
    // Handle remove example buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-example')) {
            const button = e.target.closest('.remove-example');
            const example = button.closest('.training-pair');
            example.remove();
        }
    });
}

/**
 * Initialize copy-to-clipboard functionality
 */
function initCopyToClipboard() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.dataset.clipboard;
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Change button text temporarily
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
}

/**
 * Show an alert message
 * @param {string} message - Alert message
 * @param {string} type - Alert type (success, danger, etc.)
 */
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) {
        console.error('Alerts container not found');
        return;
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertsContainer.appendChild(alert);
    
    // Remove the alert after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => {
            alertsContainer.removeChild(alert);
        }, 150);
    }, 5000);
}
