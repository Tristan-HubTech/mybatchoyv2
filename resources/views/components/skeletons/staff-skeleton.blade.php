{{-- Reusable Staff Management Skeleton Loader --}}
<div class="skeleton-wrapper staff-skeleton-placeholder" aria-hidden="true">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton skeleton-shimmer mb-1" style="width: 90px; height: 14px;"></div>
            <div class="skeleton skeleton-shimmer" style="width: 170px; height: 28px;"></div>
        </div>
        <div class="skeleton skeleton-shimmer" style="width: 140px; height: 36px; border-radius: 8px;"></div>
    </div>

    <div class="card border-border overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 30%;"><div class="skeleton skeleton-shimmer" style="width: 90px; height: 14px;"></div></th>
                        <th style="width: 20%;"><div class="skeleton skeleton-shimmer" style="width: 70px; height: 14px;"></div></th>
                        <th style="width: 20%;"><div class="skeleton skeleton-shimmer" style="width: 50px; height: 14px;"></div></th>
                        <th style="width: 15%;"><div class="skeleton skeleton-shimmer" style="width: 60px; height: 14px;"></div></th>
                        <th style="width: 15%; text-align: end;"><div class="skeleton skeleton-shimmer ms-auto" style="width: 60px; height: 14px;"></div></th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="skeleton skeleton-shimmer rounded-circle" style="width: 32px; height: 32px; flex-shrink: 0;"></div>
                                    <div>
                                        <div class="skeleton skeleton-shimmer mb-1" style="width: 110px; height: 15px;"></div>
                                        <div class="skeleton skeleton-shimmer" style="width: 140px; height: 11px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 85px; height: 15px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 70px; height: 22px; border-radius: 12px;"></div></td>
                            <td><div class="skeleton skeleton-shimmer" style="width: 60px; height: 20px; border-radius: 10px;"></div></td>
                            <td class="text-end">
                                <div class="skeleton skeleton-shimmer ms-auto" style="width: 80px; height: 28px; border-radius: 6px;"></div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
