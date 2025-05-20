/**
 * Super Admin dashboard functionality
 */

// Initialize charts and statistics when the page is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if their containers exist
    if (document.getElementById('businessesChart')) {
        initBusinessesChart();
    }
    
    if (document.getElementById('messagesDistributionChart')) {
        initMessagesDistributionChart();
    }
    
    if (document.getElementById('businessTypeChart')) {
        initBusinessTypeChart();
    }
    
    // Initialize datetime pickers for date ranges
    initDateRangePickers();
    
    // Initialize business management interfaces
    initBusinessManagement();
    
    // Initialize user management interfaces
    initUserManagement();
    
    // Initialize system logs viewer
    initSystemLogs();
});

/**
 * Initialize businesses chart
 */
function initBusinessesChart() {
    // Get the canvas element
    const ctx = document.getElementById('businessesChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: getLastNMonths(6),
        datasets: [{
            label: 'Active Businesses',
            data: [8, 10, 12, 15, 18, 22],
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
 * Initialize messages distribution chart
 */
function initMessagesDistributionChart() {
    // Get the canvas element
    const ctx = document.getElementById('messagesDistributionChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: getLastNDays(7),
        datasets: [{
            label: 'Total Messages',
            data: [250, 320, 280, 350, 400, 380, 450],
            backgroundColor: 'rgba(13, 110, 253, 0.2)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1
        }]
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
 * Initialize business type chart
 */
function initBusinessTypeChart() {
    // Get the canvas element
    const ctx = document.getElementById('businessTypeChart').getContext('2d');
    
    // Sample data - this would be populated from the server
    const data = {
        labels: ['Restaurant', 'E-commerce', 'Service Provider', 'Healthcare', 'Education', 'Finance', 'Other'],
        datasets: [{
            label: 'Business Types',
            data: [5, 8, 4, 3, 2, 1, 2],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(199, 199, 199, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    // Create the chart
    new Chart(ctx, {
        type: 'doughnut',
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
 * Get array of the last N months (for chart labels)
 * @param {number} months - Number of months
 * @return {Array} Array of month strings
 */
function getLastNMonths(months) {
    const result = [];
    for (let i = months - 1; i >= 0; i--) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        result.push(date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
    }
    return result;
}

/**
 * Initialize business management interfaces
 */
function initBusinessManagement() {
    // Handle form submission for adding/editing businesses
    const businessForm = document.getElementById('businessForm');
    if (businessForm) {
        businessForm.addEventListener('submit', function(e) {
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
                    showAlert('Business saved successfully!', 'success');
                    
                    // Reset form or redirect as needed
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        form.reset();
                    }
                } else {
                    // Show error message
                    showAlert(data.message || 'Error saving business', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while saving the business', 'danger');
            });
        });
    }
    
    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-business');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this business? This action cannot be undone.')) {
                const url = this.getAttribute('href');
                
                fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the business row from the table
                        const row = this.closest('tr');
                        if (row) {
                            row.remove();
                        }
                        
                        // Show success message
                        showAlert('Business deleted successfully!', 'success');
                    } else {
                        // Show error message
                        showAlert(data.message || 'Error deleting business', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting the business', 'danger');
                });
            }
        });
    });
}

/**
 * Initialize user management interfaces
 */
function initUserManagement() {
    // Handle form submission for adding/editing users
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
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
                    showAlert('User saved successfully!', 'success');
                    
                    // Reset form or redirect as needed
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        form.reset();
                    }
                } else {
                    // Show error message
                    showAlert(data.message || 'Error saving user', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while saving the user', 'danger');
            });
        });
    }
    
    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-user');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const url = this.getAttribute('href');
                
                fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the user row from the table
                        const row = this.closest('tr');
                        if (row) {
                            row.remove();
                        }
                        
                        // Show success message
                        showAlert('User deleted successfully!', 'success');
                    } else {
                        // Show error message
                        showAlert(data.message || 'Error deleting user', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting the user', 'danger');
                });
            }
        });
    });
}

/**
 * Initialize system logs viewer
 */
function initSystemLogs() {
    // Handle log filter form submission
    const logFilterForm = document.getElementById('logFilterForm');
    if (logFilterForm) {
        logFilterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            // Redirect to the same page with filter parameters
            window.location.href = `${form.action}?${params.toString()}`;
        });
    }
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
