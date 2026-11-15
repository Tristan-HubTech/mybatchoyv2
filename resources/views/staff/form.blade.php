@extends('layouts.app')

@section('title', isset($staffMember) && $staffMember !== null ? 'Edit Staff Account' : 'Add Staff Account')

@section('content')
@php
    $isEdit = isset($staffMember) && $staffMember !== null;
@endphp

<x-page-header
    section="Administration"
    breadcrumb="Staff Management"
    :title="$isEdit ? 'Edit Staff Account' : 'Add Staff Account'"
    :subtitle="$isEdit ? 'Update account details, activation status, or set a new password.' : 'Create a Staff Member account for kitchen and inventory tracking.'">
    <x-slot:actions>
        <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-light">&larr; Back to Staff List</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-xl-7">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>Account Details</span>
                </div>
                <span class="eb-badge eb-badge-neutral">Role: Staff Member</span>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ $isEdit ? route('owner.staff.update', $staffMember) : route('owner.staff.store') }}" data-safe-submit>
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                            <input id="name" name="name" class="form-control" value="{{ old('name', $staffMember?->name) }}" placeholder="e.g., Maria Santos" required maxlength="255">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input id="username" name="username" class="form-control" value="{{ old('username', $staffMember?->username) }}" placeholder="e.g., maria_kitchen" required maxlength="255" pattern="[A-Za-z0-9_-]+">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $staffMember?->email) }}" placeholder="e.g., maria@mybatchoy.com" required maxlength="255">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input id="phone" name="phone" class="form-control" value="{{ old('phone', $staffMember?->phone) }}" placeholder="Optional — e.g., 0917 123 4567" maxlength="32">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="is_active">Account Access <span class="text-danger">*</span></label>
                            <select id="is_active" name="is_active" class="form-select" required>
                                <option value="1" {{ old('is_active', $staffMember?->is_active ?? true) ? 'selected' : '' }}>Active (Can sign in)</option>
                                <option value="0" {{ !old('is_active', $staffMember?->is_active ?? true) ? 'selected' : '' }}>Disabled (Blocked from signing in)</option>
                            </select>
                        </div>

                        @if (!$isEdit)
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                <input id="password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                                <div class="form-text">Minimum 12 characters for security.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top" style="border-color: #2A2A2A !important;">
                        <button class="btn btn-primary btn-lg px-4" type="submit" data-loading-label="{{ $isEdit ? 'Saving Changes...' : 'Creating Account...' }}">
                            {{ $isEdit ? 'Save Account Changes' : 'Create Staff Account' }}
                        </button>
                        <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($isEdit)
        <div class="col-xl-5">
            <div class="eb-panel-card">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <span>Reset Staff Password</span>
                    </div>
                    <x-status-badge variant="warning" label="Min 12 chars" />
                </div>
                <div class="eb-panel-body">
                    <p class="small mb-3" style="color: #BDBDBD;">
                        Assign a new password for <strong class="text-white">{{ $staffMember->name }}</strong>. Active sessions should be signed in again afterward.
                    </p>
                    <form method="POST" action="{{ route('owner.staff.password', $staffMember) }}" data-safe-submit>
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="reset-password">New Password <span class="text-danger">*</span></label>
                            <input id="reset-password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="reset-password-confirmation">Confirm New Password <span class="text-danger">*</span></label>
                            <input id="reset-password-confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                        </div>
                        <button class="btn btn-warning w-100" type="submit" data-loading-label="Resetting Password...">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
