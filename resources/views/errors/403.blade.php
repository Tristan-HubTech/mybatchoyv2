@extends('layouts.app')

@section('title', 'Access Restricted — MyBatchoy')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="eb-auth-card text-center">
                <div class="eb-auth-body py-5">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 64px; height: 64px; background: rgba(255, 193, 7, 0.14); color: #FFC107; border: 1px solid rgba(255, 193, 7, 0.4); font-size: 1.75rem;">
                        <span aria-hidden="true">&#128274;</span>
                    </div>
                    <span class="eb-section-pill mb-2">403 &bull; Access Restricted</span>
                    <h1 class="h3 fw-bold text-white mb-2">Access Restricted</h1>
                    <p class="small mb-4" style="color: #BDBDBD; font-size: 0.98rem;">
                        Your account role does not have permission to open this page or perform this action.
                        Contact your administrator or business owner if you believe this is unexpected.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @auth
                            <a href="{{ route(auth()->user()->dashboardRouteName()) }}"
                               class="btn btn-primary btn-lg">
                                &larr; Back to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">&larr; Go to Sign In</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
