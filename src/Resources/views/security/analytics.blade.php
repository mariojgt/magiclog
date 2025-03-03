{{-- resources/views/vendor/request-logger/security/analytics.blade.php --}}
@extends('request-logger::layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Security Analytics Dashboard</h1>

                <div class="d-flex">
                    <form action="{{ route('request-logger.security.analytics') }}" method="GET" class="d-flex">
                        <select name="time_range" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="day" {{ $time_range == 'day' ? 'selected' : '' }}>Last 24 Hours</option>
                            <option value="week" {{ $time_range == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="month" {{ $time_range == 'month' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="quarter" {{ $time_range == 'quarter' ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="year" {{ $time_range == 'year' ? 'selected' : '' }}>Last 365 Days</option>
                        </select>
                    </form>

                    <a href="{{ route('request-logger.banned-ips.index') }}" class="btn btn-primary ml-2">
                        <span class="icon icon-shield mr-1"></span> Manage Banned IPs
                    </a>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-gradient-primary text-white">
                        <div class="card-body">
                            <h5 class="text-white-50">Active IP Bans</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="mb-0 text-white">{{ $summary['active_bans'] }}</h2>
                                <div class="card-icon bg-white text-primary rounded-circle p-3">
                                    <span class="icon icon-shield"></span>
                                </div>
                            </div>
                            <div class="mt-2 text-white-50">
                                <span class="{{ $summary['new_bans'] > 0 ? 'text-warning' : 'text-success' }}">
                                    {{ $summary['new_bans'] }} new
                                </span> in selected period
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card bg-gradient-danger text-white">
                        <div class="card-body">
                            <h5 class="text-white-50">Attacks Detected</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="mb-0 text-white">{{ $summary['attacks_detected'] }}</h2>
                                <div class="card-icon bg-white text-danger rounded-circle p-3">
                                    <span class="icon icon-alert-triangle"></span>
                                </div>
                            </div>
                            <div class="mt-2 text-white-50">
                                <span class="text-white">{{ $summary['success_rate'] }}% blocked</span> successfully
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card bg-gradient-warning text-white">
                        <div class="card-body">
                            <h5 class="text-white-50">Repeat Offenders</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="mb-0 text-white">{{ $summary['repeat_offenders'] }}</h2>
                                <div class="card-icon bg-white text-warning rounded-circle p-3">
                                    <span class="icon icon-repeat"></span>
                                </div>
                            </div>
                            <div class="mt-2 text-white-50">
                                Multiple ban incidents
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stats-card bg-gradient-info text-white">
                        <div class="card-body">
                            <h5 class="text-white-50">Top Attack Method</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="mb-0 text-white">
                                    {{ $summary['top_methods']->first()->method ?? 'N/A' }}
                                </h2>
                                <div class="card-icon bg-white text-info rounded-circle p-3">
                                    <span class="icon icon-trending-up"></span>
                                </div>
                            </div>
                            <div class="mt-2 text-white-50">
                                {{ $summary['top_methods']->first()->count ?? 0 }} requests
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attack Trends Chart -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Attack Trends</h5>
                            <div class="chart-container" style="position: relative; height: 300px;">
                                <canvas id="attackTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Top Attack Paths</h5>
                            <div class="attack-paths">
                                @forelse($top_attack_paths as $index => $path)
                                    <div class="attack-path-item mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <div class="text-truncate" title="{{ $path->path }}">
                                                <span class="badge badge-dark mr-2">{{ $index + 1 }}</span>
                                                {{ $path->path }}
                                            </div>
                                            <span class="badge badge-danger">{{ $path->count }}</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-danger"
                                                style="width: {{ ($path->count / $top_attack_paths->first()->count) * 100 }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-3">
                                        No attack paths detected in this period
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attack Heatmap and Geographic Distribution -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Attack Heatmap</h5>
                            <p class="text-muted">Attack frequency by day of week and hour (UTC)</p>
                            <div class="chart-container" style="position: relative; height: 350px;">
                                <canvas id="attackHeatmapChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Geographic Distribution</h5>
                            <p class="text-muted">Top countries by attack origin</p>

                            <div class="geo-chart">
                                @foreach($geographic_data as $geo)
                                    <div class="geo-item mb-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <div>
                                                <span class="flag-icon flag-icon-{{ strtolower($geo['country']) }} mr-2"></span>
                                                {{ $geo['country'] }}
                                            </div>
                                            <span>{{ $geo['count'] }}</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary"
                                                style="width: {{ ($geo['count'] / $geographic_data[0]['count']) * 100 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">Based on IP geolocation data</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Banned IPs and Recent Attacks -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Top Banned IPs</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Ban Count</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($top_banned_ips as $ip)
                                            <tr>
                                                <td>{{ $ip->ip_address }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $ip->ban_count > 3 ? 'danger' : 'warning' }}">
                                                        {{ $ip->ban_count }}
                                                    </span>
                                                </td>
                                                <td class="text-truncate" style="max-width: 200px;" title="{{ $ip->reason }}">
                                                    {{ $ip->reason }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $ip->banned_until->isFuture() ? 'danger' : 'secondary' }}">
                                                        {{ $ip->banned_until->isFuture() ? 'Active' : 'Expired' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No banned IPs found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Attack Attempts</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>IP</th>
                                            <th>Method</th>
                                            <th>Path</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recent_attacks as $attack)
                                            <tr>
                                                <td>{{ $attack->created_at->diffForHumans() }}</td>
                                                <td>{{ $attack->ip_address }}</td>
                                                <td>
                                                    <span class="badge badge-{{ strtolower($attack->method) }}">
                                                        {{ $attack->method }}
                                                    </span>
                                                </td>
                                                <td class="text-truncate" style="max-width: 200px;" title="{{ $attack->path }}">
                                                    {{ $attack->path }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $attack->response_status >= 400 ? 'danger' : 'success' }}">
                                                        {{ $attack->response_status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No recent attack attempts recorded</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #9333ea 0%, #6366f1 100%);
    }

    .bg-gradient-danger {
        background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #0ea5e9 0%, #22d3ee 100%);
    }

    .chart-container {
        position: relative;
        width: 100%;
    }

    .attack-path-item, .geo-item {
        padding: 8px 10px;
        border-radius: 4px;
        background-color: rgba(255, 255, 255, 0.03);
        transition: all 0.2s ease;
    }

    .attack-path-item:hover, .geo-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .text-truncate {
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .flag-icon {
        width: 1.2em;
        height: 1.2em;
        display: inline-block;
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
        vertical-align: middle;
    }

    /* Common flag icons */
    .flag-icon-us { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cg fill-rule='evenodd'%3E%3Cg stroke-width='1pt'%3E%3Cpath fill='%23bd3d44' d='M0 0h640v480H0z'/%3E%3Cpath stroke='%23fff' stroke-width='.6pt' d='M0 30h640m-640-60h640m-640-60h640m-640-60h640m-640-60h640m-640-60h640'/%3E%3C/g%3E%3Cpath fill='%23192f5d' d='M0 0h300v240H0z'/%3E%3Cpath fill='%23fff' d='M12.8 5.8l4 12.2h12.9l-10.4 7.6L73 37.8l-10.4-7.6 4-12.2H53.7l-10.4 7.6 4-12.2h-13 L27 25.6l3.9 12.2h-13L28.3 45.4l-10.4 7.6 4-12.2h-13.2l-9.7 6.6 4-12.2H-1z'/%3E%3C/g%3E%3C/svg%3E"); }
    .flag-icon-cn { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cdefs%3E%3Cpath id='a' fill='%23ffde00' d='M-.6-.9L0,0h.6z'/%3E%3C/defs%3E%3Cpath fill='%23de2910' d='M0 0h640v480H0z'/%3E%3Cuse transform='matrix(71.9991 0 0 72 36 204)' width='30' height='20'/%3E%3Cuse transform='matrix(-12.33562 -20.5126 20.50789 -12.33729 109.3 192.04)' width='30' height='20'/%3E%3C/svg%3E"); }
    .flag-icon-ru { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cg fill-rule='evenodd' stroke-width='1pt'%3E%3Cpath fill='%23fff' d='M0 0h640v480H0z'/%3E%3Cpath fill='%230052b4' d='M0 160h640v320H0z'/%3E%3Cpath fill='%23d52222' d='M0 320h640v160H0z'/%3E%3C/g%3E%3C/svg%3E"); }
    .flag-icon-in { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cpath fill='%23f93' d='M0 0h640v160H0z'/%3E%3Cpath fill='%23fff' d='M0 160h640v160H0z'/%3E%3Cpath fill='%23128807' d='M0 320h640v160H0z'/%3E%3Cg transform='translate(320 240)'%3E%3Ccircle r='70' fill='%23008'/%3E%3Ccircle r='60' fill='%23fff'/%3E%3Ccircle r='50' fill='%23008'/%3E%3C/g%3E%3C/svg%3E"); }
    .flag-icon-br { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cpath fill='%2300994d' d='M0 0h640v480H0z'/%3E%3Cpath fill='%23fedb00' d='M320 240 m0-120 1 207.8 207.9-.3-168.2 122 151.6 140.8L360 468 207.5 590.2 61 449.8%50 327.6 257.4 328z'/%3E%3C/svg%3E"); }
    .flag-icon-gb { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cpath fill='%23012169' d='M0 0h640v480H0z'/%3E%3Cpath fill='%23FFF' d='M75 0l244 181L562 0h78v62L400 241l240 178v61h-80L320 301 81 480H0v-60l239-178L0 64V0h75z'/%3E%3C/svg%3E"); }
    .flag-icon-de { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cpath fill='%23ffce00' d='M0 320h640v160H0z'/%3E%3Cpath d='M0 0h640v160H0z'/%3E%3Cpath fill='%23d00' d='M0 160h640v160H0z'/%3E%3C/svg%3E"); }
    .flag-icon-fr { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cg fill-rule='evenodd' stroke-width='1pt'%3E%3Cpath fill='%23fff' d='M0 0h640v480H0z'/%3E%3Cpath fill='%2300267f' d='M0 0h213.3v480H0z'/%3E%3Cpath fill='%23f31830' d='M426.7 0H640v480H426.7z'/%3E%3C/g%3E%3C/svg%3E"); }
    .flag-icon-jp { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cdefs%3E%3CclipPath id='a'%3E%3Cpath fill-opacity='.7' d='M-88 32h640v480H-88z'/%3E%3C/clipPath%3E%3C/defs%3E%3Cg clip-path='url(%23a)' transform='translate(88 -32)'%3E%3Cg fill-rule='evenodd' stroke-width='1pt'%3E%3Cpath fill='%23fff' d='M-128 32h720v480h-720z'/%3E%3Ccircle cx='224' cy='272' r='144' fill='%23d30000' transform='translate(-51.3 -14) scale(1.08)'/></g%3E%3C/g%3E%3C/svg%3E"); }
    .flag-icon-kr { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 480'%3E%3Cdefs%3E%3CclipPath id='a'%3E%3Cpath fill-opacity='.7' d='M-95.8 0h682.7v512H-95.8z'/%3E%3C/clipPath%3E%3C/defs%3E%3Cg clip-path='url(%23a)' transform='translate(89.8 0) scale(.9375)'%3E%3Cpath fill='%23fff' d='M-95.8 0h682.7v512H-95.8z'/%3E%3Cg transform='translate(-93.6 -89) scale(1.09)'%3E%3Ccircle cx='240.5' cy='317' r='72' fill='%23c60c30'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }

    .icon-shield:before {
        content: "\f3ed";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .icon-alert-triangle:before {
        content: "\f071";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .icon-repeat:before {
        content: "\f01e";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .icon-trending-up:before {
        content: "\f062";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .text-white-50 {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .badge-get {
        background-color: #10b981;
        color: white;
    }

    .badge-post {
        background-color: #3b82f6;
        color: white;
    }

    .badge-put {
        background-color: #f59e0b;
        color: white;
    }

    .badge-delete {
        background-color: #ef4444;
        color: white;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.2.0/dist/chartjs-chart-matrix.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize attack trends chart
    const trendsCtx = document.getElementById('attackTrendsChart').getContext('2d');

    const trendsData = @json($attack_trends);

    const attackTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: trendsData.map(item => item.date),
            datasets: [
                {
                    label: 'Attacks',
                    data: trendsData.map(item => item.attacks),
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                },
                {
                    label: 'New Bans',
                    data: trendsData.map(item => item.bans),
                    borderColor: 'rgba(147, 51, 234, 1)',
                    backgroundColor: 'rgba(147, 51, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(147, 51, 234, 1)',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    }
                }
            }
        }
    });

    // Initialize attack heatmap chart
    const heatmapCtx = document.getElementById('attackHeatmapChart').getContext('2d');

    // Check if Matrix chart type is available
    if (typeof Chart.controllers.matrix !== 'undefined') {
        const heatmapData = @json($attack_heatmap);
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const hours = Array.from({length: 24}, (_, i) => `${i}:00`);

        // Find the maximum attack count to normalize colors
        let maxCount = 0;
        for (let day = 0; day < 7; day++) {
            for (let hour = 0; hour < 24; hour++) {
                if (heatmapData[day][hour] > maxCount) {
                    maxCount = heatmapData[day][hour];
                }
            }
        }

        // Build dataset for matrix chart
        const dataset = [];
        for (let day = 0; day < 7; day++) {
            for (let hour = 0; hour < 24; hour++) {
                const value = heatmapData[day][hour];
                dataset.push({
                    x: hour,
                    y: day,
                    v: value
                });
            }
        }

        const attackHeatmapChart = new Chart(heatmapCtx, {
            type: 'matrix',
            data: {
                datasets: [{
                    data: dataset,
                    backgroundColor(context) {
                        const value = context.dataset.data[context.dataIndex].v;
                        const alpha = Math.min(0.9, Math.max(0.1, value / (maxCount || 1)));
                        return `rgba(239, 68, 68, ${alpha})`;
                    },
                    borderColor: 'rgba(30, 41, 59, 0.3)',
                    borderWidth: 1,
                    width: 20,
                    height: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            title() { return ''; },
                            label(context) {
                                const data = context.dataset.data[context.dataIndex];
                                return `${days[data.y]} at ${hours[data.x]}: ${data.v} attacks`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: hours,
                        position: 'top',
                        offset: true,
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            callback: function(value, index) {
                                return index % 3 === 0 ? this.getLabelForValue(value) : '';
                            }
                        }
                    },
                    y: {
                        type: 'category',
                        labels: days,
                        offset: true,
                        ticks: {
                            display: true
                        }
                    }
                }
            }
        });
    } else {
        console.warn('Matrix chart type is not available. Please check your Chart.js and chartjs-chart-matrix library versions.');
    }

    // Optional: Add refresh mechanism
    function refreshDashboard() {
        // You can implement AJAX refresh or redirect to the same page
        location.reload();
    }

    // Optional: Set up periodic refresh (e.g., every 5 minutes)
    // Uncomment and adjust as needed
    // setInterval(refreshDashboard, 5 * 60 * 1000);
});
</script>
@endpush
