@extends('layouts.app')

@section('title', 'Staff & Management Login')

@section('content')
@php
    $businessName = $businessSettings?->business_name ?: 'MyBatchoy';
    $logoUrl = $businessSettings?->resolveLogoUrl();
@endphp

<style>
    .login-backdrop {
        position: relative;
        width: 100%;
        flex: 1 1 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(1rem, 3vw, 2.25rem) clamp(0.85rem, 3vw, 1.5rem);
        isolation: isolate;
    }

    .login-backdrop::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -2;
        pointer-events: none;
        background:
            radial-gradient(680px circle at 14% 22%, rgba(229, 57, 53, 0.14), transparent 62%),
            radial-gradient(560px circle at 88% 82%, rgba(255, 193, 7, 0.08), transparent 62%),
            linear-gradient(180deg, #0d0d0d 0%, #111111 100%);
    }

    .login-backdrop::after {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        background-image:
            linear-gradient(to right, rgba(255, 255, 255, 0.028) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.028) 1px, transparent 1px);
        background-size: 40px 40px;
        mask-image: radial-gradient(ellipse 80% 75% at 50% 48%, #000 35%, transparent 100%);
        -webkit-mask-image: radial-gradient(ellipse 80% 75% at 50% 48%, #000 35%, transparent 100%);
    }

    .login-shell {
        width: min(100%, 1080px);
        margin-inline: auto;
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 440px);
        gap: clamp(1.75rem, 4vw, 3.25rem);
        align-items: center;
        justify-content: center;
    }

    .login-showcase {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        animation: login-enter 420ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
    }

    .login-live-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        align-self: flex-start;
        padding: 0.38rem 0.85rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e4e4e4;
        font-size: 0.73rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .login-live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4caf50;
        box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2);
    }

    .login-hero-title {
        font-size: clamp(1.85rem, 3.1vw, 2.55rem);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: -0.035em;
        color: #ffffff;
        margin: 0;
    }

    .login-hero-accent {
        background: linear-gradient(120deg, #ff6f61 0%, #ffc107 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .login-hero-copy {
        color: #b8b8b8;
        font-size: 0.96rem;
        line-height: 1.65;
        max-width: 50ch;
        margin: 0;
    }

    .login-bento {
        background: rgba(24, 24, 24, 0.86);
        border: 1px solid #2b2b2b;
        border-radius: 18px;
        padding: 1.25rem;
        box-shadow: 0 22px 60px rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(8px);
    }

    .login-bento-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.9rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #272727;
        font-size: 0.76rem;
        color: #a8a8a8;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 600;
    }

    .login-bento-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .login-module-tile {
        background: #141414;
        border: 1px solid #262626;
        border-radius: 12px;
        padding: 0.9rem;
        transition: border-color 180ms ease, transform 180ms ease;
    }

    .login-module-tile:hover {
        border-color: #3a3a3a;
        transform: translateY(-2px);
    }

    .login-module-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.65rem;
    }

    .login-module-icon--green {
        background: rgba(76, 175, 80, 0.14);
        color: #66bb6a;
        border: 1px solid rgba(76, 175, 80, 0.25);
    }

    .login-module-icon--amber {
        background: rgba(255, 193, 7, 0.14);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.28);
    }

    .login-module-icon--red {
        background: rgba(229, 57, 53, 0.14);
        color: #ef5350;
        border: 1px solid rgba(229, 57, 53, 0.28);
    }

    .login-module-title {
        color: #ffffff;
        font-size: 0.84rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }

    .login-module-desc {
        color: #9e9e9e;
        font-size: 0.75rem;
        line-height: 1.45;
        margin: 0;
    }

    .login-trust-strip {
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #262626;
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem 1.25rem;
        color: #b5b5b5;
        font-size: 0.78rem;
    }

    .login-trust-item {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .login-card {
        width: 100%;
        padding: clamp(1.5rem, 4vw, 2.25rem) !important;
        background: linear-gradient(180deg, #1b1b1b 0%, #161616 100%);
        border: 1px solid #2e2e2e;
        border-radius: 20px;
        box-shadow:
            0 28px 80px rgba(0, 0, 0, 0.68),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        animation: login-enter 380ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
    }

    .login-brand-mark {
        width: 62px;
        height: 62px;
        border-radius: 16px;
        border: 1px solid rgba(229, 57, 53, 0.42);
        background: #121212;
        box-shadow: 0 12px 28px rgba(229, 57, 53, 0.22);
        overflow: hidden;
        padding: 3px;
    }

    .login-brand-logo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }

    .login-heading {
        font-size: clamp(1.45rem, 3.8vw, 1.7rem);
        letter-spacing: -0.03em;
        color: #ffffff;
    }

    .login-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.28rem 0.72rem;
        border-radius: 999px;
        background: rgba(255, 193, 7, 0.11);
        border: 1px solid rgba(255, 193, 7, 0.3);
        color: var(--secondary);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .login-label {
        color: #ededed;
        font-size: 0.83rem;
        margin-bottom: 0.45rem;
    }

    .login-input-wrap {
        position: relative;
    }

    .login-input-icon {
        position: absolute;
        top: 50%;
        left: 0.9rem;
        transform: translateY(-50%);
        color: #7d7d7d;
        pointer-events: none;
        display: inline-flex;
        transition: color 180ms ease;
    }

    .login-input {
        min-height: 49px;
        padding: 0.74rem 0.95rem 0.74rem 2.65rem;
        border-radius: 11px;
        background: #121212;
        border: 1px solid #2f2f2f;
        color: #ffffff;
        font-size: 0.94rem;
        transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    }

    .login-input:hover:not(:focus) {
        border-color: #454545;
    }

    .login-input::placeholder {
        color: #787878;
        opacity: 1;
    }

    .login-input:focus {
        background: #171717;
        border-color: var(--secondary);
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.16);
    }

    .login-input-wrap:focus-within .login-input-icon {
        color: var(--secondary);
    }

    .login-input:-webkit-autofill,
    .login-input:-webkit-autofill:hover,
    .login-input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px #171717 inset !important;
        -webkit-text-fill-color: #ffffff !important;
        caret-color: #ffffff;
        border-color: #3f3f3f;
        transition: background-color 5000s ease-in-out 0s;
    }

    .login-input.is-invalid {
        border-color: var(--primary);
        background-image: none;
    }

    .login-password-wrap .login-input {
        padding-right: 3.35rem;
    }

    .password-visibility {
        position: absolute;
        top: 50%;
        right: 0.5rem;
        display: inline-flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #9e9e9e;
        transition: color 160ms ease, background-color 160ms ease;
    }

    .password-visibility:hover,
    .password-visibility:focus-visible {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        outline: 2px solid rgba(255, 193, 7, 0.7);
        outline-offset: 1px;
    }

    .password-visibility svg {
        width: 19px;
        height: 19px;
    }

    .login-link-yellow {
        color: var(--secondary);
        font-size: 0.81rem;
        font-weight: 500;
    }

    .login-link-yellow:hover,
    .login-link-yellow:focus-visible {
        color: #ffda64;
        text-decoration: underline !important;
        text-underline-offset: 3px;
    }

    .login-remember {
        min-height: 1.25rem;
        padding-left: 1.75rem;
    }

    .login-remember .form-check-input {
        width: 1.08rem;
        height: 1.08rem;
        margin-left: -1.75rem;
        background-color: #121212;
        border-color: #555555;
        border-radius: 4px;
    }

    .login-remember .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .login-remember .form-check-input:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.16);
    }

    .login-submit {
        min-height: 50px;
        border-radius: 11px;
        border: 0;
        background: linear-gradient(135deg, #e53935 0%, #c62828 100%);
        box-shadow: 0 10px 26px rgba(229, 57, 53, 0.28);
        letter-spacing: 0.015em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
    }

    .login-submit:hover {
        transform: translateY(-1px);
        filter: brightness(1.06);
        box-shadow: 0 14px 32px rgba(229, 57, 53, 0.38);
    }

    .login-submit:active {
        transform: translateY(0);
    }

    .login-submit:disabled {
        cursor: wait;
        opacity: 0.82;
        transform: none;
    }

    .login-spinner {
        display: none;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: login-spin 700ms linear infinite;
    }

    .login-submit.is-loading .login-spinner {
        display: inline-block;
    }

    .login-submit.is-loading .login-arrow {
        display: none;
    }

    .login-card-footer {
        margin-top: 1.35rem;
        padding-top: 1rem;
        border-top: 1px solid #262626;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.75rem;
        color: #8f8f8f;
    }

    .login-feedback {
        border-radius: 10px;
    }

    @keyframes login-enter {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes login-spin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 991.98px) {
        .login-shell {
            grid-template-columns: minmax(0, 440px);
            justify-content: center;
        }

        .login-showcase {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .login-backdrop {
            padding-inline: 0.75rem;
        }

        .login-card {
            border-radius: 16px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-showcase,
        .login-card {
            animation: none;
        }

        .login-spinner {
            animation-duration: 1.5s;
        }

        .login-module-tile,
        .login-input,
        .password-visibility,
        .login-submit {
            transition: none;
        }
    }
</style>

<div class="login-backdrop">
    <div class="login-shell">
        <aside class="login-showcase" aria-label="Inventory operations overview">
            <span class="login-live-pill">
                <span class="login-live-dot" aria-hidden="true"></span>
                Eustace Batchoy &middot; Stock Control Workspace
            </span>

            <div>
                <h2 class="login-hero-title">
                    Modern kitchen inventory,<br>
                    <span class="login-hero-accent">accounted down to the gram.</span>
                </h2>
                <p class="login-hero-copy mt-3">
                    Track ingredient stock levels, log daily deliveries and kitchen removals, review physical stock counts, and maintain a verified purchasing ledger for {{ $businessName }}.
                </p>
            </div>

            <div class="login-bento">
                <div class="login-bento-top">
                    <span>Core Operational Modules</span>
                    <span style="color: #ffc107;">4 Role Workspace</span>
                </div>

                <div class="login-bento-grid">
                    <div class="login-module-tile">
                        <div class="login-module-icon login-module-icon--green" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <div class="login-module-title">Live Stock Ledger</div>
                        <p class="login-module-desc">Real-time shelf counts, low-stock alerts &amp; spoilage logs.</p>
                    </div>

                    <div class="login-module-tile">
                        <div class="login-module-icon login-module-icon--amber" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                        </div>
                        <div class="login-module-title">Approval Workflow</div>
                        <p class="login-module-desc">Staff count requests reviewed &amp; approved by Owner.</p>
                    </div>

                    <div class="login-module-tile">
                        <div class="login-module-icon login-module-icon--red" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <div class="login-module-title">Role Boundaries</div>
                        <p class="login-module-desc">Strict separation for Owner, Staff, Supplier &amp; Admin.</p>
                    </div>
                </div>

                <div class="login-trust-strip">
                    <span class="login-trust-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2.2" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        Single active session per user
                    </span>
                    <span class="login-trust-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFC107" stroke-width="2.2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Immutable audit trail
                    </span>
                </div>
            </div>
        </aside>

        <section class="card login-card" aria-labelledby="login-heading">
            <div class="text-center mb-4">
                <div class="brand-mark login-brand-mark mx-auto mb-3">
                    <img
                        src="{{ $logoUrl ?: asset('images/eustace-mascot.jpg') }}"
                        alt="{{ $businessName }} logo"
                        class="login-brand-logo"
                    >
                </div>
                <div class="mb-2">
                    <span class="login-badge">Staff &amp; Management Access</span>
                </div>
                <h1 id="login-heading" class="login-heading fw-bold mb-1">Welcome back</h1>
                <p class="muted small mb-0">
                    Sign in to {{ $businessName === 'MyBatchoy' ? 'MyBatchoy Inventory' : $businessName . ' · MyBatchoy Inventory' }}
                </p>
            </div>

            @if (session('success'))
                <div class="alert alert-success login-feedback py-2 small mb-3" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-info login-feedback py-2 small mb-3" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div
                id="login-feedback"
                class="alert alert-danger login-feedback py-2 small mb-3 {{ $errors->any() ? '' : 'd-none' }}"
                role="alert"
                aria-live="assertive"
                @if (!$errors->any()) hidden @endif
            >{{ $errors->any() ? $errors->first() : '' }}</div>

            <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label login-label fw-semibold">Username or email</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            class="form-control login-input {{ $errors->has('username') ? 'is-invalid' : '' }}"
                            value="{{ old('username') }}"
                            placeholder="Enter your username or email"
                            autocomplete="username"
                            maxlength="255"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label login-label fw-semibold mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="login-link-yellow text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="login-input-wrap login-password-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control login-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            aria-describedby="password-visibility-label"
                        >
                        <button
                            id="password-visibility"
                            class="password-visibility"
                            type="button"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg data-eye-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/>
                                <circle cx="12" cy="12" r="2.6"/>
                            </svg>
                            <svg data-eye-closed class="d-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M3 3l18 18"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.5 3.5"/>
                                <path d="M9.4 5.3A10.7 10.7 0 0 1 12 5c6.1 0 9.5 7 9.5 7a16.4 16.4 0 0 1-3.3 4.3"/>
                                <path d="M6.4 6.4C3.9 8.2 2.5 12 2.5 12s3.4 7 9.5 7c1.6 0 3.1-.4 4.4-1"/>
                            </svg>
                        </button>
                        <span id="password-visibility-label" class="visually-hidden">Use this button to show or hide your password.</span>
                    </div>
                </div>

                <div class="form-check login-remember mb-4">
                    <input
                        type="checkbox"
                        name="remember"
                        class="form-check-input"
                        id="remember"
                        value="1"
                        @checked(old('remember'))
                    >
                    <label class="form-check-label small muted" for="remember">Remember this device</label>
                </div>

                <button id="login-submit" type="submit" class="btn btn-primary login-submit w-100 py-2 fw-semibold">
                    <span class="login-spinner" aria-hidden="true"></span>
                    <span data-submit-label>Sign In</span>
                    <svg class="login-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>

                <div class="login-card-footer">
                    <span>Active session protected</span>
                    <span>Role-verified portal</span>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('login-form');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const visibilityButton = document.getElementById('password-visibility');
        const eyeOpen = visibilityButton.querySelector('[data-eye-open]');
        const eyeClosed = visibilityButton.querySelector('[data-eye-closed]');
        const submitButton = document.getElementById('login-submit');
        const feedback = document.getElementById('login-feedback');
        const submitLabel = submitButton.querySelector('[data-submit-label]');
        let isSubmitting = false;

        visibilityButton.addEventListener('click', function () {
            const shouldShow = password.type === 'password';
            password.type = shouldShow ? 'text' : 'password';
            visibilityButton.setAttribute('aria-pressed', String(shouldShow));
            visibilityButton.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            eyeOpen.classList.toggle('d-none', shouldShow);
            eyeClosed.classList.toggle('d-none', !shouldShow);
            password.focus();
        });

        [username, password].forEach(function (field) {
            field.addEventListener('input', function () {
                field.classList.remove('is-invalid');
            });
        });

        function showFeedback(message) {
            feedback.textContent = message;
            feedback.hidden = false;
            feedback.classList.remove('d-none');
        }

        function setLoading(loading) {
            submitButton.disabled = loading;
            submitButton.classList.toggle('is-loading', loading);
            submitButton.setAttribute('aria-busy', String(loading));
            submitLabel.textContent = loading ? 'Signing in...' : 'Sign In';
        }

        form.addEventListener('submit', async function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            const missingUsername = username.value.trim() === '';
            const missingPassword = password.value === '';

            if (missingUsername || missingPassword) {
                event.preventDefault();
                username.classList.toggle('is-invalid', missingUsername);
                password.classList.toggle('is-invalid', missingPassword);
                showFeedback('Please enter your username or email and password.');
                (missingUsername ? username : password).focus();
                return;
            }

            event.preventDefault();
            isSubmitting = true;
            feedback.hidden = true;
            feedback.classList.add('d-none');
            setLoading(true);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.redirected) {
                    window.location.assign(response.url);
                    return;
                }

                if (response.ok) {
                    window.location.reload();
                    return;
                }

                if (response.status === 419) {
                    showFeedback('Your sign-in page expired. Refresh the page and try again.');
                } else if (response.status === 422 || response.status === 429) {
                    let message = 'These credentials do not match our records.';
                    try {
                        const payload = await response.json();
                        message = payload?.errors?.username?.[0]
                            || payload?.errors?.password?.[0]
                            || payload?.message
                            || message;
                    } catch (parseError) {}
                    username.classList.add('is-invalid');
                    password.classList.add('is-invalid');
                    showFeedback(message);
                } else if (response.status >= 500) {
                    showFeedback('Sign-in is temporarily unavailable. Please try again.');
                } else {
                    showFeedback('We could not sign you in. Check your details or contact the Owner/Admin for help.');
                }
            } catch (error) {
                showFeedback('Unable to reach the sign-in service. Check your connection and try again.');
            } finally {
                isSubmitting = false;
                setLoading(false);
            }
        });
    })();
</script>
@endsection
