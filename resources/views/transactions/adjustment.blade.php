@extends('layouts.app')

@section('title', 'Stock Adjustment')

@section('content')
<div class="container py-5">
    <div class="card p-4 mx-auto" style="max-width: 720px;">
        <h2 class="fw-bold mb-4">Stock Adjustment</h2>
        <form method="POST" action="{{ auth()->user()?->role?->name === 'owner' ? route('owner.transactions.adjustmentStore') : route('staff.transactions.adjustmentStore') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Inventory item</label>
                    <select name="inventory_item_id" class="form-select" required>
                        <option value="">Select item</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Updated quantity</label>
                    <input type="number" step="0.01" name="current_quantity" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Adjustment reason</label>
                    <input type="text" name="reason" class="form-control" required>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save adjustment</button>
                <a href="{{ auth()->user()?->role?->name === 'owner' ? route('owner.transactions.index') : route('staff.transactions.index') }}" class="btn btn-outline-primary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
