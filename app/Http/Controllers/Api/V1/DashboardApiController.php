<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\DashboardController;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends DashboardController
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('role');
        [$startDate, $endDate, $rangeKey, $rangeLabel] = $this->resolveDateRange($request);

        [$totalItemsCount, $inStockCount, $lowStockCount, $outOfStockCount] = $this->getInventorySummaryCounts();

        $urgentItems = InventoryItem::with(['category', 'unit'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('current_quantity', '<=', 0)
                    ->orWhereColumn('current_quantity', '<=', 'minimum_stock_level');
            })
            ->orderByRaw('CASE WHEN current_quantity <= 0 THEN 0 ELSE 1 END ASC')
            ->orderByRaw('(current_quantity / NULLIF(minimum_stock_level, 0)) ASC')
            ->take(10)
            ->get()
            ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item));

        $statusDistribution = [
            'labels' => ['In Stock (Healthy)', 'Low Stock (Warning)', 'Out of Stock (Depleted)'],
            'data' => [$inStockCount, $lowStockCount, $outOfStockCount],
        ];

        $isOwner = $user->isOwner();

        if ($isOwner) {
            $purchaseStats = Purchase::where('purchase_date', '>=', $startDate->format('Y-m-d'))
                ->where('purchase_date', '<=', $endDate->format('Y-m-d 23:59:59'))
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_expenses, COUNT(*) as purchases_count')
                ->first();

            $purchasingExpenses = (float) ($purchaseStats->total_expenses ?? 0);
            $purchasesCount = (int) ($purchaseStats->purchases_count ?? 0);

            $periodMovementsCount = InventoryTransaction::whereBetween('transaction_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])->count();

            [$chartLabels, $stockInData, $stockOutData, $hasStockMovement] = $this->buildStockMovementTimeline($startDate, $endDate);
            [$purchaseLabels, $purchaseSpendData, $hasPurchasesInPeriod] = $this->buildPurchasesTimeline($startDate, $endDate);

            $recentTransactions = InventoryTransaction::with(['inventoryItem.unit', 'user'])
                ->latest('transaction_date')
                ->take(8)
                ->get()
                ->map(fn (InventoryTransaction $tx) => TransactionApiController::formatTransaction($tx));

            $recentPurchases = Purchase::with(['supplier', 'creator'])
                ->withCount('items')
                ->latest('purchase_date')
                ->take(5)
                ->get()
                ->map(fn (Purchase $purchase) => [
                    'id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'supplier_name' => $purchase->supplier?->name ?? 'Direct / Walk-in',
                    'created_by_name' => $purchase->creator?->name ?? 'System',
                    'purchase_date' => $purchase->purchase_date?->format('Y-m-d'),
                    'items_count' => (int) $purchase->items_count,
                    'total_amount' => (float) $purchase->total_amount,
                ]);

            return response()->json([
                'role' => 'owner',
                'range' => [
                    'key' => $rangeKey,
                    'label' => $rangeLabel,
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ],
                'summary' => [
                    'total_items' => $totalItemsCount,
                    'in_stock_count' => $inStockCount,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'period_movements_count' => $periodMovementsCount,
                    'purchasing_expenses' => round($purchasingExpenses, 2),
                    'purchases_count' => $purchasesCount,
                ],
                'urgent_items' => $urgentItems,
                'charts' => [
                    'stock_movements' => [
                        'labels' => $chartLabels,
                        'stock_in' => $stockInData,
                        'stock_out' => $stockOutData,
                        'has_data' => $hasStockMovement,
                    ],
                    'purchases_timeline' => [
                        'labels' => $purchaseLabels,
                        'spend' => $purchaseSpendData,
                        'has_data' => $hasPurchasesInPeriod,
                    ],
                    'status_distribution' => $statusDistribution,
                ],
                'recent_transactions' => $recentTransactions,
                'recent_purchases' => $recentPurchases,
            ]);
        }

        $staffUserId = (int) $user->id;
        $periodMovementsCount = InventoryTransaction::where('user_id', $staffUserId)
            ->whereBetween('transaction_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])->count();

        [$chartLabels, $stockInData, $stockOutData, $hasStockMovement] = $this->buildStockMovementTimeline($startDate, $endDate, $staffUserId);

        $recentTransactions = InventoryTransaction::with(['inventoryItem.unit', 'user'])
            ->where('user_id', $staffUserId)
            ->latest('transaction_date')
            ->take(8)
            ->get()
            ->map(fn (InventoryTransaction $tx) => TransactionApiController::formatTransaction($tx));

        return response()->json([
            'role' => 'staff',
            'range' => [
                'key' => $rangeKey,
                'label' => $rangeLabel,
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_items' => $totalItemsCount,
                'in_stock_count' => $inStockCount,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'period_movements_count' => $periodMovementsCount,
            ],
            'urgent_items' => $urgentItems,
            'charts' => [
                'stock_movements' => [
                    'labels' => $chartLabels,
                    'stock_in' => $stockInData,
                    'stock_out' => $stockOutData,
                    'has_data' => $hasStockMovement,
                ],
                'status_distribution' => $statusDistribution,
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }
}
