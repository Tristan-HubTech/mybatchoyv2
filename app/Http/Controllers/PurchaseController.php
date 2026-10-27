<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'creator', 'items'])->latest();

        if ($request->filled('supplier')) {
            $purchases->where('supplier_id', $request->supplier);
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
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'created_by' => auth()->id(),
                'purchase_number' => 'PO-' . now()->format('YmdHis'),
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($validated['items'] as $itemData) {
                $inventoryItem = InventoryItem::findOrFail($itemData['inventory_item_id']);
                $quantity = (float) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = $quantity * $unitCost;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'unit_id' => $inventoryItem->unit_id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $inventoryItem->current_quantity += $quantity;
                $inventoryItem->save();

                $total += $lineTotal;
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
