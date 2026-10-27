@extends('layouts.app')

@section('title', 'Staff & Management Login')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 shadow">
                <div class="text-center mb-4">
                    <div class="brand-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.1rem; border-radius: 12px;">MB</div>
                    <h3 class="fw-bold mb-1">MyBatchoy</h3>
                    <p class="muted small mb-0">Internal Inventory Management System</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success py-2 small mb-3">
                        {{ session('success') }}
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

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username</label>
                        <input id="username" type="text" name="username" class="form-control form-control-sm" value="{{ old('username') }}" placeholder="Enter username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="small accent text-decoration-none" style="font-size: 0.8rem;">Forgot password?</a>
                        </div>
                        <input id="password" type="password" name="password" class="form-control form-control-sm" placeholder="Enter password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label small muted" for="remember">Remember this device</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
                </form>

                <div class="mt-4 pt-3 border-top" style="border-color: var(--border) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Default Credentials</span>
                    </div>
                    <div class="rounded-3 p-2 small" style="background: rgba(18, 30, 46, 0.6); border: 1px solid var(--border); font-size: 0.78rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="muted">Owner / Admin:</span>
                            <code>owneradmin</code> / <code>admin123</code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="muted">Staff Member:</span>
                            <code>staffuser</code> / <code>staff123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
