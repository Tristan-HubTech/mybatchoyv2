@extends('layouts.app')

@section('title', 'Correct Stock Quantity')

@section('content')
@php
    $routePrefix = auth()->user()?->role?->name === 'owner' ? 'owner' : 'staff';
@endphp

<x-page-header
    section="Operations"
    breadcrumb="Stock History"
    title="Correct Stock Quantity"
    subtitle="Use this feature only to fix a wrong inventory count after physically checking what is left on the shelf.">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light">&larr; Back to Stock History</a>
    </x-slot:actions>
</x-page-header>

<div class="eb-tabs" role="navigation" aria-label="Stock actions">
    <a href="{{ route($routePrefix . '.transactions.index') }}" class="eb-tab-link">Stock History</a>
    <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="eb-tab-link">+ Add Stock</a>
    <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="eb-tab-link">- Remove Stock</a>
    <a href="{{ route($routePrefix . '.transactions.adjustment') }}" class="eb-tab-link active">Correct Stock Quantity</a>
    <a href="{{ route($routePrefix . '.transactions.spoilage') }}" class="eb-tab-link">Record Spoilage</a>
</div>

<x-alert-banner />

@if ($routePrefix === 'staff')
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" style="background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.35); color: #FFE082;">
        <span>Submitting a formal stock count variance for Owner sign-off?</span>
        <a href="{{ route('staff.adjustments.index') }}" class="btn btn-sm btn-warning text-dark fw-semibold">Open Count &amp; Adjust Requests &rarr;</a>
    </div>
@endif

<div class="row">
    <div class="col-xl-9">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <x-status-badge variant="tx-adj" label="Shelf Count Fix" />
                </div>
                <span class="small" style="color: #BDBDBD;">Past stock records remain intact</span>
            </div>
            <div class="eb-panel-body">

        <form method="POST"
              action="{{ route($routePrefix . '.transactions.adjustmentStore') }}"
              data-safe-submit
              data-confirm-title="Confirm Stock Quantity Correction"
              data-confirm-button="Yes, Save Correction"
              data-confirm-variant="btn-warning text-dark fw-semibold"
              id="adjustStockForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="adjItemSelect">1. Select the item <span class="required-star">*</span></label>
                    <select id="adjItemSelect" name="inventory_item_id" class="form-select" required>
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
                                {{ $item->name }} (Recorded: {{ $qtyVal }} {{ $unitVal }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="adjNewQty">2. Corrected quantity (actual shelf count) <span class="required-star" id="adjUnitBadge">*</span></label>
                    <input id="adjNewQty" type="number" step="0.001" min="0" name="current_quantity" class="form-control" value="{{ old('current_quantity') }}" placeholder="e.g., 18" required>
                    <small class="field-help">Enter the exact number on the shelf right now (0 or more).</small>
                </div>

                <div class="col-12">
                    <label class="form-label" for="adjReason">3. Reason for correction <span class="required-star">*</span></label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach (['Physical shelf recount', 'Fixed earlier typing error', 'Unrecorded kitchen usage found'] as $preset)
                            <button type="button" class="btn btn-sm btn-outline-light adj-reason-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                        @endforeach
                    </div>
                    <input id="adjReason" type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Explain why the quantity is changing..." required>
                </div>
            </div>

            <!-- Step 4: Review Before Saving (Previous, Corrected, Difference, Reason) -->
            <div id="adjPreviewBox" class="mt-4 p-3 rounded-3 d-none" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.4);">
                <div class="fw-bold text-white mb-2">4. Review Changes Before Saving</div>
                <div class="row g-2 small">
                    <div class="col-sm-4"><span class="muted d-block">Previous quantity:</span><strong class="text-white" id="adjPrevVal">-</strong></div>
                    <div class="col-sm-4"><span class="muted d-block">Corrected quantity:</span><strong class="text-warning" id="adjNextVal">-</strong></div>
                    <div class="col-sm-4"><span class="muted d-block">Difference:</span><strong id="adjDiffVal">-</strong></div>
                    <div class="col-12 mt-1"><span class="muted">Reason for correction:</span> <strong class="text-white" id="adjReasonVal">-</strong></div>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-lg" data-loading-label="Saving Correction...">Save Corrected Quantity</button>
                <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('adjustStockForm');
    const itemSelect = document.getElementById('adjItemSelect');
    const newQtyInput = document.getElementById('adjNewQty');
    const reasonInput = document.getElementById('adjReason');
    const previewBox = document.getElementById('adjPreviewBox');
    const prevEl = document.getElementById('adjPrevVal');
    const nextEl = document.getElementById('adjNextVal');
    const diffEl = document.getElementById('adjDiffVal');
    const reasonEl = document.getElementById('adjReasonVal');
    const unitBadge = document.getElementById('adjUnitBadge');

    document.querySelectorAll('.adj-reason-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            reasonInput.value = btn.dataset.reason;
            updatePreview();
        });
    });

    function formatNum(n) {
        return Number(n).toLocaleString(undefined, { maximumFractionDigits: 3 });
    }

    function updatePreview() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        if (!option || !option.value) {
            previewBox.classList.add('d-none');
            unitBadge.textContent = '*';
            form.dataset.confirmMessage = '';
            return;
        }
        const name = option.dataset.name;
        const unit = option.dataset.unit || 'units';
        const prev = parseFloat(option.dataset.qty || '0');
        const nextVal = newQtyInput.value === '' ? NaN : parseFloat(newQtyInput.value);
        unitBadge.textContent = '(' + unit + ') *';

        prevEl.textContent = formatNum(prev) + ' ' + unit;
        reasonEl.textContent = reasonInput.value.trim() || '(enter reason above)';

        if (!isNaN(nextVal) && nextVal >= 0) {
            const diff = nextVal - prev;
            const sign = diff > 0 ? '+' : '';
            nextEl.textContent = formatNum(nextVal) + ' ' + unit;
            diffEl.textContent = sign + formatNum(diff) + ' ' + unit;
            diffEl.className = diff >= 0 ? 'text-success' : 'text-danger';
            form.dataset.confirmMessage = 'Change ' + name + ' from ' + formatNum(prev) + ' ' + unit + ' to ' + formatNum(nextVal) + ' ' + unit + ' (' + sign + formatNum(diff) + ' ' + unit + ')?';
        } else {
            nextEl.textContent = '—';
            diffEl.textContent = '—';
            diffEl.className = 'text-white';
            form.dataset.confirmMessage = '';
        }
        previewBox.classList.remove('d-none');
    }

    itemSelect.addEventListener('change', updatePreview);
    newQtyInput.addEventListener('input', updatePreview);
    reasonInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>
@endsection
