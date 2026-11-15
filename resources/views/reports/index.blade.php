@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div id="reportsSkeletonWrapper" class="d-none">
    @include('components.skeletons.reports-skeleton')
</div>

<div id="reportsLiveContent">
    <x-page-header
        section="Analytics"
        title="Operational Reports"
        subtitle="Check stock value, supplier expenses, kitchen stock usage, and spoilage losses.">
        <x-slot:actions>
            <a href="{{ route('owner.reports.export', request()->only(['from', 'to'])) }}" class="btn btn-outline-warning">
                &darr; Download CSV Spreadsheet
            </a>
            <a href="{{ route('owner.purchases.create') }}" class="btn btn-primary">
                + Record Purchase
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="eb-filter-card">
        <form method="GET" action="{{ route('owner.reports.index') }}" id="reportsFilterForm" class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-4">
                <label class="form-label" for="reportFrom">From Date (optional)</label>
                <input id="reportFrom" type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label" for="reportTo">To Date (optional)</label>
                <input id="reportTo" type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Apply Dates</button>
                @if (request()->filled('from') || request()->filled('to'))
                    <a href="{{ route('owner.reports.index') }}" class="btn btn-outline-light">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4 Operational KPI Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="eb-kpi-card" style="cursor: default;">
                <div class="eb-kpi-circle kpi-yellow">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Est. Inventory Value</div>
                    <div class="eb-kpi-value" data-counter="{{ $inventoryValue }}" data-prefix="₱" data-decimals="2">&#8369;{{ number_format($inventoryValue, 2) }}</div>
                    <div class="eb-kpi-meta meta-up">
                        <span>{{ $items->count() }} {{ Str::plural('item', $items->count()) }} on hand</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="eb-kpi-card" style="cursor: default;">
                <div class="eb-kpi-circle kpi-green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Total Purchases</div>
                    <div class="eb-kpi-value" data-counter="{{ $totalPurchases }}" data-prefix="₱" data-decimals="2">&#8369;{{ number_format($totalPurchases, 2) }}</div>
                    <div class="eb-kpi-meta meta-warn">
                        <span>&#8369;{{ number_format($monthlyPurchases, 2) }} spent this month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="eb-kpi-card" style="cursor: default;">
                <div class="eb-kpi-circle kpi-green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Removed for Kitchen Use</div>
                    <div class="eb-kpi-value" data-counter="{{ $totalUsageQty }}" data-decimals="2">{{ number_format($totalUsageQty, 2) }}</div>
                    <div class="eb-kpi-meta meta-up">
                        <span>Units issued to operations</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="eb-kpi-card" style="cursor: default;">
                <div class="eb-kpi-circle kpi-red">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Recorded Spoilage</div>
                    <div class="eb-kpi-value text-danger" data-counter="{{ $totalSpoilageQty }}" data-decimals="2">{{ number_format($totalSpoilageQty, 2) }}</div>
                    <div class="eb-kpi-meta meta-danger">
                        <span>Units lost to waste or damage</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Health & Category Summary -->
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <span>Stock Health Summary</span>
                    </div>
                    <x-status-badge
                        :variant="$lowStockItems->count() > 0 || $outOfStockItems->count() > 0 ? 'warning' : 'success'"
                        :label="$lowStockItems->count() . ' Low • ' . $outOfStockItems->count() . ' Out'" />
                </div>
                <div class="eb-panel-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: #141414; border: 1px solid #2A2A2A;">
                                <div class="small" style="color: #BDBDBD;">Running Low</div>
                                <div class="fs-4 fw-bold text-warning">{{ $lowStockItems->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: #141414; border: 1px solid #2A2A2A;">
                                <div class="small" style="color: #BDBDBD;">Out of Stock</div>
                                <div class="fs-4 fw-bold text-danger">{{ $outOfStockItems->count() }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($lowStockItems->isNotEmpty())
                        <div class="small fw-semibold mb-2" style="color: #BDBDBD;">Items needing immediate attention:</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($lowStockItems->take(6) as $low)
                                <x-status-badge variant="warning" :label="$low->name . ' (' . rtrim(rtrim(number_format((float) $low->current_quantity, 2), '0'), '.') . ' ' . ($low->unit?->short_name ?? '') . ')'" />
                            @endforeach
                        </div>
                    @else
                        <div class="small" style="color: #4CAF50;">All tracked items currently have healthy shelf quantities.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <span>Stock by Category</span>
                    </div>
                    <span class="small" style="color: #BDBDBD;">Current quantities grouped by ingredient category</span>
                </div>
                <div class="eb-panel-body">
                    @if (empty($categories))
                        <x-empty-state title="No categories configured" description="Add ingredient categories in Settings to organize stock reports." />
                    @else
                        <div class="row g-2">
                            @foreach ($categories as $category)
                                <div class="col-sm-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded-3" style="background: #141414; border: 1px solid #2A2A2A;">
                                        <div>
                                            <div class="fw-bold text-white small">{{ $category['name'] }}</div>
                                            <div class="small" style="color: #BDBDBD; font-size: 0.74rem;">{{ $category['items_count'] }} {{ Str::plural('item', $category['items_count']) }}</div>
                                        </div>
                                        <span class="fw-bold text-warning small">{{ number_format($category['total_quantity'], 1) }} units</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Purchases & Stock Activity -->
    <div class="row g-3">
        <div class="col-lg-6">
            <x-table-card title="Recent Purchases" :actionUrl="route('owner.purchases.index')" actionLabel="View All &rarr;">
                @if ($recentPurchases->isEmpty())
                    <x-empty-state title="No recent purchases" description="No supplier orders matched this date range." />
                @else
                    <div class="table-responsive">
                        <table class="table eb-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentPurchases as $po)
                                    <tr>
                                        <td class="font-monospace small text-white fw-semibold">{{ $po->purchase_number }}</td>
                                        <td class="small" style="color: #BDBDBD;">{{ \Carbon\Carbon::parse($po->purchase_date)->format('M d, Y') }}</td>
                                        <td class="small text-white">{{ $po->supplier?->name ?? 'Direct' }}</td>
                                        <td class="text-end fw-bold text-warning">&#8369;{{ number_format((float) $po->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-table-card>
        </div>

        <div class="col-lg-6">
            <x-table-card title="Recent Stock Movements" :actionUrl="route('owner.transactions.index')" actionLabel="View All &rarr;">
                @if ($recentTransactions->isEmpty())
                    <x-empty-state title="No stock movements" description="No stock additions or removals matched this date range." />
                @else
                    <div class="table-responsive">
                        <table class="table eb-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Action</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTransactions as $tx)
                                    @php
                                        $pillVariant = match ($tx->transaction_type) {
                                            'stock_in' => 'tx-in',
                                            'stock_out' => 'tx-out',
                                            'spoilage' => 'tx-spoil',
                                            default => 'tx-adj',
                                        };
                                        $pillLabel = match ($tx->transaction_type) {
                                            'stock_in' => 'Stock In',
                                            'stock_out' => 'Stock Out',
                                            'spoilage' => 'Spoilage',
                                            default => 'Adjustment',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="small" style="color: #BDBDBD;">{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('M d, h:i A') : '—' }}</td>
                                        <td class="fw-semibold text-white">{{ $tx->inventoryItem?->name ?? 'Deleted Item' }}</td>
                                        <td><x-status-badge :variant="$pillVariant" :label="$pillLabel" /></td>
                                        <td class="text-end fw-bold text-white">{{ rtrim(rtrim(number_format((float) $tx->quantity, 2), '0'), '.') ?: '0' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-table-card>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof animateCounters === 'function') {
        animateCounters();
    }

    const form = document.getElementById('reportsFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            const skeleton = document.getElementById('reportsSkeletonWrapper');
            const live = document.getElementById('reportsLiveContent');
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
