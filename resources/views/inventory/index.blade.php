@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<style>
    .inventory-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
    }
    .inventory-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: end;
    }
    .inventory-filter {
        flex: 1 1 220px;
        min-width: 0;
    }
    .inventory-table-wrap {
        overflow-x: auto;
        width: 100%;
        border-radius: 18px;
        border: 1px solid var(--border);
        background: var(--surface);
    }
    .inventory-table {
        width: 100%;
        min-width: 900px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--surface);
        color: var(--text);
    }
    .inventory-table thead th {
        background: #151515;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
        font-size: 0.76rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.9rem 1rem;
    }
    .inventory-table tbody td {
        padding: 0.95rem 1rem;
        color: var(--text);
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        background: rgba(27, 27, 27, 0.45);
    }
    .inventory-table tbody tr:last-child td { border-bottom: none; }
    .inventory-table tbody tr:hover td {
        background: rgba(229, 57, 53, 0.08);
    }
    .inventory-table .item-name {
        font-weight: 600;
        color: #FFFFFF;
    }
    .inventory-table .cell-muted {
        color: var(--muted);
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 102px;
        padding: 0.38rem 0.7rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .status-badge.available {
        background: rgba(76, 175, 80, 0.14);
        color: #81C784;
        border: 1px solid rgba(76, 175, 80, 0.35);
    }
    .status-badge.low-stock {
        background: rgba(255, 193, 7, 0.14);
        color: #FFD54F;
        border: 1px solid rgba(255, 193, 7, 0.4);
    }
    .status-badge.out-of-stock {
        background: rgba(229, 57, 53, 0.16);
        color: #EF9A9A;
        border: 1px solid rgba(229, 57, 53, 0.45);
    }
    .inventory-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--muted);
        background: rgba(27, 27, 27, 0.4);
    }
    @media (max-width: 767.98px) {
        .inventory-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        .inventory-filter {
            width: 100%;
        }
        .inventory-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
</style>

<div class="container py-5">
    <div id="inventorySkeletonWrapper" class="d-none">
        @include('components.skeletons.inventory-skeleton')
    </div>
    <div id="inventoryLiveContent">
    <div class="d-flex justify-content-between align-items-center mb-4 inventory-header">
        <div>
            <p class="text-uppercase small fw-bold accent mb-1">Inventory</p>
            <h1 class="fw-bold mb-0">Stock overview</h1>
        </div>
        <a href="{{ route('owner.inventory.create') }}" class="btn btn-primary">Add item</a>
    </div>

    <div class="card inventory-panel p-4 mb-4">
        <form method="GET" class="inventory-toolbar" id="inventoryFilterForm">
            <div class="inventory-filter">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or SKU">
            </div>
            <div class="inventory-filter">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 140px;">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="inventory-table-wrap">
        <table class="inventory-table" aria-label="Inventory table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Min</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $rawQuantity = (float) ($item->current_quantity ?? 0);
                        $rawMin = (float) ($item->minimum_stock_level ?? 0);
                        $formattedQty = $rawQuantity == floor($rawQuantity) ? number_format($rawQuantity, 0, '.', '') : rtrim(rtrim(number_format($rawQuantity, 3, '.', ''), '0'), '.');
                        $formattedMin = $rawMin == floor($rawMin) ? number_format($rawMin, 0, '.', '') : rtrim(rtrim(number_format($rawMin, 3, '.', ''), '0'), '.');
                        $unit = $item->unit?->short_name ?: '-';
                        $status = $item->current_quantity == 0 ? 'Out of stock' : ($item->current_quantity <= $item->minimum_stock_level ? 'Low stock' : 'Available');
                        $statusClass = $status === 'Available' ? 'available' : ($status === 'Low stock' ? 'low-stock' : 'out-of-stock');
                    @endphp
                    <tr>
                        <td class="item-name">{{ $item->name ?? 'Unnamed item' }}</td>
                        <td class="cell-muted">{{ $item->sku ?? '-' }}</td>
                        <td>{{ $formattedQty }} {{ $unit }}</td>
                        <td>{{ $formattedMin }}</td>
                        <td>
                            <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
                        </td>
                        <td>{{ $item->category?->name ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('owner.inventory.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="inventory-empty">No inventory items match your search. Try a different keyword or category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div> <!-- #inventoryLiveContent -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('inventoryFilterForm');
        if (form) {
            form.addEventListener('submit', function () {
                const skeleton = document.getElementById('inventorySkeletonWrapper');
                const live = document.getElementById('inventoryLiveContent');
                if (skeleton && live) {
                    skeleton.classList.remove('d-none');
                    live.style.opacity = '0.35';
                    live.style.pointerEvents = 'none';
                }
            });
        }
    });
</script>
@endsection
