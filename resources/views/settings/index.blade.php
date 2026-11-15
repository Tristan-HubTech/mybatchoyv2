@extends('layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $apkUrl = config('app.mobile_apk_url') ?: 'https://github.com/Tristan-HubTech/mybatchoyv2/releases/latest/download/mybatchoy.apk';
@endphp

<x-page-header
    section="Administration"
    title="Settings"
    subtitle="Configure business details, security timeouts, stock alert thresholds, categories, measurement units, and mobile app access.">
    <x-slot:actions>
        <a href="{{ route('owner.backup.index') }}" class="btn btn-outline-warning">Backup &amp; system health</a>
    </x-slot:actions>
</x-page-header>

<div class="eb-tabs" role="navigation" aria-label="Settings sections">
    <a href="#business-settings" class="eb-tab-link active">Business &amp; Security</a>
    <a href="#inventory-defaults" class="eb-tab-link">Inventory Defaults</a>
    <a href="#categories-units" class="eb-tab-link">Categories &amp; Units</a>
    <a href="#operational-status" class="eb-tab-link">Alerts &amp; Health</a>
    <a href="#mobile-app-download" class="eb-tab-link">Mobile App APK</a>
</div>

<x-alert-banner />

<form method="POST" action="{{ route('owner.settings.update') }}" enctype="multipart/form-data" data-settings-form data-safe-submit>
    @csrf
    @method('PUT')

    <div class="row g-4 mb-4" id="business-settings">
        <div class="col-lg-6">
            <section class="eb-panel-card h-100" aria-labelledby="business-settings-title">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <span id="business-settings-title">Business Information</span>
                    </div>
                    <x-status-badge variant="success" label="Editable" />
                </div>
                <div class="eb-panel-body">
                    <div class="mb-3">
                        <label for="business-name" class="form-label">Business name <span class="text-danger">*</span></label>
                        <input id="business-name" name="business_name" class="form-control" value="{{ old('business_name', $settings->business_name) }}" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label for="business-phone" class="form-label">Contact number</label>
                        <input id="business-phone" name="phone" class="form-control" value="{{ old('phone', $settings->phone) }}" maxlength="32" autocomplete="tel">
                    </div>
                    <div class="mb-3">
                        <label for="business-email" class="form-label">Business email</label>
                        <input id="business-email" name="email" type="email" class="form-control" value="{{ old('email', $settings->email) }}" maxlength="255" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="business-address" class="form-label">Business address</label>
                        <textarea id="business-address" name="address" class="form-control" rows="2" maxlength="1000">{{ old('address', $settings->address) }}</textarea>
                    </div>
                    <div>
                        <label for="business-logo" class="form-label">Business logo</label>
                        @if ($settings->logo_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Current business logo" class="rounded border p-1" style="border-color: #333333 !important; max-width: 100px; max-height: 72px; object-fit: contain; background: #141414;">
                            </div>
                        @endif
                        <input id="business-logo" name="logo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Optional. JPEG, PNG, or WebP up to 2 MB.</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="eb-panel-card h-100" aria-labelledby="security-settings-title">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <span id="security-settings-title">Security Settings</span>
                    </div>
                    <x-status-badge variant="warning" label="Owner Only" />
                </div>
                <div class="eb-panel-body">
                    <div class="mb-4">
                        <label for="session-timeout" class="form-label">Inactive session timeout (minutes) <span class="text-danger">*</span></label>
                        <input id="session-timeout" name="session_timeout_minutes" type="number" class="form-control" value="{{ old('session_timeout_minutes', $settings->session_timeout_minutes) }}" min="5" max="240" required>
                        <div class="form-text">Automatically logs out idle devices after this duration (5 to 240 minutes).</div>
                    </div>

                    <div class="p-3 rounded-3 mb-3" style="background: #141414; border: 1px solid #2A2A2A;">
                        <div class="fw-bold text-white small mb-2 text-uppercase" style="letter-spacing: 0.05em;">Security Status Overview</div>
                        <div class="d-flex justify-content-between align-items-center gap-3 py-2 border-bottom small" style="border-color: #242424 !important;">
                            <div>
                                <div class="text-white fw-semibold">Single Active Session</div>
                                <div style="color: #BDBDBD;">Only one device or browser can use an account at a time.</div>
                            </div>
                            <x-status-badge variant="success" label="Enabled" />
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-3 py-2 border-bottom small" style="border-color: #242424 !important;">
                            <span style="color: #BDBDBD;">Login attempt protection</span>
                            <x-status-badge variant="success" label="Enabled (Rate Limited)" />
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-3 py-2 border-bottom small" style="border-color: #242424 !important;">
                            <span style="color: #BDBDBD;">Owner MFA</span>
                            <x-status-badge
                                :variant="auth()->user()?->mfa_enabled ? 'success' : 'warning'"
                                :label="auth()->user()?->mfa_enabled ? 'Enabled (Authenticator + Email)' : 'Email Security Code Required'"
                            />
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-3 pt-2 small">
                            <span style="color: #BDBDBD;">Session revocation</span>
                            <span class="text-end text-white">Password changes &amp; Owner recovery invalidate sessions</span>
                        </div>
                    </div>

                    <div class="eb-alert eb-alert-info mb-0 small py-2">
                        <strong>Mandatory Policy:</strong> All Owner and Staff accounts allow one active login at a time. Recover stuck sessions below with password and 6-digit MFA verification.
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="eb-panel-card mb-4" id="inventory-defaults">
        <div class="eb-panel-header">
            <div class="eb-panel-title">
                <span id="inventory-settings-title">Inventory Defaults</span>
            </div>
            <span class="small" style="color: #BDBDBD;">Pre-filled when creating new items</span>
        </div>
        <div class="eb-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="default-minimum-stock" class="form-label">Default minimum stock level <span class="text-danger">*</span></label>
                    <input id="default-minimum-stock" name="default_minimum_stock_level" type="number" step="0.001" min="0" class="form-control" value="{{ old('default_minimum_stock_level', $settings->default_minimum_stock_level) }}" required>
                    <div class="form-text">Triggers Low Stock warnings when quantity drops to or below this value.</div>
                </div>
                <div class="col-md-6">
                    <label for="default-reorder-level" class="form-label">Default reorder level <span class="text-danger">*</span></label>
                    <input id="default-reorder-level" name="default_reorder_level" type="number" step="0.001" min="0" class="form-control" value="{{ old('default_reorder_level', $settings->default_reorder_level) }}" required>
                    <div class="form-text">Suggested replenishment trigger for newly registered inventory items.</div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top d-flex flex-wrap gap-2" style="border-color: #2A2A2A !important;">
                <button class="btn btn-primary px-4" type="submit" data-loading-label="Saving settings...">Save Settings</button>
            </div>
        </div>
    </div>
</form>

<!-- Active Sessions & Recovery (Owner Only) -->
<section class="eb-panel-card mb-4" id="active-sessions-card" aria-labelledby="active-sessions-title">
    <div class="eb-panel-header">
        <div class="eb-panel-title">
            <span id="active-sessions-title">Active Login Sessions &amp; Recovery</span>
        </div>
        <x-status-badge variant="success" label="Single Active Session: Enabled" />
    </div>
    <div class="eb-panel-body">
        <p class="small mb-3" style="color: #BDBDBD;">
            View which device or browser is currently signed in for each account. If a device is lost or a browser crashes, request a 6-digit MFA code to your Owner email (or use your Authenticator app) and confirm your password to safely unlock that account.
        </p>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom" style="border-color: #2A2A2A !important;">
            <div class="small" style="color: #BDBDBD;">
                Recovery verification email: <strong class="text-white">{{ auth()->user()?->email }}</strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('owner.settings.mfa.challenge') }}" data-safe-submit>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning" data-loading-label="Sending code...">
                        Send 6-Digit MFA Code to Email
                    </button>
                </form>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-light"
                    data-bs-toggle="collapse"
                    data-bs-target="#owner-mfa-setup-panel"
                    aria-expanded="false"
                    aria-controls="owner-mfa-setup-panel"
                >
                    {{ auth()->user()?->mfa_enabled ? 'Manage Authenticator App MFA' : 'Set up Authenticator App MFA' }}
                </button>
            </div>
        </div>

        <div class="collapse mb-4" id="owner-mfa-setup-panel">
            <div class="p-3 rounded-3" style="background: #141414; border: 1px solid #2A2A2A;">
                @if (auth()->user()?->mfa_enabled)
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="fw-semibold text-white">Authenticator App MFA is Active</div>
                            <div class="small" style="color: #BDBDBD;">You can enter 6-digit codes from your Authenticator app during session recovery.</div>
                        </div>
                        <x-status-badge variant="success" label="Configured" />
                    </div>
                    <form method="POST" action="{{ route('owner.settings.mfa.disable') }}" class="row g-2 align-items-end" data-safe-submit>
                        @csrf
                        @method('DELETE')
                        <div class="col-md-4">
                            <label for="disable-mfa-password" class="form-label small">Owner password</label>
                            <input id="disable-mfa-password" name="current_password" type="password" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label for="disable-mfa-code" class="form-label small">6-digit MFA code</label>
                            <input id="disable-mfa-code" name="mfa_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control form-control-sm" placeholder="123456" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Disable Authenticator MFA</button>
                        </div>
                    </form>
                @elseif ($pendingMfaSecret)
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data={{ urlencode($pendingMfaUri ?? '') }}"
                                alt="Authenticator App MFA setup QR code"
                                width="128"
                                height="128"
                                class="rounded bg-white p-2"
                            >
                        </div>
                        <div class="col">
                            <div class="fw-semibold text-white mb-1">Pair an Authenticator App (Optional)</div>
                            <p class="small mb-2" style="color: #BDBDBD;">
                                Scan the QR code with Google Authenticator or Microsoft Authenticator, or enter setup key:
                                <code class="text-warning user-select-all">{{ $pendingMfaSecret }}</code>
                            </p>
                            <form method="POST" action="{{ route('owner.settings.mfa.configure') }}" class="row g-2 align-items-end" data-safe-submit>
                                @csrf
                                <div class="col-md-4">
                                    <label for="enable-mfa-password" class="form-label small">Owner password</label>
                                    <input id="enable-mfa-password" name="current_password" type="password" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="enable-mfa-code" class="form-label small">6-digit app code</label>
                                    <input id="enable-mfa-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control form-control-sm" placeholder="123456" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Enable Authenticator MFA</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="eb-table-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Device / Browser</th>
                            <th>Login Date &amp; Time</th>
                            <th>Last Activity</th>
                            <th>Session Status</th>
                            <th class="text-end">Recovery</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeSessions as $activeSession)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">{{ $activeSession['username'] }}</div>
                                    <div class="small" style="color: #BDBDBD;">
                                        {{ $activeSession['name'] }} &middot; {{ $activeSession['role_label'] }}
                                        @if ($activeSession['is_current_user'])
                                            <span class="text-warning">(This Account)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-white">{{ $activeSession['device_info'] }}</td>
                                <td class="small" style="color: #BDBDBD;">{{ $activeSession['logged_in_at']->format('M d, Y g:i A') }}</td>
                                <td class="small" style="color: #BDBDBD;">
                                    {{ $activeSession['last_activity_at']->diffForHumans() }}
                                    <div class="opacity-75">{{ $activeSession['last_activity_at']->format('M d, Y g:i A') }}</div>
                                </td>
                                <td>
                                    <x-status-badge variant="success" :label="$activeSession['status']" />
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-revoke-session-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#revokeSessionModal"
                                        data-revoke-url="{{ route('owner.settings.sessions.revoke', $activeSession['user_id']) }}"
                                        data-revoke-username="{{ $activeSession['username'] }}"
                                        data-revoke-device="{{ $activeSession['device_info'] }}"
                                    >
                                        Revoke Session
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 small" style="color: #BDBDBD;">
                                    No active sessions are currently recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="revokeSessionModal" tabindex="-1" aria-labelledby="revokeSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #1B1B1B; border: 1px solid #333333; color: #FFFFFF;">
            <form id="revoke-session-form" method="POST" action="" data-safe-submit>
                @csrf
                <div class="modal-header" style="border-color: #2A2A2A;">
                    <h2 class="modal-title h5 fw-bold text-white" id="revokeSessionModalLabel">Revoke Active Session</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-3" style="color: #BDBDBD;">
                        This will sign out <strong id="revoke-target-username" class="text-white">this account</strong> from
                        <strong id="revoke-target-device" class="text-warning">its active device</strong> so the user can sign in again.
                    </p>
                    <div class="mb-3">
                        <label for="revoke-owner-password" class="form-label">Owner/Admin password <span class="text-danger">*</span></label>
                        <input id="revoke-owner-password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="mb-2">
                        <label for="revoke-mfa-code" class="form-label">6-digit MFA verification code <span class="text-danger">*</span></label>
                        <input id="revoke-mfa-code" name="mfa_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control" placeholder="Enter 6-digit code" required>
                        <div class="form-text">Use your Authenticator app or click &ldquo;Send 6-Digit MFA Code to Email&rdquo; first.</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color: #2A2A2A;">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-loading-label="Revoking...">Verify &amp; Revoke Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const revokeForm = document.getElementById('revoke-session-form');
    const targetUsername = document.getElementById('revoke-target-username');
    const targetDevice = document.getElementById('revoke-target-device');

    document.querySelectorAll('.js-revoke-session-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            if (revokeForm) {
                revokeForm.action = button.getAttribute('data-revoke-url') || '';
            }
            if (targetUsername) {
                targetUsername.textContent = button.getAttribute('data-revoke-username') || 'this account';
            }
            if (targetDevice) {
                targetDevice.textContent = button.getAttribute('data-revoke-device') || 'its active device';
            }
        });
    });
});
</script>

