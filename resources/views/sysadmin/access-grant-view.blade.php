@extends('layouts.app')

@section('title', 'Scoped Business Access View')

@section('content')
<x-page-header
    section="Exceptional Access"
    breadcrumb="Read-Only Grant"
    :title="'Read-Only Inspection: ' . $accessRequest->scopeLabel()"
    :subtitle="'Owner-authorized grant #' . $accessRequest->id . ' expires at ' . ($accessRequest->expires_at?->format('M d, Y h:i A') ?? 'N/A') . '. Passwords and tokens are never exposed.'">
    <x-slot:actions>
        <a href="{{ route('sysadmin.access.index') }}" class="btn btn-outline-light">&larr; Back to Requests</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center mb-4" style="background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.35); color: #FFE082;">
    <span><strong>Audited Read-Only Session:</strong> Approved by {{ $accessRequest->reviewer?->name ?? 'Owner' }}. Every access to this view is logged.</span>
    <span class="font-monospace small">Expires {{ $accessRequest->expires_at?->diffForHumans() }}</span>
</div>

<x-table-card :title="'Scoped Data (' . $accessRequest->scopeLabel() . ')'">
    @if ($accessRequest->scope === 'reports')
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
            <div class="col"><x-kpi-card label="Active Items" :value="(string) ($records['active_items'] ?? 0)" tone="amber" /></div>
            <div class="col"><x-kpi-card label="Low / Out Stock" :value="(string) ($records['low_or_out_items'] ?? 0)" tone="red" /></div>
            <div class="col"><x-kpi-card label="Total Purchases" :value="'₱' . number_format((float) ($records['purchases_total'] ?? 0), 2)" tone="blue" /></div>
            <div class="col"><x-kpi-card label="Stock Movements" :value="(string) ($records['transactions_count'] ?? 0)" tone="green" /></div>
        </div>
    @elseif (collect($records)->isEmpty())
        <x-empty-state title="No records in scoped module" description="The inspected module currently has no records." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                @if ($accessRequest->scope === 'inventory')
                    <thead><tr><th>SKU</th><th>Item</th><th>Category</th><th class="text-end">Qty</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($records as $row)
                            <tr>
                                <td class="font-monospace small text-warning">{{ $row->sku }}</td>
                                <td class="text-white fw-semibold">{{ $row->name }}</td>
                                <td class="small muted">{{ $row->category?->name ?? '—' }}</td>
                                <td class="text-end text-white">{{ number_format((float) $row->current_quantity, 3) }} {{ $row->unit?->short_name }}</td>
                                <td><x-status-badge :variant="$row->is_active ? 'success' : 'neutral'" :label="$row->is_active ? 'Active' : 'Inactive'" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif ($accessRequest->scope === 'transactions')
                    <thead><tr><th>Date</th><th>Item</th><th>Type</th><th class="text-end">Quantity</th><th>Actor</th></tr></thead>
                    <tbody>
                        @foreach ($records as $row)
                            <tr>
                                <td class="small muted">{{ $row->transaction_date }}</td>
                                <td class="text-white fw-semibold">{{ $row->inventoryItem?->name ?? 'Item' }}</td>
                                <td><x-status-badge variant="neutral" :label="$row->transaction_type" /></td>
                                <td class="text-end text-white">{{ $row->quantity }}</td>
                                <td class="small muted">{{ $row->user?->username ?? 'system' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif ($accessRequest->scope === 'purchases')
                    <thead><tr><th>PO #</th><th>Date</th><th>Supplier</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($records as $row)
                            <tr>
                                <td class="font-monospace text-warning">{{ $row->purchase_number }}</td>
                                <td class="small muted">{{ $row->purchase_date }}</td>
                                <td class="text-white">{{ $row->supplier?->name ?? 'Direct' }}</td>
                                <td class="text-end text-white">₱{{ number_format((float) $row->total_amount, 2) }}</td>
                                <td><x-status-badge variant="info" :label="($row->supplier_status ?? 'confirmed') . ' / ' . ($row->delivery_status ?? 'delivered')" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif ($accessRequest->scope === 'suppliers')
                    <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($records as $row)
                            <tr>
                                <td class="text-white fw-semibold">{{ $row->name }}</td>
                                <td class="small text-white">{{ $row->contact_person ?? '—' }}</td>
                                <td class="small muted">{{ $row->phone ?? '—' }}</td>
                                <td><x-status-badge :variant="$row->is_active ? 'success' : 'danger'" :label="$row->is_active ? 'Active' : 'Inactive'" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif ($accessRequest->scope === 'audit_logs')
                    <thead><tr><th>Timestamp</th><th>Action</th><th>Actor</th><th>Details</th></tr></thead>
                    <tbody>
                        @foreach ($records as $row)
                            <tr>
                                <td class="small muted">{{ $row->created_at?->format('M d, H:i:s') }}</td>
                                <td class="font-monospace small text-warning">{{ $row->action }}</td>
                                <td class="small text-white">{{ $row->user?->username ?? 'system' }}</td>
                                <td class="small text-white">{{ \App\Services\TechnicalAuditLogger::scrubSecrets((string) $row->details) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>
    @endif
</x-table-card>
@endsection

