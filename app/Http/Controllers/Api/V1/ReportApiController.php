<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
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

        $totalPurchases = (float) (clone $purchasesQuery)->sum('total_amount');
        $monthlyPurchases = (float) Purchase::whereMonth('purchase_date', now()->month)
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

        $lowStockItems = $items->filter(fn ($item) => $item->current_quantity > 0 && $item->current_quantity <= $item->minimum_stock_level)
            ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))
            ->values();

        $outOfStockItems = $items->filter(fn ($item) => $item->current_quantity <= 0)
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

        return response()->json([
            'summary' => [
                'inventory_value' => round((float) $inventoryValue, 2),
                'total_purchases' => round($totalPurchases, 2),
                'monthly_purchases' => round($monthlyPurchases, 2),
                'total_usage_qty' => round($totalUsageQty, 3),
                'total_spoilage_qty' => round($totalSpoilageQty, 3),
                'total_stock_in_qty' => round($totalStockInQty, 3),
                'total_items_count' => $items->count(),
                'low_stock_count' => $lowStockItems->count(),
                'out_of_stock_count' => $outOfStockItems->count(),
            ],
            'items' => $items->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))->values(),
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'categories' => $categories,
            'recent_purchases' => $recentPurchases,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'mybatchoy_inventory_report_' . now()->format('Y-m-d') . '.csv';
        $items = InventoryItem::with(['category', 'unit'])->get();

        $sanitizeCsvCell = static function ($value): string {
            $cell = (string) ($value ?? '');
            if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $cell;
            }
            return $cell;
        };

        return response()->streamDownload(function () use ($items, $sanitizeCsvCell) {
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
                    $sanitizeCsvCell($item->sku),
                    $sanitizeCsvCell($item->name),
                    $sanitizeCsvCell($item->category?->name ?? 'N/A'),
                    $sanitizeCsvCell($item->unit?->short_name ?? 'N/A'),
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
