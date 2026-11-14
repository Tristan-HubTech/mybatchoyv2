<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'supplier' => 'nullable|integer|exists:suppliers,id',
        ]);

        $purchases = Purchase::with(['supplier', 'creator', 'items'])->latest();

        if (!empty($validated['supplier'])) {
            $purchases->where('supplier_id', $validated['supplier']);
        }

        return view('purchases.index', [
            'purchases' => $purchases->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('purchases.form', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'items' => InventoryItem::with('unit')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated, $request) {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'created_by' => auth()->id(),
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
                    'user_id' => auth()->id(),
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
                'user_id' => auth()->id(),
                'action' => 'purchase_recorded',
                'model_type' => Purchase::class,
                'model_id' => $purchase->id,
                'details' => 'Recorded purchase with total amount ' . number_format($total, 2),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('owner.purchases.index')->with('success', 'Purchase recorded successfully.');
    }
}
