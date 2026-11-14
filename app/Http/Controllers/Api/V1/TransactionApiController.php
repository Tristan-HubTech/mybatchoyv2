<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:stock_in,stock_out,adjustment,spoilage',
            'inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'search' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $query = InventoryTransaction::with(['inventoryItem.unit', 'user'])->latest('transaction_date')->latest('id');

        if (!$user->isOwner()) {
            $query->where('user_id', $user->id);
        }

        if (!empty($validated['type'])) {
            $query->where('transaction_type', $validated['type']);
        }

        if (!empty($validated['inventory_item_id'])) {
            $query->where('inventory_item_id', $validated['inventory_item_id']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($sub) use ($search) {
                $sub->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->take(100)->get();

        return response()->json([
            'transactions' => $transactions->map(fn (InventoryTransaction $tx) => self::formatTransaction($tx))->values(),
            'items' => InventoryItem::with(['category', 'unit'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (InventoryItem $item) => InventoryApiController::formatItem($item))
                ->values(),
        ]);
    }

    public function stockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'nullable|string|max:255',
        ]);

        [$transaction, $item] = DB::transaction(function () use ($validated, $request): array {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();
            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = round($previousQuantity + (float) $validated['quantity'], 3);

            $item->update(['current_quantity' => $newQuantity, 'last_counted_at' => now()]);

            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $request->user()->id,
                'transaction_type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $newQuantity,
                'reason' => $validated['reason'] ?? 'Stock in',
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock_in',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Stock in: ' . $validated['quantity'] . ' for ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [$tx->load(['inventoryItem.unit', 'user']), $item->fresh(['category', 'unit'])];
        });

        return response()->json([
            'message' => 'Stock in recorded successfully.',
            'transaction' => self::formatTransaction($transaction),
            'item' => InventoryApiController::formatItem($item),
        ], 201);
    }

    public function stockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'nullable|string|max:255',
        ]);

        [$transaction, $item] = DB::transaction(function () use ($validated, $request): array {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                throw ValidationException::withMessages(['quantity' => ['Insufficient stock.']]);
            }

            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = max(0, round($previousQuantity - (float) $validated['quantity'], 3));

            $item->update(['current_quantity' => $newQuantity, 'last_counted_at' => now()]);

            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $request->user()->id,
                'transaction_type' => 'stock_out',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $newQuantity,
                'reason' => $validated['reason'] ?? 'Stock out',
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock_out',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Stock out: ' . $validated['quantity'] . ' from ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [$tx->load(['inventoryItem.unit', 'user']), $item->fresh(['category', 'unit'])];
        });

        return response()->json([
            'message' => 'Stock out recorded successfully.',
            'transaction' => self::formatTransaction($transaction),
            'item' => InventoryApiController::formatItem($item),
        ], 201);
    }

    public function adjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'current_quantity' => 'required|numeric|min:0|max:999999999.999',
            'reason' => 'required|string|max:255',
        ]);

        [$transaction, $item] = DB::transaction(function () use ($validated, $request): array {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();
            $previousQuantity = (float) $item->current_quantity;
            $updatedQuantity = round((float) $validated['current_quantity'], 3);

            $item->update(['current_quantity' => $updatedQuantity, 'last_counted_at' => now()]);

            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $request->user()->id,
                'transaction_type' => 'adjustment',
                'quantity' => round($updatedQuantity - $previousQuantity, 3),
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $updatedQuantity,
                'reason' => $validated['reason'],
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock_adjustment',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Adjustment for ' . $item->name . ': ' . $previousQuantity . ' -> ' . $updatedQuantity,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [$tx->load(['inventoryItem.unit', 'user']), $item->fresh(['category', 'unit'])];
        });

        return response()->json([
            'message' => 'Stock adjustment saved successfully.',
            'transaction' => self::formatTransaction($transaction),
            'item' => InventoryApiController::formatItem($item),
        ], 201);
    }

    public function spoilage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'required|string|max:255',
        ]);

        [$transaction, $item] = DB::transaction(function () use ($validated, $request): array {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                throw ValidationException::withMessages(['quantity' => ['Spoilage quantity cannot exceed current stock.']]);
            }

            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = round($previousQuantity - (float) $validated['quantity'], 3);

            $item->update(['current_quantity' => $newQuantity, 'last_counted_at' => now()]);

            $tx = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $request->user()->id,
                'transaction_type' => 'spoilage',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $newQuantity,
                'reason' => $validated['reason'],
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'spoilage',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Spoilage for ' . $item->name . ': ' . $validated['quantity'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [$tx->load(['inventoryItem.unit', 'user']), $item->fresh(['category', 'unit'])];
        });

        return response()->json([
            'message' => 'Spoilage recorded successfully.',
            'transaction' => self::formatTransaction($transaction),
            'item' => InventoryApiController::formatItem($item),
        ], 201);
    }

    public static function formatTransaction(InventoryTransaction $tx): array
    {
        $tx->loadMissing(['inventoryItem.unit', 'user']);

        return [
            'id' => $tx->id,
            'inventory_item_id' => $tx->inventory_item_id,
            'item_name' => $tx->inventoryItem?->name ?? 'Deleted Item',
            'item_sku' => $tx->inventoryItem?->sku ?? 'N/A',
            'unit_short_name' => $tx->inventoryItem?->unit?->short_name ?? 'unit',
            'user_id' => $tx->user_id,
            'user_name' => $tx->user?->name ?? 'System',
            'transaction_type' => $tx->transaction_type,
            'quantity' => (float) $tx->quantity,
            'previous_quantity' => (float) $tx->previous_quantity,
            'updated_quantity' => (float) $tx->updated_quantity,
            'reason' => $tx->reason,
            'transaction_date' => $tx->transaction_date?->toIso8601String(),
        ];
    }
}

