@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php
    $isOwnerUser = auth()->user()?->role?->name === 'owner';
    $dashRoute = $isOwnerUser ? route('owner.dashboard') : route('staff.dashboard');
@endphp

<x-page-header
    section="Account"
    title="My Profile"
    subtitle="Update your name, contact details, and password. Your role and username stay protected.">
    <x-slot:actions>
        <a href="{{ $dashRoute }}" class="btn btn-outline-light">&larr; Back to Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-xl-4">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>Account Access</span>
                </div>
                <x-status-badge
                    :variant="$user->is_active ? 'success' : 'neutral'"
                    :label="$user->is_active ? 'Active' : 'Inactive'" />
            </div>
            <div class="eb-panel-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 mb-3" style="color: #BDBDBD;">Role</dt>
                    <dd class="col-7 mb-3 fw-bold text-white">{{ $user->role?->label ?? 'Unassigned' }}</dd>

                    <dt class="col-5 mb-3" style="color: #BDBDBD;">Username</dt>
                    <dd class="col-7 mb-3 font-monospace text-warning">{{ $user->username }}</dd>

                    <dt class="col-5 mb-0" style="color: #BDBDBD;">Account Status</dt>
                    <dd class="col-7 mb-0 text-white">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="eb-panel-card mb-4">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>Personal Information</span>
                </div>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('profile.update') }}" data-safe-submit>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profile-name" class="form-label">Full name <span class="text-danger">*</span></label>
                            <input id="profile-name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="profile-email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input id="profile-email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="profile-phone" class="form-label">Phone</label>
                            <input id="profile-phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="e.g., 0917 123 4567" maxlength="32">
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top" style="border-color: #2A2A2A !important;">
                        <button class="btn btn-primary px-4" type="submit" data-loading-label="Saving profile...">Save profile</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>Change Password</span>
                </div>
                <x-status-badge variant="warning" label="Min 12 chars" />
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('profile.password') }}" data-password-form data-safe-submit>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current-password" class="form-label">Current password <span class="text-danger">*</span></label>
                            <input id="current-password" name="current_password" type="password" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="col-md-4">
                            <label for="new-password" class="form-label">New password <span class="text-danger">*</span></label>
                            <input id="new-password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password" aria-describedby="password-policy">
                        </div>
                        <div class="col-md-4">
                            <label for="new-password-confirmation" class="form-label">Confirm new password <span class="text-danger">*</span></label>
                            <input id="new-password-confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                        </div>
                    </div>
                    <div id="password-policy" class="form-text mt-2">
                        Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol. Your new password must differ from your current one.
                    </div>
                    <div class="mt-4 pt-3 border-top" style="border-color: #2A2A2A !important;">
                        <button class="btn btn-warning px-4" type="submit" data-loading-label="Updating password...">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
