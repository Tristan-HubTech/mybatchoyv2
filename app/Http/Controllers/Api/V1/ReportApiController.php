<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Services\InventoryCsvExporter;
use App\Services\InventoryValuationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportApiController extends Controller
{
    public function index(Request $request, InventoryValuationService $valuationService): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'per_page' => 'nullable|integer|min:10|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 100);
        $valuation = $valuationService->computeDatabaseValuation();

        $purchasesQuery = Purchase::realized();
        $transactionsQuery = InventoryTransaction::query();

        if (!empty($validated['from'])) {
            $purchasesQuery->whereDate('purchase_date', '>=', $validated['from']);
            $transactionsQuery->whereDate('transaction_date', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $purchasesQuery->whereDate('purchase_date', '<=', $validated['to']);
            $transactionsQuery->whereDate('transaction_date', '<=', $validated['to']);
        }

        $totalPurchases = (float) (clone $purchasesQuery)->sum('total_amount');
        $monthlyPurchases = (float) Purchase::realized()
            ->whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total_amount');

        // Single grouped query for transaction totals by type
        $txSums = (clone $transactionsQuery)
            ->whereIn('transaction_type', ['stock_out', 'spoilage', 'stock_in'])
            ->groupBy('transaction_type')
            ->selectRaw('transaction_type, COALESCE(SUM(quantity), 0) as total_qty')
            ->pluck('total_qty', 'transaction_type');

        $totalUsageQty = (float) ($txSums['stock_out'] ?? 0);
        $totalSpoilageQty = (float) ($txSums['spoilage'] ?? 0);
        $totalStockInQty = (float) ($txSums['stock_in'] ?? 0);

        $lowStockBase = InventoryItem::with(['category:id,name', 'unit:id,name,short_name'])
            ->where('current_quantity', '>', 0)
            ->whereColumn('current_quantity', '<=', 'minimum_stock_level');
        $outOfStockBase = InventoryItem::with(['category:id,name', 'unit:id,name,short_name'])
            ->where('current_quantity', '<=', 0);

        $lowStockCount = (clone $lowStockBase)->count();
        $outOfStockCount = (clone $outOfStockBase)->count();

        $lowStockItems = $lowStockBase->orderBy('current_quantity')
            ->take(100)
            ->get()
            ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))
            ->values();

        $outOfStockItems = $outOfStockBase->orderBy('name')
            ->take(100)
            ->get()
            ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))
            ->values();

        $categories = InventoryCategory::withCount('items')
            ->withSum('items as total_quantity', 'current_quantity')
            ->get()
            ->map(fn ($cat) => [
                'name' => $cat->name,
                'items_count' => (int) $cat->items_count,
                'total_quantity' => round((float) ($cat->total_quantity ?? 0), 3),
            ])
            ->values();

        $recentPurchases = (clone $purchasesQuery)
            ->with(['supplier', 'creator', 'items.inventoryItem.unit'])
            ->latest('purchase_date')
            ->take(10)
            ->get()
            ->map(fn (Purchase $p) => PurchaseApiController::formatPurchase($p));

        $recentTransactions = (clone $transactionsQuery)
            ->with(['inventoryItem.unit', 'user'])
            ->latest('transaction_date')
            ->take(15)
            ->get()
            ->map(fn (InventoryTransaction $tx) => TransactionApiController::formatTransaction($tx));

        $itemsPaginator = InventoryItem::with(['category:id,name', 'unit:id,name,short_name'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'summary' => [
                'inventory_value' => $valuation['inventory_value'],
                'valued_items_count' => $valuation['valued_items_count'],
                'unknown_cost_items_count' => $valuation['unknown_cost_items_count'],
                'valuation_status' => $valuation['valuation_status'],
                'total_purchases' => round($totalPurchases, 2),
                'monthly_purchases' => round($monthlyPurchases, 2),
                'total_usage_qty' => round($totalUsageQty, 3),
                'total_spoilage_qty' => round($totalSpoilageQty, 3),
                'total_stock_in_qty' => round($totalStockInQty, 3),
                'total_items_count' => $valuation['total_items_count'],
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
            'items' => $itemsPaginator->getCollection()->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))->values(),
            'pagination' => [
                'current_page' => $itemsPaginator->currentPage(),
                'last_page' => $itemsPaginator->lastPage(),
                'per_page' => $itemsPaginator->perPage(),
                'total' => $itemsPaginator->total(),
            ],
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'categories' => $categories,
            'recent_purchases' => $recentPurchases,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        return InventoryCsvExporter::streamInventoryReport();
    }
}
