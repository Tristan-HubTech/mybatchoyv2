@extends('layouts.app')

@section('title', 'Inventory Form')

@section('content')
<div class="container py-5">
    <div class="card p-4 mx-auto" style="max-width: 760px;">
        <h2 class="fw-bold mb-4">{{ isset($item) ? 'Edit Inventory Item' : 'New Inventory Item' }}</h2>

        <form method="POST" action="{{ isset($item) ? route('owner.inventory.update', $item) : route('owner.inventory.store') }}">
            @csrf
            @if (isset($item))
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Item name</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $item->name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-control" name="sku" value="{{ old('sku', $item->sku ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $item->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" class="form-select" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id ?? '') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Current quantity</label>
                    <input type="number" step="0.001" class="form-control" name="current_quantity" value="{{ old('current_quantity', $item->current_quantity ?? 0) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum stock</label>
                    <input type="number" step="0.001" class="form-control" name="minimum_stock_level" value="{{ old('minimum_stock_level', $item->minimum_stock_level ?? $defaultMinimumStockLevel) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reorder level</label>
                    <input type="number" step="0.001" class="form-control" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? $defaultReorderLevel) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $item->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save item</button>
                <a href="{{ route('owner.inventory.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
