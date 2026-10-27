{{-- Reusable Suppliers Skeleton Loader --}}
<div class="skeleton-wrapper suppliers-skeleton-placeholder" aria-hidden="true">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 100px; height: 14px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 160px; height: 28px;"></div>
        </div>
        <div class="skeleton skeleton-shimmer" style="width: 130px; height: 36px; border-radius: 8px;"></div>
    </div>

    <div class="card border-border overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 25%;"><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></th>
                        <th style="width: 20%;"><div class="skeleton skeleton-shimmer" style="width: 80px; height: 14px;"></div></th>
                        <th style="width: 18%;"><div class="skeleton skeleton-shimmer" style="width: 60px; height: 14px;"></div></th>
                        <th style="width: 22%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                        <th style="width: 15%; text-align: end;"><div class="skeleton skeleton-shimmer ms-auto" style="width: 50px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 6; $i++)
                        <tr>
                            <td>
                                <div class="skeleton skeleton-shimmer mb-1" style="width: 140px; height: 16px;"></div>
                                <div class="skeleton skeleton-shimmer" style="width: 90px; height: 12px;"></div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 110px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 95px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 130px; height: 15px;"></div></td>
                            <td class="text-end">
                                <div class="skeleton skeleton-shimmer ms-auto" style="width: 60px; height: 28px; border-radius: 6px;"></div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
