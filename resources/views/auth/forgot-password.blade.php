@extends('layouts.app')

@section('title', 'Forgot Password — MyBatchoy')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="eb-auth-card">
                <div class="eb-auth-header">
                    <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="Eustace Batchoy" class="eb-auth-logo">
                    <h1 class="h4 fw-bold text-white mb-1">Password Recovery</h1>
                    <p class="small mb-0" style="color: #BDBDBD;">Enter your registered email address to receive a verification code.</p>
                </div>

                <div class="eb-auth-body">
                    <div class="eb-alert eb-alert-info small py-2 mb-3">
                        Only active accounts with an existing email address can receive a recovery verification code.
                    </div>

                    <x-alert-banner />

                    <form method="POST" action="{{ route('password.email') }}" data-safe-submit>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" data-loading-label="Sending verification code...">
                            Send Verification Code
                        </button>
                    </form>
                </div>

                <div class="eb-auth-footer text-center">
                    <a href="{{ route('login') }}" class="text-warning text-decoration-none fw-semibold">&larr; Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
