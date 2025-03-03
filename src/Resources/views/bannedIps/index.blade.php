{{-- resources/views/vendor/request-logger/banned-ips/index.blade.php --}}
@extends('request-logger::layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">Banned IP Addresses</h1>
                    <span class="text-muted">Security protection for your application</span>

                    <div class="actions d-flex">
                        <a href="{{ route('request-logger.index') }}" class="btn btn-secondary">
                            <span class="icon icon-arrow-left mr-1"></span> Back to Requests
                        </a>
                    </div>
                </div>

                {{-- Quick Stats Cards --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title text-muted">Total Banned IPs</h5>
                                        <h2 class="mb-0 text-gradient" id="total-banned">{{ $bannedStats['total'] ?? 0 }}</h2>
                                    </div>
                                    <div class="card-icon bg-primary text-white rounded-circle p-3 glow">
                                        <span class="icon icon-shield"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title text-muted">Active Bans</h5>
                                        <h2 class="mb-0 text-gradient" id="active-bans">{{ $bannedStats['active'] ?? 0 }}</h2>
                                    </div>
                                    <div class="card-icon bg-danger text-white rounded-circle p-3 glow">
                                        <span class="icon icon-ban"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title text-muted">Repeat Offenders</h5>
                                        <h2 class="mb-0 text-gradient" id="repeat-offenders">{{ $bannedStats['repeat'] ?? 0 }}</h2>
                                    </div>
                                    <div class="card-icon bg-warning text-white rounded-circle p-3 glow-cyan">
                                        <span class="icon icon-alert-triangle"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Banned IPs Table --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Banned IP Addresses</h5>
                            <div>
                                <button class="btn btn-sm btn-secondary mr-2" id="refresh-list">
                                    <span class="icon icon-refresh mr-1"></span> Refresh
                                </button>
                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#banIpModal">
                                    <span class="icon icon-plus mr-1"></span> Ban IP Manually
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="banned-ips-table">
                                <thead>
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Reason</th>
                                        <th>Banned Until</th>
                                        <th>Ban Count</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bannedIps as $ip)
                                        <tr data-ip="{{ $ip->ip_address }}">
                                            <td>{{ $ip->ip_address }}</td>
                                            <td>{{ $ip->reason }}</td>
                                            <td>{{ $ip->banned_until->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                <span class="badge badge-{{ $ip->ban_count > 2 ? 'danger' : 'warning' }}">
                                                    {{ $ip->ban_count }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $ip->banned_until->isFuture() ? 'danger' : 'secondary' }}">
                                                    {{ $ip->banned_until->isFuture() ? 'Active' : 'Expired' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-details" data-ip="{{ $ip->ip_address }}">
                                                    Details
                                                </button>
                                                @if($ip->banned_until->isFuture())
                                                    <button class="btn btn-sm btn-primary unban-ip" data-ip="{{ $ip->ip_address }}">
                                                        Unban
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No banned IPs found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                Showing {{ $bannedIps->firstItem() ?? 0 }} to {{ $bannedIps->lastItem() ?? 0 }} of {{ $bannedIps->total() }} entries
                            </div>
                            <div>
                                <ul class="custom-pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($bannedIps->onFirstPage())
                                        <li class="disabled"><span>«</span></li>
                                    @else
                                        <li><a href="{{ $bannedIps->previousPageUrl() }}" rel="prev">«</a></li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($bannedIps->links()->elements[0] as $page => $url)
                                        @if ($page == $bannedIps->currentPage())
                                            <li class="active"><span>{{ $page }}</span></li>
                                        @else
                                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($bannedIps->hasMorePages())
                                        <li><a href="{{ $bannedIps->nextPageUrl() }}" rel="next">»</a></li>
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
    </div>

    {{-- Ban IP Modal --}}
    <div class="modal fade" id="banIpModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ban IP Address</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ban-ip-form">
                        <div class="form-group">
                            <label for="ip-address">IP Address</label>
                            <input type="text" class="form-control" id="ip-address" placeholder="Enter IP address">
                        </div>
                        <div class="form-group">
                            <label for="ban-reason">Reason</label>
                            <input type="text" class="form-control" id="ban-reason" placeholder="Reason for ban">
                        </div>
                        <div class="form-group">
                            <label for="ban-duration">Ban Duration (hours)</label>
                            <div class="d-flex align-items-center">
                                <input type="range" class="form-control-range mr-3" id="ban-duration" min="1" max="168" value="24" style="flex: 1;">
                                <span id="duration-value" class="badge badge-primary" style="width: 60px; text-align: center;">24 hours</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-ban">Ban IP</button>
                </div>
            </div>
        </div>
    </div>

    {{-- IP Details Modal --}}
    <div class="modal fade" id="ipDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">IP Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ip-details-content">
                    {{-- Content will be loaded via JS --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Include the improved JS from our standalone script
        document.addEventListener('DOMContentLoaded', function() {
            // Ban duration slider
            const banDurationSlider = document.getElementById('ban-duration');
            const durationValue = document.getElementById('duration-value');

            if (banDurationSlider) {
                banDurationSlider.addEventListener('input', function() {
                    durationValue.textContent = this.value + ' hours';
                });
            }

            // Unban IP functionality
            document.querySelectorAll('.unban-ip').forEach(button => {
                button.addEventListener('click', function() {
                    const ip = this.getAttribute('data-ip');
                    if (confirm(`Are you sure you want to unban ${ip}?`)) {
                        unbanIp(ip);
                    }
                });
            });

            // View IP details - modified to work without fetching
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const ip = row.getAttribute('data-ip');

                    // Get data from the row instead of fetching
                    const ipAddress = row.querySelector('td:nth-child(1)').textContent.trim();
                    const reason = row.querySelector('td:nth-child(2)').textContent.trim();
                    const bannedUntil = row.querySelector('td:nth-child(3)').textContent.trim();
                    const banCount = row.querySelector('td:nth-child(4) .badge').textContent.trim();
                    const status = row.querySelector('td:nth-child(5) .badge').textContent.trim();
                    const isActive = status.includes('Active');

                    // Show the data in the modal
                    showIpDetails({
                        ip_address: ipAddress,
                        reason: reason,
                        banned_until: bannedUntil,
                        ban_count: banCount,
                        is_active: isActive
                    });
                });
            });

            // Refresh list button
            document.getElementById('refresh-list')?.addEventListener('click', function() {
                window.location.reload();
            });

            // Confirm ban button
            document.getElementById('confirm-ban')?.addEventListener('click', function() {
                const ip = document.getElementById('ip-address').value;
                const reason = document.getElementById('ban-reason').value;
                const duration = document.getElementById('ban-duration').value;

                if (!ip) {
                    showAlert('Please enter an IP address', 'danger');
                    return;
                }

                banIp(ip, reason || 'Manually banned', duration);
            });

            // Function to show IP details in the modal
            function showIpDetails(ipData) {
                const detailsContent = document.getElementById('ip-details-content');

                // Format details
                let html = `
                    <div class="ip-details">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3>${ipData.ip_address}</h3>
                            <span class="badge badge-${ipData.is_active ? 'danger' : 'secondary'}">
                                ${ipData.is_active ? 'Active Ban' : 'Expired Ban'}
                            </span>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Ban Details</h5>
                                        <dl class="row">
                                            <dt class="col-sm-3">Reason</dt>
                                            <dd class="col-sm-9">${ipData.reason || 'Not specified'}</dd>

                                            <dt class="col-sm-3">Ban expires</dt>
                                            <dd class="col-sm-9">${ipData.banned_until}</dd>

                                            <dt class="col-sm-3">Times banned</dt>
                                            <dd class="col-sm-9">${ipData.ban_count}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h5 class="alert-heading">Want to see more details?</h5>
                            <p>For a full history of requests from this IP, please check your request logs.</p>
                        </div>
                    </div>`;

                detailsContent.innerHTML = html;

                // Show the modal
                if (window.modalInstances && window.modalInstances['ipDetailsModal']) {
                    window.modalInstances['ipDetailsModal'].show();
                } else {
                    // Fallback for modal display if instances aren't available
                    const modal = document.getElementById('ipDetailsModal');
                    if (modal) {
                        modal.classList.add('show');
                        modal.style.display = 'block';
                        document.body.classList.add('modal-open');

                        // Add backdrop if it doesn't exist
                        let backdrop = document.querySelector('.modal-backdrop');
                        if (!backdrop) {
                            backdrop = document.createElement('div');
                            backdrop.classList.add('modal-backdrop', 'show');
                            document.body.appendChild(backdrop);
                        }
                    }
                }
            }

            // Function to unban an IP
            function unbanIp(ip) {
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch('/request-logger/api/unban-ip', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ ip: ip })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Remove the IP from the table or update its status
                        const row = document.querySelector(`tr[data-ip="${ip}"]`);
                        if (row) {
                            row.remove();
                        }
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error unbanning IP:', error);
                    showAlert('Failed to unban IP: ' + error.message, 'danger');
                });
            }

            // Function to ban an IP
            function banIp(ip, reason, duration) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Show loading state on button
                const confirmBtn = document.getElementById('confirm-ban');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner"></span> Processing...';

                fetch('/request-logger/banned-ips', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        ip_address: ip,
                        reason: reason,
                        duration: duration
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Ban IP';

                    if (data.success) {
                        // Close modal
                        if (window.modalInstances && window.modalInstances['banIpModal']) {
                            window.modalInstances['banIpModal'].hide();
                        } else {
                            // Fallback for closing modal
                            const modal = document.getElementById('banIpModal');
                            if (modal) {
                                modal.classList.remove('show');
                                modal.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                document.querySelector('.modal-backdrop')?.remove();
                            }
                        }

                        // Show success message
                        showAlert(data.message, 'success');

                        // Refresh the page to show new ban
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        showAlert(data.message || 'Failed to ban IP', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error banning IP:', error);

                    // Reset button state
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Ban IP';

                    showAlert('Failed to ban IP: ' + error.message, 'danger');
                });
            }

            // Show alert function (if not already defined)
            if (typeof showAlert !== 'function') {
                window.showAlert = function(message, type) {
                    // Remove existing alerts
                    const existingAlerts = document.querySelectorAll('.alert-notification');
                    existingAlerts.forEach(alert => alert.remove());

                    // Create alert element
                    const alertElement = document.createElement('div');
                    alertElement.className = `alert-notification alert alert-${type}`;
                    alertElement.innerHTML = message;
                    alertElement.style.position = 'fixed';
                    alertElement.style.top = '20px';
                    alertElement.style.right = '20px';
                    alertElement.style.zIndex = '9999';
                    alertElement.style.minWidth = '250px';
                    alertElement.style.maxWidth = '350px';
                    alertElement.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';

                    // Add to document
                    document.body.appendChild(alertElement);

                    // Remove after timeout
                    setTimeout(() => {
                        alertElement.style.opacity = '0';
                        alertElement.style.transition = 'opacity 0.5s';

                        setTimeout(() => {
                            alertElement.remove();
                        }, 500);
                    }, 4000);
                };
            }

            // Format JSON function (if needed)
            if (typeof formatJSON !== 'function') {
                window.formatJSON = function(obj) {
                    if (!obj) return 'No data';
                    try {
                        return JSON.stringify(obj, null, 2);
                    } catch (e) {
                        return String(obj);
                    }
                };
            }
        });
    </script>
@endpush
