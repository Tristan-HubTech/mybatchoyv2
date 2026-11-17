@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div id="auditSkeletonWrapper" class="d-none">
    @include('components.skeletons.audit-skeleton')
</div>

<div id="auditLiveContent">
    <x-page-header
        section="Administration"
        title="Audit Logs"
        subtitle="Read-only record of inventory updates, account approvals, purchases, and security actions." />

    <div class="eb-filter-card">
        <form method="GET" action="{{ route('owner.audit.index') }}" id="auditFilterForm" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="audit-search">Search Activity</label>
                <input id="audit-search" name="search" value="{{ request('search') }}" class="form-control" type="search" placeholder="Search by staff name, action, or details...">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label" for="audit-from">From Date</label>
                <input id="audit-from" name="from" value="{{ request('from') }}" class="form-control" type="date">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label" for="audit-to">To Date</label>
                <input id="audit-to" name="to" value="{{ request('to') }}" class="form-control" type="date">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Filter</button>
                @if (request()->filled('search') || request()->filled('from') || request()->filled('to'))
                    <a class="btn btn-outline-light" href="{{ route('owner.audit.index') }}" aria-label="Clear filters" title="Clear filters">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <x-table-card title="Activity History">
        <x-slot:headerActions>
            <span class="small" style="color: #BDBDBD;">{{ $logs->total() }} {{ Str::plural('entry', $logs->total()) }}</span>
        </x-slot:headerActions>

        @if ($logs->isEmpty())
            <x-empty-state
                title="No activity logs found"
                description="No system events matched your current search or date filters."
                :actionUrl="request()->filled('search') || request()->filled('from') || request()->filled('to') ? route('owner.audit.index') : null"
                actionLabel="Clear Filters" />
        @else
            <div class="table-responsive">
                <table class="table eb-table eb-responsive-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Performed By</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-nowrap small eb-cell-primary" data-label="Timestamp" style="color: #BDBDBD;">
                                    <span class="d-block text-white fw-semibold">{{ $log->created_at->format('M d, Y') }}</span>
                                    <span>{{ $log->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="fw-semibold text-white" data-label="Performed By">{{ $log->user?->name ?? 'System' }}</td>
                                <td data-label="Action">
                                    <x-status-badge variant="neutral" :label="str_replace('_', ' ', $log->action)" />
                                </td>
                                <td class="small font-monospace" data-label="Record" style="color: #BDBDBD;">
                                    {{ $log->model_type ? class_basename($log->model_type) : '—' }}{{ $log->model_id ? ' #' . $log->model_id : '' }}
                                </td>
                                <td class="small" data-label="Details" style="color: #BDBDBD; max-width: 380px;">{{ $log->details ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div class="small" style="color: #BDBDBD;">
                    Showing {{ $logs->firstItem() ?? 0 }}&ndash;{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} log entries
                </div>
                <div>
                    {{ $logs->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('auditFilterForm');
    if (form) {
        form.addEventListener('submit', function () {
            if (typeof window.activatePageSkeleton === 'function') {
                window.activatePageSkeleton('auditSkeletonWrapper', 'auditLiveContent');
            } else {
                const skeleton = document.getElementById('auditSkeletonWrapper');
                const live = document.getElementById('auditLiveContent');
                if (skeleton && live) {
                    live.classList.add('d-none');
                    skeleton.classList.remove('d-none');
                }
            }
        });
    }
});
</script>
@endsection
