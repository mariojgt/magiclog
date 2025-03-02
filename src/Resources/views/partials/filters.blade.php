<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Advanced Filters</h5>
            <button class="btn btn-sm btn-secondary" id="toggle-advanced-filters">
                <span id="filter-toggle-text">Show Advanced</span>
                <span class="icon icon-chevron-down ml-1"></span>
            </button>
        </div>

        <form id="request-filter-form" method="GET" action="{{ route('request-logger.index') }}">
            {{-- Basic Filters Row --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="method-filter">HTTP Method</label>
                        <select name="method" class="form-control" id="method-filter">
                            <option value="">All Methods</option>
                            <option value="GET" {{ request()->input('method') == 'GET' ? 'selected' : '' }}>GET</option>
                            <option value="POST" {{ request()->input('method') == 'POST' ? 'selected' : '' }}>POST</option>
                            <option value="PUT" {{ request()->input('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                            <option value="DELETE" {{ request()->input('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            <option value="PATCH" {{ request()->input('method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status-filter">Status</label>
                        <select name="status" class="form-control" id="status-filter">
                            <option value="">All Statuses</option>
                            <option value="200" {{ request()->input('status') == '200' ? 'selected' : '' }}>200 OK</option>
                            <option value="201" {{ request()->input('status') == '201' ? 'selected' : '' }}>201 Created</option>
                            <option value="204" {{ request()->input('status') == '204' ? 'selected' : '' }}>204 No Content</option>
                            <option value="400" {{ request()->input('status') == '400' ? 'selected' : '' }}>400 Bad Request</option>
                            <option value="401" {{ request()->input('status') == '401' ? 'selected' : '' }}>401 Unauthorized</option>
                            <option value="403" {{ request()->input('status') == '403' ? 'selected' : '' }}>403 Forbidden</option>
                            <option value="404" {{ request()->input('status') == '404' ? 'selected' : '' }}>404 Not Found</option>
                            <option value="422" {{ request()->input('status') == '422' ? 'selected' : '' }}>422 Unprocessable</option>
                            <option value="500" {{ request()->input('status') == '500' ? 'selected' : '' }}>500 Server Error</option>
                            <option value="error" {{ request()->input('status') == 'error' ? 'selected' : '' }}>All Errors (4xx/5xx)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="path-filter">Path Contains</label>
                        <input type="text" name="path" class="form-control" id="path-filter"
                               placeholder="Filter by path" value="{{ request()->input('path') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group d-flex align-items-end h-100">
                        <button type="submit" class="btn btn-primary mr-2">Apply Filters</button>
                    </div>
                </div>
            </div>

            {{-- Advanced Filters (hidden by default) --}}
            <div class="advanced-filters" style="display: none;">
                <hr class="border-light">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date-range-filter">Date Range</label>
                            <input type="text" name="date_range" id="date-range-filter"
                                   class="form-control daterange-picker"
                                   placeholder="Select Date Range" value="{{ request()->input('date_range') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="ip-filter">IP Address</label>
                            <input type="text" name="ip_address" class="form-control" id="ip-filter"
                                   placeholder="Filter by IP address" value="{{ request()->input('ip_address') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="user-filter">User ID</label>
                            <input type="text" name="user_id" class="form-control" id="user-filter"
                                   placeholder="Filter by user ID" value="{{ request()->input('user_id') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="response-time-filter">Response Time</label>
                            <select name="response_time" class="form-control" id="response-time-filter">
                                <option value="">Any Response Time</option>
                                <option value="fast" {{ request()->input('response_time') == 'fast' ? 'selected' : '' }}>Fast (< 100ms)</option>
                                <option value="medium" {{ request()->input('response_time') == 'medium' ? 'selected' : '' }}>Medium (100ms - 500ms)</option>
                                <option value="slow" {{ request()->input('response_time') == 'slow' ? 'selected' : '' }}>Slow (> 500ms)</option>
                                <option value="very_slow" {{ request()->input('response_time') == 'very_slow' ? 'selected' : '' }}>Very Slow (> 1000ms)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Performance Filter</label>
                            <div class="d-flex mt-2">
                                <div class="custom-control custom-checkbox mr-3">
                                    <input type="checkbox" class="custom-control-input" id="error-filter"
                                           name="has_errors" value="1" {{ request()->has('has_errors') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="error-filter">Has Errors</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="slow-filter"
                                           name="slow" value="1" {{ request()->has('slow') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="slow-filter">Slow Requests</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="group-by">Group Results By</label>
                            <select name="group_by" class="form-control" id="group-by">
                                <option value="">No Grouping</option>
                                <option value="endpoint" {{ request()->input('group_by') == 'endpoint' ? 'selected' : '' }}>Endpoint</option>
                                <option value="method" {{ request()->input('group_by') == 'method' ? 'selected' : '' }}>HTTP Method</option>
                                <option value="status" {{ request()->input('group_by') == 'status' ? 'selected' : '' }}>Status Code</option>
                                <option value="ip" {{ request()->input('group_by') == 'ip' ? 'selected' : '' }}>IP Address</option>
                                <option value="user" {{ request()->input('group_by') == 'user' ? 'selected' : '' }}>User ID</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-12 d-flex justify-content-between">
                        <button type="reset" class="btn btn-secondary">Reset Filters</button>
                        <button type="submit" class="btn btn-primary">Apply Advanced Filters</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Toggle advanced filters
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggle-advanced-filters');
        const advancedFilters = document.querySelector('.advanced-filters');
        const toggleText = document.getElementById('filter-toggle-text');

        if (toggleButton && advancedFilters) {
            // Check if any advanced filters are active
            const hasAdvancedFilters = {{
                request()->hasAny(['date_range', 'ip_address', 'user_id', 'response_time', 'has_errors', 'slow', 'group_by'])
                ? 'true' : 'false'
            }};

            // Show advanced filters if they are in use
            if (hasAdvancedFilters) {
                advancedFilters.style.display = 'block';
                toggleText.textContent = 'Hide Advanced';
                toggleButton.querySelector('.icon').classList.add('icon-rotate-180');
            }

            // Toggle button click handler
            toggleButton.addEventListener('click', function() {
                if (advancedFilters.style.display === 'none') {
                    advancedFilters.style.display = 'block';
                    toggleText.textContent = 'Hide Advanced';
                    toggleButton.querySelector('.icon').classList.add('icon-rotate-180');
                } else {
                    advancedFilters.style.display = 'none';
                    toggleText.textContent = 'Show Advanced';
                    toggleButton.querySelector('.icon').classList.remove('icon-rotate-180');
                }
            });
        }
    });
</script>
@endpush

{{-- Add this CSS to your styles section --}}
@push('styles')
<style>
    /* Custom checkbox styling */
    .custom-control {
        position: relative;
        z-index: 1;
        display: inline-block;
        min-height: 1.5rem;
        padding-left: 1.5rem;
    }

    .custom-control-input {
        position: absolute;
        left: 0;
        z-index: -1;
        width: 1rem;
        height: 1rem;
        opacity: 0;
    }

    .custom-control-label {
        position: relative;
        margin-bottom: 0;
        vertical-align: top;
        cursor: pointer;
    }

    .custom-control-label::before {
        position: absolute;
        top: 0.25rem;
        left: -1.5rem;
        display: block;
        width: 1rem;
        height: 1rem;
        content: "";
        background-color: var(--bg-card-hover);
        border: 1px solid var(--border);
        border-radius: 0.25rem;
    }

    .custom-control-label::after {
        position: absolute;
        top: 0.25rem;
        left: -1.5rem;
        display: block;
        width: 1rem;
        height: 1rem;
        content: "";
        background: no-repeat 50% / 50% 50%;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        color: #fff;
        border-color: var(--primary);
        background-color: var(--primary);
    }

    .custom-control-input:checked ~ .custom-control-label::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e");
    }

    .custom-control-input:focus ~ .custom-control-label::before {
        box-shadow: 0 0 0 0.2rem rgba(147, 51, 234, 0.25);
    }

    /* Icon rotation */
    .icon-rotate-180 {
        transform: rotate(180deg);
        transition: transform 0.2s;
    }

    /* Icon chevron */
    .icon-chevron-down::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        transition: transform 0.2s;
    }
</style>
@endpush
