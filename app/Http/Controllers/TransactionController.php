<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index()
    {
        $query = InventoryTransaction::with(['inventoryItem', 'user'])->latest();

        if (auth()->user()?->role?->name !== 'owner') {
            $query->where('user_id', auth()->id());
        }

        $transactions = $query->get();

        return view('transactions.index', compact('transactions'));
    }

    public function stockInForm()
    {
        return view('transactions.stock-in', ['items' => InventoryItem::with('unit')->get()]);
    }

    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();
            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = $previousQuantity + (float) $validated['quantity'];

            $item->update(['current_quantity' => $newQuantity, 'last_counted_at' => now()]);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => auth()->id(),
                'transaction_type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $newQuantity,
                'reason' => $validated['reason'] ?? 'Stock in',
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'stock_in',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Stock in: ' . $validated['quantity'] . ' for ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route($this->redirectRoute())->with('success', 'Stock in recorded successfully.');
    }

    public function stockOutForm()
    {
        return view('transactions.stock-out', ['items' => InventoryItem::with('unit')->get()]);
    }

    public function stockOut(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock.']);
            }

            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = $previousQuantity - (float) $validated['quantity'];

            $item->update(['current_quantity' => max(0, $newQuantity), 'last_counted_at' => now()]);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => auth()->id(),
                'transaction_type' => 'stock_out',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => max(0, $newQuantity),
                'reason' => $validated['reason'] ?? 'Stock out',
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'stock_out',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Stock out: ' . $validated['quantity'] . ' from ' . $item->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route($this->redirectRoute())->with('success', 'Stock out recorded successfully.');
    }

    public function adjustmentForm()
    {
        return view('transactions.adjustment', ['items' => InventoryItem::with('unit')->get()]);
    }

    public function adjustment(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'current_quantity' => 'required|numeric|min:0|max:999999999.999',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();
            $previousQuantity = (float) $item->current_quantity;

            $item->update(['current_quantity' => (float) $validated['current_quantity'], 'last_counted_at' => now()]);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => auth()->id(),
                'transaction_type' => 'adjustment',
                'quantity' => (float) $validated['current_quantity'] - $previousQuantity,
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => (float) $validated['current_quantity'],
                'reason' => $validated['reason'],
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'stock_adjustment',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Adjustment for ' . $item->name . ': ' . $previousQuantity . ' -> ' . $validated['current_quantity'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route($this->redirectRoute())->with('success', 'Stock adjustment saved successfully.');
    }

    public function spoilageForm()
    {
        return view('transactions.spoilage', ['items' => InventoryItem::with('unit')->get()]);
    }

    public function spoilage(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Spoilage quantity cannot exceed current stock.']);
            }

            $previousQuantity = (float) $item->current_quantity;
            $newQuantity = $previousQuantity - (float) $validated['quantity'];

            $item->update(['current_quantity' => $newQuantity, 'last_counted_at' => now()]);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => auth()->id(),
                'transaction_type' => 'spoilage',
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'updated_quantity' => $newQuantity,
                'reason' => $validated['reason'],
                'transaction_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'spoilage',
                'model_type' => InventoryItem::class,
                'model_id' => $item->id,
                'details' => 'Spoilage for ' . $item->name . ': ' . $validated['quantity'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route($this->redirectRoute())->with('success', 'Spoilage recorded successfully.');
    }

    private function redirectRoute(): string
    {
        return auth()->user()?->role?->name === 'owner' ? 'owner.transactions.index' : 'staff.transactions.index';
    }
}
