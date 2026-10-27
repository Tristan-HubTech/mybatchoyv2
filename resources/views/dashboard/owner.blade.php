@extends('layouts.app')

@section('title', 'Owner Dashboard Overview')

@section('content')
<div class="container-fluid px-0">
    <div id="dashboardSkeletonWrapper" class="d-none">
        @include('components.skeletons.dashboard-skeleton')
    </div>
    <div id="dashboardLiveContent">
        <!-- Header: Overview, Role Identity & Controls -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-uppercase small fw-bold accent" style="letter-spacing: 0.06em;">Internal Operations</span>
                <span class="badge badge-primary-subtle">Owner / Admin</span>
                <span class="muted small d-none d-sm-inline">• {{ $currentUser->name }}</span>
            </div>
            <h1 class="fw-bold mb-0 page-title">Dashboard Overview</h1>
        </div>

        <!-- Date Range Filter & Refresh Control -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="{{ route('owner.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2" id="filterForm">
                <div class="input-group input-group-sm">
                    <label class="input-group-text bg-surface border-border text-muted" for="rangeSelect">
                        <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                        </svg>
                    </label>
                    <select name="range" id="rangeSelect" class="form-select form-select-sm" onchange="handleRangeChange(this.value)" aria-label="Select date range filter">
                        <option value="today" {{ $rangeKey === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7days" {{ $rangeKey === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ $rangeKey === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $rangeKey === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="custom" {{ $rangeKey === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div id="customDatesWrapper" class="d-flex align-items-center gap-2 {{ $rangeKey === 'custom' ? '' : 'd-none' }}">
                    <input type="date" name="from" value="{{ request('from', $startDate->toDateString()) }}" class="form-control form-control-sm" aria-label="Start Date">
                    <span class="muted small">to</span>
                    <input type="date" name="to" value="{{ request('to', $endDate->toDateString()) }}" class="form-control form-control-sm" aria-label="End Date">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </form>

            <a href="{{ route('owner.dashboard', array_filter(['range' => $rangeKey, 'from' => request('from'), 'to' => request('to')])) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Refresh Dashboard Data">
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                </svg>
                <span>Refresh</span>
            </a>
        </div>
    </div>

    <!-- Summary Cards (5 Metrics) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Catalog Items -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Total Items</small>
                    <div class="rounded-3 p-2" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border);">
                        <svg width="16" height="16" fill="var(--muted)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l6.229 2.492a.5.5 0 0 1 .314.464v8.572a.5.5 0 0 1-.314.464l-6.229 2.492a1.5 1.5 0 0 1-1.114 0L1.214 12.18a.5.5 0 0 1-.314-.464V3.14a.5.5 0 0 1 .314-.464L7.443.184z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-white" data-counter="{{ $totalItemsCount }}">{{ number_format($totalItemsCount) }}</div>
                <small class="muted">Tracked ingredients & supplies</small>
            </div>
        </div>

        <!-- 2. Items in Stock -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Items In Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(76, 175, 80, 0.12); border: 1px solid rgba(76, 175, 80, 0.25);">
                        <svg width="16" height="16" fill="var(--success)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-success" data-counter="{{ $inStockCount }}">{{ number_format($inStockCount) }}</div>
                <small class="muted">Above minimum threshold</small>
            </div>
        </div>

        <!-- 3. Low Stock Items (Yellow Warning) -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Low Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(255, 193, 7, 0.12); border: 1px solid rgba(255, 193, 7, 0.25);">
                        <svg width="16" height="16" fill="var(--warning)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-warning" data-counter="{{ $lowStockCount }}">{{ number_format($lowStockCount) }}</div>
                <small class="muted">{{ $lowStockCount > 0 ? 'Approaching depletion' : 'Optimal buffer levels' }}</small>
            </div>
        </div>

        <!-- 4. Out of Stock (Red Alert) -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Out of Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(229, 57, 53, 0.14); border: 1px solid rgba(229, 57, 53, 0.3);">
                        <svg width="16" height="16" fill="var(--danger)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-danger" data-counter="{{ $outOfStockCount }}">{{ number_format($outOfStockCount) }}</div>
                <small class="muted">{{ $outOfStockCount > 0 ? 'Needs immediate restock' : 'Zero items depleted' }}</small>
            </div>
        </div>

        <!-- 5. Period Purchases (Owner Only) -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Purchases ({{ $rangeKey === 'today' ? 'Today' : 'Period' }})</small>
                    <div class="rounded-3 p-2" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border);">
                        <svg width="16" height="16" fill="var(--muted)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-white" data-counter="{{ $purchasingExpenses }}" data-prefix="₱" data-decimals="2">₱{{ number_format($purchasingExpenses, 2) }}</div>
                <small class="muted">{{ $purchasesCount }} purchase order(s)</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small fw-semibold text-uppercase muted" style="letter-spacing: 0.05em;">Quick Actions</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('owner.inventory.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>View Inventory</span>
                </a>
                <a href="{{ route('owner.transactions.stockIn') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                    <span>+ Record Stock In</span>
                </a>
                <a href="{{ route('owner.transactions.stockOut') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>− Record Stock Out</span>
                </a>
                <a href="{{ route('owner.purchases.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>View Purchases</span>
                </a>
                <a href="{{ route('owner.reports.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>View Reports</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Stock Movements & Inventory Status Breakdown -->
    <div class="row g-4 mb-4">
        <!-- Stock Movements Chart -->
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">Stock Movement Trends</h5>
                        <p class="muted small mb-0">Stock In (supplies received) vs. Stock Out (kitchen cooking dispatch) for {{ $rangeLabel }}</p>
                    </div>
                    <span class="badge badge-secondary-subtle">Units / Quantity</span>
                </div>

                @if($hasStockMovement)
                    <div class="position-relative" style="min-height: 270px; height: 270px;">
                        <canvas id="stockMovementChart" aria-label="Stock movement line chart comparing Stock In and Stock Out" role="img"></canvas>
                    </div>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center p-5 rounded-3 border border-dashed border-border" style="min-height: 270px;">
                        <svg width="32" height="32" fill="var(--muted)" viewBox="0 0 16 16" class="mb-2" aria-hidden="true">
                            <path d="M0 0h1v15h15v1H0V0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.707l-4.146 4.147a.5.5 0 0 1-.708 0L7 6.707l-3.646 3.647a.5.5 0 0 1-.708-.708l4-4a.5.5 0 0 1 .708 0L9 7.293l3.646-3.647H10.5a.5.5 0 0 1-.5-.5z"/>
                        </svg>
                        <p class="muted mb-1 fw-semibold">No Stock Movements in {{ $rangeLabel }}</p>
                        <small class="muted">Stock-in and stock-out activity recorded during this period will plot automatically.</small>
                    </div>
                @endif

                <!-- Accessible Fallback Summary for Screen Readers -->
                <details class="mt-3">
                    <summary class="small muted cursor-pointer">View accessible movement data table</summary>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Stock In</th>
                                    <th class="text-end">Stock Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chartLabels as $index => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end text-success">{{ $stockInData[$index] ?? 0 }}</td>
                                        <td class="text-end text-primary-soft">{{ $stockOutData[$index] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>

        <!-- Inventory Status Breakdown Chart -->
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Stock Health Status</h5>
                        <p class="muted small mb-0">Current catalog availability distribution</p>
                    </div>
                </div>

                @if($totalItemsCount > 0)
                    <div class="position-relative d-flex align-items-center justify-content-center" style="min-height: 220px; height: 220px;">
                        <canvas id="inventoryStatusChart" aria-label="Inventory status doughnut chart showing in-stock, low-stock, and out-of-stock proportions" role="img"></canvas>
                    </div>
                    <div class="mt-3 pt-3 border-top border-border">
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span class="d-flex align-items-center gap-2"><span class="badge-dot" style="width:10px;height:10px;border-radius:50%;background:#22C55E;display:inline-block;"></span>In Stock (Healthy)</span>
                            <span class="fw-bold text-success">{{ $inStockCount }} items ({{ $totalItemsCount > 0 ? round(($inStockCount / $totalItemsCount) * 100) : 0 }}%)</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span class="d-flex align-items-center gap-2"><span class="badge-dot" style="width:10px;height:10px;border-radius:50%;background:#F59E0B;display:inline-block;"></span>Low Stock (Warning)</span>
                            <span class="fw-bold text-warning">{{ $lowStockCount }} items</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span class="d-flex align-items-center gap-2"><span class="badge-dot" style="width:10px;height:10px;border-radius:50%;background:#EF4444;display:inline-block;"></span>Out of Stock (Depleted)</span>
                            <span class="fw-bold text-danger">{{ $outOfStockCount }} items</span>
                        </div>
                    </div>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center p-4 text-center" style="min-height: 220px;">
                        <p class="muted mb-0">No inventory items catalogued yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Purchasing Expenses Trend (Owner Only) -->
    <div class="card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h5 class="fw-bold mb-1">Purchasing Expense Timeline</h5>
                <p class="muted small mb-0">Supplier replenishment expenses in Philippine Pesos (₱) for {{ $rangeLabel }}</p>
            </div>
            <div class="text-md-end">
                <span class="fs-5 fw-bold accent">₱{{ number_format($purchasingExpenses, 2) }}</span>
                <small class="muted d-block">{{ $purchasesCount }} Purchase Order(s)</small>
            </div>
        </div>

        @if($hasPurchasesInPeriod)
            <div class="position-relative" style="min-height: 220px; height: 220px;">
                <canvas id="purchaseExpenseChart" aria-label="Purchasing expense trend line chart over selected time period" role="img"></canvas>
            </div>
        @else
            <div class="d-flex flex-column align-items-center justify-content-center p-4 rounded-3 border border-dashed border-border" style="min-height: 180px;">
                <p class="muted mb-1 fw-semibold">No Purchase Orders in {{ $rangeLabel }}</p>
                <small class="muted">Recorded purchase orders will display here with exact daily costs.</small>
            </div>
        @endif

        <details class="mt-3">
            <summary class="small muted cursor-pointer">View accessible purchasing expense table</summary>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-striped mb-0 small">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Total Expense (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseLabels as $index => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end fw-semibold">₱{{ number_format($purchaseSpendData[$index] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <!-- Inventory Alerts (Sorted by Urgency) -->
    <div class="card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Inventory Stock Alerts</h5>
                <small class="muted">Items at or below minimum operating stock thresholds (sorted by urgency)</small>
            </div>
            <a href="{{ route('owner.inventory.index') }}" class="btn btn-sm btn-outline-primary">
                View Full Inventory ({{ $totalItemsCount }}) →
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th class="text-end">Current Stock</th>
                        <th class="text-end">Min. Threshold</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($urgentItems as $urgent)
                        <tr>
                            <td>
                                <strong class="d-block">{{ $urgent->name }}</strong>
                                <span class="font-monospace small muted">{{ $urgent->sku }}</span>
                            </td>
                            <td><span class="badge badge-secondary-subtle">{{ $urgent->category?->name ?? 'General' }}</span></td>
                            <td class="text-end">
                                <span class="fw-bold {{ $urgent->current_quantity <= 0 ? 'text-danger' : 'text-warning' }}">
                                    {{ number_format($urgent->current_quantity, 2) }} {{ $urgent->unit?->short_name }}
                                </span>
                            </td>
                            <td class="text-end muted">
                                {{ number_format($urgent->minimum_stock_level, 2) }} {{ $urgent->unit?->short_name }}
                            </td>
                            <td class="text-center">
                                @if($urgent->current_quantity <= 0)
                                    <span class="badge badge-danger-subtle">Out of Stock</span>
                                @else
                                    <span class="badge badge-warning-subtle">Low Stock</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('owner.inventory.edit', $urgent) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Item Details">
                                    Edit
                                </a>
                                <a href="{{ route('owner.transactions.stockIn') }}" class="btn btn-sm btn-primary py-1 px-2" title="Restock Item">
                                    + Stock In
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 muted">
                                <svg width="24" height="24" fill="var(--success)" viewBox="0 0 16 16" class="mb-2 d-block mx-auto" aria-hidden="true">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                </svg>
                                <strong>All items meet or exceed required minimum stock levels.</strong>
                                <div class="small muted mt-1">No low-stock or out-of-stock items requiring urgent intervention.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Row: Recent Transactions & Purchases -->
    <div class="row g-4">
        <!-- Recent Transactions -->
        <div class="col-lg-7">
            <div class="card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Recent Stock Transactions</h5>
                        <small class="muted">Live inventory movement audit trail</small>
                    </div>
                    <a href="{{ route('owner.transactions.index') }}" class="small accent">All Transactions →</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle small">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th>Change</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>
                                        <span class="d-block">{{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-' }}</span>
                                        <span class="muted small">{{ $tx->transaction_date ? $tx->transaction_date->format('H:i') : '' }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $tx->inventoryItem?->name ?? 'Item' }}</strong>
                                        @if($tx->reason)
                                            <span class="d-block muted small text-truncate" style="max-width: 140px;">{{ $tx->reason }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tx->transaction_type === 'stock_in')
                                            <span class="badge badge-success-subtle">Stock In</span>
                                        @elseif($tx->transaction_type === 'stock_out')
                                            <span class="badge badge-primary-subtle">Stock Out</span>
                                        @elseif($tx->transaction_type === 'spoilage')
                                            <span class="badge badge-danger-subtle">Spoilage</span>
                                        @else
                                            <span class="badge badge-secondary-subtle">{{ ucfirst($tx->transaction_type) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        @if($tx->transaction_type === 'stock_in')
                                            <span class="text-success">+{{ $tx->quantity }}</span>
                                        @elseif($tx->transaction_type === 'stock_out' || $tx->transaction_type === 'spoilage')
                                            <span class="text-danger">-{{ $tx->quantity }}</span>
                                        @else
                                            <span>{{ $tx->quantity }}</span>
                                        @endif
                                    </td>
                                    <td class="muted font-monospace small">
                                        {{ number_format($tx->previous_quantity, 1) }} → {{ number_format($tx->updated_quantity, 1) }}
                                    </td>
                                    <td>
                                        <span class="muted">{{ $tx->user?->name ?? 'System' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 muted">No stock transactions recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Purchases -->
        <div class="col-lg-5">
            <div class="card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Recent Purchases</h5>
                        <small class="muted">Latest supplier purchase orders</small>
                    </div>
                    <a href="{{ route('owner.purchases.index') }}" class="small accent">All Purchases →</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle small">
                        <thead>
                            <tr>
                                <th>PO & Date</th>
                                <th>Supplier</th>
                                <th>Items</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPurchases as $purchase)
                                <tr>
                                    <td>
                                        <strong class="font-monospace small d-block">{{ $purchase->purchase_number }}</strong>
                                        <span class="muted small">{{ $purchase->purchase_date ? $purchase->purchase_date->format('M d, Y') : '-' }}</span>
                                    </td>
                                    <td>{{ $purchase->supplier?->name ?? 'Direct Supplier' }}</td>
                                    <td>{{ $purchase->items_count }} item(s)</td>
                                    <td class="text-end fw-bold accent">₱{{ number_format($purchase->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 muted">No supplier purchases recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- Close #dashboardLiveContent --}}
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function showDashboardSkeleton() {
        const content = document.getElementById('dashboardLiveContent');
        const skeleton = document.getElementById('dashboardSkeletonWrapper');
        if (content && skeleton) {
            content.classList.add('d-none');
            skeleton.classList.remove('d-none');
        }
    }

    function handleRangeChange(val) {
        const customWrapper = document.getElementById('customDatesWrapper');
        if (val === 'custom') {
            customWrapper.classList.remove('d-none');
        } else {
            customWrapper.classList.add('d-none');
            showDashboardSkeleton();
            document.getElementById('filterForm').submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('filterForm')?.addEventListener('submit', showDashboardSkeleton);

        // Shared Chart.js Dark Theme Configuration with Smooth Easing
        Chart.defaults.color = '#BDBDBD';
        Chart.defaults.font.family = "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif";
        Chart.defaults.borderColor = '#333333';

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        Chart.defaults.animation = prefersReducedMotion ? false : {
            duration: 750,
            easing: 'easeOutQuart'
        };

        const tooltipConfig = {
            backgroundColor: '#1B1B1B',
            titleColor: '#FFFFFF',
            bodyColor: '#BDBDBD',
            borderColor: '#333333',
            borderWidth: 1,
            padding: 10,
            boxPadding: 4,
            usePointStyle: true,
        };

        // 1. Stock Movements Bar Chart
        const stockCanvas = document.getElementById('stockMovementChart');
        if (stockCanvas) {
            new Chart(stockCanvas, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: 'Stock In (Replenished)',
                            data: @json($stockInData),
                            backgroundColor: 'rgba(76, 175, 80, 0.75)',
                            borderColor: '#4CAF50',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Stock Out (Kitchen Usage)',
                            data: @json($stockOutData),
                            backgroundColor: 'rgba(229, 57, 53, 0.75)',
                            borderColor: '#E53935',
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 12, usePointStyle: true, color: '#BDBDBD' }
                        },
                        tooltip: tooltipConfig
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(51, 51, 51, 0.6)', drawBorder: false },
                            ticks: { color: '#BDBDBD', font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(51, 51, 51, 0.6)', drawBorder: false },
                            ticks: { color: '#BDBDBD', font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // 2. Inventory Health Status Doughnut Chart
        const statusCanvas = document.getElementById('inventoryStatusChart');
        if (statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json($statusLabels),
                    datasets: [{
                        data: @json($statusData),
                        backgroundColor: [
                            'rgba(76, 175, 80, 0.85)',
                            'rgba(255, 193, 7, 0.85)',
                            'rgba(229, 57, 53, 0.85)'
                        ],
                        borderColor: '#1B1B1B',
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: tooltipConfig
                    }
                }
            });
        }

        // 3. Purchasing Expenses Line Chart
        const purchaseCanvas = document.getElementById('purchaseExpenseChart');
        if (purchaseCanvas) {
            new Chart(purchaseCanvas, {
                type: 'line',
                data: {
                    labels: @json($purchaseLabels),
                    datasets: [{
                        label: 'Purchasing Expenses (₱)',
                        data: @json($purchaseSpendData),
                        borderColor: '#E53935',
                        backgroundColor: 'rgba(229, 57, 53, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.25,
                        pointBackgroundColor: '#E53935',
                        pointBorderColor: '#FFC107',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipConfig,
                            callbacks: {
                                label: function (context) {
                                    return ' Purchase Expense: ₱' + Number(context.raw).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(51, 51, 51, 0.6)', drawBorder: false },
                            ticks: { color: '#BDBDBD', font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(51, 51, 51, 0.6)', drawBorder: false },
                            ticks: {
                                color: '#BDBDBD',
                                font: { size: 10 },
                                callback: function (val) { return '₱' + val.toLocaleString(); }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
