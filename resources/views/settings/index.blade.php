@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <p class="text-uppercase small fw-bold accent mb-1">Management</p>
        <h1 class="fw-bold mb-0">Settings</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('owner.settings.update') }}" class="card p-4 mb-4">
        @csrf
        @method('PUT')
        <h2 class="h5 fw-bold mb-3">Business information</h2>
        <div class="row g-3">
            <div class="col-md-6"><label for="business-name" class="form-label">Business name</label><input id="business-name" name="business_name" class="form-control" value="{{ old('business_name', $settings->business_name) }}" required maxlength="150"></div>
            <div class="col-md-6"><label for="business-phone" class="form-label">Phone</label><input id="business-phone" name="phone" class="form-control" value="{{ old('phone', $settings->phone) }}" maxlength="32"></div>
            <div class="col-md-6"><label for="business-email" class="form-label">Email</label><input id="business-email" name="email" type="email" class="form-control" value="{{ old('email', $settings->email) }}" maxlength="255"></div>
            <div class="col-md-6"><label for="business-address" class="form-label">Address</label><textarea id="business-address" name="address" class="form-control" rows="2" maxlength="1000">{{ old('address', $settings->address) }}</textarea></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary" type="submit">Save business information</button></div>
    </form>

    <section class="card p-4" aria-labelledby="security-settings-title">
        <h2 id="security-settings-title" class="h5 fw-bold mb-3">Security configuration</h2>
        <dl class="row mb-0">
            <dt class="col-sm-4 muted">Idle session timeout</dt>
            <dd class="col-sm-8">{{ $sessionLifetime }} minutes, configured through the server environment.</dd>
            <dt class="col-sm-4 muted">Owner MFA</dt>
            <dd class="col-sm-8">MFA enrollment and challenge are not implemented; the existing account flag does not enforce MFA.</dd>
            <dt class="col-sm-4 muted">Backup and recovery</dt>
            <dd class="col-sm-8">In-app backup and restore are not enabled. Use a verified MySQL backup process outside the application.</dd>
        </dl>
    </section>
</div>
@endsection