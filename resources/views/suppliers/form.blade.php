@extends('layouts.app')

@section('title', isset($supplier) ? 'Edit Supplier' : 'Add New Supplier')

@section('content')
@php
    $isEdit = isset($supplier);
@endphp

<x-page-header
    section="Purchasing"
    breadcrumb="Suppliers"
    :title="$isEdit ? 'Edit Supplier' : 'Add New Supplier'"
    subtitle="Save supplier contact information, delivery schedule notes, and address details.">
    <x-slot:actions>
        <a href="{{ route('owner.suppliers.index') }}" class="btn btn-outline-light">&larr; Back to Suppliers</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="{{ $isEdit ? 'col-lg-8' : 'col-xl-9' }}">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>{{ $isEdit ? 'Supplier Details' : 'New Supplier Information' }}</span>
                </div>
                <span class="small" style="color: #BDBDBD;">Fields marked <span class="text-danger">*</span> are required</span>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ $isEdit ? route('owner.suppliers.update', $supplier) : route('owner.suppliers.store') }}" data-safe-submit>
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sup-name">Supplier / Store Name <span class="text-danger">*</span></label>
                            <input id="sup-name" type="text" name="name" class="form-control" value="{{ old('name', $supplier->name ?? '') }}" placeholder="e.g., Mang Juan's Meat Shop" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sup-contact">Contact Person</label>
                            <input id="sup-contact" type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" placeholder="e.g., Juan Dela Cruz">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sup-phone">Phone Number</label>
                            <input id="sup-phone" type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}" placeholder="e.g., 0917 123 4567">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sup-email">Email Address</label>
                            <input id="sup-email" type="email" name="email" class="form-control" value="{{ old('email', $supplier->email ?? '') }}" placeholder="e.g., supplier@example.com">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="sup-address">Store Address</label>
                            <textarea id="sup-address" name="address" class="form-control" rows="2" placeholder="Where the supplier is located">{{ old('address', $supplier->address ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="sup-notes">Delivery &amp; Payment Notes</label>
                            <textarea id="sup-notes" name="notes" class="form-control" rows="2" placeholder="Delivery schedule, payment terms, or reminders">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top" style="border-color: #2A2A2A !important;">
                        <button type="submit" class="btn btn-primary btn-lg px-4" data-loading-label="{{ $isEdit ? 'Saving Changes...' : 'Adding Supplier...' }}">
                            {{ $isEdit ? 'Save Supplier Changes' : 'Save New Supplier' }}
                        </button>
                        <a href="{{ route('owner.suppliers.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($isEdit)
        <div class="col-lg-4">
            <x-table-card title="Recent Purchase History" :actionUrl="route('owner.purchases.index', ['supplier' => $supplier->id])" actionLabel="View All">
                @if ($supplier->purchases->isEmpty())
                    <x-empty-state
                        title="No purchase orders yet"
                        description="Orders recorded under this supplier will appear here."
                        :actionUrl="route('owner.purchases.create')"
                        actionLabel="+ Record Purchase" />
                @else
                    <div class="table-responsive">
                        <table class="table eb-table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($supplier->purchases as $po)
                                    <tr>
                                        <td class="font-monospace small text-white">{{ $po->purchase_number }}</td>
                                        <td class="small" style="color: #BDBDBD;">{{ \Carbon\Carbon::parse($po->purchase_date)->format('M d, Y') }}</td>
                                        <td class="text-end fw-bold text-warning">&#8369;{{ number_format((float) $po->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-table-card>
        </div>
    @endif
</div>
@endsection
