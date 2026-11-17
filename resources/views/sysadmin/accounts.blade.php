@extends('layouts.app')

@section('title', 'System Admin Accounts')

@section('content')
<x-page-header
    section="System Operations"
    breadcrumb="Admin Accounts"
    title="Protected System Admin Management"
    subtitle="Create or deactivate technical System Admin accounts. Business Owner, Staff, and Supplier accounts remain strictly separate.">
    <x-slot:actions>
        <a href="{{ route('sysadmin.dashboard') }}" class="btn btn-outline-light">&larr; Technical Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Create System Admin Account</h2>
                <x-status-badge variant="warning" label="Password Required" />
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('sysadmin.accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="saName">Full Name <span class="required-star">*</span></label>
                        <input id="saName" type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="saUsername">Username <span class="required-star">*</span></label>
                        <input id="saUsername" type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="saEmail">Email Address <span class="required-star">*</span></label>
                        <input id="saEmail" type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="saPass">New Password <span class="required-star">*</span></label>
                            <input id="saPass" type="password" name="password" class="form-control" required autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="saPassConfirm">Confirm Password <span class="required-star">*</span></label>
                            <input id="saPassConfirm" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <small class="form-help">Minimum 12 chars with uppercase, lowercase, number, and symbol.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="saCurrentPass">Your Current System Admin Password <span class="required-star">*</span></label>
                        <input id="saCurrentPass" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Protected Admin Account</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="Existing System Admin Accounts" class="mb-4">
            @if ($sysAdmins->isEmpty())
                <x-empty-state title="No System Admin accounts found" description="Create technical admin accounts via this protected panel or php artisan app:create-system-admin." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sysAdmins as $admin)
                                <tr>
                                    <td class="fw-semibold text-white" data-label="Name">{{ $admin->name }}</td>
                                    <td class="font-monospace small text-warning" data-label="Username">{{ $admin->username }}</td>
                                    <td data-label="Status">
                                        <x-status-badge :variant="$admin->is_active ? 'success' : 'danger'" :label="$admin->is_active ? 'Active' : 'Inactive'" />
                                    </td>
                                    <td class="small muted" data-label="Last Login">{{ $admin->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="text-end eb-cell-actions">
                                        @if ((int) $admin->id === (int) auth()->id())
                                            <span class="small muted">Current Session</span>
                                        @else
                                            <form method="POST" action="{{ route('sysadmin.accounts.status', $admin) }}" class="d-inline-flex align-items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $admin->is_active ? 0 : 1 }}">
                                                <input type="password" name="current_password" class="form-control form-control-sm" style="width: 130px;" placeholder="Admin password" required>
                                                <button type="submit" class="btn btn-sm {{ $admin->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                    {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-table-card>

        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Permission Group Separation Matrix</h2>
            </div>
            <div class="eb-panel-body">
                <div class="row g-2">
                    @foreach ($permissionGroups as $groupKey => $meta)
                        <div class="col-md-6">
                            <div class="p-2 rounded-2" style="background: #141414; border: 1px solid #2A2A2A;">
                                <div class="small fw-bold text-white">{{ $meta['label'] ?? $groupKey }}</div>
                                <div class="small muted font-monospace">{{ implode(', ', $meta['roles'] ?? []) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

