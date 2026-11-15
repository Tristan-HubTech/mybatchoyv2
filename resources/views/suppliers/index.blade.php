@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div id="suppliersSkeletonWrapper" class="d-none">
    @include('components.skeletons.suppliers-skeleton')
</div>

<div id="suppliersLiveContent">
    <x-page-header
        section="Purchasing"
        title="Suppliers"
        subtitle="Keep track of ingredient vendors, contact numbers, and supplier purchase history.">
        <x-slot:actions>
            <a href="{{ route('owner.suppliers.create') }}" class="btn btn-primary">+ Add Supplier</a>
        </x-slot:actions>
    </x-page-header>

    <x-alert-banner />

    <div class="eb-filter-card">
        <form method="GET" action="{{ route('owner.suppliers.index') }}" id="suppliersFilterForm" class="row g-2 align-items-end">
            <div class="col-md-8 col-lg-6">
                <label class="form-label" for="supplierSearch">Find a Supplier</label>
                <input id="supplierSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search supplier name, contact person, or phone...">
            </div>
            <div class="col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('owner.suppliers.index') }}" class="btn btn-outline-light">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Supplier Directory">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $suppliers->total() }} {{ Str::plural('supplier', $suppliers->total()) }}</span>
        </x-slot:headerActions>

        @if ($suppliers->isEmpty())
            <x-empty-state
                title="No suppliers found"
                description="Add regular meat, noodle, or packaging vendors so recording purchases takes seconds."
                :actionUrl="request()->filled('search') ? route('owner.suppliers.index') : route('owner.suppliers.create')"
                :actionLabel="request()->filled('search') ? 'Clear Search' : '+ Add Supplier'" />
        @else
            <div class="table-responsive">
                <table class="table eb-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone &amp; Email</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Total Spend</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            @php
                                $recentPos = $supplier->purchases->map(function ($po) {
                                    return [
                                        'number' => $po->purchase_number,
                                        'date' => $po->purchase_date ? \Carbon\Carbon::parse($po->purchase_date)->format('M d, Y') : '—',
                                        'total' => number_format((float) $po->total_amount, 2),
                                    ];
                                })->values()->all();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-white">{{ $supplier->name }}</div>
                                    @if ($supplier->address)
                                        <div class="small" style="color: #BDBDBD; font-size: 0.76rem;">{{ Str::limit($supplier->address, 55) }}</div>
                                    @endif
                                </td>
                                <td class="text-white">{{ $supplier->contact_person ?: '—' }}</td>
                                <td>
                                    <div class="text-white">{{ $supplier->phone ?: '—' }}</div>
                                    @if ($supplier->email)
                                        <div class="small" style="color: #BDBDBD; font-size: 0.76rem;">{{ $supplier->email }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-status-badge variant="neutral" :label="($supplier->purchases_count ?? 0) . ' ' . Str::plural('order', $supplier->purchases_count ?? 0)" />
                                </td>
                                <td class="text-end fw-bold text-warning">
                                    &#8369;{{ number_format((float) ($supplier->total_spent ?? 0), 2) }}
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-light supplier-details-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#supplierDetailModal"
                                            data-name="{{ $supplier->name }}"
                                            data-contact="{{ $supplier->contact_person ?: '—' }}"
                                            data-phone="{{ $supplier->phone ?: '—' }}"
                                            data-email="{{ $supplier->email ?: '—' }}"
                                            data-address="{{ $supplier->address ?: 'No address recorded.' }}"
                                            data-notes="{{ $supplier->notes ?: 'No notes recorded.' }}"
                                            data-spent="&#8369;{{ number_format((float) ($supplier->total_spent ?? 0), 2) }}"
                                            data-count="{{ $supplier->purchases_count ?? 0 }}"
                                            data-purchases='@json($recentPos)'
                                            data-orders-url="{{ route('owner.purchases.index', ['supplier' => $supplier->id]) }}"
                                            data-edit-url="{{ route('owner.suppliers.edit', $supplier) }}">
                                            Details
                                        </button>
                                        <a href="{{ route('owner.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit Details</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $suppliers->firstItem() ?? 0 }}&ndash;{{ $suppliers->lastItem() ?? 0 }} of {{ $suppliers->total() }} suppliers
                </div>
                <div>
                    {{ $suppliers->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<!-- Supplier Details & Recent Purchase History Modal -->
<div class="modal fade" id="supplierDetailModal" tabindex="-1" aria-labelledby="supplierDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="eb-section-pill mb-1">Supplier Profile</span>
                    <h5 class="modal-title fw-bold mb-0" id="supplierDetailTitle">Supplier Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 small mb-3">
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Contact Person</div>
                        <div class="fw-semibold text-white" id="supDetailContact">-</div>
                    </div>
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Phone</div>
                        <div class="fw-semibold text-white" id="supDetailPhone">-</div>
                    </div>
                    <div class="col-md-4">
                        <div style="color: #BDBDBD;">Email</div>
                        <div class="fw-semibold text-white" id="supDetailEmail">-</div>
                    </div>
                    <div class="col-md-6">
                        <div style="color: #BDBDBD;">Address</div>
                        <div class="text-white" id="supDetailAddress">-</div>
                    </div>
                    <div class="col-md-3">
                        <div style="color: #BDBDBD;">Total Orders</div>
                        <div class="fw-bold text-white" id="supDetailCount">0</div>
                    </div>
                    <div class="col-md-3">
                        <div style="color: #BDBDBD;">Total Spend</div>
                        <div class="fw-bold text-warning" id="supDetailSpent">₱0.00</div>
                    </div>
                    <div class="col-12">
                        <div style="color: #BDBDBD;">Payment Terms / Notes</div>
                        <div class="p-2 rounded mt-1 text-white" style="background: #141414; border: 1px solid #2A2A2A;" id="supDetailNotes">-</div>
                    </div>
                </div>

                <div class="border-top pt-3" style="border-color: #2A2A2A !important;">
                    <div class="fw-bold text-white mb-2 small text-uppercase" style="letter-spacing: 0.05em;">Recent Purchase History</div>
                    <div class="table-responsive">
                        <table class="table eb-table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Purchase #</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="supDetailPurchasesBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                <a href="#" id="supDetailOrdersLink" class="btn btn-outline-warning">All Supplier Purchases</a>
                <a href="#" id="supDetailEditLink" class="btn btn-primary">Edit Supplier</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('suppliersFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            const skeleton = document.getElementById('suppliersSkeletonWrapper');
            const live = document.getElementById('suppliersLiveContent');
            if (skeleton && live) {
                skeleton.classList.remove('d-none');
                live.style.opacity = '0.35';
                live.style.pointerEvents = 'none';
            }
        });
    }

    document.querySelectorAll('.supplier-details-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('supplierDetailTitle').textContent = btn.dataset.name || 'Supplier Details';
            document.getElementById('supDetailContact').textContent = btn.dataset.contact || '—';
            document.getElementById('supDetailPhone').textContent = btn.dataset.phone || '—';
            document.getElementById('supDetailEmail').textContent = btn.dataset.email || '—';
            document.getElementById('supDetailAddress').textContent = btn.dataset.address || '—';
            document.getElementById('supDetailNotes').textContent = btn.dataset.notes || '—';
            document.getElementById('supDetailCount').textContent = btn.dataset.count || '0';
            document.getElementById('supDetailSpent').textContent = btn.dataset.spent || '₱0.00';

            const ordersLink = document.getElementById('supDetailOrdersLink');
            const editLink = document.getElementById('supDetailEditLink');
            if (ordersLink && btn.dataset.ordersUrl) ordersLink.href = btn.dataset.ordersUrl;
            if (editLink && btn.dataset.editUrl) editLink.href = btn.dataset.editUrl;

            const tbody = document.getElementById('supDetailPurchasesBody');
            tbody.replaceChildren();
            let purchases = [];
            try {
                purchases = JSON.parse(btn.dataset.purchases || '[]');
            } catch (e) {
                purchases = [];
            }

            if (!purchases.length) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 3;
                td.className = 'text-center py-3';
                td.style.color = '#BDBDBD';
                td.textContent = 'No recorded purchase orders for this supplier yet.';
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }

            purchases.forEach(function (po) {
                const tr = document.createElement('tr');
                const tdNum = document.createElement('td');
                tdNum.className = 'font-monospace text-white';
                tdNum.textContent = po.number || '—';

                const tdDate = document.createElement('td');
                tdDate.style.color = '#BDBDBD';
                tdDate.textContent = po.date || '—';

                const tdTotal = document.createElement('td');
                tdTotal.className = 'text-end fw-bold text-warning';
                tdTotal.textContent = '₱' + (po.total || '0.00');

                tr.append(tdNum, tdDate, tdTotal);
                tbody.appendChild(tr);
            });
        });
    });
});
</script>
@endsection
