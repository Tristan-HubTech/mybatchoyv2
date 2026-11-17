@extends('layouts.app')

@section('title', 'Stock Adjustment Approvals')

@section('content')
<x-page-header
    section="Inventory"
    breadcrumb="Stock Adjustments"
    title="Stock Count &amp; Adjustment Approvals"
    subtitle="Approve or reject Staff-submitted shelf count variances and adjustment requests. Approved adjustments update stock and record inventory history.">
    <x-slot:actions>
        <a href="{{ route('owner.transactions.adjustment') }}" class="btn btn-outline-light">Direct Adjustment</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<x-table-card title="Stock Adjustment &amp; Count Requests">
    @if ($requests->isEmpty())
        <x-empty-state title="No pending or historical adjustment requests" description="Staff-submitted stock counts and adjustment requests appear here for Owner review." />
    @else
        <div class="table-responsive">
            <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Requested By</th>
                        <th>Type</th>
                        <th class="text-end">System Qty</th>
                        <th class="text-end">Counted Qty</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Owner Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        @php
                            $unit = $req->inventoryItem?->unit?->short_name ?? 'units';
                            $variant = match ($req->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold text-white" data-label="Item">{{ $req->inventoryItem?->name ?? 'Deleted Item' }}</td>
                            <td class="small text-white" data-label="Requested By">{{ $req->requester?->name ?? 'Staff' }}</td>
                            <td data-label="Type"><x-status-badge variant="neutral" :label="$req->request_type === 'stock_count' ? 'Stock Count' : 'Adjustment'" /></td>
                            <td class="text-end small muted" data-label="System Qty">{{ rtrim(rtrim(number_format((float) $req->previous_quantity, 3), '0'), '.') ?: '0' }} {{ $unit }}</td>
                            <td class="text-end fw-bold text-warning" data-label="Counted Qty">{{ rtrim(rtrim(number_format((float) $req->counted_quantity, 3), '0'), '.') ?: '0' }} {{ $unit }}</td>
                            <td class="small text-white" data-label="Reason">{{ $req->reason }}</td>
                            <td data-label="Status">
                                <x-status-badge :variant="$variant" :label="ucfirst($req->status)" />
                                @if ($req->review_notes)
                                    <div class="small muted mt-1">{{ $req->review_notes }}</div>
                                @endif
                            </td>
                            <td class="text-end eb-cell-actions">
                                @if ($req->status === 'pending')
                                    <div class="d-flex flex-column gap-1 align-items-end">
                                        <form method="POST" action="{{ route('owner.adjustments.approve', $req) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="text" name="review_notes" class="form-control form-control-sm" style="width: 130px;" placeholder="Approval note">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.adjustments.reject', $req) }}" class="d-inline-flex gap-1">
                                            @csrf
                                            <input type="text" name="review_notes" class="form-control form-control-sm" style="width: 130px;" placeholder="Reject note">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="small muted">Reviewed {{ $req->reviewed_at?->diffForHumans() }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <div class="small muted">Showing {{ $requests->firstItem() ?? 0 }}&ndash;{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }}</div>
            <div>{{ $requests->links() }}</div>
        </x-slot:footer>
    @endif
</x-table-card>
@endsection

