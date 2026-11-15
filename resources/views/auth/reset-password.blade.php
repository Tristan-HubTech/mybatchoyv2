@extends('layouts.app')

@section('title', 'Enter Verification Code — MyBatchoy')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="eb-auth-card">
                <div class="eb-auth-header">
                    <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="Eustace Batchoy" class="eb-auth-logo">
                    <h1 class="h4 fw-bold text-white mb-1">Enter Verification Code</h1>
                    <p class="small mb-0" style="color: #BDBDBD;">Enter the 6-digit recovery code sent to your email and choose a strong new password.</p>
                </div>

                <div class="eb-auth-body">
                    <div class="eb-alert eb-alert-info small py-2 mb-3">
                        Verification codes expire after 15 minutes. After resetting your password, previous active sessions will be signed out.
                    </div>

                    <x-alert-banner />

                    <form method="POST" action="{{ route('password.update') }}" data-safe-submit>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $email) }}" placeholder="name@mybatchoy.com" required>
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label">6-Digit Verification Code</label>
                            <input id="code" type="text" name="code" class="form-control text-center fw-bold text-uppercase" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="••••••" style="letter-spacing: 0.3em; font-size: 1.15rem;" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input id="password" type="password" name="password" class="form-control" placeholder="At least 12 characters with mixed case, number, symbol" required minlength="12" autocomplete="new-password">
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" required minlength="12" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" data-loading-label="Updating Password...">
                            Verify Code &amp; Update Password
                        </button>
                    </form>
                </div>

                <div class="eb-auth-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <a href="{{ route('password.request') }}" class="text-decoration-none" style="color: #BDBDBD;">Request a new code</a>
                    <a href="{{ route('login') }}" class="text-warning text-decoration-none fw-semibold">&larr; Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
