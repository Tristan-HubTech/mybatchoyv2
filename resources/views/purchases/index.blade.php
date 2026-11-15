@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div id="purchasesSkeletonWrapper" class="d-none">
    @include('components.skeletons.purchases-skeleton')
</div>

<div id="purchasesLiveContent">
    <x-page-header
        section="Purchasing"
        title="Purchases"
        subtitle="Track supplier deliveries, purchase numbers, itemized ingredient costs, and total supply spending.">
        <x-slot:actions>
            <a href="{{ route('owner.purchases.create') }}" class="btn btn-primary">+ Record Purchase</a>
        </x-slot:actions>
    </x-page-header>

    <x-alert-banner />

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-kpi-card
                label="Purchase Orders"
                :value="number_format($totalRecords)"
                meta="Matching current filter"
                metaTone="up"
                tone="yellow">
                <x-slot:icon>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </x-slot:icon>
            </x-kpi-card>
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-kpi-card
                label="Combined Spend"
                :value="'₱' . number_format($combinedTotal, 2)"
                meta="Total cost across orders"
                metaTone="warn"
                tone="red">
                <x-slot:icon>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </x-slot:icon>
            </x-kpi-card>
        </div>
    </div>

    <div class="eb-filter-card">
        <form method="GET" action="{{ route('owner.purchases.index') }}" id="purchasesFilterForm" class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label" for="purchaseSearch">Search Order # or Supplier</label>
                <input id="purchaseSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="PO number, supplier, or note...">
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label" for="purchaseSupplierFilter">Filter by Supplier</label>
                <select id="purchaseSupplierFilter" name="supplier" class="form-select">
                    <option value="">All Suppliers</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label" for="purchaseFrom">From Date</label>
                <input id="purchaseFrom" type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label" for="purchaseTo">To Date</label>
                <input id="purchaseTo" type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                @if (request()->filled('supplier') || request()->filled('search') || request()->filled('from') || request()->filled('to'))
                    <a href="{{ route('owner.purchases.index') }}" class="btn btn-outline-light">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Purchase Orders">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $purchases->total() }} {{ Str::plural('order', $purchases->total()) }}</span>
        </x-slot:headerActions>

        @if ($purchases->isEmpty())
            <x-empty-state
                title="No purchase orders recorded"
                description="Record supplier deliveries and receipts so inventory stock and costs stay synced."
                :actionUrl="request()->filled('supplier') || request()->filled('search') || request()->filled('from') || request()->filled('to') ? route('owner.purchases.index') : route('owner.purchases.create')"
                :actionLabel="request()->filled('supplier') || request()->filled('search') || request()->filled('from') || request()->filled('to') ? 'Clear Filters' : '+ Record Purchase'" />
        @else
            <div class="table-responsive">
                <table class="table eb-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Purchase #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="text-end">Line Items</th>
                            <th class="text-end">Total Amount</th>
                            <th>Recorded By</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                            @php
                                $lineItemsPayload = $purchase->items->map(function ($line) {
                                    return [
                                        'name' => $line->inventoryItem?->name ?? 'Deleted Item',
                                        'qty' => (rtrim(rtrim(number_format((float) $line->quantity, 3), '0'), '.') ?: '0') . ' ' . ($line->inventoryItem?->unit?->short_name ?? ''),
                                        'unit_cost' => number_format((float) $line->unit_cost, 2),
                                        'subtotal' => number_format((float) $line->subtotal, 2),
                                    ];
                                })->values()->all();
                            @endphp
                            <tr>
                                <td class="font-monospace fw-bold text-white">{{ $purchase->purchase_number }}</td>
                                <td class="small" style="color: #BDBDBD;">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('M d, Y') }}</td>
                                <td class="fw-semibold text-white">{{ $purchase->supplier?->name ?? 'Direct / Walk-in' }}</td>
                                <td class="text-end">
                                    <x-status-badge variant="neutral" :label="$purchase->items->count() . ' ' . Str::plural('item', $purchase->items->count())" />
                                </td>
                                <td class="text-end fw-bold text-warning">&#8369;{{ number_format((float) $purchase->total_amount, 2) }}</td>
                                <td class="small text-white">{{ $purchase->creator?->name ?? 'System' }}</td>
                                <td class="text-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-light purchase-details-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#purchaseDetailModal"
                                        data-number="{{ $purchase->purchase_number }}"
                                        data-date="{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('M d, Y') }}"
                                        data-supplier="{{ $purchase->supplier?->name ?? 'Direct / Walk-in' }}"
                                        data-creator="{{ $purchase->creator?->name ?? 'System' }}"
                                        data-total="&#8369;{{ number_format((float) $purchase->total_amount, 2) }}"
                                        data-notes="{{ $purchase->notes ?: 'No notes recorded.' }}"
                                        data-items='@json($lineItemsPayload)'>
                                        View Items
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $purchases->firstItem() ?? 0 }}&ndash;{{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} purchase orders
                </div>
                <div>
                    {{ $purchases->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<!-- Purchase Details Modal -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="eb-section-pill mb-1">Purchase Order</span>
                    <h5 class="modal-title fw-bold mb-0 font-monospace" id="purchaseDetailTitle">PO Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 small mb-3">
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Supplier</div>
                        <div class="fw-semibold text-white" id="poDetailSupplier">-</div>
                    </div>
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Purchase Date</div>
                        <div class="fw-semibold text-white" id="poDetailDate">-</div>
                    </div>
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Recorded By</div>
                        <div class="fw-semibold text-white" id="poDetailCreator">-</div>
                    </div>
                    <div class="col-12">
                        <div style="color: #BDBDBD;">Receipt / Payment Notes</div>
                        <div class="p-2 rounded mt-1 text-white" style="background: #141414; border: 1px solid #2A2A2A;" id="poDetailNotes">-</div>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="border-color: #2A2A2A !important;">
                    <table class="table eb-table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="poDetailItemsBody"></tbody>
                        <tfoot>
                            <tr style="background: #141414;">
                                <th colspan="3" class="text-end py-2" style="color: #BDBDBD;">Total Purchase Amount:</th>
                                <th class="text-end py-2 text-warning fw-bold" id="poDetailTotal">₱0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchasesFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            const skeleton = document.getElementById('purchasesSkeletonWrapper');
            const live = document.getElementById('purchasesLiveContent');
            if (skeleton && live) {
                skeleton.classList.remove('d-none');
                live.style.opacity = '0.35';
                live.style.pointerEvents = 'none';
            }
        });
    }

    document.querySelectorAll('.purchase-details-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('purchaseDetailTitle').textContent = btn.dataset.number || 'Purchase Order';
            document.getElementById('poDetailSupplier').textContent = btn.dataset.supplier || 'Direct';
            document.getElementById('poDetailDate').textContent = btn.dataset.date || '—';
            document.getElementById('poDetailCreator').textContent = btn.dataset.creator || 'System';
            document.getElementById('poDetailNotes').textContent = btn.dataset.notes || '—';
            document.getElementById('poDetailTotal').textContent = btn.dataset.total || '₱0.00';

            const tbody = document.getElementById('poDetailItemsBody');
            tbody.replaceChildren();
            let items = [];
            try {
                items = JSON.parse(btn.dataset.items || '[]');
            } catch (e) {
                items = [];
            }

            if (!items.length) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 4;
                td.className = 'text-center py-3';
                td.style.color = '#BDBDBD';
                td.textContent = 'No line items found on this purchase.';
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }

            items.forEach(function (item) {
                const tr = document.createElement('tr');
                const tdName = document.createElement('td');
                tdName.className = 'fw-semibold text-white';
                tdName.textContent = item.name || '—';

                const tdQty = document.createElement('td');
                tdQty.className = 'text-end';
                tdQty.style.color = '#BDBDBD';
                tdQty.textContent = item.qty || '0';

                const tdCost = document.createElement('td');
                tdCost.className = 'text-end';
                tdCost.style.color = '#BDBDBD';
                tdCost.textContent = '₱' + (item.unit_cost || '0.00');

                const tdSub = document.createElement('td');
                tdSub.className = 'text-end fw-bold text-white';
                tdSub.textContent = '₱' + (item.subtotal || '0.00');

                tr.append(tdName, tdQty, tdCost, tdSub);
                tbody.appendChild(tr);
            });
        });
    });
});
</script>
@endsection
