@extends('layouts.app')

@section('title', 'Staff Dashboard Overview')

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
                <span class="badge badge-secondary-subtle">Staff Member</span>
                <span class="muted small d-none d-sm-inline">• {{ $currentUser->name }}</span>
            </div>
            <h1 class="fw-bold mb-0 page-title">Dashboard Overview</h1>
        </div>

        <!-- Date Range Filter & Refresh Control -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="{{ route('staff.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2" id="filterForm">
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

            <a href="{{ route('staff.dashboard', array_filter(['range' => $rangeKey, 'from' => request('from'), 'to' => request('to')])) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Refresh Dashboard Data">
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                </svg>
                <span>Refresh</span>
            </a>
        </div>
    </div>

    <!-- Summary Cards (5 Metrics for Staff) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Catalog Items -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Total Items</small>
                    <div class="rounded-3 p-2" style="background: rgba(111, 168, 255, 0.1); border: 1px solid rgba(111, 168, 255, 0.2);">
                        <svg width="16" height="16" fill="var(--primary-soft)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l6.229 2.492a.5.5 0 0 1 .314.464v8.572a.5.5 0 0 1-.314.464l-6.229 2.492a1.5 1.5 0 0 1-1.114 0L1.214 12.18a.5.5 0 0 1-.314-.464V3.14a.5.5 0 0 1 .314-.464L7.443.184z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1" data-counter="{{ $totalItemsCount }}">{{ number_format($totalItemsCount) }}</div>
                <small class="muted">Active tracked goods</small>
            </div>
        </div>

        <!-- 2. Items in Stock -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Items In Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                        <svg width="16" height="16" fill="var(--success)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-success" data-counter="{{ $inStockCount }}">{{ number_format($inStockCount) }}</div>
                <small class="muted">Above minimum threshold</small>
            </div>
        </div>

        <!-- 3. Low Stock Items -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Low Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                        <svg width="16" height="16" fill="var(--warning)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-warning" data-counter="{{ $lowStockCount }}">{{ number_format($lowStockCount) }}</div>
                <small class="muted">{{ $lowStockCount > 0 ? 'Approaching depletion' : 'Optimal buffer levels' }}</small>
            </div>
        </div>

        <!-- 4. Out of Stock -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Out of Stock</small>
                    <div class="rounded-3 p-2" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <svg width="16" height="16" fill="var(--danger)" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 text-danger" data-counter="{{ $outOfStockCount }}">{{ number_format($outOfStockCount) }}</div>
                <small class="muted">{{ $outOfStockCount > 0 ? 'Requires stock-in' : 'Zero items depleted' }}</small>
            </div>
        </div>

        <!-- 5. Period Movements -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="card card-hover-subtle p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <small class="muted text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Movements ({{ $rangeKey === 'today' ? 'Today' : 'Period' }})</small>
                    <div class="rounded-3 p-2" style="background: rgba(31, 106, 225, 0.1); border: 1px solid rgba(31, 106, 225, 0.2);">
                        <svg width="16" height="16" fill="var(--primary-soft)" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                        </svg>
                    </div>
                </div>
                <div class="fs-3 fw-bold mb-1 accent" data-counter="{{ $periodMovementsCount }}">{{ number_format($periodMovementsCount) }}</div>
                <small class="muted">Stock transactions recorded</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar for Staff -->
    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small fw-semibold text-uppercase muted" style="letter-spacing: 0.05em;">Staff Quick Actions</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('staff.inventory.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>View Inventory</span>
                </a>
                <a href="{{ route('staff.transactions.stockIn') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                    <span>+ Record Stock In</span>
                </a>
                <a href="{{ route('staff.transactions.stockOut') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>− Record Stock Out</span>
                </a>
                <a href="{{ route('staff.transactions.adjustment') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>Adjust Stock</span>
                </a>
                <a href="{{ route('staff.transactions.spoilage') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <span>Record Spoilage</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Row: Stock Movements & Inventory Status Breakdown -->
    <div class="row g-4 mb-4">
        <!-- Stock Movements Chart -->
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">Stock Movement Trends</h5>
                        <p class="muted small mb-0">Stock In (replenished) vs. Stock Out (kitchen cooking dispatch) for {{ $rangeLabel }}</p>
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
                        <small class="muted">Recorded transactions in this timeframe will automatically render here.</small>
                    </div>
                @endif

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
                        <p class="muted small mb-0">Current ingredient availability distribution</p>
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

    <!-- Inventory Alerts (Sorted by Urgency) -->
    <div class="card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Inventory Stock Alerts</h5>
                <small class="muted">Items requiring kitchen attention (sorted by urgency)</small>
            </div>
            <a href="{{ route('staff.inventory.index') }}" class="btn btn-sm btn-outline-primary">
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
                        <th class="text-end">Action</th>
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
                                <a href="{{ route('staff.transactions.stockIn') }}" class="btn btn-sm btn-primary py-1 px-2" title="Record Stock In">
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
                                <div class="small muted mt-1">No items currently requiring restock alerts.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions Activity -->
    <div class="card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Recent Stock Transactions</h5>
                <small class="muted">Latest logged receipts, disbursements, and kitchen usage</small>
            </div>
            <a href="{{ route('staff.transactions.index') }}" class="small accent">All Transactions →</a>
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
                        <th>Reason</th>
                        <th>Logged By</th>
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
                                <span class="d-block muted font-monospace small">{{ $tx->inventoryItem?->sku ?? '' }}</span>
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
                                <span class="muted">{{ $tx->reason ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="muted">{{ $tx->user?->name ?? 'System' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 muted">No stock transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

        Chart.defaults.color = '#B8C2D8';
        Chart.defaults.font.family = "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif";
        Chart.defaults.borderColor = '#26364D';

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        Chart.defaults.animation = prefersReducedMotion ? false : {
            duration: 750,
            easing: 'easeOutQuart'
        };

        const tooltipConfig = {
            backgroundColor: '#0B1528',
            titleColor: '#FFFFFF',
            bodyColor: '#B8C2D8',
            borderColor: '#26364D',
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
                            backgroundColor: 'rgba(34, 197, 94, 0.75)',
                            borderColor: '#22C55E',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Stock Out (Kitchen Usage)',
                            data: @json($stockOutData),
                            backgroundColor: 'rgba(31, 106, 225, 0.75)',
                            borderColor: '#1F6AE1',
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
                            labels: { boxWidth: 12, usePointStyle: true }
                        },
                        tooltip: tooltipConfig
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(38, 54, 77, 0.35)', drawBorder: false },
                            ticks: { color: '#B8C2D8', font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(38, 54, 77, 0.35)', drawBorder: false },
                            ticks: { color: '#B8C2D8', font: { size: 10 } }
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
                            'rgba(34, 197, 94, 0.85)',
                            'rgba(245, 158, 11, 0.85)',
                            'rgba(239, 68, 68, 0.85)'
                        ],
                        borderColor: '#121E2E',
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
    });
</script>
@endsection
