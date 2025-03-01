@extends('request-logger::layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Magic Logs</h1>
                <span class="text-muted">Because simple is hte ultimate sophistication</span>
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
                    <button class="btn btn-danger" id="purge-logs-btn" data-toggle="modal" data-target="#purgeLogsModal">
                        <span class="icon icon-trash mr-1"></span> Purge Old Logs
                    </button>
                </div>
            </div>

            {{-- Quick Stats Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title text-muted">Total Requests</h5>
                                    <h2 class="mb-0 text-gradient" id="total-requests">{{ $stats['total_requests'] ?? 0 }}</h2>
                                </div>
                                <div class="card-icon bg-primary text-white rounded-circle p-3 glow">
                                    <span class="icon icon-chart-line"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title text-muted">Requests Today</h5>
                                    <h2 class="mb-0 text-gradient" id="requests-today">{{ $stats['requests_today'] ?? 0 }}</h2>
                                </div>
                                <div class="card-icon bg-success text-white rounded-circle p-3 glow">
                                    <span class="icon icon-calendar-day"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title text-muted">Avg Response Time</h5>
                                    <h2 class="mb-0 text-gradient" id="avg-response-time">{{ number_format($stats['avg_response_time'] ?? 0, 2) }} ms</h2>
                                </div>
                                <div class="card-icon bg-warning text-white rounded-circle p-3 glow-cyan">
                                    <span class="icon icon-stopwatch"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title text-muted">Error Rate</h5>
                                    <h2 class="mb-0 text-gradient" id="error-rate">{{ isset($stats['error_count'], $stats['total_requests']) && $stats['total_requests'] > 0 ? number_format(($stats['error_count'] / $stats['total_requests']) * 100, 1) : 0 }}%</h2>
                                </div>
                                <div class="card-icon bg-danger text-white rounded-circle p-3 glow">
                                    <span class="icon icon-alert-circle"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Request Analysis --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Method Distribution</h5>
                            <div class="method-distribution">
                                @if(isset($stats['method_distribution']) && count($stats['method_distribution']) > 0)
                                    <div class="distribution-bars">
                                        @foreach($stats['method_distribution'] as $method)
                                            <div class="distribution-item">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="badge badge-{{ strtolower($method->method) }}">{{ $method->method }}</span>
                                                    <span>{{ $method->count }}</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ strtolower($method->method) == 'get' ? 'success' : (strtolower($method->method) == 'post' ? 'primary' : (strtolower($method->method) == 'put' ? 'warning' : 'danger')) }}"
                                                         style="width: {{ ($method->count / $stats['total_requests']) * 100 }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">No data available</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Status Distribution</h5>
                            <div class="status-distribution">
                                @if(isset($stats['status_distribution']) && count($stats['status_distribution']) > 0)
                                    <div class="distribution-bars">
                                        @foreach($stats['status_distribution'] as $status)
                                            <div class="distribution-item">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="badge badge-{{ $status->response_status >= 200 && $status->response_status < 300 ? 'success' : ($status->response_status >= 300 && $status->response_status < 400 ? 'warning' : 'danger') }}">
                                                        {{ $status->response_status }}
                                                    </span>
                                                    <span>{{ $status->count }}</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ $status->response_status >= 200 && $status->response_status < 300 ? 'success' : ($status->response_status >= 300 && $status->response_status < 400 ? 'warning' : 'danger') }}"
                                                         style="width: {{ ($status->count / $stats['total_requests']) * 100 }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">No data available</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form id="request-filter-form" method="GET" action="{{ route('request-logger.index') }}">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="method" class="form-control" id="method-filter">
                                    <option value="">All Methods</option>
                                    <option value="GET" {{ request()->input('method') == 'GET' ? 'selected' : '' }}>GET</option>
                                    <option value="POST" {{ request()->input('method') == 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="PUT" {{ request()->input('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                                    <option value="DELETE" {{ request()->input('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control" id="status-filter">
                                    <option value="">All Statuses</option>
                                    <option value="200" {{ request()->input('status') == '200' ? 'selected' : '' }}>200 OK</option>
                                    <option value="404" {{ request()->input('status') == '404' ? 'selected' : '' }}>404 Not Found</option>
                                    <option value="500" {{ request()->input('status') == '500' ? 'selected' : '' }}>500 Server Error</option>
                                    <option value="error" {{ request()->input('status') == 'error' ? 'selected' : '' }}>All Errors (4xx/5xx)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="path" class="form-control" id="path-filter"
                                       placeholder="Filter by path" value="{{ request()->input('path') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="date_range" id="date-range-filter"
                                       class="form-control daterange-picker"
                                       placeholder="Select Date Range" value="{{ request()->input('date_range') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Logs Table --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Request Logs</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="request-logs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Method</th>
                                    <th>Path</th>
                                    <th>Status</th>
                                    <th>Response Time</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr data-log-id="{{ $log->id }}" class="{{ $log->response_status >= 400 ? 'error-row' : '' }}">
                                    <td>{{ $log->id }}</td>
                                    <td>
                                        <span class="badge badge-{{ strtolower($log->method) }}">
                                            {{ $log->method }}
                                        </span>
                                    </td>
                                    <td class="truncate-text" title="{{ $log->path }}">{{ $log->path }}</td>
                                    <td>
                                        <span class="badge badge-{{ $log->response_status >= 200 && $log->response_status < 300 ? 'success' : ($log->response_status >= 300 && $log->response_status < 400 ? 'warning' : 'danger') }}">
                                            {{ $log->response_status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $log->response_time > 1000 ? 'text-danger' : ($log->response_time > 500 ? 'text-warning' : '') }}">
                                            {{ $log->response_time }} ms
                                        </span>
                                    </td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-log-details"
                                                data-log-id="{{ $log->id }}">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No logs found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
                        </div>
                        <div>
                            {{ $logs->appends(request()->except('page'))->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Log Details Modal --}}
    <div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Log Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="log-details-content">
                    {{-- Log details will be dynamically inserted here --}}
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

@push('styles')
<style>
    .truncate-text {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .error-row {
        background-color: rgba(239, 68, 68, 0.05);
    }

    .response-content-preview {
        max-height: 400px;
        overflow-y: auto;
    }

    .distribution-bars {
        margin-top: 1rem;
    }

    .distribution-item {
        margin-bottom: 1rem;
    }

    .progress {
        height: 0.75rem;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .progress-bar {
        transition: width 0.5s ease;
    }

    .form-control-range {
        -webkit-appearance: none;
        width: 100%;
        height: 8px;
        border-radius: 5px;
        background: var(--bg-card-hover);
        outline: none;
    }

    .form-control-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .form-control-range::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .form-control-range::-webkit-slider-thumb:hover {
        background: var(--primary-light);
        box-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
    }

    .form-control-range::-moz-range-thumb:hover {
        background: var(--primary-light);
        box-shadow: 0 0 10px rgba(147, 51, 234, 0.5);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    if (document.querySelector('.daterange-picker')) {
        new SimpleDatepicker('.daterange-picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });
    }

    // Set up the modal backdrop
    let modalBackdrop = document.querySelector('.modal-backdrop');
    if (!modalBackdrop) {
        modalBackdrop = document.createElement('div');
        modalBackdrop.classList.add('modal-backdrop');
        document.body.appendChild(modalBackdrop);
    }

    // Initialize days to keep slider
    const daysToKeepSlider = document.getElementById('days-to-keep');
    const daysValue = document.getElementById('days-value');

    if (daysToKeepSlider) {
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

    // Initialize dropdowns
    document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            menu.classList.toggle('show');

            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(event) {
                if (!dropdown.contains(event.target)) {
                    menu.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        });
    });

    // Show alert notification
    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
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

    // View log details
    const viewLogButtons = document.querySelectorAll('.view-log-details');
    const logDetailsModal = document.getElementById('logDetailsModal');

    if (logDetailsModal && viewLogButtons.length > 0) {
        viewLogButtons.forEach(button => {
            button.addEventListener('click', function() {
                const logId = this.getAttribute('data-log-id');
                fetchLogDetails(logId);
            });
        });
    }

    // Fetch log details via AJAX
    function fetchLogDetails(logId) {
        fetch(`/request-logger/log/${logId}`)
            .then(response => response.json())
            .then(data => {
                const modalContent = document.getElementById('log-details-content');

                // Create structured content instead of just showing raw JSON
                let detailsHTML = '';

                // Method and path header
                detailsHTML += `
                <div class="method-path">
                    <span class="badge badge-${data.method.toLowerCase()}">${data.method}</span>
                    <span class="url-path">${data.full_url || data.path}</span>
                </div>`;

                // Status and response time
                detailsHTML += `
                <div class="status-indicator">
                    <span class="status-code">${data.response_status}</span>
                    <span class="status-text">${getStatusText(data.response_status)}</span>
                    <span class="response-time">${data.response_time} ms</span>
                </div>`;

                // Request details grid
                detailsHTML += `<div class="request-details-grid">`;

                // IP and User Agent
                detailsHTML += `
                <div class="request-detail-card">
                    <h5>IP Address</h5>
                    <p>${data.ip_address || 'N/A'}</p>
                </div>
                <div class="request-detail-card">
                    <h5>User Agent</h5>
                    <p>${data.user_agent ? formatUserAgent(data.user_agent) : 'N/A'}</p>
                </div>`;

                // Timestamp
                detailsHTML += `
                <div class="request-detail-card">
                    <h5>Timestamp</h5>
                    <p>${formatDate(data.created_at)}</p>
                </div>`;

                // User ID if available
                if (data.user_id) {
                    detailsHTML += `
                    <div class="request-detail-card">
                        <h5>User ID</h5>
                        <p>${data.user_id}</p>
                    </div>`;
                }

                detailsHTML += `</div>`;

                // Full content tabs
                detailsHTML += `
                <div class="tabs-container">
                    <div class="tab-buttons">
                        <div class="tab-button active" data-target="request-headers">Headers</div>
                        <div class="tab-button" data-target="request-params">Request Parameters</div>
                        <div class="tab-button" data-target="response-body">Response</div>
                    </div>
                </div>

                <div class="tab-content">
                    <div class="tab-pane active" id="request-headers">
                        <pre>${formatJSON(data.headers || {})}</pre>
                    </div>
                    <div class="tab-pane" id="request-params">
                        <pre>${formatJSON(data.input_params || {})}</pre>
                    </div>
                    <div class="tab-pane" id="response-body">
                        <div class="response-content-preview">
                            ${data.response_body ? formatJSON(data.response_body) : '<div class="text-muted">Response body not captured</div>'}
                        </div>
                    </div>
                </div>`;

                modalContent.innerHTML = detailsHTML;

                // Initialize the tab system
                const tabButtons = modalContent.querySelectorAll('.tab-button');
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Remove active class from all tab buttons
                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');

                        // Hide all tab panes
                        const tabPanes = modalContent.querySelectorAll('.tab-pane');
                        tabPanes.forEach(pane => pane.classList.remove('active'));

                        // Show the selected tab pane
                        const targetId = this.getAttribute('data-target');
                        document.getElementById(targetId).classList.add('active');
                    });
                });

                // Show the modal using the global instance
                modalBackdrop.classList.add('show');
                window.modalInstances['logDetailsModal'].show();
            })
            .catch(error => {
                console.error('Error fetching log details:', error);
                showAlert('Failed to fetch log details', 'danger');
            });
    }

    // Helper functions
    function getStatusText(status) {
        const statusTexts = {
            200: 'OK',
            201: 'Created',
            202: 'Accepted',
            204: 'No Content',
            400: 'Bad Request',
            401: 'Unauthorized',
            403: 'Forbidden',
            404: 'Not Found',
            405: 'Method Not Allowed',
            500: 'Server Error',
            502: 'Bad Gateway',
            503: 'Service Unavailable'
        };

        return statusTexts[status] || 'Unknown Status';
    }

    function formatUserAgent(ua) {
        // Truncate if too long
        return ua.length > 30 ? ua.substring(0, 30) + '...' : ua;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    function formatJSON(json) {
        if (typeof json === 'string') {
            try {
                json = JSON.parse(json);
            } catch (e) {
                // If it's not valid JSON, just return the string
                return json;
            }
        }

        // Format the JSON with syntax highlighting
        const formatted = JSON.stringify(json, null, 2);

        // Apply syntax highlighting
        return syntaxHighlight(formatted);
    }

    function syntaxHighlight(json) {
        // Add syntax highlighting classes
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    // Reset form button
    document.querySelector('button[type="reset"]')?.addEventListener('click', function() {
        setTimeout(() => {
            window.location.href = window.location.pathname;
        }, 100);
    });
});
</script>
@endpush
