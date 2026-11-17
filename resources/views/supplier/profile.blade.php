@extends('layouts.app')

@section('title', 'Supplier Profile')

@section('content')
<x-page-header
    section="Supplier Portal"
    breadcrumb="Profile"
    :title="$supplier->name . ' — Contact Profile'"
    subtitle="Update your business contact details used for purchase order coordination.">
    <x-slot:actions>
        <a href="{{ route('supplier.dashboard') }}" class="btn btn-outline-light">&larr; Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-lg-7">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Supplier Contact Information</h2>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('supplier.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Company / Supplier Name</label>
                        <input type="text" class="form-control" value="{{ $supplier->name }}" disabled>
                        <small class="form-help">Company name changes are managed by the business Owner.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="supContactPerson">Contact Person</label>
                        <input id="supContactPerson" type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person) }}">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="supPhoneInput">Contact Phone</label>
                            <input id="supPhoneInput" type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supEmailInput">Contact Email</label>
                            <input id="supEmailInput" type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="supAddressInput">Business Address</label>
                        <textarea id="supAddressInput" name="address" rows="3" class="form-control">{{ old('address', $supplier->address) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Supplier Contact Details</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Portal Login Security</h2>
            </div>
            <div class="eb-panel-body">
                <dl class="row small mb-3">
                    <dt class="col-4 muted">Username</dt>
                    <dd class="col-8 font-monospace text-warning">{{ $user->username }}</dd>
                    <dt class="col-4 muted">Email</dt>
                    <dd class="col-8 text-white">{{ $user->email }}</dd>
                </dl>
                <a href="{{ route('profile.show') }}" class="btn btn-outline-light w-100">Change Account Password &rarr;</a>
            </div>
        </div>
    </div>
</div>
@endsection

