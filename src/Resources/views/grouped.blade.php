@extends('request-logger::layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Grouped Request Logs</h1>
                <span class="text-muted">Grouped by: {{ ucfirst($groupBy) }}</span>
                <div class="actions d-flex">
                    <div class="dropdown mr-2">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="icon icon-download mr-1"></span> Export
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
                            <a class="dropdown-item" href="{{ route('request-logger.export', array_merge(['format' => 'csv'], request()->all())) }}">CSV</a>
                            <a class="dropdown-item" href="{{ route('request-logger.export', array_merge(['format' => 'json'], request()->all())) }}">JSON</a>
                        </div>
                    </div>
                    <a href="{{ route('request-logger.index') }}" class="btn btn-secondary mr-2">
                        <span class="icon icon-list mr-1"></span> Show Normal View
                    </a>
                    <button class="btn btn-danger" id="purge-logs-btn" data-toggle="modal" data-target="#purgeLogsModal">
                        <span class="icon icon-trash mr-1"></span> Purge Old Logs
                    </button>
                </div>
            </div>

            {{-- Include the filters component --}}
            @include('request-logger::partials.filters')

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Response Time Distribution</h5>
                            <div class="chart-container" style="position: relative; height: 300px;">
                                <canvas id="response-time-distribution"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Request Count Distribution</h5>
                            <div class="chart-container" style="position: relative; height: 300px;">
                                <canvas id="request-count-comparison"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grouped Results --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Grouped Results: {{ ucfirst($groupBy) }}</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="grouped-logs-table">
                            <thead>
                                <tr>
                                    @if($groupBy == 'endpoint')
                                        <th>Endpoint</th>
                                    @elseif($groupBy == 'method')
                                        <th>Method</th>
                                    @elseif($groupBy == 'status')
                                        <th>Status</th>
                                    @elseif($groupBy == 'ip')
                                        <th>IP Address</th>
                                    @elseif($groupBy == 'user')
                                        <th>User ID</th>
                                    @endif
                                    <th>Count</th>
                                    <th>Avg. Response Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupedLogs as $log)
                                <tr>
                                    @if($groupBy == 'endpoint')
                                        <td class="truncate-text" title="{{ $log->path }}">{{ $log->path }}</td>
                                    @elseif($groupBy == 'method')
                                        <td>
                                            <span class="badge badge-{{ strtolower($log->method) }}">
                                                {{ $log->method }}
                                            </span>
                                        </td>
                                    @elseif($groupBy == 'status')
                                        <td>
                                            <span class="badge badge-{{ $log->response_status >= 200 && $log->response_status < 300 ? 'success' : ($log->response_status >= 300 && $log->response_status < 400 ? 'warning' : 'danger') }}">
                                                {{ $log->response_status }}
                                            </span>
                                        </td>
                                    @elseif($groupBy == 'ip')
                                        <td>{{ $log->ip_address }}</td>
                                    @elseif($groupBy == 'user')
                                        <td>{{ $log->user_id }}</td>
                                    @endif
                                    <td>{{ $log->count }}</td>
                                    <td>
                                        <span class="{{ $log->avg_time > 1000 ? 'text-danger' : ($log->avg_time > 500 ? 'text-warning' : '') }}">
                                            {{ round($log->avg_time, 2) }} ms
                                        </span>
                                    </td>
                                    <td>
                                        @if($groupBy == 'endpoint')
                                            <a href="{{ route('request-logger.index', ['path' => $log->path]) }}" class="btn btn-sm btn-info">
                                                View Requests
                                            </a>
                                        @elseif($groupBy == 'method')
                                            <a href="{{ route('request-logger.index', ['method' => $log->method]) }}" class="btn btn-sm btn-info">
                                                View Requests
                                            </a>
                                        @elseif($groupBy == 'status')
                                            <a href="{{ route('request-logger.index', ['status' => $log->response_status]) }}" class="btn btn-sm btn-info">
                                                View Requests
                                            </a>
                                        @elseif($groupBy == 'ip')
                                            <a href="{{ route('request-logger.index', ['ip_address' => $log->ip_address]) }}" class="btn btn-sm btn-info">
                                                View Requests
                                            </a>
                                        @elseif($groupBy == 'user')
                                            <a href="{{ route('request-logger.index', ['user_id' => $log->user_id]) }}" class="btn btn-sm btn-info">
                                                View Requests
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No logs found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $groupedLogs->firstItem() ?? 0 }} to {{ $groupedLogs->lastItem() ?? 0 }} of {{ $groupedLogs->total() }} entries
                        </div>
                        <div>
                            {{ $groupedLogs->appends(request()->except('page'))->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Purge Logs Modal --}}
    <div class="modal fade" id="purgeLogsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purge Old Logs</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>This will permanently delete old logs from the database. This action cannot be undone.</p>

                    <form id="purge-logs-form">
                        <div class="form-group">
                            <label for="days-to-keep">Keep logs for how many days?</label>
                            <div class="d-flex align-items-center">
                                <input type="range" class="form-control-range mr-3" id="days-to-keep" name="days" min="1" max="365" value="30" style="flex: 1;">
                                <span id="days-value" class="badge badge-primary" style="width: 60px; text-align: center;">30 days</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-purge-logs">Purge Logs</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the charts
    initializeCharts();

    // Initialize dropdowns
    document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const menu = this.nextElementSibling;
            if (!menu || !menu.classList.contains('dropdown-menu')) return;

            // Close all other open dropdowns first
            document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                if (openMenu !== menu) {
                    openMenu.classList.remove('show');
                }
            });

            // Toggle this dropdown
            menu.classList.toggle('show');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                openMenu.classList.remove('show');
            });
        }
    });

    // Initialize days to keep slider
    const daysToKeepSlider = document.getElementById('days-to-keep');
    const daysValue = document.getElementById('days-value');

    if (daysToKeepSlider && daysValue) {
        daysToKeepSlider.addEventListener('input', function() {
            daysValue.textContent = this.value + ' days';
        });
    }

    // Purge logs functionality
    const confirmPurgeBtn = document.getElementById('confirm-purge-logs');
    if (confirmPurgeBtn) {
        confirmPurgeBtn.addEventListener('click', function() {
            const days = document.getElementById('days-to-keep').value;
            purgeOldLogs(days);
        });
    }

    // Function to purge old logs
    function purgeOldLogs(days) {
        const confirmPurgeBtn = document.getElementById('confirm-purge-logs');
        if (!confirmPurgeBtn) return;

        // Show loading state
        confirmPurgeBtn.disabled = true;
        confirmPurgeBtn.innerHTML = '<span class="spinner"></span> Processing...';

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/request-logger/purge-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ days: days })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Hide the modal
            const purgeModal = document.getElementById('purgeLogsModal');
            if (purgeModal && window.modalInstances['purgeLogsModal']) {
                window.modalInstances['purgeLogsModal'].hide();
            }

            // Show success notification
            showAlert(data.message || 'Logs purged successfully', 'success');

            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        })
        .catch(error => {
            console.error('Error purging logs:', error);
            showAlert('Failed to purge logs: ' + error.message, 'danger');

            // Reset button state
            confirmPurgeBtn.disabled = false;
            confirmPurgeBtn.innerHTML = 'Purge Logs';
        });
    }

    // Function to initialize charts
    function initializeCharts() {
        // Fetch stats data for the current group type
        const groupBy = '{{ $groupBy }}';

        fetch('/request-logger/stats?group_by=' + groupBy)
            .then(response => response.json())
            .then(data => {
                let chartData;

                switch(groupBy) {
                    case 'endpoint':
                        chartData = data.endpoint_stats ? data.endpoint_stats.slice(0, 10) : [];
                        break;
                    case 'method':
                        chartData = data.method_stats || [];
                        break;
                    case 'status':
                        chartData = data.status_stats || [];
                        break;
                    case 'ip':
                        chartData = data.ip_stats ? data.ip_stats.slice(0, 10) : [];
                        break;
                    case 'user':
                        chartData = data.user_stats ? data.user_stats.slice(0, 10) : [];
                        break;
                    default:
                        chartData = [];
                }

                if (chartData.length === 0) {
                    document.querySelector('.chart-container').innerHTML = '<div class="alert alert-info">No data available for charts</div>';
                    return;
                }

                // Create response time distribution chart
                createResponseTimeChart(chartData);

                // Create request count comparison chart
                createRequestCountChart(chartData);
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                document.querySelectorAll('.chart-container').forEach(container => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load chart data</div>';
                });
            });
    }

    // Function to create response time distribution chart
    function createResponseTimeChart(data) {
        const ctx = document.getElementById('response-time-distribution');
        if (!ctx) return;

        // Format labels based on group type
        const groupBy = '{{ $groupBy }}';
        const labels = data.map(item => formatLabel(item, groupBy));

        // Generate colors
        const colors = generateColorPalette(data.length);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Response Time (ms)',
                    data: data.map(item => item.avg_response_time || item.avg_time),
                    backgroundColor: colors.bg,
                    borderColor: colors.border,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.8)',
                        titleColor: 'rgba(255, 255, 255, 0.9)',
                        bodyColor: 'rgba(255, 255, 255, 0.7)',
                        borderColor: 'rgba(147, 51, 234, 0.5)',
                        borderWidth: 1
                    }
                }
            }
        });
    }

    // Function to create request count comparison chart
    function createRequestCountChart(data) {
        const ctx = document.getElementById('request-count-comparison');
        if (!ctx) return;

        // Format labels based on group type
        const groupBy = '{{ $groupBy }}';
        const labels = data.map(item => formatLabel(item, groupBy));

        // Generate colors
        const colors = generateColorPalette(data.length);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data.map(item => item.request_count || item.count),
                    backgroundColor: colors.bg,
                    borderColor: colors.border,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            padding: 10,
                            font: {
                                size: 11
                            },
                            generateLabels: function(chart) {
                                const original = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                original.forEach(label => {
                                    label.text = truncateText(label.text, 25);
                                });
                                return original;
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.8)',
                        titleColor: 'rgba(255, 255, 255, 0.9)',
                        bodyColor: 'rgba(255, 255, 255, 0.7)',
                        borderColor: 'rgba(147, 51, 234, 0.5)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Helper function to format labels based on group type
    function formatLabel(item, groupBy) {
        switch(groupBy) {
            case 'endpoint':
                return truncateText(item.path, 20);
            case 'method':
                return item.method;
            case 'status':
                return item.response_status || item.status;
            case 'ip':
                return truncateText(item.ip_address, 15);
            case 'user':
                return 'User ' + item.user_id;
            default:
                return '';
        }
    }

    // Helper function to generate color palette
    function generateColorPalette(count) {
        const bgColors = [];
        const borderColors = [];

        for (let i = 0; i < count; i++) {
            const hue = (i * 137) % 360; // Golden angle for nice color distribution
            bgColors.push(`hsla(${hue}, 70%, 60%, 0.7)`);
            borderColors.push(`hsla(${hue}, 70%, 45%, 1)`);
        }

        return {
            bg: bgColors,
            border: borderColors
        };
    }

    // Helper function to truncate text
    function truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength - 3) + '...';
    }

    // Helper function to show alerts
    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-floating');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert-floating alert-${type}`;
        alert.innerHTML = message;
        document.body.appendChild(alert);

        // Show the alert
        setTimeout(() => {
            alert.classList.add('show');
        }, 10);

        // Hide after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Chart container styles */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Icon for list view */
    .icon-list::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='8' y1='6' x2='21' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='12' x2='21' y2='12'%3E%3C/line%3E%3Cline x1='8' y1='18' x2='21' y2='18'%3E%3C/line%3E%3Cline x1='3' y1='6' x2='3.01' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='12' x2='3.01' y2='12'%3E%3C/line%3E%3Cline x1='3' y1='18' x2='3.01' y2='18'%3E%3C/line%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
    }

    /* Floating alert */
    .alert-floating {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        max-width: 350px;
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .alert-floating.show {
        opacity: 1;
        transform: translateY(0);
    }

    .alert-success {
        background-color: rgba(16, 185, 129, 0.9);
        color: #ecfdf5;
        border-left: 4px solid #10b981;
    }

    .alert-danger {
        background-color: rgba(239, 68, 68, 0.9);
        color: #fef2f2;
        border-left: 4px solid #ef4444;
    }

    .alert-info {
        background-color: rgba(59, 130, 246, 0.9);
        color: #eff6ff;
        border-left: 4px solid #3b82f6;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
    }
</style>
@endpush
