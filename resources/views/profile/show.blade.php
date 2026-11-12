@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <p class="text-uppercase small fw-bold accent mb-1">Account</p>
        <h1 class="fw-bold mb-0">Profile</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card p-4 mb-4">
        <h2 class="h5 fw-bold mb-3">Account access</h2>
        <dl class="row mb-0">
            <dt class="col-sm-3 muted">Role</dt><dd class="col-sm-9">{{ $user->role?->label ?? 'Unassigned' }}</dd>
            <dt class="col-sm-3 muted">Status</dt><dd class="col-sm-9">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd>
            <dt class="col-sm-3 muted">Username</dt><dd class="col-sm-9">{{ $user->username }}</dd>
        </dl>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="card p-4 mb-4">
        @csrf
        @method('PUT')
        <h2 class="h5 fw-bold mb-3">Personal information</h2>
        <div class="row g-3">
            <div class="col-md-6"><label for="profile-name" class="form-label">Full name</label><input id="profile-name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="255"></div>
            <div class="col-md-6"><label for="profile-email" class="form-label">Email</label><input id="profile-email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required maxlength="255"></div>
            <div class="col-md-6"><label for="profile-phone" class="form-label">Phone</label><input id="profile-phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" maxlength="32"></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary" type="submit">Save profile</button></div>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="card p-4" data-password-form>
        @csrf
        @method('PUT')
        <h2 class="h5 fw-bold mb-3">Change password</h2>
        <div class="row g-3">
            <div class="col-md-4"><label for="current-password" class="form-label">Current password</label><input id="current-password" name="current_password" type="password" class="form-control" required autocomplete="current-password"></div>
            <div class="col-md-4"><label for="new-password" class="form-label">New password</label><input id="new-password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password" aria-describedby="password-policy"></div>
            <div class="col-md-4"><label for="new-password-confirmation" class="form-label">Confirm new password</label><input id="new-password-confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
        </div>
        <small id="password-policy" class="muted mt-2">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol. Your new password must differ from your current one.</small>
        <div class="mt-4"><button class="btn btn-outline-primary" type="submit" data-loading-label="Updating password...">Change password</button></div>
    </form>
</div>
<script>
    document.querySelectorAll('[data-password-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');
            if (button && form.reportValidity()) {
                button.disabled = true;
                button.textContent = button.dataset.loadingLabel || 'Updating...';
            }
        });
    });
</script>
@endsection