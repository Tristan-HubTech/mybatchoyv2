@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="container py-5">
    <div id="purchasesSkeletonWrapper" class="d-none">
        @include('components.skeletons.purchases-skeleton')
    </div>
    <div id="purchasesLiveContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-bold accent mb-1">Purchasing</p>
                <h1 class="fw-bold mb-0">Purchase records</h1>
            </div>
            <a href="{{ route('owner.purchases.create') }}" class="btn btn-primary">New purchase</a>
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Purchase no.</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchase_number }}</td>
                            <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('M d, Y') : '-' }}</td>
                            <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                            <td>₱{{ number_format($purchase->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 muted">No purchase records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
