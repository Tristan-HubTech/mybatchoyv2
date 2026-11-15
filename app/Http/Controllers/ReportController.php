<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $items = InventoryItem::with(['category', 'unit'])->get();

        // Single subquery for latest purchase unit_cost per inventory_item_id
        $latestPrices = PurchaseItem::select('inventory_item_id', 'unit_cost')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('purchase_items')
                    ->groupBy('inventory_item_id');
            })
            ->pluck('unit_cost', 'inventory_item_id');

        $inventoryValue = $items->sum(function ($item) use ($latestPrices) {
            $cost = $latestPrices[$item->id] ?? 85.00;
            return (float) $item->current_quantity * (float) $cost;
        });

        $purchasesQuery = Purchase::query();
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
        $monthlyPurchases = Purchase::whereMonth('purchase_date', now()->month)
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

        $lowStockItems = $items->filter(fn ($item) => $item->current_quantity > 0 && $item->current_quantity <= $item->minimum_stock_level);
        $outOfStockItems = $items->filter(fn ($item) => $item->current_quantity <= 0);

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
            'items',
            'inventoryValue',
            'totalPurchases',
            'monthlyPurchases',
            'totalUsageQty',
            'totalSpoilageQty',
            'totalStockInQty',
            'lowStockItems',
            'outOfStockItems',
            'categories',
            'recentPurchases',
            'recentTransactions'
        ));
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'mybatchoy_inventory_report_' . now()->format('Y-m-d') . '.csv';
        $items = InventoryItem::with(['category', 'unit'])->get();

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Item Name', 'Category', 'Unit', 'Current Qty', 'Min Stock', 'Reorder Level', 'Status']);

            foreach ($items as $item) {
                $status = 'Normal';
                if ($item->current_quantity <= 0) {
                    $status = 'Out of Stock';
                } elseif ($item->current_quantity <= $item->minimum_stock_level) {
                    $status = 'Low Stock';
                }

                fputcsv($handle, [
                    $item->sku,
                    $item->name,
                    $item->category?->name ?? 'N/A',
                    $item->unit?->short_name ?? 'N/A',
                    $item->current_quantity,
                    $item->minimum_stock_level,
                    $item->reorder_level,
                    $status,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
