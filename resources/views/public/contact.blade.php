@extends('layouts.app')

@section('title', 'Contact')

@section('content')
<section class="py-5">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-6">
                <p class="text-uppercase small fw-bold accent mb-3">Find Us</p>
                <h1 class="fw-bold mb-3">Visit MyBatchoy</h1>
                <p class="muted mb-4">We're a van-based food setup — look for the white van with the MyBatchoy banner, folding tables, and lights. You can't miss us!</p>

                <div class="card p-4">
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <strong class="d-block mb-1">📍 Location</strong>
                            <span class="muted">{{ $settings->address ?? 'Iloilo City, Western Visayas, Philippines' }}</span>
                        </div>
                        <hr class="my-0" style="border-color: var(--border);">
                        <div>
                            <strong class="d-block mb-1">📞 Contact</strong>
                            <span class="muted">{{ $settings->phone ?? '+63 917 123 4567' }}</span>
                        </div>
                        <hr class="my-0" style="border-color: var(--border);">
                        <div>
                            <strong class="d-block mb-1">🕐 Hours</strong>
                            <span class="muted">Evenings — we set up at night</span>
                        </div>
                        <hr class="my-0" style="border-color: var(--border);">
                        <div>
                            <strong class="d-block mb-1">🍜 What to expect</strong>
                            <span class="muted">Street-side food truck, folding tables & chairs, open air dining. Come as you are!</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card p-4 shadow-soft">
                    <iframe class="w-100 rounded-4" src="https://www.google.com/maps?q=Iloilo+City+Philippines&output=embed" height="420" style="border:0;" allowfullscreen loading="lazy"></iframe>
                </div>
                <div class="card p-3 mt-3 text-center" style="background: rgba(31, 106, 225, 0.08); border-color: rgba(111, 168, 255, 0.25);">
                    <p class="mb-0 fw-semibold">🚐 Look for the white van with the Eustace Batchoy banner!</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
