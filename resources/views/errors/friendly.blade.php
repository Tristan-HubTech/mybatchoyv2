@extends('layouts.app')

@section('title', 'Something Went Wrong — MyBatchoy')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="eb-auth-card text-center">
                <div class="eb-auth-body py-5">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 64px; height: 64px; background: rgba(229, 57, 53, 0.14); color: #E53935; border: 1px solid rgba(229, 57, 53, 0.45); font-size: 1.75rem;">
                        <span aria-hidden="true">&#9888;</span>
                    </div>
                    <span class="eb-section-pill mb-2">Notice</span>
                    <h1 class="h3 fw-bold text-white mb-2">Something Went Wrong</h1>
                    <p class="small mb-4" style="color: #BDBDBD; font-size: 0.98rem;">
                        {{ $friendlyMessage ?? "We couldn't save your changes. Please try again or contact the system administrator." }}
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @auth
                            <a href="{{ auth()->user()?->role?->name === 'owner' ? route('owner.dashboard') : route('staff.dashboard') }}"
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
