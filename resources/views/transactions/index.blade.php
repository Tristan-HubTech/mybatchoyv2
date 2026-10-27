@extends('layouts.app')

@section('title', 'Inventory Transactions')

@section('content')
<div class="container py-5">
    <div id="transactionsSkeletonWrapper" class="d-none">
        @include('components.skeletons.transactions-skeleton')
    </div>
    <div id="transactionsLiveContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-bold accent mb-1">Inventory</p>
                <h1 class="fw-bold mb-0">Transaction history</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ auth()->user()?->role?->name === 'owner' ? route('owner.transactions.stockIn') : route('staff.transactions.stockIn') }}" class="btn btn-primary">Stock In</a>
                <a href="{{ auth()->user()?->role?->name === 'owner' ? route('owner.transactions.stockOut') : route('staff.transactions.stockOut') }}" class="btn btn-outline-primary">Stock Out</a>
            </div>
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('M d, Y') : '-' }}</td>
                            <td>{{ $transaction->inventoryItem?->name ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->reason ?? '-' }}</td>
                            <td>{{ $transaction->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 muted">No transaction records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
