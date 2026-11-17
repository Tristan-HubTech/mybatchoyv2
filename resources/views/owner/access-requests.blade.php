@extends('layouts.app')

@section('title', 'System Admin Access Requests')

@section('content')
<x-page-header
    section="Administration"
    breadcrumb="Access Requests"
    title="Exceptional Business Access Authorizations"
    subtitle="Review, approve (with password verification), reject, or revoke temporary System Admin requests to inspect business records.">
    <x-slot:actions>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-light">&larr; Business Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<x-table-card title="System Admin Access Requests Queue">
    @if ($requests->isEmpty())
        <x-empty-state title="No access requests submitted" description="When a System Admin requests temporary business data inspection for troubleshooting, review it here." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Requested By</th>
                        <th>Scope &amp; Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Owner Decision</th>
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
                            <td data-label="Requested By">
                                <div class="fw-semibold text-white">{{ $req->systemAdmin?->name ?? 'System Admin' }}</div>
                                <div class="small font-monospace muted">{{ $req->systemAdmin?->username }}</div>
                            </td>
                            <td data-label="Scope">
                                <div class="fw-semibold text-warning">{{ $req->scopeLabel() }}</div>
                                <div class="small muted">{{ $req->duration_minutes }} minutes</div>
                            </td>
                            <td class="small text-white" data-label="Reason">
                                {{ $req->reason }}
                                @if ($req->review_notes)
                                    <div class="small muted mt-1">Owner note: {{ $req->review_notes }}</div>
                                @endif
                            </td>
                            <td data-label="Status">
                                <x-status-badge :variant="$variant" :label="ucfirst($effective)" />
                                @if ($req->expires_at)
                                    <div class="small muted mt-1 font-monospace">{{ $req->expires_at->format('M d, h:i A') }}</div>
                                @endif
                            </td>
                            <td class="text-end eb-cell-actions">
                                @if ($effective === 'pending')
                                    <div class="d-flex flex-column gap-2 align-items-end">
                                        <form method="POST" action="{{ route('owner.access.approve', $req) }}" class="d-flex flex-wrap gap-1 justify-content-end">
                                            @csrf
                                            <input type="text" name="review_notes" class="form-control form-control-sm" style="width: 140px;" placeholder="Approval note">
                                            <input type="password" name="current_password" class="form-control form-control-sm" style="width: 140px;" placeholder="Owner password" required>
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.access.reject', $req) }}" class="d-flex gap-1 justify-content-end">
                                            @csrf
                                            <input type="text" name="review_notes" class="form-control form-control-sm" style="width: 140px;" placeholder="Rejection reason">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </div>
                                @elseif ($req->isCurrentlyActive())
                                    <form method="POST" action="{{ route('owner.access.revoke', $req) }}" class="d-inline-flex gap-1 justify-content-end">
                                        @csrf
                                        <input type="password" name="current_password" class="form-control form-control-sm" style="width: 140px;" placeholder="Owner password" required>
                                        <button type="submit" class="btn btn-sm btn-danger">Revoke Access</button>
                                    </form>
                                @else
                                    <span class="small muted">Closed {{ $req->reviewed_at?->diffForHumans() }}</span>
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
@endsection

