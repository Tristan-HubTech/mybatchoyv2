@extends('layouts.app')

@section('title', 'Add Stock')

@section('content')
@php
    $routePrefix = auth()->user()?->role?->name === 'owner' ? 'owner' : 'staff';
    $preselectedItemId = old('inventory_item_id', request('item'));
@endphp

<x-page-header
    section="Operations"
    breadcrumb="Stock History"
    title="Add Stock"
    subtitle="Use this form when new ingredients or deliveries arrive. Fields marked with * are required.">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light">&larr; Back to Stock History</a>
    </x-slot:actions>
</x-page-header>

<div class="eb-tabs" role="navigation" aria-label="Stock actions">
    <a href="{{ route($routePrefix . '.transactions.index') }}" class="eb-tab-link">Stock History</a>
    <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="eb-tab-link active">+ Add Stock</a>
    <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="eb-tab-link">- Remove Stock</a>
    @if ($routePrefix === 'owner')
        <a href="{{ route('owner.transactions.adjustment') }}" class="eb-tab-link">Correct Stock Quantity</a>
    @else
        <a href="{{ route('staff.adjustments.index') }}" class="eb-tab-link">Request Stock Adjustment</a>
    @endif
    <a href="{{ route($routePrefix . '.transactions.spoilage') }}" class="eb-tab-link">Record Spoilage</a>
</div>

<x-alert-banner />

<div class="row">
    <div class="col-xl-9">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <x-status-badge variant="tx-in" label="Step-by-Step Stock Entry" />
                </div>
                <span class="small" style="color: #BDBDBD;">Updates available shelf quantity immediately</span>
            </div>
            <div class="eb-panel-body">

        <form method="POST" action="{{ route($routePrefix . '.transactions.stockInStore') }}" data-safe-submit id="addStockForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="stockInItemSelect">1. Select the item <span class="required-star">*</span></label>
                    <select id="stockInItemSelect" name="inventory_item_id" class="form-select" required>
                        <option value="">Choose an item...</option>
                        @foreach ($items as $item)
                            @php
                                $qtyVal = rtrim(rtrim(number_format((float) $item->current_quantity, 3, '.', ''), '0'), '.') ?: '0';
                                $unitVal = $item->unit?->short_name ?? 'units';
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-qty="{{ (float) $item->current_quantity }}"
                                    data-unit="{{ $unitVal }}"
                                    data-name="{{ $item->name }}"
                                    {{ (string) $preselectedItemId === (string) $item->id ? 'selected' : '' }}>
                                {{ $item->name }} (Current: {{ $qtyVal }} {{ $unitVal }})
                            </option>
                        @endforeach
                    </select>
                    <small class="field-help">Pick the ingredient or supply you are adding.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="stockInQuantity">2. Quantity received <span class="required-star" id="stockInUnitBadge">*</span></label>
                    <input id="stockInQuantity" type="number" step="0.001" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="e.g., 10" required>
                    <small class="field-help">Enter how much arrived (must be greater than 0).</small>
                </div>

                <div class="col-12">
                    <label class="form-label" for="stockInReason">3. Supplier or purchase reference (when applicable)</label>
                    <input id="stockInReason" type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g., Delivered by Aling Nena's Market — Receipt #104">
                    <small class="field-help">Optional note so you remember where this delivery came from.</small>
                </div>
            </div>

            <!-- Step 4: Live Review Box -->
            <div id="stockInPreviewBox" class="mt-4 p-3 rounded-3 d-none" style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.35);">
                <div class="fw-bold text-white mb-1">4. Review Before Saving</div>
                <div class="small text-white">
                    Item: <strong id="stockInPreviewName"></strong>
                    <span id="stockInMathWrap" class="d-none">
                        <br>Current stock: <strong id="stockInPreviewPrev"></strong> +
                        <span class="text-success fw-bold" id="stockInPreviewAdd"></span> =
                        New stock total: <strong class="text-warning" id="stockInPreviewTotal"></strong>
                    </span>
                    <span id="stockInIdleWrap" class="d-none">
                        • Current stock on hand: <strong id="stockInPreviewIdleQty"></strong>
                    </span>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-lg" data-loading-label="Adding Stock...">Confirm Add Stock</button>
                <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemSelect = document.getElementById('stockInItemSelect');
    const qtyInput = document.getElementById('stockInQuantity');
    const previewBox = document.getElementById('stockInPreviewBox');
    const previewName = document.getElementById('stockInPreviewName');
    const mathWrap = document.getElementById('stockInMathWrap');
    const idleWrap = document.getElementById('stockInIdleWrap');
    const previewPrev = document.getElementById('stockInPreviewPrev');
    const previewAdd = document.getElementById('stockInPreviewAdd');
    const previewTotal = document.getElementById('stockInPreviewTotal');
    const previewIdleQty = document.getElementById('stockInPreviewIdleQty');
    const unitBadge = document.getElementById('stockInUnitBadge');

    function formatNum(n) {
        return Number(n).toLocaleString(undefined, { maximumFractionDigits: 3 });
    }

    function updatePreview() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        if (!option || !option.value) {
            previewBox.classList.add('d-none');
            unitBadge.textContent = '*';
            return;
        }
        const unit = option.dataset.unit || 'units';
        const currentQty = parseFloat(option.dataset.qty || '0');
        const addQty = parseFloat(qtyInput.value || '0');
        unitBadge.textContent = '(' + unit + ') *';
        previewName.textContent = option.dataset.name;

        if (!isNaN(addQty) && addQty > 0) {
            const newTotal = currentQty + addQty;
            previewPrev.textContent = formatNum(currentQty) + ' ' + unit;
            previewAdd.textContent = formatNum(addQty) + ' ' + unit;
            previewTotal.textContent = formatNum(newTotal) + ' ' + unit;
            mathWrap.classList.remove('d-none');
            idleWrap.classList.add('d-none');
        } else {
            previewIdleQty.textContent = formatNum(currentQty) + ' ' + unit;
            idleWrap.classList.remove('d-none');
            mathWrap.classList.add('d-none');
        }
        previewBox.classList.remove('d-none');
    }

    itemSelect.addEventListener('change', updatePreview);
    qtyInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>
@endsection
