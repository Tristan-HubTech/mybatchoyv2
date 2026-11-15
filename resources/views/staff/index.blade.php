@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')
<div id="staffSkeletonWrapper" class="d-none">
    @include('components.skeletons.staff-skeleton')
</div>

<div id="staffLiveContent">
    <x-page-header
        section="Administration"
        title="Staff Management"
        subtitle="Review staff access requests, add team members, and enable or disable account access.">
        <x-slot:actions>
            <a href="{{ route('owner.staff.create') }}" class="btn btn-primary">+ Add Staff Account</a>
        </x-slot:actions>
    </x-page-header>

    <x-alert-banner />

    @if (isset($pendingRequests) && $pendingRequests->isNotEmpty())
        <x-table-card class="mb-4" style="border-color: rgba(255, 193, 7, 0.5);">
            <x-slot:icon>
                <span class="eb-badge eb-badge-warning">Action Needed</span>
            </x-slot:icon>
            <x-slot:headerActions>
                <h2 id="pending-requests-title" class="h6 fw-bold mb-0 text-white">
                    Pending Staff Access Requests ({{ $pendingRequests->count() }})
                </h2>
            </x-slot:headerActions>

            <div class="table-responsive">
                <table class="table eb-table table-hover align-middle mb-0" aria-labelledby="pending-requests-title">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                            <th>Requested</th>
                            <th class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingRequests as $pending)
                            <tr>
                                <td class="fw-bold text-white">{{ $pending->name }}</td>
                                <td class="font-monospace small" style="color: #BDBDBD;">{{ $pending->username }}</td>
                                <td class="small text-white">{{ $pending->email }}</td>
                                <td class="small" style="color: #BDBDBD;">{{ $pending->phone ?: '—' }}</td>
                                <td class="small" style="color: #BDBDBD;">{{ $pending->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <form method="POST"
                                              action="{{ route('owner.staff.approve', $pending) }}"
                                              data-safe-submit
                                              data-confirm-title="Approve Staff Request?"
                                              data-confirm-message="Allow {{ $pending->name }} ({{ $pending->username }}) to sign in as a Staff Member?"
                                              data-confirm-button="Yes, Approve"
                                              data-confirm-variant="btn-success">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" data-loading-label="Approving...">Approve</button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('owner.staff.reject', $pending) }}"
                                              data-safe-submit
                                              data-confirm-title="Reject Access Request?"
                                              data-confirm-message="Reject and remove the staff access request from {{ $pending->name }}? They will need to submit a new request later if needed."
                                              data-confirm-button="Yes, Reject"
                                              data-confirm-variant="btn-danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-label="Rejecting...">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-table-card>
    @endif

    <div class="eb-filter-card">
        <form method="GET" action="{{ route('owner.staff.index') }}" id="staffFilterForm" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="staff-search">Search Staff Member</label>
                <input id="staff-search" name="search" value="{{ request('search') }}" class="form-control" type="search" placeholder="Name, username, or email...">
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label" for="staff-status">Account Status</label>
                <select id="staff-status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Filter</button>
                @if (request()->filled('search') || request()->filled('status'))
                    <a class="btn btn-outline-light" href="{{ route('owner.staff.index') }}" aria-label="Clear filters" title="Clear filters">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Staff Accounts">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $staff->total() }} {{ Str::plural('account', $staff->total()) }}</span>
        </x-slot:headerActions>

        @if ($staff->isEmpty())
            <x-empty-state
                title="No staff accounts found"
                description="Add a staff account so kitchen or stock personnel can record inventory updates."
                :actionUrl="request()->filled('search') || request()->filled('status') ? route('owner.staff.index') : route('owner.staff.create')"
                :actionLabel="request()->filled('search') || request()->filled('status') ? 'Clear Filters' : '+ Add Staff Account'" />
        @else
            <div class="table-responsive">
                <table class="table eb-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staff as $member)
                            <tr>
                                <td class="fw-bold text-white">{{ $member->name }}</td>
                                <td class="font-monospace small" style="color: #BDBDBD;">{{ $member->username }}</td>
                                <td class="small text-white">{{ $member->email }}</td>
                                <td>
                                    <x-status-badge
                                        :variant="$member->is_active ? 'success' : 'neutral'"
                                        :label="$member->is_active ? 'Active' : 'Inactive'" />
                                </td>
                                <td class="small" style="color: #BDBDBD;">{{ $member->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <a href="{{ route('owner.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST"
                                              action="{{ route('owner.staff.status', $member) }}"
                                              data-safe-submit
                                              data-confirm-title="{{ $member->is_active ? 'Disable Staff Account?' : 'Enable Staff Account?' }}"
                                              data-confirm-message="{{ $member->is_active ? 'Warning: Disabling ' . $member->name . ' will immediately sign them out and block access until you enable the account again.' : 'Allow ' . $member->name . ' to sign in and use MyBatchoy again?' }}"
                                              data-confirm-button="{{ $member->is_active ? 'Yes, Disable Account' : 'Yes, Enable Account' }}"
                                              data-confirm-variant="{{ $member->is_active ? 'btn-danger' : 'btn-primary' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                            <button class="btn btn-sm {{ $member->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit" data-loading-label="{{ $member->is_active ? 'Disabling...' : 'Enabling...' }}">
                                                {{ $member->is_active ? 'Disable Account' : 'Enable Account' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $staff->firstItem() ?? 0 }}&ndash;{{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} accounts
                </div>
                <div>
                    {{ $staff->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('staffFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            const skeleton = document.getElementById('staffSkeletonWrapper');
            const live = document.getElementById('staffLiveContent');
            if (skeleton && live) {
                skeleton.classList.remove('d-none');
                live.style.opacity = '0.35';
                live.style.pointerEvents = 'none';
            }
        });
    }
});
</script>
@endsection
