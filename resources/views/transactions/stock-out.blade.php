@extends('layouts.app')

@section('title', 'Remove Stock')

@section('content')
@php
    $routePrefix = auth()->user()?->role?->name === 'owner' ? 'owner' : 'staff';
@endphp

<x-page-header
    section="Operations"
    breadcrumb="Stock History"
    title="Remove Stock"
    subtitle="Record ingredients taken out for kitchen cooking or preparation. You will be asked to confirm before stock is deducted.">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light">&larr; Back to Stock History</a>
    </x-slot:actions>
</x-page-header>

<div class="eb-tabs" role="navigation" aria-label="Stock actions">
    <a href="{{ route($routePrefix . '.transactions.index') }}" class="eb-tab-link">Stock History</a>
    <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="eb-tab-link">+ Add Stock</a>
    <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="eb-tab-link active">- Remove Stock</a>
    <a href="{{ route($routePrefix . '.transactions.adjustment') }}" class="eb-tab-link">Correct Stock Quantity</a>
    <a href="{{ route($routePrefix . '.transactions.spoilage') }}" class="eb-tab-link">Record Spoilage</a>
</div>

<x-alert-banner />

<div class="row">
    <div class="col-xl-9">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <x-status-badge variant="tx-out" label="Kitchen Usage / Stock Removal" />
                </div>
                <span class="small" style="color: #BDBDBD;">Protected with confirmation prompt</span>
            </div>
            <div class="eb-panel-body">

        <form method="POST"
              action="{{ route($routePrefix . '.transactions.stockOutStore') }}"
              data-safe-submit
              data-confirm-title="Confirm Stock Removal"
              data-confirm-button="Yes, Remove Stock"
              data-confirm-variant="btn-danger"
              id="removeStockForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="stockOutItemSelect">1. Select the item <span class="required-star">*</span></label>
                    <select id="stockOutItemSelect" name="inventory_item_id" class="form-select" required>
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
                                    {{ (string) old('inventory_item_id') === (string) $item->id ? 'selected' : '' }}>
                                {{ $item->name }} (Available: {{ $qtyVal }} {{ $unitVal }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="stockOutQuantity">2. Quantity to remove <span class="required-star" id="stockOutUnitBadge">*</span></label>
                    <input id="stockOutQuantity" type="number" step="0.001" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="e.g., 5" required>
                    <small class="field-help">Cannot be more than what is currently in stock.</small>
                </div>

                <div class="col-12">
                    <label class="form-label" for="stockOutReason">3. Reason for removal</label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach (['Kitchen cooking', 'Daily batch preparation', 'Staff meal', 'Kitchen transfer'] as $preset)
                            <button type="button" class="btn btn-sm btn-outline-light reason-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                        @endforeach
                    </div>
                    <input id="stockOutReason" type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Tap a quick reason above or type your own (e.g., Morning batch cooking)">
                </div>
            </div>

            <!-- Live Over-Stock Warning -->
            <div id="stockOutOverWarning" class="alert alert-danger mt-4 d-none" role="alert">
                <strong>&#9888; Too much requested:</strong> <span id="stockOutOverText"></span>
            </div>

            <!-- Step 4: Live Remaining Stock Preview -->
            <div id="stockOutPreviewBox" class="mt-4 p-3 rounded-3 d-none" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.35);">
                <div class="fw-bold text-white mb-1">4. Review Remaining Quantity</div>
                <div class="small text-white">
                    Item: <strong id="stockOutPreviewName"></strong>
                    <span id="stockOutMathWrap" class="d-none">
                        <br>Available now: <strong id="stockOutPreviewPrev"></strong> −
                        <span class="text-danger fw-bold" id="stockOutPreviewRemove"></span> =
                        Remaining after save: <strong class="text-warning" id="stockOutPreviewRemaining"></strong>
                    </span>
                    <span id="stockOutIdleWrap" class="d-none">
                        • Available stock right now: <strong id="stockOutPreviewIdleQty"></strong>
                    </span>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" id="stockOutSubmitBtn" class="btn btn-primary btn-lg" data-loading-label="Removing Stock...">Confirm Removal</button>
                <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('removeStockForm');
    const itemSelect = document.getElementById('stockOutItemSelect');
    const qtyInput = document.getElementById('stockOutQuantity');
    const reasonInput = document.getElementById('stockOutReason');
    const previewBox = document.getElementById('stockOutPreviewBox');
    const previewName = document.getElementById('stockOutPreviewName');
    const mathWrap = document.getElementById('stockOutMathWrap');
    const idleWrap = document.getElementById('stockOutIdleWrap');
    const previewPrev = document.getElementById('stockOutPreviewPrev');
    const previewRemove = document.getElementById('stockOutPreviewRemove');
    const previewRemaining = document.getElementById('stockOutPreviewRemaining');
    const previewIdleQty = document.getElementById('stockOutPreviewIdleQty');
    const overWarning = document.getElementById('stockOutOverWarning');
    const overText = document.getElementById('stockOutOverText');
    const submitBtn = document.getElementById('stockOutSubmitBtn');
    const unitBadge = document.getElementById('stockOutUnitBadge');

    document.querySelectorAll('.reason-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            reasonInput.value = btn.dataset.reason;
        });
    });

    function formatNum(n) {
        return Number(n).toLocaleString(undefined, { maximumFractionDigits: 3 });
    }

    function updateState() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        overWarning.classList.add('d-none');
        submitBtn.disabled = false;

        if (!option || !option.value) {
            previewBox.classList.add('d-none');
            unitBadge.textContent = '*';
            form.dataset.confirmMessage = '';
            return;
        }

        const name = option.dataset.name;
        const unit = option.dataset.unit || 'units';
        const available = parseFloat(option.dataset.qty || '0');
        const removeQty = parseFloat(qtyInput.value || '0');
        unitBadge.textContent = '(' + unit + ') *';
        previewName.textContent = name;

        if (!isNaN(removeQty) && removeQty > 0) {
            if (removeQty > available) {
                overText.textContent = 'You entered ' + formatNum(removeQty) + ' ' + unit + ', but only ' + formatNum(available) + ' ' + unit + ' of ' + name + ' is available.';
                overWarning.classList.remove('d-none');
                previewBox.classList.add('d-none');
                submitBtn.disabled = true;
                return;
            }
            const remaining = Math.max(0, available - removeQty);
            previewPrev.textContent = formatNum(available) + ' ' + unit;
            previewRemove.textContent = formatNum(removeQty) + ' ' + unit;
            previewRemaining.textContent = formatNum(remaining) + ' ' + unit;
            mathWrap.classList.remove('d-none');
            idleWrap.classList.add('d-none');
            previewBox.classList.remove('d-none');
            form.dataset.confirmMessage = 'Remove ' + formatNum(removeQty) + ' ' + unit + ' of ' + name + '? Remaining stock will be ' + formatNum(remaining) + ' ' + unit + '.';
        } else {
            previewIdleQty.textContent = formatNum(available) + ' ' + unit;
            idleWrap.classList.remove('d-none');
            mathWrap.classList.add('d-none');
            previewBox.classList.remove('d-none');
            form.dataset.confirmMessage = '';
        }
    }

    itemSelect.addEventListener('change', updateState);
    qtyInput.addEventListener('input', updateState);
    updateState();
});
</script>
@endsection
