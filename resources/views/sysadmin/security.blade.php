@extends('layouts.app')

@section('title', 'Security Monitoring')

@section('content')
<x-page-header
    section="System Operations"
    breadcrumb="Security"
    title="Security Monitoring &amp; Technical Audit"
    subtitle="Configure login throttling, inspect active session metadata (no tokens), and monitor technical security logs.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-light">&larr; Technical Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Security &amp; Lockout Controls</h2>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('sysadmin.security.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="lockoutAttempts">Max Failed Login Attempts <span class="required-star">*</span></label>
                        <input id="lockoutAttempts" type="number" name="security_lockout_attempts" min="3" max="15" class="form-control" value="{{ old('security_lockout_attempts', $techSettings->security_lockout_attempts) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lockoutMinutes">Lockout Window (Minutes) <span class="required-star">*</span></label>
                        <input id="lockoutMinutes" type="number" name="security_lockout_minutes" min="5" max="120" class="form-control" value="{{ old('security_lockout_minutes', $techSettings->security_lockout_minutes) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="apiRateLimit">API Rate Limit (Per Minute) <span class="required-star">*</span></label>
                        <input id="apiRateLimit" type="number" name="api_rate_limit_per_minute" min="20" max="300" class="form-control" value="{{ old('api_rate_limit_per_minute', $techSettings->api_rate_limit_per_minute) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="secPassword">Confirm Your System Admin Password <span class="required-star">*</span></label>
                        <input id="secPassword" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Security Policy</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="Active Sessions Metadata (Single-Session Enforcement)">
            @if ($activeSessions->isEmpty())
                <x-empty-state title="No active tracked sessions" description="Active web and API sessions appear here without exposing session or token values." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Channel</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeSessions as $sessionRow)
                                <tr>
                                    <td class="fw-semibold text-white font-monospace" data-label="Username">
                                        {{ $sessionRow['username'] }}
                                        @if ($sessionRow['is_current_user'])
                                            <span class="badge text-bg-warning ms-1">You</span>
                                        @endif
                                    </td>
                                    <td class="small text-white" data-label="Role">{{ $sessionRow['role_label'] }}</td>
                                    <td class="small muted text-uppercase" data-label="Channel">{{ $sessionRow['channel'] }}</td>
                                    <td class="small muted" data-label="Last Activity">{{ $sessionRow['last_activity_at']?->diffForHumans() ?? '—' }}</td>
                                    <td data-label="Status">
                                        <x-status-badge :variant="$sessionRow['status'] === 'Active' ? 'success' : 'neutral'" :label="$sessionRow['status']" />
                                    </td>
                                    <td class="text-end" data-label="Action">
                                        @if ($sessionRow['role_name'] === 'system_admin' && !$sessionRow['is_current_user'] && $sessionRow['status'] === 'Active')
                                            <form method="POST" action="{{ route('sysadmin.security.sessions.revoke', $sessionRow['user_id']) }}" class="d-inline-flex align-items-center gap-1">
                                                @csrf
                                                <input type="password" name="current_password" class="form-control form-control-sm" style="width: 130px;" placeholder="Password" required autocomplete="current-password">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                            </form>
                                        @else
                                            <span class="small muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-table-card>
    </div>
</div>

<x-table-card title="Technical &amp; Security Audit Logs">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('sysadmin.security') }}" class="d-flex flex-wrap gap-2">
            <select name="category" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Categories</option>
                @foreach (['security', 'technical', 'backup', 'account_admin', 'access_request'] as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width: 180px;" value="{{ request('search') }}" placeholder="Search logs...">
            <button type="submit" class="btn btn-sm btn-outline-light">Filter</button>
        </form>
    </x-slot:headerActions>

    @if ($technicalLogs->isEmpty())
        <x-empty-state title="No matching technical audit records" description="Technical and security activities are recorded separately from business audit logs." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Category</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>IP</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($technicalLogs as $log)
                        <tr>
                            <td class="small muted text-nowrap" data-label="Time">{{ $log->created_at?->format('M d, Y H:i:s') }}</td>
                            <td data-label="Category"><x-status-badge variant="neutral" :label="$log->category" /></td>
                            <td class="small font-monospace text-warning" data-label="Action">{{ $log->action }}</td>
                            <td class="small text-white" data-label="Actor">{{ $log->user?->username ?? 'system' }}</td>
                            <td class="small font-monospace muted" data-label="IP">{{ $log->ip_address ?? '—' }}</td>
                            <td class="small text-white" data-label="Details">{{ $log->details }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="small muted">Showing {{ $technicalLogs->firstItem() ?? 0 }}&ndash;{{ $technicalLogs->lastItem() ?? 0 }} of {{ $technicalLogs->total() }} entries</div>
            <div>{{ $technicalLogs->links() }}</div>
        </x-slot:footer>
    @endif
</x-table-card>
@endsection

