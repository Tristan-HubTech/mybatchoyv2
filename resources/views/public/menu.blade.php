@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <p class="text-uppercase small fw-bold accent mb-2">MyBatchoy</p>
            <h1 class="display-5 fw-bold mb-3">Food Menu</h1>
            <p class="lead muted mx-auto" style="max-width: 600px;">Affordable Filipino food served fresh. All prices as listed — no hidden charges.</p>
        </div>

        <!-- Main Bowls -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="brand-mark">01</span>
                <h3 class="fw-bold mb-0">Main Bowls</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge badge-warning-subtle mb-2">Best Seller</span>
                                <h5 class="fw-bold mb-0">Batchoy</h5>
                            </div>
                            <span class="fs-4 fw-bold accent">₱50</span>
                        </div>
                        <p class="muted small mb-0">Our signature batchoy bowl — hearty and satisfying.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold mb-0">Sisig</h5>
                            <span class="fs-4 fw-bold accent">₱50</span>
                        </div>
                        <p class="muted small mb-0">Sizzling sisig, a classic Filipino favorite.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold mb-0">Nilat-an</h5>
                            <span class="fs-4 fw-bold accent">₱50</span>
                        </div>
                        <p class="muted small mb-0">Hearty boiled beef soup, Ilonggo style.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Snacks & Sides -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="brand-mark">02</span>
                <h3 class="fw-bold mb-0">Snacks & Sides</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Japanese Siomai</strong>
                                <div class="muted small">Per piece</div>
                            </div>
                            <span class="fw-bold accent">₱10</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Siomai</strong>
                                <div class="muted small">3 pieces</div>
                            </div>
                            <span class="fw-bold accent">₱20</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Dumplings</strong>
                            </div>
                            <span class="fw-bold accent">₱15</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Lumpia</strong>
                                <div class="muted small">3 pieces</div>
                            </div>
                            <span class="fw-bold accent">₱20</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Spam</strong>
                            </div>
                            <span class="fw-bold accent">₱20</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rice & Extras -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="brand-mark">03</span>
                <h3 class="fw-bold mb-0">Rice & Extras</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Java Rice</strong>
                            <span class="fw-bold accent">₱20</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Chao Fan</strong>
                            <span class="fw-bold accent">₱20</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Plain Rice</strong>
                            <span class="fw-bold accent">₱15</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Poso</strong>
                            <span class="fw-bold accent">₱5</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drinks -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="brand-mark">04</span>
                <h3 class="fw-bold mb-0">Drinks</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Softdrinks</strong>
                            <span class="fw-bold accent">₱15</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Bottled Water</strong>
                            <span class="fw-bold accent">₱15</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('contact') }}" class="btn btn-primary px-4 py-2">Contact Us</a>
        </div>
    </div>
</section>
@endsection
