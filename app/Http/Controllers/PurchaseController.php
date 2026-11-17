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
            'search' => 'nullable|string|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $purchases = Purchase::with(['supplier', 'creator', 'items.inventoryItem.unit'])
            ->latest('purchase_date')
            ->latest('id');

        if (!empty($validated['supplier'])) {
            $purchases->where('supplier_id', $validated['supplier']);
        }

        if ($search !== '') {
            $purchases->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($validated['from'])) {
            $purchases->whereDate('purchase_date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $purchases->whereDate('purchase_date', '<=', $validated['to']);
        }

        $totalRecords = (clone $purchases)->count();
        $combinedTotal = (float) (clone $purchases)->realized()->sum('total_amount');

        return view('purchases.index', [
            'purchases' => $purchases->paginate(15)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'totalRecords' => $totalRecords,
            'combinedTotal' => $combinedTotal,
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
            'fulfillment_mode' => 'nullable|in:direct_received,supplier_po',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'items.*.unit_cost' => 'required|numeric|min:0|max:999999999.99',
        ]);

        $fulfillmentMode = $validated['fulfillment_mode'] ?? 'direct_received';
        if ($fulfillmentMode === 'supplier_po' && empty($validated['supplier_id'])) {
            return back()->withInput()->withErrors([
                'supplier_id' => 'Please select a supplier when sending a Purchase Order to the Supplier Portal.',
            ]);
        }

        DB::transaction(function () use ($validated, $request, $fulfillmentMode) {
            $isSupplierPo = $fulfillmentMode === 'supplier_po';
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'created_by' => auth()->id(),
                'purchase_number' => 'PO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'purchase_date' => $validated['purchase_date'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
                'fulfillment_mode' => $fulfillmentMode,
                'supplier_status' => $isSupplierPo ? 'pending' : 'confirmed',
                'delivery_status' => $isSupplierPo ? 'awaiting_confirmation' : 'delivered',
                'received_in_inventory' => !$isSupplierPo,
                'delivered_at' => $isSupplierPo ? null : now(),
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

                if (!$isSupplierPo) {
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
                }

                $total = round($total + $lineTotal, 2);
            }

            $purchase->update(['total_amount' => $total]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $isSupplierPo ? 'purchase_order_assigned' : 'purchase_recorded',
                'model_type' => Purchase::class,
                'model_id' => $purchase->id,
                'details' => ($isSupplierPo ? 'Assigned PO to supplier' : 'Recorded purchase') . ' with total amount ' . number_format($total, 2),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('owner.purchases.index')->with(
            'success',
            $fulfillmentMode === 'supplier_po'
                ? 'Purchase Order created and sent to the Supplier Portal.'
                : 'Purchase recorded successfully.'
        );
    }

    public function markReceived(Request $request, Purchase $purchase)
    {
        if ($purchase->received_in_inventory) {
            return redirect()->route('owner.purchases.index')->withErrors([
                'purchase' => 'This purchase has already been received into inventory.',
            ]);
        }

        if ($purchase->supplier_status !== 'confirmed') {
            return redirect()->route('owner.purchases.index')->withErrors([
                'purchase' => 'Supplier must confirm the Purchase Order before inventory receipt.',
            ]);
        }

        DB::transaction(function () use ($purchase, $request): void {
            $purchase->loadMissing('items');
            foreach ($purchase->items as $line) {
                $inventoryItem = InventoryItem::whereKey($line->inventory_item_id)->lockForUpdate()->firstOrFail();
                $previousQuantity = round((float) $inventoryItem->current_quantity, 3);
                $updatedQuantity = round($previousQuantity + (float) $line->quantity, 3);
                $inventoryItem->update([
                    'current_quantity' => $updatedQuantity,
                    'last_counted_at' => now(),
                ]);

                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'user_id' => $request->user()->id,
                    'supplier_id' => $purchase->supplier_id,
                    'transaction_type' => 'stock_in',
                    'quantity' => $line->quantity,
                    'previous_quantity' => $previousQuantity,
                    'updated_quantity' => $updatedQuantity,
                    'reason' => 'Supplier PO Received (' . $purchase->purchase_number . ')',
                    'transaction_date' => now(),
                ]);
            }

            $purchase->update([
                'delivery_status' => 'delivered',
                'delivered_at' => $purchase->delivered_at ?? now(),
                'received_in_inventory' => true,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'purchase_received_into_inventory',
                'model_type' => Purchase::class,
                'model_id' => $purchase->id,
                'details' => "Owner received PO {$purchase->purchase_number} into inventory.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('owner.purchases.index')
            ->with('success', "Purchase Order {$purchase->purchase_number} marked received and stock updated.");
    }
}
