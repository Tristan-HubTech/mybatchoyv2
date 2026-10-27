@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container py-5">
    <div id="auditSkeletonWrapper" class="d-none">
        @include('components.skeletons.audit-skeleton')
    </div>
    <div id="auditLiveContent">
        <div class="mb-4">
            <p class="text-uppercase small fw-bold accent mb-1">Security</p>
            <h1 class="fw-bold mb-0">Audit log</h1>
        </div>

        <form method="GET" action="{{ route('owner.audit.index') }}" class="card p-3 mb-4" id="auditFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label for="audit-search" class="form-label">Search activity</label>
                    <input id="audit-search" name="search" value="{{ request('search') }}" class="form-control" type="search" placeholder="User, action, record, or details">
                </div>
                <div class="col-sm-5 col-lg-2">
                    <label for="audit-from" class="form-label">From</label>
                    <input id="audit-from" name="from" value="{{ request('from') }}" class="form-control" type="date">
                </div>
                <div class="col-sm-5 col-lg-2">
                    <label for="audit-to" class="form-label">To</label>
                    <input id="audit-to" name="to" value="{{ request('to') }}" class="form-control" type="date">
                </div>
                <div class="col-sm-2 col-lg-2 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>
                    <a class="btn btn-outline-primary" href="{{ route('owner.audit.index') }}" aria-label="Clear filters" title="Clear filters">Clear</a>
                </div>
            </div>
        </form>

        <div class="card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr><th>Date</th><th>User</th><th>Action</th><th>Affected record</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
                                <td>{{ str_replace('_', ' ', $log->action) }}</td>
                                <td>{{ $log->model_type ? class_basename($log->model_type) : '—' }}{{ $log->model_id ? ' #' . $log->model_id : '' }}</td>
                                <td>{{ $log->details ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 muted">No audit events match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('auditFilterForm');
        if (form) {
            form.addEventListener('submit', function () {
                const skeleton = document.getElementById('auditSkeletonWrapper');
                const live = document.getElementById('auditLiveContent');
                if (skeleton && live) {
                    skeleton.classList.remove('d-none');
                    live.style.opacity = '0.35';
                    live.style.pointerEvents = 'none';
                }
            });
        }
    });
</script>
@endsection
