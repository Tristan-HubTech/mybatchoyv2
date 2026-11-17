{{-- Reusable Inventory Skeleton Loader --}}
<div class="skeleton-wrapper inventory-skeleton-placeholder" aria-hidden="true">
    <!-- Page Header Skeleton -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 90px; height: 14px;"></div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 180px; height: 28px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 320px; height: 14px;"></div>
        </div>
        <div class="d-flex gap-2">
            <div class="skeleton skeleton-shimmer" style="width: 115px; height: 38px; border-radius: 8px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 140px; height: 38px; border-radius: 8px;"></div>
        </div>
    </div>

    <!-- Toolbar & Filters Skeleton -->
    <div class="card p-3 mb-4 border-border">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <div class="skeleton skeleton-shimmer mb-1" style="width: 80px; height: 12px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 38px; border-radius: 8px;"></div>
            </div>
            <div class="col-md-3">
                <div class="skeleton skeleton-shimmer mb-1" style="width: 70px; height: 12px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 38px; border-radius: 8px;"></div>
            </div>
            <div class="col-md-3">
                <div class="skeleton skeleton-shimmer mb-1" style="width: 70px; height: 12px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 100%; height: 38px; border-radius: 8px;"></div>
            </div>
            <div class="col-md-2 text-md-end">
                <div class="skeleton skeleton-shimmer d-inline-block" style="width: 100%; height: 38px; border-radius: 8px;"></div>
            </div>
        </div>
    </div>

    <!-- Inventory Table Skeleton -->
    <div class="card border-border overflow-hidden">
        <div class="p-3 border-bottom border-border d-flex justify-content-between align-items-center">
            <div class="skeleton skeleton-shimmer" style="width: 140px; height: 18px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 90px; height: 16px;"></div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 28%;"><div class="skeleton skeleton-shimmer" style="width: 100px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 60px; height: 14px;"></div></th>
                        <th style="width: 18%;"><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 65px; height: 14px;"></div></th>
                        <th style="width: 12%; text-align: end;"><div class="skeleton skeleton-shimmer ms-auto" style="width: 50px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 7; $i++)
                        <tr>
                            <td>
                                <div class="skeleton skeleton-shimmer mb-1" style="width: 140px; height: 16px;"></div>
                                <div class="skeleton skeleton-shimmer" style="width: 70px; height: 11px;"></div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 90px; height: 22px; border-radius: 12px;"></div></td>
                            <td>
                                <div class="skeleton skeleton-shimmer mb-1" style="width: 50px; height: 16px;"></div>
                                <div class="skeleton skeleton-shimmer" style="width: 80px; height: 6px; border-radius: 3px;"></div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 75px; height: 20px; border-radius: 10px;"></div></td>
                            <td class="text-end">
                                <div class="skeleton skeleton-shimmer ms-auto" style="width: 65px; height: 28px; border-radius: 6px;"></div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
