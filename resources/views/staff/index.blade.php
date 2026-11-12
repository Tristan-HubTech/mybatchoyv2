@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')
<div class="container py-5">
    <div id="staffSkeletonWrapper" class="d-none">
        @include('components.skeletons.staff-skeleton')
    </div>
    <div id="staffLiveContent">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="text-uppercase small fw-bold accent mb-1">Management</p>
                <h1 class="fw-bold mb-0">Staff Management</h1>
            </div>
            <a href="{{ route('owner.staff.create') }}" class="btn btn-primary">Add staff</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('owner.staff.index') }}" class="card p-3 mb-4" id="staffFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label for="staff-search" class="form-label">Search staff</label>
                    <input id="staff-search" name="search" value="{{ request('search') }}" class="form-control" type="search" placeholder="Name, username, or email">
                </div>
                <div class="col-md-3">
                    <label for="staff-status" class="form-label">Status</label>
                    <select id="staff-status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>
                    <a class="btn btn-outline-primary" href="{{ route('owner.staff.index') }}" aria-label="Clear filters" title="Clear filters">Clear</a>
                </div>
            </div>
        </form>

        <div class="card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($staff as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->username }}</td>
                                <td>{{ $member->email }}</td>
                                <td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>{{ $member->created_at?->format('M d, Y') ?? '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('owner.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="{{ route('owner.staff.status', $member) }}" data-status-form data-confirm-message="Are you sure you want to {{ $member->is_active ? 'deactivate' : 'activate' }} {{ $member->name }}?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                            <button class="btn btn-sm btn-outline-primary" type="submit" data-loading-text="{{ $member->is_active ? 'Deactivating...' : 'Activating...' }}">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 muted">No staff accounts match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $staff->links() }}</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-status-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.dataset.confirmMessage)) {
                    event.preventDefault();
                    return;
                }

                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.textContent = button.dataset.loadingText || 'Saving...';
                }
            });
        });

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