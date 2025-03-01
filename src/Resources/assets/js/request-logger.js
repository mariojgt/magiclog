document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    const dateRangePicker = document.querySelector('input[name="date_range"]');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });
    }

    // Live filter functionality
    const filterForm = document.getElementById('request-logger-filter');
    if (filterForm) {
        filterForm.addEventListener('change', function() {
            this.submit();
        });
    }

    // Real-time statistics update
    function updateRealTimeStats() {
        fetch('/request-logger/stats')
            .then(response => response.json())
            .then(data => {
                // Update dashboard cards
                document.getElementById('total-requests').textContent = data.total_requests;
                document.getElementById('requests-today').textContent = data.requests_today;
                document.getElementById('avg-response-time').textContent = data.avg_response_time + 'ms';
            })
            .catch(error => console.error('Error updating stats:', error));
    }

    // Periodically update stats every 30 seconds
    setInterval(updateRealTimeStats, 30000);

    // Log details modal
    const logRows = document.querySelectorAll('.log-row');
    logRows.forEach(row => {
        row.addEventListener('click', function() {
            const logId = this.dataset.logId;
            fetch(`/request-logger/log/${logId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate modal with log details
                    const modal = document.getElementById('log-details-modal');
                    modal.querySelector('.modal-body').innerHTML = `
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                });
        });
    });
});
