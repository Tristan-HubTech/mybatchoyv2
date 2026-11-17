@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
@php
    $isOwnerUser = auth()->user()?->role?->name === 'owner';
    $routePrefix = $isOwnerUser ? 'owner' : 'staff';
@endphp

<div id="inventorySkeletonWrapper" class="d-none">
    @include('components.skeletons.inventory-skeleton')
</div>

<div id="inventoryLiveContent">
    <x-page-header
        section="Inventory"
        title="Manage Stock"
        subtitle="Look up any ingredient or supply, check how much is left right now, and spot items running low.">
        <x-slot:actions>
            <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="btn btn-outline-primary">
                + Add Stock
            </a>
            @if ($isOwnerUser)
                <a href="{{ route('owner.inventory.create') }}" class="btn btn-primary">
                    + Add New Item
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-alert-banner />

    <div class="eb-filter-card">
        <form method="GET" action="{{ route($routePrefix . '.inventory.index') }}" id="inventoryFilterForm" class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label" for="invSearchInput">Find an Item</label>
                <input id="invSearchInput" type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search item name or code (e.g. Pork Belly, Miki)...">
            </div>
            <div class="col-sm-6 col-md-3 col-lg-3">
                <label class="form-label" for="invCategorySelect">Group / Category</label>
                <select id="invCategorySelect" name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2 col-lg-3">
                <label class="form-label" for="invStatusSelect">Stock Status</label>
                <select id="invStatusSelect" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                @if (request()->filled('search') || request()->filled('category') || request()->filled('status'))
                    <a href="{{ route($routePrefix . '.inventory.index') }}" class="btn btn-outline-light">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Inventory Items">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $items->total() }} {{ Str::plural('item', $items->total()) }} matched</span>
        </x-slot:headerActions>

        @if ($items->isEmpty())
            <x-empty-state
                title="No inventory items found"
                description="Try clearing your filters or add a new ingredient or supply item to track stock."
                :actionUrl="request()->filled('search') || request()->filled('category') || request()->filled('status') ? route($routePrefix . '.inventory.index') : ($isOwnerUser ? route('owner.inventory.create') : null)"
                :actionLabel="request()->filled('search') || request()->filled('category') || request()->filled('status') ? 'Clear Filters' : ($isOwnerUser ? '+ Add New Item' : null)" />
        @else
            <div class="table-responsive">
                <table class="table eb-table eb-responsive-table table-hover align-middle inventory-table" aria-label="Inventory table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Item Code</th>
                            <th>Current Stock</th>
                            <th>Minimum Level</th>
                            <th>Status</th>
                            <th>Category</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $unitShort = $item->unit?->short_name ?? 'units';
                                $statusVariant = $item->isOutOfStock() ? 'danger' : ($item->isLowStock() ? 'warning' : 'success');
                                $stockInUrl = route($routePrefix . '.transactions.stockIn', ['item' => $item->id]);
                                $editUrl = $isOwnerUser ? route('owner.inventory.edit', $item) : '';
                            @endphp
                            <tr>
                                <td class="eb-cell-primary" data-label="Item">
                                    <div class="fw-bold text-white">{{ $item->name }}</div>
                                    @if ($item->description)
                                        <div class="small" style="color: #BDBDBD; font-size: 0.76rem;">{{ Str::limit($item->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="small" data-label="Code" style="color: #BDBDBD;">{{ $item->sku ?: '—' }}</td>
                                <td data-label="Current Stock">
                                    <span class="fw-bold {{ $item->isOutOfStock() ? 'text-danger' : ($item->isLowStock() ? 'text-warning' : 'text-white') }}">
                                        {{ rtrim(rtrim(number_format((float) $item->current_quantity, 3), '0'), '.') ?: '0' }}
                                    </span>
                                    <span class="small" style="color: #BDBDBD;">{{ $unitShort }}</span>
                                </td>
                                <td class="small" data-label="Min Level" style="color: #BDBDBD;">
                                    {{ rtrim(rtrim(number_format((float) $item->minimum_stock_level, 3), '0'), '.') ?: '0' }} {{ $unitShort }}
                                </td>
                                <td data-label="Status">
                                    <x-status-badge :variant="$statusVariant" :label="$item->status" />
                                </td>
                                <td class="small" data-label="Category" style="color: #BDBDBD;">{{ $item->category?->name ?? 'Uncategorized' }}</td>
                                <td class="text-end eb-cell-actions">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-light item-details-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#itemDetailModal"
                                            data-name="{{ $item->name }}"
                                            data-sku="{{ $item->sku ?: 'Not assigned' }}"
                                            data-category="{{ $item->category?->name ?? 'Uncategorized' }}"
                                            data-unit="{{ $item->unit?->name ? $item->unit->name . ' (' . $unitShort . ')' : $unitShort }}"
                                            data-qty="{{ (rtrim(rtrim(number_format((float) $item->current_quantity, 3), '0'), '.') ?: '0') . ' ' . $unitShort }}"
                                            data-min="{{ (rtrim(rtrim(number_format((float) $item->minimum_stock_level, 3), '0'), '.') ?: '0') . ' ' . $unitShort }}"
                                            data-reorder="{{ (rtrim(rtrim(number_format((float) $item->reorder_level, 3), '0'), '.') ?: '0') . ' ' . $unitShort }}"
                                            data-status="{{ $item->status }}"
                                            data-description="{{ $item->description ?: 'No additional notes recorded.' }}"
                                            data-stock-in-url="{{ $stockInUrl }}"
                                            data-edit-url="{{ $editUrl }}">
                                            Details
                                        </button>
                                        <a href="{{ $stockInUrl }}" class="btn btn-sm btn-primary">+ Add Stock</a>
                                        @if ($isOwnerUser)
                                            <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $items->firstItem() ?? 0 }}&ndash;{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} items
                </div>
                <div>
                    {{ $items->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="itemDetailModal" tabindex="-1" aria-labelledby="itemDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="eb-section-pill mb-1">Inventory Item</span>
                    <h5 class="modal-title fw-bold mb-0" id="itemDetailTitle">Item Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 small">
                    <div class="col-6">
                        <div style="color: #BDBDBD;">Item Code / SKU</div>
                        <div class="fw-semibold text-white" id="detailSku">-</div>
                    </div>
                    <div class="col-6">
                        <div style="color: #BDBDBD;">Status</div>
                        <div class="fw-bold text-warning" id="detailStatus">-</div>
                    </div>
                    <div class="col-6">
                        <div style="color: #BDBDBD;">Category</div>
                        <div class="fw-semibold text-white" id="detailCategory">-</div>
                    </div>
                    <div class="col-6">
                        <div style="color: #BDBDBD;">Unit</div>
                        <div class="fw-semibold text-white" id="detailUnit">-</div>
                    </div>
                    <div class="col-4">
                        <div style="color: #BDBDBD;">Current Stock</div>
                        <div class="fw-bold text-white" id="detailQty">-</div>
                    </div>
                    <div class="col-4">
                        <div style="color: #BDBDBD;">Minimum Level</div>
                        <div class="fw-semibold text-white" id="detailMin">-</div>
                    </div>
                    <div class="col-4">
                        <div style="color: #BDBDBD;">Reorder Level</div>
                        <div class="fw-semibold text-white" id="detailReorder">-</div>
                    </div>
                    <div class="col-12">
                        <div style="color: #BDBDBD;">Notes / Storage Instructions</div>
                        <div class="p-2 rounded mt-1 text-white" style="background: #141414; border: 1px solid #2A2A2A;" id="detailDesc">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                @if ($isOwnerUser)
                    <a href="#" id="detailEditLink" class="btn btn-outline-warning d-none">Edit Item</a>
                @endif
                <a href="#" id="detailStockInLink" class="btn btn-primary">+ Add Stock</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inventoryFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            if (typeof window.activatePageSkeleton === 'function') {
                window.activatePageSkeleton('inventorySkeletonWrapper', 'inventoryLiveContent');
            } else {
                const skeleton = document.getElementById('inventorySkeletonWrapper');
                const live = document.getElementById('inventoryLiveContent');
                if (skeleton && live) {
                    live.classList.add('d-none');
                    skeleton.classList.remove('d-none');
                }
            }
        });
    }

    document.querySelectorAll('.item-details-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('itemDetailTitle').textContent = btn.dataset.name || 'Item Details';
            document.getElementById('detailSku').textContent = btn.dataset.sku || '—';
            document.getElementById('detailStatus').textContent = btn.dataset.status || '—';
            document.getElementById('detailCategory').textContent = btn.dataset.category || 'Uncategorized';
            document.getElementById('detailUnit').textContent = btn.dataset.unit || '—';
            document.getElementById('detailQty').textContent = btn.dataset.qty || '0';
            document.getElementById('detailMin').textContent = btn.dataset.min || '0';
            document.getElementById('detailReorder').textContent = btn.dataset.reorder || '0';
            document.getElementById('detailDesc').textContent = btn.dataset.description || 'None';

            const stockInLink = document.getElementById('detailStockInLink');
            if (stockInLink && btn.dataset.stockInUrl) {
                stockInLink.href = btn.dataset.stockInUrl;
            }

            const editLink = document.getElementById('detailEditLink');
            if (editLink) {
                if (btn.dataset.editUrl) {
                    editLink.href = btn.dataset.editUrl;
                    editLink.classList.remove('d-none');
                } else {
                    editLink.classList.add('d-none');
                }
            }
        });
    });
});
</script>
@endsection
