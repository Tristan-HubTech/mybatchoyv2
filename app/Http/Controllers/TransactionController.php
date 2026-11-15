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
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'type' => 'nullable|in:stock_in,stock_out,adjustment,spoilage',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = InventoryTransaction::with(['inventoryItem.unit', 'user', 'supplier'])
            ->latest('transaction_date')
            ->latest('id');

        if (auth()->user()?->role?->name !== 'owner') {
            $query->where('user_id', auth()->id());
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', '%' . $search . '%')
                    ->orWhereHas('inventoryItem', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('sku', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($validated['type'])) {
            $query->where('transaction_type', $validated['type']);
        }

        if (!empty($validated['from'])) {
            $query->whereDate('transaction_date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('transaction_date', '<=', $validated['to']);
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function stockInForm()
    {
        return view('transactions.stock-in', [
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
        ]);
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

        $updatedItem = InventoryItem::with('unit')->find($validated['inventory_item_id']);
        $formattedQty = $this->formatQty((float) ($updatedItem?->current_quantity ?? 0));
        $unit = $updatedItem?->unit?->short_name ?: 'units';

        return redirect()->route($this->redirectRoute())->with(
            'success',
            "Stock successfully added for {$updatedItem?->name}. The new quantity is now {$formattedQty} {$unit}."
        );
    }

    public function stockOutForm()
    {
        return view('transactions.stock-out', [
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function stockOut(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'nullable|string|max:255',
        ], [
            'inventory_item_id.required' => 'Please choose an item first.',
            'quantity.required' => 'Please enter how much stock to remove.',
            'quantity.min' => 'Please enter an amount greater than zero.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::with('unit')->whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                $requested = $this->formatQty((float) $validated['quantity']);
                $available = $this->formatQty((float) $item->current_quantity);
                $unit = $item->unit?->short_name ?: 'units';
                throw ValidationException::withMessages([
                    'quantity' => "Cannot remove {$requested} {$unit}. Only {$available} {$unit} of {$item->name} is currently in stock.",
                ]);
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

        $updatedItem = InventoryItem::with('unit')->find($validated['inventory_item_id']);
        $formattedQty = $this->formatQty((float) ($updatedItem?->current_quantity ?? 0));
        $unit = $updatedItem?->unit?->short_name ?: 'units';

        return redirect()->route($this->redirectRoute())->with(
            'success',
            "Stock successfully removed for {$updatedItem?->name}. The remaining quantity is now {$formattedQty} {$unit}."
        );
    }

    public function adjustmentForm()
    {
        return view('transactions.adjustment', [
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function adjustment(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'current_quantity' => 'required|numeric|min:0|max:999999999.999',
            'reason' => 'required|string|max:255',
        ], [
            'inventory_item_id.required' => 'Please choose an item first.',
            'current_quantity.required' => 'Please enter the actual counted quantity.',
            'current_quantity.min' => 'Stock quantity cannot be negative.',
            'reason.required' => 'Please explain why the stock quantity is being corrected.',
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

        $updatedItem = InventoryItem::with('unit')->find($validated['inventory_item_id']);
        $formattedQty = $this->formatQty((float) ($updatedItem?->current_quantity ?? 0));
        $unit = $updatedItem?->unit?->short_name ?: 'units';

        return redirect()->route($this->redirectRoute())->with(
            'success',
            "Stock quantity corrected for {$updatedItem?->name}. The new quantity is now {$formattedQty} {$unit}."
        );
    }

    public function spoilageForm()
    {
        return view('transactions.spoilage', [
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function spoilage(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01|max:999999999.999',
            'reason' => 'required|string|max:255',
        ], [
            'inventory_item_id.required' => 'Please choose an ingredient first.',
            'quantity.required' => 'Please enter how much spoiled.',
            'quantity.min' => 'Please enter an amount greater than zero.',
            'reason.required' => 'Please explain what happened (for example: expired or damaged).',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::with('unit')->whereKey($validated['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->current_quantity < $validated['quantity']) {
                $requested = $this->formatQty((float) $validated['quantity']);
                $available = $this->formatQty((float) $item->current_quantity);
                $unit = $item->unit?->short_name ?: 'units';
                throw ValidationException::withMessages([
                    'quantity' => "Cannot record {$requested} {$unit} as spoilage. Only {$available} {$unit} of {$item->name} is currently in stock.",
                ]);
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

        $updatedItem = InventoryItem::with('unit')->find($validated['inventory_item_id']);
        $formattedQty = $this->formatQty((float) ($updatedItem?->current_quantity ?? 0));
        $unit = $updatedItem?->unit?->short_name ?: 'units';

        return redirect()->route($this->redirectRoute())->with(
            'success',
            "Spoilage recorded for {$updatedItem?->name}. The remaining quantity is now {$formattedQty} {$unit}."
        );
    }

    private function formatQty(float $quantity): string
    {
        $formatted = rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');

        return ($formatted === '' || $formatted === '-0') ? '0' : $formatted;
    }

    private function redirectRoute(): string
    {
        return auth()->user()?->role?->name === 'owner' ? 'owner.transactions.index' : 'staff.transactions.index';
    }
}
