@extends('layouts.app')

@section('title', 'Technical Dashboard')

@section('content')
<x-page-header
    section="Technical Administration"
    breadcrumb="Dashboard"
    title="System Admin Technical Dashboard"
    subtitle="Application health, technical configuration, active session count, and security logs. Confidential business financials are isolated.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.health') }}" class="btn btn-outline-light">App Health</a>
        <a href="{{ route('sysadmin.access.index') }}" class="btn btn-primary">Request Business Access</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <x-kpi-card
            label="Database Status"
            :value="$systemInfo['database']"
            :meta="$systemInfo['database_status'] . ' · ' . $systemInfo['environment']"
            :metaTone="$systemInfo['database_status'] === 'Connected' ? 'up' : 'danger'"
            :tone="$systemInfo['database_status'] === 'Connected' ? 'green' : 'red'" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Active Sessions"
            :value="(string) $activeSessionsCount"
            meta="Tracked single-session locks"
            metaTone="neutral"
            tone="amber" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Failed Logins (24h)"
            :value="(string) $failedLoginCount24h"
            :meta="$failedLoginCount24h > 0 ? 'Review security log' : 'No spikes detected'"
            :metaTone="$failedLoginCount24h > 0 ? 'warning' : 'up'"
            :tone="$failedLoginCount24h > 0 ? 'red' : 'green'" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Maintenance Mode"
            :value="$techSettings->maintenance_mode ? 'ENABLED' : 'Normal'"
            :meta="'API rate: ' . $techSettings->api_rate_limit_per_minute . '/min'"
            :metaTone="$techSettings->maintenance_mode ? 'warning' : 'up'"
            :tone="$techSettings->maintenance_mode ? 'amber' : 'blue'" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Runtime &amp; Role Footprint</h2>
                <a href="{{ route('sysadmin.config') }}" class="eb-panel-action">Configure &rarr;</a>
            </div>
            <div class="eb-panel-body">
                <dl class="row mb-3 small">
                    <dt class="col-5 muted">PHP / Laravel</dt>
                    <dd class="col-7 text-white font-monospace">{{ $systemInfo['php_version'] }} / {{ $systemInfo['laravel_version'] }}</dd>
                    <dt class="col-5 muted">Cache / Session</dt>
                    <dd class="col-7 text-white font-monospace">{{ $systemInfo['cache_driver'] }} / {{ $systemInfo['session_driver'] }}</dd>
                    <dt class="col-5 muted">Lockout Policy</dt>
                    <dd class="col-7 text-white">{{ $techSettings->security_lockout_attempts }} attempts / {{ $techSettings->security_lockout_minutes }} mins</dd>
                    <dt class="col-5 muted">Log / Backup Retention</dt>
                    <dd class="col-7 text-white">{{ $techSettings->log_retention_days }}d / {{ $techSettings->backup_retention_days }}d</dd>
                </dl>

                <div class="border-top pt-3" style="border-color: #2A2A2A !important;">
                    <div class="small fw-semibold muted mb-2 text-uppercase">Account Distribution (Counts Only)</div>
                    <div class="d-flex flex-wrap gap-2">
                        <x-status-badge variant="info" :label="'System Admin: ' . ($roleCounts['system_admin'] ?? 0)" />
                        <x-status-badge variant="warning" :label="'Owner: ' . ($roleCounts['owner'] ?? 0)" />
                        <x-status-badge variant="success" :label="'Staff: ' . ($roleCounts['staff'] ?? 0)" />
                        <x-status-badge variant="neutral" :label="'Supplier: ' . ($roleCounts['supplier'] ?? 0)" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="Recent Technical &amp; Security Events">
            <x-slot:headerActions>
                <a href="{{ route('sysadmin.security') }}" class="eb-panel-action">View All &rarr;</a>
            </x-slot:headerActions>

            @if ($recentSecurityEvents->isEmpty())
                <x-empty-state title="No technical events recorded" description="Authentication, configuration, backup, and access events appear here." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Category</th>
                                <th>Action</th>
                                <th>Actor</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSecurityEvents as $event)
                                <tr>
                                    <td class="small muted text-nowrap" data-label="Time">{{ $event->created_at?->format('M d, H:i') }}</td>
                                    <td data-label="Category"><x-status-badge variant="neutral" :label="$event->category" /></td>
                                    <td class="small font-monospace text-warning" data-label="Action">{{ $event->action }}</td>
                                    <td class="small text-white" data-label="Actor">{{ $event->user?->username ?? 'system' }}</td>
                                    <td class="small text-white" data-label="Details">{{ Str::limit($event->details, 90) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-table-card>
    </div>

    <div class="col-12">
        <x-table-card title="Recent Exceptional Business Data Access Requests">
            <x-slot:headerActions>
                <a href="{{ route('sysadmin.access.index') }}" class="eb-panel-action">Manage Grants &rarr;</a>
            </x-slot:headerActions>
            @if ($myGrants->isEmpty())
                <x-empty-state title="No access requests submitted" description="Confidential sales, supplier pricing, and inventory records require explicit time-limited Owner authorization." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Scope</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Expires At</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($myGrants as $grant)
                                @php
                                    $effective = $grant->effectiveStatus();
                                    $variant = match ($effective) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'revoked', 'rejected' => 'danger',
                                        default => 'neutral',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-white" data-label="Scope">{{ $grant->scopeLabel() }}</td>
                                    <td class="small muted" data-label="Duration">{{ $grant->duration_minutes }} mins</td>
                                    <td data-label="Status"><x-status-badge :variant="$variant" :label="ucfirst($effective)" /></td>
                                    <td class="small muted" data-label="Expires">{{ $grant->expires_at?->format('M d, Y h:i A') ?? '—' }}</td>
                                    <td class="text-end eb-cell-actions">
                                        @if ($grant->isCurrentlyActive())
                                            <a href="{{ route('sysadmin.access.view', $grant) }}" class="btn btn-sm btn-warning text-dark fw-semibold">Inspect Scoped Data</a>
                                        @else
                                            <span class="small muted">Read access closed</span>
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
@endsection

