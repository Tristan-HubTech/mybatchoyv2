@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container py-4">
    <div id="reportsSkeletonWrapper" class="d-none">
        @include('components.skeletons.reports-skeleton')
    </div>
    <div id="reportsLiveContent">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <p class="text-uppercase small fw-bold accent mb-1">Analytics & Auditing</p>
            <h1 class="fw-bold mb-0">Operational Reports</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('owner.reports.export') }}" class="btn btn-outline-primary">
                <span>↓ Download CSV Report</span>
            </a>
            <a href="{{ route('owner.purchases.create') }}" class="btn btn-primary">
                <span>+ New Purchase</span>
            </a>
        </div>
    </div>

    <!-- Core Metric Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-4 h-100 card-hover-subtle">
                <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Est. Inventory Value</small>
                <h3 class="fw-bold mt-2 mb-1 accent" data-counter="{{ $inventoryValue }}" data-prefix="₱" data-decimals="2">₱{{ number_format($inventoryValue, 2) }}</h3>
                <small class="muted">{{ $items->count() }} active tracked items</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 h-100 card-hover-subtle">
                <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Total Purchases</small>
                <h3 class="fw-bold mt-2 mb-1" data-counter="{{ $totalPurchases }}" data-prefix="₱" data-decimals="2">₱{{ number_format($totalPurchases, 2) }}</h3>
                <small class="accent">₱{{ number_format($monthlyPurchases, 2) }} this month</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 h-100 card-hover-subtle">
                <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Stock Out / Usage</small>
                <h3 class="fw-bold mt-2 mb-1" data-counter="{{ $totalUsageQty }}" data-decimals="2">{{ number_format($totalUsageQty, 2) }}</h3>
                <small class="muted">Units dispatched to kitchen</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 h-100 card-hover-subtle">
                <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Recorded Spoilage</small>
                <h3 class="fw-bold mt-2 mb-1 text-danger" data-counter="{{ $totalSpoilageQty }}" data-decimals="2">{{ number_format($totalSpoilageQty, 2) }}</h3>
                <small class="muted">Lost / spoiled units</small>
            </div>
        </div>
    </div>

    <!-- Stock Health Summary Alerts -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Stock Health Indicators</h5>
                    <span class="badge {{ $lowStockItems->count() > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                        {{ $lowStockItems->count() }} Low • {{ $outOfStockItems->count() }} Out
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25);">
                            <small class="muted d-block">Low Stock Warning</small>
                            <span class="fs-4 fw-bold text-warning">{{ $lowStockItems->count() }}</span>
                            <small class="muted d-block">Items below minimum</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25);">
                            <small class="muted d-block">Out of Stock</small>
                            <span class="fs-4 fw-bold text-danger">{{ $outOfStockItems->count() }}</span>
                            <small class="muted d-block">Zero quantity on hand</small>
                        </div>
                    </div>
                </div>

                @if($lowStockItems->isNotEmpty())
                    <div class="mt-3">
                        <small class="text-uppercase fw-bold muted mb-2 d-block">Items needing replenishment:</small>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($lowStockItems->take(6) as $low)
                                <span class="badge bg-secondary">{{ $low->name }} ({{ $low->current_quantity }} {{ $low->unit?->short_name }})</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 h-100">
                <h5 class="fw-bold mb-3">Stock by Category</h5>
                <div class="d-flex flex-column gap-3">
                    @forelse($categories as $category)
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><strong>{{ $category['name'] }}</strong> ({{ $category['items_count'] }} items)</span>
                                <span class="accent fw-bold">{{ number_format($category['total_quantity'], 1) }} total units</span>
                            </div>
                        </div>
                    @empty
                        <p class="muted mb-0">No categories recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tables -->
    <div class="row g-4">
        <!-- Recent Purchases -->
        <div class="col-lg-6">
            <div class="card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Purchase Orders</h5>
                    <a href="{{ route('owner.purchases.index') }}" class="small accent">View all</a>
                </div>
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>PO #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPurchases as $po)
                            <tr>
                                <td><span class="font-monospace small">{{ $po->purchase_number }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($po->purchase_date)->format('M d, Y') }}</td>
                                <td>{{ $po->supplier?->name ?? 'Direct' }}</td>
                                <td class="text-end fw-bold">₱{{ number_format($po->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-3 muted">No purchases recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Stock Activity -->
        <div class="col-lg-6">
            <div class="card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Stock Activity</h5>
                    <a href="{{ route('owner.transactions.index') }}" class="small accent">View all</a>
                </div>
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td>{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('M d, H:i') : '-' }}</td>
                                <td>{{ $tx->inventoryItem?->name ?? '-' }}</td>
                                <td>
                                    @if($tx->transaction_type === 'stock_in')
                                        <span class="badge bg-success">In</span>
                                    @elseif($tx->transaction_type === 'stock_out')
                                        <span class="badge bg-primary">Out</span>
                                    @elseif($tx->transaction_type === 'spoilage')
                                        <span class="badge bg-danger">Spoilage</span>
                                    @else
                                        <span class="badge bg-secondary">Adjust</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $tx->quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-3 muted">No activity recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div> <!-- #reportsLiveContent -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof animateCounters === 'function') {
            animateCounters();
        }
    });
</script>
@endsection