<!-- Staff Access Management (Owner/Admin Only) -->
<section class="eb-panel-card mb-4" id="staff-access-management" aria-labelledby="staff-access-title">
    <div class="eb-panel-header">
        <div>
            <div class="eb-panel-title" id="staff-access-title">Staff Access Management</div>
            <div class="small" style="color: #BDBDBD;">
                Public registration is disabled. Only Owner/Admin can create Staff accounts after security verification.
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($staffAccessVerified)
                <x-status-badge variant="success" label="Unlocked (10 min)" />
                <form method="POST" action="{{ route('owner.settings.staff-access.lock') }}" class="d-inline" data-safe-submit>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">Lock Controls</button>
                </form>
            @else
                <x-status-badge variant="warning" label="Verification Required" />
            @endif
        </div>
    </div>
    <div class="eb-panel-body">
        @if ($errors->has('staff_access'))
            <div class="alert alert-danger py-2 small mb-3" role="alert">
                {{ $errors->first('staff_access') }}
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 rounded-3 mb-3" style="background: #141414; border: 1px solid #2A2A2A;">
            <div class="small" style="color: #BDBDBD;">
                <span class="text-white fw-semibold">{{ $staffAccounts->count() }}</span> staff {{ \Illuminate\Support\Str::plural('account', $staffAccounts->count()) }}
                (<span class="text-success fw-semibold">{{ $staffAccounts->where('is_active', true)->count() }} active</span>,
                <span class="text-warning fw-semibold">{{ $staffAccounts->where('is_active', false)->count() }} inactive</span>).
                Accounts created here always use the <span class="text-white fw-semibold">Staff</span> role.
            </div>
            @if (auth()->user()?->mfa_enabled)
                <x-status-badge variant="success" label="Password + MFA Required" />
            @else
                <x-status-badge variant="neutral" label="Password Re-Verification Required" />
            @endif
        </div>

        @if (!$staffAccessVerified)
            <div class="p-3 rounded-3" style="background: #141414; border: 1px solid #2A2A2A;">
                <div class="fw-semibold text-white mb-1">Verify Owner/Admin Identity</div>
                <p class="small mb-3" style="color: #BDBDBD;">
                    Re-enter your Owner/Admin password
                    @if (auth()->user()?->mfa_enabled)
                        and your 6-digit Authenticator MFA code
                    @endif
                    to access sensitive Staff account creation controls.
                </p>

                <form method="POST" action="{{ route('owner.settings.staff-access.verify') }}" class="row g-3 align-items-end" data-safe-submit>
                    @csrf
                    <div class="col-md-5">
                        <label for="staff-access-verify-password" class="form-label small">Owner/Admin password</label>
                        <input
                            id="staff-access-verify-password"
                            name="current_password"
                            type="password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            required
                            autocomplete="current-password"
                        >
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if (auth()->user()?->mfa_enabled)
                        <div class="col-md-4">
                            <label for="staff-access-verify-mfa" class="form-label small">6-digit MFA code</label>
                            <input
                                id="staff-access-verify-mfa"
                                name="mfa_code"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                maxlength="6"
                                class="form-control @error('mfa_code') is-invalid @enderror"
                                placeholder="123456"
                                required
                            >
                            @error('mfa_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" data-loading-label="Verifying...">Unlock Staff Controls</button>
                    </div>
                </form>
            </div>
        @else
            <div class="p-3 rounded-3 mb-3" style="background: #141414; border: 1px solid #2A2A2A;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <div class="fw-semibold text-white">Create Staff Account</div>
                        <div class="small" style="color: #BDBDBD;">Only Owner/Admin can provision new staff accounts. Role assignment is restricted to Staff.</div>
                    </div>
                    <a href="{{ route('owner.staff.index') }}" class="btn btn-sm btn-outline-light">Manage Existing Staff</a>
                </div>

                <form method="POST" action="{{ route('owner.settings.staff-access.create') }}" class="row g-3" data-settings-form data-safe-submit>
                    @csrf
                    <div class="col-md-6">
                        <label for="settings-staff-name" class="form-label small">Full name</label>
                        <input id="settings-staff-name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="settings-staff-username" class="form-label small">Username</label>
                        <input id="settings-staff-username" name="username" type="text" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required maxlength="255">
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="settings-staff-email" class="form-label small">Email address</label>
                        <input id="settings-staff-email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="255">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="settings-staff-phone" class="form-label small">Phone (optional)</label>
                        <input id="settings-staff-phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="32">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="settings-staff-password" class="form-label small">Password (12+ chars, mixed, number, symbol)</label>
                        <input id="settings-staff-password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="settings-staff-password-confirm" class="form-label small">Confirm password</label>
                        <input id="settings-staff-password-confirm" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                        <label for="settings-staff-status" class="form-label small">Account status</label>
                        <select id="settings-staff-status" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                            <option value="1" @selected(old('is_active', '1') === '1')>Active (Can sign in immediately)</option>
                            <option value="0" @selected(old('is_active') === '0')>Inactive (Disabled until activated)</option>
                        </select>
                        @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2 pt-1">
                        <span class="small" style="color: #BDBDBD;">Role: <strong class="text-white">Staff</strong> (Owner/Admin accounts cannot be created here)</span>
                        <button type="submit" class="btn btn-primary" data-loading-label="Creating Staff...">Create Staff Account</button>
                    </div>
                </form>
            </div>

            @if ($staffAccounts->isNotEmpty())
                <div class="eb-table-card">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th class="text-end">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($staffAccounts as $staffAccount)
                                    <tr>
                                        <td class="text-white fw-semibold">{{ $staffAccount->name }}</td>
                                        <td style="color: #BDBDBD;">{{ $staffAccount->username }}</td>
                                        <td style="color: #BDBDBD;">{{ $staffAccount->email }}</td>
                                        <td>
                                            <x-status-badge :variant="$staffAccount->is_active ? 'success' : 'neutral'" :label="$staffAccount->is_active ? 'Active' : 'Inactive'" />
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('owner.staff.edit', $staffAccount) }}" class="btn btn-sm btn-outline-light">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>

