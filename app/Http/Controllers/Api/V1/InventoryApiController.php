<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:inventory_categories,id',
            'status' => 'nullable|string|in:available,low_stock,out_of_stock',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 100);

        $query = InventoryItem::with(['category:id,name', 'unit:id,name,short_name'])
            ->select('inventory_items.*')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('inventory_items.name', 'like', '%' . $search . '%')
                        ->orWhere('inventory_items.sku', 'like', '%' . $search . '%');
                });
            })
            ->when(!empty($validated['category']), function ($q) use ($validated) {
                $q->where('inventory_items.category_id', $validated['category']);
            })
            ->when(!empty($validated['status']), function ($q) use ($validated) {
                if ($validated['status'] === 'out_of_stock') {
                    $q->where('inventory_items.current_quantity', '<=', 0);
                } elseif ($validated['status'] === 'low_stock') {
                    $q->where('inventory_items.current_quantity', '>', 0)
                        ->whereColumn('inventory_items.current_quantity', '<=', 'inventory_items.minimum_stock_level');
                } elseif ($validated['status'] === 'available') {
                    $q->whereColumn('inventory_items.current_quantity', '>', 'inventory_items.minimum_stock_level');
                }
            });

        $paginator = $query->latest('inventory_items.created_at')
            ->latest('inventory_items.id')
            ->paginate($perPage);
        $settings = BusinessSetting::find(1);

        return response()->json([
            'items' => $paginator->getCollection()->map(fn (InventoryItem $item) => self::formatItem($item))->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'categories' => InventoryCategory::orderBy('name')->get()->map(fn (InventoryCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'is_active' => (bool) $cat->is_active,
            ]),
            'units' => Unit::orderBy('name')->get()->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'short_name' => $unit->short_name,
                'is_active' => (bool) $unit->is_active,
            ]),
            'defaults' => [
                'minimum_stock_level' => (float) ($settings?->default_minimum_stock_level ?? 0),
                'reorder_level' => (float) ($settings?->default_reorder_level ?? 0),
            ],
        ]);
    }

    public function show(Request $request, InventoryItem $item): JsonResponse
    {
        $item->load(['category', 'unit']);
        $user = $request->user();

        $txQuery = InventoryTransaction::with(['inventoryItem.unit', 'user'])
            ->where('inventory_item_id', $item->id)
            ->latest('transaction_date');

        if (!$user->isOwner()) {
            $txQuery->where('user_id', $user->id);
        }

        $recentTransactions = $txQuery->take(15)->get()
            ->map(fn (InventoryTransaction $tx) => TransactionApiController::formatTransaction($tx));

        return response()->json([
            'item' => self::formatItem($item),
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = InventoryItem::validateItemRequest($request);

        $item = DB::transaction(function () use ($validated, $request): InventoryItem {
            $item = InventoryItem::create($validated);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'inventory_created',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Created inventory item: ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $item->load(['category', 'unit']);
        });

        return response()->json([
            'message' => 'Inventory item created successfully.',
            'item' => self::formatItem($item),
        ], 201);
    }

    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        $validated = InventoryItem::validateItemRequest($request, $item);

        DB::transaction(function () use ($item, $validated, $request): void {
            $item->update($validated);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'inventory_updated',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Updated inventory item: ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'item' => self::formatItem($item->fresh(['category', 'unit'])),
        ]);
    }

    public static function formatItem(InventoryItem $item): array
    {
        $item->loadMissing(['category', 'unit']);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'description' => $item->description,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name ?? 'Uncategorized',
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit?->name ?? 'Unit',
            'unit_short_name' => $item->unit?->short_name ?? 'unit',
            'current_quantity' => (float) $item->current_quantity,
            'minimum_stock_level' => (float) $item->minimum_stock_level,
            'reorder_level' => (float) $item->reorder_level,
            'status' => $item->status,
            'is_low_stock' => $item->isLowStock(),
            'is_out_of_stock' => $item->isOutOfStock(),
            'is_active' => (bool) $item->is_active,
            'last_counted_at' => $item->last_counted_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}

