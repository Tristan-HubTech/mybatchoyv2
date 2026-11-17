@extends('layouts.app')

@section('title', 'Supplier Dashboard')

@section('content')
<x-page-header
    section="Supplier Portal"
    breadcrumb="Dashboard"
    :title="$supplier->name . ' — Supplier Dashboard'"
    subtitle="Review your assigned purchase orders, confirm deliveries, submit invoice references, and keep contact details current.">
    <x-slot:actions>
        <a href="{{ route('supplier.orders.index') }}" class="btn btn-primary">Manage Purchase Orders</a>
        <a href="{{ route('supplier.profile') }}" class="btn btn-outline-light">Supplier Profile</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <x-kpi-card label="Pending Confirmation" :value="(string) $pendingCount" meta="Awaiting your acceptance" :metaTone="$pendingCount > 0 ? 'warning' : 'up'" :tone="$pendingCount > 0 ? 'amber' : 'green'" />
    </div>
    <div class="col">
        <x-kpi-card label="Active Deliveries" :value="(string) $activeDeliveriesCount" meta="Preparing / In transit" metaTone="neutral" tone="blue" />
    </div>
    <div class="col">
        <x-kpi-card label="Delivered Orders" :value="(string) $deliveredCount" meta="Completed fulfillments" metaTone="up" tone="green" />
    </div>
    <div class="col">
        <x-kpi-card label="Total Assigned POs" :value="(string) $totalPoCount" meta="Scoped strictly to your account" metaTone="neutral" tone="red" />
    </div>
</div>

<x-table-card title="Recent Assigned Purchase Orders">
    <x-slot:headerActions>
        <a href="{{ route('supplier.orders.index') }}" class="eb-panel-action">View All POs &rarr;</a>
    </x-slot:headerActions>

    @if ($recentOrders->isEmpty())
        <x-empty-state title="No purchase orders assigned yet" description="Assigned orders for {{ $supplier->name }} will appear here for confirmation and delivery updates." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th class="text-end">Total Amount</th>
                        <th>PO Status</th>
                        <th>Delivery / Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        @php
                            $variant = match ($order->supplier_status) {
                                'confirmed' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td class="font-monospace fw-bold text-warning" data-label="PO #">{{ $order->purchase_number }}</td>
                            <td class="small muted" data-label="Date">{{ \Carbon\Carbon::parse($order->purchase_date)->format('M d, Y') }}</td>
                            <td class="small text-white" data-label="Items">
                                {{ $order->items->count() }} {{ Str::plural('line', $order->items->count()) }}
                            </td>
                            <td class="text-end fw-bold text-white" data-label="Total">&#8369;{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td data-label="Status"><x-status-badge :variant="$variant" :label="ucfirst($order->supplier_status)" /></td>
                            <td class="small muted" data-label="Delivery">
                                {{ ucfirst(str_replace('_', ' ', $order->delivery_status)) }}
                                @if ($order->invoice_number)
                                    <div class="font-monospace text-white">Inv: {{ $order->invoice_number }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-table-card>
@endsection