<!-- Categories & Units Management -->
<div class="eb-panel-card mb-4" id="categories-units">
    <div class="eb-panel-header">
        <div class="eb-panel-title">
            <span id="categories-title">Categories and measurement units</span>
        </div>
        <span class="small" style="color: #BDBDBD;">Deactivating preserves all historical records</span>
    </div>
    <div class="eb-panel-body">
        <div class="row g-4">
            <div class="col-xl-6">
                <h3 class="h6 fw-bold text-white mb-3">Inventory Categories</h3>
                <form method="POST" action="{{ route('owner.settings.categories.store') }}" class="row g-2 mb-3" data-settings-form data-safe-submit>
                    @csrf
                    <div class="col-sm-5">
                        <label class="visually-hidden" for="new-category-name">New category name</label>
                        <input id="new-category-name" name="name" class="form-control" placeholder="Category name" required maxlength="255">
                    </div>
                    <div class="col-sm-5">
                        <label class="visually-hidden" for="new-category-description">Category description</label>
                        <input id="new-category-description" name="description" class="form-control" placeholder="Description (optional)" maxlength="1000">
                    </div>
                    <div class="col-sm-2">
                        <button class="btn btn-primary w-100" type="submit">Add</button>
                    </div>
                </form>

                @forelse ($categories as $category)
                    <div class="p-3 rounded-3 mb-2" style="background: #141414; border: 1px solid #2A2A2A;">
                        <form method="POST" action="{{ route('owner.settings.categories.update', $category) }}" class="row g-2 align-items-center" data-settings-form data-safe-submit>
                            @csrf
                            @method('PUT')
                            <div class="col-md-5">
                                <label class="visually-hidden" for="category-name-{{ $category->id }}">Category name</label>
                                <input id="category-name-{{ $category->id }}" name="name" class="form-control form-control-sm" value="{{ $category->name }}" required maxlength="255">
                            </div>
                            <div class="col-md-5">
                                <label class="visually-hidden" for="category-description-{{ $category->id }}">Description</label>
                                <input id="category-description-{{ $category->id }}" name="description" class="form-control form-control-sm" value="{{ $category->description }}" placeholder="Optional description" maxlength="1000">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-outline-light w-100" type="submit" data-loading-label="Saving...">Save</button>
                            </div>
                        </form>
                        <form method="POST"
                              action="{{ route('owner.settings.categories.status', $category) }}"
                              class="mt-2 d-flex align-items-center justify-content-between"
                              data-safe-submit
                              data-confirm-title="Update Category Status?"
                              data-confirm-message="Change the active status of category {{ $category->name }}?"
                              data-confirm-button="Yes, Update"
                              data-confirm-variant="{{ $category->is_active ? 'btn-warning text-dark' : 'btn-primary' }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                            <x-status-badge :variant="$category->is_active ? 'success' : 'neutral'" :label="$category->is_active ? 'Active' : 'Inactive'" />
                            <button class="btn btn-sm {{ $category->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="small mb-0" style="color: #BDBDBD;">No categories have been created.</p>
                @endforelse
            </div>

            <div class="col-xl-6">
                <h3 class="h6 fw-bold text-white mb-3">Measurement Units</h3>
                <form method="POST" action="{{ route('owner.settings.units.store') }}" class="row g-2 mb-3" data-settings-form data-safe-submit>
                    @csrf
                    <div class="col-sm-4">
                        <label class="visually-hidden" for="new-unit-name">New unit name</label>
                        <input id="new-unit-name" name="name" class="form-control" placeholder="Unit name" required maxlength="255">
                    </div>
                    <div class="col-sm-3">
                        <label class="visually-hidden" for="new-unit-short-name">Short name</label>
                        <input id="new-unit-short-name" name="short_name" class="form-control" placeholder="Short (kg)" required maxlength="255">
                    </div>
                    <div class="col-sm-3">
                        <label class="visually-hidden" for="new-unit-description">Unit description</label>
                        <input id="new-unit-description" name="description" class="form-control" placeholder="Description" maxlength="1000">
                    </div>
                    <div class="col-sm-2">
                        <button class="btn btn-primary w-100" type="submit">Add</button>
                    </div>
                </form>

                @forelse ($units as $unit)
                    <div class="p-3 rounded-3 mb-2" style="background: #141414; border: 1px solid #2A2A2A;">
                        <form method="POST" action="{{ route('owner.settings.units.update', $unit) }}" class="row g-2 align-items-center" data-settings-form data-safe-submit>
                            @csrf
                            @method('PUT')
                            <div class="col-md-4">
                                <label class="visually-hidden" for="unit-name-{{ $unit->id }}">Unit name</label>
                                <input id="unit-name-{{ $unit->id }}" name="name" class="form-control form-control-sm" value="{{ $unit->name }}" required maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label class="visually-hidden" for="unit-short-name-{{ $unit->id }}">Short name</label>
                                <input id="unit-short-name-{{ $unit->id }}" name="short_name" class="form-control form-control-sm" value="{{ $unit->short_name }}" required maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label class="visually-hidden" for="unit-description-{{ $unit->id }}">Description</label>
                                <input id="unit-description-{{ $unit->id }}" name="description" class="form-control form-control-sm" value="{{ $unit->description }}" placeholder="Description" maxlength="1000">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-outline-light w-100" type="submit" data-loading-label="Saving...">Save</button>
                            </div>
                        </form>
                        <form method="POST"
                              action="{{ route('owner.settings.units.status', $unit) }}"
                              class="mt-2 d-flex align-items-center justify-content-between"
                              data-safe-submit
                              data-confirm-title="Update Unit Status?"
                              data-confirm-message="Change the active status of unit {{ $unit->name }}?"
                              data-confirm-button="Yes, Update"
                              data-confirm-variant="{{ $unit->is_active ? 'btn-warning text-dark' : 'btn-primary' }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $unit->is_active ? 0 : 1 }}">
                            <x-status-badge :variant="$unit->is_active ? 'success' : 'neutral'" :label="$unit->is_active ? 'Active' : 'Inactive'" />
                            <button class="btn btn-sm {{ $unit->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                {{ $unit->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="small mb-0" style="color: #BDBDBD;">No measurement units have been created.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Notifications, Appearance, Health, and Mobile App -->
