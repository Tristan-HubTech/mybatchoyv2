@extends('layouts.app')

@section('title', 'Stock Transactions')

@section('content')
@php
    $isOwnerUser = auth()->user()?->role?->name === 'owner';
    $routePrefix = $isOwnerUser ? 'owner' : 'staff';
@endphp

<div id="transactionsSkeletonWrapper" class="d-none">
    @include('components.skeletons.transactions-skeleton')
</div>

<div id="transactionsLiveContent">
    <x-page-header
        section="Operations"
        title="Stock History"
        subtitle="Every time stock was added, used in the kitchen, corrected, or spoiled.">
        <x-slot:actions>
            <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="btn btn-primary">+ Add Stock</a>
            <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="btn btn-outline-light">- Remove Stock</a>
        </x-slot:actions>
    </x-page-header>

    <div class="eb-tabs" role="navigation" aria-label="Stock actions">
        <a href="{{ route($routePrefix . '.transactions.index') }}" class="eb-tab-link active">Stock History</a>
        <a href="{{ route($routePrefix . '.transactions.stockIn') }}" class="eb-tab-link">+ Add Stock</a>
        <a href="{{ route($routePrefix . '.transactions.stockOut') }}" class="eb-tab-link">- Remove Stock</a>
        <a href="{{ route($routePrefix . '.transactions.adjustment') }}" class="eb-tab-link">Correct Stock Quantity</a>
        <a href="{{ route($routePrefix . '.transactions.spoilage') }}" class="eb-tab-link">Record Spoilage</a>
    </div>

    <x-alert-banner />

    <div class="eb-filter-card">
        <form method="GET" action="{{ route($routePrefix . '.transactions.index') }}" id="transactionsFilterForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="txSearchInput">Search Item or Note</label>
                <input id="txSearchInput" type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Item name, code, or reason...">
            </div>
            <div class="col-sm-4 col-md-2">
                <label class="form-label" for="txTypeSelect">Action Type</label>
                <select id="txTypeSelect" name="type" class="form-select">
                    <option value="">All Actions</option>
                    <option value="stock_in" {{ request('type') === 'stock_in' ? 'selected' : '' }}>Added Stock</option>
                    <option value="stock_out" {{ request('type') === 'stock_out' ? 'selected' : '' }}>Removed Stock</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Corrected Stock</option>
                    <option value="spoilage" {{ request('type') === 'spoilage' ? 'selected' : '' }}>Spoiled / Wasted</option>
                </select>
            </div>
            <div class="col-sm-4 col-md-2">
                <label class="form-label" for="txFromInput">From Date</label>
                <input id="txFromInput" type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-sm-4 col-md-2">
                <label class="form-label" for="txToInput">To Date</label>
                <input id="txToInput" type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                @if (request()->filled('search') || request()->filled('type') || request()->filled('from') || request()->filled('to'))
                    <a href="{{ route($routePrefix . '.transactions.index') }}" class="btn btn-outline-light">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Stock Movements">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $transactions->total() }} {{ Str::plural('record', $transactions->total()) }}</span>
        </x-slot:headerActions>

        @if ($transactions->isEmpty())
            <x-empty-state
                title="No stock activities found"
                description="Record a stock addition, kitchen dispatch, or shelf count adjustment to build your history."
                :actionUrl="request()->filled('search') || request()->filled('type') || request()->filled('from') || request()->filled('to') ? route($routePrefix . '.transactions.index') : route($routePrefix . '.transactions.stockIn')"
                :actionLabel="request()->filled('search') || request()->filled('type') || request()->filled('from') || request()->filled('to') ? 'Clear Filters' : '+ Add Stock'" />
        @else
            <div class="table-responsive">
                <table class="table eb-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Item</th>
                            <th>Action</th>
                            <th>Quantity Changed</th>
                            <th>Before &rarr; After</th>
                            <th>Note / Reason</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            @php
                                $unit = $transaction->inventoryItem?->unit?->short_name ?? 'units';
                                $type = $transaction->transaction_type;
                                $isIn = $type === 'stock_in';
                                $isOut = $type === 'stock_out' || $type === 'spoilage';
                                $badgeVariant = match ($type) {
                                    'stock_in' => 'tx-in',
                                    'stock_out' => 'tx-out',
                                    'adjustment' => 'tx-adj',
                                    'spoilage' => 'tx-spoil',
                                    default => 'neutral',
                                };
                                $label = match ($type) {
                                    'stock_in' => 'Added Stock',
                                    'stock_out' => 'Removed Stock',
                                    'adjustment' => 'Corrected Stock',
                                    'spoilage' => 'Spoiled / Wasted',
                                    default => ucfirst(str_replace('_', ' ', $type)),
                                };
                                $qtyNum = rtrim(rtrim(number_format((float) $transaction->quantity, 3), '0'), '.') ?: '0';
                                $prevNum = rtrim(rtrim(number_format((float) $transaction->previous_quantity, 3), '0'), '.') ?: '0';
                                $afterNum = rtrim(rtrim(number_format((float) $transaction->updated_quantity, 3), '0'), '.') ?: '0';
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    <span class="d-block text-white fw-semibold">{{ $transaction->transaction_date ? $transaction->transaction_date->format('M d, Y') : '—' }}</span>
                                    <small style="color: #BDBDBD;">{{ $transaction->transaction_date ? $transaction->transaction_date->format('h:i A') : '' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-white">{{ $transaction->inventoryItem?->name ?? 'Deleted Item' }}</div>
                                    @if ($transaction->inventoryItem?->sku)
                                        <div class="small" style="color: #BDBDBD; font-size: 0.74rem;">{{ $transaction->inventoryItem->sku }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :variant="$badgeVariant" :label="$label" />
                                </td>
                                <td class="fw-bold {{ $isIn ? 'text-success' : ($isOut ? 'text-danger' : 'text-warning') }}">
                                    {{ $isIn ? '+' : ($isOut ? '-' : '') }}{{ $qtyNum }} {{ $unit }}
                                </td>
                                <td class="text-nowrap small" style="color: #BDBDBD;">
                                    <span>{{ $prevNum }}</span>
                                    <span class="mx-1">&rarr;</span>
                                    <strong class="text-white">{{ $afterNum }} {{ $unit }}</strong>
                                </td>
                                <td class="small" style="color: #BDBDBD; max-width: 240px;">
                                    {{ $transaction->reason ?: 'No notes added' }}
                                </td>
                                <td class="small text-white">{{ $transaction->user?->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $transactions->firstItem() ?? 0 }}&ndash;{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('transactionsFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            const skeleton = document.getElementById('transactionsSkeletonWrapper');
            const live = document.getElementById('transactionsLiveContent');
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
