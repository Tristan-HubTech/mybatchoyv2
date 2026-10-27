<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with(['category', 'unit'])->get();

        // Calculate approximate inventory valuation based on latest purchase prices or default 100/unit
        $latestPrices = PurchaseItem::select('inventory_item_id', 'unit_cost')
            ->orderBy('id', 'desc')
            ->get()
            ->unique('inventory_item_id')
            ->pluck('unit_cost', 'inventory_item_id');

        $inventoryValue = $items->sum(function ($item) use ($latestPrices) {
            $cost = $latestPrices[$item->id] ?? 85.00;
            return (float) $item->current_quantity * (float) $cost;
        });

        $totalPurchases = Purchase::sum('total_amount');
        $monthlyPurchases = Purchase::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total_amount');

        $totalUsageQty = InventoryTransaction::where('transaction_type', 'stock_out')->sum('quantity');
        $totalSpoilageQty = InventoryTransaction::where('transaction_type', 'spoilage')->sum('quantity');
        $totalStockInQty = InventoryTransaction::where('transaction_type', 'stock_in')->sum('quantity');

        $lowStockItems = $items->filter(fn ($item) => $item->current_quantity > 0 && $item->current_quantity <= $item->minimum_stock_level);
        $outOfStockItems = $items->filter(fn ($item) => $item->current_quantity <= 0);

        $categories = InventoryCategory::withCount('items')
            ->with(['items' => function ($q) {
                $q->select('id', 'category_id', 'current_quantity');
            }])
            ->get()
            ->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'items_count' => $cat->items_count,
                    'total_quantity' => $cat->items->sum('current_quantity'),
                ];
            });

        $recentPurchases = Purchase::with(['supplier', 'creator'])->latest('purchase_date')->take(5)->get();
        $recentTransactions = InventoryTransaction::with(['inventoryItem', 'user'])->latest()->take(6)->get();

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
