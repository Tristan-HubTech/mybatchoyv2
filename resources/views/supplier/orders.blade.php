@extends('layouts.app')

@section('title', 'Supplier Purchase Orders')

@section('content')
<x-page-header
    section="Supplier Portal"
    breadcrumb="Purchase Orders"
    :title="$supplier->name . ' — Purchase Orders & Deliveries'"
    subtitle="Confirm or reject pending purchase orders and submit delivery status updates and invoice references.">
    <x-slot:actions>
        <a href="{{ route('supplier.dashboard') }}" class="btn btn-outline-light">&larr; Dashboard</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="eb-tabs mb-4" role="navigation" aria-label="Order filter">
    <a href="{{ route('supplier.orders.index') }}" class="eb-tab-link {{ !request('status') ? 'active' : '' }}">All Orders</a>
    <a href="{{ route('supplier.orders.index', ['status' => 'pending']) }}" class="eb-tab-link {{ request('status') === 'pending' ? 'active' : '' }}">Pending</a>
    <a href="{{ route('supplier.orders.index', ['status' => 'confirmed']) }}" class="eb-tab-link {{ request('status') === 'confirmed' ? 'active' : '' }}">Confirmed</a>
    <a href="{{ route('supplier.orders.index', ['status' => 'delivered']) }}" class="eb-tab-link {{ request('status') === 'delivered' ? 'active' : '' }}">Delivered</a>
    <a href="{{ route('supplier.orders.index', ['status' => 'rejected']) }}" class="eb-tab-link {{ request('status') === 'rejected' ? 'active' : '' }}">Rejected</a>
</div>

<x-table-card title="Assigned Purchase Orders">
    @if ($orders->isEmpty())
        <x-empty-state title="No purchase orders match this filter" description="Only orders assigned to {{ $supplier->name }} appear in this portal." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>PO # &amp; Date</th>
                        <th>Requested Items</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Fulfillment Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $variant = match ($order->supplier_status) {
                                'confirmed' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td data-label="PO #">
                                <div class="font-monospace fw-bold text-warning">{{ $order->purchase_number }}</div>
                                <div class="small muted">{{ \Carbon\Carbon::parse($order->purchase_date)->format('M d, Y') }}</div>
                            </td>
                            <td data-label="Items">
                                <ul class="list-unstyled mb-0 small">
                                    @foreach ($order->items as $line)
                                        <li class="text-white">
                                            {{ $line->inventoryItem?->name ?? 'Item' }} &mdash;
                                            <span class="text-warning">{{ rtrim(rtrim(number_format((float) $line->quantity, 3), '0'), '.') ?: '0' }} {{ $line->inventoryItem?->unit?->short_name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-end fw-bold text-white" data-label="Total">&#8369;{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td data-label="Status">
                                <x-status-badge :variant="$variant" :label="ucfirst($order->supplier_status)" />
                                <div class="small muted mt-1">Delivery: {{ ucfirst(str_replace('_', ' ', $order->delivery_status)) }}</div>
                                @if ($order->invoice_number)
                                    <div class="small font-monospace text-white">Inv: {{ $order->invoice_number }}</div>
                                @endif
                            </td>
                            <td class="text-end eb-cell-actions">
                                @if ($order->supplier_status === 'pending')
                                    <div class="d-flex flex-column gap-2 align-items-end">
                                        <form method="POST" action="{{ route('supplier.orders.confirm', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Confirm PO</button>
                                        </form>
                                        <form method="POST" action="{{ route('supplier.orders.reject', $order) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="text" name="delivery_notes" class="form-control form-control-sm" style="width: 150px;" placeholder="Rejection reason" required>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </div>
                                @elseif ($order->supplier_status === 'confirmed')
                                    <form method="POST" action="{{ route('supplier.orders.delivery', $order) }}" class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
                                        @csrf
                                        @method('PATCH')
                                        <select name="delivery_status" class="form-select form-select-sm" style="width: 130px;">
                                            <option value="preparing" @selected($order->delivery_status === 'preparing')>Preparing</option>
                                            <option value="in_transit" @selected($order->delivery_status === 'in_transit')>In Transit</option>
                                            <option value="delivered" @selected($order->delivery_status === 'delivered')>Delivered</option>
                                        </select>
                                        <input type="text" name="invoice_number" class="form-control form-control-sm" style="width: 120px;" value="{{ $order->invoice_number }}" placeholder="Invoice #">
                                        <input type="text" name="delivery_notes" class="form-control form-control-sm" style="width: 140px;" value="{{ $order->delivery_notes }}" placeholder="Delivery notes">
                                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">Update</button>
                                    </form>
                                @else
                                    <span class="small muted">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <div class="small muted">Showing {{ $orders->firstItem() ?? 0 }}&ndash;{{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }}</div>
            <div>{{ $orders->links() }}</div>
        </x-slot:footer>
    @endif
</x-table-card>
@endsection

