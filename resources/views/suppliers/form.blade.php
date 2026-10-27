@extends('layouts.app')

@section('title', 'Supplier')

@section('content')
<div class="container py-5">
    <div class="card p-4 mx-auto" style="max-width: 760px;">
        <h2 class="fw-bold mb-4">{{ isset($supplier) ? 'Edit supplier' : 'New supplier' }}</h2>
        <form method="POST" action="{{ isset($supplier) ? route('owner.suppliers.update', $supplier) : route('owner.suppliers.store') }}">
            @csrf
            @if (isset($supplier))
                @method('PUT')
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save supplier</button>
                <a href="{{ route('owner.suppliers.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
