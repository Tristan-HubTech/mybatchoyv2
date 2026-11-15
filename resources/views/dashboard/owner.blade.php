@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Skeleton Placeholder for Dynamic Transitions -->
<div id="dashboardSkeletonWrapper" class="d-none">
    @include('components.skeletons.dashboard-skeleton')
</div>

<div id="dashboardLiveContent">
    <!-- 1. HERO WELCOME BANNER (Matches Target Design Reference IMAGE 1) -->
    <div class="eb-hero-banner">
        <div class="eb-hero-content">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge px-2 py-1" style="background: rgba(229, 57, 53, 0.2); color: #FFFFFF; border: 1px solid rgba(229, 57, 53, 0.5); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                    Dashboard Overview
                </span>
                <span class="badge px-2 py-1" style="background: rgba(255, 193, 7, 0.16); color: #FFC107; border: 1px solid rgba(255, 193, 7, 0.4); font-size: 0.72rem; font-weight: 700;">
                    Owner / Admin
                </span>
                <span class="small" style="color: #BDBDBD; font-size: 0.78rem;">
                    &bull; Period: <strong class="text-white">{{ $rangeLabel }}</strong> ({{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }})
                </span>
            </div>

            <h1 class="eb-hero-title">Welcome back, {{ $currentUser->name }}!</h1>
            <p class="eb-hero-subtitle">Here's what's happening with your business today.</p>

            <!-- Integrated Date Filter & Refresh Bar -->
            <form action="{{ route('owner.dashboard') }}" method="GET" id="filterForm" class="eb-hero-controls">
                <div class="d-flex align-items-center gap-2">
                    <label for="rangeSelector" class="small fw-semibold mb-0" style="color: #BDBDBD; font-size: 0.78rem;">Date Range:</label>
                    <select name="range" id="rangeSelector" class="form-select form-select-sm" style="width: auto; min-width: 150px; background-color: #111111; color: #FFFFFF; border-color: #333333; font-size: 0.82rem;">
                        <option value="today" {{ $rangeKey === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7days" {{ $rangeKey === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ $rangeKey === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $rangeKey === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="custom" {{ $rangeKey === 'custom' ? 'selected' : '' }}>Custom Range...</option>
                    </select>
                </div>

                <div id="customDatesWrapper" class="{{ $rangeKey === 'custom' ? 'd-flex' : 'd-none' }} flex-wrap align-items-center gap-2">
                    <input type="date" name="from" class="form-control form-control-sm" style="width: 144px;" value="{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" aria-label="Start date">
                    <span class="small" style="color: #BDBDBD;">to</span>
                    <input type="date" name="to" class="form-control form-control-sm" style="width: 144px;" value="{{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" aria-label="End date">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Apply</button>
                </div>

                <a href="{{ route('owner.dashboard', ['range' => $rangeKey, 'from' => \Carbon\Carbon::parse($startDate)->format('Y-m-d'), 'to' => \Carbon\Carbon::parse($endDate)->format('Y-m-d')]) }}"
                   class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1"
                   id="refreshMetricsBtn"
                   title="Reload the latest stock numbers">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFC107" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    <span>Refresh</span>
                </a>
            </form>
        </div>

        <div class="eb-hero-art" aria-hidden="true">
            <img src="{{ asset('images/eustace-bowl-hero.jpg') }}" alt="Eustace Batchoy Bowl">
        </div>
    </div>

    <!-- 2. SUMMARY KPI CARDS (5 Cards Matching Target Reference Proportions & Layout) -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4">
        <!-- Card 1: Total Items -->
        <div class="col">
            <a href="{{ route('owner.inventory.index') }}" class="eb-kpi-card" title="View all active inventory items">
                <div class="eb-kpi-circle kpi-red">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Total Items</div>
                    <div class="eb-kpi-value" data-counter="{{ $totalItemsCount }}">{{ number_format($totalItemsCount) }}</div>
                    <div class="eb-kpi-meta meta-up">
                        <span>&#8593; Active catalog</span>
                    </div>
                </div>
                <svg class="eb-kpi-spark" viewBox="0 0 70 32" fill="none" aria-hidden="true">
                    <path d="M2 26 C14 24, 18 14, 28 18 C38 22, 46 8, 56 12 C62 14, 65 6, 68 4" stroke="#E53935" stroke-width="2.2" stroke-linecap="round"/>
                    <path d="M2 26 C14 24, 18 14, 28 18 C38 22, 46 8, 56 12 C62 14, 65 6, 68 4 L68 32 L2 32 Z" fill="rgba(229, 57, 53, 0.16)"/>
                </svg>
            </a>
        </div>

        <!-- Card 2: Items In Stock -->
        <div class="col">
            <a href="{{ route('owner.inventory.index', ['status' => 'in_stock']) }}" class="eb-kpi-card" title="View healthy stock items">
                <div class="eb-kpi-circle kpi-green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Items In Stock</div>
                    <div class="eb-kpi-value" data-counter="{{ $inStockCount }}">{{ number_format($inStockCount) }}</div>
                    <div class="eb-kpi-meta meta-up">
                        <span>&#10003; Healthy supply</span>
                    </div>
                </div>
                <svg class="eb-kpi-spark" viewBox="0 0 70 32" fill="none" aria-hidden="true">
                    <path d="M2 24 C14 20, 22 22, 32 14 C42 8, 50 16, 68 5" stroke="#4CAF50" stroke-width="2.2" stroke-linecap="round"/>
                    <path d="M2 24 C14 20, 22 22, 32 14 C42 8, 50 16, 68 5 L68 32 L2 32 Z" fill="rgba(76, 175, 80, 0.16)"/>
                </svg>
            </a>
        </div>

        <!-- Card 3: Purchases -->
        <div class="col">
            <a href="{{ route('owner.purchases.index') }}" class="eb-kpi-card" title="View purchase orders and spend">
                <div class="eb-kpi-circle kpi-yellow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Purchases</div>
                    <div class="eb-kpi-value" data-counter="{{ $purchasesCount }}">{{ number_format($purchasesCount) }}</div>
                    <div class="eb-kpi-meta meta-warn" title="Total spend in {{ $rangeLabel }}">
                        <span>&#8369;{{ number_format($purchasingExpenses, 2) }}</span>
                    </div>
                </div>
                <svg class="eb-kpi-spark" viewBox="0 0 70 32" fill="none" aria-hidden="true">
                    <path d="M2 26 C16 25, 22 15, 34 17 C46 19, 54 9, 68 6" stroke="#FFC107" stroke-width="2.2" stroke-linecap="round"/>
                    <path d="M2 26 C16 25, 22 15, 34 17 C46 19, 54 9, 68 6 L68 32 L2 32 Z" fill="rgba(255, 193, 7, 0.16)"/>
                </svg>
            </a>
        </div>

        <!-- Card 4: Low Stock -->
        <div class="col">
            <a href="{{ route('owner.inventory.index', ['status' => 'low_stock']) }}" class="eb-kpi-card" title="View low stock items needing reorder">
                <div class="eb-kpi-circle kpi-yellow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Low Stock</div>
                    <div class="eb-kpi-value" data-counter="{{ $lowStockCount }}">{{ number_format($lowStockCount) }}</div>
                    <div class="eb-kpi-meta {{ $lowStockCount > 0 ? 'meta-warn' : 'meta-up' }}">
                        <span>{{ $lowStockCount > 0 ? 'View Details →' : 'None low' }}</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card 5: Out of Stock -->
        <div class="col">
            <a href="{{ route('owner.inventory.index', ['status' => 'out_of_stock']) }}" class="eb-kpi-card" title="View depleted items">
                <div class="eb-kpi-circle kpi-red">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="eb-kpi-body">
                    <div class="eb-kpi-label">Out of Stock</div>
                    <div class="eb-kpi-value" data-counter="{{ $outOfStockCount }}">{{ number_format($outOfStockCount) }}</div>
                    <div class="eb-kpi-meta {{ $outOfStockCount > 0 ? 'meta-danger' : 'meta-up' }}">
                        <span>{{ $outOfStockCount > 0 ? 'View Details →' : 'Zero depleted' }}</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. ATTENTION CALLOUT & QUICK ACTIONS STRIP -->
    <div class="eb-panel-card mb-4">
        <div class="eb-panel-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-xl-5">
                    <div class="d-flex align-items-center gap-3">
                        @if($outOfStockCount > 0)
                            <div class="eb-kpi-circle kpi-red" style="width: 42px; height: 42px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                        @elseif($lowStockCount > 0)
                            <div class="eb-kpi-circle kpi-yellow" style="width: 42px; height: 42px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                        @else
                            <div class="eb-kpi-circle kpi-green" style="width: 42px; height: 42px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                        @endif
                        <div>
                            <div class="fw-bold text-white" style="font-size: 0.95rem;">What Needs Attention Today?</div>
                            <div class="small" style="color: #BDBDBD; font-size: 0.82rem;">
                                @if($outOfStockCount > 0 && $lowStockCount > 0)
                                    <strong style="color: #E53935;">{{ $outOfStockCount }} {{ Str::plural('item', $outOfStockCount) }} out of stock</strong> and <strong style="color: #FFC107;">{{ $lowStockCount }} running low</strong>.
                                    <a href="#urgentAlertsSection" class="ms-1 fw-bold" style="color: #FFC107; text-decoration: none;">Review alerts &darr;</a>
                                @elseif($outOfStockCount > 0)
                                    <strong style="color: #E53935;">{{ $outOfStockCount }} {{ Str::plural('item', $outOfStockCount) }} completely out of stock.</strong>
                                    <a href="#urgentAlertsSection" class="ms-1 fw-bold" style="color: #E53935; text-decoration: none;">Restock now &darr;</a>
                                @elseif($lowStockCount > 0)
                                    <strong style="color: #FFC107;">{{ $lowStockCount }} {{ Str::plural('item', $lowStockCount) }} running low</strong> and below reorder level.
                                    <a href="#urgentAlertsSection" class="ms-1 fw-bold" style="color: #FFC107; text-decoration: none;">View items &darr;</a>
                                @else
                                    <span style="color: #4CAF50; font-weight: 600;">All {{ $totalItemsCount }} active items have healthy stock levels.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                        <a href="{{ route('owner.transactions.stockIn') }}" class="eb-quick-btn qb-primary">
                            <span style="color: #FFFFFF; font-weight: 800;">+</span>
                            <span>Add Stock</span>
                        </a>
                        <a href="{{ route('owner.transactions.stockOut') }}" class="eb-quick-btn">
                            <span style="color: #E53935; font-weight: 800;">&minus;</span>
                            <span>Remove Stock</span>
                        </a>
                        <a href="{{ route('owner.transactions.adjustment') }}" class="eb-quick-btn">
                            <span style="color: #FFC107;">&#9998;</span>
                            <span>Correct Stock</span>
                        </a>
                        <a href="{{ route('owner.transactions.spoilage') }}" class="eb-quick-btn">
                            <span style="color: #E53935;">&#9888;</span>
                            <span>Record Spoilage</span>
                        </a>
                        <a href="{{ route('owner.inventory.index') }}" class="eb-quick-btn">
                            <span style="color: #BDBDBD;">&#9776;</span>
                            <span>Manage Inventory</span>
                        </a>
                        <a href="{{ route('owner.purchases.index') }}" class="eb-quick-btn">
                            <span style="color: #FFC107;">&#128722;</span>
                            <span>Purchases</span>
                        </a>
                        <a href="{{ route('owner.reports.index') }}" class="eb-quick-btn">
                            <span style="color: #4CAF50;">&#128202;</span>
                            <span>Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. MAIN ROW 1: INVENTORY OVERVIEW DONUT (LEFT) + RECENT TRANSACTIONS TABLE (RIGHT) -->
    <div class="row g-3 mb-4">
        <!-- Left: Inventory Overview / Stock Health Status (Matches IMAGE 1 Donut + Right Legend) -->
        <div class="col-lg-5">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E53935" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        <span>Inventory Overview</span>
                        <span class="visually-hidden">Stock Health Status</span>
                    </div>
                    <span class="badge" style="background: #111111; color: #BDBDBD; border: 1px solid #333333; font-size: 0.75rem; font-weight: 600;">
                        {{ $rangeLabel }}
                    </span>
                </div>
                <div class="eb-panel-body d-flex flex-column justify-content-center">
                    @if($totalItemsCount === 0)
                        <div class="text-center py-4">
                            <div class="fw-bold text-white mb-1">No Active Inventory Items</div>
                            <p class="small mb-3" style="color: #BDBDBD;">Add your first batchoy ingredient or supply item to track stock health.</p>
                            <a href="{{ route('owner.inventory.create') }}" class="btn btn-sm btn-primary">+ Add Item</a>
                        </div>
                    @else
                        <div class="row align-items-center g-3">
                            <div class="col-sm-6">
                                <div class="eb-donut-stage">
                                    <canvas id="inventoryStatusChart"></canvas>
                                    <div class="eb-donut-center">
                                        <div class="donut-num">{{ number_format($totalItemsCount) }}</div>
                                        <div class="donut-lbl">Items</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <ul class="eb-legend-list">
                                    <li>
                                        <span class="eb-legend-label">
                                            <span class="eb-legend-dot" style="background: #4CAF50;"></span>
                                            <span>In Stock</span>
                                        </span>
                                        <span class="eb-legend-val">{{ number_format($inStockCount) }}</span>
                                    </li>
                                    <li>
                                        <span class="eb-legend-label">
                                            <span class="eb-legend-dot" style="background: #FFC107;"></span>
                                            <span>Low Stock</span>
                                        </span>
                                        <span class="eb-legend-val">{{ number_format($lowStockCount) }}</span>
                                    </li>
                                    <li>
                                        <span class="eb-legend-label">
                                            <span class="eb-legend-dot" style="background: #E53935;"></span>
                                            <span>Out of Stock</span>
                                        </span>
                                        <span class="eb-legend-val">{{ number_format($outOfStockCount) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Recent Transactions Table (Matches IMAGE 1 Pill Badges & Columns) -->
        <div class="col-lg-7">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFC107" stroke="#FFC107" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Recent Transactions</span>
                    </div>
                    <a href="{{ route('owner.transactions.index') }}" class="eb-panel-action">View All &rarr;</a>
                </div>
                <div class="eb-panel-body p-0">
                    @if($recentTransactions->isEmpty())
                        <div class="text-center py-5 px-3">
                            <div class="fw-bold text-white mb-1">No Stock Transactions Yet</div>
                            <p class="small mb-3" style="color: #BDBDBD;">Record deliveries, kitchen usage, or stock counts to see recent activity here.</p>
                            <a href="{{ route('owner.transactions.stockIn') }}" class="btn btn-sm btn-primary">+ Add Stock</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table eb-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Type</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Date</th>
                                        <th class="pe-3">Performed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $tx)
                                        @php
                                            $pillVariant = match($tx->transaction_type) {
                                                'stock_in' => 'tx-in',
                                                'stock_out' => 'tx-out',
                                                'adjustment' => 'tx-adj',
                                                'spoilage' => 'tx-spoil',
                                                default => 'tx-out',
                                            };
                                            $pillLabel = match($tx->transaction_type) {
                                                'stock_in' => 'Stock In',
                                                'stock_out' => 'Stock Out',
                                                'adjustment' => 'Adjustment',
                                                'spoilage' => 'Spoilage',
                                                default => ucfirst($tx->transaction_type),
                                            };
                                            $isPositive = $tx->transaction_type === 'stock_in'
                                                || ($tx->transaction_type === 'adjustment' && (float) $tx->updated_quantity >= (float) $tx->previous_quantity);
                                            $qtyColor = $tx->transaction_type === 'stock_in'
                                                ? '#4CAF50'
                                                : ($tx->transaction_type === 'adjustment' ? '#FFC107' : '#E53935');
                                            $unitAbbr = $tx->inventoryItem?->unit?->short_name ?? '';
                                            $txDate = $tx->transaction_date ?? $tx->created_at;
                                        @endphp
                                        <tr>
                                            <td class="ps-3">
                                                <x-status-badge :variant="$pillVariant" :label="$pillLabel" />
                                            </td>
                                            <td class="fw-semibold text-white">
                                                <div>{{ $tx->inventoryItem->name ?? 'Deleted Item' }}</div>
                                                @if(!empty($tx->reason))
                                                    <div class="small" style="color: #BDBDBD; font-size: 0.74rem; font-weight: 400;">{{ $tx->reason }}</div>
                                                @endif
                                            </td>
                                            <td class="fw-bold" style="color: {{ $qtyColor }};">
                                                {{ $isPositive ? '+' : '-' }}{{ number_format((float) $tx->quantity, 2) }}{{ $unitAbbr ? ' ' . $unitAbbr : '' }}
                                            </td>
                                            <td class="small" style="color: #BDBDBD;">
                                                {{ $txDate ? $txDate->format('M d, Y H:i') : '—' }}
                                            </td>
                                            <td class="pe-3 small text-white">
                                                {{ $tx->user->name ?? 'System' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 5. MAIN ROW 2: PURCHASE SUMMARY (LEFT) + STOCK MOVEMENT TRENDS (RIGHT) -->
    <div class="row g-3 mb-4">
        <!-- Left: Purchase Summary / Purchasing Expense Timeline (Matches IMAGE 1 Yellow Line Chart + Total Spent Box) -->
        <div class="col-lg-6">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFC107" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <span>Purchase Summary</span>
                        <span class="small fw-normal ms-1" style="color: #BDBDBD;">(Purchasing Expense Timeline)</span>
                    </div>
                    <a href="{{ route('owner.purchases.index') }}" class="eb-panel-action">Manage Purchases &rarr;</a>
                </div>
                <div class="eb-panel-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            @if(!$hasPurchasesInPeriod)
                                <div class="text-center py-4 px-2" style="background: #141414; border: 1px dashed #333333; border-radius: 10px;">
                                    <div class="fw-bold text-white mb-1" style="font-size: 0.9rem;">No Purchase Orders in {{ $rangeLabel }}</div>
                                    <p class="small mb-2" style="color: #BDBDBD; font-size: 0.78rem;">No approved or received supplier purchases in this date range.</p>
                                    <a href="{{ route('owner.purchases.create') }}" class="btn btn-sm btn-outline-light" style="font-size: 0.75rem;">+ New Purchase Order</a>
                                </div>
                            @else
                                <div style="position: relative; height: 195px; width: 100%;">
                                    <canvas id="purchaseExpenseChart"></canvas>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <div class="eb-purchase-total-box">
                                <div class="small" style="color: #BDBDBD; font-size: 0.78rem; font-weight: 600;">Total Spent</div>
                                <div class="total-amount">&#8369;{{ number_format($purchasingExpenses, 2) }}</div>
                                <div class="small mt-1" style="color: #4CAF50; font-size: 0.76rem; font-weight: 700;">
                                    &#8593; {{ number_format($purchasesCount) }} {{ Str::plural('order', $purchasesCount) }} ({{ $rangeLabel }})
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Stock Movement Trends -->
        <div class="col-lg-6">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        <span>Stock Movement Trends</span>
                    </div>
                    <span class="small" style="color: #BDBDBD; font-size: 0.76rem;">Add Stock vs Remove Stock</span>
                </div>
                <div class="eb-panel-body">
                    @if(!$hasStockMovement)
                        <div class="text-center py-4 px-3" style="background: #141414; border: 1px dashed #333333; border-radius: 10px;">
                            <div class="fw-bold text-white mb-1" style="font-size: 0.9rem;">No Stock Movements in {{ $rangeLabel }}</div>
                            <p class="small mb-3" style="color: #BDBDBD; font-size: 0.78rem;">No stock additions or removals were recorded during this period.</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('owner.transactions.stockIn') }}" class="btn btn-sm btn-primary">+ Add Stock</a>
                                <a href="{{ route('owner.transactions.stockOut') }}" class="btn btn-sm btn-outline-light">&minus; Remove Stock</a>
                            </div>
                        </div>
                    @else
                        <div style="position: relative; height: 195px; width: 100%;">
                            <canvas id="stockMovementChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 6. MAIN ROW 3: STOCK ALERTS TABLE (LEFT) + SYSTEM STATUS & MOBILE APK PROMO (RIGHT) -->
    <div class="row g-3">
        <!-- Left: Urgent Stock Alerts Table -->
        <div class="col-lg-7" id="urgentAlertsSection">
            <div class="eb-panel-card h-100">
                <div class="eb-panel-header">
                    <div class="eb-panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E53935" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>Stock Alerts &mdash; Items Needing Restock</span>
                    </div>
                    <a href="{{ route('owner.inventory.index') }}" class="eb-panel-action">Full Inventory &rarr;</a>
                </div>
                <div class="eb-panel-body p-0">
                    @if($urgentItems->isEmpty())
                        <div class="text-center py-5 px-3">
                            <div class="fw-bold mb-1" style="color: #4CAF50;">All Inventory Levels Are Healthy</div>
                            <p class="small mb-0" style="color: #BDBDBD;">No items are currently out of stock or below their reorder level.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table eb-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Item Name</th>
                                        <th>Status</th>
                                        <th class="text-end">Current Stock</th>
                                        <th class="text-end">Reorder Level</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($urgentItems as $item)
                                        @php
                                            $isOut = $item->isOutOfStock();
                                            $unitName = $item->unit?->short_name ?? '';
                                        @endphp
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-white">{{ $item->name }}</div>
                                                <div class="small" style="color: #BDBDBD; font-size: 0.74rem;">{{ $item->category->name ?? 'Uncategorized' }}</div>
                                            </td>
                                            <td>
                                                @if($isOut)
                                                    <x-status-badge variant="tx-out" label="Out of Stock" />
                                                @else
                                                    <x-status-badge variant="tx-adj" label="Low Stock" />
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold" style="color: {{ $isOut ? '#E53935' : '#FFC107' }};">
                                                {{ number_format((float) $item->current_quantity, 2) }}
                                                <span class="small fw-normal" style="color: #BDBDBD;">{{ $unitName }}</span>
                                            </td>
                                            <td class="text-end small" style="color: #BDBDBD;">
                                                {{ number_format((float) $item->minimum_stock_level, 2) }} {{ $unitName }}
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="{{ route('owner.transactions.stockIn', ['item' => $item->id]) }}"
                                                       class="btn btn-sm btn-primary py-1 px-2"
                                                       style="font-size: 0.74rem; font-weight: 700;">
                                                        + Add Stock
                                                    </a>
                                                    <a href="{{ route('owner.inventory.edit', $item->id) }}"
                                                       class="btn btn-sm btn-outline-light py-1 px-2"
                                                       style="font-size: 0.74rem;">
                                                        Edit
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: System Status + MyBatchoy Mobile App Card (Matches IMAGE 1 Bottom Right) -->
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="eb-panel-card h-100">
                        <div class="eb-panel-header">
                            <div class="eb-panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFC107" stroke="#111111" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span>System Status</span>
                            </div>
                        </div>
                        <div class="eb-panel-body d-flex align-items-center">
                            <div class="eb-sys-grid w-100">
                                <div class="eb-sys-item">
                                    <div class="eb-sys-check">&#10003;</div>
                                    <div class="eb-sys-label">Database<br>Connected</div>
                                </div>
                                <div class="eb-sys-item">
                                    <div class="eb-sys-check">&#10003;</div>
                                    <div class="eb-sys-label">Application<br>Healthy</div>
                                </div>
                                <div class="eb-sys-item">
                                    <div class="eb-sys-clock">&#128337;</div>
                                    <div class="eb-sys-label">
                                        Synced<br>
                                        <span style="color: #BDBDBD; font-size: 0.68rem;">{{ now()->format('M d, H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    @php
                        $apkUrl = config('app.mobile_apk_url') ?: route('owner.settings.index') . '#mobile-app-download';
                    @endphp
                    <div class="eb-apk-card">
                        <div style="max-width: 62%;">
                            <div class="apk-title">MyBatchoy</div>
                            <div class="apk-sub">Mobile App</div>
                            <div class="apk-desc">Manage your inventory on the go!</div>
                            <a href="{{ $apkUrl }}" class="btn-apk">
                                <span>Download APK &rarr;</span>
                            </a>
                        </div>
                        <div class="eb-apk-phone" aria-hidden="true">
                            <img src="{{ asset('images/eustace-mascot.jpg') }}" alt="MyBatchoy App">
                            <span style="font-size: 0.5rem; font-weight: 800; color: #FFC107; margin-top: 3px; letter-spacing: 0.3px;">MyBatchoy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Local Bundle -->
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Date Range Selector Auto-Submit
    const rangeSelector = document.getElementById('rangeSelector');
    const customWrapper = document.getElementById('customDatesWrapper');
    const filterForm = document.getElementById('filterForm');
    const skeletonWrapper = document.getElementById('dashboardSkeletonWrapper');
    const liveContent = document.getElementById('dashboardLiveContent');
    const refreshBtn = document.getElementById('refreshMetricsBtn');

    function showDashboardSkeleton() {
        if (skeletonWrapper && liveContent) {
            liveContent.classList.add('d-none');
            skeletonWrapper.classList.remove('d-none');
        }
    }

    if (rangeSelector) {
        rangeSelector.addEventListener('change', function() {
            if (this.value === 'custom') {
                customWrapper.classList.remove('d-none');
                customWrapper.classList.add('d-flex');
            } else {
                customWrapper.classList.remove('d-flex');
                customWrapper.classList.add('d-none');
                showDashboardSkeleton();
                filterForm.submit();
            }
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            showDashboardSkeleton();
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            showDashboardSkeleton();
        });
    }

    // 2. Smooth KPI Number Counter
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'), 10);
        if (isNaN(target) || target <= 0) return;

        const duration = 450;
        const startTime = performance.now();

        function updateCounter(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        }
        requestAnimationFrame(updateCounter);
    });

    if (typeof Chart === 'undefined') return;

    // Strict Eustace Batchoy Brand Palette
    Chart.defaults.color = '#BDBDBD';
    Chart.defaults.borderColor = '#262626';
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";

    const tooltipTheme = {
        backgroundColor: '#111111',
        titleColor: '#FFFFFF',
        bodyColor: '#BDBDBD',
        borderColor: '#333333',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8
    };

    // 3. Inventory Overview Donut Chart (Matches IMAGE 1 Ring Proportions)
    const statusCanvas = document.getElementById('inventoryStatusChart');
    if (statusCanvas) {
        new Chart(statusCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($statusLabels) !!},
                datasets: [{
                    data: {!! json_encode($statusData) !!},
                    backgroundColor: [
                        '#4CAF50', // In Stock
                        '#FFC107', // Low Stock
                        '#E53935'  // Out of Stock
                    ],
                    borderColor: '#1B1B1B',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipTheme
                }
            }
        });
    }

    // 4. Purchase Summary Line Chart (Matches IMAGE 1 Yellow Area Line)
    const purchaseCanvas = document.getElementById('purchaseExpenseChart');
    if (purchaseCanvas) {
        new Chart(purchaseCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($purchaseLabels) !!},
                datasets: [{
                    label: 'Purchase Spend (₱)',
                    data: {!! json_encode($purchaseSpendData) !!},
                    borderColor: '#FFC107',
                    backgroundColor: 'rgba(255, 193, 7, 0.16)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#FFC107',
                    pointBorderColor: '#111111',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipTheme,
                        callbacks: {
                            label: function(ctx) {
                                return ' ₱' + Number(ctx.parsed.y || 0).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#242424' },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return '₱' + Number(value).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // 5. Stock Movement Trends Line Chart (Green Stock In vs Red Stock Out)
    const movementCanvas = document.getElementById('stockMovementChart');
    if (movementCanvas) {
        new Chart(movementCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Add Stock (In)',
                        data: {!! json_encode($stockInData) !!},
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.14)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#4CAF50',
                        pointRadius: 3,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Remove Stock (Out)',
                        data: {!! json_encode($stockOutData) !!},
                        borderColor: '#E53935',
                        backgroundColor: 'rgba(229, 57, 53, 0.14)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#E53935',
                        pointRadius: 3,
                        fill: true,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, boxWidth: 8, color: '#FFFFFF', font: { size: 11 } }
                    },
                    tooltip: tooltipTheme
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#242424' },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>
@endsection
