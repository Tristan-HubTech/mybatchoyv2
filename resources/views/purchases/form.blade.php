@extends('layouts.app')

@section('title', 'Create Purchase')

@section('content')
<div class="container py-5">
    <div class="card p-4 mx-auto" style="max-width: 860px;">
        <h2 class="fw-bold mb-4">Record purchase</h2>
        <form method="POST" action="{{ route('owner.purchases.store') }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">No supplier specified</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Purchase date</label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Item</label>
                        <select name="items[0][inventory_item_id]" class="form-select" required>
                            <option value="">Select item</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" name="items[0][quantity]" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit cost</label>
                        <input type="number" step="0.01" name="items[0][unit_cost]" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save purchase</button>
                <a href="{{ route('owner.purchases.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
