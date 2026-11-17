@extends('layouts.app')

@section('title', 'Stock Count & Adjustment Requests')

@section('content')
<x-page-header
    section="Operations"
    breadcrumb="Count Requests"
    title="Stock Count &amp; Adjustment Requests"
    subtitle="Submit physical count discrepancies for Owner review and approval. You cannot approve your own requests.">
    <x-slot:actions>
        <a href="{{ route('staff.transactions.index') }}" class="btn btn-outline-light">&larr; Stock History</a>
    </x-slot:actions>
</x-page-header>

<x-alert-banner />

<div class="row g-4">
    <div class="col-lg-5">
        <div class="eb-panel-card h-100">
            <div class="eb-panel-header">
                <h2 class="h6 fw-bold mb-0 text-white">Submit Stock Count / Adjustment Request</h2>
            </div>
            <div class="eb-panel-body">
                <form method="POST" action="{{ route('staff.adjustments.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="reqItem">Inventory Item <span class="required-star">*</span></label>
                        <select id="reqItem" name="inventory_item_id" class="form-select" required>
                            <option value="">Select item...</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected((string) old('inventory_item_id') === (string) $item->id)>
                                    {{ $item->name }} (Current: {{ rtrim(rtrim(number_format((float) $item->current_quantity, 3), '0'), '.') ?: '0' }} {{ $item->unit?->short_name ?? 'units' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="reqType">Request Type</label>
                            <select id="reqType" name="request_type" class="form-select">
                                <option value="stock_count" @selected(old('request_type', 'stock_count') === 'stock_count')>Stock Count</option>
                                <option value="adjustment" @selected(old('request_type') === 'adjustment')>Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="reqCountedQty">Counted Quantity <span class="required-star">*</span></label>
                            <input id="reqCountedQty" type="number" step="0.001" min="0" name="counted_quantity" class="form-control" value="{{ old('counted_quantity') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reqReason">Variance Reason / Notes <span class="required-star">*</span></label>
                        <textarea id="reqReason" name="reason" rows="3" class="form-control" minlength="5" maxlength="255" required placeholder="Explain physical shelf count difference...">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit for Approval</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <x-table-card title="My Submitted Requests">
            @if ($requests->isEmpty())
                <x-empty-state title="No requests submitted yet" description="Requests you submit remain pending until reviewed by the Owner." />
            @else
                <div class="table-responsive">
                    <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">System</th>
                                <th class="text-end">Counted</th>
                                <th>Reason</th>
                                <th>Status</th>
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
                                    <td class="fw-semibold text-white" data-label="Item">{{ $req->inventoryItem?->name ?? 'Item' }}</td>
                                    <td class="text-end small muted" data-label="System">{{ rtrim(rtrim(number_format((float) $req->previous_quantity, 3), '0'), '.') ?: '0' }} {{ $unit }}</td>
                                    <td class="text-end fw-bold text-warning" data-label="Counted">{{ rtrim(rtrim(number_format((float) $req->counted_quantity, 3), '0'), '.') ?: '0' }} {{ $unit }}</td>
                                    <td class="small text-white" data-label="Reason">{{ $req->reason }}</td>
                                    <td data-label="Status">
                                        <x-status-badge :variant="$variant" :label="ucfirst($req->status)" />
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
    </div>
</div>
@endsection

