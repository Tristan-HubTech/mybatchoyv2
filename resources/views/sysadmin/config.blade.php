@extends('layouts.app')

@section('title', 'Technical Configuration')

@section('content')
<x-page-header
    section="System Operations"
    breadcrumb="Technical Config"
    title="Technical Application Configuration"
    subtitle="Manage system maintenance banner, retention policies, and delegated Owner technical permissions.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-light">&larr; Technical Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row">
    <div class="col-lg-8">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Protected Technical Parameters</h2>
                <x-status-badge variant="warning" label="Password Re-Verification Required" />
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('sysadmin.config.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $techSettings->maintenance_mode))>
                        <label class="form-check-label text-white fw-semibold" for="maintenanceMode">Enable Maintenance Mode Flag</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="maintenanceNotice">Maintenance Notice Message</label>
                        <input id="maintenanceNotice" type="text" name="maintenance_notice" class="form-control" value="{{ old('maintenance_notice', $techSettings->maintenance_notice) }}" placeholder="e.g., Scheduled database maintenance at 11:00 PM">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="logRetention">Log Retention (Days) <span class="required-star">*</span></label>
                            <input id="logRetention" type="number" name="log_retention_days" min="7" max="365" class="form-control" value="{{ old('log_retention_days', $techSettings->log_retention_days) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="backupRetention">Backup Retention (Days) <span class="required-star">*</span></label>
                            <input id="backupRetention" type="number" name="backup_retention_days" min="7" max="365" class="form-control" value="{{ old('backup_retention_days', $techSettings->backup_retention_days) }}" required>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="ownerCanEditTechnical" name="owner_can_edit_technical" value="1" @checked(old('owner_can_edit_technical', $techSettings->owner_can_edit_technical))>
                        <label class="form-check-label text-white" for="ownerCanEditTechnical">Authorize Business Owner to Edit Technical Settings (`PUT /technical-config`)</label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="cfgPassword">Confirm Your System Admin Password <span class="required-star">*</span></label>
                        <input id="cfgPassword" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Save Technical Configuration</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

