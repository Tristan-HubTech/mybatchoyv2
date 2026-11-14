<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier' => 'nullable|integer|exists:suppliers,id',
        ]);

        $query = Purchase::with(['supplier', 'creator', 'items.inventoryItem.unit'])->latest('purchase_date')->latest('id');

        if (!empty($validated['supplier'])) {
            $query->where('supplier_id', $validated['supplier']);
        }

        $purchases = $query->take(100)->get();

        return response()->json([
            'purchases' => $purchases->map(fn (Purchase $p) => self::formatPurchase($p))->values(),
            'suppliers' => Supplier::orderBy('name')->get()->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'is_active' => (bool) $s->is_active,
            ]),
            'items' => InventoryItem::with(['category', 'unit'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item)),
        ]);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['supplier', 'creator', 'items.inventoryItem.unit']);

        return response()->json([
            'purchase' => self::formatPurchase($purchase),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'items.*.unit_cost' => 'required|numeric|min:0|max:999999999.99',
        ]);

        $purchase = DB::transaction(function () use ($validated, $request): Purchase {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'created_by' => $request->user()->id,
                'purchase_number' => 'PO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'purchase_date' => $validated['purchase_date'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $total = 0.0;

            foreach ($validated['items'] as $itemData) {
                $inventoryItem = InventoryItem::whereKey($itemData['inventory_item_id'])->lockForUpdate()->firstOrFail();
                $quantity = round((float) $itemData['quantity'], 3);
                $unitCost = round((float) $itemData['unit_cost'], 2);
                $lineTotal = round($quantity * $unitCost, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'unit_id' => $inventoryItem->unit_id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $previousQuantity = round((float) $inventoryItem->current_quantity, 3);
                $updatedQuantity = round($previousQuantity + $quantity, 3);
                $inventoryItem->current_quantity = $updatedQuantity;
                $inventoryItem->last_counted_at = now();
                $inventoryItem->save();

                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'user_id' => $request->user()->id,
                    'supplier_id' => $purchase->supplier_id,
                    'transaction_type' => 'stock_in',
                    'quantity' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'updated_quantity' => $updatedQuantity,
                    'reason' => 'Purchase (' . $purchase->purchase_number . ')',
                    'transaction_date' => now(),
                ]);

                $total = round($total + $lineTotal, 2);
            }

            $purchase->update(['total_amount' => $total]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'purchase_recorded',
                'model_type' => Purchase::class,
                'model_id' => $purchase->id,
                'details' => 'Recorded purchase with total amount ' . number_format($total, 2),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $purchase->load(['supplier', 'creator', 'items.inventoryItem.unit']);
        });

        return response()->json([
            'message' => 'Purchase recorded successfully.',
            'purchase' => self::formatPurchase($purchase),
        ], 201);
    }

    public static function formatPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing(['supplier', 'creator', 'items.inventoryItem.unit']);

        return [
            'id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'supplier_id' => $purchase->supplier_id,
            'supplier_name' => $purchase->supplier?->name ?? 'Direct / Walk-in',
            'created_by' => $purchase->created_by,
            'created_by_name' => $purchase->creator?->name ?? 'System',
            'purchase_date' => $purchase->purchase_date?->format('Y-m-d'),
            'total_amount' => (float) $purchase->total_amount,
            'notes' => $purchase->notes,
            'items_count' => $purchase->items->count(),
            'items' => $purchase->items->map(fn (PurchaseItem $pi) => [
                'id' => $pi->id,
                'inventory_item_id' => $pi->inventory_item_id,
                'item_name' => $pi->inventoryItem?->name ?? 'Unknown Item',
                'item_sku' => $pi->inventoryItem?->sku ?? 'N/A',
                'unit_short_name' => $pi->inventoryItem?->unit?->short_name ?? 'unit',
                'quantity' => (float) $pi->quantity,
                'unit_cost' => (float) $pi->unit_cost,
                'total_cost' => (float) $pi->total_cost,
            ])->values(),
            'created_at' => $purchase->created_at?->toIso8601String(),
        ];
    }
}

