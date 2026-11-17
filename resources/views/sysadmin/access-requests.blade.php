@extends('layouts.app')

@section('title', 'Exceptional Business Access Requests')

@section('content')
<x-page-header
    section="System Operations"
    breadcrumb="Access Requests"
    title="Exceptional Business Data Access"
    subtitle="Request temporary, read-only troubleshooting access to specific business modules. All requests require Owner approval and auto-expire.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-light">&larr; Technical Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Submit Troubleshooting Access Request</h2>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('sysadmin.access.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="accessScope">Target Business Module <span class="required-star">*</span></label>
                        <select id="accessScope" name="scope" class="form-select" required>
                            @foreach ($scopes as $scopeKey => $scopeLabel)
                                <option value="{{ $scopeKey }}" @selected(old('scope') === $scopeKey)>{{ $scopeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="accessDuration">Intended Duration (Minutes) <span class="required-star">*</span></label>
                        <input id="accessDuration" type="number" name="duration_minutes" min="15" max="240" class="form-control" value="{{ old('duration_minutes', 60) }}" required>
                        <small class="form-help">Scoped window between 15 and 240 minutes; revokes automatically upon expiry.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="accessReason">Troubleshooting Justification <span class="required-star">*</span></label>
                        <textarea id="accessReason" name="reason" rows="3" class="form-control" minlength="10" maxlength="1000" required placeholder="Describe the technical issue requiring scoped inspection...">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Request to Owner</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="Your Exceptional Access Requests">
            @if ($requests->isEmpty())
                <x-empty-state title="No access requests submitted yet" description="Submit a scoped troubleshooting request when read-only business inspection is required." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Scope</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Window</th>
                                <th class="text-end">Inspect</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $req)
                                @php
                                    $effective = $req->effectiveStatus();
                                    $variant = match ($effective) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'rejected', 'revoked' => 'danger',
                                        default => 'neutral',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-white" data-label="Scope">{{ $req->scopeLabel() }}</td>
                                    <td class="small text-white" data-label="Reason">{{ Str::limit($req->reason, 80) }}</td>
                                    <td data-label="Status">
                                        <x-status-badge :variant="$variant" :label="ucfirst($effective)" />
                                    </td>
                                    <td class="small muted" data-label="Window">
                                        {{ $req->duration_minutes }}m
                                        @if ($req->expires_at)
                                            <div class="font-monospace" style="font-size: 0.72rem;">Exp: {{ $req->expires_at->format('M d, H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end eb-cell-actions">
                                        @if ($req->isCurrentlyActive())
                                            <a href="{{ route('sysadmin.access.view', $req) }}" class="btn btn-sm btn-warning text-dark fw-semibold">Open Read-Only View</a>
                                        @else
                                            <span class="small muted">Closed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-slot:footer>
                    <div class="small muted">Showing {{ $requests->firstItem() ?? 0 }}&ndash;{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }}</div>
                    <div>{{ $requests->links() }}</div>
                </x-slot:footer>
            @endif
        </x-table-card>
    </div>
</div>
@endsection

