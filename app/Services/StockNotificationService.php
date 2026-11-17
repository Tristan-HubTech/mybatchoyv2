<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StockNotificationService
{
    private const CACHE_TTL_SECONDS = 30;
    private const STOCK_CACHE_KEY = 'notifications.stock_alerts';

    /**
     * Build notification counts and prioritized alert messages for the given user.
     *
     * @return array{
     *     total_count: int,
     *     counts: array{out_of_stock: int, low_stock: int, reorder: int, spoilage: int},
     *     notifications: array<int, array<string, mixed>>
     * }
     */
    public static function forUser(?User $user, ?string $roleName, int $limit = 15): array
    {
        if (!$user) {
            return [
                'total_count' => 0,
                'counts' => [
                    'out_of_stock' => 0,
                    'low_stock' => 0,
                    'reorder' => 0,
                    'spoilage' => 0,
                ],
                'notifications' => [],
            ];
        }

        $resolvedRole = $roleName ?: ($user->roleName() ?: 'staff');

        if ($resolvedRole === 'system_admin') {
            return self::forSystemAdmin($limit);
        }

        if ($resolvedRole === 'supplier') {
            return self::forSupplier($user, $limit);
        }

        $stockData = self::getStockAlertsData();
        $spoilageData = self::getSpoilageAlertsData($user, $resolvedRole);

        $merged = array_merge($stockData['items'], $spoilageData['items']);

        $totalCount = $stockData['counts']['out_of_stock']
            + $stockData['counts']['low_stock']
            + $stockData['counts']['reorder']
            + $spoilageData['count'];

        return [
            'total_count' => $totalCount,
            'counts' => [
                'out_of_stock' => $stockData['counts']['out_of_stock'],
                'low_stock' => $stockData['counts']['low_stock'],
                'reorder' => $stockData['counts']['reorder'],
                'spoilage' => $spoilageData['count'],
            ],
            'notifications' => array_slice($merged, 0, max(1, $limit)),
        ];
    }

    /**
     * Technical/security alerts only for System Admin (no confidential stock or financial records).
     */
    private static function forSystemAdmin(int $limit): array
    {
        $items = [];

        if (ActiveSessionManager::hasTable('business_access_requests')) {
            $pendingGrants = \App\Models\BusinessAccessRequest::where('system_admin_id', auth()->id())
                ->where('status', 'approved')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->get();

            foreach ($pendingGrants as $grant) {
                $items[] = [
                    'id' => 'grant-' . $grant->id,
                    'category' => 'out_of_stock',
                    'severity' => 'info',
                    'title' => 'Active Access Grant: ' . $grant->scopeLabel(),
                    'message' => 'Expires at ' . $grant->expires_at?->format('M d, h:i A'),
                    'time_ago' => $grant->expires_at?->diffForHumans(),
                    'route' => 'sysadmin.access.index',
                ];
            }
        }

        return [
            'total_count' => count($items),
            'counts' => [
                'out_of_stock' => 0,
                'low_stock' => 0,
                'reorder' => 0,
                'spoilage' => 0,
            ],
            'notifications' => array_slice($items, 0, max(1, $limit)),
        ];
    }

    /**
     * Assigned PO notifications only for Supplier Portal (no internal stock or business reports).
     */
    private static function forSupplier(User $user, int $limit): array
    {
        if (!$user->supplier_id || !ActiveSessionManager::hasTable('purchases')) {
            return [
                'total_count' => 0,
                'counts' => ['out_of_stock' => 0, 'low_stock' => 0, 'reorder' => 0, 'spoilage' => 0],
                'notifications' => [],
            ];
        }

        $pendingOrders = \App\Models\Purchase::where('supplier_id', $user->supplier_id)
            ->where('supplier_status', 'pending')
            ->latest('purchase_date')
            ->take($limit)
            ->get();

        $items = $pendingOrders->map(static function (\App\Models\Purchase $po): array {
            return [
                'id' => 'po-' . $po->id,
                'category' => 'low_stock',
                'severity' => 'warning',
                'title' => 'Pending PO: ' . $po->purchase_number,
                'message' => 'Awaiting supplier confirmation and delivery schedule.',
                'time_ago' => $po->created_at?->diffForHumans(),
                'route' => 'supplier.orders.index',
            ];
        })->all();

        return [
            'total_count' => count($items),
            'counts' => [
                'out_of_stock' => 0,
                'low_stock' => count($items),
                'reorder' => 0,
                'spoilage' => 0,
            ],
            'notifications' => $items,
        ];
    }

    public static function clearStockCache(): void
    {
        Cache::forget(self::STOCK_CACHE_KEY);
        Cache::forget('inventory.urgent_alert_count');
    }

    public static function clearSpoilageCache(?int $userId = null): void
    {
        Cache::forget('notifications.spoilage.owner');

        if ($userId !== null) {
            Cache::forget('notifications.spoilage.user.' . $userId);
        }
    }

    /**
     * @return array{
     *     counts: array{out_of_stock: int, low_stock: int, reorder: int},
     *     items: array<int, array<string, mixed>>
     * }
     */
    private static function getStockAlertsData(): array
    {
        $resolver = static function (): array {
            $items = InventoryItem::query()
                ->with('unit:id,short_name')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('current_quantity', '<=', 0)
                        ->orWhereColumn('current_quantity', '<=', 'minimum_stock_level')
                        ->orWhere(function ($reorderQuery) {
                            $reorderQuery->where('reorder_level', '>', 0)
                                ->whereColumn('current_quantity', '<=', 'reorder_level');
                        });
                })
                ->orderByRaw('CASE WHEN current_quantity <= 0 THEN 0 WHEN current_quantity <= minimum_stock_level THEN 1 ELSE 2 END ASC')
                ->orderBy('current_quantity')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit_id', 'current_quantity', 'minimum_stock_level', 'reorder_level', 'updated_at']);

            $counts = [
                'out_of_stock' => 0,
                'low_stock' => 0,
                'reorder' => 0,
            ];

            $notifications = [];

            foreach ($items as $item) {
                $qty = (float) $item->current_quantity;
                $min = (float) $item->minimum_stock_level;
                $reorder = (float) $item->reorder_level;
                $unit = $item->unit?->short_name ?: 'units';

                $qtyStr = self::formatQty($qty);
                $minStr = self::formatQty($min);
                $reorderStr = self::formatQty($reorder);

                if ($qty <= 0) {
                    $counts['out_of_stock']++;
                    $category = 'out_of_stock';
                    $severity = 'danger';
                    $badge = 'Out of Stock';
                    $title = "{$item->name} is out of stock";
                    $message = "0 {$unit} remaining (minimum needed: {$minStr} {$unit}). Add stock now.";
                } elseif ($qty <= $min) {
                    $counts['low_stock']++;
                    $category = 'low_stock';
                    $severity = 'warning';
                    $badge = 'Low Stock';
                    $title = "{$item->name} is running low";
                    $message = "{$qtyStr} {$unit} left (minimum level: {$minStr} {$unit}).";
                } else {
                    $counts['reorder']++;
                    $category = 'reorder';
                    $severity = 'info';
                    $badge = 'Reorder Soon';
                    $title = "Time to reorder {$item->name}";
                    $message = "{$qtyStr} {$unit} left (reorder at {$reorderStr} {$unit}).";
                }

                $notifications[] = [
                    'id' => 'stock-' . $item->id,
                    'category' => $category,
                    'severity' => $severity,
                    'badge' => $badge,
                    'title' => $title,
                    'message' => $message,
                    'item_id' => $item->id,
                    'transaction_id' => null,
                    'action_type' => 'stock_in',
                    'time_ago' => $item->updated_at?->diffForHumans(),
                    'created_at' => $item->updated_at?->toIso8601String(),
                ];
            }

            return [
                'counts' => $counts,
                'items' => $notifications,
            ];
        };

        if (app()->runningUnitTests()) {
            return $resolver();
        }

        return Cache::remember(self::STOCK_CACHE_KEY, self::CACHE_TTL_SECONDS, $resolver);
    }

    /**
     * @return array{
     *     count: int,
     *     items: array<int, array<string, mixed>>
     * }
     */
    private static function getSpoilageAlertsData(User $user, string $roleName): array
    {
        $resolver = static function () use ($user, $roleName): array {
            $since = now()->subDays(7);

            $baseQuery = InventoryTransaction::query()
                ->where('transaction_type', 'spoilage')
                ->where('transaction_date', '>=', $since);

            if ($roleName !== 'owner') {
                $baseQuery->where('user_id', $user->id);
            }

            $count = (clone $baseQuery)->count();

            $rows = (clone $baseQuery)
                ->with(['inventoryItem:id,name,unit_id', 'inventoryItem.unit:id,short_name', 'user:id,name'])
                ->latest('transaction_date')
                ->latest('id')
                ->take(5)
                ->get(['id', 'inventory_item_id', 'user_id', 'quantity', 'reason', 'transaction_date']);

            $notifications = [];

            foreach ($rows as $tx) {
                $itemName = $tx->inventoryItem?->name ?? 'Deleted Item';
                $unit = $tx->inventoryItem?->unit?->short_name ?: 'units';
                $qtyStr = self::formatQty((float) $tx->quantity);
                $by = $tx->user?->name ? " by {$tx->user->name}" : '';
                $reason = trim((string) ($tx->reason ?? ''));

                $notifications[] = [
                    'id' => 'spoilage-' . $tx->id,
                    'category' => 'spoilage',
                    'severity' => 'spoilage',
                    'badge' => 'Spoilage',
                    'title' => "Spoilage recorded for {$itemName}",
                    'message' => "{$qtyStr} {$unit} spoiled{$by}" . ($reason !== '' ? " — {$reason}" : '.'),
                    'item_id' => $tx->inventory_item_id,
                    'transaction_id' => $tx->id,
                    'action_type' => 'spoilage_history',
                    'time_ago' => $tx->transaction_date?->diffForHumans(),
                    'created_at' => $tx->transaction_date?->toIso8601String(),
                ];
            }

            return [
                'count' => $count,
                'items' => $notifications,
            ];
        };

        if (app()->runningUnitTests()) {
            return $resolver();
        }

        $cacheKey = $roleName === 'owner'
            ? 'notifications.spoilage.owner'
            : 'notifications.spoilage.user.' . $user->id;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, $resolver);
    }

    private static function formatQty(float $quantity): string
    {
        $formatted = rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');

        return ($formatted === '' || $formatted === '-0') ? '0' : $formatted;
    }
}

