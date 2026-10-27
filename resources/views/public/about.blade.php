@extends('layouts.app')

@section('title', 'About')

@section('content')
<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase small fw-bold mb-3" style="color: var(--primary-soft); letter-spacing: 0.1em;">Our Story</p>
                <h1 class="fw-bold mb-3" style="line-height: 1.2;">A Small Business with a Big Love for Batchoy</h1>
                <p style="color: var(--muted); line-height: 1.7;">MyBatchoy is a small, family-run food business operated from a van. We serve batchoy, sisig, nilat-an, siomai, dumplings, lumpia, and other affordable Filipino favorites to our customers — straight from the van's serving counter.</p>
                <p style="color: var(--muted); line-height: 1.7;">Our setup is simple: a white van converted into a food preparation space, folding tables, portable chairs, and warm lighting under the evening sky. Customers come for a satisfying meal at affordable prices, and keep coming back for the welcoming atmosphere.</p>
            </div>
            <div class="col-lg-6">
                <div class="card p-0 overflow-hidden" style="border-radius: 20px; border: 2px solid var(--border);">
                    <img src="{{ asset('images/mybatchoy-van-setup.jpg') }}"
                         alt="MyBatchoy van setup with outdoor seating at night"
                         class="w-100"
                         style="height: 420px; object-fit: cover; object-position: center; display: block;"
                         loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                        <svg width="18" height="18" fill="var(--warning)" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    </div>
                    <h5 class="fw-bold">Local identity</h5>
                    <p class="mb-0" style="color: var(--muted);">Rooted in the local street food scene, serving familiar Filipino comfort dishes to the neighborhood.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                        <svg width="18" height="18" fill="var(--success)" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                    </div>
                    <h5 class="fw-bold">Warm food, honest prices</h5>
                    <p class="mb-0" style="color: var(--muted);">Everything on the menu is ₱50 or less. Serving comforting bowl dishes and snacks that fit any budget.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(111, 168, 255, 0.1); border: 1px solid rgba(111, 168, 255, 0.2);">
                        <svg width="18" height="18" fill="var(--primary-soft)" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                    </div>
                    <h5 class="fw-bold">Customer-first</h5>
                    <p class="mb-0" style="color: var(--muted);">Built around convenience, accessibility, and the simple joy of a good meal shared outdoors.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="card p-0 overflow-hidden" style="border-radius: 20px; border: 2px solid var(--border);">
            <div class="row g-0 align-items-center">
                <div class="col-lg-5">
                    <img src="{{ asset('images/mybatchoy-customers.jpg') }}"
                         alt="Customers enjoying food at MyBatchoy outdoor seating"
                         class="w-100"
                         style="height: 340px; object-fit: cover; object-position: center 40%; display: block;"
                         loading="lazy">
                </div>
                <div class="col-lg-7 p-4 p-lg-5">
                    <h3 class="fw-bold mb-3">Come as you are</h3>
                    <p class="mb-3" style="color: var(--muted); line-height: 1.7;">No reservations, no dress code, no formalities. Just pull up a chair, order a bowl, and enjoy the evening. That's the MyBatchoy way.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('menu') }}" class="btn btn-primary px-4">See Our Menu</a>
                        <a href="{{ route('contact') }}" class="btn btn-outline-primary px-4">Find Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
