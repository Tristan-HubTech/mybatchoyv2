<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Services\InventoryCsvExporter;
use App\Services\InventoryValuationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, InventoryValuationService $valuationService)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $valuation = $valuationService->computeDatabaseValuation();
        $inventoryValue = $valuation['inventory_value'];
        $valuedItemsCount = $valuation['valued_items_count'];
        $unknownCostItemsCount = $valuation['unknown_cost_items_count'];
        $valuationStatus = $valuation['valuation_status'];

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

        $totalPurchases = (clone $purchasesQuery)->sum('total_amount');
        $monthlyPurchases = Purchase::realized()
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

        $lowStockCount = InventoryItem::where('current_quantity', '>', 0)
            ->whereColumn('current_quantity', '<=', 'minimum_stock_level')
            ->count();
        $outOfStockCount = InventoryItem::where('current_quantity', '<=', 0)->count();
        $lowStockItems = InventoryItem::with('unit:id,short_name')
            ->where('current_quantity', '>', 0)
            ->whereColumn('current_quantity', '<=', 'minimum_stock_level')
            ->orderBy('current_quantity')
            ->take(6)
            ->get(['id', 'name', 'unit_id', 'current_quantity', 'minimum_stock_level']);

        $categories = InventoryCategory::withCount('items')
            ->withSum('items as total_quantity', 'current_quantity')
            ->get()
            ->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'items_count' => (int) $cat->items_count,
                    'total_quantity' => (float) ($cat->total_quantity ?? 0),
                ];
            });

        $recentPurchases = (clone $purchasesQuery)->with(['supplier', 'creator'])->latest('purchase_date')->take(5)->get();
        $recentTransactions = (clone $transactionsQuery)->with(['inventoryItem', 'user'])->latest()->take(6)->get();

        return view('reports.index', compact(
            'inventoryValue',
            'valuedItemsCount',
            'unknownCostItemsCount',
            'valuationStatus',
            'totalPurchases',
            'monthlyPurchases',
            'totalUsageQty',
            'totalSpoilageQty',
            'totalStockInQty',
            'lowStockCount',
            'outOfStockCount',
            'lowStockItems',
            'categories',
            'recentPurchases',
            'recentTransactions'
        ));
    }

    public function exportCsv(): StreamedResponse
    {
        return InventoryCsvExporter::streamInventoryReport();
    }
}
