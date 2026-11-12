@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-bold accent mb-1">Management</p>
            <h1 class="fw-bold mb-1">Settings</h1>
            <p class="muted mb-0">Manage business details and application preferences.</p>
        </div>
        <a href="{{ route('owner.backup.index') }}" class="btn btn-outline-primary">Backup &amp; system health</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('owner.settings.update') }}" enctype="multipart/form-data" class="card p-4 mb-4" data-settings-form>
        @csrf
        @method('PUT')
        <div class="mb-3">
            <p class="text-uppercase small fw-semibold accent mb-1">Business information</p>
            <h2 class="h5 fw-bold mb-0">Business profile</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="business-name" class="form-label">Business name</label>
                <input id="business-name" name="business_name" class="form-control" value="{{ old('business_name', $settings->business_name) }}" required maxlength="150">
            </div>
            <div class="col-md-6">
                <label for="business-phone" class="form-label">Contact number</label>
                <input id="business-phone" name="phone" class="form-control" value="{{ old('phone', $settings->phone) }}" maxlength="32" autocomplete="tel">
            </div>
            <div class="col-md-6">
                <label for="business-email" class="form-label">Business email</label>
                <input id="business-email" name="email" type="email" class="form-control" value="{{ old('email', $settings->email) }}" maxlength="255" autocomplete="email">
            </div>
            <div class="col-md-6">
                <label for="business-address" class="form-label">Business address</label>
                <textarea id="business-address" name="address" class="form-control" rows="2" maxlength="1000">{{ old('address', $settings->address) }}</textarea>
            </div>
            <div class="col-12">
                <label for="business-logo" class="form-label">Business logo <span class="muted small">(JPEG, PNG, or WebP; up to 2 MB)</span></label>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    @if ($settings->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Current business logo" class="rounded border border-border p-1" style="max-width: 100px; max-height: 72px; object-fit: contain;">
                    @else
                        <div class="brand-mark" aria-label="No business logo uploaded">MB</div>
                    @endif
                    <input id="business-logo" name="logo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
        </div>

        <hr class="my-4 border-border">

        <div class="mb-3">
            <p class="text-uppercase small fw-semibold accent mb-1">Security settings</p>
            <h2 class="h5 fw-bold mb-0">Session and access policy</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-5">
                <label for="session-timeout" class="form-label">Inactive session timeout</label>
                <div class="input-group">
                    <input id="session-timeout" name="session_timeout_minutes" type="number" class="form-control" value="{{ old('session_timeout_minutes', $settings->session_timeout_minutes) }}" min="5" max="240" required>
                    <span class="input-group-text bg-surface-2 border-border text-white">minutes</span>
                </div>
                <small class="muted">Server-enforced; choose between 5 and 240 minutes.</small>
            </div>
            <div class="col-md-7">
                <div class="p-3 rounded border border-border h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <span class="muted">Login attempt protection</span>
                        <span class="text-end">5 per username / 30 per IP per 15 minutes</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mt-2">
                        <span class="muted">Owner MFA</span>
                        <span class="text-end">Not enabled; no MFA flow is configured</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mt-2">
                        <span class="muted">Session revocation</span>
                        <span class="text-end">Password changes invalidate other sessions on their next request</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mt-2">
                        <span class="muted">Active session list</span>
                        <span class="text-end">Not available with the configured session management</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4 border-border">

        <div class="mb-3">
            <p class="text-uppercase small fw-semibold accent mb-1">Inventory settings</p>
            <h2 class="h5 fw-bold mb-0">Default stock thresholds</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="default-minimum-stock" class="form-label">Default minimum stock</label>
                <input id="default-minimum-stock" name="default_minimum_stock_level" type="number" step="0.001" min="0" class="form-control" value="{{ old('default_minimum_stock_level', $settings->default_minimum_stock_level) }}" required>
                <small class="muted">Pre-filled for new inventory items; item-specific levels remain editable.</small>
            </div>
            <div class="col-md-6">
                <label for="default-reorder-level" class="form-label">Default reorder threshold</label>
                <input id="default-reorder-level" name="default_reorder_level" type="number" step="0.001" min="0" class="form-control" value="{{ old('default_reorder_level', $settings->default_reorder_level) }}" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary" type="submit" data-loading-label="Saving settings...">Save business and application settings</button>
        </div>
    </form>

    <section class="card p-4 mb-4" aria-labelledby="categories-title">
        <div class="mb-3">
            <p class="text-uppercase small fw-semibold accent mb-1">Inventory settings</p>
            <h2 id="categories-title" class="h5 fw-bold mb-0">Categories and measurement units</h2>
            <p class="muted small mt-2 mb-0">Entries with existing inventory are deactivated instead of deleted, preserving historical records.</p>
        </div>
        <div class="row g-4">
            <div class="col-xl-6">
                <h3 class="h6 fw-bold">Categories</h3>
                <form method="POST" action="{{ route('owner.settings.categories.store') }}" class="row g-2 mb-3" data-settings-form>
                    @csrf
                    <div class="col-sm-5"><label class="visually-hidden" for="new-category-name">New category name</label><input id="new-category-name" name="name" class="form-control" placeholder="Category name" required maxlength="255"></div>
                    <div class="col-sm-5"><label class="visually-hidden" for="new-category-description">Category description</label><input id="new-category-description" name="description" class="form-control" placeholder="Description (optional)" maxlength="1000"></div>
                    <div class="col-sm-2 d-grid"><button class="btn btn-outline-primary" type="submit" data-loading-label="Adding...">Add</button></div>
                </form>
                <div class="d-grid gap-2">
                    @forelse ($categories as $category)
                        <div class="p-3 rounded border border-border">
                            <form method="POST" action="{{ route('owner.settings.categories.update', $category) }}" class="row g-2 align-items-center" data-settings-form>
                                @csrf
                                @method('PUT')
                                <div class="col-md-4"><label class="visually-hidden" for="category-name-{{ $category->id }}">Category name</label><input id="category-name-{{ $category->id }}" name="name" class="form-control form-control-sm" value="{{ $category->name }}" required maxlength="255"></div>
                                <div class="col-md-4"><label class="visually-hidden" for="category-description-{{ $category->id }}">Description</label><input id="category-description-{{ $category->id }}" name="description" class="form-control form-control-sm" value="{{ $category->description }}" maxlength="1000"></div>
                                <div class="col-md-2 small muted">{{ $category->items_count }} item(s)</div>
                                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit" data-loading-label="Saving...">Save</button></div>
                            </form>
                            <form method="POST" action="{{ route('owner.settings.categories.status', $category) }}" class="mt-2" data-confirm-status data-confirm-message="Change the status of category {{ $category->name }}?">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $category->is_active ? 'btn-outline-warning' : 'btn-outline-primary' }}" type="submit">{{ $category->is_active ? 'Deactivate' : 'Activate' }} · {{ $category->is_active ? 'Active' : 'Inactive' }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="muted small mb-0">No categories have been created.</p>
                    @endforelse
                </div>
            </div>
            <div class="col-xl-6">
                <h3 class="h6 fw-bold">Measurement units</h3>
                <form method="POST" action="{{ route('owner.settings.units.store') }}" class="row g-2 mb-3" data-settings-form>
                    @csrf
                    <div class="col-sm-4"><label class="visually-hidden" for="new-unit-name">New unit name</label><input id="new-unit-name" name="name" class="form-control" placeholder="Unit name" required maxlength="255"></div>
                    <div class="col-sm-3"><label class="visually-hidden" for="new-unit-short-name">Short name</label><input id="new-unit-short-name" name="short_name" class="form-control" placeholder="Short name" required maxlength="255"></div>
                    <div class="col-sm-3"><label class="visually-hidden" for="new-unit-description">Unit description</label><input id="new-unit-description" name="description" class="form-control" placeholder="Description" maxlength="1000"></div>
                    <div class="col-sm-2 d-grid"><button class="btn btn-outline-primary" type="submit" data-loading-label="Adding...">Add</button></div>
                </form>
                <div class="d-grid gap-2">
                    @forelse ($units as $unit)
                        <div class="p-3 rounded border border-border">
                            <form method="POST" action="{{ route('owner.settings.units.update', $unit) }}" class="row g-2 align-items-center" data-settings-form>
                                @csrf
                                @method('PUT')
                                <div class="col-md-3"><label class="visually-hidden" for="unit-name-{{ $unit->id }}">Unit name</label><input id="unit-name-{{ $unit->id }}" name="name" class="form-control form-control-sm" value="{{ $unit->name }}" required maxlength="255"></div>
                                <div class="col-md-2"><label class="visually-hidden" for="unit-short-name-{{ $unit->id }}">Short name</label><input id="unit-short-name-{{ $unit->id }}" name="short_name" class="form-control form-control-sm" value="{{ $unit->short_name }}" required maxlength="255"></div>
                                <div class="col-md-3"><label class="visually-hidden" for="unit-description-{{ $unit->id }}">Description</label><input id="unit-description-{{ $unit->id }}" name="description" class="form-control form-control-sm" value="{{ $unit->description }}" maxlength="1000"></div>
                                <div class="col-md-2 small muted">{{ $unit->items_count }} item(s)</div>
                                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit" data-loading-label="Saving...">Save</button></div>
                            </form>
                            <form method="POST" action="{{ route('owner.settings.units.status', $unit) }}" class="mt-2" data-confirm-status data-confirm-message="Change the status of unit {{ $unit->name }}?">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $unit->is_active ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $unit->is_active ? 'btn-outline-warning' : 'btn-outline-primary' }}" type="submit">{{ $unit->is_active ? 'Deactivate' : 'Activate' }} · {{ $unit->is_active ? 'Active' : 'Inactive' }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="muted small mb-0">No measurement units have been created.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card p-4 h-100" aria-labelledby="notification-settings-title">
                <p class="text-uppercase small fw-semibold accent mb-1">Notifications</p>
                <h2 id="notification-settings-title" class="h5 fw-bold">Inventory alerts</h2>
                <ul class="muted mb-0">
                    <li>Low-stock and out-of-stock conditions appear on the dashboards using each item's thresholds.</li>
                    <li>Email alert preferences are not available because inventory email notifications are not configured.</li>
                    <li>Expiration warnings are not available; inventory items do not currently track expiration dates.</li>
                </ul>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card p-4 h-100" aria-labelledby="appearance-settings-title">
                <p class="text-uppercase small fw-semibold accent mb-1">Appearance</p>
                <h2 id="appearance-settings-title" class="h5 fw-bold">Interface preferences</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5 muted">Theme</dt><dd class="col-sm-7">Dark theme</dd>
                    <dt class="col-sm-5 muted">Sidebar</dt><dd class="col-sm-7">Collapse preference is available from the sidebar and saved in this browser.</dd>
                </dl>
            </section>
        </div>
        <div class="col-12">
            <section class="card p-4" aria-labelledby="system-settings-title">
                <p class="text-uppercase small fw-semibold accent mb-1">System information</p>
                <h2 id="system-settings-title" class="h5 fw-bold">Application health</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-3 muted">Application version</dt><dd class="col-sm-9">Laravel {{ $systemInfo['application_version'] }}</dd>
                    <dt class="col-sm-3 muted">Environment</dt><dd class="col-sm-9">{{ $systemInfo['environment'] }}</dd>
                    <dt class="col-sm-3 muted">Database</dt><dd class="col-sm-9">{{ $systemInfo['database_driver'] }} · {{ $systemInfo['database_status'] }}</dd>
                    <dt class="col-sm-3 muted">Session storage</dt><dd class="col-sm-9">{{ $systemInfo['session_driver'] }}</dd>
                </dl>
            </section>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-settings-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = button.dataset.loadingLabel || 'Saving...';
            }
        });
    });

    document.querySelectorAll('[data-confirm-status]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirmMessage)) {
                event.preventDefault();
                return;
            }

            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Updating...';
            }
        });
    });
</script>
@endsection
