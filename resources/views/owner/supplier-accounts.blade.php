@extends('layouts.app')

@section('title', 'Supplier Portal Accounts')

@section('content')
<x-page-header
    section="Purchasing"
    breadcrumb="Supplier Accounts"
    title="Supplier Account Management"
    subtitle="Create, deactivate, or reset portal accounts individually bound to an active Supplier record.">
    <x-slot:actions>
        <a href="{{ route('owner.suppliers.index') }}" class="btn btn-outline-light">&larr; Back to Suppliers</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Create Supplier Portal Account</h2>
                <x-status-badge variant="warning" label="Password Required" />
            </div>
            <div class="eb-panel-body">
                @if ($suppliers->isEmpty())
                    <x-empty-state title="No active suppliers found" description="Create an active supplier record first before provisioning a portal account." :actionUrl="route('owner.suppliers.create')" actionLabel="Create Supplier" />
                @else
                    <form method="POST" action="{{ route('owner.supplier-accounts.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="supSelect">Linked Supplier Record <span class="required-star">*</span></label>
                            <select id="supSelect" name="supplier_id" class="form-select" required>
                                <option value="">Select supplier...</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected((string) old('supplier_id') === (string) $sup->id)>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="supName">Contact Name <span class="required-star">*</span></label>
                            <input id="supName" type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="supUsername">Username <span class="required-star">*</span></label>
                                <input id="supUsername" type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="supPhone">Phone</label>
                                <input id="supPhone" type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="supEmail">Email Address <span class="required-star">*</span></label>
                            <input id="supEmail" type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="supPass">Initial Password <span class="required-star">*</span></label>
                                <input id="supPass" type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="supPassConf">Confirm Password <span class="required-star">*</span></label>
                                <input id="supPassConf" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="col-12">
                                <small class="form-help">Minimum 12 chars with uppercase, lowercase, number, and symbol.</small>
                            </div>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                        <div class="mb-3">
                            <label class="form-label" for="supOwnerPass">Confirm Your Owner Password <span class="required-star">*</span></label>
                            <input id="supOwnerPass" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Supplier Account</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="Provisioned Supplier Portal Accounts">
            @if ($accounts->isEmpty())
                <x-empty-state title="No supplier accounts provisioned" description="Accounts created here can log into the scoped Supplier Portal only." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Linked Supplier</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $acc)
                                <tr>
                                    <td data-label="Account">
                                        <div class="fw-semibold text-white">{{ $acc->name }}</div>
                                        <div class="small font-monospace muted">{{ $acc->username }} &middot; {{ $acc->email }}</div>
                                    </td>
                                    <td class="text-warning fw-semibold" data-label="Supplier">{{ $acc->supplier?->name ?? 'Unlinked' }}</td>
                                    <td data-label="Status">
                                        <x-status-badge :variant="$acc->is_active ? 'success' : 'danger'" :label="$acc->is_active ? 'Active' : 'Deactivated'" />
                                    </td>
                                    <td class="text-end eb-cell-actions">
                                        <form method="POST" action="{{ route('owner.supplier-accounts.status', $acc) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $acc->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm {{ $acc->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                {{ $acc->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-slot:footer>
                    <div class="small muted">Showing {{ $accounts->firstItem() ?? 0 }}&ndash;{{ $accounts->lastItem() ?? 0 }} of {{ $accounts->total() }}</div>
                    <div>{{ $accounts->links() }}</div>
                </x-slot:footer>
            @endif
        </x-table-card>
    </div>
</div>
@endsection

