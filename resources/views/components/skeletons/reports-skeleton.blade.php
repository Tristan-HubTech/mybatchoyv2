{{-- Reusable Reports Skeleton Loader --}}
<div class="skeleton-wrapper reports-skeleton-placeholder" aria-hidden="true">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 80px; height: 14px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 180px; height: 28px;"></div>
        </div>
        <div class="skeleton skeleton-shimmer" style="width: 120px; height: 36px; border-radius: 8px;"></div>
    </div>

    <!-- Reports Valuation & Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-4 border-border">
                <div class="skeleton skeleton-shimmer mb-2" style="width: 140px; height: 13px;"></div>
                <div class="skeleton skeleton-shimmer mb-2" style="width: 130px; height: 32px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 100px; height: 12px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-border">
                <div class="skeleton skeleton-shimmer mb-2" style="width: 120px; height: 13px;"></div>
                <div class="skeleton skeleton-shimmer mb-2" style="width: 80px; height: 32px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 110px; height: 12px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-border">
                <div class="skeleton skeleton-shimmer mb-2" style="width: 130px; height: 13px;"></div>
                <div class="skeleton skeleton-shimmer mb-2" style="width: 70px; height: 32px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 120px; height: 12px;"></div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table Skeleton -->
    <div class="card border-border overflow-hidden">
        <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
            <div class="skeleton skeleton-shimmer" style="width: 160px; height: 18px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 35%;"><div class="skeleton skeleton-shimmer" style="width: 110px; height: 14px;"></div></th>
                        <th style="width: 20%;"><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></th>
                        <th style="width: 25%;"><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></th>
                        <th style="width: 20%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            <td><div class="skeleton skeleton-shimmer" style="width: 140px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 60px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 90px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 80px; height: 20px; border-radius: 10px;"></div></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
