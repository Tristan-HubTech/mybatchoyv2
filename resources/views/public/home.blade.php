@extends('layouts.app')

@section('title', 'Home')

@section('content')
{{-- Hero Section --}}
<section class="py-5" style="position: relative; overflow: hidden;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <p class="text-uppercase small fw-bold mb-3" style="color: var(--primary-soft); letter-spacing: 0.1em;">Family-Run &bull; Affordable Food &bull; Served Fresh</p>
                <h1 class="display-4 fw-bold mb-3" style="line-height: 1.15;">Your Favorite Batchoy, Straight from Our Van.</h1>
                <p class="lead mb-4" style="color: var(--muted); line-height: 1.7;">Enjoy comforting batchoy and affordable Filipino food favorites from MyBatchoy. We serve satisfying meals from our food van in a simple, welcoming atmosphere.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('menu') }}" class="btn btn-primary px-4 py-2 fw-semibold" id="hero-explore-menu">Explore Our Menu</a>
                    <a href="{{ route('contact') }}" class="btn btn-outline-primary px-4 py-2" id="hero-contact-us">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2">
                <div style="position: relative;">
                    <div class="card p-0 overflow-hidden" style="border-radius: 20px; border: 2px solid var(--border);">
                        <img src="{{ asset('images/mybatchoy-customers.jpg') }}"
                             alt="MyBatchoy van with customers enjoying batchoy at outdoor tables"
                             class="w-100"
                             style="height: 440px; object-fit: cover; object-position: center 40%; display: block;"
                             loading="eager">
                    </div>
                    {{-- Floating badge --}}
                    <div style="position: absolute; bottom: -16px; left: 24px; z-index: 2;">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold" style="background: var(--primary); color: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(31, 106, 225, 0.35); font-size: 0.85rem;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.612 15.443c-.396.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.35.79-.746.592L8 13.187l-4.389 2.256z"/></svg>
                            Serving since Day One
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Feature Cards --}}
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                        <svg width="22" height="22" fill="var(--warning)" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                    </div>
                    <h5 class="fw-bold mb-2">Comforting Batchoy</h5>
                    <p class="mb-0" style="color: var(--muted);">Enjoy a satisfying bowl of batchoy and familiar Filipino food favorites.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                        <svg width="22" height="22" fill="var(--success)" viewBox="0 0 16 16" aria-hidden="true"><path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm0 1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1zm6 2a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1zm0 3a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1z"/></svg>
                    </div>
                    <h5 class="fw-bold mb-2">Affordable Food Choices</h5>
                    <p class="mb-0" style="color: var(--muted);">Explore our selection of batchoy, rice, sisig, siomai, dumplings, drinks, and more.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4" style="transition: transform 0.2s ease, border-color 0.2s ease;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(111, 168, 255, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
                    <div class="mb-3" style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(111, 168, 255, 0.1); border: 1px solid rgba(111, 168, 255, 0.2);">
                        <svg width="22" height="22" fill="var(--primary-soft)" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    </div>
                    <h5 class="fw-bold mb-2">Our Van-Based Setup</h5>
                    <p class="mb-0" style="color: var(--muted);">A small food business with a serving counter and an outdoor setup when available.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Our Setup Section --}}
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="card p-0 overflow-hidden" style="border-radius: 20px; border: 2px solid var(--border);">
                    <img src="{{ asset('images/mybatchoy-van-setup.jpg') }}"
                         alt="MyBatchoy food van and outdoor seating area at night"
                         class="w-100"
                         style="height: 420px; object-fit: cover; object-position: center; display: block;"
                         loading="lazy">
                </div>
            </div>
            <div class="col-lg-6">
                <p class="text-uppercase small fw-bold mb-3" style="color: var(--primary-soft); letter-spacing: 0.1em;">Our Setup</p>
                <h2 class="fw-bold mb-3" style="line-height: 1.2;">A Small Business with a Big Love for Batchoy</h2>
                <p style="color: var(--muted); line-height: 1.7;">MyBatchoy serves batchoy and other food favorites from a converted van, with a simple outdoor setup that welcomes customers looking for an affordable, satisfying meal.</p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(111, 168, 255, 0.1); border: 1px solid rgba(111, 168, 255, 0.2);">
                            <svg width="16" height="16" fill="var(--primary-soft)" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        </div>
                        <div>
                            <strong>Van-based food service</strong>
                            <div style="color: var(--muted); font-size: 0.88rem;">A white van converted into a food preparation and serving space</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                            <svg width="16" height="16" fill="var(--success)" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        </div>
                        <div>
                            <strong>Outdoor seating area</strong>
                            <div style="color: var(--muted); font-size: 0.88rem;">Portable folding chairs and tables for casual dining</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                            <svg width="16" height="16" fill="var(--warning)" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        </div>
                        <div>
                            <strong>Nighttime operation</strong>
                            <div style="color: var(--muted); font-size: 0.88rem;">Warm-lit serving counter under evening skies</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Menu Preview --}}
