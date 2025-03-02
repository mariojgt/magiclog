@extends('request-logger::layouts.app')

@section('content')
    <div class="container-fluid">
        {{-- Log Files Grid --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Available Log Files</h5>
                        <div class="log-files-grid">
                            @foreach($logFiles as $file)
                                <div class="log-file-card {{ $file['name'] === $selectedFile ? 'active' : '' }}">
                                    <a href="{{ route('request-logger.logs.index', ['file' => $file['name']]) }}"
                                        class="log-file-link">
                                        <div class="log-file-icon">
                                            <span class="icon icon-file-text"></span>
                                            @if($file['error_count'] > 0)
                                                <span class="log-file-badge">{{ $file['error_count'] }}</span>
                                            @endif
                                        </div>
                                        <div class="log-file-info">
                                            <div class="log-file-name">
                                                {{ $file['name'] }}
                                                @if($file['is_today'])
                                                    <span class="badge badge-info">Today</span>
                                                @endif
                                            </div>
                                            <div class="log-file-meta">
                                                {{ $file['size_formatted'] }} • {{ $file['modified_formatted'] }}
                                            </div>
                                        </div>
                                    </a>
                                    <div class="log-file-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link dropdown-toggle" type="button"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <span class="icon icon-more-vertical"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item"
                                                    href="{{ route('request-logger.logs.download', ['file' => $file['name']]) }}">
                                                    <span class="icon icon-download mr-1"></span> Download
                                                </a>
                                                <button class="dropdown-item text-warning"
                                                    onclick="clearLogModal('{{ $file['name'] }}')">
                                                    <span class="icon icon-trash-2 mr-1"></span> Clear
                                                </button>
                                                <button class="dropdown-item text-danger"
                                                    onclick="deleteLogModal('{{ $file['name'] }}')">
                                                    <span class="icon icon-trash mr-1"></span> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">Laravel Logs</h1>
                    <span class="text-muted">{{ $selectedFile ?? 'No log file selected' }}</span>
                    <div class="actions d-flex">
                        <a href="{{ route('request-logger.index') }}" class="btn btn-secondary">
                            <span class="icon icon-arrow-left mr-1"></span> Back to Requests
                        </a>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="log-filter-form" method="GET" action="{{ route('request-logger.logs.index') }}">
                            <input type="hidden" name="file" value="{{ $selectedFile }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="level-filter">Log Level</label>
                                        <select name="level" class="form-control" id="level-filter">
                                            <option value="">All Levels</option>
                                            <option value="emergency" {{ request()->input('level') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                            <option value="alert" {{ request()->input('level') == 'alert' ? 'selected' : '' }}>Alert</option>
                                            <option value="critical" {{ request()->input('level') == 'critical' ? 'selected' : '' }}>Critical</option>
                                            <option value="error" {{ request()->input('level') == 'error' ? 'selected' : '' }}>Error</option>
                                            <option value="warning" {{ request()->input('level') == 'warning' ? 'selected' : '' }}>Warning</option>
                                            <option value="notice" {{ request()->input('level') == 'notice' ? 'selected' : '' }}>Notice</option>
                                            <option value="info" {{ request()->input('level') == 'info' ? 'selected' : '' }}>
                                                Info</option>
                                            <option value="debug" {{ request()->input('level') == 'debug' ? 'selected' : '' }}>Debug</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date-filter">Date</label>
                                        <input type="date" name="date" class="form-control" id="date-filter"
                                            value="{{ request()->input('date') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search-filter">Search</label>
                                        <input type="text" name="search" class="form-control" id="search-filter"
                                            placeholder="Search log messages" value="{{ request()->input('search') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group d-flex align-items-end h-100">
                                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                                        <button type="button" class="btn btn-secondary" id="reset-filters">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Stats Section --}}
                @if($selectedFile && !empty($logs))
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Log Level Distribution</h5>
                                    <div class="level-distribution">
                                        @foreach($stats['levels'] as $level => $count)
                                                            @if($count > 0)
                                                                                <div class="level-item">
                                                                                    <div class="d-flex justify-content-between mb-1">
                                                                                        <span class="badge badge-{{ $level }}">{{ strtoupper($level) }}</span>
                                                                                        <span>{{ $count }}</span>
                                                                                    </div>
                                                                                    <div class="progress">
                                                                                        <div class="progress-bar bg-{{
                                                                $level == 'emergency' || $level == 'alert' || $level == 'critical' || $level == 'error' ? 'danger' :
                                                                ($level == 'warning' ? 'warning' :
                                                                    ($level == 'notice' || $level == 'info' ? 'info' : 'secondary'))
                                                                                                }}" style="width: {{ ($count / $stats['total']) * 100 }}%"></div>
                                                                                    </div>
                                                                                </div>
                                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Log Summary</h5>
                                    <div class="log-summary">
                                        <div class="summary-item">
                                            <div class="summary-label">Total Entries</div>
                                            <div class="summary-value">{{ $stats['total'] }}</div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-label">Errors</div>
                                            <div
                                                class="summary-value {{ ($stats['levels']['emergency'] + $stats['levels']['alert'] + $stats['levels']['critical'] + $stats['levels']['error']) > 0 ? 'text-danger' : '' }}">
                                                {{ $stats['levels']['emergency'] + $stats['levels']['alert'] + $stats['levels']['critical'] + $stats['levels']['error'] }}
                                                <small>({{ $stats['error_percentage'] }}%)</small>
                                            </div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-label">Today</div>
                                            <div class="summary-value">{{ $stats['today'] }}</div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-label">This Week</div>
                                            <div class="summary-value">{{ $stats['this_week'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Display success/error messages --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                {{-- Log Entries --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Log Entries</h5>

                        @if(empty($logs))
                            <div class="alert alert-info">
                                No log entries found.
                            </div>
                        @else
                            <div class="log-entries">
                                @foreach($logs as $index => $log)
                                    <div class="log-entry {{ $log['level'] }}">
                                        <div class="log-header" data-toggle="collapse" data-target="#log-content-{{ $index }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span
                                                        class="log-level badge badge-{{ $log['level'] }}">{{ strtoupper($log['level']) }}</span>
                                                    <span class="log-date">{{ $log['datetime'] }}</span>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-link">
                                                        <span class="icon-chevron-down"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="log-content collapse" id="log-content-{{ $index }}">
                                            <div class="log-message">{{ $log['message'] }}</div>

                                            @if(isset($log['stack_trace']) && $log['stack_trace'])
                                                <div class="stack-trace">
                                                    <div class="stack-header">Stack Trace:</div>
                                                    <pre>{{ $log['stack_trace'] }}</pre>
                                                </div>
                                            @endif

                                            @if(isset($log['context']) && !empty($log['context']))
                                                <div class="context-data">
                                                    <div class="context-header">Context:</div>
                                                    <pre>{{ is_string($log['context']) ? $log['context'] : json_encode($log['context'], JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pagination --}}
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        Showing {{ count($logs) }} of {{ $total }} entries
                                    </div>
                                    <div>
                                        <nav aria-label="Log pagination">
                                            <ul class="custom-pagination">
                                                {{-- Previous Page --}}
                                                <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                    <a class="page-link"
                                                    href="{{ $currentPage > 1 ? route('request-logger.logs.index', array_merge(request()->all(), ['page' => $currentPage - 1])) : '#' }}">
                                                        Previous
                                                    </a>
                                                </li>

                                                {{-- Page Numbers --}}
                                                @foreach(range(max(1, $currentPage - 2), min($lastPage, $currentPage + 2)) as $i)
                                                    <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                                        <a class="page-link"
                                                        href="{{ route('request-logger.logs.index', array_merge(request()->all(), ['page' => $i])) }}">
                                                            {{ $i }}
                                                        </a>
                                                    </li>
                                                @endforeach

                                                {{-- Next Page --}}
                                                <li class="page-item {{ $currentPage == $lastPage ? 'disabled' : '' }}">
                                                    <a class="page-link"
                                                    href="{{ $currentPage < $lastPage ? route('request-logger.logs.index', array_merge(request()->all(), ['page' => $currentPage + 1])) : '#' }}">
                                                        Next
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>

                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Clear Log Modal --}}
        <div class="modal fade" id="clearLogModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Clear Log File</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to clear the log file <strong id="clearLogName"></strong>?</p>
                        <p class="text-warning">This will empty the file but keep it in place. This action cannot be undone!
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <form action="{{ route('request-logger.logs.clear') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="file" id="clearLogFile">
                            <button type="submit" class="btn btn-warning">Clear Log</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Log Modal --}}
        <div class="modal fade" id="deleteLogModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Log File</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the log file <strong id="deleteLogName"></strong>?</p>
                        <p class="text-danger">This will permanently remove the file. This action cannot be undone!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <form action="{{ route('request-logger.logs.delete') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="file" id="deleteLogFile">
                            <button type="submit" class="btn btn-danger">Delete Log</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Log files grid */
        .log-files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .log-file-card {
            position: relative;
            display: flex;
            align-items: stretch;
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            overflow: hidden;
            transition: all 0.2s ease-in-out;
            background-color: var(--bg-card);
        }

        .log-file-card.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .log-file-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--border-light);
        }

        .log-file-link {
            display: flex;
            flex: 1;
            padding: 1rem;
            text-decoration: none;
            color: var(--text-primary);
        }

        .log-file-link:hover {
            text-decoration: none;
            color: var(--text-primary);
        }

        .log-file-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: var(--btn-radius);
            margin-right: 1rem;
        }

        .log-file-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .log-file-info {
            flex: 1;
        }

        .log-file-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }

        .log-file-name .badge {
            margin-left: 0.5rem;
            font-size: 0.65rem;
            padding: 0.15rem 0.35rem;
        }

        .log-file-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .log-file-actions {
            display: flex;
            align-items: center;
            padding-right: 0.5rem;
        }

        /* Log entries */
        .log-entries {
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            overflow: hidden;
        }

        .log-entry {
            border-bottom: 1px solid var(--border);
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-header {
            padding: 0.75rem 1rem;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.02);
            transition: background-color 0.15s ease;
        }

        .log-header:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .log-date {
            color: var(--text-muted);
            margin-left: 0.5rem;
            font-size: 0.9rem;
        }

        .log-content {
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.2);
            border-top: 1px solid var(--border);
            white-space: pre-wrap;
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
        }

        .log-message {
            margin-bottom: 1rem;
        }

        .stack-trace,
        .context-data {
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 0.25rem;
            margin-top: 1rem;
        }

        .stack-header,
        .context-header {
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        /* Level distribution */
        .level-distribution {
            margin-top: 1rem;
        }

        .level-item {
            margin-bottom: 1rem;
        }

        /* Log summary */
        .log-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .summary-item {
            padding: 0.75rem;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: var(--btn-radius);
            border: 1px solid var(--border);
        }

        .summary-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .summary-value {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .summary-value small {
            font-size: 0.75rem;
            font-weight: normal;
            opacity: 0.7;
        }

        /* Log level badges */
        .badge-emergency,
        .badge-alert,
        .badge-critical,
        .badge-error {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .badge-notice,
        .badge-info {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .badge-debug {
            background-color: rgba(107, 114, 128, 0.2);
            color: var(--text-muted);
            border: 1px solid var(--text-muted);
        }

        /* Log entry background colors based on level */
        .log-entry.emergency,
        .log-entry.alert,
        .log-entry.critical,
        .log-entry.error {
            background-color: rgba(239, 68, 68, 0.05);
        }

        .log-entry.warning {
            background-color: rgba(245, 158, 11, 0.05);
        }

        /* Icons */
        /* More vertical icon */
        .icon-more-vertical::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='5' r='1'%3E%3C/circle%3E%3Ccircle cx='12' cy='12' r='1'%3E%3C/circle%3E%3Ccircle cx='12' cy='19' r='1'%3E%3C/circle%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        /* Trash with 2 icon */
        .icon-trash-2::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'%3E%3C/polyline%3E%3Cpath d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'%3E%3C/path%3E%3Cline x1='10' y1='11' x2='10' y2='17'%3E%3C/line%3E%3Cline x1='14' y1='11' x2='14' y2='17'%3E%3C/line%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        /* Icon for chevron */
        .icon-chevron-down {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            position: relative;
        }

        .icon-chevron-down::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
            transition: transform 0.15s ease;
        }

        .collapsed .icon-chevron-down::before {
            transform: rotate(-90deg);
        }

        /* Custom icon for additional files */
        .icon-file-text::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'%3E%3C/path%3E%3Cpolyline points='14 2 14 8 20 8'%3E%3C/polyline%3E%3Cline x1='16' y1='13' x2='8' y2='13'%3E%3C/line%3E%3Cline x1='16' y1='17' x2='8' y2='17'%3E%3C/line%3E%3Cpolyline points='10 9 9 9 8 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-download::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='7 10 12 15 17 10'/%3E%3Cline x1='12' y1='15' x2='12' y2='3'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-trash::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .icon-arrow-left::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='19' y1='12' x2='5' y2='12'%3E%3C/line%3E%3Cpolyline points='12 19 5 12 12 5'%3E%3C/polyline%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        /* Page link styles */
        .page-link {
            background-color: var(--bg-card);
            border-color: var(--border);
            color: var(--text-primary);
        }

        .page-link:hover {
            background-color: var(--bg-card-hover);
            border-color: var(--border-light);
            color: var(--text-primary);
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .page-item.disabled .page-link {
            background-color: var(--bg-card);
            border-color: var(--border);
            color: var(--text-muted);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .log-files-grid {
                grid-template-columns: 1fr;
            }

            .log-summary {
                grid-template-columns: 1fr;
            }

            .summary-item {
                margin-bottom: 0.5rem;
            }
        }

        /* Reset dropdown positioning to make sure it's visible */
        .dropdown {
            position: relative !important;
            display: inline-block !important;
        }

        /* Force dropdown menu to be visible and properly positioned */
        .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            z-index: 9999 !important;
            /* Extremely high z-index */
            min-width: 10rem !important;
            padding: 0.5rem 0 !important;
            margin: 0.125rem 0 0 !important;
            font-size: 1rem !important;
            text-align: left !important;
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--card-radius) !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175) !important;
            max-height: none !important;
            overflow: visible !important;
            display: none !important;
            transform: none !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            clip: auto !important;
            visibility: visible !important;
        }

        /* Force display when .show class is present */
        .dropdown-menu.show {
            display: block !important;
        }

        /* Fix for dropdown items */
        .dropdown-item {
            display: block !important;
            width: 100% !important;
            padding: 0.5rem 1rem !important;
            clear: both !important;
            font-weight: 400 !important;
            text-align: inherit !important;
            white-space: nowrap !important;
            background-color: transparent !important;
            border: 0 !important;
            color: var(--text-primary) !important;
            text-decoration: none !important;
            cursor: pointer !important;
        }

        /* When hovering over dropdown items */
        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
        }

        /* Fix for any parent containers that might be causing issues */
        .log-file-card,
        .log-file-actions,
        .actions,
        .card,
        .card-body,
        .container-fluid,
        .row,
        .col-12 {
            overflow: visible !important;
        }

        /* Add a fixed position for testing */
        .log-file-actions .dropdown-menu {
            position: fixed !important;
            top: auto !important;
            left: auto !important;
            transform: none !important;
            margin: 0 !important;
        }

        /* Add visual debugging borders */
        .dropdown {
            border: 1px solid transparent;
        }

        .dropdown.active {
            border-color: red;
        }

        .dropdown-menu {
            border: 2px solid green !important;
        }

        /* Additional styles for the buttons */
        .btn {
            position: relative !important;
            z-index: 1 !important;
        }

        .dropdown-toggle {
            position: relative !important;
            z-index: 2 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Functions to show the clear and delete modals
        function clearLogModal(fileName) {
            document.getElementById('clearLogName').textContent = fileName;
            document.getElementById('clearLogFile').value = fileName;

            // Use the SimpleModal instance
            const modalElement = document.getElementById('clearLogModal');
            if (modalElement && window.modalInstances && window.modalInstances['clearLogModal']) {
                window.modalInstances['clearLogModal'].show();
            } else {
                // Fallback to Bootstrap modal if SimpleModal isn't available
                $('#clearLogModal').modal('show');
            }
        }

        function deleteLogModal(fileName) {
            document.getElementById('deleteLogName').textContent = fileName;
            document.getElementById('deleteLogFile').value = fileName;

            // Use the SimpleModal instance
            const modalElement = document.getElementById('deleteLogModal');
            if (modalElement && window.modalInstances && window.modalInstances['deleteLogModal']) {
                window.modalInstances['deleteLogModal'].show();
            } else {
                // Fallback to Bootstrap modal if SimpleModal isn't available
                $('#deleteLogModal').modal('show');
            }
        }

// Direct toggle function for inline use
function toggleDropdownDirect(element) {
    // Get all dropdowns
    const allDropdowns = document.querySelectorAll('.dropdown');

    // Get this dropdown
    const dropdown = element.closest('.dropdown');

    // Get this dropdown menu
    const menu = dropdown.querySelector('.dropdown-menu');

    // Remove active class from all dropdowns
    allDropdowns.forEach(d => d.classList.remove('active'));

    // Remove show class from all menus
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });

    // Toggle this menu and add active class to parent
    menu.classList.toggle('show');
    dropdown.classList.toggle('active');

    // Prevent event propagation
    event.stopPropagation();
    return false;
}
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
                dropdown.addEventListener('click', function (e) {
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
            document.addEventListener('click', function (event) {
                if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                        openMenu.classList.remove('show');
                    });
                }
            });

            // Initialize modals if SimpleModal is available
            if (typeof SimpleModal !== 'undefined') {
                document.querySelectorAll('.modal').forEach(modalElement => {
                    new SimpleModal(modalElement);
                });
            }

            // Reset filters button
            document.getElementById('reset-filters')?.addEventListener('click', function () {
                const form = document.getElementById('log-filter-form');
                const fileInput = form.querySelector('input[name="file"]');
                const fileValue = fileInput ? fileInput.value : '';

                // Reset form fields
                form.reset();

                // Preserve file parameter
                if (fileInput && fileValue) {
                    fileInput.value = fileValue;
                }

                // Submit the form
                form.submit();
            });

            // Initialize log entry toggles
            document.querySelectorAll('.log-header').forEach(header => {
                header.addEventListener('click', function () {
                    const content = this.nextElementSibling;
                    const button = this.querySelector('.icon-chevron-down');

                    if (content.classList.contains('show')) {
                        content.classList.remove('show');
                        button.classList.add('collapsed');
                    } else {
                        content.classList.add('show');
                        button.classList.remove('collapsed');
                    }
                });
            });

            // Auto-expand log entries if there are fewer than 10
            if (document.querySelectorAll('.log-entry').length < 10) {
                document.querySelectorAll('.log-header').forEach(header => {
                    const content = header.nextElementSibling;
                    const button = header.querySelector('.icon-chevron-down');
                    content.classList.add('show');
                    button.classList.remove('collapsed');
                });
            }

            // Filter handling for log level badges
            document.querySelectorAll('.badge[data-level]').forEach(badge => {
                badge.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const level = this.getAttribute('data-level');
                    const levelFilter = document.getElementById('level-filter');

                    if (levelFilter) {
                        levelFilter.value = level;
                        document.getElementById('log-filter-form').submit();
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Direct approach to initialize dropdowns
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            // Add click event to each dropdown toggle
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Get the dropdown menu
                    const dropdownMenu = this.nextElementSibling;

                    // Check if this is a dropdown
                    if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) return;

                    // First, close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove('show');
                        }
                    });

                    // Toggle the current dropdown
                    dropdownMenu.classList.toggle('show');
                });
            });

            // Close all dropdowns when clicking outside
            document.addEventListener('click', function (e) {
                // If the click was not on a dropdown toggle and not inside a dropdown menu
                if (!e.target.closest('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });

            // Modal handling functions
            window.clearLogModal = function (fileName) {
                const nameElement = document.getElementById('clearLogName');
                const fileElement = document.getElementById('clearLogFile');

                if (nameElement) nameElement.textContent = fileName;
                if (fileElement) fileElement.value = fileName;

                // Show modal - try different approaches
                const modal = document.getElementById('clearLogModal');

                // First try: Direct classList manipulation
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Add backdrop if needed
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.classList.add('modal-backdrop', 'fade', 'show');
                        document.body.appendChild(backdrop);
                    }
                }
            };

            window.deleteLogModal = function (fileName) {
                const nameElement = document.getElementById('deleteLogName');
                const fileElement = document.getElementById('deleteLogFile');

                if (nameElement) nameElement.textContent = fileName;
                if (fileElement) fileElement.value = fileName;

                // Show modal - try different approaches
                const modal = document.getElementById('deleteLogModal');

                // First try: Direct classList manipulation
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Add backdrop if needed
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.classList.add('modal-backdrop', 'fade', 'show');
                        document.body.appendChild(backdrop);
                    }
                }
            };

            // Close buttons for modals
            document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
                button.addEventListener('click', function () {
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.classList.remove('show');
                        setTimeout(() => {
                            modal.style.display = 'none';
                        }, 150);

                        document.body.classList.remove('modal-open');

                        // Remove backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.classList.remove('show');
                            setTimeout(() => {
                                backdrop.remove();
                            }, 150);
                        }
                    }
                });
            });

            // Initialize log entry toggles
            document.querySelectorAll('.log-header').forEach(header => {
                header.addEventListener('click', function () {
                    const content = this.nextElementSibling;
                    const button = this.querySelector('.icon-chevron-down');

                    if (content.classList.contains('show')) {
                        content.classList.remove('show');
                        if (button) button.classList.add('collapsed');
                    } else {
                        content.classList.add('show');
                        if (button) button.classList.remove('collapsed');
                    }
                });
            });

            // Reset filters button
            const resetButton = document.getElementById('reset-filters');
            if (resetButton) {
                resetButton.addEventListener('click', function () {
                    const form = document.getElementById('log-filter-form');
                    if (!form) return;

                    const fileInput = form.querySelector('input[name="file"]');
                    const fileValue = fileInput ? fileInput.value : '';

                    // Reset form fields
                    form.reset();

                    // Preserve file parameter
                    if (fileInput && fileValue) {
                        fileInput.value = fileValue;
                    }

                    // Submit the form
                    form.submit();
                });
            }
        });
    </script>
@endpush
