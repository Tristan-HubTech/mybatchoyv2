@extends('layouts.app')

@section('title', 'Session Expired — MyBatchoy')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="eb-auth-card text-center">
                <div class="eb-auth-body py-5">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 64px; height: 64px; background: rgba(229, 57, 53, 0.14); color: #E53935; border: 1px solid rgba(229, 57, 53, 0.45); font-size: 1.75rem;">
                        <span aria-hidden="true">&#9203;</span>
                    </div>
                    <span class="eb-section-pill mb-2">419 &bull; Page Timed Out</span>
                    <h1 class="h3 fw-bold text-white mb-2">Page Timed Out</h1>
                    <p class="small mb-4" style="color: #BDBDBD; font-size: 0.98rem;">
                        For your security, this page expired because it stayed open too long.
                        Go back, refresh the page, and try submitting again.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('login') }}" class="btn btn-primary btn-lg">
                            Refresh &amp; Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
