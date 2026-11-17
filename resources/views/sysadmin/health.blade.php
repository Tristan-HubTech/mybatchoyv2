@extends('layouts.app')

@section('title', 'Application Health')

@section('content')
<x-page-header
    section="System Operations"
    breadcrumb="Health"
    title="Application Health &amp; Diagnostics"
    subtitle="Inspect database connectivity, PHP runtime extensions, storage permissions, and scrubbed application logs.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-light">&larr; Technical Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <x-kpi-card
            label="Database Probe"
            :value="$dbConnected ? 'Online' : 'Offline'"
            :meta="$dbLatencyMs . ' ms query latency'"
            :metaTone="$dbConnected ? 'up' : 'danger'"
            :tone="$dbConnected ? 'green' : 'red'" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Log Stream"
            :value="$logChannel === 'stderr' ? 'Active (stderr)' : ($storageWritable ? 'Writable' : 'Blocked')"
            :meta="$logChannel === 'stderr' ? 'Vercel / stream logs' : 'storage/logs'"
            :metaTone="$storageWritable ? 'up' : 'danger'"
            :tone="$storageWritable ? 'green' : 'red'" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Cache Backend"
            :value="in_array($cacheDriver, ['database', 'redis'], true) ? 'Active (' . $cacheDriver . ')' : ($cacheWritable ? 'Writable' : 'Blocked')"
            :meta="in_array($cacheDriver, ['database', 'redis'], true) ? 'Persistent cache store' : 'storage/framework/cache'"
            :metaTone="$cacheWritable ? 'up' : 'danger'"
            :tone="$cacheWritable ? 'blue' : 'red'" />
    </div>
    <div class="col">
        <x-kpi-card
            label="Log Retention Policy"
            :value="$techSettings->log_retention_days . ' days'"
            meta="Backup retention: {{ $techSettings->backup_retention_days }}d"
            metaTone="neutral"
            tone="amber" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Required PHP Extensions</h2>
            </div>
            <div class="eb-panel-body">
                <div class="d-flex flex-column gap-2">
                    @foreach ($extStatus as $ext => $loaded)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: #242424 !important;">
                            <span class="font-monospace small text-white">{{ $ext }}</span>
                            <x-status-badge :variant="$loaded ? 'success' : 'danger'" :label="$loaded ? 'Loaded' : 'Missing'" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Recent Application Log Tail (Secret-Scrubbed)</h2>
            </div>
            <div class="eb-panel-body">
                @if (empty($logTail))
                    <x-empty-state title="No log entries available" description="No recent laravel.log entries found in storage/logs." />
                @else
                    <pre class="p-3 rounded-3 mb-0 small font-monospace" style="background: #0A0A0A; border: 1px solid #2A2A2A; color: #E0E0E0; max-height: 420px; overflow: auto;">@foreach($logTail as $line){{ $line }}
@endforeach</pre>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

