@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 shadow">
                <div class="text-center mb-4">
                    <div class="brand-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.1rem; border-radius: 12px;">MB</div>
                    <h3 class="fw-bold mb-1">Reset Password</h3>
                    <p class="muted small mb-0">Internal Inventory Management System</p>
                </div>

                <div class="rounded-3 p-3 mb-3 small" style="background: rgba(31, 106, 225, 0.08); border: 1px solid rgba(111, 168, 255, 0.25);">
                    <div class="d-flex gap-2">
                        <span class="accent fw-bold" style="font-size: 1rem;">ℹ</span>
                        <div class="muted" style="font-size: 0.8rem; line-height: 1.4;">
                            Enter your registered username or email. We will generate a secure <strong>6-digit verification code</strong> (valid for 15 minutes).
                        </div>
                    </div>
                </div>

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

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="identifier" class="form-label small fw-semibold">Username or Email</label>
                        <input id="identifier" type="text" name="identifier" class="form-control form-control-sm" value="{{ old('identifier') }}" placeholder="e.g. owneradmin or owner@mybatchoy.com" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">Send Security Code</button>

                    <div class="text-center">
                        <a href="{{ route('login') }}" class="small muted text-decoration-none">
                            ← Back to Login
                        </a>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top text-center" style="border-color: var(--border) !important;">
                    <small class="muted" style="font-size: 0.72rem;">Protected by Rate Limiting & Cryptographic Hash Validation</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
