{{-- Reusable Audit Logs Skeleton Loader --}}
<div class="skeleton-wrapper audit-skeleton-placeholder" aria-hidden="true">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 80px; height: 14px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 150px; height: 28px;"></div>
        </div>
        <div class="skeleton skeleton-shimmer" style="width: 200px; height: 36px; border-radius: 8px;"></div>
    </div>

    <!-- Filter Card Skeleton -->
    <div class="card p-3 mb-4 border-border">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 36px; border-radius: 8px;"></div>
            </div>
            <div class="col-md-5">
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 36px; border-radius: 8px;"></div>
            </div>
            <div class="col-md-2">
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 36px; border-radius: 8px;"></div>
            </div>
        </div>
    </div>

    <div class="card border-border overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 18%;"><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></th>
                        <th style="width: 16%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                        <th style="width: 18%;"><div class="skeleton skeleton-shimmer" style="width: 60px; height: 14px;"></div></th>
                        <th style="width: 32%;"><div class="skeleton skeleton-shimmer" style="width: 120px; height: 14px;"></div></th>
                        <th style="width: 16%;"><div class="skeleton skeleton-shimmer" style="width: 75px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 7; $i++)
                        <tr>
                            <td><div class="skeleton skeleton-shimmer" style="width: 120px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 90px; height: 22px; border-radius: 12px;"></div></td>
                            <td>
                                <div class="skeleton skeleton-shimmer mb-1" style="width: 90px; height: 15px;"></div>
                                <div class="skeleton skeleton-shimmer" style="width: 60px; height: 11px;"></div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 220px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 85px; height: 13px;"></div></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
