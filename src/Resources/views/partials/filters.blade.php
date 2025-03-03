<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-gradient">Advanced Filters</h5>
            <button class="btn btn-sm btn-secondary" id="toggle-advanced-filters">
                <span id="filter-toggle-text">Show Advanced</span>
                <span class="icon icon-chevron-down ml-1"></span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="request-filter-form" method="GET" action="{{ route('request-logger.index') }}">
            {{-- Basic Filters Row --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="method-filter" class="text-muted mb-2">HTTP Method</label>
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
                        <label for="status-filter" class="text-muted mb-2">Status</label>
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
                        <label for="path-filter" class="text-muted mb-2">Path Contains</label>
                        <input type="text" name="path" class="form-control" id="path-filter"
                               placeholder="Filter by path" value="{{ request()->input('path') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group d-flex align-items-end h-100">
                        <button type="submit" class="btn btn-primary">
                            <span class="icon icon-chart-line mr-2"></span>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            {{-- Advanced Filters (hidden by default) --}}
            <div class="advanced-filters" style="display: none;">
                <hr class="border-light my-4">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date-range-filter" class="text-muted mb-2">
                                <span class="icon icon-calendar-day mr-1"></span>
                                Date Range
                            </label>
                            <input type="text" name="date_range" id="date-range-filter"
                                   class="form-control daterange-picker"
                                   placeholder="Select Date Range" value="{{ request()->input('date_range') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="ip-filter" class="text-muted mb-2">IP Address</label>
                            <input type="text" name="ip_address" class="form-control" id="ip-filter"
                                   placeholder="Filter by IP address" value="{{ request()->input('ip_address') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="user-filter" class="text-muted mb-2">User ID</label>
                            <input type="text" name="user_id" class="form-control" id="user-filter"
                                   placeholder="Filter by user ID" value="{{ request()->input('user_id') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="response-time-filter" class="text-muted mb-2">
                                <span class="icon icon-stopwatch mr-1"></span>
                                Response Time
                            </label>
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
                            <label class="text-muted mb-2">
                                <span class="icon icon-alert-circle mr-1"></span>
                                Performance Filter
                            </label>
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
                            <label for="group-by" class="text-muted mb-2">Group Results By</label>
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

                <div class="row mt-4">
                    <div class="col-12 d-flex justify-content-between">
                        <button type="reset" class="btn btn-secondary">
                            <span class="icon icon-trash mr-2"></span>
                            Reset Filters
                        </button>
                        <button type="submit" class="btn btn-info">
                            <span class="icon icon-chart-line mr-2"></span>
                            Apply Advanced Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
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

            // Add slide animation
            toggleButton.addEventListener('click', function() {
                if (advancedFilters.style.display === 'none') {
                    // Add the slide-in-up animation class
                    advancedFilters.classList.add('slide-in-up');
                    advancedFilters.style.display = 'block';
                    toggleText.textContent = 'Hide Advanced';
                    toggleButton.querySelector('.icon').classList.add('icon-rotate-180');
                } else {
                    advancedFilters.style.display = 'none';
                    advancedFilters.classList.remove('slide-in-up');
                    toggleText.textContent = 'Show Advanced';
                    toggleButton.querySelector('.icon').classList.remove('icon-rotate-180');
                }
            });
        }

        // Add animation to buttons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.classList.add('pulse');
            });

            button.addEventListener('mouseleave', function() {
                this.classList.remove('pulse');
            });
        });
    });
</script>
@endpush