<section class="py-5" id="menu-preview">
    <div class="container">
        <div class="text-center mb-4">
            <p class="text-uppercase small fw-bold mb-2" style="color: var(--primary-soft); letter-spacing: 0.1em;">What We Serve</p>
            <h2 class="fw-bold mb-3">Our Menu</h2>
            <p style="color: var(--muted); max-width: 550px; margin: 0 auto;">Affordable Filipino food served fresh. All prices as listed — no hidden charges.</p>
        </div>

        {{-- Main Bowls --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Batchoy</strong>
                            <div style="color: var(--muted); font-size: 0.8rem;">Signature bowl</div>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft); font-size: 1.1rem;">₱50</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Sisig</strong>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft); font-size: 1.1rem;">₱50</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Nilat-an</strong>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft); font-size: 1.1rem;">₱50</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sides & Snacks --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Japanese Siomai</strong>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱10</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Siomai</strong>
                            <small style="color: var(--muted);">3 pcs</small>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱20</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Dumplings</strong>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱15</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Lumpia</strong>
                            <small style="color: var(--muted);">3 pcs</small>
                        </div>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱20</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- More Items --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Spam</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱20</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Java Rice</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱20</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Chao Fan</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱20</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Plain Rice</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱15</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Drinks & Extras --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Soft Drinks</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱15</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Bottled Water</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱15</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="font-size: 0.9rem;">Poso</strong>
                        <span class="fw-bold" style="color: var(--primary-soft);">₱5</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('menu') }}" class="btn btn-outline-primary px-4 py-2" id="menu-view-full">View Full Menu</a>
        </div>
    </div>
</section>

{{-- Location / Visit Section --}}
<section class="py-5">
    <div class="container">
        <div class="card p-4 p-md-5" style="border: 1px solid var(--border);">
            <div class="row g-4 align-items-center">
                <div class="col-md-7">
                    <p class="text-uppercase small fw-bold mb-2" style="color: var(--primary-soft); letter-spacing: 0.1em;">Visit Us</p>
                    <h3 class="fw-bold mb-3">Come find MyBatchoy</h3>
                    <p class="mb-2" style="color: var(--muted);"><strong style="color: var(--text);">Where:</strong> {{ $settings->address ?? 'Iloilo City, Western Visayas, Philippines' }}</p>
                    <p class="mb-2" style="color: var(--muted);"><strong style="color: var(--text);">When:</strong> Evenings — look for the van with the lit-up serving counter</p>
                    <p class="mb-0" style="color: var(--muted);"><strong style="color: var(--text);">Contact:</strong> {{ $settings->phone ?? '+63 917 123 4567' }}</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <a href="{{ route('contact') }}" class="btn btn-primary px-4 py-2" id="visit-contact">Get in Touch</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
