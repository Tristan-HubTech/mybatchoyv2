@extends('layouts.app')

@section('title', 'Record Purchase')

@section('content')
<x-page-header
    section="Purchasing"
    breadcrumb="Purchases"
    title="Record Purchase"
    subtitle="Enter the supplier, purchase date, and purchased items. Saving this purchase automatically adds those quantities to stock.">
    <x-slot:actions>
        <a href="{{ route('owner.purchases.index') }}" class="btn btn-outline-light">&larr; Back to Purchases</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row">
    <div class="col-xl-10">
        <div class="eb-panel-card">
            <div class="eb-panel-header">
                <div class="eb-panel-title">
                    <span>Purchase Order &amp; Line Items</span>
                </div>
                <span class="small" style="color: #BDBDBD;">Stock levels update automatically on save</span>
            </div>
            <div class="eb-panel-body">

        <form method="POST" action="{{ route('owner.purchases.store') }}" data-safe-submit id="purchaseCreateForm">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="purchaseSupplier">1. Supplier (optional)</label>
                    <select id="purchaseSupplier" name="supplier_id" class="form-select">
                        <option value="">Direct / Walk-in Purchase</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="purchaseDate">2. Purchase date <span class="required-star">*</span></label>
                    <input id="purchaseDate" type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="fw-bold text-white">3. Purchased Items <span class="required-star">*</span></span>
                    <small class="muted d-block">Pick each item, how much you bought, and the price per unit.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning" id="addPurchaseLineBtn">+ Add Another Item</button>
            </div>

            <div id="purchaseLinesContainer" class="d-flex flex-column gap-3">
                <div class="border border-border rounded-3 p-3 purchase-line-row" style="background: #141414;">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Item <span class="required-star">*</span></label>
                            <select name="items[0][inventory_item_id]" class="form-select purchase-item-select" required>
                                <option value="">Choose an item...</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit?->short_name ?? 'unit' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Quantity <span class="required-star">*</span></label>
                            <input type="number" step="0.001" min="0.01" name="items[0][quantity]" class="form-control purchase-qty" placeholder="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit Cost (₱) <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control purchase-cost" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <small class="muted d-block">Line Total</small>
                            <strong class="text-warning purchase-line-total">₱0.00</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-1 d-none remove-line-btn">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label" for="purchaseNotes">4. Notes / Receipt Reference (optional)</label>
                <input id="purchaseNotes" type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="e.g., Paid cash, Receipt #402">
            </div>

            <div class="mt-4 p-3 rounded-3 d-flex flex-wrap justify-content-between align-items-center" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.35);">
                <div>
                    <div class="fw-bold text-white">Total Purchase Amount</div>
                    <small class="muted">Saving will update your stock automatically.</small>
                </div>
                <div class="fs-3 fw-bold text-warning" id="purchaseGrandTotal">₱0.00</div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-lg" data-loading-label="Saving Purchase...">Save Purchase</button>
                <a href="{{ route('owner.purchases.index') }}" class="btn btn-outline-light btn-lg">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchaseLinesContainer');
    const addBtn = document.getElementById('addPurchaseLineBtn');
    const grandTotalEl = document.getElementById('purchaseGrandTotal');

    function recalculateTotals() {
        let grand = 0;
        const rows = container.querySelectorAll('.purchase-line-row');
        rows.forEach(function (row, idx) {
            row.querySelector('.purchase-item-select').name = 'items[' + idx + '][inventory_item_id]';
            row.querySelector('.purchase-qty').name = 'items[' + idx + '][quantity]';
            row.querySelector('.purchase-cost').name = 'items[' + idx + '][unit_cost]';

            const qty = parseFloat(row.querySelector('.purchase-qty').value || '0');
            const cost = parseFloat(row.querySelector('.purchase-cost').value || '0');
            const lineTotal = (!isNaN(qty) && !isNaN(cost) && qty > 0 && cost >= 0) ? qty * cost : 0;
            row.querySelector('.purchase-line-total').textContent = '₱' + lineTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            grand += lineTotal;

            const removeBtn = row.querySelector('.remove-line-btn');
            if (removeBtn) {
                removeBtn.classList.toggle('d-none', rows.length <= 1);
            }
        });
        grandTotalEl.textContent = '₱' + grand.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    container.addEventListener('input', recalculateTotals);
    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-line-btn')) {
            if (container.querySelectorAll('.purchase-line-row').length > 1) {
                e.target.closest('.purchase-line-row').remove();
                recalculateTotals();
            }
        }
    });

    addBtn.addEventListener('click', function () {
        const firstRow = container.querySelector('.purchase-line-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('.purchase-item-select').value = '';
        clone.querySelector('.purchase-qty').value = '';
        clone.querySelector('.purchase-cost').value = '';
        clone.querySelector('.purchase-line-total').textContent = '₱0.00';
        container.appendChild(clone);
        recalculateTotals();
    });

    recalculateTotals();
});
</script>
@endsection
