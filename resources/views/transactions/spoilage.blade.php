@extends('layouts.app')

@section('title', 'Record Spoilage')

@section('content')
@php
    $routePrefix = auth()->user()?->role?->name === 'owner' ? 'owner' : 'staff';
@endphp

<x-page-header
    section="Operations"
    breadcrumb="Stock History"
    title="Record Spoilage"
    subtitle="Use this page when an ingredient has spoiled, expired, or been damaged. Recording spoilage deducts available stock.">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light">&larr; Back to Stock History</a>
    </x-slot:actions>
</x-page-header>

<div class="eb-tabs" role="navigation" aria-label="Stock actions">
    <a href="{{ route($routePrefix . '.transactions.index') }}" class="eb-tab-link">Stock History</a>
    <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="eb-tab-link">+ Add Stock</a>
    <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="eb-tab-link">- Remove Stock</a>
    <a href="{{ route($routePrefix . '.transactions.adjustment') }}" class="eb-tab-link">Correct Stock Quantity</a>
    <a href="{{ route($routePrefix . '.transactions.spoilage') }}" class="eb-tab-link active">Record Spoilage</a>
</div>

<x-alert-banner />

<div class="row">
    <div class="col-xl-9">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <x-status-badge variant="tx-spoil" label="Spoiled or Damaged Stock" />
                </div>
                <span class="small" style="color: #BDBDBD;">Auto-deducts from inventory</span>
            </div>
            <div class="eb-panel-body">

        <div class="p-3 rounded-3 mb-4 d-flex flex-wrap justify-content-between gap-2 small" style="background: #141414; border: 1px solid #333333;">
            <div><span style="color: #BDBDBD;">Date:</span> <strong class="text-white">{{ now()->format('M d, Y h:i A') }}</strong></div>
            <div><span style="color: #BDBDBD;">Responsible staff member:</span> <strong class="text-white">{{ auth()->user()?->name }}</strong></div>
        </div>

        <form method="POST"
              action="{{ route($routePrefix . '.transactions.spoilageStore') }}"
              data-safe-submit
              data-confirm-title="Confirm Spoilage Record"
              data-confirm-button="Yes, Deduct Spoiled Stock"
              data-confirm-variant="btn-danger"
              id="spoilageForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="spoilItemSelect">1. Ingredient name <span class="required-star">*</span></label>
                    <select id="spoilItemSelect" name="inventory_item_id" class="form-select" required>
                        <option value="">Choose spoiled ingredient...</option>
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
                                {{ $item->name }} (In Stock: {{ $qtyVal }} {{ $unitVal }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="spoilQuantity">2. Spoiled quantity <span class="required-star" id="spoilUnitBadge">*</span></label>
                    <input id="spoilQuantity" type="number" step="0.001" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="e.g., 2" required>
                    <small class="field-help">Cannot exceed available stock on hand.</small>
                </div>

                <div class="col-12">
                    <label class="form-label" for="spoilReason">3. Reason for spoilage <span class="required-star">*</span></label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach (['Expired', 'Spoiled / turned bad', 'Damaged packaging', 'Spilled / contaminated'] as $preset)
                            <button type="button" class="btn btn-sm btn-outline-light spoil-reason-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                        @endforeach
                    </div>
                    <input id="spoilReason" type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Example: Past expiration date" required>
                </div>
            </div>

            <!-- Live Over-Stock Warning -->
            <div id="spoilOverWarning" class="alert alert-danger mt-4 d-none" role="alert">
                <strong>&#9888; Too much entered:</strong> <span id="spoilOverText"></span>
            </div>

            <!-- Review Box -->
            <div id="spoilPreviewBox" class="mt-4 p-3 rounded-3 d-none" style="background: rgba(229, 57, 53, 0.1); border: 1px solid rgba(229, 57, 53, 0.35);">
                <div class="fw-bold text-white mb-1">4. Review Stock Impact Before Saving</div>
                <div class="small text-white">
                    Ingredient: <strong id="spoilPreviewName"></strong>
                    <span id="spoilMathWrap" class="d-none">
                        <br>Available stock: <strong id="spoilPreviewPrev"></strong> −
                        <span class="text-danger fw-bold" id="spoilPreviewDeduct"></span> =
                        Remaining stock: <strong class="text-warning" id="spoilPreviewRemaining"></strong>
                    </span>
                    <span id="spoilIdleWrap" class="d-none">
                        • Available stock right now: <strong id="spoilPreviewIdleQty"></strong>
                    </span>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" id="spoilSubmitBtn" class="btn btn-danger btn-lg" data-loading-label="Recording Spoilage...">Save Spoilage</button>
                <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('spoilageForm');
    const itemSelect = document.getElementById('spoilItemSelect');
    const qtyInput = document.getElementById('spoilQuantity');
    const reasonInput = document.getElementById('spoilReason');
    const previewBox = document.getElementById('spoilPreviewBox');
    const previewName = document.getElementById('spoilPreviewName');
    const mathWrap = document.getElementById('spoilMathWrap');
    const idleWrap = document.getElementById('spoilIdleWrap');
    const previewPrev = document.getElementById('spoilPreviewPrev');
    const previewDeduct = document.getElementById('spoilPreviewDeduct');
    const previewRemaining = document.getElementById('spoilPreviewRemaining');
    const previewIdleQty = document.getElementById('spoilPreviewIdleQty');
    const overWarning = document.getElementById('spoilOverWarning');
    const overText = document.getElementById('spoilOverText');
    const submitBtn = document.getElementById('spoilSubmitBtn');
    const unitBadge = document.getElementById('spoilUnitBadge');

    document.querySelectorAll('.spoil-reason-chip').forEach(function (btn) {
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
        const spoilQty = parseFloat(qtyInput.value || '0');
        unitBadge.textContent = '(' + unit + ') *';
        previewName.textContent = name;

        if (!isNaN(spoilQty) && spoilQty > 0) {
            if (spoilQty > available) {
                overText.textContent = 'You entered ' + formatNum(spoilQty) + ' ' + unit + ', but only ' + formatNum(available) + ' ' + unit + ' of ' + name + ' is currently in stock.';
                overWarning.classList.remove('d-none');
                previewBox.classList.add('d-none');
                submitBtn.disabled = true;
                return;
            }
            const remaining = Math.max(0, available - spoilQty);
            previewPrev.textContent = formatNum(available) + ' ' + unit;
            previewDeduct.textContent = formatNum(spoilQty) + ' ' + unit + ' (spoiled)';
            previewRemaining.textContent = formatNum(remaining) + ' ' + unit;
            mathWrap.classList.remove('d-none');
            idleWrap.classList.add('d-none');
            previewBox.classList.remove('d-none');
            form.dataset.confirmMessage = 'Record ' + formatNum(spoilQty) + ' ' + unit + ' of ' + name + ' as spoiled? Available stock will drop to ' + formatNum(remaining) + ' ' + unit + '.';
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
