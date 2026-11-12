@extends('layouts.app')

@section('title', 'Backup & System Health')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <p class="text-uppercase small fw-bold accent mb-1">Administration</p>
            <h1 class="fw-bold mb-0">Backup & System Diagnostics</h1>
        </div>
        <div>
            <a href="{{ route('owner.backup.download') }}" class="btn btn-primary">
                <span>↓ Download SQL Backup</span>
            </a>
        </div>
    </div>

    <!-- System Diagnostics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-4 h-100">
                <small class="muted text-uppercase fw-semibold">Database Engine</small>
                <h4 class="fw-bold mt-2 mb-1 accent">{{ $systemInfo['database'] }}</h4>
                <small class="{{ $systemInfo['database_status'] === 'Connected' ? 'text-success' : 'text-danger' }}">● {{ $systemInfo['database_status'] }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 h-100">
                <small class="muted text-uppercase fw-semibold">Database Schema</small>
                <h4 class="fw-bold mt-2 mb-1">{{ $systemInfo['tables_count'] }} Tables</h4>
                <small class="muted">~{{ number_format($systemInfo['total_rows']) }} total rows</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 h-100">
                <small class="muted text-uppercase fw-semibold">PHP Environment</small>
                <h4 class="fw-bold mt-2 mb-1">PHP {{ $systemInfo['php_version'] }}</h4>
                <small class="muted">Laravel {{ $systemInfo['laravel_version'] }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 h-100">
                <small class="muted text-uppercase fw-semibold">Data Footprint</small>
                <h4 class="fw-bold mt-2 mb-1">{{ $systemInfo['total_size_kb'] }} KB</h4>
                <small class="muted">Local storage snapshot</small>
            </div>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1">Recent backup exports</h2>
            <p class="muted small mb-0">Exports are streamed to the administrator and are not retained as downloadable files on the server.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead><tr><th>Generated</th><th>Requested by</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($backupHistory as $backup)
                        <tr>
                            <td>{{ $backup->created_at?->format('M d, Y H:i') ?? '-' }}</td>
                            <td>{{ $backup->user?->name ?? 'Former account' }}</td>
                            <td><span class="badge bg-success">Export generated</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-3 muted">No completed backup exports are recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Backup Instructions and Actions -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-2">Manual Snapshot & Recovery</h5>
        <p class="muted mb-3">Download a SQL export of the current application database. Keep the downloaded file in an access-controlled location because it contains sensitive business and account data.</p>
        @if ($systemInfo['database'] === 'SQLITE')
            <p class="text-warning small">This export uses SQLite syntax. Restore it only with SQLite tooling into a separate test database; do not use MySQL restore instructions.</p>
        @else
            <p class="text-warning small">Test restoration only on a separate staging database. This SQL file contains DROP TABLE statements and must never be imported into the live database for testing.</p>
        @endif
        <div class="d-flex gap-3 flex-wrap align-items-center">
            <a href="{{ route('owner.backup.download') }}" class="btn btn-primary px-4">
                Export & Download Database (.sql)
            </a>
            <span class="muted small">Current server time: {{ $systemInfo['server_time'] }}</span>
        </div>
    </div>

    <!-- Tables List -->
    <div class="card p-0 overflow-hidden">
        <div class="p-3 border-bottom">
            <h5 class="fw-bold mb-0">Database Tables Inventory</h5>
        </div>
        <table class="table table-striped mb-0 align-middle">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th class="text-center">Estimated Rows</th>
                    <th class="text-end">Storage Size</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tables as $table)
                    <tr>
                        <td><span class="font-monospace accent">{{ $table->name }}</span></td>
                        <td class="text-center">{{ number_format($table->rows) }}</td>
                        <td class="text-end muted">{{ round($table->size / 1024, 1) }} KB</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-3 muted">No tables detected.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
