{{-- Reusable Stock Transactions Skeleton Loader --}}
<div class="skeleton-wrapper transactions-skeleton-placeholder" aria-hidden="true">
    <!-- Filter Pills & Date Range Skeleton -->
    <div class="card p-3 mb-4 border-border">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap gap-2">
                @for($i = 0; $i < 5; $i++)
                    <div class="skeleton skeleton-shimmer" style="width: 85px; height: 32px; border-radius: 999px;"></div>
                @endfor
            </div>
            <div class="d-flex gap-2">
                <div class="skeleton skeleton-shimmer" style="width: 140px; height: 34px; border-radius: 8px;"></div>
                <div class="skeleton skeleton-shimmer" style="width: 110px; height: 34px; border-radius: 8px;"></div>
            </div>
        </div>
    </div>

    <!-- Transactions Table Skeleton -->
    <div class="card border-border overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 18%;"><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                        <th style="width: 24%;"><div class="skeleton skeleton-shimmer" style="width: 110px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 60px; height: 14px;"></div></th>
                        <th style="width: 16%;"><div class="skeleton skeleton-shimmer" style="width: 75px; height: 14px;"></div></th>
                        <th style="width: 14%;"><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 7; $i++)
                        <tr>
                            <td><div class="skeleton skeleton-shimmer" style="width: 110px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 75px; height: 22px; border-radius: 12px;"></div></td>
                            <td>
                                <div class="skeleton skeleton-shimmer mb-1" style="width: 130px; height: 15px;"></div>
                                <div class="skeleton skeleton-shimmer" style="width: 65px; height: 11px;"></div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 50px; height: 16px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
