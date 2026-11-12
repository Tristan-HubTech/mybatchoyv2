@extends('layouts.app')

@section('title', $staffMember ? 'Edit Staff' : 'Add Staff')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <p class="text-uppercase small fw-bold accent mb-1">Management</p>
        <h1 class="fw-bold mb-0">{{ $staffMember ? 'Edit staff account' : 'Add staff account' }}</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $staffMember ? route('owner.staff.update', $staffMember) : route('owner.staff.store') }}" class="card p-4" data-loading-form>
        @csrf
        @if ($staffMember) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label for="name" class="form-label">Full name</label><input id="name" name="name" class="form-control" value="{{ old('name', $staffMember?->name) }}" required maxlength="255"></div>
            <div class="col-md-6"><label for="username" class="form-label">Username</label><input id="username" name="username" class="form-control" value="{{ old('username', $staffMember?->username) }}" required maxlength="255" pattern="[A-Za-z0-9_-]+"></div>
            <div class="col-md-6"><label for="email" class="form-label">Email</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $staffMember?->email) }}" required maxlength="255"></div>
            <div class="col-md-6"><label for="phone" class="form-label">Phone</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $staffMember?->phone) }}" maxlength="32"></div>
            @unless ($staffMember)
                <div class="col-md-6">
                    <label for="is_active" class="form-label">Account status</label>
                    <select id="is_active" name="is_active" class="form-select" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                </div>
            @endunless
            @unless ($staffMember)
                <div class="col-md-6"><label for="password" class="form-label">Initial password</label><input id="password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password"><small class="muted">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</small></div>
                <div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
            @endunless
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit" data-loading-text="{{ $staffMember ? 'Saving...' : 'Creating account...' }}">{{ $staffMember ? 'Save changes' : 'Create staff account' }}</button>
            <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-primary">Cancel</a>
        </div>
    </form>

    @if ($staffMember)
        <form method="POST" action="{{ route('owner.staff.password', $staffMember) }}" class="card p-4 mt-4" data-loading-form>
            @csrf
            @method('PUT')
            <h2 class="h5 fw-bold">Reset password</h2>
            <p class="muted">The new password is stored as a hash and is never displayed after submission.</p>
            <div class="row g-3">
                <div class="col-md-6"><label for="reset-password" class="form-label">New password</label><input id="reset-password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
                <div class="col-md-6"><label for="reset-password-confirmation" class="form-label">Confirm new password</label><input id="reset-password-confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
            </div>
            <div class="mt-3"><button class="btn btn-outline-primary" type="submit" data-loading-text="Resetting password...">Reset password</button></div>
        </form>
    @endif
</div>
<script>
    document.querySelectorAll('[data-loading-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = button.dataset.loadingText || 'Saving...';
            }
        });
    });
</script>
@endsection