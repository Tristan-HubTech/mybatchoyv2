@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container py-5">
    <div id="suppliersSkeletonWrapper" class="d-none">
        @include('components.skeletons.suppliers-skeleton')
    </div>
    <div id="suppliersLiveContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-bold accent mb-1">Purchasing</p>
                <h1 class="fw-bold mb-0">Supplier list</h1>
            </div>
            <a href="{{ route('owner.suppliers.create') }}" class="btn btn-primary">Add supplier</a>
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?? '-' }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('owner.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 muted">No suppliers added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
