@extends('layouts.app')

@section('title', 'Verify Code & Set Password')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 shadow">
                <div class="text-center mb-4">
                    <div class="brand-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.1rem; border-radius: 12px;">MB</div>
                    <h3 class="fw-bold mb-1">Enter Verification Code</h3>
                    <p class="muted small mb-0">Internal Inventory Management System</p>
                </div>

                @if (session('security_code_demo'))
                    <div class="rounded-3 p-3 mb-3 small" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.4); color: #c9f7d8;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <strong>Security Verification Code:</strong>
                            <span class="badge bg-success text-dark fw-bold px-2 py-1" style="font-size: 1rem; letter-spacing: 0.15em;">{{ session('security_code_demo') }}</span>
                        </div>
                        <div class="text-white-50" style="font-size: 0.75rem;">(Testing Mode: Generated securely & hashed with bcrypt in database)</div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-info py-2 small mb-3" style="background: rgba(31, 106, 225, 0.15); border-color: rgba(111, 168, 255, 0.3); color: #c9ddff;">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email Address</label>
                        <input id="email" type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $email) }}" placeholder="name@mybatchoy.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label small fw-semibold">6-Digit Security Code</label>
                        <input id="code" type="text" name="code" class="form-control form-control-sm text-center fw-bold text-uppercase" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="••••••" style="letter-spacing: 0.3em; font-size: 1.1rem;" required autofocus>
                        <small class="muted" style="font-size: 0.72rem;">Valid for 15 minutes. One-time use.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">New Password</label>
                        <input id="password" type="password" name="password" class="form-control form-control-sm" placeholder="At least 8 characters (letters & numbers)" required>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label small fw-semibold">Confirm New Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Repeat new password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">Reset Password</button>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('password.request') }}" class="small muted text-decoration-none">
                            Request new code
                        </a>
                        <a href="{{ route('login') }}" class="small accent text-decoration-none">
                            ← Return to Login
                        </a>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top text-center" style="border-color: var(--border) !important;">
                    <small class="muted" style="font-size: 0.72rem;">Security Policy: Min 8 chars, alphanumeric required</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
