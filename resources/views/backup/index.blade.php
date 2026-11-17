@extends('layouts.app')

@section('title', 'Database Backup')

@section('content')
@php
    $isSysAdmin = auth()->user()?->isSystemAdmin();
    $backRoute = $isSysAdmin ? route('sysadmin.dashboard') : route('owner.settings.index');
    $backLabel = $isSysAdmin ? '← Back to Technical Dashboard' : '← Back to Settings';
    $downloadRoute = $isSysAdmin ? route('sysadmin.backup.download') : route('owner.backup.download');
@endphp

<x-page-header
    :section="$isSysAdmin ? 'System Operations' : 'Administration'"
    :breadcrumb="$isSysAdmin ? 'Backups' : 'Settings'"
    title="Database Backup &amp; System Health"
    subtitle="Inspect database health metrics, review recent exports, and download an SQL database backup.">
    <x-slot:actions>
        <a href="{{ $backRoute }}" class="btn btn-outline-light">{{ $backLabel }}</a>
        <a href="#verified-backup-form" class="btn btn-primary">Download SQL Backup</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div id="verified-backup-form" class="eb-panel-card mb-4">
    <div class="eb-panel-header">
        <div>
            <h2 class="h6 fw-bold mb-0 text-white">
                {{ $isSysAdmin ? 'Technical Infrastructure SQL Backup' : 'Business Database SQL Backup' }}
            </h2>
            <div class="small muted mt-1">
                {{ $isSysAdmin
                    ? 'Exports database schema DDL and technical configuration/logs only. User credentials, password hashes, MFA secrets, tokens, sessions, and confidential business data are excluded.'
                    : 'Exports full business schema and operational inventory/purchasing records (ephemeral tokens and active sessions excluded).' }}
            </div>
        </div>
    </div>
    <div class="eb-panel-body">
        <form method="POST" action="{{ $downloadRoute }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-7 col-lg-5">
                <label class="form-label" for="backupCurrentPassword">Confirm Your Password <span class="required-star">*</span></label>
                <input id="backupCurrentPassword" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Enter your account password to export SQL" required autocomplete="current-password">
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-5 col-lg-4">
                <button type="submit" class="btn btn-primary w-100">Verify Password &amp; Download SQL</button>
            </div>
        </form>
    </div>
</div>

<h2 class="h6 fw-bold text-uppercase mb-3" style="color: #BDBDBD; letter-spacing: 0.06em;">System Diagnostics</h2>
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <x-kpi-card
            label="Database Engine"
            :value="$systemInfo['database']"
            :meta="$systemInfo['database_status']"
            :metaTone="$systemInfo['database_status'] === 'Connected' ? 'up' : 'danger'"
            :tone="$systemInfo['database_status'] === 'Connected' ? 'green' : 'red'">
            <x-slot:icon>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            </x-slot:icon>
        </x-kpi-card>
    </div>
    <div class="col">
        <x-kpi-card
            label="Tables Tracked"
            :value="$systemInfo['tables_count'] . ' Tables'"
            :meta="'~' . number_format($systemInfo['total_rows']) . ' total rows'"
            metaTone="up"
            tone="yellow">
            <x-slot:icon>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            </x-slot:icon>
        </x-kpi-card>
    </div>
    <div class="col">
        <x-kpi-card
            label="Runtime Environment"
            :value="'PHP ' . $systemInfo['php_version']"
            :meta="'Laravel ' . $systemInfo['laravel_version']"
            metaTone="up"
            tone="green">
            <x-slot:icon>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </x-slot:icon>
        </x-kpi-card>
    </div>
    <div class="col">
        <x-kpi-card
            label="Estimated Storage"
            :value="$systemInfo['total_size_kb'] . ' KB'"
            meta="Active database footprint"
            metaTone="warn"
            tone="red">
            <x-slot:icon>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </x-slot:icon>
        </x-kpi-card>
    </div>
</div>

@if ($systemInfo['database'] === 'SQLITE')
    <div class="eb-alert eb-alert-info small">
        <strong>SQLite Note:</strong> Backups export standard SQL statements ready for inspection or archival storage.
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-6">
        <x-table-card title="Recent backup exports">
            <x-slot:headerActions>
                <span class="small" style="color: #BDBDBD;">Server time: {{ $systemInfo['server_time'] }}</span>
            </x-slot:headerActions>

            @if ($backupHistory->isEmpty())
                <x-empty-state
                    title="No exports recorded yet"
                    description="Enter your password above and click Verify Password &amp; Download SQL to generate a backup."
                    actionUrl="#verified-backup-form"
                    actionLabel="Download SQL Backup" />
            @else
                <div class="table-responsive">
                    <table class="table eb-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Triggered By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backupHistory as $backup)
                                <tr>
                                    <td class="small text-white">{{ $backup->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                    <td class="small" style="color: #BDBDBD;">{{ $backup->user?->name ?? 'Former account' }}</td>
                                    <td><x-status-badge variant="success" label="Export generated" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-table-card>
    </div>

    <div class="col-lg-6">
        <x-table-card title="Database Tables Inventory">
            <x-slot:headerActions>
                <a href="#verified-backup-form" class="eb-panel-action">Verify &amp; Export SQL &rarr;</a>
            </x-slot:headerActions>

            @if (empty($tables))
                <x-empty-state title="No tables found" description="Unable to inspect schema tables for this database driver." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th class="text-center">Rows</th>
                                <th class="text-end">Approx. Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tables as $table)
                                <tr>
                                    <td class="font-monospace small text-warning">{{ $table->name }}</td>
                                    <td class="text-center text-white fw-semibold">{{ number_format($table->rows) }}</td>
                                    <td class="text-end small" style="color: #BDBDBD;">{{ round($table->size / 1024, 1) }} KB</td>
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
