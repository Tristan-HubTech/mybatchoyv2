<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role && Auth::user()->role->name === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        return redirect()->route('staff.dashboard');
    }

    public function ownerDashboard(Request $request)
    {
        [$startDate, $endDate, $rangeKey, $rangeLabel] = $this->resolveDateRange($request);

        // Summary counts via direct database aggregations
        $totalItemsCount = InventoryItem::where('is_active', true)->count();
        $inStockCount = InventoryItem::where('is_active', true)
            ->whereColumn('current_quantity', '>', 'minimum_stock_level')
            ->count();
        $lowStockCount = InventoryItem::where('is_active', true)
            ->where('current_quantity', '>', 0)
            ->whereColumn('current_quantity', '<=', 'minimum_stock_level')
            ->count();
        $outOfStockCount = InventoryItem::where('is_active', true)
            ->where('current_quantity', '<=', 0)
            ->count();

        // Period purchasing metrics
        $purchasingExpenses = (float) Purchase::where('purchase_date', '>=', $startDate->format('Y-m-d'))
            ->where('purchase_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->sum('total_amount');

        $purchasesCount = Purchase::where('purchase_date', '>=', $startDate->format('Y-m-d'))
            ->where('purchase_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->count();

        // Urgent stock alerts: out of stock first, then lowest ratio against minimum stock
        $urgentItems = InventoryItem::with(['category', 'unit'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('current_quantity', '<=', 0)
                  ->orWhereColumn('current_quantity', '<=', 'minimum_stock_level');
            })
            ->orderByRaw('CASE WHEN current_quantity <= 0 THEN 0 ELSE 1 END ASC')
            ->orderByRaw('(current_quantity / NULLIF(minimum_stock_level, 0)) ASC')
            ->take(8)
            ->get();

        // Chart 1: Stock Movements (In vs Out) over the selected range
        [$chartLabels, $stockInData, $stockOutData, $hasStockMovement] = $this->buildStockMovementTimeline($startDate, $endDate);

        // Chart 2: Purchasing expenses over time
        [$purchaseLabels, $purchaseSpendData, $hasPurchasesInPeriod] = $this->buildPurchasesTimeline($startDate, $endDate);

        // Chart 3: Inventory status distribution
        $statusLabels = ['In Stock (Healthy)', 'Low Stock (Warning)', 'Out of Stock (Depleted)'];
        $statusData = [$inStockCount, $lowStockCount, $outOfStockCount];

        // Recent activity
        $recentTransactions = InventoryTransaction::with(['inventoryItem.unit', 'user'])
            ->latest('transaction_date')
            ->take(6)
            ->get();

        $recentPurchases = Purchase::with(['supplier', 'creator'])
            ->withCount('items')
            ->latest('purchase_date')
            ->take(5)
            ->get();

        $currentUser = Auth::user();

        return view('dashboard.owner', compact(
            'totalItemsCount',
            'inStockCount',
            'lowStockCount',
            'outOfStockCount',
            'purchasingExpenses',
            'purchasesCount',
            'urgentItems',
            'chartLabels',
            'stockInData',
            'stockOutData',
            'hasStockMovement',
            'purchaseLabels',
            'purchaseSpendData',
            'hasPurchasesInPeriod',
            'statusLabels',
            'statusData',
            'recentTransactions',
            'recentPurchases',
            'startDate',
            'endDate',
            'rangeKey',
            'rangeLabel',
            'currentUser'
        ));
    }

    public function staffDashboard(Request $request)
    {
        [$startDate, $endDate, $rangeKey, $rangeLabel] = $this->resolveDateRange($request);

        // Summary counts via direct database aggregations
        $totalItemsCount = InventoryItem::where('is_active', true)->count();
        $inStockCount = InventoryItem::where('is_active', true)
            ->whereColumn('current_quantity', '>', 'minimum_stock_level')
            ->count();
        $lowStockCount = InventoryItem::where('is_active', true)
            ->where('current_quantity', '>', 0)
            ->whereColumn('current_quantity', '<=', 'minimum_stock_level')
            ->count();
        $outOfStockCount = InventoryItem::where('is_active', true)
            ->where('current_quantity', '<=', 0)
            ->count();

        // Period movements count (Staff sees operational activity rather than purchase costs)
        $periodMovementsCount = InventoryTransaction::whereBetween('transaction_date', [
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay(),
        ])->count();

        // Urgent stock alerts
        $urgentItems = InventoryItem::with(['category', 'unit'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('current_quantity', '<=', 0)
                  ->orWhereColumn('current_quantity', '<=', 'minimum_stock_level');
            })
            ->orderByRaw('CASE WHEN current_quantity <= 0 THEN 0 ELSE 1 END ASC')
            ->orderByRaw('(current_quantity / NULLIF(minimum_stock_level, 0)) ASC')
            ->take(8)
            ->get();

        // Chart 1: Stock Movements (In vs Out)
        [$chartLabels, $stockInData, $stockOutData, $hasStockMovement] = $this->buildStockMovementTimeline($startDate, $endDate);

        // Chart 2: Inventory status distribution
        $statusLabels = ['In Stock (Healthy)', 'Low Stock (Warning)', 'Out of Stock (Depleted)'];
        $statusData = [$inStockCount, $lowStockCount, $outOfStockCount];

        // Recent activity
        $recentTransactions = InventoryTransaction::with(['inventoryItem.unit', 'user'])
            ->latest('transaction_date')
            ->take(8)
            ->get();

        $currentUser = Auth::user();

        return view('dashboard.staff', compact(
            'totalItemsCount',
            'inStockCount',
            'lowStockCount',
            'outOfStockCount',
            'periodMovementsCount',
            'urgentItems',
            'chartLabels',
            'stockInData',
            'stockOutData',
            'hasStockMovement',
            'statusLabels',
            'statusData',
            'recentTransactions',
            'startDate',
            'endDate',
            'rangeKey',
            'rangeLabel',
            'currentUser'
        ));
    }

    protected function resolveDateRange(Request $request): array
    {
        $range = $request->input('range', '7days');
        $now = Carbon::now();

        switch ($range) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Today';
                break;
            case '30days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Last 30 Days';
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
                $label = 'This Month (' . $now->format('M Y') . ')';
                break;
            case 'custom':
                try {
                    $start = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : $now->copy()->subDays(6)->startOfDay();
                } catch (\Throwable $e) {
                    $start = $now->copy()->subDays(6)->startOfDay();
                }

                try {
                    $end = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : $now->copy()->endOfDay();
                } catch (\Throwable $e) {
                    $end = $now->copy()->endOfDay();
                }

                if ($start->gt($end)) {
                    $temp = $start;
                    $start = $end->copy()->startOfDay();
                    $end = $temp->copy()->endOfDay();
                }

                $label = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                break;
            case '7days':
            default:
                $range = '7days';
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Last 7 Days';
                break;
        }

        return [$start, $end, $range, $label];
    }

    protected function buildStockMovementTimeline(Carbon $startDate, Carbon $endDate): array
    {
        $movements = InventoryTransaction::whereBetween('transaction_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->transaction_date)->format('Y-m-d');
            });

        $chartLabels = [];
        $stockInData = [];
        $stockOutData = [];

        $current = $startDate->copy();
        $daysCount = $startDate->diffInDays($endDate) + 1;

        if ($daysCount <= 1) {
            $dateStr = $current->format('Y-m-d');
            $chartLabels[] = $current->format('M d');
            $group = $movements->get($dateStr);
            $stockInData[] = $group ? round((float) $group->where('transaction_type', 'stock_in')->sum('quantity'), 2) : 0;
            $stockOutData[] = $group ? round((float) $group->where('transaction_type', 'stock_out')->sum('quantity'), 2) : 0;
        } else {
            // Keep points between 1 and 31
            $step = max(1, (int) floor($daysCount / 31));
            while ($current->lte($endDate) && count($chartLabels) < 31) {
                $dateStr = $current->format('Y-m-d');
                $chartLabels[] = $daysCount <= 7 ? $current->format('D, M d') : $current->format('M d');
                $group = $movements->get($dateStr);
                $stockInData[] = $group ? round((float) $group->where('transaction_type', 'stock_in')->sum('quantity'), 2) : 0;
                $stockOutData[] = $group ? round((float) $group->where('transaction_type', 'stock_out')->sum('quantity'), 2) : 0;
                $current->addDays($step);
            }
        }

        $hasStockMovement = (array_sum($stockInData) > 0 || array_sum($stockOutData) > 0);

        return [$chartLabels, $stockInData, $stockOutData, $hasStockMovement];
    }

    protected function buildPurchasesTimeline(Carbon $startDate, Carbon $endDate): array
    {
        $purchases = Purchase::where('purchase_date', '>=', $startDate->format('Y-m-d'))
            ->where('purchase_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->purchase_date)->format('Y-m-d');
            });

        $purchaseLabels = [];
        $purchaseSpendData = [];

        $current = $startDate->copy();
        $daysCount = $startDate->diffInDays($endDate) + 1;

        if ($daysCount <= 1) {
            $dateStr = $current->format('Y-m-d');
            $purchaseLabels[] = $current->format('M d');
            $group = $purchases->get($dateStr);
            $purchaseSpendData[] = $group ? round((float) $group->sum('total_amount'), 2) : 0;
        } else {
            $step = max(1, (int) floor($daysCount / 31));
            while ($current->lte($endDate) && count($purchaseLabels) < 31) {
                $dateStr = $current->format('Y-m-d');
                $purchaseLabels[] = $daysCount <= 7 ? $current->format('D, M d') : $current->format('M d');
                $group = $purchases->get($dateStr);
                $purchaseSpendData[] = $group ? round((float) $group->sum('total_amount'), 2) : 0;
                $current->addDays($step);
            }
        }

        $hasPurchasesInPeriod = (array_sum($purchaseSpendData) > 0);

        return [$purchaseLabels, $purchaseSpendData, $hasPurchasesInPeriod];
    }
}
