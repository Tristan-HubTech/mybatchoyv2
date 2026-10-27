{{-- Reusable Dashboard Skeleton Loader --}}
<div class="skeleton-wrapper dashboard-skeleton-placeholder" aria-hidden="true">
    <!-- Header Skeleton -->
    <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="skeleton skeleton-shimmer mb-2" style="width: 140px; height: 16px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 220px; height: 32px;"></div>
        </div>
        <div class="d-flex gap-2">
            <div class="skeleton skeleton-shimmer" style="width: 140px; height: 36px; border-radius: 8px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 90px; height: 36px; border-radius: 8px;"></div>
        </div>
    </div>

    <!-- 5 Summary Cards Skeleton -->
    <div class="row g-3 mb-4">
        @for($i = 0; $i < 5; $i++)
            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                <div class="card p-3 h-100 border-border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div>
                        <div class="skeleton skeleton-shimmer" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                    </div>
                    <div class="skeleton skeleton-shimmer my-2" style="width: 90px; height: 32px;"></div>
                    <div class="skeleton skeleton-shimmer" style="width: 120px; height: 12px;"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Quick Actions Skeleton -->
    <div class="card p-3 mb-4 border-border">
        <div class="d-flex justify-content-between align-items-center">
            <div class="skeleton skeleton-shimmer" style="width: 100px; height: 16px;"></div>
            <div class="d-flex gap-2">
                <div class="skeleton skeleton-shimmer" style="width: 110px; height: 30px; border-radius: 8px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 120px; height: 30px; border-radius: 8px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 120px; height: 30px; border-radius: 8px;"></div>
            </div>
        </div>
    </div>

    <!-- Charts Row Skeleton -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card p-4 h-100 border-border">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="skeleton skeleton-shimmer mb-1" style="width: 180px; height: 20px;"></div>
                        <div class="skeleton skeleton-shimmer" style="width: 260px; height: 14px;"></div>
                    </div>
                    <div class="skeleton skeleton-shimmer" style="width: 80px; height: 22px; border-radius: 12px;"></div>
                </div>
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 260px; border-radius: 10px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100 border-border">
                <div class="skeleton skeleton-shimmer mb-1" style="width: 150px; height: 20px;"></div>
                <div class="skeleton skeleton-shimmer mb-3" style="width: 180px; height: 14px;"></div>
                <div class="skeleton skeleton-shimmer mx-auto my-3" style="width: 200px; height: 200px; border-radius: 50%;"></div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Skeleton -->
    <div class="card p-4 border-border">
        <div class="skeleton skeleton-shimmer mb-3" style="width: 190px; height: 22px;"></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        @for($i = 0; $i < 5; $i++)
                            <th><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @for($row = 0; $row < 5; $row++)
                        <tr>
                            <td><div class="skeleton skeleton-shimmer" style="width: 90px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 140px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 60px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 80px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 110px; height: 16px;"></div></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
