@extends('request-logger::layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">Magic Logs</h1>
                    <span class="text-muted">Because simple is the ultimate sophistication</span>
                    <div class="actions d-flex">
                        <div class="dropdown mr-2">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="icon icon-download mr-1"></span> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
                                <a class="dropdown-item"
                                    href="{{ route('request-logger.export', array_merge(['format' => 'csv'], request()->all())) }}">CSV</a>
                                <a class="dropdown-item"
                                    href="{{ route('request-logger.export', array_merge(['format' => 'json'], request()->all())) }}">JSON</a>
                            </div>
                        </div>
                        <button class="btn btn-danger" id="purge-logs-btn" data-toggle="modal"
                            data-target="#purgeLogsModal">
                            <span class="icon icon-trash mr-1"></span> Purge Old Logs
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="actions d-flex">
                        <a href="{{ route('request-logger.logs.index') }}" class="btn btn-primary">View
                            All Logs</a>
                        <a href="{{ route('request-logger.banned-ips.index') }}" class="btn btn-primary">Ban Ips</a>
                        <a href="{{ route('request-logger.security.analytics') }}" class="btn btn-primary">Analytics</a>
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
                                        <h2 class="mb-0 text-gradient" id="total-requests">
                                            {{ $stats['total_requests'] ?? 0 }}</h2>
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
                                        <h2 class="mb-0 text-gradient" id="requests-today">
                                            {{ $stats['requests_today'] ?? 0 }}</h2>
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
                                        <h2 class="mb-0 text-gradient" id="avg-response-time">
                                            {{ number_format($stats['avg_response_time'] ?? 0, 2) }} ms</h2>
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
                                        <h2 class="mb-0 text-gradient" id="error-rate">
                                            {{ isset($stats['error_count'], $stats['total_requests']) && $stats['total_requests'] > 0 ? number_format(($stats['error_count'] / $stats['total_requests']) * 100, 1) : 0 }}%
                                        </h2>
                                    </div>
                                    <div class="card-icon bg-danger text-white rounded-circle p-3 glow">
                                        <span class="icon icon-alert-circle"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Response Time Trends</h5>
                                <div id="request-time-chart-container" style="min-height: 300px;">
                                    <canvas id="request-time-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Request Analysis Advanced --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top Endpoints</h5>
                                <div id="request-distribution-chart-container" style="min-height: 350px;">
                                    <canvas id="request-distribution-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Endpoint Performance</h5>

                                <div class="endpoint-performance-table">
                                    <table class="table table-hover table-sm">
                                        <thead>
                                            <tr>
                                                <th>Endpoint</th>
                                                <th>Requests</th>
                                                <th>Avg. Time</th>
                                                <th>Error Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody id="endpoint-performance-body">
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <div class="spinner"></div> Loading...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 text-right">
                                    <a href="#" class="btn btn-sm btn-info" id="view-all-endpoints">View All Endpoints</a>
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
                                                        <span
                                                            class="badge badge-{{ strtolower($method->method) }}">{{ $method->method }}</span>
                                                        <span>{{ $method->count }}</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-{{ strtolower($method->method) == 'get' ? 'success' : (strtolower($method->method) == 'post' ? 'primary' : (strtolower($method->method) == 'put' ? 'warning' : 'danger')) }}"
                                                            style="width: {{ ($method->count / $stats['total_requests']) * 100 }}%">
                                                        </div>
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
                                                        <span
                                                            class="badge badge-{{ $status->response_status >= 200 && $status->response_status < 300 ? 'success' : ($status->response_status >= 300 && $status->response_status < 400 ? 'warning' : 'danger') }}">
                                                            {{ $status->response_status }}
                                                        </span>
                                                        <span>{{ $status->count }}</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-{{ $status->response_status >= 200 && $status->response_status < 300 ? 'success' : ($status->response_status >= 300 && $status->response_status < 400 ? 'warning' : 'danger') }}"
                                                            style="width: {{ ($status->count / $stats['total_requests']) * 100 }}%">
                                                        </div>
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

                {{-- Add the adavnce filter component --}}
                @include('request-logger::partials.filters')

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
                                        <tr data-log-id="{{ $log->id }}"
                                            class="{{ $log->response_status >= 400 ? 'error-row' : '' }}">
                                            <td>{{ $log->id }}</td>
                                            <td>
                                                <span class="badge badge-{{ strtolower($log->method) }}">
                                                    {{ $log->method }}
                                                </span>
                                            </td>
                                            <td class="truncate-text" title="{{ $log->path }}">{{ $log->path }}</td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $log->response_status >= 200 && $log->response_status < 300 ? 'success' : ($log->response_status >= 300 && $log->response_status < 400 ? 'warning' : 'danger') }}">
                                                    {{ $log->response_status }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="{{ $log->response_time > 1000 ? 'text-danger' : ($log->response_time > 500 ? 'text-warning' : '') }}">
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
                                <ul class="custom-pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($logs->onFirstPage())
                                        <li class="disabled"><span>«</span></li>
                                    @else
                                        <li><a href="{{ $logs->previousPageUrl() }}" rel="prev">«</a></li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($logs->links()->elements[0] as $page => $url)
                                        @if ($page == $logs->currentPage())
                                            <li class="active"><span>{{ $page }}</span></li>
                                        @else
                                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($logs->hasMorePages())
                                        <li><a href="{{ $logs->nextPageUrl() }}" rel="next">»</a></li>
                                    @else
                                        <li class="disabled"><span>»</span></li>
                                    @endif
                                </ul>
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
                                    <input type="range" class="form-control-range mr-3" id="days-to-keep" name="days"
                                        min="1" max="365" value="30" style="flex: 1;">
                                    <span id="days-value" class="badge badge-primary"
                                        style="width: 60px; text-align: center;">30 days</span>
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
        document.addEventListener('DOMContentLoaded', function () {
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
                daysToKeepSlider.addEventListener('input', function () {
                    daysValue.textContent = this.value + ' days';
                });
            }

            // Purge logs functionality
            const confirmPurgeBtn = document.getElementById('confirm-purge-logs');
            if (confirmPurgeBtn) {
                confirmPurgeBtn.addEventListener('click', function () {
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
                dropdown.addEventListener('click', function (e) {
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
                    button.addEventListener('click', function () {
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
                            button.addEventListener('click', function () {
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
            document.querySelector('button[type="reset"]')?.addEventListener('click', function () {
                setTimeout(() => {
                    window.location.href = window.location.pathname;
                }, 100);
            });


            // Initialize charts when the DOM is fully loaded
            initializeRequestTimeChart();
            initializeRequestDistributionChart();
        });

        // Function to initialize the request time chart
        function initializeRequestTimeChart() {
            const ctx = document.getElementById('request-time-chart');
            if (!ctx) return;

            // Fetch data from the stats endpoint with time grouping
            fetch('/request-logger/stats?group_by=time')
                .then(response => response.json())
                .then(data => {
                    // Setup the chart
                    const timeChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.time_series.map(item => item.time_label),
                            datasets: [{
                                label: 'Average Response Time (ms)',
                                data: data.time_series.map(item => item.avg_response_time),
                                borderColor: 'rgba(34, 211, 238, 1)',
                                backgroundColor: 'rgba(34, 211, 238, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }, {
                                label: 'Request Count',
                                data: data.time_series.map(item => item.request_count),
                                borderColor: 'rgba(147, 51, 234, 0.8)',
                                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Response Time (ms)',
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Request Count',
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
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(30, 41, 59, 0.8)',
                                    titleColor: 'rgba(255, 255, 255, 0.9)',
                                    bodyColor: 'rgba(255, 255, 255, 0.7)',
                                    borderColor: 'rgba(147, 51, 234, 0.5)',
                                    borderWidth: 1
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading request time chart:', error);
                    document.getElementById('request-time-chart-container').innerHTML =
                        '<div class="alert alert-danger">Failed to load chart data</div>';
                });
        }

        // Function to initialize the request distribution chart
        function initializeRequestDistributionChart() {
            const ctx = document.getElementById('request-distribution-chart');
            if (!ctx) return;

            // Fetch data from the stats endpoint with endpoint grouping
            fetch('/request-logger/stats?group_by=endpoint')
                .then(response => response.json())
                .then(data => {
                    // Get top endpoints by request count
                    const topEndpoints = data.endpoint_stats.slice(0, 10);

                    // Generate color palette
                    const colorPalette = generateColorPalette(topEndpoints.length);

                    // Setup the chart
                    const distributionChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: topEndpoints.map(item => truncateText(item.path, 30)),
                            datasets: [{
                                label: 'Request Count',
                                data: topEndpoints.map(item => item.request_count),
                                backgroundColor: colorPalette.bg,
                                borderColor: colorPalette.border,
                                borderWidth: 1
                            }, {
                                label: 'Average Response Time (ms)',
                                data: topEndpoints.map(item => item.avg_response_time),
                                type: 'line',
                                borderColor: 'rgba(34, 211, 238, 1)',
                                pointBackgroundColor: 'rgba(34, 211, 238, 1)',
                                pointBorderColor: '#fff',
                                pointRadius: 4,
                                fill: false,
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Request Count',
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Avg Response Time (ms)',
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
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(30, 41, 59, 0.8)',
                                    titleColor: 'rgba(255, 255, 255, 0.9)',
                                    bodyColor: 'rgba(255, 255, 255, 0.7)',
                                    borderColor: 'rgba(147, 51, 234, 0.5)',
                                    borderWidth: 1,
                                    callbacks: {
                                        title: function (tooltipItems) {
                                            return data.endpoint_stats[tooltipItems[0].dataIndex].path;
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading request distribution chart:', error);
                    document.getElementById('request-distribution-chart-container').innerHTML =
                        '<div class="alert alert-danger">Failed to load chart data</div>';
                });
        }

        // Helper function to generate color palette
        function generateColorPalette(count) {
            const bgColors = [];
            const borderColors = [];

            for (let i = 0; i < count; i++) {
                const hue = (i * 137) % 360; // Golden angle approximation for evenly distributed colors
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
            if (text.length <= maxLength) return text;
            return text.substr(0, maxLength - 3) + '...';
        }

        // Add at the end of your DOMContentLoaded function
        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const menu = this.nextElementSibling;
                menu.classList.toggle('show');
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Load the endpoint performance table
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the endpoint performance table
            loadEndpointPerformance();

            // View all endpoints button
            document.getElementById('view-all-endpoints')?.addEventListener('click', function (e) {
                e.preventDefault();
                showEndpointDetailsModal();
            });
        });

        // Function to load endpoint performance data
        function loadEndpointPerformance() {
            const tableBody = document.getElementById('endpoint-performance-body');
            if (!tableBody) return;

            fetch('/request-logger/stats?group_by=endpoint')
                .then(response => response.json())
                .then(data => {
                    // Get top 5 endpoints by request count
                    const topEndpoints = data.endpoint_stats.slice(0, 5);

                    // Clear loading indicator
                    tableBody.innerHTML = '';

                    // Populate table
                    topEndpoints.forEach(endpoint => {
                        const row = document.createElement('tr');

                        // Endpoint path (truncated)
                        const pathCell = document.createElement('td');
                        pathCell.className = 'truncate-text';
                        pathCell.title = endpoint.path;
                        pathCell.textContent = truncateText(endpoint.path, 25);

                        // Request count
                        const countCell = document.createElement('td');
                        countCell.textContent = endpoint.request_count;

                        // Average response time
                        const timeCell = document.createElement('td');
                        timeCell.className = endpoint.avg_response_time > 1000 ? 'text-danger' :
                            (endpoint.avg_response_time > 500 ? 'text-warning' : '');
                        timeCell.textContent = endpoint.avg_response_time + ' ms';

                        // Error rate
                        const errorCell = document.createElement('td');
                        errorCell.className = endpoint.error_rate > 5 ? 'text-danger' :
                            (endpoint.error_rate > 0 ? 'text-warning' : 'text-success');
                        errorCell.textContent = endpoint.error_rate + '%';

                        // Add cells to row
                        row.appendChild(pathCell);
                        row.appendChild(countCell);
                        row.appendChild(timeCell);
                        row.appendChild(errorCell);

                        // Add row to table
                        tableBody.appendChild(row);
                    });

                    // If no data
                    if (topEndpoints.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No data available</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading endpoint performance:', error);
                    tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load data</td></tr>';
                });
        }

        // Function to show endpoint details modal
        function showEndpointDetailsModal() {
            fetch('/request-logger/stats?group_by=endpoint')
                .then(response => response.json())
                .then(data => {
                    // Create modal content
                    let modalContent = `
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Requests</th>
                                    <th>Avg. Time</th>
                                    <th>Max Time</th>
                                    <th>Error Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    // Add rows for each endpoint
                    data.endpoint_stats.forEach(endpoint => {
                        modalContent += `
                            <tr>
                                <td class="truncate-text" title="${endpoint.path}">${endpoint.path}</td>
                                <td>${endpoint.request_count}</td>
                                <td class="${endpoint.avg_response_time > 1000 ? 'text-danger' :
                                (endpoint.avg_response_time > 500 ? 'text-warning' : '')}">${endpoint.avg_response_time} ms</td>
                                <td class="${endpoint.max_response_time > 2000 ? 'text-danger' :
                                (endpoint.max_response_time > 1000 ? 'text-warning' : '')}">${endpoint.max_response_time} ms</td>
                                <td class="${endpoint.error_rate > 5 ? 'text-danger' :
                                (endpoint.error_rate > 0 ? 'text-warning' : 'text-success')}">${endpoint.error_rate}%</td>
                            </tr>
                        `;
                    });

                    modalContent += `
                            </tbody>
                        </table>
                    </div>
                    `;

                    // Add modal to page if it doesn't exist
                    let endpointModal = document.getElementById('endpointDetailsModal');
                    if (!endpointModal) {
                        endpointModal = document.createElement('div');
                        endpointModal.className = 'modal fade';
                        endpointModal.id = 'endpointDetailsModal';
                        endpointModal.tabIndex = '-1';
                        endpointModal.role = 'dialog';
                        endpointModal.innerHTML = `
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Endpoint Performance</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body" id="endpoint-details-content">
                                        ${modalContent}
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(endpointModal);

                        // Initialize new modal
                        new SimpleModal(endpointModal);
                    } else {
                        // Update existing modal content
                        document.getElementById('endpoint-details-content').innerHTML = modalContent;
                    }

                    // Show the modal
                    window.modalInstances['endpointDetailsModal'].show();
                })
                .catch(error => {
                    console.error('Error loading endpoint details:', error);
                    showAlert('Failed to load endpoint details', 'danger');
                });
        }
    </script>
@endpush
