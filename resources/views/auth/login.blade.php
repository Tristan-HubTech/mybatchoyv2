@extends('layouts.app')

@section('title', 'Staff & Management Login')

@section('content')
@php
    $businessName = $businessSettings?->business_name ?: 'MyBatchoy';
    $logoPath = $businessSettings?->logo_path;
@endphp

<style>
    .login-shell {
        width: 100%;
        padding: clamp(0.75rem, 3vw, 2rem) 1rem;
    }

    .login-card {
        width: min(100%, 440px);
        margin-inline: auto;
        padding: clamp(1.5rem, 5vw, 2.35rem) !important;
        background: var(--surface);
        border: 1px solid var(--border);
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.56);
        animation: login-enter 380ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
    }

    .login-brand-mark {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        font-size: 1.15rem;
        box-shadow: 0 8px 24px rgba(229, 57, 53, 0.18);
        overflow: hidden;
    }

    .login-brand-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    .login-heading {
        font-size: clamp(1.4rem, 4.5vw, 1.65rem);
        letter-spacing: -0.03em;
    }

    .login-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.28);
        color: var(--secondary);
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .login-label {
        color: #f0f0f0;
        font-size: 0.84rem;
        margin-bottom: 0.45rem;
    }

    .login-input {
        min-height: 48px;
        padding: 0.72rem 0.9rem;
        border-radius: 10px;
        background: #202020;
        border: 1px solid var(--border);
        color: #fff;
        transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    }

    .login-input::placeholder {
        color: #8f8f8f;
        opacity: 1;
    }

    .login-input:focus {
        background: #242424;
        border-color: var(--secondary);
        color: #fff;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.16);
    }

    .login-input.is-invalid {
        border-color: var(--primary);
        background-image: none;
    }

    .login-password-wrap {
        position: relative;
    }

    .login-password-wrap .login-input {
        padding-right: 3.35rem;
    }

    .password-visibility {
        position: absolute;
        top: 50%;
        right: 0.55rem;
        display: inline-flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--muted);
        transition: color 160ms ease, background-color 160ms ease;
    }

    .password-visibility:hover,
    .password-visibility:focus-visible {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        outline: 2px solid rgba(255, 193, 7, 0.7);
        outline-offset: 1px;
    }

    .password-visibility svg {
        width: 19px;
        height: 19px;
    }

    .login-link-yellow {
        color: var(--secondary);
        font-size: 0.82rem;
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
        width: 1.1rem;
        height: 1.1rem;
        margin-left: -1.75rem;
        background-color: #141414;
        border-color: #666;
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
        min-height: 49px;
        border-radius: 10px;
        letter-spacing: 0.01em;
        transition: background-color 180ms ease, border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    .login-submit:hover {
        transform: translateY(-1px);
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
        margin-right: 0.5rem;
        vertical-align: -0.15rem;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: login-spin 700ms linear infinite;
    }

    .login-submit.is-loading .login-spinner {
        display: inline-block;
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

    @media (max-width: 575.98px) {
        .login-shell {
            padding-inline: 0.85rem;
        }

        .login-card {
            border-radius: 14px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-card {
            animation: none;
        }

        .login-spinner {
            animation-duration: 1.5s;
        }

        .login-input,
        .password-visibility,
        .login-submit {
            transition: none;
        }
    }
</style>

<div class="login-shell">
    <section class="card login-card" aria-labelledby="login-heading">
        <div class="text-center mb-4">
            @if ($logoPath)
                <div class="brand-mark login-brand-mark mx-auto mb-3 bg-surface-2">
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $businessName }} logo" class="login-brand-logo">
                </div>
            @else
                <div class="brand-mark login-brand-mark mx-auto mb-3" aria-hidden="true">MB</div>
            @endif
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

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="password" class="form-label login-label fw-semibold mb-0">Password</label>
                    <a href="{{ route('password.request') }}" class="login-link-yellow text-decoration-none">Forgot password?</a>
                </div>
                <div class="login-password-wrap">
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
            </button>
        </form>
    </section>
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
