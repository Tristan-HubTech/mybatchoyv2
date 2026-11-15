@extends('layouts.app')

@section('title', isset($item) ? 'Edit Inventory Item' : 'Add New Inventory Item')

@section('content')
@php
    $defaultMinimumStockLevel = $defaultMinimumStockLevel ?? 5;
    $defaultReorderLevel = $defaultReorderLevel ?? 10;
    $isEdit = isset($item);
@endphp

<x-page-header
    section="Inventory"
    breadcrumb="Manage Stock"
    :title="$isEdit ? 'Edit Inventory Item' : 'Add New Inventory Item'"
    subtitle="Fill in the ingredient name, measuring unit, and minimum stock alert threshold.">
    <x-slot:actions>
        <a href="{{ route('owner.inventory.index') }}" class="btn btn-outline-light">&larr; Back to Stock List</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row">
    <div class="col-xl-9">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>{{ $isEdit ? 'Update Item Details' : 'New Item Details' }}</span>
                </div>
                <span class="small" style="color: #BDBDBD;">Fields marked <span class="text-danger">*</span> are required</span>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ $isEdit ? route('owner.inventory.update', $item) : route('owner.inventory.store') }}" data-safe-submit>
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="item-name">Item Name <span class="text-danger">*</span></label>
                            <input id="item-name" type="text" class="form-control" name="name" value="{{ old('name', $item->name ?? '') }}" placeholder="e.g., Pork Belly or Fresh Miki Noodles" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-category">Group / Category <span class="text-danger">*</span></label>
                            <select id="item-category" name="category_id" class="form-select" required>
                                <option value="">Choose a category...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $item->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-unit">How Do You Measure It? <span class="text-danger">*</span></label>
                            <select id="item-unit" name="unit_id" class="form-select" required>
                                <option value="">Choose a unit (kg, pcs, bottles)...</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->short_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-sku">Item Code / SKU</label>
                            <input id="item-sku" type="text" class="form-control" name="sku" value="{{ old('sku', $item->sku ?? '') }}" placeholder="Leave blank to create automatically">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-qty">Starting / Current Stock <span class="text-danger">*</span></label>
                            <input id="item-qty" type="number" step="0.001" min="0" class="form-control" name="current_quantity" value="{{ old('current_quantity', $item->current_quantity ?? 0) }}" required>
                            <div class="form-text">Total quantity currently sitting on the shelf or in the freezer.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-min">Low-Stock Warning Level <span class="text-danger">*</span></label>
                            <input id="item-min" type="number" step="0.001" min="0" class="form-control" name="minimum_stock_level" value="{{ old('minimum_stock_level', $item->minimum_stock_level ?? $defaultMinimumStockLevel) }}" required>
                            <div class="form-text">Marked as Low Stock once quantity reaches or drops below this number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="item-reorder">Suggested Reorder Level</label>
                            <input id="item-reorder" type="number" step="0.001" min="0" class="form-control" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? $defaultReorderLevel) }}">
                            <div class="form-text">Recommended quantity threshold to trigger a supplier restock order.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="item-description">Notes or Storage Tips</label>
                            <textarea id="item-description" name="description" class="form-control" rows="3" placeholder="Example: Keep frozen, check freshness on delivery">{{ old('description', $item->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top" style="border-color: #2A2A2A !important;">
                        <button type="submit" class="btn btn-primary btn-lg px-4" data-loading-label="{{ $isEdit ? 'Updating Item...' : 'Adding Item...' }}">
                            {{ $isEdit ? 'Save Item Changes' : 'Save New Item' }}
                        </button>
                        <a href="{{ route('owner.inventory.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