<div class="row g-4" id="operational-status">
    <div class="col-lg-6">
        <section class="eb-panel-card h-100" aria-labelledby="notification-settings-title">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span id="notification-settings-title">Inventory alerts</span>
                </div>
                <x-status-badge variant="success" label="In-App Active" />
            </div>
            <div class="eb-panel-body">
                <ul class="small mb-0 ps-3" style="color: #BDBDBD; line-height: 1.75;">
                    <li>Low-stock and out-of-stock conditions appear immediately on dashboards and the alert bell using each item's thresholds.</li>
                    <li>Email alert preferences are disabled because email notifications are not configured.</li>
                    <li>Expiration notifications are unavailable since inventory items do not track expiration dates.</li>
                </ul>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="eb-panel-card h-100" aria-labelledby="appearance-settings-title">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span id="appearance-settings-title">Interface preferences</span>
                </div>
                <x-status-badge variant="neutral" label="Eustace Dark UI" />
            </div>
            <div class="eb-panel-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 mb-2" style="color: #BDBDBD;">Theme</dt>
                    <dd class="col-7 mb-2 text-white fw-semibold">Dark theme (Eustace Batchoy Standard)</dd>
                    <dt class="col-5 mb-0" style="color: #BDBDBD;">Sidebar</dt>
                    <dd class="col-7 mb-0" style="color: #BDBDBD;">Collapse state saves automatically in this browser.</dd>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-12">
        <section class="eb-panel-card" aria-labelledby="system-settings-title">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span id="system-settings-title">Application health</span>
                </div>
                <x-status-badge :variant="$systemInfo['database_status'] === 'Connected' ? 'success' : 'danger'" :label="$systemInfo['database_status']" />
            </div>
            <div class="eb-panel-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-3 mb-2" style="color: #BDBDBD;">Application version</dt>
                    <dd class="col-sm-9 mb-2 text-white">Laravel {{ $systemInfo['application_version'] }}</dd>
                    <dt class="col-sm-3 mb-2" style="color: #BDBDBD;">Environment</dt>
                    <dd class="col-sm-9 mb-2 text-white">{{ $systemInfo['environment'] }}</dd>
                    <dt class="col-sm-3 mb-2" style="color: #BDBDBD;">Database</dt>
                    <dd class="col-sm-9 mb-2 text-white">{{ $systemInfo['database_driver'] }} &middot; {{ $systemInfo['database_status'] }}</dd>
                    <dt class="col-sm-3 mb-0" style="color: #BDBDBD;">Session storage</dt>
                    <dd class="col-sm-9 mb-0 text-white">{{ $systemInfo['session_driver'] }}</dd>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-12" id="mobile-app-download">
        <section class="eb-panel-card" aria-labelledby="mobile-app-title">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span id="mobile-app-title">Download Android App</span>
                </div>
                <x-status-badge variant="warning" label="Mobile Companion" />
            </div>
            <div class="eb-panel-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
                    <div style="max-width: 560px;">
                        <p class="small mb-3" style="color: #BDBDBD;">
                            Install the MyBatchoy Android companion app on phones or tablets in the kitchen to check inventory and log stock movements on the go.
                        </p>
                        <a href="{{ $apkUrl }}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Download Android APK</a>
                    </div>
                    <div class="text-center">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($apkUrl) }}"
                            alt="QR code to download MyBatchoy Android APK"
                            width="140"
                            height="140"
                            class="rounded bg-white p-2"
                        >
                        <div class="small mt-2" style="color: #BDBDBD;">Scan to download</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
